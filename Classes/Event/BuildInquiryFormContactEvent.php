<?php

namespace WapplerSystems\Inquiry\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormContactEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetContact;

    private ?ServerRequestInterface $request = null;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetContact, ?ServerRequestInterface $request = null)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetContact = $fieldsetContact;
        $this->request = $request;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetContact(): Section
    {
        return $this->fieldsetContact;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }
}
