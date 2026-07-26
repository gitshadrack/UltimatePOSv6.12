# Invoices and Tax

Invoice presentation, customer balances, Kenya tax reporting, and location-aware invoice calculations.

Return to the [documentation index](../../readme.md) or [customizations index](../CUSTOMIZATIONS.md).

## Contents

- [17. Kenya Tax Administration Sidebar](#17-kenya-tax-administration-sidebar)
- [Tax Invoice Receipt Design](#tax-invoice-receipt-design)
- [Customer Balance On Invoices](#customer-balance-on-invoices)
- [Customer Balance On Invoices By Location](#customer-balance-on-invoices-by-location)

---

<a id="17-kenya-tax-administration-sidebar"></a>
## 17. Kenya Tax Administration Sidebar

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


---

<a id="tax-invoice-receipt-design"></a>
## Tax Invoice Receipt Design

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


---

<a id="customer-balance-on-invoices"></a>
## Customer Balance On Invoices

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


---

<a id="customer-balance-on-invoices-by-location"></a>
## Customer Balance On Invoices By Location

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
