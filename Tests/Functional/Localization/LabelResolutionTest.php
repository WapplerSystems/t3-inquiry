<?php

declare(strict_types=1);

namespace WapplerSystems\Inquiry\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The unit test next door proves the XLIFF files are complete. This one proves
 * TYPO3 actually finds them: the "<language>.<file>.xlf" naming convention, the
 * extension key in the LLL path and the locale lookup are all involved, and any
 * of them can be right in isolation while the label still renders in English.
 */
final class LabelResolutionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/inquiry',
    ];

    /**
     * Representative labels rather than all of them — the unit test covers
     * completeness, this one covers the lookup path.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function labelProvider(): array
    {
        $submitButton = 'element.inquiryForm.renderingOptions.submitButtonLabel';

        return [
            // The submit button: the most visible label on the list.
            'submit / de' => ['form.xlf', $submitButton, 'de', 'Anfrage absenden'],
            'submit / pl' => ['form.xlf', $submitButton, 'pl', 'Wyślij zapytanie'],
            'submit / fr' => ['form.xlf', $submitButton, 'fr', 'Envoyer la demande'],

            // The postcode field, twice. Both keys must resolve: integrations
            // name the element either "zipcode" or "zip", and the one that finds
            // no key falls through to whatever label it hardcoded itself.
            'zipcode / pl' => ['form.xlf', 'element.zipcode.properties.label', 'pl', 'Kod pocztowy'],
            'zip / pl' => ['form.xlf', 'element.zip.properties.label', 'pl', 'Kod pocztowy'],
            'zipcode / fr' => ['form.xlf', 'element.zipcode.properties.label', 'fr', 'Code postal'],
            'zip / fr' => ['form.xlf', 'element.zip.properties.label', 'fr', 'Code postal'],

            // Frontend labels come from a second file with its own lookup.
            'generatePdf / pl' => ['frontend.xlf', 'generatePdf', 'pl', 'Pobierz PDF'],
            'generatePdf / fr' => ['frontend.xlf', 'generatePdf', 'fr', 'Télécharger le PDF'],
            'flyIn.title / pl' => ['frontend.xlf', 'flyIn.title', 'pl', 'Lista zapytań'],
            'flyIn.title / fr' => ['frontend.xlf', 'flyIn.title', 'fr', 'Liste de demandes'],
        ];
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function labelResolvesToTheTranslatedText(string $file, string $key, string $language, string $expected): void
    {
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create($language);
        $actual = $languageService->sL(sprintf('LLL:EXT:inquiry/Resources/Private/Language/%s:%s', $file, $key));

        self::assertSame(
            $expected,
            $actual,
            sprintf('"%s" in "%s" did not resolve for language "%s".', $key, $file, $language)
        );
    }
}
