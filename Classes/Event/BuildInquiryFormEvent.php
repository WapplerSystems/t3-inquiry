<?php

namespace WapplerSystems\Inquiry\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class BuildInquiryFormEvent
{
    private FormDefinition $formDefinition;
    private Section $fieldsetItem;
    private ?ServerRequestInterface $request = null;


    public function __construct(FormDefinition $formDefinition, Section $fieldsetItem, ?ServerRequestInterface $request = null)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetItem = $fieldsetItem;
        $this->request = $request;
    }

    public function getFieldsetItem(): Section
    {
        return $this->fieldsetItem;
    }


    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

}
