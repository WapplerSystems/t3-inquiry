<?php

namespace WapplerSystems\Inquiry\Event;


class ResolveItemEvent
{
    private int $uid;
    private string $type;

    private string $resolvedName;
    private mixed $resolvedImage = null;
    private mixed $resolvedObject = null;

    public function __construct(int $uid, string $type)
    {
        $this->uid = $uid;
        $this->type = $type;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getResolvedName(): string
    {
        return $this->resolvedName;
    }

    public function setResolvedName(string $resolvedName): void
    {
        $this->resolvedName = $resolvedName;
    }

    public function getResolvedObject(): mixed
    {
        return $this->resolvedObject;
    }

    public function setResolvedObject(mixed $resolvedObject): void
    {
        $this->resolvedObject = $resolvedObject;
    }

    public function getResolvedImage(): mixed
    {
        return $this->resolvedImage;
    }

    public function setResolvedImage(mixed $resolvedImage): void
    {
        $this->resolvedImage = $resolvedImage;
    }

}
