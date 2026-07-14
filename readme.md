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

### 19. Pre-Location Login Routing

Purpose: Let each business location decide its login style before the cashier sees the login form. A location can use numeric PIN login while another location in the same business uses normal ABC username/password login, without asking the cashier to choose from a location dropdown.

Files added:

- `database/migrations/2026_05_21_000001_add_enable_numeric_login_to_business_locations_table.php`
- `database/migrations/2026_05_25_000001_add_login_domain_to_business_locations_table.php`

Files changed:

- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/BusinessLocationController.php`
- `app/Http/Controllers/SellPosController.php`
- `resources/views/auth/login.blade.php`
- `resources/views/business_location/create.blade.php`
- `resources/views/business_location/edit.blade.php`
- `lang/en/lang_v1.php`
- `readme.md`

Database fields added to `business_locations`:

- `enable_numeric_login`: location-level toggle for numeric PIN login.
- `login_domain`: optional domain, subdomain, or short code used to pre-select a location on the login page.

What changed:

- Business Location create/edit now includes `Location login domain/code`.
- The login page can resolve a location from the current domain or from a URL query such as `/login?location=branch-a`.
- If a location is resolved, the login location field is hidden and that location is used automatically.
- If the resolved location has `Enable Numeric Login` enabled, the login page shows the PIN keypad.
- If the resolved location does not have `Enable Numeric Login` enabled, the login page shows normal ABC username/password login.
- For ABC login, the preselected location is stored in the session after authentication and POS defaults to that location.
- PIN login remains guarded by location, so a PIN can only log in through a location where numeric login is enabled.
- If no domain/code is matched, the login page uses the first active location in the scoped location list and still keeps the location field hidden.

How to configure pre-location login:

1. Run `php artisan migrate`.
2. Open Business Settings > Business Locations.
3. Edit each location.
4. Set `Location login domain/code`, for example:
   - `branch-a.pos-system.co.ke`
   - `branch-b`
   - `main-shop`
5. Enable or disable `Enable Numeric Login` per location.
6. Open the location login page by domain or code:
   - `https://branch-a.pos-system.co.ke/login`
   - `/login?location=branch-b`

Matching behavior:

1. Full domains can match the browser host, for example `branch-a.pos-system.co.ke`.
2. Short codes can match the first subdomain segment, so `branch-a` matches `branch-a.pos-system.co.ke`.
3. URL query values can match the location database id, the location id/code, or the `Location login domain/code`.
4. When a business `Tenant domain` is matched, location matching is scoped to that business.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### POS Default Purchase Price Visibility Permission

Purpose: Add a separate role permission for viewing the default purchase price from the POS product row.

Files added:

- `database/migrations/2026_05_19_000001_add_view_default_purchase_price_from_pos_screen_permission.php`

Files changed:

- `resources/views/sale_pos/product_row.blade.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`

What changed:

- Added permission `view_default_purchase_price_from_pos_screen`.
- POS product rows now check this permission before showing the default purchase price.
- Added the permission checkbox to role create/edit screens.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### POS Virtual Keyboard Toggle

Purpose: Add an optional on-screen keyboard for the POS screen, controlled from Business Settings > POS. The setting is disabled by default.

Files added:

- `resources/views/sale_pos/partials/virtual_keyboard.blade.php`
- `public/js/pos_virtual_keyboard.js`

Files changed:

- `app/Utils/BusinessUtil.php`
- `resources/views/business/partials/settings_pos.blade.php`
- `resources/views/sale_pos/create.blade.php`
- `resources/views/sale_pos/edit.blade.php`
- `readme.md`

What changed:

- Added `enable_virtual_keyboard` to the default POS settings with a default value of `0`.
- Added an `Enable virtual keyboard` checkbox in Business Settings > POS.
- Loaded the virtual keyboard Blade partial and JavaScript only when the setting is enabled.
- Added a POS on-screen keyboard with `ABC` and `123` layouts, backspace, clear, space, and enter.
- Added a light/dark theme button on the virtual keyboard toolbar; the selected theme is remembered in the browser.
- Kept the keyboard disabled by default for existing and new businesses.

### Tax Invoice Receipt Design

Purpose: Add a stronger tax-invoice receipt layout that captures tax details clearly and gives printed invoices a more genuine, verifiable feel.

Files added:

- `resources/views/sale_pos/receipts/tax-invoice.blade.php`

Files changed:

- `app/Http/Controllers/InvoiceLayoutController.php`
- `app/Utils/TransactionUtil.php`
- `public/js/app.js`
- `resources/views/invoice_layout/edit.blade.php`
- `readme.md`

