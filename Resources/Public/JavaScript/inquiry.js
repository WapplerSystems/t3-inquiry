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
      toggleItemUrl.searchParams.append('tx_inquiry[uid]', uid);
      toggleItemUrl.searchParams.append('tx_inquiry[type]', type);

        fetch(toggleItemUrl, {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
          .then(response => {
            if (!response.ok) {
              const fs = async function() {
                const data = await response.json();
                throw new Error(data.message || 'No error message provided');
              }
              return fs();
            }
            return response.json();
          })
          .then(data => {
            inquiryListItems = data.items;

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
        throw new Error('Netzwerk-Antwort war nicht ok');
      }
      return response.json();
    })
    .then(data => {
      inquiryListItems = Object.values(data.items);

      let count = inquiryListItems.length;
      if (count == 0) {
        return;
      }

      /* inquiry links/buttons */
      document.querySelectorAll('.to-inquiry-list').forEach(link => {
        let countSpan = link.querySelector('.inquiry-count');
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
      console.error('Fehler beim Abrufen:', error);
    });
}

function updateInquiryLinks() {
  inquiryListItems.forEach(item => {
    const inquiryLinks = document.querySelectorAll('a[data-inquiry-item-uid][data-inquiry-item-type], button[data-inquiry-item-uid][data-inquiry-item-type]');
    inquiryLinks.forEach(link => {
      const uid = link.getAttribute('data-inquiry-item-uid');
      const type = link.getAttribute('data-inquiry-item-type');
      const addToListLabel = link.getAttribute('data-add-label');
      const removeFromListLabel = link.getAttribute('data-remove-label');
      if (uid == item.uid && type == item.type) {
        link.classList.add('added');
        const labelSpan = link.querySelector('.inquiry-button-label');
        if (labelSpan && removeFromListLabel) {
          labelSpan.textContent = removeFromListLabel;
        }
      }
    });
  });
}

const observer = new MutationObserver(() => {
  observer.disconnect();
  updateInquiryLinks();
  addClickListenerToInquiryLinks();
  observer.observe(document.body, {childList: true, subtree: true});
});
observer.observe(document.body, {childList: true, subtree: true});

document.querySelectorAll('.inquiry-item-delete').forEach(btn => {
  btn.addEventListener('click', function (e) {
    e.preventDefault();

    let fieldSetId = this.getAttribute('data-target');
    let newRemoveItemUrl = removeItemUrl;
    const uid = this.getAttribute('data-inquiry-item-uid');
    const type = this.getAttribute('data-inquiry-item-type');
    newRemoveItemUrl.searchParams.append('tx_inquiry[uid]', uid);
    newRemoveItemUrl.searchParams.append('tx_inquiry[type]', type);

    fetch(newRemoveItemUrl, {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Netzwerk-Antwort war nicht ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.removed === true) {
          let fieldSet = document.getElementById(fieldSetId);
          if (fieldSet) {
            fieldSet.remove();
          }
        }


      })
      .catch(error => {
        console.error('Fehler beim Abrufen:', error);
      });

  });
})

document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);
  params.forEach(function (value, key) {
    const match = key.match(/^prefill\[([^\]]+)\]\[([^\]]+)\]$/);
    if (match) {
      const hash = match[1];
      const fieldKey = match[2];
      const input = document.querySelector('[data-inquiry-pdf-hash="' + hash + '"][data-inquiry-pdf-key="' + fieldKey + '"]');
      if (input) {
        input.value = value;
      }
    }
  });

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

  document.querySelectorAll('.inquiry-generate-pdf').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();

      let url = this.href;
      const pdfParams = [];

      document.querySelectorAll('[data-inquiry-pdf-key][data-inquiry-pdf-hash]').forEach(function (input) {
        const hash = input.getAttribute('data-inquiry-pdf-hash');
        const key = input.getAttribute('data-inquiry-pdf-key');
        pdfParams.push('pdf_fields[' + hash + '][' + key + ']=' + encodeURIComponent(input.value));
      });

      if (pdfParams.length > 0) {
        url += '&' + pdfParams.join('&');
      }

      window.location.href = url;
    });
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const completedField = document.getElementById('inquiryForm-completed');
  if (completedField) {
    completedField.value = '';
  }
});
