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

Purpose: Keep the existing login fields but display a left-side image panel like the supplied reference.

Files added:

- `public/img/login-side.jpg`
- `database/migrations/2026_05_15_000003_add_login_image_to_business_locations.php`

Files changed:

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
- Text on the login image now appears only when the current domain matches a Business Location `Website`; it displays that Business Location name only.
- Business Location create/edit now includes a `Sign in page image` upload field.
- If a matched Business Location has its own uploaded sign-in image, login uses that image instead of the default `public/img/login-side.jpg`.
- Business Location modal submit now supports file upload.
- Updated `public/img/login-side.jpg` using the supplied Sysnettechs POS image.
- Right side keeps the current login form, language selector, and original blue gradient background.
- Login card is wider on desktop and uses responsive padding for mobile.
- Desktop login page height is locked to the viewport to avoid page scrolling; mobile can still scroll when needed.
- On tablet/mobile, the image panel is hidden and the login form remains full width.

How to change the image:

1. Upload your preferred image as `public/img/login-side.jpg`.
2. Keep the same file name to avoid editing code.
3. Use a wide image, ideally 1200px or wider.
4. Run `php artisan optimize:clear`.
5. Hard-refresh the browser if the old image is cached.

How to show the business location name on the image:

1. Open the Business Location.
2. Set the `Website` field to the domain/subdomain used by that client, for example `shop.co.ke` or `shop.sysnettechs.co.ke`.
3. When that domain opens the login page, the image text will show only that Business Location name.

How to let each location upload its own image:

1. Run `php artisan migrate`.
2. Open Business Settings > Business Locations.
3. Edit the location.
4. Upload an image in `Sign in page image`.
5. Save the location.
6. Make sure the location `Website` matches the login domain/subdomain.

### Recommended Online Deployment Steps

1. Upload all changed controller files.
2. Upload all changed Blade view files.
3. Upload changed language files.
4. Upload changed `routes/web.php`.
5. Upload new migration files.
6. Back up the live database from cPanel or phpMyAdmin.
7. Run migrations only if uploading features that added permissions.

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

The added migrations only create permissions. They should not delete existing sales, products, customers, stock, or payment data. Always back up the live database before running migrations.