What changed:

- Added a new selectable invoice layout design named `Tax Invoice`.
- Based the new design on the existing `columnize-taxes` receipt so it keeps item-level taxable value and per-tax columns.
- Added an `Official Tax Invoice` badge.
- Added a tax invoice verification block with invoice serial, invoice date, business tax IDs, customer tax ID, printed time, verification code, and invoice URL.
- Moved the QR code into the verification area with a `Scan to verify` label.
- Added a stronger tax summary showing net taxable amount, exempt amount, individual taxes, total tax, and gross invoice amount.
- Added receipt data fields for `invoice_url`, `printed_at`, `document_verification_code`, and unformatted total tax.
- Enabled the tax heading inputs for both `columnize-taxes` and `tax-invoice` designs.

How to configure:

1. Open Business Settings > Invoice Settings > Invoice Layouts.
2. Create or edit an invoice layout.
3. Select `Tax Invoice` as the design.
4. Fill the tax column headings, for example `VAT`.
5. Enable business tax numbers, customer tax label, QR code, and invoice URL fields as needed.
6. Assign the layout to a business location for POS or sale invoices.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### Customer Balance On Invoices

Purpose: Show the customer's total outstanding balance on printed invoices and receipts.

Files changed:

- `app/Utils/TransactionUtil.php`
- `resources/views/sale_pos/receipts/english-arabic.blade.php`
- `readme.md`

What changed:

- The receipt payload now always includes the customer's total balance across all sales for finalized sales.
- Existing receipt designs that already render the all-sales balance now show it without requiring the old layout checkbox.
- The English/Arabic receipt design now includes a customer balance row.
- The label still uses the invoice layout's `Total due (all sales)` label when configured, with a `Customer Balance` fallback.
- No migration is needed.

### IntaSend M-PESA Webhook and Reconciliation Holding Pool

Purpose: Add the first implementation phase for the IntaSend M-PESA integration described in `intasend_ultimate_pos_integration.docx`, with location settings, webhook ingestion, duplicate protection, conservative customer matching, and a holding pool for manual reconciliation.

Files added:

- `database/migrations/2026_06_03_000001_create_intasend_integration_tables.php`
- `database/migrations/2026_06_03_000002_add_verification_and_match_fields_to_intasend_tables.php`
- `database/migrations/2026_06_03_000003_add_net_and_charges_to_intasend_payments.php`
- `app/IntaSendSetting.php`
- `app/IntaSendPayment.php`
- `app/Utils/IntaSendUtil.php`
- `app/Http/Controllers/IntaSendController.php`
- `resources/views/intasend/settings.blade.php`
- `resources/views/intasend/pool.blade.php`
- `resources/views/intasend/collections.blade.php`
- `routes/webhooks.php`

Files changed:

- `app/IntaSendSetting.php`
- `app/IntaSendPayment.php`
- `app/Http/Controllers/IntaSendController.php`
- `resources/views/intasend/settings.blade.php`
- `resources/views/intasend/pool.blade.php`
- `resources/views/intasend/collections.blade.php`
- `app/Providers/RouteServiceProvider.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/TransactionUtil.php`
- `resources/views/sale_pos/partials/payment_modal.blade.php`
- `resources/views/sale_pos/partials/payment_type_details.blade.php`
- `public/js/pos.js`
- `public/.htaccess`
- `routes/web.php`
- `app/Http/Middleware/VerifyCsrfToken.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `lang/en/lang_v1.php`
- `readme.md`

Database tables added:

- `intasend_settings`: stores active IntaSend credentials, till/paybill number, and payment link per business location.
- `intasend_payments`: stores every inbound IntaSend payment payload, mapping status, matched customer, attached Ultimate POS payment, and audit fields.

Database fields added later:

- `intasend_settings.webhook_secret`
- `intasend_settings.require_webhook_signature`
- `intasend_payments.match_reason`
- `intasend_payments.match_note`
- `intasend_payments.auto_attached`
- `intasend_payments.net_amount`
- `intasend_payments.charges`
- `intasend_payments.currency`

What changed:

- Added permission `intasend.manage`.
- Added IntaSend to Business Settings > Modules, so each business can enable or disable the integration.
- Added Settings > IntaSend Settings.
- Added Settings > IntaSend Holding Pool.
- Added Settings > IntaSend Collections Report.
- Added middleware-free public webhook endpoint `GET|POST /intasend/webhook` through `routes/webhooks.php`.
- Added CSRF exception for `/intasend/webhook`.
- Webhook supports IntaSend challenge responses. Challenge-only requests echo the challenge, while real `collection_event` callbacks are processed even when IntaSend includes a `challenge` field in the payment payload.
- Webhook responses send no-cache headers, and `public/.htaccess` excludes `/intasend/webhook` from PageSpeed processing to avoid stale cached challenge responses.
- Webhook callbacks for a resolved business are ignored with `status: disabled` when the IntaSend module is disabled for that business.
- Added optional webhook signature verification using a per-location shared secret. The verifier supports common HMAC-SHA256 signature header formats such as `X-IntaSend-Signature`, `X-Webhook-Signature`, and `X-Hub-Signature-256`, including raw-body and timestamp-prefixed HMAC variants.
- Webhook records `COMPLETE` payments, ignores non-complete states such as `PENDING`, and prevents duplicate processing with a unique transaction code.
- Location is resolved from the configured till/paybill number. For sandbox testing where no till/paybill is sent, the resolver falls back to the single active configured IntaSend location.
- Customer is matched first by `api_ref` values like `customer_id_123`, then by normalized phone number.
- Match reason and match note are stored for audit review, including `api_ref`, `phone`, `phone_multiple`, manual, or no-match cases.
- If a payment is complete, location-resolved, and customer-matched, it is attached as a verified `custom_pay_1` / M-PESA customer due payment.
- If it cannot be safely attached, it remains visible in the IntaSend Holding Pool for manual linking.
- POS cashier M-PESA billing can link to IntaSend by transaction code. When a cashier enters the M-PESA/IntaSend code in the POS transaction number field or express M-PESA popup for `custom_pay_1`, the matching COMPLETE IntaSend payment is linked to the selected sale customer, marked attached, and the POS M-PESA payment is marked verified. This works whether the webhook arrives before or after the cashier keys the code.
- The M-PESA express button opens the IntaSend/STK popup. Cashiers can enter the customer's phone number, send a STK prompt for the current POS total, search confirmed M-PESA messages by phone number, transaction code, or matching gross amount, select the M-PESA message, and finalize the payment.
- POS multiple-payment rows now support IntaSend STK Push per M-PESA split line. When a cashier selects `M-PESA` / `custom_pay_1` on any payment row, the row shows a `Send STK Push` button beside the transaction number field.
- The IntaSend popup is row-aware for multiple payments. It uses the selected row's amount, searches collections for that row amount, and writes the selected M-PESA/IntaSend transaction code back to the same payment row instead of always using the first payment row.
- Dynamically added payment rows also receive the IntaSend row action when the IntaSend module is enabled for the business.
- IntaSend gross/net handling is explicit: `intasend_payments.amount` stores the customer-paid gross amount from `value`, while `net_amount`, `charges`, and `currency` store settlement details from IntaSend. Customer dues and POS M-PESA payments use the gross amount; finance reports can compare gross collected, charges, and net received.
- POS transaction-code linking trusts the unique M-PESA/IntaSend code and does not require the POS gross amount to equal the IntaSend `net_amount`, because IntaSend may send `value` as the paid amount and `net_amount` after charges.
- POS STK Push now carries the configured branch till/paybill in the outgoing `api_ref`, common till fields, and metadata. If IntaSend rejects the extended STK payload with a validation error, the sender retries once with the previous minimal payload so cashier checkout is not blocked by unsupported optional fields.
- Webhook normalization accepts the till/paybill from common direct fields such as `till_identifier`, `till_number`, `paybill_number`, `business_shortcode`, and matching nested `metadata`/`data` fields. It can also recover the branch till/paybill from POS-generated `api_ref` values like `..._till_4012345...`.
- Manual linking creates an Ultimate POS customer due payment, distributes it to unpaid sales/opening balance through the existing `payAtOnce()` utility, and posts to the configured M-PESA account when account module/payment account settings allow it.
- Explicitly set model table names to `intasend_settings` and `intasend_payments` so Laravel does not infer `inta_send_settings` / `inta_send_payments`.
- Added a migration-required warning on IntaSend screens so missing tables show a clear message instead of a 500 error.
- IntaSend Settings uses the existing `business.is_active` language label for the Active checkbox.
- Register integration decision for this phase: IntaSend payments remain a back-office/customer-ledger collection workflow and do not create cashier register transactions. This avoids assigning webhook payments to the wrong cashier or shift when payments arrive without an active POS register.

Important limitations:

- The exact IntaSend signing header must still be confirmed against the live IntaSend account configuration before enabling `Require webhook signature` in production.
- Auto-matching is intentionally conservative. Payments without a resolved location or customer stay in the holding pool.
- The current implementation applies payments to customer due balances, not to a cashier's active POS register session.
- `PENDING` IntaSend callbacks stay ignored. A later `COMPLETE` callback with the same final M-PESA reference is required before the system can link, attach, or verify the collection.
- Sandbox environments may not expose a till/paybill number. Keep only one active configured IntaSend location during sandbox testing, or add real till/paybill mapping before testing multiple locations.
- POS STK Push requires an IntaSend secret key on the active location setting. The modal search can only select payments after IntaSend has sent a `COMPLETE` webhook into the local holding pool.
- The exact official IntaSend STK field for choosing a merchant till/paybill should still be confirmed with IntaSend. The code sends likely fields and metadata, then falls back to the original minimal payload on validation failure.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

Setup:

1. Run migrations.
2. Open Business Settings > Modules and enable `IntaSend Integration`.
3. Grant `Manage IntaSend Settings` to users who configure credentials/webhooks and `View IntaSend Transactions` to users who reconcile collections. Existing roles with the legacy `Manage IntaSend` permission receive both automatically during migration.
4. Open Settings > IntaSend Settings.
5. For each live location, enter the till/paybill number exactly as IntaSend sends it in the webhook payload. In sandbox, this can be left blank only if there is exactly one active configured IntaSend location.
6. Optionally enter a webhook secret and enable `Require webhook signature` after confirming the signing header with IntaSend.
7. Map each location's `custom_pay_1` payment account to the correct M-PESA/mobile money account.
8. Configure IntaSend to send webhooks to `/intasend/webhook`.
9. Review unmatched payments from Settings > IntaSend Holding Pool.
10. Review completed collections from Settings > IntaSend Collections Report.
11. For POS cashier linking, select the M-PESA payment method and enter the final M-PESA/IntaSend transaction code in the transaction number field. The code must match a COMPLETE IntaSend collection.
12. For POS STK Push from the M-PESA button, enter the customer's phone number in the M-PESA popup, send STK, wait for the customer to approve, search M-PESA messages, select the confirmed message, then finalize the POS payment.
13. For multiple payments, add the required payment rows, set the M-PESA row amount, choose M-PESA on that row, click `Send STK Push` beside that row's transaction number field, select the confirmed M-PESA message, and continue finalizing the sale after all split rows are correct.

### POS Inactivity Lock By Business Location

Purpose: Allow each business location to lock unattended POS screens after a configured period of inactivity without destroying the current session, cart, register context, or selected location.

Files added:

- `database/migrations/2026_06_18_000001_add_pos_inactivity_logout_minutes_to_business_locations_table.php`

Files changed:

- `app/BusinessLocation.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/BusinessLocationController.php`
- `app/Http/Controllers/Install/InstallController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/InstallUtil.php`
- `public/js/pos.js`
- `resources/views/business_location/create.blade.php`
- `resources/views/business_location/edit.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/sale_pos/create.blade.php`
- `resources/views/sale_pos/edit.blade.php`
- `routes/web.php`
- `readme.md`

Database field added to `business_locations`:

- `pos_inactivity_logout_minutes`: unsigned small integer, default `0`.

What changed:

- Business Location create/edit now includes `POS inactivity lock (minutes)`.
- `0` disables automatic POS locking for that location.
- Values are validated server-side as integers from `0` to `65535`.
- The browser input also uses `min="0"`, `max="65535"`, and whole-minute steps.
- POS pages receive the selected location's timeout value through `data-pos_inactivity_logout_minutes`.
- POS pages also receive `data-enable_numeric_login` so the lock screen can prefer PIN unlock for numeric-login locations.
- When a cashier changes the selected POS location, the inactivity timer refreshes to use the new location setting.
- POS layout pages show a full-screen lock overlay after the configured inactivity period.
- POS header includes a `Lock POS` button for immediate manual locking without waiting for inactivity.
- Lock state is stored in browser `sessionStorage`, so refreshing the POS page keeps the screen locked until the current user unlocks or logs out.
- The locked POS screen can be unlocked with the current user's PIN when numeric login is enabled for the location.
- The locked POS screen can also be unlocked with the current user's password.
- Unlock is verified server-side through `POST /pos/unlock`.
- PIN unlock verifies only the currently authenticated user's staff PIN. It does not switch the session to another user.
- A `Log out` button remains available from the lock screen for shift changes or deliberate sign-out.
- Manual lock works even when the automatic inactivity lock is disabled with `0`.
- Manual logout still redirects to `/login?location_id={location_id}` so the login page can preselect the same location.
- `InstallUtil::addPosInactivityLogoutMinutesToBusinessLocations()` was added as an idempotent fallback for updater flows. The normal updater already runs `php artisan migrate --force`, so the migration remains the primary upgrade path.

Setup:

1. Run migrations.
2. Open Business Settings > Business Locations.
3. Edit the target location.
4. Set `POS inactivity lock (minutes)` to the desired timeout.
5. Use `0` for locations where automatic lock should stay disabled.
6. For PIN unlock, enable `Enable Numeric Login` on the location and make sure the current cashier user has a staff PIN.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### Customer Balance On Invoices By Location

Purpose: Let selected business locations append a registered customer's current balance to sale invoices while keeping walk-in customer invoices clean.

Files changed:

- `app/BusinessLocation.php`
- `app/Http/Controllers/BusinessLocationController.php`
- `app/Http/Controllers/Install/InstallController.php`
- `app/Utils/InstallUtil.php`
- `app/Utils/TransactionUtil.php`
- `database/migrations/2026_06_20_000001_add_show_customer_balance_on_invoice_to_business_locations_table.php`
- `resources/views/business_location/create.blade.php`
- `resources/views/business_location/edit.blade.php`
- `readme.md`

Database field added to `business_locations`:

- `show_customer_balance_on_invoice`: boolean, default `0`.

What changed:

- Business Location create/edit now includes `Show customer balance on invoices`.
- When enabled for a location, sale invoices can show the customer's current balance using the existing `Customer Balance`/`all_due` receipt rows.
- Walk-in customers are excluded by checking `contacts.is_default`, even when the location setting is enabled.
- Existing invoice-layout previous-balance behavior remains independent of this location switch.
- `InstallUtil::addShowCustomerBalanceOnInvoiceToBusinessLocations()` was added as an idempotent fallback for updater flows. The normal updater still runs `php artisan migrate --force`, so the migration remains the primary upgrade path.

Setup:

1. Run migrations.
2. Open Business Settings > Business Locations.
3. Edit the target location.
4. Enable `Show customer balance on invoices`.
5. Save and print a sale invoice for a registered customer.

Server action:

```bash
php artisan migrate
php artisan optimize:clear
```

### Log Triage Fixes For Recurring Errors

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

### Direct Safaricom Daraja M-PESA Integration

Purpose: Provide a direct Safaricom M-PESA alternative to IntaSend for businesses that do not want a third-party payment provider. IntaSend remains available and both providers use the existing `custom_pay_1` M-PESA accounting and verification workflow.

Files added:

- `app/DarajaPayment.php`
- `app/DarajaSetting.php`
- `app/Http/Controllers/DarajaController.php`
- `app/Utils/DarajaUtil.php`
- `app/Utils/MpesaVerificationUtil.php`
- `database/migrations/2026_07_02_000002_create_daraja_integration_tables.php`
- `database/migrations/2026_07_02_000003_add_callback_token_to_daraja_settings.php`
- `database/migrations/2026_07_03_000001_add_payment_provider_settings_and_transaction_permissions.php`
- `resources/views/daraja/settings.blade.php`
- `resources/views/daraja/transactions.blade.php`

Main files updated:

- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Utils/ModuleUtil.php`
- `app/Utils/TransactionUtil.php`
- `config/constants.php`
- `lang/en/lang_v1.php`
- `public/js/pos.js`
- `public/service-worker.js`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `resources/views/sale_pos/partials/payment_modal.blade.php`
- `resources/views/sale_pos/partials/payment_type_details.blade.php`
- `routes/web.php`
- `routes/webhooks.php`

