# RaggieSoft Book Reader (`https://books.raggiesoft.com`)

Framework-free Progressive Web App (PWA) and PHP 8.5 digital reading platform powering **Ocean View Archives** and **RaggieSoft Story Crossroads** at `https://books.raggiesoft.com`.

## Architecture & Accessibility Highlights

- **Zero-Framework Web Standards:** Pure semantic HTML5, PHP 8.5 manifest routing, and modular ES2024 JavaScript with persistent `<audio>` soundtrack playback across client-side SPA chapter transitions.
- **Interactive & Linear Reading Modes:** Supports both serialized multi-era linear novels and **RaggieSoft Story Crossroads** interactive branching narratives, backed by static JSON route manifests (`catalog.json`, `toc.json`) and client-side full-text search (`search-index.json`).
- **WCAG 2.1 AA & Section 508 Reader Controls:** Reader-customizable font sizing, line height, measure width, and high-contrast Light, Dark, and Sepia themes persisted locally, paired with an offline-capable Service Worker and downloadable EPUB/PDF editions.
- **Protected Front-Controller Routing:** Isolates application templates outside the public web root behind a single Nginx `try_files` PHP 8.5 front controller.

## Licensing
- **Source Code:** Licensed under the **MIT License**.
- **Creative Narrative Content:** Licensed under **CC BY-SA 4.0**.
