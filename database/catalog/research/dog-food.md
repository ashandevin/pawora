# Dog Food research worksheet (verification pass 2, final)

Checked on 2026-09-26. **Nothing here has been inserted into the database yet** - this is the
worksheet `database/catalog/build-research-sql.php` turns into `products.sql`.

Companion data file: `dog-food.json` (the machine-readable version of everything below).
Draft SQL: `php database/catalog/build-research-sql.php dog-food`.
Dry run: `php database/catalog/import.php --category "Dog Food"`.

Verification levels:

- `FETCHED` - the page was downloaded and the value was read from its JSON-LD or `product:price:*` meta tags.
- `SNIPPET` - seen in search results only; **not** used as a source price anywhere.

## Decisions taken in this pass

1. **Currency.** The shop trades in LKR and checkout refuses foreign-currency rows, so verified
   foreign prices are converted with one dated rate each:

   | From | Rate | Provider | Rate date |
   | --- | --- | --- | --- |
   | USD | 329.923447 | https://open.er-api.com/v6/latest/USD | 2026-09-26T00:02:32Z |
   | CAD | 235.50872 | https://open.er-api.com/v6/latest/CAD | 2026-09-26T00:02:32Z |
   | GBP | 439.634127 | https://open.er-api.com/v6/latest/GBP | 2026-09-26T00:02:32Z |

   Converted prices are rounded to the nearest 5 LKR so they read like shop prices.
2. **Variant changes** against the original product list, each forced by the sources that actually answer:
   - Pedigree Puppy uses the US `Chicken, Rice & Vegetable` recipe, not the India `Chicken & Milk` variant.
   - Hill's is `Adult Chicken & Barley Recipe` (that is the recipe the fetched page sells).
   - IAMS is the standard Adult Minichunks recipe, not Large Breed (different SKU 019014044191).
   - Pedigree Adult and Dog Chow are listed in the 44 lb bag, the size that had a fetchable page.
3. **Stock is DEMO for all ten.** The sources say in stock / out of stock but not a quantity, so
   quantities are demo figures and `stock_type = DEMO`. Orijen is the one verified out of stock.
4. **Ratings stay NULL** for all ten. No source with a meaningful review count was found, and an
   invented rating would be worse than none.
5. **Two rows are DEMO priced** (Pedigree Puppy, IAMS): no retailer page could be downloaded for a
   price, so `price_type = DEMO`, `source_price` is NULL and the sheet says so in `notes`.

## The ten products

| # | Product | Size | Source price | LKR price | Price type |
| --- | --- | --- | --- | --- | --- |
| 1 | Royal Canin Medium Adult | 4 lb | USD 26.49 (official) | 8,740 | SOURCE |
| 2 | Royal Canin Maxi Puppy | 3 kg | GBP 22.49 (official UK) | 9,885 | SOURCE |
| 3 | Pedigree Adult, Roasted Chicken Rice & Vegetable | 44 lb | USD 35.99 (Grange Coop) | 11,875 | SOURCE |
| 4 | Purina Dog Chow Complete Adult, real chicken | 44 lb | USD 32.99 (Grange Coop) | 10,885 | SOURCE |
| 5 | Hill's Science Diet Adult Chicken & Barley | 15 lb | USD 52.99 (Grange Coop) | 17,485 | SOURCE |
| 6 | Eukanuba Adult Medium Breed | 30 lb | CAD 82.44 (G&E Pharmacy) | 19,415 | SOURCE |
| 7 | Purina ONE Chicken & Rice | 16.5 lb | USD 30.28 (1800PetMeds) | 9,990 | SOURCE |
| 8 | Pedigree Puppy, Chicken Rice & Vegetable | 30 lb | - | 8,950 | DEMO |
| 9 | IAMS Proactive Health Adult Minichunks | 30 lb | - | 15,250 | DEMO |
| 10 | Orijen Original | 4.5 lb | USD 35.99 (1800PetMeds) | 11,875 | SOURCE (out of stock) |

## 1. Royal Canin Medium Adult (US)

- Source page: https://www.royalcanin.com/us/dogs/products/retail-products/medium-adult-3004
- Image (manufacturer): https://marspetcareaprimocdn.petcare.global/2ac9bc44-82ec-4db1-babf-b249000707aa/2ac9bc44-82ec-4db1-babf-b249000707aa_DownloadAsJpg.jpg
- Price (FETCHED, official JSON-LD): USD 26.49 (4 lb, SKU 1-030111174550). The same page also lists
  17 lb at USD 61.99 (1-030111517470), 30 lb at USD 99.99 (1-030111517432) and 40 lb at USD 109.99 (1-030111517449).
- Image check: 2400x2400 JPEG, 220 KB. Passes.

## 2. Royal Canin Maxi Puppy (UK)

