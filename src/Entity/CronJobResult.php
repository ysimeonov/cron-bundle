<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use DateTime;
use DateTimeInterface;

use function sprintf;

class CronJobResult extends AbstractEntity
{
    public const SUCCEEDED = 'succeeded';
    public const FAILED    = 'failed';
    public const SKIPPED   = 'skipped';

    private DateTimeInterface $runAt;

    private float $runTime;

    private int $statusCode;

    private ?string $output;

    private CronJob $cronJob;

    public function __construct(
        CronJob $cronJob,
        float $runTime,
        int $statusCode,
        ?string $output
    ) {
        parent::__construct();

        $this->runTime    = $runTime;
        $this->statusCode = $statusCode;
        $this->output     = $output;
        $this->cronJob    = $cronJob;
        $this->runAt      = new DateTime();
    }

    public function getRunAt(): DateTimeInterface
    {
        return $this->runAt;
    }

    public function getRunTime(): float
    {
        return $this->runTime;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getCronJob(): CronJob
    {
        return $this->cronJob;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s',
            $this->getCronJob()->getCommand(),
            $this->getRunAt()->format('d.m.Y H:i P')
        );
    }
}
