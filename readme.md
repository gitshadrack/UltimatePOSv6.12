## About Ultimate POS

Ultimate POS is a POS application by [Ultimate Fosters](http://ultimatefosters.com), a brand of [The Web Fosters](http://thewebfosters.com).

## Installation & Documentation
You will find installation guide and documentation in the downloaded zip file.
Also, For complete updated documentation of the ultimate pos please visit online [documentation guide](http://ultimatefosters.com/ultimate-pos/).

## Security Vulnerabilities

If you discover a security vulnerability within ultimate POS, please send an e-mail to support at thewebfosters@gmail.com. All security vulnerabilities will be promptly addressed.

## License

The Ultimate POS software is licensed under the [Codecanyon license](https://codecanyon.net/licenses/standard).

## Custom Changes Added on 2026-05-14

This section documents the custom changes added to this Ultimate POS project so they can be repeated step by step on another installation.

### 1. M-PESA / Custom Pay Button

Purpose: Make the custom pay button visible, orange, and closer in size to the other POS payment buttons.

Files changed:

- `resources/views/sale_pos/partials/pos_form_actions.blade.php`

What changed:

- Styled the `custom_pay_1` buttons with an orange background.
- Added fixed sizing so the button aligns better with the other payment buttons.
- Kept the existing show/hide behavior.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 2. M-PESA Transaction Number Modal

Purpose: Replace the browser prompt with a modal where the cashier enters only the transaction number.

Files changed:

- `resources/views/sale_pos/partials/payment_modal.blade.php`
- `public/js/pos.js`

What changed:

- Added a transaction number modal.
- Added one input field for `Transaction No`.
- Updated POS JavaScript so the custom payment button opens the modal.
- On save, the transaction number is copied into the payment line and the POS form submits.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 3. Cash Register: View and Close Own Register Only

Purpose: Cashiers should only view and close their own cash register unless they have a special permission.

Files changed:

- `app/Http/Controllers/CashRegisterController.php`
- `app/Utils/CashRegisterUtil.php`
- `resources/views/cash_register/close_register_modal.blade.php`
- `app/Http/Controllers/ReportController.php`
- `app/Utils/TransactionUtil.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `lang/en/lang_v1.php`

Migration added:

- `database/migrations/2026_05_14_000001_add_view_all_cash_register_permission.php`

What changed:

- Added permission `view_all_cash_register`.
- Users without this permission can only access their own register.
- Close register now uses the register ID instead of trusting the submitted user ID.
- Register report is restricted to the logged-in user unless they have permission to view all registers.
- Added the new permission checkbox to role create/edit pages.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### 4. Stock Reset by Location with Admin Password

Purpose: Add a button to reset stock for one selected location only, while respecting business/location multitenancy.

Files changed:

- `resources/views/stock_adjustment/index.blade.php`
- `app/Http/Controllers/StockAdjustmentController.php`
- `routes/web.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `lang/en/stock_adjustment.php`
- `lang/en/role.php`

Migration added:

- `database/migrations/2026_05_14_000002_add_stock_reset_permission.php`

What changed:

- Added permission `stock_adjustment.reset`.
- Added a Reset Stock button on the stock adjustment page.
- Added reset modal fields for location, confirmation text `RESET`, and admin password.
- Controller checks the selected location belongs to the current business.
- Controller checks the user is allowed to access the selected location.
- Controller checks the entered password against the logged-in user's password.
- Reset creates a stock adjustment transaction, so the action is recorded in reports.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### 5. Product Sales Tab Inside Sell > All Sales

Purpose: Add a product-level sales tab inside the existing Sell > All Sales page.

Files changed:

- `app/Http/Controllers/SellController.php`
- `resources/views/sell/index.blade.php`
- `routes/web.php`

What changed:

- Added AJAX route `/sells/product-sales`.
- Added controller method `getProductSales()`.
- Added Category and Brand filters to the Sell page.
- Added a new `Product Sales` tab next to the existing All Sales tab.
- Added a product sales table with product, SKU, category, brand, location, sales rep, invoice number, date, quantity, unit price, total, and payment method.
- Filters supported: location, sales rep/user, commission agent, category, brand, and date range.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 6. Embedded Ultimate POS Guide Under Reports

Purpose: Add in-app documentation inside the Reports section.

Files changed:

- `routes/web.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`

File added:

- `resources/views/report/ultimate_pos_guide.blade.php`

What changed:

- Added route `/reports/ultimate-pos-guide`.
- Added controller method `ultimatePosGuide()`.
- Added `Ultimate POS Guide` link inside the Reports menu.
- Added guide sections for products, stock, sales, payments, reports, and daily closing.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 7. Monthly Total Sales Card on Home Dashboard

Purpose: Show extra dashboard analytics on the Home dashboard without requiring users to open reports.

Files changed:

- `app/Http/Controllers/HomeController.php`
- `resources/views/home/index.blade.php`
- `public/js/home.js`
- `lang/en/home.php`

What changed:

- Added `monthly_total_sell` to the Home dashboard totals response.
- The value is calculated from the first day of the current month to the last day of the current month.
- Added a new `Monthly Total Sales` card as the first dashboard card, followed by `Total Sales`.
- The card is populated automatically when the Home page loads.
- The card also renders a server-side fallback value so it does not stay blank before JavaScript updates it.
- The card respects the selected dashboard location, if a location is selected.
- The card does not depend on the dashboard date filter.
- Added `M-PESA Payment` card using `custom_pay_1` totals for the selected dashboard date/location.
- Added `Cash Payment` card using `cash` payment totals for the selected dashboard date/location.
- Added `Bank Balance` card using current payment account balances.
- The new M-PESA, Cash, and Bank Balance cards render fallback values and refresh through `public/js/home.js`.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 8. Separate Import Sales Permission

Purpose: Allow users to access POS sales without allowing them to import sales.

Files changed:

- `app/Http/Controllers/ImportSalesController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `lang/en/role.php`

Migration added:

- `database/migrations/2026_05_15_000001_add_import_sales_permission.php`

What changed:

- Added a new permission named `import_sales`.
- Added `Import sales` checkbox to role create/edit screens.
- Import Sales menu now appears only for admins or users with `import_sales`.
- Import Sales pages/actions now require `import_sales`.
- POS access can remain enabled using `sell.create` without giving import access.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

After migration, edit the role and enable `Import sales` only for users who should import sales.

### 9. Basic PWA Installation Support

Purpose: Make the POS installable as a basic Progressive Web App using the Sysnettechs Solutions logo.

Files added:

- `public/manifest.json`
- `public/service-worker.js`
- `resources/views/layouts/partials/pwa.blade.php`
- `public/pwa/sysnettechs-logo.png`
- `public/pwa/icon-192.png`
- `public/pwa/icon-512.png`

Files changed:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/layouts/auth2.blade.php`
- `resources/views/layouts/restaurant.blade.php`
- `readme.md`

What changed:

- Added web app manifest for `Sysnettechs Solutions POS`.
- Added install icons generated from the supplied Sysnettechs Solutions logo.
- Added service worker registration to the main app, auth, and restaurant layouts.
- Added a basic service worker that caches static assets only.
- The PWA is installable on supported browsers when served over HTTPS or localhost.

Important limitation:

- This is a basic installable PWA. It does not make sales, stock, payments, or reports work offline.
- Offline POS sales would require a separate offline queue and sync feature to protect stock and payment accuracy.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 10. Lot-Based Selling Price For POS

Purpose: Keep the normal product selling price unchanged, but allow a new stock lot to carry its own selling price. POS uses the old/default product price until the cashier selects a lot with a lot selling price.

Files added:

- `database/migrations/2026_05_15_000002_add_lot_sell_price_to_purchase_lines.php`

Files changed:

- `app/Utils/ProductUtil.php`
- `app/Utils/TransactionUtil.php`
- `app/Http/Controllers/OpeningStockController.php`
- `resources/views/purchase/create.blade.php`
- `resources/views/purchase/partials/purchase_entry_row.blade.php`
- `resources/views/purchase/partials/edit_purchase_entry_row.blade.php`
- `resources/views/opening_stock/form-part.blade.php`
- `resources/views/sale_pos/product_row.blade.php`
- `public/js/pos.js`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- Added `lot_sell_price_inc_tax` to `purchase_lines`.
- Added a `Lot selling price` input on purchase rows and opening stock rows.
- Lot selling price is saved per purchase/opening-stock lot.
- POS lot dropdown now carries the lot selling price.
- POS keeps the default product price when no lot is selected.
- When a lot with `Lot selling price` is selected, POS changes that sale row price to the lot price.
- POS reads the lot price from a browser-safe `data-lot-sell-price-inc-tax` attribute and reapplies it if the unit is changed.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

Usage:

1. Keep the product selling price as the old/default price.
2. When receiving new stock, enter the new price in `Lot selling price`.
3. In POS, sell without selecting a lot to use the default price.
4. Select the specific lot to use that lot's selling price.

### 11. M-PESA Cash Flow Treatment

Purpose: Treat M-PESA as money received in cash flow, but keep it separate from physical cash/register drawer totals.

Files changed:

- `app/Utils/Util.php`
- `resources/views/cash_register/payment_details.blade.php`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- `custom_pay_1` now defaults to `M-PESA` when no custom label is configured.
- Cash register summaries show physical cash as `Expected cash in drawer`.
- Total received money is labelled `Total collections`, so M-PESA is not confused with physical cash.
- M-PESA remains part of cash flow as an inflow, but it is displayed as its own payment method.

Recommended setup:

1. Go to each business location payment account settings.
2. Map `M-PESA` / `custom_pay_1` to an M-PESA or mobile money account.
3. Keep `Cash` mapped to the physical cash account only.
4. Run `php artisan optimize:clear` after uploading these files.

### 12. Login Page Side Image

Purpose: Keep the existing login fields but display a left-side image panel like the supplied reference, with tenant-specific branding based on the domain used to open the login page.

Files added:

- `public/img/login-side.jpg`
- `database/migrations/2026_05_15_000003_add_login_image_to_business_locations.php`
- `database/migrations/2026_05_15_000004_add_tenant_domain_and_login_image_to_business.php`

Files changed:

- `app/Http/Controllers/BusinessController.php`
- `Modules/Superadmin/Http/Controllers/BusinessController.php`
- `resources/views/business/partials/register_form.blade.php`
- `resources/views/business/partials/settings_business.blade.php`
- `app/Http/Controllers/BusinessLocationController.php`
- `resources/views/business_location/create.blade.php`
- `resources/views/business_location/edit.blade.php`
- `resources/views/layouts/auth2.blade.php`
- `resources/views/auth/login.blade.php`
- `public/js/app.js`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- Added a body class hook to the auth layout.
- Changed the login page to a two-column layout on desktop, with a wider login section than image section.
- Left side displays the sign-in image fitted to its full section.
- Added business-level `Tenant domain` and `Sign in page image` fields.
- Superadmin can set the tenant domain and sign-in image while creating a business.
- Business Settings can set or update the tenant domain and sign-in image for an existing business.
- Text on the login image now appears only when the current domain matches a Business `Tenant domain`; it displays that Business name only.
- Tenant matching accepts full domains, URL-style values, and short subdomain aliases. For example, `peak.pos-system.co.ke`, `https://peak.pos-system.co.ke/login`, and `peak` can all match the `peak.pos-system.co.ke` login host.
- Tenant login image resolution accepts a saved filename, a public path such as `uploads/business_login_images/file.jpg`, a storage path such as `storage/business_login_images/file.jpg`, or a full image URL.
- Tenant login images are served through `/tenant-login-image/{filename}` so images stored in either public uploads or Laravel storage can load on the login page.
- If the matched Business has its own uploaded sign-in image, login uses that image instead of the default `public/img/login-side.jpg`.
- Business Location create/edit now includes a `Sign in page image` upload field.
- Business Location modal submit now supports file upload.
- Updated `public/img/login-side.jpg` using the supplied Sysnettechs POS image.
- Right side keeps the current login form, language selector, and original blue gradient background.
- Login card is wider on desktop and uses responsive padding for mobile.
- Desktop login page height is locked to the viewport to avoid page scrolling; mobile can still scroll when needed.
- On tablet/mobile, the image panel is hidden and the login form remains full width.

How to set tenant-specific login branding:

1. Run `php artisan migrate`.
2. Open Business Settings > Business for an existing tenant, or create a business from Superadmin.
3. Set `Tenant domain` to the exact client domain/subdomain, for example `shop.co.ke` or `shop.sysnettechs.co.ke`, or to the short subdomain alias, for example `peak` for `peak.pos-system.co.ke`.
4. Upload the tenant `Sign in page image`.
5. Make sure the domain points to the same Ultimate POS installation.
6. Open the login page using that domain. If the domain matches, the login page shows that business name and image.

Fallback behavior:

1. If the domain does not match any Business `Tenant domain`, login uses `public/img/login-side.jpg`.
2. If the domain matches a tenant but no tenant image is uploaded, login still uses `public/img/login-side.jpg`.
3. The business name only appears when the domain matches a tenant.
4. A short alias only matches the first subdomain segment, so `peak` matches `peak.pos-system.co.ke` but not `other.pos-system.co.ke`.

How to change the default fallback image:

1. Upload your preferred image as `public/img/login-side.jpg`.
2. Keep the same file name to avoid editing code.
3. Use a wide image, ideally 1200px or wider.
4. Run `php artisan optimize:clear`.
5. Hard-refresh the browser if the old image is cached.

### 13. Manual M-PESA Payment Verification

Purpose: Let an admin confirm that each POS M-PESA payment entered by a cashier is backed by a real Safaricom/M-PESA message, while keeping the current `custom_pay_1` / M-PESA payment workflow.

Files added:

- `database/migrations/2026_05_16_000001_add_mpesa_verification_fields_to_transaction_payments_table.php`
- `database/migrations/2026_05_16_000002_remove_unverified_mpesa_account_transactions.php`
- `database/migrations/2026_05_16_000004_add_enable_mpesa_verification_to_business_locations_table.php`
- `resources/views/report/mpesa_verification.blade.php`

Files changed:

- `app/AccountTransaction.php`
- `app/BusinessLocation.php`
- `app/Http/Controllers/BusinessLocationController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/CashRegisterController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Listeners/AddAccountTransaction.php`
- `app/Utils/CashRegisterUtil.php`
- `app/Utils/TransactionUtil.php`
- `resources/views/business_location/create.blade.php`
- `resources/views/business_location/edit.blade.php`
- `resources/views/cash_register/close_register_modal.blade.php`
- `resources/views/cash_register/register_details.blade.php`
- `resources/views/report/mpesa_verification.blade.php`
- `routes/web.php`
- `lang/en/lang_v1.php`
- `readme.md`

Database fields added to `transaction_payments`:

- `mpesa_verification_status`: `pending`, `verified`, or `rejected`
- `mpesa_verified_by`
- `mpesa_verified_at`
- `mpesa_verification_note`

Database fields added to `business_locations`:

- `enable_mpesa_verification`: location-level toggle for manual M-Pesa verification.

What changed:

- M-PESA payments continue to be saved as `transaction_payments.method = custom_pay_1`.
- The cashier-entered M-PESA transaction number continues to be saved in `transaction_payments.transaction_no`, but is now normalized with `strtoupper(trim(...))` before saving.
- Added a Business Location checkbox: `Enable Manual M-PESA Verification`.
- Added `Reports > M-Pesa Audit Trail`.
- The audit screen is a pending-only utility dashboard with sale date/invoice, cashier, M-Pesa reference code, KES amount, and fast AJAX `Verify` / `Reject` buttons.
- The audit screen supports filters for cashier, location, register, and date range.
- Closed/current register details and the close-register modal now show an `M-Pesa Audit Trail` summary with Total M-PESA, Verified Match, Pending Audit, and Invalid / Missing Message amounts.
- Register M-Pesa totals are dynamic per location. If the register location has manual verification enabled, totals are split into verified, pending, and rejected via `SUM(CASE WHEN...)` audit queries. If disabled, all `custom_pay_1` collections are treated as verified immediately.
- M-PESA payments only stay out of linked payment accounts when the transaction location has `enable_mpesa_verification = 1` and the payment is still `pending` or `rejected`.
- For enabled locations, the linked payment account is posted only after the M-PESA payment is marked `Verified`.
- If a verified M-PESA payment in an enabled location is later rejected, its account transaction is removed from account balances.
- Existing unverified M-PESA account transactions are soft-deleted by the cleanup migration.
- Access uses the existing `register_report.view` permission.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

Recommended workflow:

1. Open Business Settings > Business Locations.
2. Edit each store that needs manual matching.
3. Enable `Enable Manual M-PESA Verification`.
4. Save the location.
5. Cashier makes a POS sale in that location.
6. Cashier selects M-PESA.
7. Cashier enters the M-PESA transaction/reference code.
8. Cashier completes the sale and closes the register.
9. Admin opens `Reports > M-Pesa Audit Trail`.
10. Admin compares each transaction number and amount against Safaricom/M-PESA messages.
11. Admin marks each payment as `Verified` or `Rejected`.

For locations where `Enable Manual M-PESA Verification` is disabled:

1. Cashier makes a POS sale.
2. Cashier selects M-PESA.
3. Cashier enters the M-PESA transaction number.
4. Cashier completes the sale.
5. M-PESA behaves like the standard `custom_pay_1` workflow and posts normally to the configured payment account.

Verification notes:

- New M-PESA payments default to `pending`.
- Existing M-PESA rows are backfilled to `pending` by the migration.
- Pending or rejected M-PESA payments remain visible in payment/register reports, but they do not increase the linked payment account balance for locations where manual verification is enabled.
- Payment account posting is guarded in `TransactionUtil`, `AddAccountTransaction`, and `AccountTransaction` so enabled locations cannot accidentally post unverified M-PESA to `account_transactions`.
- Disabled locations bypass the audit queue and treat all M-PESA as verified for register/accounting purposes.
- This is a manual verification workflow. Future automation can import M-PESA statements/SMS/API data and match by transaction number, amount, date/time, till/paybill, and reference.

Previous location-level image option:

1. Run `php artisan migrate`.
2. Open Business Settings > Business Locations.
3. Edit the location.
4. Upload an image in `Sign in page image`.
5. Save the location.
6. This remains available for records, but the login page now uses Business `Tenant domain` for tenant branding.

### 14. Superadmin Business Data Initialization

Purpose: Let Superadmin initialize a tenant without deleting the business, so products and prices remain but sales/stock/report transaction data is cleared.

Files added:

- `Modules/Superadmin/Resources/views/business/initialize_data.blade.php`

Files changed:

- `Modules/Superadmin/Routes/web.php`
- `Modules/Superadmin/Http/Controllers/BusinessController.php`
- `Modules/Superadmin/Resources/lang/en/lang.php`
- `readme.md`

What changed:

- Added an `Initialize Data` action beside each business in Superadmin > Business.
- Added a confirmation page with optional Business Location selection.
- Superadmin must enter their password and type `RESET`.
- If no location is selected, all locations under the business are initialized.
- If a location is selected, only that location scope is initialized.
- Deletes transactions, payments, account transactions, cash register history, transaction media, and sale/purchase/stock lines linked to the selected scope.
- Sets current stock quantity to zero in `variation_location_details` for products belonging to that business and selected location scope.
- Keeps products, variations, selling prices, categories, brands, units, users, roles, business locations, tenant domain, login image, and business settings.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 15. Public Index Cards and Superadmin Business Branding Edit

Purpose: Improve the public index page and let Superadmin update tenant branding fields after a business has already been created.

Files added:

- `Modules/Superadmin/Resources/views/business/edit.blade.php`

Files changed:

- `routes/web.php`
- `resources/views/welcome.blade.php`
- `Modules/Superadmin/Http/Controllers/BusinessController.php`
- `Modules/Superadmin/Resources/views/business/show.blade.php`
- `Modules/Superadmin/Http/Controllers/PricingController.php`
- `.env`

What changed:

- Public `/` index now shows business-type cards using the original Bootstrap/AdminLTE card structure.
- Added cards for Pharmacy, Electronics, Supermarket, Restaurant, Fashion, and Hardware.
- Each public card links to business registration.
- Public pricing was hidden from the index page.
- The root route was simplified so it no longer loads pricing package data for the index page.
- Superadmin > Business list now includes an `Edit` action for each business.
- Superadmin business detail page now includes an `Edit` button.
- Added a Superadmin business edit page for updating:
  - Business name
  - Tenant domain
  - Business logo
  - Sign in page image
- Superadmin business detail page now displays the tenant domain and sign-in page image when present.
- Superadmin business update saves uploaded images to the existing `business_logos` and `business_login_images` upload folders.
- Registration was enabled in local `.env` and `APP_URL` was pointed to the local `CodeBaseV7/public` URL during local setup.

How to update tenant branding from Superadmin:

1. Log in as Superadmin.
2. Open Superadmin > Business.
3. Click `Edit` for the target business.
4. Set `Tenant domain`, for example `shop.co.ke`, `shop.sysnettechs.co.ke`, or short alias `peak` for `peak.pos-system.co.ke`.
5. Upload a `Sign in page image` if needed.
6. Save the form.
7. Open the login page through the tenant domain to confirm the tenant-specific image appears.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed if the tenant branding migration from section 12 has already been run.

### 16. Stock Sheet Report

Purpose: Add a dedicated printable/exportable stock counting sheet for physical stock take, without changing the normal Stock Report.

Files added:

- `resources/views/report/stock_sheet.blade.php`

Files changed:

- `routes/web.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `lang/en/report.php`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- Added `Reports > Stock Sheet`.
- Added route `/reports/stock-sheet`.
- Added controller method `getStockSheet()`.
- Stock Sheet reuses the existing stock calculation from `ProductUtil::getProductStockDetails()`.
- Added filters for business location, category, brand, and unit.
- Added columns for SKU, product, variation, category, location, system stock, physical count, difference, and note.
- `Physical Count`, `Difference`, and `Note` are intentionally blank so staff can write counts on a printed sheet or fill them after export.
- Uses existing `stock_report.view` permission.
- Export buttons are available to users with the existing `view_export_buttons` permission.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### 17. Kenya Tax Administration Sidebar

Purpose: Add a dedicated Tax Administration area for Kenyan tax workflows instead of mixing every tax function inside the general Reports menu.

Files added:

- `database/migrations/2026_05_16_000003_add_kenya_tax_fields_to_transactions.php`
- `resources/views/tax_admin/dashboard.blade.php`
- `resources/views/tax_admin/vat_sales_schedule.blade.php`
- `resources/views/tax_admin/vat_purchase_schedule.blade.php`
- `resources/views/tax_admin/turnover_tax.blade.php`
- `resources/views/tax_admin/etims_tracking.blade.php`
- `resources/views/tax_admin/partials/date_location_filters.blade.php`
- `resources/views/tax_admin/partials/schedule_js.blade.php`

Files changed:

- `routes/web.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Utils/ModuleUtil.php`
- `lang/en/lang_v1.php`
- `readme.md`

Database fields added to `transactions`:

- `buyer_pin`
- `etims_status`: `pending`, `submitted`, `accepted`, `failed`, or `cancelled`
- `etims_invoice_no`
- `etims_control_code`
- `etims_qr_code`
- `etims_submitted_at`
- `etims_response`

What changed:

- Added a new optional module checkbox named `Tax Administration` under Settings > Business Settings > Modules.
- The Tax Administration sidebar dropdown is hidden by default and appears only after the `Tax Administration` module is enabled.
- Sidebar access still requires Admin role or the existing `tax_report.view` permission.
- Added a new sidebar dropdown named `Tax Administration`.
- Added `Tax Dashboard` with output VAT, input VAT, net VAT payable, gross sales, turnover tax estimate, and eTIMS attention counts.
- Added `VAT Sales Schedule` with invoice, customer, buyer PIN, location, taxable amount, output VAT, gross total, and eTIMS status.
- Added `VAT Purchase Schedule` with supplier, supplier tax number, location, taxable amount, input VAT, and gross total.
- Added `Turnover Tax Report` using the current KRA TOT estimate rate of 1.5% of gross sales.
- Added `eTIMS Tracking` for manually recording buyer PIN, eTIMS invoice number, eTIMS control code, and eTIMS status per sale.
- Added links to the existing `Tax Report` and `Tax Rates` under the same Tax Administration dropdown.
- Access uses the existing `tax_report.view` permission after the module is enabled.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

Important limitation:

- This is the internal Tax Administration foundation. It does not yet transmit invoices to KRA eTIMS automatically.
- Full automation still requires KRA eTIMS system-to-system access or an approved middleware/provider integration.

### 18. Decorated Admin Sidebar

Purpose: Refresh the admin sidebar so menu items look like rounded action buttons, with a search box and highlighted colored module buttons similar to the supplied sidebar reference.

Files changed:

- `resources/views/layouts/partials/sidebar.blade.php`
- `app/Http/AdminlteCustomPresenter.php`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- Added a `Search menu...` input at the top of the admin sidebar.
- Added live client-side filtering for sidebar menu items.
- Added rounded button-style decoration for normal and dropdown sidebar links.
- Added consistent icon spacing and coloring for SVG and Font Awesome menu icons.
- Added colored highlighted pills for selected module/menu names:
  - `Superadmin`
  - `Manufacturing`
  - `Repair`
  - `Accounting`
  - `AI Assistance`
  - `HMS`
  - `GYM`
  - `Zatca`
- Kept the existing sidebar menu permissions, module checks, routes, and dropdown behavior intact.
- Hid the previous business-name sidebar brand block so the search box starts at the top like the reference.
- Sidebar styling is included in `sidebar.blade.php` so it survives rebuilds of the ignored/generated `public/css` assets.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### Recommended Online Deployment Steps

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

### Migration Safety Note

The added migrations create permissions and small nullable columns, such as tenant login branding fields. They should not delete existing sales, products, customers, stock, or payment data. Always back up the live database before running migrations.
