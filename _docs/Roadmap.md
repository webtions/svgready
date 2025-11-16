# SVG Ready – Roadmap

This document outlines the next development milestones for SVG Ready, focusing on simplifying maintenance, improving performance, and preparing the project for future scalability.

---

## 🪵 1. Simplify Logger
**Goal:** Replace local log file creation with direct LogSnag integration.
**Rationale:** Easier monitoring, cleaner codebase, and sufficient free quota from LogSnag.

**Tasks**
- [ ] Remove local `debug.log` file creation and rotation logic
- [ ] Keep only `sendToLogSnag()` for all log levels
- [ ] Add fallback to `error_log()` if LogSnag fails
- [ ] Test behavior in both local and production environments

---

## 🎨 2. CSS/JS Minification & Optimization
**Goal:** Implement auto-formatting, grouping, and minification similar to the Docist setup.

**Tasks**
- [ ] Add Prettier + Stylelint + ESLint configs
- [ ] Enable rule grouping and logical property order
- [ ] Add build step to output `style.min.css` and `scripts.min.js`
- [ ] Integrate error reporting on syntax issues

---

## ⚙️ 3. Auto Deployment via GitHub Actions
**Goal:** Deploy updates automatically while keeping the site stable.

**Tasks**
- [ ] Create `.maintenance` file at deployment start
- [ ] Run deploy commands (pull/update/cache clear)
- [ ] Remove `.maintenance` when deployment succeeds
- [ ] Send LogSnag alert on failure
- [ ] Mirror setup used for Webtions site

---

## 📁 4. File Upload & Download
**Goal:** Add secure SVG upload and processed file download options.

**Tasks**
- [ ] Add upload input to conversion form
- [ ] Validate MIME type and size (SVG only)
- [ ] Generate downloadable file for converted output
- [ ] Handle temporary file cleanup safely

---

## 🌍 5. Internationalisation (i18n)
**Goal:** Move all visible strings into language files for easy translation.

**Tasks**
- [ ] Create `/lang/en.php` as default
- [ ] Move error and UI text into translation arrays
- [ ] Add basic language loader in `Core` class
- [ ] Add `/lang/vi.php` for future localisation

---

### Notes
This roadmap will evolve as new features are finalised.
Target approach: **Keep SVG Ready simple, modular, and dependency-light.**

---

*Last updated: {{DATE}}*
