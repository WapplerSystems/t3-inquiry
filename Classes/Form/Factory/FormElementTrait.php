<?php

namespace WapplerSystems\Inquiry\Form\Factory;

use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;

trait FormElementTrait
{
    private function addFormElement(
        AbstractSection $section,
        string          $type,
        string          $id,
        ?string         $label = null,
        mixed           $defaultValue = null,
        ?array          $properties = null,
        ?array          $renderingOptions = null,
        ?array          $validators = null
    ): AbstractRenderable
    {
        /** @var AbstractRenderable $element */
        $element = $section->createElement($id, $type);

        if (isset($label)) $element->setLabel($label);
        if (isset($defaultValue)) $element->setDefaultValue($defaultValue);
        if (isset($properties)) {
            foreach ($properties as $key => $value) {
                $element->setProperty($key, $value);
            }
        }
        if (isset($renderingOptions)) {
            foreach ($renderingOptions as $key => $value) {
                $element->setRenderingOption($key, $value);
            }
        }
        if (isset($validators)) {
            foreach ($validators as $validator) {
                $element->addValidator($validator);
            }
        }

        return $element;
    }
}