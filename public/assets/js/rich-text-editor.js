(function () {
  'use strict';

  const allowedTags = new Set([
    'A', 'B', 'BLOCKQUOTE', 'BR', 'CODE', 'DIV', 'EM', 'H2', 'H3', 'I',
    'LI', 'OL', 'P', 'PRE', 'S', 'STRIKE', 'STRONG', 'U', 'UL'
  ]);
  const dropWithContent = new Set([
    'BASE', 'BUTTON', 'EMBED', 'FORM', 'IFRAME', 'INPUT', 'LINK', 'MATH',
    'META', 'OBJECT', 'OPTION', 'SCRIPT', 'SELECT', 'STYLE', 'SVG', 'TEXTAREA'
  ]);

  function safeUrl(value) {
    const url = value.trim();
    if (url === '' || url.startsWith('#')) {
      return true;
    }
    if (url.startsWith('/') && !url.startsWith('//')) {
      return true;
    }

    try {
      const parsed = new URL(url);
      return ['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol);
    } catch (error) {
      return false;
    }
  }

  function sanitize(html) {
    const template = document.createElement('template');
    template.innerHTML = html;

    function clean(parent) {
      Array.from(parent.childNodes).forEach(function (node) {
        if (node.nodeType === Node.COMMENT_NODE) {
          node.remove();
          return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE) {
          return;
        }
        if (dropWithContent.has(node.tagName)) {
          node.remove();
          return;
        }

        clean(node);
        if (!allowedTags.has(node.tagName)) {
          node.replaceWith.apply(node, Array.from(node.childNodes));
          return;
        }

        Array.from(node.attributes).forEach(function (attribute) {
          if (node.tagName !== 'A' || !['href', 'rel', 'target', 'title'].includes(attribute.name)) {
            node.removeAttribute(attribute.name);
          }
        });

        if (node.tagName === 'A') {
          if (!safeUrl(node.getAttribute('href') || '')) {
            node.removeAttribute('href');
          }
          if (node.getAttribute('target') === '_blank') {
            node.setAttribute('rel', 'noopener noreferrer');
          } else {
            node.removeAttribute('target');
            node.removeAttribute('rel');
          }
        }
      });
    }

    clean(template.content);
    return template.innerHTML.trim();
  }

  function initializeEditor(root) {
    const source = root.querySelector('[data-rich-text-source]');
    const surface = root.querySelector('[data-rich-text-surface]');
    const count = root.querySelector('[data-rich-text-count]');
    const block = root.querySelector('[data-rich-text-block]');
    const controls = Array.from(root.querySelectorAll('[data-rich-text-command]'));

    if (!source || !surface || !count || !block) {
      return;
    }

    const required = source.required;

    function sync() {
      source.value = sanitize(surface.innerHTML);
      const length = (surface.textContent || '').trim().length;
      count.textContent = length + (length === 1 ? ' character' : ' characters');
      surface.setAttribute('aria-invalid', required && length === 0 ? 'true' : 'false');
    }

    function run(command, value) {
      surface.focus();
      if (command === 'createLink') {
        const url = window.prompt('Enter a safe HTTP(S), email, telephone, anchor, or relative URL:');
        if (url === null || !safeUrl(url)) {
          return;
        }
        value = url;
      }

      document.execCommand(command, false, value || null);
      sync();
    }

    surface.innerHTML = sanitize(source.value);
    source.required = false;
    source.classList.add('hidden');
    surface.classList.remove('hidden');
    sync();

    controls.forEach(function (control) {
      control.addEventListener('click', function () {
        run(control.dataset.richTextCommand || '', control.dataset.richTextValue || '');
      });
    });

    block.addEventListener('change', function () {
      run('formatBlock', block.value);
      block.value = 'p';
    });

    surface.addEventListener('input', sync);
    surface.addEventListener('blur', function () {
      const clean = sanitize(surface.innerHTML);
      if (clean !== surface.innerHTML) {
        surface.innerHTML = clean;
      }
      sync();
    });
    surface.addEventListener('paste', function (event) {
      event.preventDefault();
      const clipboard = event.clipboardData;
      const html = clipboard ? clipboard.getData('text/html') : '';
      const text = clipboard ? clipboard.getData('text/plain') : '';
      const content = html !== '' ? sanitize(html) : text.replace(/[&<>]/g, function (character) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[character];
      }).replace(/\n/g, '<br>');
      document.execCommand('insertHTML', false, content);
      sync();
    });

    const form = source.closest('form');
    if (form) {
      form.addEventListener('submit', function (event) {
        sync();
        if (required && (surface.textContent || '').trim() === '') {
          event.preventDefault();
          surface.focus();
        }
      });
    }
  }

  function initializeAll() {
    document.querySelectorAll('[data-rich-text-editor]').forEach(initializeEditor);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll, { once: true });
  } else {
    initializeAll();
  }
}());
