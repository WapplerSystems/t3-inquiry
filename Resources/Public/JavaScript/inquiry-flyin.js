(function () {
    const flyIn = document.getElementById('offcanvasInquiryFlyIn');
    if (!flyIn) {
        return;
    }

    const itemsMeta = document.querySelector('meta[name="inquiry-flyin-items"]');
    const removeMeta = document.querySelector('meta[name="inquiry-remove-item"]');
    if (!itemsMeta) {
        return;
    }
    const itemsUrl = itemsMeta.getAttribute('content');
    const removeUrl = removeMeta ? removeMeta.getAttribute('content') : null;

    const body = flyIn.querySelector('[data-inquiry-flyin-body]');
    const footer = flyIn.querySelector('[data-inquiry-flyin-footer]');

    let contentFresh = false;
    let inFlight = null;

    function refreshBody() {
        if (!body) return Promise.resolve();
        if (inFlight) return inFlight;
        inFlight = fetch(itemsUrl, { headers: { 'Accept': 'text/html' } })
            .then(r => r.ok ? r.text() : Promise.reject(new Error('Failed to load FlyIn items')))
            .then(html => {
                body.innerHTML = html;
                contentFresh = true;
                updateFooterVisibility();
                bindRemoveButtons();
            })
            .catch(err => {
                console.error('[tx_inquiry] FlyIn fetch failed:', err);
            })
            .finally(() => {
                inFlight = null;
            });
        return inFlight;
    }

    // Only fetch when the body does not already match the current items, so a
    // panel that was warmed up beforehand opens without waiting for a request.
    function ensureBody() {
        return contentFresh ? Promise.resolve() : refreshBody();
    }

    function updateFooterVisibility() {
        if (!footer) return;
        const empty = body.querySelector('[data-inquiry-flyin-empty="true"]');
        footer.style.display = empty ? 'none' : '';
    }

    function bindRemoveButtons() {
        if (!body || !removeUrl) return;
        body.querySelectorAll('.inquiry-flyin-item-remove').forEach(btn => {
            if (btn._flyInRemoveBound) return;
            btn._flyInRemoveBound = true;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const uid = this.getAttribute('data-inquiry-item-uid');
                const type = this.getAttribute('data-inquiry-item-type');
                // Second argument is required: removeUrl is root-relative when
                // the site base is host-relative, and new URL() would throw.
                const url = new URL(removeUrl, window.location.href);
                url.searchParams.append('tx_inquiry[uid]', uid);
                url.searchParams.append('tx_inquiry[type]', type);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('Remove failed')))
                    .then(data => {
                        if (data && Array.isArray(data.items)) {
                            if (typeof window.setInquiryListItems === 'function') {
                                window.setInquiryListItems(data.items);
                            }
                            if (typeof window.broadcastInquiryItems === 'function') {
                                window.broadcastInquiryItems(data.items);
                            }
                        }
                        document.dispatchEvent(new CustomEvent('inquiry:item-removed', {
                            detail: { uid: uid, type: type }
                        }));
                    })
                    .catch(err => console.error('[tx_inquiry] FlyIn remove failed:', err));
            });
        });
    }

    flyIn.addEventListener('show.bs.offcanvas', ensureBody);

    // Warm the panel up as soon as the visitor shows intent to open it. The
    // request then overlaps the offcanvas animation instead of following it.
    flyIn.ownerDocument.querySelectorAll('.inquiry-flyin-trigger').forEach(trigger => {
        ['pointerenter', 'focus', 'touchstart'].forEach(type => {
            trigger.addEventListener(type, ensureBody, { once: true, passive: true });
        });
    });

    document.addEventListener('inquiry:items-changed', function () {
        contentFresh = false;
        if (flyIn.classList.contains('show')) {
            refreshBody();
        }
    });

    document.addEventListener('inquiry:item-removed', function (e) {
        const detail = e.detail || {};

        if (!body || !detail.uid || !detail.type) {
            refreshBody();
            return;
        }

        const btn = body.querySelector('.inquiry-flyin-item-remove[data-inquiry-item-uid="' + CSS.escape(detail.uid) + '"][data-inquiry-item-type="' + CSS.escape(detail.type) + '"]');
        const li = btn ? btn.closest('.inquiry-flyin-item') : null;
        if (li) {
            li.remove();
        } else {
            contentFresh = false;
        }
        if (body.querySelectorAll('.inquiry-flyin-item').length === 0) {
            refreshBody();
        }
    });
})();