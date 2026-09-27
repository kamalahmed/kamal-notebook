(() => {
  "use strict";

  const { createElement: h } = wp.element;
  const { useSelect } = wp.data;
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editor;
  const { __ } = wp.i18n;

  function plainText(value) {
    const element = document.createElement("div");
    element.innerHTML = String(value || "");
    return element.textContent.trim();
  }

  function collectHeadings(blocks, headings = []) {
    blocks.forEach((block) => {
      if (
        block.name === "core/heading" &&
        Number(block.attributes.level || 2) === 2 &&
        !String(block.attributes.className || "").includes("kn-utility-heading")
      ) {
        const title = plainText(block.attributes.content);
        if (title) headings.push({ clientId: block.clientId, title });
      }
      if (block.name === "core/html" || block.name === "core/freeform") {
        const element = document.createElement("div");
        element.innerHTML = String(block.attributes.content || "");
        element.querySelectorAll("h2").forEach((heading, index) => {
          if (heading.className.includes("kn-utility-heading")) return;
          const title = heading.textContent.trim();
          if (title) headings.push({ clientId: `${block.clientId}-${index}`, title });
        });
      }
      if (block.innerBlocks?.length) collectHeadings(block.innerBlocks, headings);
    });
    return headings;
  }

  function isTutorial(blocks) {
    return blocks.some(
      (block) =>
        String(block.attributes.className || "").split(/\s+/).includes("kn-tutorial") ||
        isTutorial(block.innerBlocks || []),
    );
  }

  function OutlinePreview() {
    const { blocks, postType } = useSelect((select) => ({
      blocks: select("core/block-editor").getBlocks(),
      postType: select("core/editor").getCurrentPostType(),
    }), []);
    if (postType !== "post") return null;

    const headings = collectHeadings(blocks);
    const tutorial = isTutorial(blocks);
    return h(
      PluginDocumentSettingPanel,
      {
        name: "kn-outline-preview",
        title: __("Table of contents preview", "kamal-notebook"),
        initialOpen: true,
        className: "kn-editor-toc-panel",
      },
      h(
        "div",
        { className: "kn-editor-toc" },
        h(
          "div",
          { className: "toc-label" },
          tutorial ? __("THE LESSONS", "kamal-notebook") : __("ON THIS PAGE", "kamal-notebook"),
          h("span", null, String(headings.length).padStart(2, "0")),
        ),
        headings.length
          ? h(
              "ol",
              null,
              headings.map((item) =>
                h("li", { className: "toc-link", key: item.clientId }, item.title),
              ),
            )
          : h(
              "p",
              { className: "kn-editor-toc-empty" },
              __("Add Heading 2 blocks to build the article outline.", "kamal-notebook"),
            ),
        h(
          "div",
          { className: "toc-end" },
          h("span", { className: "toc-end-mark", "aria-hidden": "true" }, "✳"),
          h("span", null, tutorial ? __("Go at your own pace.", "kamal-notebook") : __("Take your time with this one.", "kamal-notebook")),
        ),
      ),
    );
  }

  registerPlugin("kn-editor-outline", { render: OutlinePreview });
})();
