<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormContactEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetContact;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetContact)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetContact = $fieldsetContact;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetContact(): Section
    {
        return $this->fieldsetContact;
    }
}
