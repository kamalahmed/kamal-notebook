(() => {
  document.querySelectorAll('[data-reading-lab]').forEach((lab) => {
    const words = lab.querySelector('[data-words]');
    const speed = lab.querySelector('[data-speed]');
    const output = lab.querySelector('output');
    const update = () => {
      const count = Number(words.value);
      const rate = Number(speed.value);
      if (!words.value.trim() || !Number.isInteger(count) || count < 0 || count > 1000000) {
        output.textContent = 'Enter a whole number from 0 to 1,000,000.';
        words.setAttribute('aria-invalid', 'true');
        return;
      }
      words.removeAttribute('aria-invalid');
      const minutes = Math.ceil(count / rate);
      output.textContent = count === 0
        ? '0 words → No reading time label'
        : `${count.toLocaleString('en')} ÷ ${rate} = ${(count / rate).toFixed(2)} → ${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`;
    };
    words.addEventListener('input', update);
    speed.addEventListener('change', update);
    lab.querySelector('[data-reset]').addEventListener('click', () => {
      words.value = '450';
      speed.value = '200';
      update();
    });
    update();
  });
})();
