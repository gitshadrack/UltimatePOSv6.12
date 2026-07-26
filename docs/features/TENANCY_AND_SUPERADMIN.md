# Tenancy and Superadmin

Tenant branding, initialization, login routing, navigation, and system maintenance controls.

Return to the [documentation index](../../readme.md) or [customizations index](../CUSTOMIZATIONS.md).

## Contents

- [12. Login Page Side Image](#12-login-page-side-image)
- [14. Superadmin Business Data Initialization](#14-superadmin-business-data-initialization)
- [15. Public Index Cards and Superadmin Business Branding Edit](#15-public-index-cards-and-superadmin-business-branding-edit)
- [18. Decorated Admin Sidebar](#18-decorated-admin-sidebar)
- [19. Pre-Location Login Routing](#19-pre-location-login-routing)
- [20. Superadmin System Maintenance Mode](#20-superadmin-system-maintenance-mode)

---

<a id="12-login-page-side-image"></a>
## 12. Login Page Side Image

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


---

<a id="14-superadmin-business-data-initialization"></a>
## 14. Superadmin Business Data Initialization

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


---

<a id="15-public-index-cards-and-superadmin-business-branding-edit"></a>
## 15. Public Index Cards and Superadmin Business Branding Edit

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


---

<a id="18-decorated-admin-sidebar"></a>
## 18. Decorated Admin Sidebar

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


---

<a id="19-pre-location-login-routing"></a>
## 19. Pre-Location Login Routing

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


---

<a id="20-superadmin-system-maintenance-mode"></a>
## 20. Superadmin System Maintenance Mode

Purpose: Allow Superadmin to temporarily take the entire ERP offline during database cleanup, deployments, backups, or other planned maintenance without requiring direct terminal access for the normal enable/disable workflow.

Files added:

- `Modules/Superadmin/Http/Controllers/MaintenanceModeController.php`
- `Modules/Superadmin/Resources/views/maintenance/index.blade.php`
- `resources/views/errors/503.blade.php`
- `tests/Unit/MaintenanceModeSafetyTest.php`

Files changed:

- `Modules/Superadmin/Routes/web.php`
- `Modules/Superadmin/Resources/views/layouts/nav.blade.php`
- `Modules/Superadmin/Resources/lang/en/lang.php`
- `app/Http/Middleware/EncryptCookies.php`
- `readme.md`

What changed:

- Added `Superadmin > Maintenance Mode`.
- Added separate POST-only actions for enabling and disabling maintenance mode.
- Both actions require an authenticated Superadmin and the current Superadmin password.
- Enabling requires the operator to:
  - Confirm that a restorable backup or provider snapshot exists.
  - Confirm that users were notified and active transactions were stopped.
  - Type `MAINTENANCE`.
- Disabling requires the operator to type `ONLINE`.
- Uses Laravel's native maintenance mode and returns HTTP status `503` to blocked requests.
- Pre-renders a standalone maintenance page so it does not depend on normal database-backed page rendering.
- Creates a cryptographically signed administrator bypass cookie valid for 12 hours.
- Writes enable, disable, and failure events to the application log.
- Keeps `php artisan up` available as an emergency recovery method.
- No database migration is required.

#### Who can access the ERP during maintenance

- The Superadmin browser that enables maintenance mode is not logged out. It receives a private bypass cookie and can continue accessing the ERP.
- Normal users, API clients, and other browsers receive the scheduled-maintenance page.
- Existing user sessions are not deleted; they are temporarily blocked until maintenance mode is disabled.
- Other Superadmins are also blocked unless they open the private bypass link in their browser.
- The bypass link does not log someone into the ERP. Normal authentication is still required.
- The bypass link remains usable while maintenance mode is active and must be treated as confidential.
- Disabling maintenance mode invalidates the maintenance secret. The browser bypass cookie is also removed from the disabling Superadmin's browser.

#### Precautions before enabling maintenance

1. Obtain a restorable database backup or ask the hosting provider for a server-side snapshot.
2. Record the backup/snapshot identifier and confirm how it would be restored.
3. Notify all users of the outage.
4. Ensure tills complete or cancel sales currently in progress.
5. Confirm that no purchase, return, stock adjustment, import, or other write operation is running.
6. Pause external cron jobs, queue workers started with `--force`, imports, webhooks, or integrations that can write directly to the database.
7. Keep cPanel Terminal or SSH access available for emergency recovery.
8. Do not enable maintenance solely to test the button on a production system; use a planned maintenance window.

Laravel's scheduler and normal queue workers respect maintenance mode unless a task or worker was explicitly configured to run during maintenance. External processes that write directly to MariaDB are outside Laravel's protection and must be paused separately.

#### How to enable maintenance mode

1. Log in using a Superadmin account.
2. Open `Superadmin > Maintenance Mode`.
3. Confirm the backup/snapshot checkbox.
4. Confirm that users were notified and active transactions were stopped.
5. Set the retry-after value between 30 and 3600 seconds. The default is 60 seconds.
6. Enter the current Superadmin password.
7. Type `MAINTENANCE` exactly.
8. Click `Enable maintenance mode` and approve the final browser confirmation.
9. Save the displayed bypass link securely for the current maintenance window.
10. Open the public ERP URL in an incognito/private browser and confirm that it shows the scheduled-maintenance page.

The activating Superadmin's current browser should continue working through its bypass cookie. The cookie lasts for 12 hours. If it expires while maintenance mode remains active, open the saved bypass link again to obtain a new valid cookie, then authenticate normally if required.

#### How to return the ERP online

1. Complete the maintenance work.
2. Validate login, existing invoices, sales, purchases, stock, stock valuation, returns, and profit/cost reports as appropriate.
3. Return to `Superadmin > Maintenance Mode` using the bypass-enabled browser.
4. Enter the current Superadmin password.
5. Type `ONLINE` exactly.
6. Click `Return system online` and approve the confirmation.
7. Re-enable paused cron jobs, integrations, and queue workers.
8. Open the ERP in a separate incognito/private browser and confirm normal access.
9. Notify users that the ERP is available again.

#### Emergency terminal recovery

If the bypass link or cookie is unavailable, open cPanel Terminal or SSH, change to the application directory containing `artisan`, and run:

```bash
php artisan up
```

If cPanel requires a version-specific PHP executable, use the PHP version assigned to the ERP domain. For example:

```bash
/opt/cpanel/ea-php81/root/usr/bin/php artisan up
```

If the command reports `Could not open input file: artisan`, change to the correct UltimatePOS application directory first.

The equivalent terminal command for enabling maintenance remains available:

```bash
php artisan down --secret="USE-A-LONG-RANDOM-SECRET" --retry=60
```

Do not publish or share the secret URL generated from that command.

#### Deployment

Upload all files listed above, then clear cached routes, configuration, and views:

```bash
php artisan optimize:clear
```

No migration is needed. After deployment, log in as Superadmin and confirm that the `Maintenance Mode` navigation item and page load correctly. Do not activate it on the live ERP until a real maintenance window is scheduled.

Tests:

```bash
php vendor/bin/phpunit --do-not-cache-result tests/Unit/MaintenanceModeSafetyTest.php
```

The tests cover:

- Unencrypted framework-compatible maintenance bypass cookie handling.
- Cryptographic bypass-cookie validation.
- POST-only, authenticated, Superadmin-protected enable/disable routes.
- Database-independent pre-rendering of the custom `503` maintenance page.
