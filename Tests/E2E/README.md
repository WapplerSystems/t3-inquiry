# Browser tests

These cover the parts of the extension that only exist while a browser is
running: the toggle round trip, the item counter, the inquiry list, the fly-in
panel and the synchronisation between open tabs. A PHP test cannot reach any of
them.

## What they need

A running TYPO3 with EXT:inquiry **and a consumer that resolves items**. The
extension alone renders `ERROR: Item cannot be resolved` instead of a button —
something has to answer `CanResolveItemEvent`. `Tests/Fixtures/Extensions/
inquiry_test_consumer` is the smallest such consumer and is what the functional
PHP tests use.

The instance has to offer two pages:

| Variable | What it points at |
|---|---|
| `INQUIRY_BASE_URL` | the site root, e.g. `https://example.ddev.site` |
| `INQUIRY_PRODUCT_PATH` | a page carrying a toggle button and a `.to-inquiry-list` trigger |
| `INQUIRY_LIST_PATH` | the page holding the `Inquiry: Form` content element |

Nothing is hardcoded, because the extension does not ship a page tree — every
installation names its pages differently.

## Running them

```bash
cd Tests/E2E
npm install
npx playwright install chromium

INQUIRY_BASE_URL=https://example.ddev.site \
INQUIRY_PRODUCT_PATH=/some-product \
INQUIRY_LIST_PATH=/inquiry-list \
npm test
```

`npm run test:headed` watches it happen, `npm run report` opens the HTML report
after a failure. Traces and screenshots are kept for failed tests only.

## Why they are not in CI yet

CI would need to build the page tree first — install TYPO3, enable both
extensions, create the two pages and place the content element. That seeding
step does not exist yet; until it does these run locally against an instance you
already have. The PHP suites in `phpunit.xml` and `phpunit.functional.xml` do
run in CI and cover everything that does not need a browser.

## Adding a test

`support/inquiry.ts` holds the helpers. Prefer waiting on a state the code
actually reaches — `addFirstItem()` waits for the `added` class rather than
sleeping — and reach for `expect.poll()` when a change arrives over the
BroadcastChannel instead of through a request.

Note that the counter is empty at zero rather than showing `0`, on purpose.
