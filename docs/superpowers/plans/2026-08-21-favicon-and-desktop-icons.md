# Favicon and Desktop Icons Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give wereabouts a favicon, apple-touch-icon, and installable-desktop-app manifest, generated at build time from the sword glyph, matching the pattern used in `~/code/aquarion/bloom`.

**Architecture:** A Vite plugin renders favicon/PWA icon files from an SVG glyph + per-environment background color at `buildStart`, writing them (gitignored) to `resources/icons/`. A new Blade partial links them from `<head>`, and a Laravel route serves a dynamic `site.webmanifest` whose `theme_color` matches the current environment's icon background color.

**Tech Stack:** Vite plugin (Node/`sharp`), Laravel Blade, Laravel routing, Pest (PHPUnit-style) feature tests.

---

## Reference: spec

Full design at `docs/superpowers/specs/2026-08-21-favicon-and-desktop-icons-design.md`. This plan implements it, with one refinement discovered while reading Bloom's actual source: the web manifest is served from a plain route closure returning `response()->json()` (exactly how Bloom does it), not a separate Blade view — simpler, and it's what Bloom itself does even though the spec sketched a `manifest.blade.php`.

## Prerequisites

- [ ] **Step 1: Confirm you're on the right branch**

Run: `git -C /home/aquarion/code/istic/wereabouts branch --show-current`
Expected: `feature/favicon-and-desktop-icons`

If not, stop and check out that branch before continuing — do not start this work on another branch.

---

### Task 1: Branding source assets

**Files:**
- Create: `resources/branding/noun-sword-point-6919420-FFFFFF.svg`
- Create: `resources/branding/icon-config.json`
- Create: `resources/branding/wereabouts.icon/icon.json`
- Create: `resources/branding/wereabouts.icon/Assets/noun-sword-point-6919420-FFFFFF.svg`

- [ ] **Step 1: Create the white-fill glyph SVG**

This is the sword glyph from `~/WinHome/Downloads/noun-sword-point-6919420.svg` with `fill="#FFFFFF"` added to the root `<svg>` element (none of its `<path>` elements set their own `fill`, so this recolors the whole glyph white — required because the generation scripts composite this glyph as a solid white cutout onto a colored background).

Write to `resources/branding/noun-sword-point-6919420-FFFFFF.svg`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200pt" height="1200pt" version="1.1" viewBox="0 0 1200 1200" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF">
 <path d="m648.98 267.84h-97.969c-10.125 0-18.375 8.25-18.375 18.375 0 10.172 8.25 18.375 18.375 18.375h97.969c10.125 0 18.375-8.2031 18.375-18.375 0-10.125-8.25-18.375-18.375-18.375z"/>
 <path d="m593.86 660.32v-253.97h-74.578l-43.078 402.84 117.66 153.84v-181.55c-31.312-2.8594-55.266-29.156-55.266-60.609s23.953-57.703 55.266-60.609z"/>
 <path d="m527.76 150.42c1.7344 4.4062 5.9531 7.2656 10.641 7.2188h123.14c4.6875 0.046875 8.9531-2.8125 10.641-7.2188l10.406-26.016v0.046875c1.4062-3.5625 0.98438-7.5938-1.125-10.734-2.1562-3.1406-5.7188-5.0156-9.5156-5.0625h-143.9c-3.7969 0-7.3594 1.9219-9.5156 5.0625-2.1094 3.1406-2.5781 7.1719-1.125 10.734z"/>
 <path d="m648.98 169.87h-97.969c-10.125 0-18.375 8.25-18.375 18.375 0 10.172 8.25 18.375 18.375 18.375h97.969c10.125 0 18.375-8.2031 18.375-18.375 0-10.125-8.25-18.375-18.375-18.375z"/>
 <path d="m648.98 218.86h-97.969c-10.125 0-18.375 8.25-18.375 18.375 0 10.172 8.25 18.375 18.375 18.375h97.969c10.125 0 18.375-8.2031 18.375-18.375 0-10.125-8.25-18.375-18.375-18.375z"/>
 <path d="m397.97 356.81c0 73.453-110.2 73.453-110.2 0 0-73.5 110.2-73.5 110.2 0"/>
 <path d="m912.24 356.81c0 73.453-110.2 73.453-110.2 0 0-73.5 110.2-73.5 110.2 0"/>
 <path d="m712.36 844.36-107.48 140.48c-0.09375 0.09375-0.1875 0.14062-0.28125 0.23438h0.046875c-0.42188 0.46875-0.89062 0.89062-1.4531 1.2188-0.23438 0.14062-0.375 0.32812-0.60938 0.42188v-0.046875c-1.5938 0.84375-3.4688 0.84375-5.1094 0-0.23438-0.09375-0.375-0.28125-0.60938-0.42188v0.046875c-0.51562-0.32812-0.98438-0.75-1.4062-1.2188-0.09375-0.09375-0.1875-0.14062-0.28125-0.23438l-107.53-140.48c-106.5 20.109-177.84 67.453-177.84 118.41 0 70.875 130.18 128.58 290.21 128.58s290.21-57.656 290.21-128.58c0-50.953-71.344-98.297-177.84-118.41z"/>
 <path d="m606.14 781.5v181.55l117.7-153.79-43.078-402.84h-74.625v253.92c31.312 2.8594 55.266 29.156 55.266 60.609 0 31.453-23.953 57.703-55.266 60.609z"/>
 <path d="m801.1 394.13c-15.047-22.594-15.047-52.031 0-74.578h-402.19c15.094 22.547 15.094 51.984 0 74.578z"/>
