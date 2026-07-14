# Design System — E-Perpustakaan Digital KPU

## Direction

Original modern digital library and clean government portal. The refreshed direction is editorial,
accessible, and calm: open bands, book rails, restrained list panels, and generous whitespace instead
of wooden shelves or repetitive nested cards. The July 2026 concept is stored in the Codex generated
image workspace and implemented through semantic tokens in `resources/css/app.css`.

## Tokens

- Light: page/raised `#ffffff`, soft `#f4f7fa`, subtle `#eef3f8`.
- Dark: page `#08111f`, raised `#0f1b2d`, soft `#111f33`, subtle `#17263a`.
- Light text: deep navy `#0f2747`, body `#53657c`, muted `#748399`.
- Dark text: primary `#f7fafc`, body `#c4cfdd`, muted `#94a3b8`.
- Primary action: election red `#b91c1c`, hover `#991b1b`.
- Accent: restrained gold `#c59a3d`, used only for access highlights.
- Borders: light `#dce4ec`/`#c7d2df`; dark `#26364b`/`#3a4b61`.
- Focus ring: `#dc2626` with 3px offset; radius: 10px controls, 14px panels, 22px hero media.
- Shadows are restrained and reserved for search, hero media, and book depth.

## Typography and layout

- Instrument Sans; heading 700/1.08, body 400/1.65, controls 600/1.2.
- Content max width 1280px, gutters 20px mobile/32px desktop.
- Header uses a lightly translucent theme surface with a fine border and visible active-route marker.
- The public homepage varies its rhythm between hero, categories, book rail, split discovery lists,
  statistics, and announcement. Navy bands are reserved for footer and selected controls.
- Covers use portrait ratio 3:4.25. Lists and shelf mode reuse the same metadata hierarchy.

## Components

Header/mobile drawer, breadcrumb, search bar, filter drawer/rail, sort/view controls, book cover/card,
book list row, digital shelf rail, taxonomy link, pagination, announcement band, access panel, empty state,
footer. Icons are small inline SVG with consistent 1.75 stroke and `currentColor`.

## Accessibility

Visible focus, skip link, semantic landmarks, ARIA-expanded mobile drawer, reduced motion support,
dark/high-contrast preferences, scalable text, and scroll mode-compatible content hierarchy.
