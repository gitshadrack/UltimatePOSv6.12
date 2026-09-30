# POS feature backport

This installation includes the requested feature backports; its version has not been changed to imply an official UltimatePOS 7.2 upgrade.

- **Reports → Z Report**: printable daily sales, returns, net sales, invoice tax, expenses less refunds, and sales collections by payment method. Requires `register_report.view`. Location access is enforced; users without `view_all_cash_register` see transactions they created. Sales use transaction date; collections use payment date, so collections can include earlier sales. Change and sales refunds reduce collections. Advance balance applied to sales appears as a separate payment method. Invoice tax is the transaction-level tax, not a recomputation of product tax. This is a live summary, with no fiscal numbering, frozen snapshot, register closing, or reset.
- **POS**: numbered product rows on create and edit; existing recalculation renumbers after rows are added or removed. These are row numbers, not device serial/IMEI numbers.
- **Profile photo**: uploaded profile image appears in the main navigation and POS header. Upload through the existing Profile page.
- **Invoice Layouts**: “Hide payment date on invoice” beside Show Payments. Existing layouts continue showing dates by default. Applies to bundled receipt templates that display payment dates.
- **Scrolling**: menu and main content have independent scroll areas; print layout remains unrestricted.
- **Payments**: account Select2 dropdown attaches to its payment modal; missing default payment method entries no longer throw an exception.
- **Arabic**: account/logout menu aligns using the document's writing direction.
- **Product History**: CSV/Excel filenames include product variation ID, location ID, and export date; export permissions remain in effect.
- **Contacts**: permissions are checked against the actual contact type before CRUD, status changes, ledger, payment history, and supplier stock report access. Own-view permission checks ownership or assignment and does not grant create/edit/delete. Viewing shared Customer & Supplier contacts accepts either matching view permission; changing shared contacts requires both. Import rows also enforce contact type creation permissions. Contact map respects customer/supplier view permissions.

No database migration is required. Existing customizations were preserved. Asset version was incremented to refresh JavaScript caches. Validate the visual layout, Arabic menu, printing, and modal dropdowns in an authenticated browser before rollout.

Automated coverage: isolated in-memory report calculations (date boundaries, tenant/location/user scope, drafts, refunds, change, split payments, and prior-day collections) and contact permission combinations.
what o