<?php

namespace WapplerSystems\Inquiry\Event;


use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;

class CreateEmailToReceiverFinisherEvent
{
    private FinisherInterface $finisher;
    private array $configuration;

    public function __construct(FinisherInterface $finisher, array $configuration)
    {
        $this->finisher = $finisher;
        $this->configuration = $configuration;
    }

    public function getFinisher(): FinisherInterface
    {
        return $this->finisher;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

}
