<?php

declare(strict_types=1);

namespace WapplerSystems\InquiryTestConsumer\EventListener;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\Inquiry\Event\CanResolveItemByIdentifierEvent;
use WapplerSystems\Inquiry\Event\CanResolveItemEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;
use WapplerSystems\Inquiry\Event\ResolveItemName;

/**
 * Treats ordinary pages as inquiry items, which is the smallest consumer that
 * makes the extension do something visible.
 *
 * Beyond serving the fixture this pins the event API that real consumers build
 * on. A consumer living in another repository can call a method this extension
 * dropped and nothing here would notice — that is exactly how a production
 * outage happened on 2026-09-28. Every setter a consumer is expected to use is
 * therefore exercised below, so removing one breaks this suite first.
 */
final class PageItemListener
{
    private const ITEM_TYPE = 'pages';

    #[AsEventListener(identifier: 'inquiry-test-consumer/can-resolve-item')]
    public function canResolveItem(CanResolveItemEvent $event): void
    {
        $uid = $this->uidOf($event->getItem());
        if ($uid === null) {
            return;
        }

        $event->setResolvedItemUid($uid);
        $event->setResolvedItemType(self::ITEM_TYPE);
        $event->setResult(true);
    }

    #[AsEventListener(identifier: 'inquiry-test-consumer/can-resolve-item-by-identifier')]
    public function canResolveItemByIdentifier(CanResolveItemByIdentifierEvent $event): void
    {
        if ($event->getType() === self::ITEM_TYPE && $this->pageTitle($event->getUid()) !== null) {
            $event->setResult(true);
        }
    }

    #[AsEventListener(identifier: 'inquiry-test-consumer/resolve-item')]
    public function resolveItem(ResolveItemEvent $event): void
    {
        if ($event->getType() !== self::ITEM_TYPE) {
            return;
        }

        $title = $this->pageTitle($event->getUid());
        if ($title === null) {
            return;
        }

        $event->setResolvedName($title);
        $event->setResolvedObject(['uid' => $event->getUid(), 'title' => $title]);
        $event->setHtmlPreview(sprintf('<span class="inquiry-test-preview">%s</span>', htmlspecialchars($title)));
        $event->setHtmlPreviewPdf(sprintf('<span>%s</span>', htmlspecialchars($title)));
        $event->setPdfFields([
            [
                'label' => 'Page uid',
                'value' => (string)$event->getUid(),
            ],
        ]);
    }

    #[AsEventListener(identifier: 'inquiry-test-consumer/resolve-item-name')]
    public function resolveItemName(ResolveItemName $event): void
    {
        $uid = $this->uidOf($event->getItem());
        $title = $uid === null ? null : $this->pageTitle($uid);

        if ($title !== null) {
            $event->setName($title);
        }
    }

    /**
     * Accepts the shapes a template realistically hands over: a page record
     * array, an object exposing getUid(), or a bare uid.
     */
    private function uidOf(mixed $item): ?int
    {
        if (is_array($item) && isset($item['uid'])) {
            return (int)$item['uid'];
        }

        if (is_object($item) && method_exists($item, 'getUid')) {
            return (int)$item->getUid();
        }

        if (is_numeric($item)) {
            return (int)$item;
        }

        return null;
    }

    private function pageTitle(int $uid): ?string
    {
        if ($uid <= 0) {
            return null;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $title = $queryBuilder
            ->select('title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchOne();

        return $title === false ? null : (string)$title;
    }
}
