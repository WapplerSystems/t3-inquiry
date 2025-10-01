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
      console.log(data);

      let countSpan = link.querySelector('.inquiry-count');
      if (!countSpan) {
        countSpan = document.createElement('span');
        countSpan.className = 'inquiry-count calltoaction-item-counter';
        link.appendChild(countSpan);
      }
      countSpan.textContent = data.count;


    })
    .catch(error => {
      console.error('Fehler beim Abrufen:', error);
    });
  });
});


const countItemsMeta = document.querySelector('meta[name="inquiry-count-items"]');
if (countItemsMeta) {
  const countItemsUrl = countItemsMeta.getAttribute('content');
  fetch(countItemsUrl, {
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

      if (data.count == 0) {
        return;
      }

      document.querySelectorAll('a.add-to-inquiry-list').forEach(link => {
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
