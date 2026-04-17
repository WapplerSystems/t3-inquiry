<?php

namespace WapplerSystems\Inquiry\Form\Factory;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
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
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridRow;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WapplerSystems\Inquiry\Domain\Model\RequestTextTemplate;
use WapplerSystems\Inquiry\Domain\Repository\RequestTextTemplateRepository;
use WapplerSystems\Inquiry\Event\BuildInquiryFormContactEvent;
use WapplerSystems\Inquiry\Event\BuildInquiryFormItemEvent;
use WapplerSystems\Inquiry\Event\CreateConfirmationFinisherEvent;
use WapplerSystems\Inquiry\Event\CreateEmailToReceiverFinisherEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;

class InquiryFormFactory extends AbstractFormFactory
{
    use FormElementTrait;


    public function __construct(
        readonly private RequestTextTemplateRepository $requestTextTemplateRepository,
        private EventDispatcherInterface               $eventDispatcher,
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

        $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'inquiryForm', $prototypeConfiguration);
        $formDefinition->setRendererClassName(FluidFormRenderer::class);
        $formDefinition->setRenderingOption('controllerAction', 'form');
        $resolver = GeneralUtility::makeInstance(ValidatorResolver::class);

        /** @var Page $page */
        $page = $formDefinition->createPage('page1');

        $this->addFormElement(
            $page,
            type: 'Hidden',
            id: 'completed',
            validators: [
                $resolver->createValidator(NotEmptyValidator::class)
            ]
        );

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
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 8
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 8
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 8
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                    ]
                ]
            ],
        );


        $requestTextTemplates = $this->requestTextTemplateRepository->findAll();
        $requestTextTemplatesOptions = [];
        /** @var RequestTextTemplate $requestTextTemplate */
        foreach ($requestTextTemplates as $requestTextTemplate) {
            $requestTextTemplatesOptions[$requestTextTemplate->getUid()] = $requestTextTemplate->getTitle();
        }

        $items = [];
        if ($userSession->get('items')) {
            $items = $userSession->get('items');
        }


        $i = 0;
        foreach ($items as $key => $item) {

            $hash = $item['hash'];
            $event = new ResolveItemEvent($item['uid'], $item['type'], $request);
            $this->eventDispatcher->dispatch($event);

            if ($event->getResolvedObject() === null) {
                continue;
            }

            /** @var Section $fieldsetItem */
            $fieldsetItem = $this->addFormElement(
                $leftColumn,
                type: 'InquiryItemFieldset',
                id: 'fieldsetItem_' . $hash,
                properties: [
                    'hash' => $hash,
                    'uid' => $item['uid'],
                    'type' => $item['type'],
                    'event' => $event
                ],
                label: $event->getResolvedName(),
            );


            $this->addFormElement(
                $fieldsetItem,
                //type: 'RequestTypeSelect',
                type: 'SingleSelect',
                id: 'requestType_' . $hash,
                label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:inquiryForm.element.requestType.properties.label'),
                properties: [
                    'options' => $requestTextTemplatesOptions,
                    'prependOptionLabel' => LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:inquiryForm.element.requestType.properties.prependOptionLabel')
                ],
                validators: [
                    $resolver->createValidator(StringLengthValidator::class, ['maximum' => 300])
                ]
            );

            $this->addFormElement(
                $fieldsetItem,
                type: 'Textarea',
                id: 'message_' . $hash,
                label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:inquiryForm.element.message.properties.label'),
                properties: [
                    'fluidAdditionalAttributes' => [
                        'maxlength' => 1000
                    ]
                ],
                validators: [
                    $resolver->createValidator(StringLengthValidator::class, ['maximum' => 1000])
                ]
            );

            $this->addFormElement(
                $fieldsetItem,
                type: 'Hidden',
                id: 'itemUid_' . $hash,
                defaultValue: $item['uid'],
                validators: [
                    $resolver->createValidator(NotEmptyValidator::class)
                ]
            );

            $this->addFormElement(
                $fieldsetItem,
                type: 'Hidden',
                id: 'itemType_' . $hash,
                defaultValue: $item['type']
            );

            $this->eventDispatcher->dispatch(new BuildInquiryFormItemEvent($formDefinition, $fieldsetItem, $hash, $request));

            $i++;
        }


        /** @var Section $fieldsetContact */
        $fieldsetContact = $this->addFormElement(
            $gridRow,
            type: 'Fieldset',
            id: 'fieldsetContact',
        );


        $this->addFormElement(
            $fieldsetContact,
            type: 'Text',
            id: 'name',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.name.properties.label'),
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
            $fieldsetContact,
            type: 'Email',
            id: 'email',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.email.properties.label'),
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
            $fieldsetContact,
            type: 'Text',
            id: 'phonenumber',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.phonenumber.properties.label'),
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
            $fieldsetContact,
            type: 'Text',
            id: 'company',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.company.properties.label'),
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
            $fieldsetContact,
            type: 'Text',
            id: 'country',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.country.properties.label'),
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

        $this->eventDispatcher->dispatch(new BuildInquiryFormContactEvent($formDefinition, $fieldsetContact, $request));

        $recipients = [];
        foreach ($configuration['recipients'] ?? [] as $recipient) {
            $recipients[$recipient['container']['address']] = $recipient['container']['name'];
        }
        $replyToRecipients = [
            '{email}' => '{name}'
        ];

        $mailSettings = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];

        $emailToReceiver = $formDefinition->createFinisher('EmailToReceiver');
        $emailToReceiver->setOptions([
            'subject' => $configuration['subject'] ?? 'Mail from inquiry form',
            'recipients' => $recipients,
            'senderName' => $mailSettings['defaultMailFromName'],
            'senderAddress' => $mailSettings['defaultMailFromAddress'],
            'replyToRecipients' => $replyToRecipients,
            'templateName' => 'MailToReceiver',
            'templateRootPaths' => [
                34240 => 'EXT:inquiry/Resources/Private/Extensions/Form/Frontend/Templates/Finisher/EmailToReceiver/',
            ]
        ]);
        $this->eventDispatcher->dispatch(new CreateEmailToReceiverFinisherEvent($emailToReceiver, $configuration));

        $inquiryFinisher = $formDefinition->createFinisher('Inquiry');
        $inquiryFinisher->setOptions([
            'emptyList' => ($configuration['emptyList']  ?? '1') === '1',
        ]);


        $confirmationFinisher = $formDefinition->createFinisher('Confirmation');
        $confirmationFinisher->setOptions([
            'message' => LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:confirmation.message'),
            'templateName' => 'Confirmation',
            'templateRootPaths' => [
                10 => 'EXT:inquiry/Resources/Private/Extensions/Form/Frontend/Templates/Finisher/Confirmation/',
            ]
        ]);
        $this->eventDispatcher->dispatch(new CreateConfirmationFinisherEvent($confirmationFinisher));


        $this->triggerFormBuildingFinished($formDefinition);

        return $formDefinition;
    }

}
