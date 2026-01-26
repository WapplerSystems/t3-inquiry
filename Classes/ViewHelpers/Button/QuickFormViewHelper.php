<?php

namespace WapplerSystems\Inquiry\ViewHelpers\Button;


use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
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
        $this->registerArgument('pageUid', 'number', 'The page Uid of the product', true);
        $this->registerArgument('pageType', 'number', 'The page type of the product', true);
        $this->registerArgument('itemType', 'string', 'The page type of the product', true);
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

        $uri = $this->renderFrontendLinkWithCoreContext($request);

        $this->tag->addAttribute('data-inquiry-item-uid', $event->getResolvedItemUid());
        $this->tag->addAttribute('data-inquiry-item-type', $event->getResolvedItemType());
        $this->tag->addAttribute('data-url', $uri);
        $this->tag->addAttribute('data-add-label', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:directInquiry', 'inquiry'));
        $this->tag->addAttribute('title', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:directInquiry', 'inquiry'));
        $this->tag->addAttribute('data-remove-label', LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:removeFromList', 'inquiry'));
        $this->tag->addAttribute('class', $this->tag->getAttribute('class') . ' quick-inquiry-button');

        $this->tag->setContent('<i class="t3b-icon-pencil tg-product-icon"></i><span class="inquiry-button-label">' . LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:directInquiry', 'inquiry') . '</span>');
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }


    protected function renderFrontendLinkWithCoreContext(ServerRequestInterface $request): string
    {
        $pageUid = isset($this->arguments['pageUid']) ? (int)$this->arguments['pageUid'] : 'current';
        $pageType = isset($this->arguments['pageType']) ? (int)$this->arguments['pageType'] : 0;
        $noCache = isset($this->arguments['noCache']) && (bool)$this->arguments['noCache'];
        $section = isset($this->arguments['section']) ? (string)$this->arguments['section'] : '';
        $language = isset($this->arguments['language']) ? (string)$this->arguments['language'] : null;
        $linkAccessRestrictedPages = isset($this->arguments['linkAccessRestrictedPages']) && (bool)$this->arguments['linkAccessRestrictedPages'];

        $additionalParams = [
            'item-type' => $this->arguments['itemType'],
            'item-uid' => $this->arguments['pageUid'],
        ];
        $absolute = isset($this->arguments['absolute']) && (bool)$this->arguments['absolute'];
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($this->arguments['argumentsToBeExcludedFromQueryString']) ? (array)$this->arguments['argumentsToBeExcludedFromQueryString'] : [];

        $typolinkConfiguration = [
            'parameter' => $pageUid,
        ];
        if ($pageType) {
            $typolinkConfiguration['parameter'] .= ',' . $pageType;
        }
        if ($noCache) {
            $typolinkConfiguration['no_cache'] = 1;
        }
        if ($language !== null) {
            $typolinkConfiguration['language'] = $language;
        }
        if ($section) {
            $typolinkConfiguration['section'] = $section;
        }
        if ($linkAccessRestrictedPages) {
            $typolinkConfiguration['linkAccessRestrictedPages'] = 1;
        }
        if ($additionalParams) {
            $typolinkConfiguration['additionalParams'] = HttpUtility::buildQueryString($additionalParams, '&');
        }
        if ($absolute) {
            $typolinkConfiguration['forceAbsoluteUrl'] = true;
        }
        if ($addQueryString && $addQueryString !== 'false') {
            $typolinkConfiguration['addQueryString'] = $addQueryString;
            if ($argumentsToBeExcludedFromQueryString !== []) {
                $typolinkConfiguration['addQueryString.']['exclude'] = implode(',', $argumentsToBeExcludedFromQueryString);
            }
        }

        try {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->setRequest($request);
            $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
            $linkResult = $linkFactory->create((string)$this->renderChildren(), $typolinkConfiguration, $cObj);

        } catch (UnableToLinkException) {
        }

        return $linkResult->getUrl();
    }

}
