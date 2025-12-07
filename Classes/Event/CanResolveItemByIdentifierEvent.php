<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class CanResolveItemByIdentifierEvent
{
    private int $uid;
    private string $type;
    private bool $result = false;
    private ?ServerRequestInterface $request = null;


    public function __construct(int $uid, string $type, ?ServerRequestInterface $request = null)
    {
        $this->uid = $uid;
        $this->type = $type;
        $this->request = $request;
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

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

}