</svg>
```

- [ ] **Step 2: Create the icon config**

Write to `resources/branding/icon-config.json`:

```json
{
  "glyph": "resources/branding/noun-sword-point-6919420-FFFFFF.svg",
  "backgroundColor": "#FF6200",
  "backgroundColors": {
    "local": "#CC0000",
    "development": "#CC0000",
    "staging": "#0077CC",
    "production": "#FF6200"
  }
}
```

- [ ] **Step 3: Create the Apple Icon Composer bundle**

Write to `resources/branding/wereabouts.icon/icon.json`:

```json
{
  "fill": {
    "automatic-gradient": "display-p3:1.00000,0.38400,0.00000,1.00000"
  },
  "groups": [
    {
      "layers": [
        {
          "glass": true,
          "hidden": false,
          "image-name": "noun-sword-point-6919420-FFFFFF.svg",
          "name": "noun-sword-point-6919420-FFFFFF",
          "position": {
            "scale": 0.77,
            "translation-in-points": [
              0,
              0
            ]
          }
        }
      ],
      "shadow": {
        "kind": "neutral",
        "opacity": 0.5
      },
      "translucency": {
        "enabled": true,
        "value": 0.5
      }
    }
  ],
  "supported-platforms": {
    "circles": [
      "watchOS"
    ],
    "squares": "shared"
  }
}
```

The `fill.automatic-gradient` value is a placeholder — `generate-apple-touch-icon.js` (Task 5) overwrites it on every run to match `icon-config.json`'s current background color, so its starting value doesn't matter beyond being valid JSON.

- [ ] **Step 4: Copy the glyph into the icon bundle's Assets folder**

The bundle format expects its glyph asset alongside `icon.json`, not shared from `resources/branding/` directly (this matches Bloom's `bloom.icon/Assets/` layout).

Run:
```bash
mkdir -p /home/aquarion/code/istic/wereabouts/resources/branding/wereabouts.icon/Assets
cp /home/aquarion/code/istic/wereabouts/resources/branding/noun-sword-point-6919420-FFFFFF.svg \
   /home/aquarion/code/istic/wereabouts/resources/branding/wereabouts.icon/Assets/noun-sword-point-6919420-FFFFFF.svg
```

- [ ] **Step 5: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add resources/branding/
git commit -m "🎇 Add sword glyph and icon config for favicon generation"
```

---

### Task 2: Port pure utility modules

**Files:**
- Create: `bin/icons/colors.js`
- Create: `bin/icons/squircle.js`
- Create: `bin/icons/pack-ico.js`

These three files are copied verbatim from `~/code/aquarion/bloom/bin/icons/` — they contain no Bloom-specific paths or names, only generic color-space math, a squircle path generator, and an `.ico` packer.

- [ ] **Step 1: Create `bin/icons/colors.js`**

```javascript
// D65 matrices, ported from regen-icons.sh's hex_to_display_p3 Python heredoc.
const SRGB_TO_XYZ = [
    [0.4124564, 0.3575761, 0.1804375],
    [0.2126729, 0.7151522, 0.072175],
    [0.0193339, 0.119192, 0.9503041],
];

const XYZ_TO_P3 = [
    [2.4934969, -0.9313836, -0.4027108],
    [-0.829489, 1.762664, 0.0236247],
    [0.0358458, -0.0761724, 0.9568845],
];

// Empirical per-channel darkening observed in apple-touch-icon output vs the
// requested background color, ported from regen-icons.sh's DARKEN constant.
const APPLE_RENDER_DARKEN = [
    0.6022727272727273, 0.4375, 0.7962962962962963,
];

function multiply(matrix, vector) {
    return matrix.map((row) =>
        row.reduce((sum, value, index) => sum + value * vector[index], 0),
    );
}

function srgbToLinear(channel) {
    return channel <= 0.04045
        ? channel / 12.92
        : ((channel + 0.055) / 1.055) ** 2.4;
}

export function hexToRgb(hex) {
    const raw = hex.replace('#', '');

    return [0, 2, 4].map((offset) => Number.parseInt(raw.slice(offset, offset + 2), 16));
}

export function rgbToHex([r, g, b]) {
    return `#${[r, g, b]
        .map((channel) => channel.toString(16).padStart(2, '0').toUpperCase())
        .join('')}`;
}

export function hexToDisplayP3(hex) {
    const linear = hexToRgb(hex).map((channel) => srgbToLinear(channel / 255));
    const xyz = multiply(SRGB_TO_XYZ, linear);
    const p3 = multiply(XYZ_TO_P3, xyz).map((channel) => Math.min(1, Math.max(0, channel)));

    return `display-p3:${p3.map((channel) => channel.toFixed(5)).join(',')},1.00000`;
}

export function compensateForAppleRender(hex) {
    const compensated = hexToRgb(hex).map((channel, index) =>
        Math.min(255, Math.max(0, Math.round(channel / APPLE_RENDER_DARKEN[index]))),
    );

    return rgbToHex(compensated);
}

// Apple's icon tool stores gamma-encoded P3 components directly in the sRGB
// container without gamut conversion. This replicates that quirk so the
// background color used for icon rendering matches the old pipeline exactly.
export function p3StringToAppleRgb(p3Str) {
    const match = p3Str.match(/display-p3:([\d.]+),([\d.]+),([\d.]+)/);

    if (!match) {
        throw new Error(`p3StringToAppleRgb: cannot parse "${p3Str}"`);
    }

    return [match[1], match[2], match[3]].map((v) => Math.round(Number(v) * 255));
}
```

