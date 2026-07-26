# Inventory and Reporting

Location stock controls, stock sheets, operational reporting, and inventory-related module fixes.

Return to the [documentation index](../../readme.md) or [customizations index](../CUSTOMIZATIONS.md).

## Contents

- [4. Stock Reset by Location with Admin Password](#4-stock-reset-by-location-with-admin-password)
- [16. Stock Sheet Report](#16-stock-sheet-report)
- [Damage Management Installation Detection Fix (2026-07-14)](#damage-management-installation-detection-fix-2026-07-14)

---

<a id="4-stock-reset-by-location-with-admin-password"></a>
## 4. Stock Reset by Location with Admin Password

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


---

<a id="16-stock-sheet-report"></a>
## 16. Stock Sheet Report

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


---

<a id="damage-management-installation-detection-fix-2026-07-14"></a>
## Damage Management Installation Detection Fix (2026-07-14)

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
