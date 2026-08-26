# Word Truth Spirit development guide

## Project shape

This is a dependency-free PHP 8.1+ application. Public entry points live at the
repository root or in route folders such as `blog/`, `donate/`, and `shop/`.
Shared PHP belongs in `includes/`, admin screens belong in `admin/`, and all
database changes must be represented in both `database/schema.sql` (fresh
installs) and `includes/updates.php` (existing installs).

The application supports the native `wts_*` tables and the legacy Node export
detected by `databaseUsesLegacySchema()`. Do not assume a database connection on
public pages. Public helpers must fail gracefully; admin pages should show a
useful error instead of emitting a fatal error.

## Engineering rules

- Start every PHP file with `declare(strict_types=1);`.
- Escape untrusted output with `e()` and use prepared statements for values.
- Protect every state-changing admin request with `verifyCsrf()`.
- Use `requireAdmin()` for all authenticated admin screens.
- Never store card data or secret PayPal credentials. Checkout hands off to
  PayPal over HTTPS using public merchant configuration.
- Validate product and contribution amounts on the server. Hidden inputs are not
  a catalog source of truth.
- Keep migrations additive and repeatable. Never drop user data from the web
  update runner.
- Generate internal links with `url()` and absolute return URLs with
  `applicationUrl()` so subfolder installs continue to work.
- Reuse the shared admin shell. The blog editor may keep its focused workspace.
- Prefer readable multi-line PHP and HTML for new work.

## Admin UX

Admin pages need a clear title, a short description, a primary action, useful
empty states, and visible success/error notices. Destructive actions require
confirmation. Never allow deletion of the current or last administrator.

The editor must retain recovery drafts, HTML/source mode, keyboard save, image
preview, block reordering, and the hidden canonical `body` field.

## Commerce model

`wts_products` is the catalog source of truth. `fixed` items have one server-set
price. `contribution` items have suggested amounts and may permit a custom amount
within configured bounds. `shop/checkout.php` validates the catalog record and
amount before PayPal handoff.

A successful redirect is not proof of payment. Do not add fulfillment or revenue
reporting without PayPal webhook verification and an order ledger.

## Verification

1. Run `find . -name '*.php' -print0 | xargs -0 -n1 php -l`.
2. Start `php -S 127.0.0.1:8080` and smoke-test public fallback pages.
3. Run `git diff --check` and inspect `git status --short`.
4. For schema work, verify both fresh-install SQL and the pending update entry.
