(() => {
  const dialog = document.querySelector('#media-picker');
  if (!dialog) return;

  // Uploads are deliberately centralised in the authenticated media library,
  // where type and size checks run. Do not let the editor create blob attachments.
  document.querySelectorAll('trix-editor').forEach((editor) => {
    editor.addEventListener('trix-file-accept', (event) => event.preventDefault());
  });

  let targetInput = null;
  let imageInput = null;
  const openPicker = (inputId) => {
    targetInput = inputId;
    imageInput = null;
    if (typeof dialog.showModal === 'function') dialog.showModal();
  };

  document.querySelectorAll('[data-media-picker]').forEach((button) => {
    button.addEventListener('click', () => openPicker(button.dataset.editorInput));
  });
  document.querySelectorAll('[data-image-picker]').forEach((button) => {
    button.addEventListener('click', () => {
      targetInput = null;
      imageInput = document.querySelector(button.dataset.imagePicker);
      if (imageInput && typeof dialog.showModal === 'function') dialog.showModal();
    });
  });

  document.querySelector('[data-close-media-picker]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  document.querySelectorAll('[data-insert-media]').forEach((button) => {
    button.addEventListener('click', () => {
      if (imageInput && button.dataset.url) {
        imageInput.value = button.dataset.url;
        dialog.close();
        imageInput.focus();
        return;
      }
      const editor = document.querySelector(`trix-editor[input="${targetInput}"]`);
      if (!editor || !button.dataset.url) return;
      const image = document.createElement('img');
      image.src = button.dataset.url;
      image.alt = button.dataset.alt || '';
      editor.editor.insertHTML(`<figure>${image.outerHTML}</figure>`);
      dialog.close();
      editor.focus();
    });
  });
})();
