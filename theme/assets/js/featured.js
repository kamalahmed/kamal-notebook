(() => {
  "use strict";
  document.querySelectorAll("[data-kn-featured]").forEach((carousel) => {
    const slides = [...carousel.querySelectorAll(".kn-featured-slide")];
    const controls = carousel.querySelector(".kn-featured-controls");
    if (slides.length < 2 || !controls) return;
    let current = 0;
    function show(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, position) => {
        const active = position === current;
        slide.classList.toggle("is-active", active);
        slide.inert = !active;
        if (active) slide.removeAttribute("aria-hidden");
        else slide.setAttribute("aria-hidden", "true");
        // Explicit tabindex also protects keyboard users on older browsers without inert.
        slide.querySelectorAll("a").forEach((link) => active ? link.removeAttribute("tabindex") : link.setAttribute("tabindex", "-1"));
      });
      carousel.querySelector("[data-featured-current]").textContent = String(current + 1);
      carousel.querySelector("[data-featured-status]").textContent = `${slides[current].getAttribute("aria-label")}: ${slides[current].querySelector("h2").textContent}`;
    }
    carousel.querySelector("[data-featured-prev]").addEventListener("click", () => show(current - 1));
    carousel.querySelector("[data-featured-next]").addEventListener("click", () => show(current + 1));
    controls.hidden = false;
  });
})();
