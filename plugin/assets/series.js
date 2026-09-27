(() => {
  "use strict";

  const config = window.kntSeries;
  const article = document.querySelector("article[data-knt-lesson-id]");
  const body = article?.querySelector("[data-knt-lesson-content]");
  const nav = article?.querySelector("[data-knt-series-nav]");
  if (!config?.endpoint || !article || !body || !nav || !window.fetch || !window.history?.pushState) return;

  const initialId = Number(article.dataset.kntLessonId);
  const cache = new Map();
  let requestNumber = 0;
  history.replaceState({ ...history.state, kntLesson: initialId }, "", location.href);

  function safeHttpUrl(url) {
    try {
      const parsed = new URL(url, location.href);
      return /^https?:$/.test(parsed.protocol) ? parsed : null;
    } catch {
      return null;
    }
  }

  function sameOrigin(url) {
    const parsed = safeHttpUrl(url);
    return parsed?.origin === location.origin ? parsed : null;
  }

  function lesson(id) {
    if (!Number.isSafeInteger(id) || id < 1) return Promise.reject(new Error("Invalid lesson"));
    if (!cache.has(id)) {
      const request = fetch(`${config.endpoint}${id}`, { credentials: "same-origin", headers: { Accept: "application/json" } })
        .then((response) => {
          if (!response.ok) throw new Error("Lesson unavailable");
          return response.json();
        })
        .then((data) => {
          if (data.id !== id || !sameOrigin(data.url) || !data.series || typeof data.content !== "string") throw new Error("Invalid lesson response");
          return data;
        })
        .catch((error) => {
          cache.delete(id);
          throw error;
        });
      cache.set(id, request);
      if (cache.size > 5) cache.delete(cache.keys().next().value);
    }
    return cache.get(id);
  }

  function element(tag, className, value) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (value !== undefined) node.textContent = value;
    return node;
  }

  function tocLink(item) {
    const link = element("a", "toc-link", item.title);
    link.href = `#${encodeURIComponent(item.id)}`;
    return link;
  }

  function updateOutline(outline, tutorial) {
    const shell = article.querySelector(".article-shell");
    const content = article.querySelector(".article-content");
    if (!shell || !content) return;
    shell.querySelector(".toc-rail")?.remove();
    content.querySelector(".toc-mobile")?.remove();
    const hasOutline = Array.isArray(outline) && outline.length > 0;
    shell.classList.toggle("has-outline", hasOutline);
    shell.classList.toggle("no-outline", !hasOutline);
    if (!hasOutline) return;

    const rail = element("aside", "toc-rail");
    const sticky = element("div", "toc-sticky");
    const label = element("div", "toc-label", tutorial ? "THE LESSONS" : "ON THIS PAGE");
    label.append(element("span", "", String(outline.length).padStart(2, "0")));
    const links = element("nav");
    links.setAttribute("aria-label", "Article sections");
    outline.forEach((item) => links.append(tocLink(item)));
    const end = element("div", "toc-end");
    end.append(element("span", "toc-end-mark", "✳"), element("span", "", tutorial ? "Go at your own pace." : "Take your time with this one."));
    sticky.append(label, links, end);
    rail.append(sticky);
    shell.insertBefore(rail, content);

    const mobile = element("details", "toc-mobile");
    const summary = element("summary", "", tutorial ? "Lessons in this tutorial " : "In this piece ");
    const arrow = element("span", "", "↓");
    arrow.setAttribute("aria-hidden", "true");
    summary.append(arrow);
    const mobileLinks = element("nav");
    mobileLinks.setAttribute("aria-label", "Article sections");
    outline.forEach((item) => mobileLinks.append(tocLink(item)));
    mobile.append(summary, mobileLinks);
    content.insertBefore(mobile, body);
  }

  function updateNavigation(series) {
    const status = nav.querySelector("[data-knt-series-status]");
    nav.replaceChildren();
    const head = element("div", "knt-series-head");
    head.append(
      element("span", "", "A COURSE IN THE NOTEBOOK"),
      element("strong", "", series.name),
      element("small", "", `Lesson ${series.position} of ${series.total}`),
    );
    const links = element("div", "knt-series-links");
    [["previous", "Previous lesson", "←"], ["next", "Next lesson", "→"]].forEach(([direction, label, arrow]) => {
      const target = series[direction];
      const url = target && sameOrigin(target.url);
      if (!url) return;
      const link = element("a");
      link.href = url.href;
      link.dataset.kntLessonLink = "";
      link.dataset.kntLessonId = String(target.id);
      const symbol = element("span", "", arrow);
      symbol.setAttribute("aria-hidden", "true");
      link.append(element("span", "", label), element("strong", "", target.title), symbol);
      links.append(link);
    });
    nav.append(head, links, status || element("span", "screen-reader-text"));
  }

  function updateHero(data) {
    const title = article.querySelector(".article-hero h1");
    if (title) title.textContent = data.title;
    const heroCopy = article.querySelector(".article-hero-copy");
    let excerpt = heroCopy?.querySelector(".article-standfirst");
    if (data.excerpt) {
      if (!excerpt) {
        excerpt = element("p", "article-standfirst");
        title?.after(excerpt);
      }
      excerpt.textContent = data.excerpt;
    } else excerpt?.remove();
    const kicker = heroCopy?.querySelector(".article-kicker");
    if (kicker?.firstElementChild) kicker.firstElementChild.textContent = `${data.hero.topic} / ${data.hero.kind}`;
    if (kicker?.lastElementChild) kicker.lastElementChild.textContent = `${data.hero.readingMinutes} MIN READ`;
    const author = heroCopy?.querySelector(".article-byline strong");
    const byline = heroCopy?.querySelector(".article-byline small");
    if (author) author.textContent = data.hero.author;
    if (byline) byline.textContent = `${data.hero.date} · ${data.hero.topic}`;
    const image = article.querySelector(".article-hero-art img");
    const imageUrl = safeHttpUrl(data.hero.image);
    if (image && imageUrl) {
      image.removeAttribute("srcset");
      image.removeAttribute("sizes");
      image.src = imageUrl.href;
      image.alt = "";
    }
  }

  function commit(data, push) {
    const target = sameOrigin(data.url);
    if (!target || !data.canSwap) return false;
    const currentSeries = Number(article.dataset.kntSeriesId);
    if (currentSeries && Number(data.series.id) !== currentSeries) return false;
    // The REST endpoint returns published content passed through WordPress's post HTML allowlist.
    // Script elements inserted by innerHTML do not execute; special interactive posts use full links.
    body.innerHTML = data.content;
    updateHero(data);
    updateOutline(data.outline, data.hero.kind === "TUTORIAL");
    updateNavigation(data.series);
    article.id = `post-${data.id}`;
    article.dataset.kntLessonId = String(data.id);
    article.dataset.kntSeriesId = String(data.series.id);
    article.classList.toggle("kn-article-tutorial", data.hero.kind === "TUTORIAL");
    const end = article.querySelector(".article-end span:last-child");
    if (end) end.textContent = data.hero.kind === "LESSON" ? "END OF LESSON" : data.hero.kind === "TUTORIAL" ? "END OF TUTORIAL" : "END OF STORY";
    const actions = article.querySelector(".reader-actions");
    if (actions) Object.assign(actions.dataset, { postId: String(data.id), title: data.title, url: target.href });
    const feedback = article.querySelector("[data-feedback]");
    if (feedback) feedback.dataset.postId = String(data.id);
    article.querySelector("[data-reader-status]")?.replaceChildren();
    article.querySelector("[data-feedback-status]")?.replaceChildren();
    document.title = `${data.title} – ${config.siteTitle}`;
    const description = document.querySelector('meta[name="description"]');
    if (description) description.content = data.excerpt || "";
    if (push) history.pushState({ ...history.state, kntLesson: data.id }, "", target.href);
    document.dispatchEvent(new CustomEvent("knt:lessonchange", { detail: { id: data.id, data } }));
    window.scrollTo({ top: 0, behavior: "auto" });
    const title = article.querySelector(".article-hero h1");
    if (title) {
      title.tabIndex = -1;
      title.focus({ preventScroll: true });
    }
    const status = nav.querySelector("[data-knt-series-status]");
    if (status) status.textContent = `${data.title}, lesson ${data.series.position} of ${data.series.total}`;
    return true;
  }

  async function navigate(id, fallbackUrl, push) {
    const number = ++requestNumber;
    nav.setAttribute("aria-busy", "true");
    try {
      const data = await lesson(id);
      if (number !== requestNumber) return;
      if (!commit(data, push)) throw new Error("This lesson needs a page load");
    } catch {
      if (number === requestNumber) location.assign(fallbackUrl);
    } finally {
      if (number === requestNumber) nav.removeAttribute("aria-busy");
    }
  }

  nav.addEventListener("click", (event) => {
    const link = event.target.closest("a[data-knt-lesson-link]");
    if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute("download")) return;
    const url = sameOrigin(link.href);
    const id = Number(link.dataset.kntLessonId);
    if (!url || !Number.isSafeInteger(id)) return;
    event.preventDefault();
    navigate(id, url.href, true);
  });
  for (const type of ["pointerover", "focusin"]) {
    nav.addEventListener(type, (event) => {
      const link = event.target.closest("a[data-knt-lesson-link]");
      if (link) lesson(Number(link.dataset.kntLessonId)).catch(() => {});
    });
  }
  window.addEventListener("popstate", (event) => {
    const id = Number(event.state?.kntLesson);
    if (!Number.isSafeInteger(id) || id < 1) {
      location.reload();
      return;
    }
    if (id !== Number(article.dataset.kntLessonId)) navigate(id, location.href, false);
  });
})();
