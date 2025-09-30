<?php

namespace WapplerSystems\Inquiry\ViewHelpers\Link;


use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;


class AddItemViewHelper extends AbstractTagBasedViewHelper
{

    protected $tagName = 'a';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'The uid', true);
        $this->registerArgument('type', 'string', 'The type of item', false);
    }

    public function render(): string
    {



        $number = $this->arguments['number'] ?? '';
        if ($number === '' || !str_starts_with($number, '+')) {
            return (string)$this->renderChildren();
        }

        $number = str_replace('(0)', '', $number);
        // Remove any non-numeric characters from the number
        $number = preg_replace('/[^\d+]/', '', $number);

        $this->tag->addAttribute('href', 'tel:' . $number);
        $this->tag->setContent((string)$this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

}
