# Browser tests

These cover the parts of the extension that only exist while a browser is
running: the toggle round trip, the item counter, the inquiry list, the fly-in
panel and the synchronisation between open tabs. A PHP test cannot reach any of
them.

## Running them against the bundled instance

`Build/e2e` builds a throwaway TYPO3 on sqlite with everything in place:

```bash
Build/e2e/setup.sh 8099
php -S 127.0.0.1:8099 -t Build/e2e/public Build/e2e/router.php &

cd Tests/E2E
npm install
npx playwright install chromium

INQUIRY_BASE_URL=http://127.0.0.1:8099 \
INQUIRY_PRODUCT_PATH=/product \
INQUIRY_LIST_PATH=/inquiry-list \
npm test
```

That is exactly what CI does. Around seven seconds for the full suite.

## Running them against your own instance

Any TYPO3 with EXT:inquiry **and a consumer that resolves items** will do. The
extension alone renders `ERROR: Item cannot be resolved` instead of a button —
something has to answer `CanResolveItemEvent`.
`Tests/Fixtures/Extensions/inquiry_test_consumer` is the smallest such consumer
and is what `Build/e2e` and the functional PHP tests use.

The instance has to offer two pages:

| Variable | What it points at |
|---|---|
| `INQUIRY_BASE_URL` | the site root, e.g. `https://example.ddev.site` |
| `INQUIRY_PRODUCT_PATH` | a page carrying a toggle button and a `.to-inquiry-list` trigger |
| `INQUIRY_LIST_PATH` | the page holding the `Inquiry: Form` content element |

Nothing is hardcoded, because the extension does not ship a page tree — every
installation names its pages differently.

```bash
INQUIRY_BASE_URL=https://example.ddev.site \
INQUIRY_PRODUCT_PATH=/some-product \
INQUIRY_LIST_PATH=/inquiry-list \
npm test
```

`npm run test:headed` watches it happen, `npm run report` opens the HTML report
after a failure. Traces and screenshots are kept for failed tests only.

## Two things the fixture had to learn the hard way

`typo3 setup --create-site` leaves a `sys_template` behind that clears constants
and setup and renders the "powered by TYPO3" placeholder. With site sets it is
not needed, and while it is there the frontend silently shows the welcome page
instead of anything else. `seed.php` deletes it.

The `Inquiry: Form` element refuses to render without a subject and at least one
recipient, printing a warning in place of the form. Both live in the FlexForm
sheet named `options`, and the recipients are a section — `seed.php` writes the
nesting out in full.

## Adding a test

`support/inquiry.ts` holds the helpers. Prefer waiting on a state the code
actually reaches — `addFirstItem()` waits for the `added` class rather than
sleeping — and reach for `expect.poll()` when a change arrives over the
BroadcastChannel instead of through a request.

Note that the counter is empty at zero rather than showing `0`, on purpose.
