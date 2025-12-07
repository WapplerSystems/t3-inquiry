<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;

class CanResolveItemEvent
{
    private mixed $item;
    private bool $result = false;
    private ?ServerRequestInterface $request = null;
    private int $uid;
    private string $type;


    public function __construct(mixed $item, ?ServerRequestInterface $request = null)
    {
        $this->item = $item;
        $this->request = $request;
    }

    public function getItem(): mixed
    {
        return $this->item;
    }

    public function isResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): void
    {
        $this->result = $result;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function getResolvedItemUid(): int
    {
        return $this->uid;
    }

    public function setResolvedItemUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getResolvedItemType(): string
    {
        return $this->type;
    }

    public function setResolvedItemType(string $type): void
    {
        $this->type = $type;
    }

}
