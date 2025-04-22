<?php

namespace WapplerSystems\Inquiry\Form\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Factory\AbstractFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridRow;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridColumn;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer;

class InquiryFormFactory extends AbstractFormFactory
{
    public function create(): Page
    {

        /** @var ConfigurationService $configurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration('standard');

        $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'inquiryFormPage', $prototypeConfiguration);
        $formDefinition->setRendererClassName(FluidFormRenderer::class);
        $formDefinition->setRenderingOption('controllerAction', 'show');
        $formDefinition->setRenderingOption('submitButtonLabel', 'send');
        $formDefinition->setRenderingOption('additionalParams', [
            'tx_inquiry_form' => [
                'job' => 'deed',
            ]
        ]);

        $page = $formDefinition->createPage('page1');

        $gridRow = $this->createGridRow('gridRow');


        $this->addFormElement(
            $page,
            type: 'GridRow',
            id: 'gridRow',
        );


        $leftColumn = $this->createGridColumn('leftColumn', 6);
        $leftColumn->addChild($this->createDropdown('requestType', 'Request Type', true));
        $leftColumn->addChild($this->createTextField('additionalInfo', 'Additional Information'));

        $rightColumn = $this->createGridColumn('rightColumn', 6);
        $rightColumn->addChild($this->createTextField('name', 'Name', true));
        $rightColumn->addChild($this->createEmailField('email', 'Email', true));
        $rightColumn->addChild($this->createTextField('phone', 'Phone'));
        $rightColumn->addChild($this->createTextField('company', 'Company'));
        $rightColumn->addChild($this->createTextField('country', 'Country'));

        // Füge die Spalten zur Zeile hinzu
        $gridRow->addChild($leftColumn);
        $gridRow->addChild($rightColumn);

        // Füge die Zeile zur Seite hinzu
        $page->addChild($gridRow);

        $this->triggerFormBuildingFinished($page);

        return $page;
    }

    private function createDropdown(string $identifier, string $label, bool $required = false): GenericFormElement
    {
        $dropdown = $this->createFormElement('Select', $identifier, $label);
        $dropdown->setProperties([
            'options' => [
                ['value' => 'option1', 'label' => 'Option 1'],
                ['value' => 'option2', 'label' => 'Option 2'],
                ['value' => 'option3', 'label' => 'Option 3'],
            ],
            'required' => $required,
        ]);
        return $dropdown;
    }

    private function createTextField(string $identifier, string $label, bool $required = false): GenericFormElement
    {
        $textField = $this->createFormElement('Text', $identifier, $label);
        $textField->setProperties([
            'required' => $required,
        ]);
        return $textField;
    }

    private function createEmailField(string $identifier, string $label, bool $required = false): GenericFormElement
    {
        $emailField = $this->createFormElement('Email', $identifier, $label);
        $emailField->setProperties([
            'required' => $required,
        ]);
        return $emailField;
    }

    private function createPage(string $identifier, string $label): Page
    {
        $page = new Page($identifier);
        $page->setLabel($label);
        return $page;
    }

    private function createGridRow(string $identifier): GridRow
    {
        $gridRow = new GridRow($identifier, 'GridRow');
        return $gridRow;
    }

    private function createFormElement(string $identifier, string $type, string $label): GenericFormElement
    {
        $formElement = new GenericFormElement($identifier, $type);
        $formElement->setLabel($label);
        return $formElement;
    }

    private function createGridColumn(string $identifier, int $width): GridColumn
    {
        $gridColumn = new GridColumn($identifier, $width);
        return $gridColumn;

    }

    private function addFormElement(
        AbstractSection $section,
        string          $type,
        string          $id,
        ?string         $label = null,
        mixed           $defaultValue = null,
        ?array          $properties = null,
        ?array          $renderingOptions = null,
        ?array          $validators = null
    ): AbstractFormElement
    {
        /** @var AbstractFormElement $element */
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