Phase 1 - Direct STK Push:

- Added per-location sandbox or production Daraja settings.
- Added encrypted-at-rest consumer key, consumer secret, and Lipa na M-PESA passkey fields.
- Added support for Paybill (`CustomerPayBillOnline`) and Buy Goods/Till (`CustomerBuyGoodsOnline`).
- Added Daraja OAuth token generation and short-lived token caching.
- Added direct STK Push through Safaricom's sandbox or production endpoint.
- Each accepted STK request is saved before waiting for its asynchronous callback.
- Successful callbacks are matched to an initiated request using `CheckoutRequestID`.
- Callback metadata stores the M-PESA receipt number, actual amount, phone number, transaction date, and result details.
- Callback URLs contain a random per-location token. The system verifies this token and the stored `CheckoutRequestID` before accepting an STK result.
- POS searches can find successful, unattached Daraja payments using receipt number, phone number, or amount.
- Exact receipt number and amount matching verifies the existing `custom_pay_1` transaction payment.
- Verified Daraja payments reuse the existing M-PESA audit trail, cash-register totals, payment account posting, and manual verification controls.
- Late callbacks can verify and post a POS payment that was initially held as pending.

Phase 2 - C2B Manual Payments:

- Added C2B validation and confirmation callback endpoints.
- Added a `Register C2B URLs` action on each saved Daraja location setting.
- Manual Paybill/Till confirmations are stored in the same Daraja transaction pool with source `c2b`.
- Duplicate confirmations are idempotent by unique M-PESA transaction code.
- The system conservatively matches customers using a `customer_id` account reference or a unique exact phone-number match.
- A matched C2B collection can automatically pay an outstanding customer sale/opening balance.
- Unmatched collections remain in the Daraja transaction pool for manual customer attachment.

