# Restaurant POS Full-Commitment Implementation Plan

## Document status

This document is a future implementation specification. It records the agreed
restaurant workflow and the controls required before development begins. It
does not indicate that these features are already available.

## Objective

Transform the POS into a complete restaurant workflow supporting waiter
ordering, floors and tables, preparation stations, kitchen displays, routed
order printing, bill management, cashier settlement, tips, returns, swaps,
voids, inventory integrity, and operational reporting.

The design must remain multitenant. Floors, tables, products, stations,
printers, permissions, orders, payments, tips, and reports must always be
restricted by `business_id` and, where applicable, `location_id`.

## Principal roles

### Waiter

- Signs in to the Waiter Screen using a PIN or password.
- Selects a location, floor, service area, and table.
- Creates and sends orders.
- Reopens an occupied table and adds more items.
- Views preparation and bill status.
- Prints a provisional bill.
- May split, merge, return, swap, discount, or receive payment only when the
  assigned role permits it.

### Cashier

- Searches for an open bill using its bill/receipt number, table, or waiter.
- Reviews the bill before settlement.
- Receives cash, M-PESA, card, credit, or multiple payments.
- Completes the bill and releases the table.
- Reprints the final receipt subject to permission.

### Preparation-station user

- Sees only items routed to that station.
- Accepts, prepares, marks ready, and completes items.
- Receives cancellation and replacement notifications.

### Supervisor

- Approves restricted returns, swaps, discounts, complimentary items, and
  voids.
- Resolves wrong-order disputes.
- Reviews exception, void, return, wastage, tip, and waiter reports.

### Administrator

- Configures floors, tables, stations, printers, kitchen displays, products,
  permissions, payment rules, and tip policies.

## End-to-end service workflow

### 1. Waiter authentication

1. The waiter opens the Waiter Screen.
2. The waiter enters a personal PIN or password.
3. The system verifies that the waiter is active, assigned to the business and
   location, and allowed to use the screen.
4. The system records the authenticated user against every subsequent order
   action.

PINs must be hashed, never stored in plain text, and protected with retry
limits, temporary lockout, session timeout, and supervisor reset controls.
Shared waiter PINs must not be permitted because they destroy accountability.

### Waiter shift and register eligibility

Each location should be able to enable **Require active waiter shift before
selling**. When enabled, a waiter whose shift is closed, expired, suspended, or
not assigned to the current location cannot:

- claim a new table;
- create a new order;
- add items to an existing table;
- send items to preparation;
- complete a sale or receive payment.

The restriction must be enforced by the server on every protected action, not
only by hiding buttons in the browser.

Register requirements must be configurable separately:

- **Waiter receives payment:** require both an active waiter shift and an open
  register assigned to that waiter/location before payment can be accepted.
- **Designated cashier receives payment:** require the waiter to have an active
  service shift for ordering, but do not require the waiter to own a cash
  register. The designated cashier must have an open register before settling
  the bill.
- **Strict register mode:** optionally prevent all new ordering when the
  location's required register is closed.

Closing a shift must check for open tables, unsent items, unpaid bills, pending
tips, and unsettled cash. The configured policy should either:

1. prevent shift closure until the work is completed;
2. require a supervisor to transfer open tables and bills to another active
   waiter; or
3. allow a supervisor override with a reason and audit record.

After a shift closes, the waiter may retain read-only access to their completed
shift summary if permitted, but must not continue selling using an already-open
browser session. Reopening a shift, changing an end time, or overriding the
restriction requires permission and must be audited.

### 2. Floor and table selection

After authentication, the first selection screen displays the available
location, service area, floor, and table.

Suggested table states:

| State | Meaning |
| --- | --- |
| Available | No active order is attached to the table. |
| Selected | The current waiter is preparing a new order. |
| Occupied | An active order has been sent. |
| Preparing | At least one item is being prepared. |
| Ready | At least one station has marked items ready. |
| Bill requested | A provisional bill has been printed or requested. |
| Payment pending | The bill is waiting for an authorized cashier. |
| Paid | Settlement has completed. |
| Reserved | The table has a future/current reservation. |
| Cleaning/blocked | The table cannot currently be assigned. |

Colours must be configurable and must also include text/icons for
accessibility. Colour alone must never be the only indication of state.

Selecting a table must create an atomic booking/claim so two waiters cannot
open separate orders for the same table accidentally. A supervisor may
transfer a table or waiter with an audit record.

