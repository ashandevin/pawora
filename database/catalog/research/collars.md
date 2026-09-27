# Collars research worksheet (imported)

Checked on 2026-09-26 and imported on 2026-09-27 as products **Collars** (product ids `103-111`),
alongside the one PAWORA demo row the category keeps (CL001 renamed to PAWORA Everyday Comfort Collar).

Companion data file: `collars.json`. Rebuild with:

```
php database/catalog/build-research-sql.php collars
php database/catalog/import.php --category "Collars" --apply
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
| 1 | [Ruffwear Crag Reflective Dog Collar](https://ruffwear.com/products/crag-reflective-dog-collar) | Ruffwear | 9 in to 26 in, many colourways | `25802-0351114` | 24.49 USD | Rs 8,080 | Chosen variant '11"-14" / Granite Gray (035) / New', USD 24.49, store SKU 25802-0351114. Store options: 11"-14"; Tropical Bloom (946); New; Northern Lights (977); Desert Spice (944); Alpine Dusk (982); Alpine Dawn (980); Rising Wave (978). Full store price range 18.37-34.99. Kept exactly as the user listed it (Crag Reflective Dog Collar). |
| 2 | [LupinePet Fi Collar Basic Solid Colors](https://www.lupinepet.com/products/lupinepet-fi-collar-basic-solids) | LupinePet | 3/4 in or 1 in wide, 11.5 in to 32 in | `15015` | 42.60 USD | Rs 14,055 | Chosen variant '3/4in wide / Aqua / 11.5in - 13.5in', USD 42.60, store SKU 15015. Store options: 3/4in wide; Blue; 11.5in - 13.5in; 1in wide; 13in - 16in; 15in - 20in; 19in - 26in; 25in - 32in. Full store price range 42.60-44.80. Kept as the user's LupinePet Basic Nylon Collar. |
| 3 | [GoTags Personalized Reflective Dog Collar](https://www.gotags.com/products/personalized-reflective-dog-collar-with-quick-release-buckle-aluminum-nameplate) | GoTags | X-Small 8-12 in to Large 18-26 in | `dog-refadj-beige-tag-aluminum-small` | 15.95 USD | Rs 5,260 | Chosen variant 'Beige / Small 11-16"', USD 15.95, store SKU dog-refadj-beige-tag-aluminum-small. Store options: Blue; X-Small 8-12"; Small 11-16"; Medium 14-20"; Large 18-26"; Light Blue; Red; Pink. Full store price range 13.95-15.95. Kept as the user's GoTags Personalized Pet Collar. |
| 4 | [Blue-9 Secure Pro Dog Collar](https://blue-9.com/products/clrm) | Blue-9 | Small to Large, several colours | `CLRM-BLK-L` | 19.95 USD | Rs 6,580 | Chosen variant 'Large / Black', USD 19.95, store SKU CLRM-BLK-L. Store options: Small; Black; Blue; Purple; Red; Sky Blue; Hot Pink; Hunter Green. User listed 'Blue-9 Balance Dog Collar'; the feed shows Balance is a harness line, and this is Blue-9's collar. |
| 5 | [LupinePet Fi Collar Reflective Designs](https://www.lupinepet.com/products/lupinepet-fi-collar-reflective-designs) | LupinePet | 3/4 in or 1 in wide, 11.5 in to 32 in | `48015` | 45.40 USD | Rs 14,980 | Chosen variant '3/4in wide / Blue Diamond / 11.5in - 13.5in', USD 45.40, store SKU 48015. Store options: 3/4in wide; Pink Diamond; 11.5in - 13.5in; Orange Diamond; Blue Diamond; Blue Paws; Green Diamond; Pink Paws. Full store price range 45.40-47.90. Substitute for KONG Comfort Reflective Collar; no such product is published in the KONG site or its BigCommerce data. |
| 6 | [Ruffwear Ridgeline Collar](https://ruffwear.com/products/ridgeline-collar) | Ruffwear | 11 in to 26 in | `2593-0011114` | 49.99 USD | Rs 16,495 | Chosen variant '11"-14" / Obsidian Black (001) / New', USD 49.99, store SKU 2593-0011114. Store options: 11"-14"; Obsidian Black (001); New; 14"-20"; 20"-26"; Used-1; Used-2. Full store price range 37.49-49.99. Substitute for Frisco Nylon Dog Collar; frisco.com answers 403 from this host. |
| 7 | [Ruffwear Front Range Flex Collar](https://ruffwear.com/products/front-range-flex-collar) | Ruffwear | 9 in to 26 in | `2525-0421114` | 29.99 USD | Rs 9,895 | Chosen variant '11"-14" / Basalt Gray (042) / New', USD 29.99, store SKU 2525-0421114. Store options: 9"-11"; Polar Blue (482); New; Rose Violet (670); Deep Teal (444); Basalt Gray (042); 11"-14"; 14"-20". Full store price range 22.49-29.99. Substitute for PetSafe Adjustable Nylon Collar; petsafe.com runs Shopify with its public feed and product sitemap both disabled. |
| 8 | [GoTags Personalized Dog Collar with Quick Release Buckle](https://www.gotags.com/products/personalized-dog-collar-with-aluminum-nameplate) | GoTags | X-Small 8-12 in to Large 18-26 in | `dog-adj-beige-tag-aluminum-small` | 13.95 USD | Rs 4,600 | Chosen variant 'Beige / Small 11-16"', USD 13.95, store SKU dog-adj-beige-tag-aluminum-small. Store options: Blue; X-Small 8-12"; Small 11-16"; Medium 14-20"; Large 18-26"; Light Blue; Lavender; Red. Substitute for Zee.Dog NeoPro Dog Collar; zee.dog is a single-page site with no product feed or sitemap from this host. |
| 9 | [Ruffwear Top Rope Metal Buckle Dog Collar](https://ruffwear.com/products/top-rope-dog-collar) | Ruffwear | 11 in to 26 in | `25504-0421114` | 39.99 USD | Rs 13,195 | Chosen variant '11"-14" / Basalt Gray (042) / New', USD 39.99, store SKU 25504-0421114. Store options: 11"-14"; Basalt Gray (042); New; River Rock Green (355); Blue Pool (410); 14"-20"; 20"-26"; Used-1. Full store price range 29.99-39.99. Substitute for ROGZ Reflecto Dog Collar; the ROGZ feed shows Reflecto is a dog harness, its collar line is ReflectoCat, and its store returns ZAR with no prices. |

## Images

`products.image` holds the local file as `products/<sku>.<ext>` (the app prefixes `assets/images/`),
and `products.image_url` keeps the Shopify URL for attribution. `image_source_name`/`image_source_url`
name the brand store the pack shot came from.

## Verification

- 9 products, no duplicate SKUs, every description 20-50 words, every price non-zero.
- `php -l` clean, `catalog_fields.py` 0 failures, guest/user/admin crawls 0 problems.
- All 36 pack shots of this batch answer 200 from the Apache copy in `/opt/lampp/htdocs/pawora`.
