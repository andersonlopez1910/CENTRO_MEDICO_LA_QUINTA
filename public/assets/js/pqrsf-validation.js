(() => {
  const form = document.querySelector('#pqrsf-form');
  if (!form) return;

  const checkbox = form.querySelector('#data_policy');
  const submit = form.querySelector('#pqrsf-submit');
  const modal = document.querySelector('#policy-modal');
  const openButtons = form.querySelectorAll('[data-policy-open]');
  const closeButtons = modal?.querySelectorAll('[data-policy-close]') || [];
  const acceptButton = modal?.querySelector('[data-policy-accept]');
  const feedback = form.querySelector('#pqrsf-feedback');
  let opener = null;

  const updateSubmitState = () => { submit.disabled = !checkbox.checked; };
  const openPolicy = (button) => {
    if (!modal) return;
    opener = button || document.activeElement;
    modal.hidden = false;
    document.body.classList.add('has-modal');
    modal.querySelector('[data-policy-close]')?.focus();
  };
  const closePolicy = () => {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('has-modal');
    if (opener instanceof HTMLElement) opener.focus();
  };
  const showFeedback = (message, isSuccess = false) => {
    if (!feedback) return;
    feedback.hidden = false;
    feedback.className = isSuccess ? 'pqrsf-success pqrsf-success--inline' : 'form-alert';
    feedback.textContent = message;
  };

  const validateVisibleFields = () => {
    const rules = [
      ['request_type', (value) => value !== '', 'Selecciona el tipo de solicitud.'],
      ['document_type', (value) => value !== '', 'Selecciona el tipo de documento.'],
      ['document_number', (value) => /^[A-Za-z0-9.-]{5,30}$/.test(value), 'Escribe un número de documento válido.'],
      ['full_name', (value) => value.length >= 3 && value.length <= 150, 'Escribe tu nombre completo.'],
      ['email', (value, field) => value.length <= 254 && field.validity.valid, 'Escribe un correo electrónico válido.'],
      ['phone', (value) => /^[0-9+() .-]{7,30}$/.test(value), 'Escribe un teléfono de contacto válido.'],
      ['subject', (value) => value.length >= 5 && value.length <= 180, 'El asunto debe tener entre 5 y 180 caracteres.'],
      ['message', (value) => value.length >= 20 && value.length <= 5000, 'Describe tu solicitud en un texto de 20 a 5.000 caracteres.'],
    ];
    const invalid = [];
    rules.forEach(([id, test, message]) => {
      const field = form.querySelector(`#${id}`);
      if (!field) return;
      field.setCustomValidity('');
      const value = field.value.trim();
      const valid = test(value, field);
      field.setCustomValidity(valid ? '' : message);
      field.setAttribute('aria-invalid', String(!valid));
      if (!valid) invalid.push({ field, message });
    });
    if (!checkbox.checked) invalid.push({ field: checkbox, message: 'Debes aceptar el tratamiento de datos personales.' });
    if (invalid.length > 0) console.warn('Validación PQRSF: campos inválidos', invalid.map((item) => item.field.id || item.field.name));
    return invalid;
  };

  openButtons.forEach((button) => button.addEventListener('click', () => openPolicy(button)));
  closeButtons.forEach((button) => button.addEventListener('click', closePolicy));
  acceptButton?.addEventListener('click', () => {
    checkbox.checked = true;
    updateSubmitState();
    closePolicy();
  });
  checkbox.addEventListener('change', () => {
    updateSubmitState();
    if (checkbox.checked) openPolicy(checkbox);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.hidden) closePolicy();
  });
  updateSubmitState();

  form.addEventListener('submit', async (event) => {
    const invalid = validateVisibleFields();
    if (invalid.length > 0) {
      event.preventDefault();
      showFeedback(invalid[0].message);
      invalid[0].field.focus();
      return;
    }

    event.preventDefault();
    submit.disabled = true;
    const originalText = submit.textContent;
    submit.textContent = 'Enviando solicitud…';
    try {
      const response = await fetch(form.dataset.endpoint || 'process-pqrsf.php', {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'No fue posible registrar la solicitud.');
      form.reset();
      updateSubmitState();
      showFeedback(`Solicitud recibida. Tu número de radicado es ${payload.radicado || payload.reference}. Guarda este código para hacer seguimiento.`, true);
    } catch (error) {
      showFeedback(error instanceof Error ? error.message : 'No fue posible registrar la solicitud. Inténtalo de nuevo.');
      submit.disabled = false;
    } finally {
      submit.textContent = originalText;
    }
  });
})();
