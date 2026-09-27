# Health and Care research worksheet (imported)

Checked on 2026-09-26 and imported on 2026-09-27 as products **Health and Care** (product ids `121-129`),
alongside the one PAWORA demo row the category keeps (HC001 renamed to PAWORA Pet First Aid Kit).

Companion data file: `health-care.json`. Rebuild with:

```
php database/catalog/build-research-sql.php health-care
php database/catalog/import.php --category "Health and Care" --apply
php database/catalog/localize-images.php --apply   # then per new id
```

## How the data was collected

Every price, SKU, option list and image below was pulled from the brand's own public Shopify feed on 2026-09-26. Products the user listed that could not be verified (blocked sites, or names that do not exist as listed) were replaced with a same-type product from a brand feed that answers, and each replacement is named in the notes.

Every row was read from the brand's own public Shopify feed, so the price, SKU, variant list and
image come from the seller. Two pack shots were over the 4 MB local-image cap and were re-fetched
through the CDN resize parameter (`&width=900`) before being saved locally.

## Currency decision

The shop trades in LKR and checkout refuses foreign-currency rows, so each verified source price is
converted with one dated rate and rounded to the nearest 5 LKR. `price_type` stays `SOURCE`,
`source_price` keeps the original figure and `price_source`/`source_url` keep the evidence.

| From | Rate | Rate date |
| --- | --- | --- |
| USD | 329.923447 | 2026-09-26T00:02:32Z |
| CAD | 235.50872 | 2026-09-26T00:02:32Z |
| GBP | 439.634127 | 2026-09-26T00:02:32Z |

## Products

| # | Product | Brand | Size | SKU | Source price | Shop price | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | [Vet's Best Dental Care Finger Wipes](https://www.vetsbest.com/products/dental-care-finger-wipes) | Vet's Best | 1 pack | `31658000012` | 17.11 USD | Rs 5,645 | Chosen variant 'Default Title', USD 17.11, store SKU 31658000012. Kept as the user's Vet's Best Dental Care Finger Wipes. |
| 2 | [TropiClean Fresh Breath Clean Teeth Dental Oral Care Gel](https://www.tropiclean.com/products/tropiclean-fresh-breath-no-brushing-clean-teeth-dental-oral-care-gel-for-dogs-4z) | TropiClean | 4 oz | `FBCTGL4Z-WIP` | 19.99 USD | Rs 6,595 | Chosen variant 'Default Title', USD 19.99, store SKU FBCTGL4Z-WIP. Kept as the user's TropiClean Fresh Breath Dental Gel. |
| 3 | [VetIQ Multivitamin](https://www.vetiq.com/products/multivitamin) | VetIQ | 1 bottle | `100107` | 18.99 USD | Rs 6,265 | Chosen variant 'Default Title', USD 18.99, store SKU 100107. Kept as the user's VetIQ Multivitamin Supplement. The store image file name shows 90ct, but the variant publishes no size option. |
| 4 | [NaturVet Omega-Gold Skin & Coat Soft Chews](https://www.naturvet.com/products/omega-gold) | NaturVet | 90 ct or 180 ct | `79903692` | 21.99 USD | Rs 7,255 | Chosen variant '90 ct', USD 21.99, store SKU 79903692. Store options: 90 ct; 180 ct. Full store price range 21.99-37.99. Kept as the user's NaturVet Omega-Gold Soft Chews. |
| 5 | [NaturVet Hip & Joint Soft Chews](https://www.naturvet.com/products/hip-joint-soft-chews) | NaturVet | 120 ct | `79903580` | 23.99 USD | Rs 7,915 | Chosen variant '120 ct', USD 23.99, store SKU 79903580. Store options: 120 ct. Substitute for Nutri-Vet Hip & Joint Soft Chews; nutrivet.com intermittently fails TLS and its catalogue could not be verified. |
| 6 | [NaturVet Dental Targeted Care Soft Chews](https://www.naturvet.com/products/dental-targeted-care-soft-chews) | NaturVet | 60 ct | `760-00866` | 19.97 USD | Rs 6,590 | Chosen variant '60 ct', USD 19.97, store SKU 760-00866. Store options: 60 ct. Substitute for KONG Dental Stick; no KONG dental stick is published in their site or BigCommerce data. |
| 7 | [tuft + paw THE BALM Paw & Nose Moisturizing Balm](https://fablepets.com/products/the-balm-healing-pet-balm-natural-paw-nose-moisturizing-balm-for-dogs-cats) | tuft + paw | 1 jar | `PG-BALM` | 23.00 USD | Rs 7,590 | Chosen variant 'Default Title', USD 23.00, store SKU PG-BALM. Substitute for Burt's Bees Paw & Nose Lotion; burtsbees.com exposes no readable product feed from this host. |
| 8 | [Vet's Best Ear Relief Finger Wipes](https://www.vetsbest.com/products/ear-relief-finger-wipes) | Vet's Best | 1 pack | `31658000005` | 18.25 USD | Rs 6,020 | Chosen variant 'Default Title', USD 18.25, store SKU 31658000005. Substitute for Pet MD Chlorhexidine Wipes; petmd.com publishes no product sitemap and its /shop path returns 404 from this host. |
| 9 | [Vet's Best Enzymatic Dog Toothpaste](https://www.vetsbest.com/products/enzymatic-dog-toothpaste) | Vet's Best | 1 tube | `3165810096` | 9.53 USD | Rs 3,145 | Chosen variant 'Default Title', USD 9.53, store SKU 3165810096. Substitute for Virbac C.E.T. Enzymatic Toothpaste; virbac.com's sitemap exposes no priced product pages from this host. Same enzymatic toothpaste type. |

## Images

`products.image` holds the local file as `products/<sku>.<ext>` (the app prefixes `assets/images/`),
and `products.image_url` keeps the Shopify URL for attribution. `image_source_name`/`image_source_url`
name the brand store the pack shot came from.

## Verification

- 9 products, no duplicate SKUs, every description 20-50 words, every price non-zero.
- `php -l` clean, `catalog_fields.py` 0 failures, guest/user/admin crawls 0 problems.
- All 36 pack shots of this batch answer 200 from the Apache copy in `/opt/lampp/htdocs/pawora`.
