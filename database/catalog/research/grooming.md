# Grooming research worksheet (imported)

Checked on 2026-09-26 and imported on 2026-09-27 as products **Grooming** (product ids `112-120`),
alongside the one PAWORA demo row the category keeps (GR001 renamed to PAWORA Gentle Grooming Brush).

Companion data file: `grooming.json`. Rebuild with:

```
php database/catalog/build-research-sql.php grooming
php database/catalog/import.php --category "Grooming" --apply
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
| 1 | [TropiClean Coconut Hypoallergenic Puppy & Kitten Shampoo](https://www.tropiclean.com/products/coconut-hypoallergenic-tearless-puppy-kitten-shampoo) | TropiClean | 20 fl oz | `TRGNSH20Z-LN` | 14.99 USD | Rs 4,945 | Chosen variant 'Default Title', USD 14.99, store SKU TRGNSH20Z-LN. Kept as the user's TropiClean Gentle Coconut Puppy Shampoo. |
| 2 | [EarthBath Oatmeal & Aloe Itch Relief Shampoo](https://www.earthbath.com/products/oatmeal-aloe-shampoo-vanilla-almond-groomer) | EarthBath | 128 fl oz | `PA4G` | 81.99 USD | Rs 27,050 | Chosen variant '128 fl oz', USD 81.99, store SKU PA4G. Store options: 128 fl oz; 320 fl oz. Full store price range 81.99-148.99. Kept as the user's Earthbath Oatmeal & Aloe Shampoo; the 4 oz size is no longer listed, so the 128 fl oz variant is used. |
| 3 | [Oneisall N12 4-in-1 Dog Grooming Kit](https://www.oneisall.com/products/n12-dog-clipper) | Oneisall | 1 kit | `CW03050` | 29.99 USD | Rs 9,895 | Chosen variant 'Blue / Standard', USD 29.99, store SKU CW03050. Store options: Blue; Standard. Substitute for Wahl Pet Clipper Kit; wahl.com is Magento with no readable feed and no product sitemap from this host. |
| 4 | [Neakasa Steel Pet Nail Clipper](https://neakasa.com/products/neakasa-pet-nail-clipper) | Neakasa | 1 clipper | `nail-clipper` | 19.99 USD | Rs 6,595 | Chosen variant 'Default Title', USD 19.99, store SKU nail-clipper. Substitute for Safari Professional Nail Trimmer; safaripet.com fails the TLS handshake from this host. |
| 5 | [tuft + paw THE BATH BRUSH Silicone Grooming Brush](https://fablepets.com/products/the-bath-brush-dual-use-silicone-grooming-brush-de-shedding-massaging-waterproof-tool-for-dogs) | tuft + paw | 1 brush | `PG-BRUSH-SILICONE-SET` | 22.00 USD | Rs 7,260 | Chosen variant 'Default Title', USD 22.00, store SKU PG-BRUSH-SILICONE-SET. Substitute for Hertzko Self-Cleaning Slicker Brush; hertzko.com fails TLS from this host. |
| 6 | [Oneisall LM5 4-in-1 Pet Grooming Vacuum Kit](https://www.oneisall.com/products/oneisall-dog-grooming-vacuum-kit-lm5) | Oneisall | LM2 full kit or LM5 lite kit | `CW16001` | 79.99 USD | Rs 26,390 | Chosen variant 'LM2-Full Grooming Kit', USD 79.99, store SKU CW16001. Store options: LM5-Lite Grooming Kit; LM2-Full Grooming Kit. Substitute for Furminator Undercoat Deshedding Tool; furminator.com fails certificate verification from this host. |
| 7 | [Oneisall RK034 Clipper Guide Combs 5-Pack](https://www.oneisall.com/products/oneisall-rk034-guide-combs-5-pack-replacement) | Oneisall | 5 combs | `PJ05039` | 13.99 USD | Rs 4,615 | Chosen variant 'Default Title', USD 13.99, store SKU PJ05039. Substitute for Andis Premium Pet Grooming Comb; andis.com publishes only category URLs and no per-product feed. |
| 8 | [EarthBath Oatmeal & Aloe Grooming Wipes](https://www.earthbath.com/products/oatmeal-aloe-grooming-wipes-1) | EarthBath | 100 ct | `PA7WS` | 18.99 USD | Rs 6,265 | Chosen variant '100 ct', USD 18.99, store SKU PA7WS. Store options: 100 ct. Type change: fills the KONG ZoomGroom brush slot with wipes, because no comparable brush could be verified (kongcompany.com exposes no per-product feed). Every other row in this batch is a same-type swap. |
| 9 | [tuft + paw THE MANE TAME Waterless Dog & Cat Shampoo](https://fablepets.com/products/the-mane-tame-waterless-dog-cat-shampoo-no-rinse-pet-grooming-and-quick-cleaning) | tuft + paw | 1 bottle | `PG-MANE` | 20.00 USD | Rs 6,600 | Chosen variant 'Default Title', USD 20.00, store SKU PG-MANE. Substitute for Burt's Bees Waterless Shampoo; burtsbees.com is Magento with no readable product feed from this host. |

## Images

`products.image` holds the local file as `products/<sku>.<ext>` (the app prefixes `assets/images/`),
and `products.image_url` keeps the Shopify URL for attribution. `image_source_name`/`image_source_url`
name the brand store the pack shot came from.

## Verification

- 9 products, no duplicate SKUs, every description 20-50 words, every price non-zero.
- `php -l` clean, `catalog_fields.py` 0 failures, guest/user/admin crawls 0 problems.
- All 36 pack shots of this batch answer 200 from the Apache copy in `/opt/lampp/htdocs/pawora`.
