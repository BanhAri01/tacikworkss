# TacikWorks (DesignMarket) — Spec

## Positioning (why sellers choose us)
1. Hybrid fee: LOW fee when the seller brings the buyer (storefront / referral link), HIGHER fee only when our marketplace brings the buyer. Configurable in admin settings (defaults: 10% own-traffic, 30% marketplace).
2. Transparent fee breakdown on every sale (price, platform fee, payment fee, net).
3. Seller storefront at `/shop/{username}` usable as their own store.
4. Tax info form per seller (country, legal name, foreign-status/treaty fields).
5. Non-exclusive by default: sellers can also sell elsewhere.

## Money flow (decided 2026-10-05)
- Platform NEVER holds buyer money. PayPal Commerce Platform multiparty: each PayPal purchase unit has `payee` = seller's PayPal merchant id; platform fee is taken via `payment_instruction.platform_fees` (disbursement INSTANT).
- Sellers must "Connect PayPal" (Partner Referrals onboarding) before their products can be bought. Live mode requires PayPal partner approval.
- Buyer pays the PayPal processing fee: checkout adds a "processing fee" per seller unit, gross = ceil((items + fixed) / (1 − rate)); defaults 4.4% + 30¢ (settings). Seller net = price + buyer fee − actual PayPal fee − platform fee.
- One checkout can contain max 10 sellers (PayPal purchase-unit limit).
- Coupons belong to a seller and only discount that seller's items.
- ledger_entries are a record of sales/fees only (no custody); payout_requests not needed.
- Affiliates (?aff=code, 30 days) earn `affiliate_bps` of item price on marketplace-traffic sales only, capped at and paid from OUR platform fee; admin pays them manually from the platform PayPal and marks paid.
- Refunds: admin refunds an order item from the seller's capture (PayPal-Auth-Assertion) and returns our platform fee; ledger gets a negative entry; license/download revoked; review removed.
- Bundles: seller sets one price + one license for ≥2 own items; price is split across items by their separate prices.

## Data model (core)
- users (roles via spatie), seller_profiles (username, display_name, bio, avatar, banner, country, paypal_email, tax_info json, status: pending/approved/suspended)
- categories (nested parent_id), tags, product_tag
- products (seller_id, category_id, title, slug, description, status: draft/pending/approved/rejected, rejection_reason, file_formats json, compatible_software json, is_free, featured_at, sales_count, rating_avg, version)
- product_previews (product_id, path, sort); product_files (product_id, private path, size, version, changelog)
- licenses (Personal/Commercial/Extended, description) + product_license_prices (product_id, license_id, price_cents)
- carts, cart_items; orders (buyer_id, total_cents, status, gateway, gateway_ref); order_items (product_id, license_id, seller_id, price_cents, platform_fee_cents, payment_fee_cents, seller_net_cents, attribution: own|marketplace, purchase_code uuid)
- downloads (order_item_id, ip, created_at)
- ledger_entries (seller_id, type: sale/refund/payout/adjustment, amount_cents, order_item_id nullable); seller balance = sum
- payout_requests (seller_id, amount_cents, status, paypal_batch_ref)
- reviews (order_item_id unique, rating 1-5, body, seller_reply), favorites, collections, follows (buyer→seller)
- coupons (seller_id nullable, code, percent/fixed, limits, expires_at), bundles (seller_id, products, price)
- reports (product_id, reason: copyright/other, status), settings (key/value)

## Phases

### Phase 1 — Foundation
Install stack; auth (register, login, email verify, reset); roles + admin seeder; "Become a seller" application → admin approves; Blade panels /admin and /seller with role guards; base layout (header with search, footer), mobile-first.

### Phase 2 — Catalog & upload
Seller creates/edits products in Blade seller dashboard; multiple previews auto-converted to WebP with watermark on large preview; private zip upload; license prices, tags, formats, software; submit → pending. Admin: moderation queue (approve/reject with reason), categories, tags, licenses.

### Phase 3 — Browse & storefront
Home (featured, new, popular, free of the week); category pages; search with filters (category, price range, format, software, free) and sort (newest, best-selling, rating, price); product page (gallery, license selector, seller card, reviews, related, more from seller); storefront /shop/{username} with follow button; SEO (slugs, meta, Open Graph, schema.org Product, sitemap.xml).

### Phase 4 — Checkout & downloads
Livewire cart with coupon field; PayPal Checkout (sandbox first) with webhook verification and idempotent order creation; on payment create order_items with fee calc (attribution from `?ref=username` or storefront visit, 30-day cookie), ledger entries, emails; "My downloads" with 15-minute signed URLs, download log, license certificate PDF + purchase code; free products require login, no payment.

### Phase 5 — Seller dashboard & payouts
Sales list with fee breakdown, earnings chart, totals (no balance/payouts: money goes straight to seller PayPal, see Money flow); tax info form; new file version + changelog → email buyers.

### Phase 6 — Community & growth
Reviews (verified buyers only, seller reply); favorites, collections; follow seller + new-product notification; seller coupons; bundles; affiliate links (% on referred sales, configurable); public license check `/verify/{purchase_code}` + JSON API.

### Phase 7 — Trust & polish
Report product (copyright) → admin queue; refunds (reverse ledger); user suspension; admin dashboard stats; settings page (fees, payout minimum, free of the week); rate limiting, image caching, N+1 checks, 404/500 pages.

## Out of scope for now
Subscription "download everything" plan, multi-currency, automatic VAT engine (decide later: own handling vs merchant-of-record like Lemon Squeezy/Paddle).
