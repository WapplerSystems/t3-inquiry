let toggleItemMeta = document.querySelector('meta[name="inquiry-toggle-item"]');
let toggleItemUrl = null;
if (toggleItemMeta) {
  toggleItemUrl = new URL(toggleItemMeta.getAttribute('content'));
}

let removeItemMeta = document.querySelector('meta[name="inquiry-remove-item"]');
let removeItemUrl = null;
if (removeItemMeta) {
  removeItemUrl = new URL(removeItemMeta.getAttribute('content'));
}

let saveSnapshotMeta = document.querySelector('meta[name="inquiry-save-snapshot"]');
let saveSnapshotUrl = null;
if (saveSnapshotMeta) {
  saveSnapshotUrl = saveSnapshotMeta.getAttribute('content');
}

let getPrefillMeta = document.querySelector('meta[name="inquiry-get-prefill"]');
let getPrefillUrl = null;
if (getPrefillMeta) {
  getPrefillUrl = getPrefillMeta.getAttribute('content');
}

let itemsListMetaForSync = document.querySelector('meta[name="inquiry-items-list"]');
let itemsListSyncUrl = itemsListMetaForSync ? itemsListMetaForSync.getAttribute('content') : null;

let inquirySyncChannel = ('BroadcastChannel' in window) ? new BroadcastChannel('tx_inquiry:sync') : null;

function broadcastInquiryItems(items) {
  if (!inquirySyncChannel || !Array.isArray(items)) return;
  inquirySyncChannel.postMessage({ type: 'items-changed', items: items });
}

if (inquirySyncChannel) {
  inquirySyncChannel.addEventListener('message', function (e) {
    if (!e.data || e.data.type !== 'items-changed') return;
    const items = Array.isArray(e.data.items) ? e.data.items : Object.values(e.data.items || {});
    reconcileInquiryItems(items);
  });
}


