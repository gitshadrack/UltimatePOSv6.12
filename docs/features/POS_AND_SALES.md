# POS and Sales

Register access, POS selling behaviour, pricing, usability, printing, PWA, customer display, and cashier controls.

Return to the [documentation index](../../readme.md) or [customizations index](../CUSTOMIZATIONS.md).

## Contents

- [3. Cash Register: View and Close Own Register Only](#3-cash-register-view-and-close-own-register-only)
- [5. Product Sales Tab Inside Sell > All Sales](#5-product-sales-tab-inside-sell-all-sales)
- [6. Embedded Ultimate POS Guide Under Reports](#6-embedded-ultimate-pos-guide-under-reports)
- [7. Monthly Total Sales Card on Home Dashboard](#7-monthly-total-sales-card-on-home-dashboard)
- [8. Separate Import Sales Permission](#8-separate-import-sales-permission)
- [9. Basic PWA Installation Support](#9-basic-pwa-installation-support)
- [10. Lot-Based Selling Price For POS](#10-lot-based-selling-price-for-pos)
- [POS Default Purchase Price Visibility Permission](#pos-default-purchase-price-visibility-permission)
- [POS Virtual Keyboard Toggle](#pos-virtual-keyboard-toggle)
- [POS Inactivity Lock By Business Location](#pos-inactivity-lock-by-business-location)
- [Automatic Windows POS Print Server](#automatic-windows-pos-print-server)
- [POS Open Cash Drawer Button](#pos-open-cash-drawer-button)
- [Mixed Customer Group Prices on POS (Updated 2026-07-15)](#mixed-customer-group-prices-on-pos-updated-2026-07-15)

---

<a id="3-cash-register-view-and-close-own-register-only"></a>
## 3. Cash Register: View and Close Own Register Only

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


---

<a id="5-product-sales-tab-inside-sell-all-sales"></a>
## 5. Product Sales Tab Inside Sell > All Sales

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


---

<a id="6-embedded-ultimate-pos-guide-under-reports"></a>
## 6. Embedded Ultimate POS Guide Under Reports

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


---

<a id="7-monthly-total-sales-card-on-home-dashboard"></a>
## 7. Monthly Total Sales Card on Home Dashboard

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


---

<a id="8-separate-import-sales-permission"></a>
## 8. Separate Import Sales Permission

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


---

<a id="9-basic-pwa-installation-support"></a>
## 9. Basic PWA Installation Support

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


---

<a id="10-lot-based-selling-price-for-pos"></a>
## 10. Lot-Based Selling Price For POS

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


---

<a id="pos-default-purchase-price-visibility-permission"></a>
## POS Default Purchase Price Visibility Permission

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


---

<a id="pos-virtual-keyboard-toggle"></a>
## POS Virtual Keyboard Toggle

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


---

<a id="pos-inactivity-lock-by-business-location"></a>
## POS Inactivity Lock By Business Location

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


---

<a id="automatic-windows-pos-print-server"></a>
## Automatic Windows POS Print Server

Purpose: Allow non-technical cashiers to use configured ESC/POS receipt
printers without opening a terminal or manually running `php server.php`.

Implementation:

- Deployment source: `tools/windows-print-server`
- Preferred technician installer:
  `tools/windows-print-server/artifacts/UltimatePOS-PrintServer-Setup.exe`
- Fallback manual package:
  `tools/windows-print-server/artifacts/UltimatePOS-PrintServer.zip`
- Installation directory:
  `%LOCALAPPDATA%\UltimatePOS\PrintServer`
- Automatic task name: `UltimatePOS Print Server`
- Local endpoint: `ws://127.0.0.1:6441`
- Log directory:
  `%LOCALAPPDATA%\UltimatePOS\PrintServer\logs`

The setup EXE includes the official UltimatePOS POS Print Server and a portable
PHP runtime. A technician runs the EXE once while signed in as the Windows user
used by the cashier.
The installer registers an automatic per-user Scheduled Task, starts the
server immediately, verifies a WebSocket connection, and adds Test and
Uninstall shortcuts under the Windows Start menu.

Cashiers only open UltimatePOS normally. No WAMP/XAMPP window, PowerShell
command, or daily printer-server action is required.

Authorized users can open **Settings > Business Settings > System > Open
System Downloads** and download the current
`UltimatePOS-PrintServer-Setup.exe`. The page serves the exact versioned
repository artifact, shows its version, size, SHA-256 checksum, and provides
the repository link as a fallback. This removes the need to distribute updates
using a flash disk.

Build the technician ZIP:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File .\tools\windows-print-server\Build-DeploymentPackage.ps1 `
  -PrintServerSource "C:\path\to\pos_print_server" `
  -PhpRuntimeDirectory "C:\path\to\portable-php"
```

Quick configuration:

1. Install the printer driver and confirm a Windows test page prints.
2. Share the working USB printer queue using a simple share name such as
   `ReceiptPrinter`.
3. Run `UltimatePOS-PrintServer-Setup.exe` as the cashier Windows user.
4. Confirm the installer reports that the server is ready.
5. In UltimatePOS, open Settings > Receipt Printers and create the printer:
   Connection Type `Windows`, Path
   `smb://localhost/ReceiptPrinter`, and the
   correct characters per line (`32` for most 58 mm printers or `42/48` for
   most 80 mm printers).
6. Open Settings > Business Locations > Settings > Receipt Settings, select
   `Use Configured Receipt Printer`, choose the printer, enable printing on
   invoice, and save.
7. Complete one test sale and test the Open Drawer button if a cash drawer is
   connected.

Printer connection rules:

- For printers shown on Windows ports such as `USB001` or `USB003`, share the
  working Windows printer queue and use
  `smb://localhost/ReceiptPrinter`. Do not use the USB port name as the path.
- Direct `LPT1`-`LPT9` and `COM1`-`COM9` paths are supported only when the
  printer is genuinely connected or mapped to that local port.
- For a raw TCP ESC/POS network printer, select Connection Type `Network`,
  enter its static/reserved IP address and its raw-printing port (commonly
  `9100`), and leave Path blank.
- If a network printer uses a Windows/WSD driver rather than raw TCP, print a
  Windows test page, share that working queue, and use the Windows SMB path.

How to confirm it is running:

1. Open Windows Start > UltimatePOS > Test Print Server. A healthy installation
   displays `UltimatePOS Print Server is READY on ws://127.0.0.1:6441`.
2. On the POS screen, confirm the printer indicator changes to
   `Printer ready`. A red `Printer offline` indicator can be clicked to
   retry.
3. A technician can verify the background task and listening port with:

```powershell
Get-ScheduledTask -TaskName "UltimatePOS Print Server" |
  Select-Object TaskName, State

Get-NetTCPConnection -State Listen -LocalPort 6441
```

The task should show `Running`, and port `6441` should show `Listen`. If either
check fails, sign out and back in or rerun the installer. Review errors under
`%LOCALAPPDATA%\UltimatePOS\PrintServer\logs`.

The POS now displays `Connecting to printer`, `Printer ready`,
`Receipt queued`, `Printing`, `Receipt printed`, or a red offline warning. Clicking the
offline warning retries the connection. Receipt printing no longer attempts
`socket.send()` before the WebSocket is ready, and failures produce a
cashier-readable notification.

Configured-printer receipts are persisted under
`%LOCALAPPDATA%\UltimatePOS\PrintServer\queue` before acknowledgment. A hidden
worker automatically retries printer failures with increasing delays, up to
five minutes between attempts. Successful jobs discard the receipt payload and
retain status metadata for 48 hours. Pending jobs expire after 24 hours to
prevent stale receipts printing unexpectedly. Open Drawer commands are
live-only and are never queued.

The agent binds only to loopback (`127.0.0.1`). Do not expose port `6441` in
the router or to the public internet.

Verified on 26 July 2026:

- Portable PHP 8.2 and the downloaded official print-server payload started.
- The automatic Scheduled Task started successfully.
- A real WebSocket handshake to `ws://127.0.0.1:6441` succeeded.
- Logs were written correctly.
- Uninstall stopped the listener, removed the task, and removed installed
  files.
- A physical receipt test remains required on a computer with an installed
  thermal printer.

The complete configuration, installation, status, offline queue, update,
cash-drawer, and troubleshooting guide is maintained in
`tools/windows-print-server/README.md`.


---

<a id="pos-open-cash-drawer-button"></a>
## POS Open Cash Drawer Button

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


---

<a id="mixed-customer-group-prices-on-pos-updated-2026-07-15"></a>
## Mixed Customer Group Prices on POS (Updated 2026-07-15)

Purpose: Allow the same product to be added as separate Retail, Wholesale, Family, or other selling-price-group lines instead of incorrectly increasing the quantity of a line created under a different price group.

Files changed:

- `public/js/pos.js`
- `app/Utils/ProductUtil.php`
- `resources/views/sale_pos/product_row.blade.php`
- `config/constants.php`
- `readme.md`

What changed:

- Each POS product row now retains the selling price group that was active when the item was added.
- The duplicate-item check now compares both the product variation and its selling price group.
- Selecting a customer whose customer group uses `Selling Price Group` changes the active price used for subsequently added items.
- Existing lines retain the price and selling price group under which they were added.
- Switching from a selling-price customer group to a percentage/default group resets the previous customer's selling price group instead of retaining it.
- Product selection now requests the priced row from the server before performing duplicate detection.
- Duplicate detection uses the selling price group embedded in the server-generated row, rather than relying only on the browser's current dropdown value.
- Adding the same product again only increases the quantity of a row whose product variation and server-confirmed price group both match.
- Adding the same product after selecting a different price group creates a separate line at that group's price.
- Both barcode/search auto-add and normal POS product selection use the same price-group-aware duplicate check.
- Increased the asset version from `618` to `619` so cashier browsers request the corrected POS JavaScript.
- No database migration is required because the calculated unit price is already stored on each sale line.

Required setup:

1. Create the required groups under Selling Price Groups, such as `Retail`, `Wholesale`, and `Family`.
2. Open each product and save its price for every selling price group.
3. Create or edit the corresponding customer group.
4. Set `Price calculation type` to `Selling Price Group` and select the matching selling price group. Alternatively, select `Percentage` and enter the applicable percentage.
5. Assign each customer to the correct customer group.
6. Ensure the cashier's role can access the required selling price groups.

Cashier workflow:

1. Select the Retail customer or price group and add a product.
2. Select the Wholesale, Family, or another configured customer/price group.
3. Add the same product again and confirm that it appears on a separate line at the newly selected price.
4. Add it once more without changing the group and confirm that only the matching line's quantity increases.

Server action:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

After deployment, purge PageSpeed/CDN caches if enabled and force-refresh the POS page with `Ctrl+F5` so the browser loads `public/js/pos.js?v=619`.