- Source page: https://www.royalcanin.com/uk/dogs/products/retail-products/puppy---maxi-3006
- Image (manufacturer): https://marspetcareaprimocdn.petcare.global/a9373f90-1418-488b-950e-b2490009a7fd/a9373f90-1418-488b-950e-b2490009a7fd_DownloadAsJpg.jpg
- Price (FETCHED, official UK JSON-LD): GBP 22.49 (3 kg, SKU 1-3182551065213); 15 kg is GBP 72.99 (1-3182550402163).
- Note: the US page for this recipe did not answer inside the fetch timeout, so the UK bag is used
  and the conversion is recorded rather than hidden.
- Image check: 2480x3508 JPEG, 449 KB. Passes.

## 3. Pedigree Adult, Roasted Chicken Rice & Vegetable (44 lb)

- Official page: https://www.pedigree.com/products/dry/pedigree-adult-dry-dog-food-chicken-vegetable
  (404 to direct downloads, so it is only the fact source, not the price source)
- Price (FETCHED): USD 35.99 for 44 lb, https://www.grangecoop.com/pedigree-adult-roasted-chicken-rice-vegetable-dry-dog-food-44-lb/
- Retailer identifiers: retailer SKU 189800, UPC 023100143538
- Image (retailer): https://cdn11.bigcommerce.com/s-qz13ep5fb4/products/189800/images/275600/167524__52752.1761654694.386.513.jpg?c=1
- Image check: 386x386 JPEG, 40 KB. Passes, but it is small - it will look soft next to the Royal Canin shots.
- Other sizes seen (SNIPPET only): 18 lb from USD 21.99 to USD 25.29.

## 4. Purina Dog Chow Complete Adult, real chicken (44 lb)

- Official page: https://www.purina.com/dogs/shop/dog-chow-complete-adult-chicken-dry-dog-food
  (403 to direct downloads; sizes 4.4 / 18.5 / 44 / 48 lb confirmed by search extraction)