Database and permission setup:

```bash
php artisan migrate
php artisan optimize:clear
```

The migrations create:

- `daraja_settings`
- `daraja_payments`
- Granular provider permissions: `intasend.settings`, `intasend.transactions`, `daraja.settings`, and `daraja.transactions`
- Existing `intasend.manage` and `daraja.manage` roles are migrated to both corresponding granular permissions for backward compatibility.

Enable and assign Daraja:

1. Go to Business Settings and enable `Direct M-PESA Integration` under enabled modules.
2. Go to User Management > Roles.
3. Enable `Manage M-PESA Settings` for users who configure credentials and callback URLs.
4. Enable `View M-PESA Transactions` for users who view, filter, and reconcile payments.
5. Sign out and back in to refresh enabled modules and permissions.
6. Open Settings > M-PESA Settings.

Per-location Daraja settings:

1. Select `Sandbox` while testing and `Production` only after Safaricom approves the live app.
2. Enter the Daraja consumer key and consumer secret.
3. Enter the Paybill or Till shortcode used for M-PESA Express/STK Push.
4. Enter the separate C2B shortcode assigned to the Daraja app. In Sandbox this is commonly `600000`, while the STK test shortcode is `174379`; use the values shown in the Safaricom test credentials. In Production, the two values may be the same merchant shortcode.
5. Enter the Lipa na M-PESA Online passkey.
6. Select Paybill or Buy Goods/Till transaction type.
7. Enter an account reference of at most 12 characters.
8. Keep the location active and save.

