<?php

namespace WapplerSystems\Inquiry\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;

class BuildInquiryFormItemEvent
{
    private FormDefinition $formDefinition;

    private Section $fieldsetItem;
    private string $hash;
    private ?ServerRequestInterface $request;

    public function __construct(FormDefinition $formDefinition, Section $fieldsetItem, string $hash, ?ServerRequestInterface $request = null)
    {
        $this->formDefinition = $formDefinition;
        $this->fieldsetItem = $fieldsetItem;
        $this->hash = $hash;
        $this->request = $request;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    public function getFieldsetItem(): Section
    {
        return $this->fieldsetItem;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

}
