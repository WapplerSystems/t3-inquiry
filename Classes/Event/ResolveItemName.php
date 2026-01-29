<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class ResolveItemName
{

    private mixed $item;
    private string $name = '';

    public function __construct(mixed $item)
    {
        $this->item = $item;
    }

    public function getItem(): mixed
    {
        return $this->item;
    }

    public function setItem(mixed $item): void
    {
        $this->item = $item;
    }

    public function getName(): mixed
    {
        return $this->item;
    }

    public function setName(mixed $item): void
    {
        $this->item = $item;
    }


}
