<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use Cron\CronExpression;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CronJob extends AbstractEntity
{
    private string $command;

    private ?string $arguments = null;

    private ?string $description = null;

    private int $runningInstances = 0;

    private int $maxInstances = 1;

    private int $number = 1;

    private string $period;

    private ?DateTimeInterface $lastUse = null;

    private DateTimeInterface $nextRun;

    /**
     * @var Collection<int, CronJobResult>
     */
    private Collection $results;

    protected $enable = true;

    public function __construct(
        string $command,
        string $period
    ) {
        parent::__construct();

        $this->command = $command;
        $this->period  = $period;
        $this->results = new ArrayCollection();

        $this->calculateNextRun();
    }

    public static function create(string $command, string $period): self
    {
        return new self($command, $period);
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getFullCommand(): string
    {
        $arguments = '';

        if ($this->getArguments() !== null) {
            $arguments = ' ' . $this->getArguments();
        }

        return $this->getCommand() . $arguments;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getRunningInstances(): int
    {
        return $this->runningInstances;
    }

    public function increaseRunningInstances(): self
    {
        ++$this->runningInstances;

        return $this;
    }

    public function decreaseRunningInstances(): self
    {
        --$this->runningInstances;

        return $this;
    }

    public function getMaxInstances(): int
    {
        return $this->maxInstances;
    }

    public function setMaxInstances(int $maxInstances): self
    {
        $this->maxInstances = $maxInstances;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    public function setPeriod(string $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getLastUse(): ?DateTimeInterface
    {
        return $this->lastUse;
    }

    public function setLastUse(DateTimeInterface $lastUse): self
    {
        $this->lastUse = $lastUse;

        return $this;
    }

    public function setNextRun(DateTimeInterface $nextRun): self
    {
        $this->nextRun = $nextRun;

        return $this;
    }

    public function getNextRun(): DateTimeInterface
    {
        return $this->nextRun;
    }

    /**
     * @return Collection<int, CronJobResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function setEnable(bool $enable): self
    {
        $this->enable = $enable;

        return $this;
    }

    public function isEnable(): bool
    {
        return $this->enable;
    }

    public function calculateNextRun(): self
    {
        $cron = new CronExpression($this->getPeriod());
        $this->setNextRun($cron->getNextRunDate());

        return $this;
    }

    public function __toString(): string
    {
        return $this->getCommand();
    }
}
