<?php

namespace WapplerSystems\Inquiry\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class InquiryController extends ActionController
{


    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
    }


    public function formAction(): ResponseInterface
    {

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();


        $items = $userSession->get('items') ?? [];

        $arguments = $this->request->getArguments()['inquiryFormPage'] ?? [];
        foreach ($items as $key => $item) {
            if (($arguments['itemDelete_'.$item['hash']] ?? 0) === '1') {
                unset($items[$key]);
            }
        }

        $userSession->set('items', $items);
        $frontendUserAuthentication->storeSessionData();


        if (count($items) === 0) {

            $this->addFlashMessage(
                'No items were added',
                '',
                ContextualFeedbackSeverity::INFO,
                false);

            $this->view->assignMultiple([
                'showForm' => 0
            ]);
        } else {
            $this->view->assignMultiple([
                'showForm' => 1
            ]);
        }

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

        $hash = md5($uid . '_' . $type);

        $items = [
            ['uid' => $uid, 'type' => $type, 'hash' => md5($uid . '_' . $type) ]
        ];

        // check if item is allowed to be added

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();
        $storedItems = $userSession->get('items') ?? [];

        if (in_array($hash, array_column($storedItems, 'hash'))) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['success' => false, 'code' => 1000, 'count' => count($storedItems), 'message' => 'Item already in inquiry']));
            }
            return $this->htmlResponse('Item already in inquiry');
        }
        $items = array_merge($storedItems, $items);
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

    public function getItemsAction() : ResponseInterface
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
            'items' => $items
        ];

        return $this->jsonResponse(json_encode($data));
    }

    public function addItemFormAction() : ResponseInterface
    {


        return $this->htmlResponse();
    }


}
