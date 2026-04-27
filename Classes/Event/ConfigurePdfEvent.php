<?php

namespace WapplerSystems\Inquiry\Event;

class ConfigurePdfEvent
{
    private array $fontDirs = [];
    private array $fontData = [];
    private string $defaultFont = '';

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
}