- [ ] **Step 2: Create `bin/icons/squircle.js`**

```javascript
// Quintic superellipse (n=5 by convention here) path generator matching
// Apple's squircle shape, traced in one-degree steps from the rightmost point.
export function generateSquirclePath(size, exponent) {
    const radius = size / 2;
    const center = size / 2;
    let path = `M ${radius + center},${center} `;

    for (let degrees = 0; degrees <= 360; degrees += 1) {
        const angle = (degrees * Math.PI) / 180;
        const cos = Math.cos(angle);
        const sin = Math.sin(angle);
        const x = Math.abs(cos) ** (2 / exponent) * radius * Math.sign(cos) + center;
        const y = Math.abs(sin) ** (2 / exponent) * radius * Math.sign(sin) + center;

        path += `L ${x},${y} `;
    }

    return `${path}Z`;
}
```

- [ ] **Step 3: Create `bin/icons/pack-ico.js`**

```javascript
/* global Buffer */

const HEADER_SIZE = 6;
const DIRECTORY_ENTRY_SIZE = 16;
const PNG_SIGNATURE = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

export function readPngDimensions(png) {
    if (!png.subarray(0, 8).equals(PNG_SIGNATURE)) {
        throw new Error('readPngDimensions: buffer is not a PNG image');
    }

    return { width: png.readUInt32BE(16), height: png.readUInt32BE(20) };
}

// Builds a multi-frame .ico container directly from PNG buffers — the format
// every modern browser and OS supports since Windows Vista. Frames at or above
// 256px store 0 in the (single-byte) directory width/height fields; readers
// fall back to the embedded PNG's own dimensions, which is exactly how
// ImageMagick encodes the existing favicon.ico's 512px frame.
export function packIco(pngBuffers) {
    const directorySize = DIRECTORY_ENTRY_SIZE * pngBuffers.length;
    const header = Buffer.alloc(HEADER_SIZE);

    header.writeUInt16LE(0, 0); // reserved
    header.writeUInt16LE(1, 2); // type: icon
    header.writeUInt16LE(pngBuffers.length, 4);

    const directory = Buffer.alloc(directorySize);
    let dataOffset = HEADER_SIZE + directorySize;

    pngBuffers.forEach((png, index) => {
        const { width, height } = readPngDimensions(png);
        const entry = index * DIRECTORY_ENTRY_SIZE;

        directory.writeUInt8(width >= 256 ? 0 : width, entry);
        directory.writeUInt8(height >= 256 ? 0 : height, entry + 1);
        directory.writeUInt8(0, entry + 2); // palette colors (none)
        directory.writeUInt8(0, entry + 3); // reserved
        directory.writeUInt16LE(1, entry + 4); // color planes
        directory.writeUInt16LE(32, entry + 6); // bits per pixel
        directory.writeUInt32LE(png.length, entry + 8);
        directory.writeUInt32LE(dataOffset, entry + 12);

        dataOffset += png.length;
    });

    return Buffer.concat([header, directory, ...pngBuffers]);
}
```

- [ ] **Step 4: Syntax-check all three files**

There's no JS test runner in this repo (a deliberate scope decision — see the spec), so verify these parse correctly with Node's own syntax checker instead:

Run: `node --check /home/aquarion/code/istic/wereabouts/bin/icons/colors.js && node --check /home/aquarion/code/istic/wereabouts/bin/icons/squircle.js && node --check /home/aquarion/code/istic/wereabouts/bin/icons/pack-ico.js && echo OK`
Expected: `OK`

- [ ] **Step 5: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add bin/icons/colors.js bin/icons/squircle.js bin/icons/pack-ico.js
git commit -m "🎇 Port icon color/squircle/ico-packing utilities from Bloom"
```

---

### Task 3: Port the web icon generator

**Files:**
- Create: `bin/icons/generate-web-icons.js`

This is Bloom's `generate-web-icons.js` with the two Bloom-branded outputs (`bloom-standard.png`, `bloom-on-white.png`) removed — wereabouts has no use for them (Bloom itself never links them from anywhere either).

- [ ] **Step 1: Create `bin/icons/generate-web-icons.js`**

```javascript
/* global Buffer */
import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';
import { packIco } from './pack-ico.js';

