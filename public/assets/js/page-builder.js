(() => {
  const builder = document.querySelector('[data-page-builder]');
  if (!builder || !builder.dataset.reorderUrl) return;

  let dragged = null;
  const cards = () => [...builder.querySelectorAll('[data-section-id]')];
  const updatePositions = () => cards().forEach((card, index) => {
    const label = card.querySelector('[data-section-position]');
    if (label) label.textContent = String((index + 1) * 10);
  });
  const persist = async () => {
    const data = new URLSearchParams();
    data.set(builder.dataset.csrfName, builder.dataset.csrfHash);
    data.set('order', JSON.stringify(cards().map((card) => Number(card.dataset.sectionId))));
    const response = await fetch(builder.dataset.reorderUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' }, body: data });
    if (!response.ok) throw new Error('Could not save the section order.');
    const result = await response.json();
    if (result.csrfHash) builder.dataset.csrfHash = result.csrfHash;
  };

  cards().forEach((card) => {
    card.addEventListener('dragstart', () => { dragged = card; card.classList.add('is-dragging'); });
    card.addEventListener('dragend', async () => {
      card.classList.remove('is-dragging');
      if (!dragged) return;
      dragged = null;
      updatePositions();
      try { await persist(); } catch (error) { window.alert(error.message); window.location.reload(); }
    });
    card.addEventListener('dragover', (event) => {
      event.preventDefault();
      if (!dragged || dragged === card) return;
      const after = event.clientY > card.getBoundingClientRect().top + card.offsetHeight / 2;
      builder.insertBefore(dragged, after ? card.nextSibling : card);
    });
    card.querySelectorAll('[data-move]').forEach((button) => {
      button.addEventListener('click', async () => {
        const siblings = cards();
        const index = siblings.indexOf(card);
        if (button.dataset.move === 'up' && index > 0) builder.insertBefore(card, siblings[index - 1]);
        if (button.dataset.move === 'down' && index < siblings.length - 1) builder.insertBefore(card, siblings[index + 2] || null);
        updatePositions();
        try { await persist(); } catch (error) { window.alert(error.message); window.location.reload(); }
      });
    });
  });
})();
