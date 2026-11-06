(() => {
  const form = document.querySelector('#editor-form');
  const title = document.querySelector('#title');
  const counter = document.querySelector('#title-counter');
  const imageInput = document.querySelector('#post_img');
  const imagePreview = document.querySelector('#image-preview');
  const fileName = document.querySelector('#file-name');
  const saveStatus = document.querySelector('#save-status');
  const submitButton = document.querySelector('#submit-button');

  if (!form) return;

  let dirty = false;
  let previewUrl = null;

  const updateCounter = () => {
    if (!title || !counter) return;
    counter.textContent = `${title.value.length}/120`;
    counter.classList.toggle('near-limit', title.value.length >= 105);
  };

  const markDirty = () => {
    dirty = true;
    if (saveStatus) {
      saveStatus.textContent = 'Unsaved changes';
      saveStatus.classList.add('is-dirty');
    }
  };

  title?.addEventListener('input', () => {
    updateCounter();
    markDirty();
  });

  form.querySelectorAll('input, select, textarea').forEach((field) => {
    if (field !== title) field.addEventListener('input', markDirty);
    field.addEventListener('change', markDirty);
  });

  imageInput?.addEventListener('change', () => {
    const file = imageInput.files?.[0];
    if (!file) return;
    fileName.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)}MB`;

    if (!file.type.startsWith('image/')) return;
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(file);
    if (imagePreview) {
      imagePreview.src = previewUrl;
      imagePreview.alt = `Preview of ${file.name}`;
    } else {
      const preview = document.createElement('img');
      preview.id = 'image-preview';
      preview.className = 'image-preview';
      preview.alt = `Preview of ${file.name}`;
      preview.src = previewUrl;
      imageInput.insertAdjacentElement('beforebegin', preview);
    }
    markDirty();
  });

  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      form.classList.add('show-errors');
      form.querySelector(':invalid')?.focus();
      return;
    }
    dirty = false;
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = 'Saving changes <span class="spinner" aria-hidden="true"></span>';
    }
    if (saveStatus) {
      saveStatus.textContent = 'Saving your changes…';
      saveStatus.classList.remove('is-dirty');
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });

  updateCounter();
})();