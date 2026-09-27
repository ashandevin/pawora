# Cat Food research worksheet

Checked on 2026-09-26. **Nothing here has been inserted into the database yet** - this is the
worksheet `database/catalog/build-research-sql.php` turns into `products.sql`.

Companion data file: `cat-food.json`.
Draft SQL: `php database/catalog/build-research-sql.php cat-food`.
Dry run: `php database/catalog/import.php --category "Cat Food"`.

## Sourcing decisions

1. **Two rows come from the manufacturer.** Royal Canin's own US product pages answer downloads and
   publish the bag sizes, price, SKU and pack image in their JSON-LD, so both Royal Canin rows are
   priced from royalcanin.com.
2. **Eight rows come from one retailer.** Grange Coop product pages were downloaded and carry the
   product name, bag size, price, currency, manufacturer number and pack shot, so the retailer page
   is recorded as the source of both the facts and the price. The manufacturers' own sites could
   not be used: purina.com, hillspet.com and bluebuffalo.com return 403 to direct downloads, and
   the guessed product paths on iams.com, acana.com and orijenpetfoods.com return 404.
3. **Currency.** Same dated rate as the Dog Food batch so the two categories stay consistent:
   USD 329.923447, open.er-api.com, 2026-09-26T00:02:32Z. Converted prices are rounded to the
   nearest 5 LKR.
4. **Stock is DEMO for all ten** and **ratings stay NULL**: availability was not captured from the
   pages, and no source with a meaningful review count was found.
5. **All ten prices are SOURCE priced**, unlike the Dog Food batch where two rows had no
   downloadable price.

## The ten products

| # | Product | Size | Source price | LKR price | Source |
| --- | --- | --- | --- | --- | --- |
| 1 | Royal Canin Indoor Adult | 7 lb | USD 36.99 | 12,205 | Royal Canin US |
| 2 | Royal Canin Kitten | 7 lb | USD 41.99 | 13,855 | Royal Canin US |
| 3 | Purina Pro Plan Adult Complete Essentials Salmon & Rice | 7 lb | USD 27.08 | 8,935 | Grange Coop |
| 4 | Purina Cat Chow Complete | 15 lb | USD 27.99 | 9,235 | Grange Coop |
| 5 | Hill's Science Diet Adult Chicken Recipe | 4 lb | USD 23.99 | 7,915 | Grange Coop |
| 6 | IAMS Proactive Health Healthy Adult with Chicken | 7 lb | USD 24.99 | 8,245 | Grange Coop |
| 7 | Blue Buffalo Sensitive Stomach Chicken and Brown Rice | 15 lb | USD 54.99 | 18,140 | Grange Coop |
| 8 | Orijen Kitten Formula | 4 lb | USD 31.99 | 10,555 | Grange Coop |
| 9 | Acana Indoor Entree | 10 lb | USD 41.99 | 13,855 | Grange Coop |
| 10 | Exclusive Signature All Life Stages Chicken and Brown Rice | 15 lb | USD 33.99 | 11,215 | Grange Coop |

Eight brands across ten products, the same spread as the Dog Food batch.

## Detail per product

1. **Royal Canin Indoor Adult, 7 lb** - https://www.royalcanin.com/us/cats/products/retail-products/indoor-adult-2529
   - SKU 1-030111628084, USD 36.99, InStock. Page copy: "formulated for indoor cats 1-7 years old".
   - The same JSON-LD lists 3 lb at USD 23.99 (1-030111430403) and 15 lb at USD 67.99 (1-030111628169).
   - Image: manufacturer CDN, 1320x1320 PNG, 643 KB.
2. **Royal Canin Kitten, 7 lb** - https://www.royalcanin.com/us/cats/products/retail-products/kitten-2522
   - SKU 1-030111542571, USD 41.99, InStock. Page copy: formulated for the second stage of kittenhood,
     between 4 months and 1 year.
   - Also lists 3 lb at USD 29.99 (1-030111451033) and 14 lb at USD 73.99 (1-030111451149).
   - Image: manufacturer CDN, 2400x2400 JPEG, 235 KB.
3. **Purina Pro Plan Adult Complete Essentials Salmon & Rice, 7 lb** - Grange Coop, USD 27.08,
   retailer SKU 99099, mpn 038100131225, 386x386 JPEG.
