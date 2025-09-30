<?php

namespace WapplerSystems\Inquiry\Form\Factory;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Factory\AbstractFormFactory;
use TYPO3\CMS\Form\Domain\Model\Exception\FinisherPresetNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridRow;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WapplerSystems\Inquiry\Domain\Model\RequestTextTemplate;
use WapplerSystems\Inquiry\Domain\Repository\RequestTextTemplateRepository;
use WapplerSystems\Inquiry\Event\BuildInquiryFormEvent;
use WapplerSystems\Inquiry\Event\BuildInquiryFormProductEvent;
use WapplerSystems\Inquiry\Event\ResolveItemsEvent;

class InquiryFormFactory extends AbstractFormFactory
{


    public function __construct(
        readonly private RequestTextTemplateRepository $requestTextTemplateRepository,
        private EventDispatcherInterface $eventDispatcher,
    )
    {

    }

    /**
     * @param array $configuration
     * @param string|null $prototypeName
     * @param ServerRequestInterface|null $request
     * @return FormDefinition
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws FinisherPresetNotFoundException
     */
    public function build(array $configuration, ?string $prototypeName = null, ?ServerRequestInterface $request = null): FormDefinition
    {

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();


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
        $resolver = GeneralUtility::makeInstance(ValidatorResolver::class);

        /** @var Page $page */
        $page = $formDefinition->createPage('page1');

        /** @var GridRow $gridRow */
        $gridRow = $this->addFormElement(
            $page,
            type: 'GridRow',
            id: 'gridRow',
        );


        /** @var Section $leftColumn */
        $leftColumn = $this->addFormElement(
            $gridRow,
            type: 'Fieldset',
            id: 'leftColumn',
        );




        $requestTextTemplates = $this->requestTextTemplateRepository->findAll();
        $requestTextTemplatesOptions = [];
        DebugUtility::debug($requestTextTemplates);
        /** @var RequestTextTemplate $requestTextTemplate */
        foreach ($requestTextTemplates as $requestTextTemplate) {
            $requestTextTemplatesOptions[$requestTextTemplate->getUid()] = $requestTextTemplate->getTitle();
        }
        DebugUtility::debug($requestTextTemplatesOptions);

        $items = [];
        if ($userSession->get('items')) {
            $items = $userSession->get('items');
        }

        $event = new ResolveItemsEvent($items);
        $this->eventDispatcher->dispatch($event);

        $resolvedItems = $event->getResolvedItems();
        DebugUtility::debug($resolvedItems, 'resolvedItems');


        $i = 0;
        foreach ($items as $key => $value) {

            /** @var Section $fieldsetProduct */
            $fieldsetProduct = $this->addFormElement(
                $leftColumn,
                type: 'Fieldset',
                id: 'fieldsetProduct'.$i,
            );


            $this->addFormElement(
                $fieldsetProduct,
                type: 'SingleSelect',
                id: 'requestType'.$i,
                label: 'requestType',
                properties: [
                    'options' => $requestTextTemplatesOptions,
                ],
                validators: [
                    $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300])
                ]
            );

            $this->addFormElement(
                $fieldsetProduct,
                type: 'Textarea',
                id: 'message'.$i,
                label: 'message',
                properties: [
                    'fluidAdditionalAttributes' => [
                        'maxlength' => 1000
                    ]
                ],
                validators: [
                    $resolver->createValidator(StringLengthValidator::class, ['maximum' => 1000])
                ]
            );

            $this->eventDispatcher->dispatch(new BuildInquiryFormProductEvent($formDefinition, $fieldsetProduct));


            $i++;
        }


        /** @var Section $rightColumn */
        $rightColumn = $this->addFormElement(
            $gridRow,
            type: 'Fieldset',
            id: 'rightColumn',
        );


        $this->addFormElement(
            $rightColumn,
            type: 'Text',
            id: 'name',
            label: 'name',
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 300
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300])
            ]
        );

        $this->addFormElement(
            $rightColumn,
            type: 'Email',
            id: 'email',
            label: 'email',
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 300
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300]),
                $resolver->createValidator(EmailAddressValidator::class)
            ]
        );
        $this->addFormElement(
            $rightColumn,
            type: 'Text',
            id: 'phonenumber',
            label: 'phonenumber',
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 20
                ]
            ],
            validators: [
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 20])
            ]
        );
        $this->addFormElement(
            $rightColumn,
            type: 'Text',
            id: 'company',
            label: 'company',
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 300
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300])
            ]
        );
        $this->addFormElement(
            $rightColumn,
            type: 'Text',
            id: 'country',
            label: 'country',
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 300
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300])
            ]
        );


        $this->triggerFormBuildingFinished($formDefinition);

        return $formDefinition;


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
        return new GridRow($identifier, 'GridRow');
    }

    private function createFormElement(string $identifier, string $type, string $label): GenericFormElement
    {
        $formElement = new GenericFormElement($identifier, $type);
        $formElement->setLabel($label);
        return $formElement;
    }

    private function createGridColumn(string $identifier, int $width): GridColumn
    {
        return new GridColumn($identifier, $width);

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