const DEFAULT_OUTPUT_DIR = 'resources/icons';
const CANVAS_SIZE = 1200;
const FAVICON_SIZES = [16, 32, 48, 64, 128, 256, 512];

function extractGlyphMarkup(svgMarkup) {
    const match = svgMarkup.match(/<svg\b[^>]*>([\s\S]*)<\/svg>/i);

    if (!match) {
        throw new Error('extractGlyphMarkup: unable to parse glyph SVG');
    }

    return match[1].trim();
}

function buildCanvasSvg(glyphMarkup, backgroundColor) {
    return `<?xml version="1.0" encoding="UTF-8"?>
<svg viewBox="0 0 ${CANVAS_SIZE} ${CANVAS_SIZE}" xmlns="http://www.w3.org/2000/svg">
  <rect x="0" y="0" width="${CANVAS_SIZE}" height="${CANVAS_SIZE}" fill="${backgroundColor}"/>
  ${glyphMarkup}
</svg>`;
}

async function writeOutput(outputDir, name, contents) {
    await fs.writeFile(path.join(outputDir, name), contents);
}

async function renderPng(svgBuffer, size) {
    return sharp(svgBuffer).resize(size, size).png().toBuffer();
}

export async function generateWebIcons(config, outputDir = DEFAULT_OUTPUT_DIR) {
    const glyphSource = await fs.readFile(config.glyph, 'utf-8');
    const glyphMarkup = extractGlyphMarkup(glyphSource);

    const standardSvg = buildCanvasSvg(glyphMarkup, config.backgroundColor);
    const standardBuffer = Buffer.from(standardSvg);

    await fs.mkdir(outputDir, { recursive: true });

    const faviconFrames = await Promise.all(FAVICON_SIZES.map((size) => renderPng(standardBuffer, size)));

    await Promise.all([
        writeOutput(outputDir, 'favicon.svg', standardSvg),
        writeOutput(outputDir, 'favicon.ico', packIco(faviconFrames)),
        renderPng(standardBuffer, 96).then((png) => writeOutput(outputDir, 'favicon-96x96.png', png)),
        renderPng(standardBuffer, 192).then((png) => writeOutput(outputDir, 'web-app-manifest-192x192.png', png)),
        renderPng(standardBuffer, 512).then((png) => writeOutput(outputDir, 'web-app-manifest-512x512.png', png)),
    ]);
}
```

- [ ] **Step 2: Syntax-check**

Run: `node --check /home/aquarion/code/istic/wereabouts/bin/icons/generate-web-icons.js && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add bin/icons/generate-web-icons.js
git commit -m "🎇 Add web icon generator (favicon, web-app-manifest PNGs)"
```

---

### Task 4: Port the Apple touch icon generator

**Files:**
- Create: `bin/icons/generate-apple-touch-icon.js`

Copied verbatim from Bloom — no Bloom-specific paths (it reads whatever `icon.json`/glyph the config passed to it points at).

- [ ] **Step 1: Create `bin/icons/generate-apple-touch-icon.js`**

```javascript
/* global Buffer */
import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';
import { compensateForAppleRender, hexToDisplayP3, p3StringToAppleRgb } from './colors.js';
import { generateSquirclePath } from './squircle.js';

const ICON_DIR = 'resources/branding/wereabouts.icon';
const JSON_PATH = path.join(ICON_DIR, 'icon.json');
const DEFAULT_OUTPUT_DIR = 'resources/icons';
const SIZE = 1024;

// Apple's "automatic-gradient" lightens the top of the icon by ~40 RGB units,
// reaching the base color at ~70% of the height and staying flat below that.
const GRADIENT_LIFT = 40;

async function fileExists(filePath) {
    try {
        await fs.access(filePath);

        return true;
    } catch {
        return false;
    }
}

async function syncIconJsonGradient(compensatedHex, write = true) {
    const iconData = JSON.parse(await fs.readFile(JSON_PATH, 'utf-8'));

    iconData.fill = { ...iconData.fill, 'automatic-gradient': hexToDisplayP3(compensatedHex) };

    if (write) {
        await fs.writeFile(JSON_PATH, `${JSON.stringify(iconData, null, 2)}\n`, 'utf-8');
    }

    return iconData;
}

function backgroundLayer(rgb) {
    const [r, g, b] = rgb;
    const baseColor = `rgb(${r}, ${g}, ${b})`;
    const topColor = `rgb(${Math.min(255, r + GRADIENT_LIFT)}, ${Math.min(255, g + GRADIENT_LIFT)}, ${Math.min(255, b + GRADIENT_LIFT)})`;

    const squircleMask = Buffer.from(`
        <svg width="${SIZE}" height="${SIZE}" viewBox="0 0 ${SIZE} ${SIZE}">
            <path d="${generateSquirclePath(SIZE, 5)}" fill="white" />
        </svg>
    `);

    const gradient = Buffer.from(`
        <svg width="${SIZE}" height="${SIZE}" viewBox="0 0 ${SIZE} ${SIZE}">
            <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:${topColor}" />
                <stop offset="70%" style="stop-color:${baseColor}" />
                <stop offset="100%" style="stop-color:${baseColor}" />
            </linearGradient>
            <rect width="${SIZE}" height="${SIZE}" fill="url(#grad)" />
        </svg>
    `);

    return sharp(gradient).composite([{ input: squircleMask, blend: 'dest-in' }]).png().toBuffer();
}

