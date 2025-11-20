<?php

namespace WapplerSystems\Inquiry\Event;

use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class BuildInquiryFormEvent
{
    private FormDefinition $formDefinition;
    private Section $fieldsetProduct;



    public function __construct(FormDefinition $formDefinition, Section &$fieldsetProduct)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetProduct = $fieldsetProduct;
    }

    public function getFieldsetProduct(): Section
    {
        return $this->fieldsetProduct;
    }


    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }
}
