(() => {
  document.querySelectorAll('[data-contact-lab]').forEach((lab) => {
    const name = lab.querySelector('[data-name]');
    const email = lab.querySelector('[data-email]');
    const message = lab.querySelector('[data-message]');
    const fields = [name, email, message];
    const result = lab.querySelector('[data-result]');
    const check = () => {
      for (const field of fields) {
        field.setCustomValidity(field.value.trim() ? '' : 'Please complete this field.');
        if (!field.reportValidity()) {
          result.textContent = 'Check the highlighted field, then try again. Nothing was sent.';
          return;
        }
      }
      result.textContent = 'The form passes browser validation. A real form must also validate on the server before sending email. This practice message was not sent.';
    };
    fields.forEach((field) => field.addEventListener('input', () => {
      field.setCustomValidity('');
      result.textContent = 'Details changed. Check the form again when you are ready.';
    }));
    lab.querySelector('[data-check]').addEventListener('click', check);
    lab.querySelector('[data-example]').addEventListener('click', () => {
      name.value = 'Alex Reader';
      email.value = 'alex@example.com';
      message.value = 'I am practising the contact form tutorial.';
      fields.forEach((field) => field.setCustomValidity(''));
      result.textContent = 'Example filled. Select “Check this form” to validate it.';
    });
    lab.querySelector('[data-reset]').addEventListener('click', () => {
      fields.forEach((field) => { field.value = ''; field.setCustomValidity(''); });
      result.textContent = 'Ready to try. No message has been sent.';
      name.focus();
    });
  });
})();
