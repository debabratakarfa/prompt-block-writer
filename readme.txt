=== Prompt Block Writer ===
Contributors: dkarfa
Tags: ai, block editor, content generation, gutenberg, writing
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate post content from a prompt using the WordPress Abilities API and insert it as native Gutenberg blocks.

== Description ==

Prompt Block Writer adds an **AI Content Generator** panel to the Gutenberg block editor sidebar. Write a prompt, optionally use your existing post as context, review the generated content, and insert it directly into the editor as properly structured native blocks — headings, lists, paragraphs, and more.

**How it works:**

1. Open any post or page in the block editor.
2. Find the **AI Content Generator** panel in the Document sidebar.
3. Click **Generate from context** to open the modal.
4. Enter a prompt describing what you want written.
5. Click **Generate** — the plugin sends your prompt (plus current post content as context) to the configured AI connector.
6. Review and optionally edit the result in the modal.
7. Click **Insert into editor** to add the content as native blocks at the current cursor position.

**Key features:**

* Uses the WordPress Abilities API — no third-party SDK required.
* Markdown output is automatically converted to proper Gutenberg blocks (headings, lists, bold, italic, and more).
* Supports any REST-enabled post type.
* Shows a notice if no AI connector is configured, with a direct link to manage connectors.
* Regenerate as many times as needed before inserting.

**Requirements:**

This plugin requires:

* WordPress 7.0+ (with core AI support enabled)
* The official [WordPress AI plugin](https://wordpress.org/plugins/ai/) installed and active
* An AI connector configured under **Settings → Connectors**

== Installation ==

1. Upload the `prompt-block-writer` folder to `/wp-content/plugins/`.
2. Activate **Prompt Block Writer** from the **Plugins** screen.
3. Ensure the **AI** plugin is installed and active.
4. Configure an AI connector under **Settings → Connectors**.

== Frequently Asked Questions ==

= Does this plugin work without the WordPress AI plugin? =

No. Prompt Block Writer uses the WordPress Abilities API, which is provided by the WordPress AI feature plugin. Both must be active.

= Which AI providers are supported? =

Any provider supported by the WordPress AI plugin and its connector system — the list grows as the AI plugin adds new connectors.

= Will the generated content overwrite my existing post? =

No. Content is inserted at the current cursor position in the editor, leaving all existing blocks untouched.

= Can I edit the generated content before inserting? =

Yes. The modal shows an editable textarea with the generated output. Edit freely before clicking **Insert into editor**.

== Screenshots ==

1. The AI Content Generator panel in the Document sidebar.
2. The generation modal with prompt input and generated output.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
