(() => {
  "use strict";
  const { registerBlockType } = wp.blocks;
  const { createElement: h, useEffect, useRef } = wp.element;
  const { TextControl, SelectControl, Notice } = wp.components;
  const { useBlockProps, InspectorControls } = wp.blockEditor;
  const { __ } = wp.i18n;

  const modes = {
    javascript: "javascript",
    css: "css",
    markup: "htmlmixed",
    json: "application/json",
    php: "application/x-httpd-php",
    bash: "shell",
    python: "python",
    plain: "text/plain",
  };
  const options = [
    ["javascript", "JavaScript"],
    ["css", "CSS"],
    ["markup", "HTML"],
    ["json", "JSON"],
    ["php", "PHP"],
    ["bash", "Shell"],
    ["python", "Python"],
    ["plain", "Plain text"],
  ].map(([value, label]) => ({ value, label }));

  registerBlockType("kamal-notebook/code", {
    edit({ attributes, setAttributes }) {
      const textarea = useRef(null);
      const editor = useRef(null);
      const blockProps = useBlockProps({ className: "knt-editor-block" });

      useEffect(() => {
        if (!textarea.current || !wp.codeEditor?.initialize) return;
        const instance = wp.codeEditor.initialize(textarea.current, {
          codemirror: {
            mode: modes[attributes.language] || "text/plain",
            lineNumbers: true,
            lineWrapping: false,
            indentUnit: 2,
            tabSize: 2,
            lint: false,
          },
        });
        editor.current = instance.codemirror;
        editor.current.on("change", (cm) =>
          setAttributes({ code: cm.getValue() }),
        );
        editor.current.setValue(attributes.code || "");
        return () => {
          editor.current?.toTextArea();
          editor.current = null;
        };
      }, []);

      useEffect(() => {
        editor.current?.setOption(
          "mode",
          modes[attributes.language] || "text/plain",
        );
      }, [attributes.language]);

      useEffect(() => {
        if (
          editor.current &&
          editor.current.getValue() !== (attributes.code || "")
        ) {
          editor.current.setValue(attributes.code || "");
        }
      }, [attributes.code]);

      return h(
        "div",
        blockProps,
        h(
          InspectorControls,
          null,
          h(
            "div",
            { style: { padding: "18px" } },
            h(SelectControl, {
              label: __("Language", "kamal-notebook-tools"),
              value: attributes.language,
              options,
              onChange: (language) => setAttributes({ language }),
            }),
            h(TextControl, {
              label: __("Filename or label", "kamal-notebook-tools"),
              value: attributes.filename,
              onChange: (filename) => setAttributes({ filename }),
            }),
          ),
        ),
        h(
          "div",
          { className: "knt-editor-toolbar" },
          h(TextControl, {
            label: __("Filename", "kamal-notebook-tools"),
            value: attributes.filename,
            onChange: (filename) => setAttributes({ filename }),
          }),
          h(SelectControl, {
            label: __("Language", "kamal-notebook-tools"),
            value: attributes.language,
            options,
            onChange: (language) => setAttributes({ language }),
          }),
        ),
        h(
          "label",
          { className: "knt-editor-label" },
          __("Code", "kamal-notebook-tools"),
        ),
        h("textarea", {
          ref: textarea,
          defaultValue: attributes.code,
          onChange: (event) => setAttributes({ code: event.target.value }),
          spellCheck: false,
          rows: 12,
          "aria-label": __("Code", "kamal-notebook-tools"),
        }),
        !wp.codeEditor?.initialize &&
          h(
            Notice,
            { status: "info", isDismissible: false },
            __(
              "Syntax highlighting is disabled in your WordPress profile; the code remains editable.",
              "kamal-notebook-tools",
            ),
          ),
      );
    },
    save({ attributes }) {
      if (!attributes.code?.trim()) return null;
      return h(
        "div",
        { className: "kn-code kn-code-fallback" },
        h(
          "div",
          { className: "kn-code-head" },
          h("span", null, attributes.filename || attributes.language),
        ),
        h(
          "pre",
          { tabIndex: 0 },
          h(
            "code",
            { className: `language-${attributes.language}` },
            attributes.code,
          ),
        ),
      );
    },
  });
})();
