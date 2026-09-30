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
- **A contact form** with validation, duplicate suppression, honeypot protection, rate limits, and optional Cloudflare Turnstile or Google reCAPTCHA v2.
- **Smooth archive filtering** with in-place topic, search, and pagination updates, browser history, and reduced-motion support.

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

### Featured articles and contact protection

In **Appearance → Notebook settings → Homepage**, choose **Single featured article** or **Featured article slider**, then choose up to four published articles in display order and save. The first article leads the homepage and Writing page. The slider uses reader-controlled previous/next buttons. Checking **Featured** in an article’s editor moves it into the first position; unchecking removes it from the selection.

The **Contact & security** tab controls the recipient, Cloudflare Turnstile and flood limits. Turnstile can reuse the existing Contact Form 7 integration or use custom credentials. A custom widget must allow the site hostname; its secret is stored separately and is never shown again. Leaving the secret field blank preserves it. Verification happens on the server and checks the hostname and `notebook_contact` action; missing, invalid or unavailable verification blocks sending when enabled.

Rate protection is always active: defaults are 20 attempts per IP per 10 minutes, 5 send attempts per IP per hour, 3 per sender email per hour, and 20 across the site per hour. All limits are configurable. Duplicate messages and token replays are also blocked. Counters use atomic database operations, hashed identities and automatic expiry. Shared networks share the IP budget; failed mail attempts still consume the send budget. Protection limits abuse but does not guarantee that every unwanted message is blocked.

Search finds controls across every tab. One **Save notebook settings** button saves all tabs together.

Contact delivery uses the site’s WordPress mail configuration. Saved articles stay in the reader’s browser; they do not sync between devices.

## Built with

WordPress, PHP, native blocks, CSS, JavaScript, Canvas, and Prism syntax highlighting. No frontend framework or external image-generation service is required.

Local fonts, deferred scripts, responsive images, and page-specific styles keep the reading experience light. Page caching and Brotli/Gzip compression belong to your hosting stack or cache plugin. Performance also depends on content, hosting, and third-party integrations.

The live site deploys through [Deployward](https://github.com/kamalahmed/deployward) using signed GitHub webhooks, without a GitHub Actions build.

Created by [Kamal Ahmed](https://kamalahmed.me).

### Contact verification

See [cloudflare.txt](cloudflare.txt) for Turnstile and Google reCAPTCHA setup, official documentation, and testing instructions. Both are optional; the built-in honeypot and rate limits also work without a third-party CAPTCHA.
