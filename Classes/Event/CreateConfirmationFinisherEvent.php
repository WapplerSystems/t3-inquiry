<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;

class CreateConfirmationFinisherEvent
{
    private FinisherInterface $finisher;
    private ?ServerRequestInterface $request = null;

    public function __construct(FinisherInterface $finisher, ?ServerRequestInterface $request = null)
    {
        $this->finisher = $finisher;
        $this->request = $request;
    }

    public function getFinisher(): FinisherInterface
    {
        return $this->finisher;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

}
