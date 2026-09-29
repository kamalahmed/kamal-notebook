(() => {
  "use strict";

  const appearance = document.querySelector("#kn-appearance");
  const appearanceMedia = window.matchMedia("(prefers-color-scheme: dark)");
  function readAppearance() {
    try {
      const choice = localStorage.getItem("kn-appearance");
      return ["light", "dark"].includes(choice) ? choice : "system";
    } catch {
      return "system";
    }
  }
  function applyAppearance() {
    const choice = readAppearance();
    const dark = choice === "dark" || (choice === "system" && appearanceMedia.matches);
    document.documentElement.dataset.theme = dark ? "dark" : "light";
    document.querySelector("#kn-theme-color")?.setAttribute("content", dark ? "#171f1c" : "#f6f2e9");
    if (appearance) appearance.value = choice;
  }
  appearance?.addEventListener("change", () => {
    try {
      if (appearance.value === "system") localStorage.removeItem("kn-appearance");
      else localStorage.setItem("kn-appearance", appearance.value);
    } catch {}
    applyAppearance();
  });
  appearanceMedia.addEventListener?.("change", applyAppearance);
  window.addEventListener("storage", applyAppearance);
  applyAppearance();

  const key = "kn-saved-v1";
  const dialog = document.querySelector("#kn-saved-dialog");
  const savedContent = dialog?.querySelector("[data-saved-content]");
  const saveButton = document.querySelector("[data-save]");
  const actions = document.querySelector(".reader-actions");
  const status = document.querySelector("[data-reader-status]");

  function readSaved() {
    try {
      const items = JSON.parse(localStorage.getItem(key) || "[]");
      return Array.isArray(items) ? items : [];
    } catch {
      return [];
    }
  }

  function writeSaved(items) {
    try {
      localStorage.setItem(key, JSON.stringify(items));
      return true;
    } catch {
      if (status) status.textContent = "Saving is unavailable in this browser.";
      return false;
    }
  }

  function safeUrl(value) {
    try {
      const url = new URL(value, location.href);
      return url.origin === location.origin && /^https?:$/.test(url.protocol)
        ? url.href
        : null;
    } catch {
      return null;
    }
  }

  async function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return;
    }
    const helper = document.createElement("textarea");
    helper.value = value;
    helper.style.position = "fixed";
    helper.style.opacity = "0";
    document.body.append(helper);
    helper.select();
    const copied = document.execCommand("copy");
    helper.remove();
    if (!copied) throw new Error("Copy unavailable");
  }

  function syncSaved() {
    const items = readSaved();
    document.querySelectorAll("[data-saved-count]").forEach((node) => {
      node.textContent = String(items.length);
    });
    if (saveButton && actions) {
      const selected = items.some(
        (item) => String(item.id) === actions.dataset.postId,
      );
      saveButton.setAttribute("aria-pressed", String(selected));
      saveButton.textContent = selected ? "Saved ✓" : "Save for later";
    }
    if (!savedContent) return;
    savedContent.replaceChildren();
    if (!items.length) {
      const empty = document.createElement("p");
      empty.textContent =
        "No stories saved yet. Open an article and choose “Save for later.”";
      savedContent.append(empty);
      return;
    }
    const list = document.createElement("ul");
    items.forEach((item) => {
      const href = safeUrl(item.url);
      if (!href) return;
      const row = document.createElement("li");
      const link = document.createElement("a");
      link.href = href;
      link.textContent = String(item.title || "Untitled story");
      const remove = document.createElement("button");
      remove.type = "button";
      remove.textContent = "Remove";
      remove.addEventListener("click", () => {
        writeSaved(
          readSaved().filter((saved) => String(saved.id) !== String(item.id)),
        );
        syncSaved();
      });
      row.append(link, remove);
      list.append(row);
    });
    savedContent.append(list);
  }

  saveButton?.addEventListener("click", () => {
    if (!actions) return;
    const items = readSaved();
    const id = actions.dataset.postId;
    const saved = items.some((item) => String(item.id) === id);
    const next = saved
      ? items.filter((item) => String(item.id) !== id)
      : [
          ...items,
          { id, title: actions.dataset.title, url: actions.dataset.url },
        ];
    if (writeSaved(next)) {
      syncSaved();
      if (status)
        status.textContent = saved
          ? "Removed from your reading list."
          : "Saved in this browser.";
    }
  });

  actions
    ?.querySelector("[data-share]")
    ?.addEventListener("click", async () => {
      const url = actions.dataset.url;
      const title = actions.dataset.title;
      try {
        if (navigator.share) {
          await navigator.share({ title, url });
          if (status) status.textContent = "Shared.";
        } else {
          await copyText(url);
          if (status) status.textContent = "Story link copied.";
        }
      } catch (error) {
        if (error.name !== "AbortError" && status)
          status.textContent =
            "Sharing is unavailable here. You can copy the address from your browser.";
      }
    });

  document.querySelector("[data-open-saved]")?.addEventListener("click", () => {
    syncSaved();
    if (dialog?.showModal) dialog.showModal();
  });
  dialog
    ?.querySelector("[data-close-saved]")
    ?.addEventListener("click", () => dialog.close());
  dialog?.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });
  window.addEventListener("storage", syncSaved);
  document.addEventListener("knt:lessonchange", syncSaved);
  syncSaved();
})();
