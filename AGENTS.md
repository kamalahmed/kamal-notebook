# Verification and delivery

For work on the local or production site, provide visual evidence before claiming completion.

- Capture local and live pages side by side at the same desktop and mobile viewport sizes. For a full-site deployment or demo import, cover every published page and post; for a bounded change, cover every affected page and interaction.
- Inspect the screenshots. HTTP success, matching text, an importer notice, and passing automated tests do not establish visual parity by themselves.
- Include clickable evidence in the final response. State the capture time, URLs, viewport sizes, browser/session state, scope, and any remaining differences.
- Check normal public URLs with a fresh guest session and ordinary repeat visits. When the user reports a discrepancy, inspect the actual affected browser tab when available and preserve its evidence before reloading or clearing caches.
- Distinguish observations from explanations. Do not name a cache layer as the cause without evidence identifying it. Report unresolved differences explicitly.
- Keep generated screenshots and operational reports in the ignored `reports/` or `local/` directories.
