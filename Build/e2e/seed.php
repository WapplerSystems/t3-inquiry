<?php

/**
 * Builds the page tree the browser tests drive.
 *
 * `typo3 setup --create-site` leaves a bare root page behind. This adds what
 * EXT:inquiry needs on top: a product page carrying the toggle button, a list
 * page holding the "Inquiry: Form" content element, and the site sets that wire
 * the two extensions into the frontend.
 *
 * Idempotent — running it twice leaves the same tree.
 *
 * Usage: php Build/e2e/seed.php
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    $instanceRoot = __DIR__;
    require $instanceRoot . '/vendor/autoload.php';

    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
    Bootstrap::init(require $instanceRoot . '/vendor/autoload.php');

    normaliseSqlitePath($instanceRoot);

    $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

    $rootUid = (int)$connection->executeQuery(
        'SELECT uid FROM pages WHERE is_siteroot = 1 AND deleted = 0 ORDER BY uid ASC LIMIT 1'
    )->fetchOne();

    if ($rootUid === 0) {
        fwrite(STDERR, "No site root found. Run \"typo3 setup --create-site=...\" first.\n");
        exit(1);
    }

    dropWelcomeTemplate($connection);
    $productUid = upsertPage($connection, $rootUid, 'E2E Product', '/product');
    $listUid = upsertPage($connection, $rootUid, 'E2E Inquiry List', '/inquiry-list');
    upsertInquiryFormElement($connection, $listUid);
    writeSiteConfiguration($instanceRoot, $listUid);

    printf("root=%d product=%d list=%d\n", $rootUid, $productUid, $listUid);
    echo "INQUIRY_PRODUCT_PATH=/product\n";
    echo "INQUIRY_LIST_PATH=/inquiry-list\n";
})();

/**
 * TYPO3 writes an absolute path for the sqlite file, so an instance set up in a
 * container and served from the host looks for a database that is not there.
 * Rewriting the path against this directory makes the instance portable — and
 * self-healing when it moves.
 */
function normaliseSqlitePath(string $instanceRoot): void
{
    $settingsFile = $instanceRoot . '/config/system/settings.php';
    if (!is_file($settingsFile)) {
        return;
    }

    $settings = file_get_contents($settingsFile) ?: '';
    if (!preg_match("/'path' => '([^']*\\.sqlite)'/", $settings, $matches)) {
        return;
    }

    $stored = $matches[1];
    $expected = $instanceRoot . '/var/sqlite/' . basename($stored);

    if ($stored === $expected) {
        return;
    }

    file_put_contents($settingsFile, str_replace("'" . $stored . "'", "'" . $expected . "'", $settings));
    printf("sqlite path rewritten to %s\n", $expected);
}

/**
 * "typo3 setup --create-site" leaves a sys_template behind that carries the
 * "powered by TYPO3" placeholder and is set to clear constants and setup. That
 * wipes everything the site sets provide, and the frontend shows the welcome
 * page instead of the fixture — with no error anywhere to explain it.
 *
 * Sets need no sys_template at all, so the record goes.
 */
function dropWelcomeTemplate(object $connection): void
{
    $removed = $connection->executeStatement('DELETE FROM sys_template WHERE root = 1');

    if ($removed > 0) {
        printf("removed %d sys_template record(s) that would override the site sets\n", $removed);
    }
}

function upsertPage(object $connection, int $parentUid, string $title, string $slug): int
{
    $existing = (int)$connection->executeQuery(
        'SELECT uid FROM pages WHERE slug = ? AND deleted = 0 LIMIT 1',
        [$slug]
    )->fetchOne();

    if ($existing > 0) {
        return $existing;
    }

    $connection->insert('pages', [
        'pid' => $parentUid,
        'title' => $title,
        'slug' => $slug,
        'doktype' => 1,
        'hidden' => 0,
        'deleted' => 0,
        'crdate' => time(),
        'tstamp' => time(),
    ]);

    return (int)$connection->lastInsertId();
}

/**
 * The plugin is registered as a content element type, so a plain tt_content row
 * with the matching CType is all it takes — no FlexForm, no list_type.
 */
function upsertInquiryFormElement(object $connection, int $pageUid): void
{
    $flexForm = inquiryFormFlexForm();

    $existing = (int)$connection->executeQuery(
        'SELECT uid FROM tt_content WHERE pid = ? AND CType = ? AND deleted = 0 LIMIT 1',
        [$pageUid, 'inquiry_form']
    )->fetchOne();

    if ($existing > 0) {
        $connection->update('tt_content', ['pi_flexform' => $flexForm], ['uid' => $existing]);

        return;
    }

    $connection->insert('tt_content', [
        'pid' => $pageUid,
        'CType' => 'inquiry_form',
        'header' => 'Inquiry',
        'colPos' => 0,
        'hidden' => 0,
        'deleted' => 0,
        'pi_flexform' => $flexForm,
        'crdate' => time(),
        'tstamp' => time(),
    ]);
}

/**
 * Without a subject and at least one recipient the plugin refuses to render and
 * prints a warning instead of the form — no exception, no log entry, just a
 * missing form. Both live in the FlexForm sheet named "options", and the
 * recipients are a section, so the nesting below is not optional decoration.
 */
function inquiryFormFlexForm(): string
{
    return <<<XML
<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="options">
            <language index="lDEF">
                <field index="settings.subject">
                    <value index="vDEF">E2E inquiry</value>
                </field>
                <field index="settings.recipients">
                    <el index="1">
                        <container index="container">
                            <el>
                                <field index="address">
                                    <value index="vDEF">e2e@example.com</value>
                                </field>
                                <field index="name">
                                    <value index="vDEF">E2E Recipient</value>
                                </field>
                            </el>
                        </container>
                    </el>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>
XML;
}

/**
 * Adds the two site sets and pins the list page uid the page template links to.
 */
function writeSiteConfiguration(string $instanceRoot, int $listPageUid): void
{
    $directories = glob($instanceRoot . '/config/sites/*', GLOB_ONLYDIR) ?: [];
    if ($directories === []) {
        fwrite(STDERR, "No site configuration found.\n");
        exit(1);
    }

    $configFile = $directories[0] . '/config.yaml';
    $config = file_get_contents($configFile) ?: '';

    if (!str_contains($config, 'wapplersystems/inquiry-test-consumer')) {
        $config .= "\ndependencies:\n"
            . "  - wapplersystems/inquiry\n"
            . "  - wapplersystems/inquiry-test-consumer\n";
    }

    if (!str_contains($config, 'listPageUid')) {
        $config .= "\nsettings:\n"
            . "  inquiryTestConsumer:\n"
            . "    listPageUid: " . $listPageUid . "\n";
    }

    file_put_contents($configFile, $config);
}
