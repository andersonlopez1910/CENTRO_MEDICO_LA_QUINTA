(() => {
  const menuButton = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('#site-navigation');
  const header = document.querySelector('.site-header');

  if (header) {
    const updateHeaderState = () => header.classList.toggle('is-scrolled', window.scrollY > 4);
    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });
  }

  if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
      const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
      menuButton.setAttribute('aria-expanded', String(!isOpen));
      navigation.classList.toggle('is-open', !isOpen);
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || '¿Continuar?')) {
        event.preventDefault();
      }
    });
  });

  const videoForm = document.querySelector('#video-form');
  if (videoForm) {
    const sourceInputs = videoForm.querySelectorAll('input[name="source_type"]');
    const sourceFields = videoForm.querySelectorAll('[data-video-source]');
    const localInput = videoForm.querySelector('#video_file');
    const externalInput = videoForm.querySelector('#external_source');
    const preview = videoForm.querySelector('#video-form-preview');
    let objectUrl = null;

    const externalEmbedUrl = (rawValue) => {
      const iframeMatch = rawValue.match(/<iframe\b[^>]*\bsrc\s*=\s*["']([^"']+)["']/i);
      const candidate = iframeMatch ? iframeMatch[1] : rawValue.trim();
      try {
        const url = new URL(candidate);
        if (url.protocol !== 'https:') return null;
        const host = url.hostname.toLowerCase();
        let id = '';
        if (host === 'youtu.be' || host === 'www.youtu.be') id = url.pathname.slice(1);
        if (['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'].includes(host)) id = url.pathname.startsWith('/embed/') ? url.pathname.slice(7) : url.searchParams.get('v') || '';
        if (/^[A-Za-z0-9_-]{6,20}$/.test(id)) return `https://www.youtube-nocookie.com/embed/${id}`;
        if (['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'].includes(host)) {
          id = url.pathname.split('/').filter(Boolean).pop() || '';
          if (/^\d{6,15}$/.test(id)) return `https://player.vimeo.com/video/${id}`;
        }
      } catch (_) {
        return null;
      }
      return null;
    };

    const updateVideoForm = () => {
      const sourceType = videoForm.querySelector('input[name="source_type"]:checked')?.value || 'local';
      sourceFields.forEach((field) => { field.hidden = field.dataset.videoSource !== sourceType; });
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
      preview.replaceChildren();
      if (sourceType === 'local' && localInput.files?.[0]) {
        objectUrl = URL.createObjectURL(localInput.files[0]);
        const video = document.createElement('video');
        video.controls = true;
        video.preload = 'metadata';
        video.src = objectUrl;
        preview.append(video);
      } else if (sourceType === 'external' && externalInput.value.trim()) {
        const url = externalEmbedUrl(externalInput.value);
        if (url) {
          const iframe = document.createElement('iframe');
          iframe.src = url;
          iframe.title = 'Previsualización del video';
          iframe.allowFullscreen = true;
          preview.append(iframe);
        } else {
          preview.textContent = 'Usa una URL o iframe válido de YouTube o Vimeo.';
        }
      } else {
        preview.textContent = 'Selecciona un archivo o pega una URL para ver la previsualización.';
      }
    };

    sourceInputs.forEach((input) => input.addEventListener('change', updateVideoForm));
    localInput.addEventListener('change', updateVideoForm);
    externalInput.addEventListener('input', updateVideoForm);
    updateVideoForm();
  }

  document.querySelectorAll('[data-copy-video]').forEach((button) => {
    button.addEventListener('click', async () => {
      const code = button.dataset.videoCode || '';
      if (!code) return;
      try {
        await navigator.clipboard.writeText(code);
      } catch (_) {
        const textarea = document.createElement('textarea');
        textarea.value = code;
        document.body.append(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
      }
      const originalText = button.textContent;
      button.textContent = 'Copiado';
      window.setTimeout(() => { button.textContent = originalText; }, 1600);
    });
  });

  const pdfInput = document.querySelector('#document-form #pdf');
  if (pdfInput) {
    const maximumPdfSize = 40 * 1024 * 1024;
    const error = document.querySelector('#pdf-size-error');
    pdfInput.addEventListener('change', () => {
      const file = pdfInput.files?.[0];
      const tooLarge = Boolean(file && file.size > maximumPdfSize);
      pdfInput.setCustomValidity(tooLarge ? 'El archivo supera el límite permitido de 40 MB.' : '');
      if (error) error.hidden = !tooLarge;
    });
  }

  const postForm = document.querySelector('#post-form');
  if (postForm) {
    const imageInput = postForm.querySelector('#image');
    const preview = postForm.querySelector('#post-image-preview');
    const clearButton = postForm.querySelector('[data-clear-post-image]');
    const removeInput = postForm.querySelector('#remove-image');
    const currentImage = preview.dataset.currentImage || '';
    let objectUrl = null;

    const showPlaceholder = (message) => {
      preview.replaceChildren();
      const placeholder = document.createElement('span');
      placeholder.textContent = message;
      preview.append(placeholder);
    };
    const showImage = (url, alt) => {
      preview.replaceChildren();
      const image = document.createElement('img');
      image.src = url;
      image.alt = alt;
      preview.append(image);
    };
    const clearObjectUrl = () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
    };
    const restoreCurrent = () => {
      clearObjectUrl();
      if (currentImage && !(removeInput?.checked)) {
        showImage(currentImage, 'Imagen actual de la publicación');
      } else {
        showPlaceholder(removeInput?.checked ? 'La imagen actual se eliminará al guardar.' : 'Sin imagen seleccionada');
      }
    };

    imageInput?.addEventListener('change', () => {
      clearObjectUrl();
      const file = imageInput.files?.[0];
      if (!file) {
        restoreCurrent();
        return;
      }
      if (removeInput) removeInput.checked = false;
      objectUrl = URL.createObjectURL(file);
      showImage(objectUrl, 'Previsualización de la imagen seleccionada');
    });
    clearButton?.addEventListener('click', () => {
      imageInput.value = '';
      restoreCurrent();
    });
    removeInput?.addEventListener('change', () => {
      if (removeInput.checked) {
        imageInput.value = '';
      }
      restoreCurrent();
    });
  }
})();
