# M-PESA and Payments

M-PESA collection, verification, reconciliation, dashboards, Daraja, IntaSend, reversals, and payment controls.

Return to the [documentation index](../../readme.md) or [customizations index](../CUSTOMIZATIONS.md).

For the proposed local/offline SMS verification design, see [Offline M-PESA Verification with a GSM Modem](../OFFLINE_MPESA_GSM_MODEM.md).

## Contents

- [1. M-PESA / Custom Pay Button](#1-m-pesa-custom-pay-button)
- [2. M-PESA Transaction Number Modal](#2-m-pesa-transaction-number-modal)
- [11. M-PESA Cash Flow Treatment](#11-m-pesa-cash-flow-treatment)
- [13. Manual M-PESA Payment Verification](#13-manual-m-pesa-payment-verification)
- [IntaSend M-PESA Webhook and Reconciliation Holding Pool](#intasend-m-pesa-webhook-and-reconciliation-holding-pool)
- [Direct Safaricom Daraja M-PESA Integration](#direct-safaricom-daraja-m-pesa-integration)
- [Unified M-PESA Dashboard and Dedicated Sidebar (2026-07-16)](#unified-m-pesa-dashboard-and-dedicated-sidebar-2026-07-16)
- [Direct Daraja M-PESA Reversal Execution (2026-07-17)](#direct-daraja-m-pesa-reversal-execution-2026-07-17)

---

<a id="1-m-pesa-custom-pay-button"></a>
## 1. M-PESA / Custom Pay Button

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


---

<a id="2-m-pesa-transaction-number-modal"></a>
## 2. M-PESA Transaction Number Modal

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


---

<a id="11-m-pesa-cash-flow-treatment"></a>
## 11. M-PESA Cash Flow Treatment

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


---

<a id="13-manual-m-pesa-payment-verification"></a>
## 13. Manual M-PESA Payment Verification

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


---

<a id="intasend-m-pesa-webhook-and-reconciliation-holding-pool"></a>
## IntaSend M-PESA Webhook and Reconciliation Holding Pool

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
- The confirmed-message picker supports selecting more than one IntaSend or direct Daraja M-PESA receipt for the same sale. The first selected receipt fills the active M-PESA row, and every additional receipt creates a separate `custom_pay_1` payment row containing that receipt's provider amount and transaction code.
- When a selected receipt amount is below the amount still required, POS shows a warning notification with the selected amount, required amount, and remaining balance. The cashier can then select another M-PESA message or add another payment method.
- A selected message changes from `Select` to `Deselect`. Deselecting removes only that receipt, removes an extra payment row when applicable, restores the original active payment row when necessary, and recalculates the sale balance immediately.
- The same receipt cannot be selected twice. Selected receipts remain visibly marked in the current picker session.
- M-PESA message selections are provisional while the popup is open. Clicking `Use Selected M-PESA Messages` or `Finalize Payment` commits them. Closing the popup with `Close` or the `X` cancels all unconfirmed selections, removes payment rows created by that session, restores the original payment row, and resets the picker actions to `Select`.
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


---

<a id="direct-safaricom-daraja-m-pesa-integration"></a>
## Direct Safaricom Daraja M-PESA Integration

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


---

<a id="unified-m-pesa-dashboard-and-dedicated-sidebar-2026-07-16"></a>
## Unified M-PESA Dashboard and Dedicated Sidebar (2026-07-16)

Purpose: Provide one operational dashboard and one dedicated navigation section for both IntaSend and direct Safaricom M-PESA (Daraja), without duplicating provider links under the general Settings menu.

Files added:

- `app/Http/Controllers/MpesaDashboardController.php`
- `database/migrations/2026_07_16_000001_add_reversal_tracking_to_mpesa_payments.php`
- `resources/views/mpesa/dashboard.blade.php`
- `resources/views/mpesa/records.blade.php`

Files changed:

- `app/DarajaPayment.php`
- `app/IntaSendPayment.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `routes/web.php`
- `lang/en/lang_v1.php`
- `readme.md`

What changed:

- Added the authenticated `GET /mpesa/dashboard` route named `mpesa.dashboard`.
- Added the authenticated `GET /mpesa/records/{metric}` route named `mpesa.records`.
- Added a dedicated M-PESA sidebar immediately below Settings.
- The M-PESA sidebar appears only when `IntaSend Integration` or `Direct M-PESA Integration` is enabled in Business Settings > Modules.
- Sidebar links remain permission-aware and only show the enabled provider's tools.
- Moved IntaSend Settings, Holding Pool, Collections, direct M-PESA Settings, and M-PESA Transactions into the dedicated M-PESA section.
- Removed all IntaSend and direct M-PESA duplicates from the general Settings dropdown.
- The sidebar reuses the current business module configuration stored in the session; saving Business Settings refreshes that session without an extra business query on every page.
- Added a unified business-scoped dashboard with location and date filters.
- Made every dashboard card open its corresponding unified records report while preserving the selected location and date filters.
- Card reports combine enabled IntaSend and direct Safaricom records and show provider, reference, phone, status, reconciliation state, linked sale invoice, amount, and reversal state.
- Matched the Damage Management dashboard presentation: gradient statistic cards, large background icons, responsive Bootstrap columns, shadows, and hover movement.
- Added reversal audit fields to both `daraja_payments` and `intasend_payments`.

Dashboard metric definitions:

- `Total Records`: all provider records in the selected business, location, and date range.
- `Linked to Sales`: records with an Ultimate POS `transaction_payment_id`.
- `Unlinked Ready`: successful, unattached records still available for reconciliation.
- `Picked`: successful records selected, matched, or attached outside a direct sale link.
- `Pending`: records whose provider status is `PENDING`.
- `Failed / Cancelled`: failed, cancelled, canceled, rejected, or timed-out provider records.
- `Linked Amount`: sum of provider amounts linked to Ultimate POS sales.
- `Reversal Pending / Requested`: records with reversal state `requested` or `pending`.
- `Successful Reversal`: records with reversal state `successful`; the dashboard also shows the reversed amount.

Reversal fields added to each provider payment table:

- `reversal_status`: `none`, `requested`, `pending`, `successful`, or `failed`.
- `reversal_request_id`
- `reversal_note`
- `reversal_requested_at`
- `reversed_at`

The migration provides auditable reversal tracking for dashboard reporting. It does not by itself submit a reversal request to IntaSend or Safaricom; provider-specific reversal API actions remain a separate implementation.


---

<a id="direct-daraja-m-pesa-reversal-execution-2026-07-17"></a>
## Direct Daraja M-PESA Reversal Execution (2026-07-17)

Direct Daraja receipts linked to a POS sale can now be submitted for a full reversal by a user with the `mpesa.reversal` permission. Configure the Safaricom initiator name and generated SecurityCredential under M-PESA Settings; the SecurityCredential is encrypted in the database and is distinct from the STK passkey.

The request moves through `requested`, `pending`, `successful`, or `failed`. Ultimate POS does not change the invoice, account, or register when the request is merely accepted. After an authenticated Safaricom success callback, it creates an audited M-PESA return payment, posts the account debit, adds the register refund, and recalculates the sale payment status. Duplicate success callbacks are idempotent. Queue timeouts remain pending for manual provider-status verification and must not be retried blindly.

Automatic provider reversal is intentionally unavailable for IntaSend collections because its published collection API does not expose an equivalent reversal operation. A payout is not treated as a reversal.

#### Dashboard-to-reversal workflow

1. IntaSend and direct Safaricom records are normalized into the unified M-PESA dashboard and metric reports.
2. At POS, every selected M-PESA message sets its payment row to the provider's actual amount. IntaSend and Daraja receipt verification both require the receipt amount to match that row.
3. A walk-in sale cannot be finalized with an unpaid balance. Multiple M-PESA messages or mixed payment methods are allowed when their combined payment covers the sale total.
4. Saved M-PESA and IntaSend credentials render as `********`; their real values are not returned in the settings HTML. Leaving a masked field blank preserves the saved credential.
5. Role Create/Edit screens contain a dedicated **M-PESA Permissions** group:
   - IntaSend M-PESA: manage settings and view transactions.
   - Direct Safaricom M-PESA: manage settings, view transactions, and reverse payments.
6. A direct Daraja reversal requires transaction access (`daraja.transactions` or legacy `daraja.manage`) together with `mpesa.reversal`, unless the user is `superadmin`.
7. Safaricom acceptance changes the reversal to pending only. Financial records change exclusively after the authenticated success callback.
8. A successful callback creates one idempotent M-PESA return payment, debits the payment account, records a register refund, and recalculates the linked sale's payment status.
9. A failed callback records the provider error without changing the sale. A queue timeout remains pending for manual verification and cannot be blindly resubmitted.

#### POS M-PESA multiple-message cashier workflow

1. Select M-PESA as the payment method and open the M-PESA tools.
2. Search confirmed IntaSend or direct Daraja messages using the phone number, amount, or transaction code.
3. Click `Select` on each receipt that belongs to the sale. Each receipt is recorded in its own M-PESA payment row so its amount and transaction code can be verified independently.
4. If a receipt is selected by mistake, click `Deselect`. The receipt is removed and the outstanding balance is recalculated.
5. If the selected messages do not cover the required amount, review the warning and select another message or add another payment method.
6. Click `Use Selected M-PESA Messages` when working from a payment row, or `Finalize Payment` during express M-PESA checkout, to keep the selections.
7. Use `Close` or the `X` to abandon the popup without keeping selections made during that popup session.

Files involved in this picker workflow:

- `public/js/pos.js` â€” message selection, provider amount application, multiple payment-row creation, duplicate prevention, underpayment notifications, deselection, commit, and close-to-cancel rollback.
- `resources/views/sale_pos/partials/payment_modal.blade.php` â€” confirmed-message table and cashier instructions.
- `public/js/lang/en.js` and `lang/en/lang_v1.php` â€” picker actions, notifications, and help text.

No database migration is required for this UI workflow. After deployment, run `php artisan optimize:clear` and force-refresh the POS browser so the updated JavaScript is loaded.

#### Retaining M-PESA excess as customer credit

For a named customer, POS can retain an M-PESA overpayment as reusable customer credit instead of returning it as cash. When the received M-PESA total is greater than the sale total, the cashier sees `Keep excess as customer credit` in both the payment window and express M-PESA tools.

Example:

- Sale total: KES 20
- Confirmed M-PESA receipt: KES 30
- Amount applied to the sale: KES 20
- Customer Advance balance created: KES 10

The full KES 30 receipt remains recorded under M-PESA so it continues to match the provider record and the actual M-PESA account inflow. A KES 10 `Advance` change-return line offsets the sale overpayment without recording a physical cash refund, and adds KES 10 to the selected customer's advance balance. On a later sale, the cashier can select `Advance` as the payment method and use some or all of that balance; using it reduces the stored customer balance.

Operational rules:

- A named customer must be selected. Walk-in customers cannot hold reusable credit.
- The retained credit cannot exceed the M-PESA amount collected on that sale.
- If the option is not selected, POS continues using the normal change-return workflow.
- Deleting the credit-producing change-return line removes the corresponding customer credit.
- Editing its amount adjusts the customer balance by the difference.
- The Sell Payment Report shows the full M-PESA receipt and a negative `Advance (Change Return)` line. Their net equals the amount settled on the invoice.
- No database migration is required; the feature uses Ultimate POS's existing contact `balance` and `Advance` payment method.

Files involved:

- `resources/views/sale_pos/partials/payment_modal.blade.php` â€” excess-credit option in the payment and M-PESA tools windows.
- `public/js/pos.js` â€” eligibility, displayed excess amount, synchronized controls, and change-return presentation.
- `app/Http/Controllers/SellPosController.php` â€” named-customer and M-PESA-source validation, and conversion of change return to customer credit.
- `app/Utils/TransactionUtil.php` â€” advance return validation and balance correction when payment lines are edited.
- `app/Listeners/AddAccountTransaction.php` and `app/Listeners/DeleteAccountTransaction.php` â€” add/remove customer credit while preserving normal Advance consumption.
- `tests/Unit/MpesaExcessCreditTest.php` â€” credit creation, future use, and deletion behavior.

#### Affected files: M-PESA dashboard through reversal

Unified dashboard and navigation:

- `app/Http/Controllers/MpesaDashboardController.php` â€” unified provider metrics and drill-down records.
- `app/Http/Middleware/AdminSidebarMenu.php` â€” permission-aware M-PESA sidebar.
- `resources/views/mpesa/dashboard.blade.php` â€” dashboard cards and filters.
- `resources/views/mpesa/records.blade.php` â€” unified metric record listing.
- `database/migrations/2026_07_16_000001_add_reversal_tracking_to_mpesa_payments.php` â€” shared reversal status fields.
- `app/IntaSendPayment.php` and `app/DarajaPayment.php` â€” reversal date/response casts.

POS payment selection and amount enforcement:

- `public/js/pos.js` â€” applies the selected provider message amount to its M-PESA payment row.
- `app/Http/Controllers/SellPosController.php` â€” enforces full walk-in payment and rejects known receipt/row amount mismatches.
- `app/Utils/IntaSendUtil.php` â€” requires IntaSend receipt and payment-row amounts to match.
- `app/Utils/MpesaVerificationUtil.php` â€” resolves receipt verification across enabled providers.

Daraja reversal execution:

- `app/DarajaSetting.php` â€” encrypts/decrypts the reversal SecurityCredential.
- `app/DarajaPayment.php` â€” casts reversal callback audit data.
- `app/Http/Controllers/DarajaController.php` â€” authorization, reversal request action, result callback, and timeout callback.
- `app/Utils/DarajaUtil.php` â€” Safaricom reversal request, state transitions, idempotent payment/account/register reversal, and invoice payment-status update.
- `resources/views/daraja/settings.blade.php` â€” masked credentials, initiator settings, and callback URLs.
- `resources/views/daraja/transactions.blade.php` â€” reversal status, confirmation modal, reason, and action.
- `routes/web.php` â€” authenticated reversal request route.
- `routes/webhooks.php` â€” token-protected Safaricom result and timeout routes.
- `database/migrations/2026_07_17_000001_add_daraja_reversal_execution.php` â€” reversal credentials, callback audit fields, accounting link, and `mpesa.reversal` permission.

IntaSend settings, roles, language, tests, and documentation:

- `app/Http/Controllers/IntaSendController.php` â€” preserves masked IntaSend keys when settings are saved without replacement values.
- `resources/views/intasend/settings.blade.php` â€” masks the public key, secret key, and webhook secret.
- `resources/views/role/create.blade.php` and `resources/views/role/edit.blade.php` â€” dedicated provider-specific M-PESA permission group.
- `lang/en/lang_v1.php` â€” dashboard, settings, permissions, validation, and reversal messages.
- `tests/Unit/DarajaReversalTest.php` â€” encrypted credential and route registration checks.
- `routes/web.php` and `readme.md` â€” dashboard/reversal routes and operational documentation.

Server action:

```bash
php artisan migrate
php artisan optimize
php artisan event:cache
php artisan view:cache
```

When PHP is installed through WAMP but is not available on the Windows PATH, use:

```powershell
& 'C:\wamp64\bin\php\php8.2.29\php.exe' artisan migrate
& 'C:\wamp64\bin\php\php8.2.29\php.exe' artisan optimize
& 'C:\wamp64\bin\php\php8.2.29\php.exe' artisan event:cache
& 'C:\wamp64\bin\php\php8.2.29\php.exe' artisan view:cache
```
