# fares-theme

Classic (non-FSE) WooCommerce theme + `fares-store` companion plugin for an
Arabic, RTL, dark code-delivery store.

## Development

```bash
npm install
npm run env:start        # boot wp-env (Docker: WooCommerce + serial-numbers + fares-store, locale ar)
npm run build            # PostCSS + esbuild (assets/css/dist, assets/js/dist)
npm run seed             # demo content: categories, sample products, pages, menu, serials, COD gateway
npm run import           # real catalogue: 357 products from bin/data/products.json (fast, offline-safe)
npm run import:images    # same, plus sideloading each product's Salla CDN image (network-dependent)
npx playwright test      # e2e at 375 / 768 / 1440 + axe
```

## Product catalogue

`bin/data/products.json` is the normalised catalogue (exported from Salla,
one JSON object per product). `bin/import-products.php` loads it into
WooCommerce **idempotently, keyed by SKU** (`salla-{id}`):

- Re-running updates price / sale / stock / status / categories in place — never
  duplicates. Descriptions are only written on first import, so hand-edits survive.
- Status maps `متاح → publish/instock`, `غير متاح → publish/outofstock`,
  `مخفي → draft`. All products are virtual.
- Featured images are only fetched when missing, so `import:images` is resumable.

The importer is additive: it leaves the `npm run seed` demo products, pages,
menus and serial keys (which the e2e suite depends on) untouched.

To refresh the catalogue from a new Salla export, regenerate
`bin/data/products.json` and re-run `npm run import`.

## Storefront chrome

Presentation is composed from small template parts (`template-parts/**`) plus
WooCommerce hook re-composition — the homepage is a fixed sequence, not
block-editor content. Colours, spacing and type come from design tokens
(`assets/css/base/tokens.css`); every stylesheet/script is declared in the
asset manifest (`inc/assets/manifest.php`). Run `npm run build` after editing
CSS or `assets/js/src/**`.

Interactive pieces are vanilla JS (jQuery only where WooCommerce requires it):

- **Header** — centred logo with a leading cluster (hamburger + mobile account
  icon) on the inline-start edge and the cart badge on the other; the labelled
  login pill and locale show on desktop only.
- **Category drawer** — the header hamburger opens a slide-in menu of product
  categories on every breakpoint (`template-parts/global/category-drawer.php`).
  Accessible: focus trap, `Esc`, and scrim-click close.
- **Add-to-cart popup** — an AJAX loop add fires a confirmation modal showing the
  product with continue / view-cart / checkout actions
  (`template-parts/global/added-to-cart-modal.php`,
  `assets/js/src/add-to-cart-popup.js`).
- **Mobile bottom nav + search overlay** — sticky bottom nav with a full-screen
  product search (`template-parts/header/mobile-bottom-nav.php`).

Homepage product sections render a title row + RTL carousel; a decorative
divider image is used only where a dark-theme asset exists (the Sony section).

## Assets

Built CSS/JS under `assets/**/dist/` is git-ignored — run `npm run build`
after cloning. `assets/js/global.js` is hand-authored (loaded as-is, not
bundled). Raw source exports (e.g. the Salla `.xlsx`) are kept out of the repo.
