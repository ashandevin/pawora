# Accessories research worksheet

Checked on 2026-09-26. **Nothing here has been inserted into the database yet** - this is the
worksheet `database/catalog/build-research-sql.php` turns into `products.sql`.

Companion data file: `accessories.json`.
Draft SQL: `php database/catalog/build-research-sql.php accessories`.
Dry run: `php database/catalog/import.php --category "Accessories"`.

## Sourcing decisions

1. **This is the first batch where most manufacturers block direct downloads.** petco.com returns
   403, chewy.com 429, petsmart.com times out, and moderndog.com, outwardhound.com, catit.com,
   hepper.com, petcube.com and neaterfeeder.com have no readable product feed. 1800petmeds.com
   does answer, but its accessory selection is nine Spunky Pup lick mats, so it was not used.
2. **Five brand stores that publish a public Shopify feed were used instead**: waggo.com,
   fablepets.com, petlibro.com, petkit.com and roverlund.com. A Shopify store exposes
   `/products.json` and `/products/<handle>.json`, which return the exact variant price, the store's
   own SKU and the image list - better evidence than scraping a rendered page.
3. **Every source page was then downloaded and returned HTTP 200**, and every pack shot was
   downloaded and measured (type, pixel size, byte size) before being listed here.
4. **Each row is a specific published variant, not a price range**, with the store's own SKU.
5. **Ambiguous photos were avoided.** WAGGO's Gloss Ceramic bowl has one photo per colourway with no
   per-variant mapping, so WAGGO's single-variant Splash bowl was used instead, and the placemat row
   uses the black photo that matches its "Small / Black" variant.
6. **Currency.** Same dated rate as the Dog Food and Cat Food batches: USD 329.923447,
   open.er-api.com, 2026-09-26T00:02:32Z, rounded to the nearest 5 LKR.
7. **Stock is DEMO for all ten** and **ratings stay NULL**: no store feed reports stock counts or
   meaningful review counts.
8. **All ten prices are SOURCE priced** from a store page.

## The ten products

| # | Product | Variant | Source price | LKR price | Brand | Store |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Splash Ceramic Dog Bowl | Black Splash | USD 14.00 | 4,620 | Waggo | waggo.com |
| 2 | Habit Circle Dog Placemat | Set of 2, small, black | USD 16.95 | 5,590 | Waggo | waggo.com |
| 3 | Halo Ceramic Cat Bowl | One bowl | USD 30.00 | 9,900 | Catenary | fablepets.com |
| 4 | Porto Cat Carrier | Charcoal | USD 109.00 | 35,960 | tuft + paw | fablepets.com |
| 5 | Stainless Steel Water Fountain | With 4 filters | USD 39.99 | 13,195 | PETLIBRO | petlibro.com |
| 6 | Scout Smart Pet Camera | White, single | USD 79.99 | 26,390 | PETLIBRO | petlibro.com |
| 7 | Breezy 2 Cat Carrier | Gradient pink and blue | USD 74.99 | 24,740 | PETKIT | petkit.com |
| 8 | Raised Feeder Stand | One stand | USD 19.99 | 6,595 | PETKIT | petkit.com |
| 9 | Out-of-Office Collapsible Dog Bowl | Black | USD 19.00 | 6,270 | Roverlund | roverlund.com |
| 10 | Leader-of-the-Pack Poop Bag Holder | Camo and orange | USD 19.00 | 6,270 | Roverlund | roverlund.com |

Type spread: three feeding items (two bowls plus a placemat), one feeder stand, one fountain, one
camera, two carriers, one travel bowl and one walk-time essential. Six brands, no more than two rows
each.

## Detail per product

1. **Waggo Splash Ceramic Dog Bowl** - https://www.waggo.com/products/splash-ceramic-dog-bowl
   - SKU W012031-01, USD 14.00, single variant "Black Splash".
   - Image 2000x2000 JPEG, 61 KB. Page copy: "Good dogs deserve treats! Our modern Splash Dog bowls
     are a fun pop in any kitchen or living room! Hand-splashed with black glaze".
2. **Waggo Habit Circle Dog Placemat, set of 2** - https://www.waggo.com/products/habit-dog-placemat
   - SKU W063621-01, USD 16.95, variant "Small / Black". Also published in white, rose, forest green
     and light grey at the same price; Medium sets run USD 7.00 to 19.95.
   - Image 1170x1170 JPEG, 39 KB (the black set).
3. **Catenary Halo Ceramic Cat Bowl** - https://fablepets.com/products/halo
   - SKU HALO1, USD 30.00, single variant. Made by Catenary and sold through Fable.
   - Published image is a 1915x2394 PNG at 7.8 MB, over the 4 MB cap, so the URL asks the store's
     image CDN for `width=900`, which serves the same photo at 900x1125 and 1.6 MB.
4. **tuft + paw Porto Cat Carrier** - https://fablepets.com/products/porto-cat-carrier
   - SKU JI-POR-O-CH, USD 109.00, variant "Charcoal" (also Sand, same price).
   - Airline-approved soft carrier in water-resistant canvas with mesh windows and a removable
     faux-shearling bed. Image 1200x800 JPEG, 57 KB. Highest priced row in this batch.
