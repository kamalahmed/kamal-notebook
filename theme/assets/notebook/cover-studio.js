(() => {
  "use strict";

  const form = document.querySelector("#knt-cover-form");
  const canvas = document.querySelector("#knt-cover-canvas");
  if (!form || !canvas || !window.kntCoverStudio) return;
  const ctx = canvas.getContext("2d");
  const post = form.querySelector("#knt-cover-post");
  const title = form.querySelector("#knt-cover-title");
  const tags = form.querySelector("#knt-cover-tags");
  const palette = form.querySelector("#knt-cover-palette");
  const motif = form.querySelector("#knt-cover-motif");
  const status = form.querySelector("#knt-cover-status");
  const save = form.querySelector("#knt-cover-save");
  let seed = 13741;

  const colors = {
    forest: { paper: "#f6f2e9", ink: "#173a31", field: "#164b3d", accent: "#e86646", soft: "#dbe8d7", light: "#f5ce7a" },
    sage: { paper: "#f5f1e7", ink: "#173a31", field: "#bdcfb6", accent: "#e86646", soft: "#ecede2", light: "#f5d17d" },
    night: { paper: "#e9ede5", ink: "#173a31", field: "#142d2a", accent: "#eb7958", soft: "#dce6dc", light: "#efd088" },
  };

  function randomFactory(value) {
    let state = value >>> 0;
    return () => {
      state ^= state << 13;
      state ^= state >>> 17;
      state ^= state << 5;
      return (state >>> 0) / 4294967296;
    };
  }

  function drawLines(x, y, width, height, color, alpha = 0.11) {
    ctx.strokeStyle = color;
    ctx.globalAlpha = alpha;
    ctx.lineWidth = 1;
    for (let px = x; px <= x + width; px += 36) {
      ctx.beginPath(); ctx.moveTo(px, y); ctx.lineTo(px, y + height); ctx.stroke();
    }
    for (let py = y; py <= y + height; py += 36) {
      ctx.beginPath(); ctx.moveTo(x, py); ctx.lineTo(x + width, py); ctx.stroke();
    }
    ctx.globalAlpha = 1;
  }

  function drawOrbit(c, rand) {
    const x = 954 + (rand() - 0.5) * 65;
    const y = 436 + (rand() - 0.5) * 70;
    ctx.strokeStyle = c.soft;
    ctx.lineWidth = 2;
    for (const r of [153, 207, 265]) {
      ctx.beginPath(); ctx.ellipse(x, y, r, r * 0.9, -0.28, 0, Math.PI * 2); ctx.stroke();
    }
    ctx.fillStyle = c.light;
    ctx.beginPath(); ctx.arc(x, y, 121, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = c.accent;
    ctx.beginPath(); ctx.arc(x + 171, y - 128, 37, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = c.paper;
    ctx.beginPath(); ctx.arc(x - 177, y + 121, 14, 0, Math.PI * 2); ctx.fill();
    ctx.strokeStyle = c.ink; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(x - 53, y); ctx.lineTo(x + 53, y); ctx.moveTo(x, y - 53); ctx.lineTo(x, y + 53); ctx.stroke();
  }

  function drawPages(c, rand) {
    const x = 820 + rand() * 40, y = 205 + rand() * 45;
    [[-0.17, c.accent, -42, 28], [0.12, c.light, 38, -17], [-0.035, c.paper, 4, 12]].forEach(([angle, color, dx, dy], index) => {
      ctx.save(); ctx.translate(x + dx + 156, y + dy + 204); ctx.rotate(angle);
      ctx.fillStyle = color; ctx.fillRect(-156, -204, 312, 408);
      ctx.strokeStyle = c.ink; ctx.lineWidth = 2; ctx.strokeRect(-156, -204, 312, 408);
      if (index === 2) {
        ctx.fillStyle = c.accent; ctx.fillRect(-111, -146, 116, 10);
        ctx.fillStyle = c.ink;
        for (let i = 0; i < 5; i++) ctx.fillRect(-111, -65 + i * 43, i === 3 ? 153 : 218, 5);
        ctx.beginPath(); ctx.arc(89, 135, 31, 0, Math.PI * 2); ctx.fill();
        ctx.strokeStyle = c.paper; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(76, 135); ctx.lineTo(86, 145); ctx.lineTo(104, 123); ctx.stroke();
      }
      ctx.restore();
    });
  }

  function drawPath(c, rand) {
    const offset = (rand() - 0.5) * 90;
    ctx.lineCap = "round";
    [[93, c.soft], [59, c.accent], [25, c.light]].forEach(([width, color], index) => {
      ctx.strokeStyle = color; ctx.lineWidth = width;
      ctx.beginPath();
      ctx.moveTo(735, 683 + offset + index * 18);
      ctx.bezierCurveTo(980, 670, 750, 290, 1014, 260 + offset);
      ctx.bezierCurveTo(1170, 208, 1170, 440, 1270, 312);
      ctx.stroke();
    });
    ctx.fillStyle = c.light; ctx.beginPath(); ctx.arc(1040, 253 + offset, 62, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = c.ink; ctx.beginPath(); ctx.arc(1040, 253 + offset, 19, 0, Math.PI * 2); ctx.fill();
  }

  function drawBotanical(c, rand) {
    const sway = (rand() - 0.5) * 44;
    ctx.save(); ctx.translate(954, 725);
    ctx.strokeStyle = c.paper; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(0, 0); ctx.bezierCurveTo(-45, -150, 62 + sway, -330, sway, -520); ctx.stroke();
    for (let i = 0; i < 7; i++) {
      const y = -75 - i * 60, side = i % 2 ? 1 : -1;
      const x = Math.sin(i * 0.9) * 16;
      ctx.save(); ctx.translate(x, y); ctx.rotate(side * 0.42);
      ctx.fillStyle = [c.soft, c.light, c.accent][i % 3];
      ctx.beginPath(); ctx.moveTo(0, 0);
      ctx.bezierCurveTo(side * 120, 10, side * 156, -75, side * 158, -98);
      ctx.bezierCurveTo(side * 48, -102, side * 12, -45, 0, 0); ctx.fill();
      ctx.strokeStyle = c.field; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.moveTo(side * 12, -8); ctx.quadraticCurveTo(side * 83, -44, side * 139, -83); ctx.stroke();
      ctx.restore();
    }
    ctx.fillStyle = c.accent; ctx.beginPath(); ctx.arc(sway, -537, 24, 0, Math.PI * 2); ctx.fill();
    ctx.restore();
  }

  function drawWeave(c, rand) {
    const shift = Math.floor(rand() * 3);
    ctx.save(); ctx.translate(778, 241);
    const shades = [c.soft, c.accent, c.light];
    for (let row = 0; row < 5; row++) {
      for (let col = 0; col < 4; col++) {
        const x = col * 88, y = row * 88;
        ctx.fillStyle = shades[(row + col + shift) % 3];
        ctx.fillRect(x + 3, y + 3, 78, 78);
        ctx.strokeStyle = c.field; ctx.lineWidth = 5;
        ctx.beginPath();
        for (let line = 16; line < 78; line += 16) {
          if ((row + col) % 2) { ctx.moveTo(x + line, y + 3); ctx.lineTo(x + line, y + 81); }
          else { ctx.moveTo(x + 3, y + line); ctx.lineTo(x + 81, y + line); }
        }
        ctx.stroke();
      }
    }
    ctx.strokeStyle = c.paper; ctx.lineWidth = 1;
    ctx.strokeRect(-17, -17, 382, 470); ctx.restore();
  }

  function drawHorizon(c, rand) {
    const horizon = 451 + rand() * 40;
    ctx.save(); ctx.beginPath(); ctx.rect(714, 110, 486, 627); ctx.clip();
    ctx.fillStyle = c.light; ctx.beginPath(); ctx.arc(956, 321, 119, 0, Math.PI * 2); ctx.fill();
    [c.soft, c.accent, c.paper].forEach((shade, i) => {
      const y = horizon + i * 88;
      ctx.fillStyle = shade; ctx.beginPath(); ctx.moveTo(698, y + 70);
      ctx.bezierCurveTo(852, y - 133, 974, y + 142, 1217, y - 17);
      ctx.lineTo(1217, 750); ctx.lineTo(698, 750); ctx.closePath(); ctx.fill();
      ctx.strokeStyle = c.field; ctx.lineWidth = 1.5;
      for (let n = 1; n <= 3; n++) {
        ctx.beginPath(); ctx.moveTo(698, y + 70 + n * 15);
        ctx.bezierCurveTo(852, y - 133 + n * 15, 974, y + 142 + n * 15, 1217, y - 17 + n * 15); ctx.stroke();
      }
    });
    ctx.restore();
  }

  function drawWindows(c, rand) {
    const shift = rand() * 22;
    ctx.save(); ctx.translate(790, 198 + shift);
    [[0, 100, c.soft], [106, 0, c.light], [212, 164, c.accent]].forEach(([x, y, shade]) => {
      ctx.fillStyle = shade;
      ctx.beginPath(); ctx.moveTo(x, y + 340); ctx.lineTo(x, y + 55);
      ctx.arc(x + 55, y + 55, 55, Math.PI, 0); ctx.lineTo(x + 110, y + 340); ctx.closePath(); ctx.fill();
      ctx.strokeStyle = c.field; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.moveTo(x + 55, y + 16); ctx.lineTo(x + 55, y + 323);
      for (const offset of [106, 188, 270]) { ctx.moveTo(x + 15, y + offset); ctx.lineTo(x + 95, y + offset); }
      ctx.stroke();
    });
    ctx.strokeStyle = c.paper; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(-22, 527); ctx.lineTo(348, 527); ctx.stroke(); ctx.restore();
  }

  // New plates stay inside the illustration field. Existing recipes retain
  // their original drawing functions and random sequence.
  function illustration(drawPlate) {
    return (c, rand) => {
      ctx.save();
      ctx.beginPath(); ctx.rect(714, 110, 486, 627); ctx.clip();
      drawPlate({ ...c, line: c === colors.sage ? c.ink : c.soft }, rand);
      ctx.restore();
    };
  }

  function polygon(points, color) {
    ctx.fillStyle = color; ctx.beginPath();
    points.forEach(([x, y], index) => index ? ctx.lineTo(x, y) : ctx.moveTo(x, y));
    ctx.closePath(); ctx.fill();
  }

  function drawConstellation(c, rand) {
    const points = [[789, 307], [913, 213], [1102, 292], [1004, 419], [1111, 592], [906, 642], [809, 515]]
      .map(([x, y]) => [x + (rand() - 0.5) * 38, y + (rand() - 0.5) * 38]);
    ctx.strokeStyle = c.line; ctx.lineWidth = 2;
    [[0, 1], [1, 2], [2, 3], [3, 0], [3, 4], [4, 5], [5, 6], [6, 3]].forEach(([a, b]) => {
      ctx.beginPath(); ctx.moveTo(...points[a]); ctx.lineTo(...points[b]); ctx.stroke();
    });
    points.forEach(([x, y], index) => {
      const radius = [18, 31, 13, 49, 24, 13, 28][index];
      ctx.fillStyle = index === 3 ? c.accent : index % 2 ? c.light : c.paper;
      ctx.beginPath(); ctx.arc(x, y, radius, 0, Math.PI * 2); ctx.fill();
      ctx.strokeStyle = c.line; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.arc(x, y, radius + 9, 0, Math.PI * 2); ctx.stroke();
    });
    ctx.strokeStyle = c.light; ctx.lineWidth = 1.5;
    [[796, 194], [1143, 463], [828, 666]].forEach(([x, y]) => {
      ctx.beginPath(); ctx.moveTo(x - 8, y); ctx.lineTo(x + 8, y);
      ctx.moveTo(x, y - 8); ctx.lineTo(x, y + 8); ctx.stroke();
    });
  }

  function drawFolded(c, rand) {
    const offset = (rand() - 0.5) * 32;
    ctx.translate(0, offset);
    polygon([[775, 255], [1068, 178], [1158, 568], [868, 654]], c.ink);
    polygon([[754, 239], [1049, 161], [1138, 550], [847, 635]], c.paper);
    polygon([[754, 239], [974, 365], [1049, 161]], c.light);
    polygon([[974, 365], [1138, 550], [1049, 161]], c.accent);
    polygon([[847, 635], [974, 365], [1138, 550]], c.soft);
    ctx.strokeStyle = c.field; ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.moveTo(754, 239); ctx.lineTo(974, 365); ctx.lineTo(1138, 550);
    ctx.moveTo(847, 635); ctx.lineTo(974, 365); ctx.lineTo(1049, 161); ctx.stroke();
    ctx.strokeStyle = c.ink; ctx.lineWidth = 2;
    for (let i = 0; i < 5; i++) {
      ctx.beginPath(); ctx.moveTo(817 + i * 6, 388 + i * 28);
      ctx.lineTo(902 + i * 6, 414 + i * 14); ctx.stroke();
    }
    ctx.fillStyle = c.accent; ctx.beginPath(); ctx.arc(1052, 654, 24, 0, Math.PI * 2); ctx.fill();
  }

  function drawTerraces(c, rand) {
    const shift = (rand() - 0.5) * 42;
    [c.soft, c.light, c.paper, c.accent, c.field, c.light].forEach((shade, index) => {
      ctx.save(); ctx.translate(967 + shift, 440); ctx.rotate(-0.19);
      const scale = 1 - index * 0.135;
      ctx.scale(scale, scale);
      ctx.beginPath(); ctx.moveTo(-209, -144);
      ctx.bezierCurveTo(-223, -291, 99, -308, 146, -178);
      ctx.bezierCurveTo(190, -72, 258, -24, 193, 92);
      ctx.bezierCurveTo(139, 160, 162, 277, 29, 270);
      ctx.bezierCurveTo(-128, 269, -270, 181, -205, 72);
      ctx.bezierCurveTo(-154, -11, -193, -69, -209, -144);
      ctx.closePath(); ctx.fillStyle = shade; ctx.fill();
      ctx.strokeStyle = c.field; ctx.lineWidth = 1.5 / scale; ctx.stroke(); ctx.restore();
    });
    ctx.strokeStyle = c.ink; ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.moveTo(953 + shift, 428); ctx.lineTo(981 + shift, 428);
    ctx.moveTo(967 + shift, 414); ctx.lineTo(967 + shift, 442); ctx.stroke();
  }

  function drawMosaic(c, rand) {
    const shades = [c.paper, c.light, c.accent, c.soft];
    ctx.translate(764, 213);
    for (let row = 0; row < 4; row++) {
      for (let col = 0; col < 3; col++) {
        ctx.save(); ctx.translate(col * 128 + 60, row * 128 + 60);
        ctx.rotate(Math.floor(rand() * 4) * Math.PI / 2);
        const index = Math.floor(rand() * shades.length);
        ctx.fillStyle = shades[index]; ctx.fillRect(-59, -59, 118, 118);
        ctx.fillStyle = shades[(index + 1) % shades.length];
        ctx.beginPath(); ctx.moveTo(-59, -59); ctx.arc(-59, -59, 118, 0, Math.PI / 2); ctx.closePath(); ctx.fill();
        ctx.fillStyle = c.field;
        ctx.beginPath(); ctx.arc(-59, -59, 53, 0, Math.PI / 2); ctx.lineTo(-59, -59); ctx.closePath(); ctx.fill();
        ctx.restore();
      }
    }
  }

  function drawSignal(c, rand) {
    const phase = rand() * Math.PI * 2;
    ctx.lineWidth = 3;
    for (let row = 0; row < 19; row++) {
      ctx.strokeStyle = row > 7 && row < 12 ? c.accent : row % 3 === 0 ? c.light : c.line;
      ctx.beginPath();
      for (let x = 752; x <= 1166; x += 3) {
        const envelope = Math.pow(Math.sin((x - 752) / 414 * Math.PI), 2);
        const y = 224 + row * 23 + Math.sin((x - 752) / 53 + phase + row * 0.18) * 67 * envelope;
        if (x === 752) ctx.moveTo(x, y); else ctx.lineTo(x, y);
      }
      ctx.stroke();
    }
    ctx.strokeStyle = c.paper; ctx.lineWidth = 1;
    for (let x = 770; x < 1160; x += 24) {
      ctx.beginPath(); ctx.moveTo(x, 702); ctx.lineTo(x, x % 48 === 2 ? 690 : 696); ctx.stroke();
    }
  }

  function drawSundial(c, rand) {
    const angle = -0.75 + rand() * 0.65;
    ctx.translate(958, 435);
    ctx.fillStyle = c.paper; ctx.beginPath(); ctx.arc(0, 0, 193, 0, Math.PI * 2); ctx.fill();
    ctx.strokeStyle = c.field; ctx.lineWidth = 2;
    for (let i = 0; i < 48; i++) {
      const a = i * Math.PI / 24;
      const inner = i % 4 ? 171 : 153;
      ctx.beginPath(); ctx.moveTo(Math.cos(a) * inner, Math.sin(a) * inner);
      ctx.lineTo(Math.cos(a) * 181, Math.sin(a) * 181); ctx.stroke();
    }
    ctx.rotate(angle);
    polygon([[0, -123], [119, 105], [0, 44]], c.soft);
    polygon([[0, -123], [-58, 81], [0, 44]], c.accent);
    polygon([[0, -123], [58, 81], [0, 44]], c.light);
    ctx.fillStyle = c.field; ctx.beginPath(); ctx.arc(0, 44, 7, 0, Math.PI * 2); ctx.fill();
  }

  const motifs = {
    orbit: drawOrbit, pages: drawPages, path: drawPath, botanical: drawBotanical,
    weave: drawWeave, horizon: drawHorizon, windows: drawWindows,
    constellation: illustration(drawConstellation), folded: illustration(drawFolded),
    terraces: illustration(drawTerraces), mosaic: illustration(drawMosaic),
    signal: illustration(drawSignal), sundial: illustration(drawSundial),
  };

  function fitLines(text, width) {
    const words = text.split(/\s+/).filter(Boolean);
    for (let size = 74; size >= 32; size -= 2) {
      ctx.font = `500 ${size}px Georgia, serif`;
      const lines = [];
      let line = "";
      for (let word of words) {
        while (ctx.measureText(word).width > width) {
          if (line) { lines.push(line); line = ""; }
          let cut = 1;
          while (cut < word.length && ctx.measureText(word.slice(0, cut + 1)).width <= width) cut++;
          lines.push(word.slice(0, cut));
          word = word.slice(cut);
        }
        if (!word) continue;
        const trial = line ? `${line} ${word}` : word;
        if (ctx.measureText(trial).width > width && line) { lines.push(line); line = word; }
        else line = trial;
      }
      if (line) lines.push(line);
      if (lines.length <= Math.floor(390 / (size * 1.11))) return { lines, size };
    }
    return { lines: [text.slice(0, 60) + "…"], size: 32 };
  }

  function draw() {
    const c = colors[palette.value] || colors.forest;
    const labelTags = tags.value.split(/[,;]+/).map((part) => part.trim()).filter(Boolean).slice(0, 3);
    const hash = labelTags.join("|").split("").reduce((value, letter) => ((value * 31 + letter.charCodeAt(0)) >>> 0), 0);
    const rand = randomFactory(seed ^ hash);
    // Each recipe must render identically regardless of the previous motif.
    ctx.lineCap = "butt";
    ctx.clearRect(0, 0, 1200, 900);
    ctx.fillStyle = c.paper; ctx.fillRect(0, 0, 1200, 900);
    ctx.fillStyle = c.field; ctx.fillRect(714, 0, 486, 900);
    drawLines(714, 0, 486, 900, c.paper, palette.value === "sage" ? 0.21 : 0.13);
    (motifs[motif.value] || drawOrbit)(c, rand);

    ctx.fillStyle = c.accent; ctx.fillRect(70, 75, 35, 5);
    ctx.fillStyle = c.ink; ctx.font = "700 20px Arial, sans-serif";
    ctx.letterSpacing = "2px";
    ctx.fillText("THE NOTEBOOK", 70, 129);
    ctx.letterSpacing = "0px";
    ctx.strokeStyle = c.ink; ctx.globalAlpha = 0.7; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(70, 167); ctx.lineTo(646, 167); ctx.stroke(); ctx.globalAlpha = 1;

    const content = title.value.trim() || "A story worth opening";
    const fitted = fitLines(content, 575);
    ctx.fillStyle = c.ink; ctx.font = `500 ${fitted.size}px Georgia, serif`;
    const lineHeight = fitted.size * 1.11;
    const startY = 286 + Math.max(0, (5 - fitted.lines.length) * 19);
    fitted.lines.forEach((line, index) => ctx.fillText(line, 70, startY + index * lineHeight));

    ctx.fillStyle = c.accent; ctx.fillRect(70, 715, 55, 5);
    ctx.fillStyle = c.ink; ctx.font = "700 20px Arial, sans-serif";
    ctx.fillText(labelTags.length ? labelTags.join("  /  ").toUpperCase().slice(0, 48) : "IDEAS · STORIES · NOTES", 70, 765, 575);
    ctx.strokeStyle = c.ink; ctx.globalAlpha = 0.7;
    ctx.beginPath(); ctx.moveTo(70, 815); ctx.lineTo(646, 815); ctx.stroke(); ctx.globalAlpha = 1;
    ctx.fillStyle = c.ink; ctx.font = "17px Arial, sans-serif";
    ctx.fillText(String(window.kntCoverStudio.siteName || "Kamal Notebook").slice(0, 40), 70, 850);
    ctx.fillStyle = c.paper; ctx.font = "italic 64px Georgia, serif";
    ctx.fillText("k.", 1100, 838);
  }

  form.addEventListener("input", draw);
  form.addEventListener("change", draw);
  function loadPost() {
    const selected = post.selectedOptions[0];
    let recipe = null;
    try { recipe = JSON.parse(selected?.dataset.recipe || "null"); } catch { /* Existing image is not a Studio cover. */ }
    title.value = recipe?.title || selected?.dataset.title || "";
    tags.value = recipe?.tags || "";
    palette.value = colors[recipe?.palette] ? recipe.palette : "forest";
    motif.value = Object.hasOwn(motifs, recipe?.motif) ? recipe.motif : "orbit";
    seed = Number.isSafeInteger(recipe?.seed) && recipe.seed >= 0 ? recipe.seed : 13741;
    draw();
  }
  post.addEventListener("change", loadPost);
  form.querySelector("#knt-cover-variation").addEventListener("click", () => {
    const values = new Uint32Array(1);
    if (crypto?.getRandomValues) crypto.getRandomValues(values);
    seed = values[0] || Math.floor(Math.random() * 4294967295);
    draw();
  });
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    save.disabled = true;
    status.textContent = "Saving the cover…";
    try {
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/png"));
      if (!blob) throw new Error("The browser could not create the PNG.");
      const payload = new FormData();
      payload.append("action", "knt_save_cover");
      payload.append("nonce", window.kntCoverStudio.nonce);
      payload.append("post_id", post.value);
      payload.append("title", title.value);
      payload.append("tags", tags.value);
      payload.append("palette", palette.value);
      payload.append("motif", motif.value);
      payload.append("seed", String(seed));
      payload.append("cover", blob, "notebook-cover.png");
      const response = await fetch(window.kntCoverStudio.endpoint, { method: "POST", credentials: "same-origin", body: payload });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.data?.message || "The cover could not be saved.");
      status.replaceChildren(document.createTextNode("Saved as the post’s Featured image. "));
      const link = document.createElement("a");
      link.href = result.data.editUrl;
      link.textContent = "Return to the post editor ↗";
      status.append(link);
    } catch (error) { status.textContent = error.message || "The cover could not be saved."; }
    finally { save.disabled = false; }
  });
  loadPost();
})();