async function glyphLayer(group, layer) {
    const imagePath = path.join(ICON_DIR, 'Assets', layer['image-name']);

    if (!(await fileExists(imagePath))) {
        return null;
    }

    const originalSvg = await fs.readFile(imagePath, 'utf-8');
    const pathMatch = originalSvg.match(/<path d="([^"]+)"/);

    if (!pathMatch) {
        return null;
    }

    const scale = layer.position?.scale || 1.0;
    // Apple's icon JSON expresses scale as a "coverage" fraction; its renderer
    // maps that to an effective layer size via a power curve — exponent ~0.35
    // empirically matches Xcode's output across the observable scale range.
    const renderedScale = scale ** 0.35;
    const layerSize = Math.round(SIZE * renderedScale);
    const layerOffset = Math.round((SIZE - layerSize) / 2);

    // Apple's translucency is a frosted-glass blend, not simple fill-opacity.
    // The interior petal pixels in the reference output match ~0.70 opacity for
    // translucency=0.5; specular highlights then push bright edges toward white.
    const translucency = group.translucency?.enabled ? (group.translucency.value ?? 0.5) : 1.0;
    const layerOpacity = Math.min(1.0, 0.4 + translucency * 0.55);

    const glassGlyphSvg = `
        <svg width="${layerSize}" height="${layerSize}" viewBox="0 0 1200 1200">
            <defs>
                <filter id="liquidGlass" x="-15%" y="-15%" width="130%" height="130%">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="14" result="glowBlur" />
                    <feFlood flood-color="white" flood-opacity="0.3" result="glowFill" />
                    <feComposite in="glowFill" in2="glowBlur" operator="in" result="outerGlow" />
                    <feGaussianBlur in="SourceAlpha" stdDeviation="16" result="bump" />
                    <feSpecularLighting in="bump" surfaceScale="6" specularConstant="3" specularExponent="25" lighting-color="white" result="spec">
                        <fePointLight x="-300" y="-500" z="900" />
                    </feSpecularLighting>
                    <feComposite in="spec" in2="SourceAlpha" operator="in" result="specLight" />
                    <feMerge>
                        <feMergeNode in="outerGlow" />
                        <feMergeNode in="SourceGraphic" />
                        <feMergeNode in="specLight" />
                    </feMerge>
                </filter>
            </defs>
            <path d="${pathMatch[1]}" fill="white" fill-opacity="${layerOpacity}" filter="url(#liquidGlass)" />
        </svg>
    `;

    const input = await sharp(Buffer.from(glassGlyphSvg)).png().toBuffer();

    return { input, top: layerOffset, left: layerOffset };
}

// Apple's squircle has a ~20px bright specular highlight along all edges,
// clipped to the squircle boundary: feMorphology erode carves a border ring,
// then a Gaussian blur softens it inward.
async function edgeGlowLayer() {
    const svg = `
        <svg width="${SIZE}" height="${SIZE}" viewBox="0 0 ${SIZE} ${SIZE}">
            <defs>
                <filter id="edgeGlow" x="0%" y="0%" width="100%" height="100%">
                    <feMorphology in="SourceAlpha" operator="erode" radius="16" result="eroded" />
                    <feComposite in="SourceAlpha" in2="eroded" operator="arithmetic" k2="1" k3="-1" result="ring" />
                    <feGaussianBlur in="ring" stdDeviation="7" result="soft" />
                    <feFlood flood-color="white" flood-opacity="0.6" result="white" />
                    <feComposite in="white" in2="soft" operator="in" result="glow" />
                    <feComposite in="glow" in2="SourceAlpha" operator="in" />
                </filter>
            </defs>
            <path d="${generateSquirclePath(SIZE, 5)}" fill="white" filter="url(#edgeGlow)" />
        </svg>
    `;

    return { input: await sharp(Buffer.from(svg)).png().toBuffer(), top: 0, left: 0 };
}

// Apple's top-left corner has a stronger, crisper highlight. surfaceScale=51
// compensates for librsvg normalising bump gradients by 255; the low z=80
// point light makes interior normals near-zero while the TL corner's outward
// normal aligns with the light, creating a highlight that fades at TR/BR.
async function cornerSpecularLayer() {
    const svg = `
        <svg width="${SIZE}" height="${SIZE}" viewBox="0 0 ${SIZE} ${SIZE}">
            <defs>
                <filter id="cornerSpec" x="0%" y="0%" width="100%" height="100%">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="12" result="bump" />
                    <feSpecularLighting in="bump" surfaceScale="51" specularConstant="0.65" specularExponent="8" lighting-color="white" result="spec">
                        <fePointLight x="-100" y="-100" z="80" />
                    </feSpecularLighting>
                    <feComposite in="spec" in2="SourceAlpha" operator="in" />
                </filter>
            </defs>
            <path d="${generateSquirclePath(SIZE, 5)}" fill="white" filter="url(#cornerSpec)" />
        </svg>
    `;

    return { input: await sharp(Buffer.from(svg)).png().toBuffer(), top: 0, left: 0 };
}

