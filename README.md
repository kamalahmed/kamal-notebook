# Kamal Notebook

A personal WordPress journal for code, ideas, and the things worth paying attention to.

[Visit the notebook](https://learnwithkamal.com) · [Kamal Ahmed](https://kamalahmed.me)

![Kamal Notebook homepage](docs/images/home.jpg)

## Made for reading

Warm paper tones, expressive typography, and a quiet editorial layout give short notes and detailed tutorials the same thoughtful home. Articles have a live section outline, highlighted code with a copy button, and illustrated covers that stay readable across screen sizes.

The theme handles presentation. **Notebook Tools**, its companion plugin, adds the writing and reader features:

- **Linked lesson series** with individual URLs, ordered navigation, and browser history support.
- **Cover Studio** with seven illustration styles, three palettes, and unlimited variations generated in the browser.
- **An intentional featured story**, chosen by the author.
- **Native block editing** with article patterns, a live outline, and an editable code block.
- **Reader tools** for sharing, saving on the current device, and leaving feedback.
- **A contact form** with validation, duplicate suppression, honeypot protection, and rate limits.

## A closer look

| Article | Lesson series |
| --- | --- |
| ![An article with its complete illustrated cover](docs/images/article.jpg) | ![A lesson with ordered course navigation](docs/images/series.jpg) |

| About | Contact |
| --- | --- |
| ![The About page with an index and selected projects](docs/images/about.jpg) | ![The contact page](docs/images/contact.jpg) |

![Cover Studio](docs/images/cover-studio.jpg)

Screenshots include clearly labeled demonstration articles and lessons.

## Use it

Install and activate **Kamal Notebook** and **Kamal Notebook Tools** from the [latest release](https://github.com/kamalahmed/kamal-notebook/releases/latest). These are separate theme and plugin ZIPs, maintained together in this project. Install both to use the demo importer and writing tools. Requires WordPress 6.6+ and PHP 8.0+.

Choose **Appearance → Notebook settings** to set the introduction, reading tools, and contact recipient. Under **Posts**, use **Cover Studio** to compose a featured image and **Series** to group lessons. Each lesson’s editor includes its series and lesson number.

For an example to explore, choose **Appearance → Import Notebook demo**. The importer adds labeled articles, a three-part course, and Home, Writing, About, and Contact pages. It can assign the static homepage and posts page while preserving existing content, contact recipients, and customized settings. Repeat imports reuse existing items. Demo content is a starter; transferring a customized site requires a separate content migration.

With no custom menu assigned, navigation links to Writing, About, and Contact; the logo links home. An existing menu stays under your control. The importer preserves your site name and existing articles; the live site's personal biography and custom cover images are not part of the starter.

Contact delivery uses the site’s WordPress mail configuration. Saved articles stay in the reader’s browser; they do not sync between devices.

## Built with

WordPress, PHP, native blocks, CSS, JavaScript, Canvas, and Prism syntax highlighting. No frontend framework or external image-generation service is required.

Local fonts, deferred scripts, responsive images, and page-specific styles keep the reading experience light. Page caching and Brotli/Gzip compression belong to your hosting stack or cache plugin. Performance also depends on content, hosting, and third-party integrations.

The live site deploys through [Deployward](https://github.com/kamalahmed/deployward) using signed GitHub webhooks, without a GitHub Actions build.

Created by [Kamal Ahmed](https://kamalahmed.me).
