<?php

namespace WapplerSystems\Inquiry\Event;


use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;

class CreateEmailToReceiverFinisherEvent
{
    private FinisherInterface $finisher;

    public function __construct(FinisherInterface $finisher)
    {
        $this->finisher = $finisher;
    }

    public function getFinisher(): FinisherInterface
    {
        return $this->finisher;
    }

}
