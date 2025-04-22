<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class BuildInquiryFormEvent
{
    private FormDefinition $formDefinition;

    public function __construct(FormDefinition $formDefinition)
    {
        $this->formDefinition = $formDefinition;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }
}