export async function generateAppleTouchIcon(config, outputDir = DEFAULT_OUTPUT_DIR, { syncJson = true } = {}) {
    const compensatedHex = compensateForAppleRender(config.backgroundColor);
    const iconData = await syncIconJsonGradient(compensatedHex, syncJson);
    // Replicate Apple's icon tool quirk: P3 components are stored in the sRGB
    // container without gamut conversion, so we read them back the same way.
    const rgb = p3StringToAppleRgb(iconData.fill['automatic-gradient']);

    const composites = [{ input: await backgroundLayer(rgb), top: 0, left: 0 }];

    for (const group of iconData.groups || []) {
        for (const layer of group.layers || []) {
            const composite = await glyphLayer(group, layer);

            if (composite) {
                composites.push(composite);
            }
        }
    }

    composites.push(await edgeGlowLayer());
    composites.push(await cornerSpecularLayer());

    await fs.mkdir(outputDir, { recursive: true });
    await sharp({
        create: { width: SIZE, height: SIZE, channels: 4, background: { r: 0, g: 0, b: 0, alpha: 0 } },
    })
        .composite(composites)
        .toFile(path.join(outputDir, 'apple-touch-icon.png'));
}
```

Note `ICON_DIR` is `resources/branding/wereabouts.icon` (renamed from Bloom's `bloom.icon`) — this must match the directory created in Task 1.

- [ ] **Step 2: Syntax-check**

Run: `node --check /home/aquarion/code/istic/wereabouts/bin/icons/generate-apple-touch-icon.js && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add bin/icons/generate-apple-touch-icon.js
git commit -m "🎇 Add Apple touch icon generator (liquid-glass render)"
```

---

### Task 5: Wire icon generation into the Vite build

**Files:**
- Create: `bin/icons/vite-plugin.js`
- Modify: `vite.config.js`
- Modify: `package.json`
- Modify: `.gitignore`

- [ ] **Step 1: Create `bin/icons/vite-plugin.js`**

```javascript
/* global process */
import fs from 'node:fs/promises';
import { generateAppleTouchIcon } from './generate-apple-touch-icon.js';
import { generateWebIcons } from './generate-web-icons.js';

const CONFIG_PATH = 'resources/branding/icon-config.json';

export function iconGenerationPlugin() {
    let viteMode;
    let isServe;

    return {
        name: 'wereabouts-icon-generation',
        configResolved(config) {
            // APP_ENV (local/staging/production) takes precedence over Vite mode so
            // staging servers don't need --mode staging on their build command.
            viteMode = process.env.APP_ENV ?? config.mode;
            isServe = config.command === 'serve';
        },
        async buildStart() {
            const iconConfig = JSON.parse(await fs.readFile(CONFIG_PATH, 'utf-8'));
            const backgroundColor = iconConfig.backgroundColors?.[viteMode] ?? iconConfig.backgroundColor;
            const config = { ...iconConfig, backgroundColor };

            await generateWebIcons(config);
            // Skip syncing icon.json during dev server — it causes constant
            // working-tree drift as Vite restarts with the environment colour.
            await generateAppleTouchIcon(config, undefined, { syncJson: !isServe });
        },
    };
}
```

- [ ] **Step 2: Add `sharp` as a devDependency**

Read `/home/aquarion/code/istic/wereabouts/package.json`, then edit the `devDependencies` block to insert `sharp` alphabetically after `sass`:

```json
        "sass": "^1.102.0",
        "sharp": "^0.35.3",
        "vite": "^8"
```

- [ ] **Step 3: Wire the plugin and generated files into `vite.config.js`**

Replace the full contents of `/home/aquarion/code/istic/wereabouts/vite.config.js` with:

```javascript
import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin';
import { iconGenerationPlugin } from './bin/icons/vite-plugin.js';

const env = loadEnv('', process.cwd(), '');

export default defineConfig({
    plugins: [
        iconGenerationPlugin(),
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/icons/apple-touch-icon.png',
                'resources/icons/favicon-96x96.png',
                'resources/icons/favicon.ico',
                'resources/icons/favicon.svg',
                'resources/icons/web-app-manifest-192x192.png',
                'resources/icons/web-app-manifest-512x512.png',
            ],
            refresh: true,
        }),
    ],
  server: {
    hmr: {
      host: env.APP_HOST,
      port: 5173,
    },
    watch: {
      usePolling: true,
    },
    host: '0.0.0.0',
    port: 5173,
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler' // or "modern"
      }
    }
  }
});
```

- [ ] **Step 4: Add the generated icon files to `.gitignore`**

Edit `/home/aquarion/code/istic/wereabouts/.gitignore`, adding this block (placement anywhere is fine — e.g. right after the existing `/public/build` line):

```
/resources/icons/apple-touch-icon.png
/resources/icons/favicon-96x96.png
/resources/icons/favicon.ico
/resources/icons/favicon.svg
/resources/icons/web-app-manifest-192x192.png
/resources/icons/web-app-manifest-512x512.png
```

- [ ] **Step 5: Install `sharp`**

Run: `cd /home/aquarion/code/istic/wereabouts && npm install`
Expected: exits 0, `sharp` appears in `package-lock.json`.

- [ ] **Step 6: Syntax-check the plugin**

Run: `node --check /home/aquarion/code/istic/wereabouts/bin/icons/vite-plugin.js && echo OK`
Expected: `OK`

- [ ] **Step 7: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add bin/icons/vite-plugin.js vite.config.js package.json package-lock.json .gitignore
git commit -m "🎇 Wire icon generation into the Vite build"
```

