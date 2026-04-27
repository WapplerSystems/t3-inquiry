<?php

namespace WapplerSystems\Inquiry\Controller;

use Mpdf\Mpdf;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WapplerSystems\Inquiry\Domain\Repository\ListSnapshotRepository;
use WapplerSystems\Inquiry\Event\CanResolveItemByIdentifierEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;

class InquiryController extends ActionController
{


    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        private readonly ListSnapshotRepository $listSnapshotRepository,
    ) {
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
                LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:noItemsAdded', 'inquiry'),
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


    public function removeAllItemsAction(): ResponseInterface
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

        foreach ($items as $key => $item) {
            $event = new CanResolveItemByIdentifierEvent($item['uid'], $item['type'], $this->request);
            $this->eventDispatcher->dispatch($event);
            if (!$event->isResult()) {
                unset($items[$key]);
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

        foreach ($items as $key => $item) {
            $event = new CanResolveItemByIdentifierEvent($item['uid'], $item['type'], $this->request);
            $this->eventDispatcher->dispatch($event);
            if (!$event->isResult()) {
                unset($items[$key]);
            }
        }

        $data = [
            'items' => $items,
        ];

        return $this->jsonResponse(json_encode($data));
    }

    public function saveListSnapshotAction(): ResponseInterface
    {
        $rawBody = $this->request->getBody()->getContents();
        $body = json_decode($rawBody, true) ?? [];
        $items = $body['items'] ?? [];
        $prefill = $body['prefill'] ?? [];

        $identifier = md5(json_encode(['items' => array_values($items), 'prefill' => $prefill]));
        $this->listSnapshotRepository->save($identifier, array_values($items), $prefill);

        return $this->jsonResponse(json_encode(['identifier' => $identifier]));
    }

    public function getPrefillAction(): ResponseInterface
    {
        $identifier = $this->request->getQueryParams()['tx_inquiry']['identifier'] ?? '';
        if ($identifier === '') {
            return $this->jsonResponse(json_encode(['prefill' => []]))->withStatus(400);
        }

        $snapshot = $this->listSnapshotRepository->findByIdentifier($identifier);
        if ($snapshot === null) {
            return $this->jsonResponse(json_encode(['prefill' => []]))->withStatus(404);
        }

        $prefill = $snapshot['prefill'];
        unset($prefill['_contact']);
        return $this->jsonResponse(json_encode(['prefill' => $prefill]));
    }

    public function preloadItemsAction(): ResponseInterface
    {
        $identifier = $this->request->getQueryParams()['tx_inquiry']['identifier'] ?? '';
        if ($identifier === '') {
            return $this->redirectToUri($this->getFallbackListUri());
        }

        $snapshot = $this->listSnapshotRepository->findByIdentifier($identifier);
        if ($snapshot === null) {
            return $this->redirectToUri($this->getFallbackListUri());
        }

        /** @var FrontendUserAuthentication $frontendUserAuthentication */
        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $userSession = $frontendUserAuthentication->getSession();

        $items = [];
        foreach ($snapshot['items'] as $entry) {
            $uid = (int)($entry['uid'] ?? 0);
            $type = $entry['type'] ?? '';
            if ($uid <= 0 || $type === '') {
                continue;
            }
            $event = new CanResolveItemByIdentifierEvent($uid, $type, $this->request);
            $this->eventDispatcher->dispatch($event);
            if (!$event->isResult()) {
                continue;
            }
            $hash = md5($uid . '_' . $type);
            if (!in_array($hash, array_column($items, 'hash'), true)) {
                $items[] = ['uid' => $uid, 'type' => $type, 'hash' => $hash];
            }
        }

        $userSession->set('items', $items);
        $frontendUserAuthentication->storeSessionData();

        $listPageUid = (int)($this->settings['listPageUid'] ?? 0);
        $baseUri = $listPageUid > 0
            ? $this->uriBuilder->reset()->setTargetPageUid($listPageUid)->buildFrontendUri()
            : '/';

        $separator = str_contains($baseUri, '?') ? '&' : '?';
        $redirectUri = $baseUri . $separator . http_build_query(['tx_inquiry' => ['identifier' => $identifier]]);

        return $this->redirectToUri($redirectUri);
    }


    public function generatePdfAction(): ResponseInterface
    {
        $identifier = $this->request->getQueryParams()['tx_inquiry']['identifier'] ?? '';
        if ($identifier === '') {
            return $this->responseFactory->createResponse(400)
                ->withBody($this->streamFactory->createStream('Missing identifier'));
        }

        $snapshot = $this->listSnapshotRepository->findByIdentifier($identifier);
        if ($snapshot === null) {
            return $this->responseFactory->createResponse(404)
                ->withBody($this->streamFactory->createStream('Snapshot not found'));
        }

        $items = $snapshot['items'];
        $pdfFields = $snapshot['prefill'];

        $resolvedItems = [];
        foreach (array_values($items) as $item) {
            $event = new ResolveItemEvent((int)$item['uid'], $item['type'], $this->request);
            $event->setPdfFields($pdfFields[$item['hash']] ?? []);
            $this->eventDispatcher->dispatch($event);
            if ($event->getResolvedObject() !== null) {
                $resolvedItems[] = $event->getHtmlPreviewPdf() ?? $event->getHtmlPreview();
            }
        }

        $this->view->assignMultiple([
            'items'      => $resolvedItems,
            'preloadUrl' => $this->buildPreloadUrl($identifier),
            'contact'    => $pdfFields['_contact'] ?? [],
        ]);
        $html = $this->view->render();

        $ttfPath = $this->getT3bootstrapTtfPath();
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $mpdf = new Mpdf([
            'fontDir'  => array_merge($defaultConfig['fontDir'], [dirname($ttfPath)]),
            'fontdata' => array_merge($defaultFontConfig['fontdata'], ['t3bootstrap' => ['R' => basename($ttfPath)]]),
        ]);

        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', 'S');

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="inquiry-list.pdf"')
            ->withBody($this->streamFactory->createStream($pdfContent));
    }


    private function getT3bootstrapTtfPath(): string
    {
        $ttfPath = sys_get_temp_dir() . '/t3bootstrap_icon.ttf';
        if (!file_exists($ttfPath)) {
            $woff = (string)file_get_contents(GeneralUtility::getFileAbsFileName('EXT:template/Resources/Public/Fonts/T3Bootstrap/t3bootstrap.woff'));
            file_put_contents($ttfPath, $this->woffToTtf($woff));
        }
        return $ttfPath;
    }

    private function woffToTtf(string $woff): string
    {
        $header = unpack('Nsig/Nflavor/Nlength/nnumTables', substr($woff, 0, 14));
        $numTables = $header['numTables'];
        $flavor = $header['flavor'];

        $tables = [];
        $pos = 44;
        for ($i = 0; $i < $numTables; $i++) {
            $entry = unpack('a4tag/Noffset/NcompLength/NorigLength/NorigChecksum', substr($woff, $pos, 20));
            $tables[] = $entry;
            $pos += 20;
        }
        usort($tables, static fn($a, $b) => strcmp($a['tag'], $b['tag']));

        $entrySelector = (int)floor(log($numTables, 2));
        $searchRange = (int)(pow(2, $entrySelector) * 16);
        $rangeShift = $numTables * 16 - $searchRange;

        $ttf = pack('Nnnnn', $flavor, $numTables, $searchRange, $entrySelector, $rangeShift);

        $dataStart = 12 + $numTables * 16;
        $currentOffset = $dataStart;
        $offsets = [];
        foreach ($tables as $table) {
            $offsets[] = $currentOffset;
            $currentOffset += $table['origLength'];
            $currentOffset = ($currentOffset + 3) & ~3;
        }

        foreach ($tables as $i => $table) {
            $ttf .= pack('a4NNN', $table['tag'], $table['origChecksum'], $offsets[$i], $table['origLength']);
        }

        foreach ($tables as $table) {
            $data = substr($woff, $table['offset'], $table['compLength']);
            if ($table['compLength'] < $table['origLength']) {
                $data = (string)zlib_decode($data);
            }
            $ttf .= $data;
            $pad = (4 - (strlen($data) % 4)) % 4;
            $ttf .= str_repeat("\0", $pad);
        }

        return $ttf;
    }

    private function buildPreloadUrl(string $identifier): string
    {
        $params = [
            'type'       => (int)($this->settings['preloadItemsTypeNum'] ?? 678937),
            'tx_inquiry' => ['identifier' => $identifier],
        ];
        $language = $this->request->getAttribute('language');
        return rtrim((string)$language->getBase(), '/') . '/?' . http_build_query($params);
    }

    private function getFallbackListUri(): string
    {
        $listPageUid = (int)($this->settings['listPageUid'] ?? 0);
        return $listPageUid > 0
            ? $this->uriBuilder->reset()->setTargetPageUid($listPageUid)->buildFrontendUri()
            : '/';
    }

}