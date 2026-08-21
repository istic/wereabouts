# Favicon and desktop icons (#8)

## Goal

Give wereabouts a favicon, apple-touch-icon, and installable-desktop-app icons, ported from the icon-generation pipeline in `~/code/aquarion/bloom`, using the sword glyph at `~/WinHome/Downloads/noun-sword-point-6919420.svg`.

## Source glyph

Add `resources/branding/noun-sword-point-6919420-FFFFFF.svg`: a copy of the sword SVG with `fill="#FFFFFF"` set on the root `<svg>` element (none of its `<path>` elements specify their own fill, so this recolors the whole glyph white). The original file in Downloads is left untouched and is not itself committed.

## Icon config

`resources/branding/icon-config.json`:

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

`backgroundColor` is the fallback; `backgroundColors[env]` overrides it per `APP_ENV`/Vite mode, matching Bloom's pattern exactly.

## Generation scripts

Port from Bloom's `bin/icons/`, adapted only where they reference Bloom-specific paths/names:

- `colors.js` — Display P3 / Apple-render color helpers, unchanged.
- `squircle.js` — squircle path generator, unchanged.
- `pack-ico.js` — PNG-frames-to-.ico packer, unchanged.
- `generate-web-icons.js` — renders `favicon.svg`, `favicon.ico`, `favicon-96x96.png`, `web-app-manifest-192x192.png`, `web-app-manifest-512x512.png` from the glyph + background color. `DEFAULT_OUTPUT_DIR` stays `resources/icons`. Drop the `bloom-standard.png` / `bloom-on-white.png` outputs — Bloom doesn't link these anywhere either, and they're not needed here.
- `generate-apple-touch-icon.js` — full liquid-glass render (squircle mask, gradient, specular highlights) via the Apple Icon Composer JSON bundle format, unchanged in logic. Reads from `resources/branding/wereabouts.icon/icon.json` (renamed from `bloom.icon`).
- `vite-plugin.js` — `iconGenerationPlugin()`, unchanged logic, `CONFIG_PATH` points at the wereabouts `icon-config.json`.

No `.test.js` files are ported — wereabouts has no JS test runner today, and adding one is out of scope for this feature. The generation scripts are exercised implicitly on every `vite build`/`vite dev`; visible breakage (bad icons, failed build) is easy to catch manually.

`resources/branding/wereabouts.icon/icon.json` — same shape as Bloom's `bloom.icon/icon.json`, one glass layer referencing `noun-sword-point-6919420-FFFFFF.svg`, default `scale: 0.77` (adjust after visually reviewing the rendered icon), `translucency` enabled at `0.5`, neutral shadow. `Assets/noun-sword-point-6919420-FFFFFF.svg` is a copy of the same white glyph, matching Bloom's `.icon` bundle layout.

## Build wiring

- Add `sharp` as a devDependency in `package.json` (same version constraint as Bloom, `^0.35.3`, unless a newer compatible version is current at install time).
- `vite.config.js`: import and register `iconGenerationPlugin()` in `plugins`, and add the six generated icon paths (`resources/icons/{apple-touch-icon.png,favicon-96x96.png,favicon.ico,favicon.svg,web-app-manifest-192x192.png,web-app-manifest-512x512.png}`) to the Laravel plugin's `input` array so `Vite::asset()` can resolve their hashed URLs.
- `.gitignore`: add the same `resources/icons/*` generated-file entries Bloom ignores (six files above). The `resources/branding/` sources (config, glyphs, `.icon` bundle) are committed as normal.

## `<head>` wiring

New `resources/views/components/icons.blade.php`, mirroring Bloom's:

```blade
<link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon-96x96.png') }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon.svg') }}" />
<link rel="shortcut icon" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/favicon.ico') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/icons/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ route('manifest') }}" />
```

Included via `@include('components.icons')` inside `<head>` in `resources/views/layouts/app.blade.php`, alongside the existing `@vite(...)` call.

## Web app manifest

wereabouts doesn't have per-environment branding config on the PHP side yet, so:

- `config/branding.php` — returns the background-color map keyed by environment, read via `env('APP_ENV')`, mirroring `resources/branding/icon-config.json`'s `backgroundColors`. A comment notes the two files must be kept in sync (this is the one non-obvious cross-file/cross-language invariant in the feature).
- `resources/views/manifest.blade.php` — a Blade view producing the manifest JSON body: `name`/`short_name: "Wereabouts"`, `start_url: "/"`, `display: "standalone"`, `theme_color`/`background_color` from `config('branding.color')`, and an `icons` array pointing at the 192x192/512x512 PNGs via `Vite::asset()`.
- `routes/web.php` — `Route::get('/site.webmanifest', fn () => response()->view('manifest', [], 200, ['Content-Type' => 'application/manifest+json']))->name('manifest');`. A route (not a static file) is required because the icon URLs are build-hashed by Vite and can change between deploys.

## Out of scope

- No JS test coverage for the ported generation scripts (see above).
- No "on-white"/brand-asset PNG variants (Bloom generates but never links these).
- No changes to the existing PHP test suite — this feature has no server-side behavior beyond the new manifest route, which is simple enough not to need dedicated test coverage beyond a smoke check in the implementation plan.
