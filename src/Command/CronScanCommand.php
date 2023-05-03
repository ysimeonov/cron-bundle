<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Manager\CronJobManager;
use Shapecode\Bundle\CronBundle\Model\CronJobMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_search;
use function count;
use function in_array;
use function sprintf;

final class CronScanCommand extends BaseCommand
{
    private CronJobManager $cronJobManager;

    public function __construct(
        CronJobManager $manager,
        ManagerRegistry $registry
    ) {
        $this->cronJobManager = $manager;

        parent::__construct($registry);
    }

    protected function configure(): void
    {
        $this
            ->setName('shapecode:cron:scan')
            ->setDescription('Scans for any new or deleted cron jobs');

        $this
            ->addOption('keep-deleted', 'k', InputOption::VALUE_NONE, 'If set, deleted cron jobs will not be removed')
            ->addOption('default-disabled', 'd', InputOption::VALUE_NONE, 'If set, new jobs will be disabled by default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CronStyle($input, $output);
        $io->comment(sprintf('Scan for cronjobs started at %s', (new DateTime())->format('r')));
        $io->title('scanning ...');

        $keepDeleted     = (bool) $input->getOption('keep-deleted');
        $defaultDisabled = (bool) $input->getOption('default-disabled');

        // Enumerate the known jobs
        $jobRepo   = $this->getCronJobRepository();
        $knownJobs = $jobRepo->getKnownJobs();
        $em        = $this->getManager();

        $counter = [];
        foreach ($this->cronJobManager->getJobs() as $jobMetadata) {
            $command = $jobMetadata->command;

            $io->section($command);

            if (! isset($counter[$command])) {
                $counter[$command] = 0;
            }

            $counter[$command]++;

            if (in_array($command, $knownJobs, true)) {
                // Clear it from the known jobs so that we don't try to delete it
                unset($knownJobs[array_search($command, $knownJobs, true)]);

                // Update the job if necessary
                $currentJob = $jobRepo->findOneByCommand($command, $counter[$command]);

                if ($currentJob === null) {
                    continue;
                }

                $currentJob->setDescription($jobMetadata->description);
                $currentJob->setArguments($jobMetadata->arguments);

                $io->text(sprintf('command: %s', $jobMetadata->command));
                $io->text(sprintf('arguments: %s', $jobMetadata->arguments));
                $io->text(sprintf('expression: %s', $jobMetadata->expression));
                $io->text(sprintf('instances: %s', $jobMetadata->maxInstances));

                if (
                    $currentJob->getPeriod() !== $jobMetadata->expression ||
                    $currentJob->getMaxInstances() !== $jobMetadata->maxInstances ||
                    $currentJob->getArguments() !== $jobMetadata->arguments
                ) {
                    $currentJob->setPeriod($jobMetadata->expression);
                    $currentJob->setArguments($jobMetadata->arguments);
                    $currentJob->setMaxInstances($jobMetadata->maxInstances);

                    $currentJob->calculateNextRun();
                    $io->notice('cronjob updated');
                }
            } else {
                $this->newJobFound($io, $jobMetadata, $defaultDisabled, $counter[$command]);
            }
        }

        $io->success('Finished scanning for cronjobs');

        // Clear any jobs that weren't found
        if ($keepDeleted === false) {
            $io->title('remove cronjobs');

            if (count($knownJobs) > 0) {
                foreach ($knownJobs as $deletedJob) {
                    $io->notice(sprintf('Deleting job: %s', $deletedJob));
                    $jobsToDelete = $jobRepo->findByCommand($deletedJob);
                    foreach ($jobsToDelete as $jobToDelete) {
                        $em->remove($jobToDelete);
                    }
                }
            } else {
                $io->info('No cronjob has to be removed.');
            }
        }

        $em->flush();

        return static::SUCCESS;
    }

    private function newJobFound(CronStyle $io, CronJobMetadata $metadata, bool $defaultDisabled, int $counter): void
    {
        $newJob =
            CronJob::create(
                $metadata->command,
                $metadata->expression
            )
            ->setArguments($metadata->arguments)
            ->setDescription($metadata->description)
            ->setEnable(! $defaultDisabled)
            ->setNumber($counter)
            ->calculateNextRun();

        $message = sprintf('Found new job: "%s" with period %s', $newJob->getFullCommand(), $newJob->getPeriod());
        $io->success($message);

        $this->getManager()->persist($newJob);
    }
}
