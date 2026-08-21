# Wereabouts branding

Brand color: #FF6200
Icon Origin: [The Noun Project - Sword Point](https://thenounproject.com/icon/sword-point-6919420/)
Brand Icon: resources/branding/noun-sword-point-6919420-FFFFFF.svg

* Icons are brand icon (white) on a per-environment background color.

## Icon source of truth

### Source assets

* Clean icon source (non-Apple): brand-icon, as above
* Apple composer source: `resources/branding/wereabouts.icon`
* Per-environment background colors: `resources/branding/icon-config.json`

### Apple exception

* `resources/icons/apple-touch-icon.png` is generated from the Apple Icon Composer bundle (`resources/branding/wereabouts.icon`) and intentionally includes Apple's liquid-glass styling.
* Do not use `resources/icons/apple-touch-icon.png` as a source for any other icon files.

### Non-Apple icon outputs

Generated from the clean SVG source, not from the Apple touch icon:

* resources/icons/favicon.svg
* resources/icons/favicon-96x96.png
* resources/icons/favicon.ico
* resources/icons/web-app-manifest-192x192.png
* resources/icons/web-app-manifest-512x512.png

### Regeneration

All icon outputs regenerate automatically on every `npm run build` / `npm run dev`, via a Vite plugin (`bin/icons/vite-plugin.js`) that runs at `buildStart`. There is no manual script to run and no separate Icon Composer app step — the plugin reads `resources/branding/icon-config.json` for the glyph path and per-environment background color (keyed by `APP_ENV`), then:

* `bin/icons/generate-web-icons.js` renders the favicon/web-app-manifest outputs directly from the clean SVG glyph.
* `bin/icons/generate-apple-touch-icon.js` renders `apple-touch-icon.png` from `resources/branding/wereabouts.icon/icon.json`, and syncs that file's `fill.automatic-gradient` value to match the current background color on every non-dev build (dev-server runs skip the sync to avoid constant working-tree drift).

Generated files under `resources/icons/` are gitignored — only the sources (`resources/branding/`) are committed.

### Maintenance notes

* Changing the background color for an environment is a one-line edit to `resources/branding/icon-config.json` (`backgroundColors.<env>`) — the next build regenerates every icon, including a re-synced `wereabouts.icon/icon.json`.
* The Apple-render color compensation in `bin/icons/colors.js` (`compensateForAppleRender`) is empirical; re-check and recalibrate it after major macOS/Icon Composer updates if rendered colors drift.

### Color treatment rules

* Standard favicon/app icons: brand color background with white glyph.

## Core palette

https://coolors.co/ff6200-ffead0-96616b-37505c-113537

Brand colour: "Blaze Orange" #ff6200"
Secondary Colours:
	"Champagne Mist" #ffead0
	"Smoky Rose": #96616b
	"Charcoal Blue": #37505c
	"Dark Teal": #113537

All defined as SCSS variables in `resources/sass/_variables.scss`
