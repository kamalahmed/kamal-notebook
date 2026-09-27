(() => {
  "use strict";
  const frame = document.querySelector(".kn-legacy-frame");
  if (!frame) return;
  addEventListener("message", (event) => {
    if (event.source !== frame.contentWindow) return;
    const height = Number(event.data?.knLegacyHeight);
    if (!Number.isFinite(height) || height <= 0) return;
    frame.style.height = `${Math.min(Math.max(height, 400), 30000)}px`;
  });
})();
