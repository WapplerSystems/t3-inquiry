<?php

namespace WapplerSystems\Iquiry\ViewHelpers\Form;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class StaticItemNameViewHelper extends AbstractViewHelper
{
    /**
     * ViewHelper braucht keine Child-Content-Verarbeitung
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'UID der Seite', true);
    }

    public function render(): string
    {
        $uid = (int)$this->arguments['uid'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $result = $queryBuilder
            ->select('title', 'subtitle')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $result['subtitle'] ?? $result['title'] ?? '';
    }
}
