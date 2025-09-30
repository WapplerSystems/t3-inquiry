<?php

namespace WapplerSystems\Inquiry\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class InquiryController extends ActionController
{


    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
    }


    public function formAction(): ResponseInterface
    {


        return $this->htmlResponse();
    }


    public function addItemAction() : ResponseInterface
    {

        $params = $this->request->getParsedBody();
        $uid = $params['tx_inquiry_additemform']['uid'] ?? $this->request->getArguments()['uid'] ?? null;
        $type = $params['tx_inquiry_additemform']['type'] ?? $this->request->getArguments()['type'] ?? null;
        if (!$uid || !$type) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['success' => false, 'message' => 'No uid or type given']));
            }
            return $this->htmlResponse('No uid or type given');
        }

        $items = [
            ['uid' => $uid, 'type' => $type]
        ];

        // check if item is allowed to be added

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();

        if ($userSession->get('items')) {
            $items = array_merge($userSession->get('items'), $items);
        }
        //DebugUtility::debug($items, 'items');

        $userSession->set('items', $items);

        $frontendUserAuthentication->storeSessionData();

        $data = [
            'success' => true,
            'count' => count($items)
        ];

        $accept = $this->request->getHeader('accept')[0] ?? '';
        if (str_contains($accept, 'application/json')) {
            return $this->jsonResponse(json_encode($data));
        }
        $this->view->assignMultiple([
            'items' => $items,
            'count' => count($items)
        ]);
        return $this->htmlResponse();
    }

    public function removeItemAction() : ResponseInterface
    {

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();


        $data = [
            'success' => true,
            'count' => count($items)
        ];

        return $this->jsonResponse(json_encode($data));
    }


    public function countItemsAction() : ResponseInterface
    {
        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();

        if ($userSession->get('items')) {
            $items = $userSession->get('items');
        } else {
            $items = [];
        }

        $data = [
            'success' => true,
            'count' => count($items)
        ];

        return $this->jsonResponse(json_encode($data));
    }

    public function addItemFormAction() : ResponseInterface
    {


        return $this->htmlResponse();
    }


}
