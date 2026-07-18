# M-PESA Missing Payment Recovery Report

## 1. Purpose

This report proposes a recovery mechanism for cases where a customer has completed an M-PESA payment but the provider callback/webhook has not reached the POS.

The goal is to prevent a valid payment from remaining invisible to the cashier while also preventing duplicate, forged, or incorrectly assigned payments.

## 2. Current system behaviour

The POS currently relies mainly on provider callbacks:

- Safaricom Daraja sends STK and C2B callbacks.
- IntaSend sends collection webhooks.
- The M-PESA selection window searches local `daraja_payments` and `intasend_payments` records.
- Only completed, available local records can be selected by a cashier.

If a callback is delayed, rejected, or lost, the provider may have received the customer's money while the corresponding completed record is missing from the local database. Searching again in the POS does not fix this because it searches the same local data.

### Existing strengths

- `daraja_payments` stores `merchant_request_id` and `checkout_request_id` for POS-initiated STK requests.
- Provider transaction codes are unique, which can be used to prevent duplicates.
- Transactions already have status, attachment, reconciliation, location, amount, phone, and raw payload fields.
- The POS already has a transaction search and selection interface that can display recovered payments.

### Identified gaps

- There is no active Daraja STK status-query operation.
- IntaSend STK requests are not persisted locally as pending requests with their returned invoice/checkout identifiers.
- There is no scheduled job that retries pending transactions.
- There is no cashier-facing **Refresh status** action.
- There is no wider provider reconciliation process for payments that were not initiated by the POS.
- There is no dedicated recovery history showing how and when a missing payment was found.

## 3. Recommended solution

Use four complementary recovery mechanisms. No single mechanism covers every payment type.

### 3.1 Provider status query

Use this for payments initiated by the POS.

#### Daraja STK

Query the Lipa Na M-PESA Online status using the saved `CheckoutRequestID`. If Safaricom confirms the transaction as successful, update the existing pending `daraja_payments` row to `COMPLETE` using the same normalization rules as the callback.

#### IntaSend STK

Persist the invoice or checkout identifier returned when initiating STK. Query IntaSend's payment-status endpoint using that identifier and update the pending local record when the provider returns a terminal state.

This should support both a cashier-triggered refresh and automatic background checks.

### 3.2 Scheduled pending-payment reconciliation

Create a Laravel command such as:

```text
php artisan mpesa:reconcile-pending
```

The command should:

1. Find recent `PENDING` or `PROCESSING` provider requests.
2. Query the appropriate provider.
3. Convert provider results into the application's standard payment structure.
4. Update the existing record idempotently.
5. Record the attempt, response, and recovery source.
6. Leave unresolved payments pending for a later retry.

Suggested schedule:

| Age of request | Check frequency | Action |
|---|---:|---|
| 0-2 minutes | Every 15-30 seconds from POS UI | Fast cashier feedback |
| 2-15 minutes | Every minute | Background reconciliation |
| 15 minutes-24 hours | Every 10-15 minutes | Delayed provider recovery |
| More than 24 hours | Daily/manual queue | Investigation or statement reconciliation |

The server must run Laravel's scheduler every minute. The command should use `withoutOverlapping()` and ideally `onOneServer()` where multiple application servers are used.

### 3.3 Provider transaction pull

Use this for Till/Paybill payments that were not initiated through the POS and therefore have no local checkout request ID.

- Safaricom provides a Pull Transaction API, subject to Daraja product access and onboarding.
- IntaSend exposes invoice listing and invoice retrieval endpoints that can be filtered and used for reconciliation.

The synchronization process should request a narrow time range, verify the configured shortcode/account, and upsert returned transactions by provider transaction code.

This process should run separately from the fast STK pending-status job because it scans a wider provider data set.

### 3.4 Manual recovery

Provide a restricted recovery screen for cases that automatic queries cannot resolve.

The cashier or manager should be able to:

- Enter an M-PESA receipt number.
- Search locally first.
- Query the provider where the API supports receipt-level lookup.
- Import and reconcile an official provider statement when direct lookup is unavailable.
- View why a payment was accepted, rejected, or left for review.

