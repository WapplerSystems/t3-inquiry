<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormItemEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetProduct;
    private string $hash;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetProduct, string $hash)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetProduct = $fieldsetProduct;
        $this->hash = $hash;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetProduct(): Section
    {
        return $this->fieldsetProduct;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
