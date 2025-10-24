const meta = document.querySelector('meta[name="inquiry-add-to-list"]');

document.querySelectorAll('a.add-to-inquiry-list').forEach(link => {
  link.addEventListener('click', function (e) {
    e.preventDefault();

    fetch(link.getAttribute('href'), {
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

      link.classList.add('added-to-inquiry-list');

      document.querySelectorAll('a.to-inquiry-list').forEach(link => {
        let countSpan = link.querySelector('.inquiry-count');
        if (!countSpan) {
          countSpan = document.createElement('span');
          countSpan.className = 'inquiry-count calltoaction-item-counter';
          link.appendChild(countSpan);
        }
        countSpan.textContent = data.count;
      });

    })
    .catch(error => {
      console.error('Fehler beim Abrufen:', error);
    });
  });
});


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
      let items = Object.values(data.items);

      let count = items.length;
      if (count == 0) {
        return;
      }

      document.querySelectorAll('a.to-inquiry-list').forEach(link => {
        let countSpan = link.querySelector('.inquiry-count');
        if (!countSpan) {
          countSpan = document.createElement('span');
          countSpan.className = 'inquiry-count calltoaction-item-counter';
          link.appendChild(countSpan);
        }
        countSpan.textContent = count;
      });


      items.forEach(item => {
        const inquiryLinks = document.querySelectorAll('a[data-inquiry-item-uid][data-inquiry-item-type]');
        inquiryLinks.forEach(link => {
          const uid = link.getAttribute('data-inquiry-item-uid');
          const type = link.getAttribute('data-inquiry-item-type');
          const addToListLabel = link.getAttribute('data-add-label');
          const removeFromListLabel = link.getAttribute('data-remove-label');
          if (uid === item.uid && type === item.type) {
            link.classList.add('added-to-inquiry-list');
            const labelSpan = link.querySelector('.inquiry-button-label');
            console.debug(link);
            if (labelSpan && removeFromListLabel) {
              labelSpan.textContent = removeFromListLabel;
            }
          }

        });
      });
    })
    .catch(error => {
      console.error('Fehler beim Abrufen:', error);
    });
}

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
