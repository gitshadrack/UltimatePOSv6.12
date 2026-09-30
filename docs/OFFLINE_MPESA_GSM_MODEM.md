# Offline M-PESA Verification with a GSM Modem

This document describes how to add local M-PESA payment capture to Ultimate POS when the shop's internet connection is unavailable. It is an implementation plan, not a claim that M-PESA itself works without Safaricom network coverage.

Return to the [documentation index](../readme.md) or [M-PESA and payments](features/MPESA_AND_PAYMENTS.md).

## What “offline” means

The POS computer and local database can continue operating without internet while a GSM/LTE modem receives the merchant's M-PESA confirmation SMS over Safaricom's mobile network. The modem approach can verify a customer-presented transaction locally and queue it for later reconciliation.

It cannot perform Daraja STK Push, call Transaction Status, receive an internet webhook, or prove final settlement while the internet is unavailable. Those operations require connectivity. Safaricom confirms that a Business Till receives payment confirmation by SMS, while Daraja is the online API integration platform:

- [Safaricom M-PESA Business Till booklet](https://www.safaricom.co.ke/images/Downloads/Resources_Downloads/M-PESA_BUSINESS_TILL_Booklet.pdf)
- [Safaricom Daraja Developer Portal](https://developer.safaricom.co.ke/)

Treat SMS matching as **provisional local verification** and reconcile it against Daraja, IntaSend, or a downloaded merchant statement when internet access returns.

## Recommended architecture

```text
Customer pays merchant Till/Paybill
                |
                v
Safaricom sends confirmation SMS to merchant SIM
                |
                v
USB GSM/LTE modem connected to POS server
                |
                v
Windows GSM Bridge service (owns the COM port)
                |
        signed localhost HTTP request
                |
                v
Ultimate POS GSM inbox -> parser -> duplicate check
                |
                v
Pending / matched / verified / rejected payment
                |
                v
Online reconciliation when internet returns
```

Do not read the serial port directly from a Laravel web request. A long-running bridge service should be the only process that owns the COM port. It can survive web requests, modem disconnects, and Windows restarts.

## Hardware and SIM requirements

Use a USB GSM/LTE modem that:

- supports Safaricom's currently available network bands;
- exposes a serial AT-command port on Windows;
- supports receiving and storing SMS using standard commands;
- has stable Windows drivers and a powered USB connection;
- can report SIM/network state and signal strength.

Prefer a supported 4G/LTE module over a 2G-only board. Confirm the exact Kenyan variant and bands with the supplier before purchase. Quectel documents the standard SMS commands used by its modems, including `AT+CMGF`, `AT+CNMI`, `AT+CMGR`, `AT+CMGL`, and `AT+CMGD`: [Quectel GSM SMS application note](https://quectel.com/content/uploads/2021/03/Quectel_GSM_SMS_Application_Note_V1.1.pdf).

The SIM placed in the modem must be the SIM that legitimately receives notifications for the merchant Till/Paybill. Test this with Safaricom before development. Do not use an employee's personal SIM, and do not automate SIM Toolkit menus or store a Till operator PIN in the application.

## Proposed database tables

### `gsm_modem_settings`

- `business_id` and `location_id`
- `enabled`
- `port_name`, for example `COM5`
- `baud_rate`
- `merchant_identifier` (Till/Paybill)
- `bridge_secret_hash`
- `last_seen_at`, `last_signal`, and `last_error`
- timestamps

Only one enabled modem should own a location/merchant identifier unless an explicit high-availability design is added.

### `gsm_mpesa_messages`

- tenant and location identifiers
- modem-generated UUID
- modem/SIM message index for diagnostics only
- sender, SMS timestamp, received timestamp, and raw message
- parsed receipt number, amount, phone, customer name, merchant identifier, and balance
- parser version and parse status
- match status: `received`, `parsed`, `matched`, `verified_local`, `rejected`, `reconciled`, or `conflict`
- linked `transaction_payment_id`
- reconciliation provider/reference/time
- raw-message hash and timestamps

Add unique constraints scoped by tenant/provider for the normalized M-PESA receipt number and by modem UUID. Never depend on the SIM storage index for uniqueness because indexes are reused.

## GSM bridge behavior

On startup, the Windows service should:

1. Open the configured COM port with exclusive ownership.
2. Check the modem with `AT`, SIM readiness with `AT+CPIN?`, registration with `AT+CREG?`/the modem's LTE equivalent, and signal with `AT+CSQ`.
3. Select SMS text mode with `AT+CMGF=1` and configure message storage.
4. Enable new-message indications with the modem-supported `AT+CNMI` mode.
5. Read all unread messages and then listen continuously for new indexes.
6. Assign every message a UUID and POST it to a localhost-only Laravel endpoint.
7. Retry delivery until Ultimate POS acknowledges the same UUID.
8. Delete a modem message only after durable acknowledgement, or retain it until a configurable safe cleanup window has passed.

The bridge needs a local durable queue (SQLite is sufficient), structured logs with redacted phone numbers, exponential retries, automatic COM-port reconnect, and a Windows Service installer. Never expose the bridge listener or serial controls to the public internet.

## Laravel implementation points

Suggested new components:

- `app/GsmModemSetting.php`
- `app/GsmMpesaMessage.php`
- `app/Utils/GsmMpesaParser.php`
- `app/Utils/GsmMpesaMatcher.php`
- `app/Http/Controllers/GsmMpesaController.php`
- `app/Console/Commands/ReconcileGsmMpesa.php`
- migrations for the two tables and required permissions
- settings, inbox, conflict, and health views
- a localhost ingestion route separate from browser/session authentication
- a bridge application under `tools/mpesa-gsm-bridge/`

The ingestion endpoint should require all of the following:

- request source restricted to loopback or a configured private host;
- timestamp and nonce with a short acceptance window;
- HMAC-SHA256 signature over the exact raw request body plus timestamp;
- constant-time signature comparison;
- modem UUID idempotency;
- tenant/location lookup from server-side credentials, never trusted directly from the payload;
- rate limiting and an audit log.

Store the raw SMS encrypted at rest if operationally possible. Limit access to the inbox because messages contain phone numbers, names, transaction references, amounts, and balances.

## Parsing and matching rules

SMS wording changes, so parsing must be versioned and fixture-tested. Normalize the receipt number to uppercase and currency amounts using a locale-independent parser. Retain every unparsed message for review rather than silently discarding it.

A message may be linked automatically only when all mandatory checks pass:

- recognized M-PESA receipt format;
- unique receipt number;
- intended merchant/location;
- exact expected amount, unless an authorized split/partial-payment workflow is active;
- SMS time inside a configured window around the sale;
- receipt has not already been used by another POS payment.

Sender ID is useful for filtering but is not sufficient proof because SMS metadata and message text can be spoofed. Do not auto-match based only on phone number, customer name, or a cashier-entered receipt number.

## POS workflow

1. Cashier selects M-PESA (`custom_pay_1`).
2. Customer completes payment to the displayed Till/Paybill.
3. POS waits briefly for a matching local GSM message and displays modem health/status.
4. If one exact unused match is found, the receipt number is filled and the payment becomes `verified_local`.
5. If there is no exact match, the cashier may retry or save it as pending according to location policy. A supervisor override must require permission and a reason.
6. If multiple candidates exist, no automatic choice is made; the payment goes to the conflict screen.
7. Once internet returns, a scheduled job reconciles locally verified receipts with the existing Daraja/IntaSend records or merchant statement.
8. A disagreement changes the item to `conflict` and alerts an administrator; it must never silently overwrite financial records.

Reuse the current `transaction_payments.transaction_no` and M-PESA verification/account-posting safeguards. Do not create a second payment ledger. The GSM table is an evidence inbox linked to the existing payment record.

## Failure and security policy

- Modem unavailable: show a visible warning and follow the configured pending-payment policy.
- Internet unavailable: continue local SMS capture; queue reconciliation.
- Safaricom mobile network unavailable: the modem cannot verify payment; do not label the payment verified.
- Duplicate receipt: block it across all registers and locations in the same business.
- Amount mismatch: require review; never round or silently accept it.
- Late SMS: retain and offer controlled matching from the inbox.
- Parser failure: retain raw evidence and alert an administrator.
- Database unavailable: keep the message in the bridge queue and on the modem where possible.
- Lost/stolen modem or SIM: disable its credential, replace the bridge secret, and follow Safaricom SIM/Till security procedures.

## Delivery phases

### Phase 1 — proof of concept

- Confirm the merchant SIM receives usable payment SMS.
- Select the exact modem and identify its Windows COM port.
- Capture at least 30 sanitized SMS samples: successful payments, reversals, withdrawals, balance messages, malformed/concatenated SMS, and duplicate delivery.
- Build a read-only bridge diagnostic that never deletes SMS.

### Phase 2 — durable inbox

- Add settings, ingestion authentication, database tables, idempotency, health monitoring, parser fixtures, and an admin inbox.
- Run in shadow mode: compare suggestions with manual verification but do not change payment status.

### Phase 3 — controlled local verification

- Link exact matches to existing `custom_pay_1` payments.
- Add supervisor override, conflicts, alerts, register summaries, and full audit history.
- Pilot at one location and one register.

### Phase 4 — online reconciliation

- Reconcile with the existing Daraja/IntaSend payment pools or official merchant statements.
- Monitor mismatch, duplicate, parse-failure, and modem-offline rates.
- Expand location by location only after the pilot meets agreed thresholds.

## Acceptance tests

- A valid exact payment is linked once even when the bridge sends it repeatedly.
- The same receipt cannot verify two sales.
- Wrong amount, wrong Till, stale SMS, reversal, and malformed messages do not auto-verify.
- Split payments work only through the authorized split-payment rules.
- Restarting WAMP, Laravel queues, Windows, or the modem loses no acknowledged or unacknowledged messages.
- An unplugged modem is visible to cashiers and administrators.
- Offline receipts reconcile correctly after connectivity returns.
- Every manual action records user, time, old state, new state, and reason.
- Users cannot access another tenant's modem messages.

## Information required before coding

Record these values for the pilot location:

- exact modem manufacturer/model and Windows driver;
- COM port and supported baud rate;
- Till or Paybill type and identifier;
- confirmation that its notification SIM receives full transaction SMS;
- several sanitized real SMS formats, including reversals;
- whether the POS and database remain on the same local Windows machine during an internet outage;
- policy for no match: block sale, keep pending, or supervisor override;
- maximum wait and matching time window;
- whether partial and overpayments are allowed.

Do not enable automatic verification until these inputs and shadow-mode results are reviewed.
