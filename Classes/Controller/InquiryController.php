<?php

namespace WapplerSystems\Inquiry\Controller;

use Doctrine\DBAL\ParameterType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

class InquiryController extends ActionController
{


    public function __construct(EventDispatcherInterface               $eventDispatcher)
    {
    }


    public function formPlaceholderAction(): ResponseInterface
    {


        return $this->htmlResponse();
    }


    public function formAction(): ResponseInterface
    {



        return $this->htmlResponse();
    }

    /**
     *
     * @param string $hash
     * @return ResponseInterface
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function confirmAction(string $hash = ''): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        $contentUid = $currentContentObject->data['uid'];

        if (($this->settings['form'] ?? '') === '') {
            return $this->htmlResponse($this->view->renderSection('Error', ['error' => 'No form configuration found.']));
        }

        if ($hash !== '') {
            /** @var ConfirmationRequest $confirmationRequest */
            $confirmationRequest = $this->confirmationRequestRepository->findOneByConfirmationHash($hash);
            /** @var \DateTimeImmutable $currentDateTime */
            $currentDateTime = $context->getPropertyFromAspect('date', 'full');
            $confirmationRequest->setConfirmationDate(\DateTime::createFromImmutable($currentDateTime));
            $this->confirmationRequestRepository->update($confirmationRequest);

            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();


            if ($confirmationRequest) {

                $feUser = $this->confirmationService->requestToFeUser($confirmationRequest, $this->settings);
                if ($feUser === null) {
                    return $this->htmlResponse($this->view->renderSection('Error', ['error' => 'No user loadable or creatable.']));
                }
                if (($feUser['registration_completed'] ?? 0) === 1) {
                    return $this->htmlResponse($this->view->renderSection('AlreadyCompleted'));
                }

                $completeRegistrationFinisher = [
                    'identifier' => 'CompleteRegistration',
                    'options' => [
                        'confirmationRequest' => $confirmationRequest,
                        'feUserUid' => $feUser['uid'],
                        'settings' => $this->settings
                    ]
                ];

                $notificationEmailReceipients = [];
                if ((int)($this->settings['notificationEmails']['registrationCompleted']['emailAddresses'] ?? 0) > 0) {
                    $addresses = $this->emailAddressRepository->findByTablenameAndUidForeignAndFieldname('tt_content', $contentUid, 'settings.notificationEmails.registrationCompleted.emailAddresses');
                    /** @var EmailAddress $address */
                    foreach ($addresses as $address) {
                        $notificationEmailReceipients[$address->getEmail()] = $address->getName();
                    }
                }
                $notificationEmailFinisher = [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'senderAddress' => $this->settings['notificationEmails']['senderEmailAddress'] ?? '',
                        'senderName' => $this->settings['notificationEmails']['senderName'] ?? '',
                        'useFluidEmail' => $this->settings['notificationEmails']['useFluidEmail'] ?? 0,
                        'subject' => LocalizationUtility::translate('LLL:EXT:fe_registration/Resources/Private/Language/locallang.xlf:notificationEmail.subject'),
                        'recipients' => $notificationEmailReceipients,
                        'templateName' => 'Email/Notification/RegistrationCompleted',
                        'variables' => [
                            'user' => $feUser,
                        ]
                    ]
                ];
                $redirectFinisher = [
                    'identifier' => 'RedirectToUri',
                    'options' => [
                        'uri' => $this->uriBuilder->reset()->uriFor('success'),
                    ]
                ];

                $finishers = [];
                if (count($notificationEmailReceipients) > 0) {
                    $finishers['NotificationEmail'] = $notificationEmailFinisher;
                }
                $finishers['CompleteRegistration'] = $completeRegistrationFinisher;
                $finishers['RedirectToUri'] = $redirectFinisher;

                $overrideConfiguration = [
                    'finishers' => $finishers,
                    'renderingOptions' => [
                        'controllerAction' => 'confirm',
                        'additionalParams' => ['tx_feregistration_registration' => ['hash' => $hash]],
                        'submitButtonLabel' => LocalizationUtility::translate('LLL:EXT:fe_registration/Resources/Private/Language/locallang.xlf:btn.completeRegistration'),
                    ]
                ];

                $factory = GeneralUtility::makeInstance(RegistrationPatchFormFactory::class, $this->settings, $this->uriBuilder, $confirmationRequest->getDecodedValues());

                return $this->htmlResponse($this->view->renderSection('Form', [
                    'settings' => $this->settings,
                    'overrideConfiguration' => $overrideConfiguration,
                    'factory' => $factory,
                ]));
            }
        }

        return $this->htmlResponse($this->view->renderSection('HashNotFound'));
    }



    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws FinisherException
     * @throws \Doctrine\DBAL\Exception
     */
    public function resendConfirmationEmailAction(): ResponseInterface
    {


        $currentPageId = (int)($this->request->getAttribute('routing')->getPageId() ?? 0);
        $currentLanguageUid = (int)($this->request->getAttribute('language')->getLanguageId() ?? 0);

        // QueryBuilder für tt_content erstellen
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);


        // Inhaltselement mit CType 'feregistration' und aktueller Sprache suchen
        $record = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($currentPageId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('feregistration_registration')),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($currentLanguageUid, ParameterType::INTEGER))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($record) {
            // FlexForm-Daten parsen
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $flexFormSettings = $flexFormService->convertFlexFormContentToArray($record['pi_flexform']);

            // FlexForm-Werte nutzen
            $settings = array_merge($settings, $flexFormSettings['settings'] ?? []);

            $email = $this->request->getQueryParams()['email'] ?? '';

            /** @var ConfirmationRequest $confirmationRequestRecord */
            $confirmationRequestRecord = $this->confirmationRequestRepository->findOneByEmail($email);
            if ($confirmationRequestRecord) {

                if ($confirmationRequestRecord->isConfirmed()) {
                    return new JsonResponse(['success' => false, 'alreadyConfirmed' => true]);
                }
                if ($confirmationRequestRecord->getLastSent() && $confirmationRequestRecord->getLastSent()->getTimestamp() + (int)$settings['confirmationEmail']['timeLock'] > time()) {
                    return new JsonResponse(['success' => false, 'wait' => true, 'nextSend' => $confirmationRequestRecord->getLastSent()->getTimestamp() + (int)$settings['confirmationEmail']['timeLock']]);
                }

                /** @var MailingService $mailer */
                $mailer = GeneralUtility::makeInstance(MailingService::class);
                $mailer->sendConfirmationMail($confirmationRequestRecord, $this->request, $settings, $currentPageId);


                return new JsonResponse(['success' => true]);
            }


        }


        return new JsonResponse(['success' => false]);
    }


}