5. **PETLIBRO Stainless Steel Water Fountain** - https://petlibro.com/products/stainless-steel-fountain-for-pet
   - SKU PL-WF006-03M, USD 39.99, variant "Fountain & 4 Filters". Filter bundles also published at
     USD 53.99 (8 filters) and USD 63.99 (12 filters).
   - 304 stainless steel, BPA-free, pump below 30dB. Image 1800x1800 PNG, 566 KB.
6. **PETLIBRO Scout Smart Pet Camera** - https://petlibro.com/products/scout-smart-camera-for-pet
   - SKU PL-PC001-01W, USD 79.99, variant "White / 1 Pack". Two-pack USD 159.98; black also available.
   - Image 1200x1280 PNG, 213 KB.
7. **PETKIT Breezy 2 Cat Carrier** - https://www.petkit.com/products/breezy-dome-2
   - SKU BP2-Pink, USD 74.99, single variant. Backpack carrier with a built-in fan, adjustable wind
     speed and light, self-locking zipper, padded straps.
   - The same carrier is also listed in Cream White, but that listing carries the store's placeholder
     SKU "BP2-WT-test", so the gradient listing with the clean SKU was used. Image 1500x1500 PNG, 458 KB.
8. **PETKIT Raised Feeder Stand** - https://www.petkit.com/products/petkit-raised-stand
   - SKU DP1, USD 19.99, single variant. BPA-free food-grade material, anti-slip pads, cable notch.
   - Image 1500x1500 PNG, 119 KB.
9. **Roverlund Out-of-Office Collapsible Dog Bowl** - https://roverlund.com/products/collapsible-dog-bowl
   - SKU 11122, USD 19.00, variant BLACK (blue 11184, camo 11118, desert camo 11126 same price).
   - Image 1200x1200 JPEG, 121 KB.
10. **Roverlund Leader-of-the-Pack Poop Bag Holder** - https://roverlund.com/products/leader-of-the-pack-poop-bag-holder
    - SKU 11113, USD 19.00, variant "CAMO / ORANGE" (five other pairings same price).
    - Image 1200x1200 JPEG, 99 KB.

## Candidates found but not used

| Product | Why not |
| --- | --- |
| Waggo Gloss Ceramic Dog Bowl, USD 10.00-16.00 | One photo for all four colourways, no per-variant mapping, so the photo could be the wrong colour. |
| Waggo Ripple / Uplift / Dipper / Edge / Farmhouse ceramic bowls | Would make a third WAGGO row of near-identical bowls. |
| PETLIBRO Granary Slow Feeder Bowl, USD 19.99 | Would be a third PETLIBRO row; also made for the Granary feeder. |
| PETKIT Fresh Element Solo Automatic Feeder, USD 69.99 | Feeder, not an accessory, and a third PETKIT product in the same store. |
| PETKIT Breezy 2 Cat Carrier in Cream White | Store SKU is the placeholder "BP2-WT-test". |
| Roverlund Out-of-Office Kibble Storage Bag, USD 29.00 | Third Roverlund row; the storage bag competes with the insulated lunchbox and kibble bag already seen. |
| Roverlund Out-of-Office Insulated Frozen Food Carry Case, USD 39.00 | Same reason. |
| Fable Catenary Elevated Halo Bowl, USD 45.00 | Second Catenary bowl. |
| Fable Porto Cat Carrier in Sand, USD 109.00 | Same carrier, other colourway. |
| Diggs Poop Bags, USD 39.00-119.00 | Diggs' own crates start at USD 299; too expensive for this shop. |
| 1800petmeds Spunky Pup lick mats and treat trays | The only accessories that store lists; brand is a single supplier and prices are low. |

## Image check summary

All ten pack shots were downloaded and measured on 2026-09-26. Every one is a real JPG or PNG, at
least 200 px on both sides and under the 4 MB localizer cap. Sizes range from 37 KB to 1.6 MB, and
the smallest (the Waggo Splash bowl photo at 2000x2000 and 37 KB) is still well above the
resolution floor.

## Sources that answer downloads

- waggo.com, fablepets.com, petlibro.com, petkit.com, roverlund.com - Shopify stores:
  `/products.json` and `/products/<handle>.json` for catalogue and variant data, product pages for
  the human-readable copy
- gepharmacy.com, 1800petmeds.com, grangecoop.com, shopmypricechopper.com, royalcanin.com - used
  in the earlier batches

## Sources that block downloads

- petco.com - 403
- chewy.com - 429
- petsmart.com - request never completes
- moderndog.com, outwardhound.com, catit.com, hepper.com, petcube.com, kongcompany.com, pioneerpet.com,
  wagandwhistle.co.uk - 404 on every product feed path tried
- beco-pets.com, dulcutt.co.uk, lordslabradors.co.uk - DNS does not resolve from this host
- neaterfeeder.com, ozkies.com - TLS failure
- buntypetproducts.com - 402