### 3. Menu selection and initial order

1. The waiter selects food, drinks, pastries, barista items, or other menu
   products.
2. Variations, modifiers, cooking instructions, quantity, seat/guest, course,
   allergies, and notes can be captured where configured.
3. The waiter reviews the order.
4. Clicking **Send Order** commits the order and assigns an immutable dispatch
   sequence/version.
5. Items are grouped and routed to their configured preparation stations.

The original sent quantity must be retained. Later changes must be represented
as additional, cancellation, return, or replacement events rather than
silently overwriting the first order.

### 4. Preparation-station routing

Products should map to a preparation station rather than directly to only a
printer.

Example stations:

- Kitchen
- Bar
- Barista
- Pastry
- Grill
- Pizza
- Cold kitchen
- External preparation point

Each station can use:

- a configured ESC/POS order printer;
- a Kitchen Display Screen (KDS);
- both printer and KDS;
- a fallback printer.

Example product routing:

- Meals → Kitchen
- Beer and cocktails → Bar
- Coffee and hot drinks → Barista
- Cakes and pastries → Pastry

When an order is sent, each station receives only its own items. The ticket
must show the order number, table, waiter, time, item, quantity, modifiers,
notes, course, and dispatch sequence. Prices should normally be omitted from
preparation tickets unless the business enables them.

Changing a station printer must not require editing every product. Product
records point to a station; the station controls its printer and display.

### 5. Kitchen Display Screen lifecycle

Suggested item states:

```text
New → Accepted → Preparing → Ready → Served
                         ↘ Cancelled
```

The KDS must:

- group items by order/table and preparation station;
- show elapsed preparation time;
- highlight overdue items;
- display additions separately from the original dispatch;
- show cancellation and replacement alerts prominently;
- prevent one station from completing another station's items;
- update the Waiter Screen in near real time;
- retain timestamps and the user responsible for every state change.

Printer and KDS delivery must use idempotency keys so reconnecting or retrying
does not create duplicate preparation work.

### 6. Adding items to an occupied table

1. The waiter reopens the table.
2. Previously sent items remain visible and clearly marked as sent.
3. The waiter adds more items.
4. Clicking **Send Order** dispatches only the new or changed quantities.
5. Each additional dispatch receives its own sequence number.
6. New items route to their configured printers and kitchen displays.
7. The running table total is recalculated.

Previously sent items must never print again merely because the order was
reopened.

### 7. Provisional bill

When the customer is satisfied:

1. The waiter selects **Print Bill**.
2. The system verifies that all billable items are included.
3. A provisional bill with a unique bill number is printed.
4. The order changes to **Bill requested** or **Payment pending**.
5. Printing the provisional bill does not mark the transaction paid and does
   not release the table.

The bill may be reopened only under defined permissions. New items after bill
printing should invalidate the old bill version and generate a revised bill.

## Payment and table release

### Waiter-controlled payment

Where permitted, the waiter may:

- select a payment method;
- receive payment;
- record a split/multiple payment;
- complete the transaction;
- print the final receipt;
- release the table.

### Designated-cashier payment

Where waiter payment is restricted:

1. The waiter prints or submits the provisional bill.
2. The cashier searches by bill/receipt number, table, or waiter.
3. The cashier confirms that the bill is open and has not already been paid.
4. The cashier selects Cash, M-PESA, Card, Customer Credit, Bank Transfer, or
   Multiple Payments.
5. For multiple payments, the cashier allocates the amount across payment
   methods.
6. The system validates that the received amount covers the amount due.
7. The cashier confirms settlement.
8. Payment, account/register posting, and sale completion occur atomically.
9. The bill becomes **Paid**.
10. The table is released.
11. The Waiter Screen updates to **Paid** without requiring a new login.

M-PESA must follow the project's configured verification rules. A pending
provider request must not release the table until the payment is confirmed or
an authorized person applies the documented exception workflow.

Payment submission must be idempotent. Double-clicks, browser retries, or
concurrent cashier actions must not create duplicate payments.

## Bill splitting

A permitted waiter or cashier may split one open bill into multiple bills by:

- selected items;
- quantities;
- seat/guest;
- equal values;
- specified amounts, where accounting rules allow.

Requirements:

- Every child bill receives a unique bill/receipt number.
- The original bill retains links to all child bills.
- An item quantity cannot be allocated more than once.
- Taxes, discounts, service charges, rounding, and tips are recalculated using
  deterministic rules.
