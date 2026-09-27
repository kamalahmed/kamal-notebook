# Kamal Notebook

The repository contains the WordPress theme in `theme/` and its companion plugin in `plugin/`. The source checkout currently lives in `~/Downloads/code/kamal-notebook`; the local WordPress site's theme and plugin directories link to those two folders. The ZIP files in `dist/` are installable copies. Moving the source checkout requires updating the local links.

## Install and import the demo

Install and activate both **Kamal Notebook** and **Kamal Notebook Tools**. Then open **Appearance → Import Notebook demo** and press **Import demo**. The button creates five posts labeled “Demonstration,” gives them categories and illustrations, and applies the prototype home introduction and grid layout. A second import skips the existing demo posts and leaves later setting changes alone. Your existing posts, site title, logo, and media are retained, so an otherwise empty site gives the closest match to the prototype. The first import also switches the front page to latest posts; the previous theme and front page settings are saved in the `knt_demo_previous_options` option for recovery.

## Write a tutorial in one post

Open **Posts → Add New** and choose **Tutorial with lessons**. Each lesson uses a Heading 2, which appears in the live **Table of contents preview** in the editor's document sidebar and in the front-end lesson navigator. Insert **Tutorial lesson** to add more sections. Use **Visual walkthrough** for a replaceable image or diagram with a caption and explanation. Ordinary WordPress Image, Gallery, Columns, and Media & Text blocks also work inside a lesson. Add alt text to informative images.

The **Highlighted code example** pattern inserts an editable **Notebook Code** block. Choose JavaScript, CSS, HTML, JSON, PHP, Bash, Python, or plain text. The block shows language colors in the editor and on the site and adds a copy button for readers. WordPress's ordinary Code block is still available, but it does not use Notebook's language highlighting.

The existing drag-and-drop tutorial is legacy HTML with its own CSS and JavaScript. The theme displays it inside an isolated frame so its six interactive lessons cannot restyle the rest of the site. Its editing format remains HTML; writing future tutorials with the native lesson patterns makes every section, image, and code example editable as WordPress blocks.

Build installable ZIPs with `python3 local/package.py`.
