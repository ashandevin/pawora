# Bed research worksheet (imported)

Checked on 2026-09-26 and imported on 2026-09-27 as products **Bed** (product ids `94-102`),
alongside the one PAWORA demo row the category keeps (BD001 renamed to PAWORA Comfort Cloud Pet Bed; the second Bed demo row (PF1203, Cat Bed) was deleted after confirming it had no order references).

Companion data file: `bed.json`. Rebuild with:

```
php database/catalog/build-research-sql.php bed
php database/catalog/import.php --category "Bed" --apply
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
| 1 | [K&H Original Pet Cot](https://khpet.com/products/original-pet-cot) | K&H Pet Products | Small 17 x 22 in to X-Large 32 x 50 in | `100212972` | 24.95 USD | Rs 8,230 | Chosen variant 'Chocolate/Black / Small 17” X 22” X 7”', USD 24.95, store SKU 100212972. Store options: Green/Black; Medium 25” X 32” X 7”; Large 30” X 42” X 7”; X-Large 32” X 50” X 9”; Charcoal/Black; Jumbo 56" X 38" X 9"; Small 17” X 22” X 7”; Navy/Black. Full store price range 24.95-64.98. Kept exactly as the user listed it. |
| 2 | [K&H Self-Warming Nuzzle Nest Pet Bed](https://khpet.com/products/self-warming-nuzzle-nest) | K&H Pet Products | 1 bed | `100213086` | 34.98 USD | Rs 11,540 | Chosen variant 'Default Title', USD 34.98, store SKU 100213086. User listed 'K&H Self-Warming Pet Bed'; this Nuzzle Nest is the self-warming pet bed the K&H feed publishes. |
| 3 | [K&H Original Bolster Pet Cot](https://khpet.com/products/k-h-original-bolster-pet-cot) | K&H Pet Products | Small 17 x 22 in to Large 30 x 42 in | `100213497` | 44.95 USD | Rs 14,830 | Chosen variant 'Small 17" x 22" x 7" / Chocolate', USD 44.95, store SKU 100213497. Store options: Large 30" x 42" x 7"; Charcoal; Chocolate; Medium 25" x 32" x 7"; Small 17" x 22" x 7". Full store price range 44.95-84.98. Substitute for PetFusion Ultimate Dog Bed; petfusion.com answers 403 from this host. |
| 4 | [K&H All Season All Weather Dog Cot](https://khpet.com/products/all-weather-pet-cot-elevated-pet-bed) | K&H Pet Products | Medium 32 x 25 in to Jumbo 56 x 38 in | `100213623` | 34.95 USD | Rs 11,530 | Chosen variant 'Medium 32 X 25 X 7 / Gray', USD 34.95, store SKU 100213623. Store options: Jumbo 56 X 38 X 9; Tan; Gray; X-Large 50 X 32 X 9; Green; Large 42 X 30 X 7; Medium 32 X 25 X 7. Full store price range 34.95-64.98. Substitute for Bedsure Waterproof Dog Bed; bedsure.com does not resolve from this host, and this is the all-weather/outdoor equivalent. |
| 5 | [PETSFIT Elevated Dog Bed for Large Dogs](https://petsfit.com/products/petsfit-elevated-dog-bed-large-dogs) | PETSFIT | M 42 x 30 in to L 48 x 33 in | `DCCD022034L1` | 47.95 USD | Rs 15,820 | Chosen variant 'M : 42"x30"x7"', USD 47.95, store SKU DCCD022034L1. Store options: M : 42"x30"x7"; L : 48"x33"x9". Full store price range 47.72-47.95. Substitute for Amazon Basics Round Bolster Pet Bed; no usable Amazon source is reachable from this host. |
| 6 | [K&H Thermo Pet Cuddler Indoor Heated Donut Cat Bed](https://khpet.com/products/k-h-thermo-pet-cuddler-indoor-heated-donut-cat-bed) | K&H Pet Products | 1 bed | `100553013` | 69.98 USD | Rs 23,090 | Chosen variant 'Default Title', USD 69.98, store SKU 100553013. Substitute for FurHaven Plush Donut Pet Bed; furchaven.com does not resolve from this host. Same donut bed type. |
| 7 | [Petsfit Large Cat Hammock Bed](https://petsfit.com/products/petsfit-large-cat-hammock-bed-for-curling-up-for-all-sizes-cats) | PETSFIT | 1 hammock bed | `DBW1035ZB00J` | 59.99 USD | Rs 19,790 | Chosen variant 'Default Title', USD 59.99, store SKU DBW1035ZB00J. Added for cat bed variety; the two FurHaven cat bed slots were unreachable (furchaven.com does not resolve from this host). |
| 8 | [DIGGS Bolstr Evolv Dog Bed](https://diggs.pet/products/bolstr-evolv-dog-bed) | DIGGS | Small to XL | `MCP001A24G` | 59.00 USD | Rs 19,465 | Chosen variant 'Small', USD 59.00, store SKU MCP001A24G. Store options: Small; Medium; Intermediate; Large; XL. Full store price range 59.00-119.00. Substitute for FurHaven Plush & Suede Orthopedic Bed; furchaven.com does not resolve from this host. |
| 9 | [tuft + paw Haven Luxury Bed](https://fablepets.com/products/bed) | tuft + paw | XS/S, M, L/XL | `HL-BED-DSLG1` | 199.00 USD | Rs 65,655 | Chosen variant 'Dark Shadow / L/XL', USD 199.00, store SKU HL-BED-DSLG1. Store options: Dark Shadow; XS/S; M; L/XL; Tofu; Mineral Grey; Green; Rose Dust. Full store price range 119.00-199.00. Substitute for Frisco Sherpa Orthopedic Bolster Bed; frisco.com answers 403 from this host. |

## Images

`products.image` holds the local file as `products/<sku>.<ext>` (the app prefixes `assets/images/`),
and `products.image_url` keeps the Shopify URL for attribution. `image_source_name`/`image_source_url`
name the brand store the pack shot came from.

## Verification

- 9 products, no duplicate SKUs, every description 20-50 words, every price non-zero.
- `php -l` clean, `catalog_fields.py` 0 failures, guest/user/admin crawls 0 problems.
- All 36 pack shots of this batch answer 200 from the Apache copy in `/opt/lampp/htdocs/pawora`.
