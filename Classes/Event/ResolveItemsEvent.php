<?php

namespace WapplerSystems\Inquiry\Event;


class ResolveItemsEvent
{
    private array $items;
    private array $resolvedItems = [];

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getResolvedItems(): array
    {
        return $this->resolvedItems;
    }

    public function setResolvedItems(array $resolvedItems): void
    {
        $this->resolvedItems = $resolvedItems;
    }

}