- Partial payment against each child bill is tracked independently.
- The table remains occupied until all linked bills are paid, transferred, or
  validly voided.
- Split history must remain auditable even if bills are later merged.

## Bill merging

Compatible open bills may be merged when the user has permission.

Requirements:

- Bills must belong to the same business, currency, and compatible location.
- Paid or fiscalized bills cannot be silently merged.
- The merged bill receives a unique bill number.
- Source bill numbers remain searchable and linked to the merged bill.
- Items, taxes, discounts, service charges, payments, tips, waiter
  attribution, and table ownership must be reconciled explicitly.
- Merging multiple tables must not release a table until its responsibility
  has been transferred to the merged bill.

## Returns, removals, and swaps

### Removing or returning an item

The workflow must distinguish:

- unsent draft removal;
- cancellation before preparation;
- cancellation during preparation;
- prepared-item wastage;
- served-item return;
- return after payment/refund.

The system must capture a reason and the responsible waiter. Configurable cases
must require supervisor approval.

Stock should return to available inventory only when the physical item or
ingredient is genuinely reusable. Prepared food that cannot be reused should
be recorded as wastage, not restored as saleable stock.

### Swapping an item

When a customer requests a different item:

1. Record the original item and swap reason.
2. Determine whether the original item is cancelled, returned, or wasted.
3. Reverse or reclassify the original stock movement appropriately.
4. Add the replacement item as a new linked order line.
5. Consume stock for the replacement item.
6. Send a cancellation notice to the original preparation station if needed.
7. Send the replacement to its configured station.
8. Recalculate the bill difference, taxes, and discounts.
9. Record any supervisor approval.

Product history must retain both sides of the swap. The original order line
must not be overwritten or deleted.

## Supervisor voids and dispute management

Only an authorized supervisor may void a committed bill.

Void workflow:

1. Locate the bill.
2. Display its complete order, payment, preparation, and stock history.
3. Require a predefined reason and optional explanatory notes.
4. Require the supervisor to authenticate or re-enter a PIN where configured.
5. Reverse financial, register/account, stock, loyalty, tax, and tip effects
   using linked reversal records.
6. Notify affected preparation stations.
7. Release or retain the table according to the remaining linked bills.
8. Preserve the original bill and mark it void; never hard-delete it.

Void reports should support filtering by:

- business and location;
- date and shift;
- waiter;
- cashier;
- approving supervisor;
- table;
- reason;
- item or product;
- amount.

Reports must show original values, reversals, approvals, timestamps, and the
resulting stock treatment.

## Tips management

Tips must be managed separately from product sales and from mandatory service
charges. Accounting and tax treatment varies by jurisdiction, so production
posting rules must be approved by the business accountant.

### Tip capture

A tip may be recorded:

- before payment as a suggested or customer-selected amount;
- during cashier settlement;
- as a percentage or fixed amount;
- across Cash, M-PESA, Card, or supported split payments;
- after payment only through an authorized adjustment workflow.

The system must capture:

- bill and payment references;
- tip amount and currency;
- payment method;
- waiter/service team;
- location and shift;
- date and time;
- user who entered the tip;
- allocation and payout status.

Cash tips handed directly to a waiter may be configured as declared tips
without increasing the cash register balance. Tips collected through the POS
must reconcile with the relevant register, bank, card, or M-PESA collection.

### Tip allocation policies

Each location should select one policy:

- Direct to the bill's assigned waiter.
- Shared equally among eligible shift staff.
- Pooled by location and shift.
- Weighted by role, hours, points, or configured percentage.
- Manually allocated by an authorized supervisor.

The chosen policy must be versioned. Changing a policy must not recalculate
already approved or paid tips unless a controlled adjustment is created.

For split bills, tips may be attached to each child bill or allocated across
the parent order using a documented rule. For merged bills, original waiter
attribution must remain available even when one cashier collects payment.

### Tip lifecycle

Suggested statuses:

```text
Recorded → Reconciled → Approved → Paid out
                     ↘ Adjusted/Reversed
```

Controls:

- A tip cannot be paid out twice.
- Approval and payout should use separate permissions.
- Payout records must identify the recipient, approver, method, date, shift,
  and amount.
