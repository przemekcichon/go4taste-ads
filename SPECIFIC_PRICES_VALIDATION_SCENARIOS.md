# Specific Prices Validation Scenarios

These scenarios validate promo price mapping from PrestaShop specific_prices to Algolia payload.

## Scope
- Fetcher: core/api-client/class-go4taste-ads-api-fetcher.php
- Renderer consumers: banner-sidebar-widget, banner-inline-block

## Scenarios

1. Percentage reduction, tax included
- Input:
  - base gross: 32,00
  - specific_price.price: -1
  - reduction: 0.16
  - reduction_type: percentage
  - reduction_tax: 1
- Expected:
  - price_without_reduction: 32,00 zł
  - price: 26,88 zł

2. Amount reduction, tax included
- Input:
  - base gross: 50,00
  - specific_price.price: -1
  - reduction: 5.00
  - reduction_type: amount
  - reduction_tax: 1
- Expected:
  - price_without_reduction: 50,00 zł
  - price: 45,00 zł

3. Amount reduction, tax excluded
- Input:
  - base net: 40,65
  - tax rate: 23%
  - specific_price.price: -1
  - reduction: 5.00
  - reduction_type: amount
  - reduction_tax: 0
- Expected:
  - final net: 35,65
  - price (gross): 43,85 zł

4. No active promotion
- Input:
  - no active specific_price or unsupported scope/date
- Expected:
  - price_without_reduction equals price
  - no visual old price in sidebar/inline render

5. Multiple specific_price rows conflict
- Input:
  - multiple active rows for the same product
- Expected:
  - one candidate selected deterministically (latest id after filtering)
  - if computed promo is not lower than base, fallback to base (no fake promo)

## Manual integration checks
1. Run product sync to Algolia.
2. Verify one promo product record has different values in fields:
   - price
   - price_without_reduction
3. Open sidebar widget and inline block placement:
   - old price is shown only when values differ.
4. Verify non-promo product keeps single price rendering.
