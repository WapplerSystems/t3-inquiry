<?php

namespace WapplerSystems\Inquiry\Event;


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class ResolveItemEvent
{
    private int $uid;
    private string $type;

    private ?string $resolvedName = null;
    private ?FileReference $resolvedImage = null;
    private mixed $resolvedObject = null;
    private ?string $htmlPreview = null;
    private ?string $htmlPreviewPdf = null;
    private array $pdfFields = [];
    private ServerRequestInterface $request;

    public function __construct(int $uid, string $type, ServerRequestInterface $request)
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

    public function getResolvedName(): ?string
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

    public function getResolvedImage(): ?FileReference
    {
        return $this->resolvedImage;
    }

    public function setResolvedImage(FileReference $resolvedImage): void
    {
        $this->resolvedImage = $resolvedImage;
    }

    public function getHtmlPreview(): ?string
    {
        return $this->htmlPreview;
    }

    public function setHtmlPreview(string $htmlPreview): void
    {
        $this->htmlPreview = $htmlPreview;
    }

    public function getHtmlPreviewPdf(): ?string
    {
        return $this->htmlPreviewPdf;
    }

    public function setHtmlPreviewPdf(string $htmlPreviewPdf): void
    {
        $this->htmlPreviewPdf = $htmlPreviewPdf;
    }

    public function getPdfFields(): array
    {
        return $this->pdfFields;
    }

    public function setPdfFields(array $pdfFields): void
    {
        $this->pdfFields = $pdfFields;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

}