- Cash payouts must post against the correct register/account.
- Electronic tips remain payable until the business disburses them.
- Refunds, returned bills, chargebacks, and voids must create traceable tip
  reversals or recovery balances.
- A waiter must not be able to edit an approved tip payable to themselves.

### Service charge

A service charge is not automatically a tip. It should have separate
configuration for:

- percentage/fixed calculation;
- taxable treatment;
- revenue or liability account;
- staff distribution policy;
- inclusion or exclusion from discounts;
- refund and void treatment.

The receipt and reports must distinguish voluntary tip, mandatory service
charge, and product sale amounts.

### Tip reports

Required reports:

- Tips by waiter, team, role, location, shift, and date.
- Tips by payment method.
- Recorded versus reconciled tips.
- Approved but unpaid tip liability.
- Tip payouts and payout method.
- Tip reversals, chargebacks, and adjustments.
- Pooled-tip calculation details.
- Service-charge collections and distributions.
- Cash-register reconciliation including tips.

## Permissions

Permissions should be granular rather than using one broad restaurant
permission:

- Access Waiter Screen
- Claim/release table
- Transfer table or waiter
- Send order
- Cancel unsent item
- Cancel sent item
- Swap item
- Return served item
- Print provisional bill
- Split bill
- Merge bills
- Apply discount
- Add complimentary item
- Receive payment
- Accept multiple payments
- Reopen bill
- Void bill
- Approve return/swap/void
- Open cash drawer
- Open/close own waiter shift
- Override closed waiter shift
- Transfer open tables at shift close
- Record tip
- Approve tip allocation
- Pay out tips
- Configure floors/tables/stations/KDS/printers
- View restaurant exception reports

Waiters restricted from payments must not see payment-completion controls.
Cashiers should not gain supervisor void authority merely because they can
receive payment. Administrators and super administrators should still produce
audit records when using operational actions.

## Suggested data model

The implementation should evaluate dedicated, tenant-scoped entities for:

- restaurant floors/service areas;
- restaurant tables;
- preparation stations;
- station printer/KDS configuration;
- product-to-station mapping;
- table sessions;
- restaurant orders;
- order dispatch versions;
- order lines and modifiers;
- preparation tickets and ticket lines;
- item status events;
- bill parents, splits, merges, and lineage;
- returns, swaps, wastage, and void approvals;
- tip records, allocations, approvals, payouts, and reversals.

Existing UltimatePOS transactions, transaction lines, payments, stock
movements, cash registers, accounts, users, products, variations, locations,
printers, and activity logs should be reused where safe. Restaurant operational
state should not be forced into unrelated fields if doing so would make
concurrency, audit, or reporting unreliable.

Every mutable operational record should include tenant/location ownership,
status, timestamps, creator, updater, and an optimistic-lock version or other
concurrency control.

## Printer and KDS reliability

- Use the durable Windows Print Server queue for local preparation printers.
- Give every kitchen ticket, addition, cancellation, replacement, bill, and
  receipt a stable idempotency key.
- Preparation tickets should not expire using the same policy as customer
  receipts without an explicit restaurant rule; a missed food order requires
  visible escalation.
- Show station delivery status on the Waiter Screen.
- Provide a controlled reprint that labels duplicates as `REPRINT`.
- Record who requested each reprint.
- KDS remains the authoritative preparation status when both KDS and printer
  are enabled.
- Open Drawer commands remain live-only and must never be queued.

## Required reports

- Open tables and table aging.
- Orders by waiter, table, station, location, and shift.
- Preparation time by item and station.
- Late, cancelled, returned, swapped, and wasted items.
- Bill splits and merges.
- Provisional bills awaiting payment.
- Payment collections by cashier and method.
- Voided bills and approving supervisors.
- Discounts and complimentary items.
- Waiter sales and service performance.
- Tip and service-charge reports described above.
- Stock variance attributable to restaurant exceptions.

## Concurrency and audit requirements

- Table claiming, bill completion, payment posting, and table release must be
  transactional.
- Lock or version-check an order while splitting, merging, paying, or voiding.
- Prevent two users from settling the same bill.
- Use idempotency identifiers for order sends, printer delivery, KDS delivery,
  M-PESA callbacks, and payment completion.
- Store append-only status events for sensitive actions.
- Never hard-delete a sent order, payment, return, swap, void, tip, or stock
  reversal.
- Record before/after values, user, terminal, reason, approval, and timestamp.

## Architecture recommendations

### 1. Deliver a focused MVP first

