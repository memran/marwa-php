(function () {
  'use strict';

  function isPreviewable(value) {
    return value.startsWith('/') || value.startsWith('https://') || value.startsWith('http://');
  }

  function initializePicker(root) {
    const valueInput = root.querySelector('[data-media-picker-value]');
    const selection = root.querySelector('[data-media-picker-selection]');
    const preview = root.querySelector('[data-media-picker-preview]');
    const icon = root.querySelector('[data-media-picker-icon]');
    const selectedName = root.querySelector('[data-media-picker-name]');
    const selectedPath = root.querySelector('[data-media-picker-path]');
    const clear = root.querySelector('[data-media-picker-clear]');
    const items = Array.from(root.querySelectorAll('[data-media-picker-item]'));
    const search = root.querySelector('[data-media-picker-search]');
    const empty = root.querySelector('[data-media-picker-empty]');
    const upload = root.querySelector('[data-file-upload-input]');

    if (!valueInput || !selection || !preview || !icon || !selectedName || !selectedPath) {
      return;
    }

    function showSelection(value, name, previewUrl) {
      const hasValue = value !== '';
      valueInput.value = value;
      selection.classList.toggle('hidden', !hasValue);
      selection.classList.toggle('flex', hasValue);
      selectedName.textContent = name || value;
      selectedPath.textContent = value;

      const canPreview = hasValue && isPreviewable(previewUrl);
      preview.classList.toggle('hidden', !canPreview);
      icon.classList.toggle('hidden', canPreview);
      if (canPreview) {
        preview.src = previewUrl;
      } else {
        preview.removeAttribute('src');
      }

      if (clear) {
        clear.classList.toggle('hidden', !hasValue);
        clear.classList.toggle('inline-flex', hasValue);
      }

      items.forEach(function (item) {
        const selected = item.dataset.mediaValue === value;
        item.setAttribute('aria-pressed', selected ? 'true' : 'false');
        item.classList.toggle('border-app-accent', selected);
        item.classList.toggle('bg-app-accent/5', selected);
        item.classList.toggle('border-app-border', !selected);
      });

      root.dispatchEvent(new CustomEvent('media-picker:change', {
        bubbles: true,
        detail: { value: value, name: name || value }
      }));
    }

    items.forEach(function (item) {
      item.addEventListener('click', function () {
        showSelection(
          item.dataset.mediaValue || '',
          item.dataset.mediaName || '',
          item.dataset.mediaUrl || ''
        );
      });
    });

    if (valueInput.type !== 'hidden') {
      valueInput.addEventListener('input', function () {
        const value = valueInput.value.trim();
        showSelection(value, value, value);
      });
    }

    if (clear) {
      clear.addEventListener('click', function () {
        showSelection('', '', '');
      });
    }

    if (search) {
      search.addEventListener('input', function () {
        const term = search.value.trim().toLowerCase();
        let visible = 0;

        items.forEach(function (item) {
          const matches = term === '' || (item.dataset.mediaSearch || '').includes(term);
          item.classList.toggle('hidden', !matches);
          if (matches) {
            visible += 1;
          }
        });

        if (empty) {
          empty.classList.toggle('hidden', visible !== 0);
        }
      });
    }

    if (upload) {
      upload.addEventListener('change', function () {
        if (upload.files && upload.files.length > 0) {
          showSelection('', '', '');
        }
      });
    }
  }

  function initializeAll() {
    document.querySelectorAll('[data-media-picker]').forEach(initializePicker);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll, { once: true });
  } else {
    initializeAll();
  }
}());
