<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormItemEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetItem;
    private string $hash;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetItem, string $hash)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetItem = $fieldsetItem;
        $this->hash = $hash;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetItem(): Section
    {
        return $this->fieldsetItem;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
