# Prompt Block Writer

A WordPress block editor plugin that generates post content from a prompt using the WordPress Abilities API, then inserts it as native Gutenberg blocks.

## Features

- **Block editor panel** — Adds an **AI Content Generator** section to the Document sidebar.
- **Prompt modal** — Click **Generate from context** to open a modal, describe what you want written, and generate on demand.
- **Post context** — Current post content is sent to the model as background context when available.
- **Markdown → blocks** — AI output is parsed from Markdown to HTML and converted into proper Gutenberg blocks (headings, lists, bold, italic, paragraphs).
- **Review before insert** — Edit generated text in the modal, regenerate if needed, then insert.
- **WordPress Abilities API** — Registers `prompt-block-writer/generate` and runs via the Abilities API (client-side with REST fallback).

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 7.0+ (with AI / Abilities API support) |
| PHP | 7.4+ |
| [WordPress AI plugin](https://wordpress.org/plugins/ai/) | Active |
| AI connector | Configured under **Settings → Connectors** |

## Installation

1. Copy the `prompt-block-writer` folder into `wp-content/plugins/`.
2. Activate **Prompt Block Writer** from the **Plugins** screen.
3. Ensure the **AI** plugin is installed and active.
4. Configure an AI connector (**Settings → Connectors**) with a provider that supports text generation.

## Usage

1. Open a post (or any REST-enabled post type) in the block editor.
2. In the **Document** sidebar, find the **AI Content Generator** panel.
3. Click **Generate from context**.
4. Enter a prompt (e.g. "Write an intro paragraph summarizing the keypoints").
5. Click **Generate**.
6. Review and edit the output in the modal.
7. Click **Insert into editor** to add the content as native blocks.

If no connector is configured, the plugin shows a notice with a link to **Manage Connectors**.

## Development

### Setup

```bash
cd wp-content/plugins/prompt-block-writer
npm install
```

### Scripts

| Command | Description |
|---|---|
| `npm run start` | Watch mode for block editor assets |
| `npm run build` | Production build to `build/` |

After changing `src/index.js`, run `npm run build` and commit the updated `build/` files.

### Project Structure

```
prompt-block-writer/
├── prompt-block-writer.php   # Bootstrap, ability registration, asset enqueue
├── includes/
│   └── Ability.php           # prompt-block-writer/generate ability
├── src/
│   └── index.js              # Block editor UI (modal, panel)
├── build/                    # Compiled assets (committed)
├── composer.json
├── package.json
├── readme.txt                # WordPress.org readme
└── README.md
```

### Composer

```bash
composer install
```

**Package:** `debabratakarfa/prompt-block-writer`
**Namespace:** `DebabrataKarfa\PromptBlockWriter`

### Ability

The registered ability (`prompt-block-writer/generate`) accepts:

| Input | Type | Required | Description |
|---|---|---|---|
| `prompt` | string | Yes | User instruction for what to generate |
| `context` | string | No | Post content used as context |

REST endpoint (fallback):

```
POST /wp-json/wp-abilities/v1/abilities/prompt-block-writer/generate/run
```

## Author

**Debabrata Karfa**

- WordPress.org profile: https://profiles.wordpress.org/dkarfa
- Plugin URI: https://github.com/debabratakarfa/ai.deb.im

## License

GPL-2.0-or-later — same as WordPress.