---

### Task 6: Verify icon generation actually produces correct output

**Files:** none (verification only — this task is the substitute for the JS unit tests this feature deliberately skips; see the spec's "Out of scope" section).

- [ ] **Step 1: Run a full build**

Run: `cd /home/aquarion/code/istic/wereabouts && rm -rf resources/icons && npm run build`
Expected: exits 0, no errors from the icon plugin's `buildStart` hook.

- [ ] **Step 2: Verify every expected file was generated**

Run:
```bash
cd /home/aquarion/code/istic/wereabouts
for f in favicon.svg favicon.ico favicon-96x96.png web-app-manifest-192x192.png web-app-manifest-512x512.png apple-touch-icon.png; do
  test -f "resources/icons/$f" && echo "OK: $f" || echo "MISSING: $f"
done
```
Expected: `OK:` for all six files, no `MISSING:` lines.

- [ ] **Step 3: Verify PNG dimensions match their filenames**

Run:
```bash
cd /home/aquarion/code/istic/wereabouts
file resources/icons/favicon-96x96.png resources/icons/web-app-manifest-192x192.png resources/icons/web-app-manifest-512x512.png resources/icons/apple-touch-icon.png
```
Expected: reports `96 x 96`, `192 x 192`, `512 x 512`, and `1024 x 1024` respectively (apple-touch-icon.png is rendered at `SIZE = 1024` in `generate-apple-touch-icon.js`, then displayed by browsers at 180x180 via the `sizes="180x180"` attribute set in Task 7 — this is intentional, matching Bloom).

- [ ] **Step 4: Verify the icon.json gradient was synced to the production color**

Run: `git -C /home/aquarion/code/istic/wereabouts diff resources/branding/wereabouts.icon/icon.json`
Expected: a diff showing `fill.automatic-gradient` changed from the Task 1 placeholder to a value derived from `#FF6200` (the default `backgroundColor` in `icon-config.json`, used because no `APP_ENV` was set for this manual build).

- [ ] **Step 5: Commit the synced icon.json**

```bash
cd /home/aquarion/code/istic/wereabouts
git add resources/branding/wereabouts.icon/icon.json
git commit -m "🎇 Sync icon.json gradient to production background color"
```

---

### Task 7: Link the icons from `<head>`

**Files:**
- Create: `resources/views/components/icons.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/LayoutIconsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LayoutIconsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class LayoutIconsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::del('venue.index.v3', 'venue.index.v3.stale');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_the_layout_head_links_the_generated_favicons_and_manifest(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([]));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('rel="icon"', false);
        $response->assertSee('rel="shortcut icon"', false);
        $response->assertSee('rel="apple-touch-icon"', false);
        $response->assertSee('rel="manifest"', false);
    }
}
```

This reuses the `FakeGoogleClient` helper already defined in `tests/Feature/VenueListingTest.php` (same namespace, so it's visible here without an import).

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/aquarion/code/istic/wereabouts && php artisan test --filter=LayoutIconsTest`
Expected: FAIL — the assertions for `rel="icon"` etc. don't find anything in the response body, since `<head>` doesn't emit them yet.

Note: this requires `public/build/manifest.json` to exist. If Task 6 already ran `npm run build`, it does. If you skipped straight to this task, run `npm run build` first.

- [ ] **Step 3: Create the icons partial**

Write to `resources/views/components/icons.blade.php`:

```blade
<link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon-96x96.png') }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon.svg') }}" />
<link rel="shortcut icon" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon.ico') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/apple-touch-icon.png') }}" />
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Wereabouts') }}" />
<link rel="manifest" href="{{ route('manifest.webmanifest') }}" />
```

- [ ] **Step 4: Include it from the layout**

Modify `resources/views/layouts/app.blade.php` — in the `<head>` block, change:

```blade
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
```

to:

```blade
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Icons -->
    @include('components.icons')

    <!-- Fonts -->
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `cd /home/aquarion/code/istic/wereabouts && php artisan test --filter=LayoutIconsTest`
Expected: PASS

Note: this test will fail with a Vite manifest error until Task 8 adds the `manifest.webmanifest` named route (`route('manifest.webmanifest')` in the partial will throw a `RouteNotFoundException` otherwise). If that happens here, that's expected — proceed to Task 8, then return and re-run this test as part of Task 8's own verification step.

