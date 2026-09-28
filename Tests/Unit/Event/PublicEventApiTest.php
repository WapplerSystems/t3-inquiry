<?php

declare(strict_types=1);

namespace WapplerSystems\Inquiry\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\Inquiry\Event\CanResolveItemByIdentifierEvent;
use WapplerSystems\Inquiry\Event\CanResolveItemEvent;
use WapplerSystems\Inquiry\Event\ResolveItemEvent;
use WapplerSystems\Inquiry\Event\ResolveItemName;

/**
 * The events are this extension's public surface. Consumers live in other
 * repositories — a site package, a customer adapter — and this repository has
 * no way of knowing what they call.
 *
 * On 2026-09-28 that cost a production outage: a consumer called
 * ResolveItemEvent::getPdfFields(), the version installed on the server did not
 * have it yet, and the inquiry list answered with a 500 in every language. The
 * mismatch was invisible here because both packages looked healthy on their own.
 *
 * This test pins the surface. Renaming or dropping one of these methods is a
 * breaking change for consumers, and it should fail here — loudly, before a
 * release — rather than on someone's live site.
 *
 * Adding a method is fine and needs no change. Removing one is a deliberate act:
 * update this list, bump the major version, tell the consumers.
 */
final class PublicEventApiTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: class-string, 1: list<string>}>
     */
    public static function publicEventApiProvider(): array
    {
        return [
            'CanResolveItemEvent' => [
                CanResolveItemEvent::class,
                ['getItem', 'isResult', 'setResult', 'getRequest', 'getResolvedItemUid', 'setResolvedItemUid', 'getResolvedItemType', 'setResolvedItemType'],
            ],
            'CanResolveItemByIdentifierEvent' => [
                CanResolveItemByIdentifierEvent::class,
                ['getUid', 'setUid', 'getType', 'setType', 'isResult', 'setResult', 'getRequest'],
            ],
            'ResolveItemEvent' => [
                ResolveItemEvent::class,
                [
                    'getUid', 'setUid', 'getType', 'setType',
                    'getResolvedName', 'setResolvedName',
                    'getResolvedObject', 'setResolvedObject',
                    'getResolvedImage', 'setResolvedImage',
                    'getHtmlPreview', 'setHtmlPreview',
                    // The two that were missing on the server in September 2026.
                    'getHtmlPreviewPdf', 'setHtmlPreviewPdf',
                    'getPdfFields', 'setPdfFields',
                    'getRequest', 'setRequest',
                ],
            ],
            'ResolveItemName' => [
                ResolveItemName::class,
                ['getItem', 'setItem', 'getName', 'setName'],
            ],
        ];
    }

    /**
     * @param class-string $eventClass
     * @param list<string> $expectedMethods
     */
    #[Test]
    #[DataProvider('publicEventApiProvider')]
    public function eventKeepsTheMethodsConsumersCall(string $eventClass, array $expectedMethods): void
    {
        $reflection = new \ReflectionClass($eventClass);

        $actual = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !$method->isStatic()) {
                $actual[] = $method->getName();
            }
        }

        $missing = array_diff($expectedMethods, $actual);

        self::assertSame(
            [],
            array_values($missing),
            sprintf(
                '%s lost public methods that consumers call: %s. Removing them breaks every consumer at runtime, '
                . 'with a "Call to undefined method" and a 500 — not at install time.',
                $reflection->getShortName(),
                implode(', ', $missing)
            )
        );
    }

    #[Test]
    public function resolveItemEventAcceptsAndReturnsPdfFields(): void
    {
        $event = new ResolveItemEvent(1, 'pages', new \TYPO3\CMS\Core\Http\ServerRequest());

        self::assertSame([], $event->getPdfFields(), 'A fresh event should offer an empty field list, not null.');

        $event->setPdfFields([['label' => 'Amount', 'value' => '3']]);

        self::assertSame([['label' => 'Amount', 'value' => '3']], $event->getPdfFields());
    }
}
