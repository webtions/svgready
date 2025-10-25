# SVG Ready

Convert raw SVG markup into CSS-ready Data URIs — fast, safe, and reliable.

SVG Ready lets you turn SVG code into clean, percent-encoded or base64 Data URIs you can drop straight into CSS.
It runs entirely on your own server, with no ads, no tracking, and no external calls.

---

## Features

- Paste your raw `<svg>` markup and instantly get clean output
- Generates:
  - Percent-encoded Data URI
  - Background and Mask CSS snippets
  - Optional Base64 version
- Live SVG preview (sanitized for safety)
- Blocks unsafe markup like `<script>`, `<foreignObject>`, or external images
- AJAX-based conversion for a faster workflow
- Keyboard shortcut: **Cmd + Enter / Ctrl + Enter** to run conversion
- Dark and light themes with local storage memory
- Can run locally without any external dependencies

---

## Security

SVG Ready sanitizes and validates every input before rendering:
- Detects and rejects any script tags or embedded HTML
- Removes inline event handlers and dangerous attributes
- Allows only a limited set of safe SVG elements

---

## Tech Stack

- PHP 8+
- Vanilla JavaScript (no frameworks)
- HTML5 and CSS3
- Hosted on Cloudways
- Privacy-friendly analytics via Koko Analytics

---

## Folder Structure

```
/index.php
/ajax.php
/inc/SvgConverter.php
/inc/functions.php
/inc/form.php
/inc/output.php
/inc/ads.php
/assets/scripts.js
/assets/style.css
```

---

## Future Ideas

- Support for SVG file uploads
- Drag and drop input
- Shareable encoded result links
- Optional SVG optimization tools

---

## License

MIT License — free to use, credit appreciated.

---

Built by [Harish Chouhan](https://webtions.com)
