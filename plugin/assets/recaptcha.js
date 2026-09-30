(() => {
  "use strict";
  const widget = document.querySelector("[data-knt-recaptcha]");
  if (!widget) return;
  const status = document.querySelector("[data-knt-captcha-status]");
  const error = () => { status.textContent = widget.dataset.error; };
  if (!window.grecaptcha) { error(); return; }
  const timeout = setTimeout(error, 15000);
  window.grecaptcha.ready(() => {
    clearTimeout(timeout);
    try {
      window.grecaptcha.render(widget, {
        sitekey: widget.dataset.sitekey,
        // Compact remains usable after rotation or resizing without resetting a solved token.
        size: "compact",
        theme: document.documentElement.dataset.theme === "dark" ? "dark" : "light",
        callback: () => { status.textContent = ""; },
        "expired-callback": () => { status.textContent = widget.dataset.expired; },
        "error-callback": error,
      });
      status.textContent = "";
    } catch { error(); }
  });
})();