function addClickListenerToInquiryLinks() {
  if (!toggleItemUrl) {
    return;
  }

  document.querySelectorAll('.toggle-inquiry-item-status-button').forEach(link => {
    if (link._inquiryListenerAdded) return;
    link.addEventListener('click', function (e) {
      e.preventDefault();

      const uid = this.getAttribute('data-inquiry-item-uid');
      const type = this.getAttribute('data-inquiry-item-type');
      const requestUrl = new URL(toggleItemUrl.toString());
      requestUrl.searchParams.set('tx_inquiry[uid]', uid);
      requestUrl.searchParams.set('tx_inquiry[type]', type);

        fetch(requestUrl, {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
          .then(response => {
            if (!response.ok) {
              const fs = async function() {
                const data = await response.json();
                throw new Error('[tx_inquiry] ' + (data.message || 'No error message provided'));
              }
              return fs();
            }
            return response.json();
          })
          .then(data => {
            inquiryListItems = data.items;
            broadcastInquiryItems(inquiryListItems);

            const addToListLabel = link.getAttribute('data-add-label');
            const removeFromListLabel = link.getAttribute('data-remove-label');
            const labelSpan = link.querySelector('.inquiry-button-label');

            if (data.added) {
              link.classList.add('added');
              if (labelSpan && removeFromListLabel) {
                labelSpan.textContent = removeFromListLabel;
              }
            } else if (data.removed) {
              link.classList.remove('added');
              if (labelSpan && removeFromListLabel) {
                labelSpan.textContent = addToListLabel;
              }
            }

            link.blur();

            let count = data.items.length;

            document.querySelectorAll('.to-inquiry-list').forEach(link => {
              let countSpan = link.querySelector('.inquiry-item-counter');
              if (!countSpan) {
                countSpan = document.createElement('span');
                countSpan.className = 'inquiry-item-counter';
                link.appendChild(countSpan);
              }
              countSpan.textContent = count;
            });

          })
          .catch(error => {
            alert(error);
          });
    });
    link._inquiryListenerAdded = true;

  });

}


let inquiryListItems = [];

const itemsListMeta = document.querySelector('meta[name="inquiry-items-list"]');
if (itemsListMeta) {
  const itemsListUrl = itemsListMeta.getAttribute('content');
  fetch(itemsListUrl, {
    headers: {
      'Accept': 'application/json'
    }
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('[tx_inquiry] Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      inquiryListItems = Object.values(data.items);

      let count = inquiryListItems.length;

      /* inquiry links/buttons */
      document.querySelectorAll('.to-inquiry-list').forEach(link => {
        let countSpan = link.querySelector('.inquiry-item-counter');
        if (!countSpan) {
          countSpan = document.createElement('span');
          countSpan.className = 'inquiry-item-counter';
          link.appendChild(countSpan);
        }
        countSpan.textContent = count;
      });


      updateInquiryLinks();

    })
    .catch(error => {
      console.error('[tx_inquiry] Error fetching:', error);
    });
}

window.setInquiryListItems = function (items) {
  inquiryListItems = Array.isArray(items) ? items : [];
  updateInquiryLinks();
};

function updateInquiryLinks() {
  document.querySelectorAll('.toggle-inquiry-item-status-button[data-inquiry-item-uid][data-inquiry-item-type]').forEach(link => {
    const uid = link.getAttribute('data-inquiry-item-uid');
    const type = link.getAttribute('data-inquiry-item-type');
    const addToListLabel = link.getAttribute('data-add-label');
    const removeFromListLabel = link.getAttribute('data-remove-label');
    const inList = inquiryListItems.some(item => uid == item.uid && type == item.type);
    const labelSpan = link.querySelector('.inquiry-button-label');
    if (inList) {
      link.classList.add('added');
      if (labelSpan && removeFromListLabel) {
        labelSpan.textContent = removeFromListLabel;
      }
    } else {
      link.classList.remove('added');
      if (labelSpan && addToListLabel) {
        labelSpan.textContent = addToListLabel;
      }
    }
  });
}

const observer = new MutationObserver(() => {
  observer.disconnect();
  updateInquiryLinks();
  addClickListenerToInquiryLinks();
  observer.observe(document.body, {childList: true, subtree: true});
});
observer.observe(document.body, {childList: true, subtree: true});

function bindInquiryItemDeleteHandlers() {
  if (!removeItemUrl) return;
  document.querySelectorAll('.inquiry-item-delete').forEach(btn => {
    if (btn._inquiryDeleteBound) return;
    btn._inquiryDeleteBound = true;
    btn.addEventListener('click', function (e) {
      e.preventDefault();

      let fieldSetId = this.getAttribute('data-target');
      const uid = this.getAttribute('data-inquiry-item-uid');
      const type = this.getAttribute('data-inquiry-item-type');
      const newRemoveItemUrl = new URL(removeItemUrl.toString());
      newRemoveItemUrl.searchParams.set('tx_inquiry[uid]', uid);
      newRemoveItemUrl.searchParams.set('tx_inquiry[type]', type);

      fetch(newRemoveItemUrl, {
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(response => {
          if (!response.ok) {
            throw new Error('[tx_inquiry] Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.removed === true) {
            let fieldSet = document.getElementById(fieldSetId);
            if (fieldSet) {
              fieldSet.remove();
            }
            if (Array.isArray(data.items)) {
              inquiryListItems = data.items;
              broadcastInquiryItems(inquiryListItems);
            }
            document.dispatchEvent(new CustomEvent('inquiry:item-removed', {
              detail: { uid: uid, type: type }
            }));
          }


        })
        .catch(error => {
          console.error('[tx_inquiry] Error fetching:', error);
        });

    });
  });
}
bindInquiryItemDeleteHandlers();

document.addEventListener('inquiry:item-removed', function (e) {
  const detail = e.detail || {};
  if (!detail.uid || !detail.type) return;
  const btn = document.querySelector('.inquiry-item-delete[data-inquiry-item-uid="' + CSS.escape(detail.uid) + '"][data-inquiry-item-type="' + CSS.escape(detail.type) + '"]');
  if (!btn) return;
  const fieldsetId = btn.getAttribute('data-target');
  if (!fieldsetId) return;
  const fieldset = document.getElementById(fieldsetId);
  if (fieldset) {
    fieldset.remove();
  }
});

document.addEventListener('DOMContentLoaded', function () {
  // Prefill form fields from DB snapshot when an identifier is present in the URL
  const params = new URLSearchParams(window.location.search);
  const identifier = params.get('tx_inquiry[identifier]');
  if (identifier && getPrefillUrl) {
    fetch(getPrefillUrl + '&tx_inquiry[identifier]=' + encodeURIComponent(identifier), {
      headers: { 'Accept': 'application/json' }
    })
      .then(response => response.ok ? response.json() : null)
      .then(data => {
        if (!data || !data.prefill) return;
        Object.entries(data.prefill).forEach(function ([hash, fields]) {
          Object.entries(fields).forEach(function ([fieldKey, value]) {
            const input = document.querySelector('[data-inquiry-pdf-hash="' + CSS.escape(hash) + '"][data-inquiry-pdf-key="' + CSS.escape(fieldKey) + '"]');
            if (input) {
              input.value = value;
            }
          });
        });
      })
      .catch(error => {
        console.error('[tx_inquiry] Error loading prefill:', error);
      });
  }

  addClickListenerToInquiryLinks();
  const form = document.getElementById('inquiryForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      const completedField = document.getElementById('inquiryForm-completed');
      if (completedField) {
        completedField.value = '1';
      }
    });
  }

  bindInquiryGeneratePdfHandlers();
});

function bindInquiryGeneratePdfHandlers() {
  document.querySelectorAll('.inquiry-generate-pdf').forEach(function (link) {
    if (link._inquiryPdfBound) return;
    link._inquiryPdfBound = true;
    link.addEventListener('click', function (e) {
      e.preventDefault();

      if (!saveSnapshotUrl) {
        console.error('[tx_inquiry] inquiry-save-snapshot meta tag missing');
        return;
      }

      const pdfFields = {};
      document.querySelectorAll('[data-inquiry-pdf-key][data-inquiry-pdf-hash]').forEach(function (input) {
        const hash = input.getAttribute('data-inquiry-pdf-hash');
        const key = input.getAttribute('data-inquiry-pdf-key');
        if (!pdfFields[hash]) {
          pdfFields[hash] = {};
        }
        pdfFields[hash][key] = input.value;
      });

      const pdfUrl = this.href;

      fetch(saveSnapshotUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ items: inquiryListItems, prefill: pdfFields })
      })
        .then(response => {
          if (!response.ok) {
            throw new Error('[tx_inquiry] Failed to save snapshot');
          }
          return response.json();
        })
        .then(data => {
          window.location.href = pdfUrl + '&tx_inquiry[identifier]=' + encodeURIComponent(data.identifier);
        })
        .catch(error => {
          console.error('[tx_inquiry] Error saving snapshot:', error);
        });
    });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const completedField = document.getElementById('inquiryForm-completed');
  if (completedField) {
    completedField.value = '';
  }
});

function hashSetFromItems(items) {
  const set = new Set();
  (items || []).forEach(function (item) {
    if (item && item.hash) {
      set.add(item.hash);
    }
  });
  return set;
}

function hashSetsEqual(a, b) {
  if (a.size !== b.size) return false;
  for (const v of a) {
    if (!b.has(v)) return false;
  }
  return true;
}

function captureInquiryFormValues(form) {
  const values = {};
  form.querySelectorAll('input, textarea, select').forEach(el => {
    const name = el.name;
    if (!name) return;
    if (name.includes('[__')) return;
    if (el.type === 'checkbox' || el.type === 'radio') {
      if (el.checked) {
        if (!values[name]) values[name] = [];
        values[name].push(el.value);
      }
    } else if (el.value !== '' && el.value != null) {
      values[name] = el.value;
    }
  });
  return values;
}

function restoreInquiryFormValues(form, values) {
  Object.entries(values).forEach(([name, val]) => {
    const fields = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');
    if (fields.length === 0) return;
    if (Array.isArray(val)) {
      fields.forEach(el => { el.checked = val.includes(el.value); });
    } else {
      fields[0].value = val;
    }
  });
}

function attachInquiryFormSubmitHandler(form) {
  if (!form || form._inquirySubmitHandlerBound) return;
  form._inquirySubmitHandlerBound = true;
  form.addEventListener('submit', function () {
    const completedField = document.getElementById('inquiryForm-completed');
    if (completedField) completedField.value = '1';
  });
}

let inquiryFormSwapInProgress = false;

function swapInquiryForm() {
  if (inquiryFormSwapInProgress) return;
  const form = document.getElementById('inquiryForm');
  if (!form) return;
  const values = captureInquiryFormValues(form);
  inquiryFormSwapInProgress = true;
  fetch(window.location.href, {
    headers: { 'Accept': 'text/html' },
    credentials: 'same-origin'
  })
    .then(r => r.ok ? r.text() : Promise.reject(new Error('Page fetch failed')))
    .then(html => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const newForm = doc.getElementById('inquiryForm');
      if (!newForm) throw new Error('Form not found in fetched page');
      const liveForm = document.getElementById('inquiryForm');
      if (!liveForm) return;
      liveForm.replaceWith(newForm);
      restoreInquiryFormValues(newForm, values);
      attachInquiryFormSubmitHandler(newForm);
      bindInquiryItemDeleteHandlers();
      bindInquiryGeneratePdfHandlers();
    })
    .catch(err => console.error('[tx_inquiry] Form swap failed:', err))
    .finally(() => { inquiryFormSwapInProgress = false; });
}

function reconcileInquiryItems(serverItems) {
  const newSet = hashSetFromItems(serverItems);
  const currentSet = hashSetFromItems(inquiryListItems);
  if (hashSetsEqual(newSet, currentSet)) return;

  inquiryListItems = serverItems;

  const count = inquiryListItems.length;
  document.querySelectorAll('.to-inquiry-list').forEach(link => {
    let countSpan = link.querySelector('.inquiry-item-counter');
    if (!countSpan) {
      countSpan = document.createElement('span');
      countSpan.className = 'inquiry-item-counter';
      link.appendChild(countSpan);
    }
    countSpan.textContent = count;
  });

  updateInquiryLinks();

  document.dispatchEvent(new CustomEvent('inquiry:items-changed', {
    detail: { items: inquiryListItems }
  }));

  if (document.getElementById('inquiryForm')) {
    const renderedHashes = new Set(
      Array.from(document.querySelectorAll('[id^="fieldsetItem_"]'))
        .map(el => el.id.substring('fieldsetItem_'.length))
    );
    if (!hashSetsEqual(renderedHashes, newSet)) {
      swapInquiryForm();
    }
  }
}

document.addEventListener('visibilitychange', function () {
  if (document.visibilityState !== 'visible') return;
  if (!itemsListSyncUrl) return;

  fetch(itemsListSyncUrl, { headers: { 'Accept': 'application/json' } })
    .then(r => r.ok ? r.json() : Promise.reject(new Error('Items-list fetch failed')))
    .then(data => {
      const items = Array.isArray(data.items) ? data.items : Object.values(data.items || {});
      reconcileInquiryItems(items);
    })
    .catch(err => console.error('[tx_inquiry] visibility re-sync failed:', err));
});