- Price (FETCHED): USD 32.99 for 44 lb, https://www.grangecoop.com/purina-dog-chow-complete-adult-chicken-flavor-dry-dog-food-44-lb/
- Retailer identifiers: retailer SKU 106970, UPC 017800806725
- Image (retailer): https://cdn11.bigcommerce.com/s-qz13ep5fb4/products/187202/images/271522/106970__70426.1741113716.386.513.jpg?c=1
- Image check: 386x386 JPEG, 36 KB. Passes (same small-size caveat as #3).

## 5. Hill's Science Diet Adult Chicken & Barley (15 lb)

- Official page: https://www.hillspet.com/dog-food/science-diet-adult-original-dry (403 to direct downloads)
- Official sizes 2 / 5 / 15 / 35 / 45 lb; 3675 kcal/kg, 365 kcal/cup
- Price (FETCHED): USD 52.99 for 15 lb, https://www.grangecoop.com/hills-science-diet-adult-chicken-barley-recipe-dog-food-15-lb/
- Identifier: MPN/UPC 052742020419
- Image (retailer): https://cdn11.bigcommerce.com/s-qz13ep5fb4/products/98178/images/209286/8f6f20c0-9c61-47ee-bad8-9b326acec47b__14383.1674880038.386.513.jpg?c=1
- Image check: 386x386 JPEG, 35 KB. Passes.

## 6. Eukanuba Adult Medium Breed (30 lb)

- Official page: https://www.eukanuba.com/products/dry/eukanuba-adult-dry-dog-food-medium-dogs (FETCHED)
- Manufacturer identifier: 030111604521
- Price (FETCHED): CAD 82.44 for 30 lb, https://gepharmacy.com/products/eukanuba-adult-medium-breed-30lb (InStock)
- Cross-check: USD 74.99 for the same 30 lb bag at 1800PetMeds, but flagged OutOfStock there.
- Image: the official pack shot is a **23 MB** 2400x2400 PNG, over the localizer's 4 MB cap, so the
  retailer's version is used instead and attributed to G&E Pharmacy:
  https://gepharmacy.com/cdn/shop/products/eukad_1024x1024.png (859x1024 PNG, 759 KB). Passes.

## 7. Purina ONE Chicken & Rice (16.5 lb)

- Official page: https://www.purina.com/dogs/shop/purina-one-chicken-rice-dry-dog-food (403 to direct downloads)
- Price (FETCHED): USD 30.28 for 16.5 lb, https://www.1800petmeds.com/dog/food/product/purina-one-natural-smartblend-chicken-rice-formula-dry/prod63750.html (InStock)
- Cross-check (FETCHED): USD 35.99 for the same 16.5 lb bag at Price Chopper.
- Retailer identifier: 63750. The retailer calls the recipe "SmartBlend", which is Purina's older
  line name for the same chicken and rice recipe.
- Image: https://imagedelivery.net/OWeDZSTbTyzlX4Fsq0SDAw/63750/w=1000 - 1000x1000 JPEG, 86 KB. Passes.

## 8. Pedigree Puppy, Chicken Rice & Vegetable (30 lb)

- Source page (FETCHED): https://www.pedigree.com/products/dry/puppy-dry-dog-food-chicken-rice-vegetable
- Page data name: "Puppy Dry Dog Food, Chicken, Rice & Vegetable"; brand Pedigree; sizes 3.5 / 12 / 14 / 30 lb;
  copy mentions 36 vitamins, minerals and amino acids.
- Identifier: SKU 023100103655
- Image (manufacturer): https://www.pedigree.com/sites/g/files/fnmzdf3076/files/migrate-product-files/images/pvyhlzdwxgy7fjb6rmwu.png
  - 1000x1000 PNG, 144 KB. Passes.
- **Price: DEMO.** No retailer page for this recipe could be downloaded. A search listing showed
  USD 26.49 for the 30 lb bag at Target, which would convert to about 8,740 LKR, so the demo price
  of 8,950 is deliberately not that number.

## 9. IAMS Proactive Health Adult Minichunks, chicken & whole grain (30 lb)

- Source page (FETCHED): https://www.iams.com/products/dry/iams-minichunks-chicken-whole-grains
- Identifier: SKU 019014610860; sizes 3.3 / 5 / 7 / 11 / 15 / 30 / 38.5 / 40 / 44 / 50 lb
- Image (manufacturer): https://www.iams.com/sites/g/files/fnmzdf9391/files/migrate-product-files/images/lyjjorwjzhvdodou6lld.png
  - 1000x1000 PNG, 178 KB. Passes.
- Not the Large Breed recipe (SKU 019014044191), which is a different product.
- **Price: DEMO.** Search listings showed USD 46.99 at PetSmart and USD 46.98 at Petco for the
  30 lb bag (about 15,500 LKR); 1800PetMeds marks the product unavailable. None could be
  downloaded, so the demo price is 15,250.

## 10. Orijen Original (4.5 lb)

- Source page (FETCHED): https://www.orijenpetfoods.com/en-US/dogs/dogs-food... (page exposes no price and no og:image)
- Page data: identifier ds-ori-original-dog; US sizes 4.5 / 13 / 23.5 / 31 lb; 85% quality animal
  ingredients; 3860 kcal/kg; meets FEDIAF all life stages.
- Price (FETCHED): USD 35.99 for 4.5 lb, https://www.1800petmeds.com/dog/food/product/orijen-original-dry-food-dog/prod67172.html
- **OutOfStock at that retailer**, so stock is 0 and the product shows as out of stock.
- Image: https://imagedelivery.net/OWeDZSTbTyzlX4Fsq0SDAw/67172/w=1000 - 1000x1000 JPEG, 73 KB. Passes.
- SKU upper-cased to `DS-ORI-ORIGINAL-DOG` because the importer requires uppercase.

## Image check summary

All ten pack shots were downloaded and measured on 2026-09-26; every one is 200 px or larger on
both sides, is a real JPG/PNG, and is under the 4 MB localizer cap:

| Product | Format | Size | Bytes |
| --- | --- | --- | --- |
| Royal Canin Medium Adult | JPEG | 2400x2400 | 220 KB |
| Royal Canin Maxi Puppy | JPEG | 2480x3508 | 449 KB |
| Pedigree Adult 44 lb | JPEG | 386x386 | 40 KB |
| Purina Dog Chow 44 lb | JPEG | 386x386 | 36 KB |
| Hill's Science Diet 15 lb | JPEG | 386x386 | 35 KB |
| Eukanuba Adult 30 lb | PNG | 859x1024 | 759 KB |
| Purina ONE 16.5 lb | JPEG | 1000x1000 | 86 KB |
| Pedigree Puppy 30 lb | PNG | 1000x1000 | 144 KB |
| IAMS Minichunks 30 lb | PNG | 1000x1000 | 178 KB |
| Orijen Original 4.5 lb | JPEG | 1000x1000 | 73 KB |

Three are PNG, which is why `localize-images.php` now saves the extension that matches the real
content instead of always writing `.jpg`.

## Blocked source sites (403/404 to direct downloads)

- purina.com, hillspet.com - 403
- pedigree.com - the Adult paths 404, the Puppy path works
- chewy.com - 429
- thepetshoppe.com - 403
- orijenpetfoods.com - works, but exposes no price or image

Retailers that do answer downloads: grangecoop.com, 1800petmeds.com, gepharmacy.com,
shopmypricechopper.com, cub.com.

## Known trade-off to decide on

Because prices are LKR conversions of US/UK/Canadian shelf prices, **large bags look cheap next to
small bags**: a 44 lb Pedigree bag converts to 11,875 LKR while a 4 lb Royal Canin bag is 8,740 LKR.
The numbers are honest conversions, not pricing errors. If you would rather the shop look
internally consistent, the fix is to list the smallest size of every product (Pedigree 3.5 lb,
Dog Chow 4.4 lb, Eukanuba 16 lb, Pedigree Puppy 3.5 lb, IAMS 3.3 lb) instead - say which you prefer
before the import is applied.
