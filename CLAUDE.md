# TacikWorks (codename DesignMarket)

Digital design asset marketplace (Envato/Creative Market style) with seller storefronts (Gumroad/Payhip style). Sellers mostly Indonesian designers; buyers international (USD, PayPal). Full spec: `docs/SPEC.md`. Progress: `docs/PROGRESS.md`.

## Stack (do not change without asking)
- Laravel 12, PHP 8.2+ (XAMPP), MySQL 8 / MariaDB 10.4 in dev
- Frontend: Blade + Livewire 4 (Volt) + Alpine.js + Tailwind CSS + Flux free components (Livewire starter kit)
- Admin panel (/admin) AND seller dashboard (/seller): plain Blade (+ Livewire/Volt where interactive). NO Filament.
- Roles: spatie/laravel-permission (buyer, seller, admin)
- Search: Laravel Scout, database driver first (Meilisearch later)
- Files: private disk `products` (local in dev, S3/Cloudflare R2 in prod); previews on public disk as WebP
- Queue: database driver. Image processing: intervention/image
- Payments: PayPal Checkout (Orders v2) via Laravel Http client, behind `App\Contracts\PaymentGateway`
- Money: integer cents (USD) everywhere. Never floats. Percentages as integer basis points (1000 = 10%).
- Platform never holds funds: PayPal multiparty (payee = seller, platform_fees = our fee); buyer pays processing fee. See "Money flow" in SPEC.
- PDF: barryvdh/laravel-dompdf (license certificates).

## Conventions
- Business logic in `app/Actions/*`. Controllers/Livewire stay thin.
- Enums in `app/Enums`. Form Requests for validation. Policies for authorization.
- Use `php artisan make:*` generators instead of hand-written boilerplate.
- Admin/seller screens: controllers + Blade views under `resources/views/admin`, `resources/views/seller`, shared sidebar layout `x-layouts.app`.
- Feature tests (Pest) only for money, orders, downloads, payouts, permissions.

## Token rules (IMPORTANT)
- Never read or search vendor/, node_modules/, storage/, public/build/, bootstrap/cache/, lock files.
- Only open files needed for the current task. Don't explore the whole repo.
- Minimal edits; never reprint whole files in chat.
- Short replies. No long summaries, no explaining code not asked about.
- Run targeted tests only: `php artisan test --filter=...`.
- Ask before installing any package not listed in Stack.
- End of each phase: append max 5 lines to docs/PROGRESS.md (done, next, known issues).

## Workflow
- One phase at a time. On "start phase N": read CLAUDE.md, docs/PROGRESS.md, and ONLY that phase in docs/SPEC.md; give a plan in max 15 bullets; wait for approval.
- After approval: implement, run relevant tests, update PROGRESS.md, list max 8 things to test manually.
- Bugs: find the cause, fix with minimal changes, don't refactor unrelated code.