An SMS, screenshot, or customer handset message should not by itself mark a payment as confirmed. Confirmation must come from the provider API, an authenticated provider portal, or an official statement.

## 4. Proposed payment recovery flow

```text
Customer pays
    |
    +-- Callback received -----------------> Store/update COMPLETE payment
    |
    +-- Callback missing
            |
            +-- POS-initiated STK ----------> Query by checkout/invoice ID
            |                                  |
            |                                  +-- COMPLETE -> update local payment
            |                                  +-- FAILED ----> show failure
            |                                  +-- PENDING ---> retry later
            |
            +-- Direct Till/Paybill payment -> Pull/list provider transactions
                                               |
                                               +-- Verified match -> insert/upsert
                                               +-- Unclear match --> review queue

Recovered COMPLETE payment
    |
    +-- Appears in existing M-PESA selection window
    +-- Cashier selects it for the sale
    +-- Unique transaction code prevents reuse
```

## 5. Proposed database changes

The exact migration can be refined during implementation, but the following information is required.

### `daraja_payments`

The table already has the identifiers needed for STK status queries. Add recovery metadata:

| Column | Suggested type | Purpose |
|---|---|---|
| `last_status_checked_at` | datetime nullable | Last active provider query |
| `status_query_attempts` | unsigned integer default 0 | Retry tracking |
| `next_status_check_at` | datetime nullable/indexed | Efficient retry scheduling |
| `recovered_via` | string nullable | `callback`, `status_query`, `pull`, `statement`, or `manual` |
| `last_query_response` | json nullable | Audit and troubleshooting |
| `recovery_error` | text nullable | Most recent safe error description |

### `intasend_payments`

Add the same recovery metadata plus outbound request identifiers:

| Column | Suggested type | Purpose |
|---|---|---|
| `invoice_id` | string nullable/indexed | IntaSend invoice identifier |
| `checkout_id` | string nullable/indexed | Checkout identifier when returned |
| `request_payload` | json nullable | Original STK request audit |
| `last_status_checked_at` | datetime nullable | Last provider query |
| `status_query_attempts` | unsigned integer default 0 | Retry tracking |
| `next_status_check_at` | datetime nullable/indexed | Efficient scheduling |
| `recovered_via` | string nullable | Recovery source |
| `last_query_response` | json nullable | Latest provider response |
| `recovery_error` | text nullable | Latest safe error description |

If IntaSend does not return a transaction code until completion, `transaction_code` must become nullable while remaining unique when present. A separate unique provider request/invoice ID should identify the pending row.

### Optional recovery-attempt table

For stronger auditing, create `mpesa_recovery_attempts` instead of overwriting the last response:

- provider and provider payment ID
- business and location ID
- recovery method
- request identifier
- attempt number
- response status/result code
- sanitized provider response
- error message
- initiating user, if manually requested
- timestamps

This is preferable if operations staff need a complete attempt history.

## 6. Application components to add or modify

### Provider services

Add provider-specific methods behind a common recovery interface:

```php
queryPaymentStatus($payment)
syncCompletedTransactions($setting, $from, $to)
normalizeProviderResult($response)
```

Suggested concrete methods:

- `DarajaUtil::queryStkStatus(DarajaPayment $payment)`
- `DarajaUtil::pullTransactions(DarajaSetting $setting, $from, $to)`
- `IntaSendUtil::queryPaymentStatus(IntaSendPayment $payment)`
- `IntaSendUtil::syncCompletedInvoices(IntaSendSetting $setting, $from, $to)`

Both callbacks and recovery queries should pass through the same normalization/upsert logic. This avoids different status rules depending on how the transaction was discovered.

### Controllers and routes

Add an authenticated POS route such as:

```text
POST /mpesa/payments/refresh-status
```

Inputs should identify an existing pending provider request, not accept arbitrary provider credentials or untrusted payment results from the browser.

The endpoint should return:

- provider
- current normalized status
- receipt number, when complete
- amount and phone
- a short cashier message
- whether the transaction is now selectable

Apply business and location authorization before querying or returning a payment.

### Console commands

Recommended commands:

```text
mpesa:reconcile-pending
mpesa:sync-provider-transactions
mpesa:reconcile-statement {file}
```

