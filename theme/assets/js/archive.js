(() => {
  "use strict";
  const archive = document.querySelector("[data-kn-archive]");
  if (!archive || !window.fetch || !window.AbortController) return;
  const status = archive.querySelector(".archive-status");
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  let controller;
  let renderedURL = location.href;
  let request = 0;
  let inputRevision = 0;
  archive.querySelector("#story-search")?.addEventListener("input", () => { ++inputRevision; });

  async function load(url, { history = true, focus = false } = {}) {
    const id = ++request;
    const revision = inputRevision;
    controller?.abort();
    controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    archive.setAttribute("aria-busy", "true");
    status.classList.remove("has-error");
    status.textContent = archive.dataset.loading;
    archive.classList.add("is-loading");
    try {
      const response = await fetch(url, { signal: controller.signal, credentials: "same-origin" });
      if (!response.ok || new URL(response.url).origin !== location.origin) throw new Error("Unavailable archive");
      const doc = new DOMParser().parseFromString(await response.text(), "text/html");
      const incoming = doc.querySelector("[data-kn-archive] .archive-results");
      if (!incoming) throw new Error("Missing archive");
      if (id !== request) return;
      const old = archive.querySelector(".archive-results");
      const restoreFocus = focus || old.contains(document.activeElement);
      old.replaceWith(incoming);
      // Keep direct links and in-place views equivalent, including featured exclusions.
      const intro = document.querySelector(".intro");
      const nextIntro = doc.querySelector(".intro");
      const featuredLinks = (node) => [...node.querySelectorAll(".kn-featured .lead-story")].map((link) => link.href).join("|");
      if (intro && nextIntro && featuredLinks(intro) !== featuredLinks(nextIntro)) {
        intro.replaceWith(nextIntro);
        document.dispatchEvent(new Event("kn:archivechange"));
      }
      archive.querySelectorAll(".topic-tab").forEach((link, index) => {
        const selected = doc.querySelectorAll(".topic-tab")[index]?.hasAttribute("aria-current");
        link.classList.toggle("is-active", !!selected);
        if (selected) link.setAttribute("aria-current", "page");
        else link.removeAttribute("aria-current");
      });
      const search = archive.querySelector("#story-search");
      if (search && revision === inputRevision) search.value = doc.querySelector("#story-search")?.value || "";
      document.title = doc.title;
      if (history && url.href !== location.href) window.history.pushState(null, "", url);
      renderedURL = location.href;
      status.textContent = incoming.querySelector(".result-count").textContent.trim();
      if (restoreFocus) incoming.focus({ preventScroll: true });
      if (history) {
        const controls = archive.querySelector(".explore-bar") || archive;
        const top = controls.getBoundingClientRect().top;
        if (top < 0 || top > innerHeight / 2) controls.scrollIntoView({ block: "start", behavior: motion.matches ? "instant" : "smooth" });
      }
      if (!motion.matches) {
        incoming.querySelectorAll(".story-card, .empty-state").forEach((card, index) => {
          card.animate?.([{ opacity: 0, transform: "translateY(12px)" }, { opacity: 1, transform: "translateY(0)" }], {
            duration: 320, delay: Math.min(index, 5) * 35, easing: "cubic-bezier(.2,.7,.2,1)", fill: "backwards",
          });
        });
      }
    } catch {
      if (id !== request) return;
      // History already moved; a normal navigation restores a consistent URL/view.
      if (!history) { location.assign(url); return; }
      const link = document.createElement("a");
      link.href = url.href;
      link.textContent = archive.dataset.error;
      status.classList.add("has-error");
      status.replaceChildren(link);
    } finally {
      clearTimeout(timeout);
      if (id === request) {
        archive.setAttribute("aria-busy", "false");
        archive.classList.remove("is-loading");
      }
    }
  }

  archive.addEventListener("click", (event) => {
    const link = event.target.closest(".topic-tab, .pagination a, .empty-state a");
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute("download")) return;
    if (link.closest(".empty-state") && !archive.querySelector(".explore-bar")) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin) return;
    event.preventDefault();
    load(url, { focus: !!link.closest(".pagination, .empty-state") });
  });
  archive.querySelector("form.search-field")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const url = new URL(event.currentTarget.action);
    url.search = new URLSearchParams(new FormData(event.currentTarget)).toString();
    url.hash = "stories";
    load(url);
  });
  window.addEventListener("popstate", () => {
    if (location.href.split("#")[0] === renderedURL.split("#")[0]) {
      ++request;
      controller?.abort();
      archive.setAttribute("aria-busy", "false");
      archive.classList.remove("is-loading");
      status.classList.remove("has-error");
      status.textContent = "";
      return;
    }
    load(new URL(location.href), { history: false });
  });
})();
