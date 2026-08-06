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
use TYPO3\CMS\Form\Domain\Model\FormElements\GridRow;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer;
use WapplerSystems\Inquiry\Domain\Model\RequestTextTemplate;
use WapplerSystems\Inquiry\Domain\Repository\RequestTextTemplateRepository;
use WapplerSystems\Inquiry\Event\BuildInquiryQuickFormContactEvent;
use WapplerSystems\Inquiry\Event\CreateConfirmationFinisherEvent;
use WapplerSystems\Inquiry\Event\CreateEmailToReceiverFinisherEvent;

class QuickInquiryFormFactory extends AbstractFormFactory
{
    use FormElementTrait;

    public function __construct(
        readonly private RequestTextTemplateRepository $requestTextTemplateRepository,
        private EventDispatcherInterface               $inquiryEventDispatcher,
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
        /** @var ConfigurationService $configurationService */
        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration('standard');

        $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'quickInquiryForm', $prototypeConfiguration);
        $formDefinition->setRendererClassName(FluidFormRenderer::class);
        $formDefinition->setRenderingOption('controllerAction', 'form');
        $formDefinition->setRenderingOption('additionalParams', [
            'item-uid' => $configuration['itemData']['uid'],
            'item-type' => $configuration['itemData']['type'],
        ]);
        $formDefinition->setRenderingOption('pageType', $configuration['quickInquiryFormPageType']);

        $resolver = GeneralUtility::makeInstance(ValidatorResolver::class);

        /** @var Page $page */
        $page = $formDefinition->createPage('page1');

        $this->addFormElement(
            $page,
            type: 'ItemName',
            id: 'itemName',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:quickInquiryForm.item-name'),
            defaultValue: $configuration['resolvedItem']->getTitle(),
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 1500
                ],
                'item' => $configuration['resolvedItem']
            ]
        );

        $this->addFormElement(
            $page,
            type: 'Textarea',
            id: 'Custom',
//            label: 'Do you have any special requests?',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:quickInquiryForm.special-request'),
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 1500
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 1500])
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
                            'numbersOfColumnsToUse' => 6
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );

        /** @var GridRow $gridRowLeftColumn */
        $gridRowLeftColumn = $this->addFormElement(
            $leftColumn,
            type: 'GridRow',
            id: 'gridRowLeftColumn',
        );

        /** @var Section $leftNameColumn */
        $leftNameColumn = $this->addFormElement(
            $gridRowLeftColumn,
            type: 'Fieldset',
            id: 'leftNameColumn',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );

        /** @var Section $rightNameColumn */
        $rightNameColumn = $this->addFormElement(
            $gridRowLeftColumn,
            type: 'Fieldset',
            id: 'rightNameColumn',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );

        /** @var Section $rightColumn */
        $rightColumn = $this->addFormElement(
            $gridRow,
            type: 'Fieldset',
            id: 'rightColumn',
        );

        $this->addFormElement(
            $rightColumn,
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

        /** @var GridRow $gridRowRightColumn */
        $gridRowRightColumn = $this->addFormElement(
            $rightColumn,
            type: 'GridRow',
            id: 'gridRowRightColumn',
        );

        /** @var Section $leftAddressColumn */
        $leftAddressColumn = $this->addFormElement(
            $gridRowRightColumn,
            type: 'Fieldset',
            id: 'leftAddressColumn',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 9
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );

        /** @var Section $rightAddressColumn */
        $rightAddressColumn = $this->addFormElement(
            $gridRowRightColumn,
            type: 'Fieldset',
            id: 'rightAddressColumn',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 3
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );


        $this->addFormElement(
            $leftNameColumn,
            type: 'Text',
            id: 'salutation',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.salutation.properties.label'),
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 50
                ]
            ],
            validators: [
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 50])
            ]
        );

        $this->addFormElement(
            $rightNameColumn,
            type: 'Text',
            id: 'firstname',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.firstname.properties.label'),
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
            $leftColumn,
            type: 'Text',
            id: 'lastname',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.lastname.properties.label'),
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
            $leftColumn,
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
            $leftColumn,
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
            $leftAddressColumn,
            type: 'Text',
            id: 'street',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.street.properties.label'),
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
            $rightAddressColumn,
            type: 'Text',
            id: 'housenumber',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.housenumber.properties.label'),
            properties: [
                'fluidAdditionalAttributes' => [
                    'maxlength' => 300
                ]
            ],
            validators: [
                $resolver->createValidator(NotEmptyValidator::class),
                $resolver->createValidator(StringLengthValidator::class, ['maximum' => 30])
            ]
        );

        /** @var GridRow $gridRow */
        $gridRow2 = $this->addFormElement(
            $rightColumn,
            type: 'GridRow',
            id: 'gridRow2',
        );
        /** @var Section $rightColumn */
        $leftColumn2 = $this->addFormElement(
            $gridRow2,
            type: 'Fieldset',
            id: 'leftColumn2',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );
        /** @var Section $rightColumn */
        $rightColumn2 = $this->addFormElement(
            $gridRow2,
            type: 'Fieldset',
            id: 'rightColumn2',
            properties: [
                'gridColumnClassAutoConfiguration' => [
                    'viewPorts' => [
                        'xxl' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'xl' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'lg' => [
                            'numbersOfColumnsToUse' => 6
                        ],
                        'md' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'sm' => [
                            'numbersOfColumnsToUse' => 12
                        ],
                        'xs' => [
                            'numbersOfColumnsToUse' => 12
                        ]
                    ]
                ]
            ]
        );
        $this->addFormElement(
            $leftColumn2,
            type: 'Text',
            id: 'zipcode',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.zipcode.properties.label'),
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
            $rightColumn2,
            type: 'Text',
            id: 'city',
            label: LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/form.xlf:element.city.properties.label'),
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

        $this->inquiryEventDispatcher->dispatch(new BuildInquiryQuickFormContactEvent($formDefinition, $gridRow, $request));

        $recipients = [];
        foreach ($configuration['recipients'] ?? [] as $recipient) {
            $recipients[$recipient['container']['address']] = $recipient['container']['name'];
        }
        $replyToRecipients = [
            '{email}' => '{firstname} {lastname}'
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
        $this->inquiryEventDispatcher->dispatch(new CreateEmailToReceiverFinisherEvent($emailToReceiver, $configuration));

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
        $this->inquiryEventDispatcher->dispatch(new CreateConfirmationFinisherEvent($confirmationFinisher));


        $this->triggerFormBuildingFinished($formDefinition);

        return $formDefinition;
    }

}
