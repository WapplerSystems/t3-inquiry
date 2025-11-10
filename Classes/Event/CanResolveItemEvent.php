<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class CanResolveItemEvent
{
    private int $uid;
    private string $type;
    private bool $result = false;


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

    public function isResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): void
    {
        $this->result = $result;
    }

}
