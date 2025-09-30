<?php

namespace WapplerSystems\Inquiry\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

class RequestTextTemplate extends AbstractDomainObject
{

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $body = '';


    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }


}