The first production pilot should include only:

- waiter PIN and active-shift validation;
- floors, service areas, and tables;
- atomic table claiming;
- product-to-preparation-station mapping;
- incremental **Send Order**;
- Kitchen/Bar printing and KDS;
- provisional billing;
- designated-cashier or permitted-waiter payment;
- automatic table release after successful settlement.

Bill splitting, merging, swaps, tips, advanced voids, complex pooling, and other
exception workflows should follow after the core order lifecycle is stable.
Database foundations may anticipate those features, but unfinished controls
must remain disabled and invisible during the MVP.

### 2. Keep operational reference numbers separate

Use distinct identifiers for:

- **Order number:** the complete table/customer order.
- **Dispatch or kitchen-ticket number:** one submission to preparation
  stations.
- **Bill number:** the provisional amount awaiting payment.
- **Invoice/receipt number:** the finalized fiscal/commercial document created
  after settlement.
- **Payment reference:** the Cash, M-PESA, Card, Credit, or split-payment
  reference.

A provisional bill must not consume a final invoice/receipt number. Every
number should remain searchable, tenant-scoped, unique within its defined
sequence, and linked to the same table session.

### 3. Use an append-only order-event history

Previously committed activity should be represented by new events instead of
overwriting or deleting historical state.

Example events:

```text
TABLE_CLAIMED
ITEM_ADDED
ORDER_SENT
ITEM_ACCEPTED
ITEM_PREPARING
ITEM_READY
ITEM_SERVED
ITEM_CANCELLED
ITEM_RETURNED
ITEM_SWAPPED
BILL_PRINTED
BILL_SPLIT
BILLS_MERGED
PAYMENT_RECEIVED
BILL_VOIDED
TABLE_RELEASED
```

Current order/table status can be projected from these events or maintained as
a validated summary for performance. The event history remains the source for
audit, dispute resolution, printer/KDS recovery, and stock reconstruction.

### 4. Model each customer visit as a table session

A table session should connect:

- table, location, floor, and service area;
- primary waiter and waiter transfers;
- guest count and seats;
- orders and dispatch versions;
- preparation tickets;
- provisional and split/merged bills;
- payments, tips, service charges, returns, and voids;
- opening, transfer, settlement, and release history.

The table must remain occupied until every linked bill is paid, transferred, or
validly voided. Releasing the visible table without resolving its child bills
must be prevented.

### 5. Reserve or consume restaurant stock before final payment

Restaurant ingredients should normally be reserved or consumed when an order
is sent to or accepted by preparation, rather than waiting until the customer
pays. This reduces overselling while tables remain open for long periods.

The exact inventory event must be configurable and tested against recipe/BOM
behaviour. Subsequent exceptions must be classified explicitly:

- returned to available stock;
- preparation cancelled before consumption;
- prepared-item wastage;
- customer return;
- supervisor-authorized stock adjustment.

Prepared food must not automatically return to saleable stock. Financial sale
completion and operational ingredient consumption should remain linked even
when they occur at different times.

### 6. Route through preparation stations

Products should map to stable preparation stations such as Kitchen, Bar,
Barista, or Pastry. Printers and KDS devices should then map to those stations.

This avoids editing every product when hardware changes, supports both printer
and KDS delivery, allows a fallback device, and keeps preparation reporting
consistent.

### 7. Make settlement atomic and idempotent

The following operations must complete in one controlled transaction:

- validate the open bill and amount due;
- create/confirm payments;
- post cash-register and account entries;
- finalize the transaction and invoice;
- apply required stock/accounting finalization;
- mark linked bills paid;
- release the table when all linked bills are resolved.

If any required operation fails, the system must not leave a partly paid bill
or prematurely available table. Idempotency keys and database constraints must
protect M-PESA callbacks, double clicks, browser retries, and two cashiers
attempting to settle the same bill.

### 8. Keep shift and register rules independent

In a designated-cashier setup:

- the waiter needs an active service shift;
- the waiter does not need a personal cash register merely to place orders;
- the designated cashier needs an open register to settle the bill.

In a waiter-payment setup, the waiter needs both an active shift and the
required open register before receiving payment. Closing a shift must resolve
or transfer its open tables, bills, unsettled cash, and pending tips.

### 9. Treat tips as a controlled liability

Tips collected through the POS should normally remain payable to staff until
approved and paid out:

