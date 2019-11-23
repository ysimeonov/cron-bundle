<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Service;

use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Entity\CronJobResultInterface;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepositoryInterface;

class CronJobResultService implements CronJobResultServiceInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $pruneInterval;

    public function __construct(ManagerRegistry $registry, string $pruneInterval)
    {
        $this->registry      = $registry;
        $this->pruneInterval = $pruneInterval;
    }

    public function prune() : void
    {
        $time = new DateTime($this->pruneInterval);

        /** @var CronJobResultRepositoryInterface $repo */
        $repo = $this->registry->getRepository(CronJobResultInterface::class);

        $repo->deleteOldLogs($time);
    }
}
