<?php

namespace WapplerSystems\Inquiry\Event;

class ConfigurePdfEvent
{
    private array $fontDirs = [];
    private array $fontData = [];
    private string $defaultFont = '';
    private string $fileName = '';

    /**
     * @param \DateTimeImmutable $generatedAt Moment the PDF is generated, in the
     *                                        downloading visitor's own time zone
     *                                        when the client reported one.
     */
    public function __construct(
        private readonly \DateTimeImmutable $generatedAt = new \DateTimeImmutable(),
    ) {}

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function addFontDir(string $path): void
    {
        $this->fontDirs[] = $path;
    }

    public function getFontDirs(): array
    {
        return $this->fontDirs;
    }

    public function addFontData(string $name, array $config): void
    {
        $this->fontData[$name] = $config;
    }

    public function getFontData(): array
    {
        return $this->fontData;
    }

    public function setDefaultFont(string $font): void
    {
        $this->defaultFont = $font;
    }

    public function getDefaultFont(): string
    {
        return $this->defaultFont;
    }

    /**
     * Name the PDF is offered under. Anything but letters, digits, dot, dash and
     * underscore is replaced by the controller, and a missing ".pdf" appended.
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}