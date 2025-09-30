<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormProductEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetProduct;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetProduct)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetProduct = $fieldsetProduct;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetProduct(): Section
    {
        return $this->fieldsetProduct;
    }
}
