<?php

declare(strict_types=1);

namespace WapplerSystems\Inquiry\Tests\Functional\Event;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\Inquiry\Event\CanResolveItemByIdentifierEvent;
use WapplerSystems\Inquiry\Event\CanResolveItemEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;

/**
 * Walks the whole resolution chain with a real consumer attached, the way a
 * site package or customer adapter would attach one.
 *
 * The sibling unit test pins the method names; this one proves the round trip
 * actually carries values, which no amount of reflection can show.
 */
final class ItemResolutionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/inquiry',
        __DIR__ . '/../../Fixtures/Extensions/inquiry_test_consumer',
    ];

    protected array $pathsToLinkInTestInstance = [];

    private const PAGE_UID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function consumerClaimsAPageItem(): void
    {
        $event = new CanResolveItemEvent(['uid' => self::PAGE_UID], new ServerRequest());
        $this->dispatch($event);

        self::assertTrue($event->isResult(), 'No consumer claimed the item — the toggle button would render an error instead.');
        self::assertSame(self::PAGE_UID, $event->getResolvedItemUid());
        self::assertSame('pages', $event->getResolvedItemType());
    }

    #[Test]
    public function unknownItemStaysUnclaimed(): void
    {
        $event = new CanResolveItemEvent(new \stdClass(), new ServerRequest());
        $this->dispatch($event);

        self::assertFalse($event->isResult(), 'A consumer claimed an item it cannot resolve.');
    }

    #[Test]
    public function itemIsRecognisedByItsIdentifier(): void
    {
        $known = new CanResolveItemByIdentifierEvent(self::PAGE_UID, 'pages', new ServerRequest());
        $this->dispatch($known);
        self::assertTrue($known->isResult());

        $unknown = new CanResolveItemByIdentifierEvent(999999, 'pages', new ServerRequest());
        $this->dispatch($unknown);
        self::assertFalse($unknown->isResult(), 'A missing page was accepted as a valid item.');
    }

    /**
     * The values below are what the inquiry list and the PDF export read back.
     * This is the exact call pattern that broke in production when the two
     * packages drifted apart.
     */
    #[Test]
    public function resolvedItemCarriesNameAndPdfFields(): void
    {
        $event = new ResolveItemEvent(self::PAGE_UID, 'pages', new ServerRequest());
        $this->dispatch($event);

        self::assertSame('Inquiry E2E Root', $event->getResolvedName());
        self::assertStringContainsString('Inquiry E2E Root', (string)$event->getHtmlPreview());
        self::assertStringContainsString('Inquiry E2E Root', (string)$event->getHtmlPreviewPdf());
        self::assertSame([['label' => 'Page uid', 'value' => '1']], $event->getPdfFields());
    }

    private function dispatch(object $event): void
    {
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
    }
}
