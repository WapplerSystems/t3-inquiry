<?php

namespace WapplerSystems\Inquiry\ViewHelpers\Button;


use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use WapplerSystems\Inquiry\Event\CanResolveItemEvent;

/**
 *
 * You can create this button manually by using the following code:
 *
 * <button data-inquiry-item-uid="{product.uid}" data-inquiry-item-type="tx_productsystem_domain_model_product" data-add-label="{f:translate(key:'LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:addToList')}" data-remove-label="{f:translate(key:'LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:removeFromList')}" title="{f:translate(key:'LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:removeFromList')}" class="btn btn-outline-dark toggle-inquiry-item-status-button">
 * <span class="inquiry-button-label">{f:translate(key:'LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:addToList')}</span>
 * </button>
 *
 */
class QuickFormViewHelper extends AbstractTagBasedViewHelper
{

    protected $tagName = 'button';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct();
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('item', 'mixed', 'The item which should be added to the list', true);
    }

    public function render(): string
    {
        $item = $this->arguments['item'] ?? null;

        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $event = new CanResolveItemEvent($item, $request);
        $this->eventDispatcher->dispatch($event);
        if (!$event->isResult()) {
            $this->tag->setContent('ERROR: Item cannot be resolved');
            $this->tag->forceClosingTag(true);
            return $this->tag->render();
        }
        $this->tag->addAttribute('data-inquiry-item-uid', $event->getResolvedItemUid());
        $this->tag->addAttribute('data-inquiry-item-type', $event->getResolvedItemType());
        $this->tag->addAttribute('data-add-label', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:addToList', 'inquiry'));
        $this->tag->addAttribute('title', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:addToList', 'inquiry'));
        $this->tag->addAttribute('data-remove-label', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:removeFromList', 'inquiry'));
        $this->tag->addAttribute('class', $this->tag->getAttribute('class') . ' quick-inquiry-button');

        $this->tag->setContent('<span class="inquiry-button-label">'.LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:addToList', 'inquiry').'</span>');
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

}