- [ ] **Step 6: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add resources/views/components/icons.blade.php resources/views/layouts/app.blade.php tests/Feature/LayoutIconsTest.php
git commit -m "🖼️ Link generated favicons and manifest from the page head"
```

---

### Task 8: Serve the web app manifest

**Files:**
- Create: `config/branding.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ManifestTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ManifestTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManifestTest extends TestCase
{
    public function test_the_web_manifest_is_served_with_the_correct_content_type(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/manifest+json');
    }

    public function test_the_web_manifest_reports_the_app_name_and_theme_color(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertJsonPath('name', config('app.name'));
        $response->assertJsonPath('short_name', config('app.name'));
        $response->assertJsonPath('display', 'standalone');
        $response->assertJsonPath('theme_color', config('branding.default_color'));
        $response->assertJsonPath('background_color', config('branding.default_color'));
    }

    public function test_the_web_manifest_lists_both_icon_sizes(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertJsonCount(2, 'icons');
        $response->assertJsonPath('icons.0.sizes', '192x192');
        $response->assertJsonPath('icons.1.sizes', '512x512');
    }
}
```

`theme_color`/`background_color` assert against `config('branding.default_color')` rather than a hardcoded hex: PHPUnit runs with `APP_ENV=testing` (set in `phpunit.xml`), which has no entry in the environment color map, so the route falls back to the default color — the same fallback the config itself will define in Step 3.

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/aquarion/code/istic/wereabouts && php artisan test --filter=ManifestTest`
Expected: FAIL — `/site.webmanifest` 404s, since the route doesn't exist yet.

- [ ] **Step 3: Create the branding config**

Write to `config/branding.php`:

```php
<?php

return [
    // Keep this in sync with resources/branding/icon-config.json's
    // "backgroundColors" map — the Vite icon generator and this file must
    // resolve the same background color for the same environment, or the
    // favicon and the web app manifest's theme_color will visibly disagree.
    'colors' => [
        'local' => '#CC0000',
        'development' => '#CC0000',
        'staging' => '#0077CC',
        'production' => '#FF6200',
    ],

    'default_color' => '#FF6200',
];
```

- [ ] **Step 4: Add the manifest route**

Modify `routes/web.php`, adding the `Vite` import and the new route:

```php
<?php

use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;

Route::get('/', [VenueController::class, 'index'])->name('home');
Route::get('/venue/{slug}', [VenueController::class, 'show'])->name('venue.show');

Route::get('site.webmanifest', function () {
    $color = config('branding.colors.'.app()->environment(), config('branding.default_color'));

    return response()->json([
        'name' => config('app.name', 'Wereabouts'),
        'short_name' => config('app.name', 'Wereabouts'),
        'start_url' => '/',
        'display' => 'standalone',
        'theme_color' => $color,
        'background_color' => $color,
        'icons' => [
            [
                'src' => Vite::asset('resources/icons/web-app-manifest-192x192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => Vite::asset('resources/icons/web-app-manifest-512x512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('manifest.webmanifest');
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `cd /home/aquarion/code/istic/wereabouts && php artisan test --filter=ManifestTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Re-run Task 7's layout test now that the named route exists**

Run: `cd /home/aquarion/code/istic/wereabouts && php artisan test --filter=LayoutIconsTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
cd /home/aquarion/code/istic/wereabouts
git add config/branding.php routes/web.php tests/Feature/ManifestTest.php
git commit -m "🎇 Serve a per-environment web app manifest at /site.webmanifest"
```

---

### Task 9: Full verification

**Files:** none.

- [ ] **Step 1: Clean build from scratch**

Run: `cd /home/aquarion/code/istic/wereabouts && rm -rf resources/icons public/build && npm run build`
Expected: exits 0.

- [ ] **Step 2: Run the full PHP test suite**

Run: `cd /home/aquarion/code/istic/wereabouts && composer test`
Expected: all tests pass, including `LayoutIconsTest` and `ManifestTest`.

- [ ] **Step 3: Run PHPStan and Pint (existing project quality gates)**

Run: `cd /home/aquarion/code/istic/wereabouts && composer phpstan && vendor/bin/pint --test`
Expected: both exit 0 with no issues. If Pint reports formatting issues in the new PHP files, run `vendor/bin/pint` (without `--test`) to fix them, then re-run `composer test`.

- [ ] **Step 4: Manually verify the icons render correctly**

Use the `/run` skill to start the app and check in a browser:
1. Load the home page, confirm a browser tab favicon appears (may require a hard refresh / clearing the favicon cache).
2. Visit `/site.webmanifest` directly and confirm it returns valid JSON with a `Content-Type: application/manifest+json` header.
3. Open browser devtools → Application/Manifest panel (Chrome) and confirm the manifest's icons load without errors.

- [ ] **Step 5: Final commit if any fixes were needed**

If Steps 3 or 4 required changes, commit them:

```bash
cd /home/aquarion/code/istic/wereabouts
git add -A
git commit -m "🪳 Fix formatting/lint issues found during favicon verification"
```

If nothing needed fixing, skip this step — there's nothing to commit.