The first command is the implementation priority. The provider-wide synchronization and statement import can follow after the fast status-query workflow is stable.

### POS user interface

Add the following to the existing M-PESA transaction modal:

- **Refresh status** button.
- A short loading message such as `Checking M-PESA...`.
- A clear result: `Payment found`, `Still waiting`, `Payment failed`, or `Could not check now`.
- Automatic refresh for a short period after initiating STK, with a visible stop/timeout condition.
- No duplicate payment row if the callback and manual refresh complete at the same time.

Do not keep the cashier trapped indefinitely. After the fast polling period, allow the sale to remain open or use another payment method while background reconciliation continues.

### Recovery and reconciliation report

Add a report that includes:

- provider
- receipt/transaction code
- checkout or invoice ID
- customer phone
- gross amount
- provider status
- local attachment status
- recovery source
- number of query attempts
- first request time
- provider transaction time
- recovered time
- sale/invoice attached to the payment
- user who manually recovered or attached it
- latest error/review reason

Useful filters are provider, location, date, status, recovery source, attached/unattached, and receipt number.

## 7. Idempotency and matching rules

Recovery introduces a race: the callback may arrive while a status query or transaction pull is processing. Every write must therefore be idempotent.

Required rules:

1. Upsert by the strongest provider identifier.
2. Use the provider transaction/receipt code as the final unique completion identifier.
3. For pending STK, use checkout or invoice ID as the unique request identifier.
4. Lock or atomically update the payment while applying a terminal provider result.
5. Never create a second sale payment when an existing provider record is already attached.
6. Validate business, location, shortcode/account, currency, amount, phone, and transaction time before matching.
7. Preserve the full provider amount, including the existing excess-to-customer-credit workflow.

Suggested identifier priority:

| Provider | Pending identifier | Completed identifier |
|---|---|---|
| Daraja STK | `checkout_request_id` | M-PESA receipt/`transaction_code` |
| Daraja C2B | Provider request/reference where available | M-PESA receipt/`transaction_code` |
| IntaSend | `invoice_id` or `checkout_id` | Provider reference/M-PESA receipt |

## 8. Retry and status policy

Normalize provider-specific responses to a small internal state set:

- `PENDING`: accepted but not finished.
- `PROCESSING`: provider is still processing.
- `COMPLETE`: confirmed successful and selectable.
- `FAILED`: provider returned a terminal failure.
- `CANCELLED`: customer or provider cancelled.
- `REVIEW`: inconsistent or insufficient evidence.

Retry only pending/processing results and temporary transport/server errors. Do not retry terminal failures indefinitely.

Use capped exponential backoff with jitter for background requests. Provider timeouts should not be treated as payment failure; they mean the status is currently unknown.

Do not automatically send another STK request merely because a status query timed out. The first request may still complete and a second prompt can cause double payment.

## 9. Security and operational controls

- Keep provider credentials on the server and load them from encrypted settings/configuration.
- Continue TLS certificate verification, including the configured Daraja CA bundle where required.
- Verify webhook signatures where supported.
- Limit manual recovery and statement import to authorized roles.
- Never log access tokens, consumer secrets, passkeys, webhook secrets, or full sensitive request headers.
- Sanitize stored query responses where necessary.
- Rate-limit cashier refresh requests.
- Alert administrators when pending counts or provider-query failures exceed a threshold.
- Retain provider payloads and recovery activity for financial audit requirements.

## 10. Failure scenarios to test

| Scenario | Expected result |
|---|---|
| Callback arrives normally | Payment becomes complete once |
| Callback is missing, status query succeeds | Existing pending row becomes complete |
| Status query and callback arrive together | One provider record and one selectable payment |
| Provider remains pending | UI shows waiting and background retry continues |
| Provider times out | Status remains unknown/pending; no false failure |
| STK fails or is cancelled | Terminal status shown; payment cannot be selected |
| Direct Till payment has no local request | Provider pull/import creates an unattached verified record |
| Same transaction appears in two sync runs | Unique identifier prevents duplication |
| Receipt is already attached | It cannot be selected for another sale |
| Amount exceeds sale total | Full provider amount is retained; existing excess-credit option remains available |
| Amount is below sale total | Payment is selectable and remaining balance stays visible |
| Wrong business/location requests refresh | Access is denied and no payment details leak |
| Cashier supplies fake SMS/receipt | No completion without provider verification |