4. **Purina Cat Chow Complete, 15 lb** - Grange Coop, USD 27.99, retailer SKU 98615,
   mpn 017800184953, 386x386 JPEG. Page copy: made with real chicken, high-quality protein for
   strong muscles, healthy carbohydrates for vital energy.
5. **Hill's Science Diet Adult Chicken Recipe, 4 lb** - Grange Coop, USD 23.99, retailer SKU 98448,
   mpn 052742679709, 386x386 JPEG. Page copy: supports heart, kidney, skin and coat health.
6. **IAMS Proactive Health Healthy Adult with Chicken, 7 lb** - Grange Coop, USD 24.99, retailer
   SKU 89496, mpn 019014712571, 386x386 JPEG. The matching kitten recipe (mpn 019014712502) is also
   USD 24.99 and was left out to keep one row per recipe.
7. **Blue Buffalo Sensitive Stomach Chicken and Brown Rice, 15 lb** - Grange Coop, USD 54.99, retailer
   SKU 39069, mpn 859610006014, 386x386 JPEG. Page copy: formulated for digestive health, starts with
   deboned chicken, packed with whole grains.
8. **Orijen Kitten Formula, 4 lb** - Grange Coop, USD 31.99, retailer SKU 78243, mpn 064992716646,
   386x386 JPEG. Page copy: 15 fresh or raw ingredients, including WholePrey muscle meat, organs
   and bone. The retailer also sells Orijen Cat and Kitten (mpn 064992201401) at USD 31.99, but that
   page states no bag size, so the size-stated Kitten Formula was used.
9. **Acana Indoor Entree, 10 lb** - Grange Coop, USD 41.99, retailer SKU 16391, mpn 064992613105,
   386x386 **PNG** - the only PNG pack shot in this batch.
10. **Exclusive Signature All Life Stages Chicken and Brown Rice, 15 lb** - Grange Coop, USD 33.99,
    retailer SKU 84468, mpn 804273062778, 386x386 JPEG. Exclusive Signature is a Diamond Pet Foods
    line; diamondpetfoods.com failed TLS verification from this host.

## Candidates found but not used

| Product | Why not |
| --- | --- |
| Royal Canin Urinary Care (official, USD 28.99/42.99/79.99) | Valid, but two Royal Canin rows is already the brand cap. |
| Purina Kit and Kaboodle Original, 13 lb (USD 19.99) | Very low price point for the range; kept Cat Chow as the budget Purina line. |
| IAMS Proactive Health Healthy Kitten, 7 lb (USD 24.99) | Same price as the adult recipe; the adult row was kept. |
| Blue Buffalo Healthy Growth Kitten, 7 lb (USD 37.99) | Second Blue Buffalo row. |
| Blue Buffalo Wilderness kitten, 5 lb | Second Blue Buffalo row. |
| Orijen Guardian 8, 10 lb | Would be a second Orijen row. |
| NutriSource Weight Management Senior, 4 lb (USD 24.38) | Extra candidate if a senior diet is wanted. |
| Taste of the Wild Prey Turkey (USD 25.99) | Extra candidate. |
| Acana / Friskies / Fancy Feast / Tiki Cat wet trays | Wet trays in singles would not match the bagged dry range. |

## Image check summary

All ten pack shots were downloaded and measured on 2026-09-26. Every one is a real JPG or PNG, at
least 200 px on both sides and under the 4 MB localizer cap. The eight retailer shots are 386x386,
which will look softer than the two Royal Canin images (1320x1320 and 2400x2400).

## Sources that answer downloads

- royalcanin.com (US product pages, JSON-LD with per-bag price and SKU) - slow, 30 to 90 s per page
- grangecoop.com product pages - `og:image`, `product:price:amount`, `product:price:currency` and
  the manufacturer number in the page JSON. Enumerable through
  `https://www.grangecoop.com/xmlsitemap.php?type=products&page=1` and `page=2` (about 13,000 URLs).
- gepharmacy.com collections and product pages (Shopify, CAD prices)
- 1800petmeds.com product pages (image CDN `imagedelivery.net`, `?w=1000` gives small images)

## Sources that block downloads

- purina.com, hillspet.com, bluebuffalo.com - 403
- chewy.com - 429
- thepetshoppe.com - 403
- acana.com, orijenpetfoods.com - no sitemap and guessed product paths 404
- 1800petmeds.com category pages - 404 (only product pages resolve)
