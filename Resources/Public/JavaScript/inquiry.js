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

      console.debug(data);

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
