# Operations and Deployment

Log triage, runtime performance, deployment steps, operational recommendations, and migration safety.

Return to the [documentation index](../readme.md) or [customizations index](CUSTOMIZATIONS.md).

## Contents

- [Log Triage Fixes For Recurring Errors](#log-triage-fixes-for-recurring-errors)
- [POS Runtime Performance Optimizations (2026-07-17)](#pos-runtime-performance-optimizations-2026-07-17)
- [Recommended Online Deployment Steps](#recommended-online-deployment-steps)
- [Operational Recommendations](#operational-recommendations)
- [Migration Safety Note](#migration-safety-note)

---

<a id="log-triage-fixes-for-recurring-errors"></a>
## Log Triage Fixes For Recurring Errors

Purpose: Reduce the most common errors found in `storage/logs`, especially issues caused by partially upgraded database schemas and stale transaction/product references.

Files changed:

- `app/AccountTransaction.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/SellController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/CashRegisterUtil.php`
- `app/Utils/ProductUtil.php`
- `app/Utils/TransactionUtil.php`
- `readme.md`

Issues reviewed from logs:

- `No query results for model [App\Variation]`
- `Call to undefined method App\Utils\CashRegisterUtil::getRegisterMpesaAuditTrail()`
- `SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded`
- `Division by zero`
- `Class "PaymentAccountController" does not exist`
- Missing column errors for `enable_mpesa_verification`, `enable_numeric_login`, and `offline_sale_uuid`

What changed:

- Confirmed `CashRegisterUtil::getRegisterMpesaAuditTrail()` exists in the current codebase. The logged failures are from an older build.
- Sale edit and POS edit screens now tolerate transaction lines whose variation record no longer exists while loading media. Missing variation media becomes an empty collection instead of throwing a 500 error.
- Combo product calculations now skip missing component variations.
- Combo calculations now guard against empty quantities and zero unit multipliers.
- M-PESA verification logic now checks whether `business_locations.enable_mpesa_verification` exists before querying it.
- If the M-PESA verification column is missing, register totals fall back to normal/plain M-PESA totals.
- If the M-PESA verification column is missing, M-PESA audit reports return an empty result instead of crashing.
- If the M-PESA verification column is missing, account posting does not skip M-PESA payments for manual verification.
- The `PaymentAccountController` error does not map to the current routes. Current routes use `AccountController` and `AccountReportsController`; if this appears again, clear route/config cache and confirm the deployed route file is current.
- The logged `Division by zero` at `ReportController.php` stock value calculation is already guarded in the current code.

Operational notes:

- `No query results for model [App\Variation]` usually means old sale lines or combo definitions reference variations that were deleted or no longer exist.
- These fixes prevent common display/calculation crashes, but they do not repair historical data. Review affected products/combo definitions if the issue keeps appearing.
- `Lock wait timeout exceeded` is a database concurrency issue, commonly around stock or purchase-line updates. It was not patched here because it needs transaction-flow review and database workload analysis.
- Missing column errors generally mean migrations were not run, route/config cache is stale, or code was deployed before the database schema update.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```


---

<a id="pos-runtime-performance-optimizations-2026-07-17"></a>
## POS Runtime Performance Optimizations (2026-07-17)

Purpose: Reduce normal page latency, especially on the unified M-PESA dashboard, and make Laravel's production caches build reliably.

Application files changed:

- `app/Http/Controllers/MpesaDashboardController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `Modules/Accounting/Helpers/general_helper.php`
- `Modules/Installment/Helpers/general_helper.php`
- `Modules/WhatsApp/Helpers/Helpers.php`
- `Modules/Accounting/Providers/AccountingServiceProvider.php`
- `Modules/Installment/Providers/InstallmentServiceProvider.php`
- `Modules/PageSpeed/Providers/PageSpeedServiceProvider.php`
- `Modules/Superadmin/Providers/SuperadminServiceProvider.php`
- `Modules/Woocommerce/Providers/WoocommerceServiceProvider.php`
- `Modules/Hms/Routes/web.php`
- `Modules/Superadmin/Routes/web.php`
- `routes/web.php`
- `readme.md`

What changed:

- Consolidated all enabled M-PESA providers into one unioned conditional aggregate SQL query, returning both provider-level figures and combined dashboard totals in one database round trip.
- Reused the session's enabled-module list and loaded the current user's permission names once while constructing the M-PESA sidebar.
- Disabled Xdebug for Apache's normal POS runtime with `xdebug.mode = off` and `xdebug.log_level = 0`. The Apache service was restarted after the change. This is server configuration in WAMP's Apache PHP INI and is not a repository file.
- Added duplicate-function guards to module helper files so Laravel can safely bootstrap them while generating caches.
- Namespaced conflicting legacy resource route names for HMS bookings and Superadmin coupons. Their URLs and controllers are unchanged.
- Ignored nonexistent optional module view-override directories, allowing Blade view caching on a clean installation.
- Built Laravel configuration, route, event, and Blade caches. The resulting files under `bootstrap/cache` and `storage/framework/views` are generated deployment artifacts and should not be committed.

Production cache command:

```bash
php artisan optimize
php artisan event:cache
php artisan view:cache
```

When deploying new code or changing `.env`, clear old caches first, then rebuild them:

```bash
php artisan optimize:clear
php artisan optimize
php artisan event:cache
php artisan view:cache
```

Keep Xdebug off during normal POS use. Enable it temporarily only for an active debugging session, then turn it off and restart Apache again.


---

<a id="recommended-online-deployment-steps"></a>
## Recommended Online Deployment Steps

1. Upload all changed controller files.
2. Upload all changed Blade view files.
3. Upload changed language files.
4. Upload changed `routes/web.php`.
5. Upload new migration files.
6. Back up the live database from cPanel or phpMyAdmin.
7. Run migrations if uploading features that added permissions or database columns.

For all changes:

```bash
php artisan migrate
php artisan optimize:clear
```

For changes without migrations:

```bash
php artisan optimize:clear
```


---

<a id="operational-recommendations"></a>
## Operational Recommendations

1. Keep this README as the source of truth for implemented customizations. When adding a migration, permission, route, or business workflow, document the changed files, required server action, and any setup steps in the same change section.
2. Treat `intasend_ultimate_pos_integration.docx` as the original integration specification. The repo now includes the first implementation phase: webhook ingestion, IntaSend tables, location settings, conservative customer matching, and a reconciliation holding pool.
3. Keep IntaSend as its own project phase for future expansion because it affects webhook security, duplicate transaction handling, multi-location till mapping, customer ledger settlement, account posting, and manual reconciliation.
4. Add live IntaSend webhook signature verification after confirming the exact signing headers and secret validation method for the merchant account.
5. Keep the manual M-PESA verification workflow under regression testing whenever payments, registers, account transactions, or reports are changed. Pending and rejected M-PESA payments must not increase linked account balances at locations where manual verification is enabled.
6. Keep the Kenya Tax Administration feature clearly labelled as internal reporting and manual eTIMS tracking. It does not automatically submit invoices to KRA eTIMS.
7. Keep PWA expectations modest. The current PWA only supports installability and static asset caching; it does not support offline selling, stock updates, payment capture, or report syncing.
8. Confirm `.env` remains untracked in Git before deployment or handoff. Store live credentials in the server environment, not in committed project files.
9. Treat `docs/RESTAURANT_POS_IMPLEMENTATION_PLAN.md` as the future
   specification for fully committing the POS to restaurant operations. It
   covers waiter PIN access, floors and tables, preparation routing, kitchen
   displays, provisional bills, cashier settlement, split/merge/return/swap
   workflows, waiter-shift and register eligibility, supervisor voids,
   inventory history, tips, service charges, permissions, audit controls,
   architecture recommendations, implementation phases, and acceptance
   criteria. The recommendations prioritize a focused MVP, separate operational
   numbers, append-only order events, table sessions, early ingredient
   reservation/consumption, station routing, atomic settlement, controlled tip
   liabilities, and a feature-flagged pilot. These capabilities remain planned
   until their implementation is completed and verified.


---

<a id="migration-safety-note"></a>
## Migration Safety Note

The added migrations create permissions and small nullable columns, such as tenant login branding fields. They should not delete existing sales, products, customers, stock, or payment data. Always back up the live database before running migrations.
