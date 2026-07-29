(function () {
  'use strict';

  const previewableTypes = new Set([
    'image/avif',
    'image/bmp',
    'image/gif',
    'image/jpeg',
    'image/png',
    'image/webp'
  ]);

  function formatBytes(bytes) {
    if (bytes < 1024) {
      return bytes + ' B';
    }

    if (bytes < 1024 * 1024) {
      return (bytes / 1024).toFixed(1) + ' KB';
    }

    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function initializeUpload(root) {
    const input = root.querySelector('[data-file-upload-input]');
    const dropzone = root.querySelector('[data-file-upload-dropzone]');
    const selection = root.querySelector('[data-file-upload-selection]');
    const name = root.querySelector('[data-file-upload-name]');
    const meta = root.querySelector('[data-file-upload-meta]');
    const preview = root.querySelector('[data-file-upload-preview]');
    const fileIcon = root.querySelector('[data-file-upload-file-icon]');
    const clear = root.querySelector('[data-file-upload-clear]');
    const clientError = root.querySelector('[data-file-upload-client-error]');
    const current = root.querySelector('[data-file-upload-current]');
    const previewImages = root.dataset.previewImages === 'true';
    const maxBytes = Number.parseInt(root.dataset.maxBytes || '0', 10);
    let objectUrl = null;

    if (!input || !dropzone || !selection || !name || !meta || !preview || !fileIcon || !clear || !clientError) {
      return;
    }

    function revokePreview() {
      if (objectUrl !== null) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
    }

    function showError(message) {
      clientError.textContent = message;
      clientError.classList.toggle('hidden', message === '');
    }

    function reset() {
      revokePreview();
      input.value = '';
      selection.classList.add('hidden');
      selection.classList.remove('flex');
      preview.classList.add('hidden');
      preview.removeAttribute('src');
      fileIcon.classList.remove('hidden');
      dropzone.classList.remove('border-app-accent/40', 'bg-app-accent/5');
      if (current) {
        current.classList.remove('hidden');
      }
      showError('');
    }

    function update() {
      const files = Array.from(input.files || []);
      if (files.length === 0) {
        reset();
        return;
      }

      const oversized = maxBytes > 0 ? files.find(function (file) {
        return file.size > maxBytes;
      }) : null;
      if (oversized) {
        reset();
        showError(oversized.name + ' exceeds the ' + formatBytes(maxBytes) + ' file size limit.');
        return;
      }

      revokePreview();
      showError('');
      selection.classList.remove('hidden');
      selection.classList.add('flex');
      dropzone.classList.add('border-app-accent/40', 'bg-app-accent/5');
      if (current) {
        current.classList.add('hidden');
      }

      name.textContent = files.length === 1 ? files[0].name : files.length + ' files selected';
      meta.textContent = files.length === 1
        ? formatBytes(files[0].size)
        : formatBytes(files.reduce(function (total, file) { return total + file.size; }, 0));

      const first = files[0];
      if (previewImages && files.length === 1 && previewableTypes.has(first.type)) {
        objectUrl = URL.createObjectURL(first);
        preview.src = objectUrl;
        preview.classList.remove('hidden');
        fileIcon.classList.add('hidden');
      } else {
        preview.classList.add('hidden');
        preview.removeAttribute('src');
        fileIcon.classList.remove('hidden');
      }
    }

    input.addEventListener('change', update);
    clear.addEventListener('click', reset);

    ['dragenter', 'dragover'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        if (input.disabled) {
          return;
        }
        event.preventDefault();
        dropzone.classList.add('border-app-accent', 'bg-app-accent/10');
      });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.remove('border-app-accent', 'bg-app-accent/10');
      });
    });

    dropzone.addEventListener('drop', function (event) {
      if (input.disabled || !event.dataTransfer || event.dataTransfer.files.length === 0) {
        return;
      }

      try {
        input.files = event.dataTransfer.files;
        update();
      } catch (error) {
        showError('Drag and drop is unavailable in this browser. Use the file chooser instead.');
      }
    });

    window.addEventListener('beforeunload', revokePreview, { once: true });
  }

  function initializeAll() {
    document.querySelectorAll('[data-file-upload]').forEach(initializeUpload);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll, { once: true });
  } else {
    initializeAll();
  }
}());
