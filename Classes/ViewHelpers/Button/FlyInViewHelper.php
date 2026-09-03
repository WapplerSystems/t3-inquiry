<?php

namespace WapplerSystems\Inquiry\ViewHelpers\Button;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Renders the trigger button that opens the inquiry FlyIn (offcanvas).
 *
 * Usage:
 *   {namespace i=WapplerSystems\Inquiry\ViewHelpers}
 *   <i:button.flyIn />
 *
 * The button uses the standard `to-inquiry-list` class so the existing
 * inquiry.js badge mechanism updates the count automatically after every
 * add/remove. Pair with `<i:flyIn.panel />` once per page to mount the offcanvas.
 *
 * Child content is rendered before the counter, so a site can give the button
 * an icon and a label of its own:
 *
 *   <i:button.flyIn class="my-button">
 *       <i class="my-icon"></i><span>Inquiry list</span>
 *   </i:button.flyIn>
 */
class FlyInViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'button';

    public function render(): string
    {
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $count = $this->getInitialItemCount($request);
        $label = LocalizationUtility::translate('LLL:EXT:inquiry/Resources/Private/Language/frontend.xlf:flyIn.openLabel', 'inquiry') ?? 'Open inquiry list';

        $this->tag->addAttribute('type', 'button');
        $this->tag->addAttribute('data-bs-toggle', 'offcanvas');
        $this->tag->addAttribute('data-bs-target', '#offcanvasInquiryFlyIn');
        $this->tag->addAttribute('aria-controls', 'offcanvasInquiryFlyIn');
        $this->tag->addAttribute('aria-label', $label);
        $this->tag->addAttribute('title', $label);
        $this->tag->addAttribute(
            'class',
            trim(($this->tag->getAttribute('class') ?? '') . ' to-inquiry-list inquiry-flyin-trigger')
        );

        $content = (string)$this->renderChildren();
        $this->tag->setContent($content . '<span class="inquiry-item-counter">' . $count . '</span>');
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    private function getInitialItemCount(?ServerRequestInterface $request): int
    {
        $frontendUser = $request?->getAttribute('frontend.user');
        $session = $frontendUser?->getSession();
        $items = $session?->get('items') ?? [];
        return is_array($items) ? count($items) : 0;
    }
}