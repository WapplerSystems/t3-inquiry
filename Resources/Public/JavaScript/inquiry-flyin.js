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

    function refreshBody() {
        if (!body) return Promise.resolve();
        return fetch(itemsUrl, { headers: { 'Accept': 'text/html' } })
            .then(r => r.ok ? r.text() : Promise.reject(new Error('Failed to load FlyIn items')))
            .then(html => {
                body.innerHTML = html;
                updateFooterVisibility();
                bindRemoveButtons();
            })
            .catch(err => {
                console.error('[tx_inquiry] FlyIn fetch failed:', err);
            });
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
                const url = new URL(removeUrl);
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

    function decrementBadges() {
        document.querySelectorAll('.to-inquiry-list .inquiry-item-counter').forEach(span => {
            const current = parseInt(span.textContent, 10) || 0;
            span.textContent = Math.max(0, current - 1);
        });
    }

    flyIn.addEventListener('show.bs.offcanvas', refreshBody);

    document.addEventListener('inquiry:items-changed', function () {
        if (flyIn.classList.contains('show')) {
            refreshBody();
        }
    });

    document.addEventListener('inquiry:item-removed', function (e) {
        const detail = e.detail || {};
        decrementBadges();

        if (!body || !detail.uid || !detail.type) {
            refreshBody();
            return;
        }

        const btn = body.querySelector('.inquiry-flyin-item-remove[data-inquiry-item-uid="' + CSS.escape(detail.uid) + '"][data-inquiry-item-type="' + CSS.escape(detail.type) + '"]');
        const li = btn ? btn.closest('.inquiry-flyin-item') : null;
        if (li) {
            li.remove();
        }
        if (body.querySelectorAll('.inquiry-flyin-item').length === 0) {
            refreshBody();
        }
    });
})();