```text
Recorded → Reconciled → Approved → Paid out
                     ↘ Adjusted/Reversed
```

Tips must remain separate from product revenue and service charges. A waiter
must not approve their own tip, an approved tip must not be editable without a
reversal, and the same tip must never be paid twice.

### 10. Use feature flags and a controlled pilot

Restaurant Mode should be enabled per business/location. The first pilot
should use:

- one business and location;
- one floor and a limited number of tables;
- Kitchen and Bar preparation stations;
- one waiter and one designated cashier;
- a limited menu with verified station and recipe mappings.

Before expansion, test concurrent table selection, repeated Send Order clicks,
printer outages, KDS reconnection, split payments, M-PESA callbacks, shift
closure, stock reversals, duplicate settlement attempts, and table release.
Maintain a rollback plan and manual service fallback throughout the pilot.

## Implementation phases

### Phase 1: Foundations

- Feature flag per business/location.
- Restaurant permissions and waiter PIN authentication.
- Waiter shifts, register eligibility rules, and shift-close handover.
- Floors, service areas, tables, and table states.
- Preparation stations and product routing.

### Phase 2: Ordering and preparation

- Waiter Screen and table claiming.
- Order creation and incremental dispatch.
- Station printers.
- KDS and preparation statuses.
- Waiter/KDS live updates.

### Phase 3: Billing and payments

- Provisional bills.
- Waiter versus cashier payment permissions.
- Cash, M-PESA, card, credit, and multiple payments.
- Atomic settlement and table release.

### Phase 4: Advanced bill operations

- Split bills.
- Merge bills.
- Table/waiter transfers.
- Returns and item swaps.
- Wastage handling.

### Phase 5: Governance and tips

- Supervisor void and approval workflows.
- Tips and service charges.
- Tip pooling, approval, payout, and reversal.
- Restaurant exception and audit reports.

### Phase 6: Pilot and rollout

- Pilot with one business, location, station, and controlled menu.
- Load and concurrency testing.
- Printer/KDS failure drills.
- Cashier and waiter training.
- Staged rollout with rollback and support procedures.

## Precautions before implementation

1. Back up the database and verify restoration before migrations.
2. Keep the restaurant workflow behind a disabled feature flag until the pilot
   location is ready.
3. Preserve existing retail POS behaviour for businesses that do not enable
   Restaurant Mode.
4. Use additive migrations first; avoid rewriting large transaction tables
   during business hours.
5. Test all tenant boundaries and location permissions.
6. Test two waiters selecting the same table simultaneously.
7. Test that a closed, expired, or suspended shift cannot continue selling
   from an already-open browser session.
8. Test shift closure with open tables, unpaid bills, pending tips, and
   unsettled cash.
9. Test waiter-payment and designated-cashier register rules independently.
10. Test duplicate Send Order clicks and temporary network loss.
11. Test printer offline recovery without duplicating kitchen preparation.
12. Test KDS reconnection and state synchronization.
13. Test split/merge calculations with discounts, taxes, service charges,
    rounding, partial payments, and tips.
14. Test M-PESA pending, successful, duplicate, cancelled, and timed-out
    outcomes.
15. Test stock outcomes for cancellation, return, swap, wastage, void, and
    refund.
16. Confirm accounting and tax treatment for tips and service charges with the
    business accountant.
17. Train staff using a sandbox location before live deployment.
18. Keep manual fallback order pads and a documented outage procedure during
    the pilot.

## Minimum acceptance criteria

Restaurant Mode is not ready for production until:

- two waiters cannot accidentally create competing sessions for one table;
- a waiter cannot sell after their shift closes or bypass the restriction with
  an existing browser session;
- waiter-payment and designated-cashier register requirements behave according
  to the location configuration;
- open tables and unpaid bills are transferred or resolved before shift close;
- only new items print when an occupied table is reopened;
- every product reaches the correct printer/KDS station;
- printer and KDS retries do not create duplicate preparation;
- waiter-payment restrictions cannot be bypassed from the browser;
- cashier settlement marks the bill paid and releases the table atomically;
- split and merged bills retain complete lineage and unique numbers;
- returns, swaps, wastage, voids, and refunds produce correct stock history;
- supervisor actions appear in immutable audit reports;
- tips reconcile by payment method and cannot be paid out twice;
- all records remain isolated by business and location;
- the standard retail POS continues operating unchanged when Restaurant Mode
  is disabled.