## 11. Phased implementation plan

### Phase 1: Persist and query POS-initiated payments

- Persist IntaSend pending STK request records and provider identifiers.
- Implement Daraja STK status query.
- Implement IntaSend invoice/payment status query.
- Extract shared idempotent normalization/upsert logic.
- Add provider-client unit tests with mocked responses.

**Outcome:** a missing callback can be recovered when the payment originated from the POS.

### Phase 2: Cashier refresh and automatic retries

- Add the authenticated refresh endpoint.
- Add **Refresh status** to the M-PESA modal.
- Add short client-side polling after STK initiation.
- Add `mpesa:reconcile-pending` and schedule it.
- Add recovery metadata and error visibility.

**Outcome:** the cashier usually sees a successful payment without manager intervention.

### Phase 3: Wider provider reconciliation

- Complete Safaricom Pull Transaction onboarding and implementation.
- Implement IntaSend completed-invoice synchronization.
- Add configurable synchronization windows and rate limits.
- Add a reconciliation review queue.

**Outcome:** direct Till/Paybill payments and longer callback outages can be recovered.

### Phase 4: Statement import and reporting

- Add official statement import with preview and validation.
- Add recovery/reconciliation reporting and exports.
- Add alerts and operational metrics.
- Document incident-handling procedures.

**Outcome:** finance and support teams can investigate and audit exceptional cases.

## 12. Acceptance criteria

The first production-ready release should meet these minimum criteria:

- A Daraja STK payment with a lost callback can be recovered using its checkout request ID.
- An IntaSend STK payment stores enough data to be queried after the original HTTP request ends.
- Refreshing repeatedly cannot duplicate a payment.
- A late callback after successful recovery cannot duplicate or downgrade the payment.
- Only `COMPLETE` provider-verified records are selectable at POS.
- Pending, failed, and network-error states display different cashier messages.
- Automatic retries stop or slow down after the configured limit.
- Every recovered payment records its recovery source and timestamps.
- Existing multiple-message selection, deselection, underpayment, and excess-credit workflows continue to work.
- Automated tests cover callback/query races, retries, authorization, and duplicate prevention.

## 13. Decisions required before implementation

1. Confirm whether both Daraja and IntaSend must support recovery in the first release.
2. Confirm access to Safaricom's Pull Transaction product for each production shortcode.
3. Decide which roles may perform manual receipt recovery and statement imports.
4. Decide how long provider responses and recovery logs must be retained.
5. Confirm whether production uses `APP_ENV=live`; the current scheduler places several jobs inside that environment check. The M-PESA reconciliation schedule must run in the actual production environment.
6. Confirm acceptable polling limits with each provider to avoid throttling.

## 14. Recommended immediate next step

Begin with Phase 1 and Phase 2 for POS-initiated STK payments. This provides the highest operational value with the lowest matching risk because the system already knows the checkout/invoice identifier, phone, amount, business, and location.

Provider-wide pulling should be added afterward because it requires stricter matching, access controls, wider query windows, and potentially additional Safaricom onboarding.

## 15. Provider references

- [Safaricom Daraja Pull Transaction API](https://developer.safaricom.co.ke/apis/PullTransaction)
- [Safaricom M-PESA business APIs and transaction-query information](https://www.safaricom.co.ke/main-mpesa/m-pesa-for-you/helpful-m-pesa-channels/sim-toolkit)
- [IntaSend Check Payment Status API](https://developers.intasend.com/reference/api_v1_payment_status_create)
- [IntaSend Payment Status guide](https://developers.intasend.com/docs/payment-status)
- [IntaSend List Invoices API](https://developers.intasend.com/reference/api_v1_invoices_list)
- [IntaSend Retrieve Invoice API](https://developers.intasend.com/reference/api_v1_invoices_retrieve)
- [IntaSend Payment Collection Events](https://developers.intasend.com/docs/payment-collection-events)

