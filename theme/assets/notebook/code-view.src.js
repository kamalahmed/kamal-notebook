import Prism from "prismjs/components/prism-core";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-json";
import "prismjs/components/prism-markup-templating";
import "prismjs/components/prism-php";
import "prismjs/components/prism-bash";
import "prismjs/components/prism-python";

Prism.manual = true;
function highlightCode() {
  document.querySelectorAll(".kn-code code").forEach((code) => {
    if (!code.dataset.kntHighlighted) {
      Prism.highlightElement(code);
      code.dataset.kntHighlighted = "1";
    }
  });
}
highlightCode();
document.addEventListener("knt:lessonchange", highlightCode);

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

document.addEventListener("click", async (event) => {
    const button = event.target.closest(".kn-code [data-copy-code]");
    if (!button) return;
    const code = button.closest(".kn-code")?.querySelector("code")?.textContent;
    if (!code) return;
    try {
      await copyText(code);
      button.textContent = "Copied ✓";
    } catch {
      button.textContent = "Copy unavailable";
    }
    setTimeout(() => {
      button.textContent = "Copy code";
    }, 2000);
});
