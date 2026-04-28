<?php

namespace WapplerSystems\Inquiry\ViewHelpers\FlyIn;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the inquiry FlyIn (Bootstrap offcanvas-end shell).
 *
 * Mount once per page (typically near </body>):
 *   {namespace i=WapplerSystems\Inquiry\ViewHelpers}
 *   <i:flyIn.panel />
 *
 * Body is populated by inquiry-flyin.js on offcanvas open via the
 * `inquiry-flyin-items` meta-tag URL. Footer CTA links to the configured
 * `plugin.tx_inquiry.settings.listPageUid`.
 */
class PanelViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function render(): string
    {
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $title = htmlspecialchars(LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:flyIn.title', 'inquiry') ?? 'Inquiry list');
        $closeLabel = htmlspecialchars(LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:flyIn.close', 'inquiry') ?? 'Close');
        $cta = htmlspecialchars(LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:flyIn.cta', 'inquiry') ?? 'Go to inquiry list');

        $listUrl = $this->buildListPageUrl($request);

        return <<<HTML
<div class="offcanvas offcanvas-end inquiry-flyin" id="offcanvasInquiryFlyIn" tabindex="-1" aria-labelledby="offcanvasInquiryFlyInLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasInquiryFlyInLabel">{$title}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{$closeLabel}"></button>
    </div>
    <div class="offcanvas-body" data-inquiry-flyin-body></div>
    <div class="offcanvas-footer p-3 border-top" data-inquiry-flyin-footer>
        <a class="btn btn-primary w-100" href="{$listUrl}">{$cta}</a>
    </div>
</div>
HTML;
    }

    private function buildListPageUrl(?ServerRequestInterface $request): string
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Inquiry');
        $pageUid = (int)($settings['listPageUid'] ?? 0);
        if ($pageUid <= 0 || $request === null) {
            return '#';
        }

        try {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->setRequest($request);
            $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
            $linkResult = $linkFactory->create('', ['parameter' => $pageUid], $cObj);
            return htmlspecialchars($linkResult->getUrl());
        } catch (UnableToLinkException) {
            return '#';
        }
    }
}