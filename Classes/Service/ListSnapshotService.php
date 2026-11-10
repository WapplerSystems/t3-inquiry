<?php

declare(strict_types=1);

namespace WapplerSystems\Inquiry\Service;

use Psr\Http\Message\ServerRequestInterface;
use WapplerSystems\Inquiry\Domain\Repository\ListSnapshotRepository;

/**
 * Stores a list snapshot and builds the URL that restores it.
 *
 * The identifier scheme and the URL layout belong to this extension, so
 * anything that wants to link back to a list - the PDF, a mail finisher in a
 * downstream extension - goes through here instead of rebuilding both.
 */
class ListSnapshotService
{
    public const DEFAULT_PRELOAD_TYPE_NUM = 678937;

    public function __construct(
        private readonly ListSnapshotRepository $listSnapshotRepository,
    ) {
    }

    /**
     * @param array $items list of ['uid' => int, 'type' => string, 'hash' => string]
     * @param array $prefill field values keyed by item hash, '_contact' for the contact block
     */
    public function store(array $items, array $prefill): string
    {
        $items = array_values($items);
        $identifier = md5((string)json_encode(['items' => $items, 'prefill' => $prefill]));
        $this->listSnapshotRepository->save($identifier, $items, $prefill);

        return $identifier;
    }

    public function buildPreloadUrl(
        string $identifier,
        ServerRequestInterface $request,
        ?int $typeNum = null,
    ): string {
        $params = [
            'type' => $typeNum ?? self::DEFAULT_PRELOAD_TYPE_NUM,
            'tx_inquiry' => ['identifier' => $identifier],
        ];

        // The language base carries the prefix, the site base does not.
        $base = $request->getAttribute('language')?->getBase();

        return rtrim((string)$base, '/') . '/?' . http_build_query($params);
    }
}