Windows/WAMP HTTPS certificate setup:

- If PHP reports `cURL error 60` or an SSL certificate-chain error, set `DARAJA_CA_BUNDLE` in `.env` to a current, readable CA bundle. Example: `DARAJA_CA_BUNDLE="C:/wamp64/bin/php/php8.4.15/extras/ssl/cacert.pem"`.
- Run `php artisan config:clear` after changing the value. Do not disable SSL verification.
- The CA bundle is applied only to direct Safaricom API requests. Invalid or unreadable paths now produce a specific configuration error.
- New locations are inactive by default. An active location must have a consumer key, consumer secret, shortcode, and passkey; blank location panels are no longer saved as active settings.

Callback requirements:

- Production callbacks must be publicly reachable over HTTPS. `localhost`, private LAN addresses, and self-signed certificates cannot receive Safaricom callbacks.
- If the application's configured `APP_URL` is already the public HTTPS URL, callback fields may remain blank and the generated URLs shown below each field will be used.
- If the application is behind a proxy or uses a different public hostname, enter the public STK, confirmation, and validation URLs in the corresponding fields.
- Do not remove the generated `token` query parameter. It protects the callback route from accepting arbitrary public requests.
- After saving a setting, click `Register C2B URLs` to submit the confirmation and validation URLs to Safaricom.
- C2B registration now reports Safaricom's returned error message and logs the HTTP status/response for troubleshooting. Secrets are not included in those logs.
- Safaricom Sandbox may return HTTP 500 with `Duplicate notification info` when the shortcode is already registered. The application treats this specific response as a successful, idempotent registration.
- Confirm C2B URL registration rules for the merchant shortcode in the Safaricom Daraja portal before production activation.

POS workflow:

1. Select M-PESA (`custom_pay_1`) on express checkout or a multiple-payment row.
2. Open M-PESA Tools.
3. Enter the customer's phone number and amount.
4. Click `Send M-PESA STK Push`. Daraja remains the technical provider name on the administrator settings screens, while the POS uses the customer-facing M-PESA name.
5. Ask the customer to approve the prompt on their phone.
6. Click `Search M-PESA Payments` and select the successful M-PESA receipt.
7. Finalize the sale. The receipt and exact amount are verified against `daraja_payments`.

Transaction reconciliation:

- Open Settings > M-PESA Transactions.
- Filter by location, source (`stk` or `c2b`), or status.
- Successful unmatched payments can be linked manually to a customer.
- `PENDING` means Safaricom has accepted the STK request but no callback has completed it.
- `COMPLETE` means a successful callback with an M-PESA receipt was received.
- `FAILED` includes rejected, cancelled, timed-out, or otherwise unsuccessful STK requests.

