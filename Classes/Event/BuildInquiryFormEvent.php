<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class BuildInquiryFormEvent
{
    private FormDefinition $formDefinition;
    private Section $fieldsetItem;



    public function __construct(FormDefinition $formDefinition, Section &$fieldsetItem)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetItem = $fieldsetItem;
    }

    public function getFieldsetItem(): Section
    {
        return $this->fieldsetItem;
    }


    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }
}
