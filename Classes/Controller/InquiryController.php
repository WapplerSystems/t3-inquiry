<?php

namespace WapplerSystems\Inquiry\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WapplerSystems\Inquiry\Event\CanResolveItemByIdentifierEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;

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

        if (($this->settings['subject'] ?? '') === '') {
            return $this->htmlResponse('<div class="alert alert-warning">The inquiry form subject setting is required.</div>');
        }
        if (count($this->settings['recipients'] ?? []) === 0) {
            return $this->htmlResponse('<div class="alert alert-warning">Please set recipients.</div>');
        }

        $items = $userSession->get('items') ?? [];

        $arguments = $this->request->getArguments()['inquiryForm'] ?? [];
        foreach ($items as $key => $item) {
            if (($arguments['itemDelete_' . $item['hash']] ?? 0) === '1') {
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


    public function quickFormAction(): ResponseInterface
    {

        if (($this->settings['subject'] ?? '') === '') {
            return $this->htmlResponse('<div class="alert alert-warning">The inquiry form subject setting is required.</div>');
        }
        if (count($this->settings['recipients'] ?? []) === 0) {
            return $this->htmlResponse('<div class="alert alert-warning">Please set recipients.</div>');
        }

        $uid = (int)($this->request->getQueryParams()['item-uid'] ?? null);
        $type = $this->request->getQueryParams()['item-type'] ?? null;


        $event = new ResolveItemEvent($uid, $type, $this->request);
        $this->eventDispatcher->dispatch($event);

        if ($event->getResolvedObject() === null) {
            return $this->htmlResponse('<div class="alert alert-warning">Item could not be resolved.</div>');
        }

        $this->settings['resolvedItem'] = $event->getResolvedObject();
        $this->settings['itemData'] = [
            'uid' => $uid,
            'type' => $type,
        ];

        $this->view->assignMultiple([
            'item' => $event->getResolvedObject(),
            'settings' => $this->settings,
        ]);

        return $this->htmlResponse();
    }


    public function toggleItemStatusAction(): ResponseInterface
    {
        $params = $this->request->getQueryParams()['tx_inquiry'] ?? [];
        $uid = (int)($params['uid'] ?? null);
        $type = $params['type'] ?? null;
        if (!$uid || !$type) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['message' => 'No uid or type given']))->withStatus(500);
            }
            return $this->htmlResponse('No uid or type given')->withStatus(500);
        }

        $event = new CanResolveItemByIdentifierEvent($uid, $type, $this->request);
        $this->eventDispatcher->dispatch($event);
        if (!$event->isResult()) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['message' => 'Item cannot be resolved']))->withStatus(500);
            }
            return $this->htmlResponse('Item cannot be resolved')->withStatus(500);
        }


        $hash = md5($uid . '_' . $type);

        // check if item is allowed to be added

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();
        $storedItems = $userSession->get('items') ?? [];

        if (!in_array($hash, array_column($storedItems, 'hash'), true)) {
            $items = array_merge($storedItems, [['uid' => $uid, 'type' => $type, 'hash' => md5($uid . '_' . $type)]]);
            $userSession->set('items', $items);
            $frontendUserAuthentication->storeSessionData();

            $data = [
                'items' => $items,
                'added' => true,
            ];
            return $this->jsonResponse(json_encode($data));
        }

        // remove item
        foreach ($storedItems as $item) {
            if ($item['hash'] === $hash) {
                unset($storedItems[array_search($item, $storedItems, true)]);
            }
        }
        $userSession->set('items', $storedItems);
        $frontendUserAuthentication->storeSessionData();

        $data = [
            'items' => $storedItems,
            'removed' => true,
        ];
        return $this->jsonResponse(json_encode($data));
    }


    public function removeAllItems() : ResponseInterface
    {

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();
        $userSession->set('items', []);
        $frontendUserAuthentication->storeSessionData();

        return $this->jsonResponse();
    }


    public function removeItemAction(): ResponseInterface
    {
        $params = $this->request->getQueryParams()['tx_inquiry'] ?? [];
        $uid = (int)($params['uid'] ?? null);
        $type = $params['type'] ?? null;
        if (!$uid || !$type) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['message' => 'No uid or type given']))->withStatus(500);
            }
            return $this->htmlResponse('No uid or type given')->withStatus(500);
        }

        $event = new CanResolveItemByIdentifierEvent($uid, $type, $this->request);
        $this->eventDispatcher->dispatch($event);
        if (!$event->isResult()) {
            $accept = $this->request->getHeader('accept')[0] ?? '';
            if (str_contains($accept, 'application/json')) {
                return $this->jsonResponse(json_encode(['message' => 'Item cannot be resolved']))->withStatus(500);
            }
            return $this->htmlResponse('Item cannot be resolved')->withStatus(500);
        }


        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();
        $storedItems = $userSession->get('items') ?? [];
        foreach ($storedItems as $item) {
            if ((int)$item['uid'] === $uid && $item['type'] === $type) {
                unset($storedItems[array_search($item, $storedItems, true)]);
            }
        }
        $userSession->set('items', $storedItems);
        $frontendUserAuthentication->storeSessionData();

        $data = [
            'removed' => true,
        ];

        return $this->jsonResponse(json_encode($data));
    }


    public function countItemsAction(): ResponseInterface
    {
        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();

        if ($userSession->get('items')) {
            $items = $userSession->get('items');
        } else {
            $items = [];
        }

        foreach ($items as &$item) {
            $event = new CanResolveItemByIdentifierEvent($item['uid'], $item['type'], $this->request);
            $this->eventDispatcher->dispatch($event);
            if (!$event->isResult()) {
                unset($item);
            }
        }

        $data = [
            'count' => count($items)
        ];

        return $this->jsonResponse(json_encode($data));
    }

    public function getItemsAction(): ResponseInterface
    {
        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();

        if ($userSession->get('items')) {
            $items = $userSession->get('items');
        } else {
            $items = [];
        }

        foreach ($items as &$item) {
            $event = new CanResolveItemByIdentifierEvent($item['uid'], $item['type'], $this->request);
            $this->eventDispatcher->dispatch($event);
            if (!$event->isResult()) {
                unset($item);
            }
        }

        $data = [
            'items' => $items
        ];

        return $this->jsonResponse(json_encode($data));
    }

    public function addItemFormAction(): ResponseInterface
    {


        return $this->htmlResponse();
    }


}
