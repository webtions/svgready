# SVG Ready

Convert SVG markup to CSS Data URIs — fast, safe, and reliable. Generates percent-encoded or base64 Data URIs ready for CSS.

---

## Features

- Paste SVG markup and get clean output
- Generates percent-encoded and base64 Data URIs
- Background and mask CSS snippets
- Live SVG preview (sanitized)
- AJAX-based conversion
- Keyboard shortcut: **Cmd/Ctrl + Enter**
- Dark/light theme support

---

## Security

SVG input is sanitized and validated using a multi-layer security approach:

**Input Validation:**
- 250 KB size limit
- Must start with `<svg>` tag
- Empty content rejection

**XML Parsing & XXE Protection:**
- Uses PHP's `DOMDocument` for proper XML parsing
- `LIBXML_NONET` flag prevents network access
- Entity loader disabled for older libxml versions
- PHP tags removed recursively before parsing

**Whitelist-Based Filtering:**
- **Tags:** Only 25 allowed SVG tags (svg, g, path, rect, circle, etc.)
- **Attributes:** Whitelist of 40+ safe SVG attributes
- Dangerous tags removed: `<script>`, `<foreignObject>`, `<iframe>`, `<object>`, `<embed>`, `<link>`, `<style>`
- Event handlers removed: All `on*` attributes (onclick, onload, etc.)
- Non-whitelisted tags/attributes automatically removed

**URL & Protocol Validation:**
- Href attributes validated for safe URLs only
- Blocks `javascript:`, `data:text/html`, `vbscript:` protocols
- Allows only safe data URIs: `data:image/png`, `data:image/gif`, `data:image/jpg`, `data:image/jpeg`, `data:image/svg+xml`
- Allows fragment identifiers (`#id`), relative paths, and HTTP/HTTPS URLs

**Structure Protection:**
- CDATA sections converted to text nodes
- Nesting depth limit: 100 levels
- `<use>` element nesting limit: 15 levels (prevents DoS)
- Well-formed XML validation required

**Output Escaping:**
- All user content escaped via `svgreadyEscapeHtml()` in templates
- JavaScript escaping via `svgreadyEscapeJs()`
- Data attribute escaping via `svgreadyEscapeDataAttr()`

---

## Tech Stack

- PHP 8+ (strict types)
- Vanilla JavaScript
- No runtime dependencies

---

## Folder Structure

```
/
├── index.php              # Main entry point
├── ajax.php               # AJAX handler
├── inc/
│   ├── classes/          # Core classes
│   │   ├── SVGReady.php  # Bootstrap & request handling
│   │   ├── SVGConverter.php  # SVG processing
│   │   └── View.php      # Template renderer
│   ├── functions/        # Helper functions
│   │   ├── functions.php # Escaping utilities
│   │   └── errors.php    # Error message definitions
│   └── templates/        # View templates
│       ├── header.php    # HTML head & header
│       ├── footer.php    # Footer & scripts
│       ├── form.php      # Input form
│       ├── output.php    # Results display
│       ├── ads.php       # Ad banner
│       ├── maintenance.php  # Maintenance page
│       └── 404.html      # 404 page
├── assets/               # Static assets
│   ├── scripts.js        # JavaScript
│   └── style.css         # Styles
└── _docs/                # Documentation
    └── Architecture.md   # Architecture documentation
```

For detailed architecture information, see [`_docs/Architecture.md`](_docs/Architecture.md).

---

## Future Ideas

- SVG file uploads
- Drag and drop input
- Shareable result links
- SVG optimization tools

---

## License

MIT License — free to use, credit appreciated.

---

Built by [Harish Chouhan](https://webtions.com)
