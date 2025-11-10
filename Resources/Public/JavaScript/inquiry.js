let toggleItemMeta = document.querySelector('meta[name="inquiry-toggle-item"]');
let toggleItemUrl = null;
if (toggleItemMeta) {
  toggleItemUrl = toggleItemMeta.getAttribute('content');
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
      let url = new URL(toggleItemUrl);
      url.searchParams.append('uid', uid);
      url.searchParams.append('type', type);

      fetch(url, {
        headers: {
          'Content-Type': 'application/json',
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
          console.error('Fehler beim Abrufen:', error);
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
  observer.observe(document.body, { childList: true, subtree: true });
});
observer.observe(document.body, { childList: true, subtree: true });

document.querySelectorAll('button.inquiry-item-delete').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const targetId = btn.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (input) {
      input.value = '1';
      const form = input.form;
      if (form) {
        form.submit();
      }
    }
  });
})

document.addEventListener('DOMContentLoaded', function() {
  addClickListenerToInquiryLinks();
  const form = document.getElementById('inquiryFormPage');
  if (form) {
    form.addEventListener('submit', function(e) {
      const completedField = document.getElementById('inquiryFormPage-completed');
      if (completedField) {
        completedField.value = '1';
      }
    });
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const completedField = document.getElementById('inquiryFormPage-completed');
  if (completedField) {
    completedField.value = '';
  }
});