Security and operational notes:

- Daraja credentials are encrypted using the application's `APP_KEY`. Back up this key; changing it prevents stored credentials from being decrypted.
- The application never marks an STK collection successful from the initial API response. Only the callback completes it.
- The callback amount must equal the POS payment amount before automatic verification.
- Safaricom callback endpoints do not use the normal authenticated web middleware, so they rely on the random callback token, setting ID, shortcode checks, request IDs, and unique transaction codes.
- Do not enable both sandbox and production credentials on the same location simultaneously; each location has one active environment.
- IntaSend and Daraja can coexist. The POS modal shows controls for each enabled provider.
- On 3 July 2026, the configured sandbox credentials successfully completed Safaricom OAuth after the WAMP CA bundle was configured. A C2B registration test reached Safaricom but Sandbox returned HTTP 500 / `500.003.1001` (`Service is currently unreachable. Please try again later.`); this is a Safaricom service response and can be retried from M-PESA Settings. Callback parsing and C2B idempotency were also verified with rollback-only database smoke tests.
- Official onboarding, sandbox apps, and production approval are managed through the [Safaricom Daraja portal](https://developer.safaricom.co.ke/).

### POS Open Cash Drawer Button

Purpose: Allow a cashier to open a printer-connected cash drawer without completing a sale or printing a receipt.

Files changed:

- `config/constants.php`
- `database/migrations/2026_07_02_000001_add_open_cash_drawer_permission.php`
- `lang/en/lang_v1.php`
- `resources/views/sale_pos/partials/pos_form_actions.blade.php`
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `public/js/printer.js`
- `public/js/pos.js`
- `public/service-worker.js`
- `readme.md`

What changed:

- Added a green `Open Drawer` button immediately to the left of `Recent Transactions` on the desktop POS action bar.
- Added the `open_cash_drawer` role permission. Only users with this permission, administrators, and super administrators see the button.
- Added `Open cash drawer from POS` to the role create/edit screens under the sales/POS permissions.
- The two buttons use a compact, non-collapsing inline action group so the drawer button remains visible without hiding the payment buttons or `Total Payable`.
- Added explicit inline visibility and sizing styles to protect the button from stale or incomplete Tailwind utility builds.
- The button connects to the existing local printer WebSocket service at `ws://127.0.0.1:6441`.
- It sends the following message to the printer connector:

```json
{"type":"open-cashdrawer","printer_config":{"connection_type":"windows","path":"smb://localhost/receipt_printer"}}
```

- The button is temporarily disabled while the command is being sent.
- A success notification is displayed after the command is delivered to the local connector.
- If the connector cannot be reached, the cashier sees `POS Print Server is not running on this computer (port 6441). Start it and try again.`
- Increased `config('constants.asset_version')` from `613` to `615` so browsers request the updated POS and printer/Daraja JavaScript files.
- Increased the PWA cache name from `sysnettechs-pos-pwa-v1` to `sysnettechs-pos-pwa-v3`, which removes previous cached JavaScript/CSS assets when the new service worker activates.

Hardware and connector requirements:

1. Connect the cash drawer to the receipt printer's drawer/DK port using the correct RJ11/RJ12 drawer cable.
2. Keep the receipt printer powered on and correctly configured on the POS computer.
3. Start the Ultimate POS printer connector and confirm that it listens on port `6441`.
4. The installed connector must support the `open-cash-drawer` message and send the appropriate drawer-kick command to the configured receipt printer.
5. A drawer connected directly by USB or serial requires compatible connector support and may not work with this printer-driven command.

Troubleshooting:

- The local URL configured for this installation is `http://localhost/UltimatePOSV6.12/public`.
- If the button is missing, run `php artisan optimize:clear`, open the configured local URL, and reload twice with `Ctrl+F5`. Two reloads may be needed for the updated service worker to activate and clear its old cache.
- Confirm the browser is loading this exact project folder. The local Apache configuration currently contains duplicate `UltimatePOS.local` virtual hosts pointing to `c:/wamp/www/...`, while this project is stored under `C:/wamp64/www/UltimatePOSV6.12`. Changes made here will not appear if the browser is serving another Ultimate POS copy.
- If using `UltimatePOS.local`, correct its Apache `DocumentRoot` and matching `<Directory>` path to `C:/wamp64/www/UltimatePOSV6.12`, remove the duplicate host entry, and restart Apache.
- If `Printer connector is not available` appears, start or restart the local printer connector.
- The cash-drawer command uses the official POS Print Server message name `open-cashdrawer` and includes the receipt-printer configuration assigned to the current business location.
- If the success message appears but the drawer stays closed, check that the connector supports `open-cash-drawer`, the drawer cable is connected to the printer's drawer port, and the drawer is physically unlocked.
- Confirm normal receipt printing works from the same POS computer before testing the drawer.

Role setup:

1. Run the migration to create the `open_cash_drawer` permission.
2. Go to User Management > Roles.
3. Create or edit the required cashier role.
4. Enable `Open cash drawer from POS` and save the role.
5. Sign the cashier out and back in so the refreshed role permissions are applied.

Server action:

```bash
php artisan optimize:clear
```

No migration is needed.

### Mixed Retail and Wholesale Prices on One POS Sale (2026-07-14)

Purpose: Allow the same customer invoice to contain products added from different selling price groups, including separate Retail and Wholesale lines for the same product.

Files changed:

- `public/js/pos.js`
- `app/Utils/ProductUtil.php`
- `resources/views/sale_pos/product_row.blade.php`
- `config/constants.php`
- `readme.md`

What changed:

- Each POS product row now retains the selling price group that was active when the item was added.
- The duplicate-item check now compares both the product variation and its selling price group.
- Adding the same product again with the same price group continues to increase its quantity.
- Adding the same product under a different price group creates a separate row with that group's price.
- Both barcode/search auto-add and normal POS product selection use the same mixed-price behavior.
- Existing line prices are not changed when the cashier selects another price group; the new group applies to subsequently added items.
- Increased the asset version from `616` to `617` so cashier browsers request the updated POS JavaScript.
- No database migration is required because the calculated unit price is already stored on each sale line.

Cashier workflow:

1. Select `Retail` in the POS selling-price-group field and add the retail items.
2. Select `Wholesale` and add the wholesale items.
3. If the same product is sold at both prices, it appears as two separate invoice lines.
4. Adding it again under the same group increments only the matching line.

Server action:

```bash
php artisan optimize:clear
```

After deployment, force-refresh the POS page so the browser loads the updated `public/js/pos.js`.

### Damage Management Installation Detection Fix (2026-07-14)

Purpose: Correct a version-key case mismatch that caused the installed Damage Management module to be omitted from module hooks and menus.

Files changed:

- `Modules/DamageManagement/Http/Controllers/InstallController.php`
- `Modules/DamageManagement/Http/Controllers/DataController.php`
- `readme.md`

What changed:

- Standardized the module version key as `damagemanagement_version`.
- Updated install, update, uninstall, and menu checks to use the same lowercase key expected by the application's module loader.
- Normalized the existing installation's `system` table key from `DamageManagement_version` to `damagemanagement_version`.
- Confirmed that the Damage Management tables and schema changes were already installed.

Existing installations may normalize the key with:

```sql
UPDATE `system`
SET `key` = 'damagemanagement_version'
WHERE `key` = 'DamageManagement_version';
```

Back up the database before running SQL on another installation. New installations use the corrected key automatically.

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

### Operational Recommendations

1. Keep this README as the source of truth for implemented customizations. When adding a migration, permission, route, or business workflow, document the changed files, required server action, and any setup steps in the same change section.
2. Treat `intasend_ultimate_pos_integration.docx` as the original integration specification. The repo now includes the first implementation phase: webhook ingestion, IntaSend tables, location settings, conservative customer matching, and a reconciliation holding pool.
3. Keep IntaSend as its own project phase for future expansion because it affects webhook security, duplicate transaction handling, multi-location till mapping, customer ledger settlement, account posting, and manual reconciliation.
4. Add live IntaSend webhook signature verification after confirming the exact signing headers and secret validation method for the merchant account.
5. Keep the manual M-PESA verification workflow under regression testing whenever payments, registers, account transactions, or reports are changed. Pending and rejected M-PESA payments must not increase linked account balances at locations where manual verification is enabled.
6. Keep the Kenya Tax Administration feature clearly labelled as internal reporting and manual eTIMS tracking. It does not automatically submit invoices to KRA eTIMS.
7. Keep PWA expectations modest. The current PWA only supports installability and static asset caching; it does not support offline selling, stock updates, payment capture, or report syncing.
8. Confirm `.env` remains untracked in Git before deployment or handoff. Store live credentials in the server environment, not in committed project files.

### Migration Safety Note

The added migrations create permissions and small nullable columns, such as tenant login branding fields. They should not delete existing sales, products, customers, stock, or payment data. Always back up the live database before running migrations.
