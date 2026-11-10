<?php

declare(strict_types=1);

namespace WapplerSystems\Inquiry\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Guards the XLIFF files against the failure mode that shipped to production:
 * a source file grows a label, the translations do not follow, and the affected
 * language silently falls back to English. On a page that already carries
 * translated content that reads as a mix of three languages.
 *
 * These checks are deliberately free of TYPO3 services so they stay fast and
 * run before anything else in CI.
 */
final class TranslationCompletenessTest extends UnitTestCase
{
    /**
     * Every language the extension claims to support. Adding one here without
     * shipping the files makes the suite fail, which is the point.
     */
    private const LANGUAGES = ['de', 'pl', 'fr'];

    private const SOURCE_FILES = ['frontend.xlf', 'form.xlf'];

    private static function languageDirectory(): string
    {
        return dirname(__DIR__, 3) . '/Resources/Private/Language';
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function sourceFileAndLanguageProvider(): array
    {
        $sets = [];
        foreach (self::SOURCE_FILES as $sourceFile) {
            foreach (self::LANGUAGES as $language) {
                $sets[$language . ' / ' . $sourceFile] = [$sourceFile, $language];
            }
        }

        return $sets;
    }

    #[Test]
    #[DataProvider('sourceFileAndLanguageProvider')]
    public function translationFileExists(string $sourceFile, string $language): void
    {
        $path = self::languageDirectory() . '/' . $language . '.' . $sourceFile;

        self::assertFileExists(
            $path,
            sprintf(
                'No translation for "%s" in "%s". Without it every label falls back to the English source.',
                $language,
                $sourceFile
            )
        );
    }

    #[Test]
    #[DataProvider('sourceFileAndLanguageProvider')]
    public function translationCoversEverySourceLabel(string $sourceFile, string $language): void
    {
        $sourceIds = $this->labelIdsOf(self::languageDirectory() . '/' . $sourceFile);
        $translatedIds = $this->labelIdsOf(self::languageDirectory() . '/' . $language . '.' . $sourceFile);

        $missing = array_diff($sourceIds, $translatedIds);

        self::assertSame(
            [],
            array_values($missing),
            sprintf(
                'These labels have no "%s" translation and would render in English: %s',
                $language,
                implode(', ', $missing)
            )
        );
    }

    #[Test]
    #[DataProvider('sourceFileAndLanguageProvider')]
    public function translationCarriesNoLabelTheSourceDoesNotKnow(string $sourceFile, string $language): void
    {
        $sourceIds = $this->labelIdsOf(self::languageDirectory() . '/' . $sourceFile);
        $translatedIds = $this->labelIdsOf(self::languageDirectory() . '/' . $language . '.' . $sourceFile);

        $orphaned = array_diff($translatedIds, $sourceIds);

        self::assertSame(
            [],
            array_values($orphaned),
            sprintf(
                'These labels exist only in "%s.%s" and are never read — most likely a renamed or dropped key: %s',
                $language,
                $sourceFile,
                implode(', ', $orphaned)
            )
        );
    }

    #[Test]
    #[DataProvider('sourceFileAndLanguageProvider')]
    public function everyTranslationHasContent(string $sourceFile, string $language): void
    {
        $path = self::languageDirectory() . '/' . $language . '.' . $sourceFile;
        $empty = [];

        foreach ($this->transUnitsOf($path) as $id => $unit) {
            $target = $unit->getElementsByTagName('target')->item(0);
            if ($target === null || trim($target->textContent) === '') {
                $empty[] = $id;
            }
        }

        self::assertSame(
            [],
            $empty,
            sprintf('Empty <target> in "%s.%s" — renders as a blank label: %s', $language, $sourceFile, implode(', ', $empty))
        );
    }

    #[Test]
    #[DataProvider('sourceFileAndLanguageProvider')]
    public function translationDeclaresTheLanguageItsFilenamePromises(string $sourceFile, string $language): void
    {
        $path = self::languageDirectory() . '/' . $language . '.' . $sourceFile;
        $document = new \DOMDocument();
        $document->load($path);

        $file = $document->getElementsByTagName('file')->item(0);
        self::assertNotNull($file, sprintf('No <file> element in "%s".', basename($path)));

        self::assertSame(
            $language,
            $file->getAttribute('target-language'),
            sprintf('"%s" declares a different target-language than its filename says.', basename($path))
        );
    }

    /**
     * @return list<string>
     */
    private function labelIdsOf(string $path): array
    {
        return array_keys($this->transUnitsOf($path));
    }

    /**
     * @return array<string, \DOMElement>
     */
    private function transUnitsOf(string $path): array
    {
        self::assertFileExists($path);

        $document = new \DOMDocument();
        self::assertTrue($document->load($path), sprintf('"%s" is not well-formed XML.', basename($path)));

        $units = [];
        foreach ($document->getElementsByTagName('trans-unit') as $unit) {
            $units[$unit->getAttribute('id')] = $unit;
        }

        return $units;
    }
}
