<?php

namespace WapplerSystems\Inquiry\ViewHelpers\Form;

use Doctrine\DBAL\ParameterType;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use WapplerSystems\Inquiry\Event\ResolveItemName;

class ItemNameViewHelper extends AbstractViewHelper
{

    public function __construct(readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * ViewHelper braucht keine Child-Content-Verarbeitung
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('item', 'mixed', 'The resolved item', true);
    }

    public function render(): string
    {

        $item = $this->arguments['item'];

        $event = new ResolveItemName($item);
        $this->eventDispatcher->dispatch($event);

        return $event->getName();
    }
}
