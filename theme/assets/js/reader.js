(() => {
  "use strict";
  const progress = document.querySelector("#reading-progress");
  const headings = [
    ...document.querySelectorAll(".article-content h2[id]"),
  ].filter((heading) => !heading.closest(".kn-key-concepts, .kn-takeaways"));
  const links = [...document.querySelectorAll(".toc-link")];
  if (!progress) return;

  let scheduled = false;
  function update() {
    const available = document.documentElement.scrollHeight - innerHeight;
    progress.style.width = `${available > 0 ? Math.min(100, Math.max(0, (scrollY / available) * 100)) : 100}%`;
    let active = headings[0]?.id;
    headings.forEach((heading) => {
      if (heading.getBoundingClientRect().top <= 170) active = heading.id;
    });
    links.forEach((link) => {
      const selected = link.hash === `#${active}`;
      link.classList.toggle("is-active", selected);
      if (selected) link.setAttribute("aria-current", "location");
      else link.removeAttribute("aria-current");
    });
    scheduled = false;
  }
  function schedule() {
    if (!scheduled) {
      scheduled = true;
      requestAnimationFrame(update);
    }
  }
  addEventListener("scroll", schedule, { passive: true });
  addEventListener("resize", schedule);
  update();
})();
