<?php

namespace WapplerSystems\Inquiry\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

class ListSnapshotRepository
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    public function save(string $identifier, array $items, array $prefill): void
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_inquiry_list_snapshot');
        $connection->executeStatement(
            'INSERT INTO tx_inquiry_list_snapshot (identifier, items, prefill, crdate) VALUES (:identifier, :items, :prefill, :crdate)'
            . ' ON DUPLICATE KEY UPDATE crdate = crdate',
            [
                'identifier' => $identifier,
                'items'      => json_encode($items),
                'prefill'    => json_encode($prefill),
                'crdate'     => time(),
            ]
        );
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_inquiry_list_snapshot');
        $row = $connection->select(
            ['items', 'prefill'],
            'tx_inquiry_list_snapshot',
            ['identifier' => $identifier]
        )->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'items'   => json_decode($row['items'], true) ?? [],
            'prefill' => json_decode($row['prefill'], true) ?? [],
        ];
    }
}