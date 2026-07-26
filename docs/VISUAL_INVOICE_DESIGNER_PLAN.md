# Visual Invoice and Receipt Designer

Status: Future undertaking  
Project: UltimatePOS V6.12  
Document date: 26 July 2026

## 1. Objective

Build a tenant-aware visual invoice and receipt designer that allows authorized users to:

- Drag components onto an invoice or receipt canvas.
- Reorder, move and resize components.
- Change fonts, colors, borders, spacing and alignment.
- Add static text and images.
- Bind components to approved business, customer, transaction and product fields.
- Display existing custom fields.
- Configure line-item table columns.
- Preview a template using an existing or sample transaction.
- Produce browser-printable invoices and receipts.
- Produce PDF invoices with output reasonably matching the browser preview.
- Preserve every existing UltimatePOS invoice layout as a fallback.

This feature should be introduced as a new designer rather than expanding the existing checkbox-based layout form.

## 2. Why Existing Templates Feel Different

UltimatePOS currently stores invoice labels, display toggles and a `design` identifier in `invoice_layouts`.

At print time:

1. `SellPosController::receiptContent()` loads the selected invoice layout.
2. `TransactionUtil::getReceiptDetails()` builds a receipt-data object.
3. The layout's `design` value selects a Blade file under:

   ```text
   resources/views/sale_pos/receipts/
   ```

Existing designs include:

- `classic.blade.php`
- `columnize-taxes.blade.php`
- `detailed.blade.php`
- `elegant.blade.php`
- `english-arabic.blade.php`
- `slim.blade.php`
- `slim2.blade.php`
- `tax-invoice.blade.php`

Each design is an independently authored Blade template. They have different HTML structures and print styles, so the same label or setting can appear differently between designs. The current invoice-layout settings change values and visibility; they do not change the underlying component placement.

Relevant current files:

- `app/Http/Controllers/InvoiceLayoutController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/TransactionUtil.php`
- `app/InvoiceLayout.php`
- `resources/views/invoice_layout/create.blade.php`
- `resources/views/invoice_layout/edit.blade.php`
- `resources/views/sale_pos/receipts/*.blade.php`

## 3. Recommended Architecture

Use a constrained, schema-driven designer rather than allowing users to write Blade, JavaScript or arbitrary HTML.

The designer should have three main areas:

```text
+----------------------+--------------------------------+----------------------+
| Component palette    | Paper/canvas                   | Properties           |
|                      |                                |                      |
| Business             | Drag and position components   | Binding              |
| Customer             | Preview real data              | Font                 |
| Invoice              | Snap to grid                   | Color                |
| Product table        | Show margins/page boundaries   | Size                 |
| Totals               |                                | Alignment            |
| Payments             |                                | Visibility           |
| Text / image / line  |                                | Border / spacing     |
+----------------------+--------------------------------+----------------------+
```

### 3.1 Suggested components

- Business logo
- Business name
- Location name and address
- Telephone, email and website
- Tax/PIN details
- Customer name and address
- Customer telephone and tax number
- Invoice number
- Invoice date and due date
- Salesperson
- Cashier
- Static text
- Bound field
- Uploaded image
- Horizontal or vertical line
- Box/panel
- Barcode
- QR code
- Line-items table
- Tax summary
- Subtotal
- Discount
- Shipping
- Grand total
- Amount paid
- Balance due
- Payment-history table
- Signature line
- Terms and conditions
- Footer
- Existing transaction custom fields
- Existing customer custom fields
- Existing product custom fields
- Existing location custom fields

## 4. Separate Page and Thermal Layout Behaviour

A4/Letter invoices and thermal receipts must not use exactly the same positioning model.

### 4.1 A4 and Letter invoices

Support:

- Free positioning in millimetres.
- Dragging and resizing.
- Portrait and landscape orientation.
- Configurable margins.
- Snap-to-grid.
- Alignment guides.
- Rulers.
- Page boundaries.
- Repeating headers and footers.
- Controlled page breaks.
- Multi-page product tables.

Example profiles:

```text
A4 portrait:    210 mm x 297 mm
A4 landscape:   297 mm x 210 mm
Letter portrait: 215.9 mm x 279.4 mm
```

### 4.2 Thermal receipts

Support:

- 58 mm and 80 mm paper profiles.
- Vertical component ordering.
- Adjustable usable width and margins.
- Adjustable product-table column widths.
- Font size and line-spacing controls.
- Automatic height based on products, payments and notes.

Avoid unrestricted absolute positioning for thermal receipts. Their height changes for every transaction and their usable width varies by printer and driver.

## 5. Database Design

Prefer a separate table so existing `invoice_layouts` remains backward compatible.

Suggested migration:

```text
invoice_designer_templates
--------------------------
id
business_id
invoice_layout_id
name
document_type
paper_type
schema_version
design_json
is_active
created_by
updated_by
created_at
updated_at
```

Suggested types:

```text
document_type:
- invoice
- receipt
- quotation
- credit_note
- packing_slip
- delivery_note

paper_type:
- a4_portrait
- a4_landscape
- letter_portrait
- thermal_80mm
- thermal_58mm
```

Required constraints and indexes:

- Foreign key from `business_id` to `business.id`.
- Foreign key from `invoice_layout_id` to `invoice_layouts.id`.
- Foreign keys from `created_by` and `updated_by` to `users.id`, where practical.
- Index on `business_id`.
- Index on `invoice_layout_id`.
- Index on `is_active`.
- Every read and write must be scoped by the authenticated user's business.

Consider a separate revision table in a later phase:

```text
invoice_designer_template_versions
----------------------------------
id
template_id
schema_version
design_json
created_by
created_at
```

## 6. JSON Layout Schema

Store a validated JSON document, not generated HTML.

Example:

```json
{
  "version": 1,
  "paper": {
    "type": "a4",
    "width_mm": 210,
    "height_mm": 297,
    "orientation": "portrait",
    "margin_top_mm": 10,
    "margin_right_mm": 10,
    "margin_bottom_mm": 10,
    "margin_left_mm": 10
  },
  "components": [
    {
      "id": "invoice-number",
      "type": "field",
      "binding": "invoice.invoice_no",
      "label": "Invoice No:",
      "frame": {
        "x_mm": 145,
        "y_mm": 18,
        "width_mm": 45,
        "height_mm": 8
      },
      "style": {
        "font_family": "Arial",
        "font_size_pt": 12,
        "font_weight": "bold",
        "text_align": "right",
        "color": "#16324f"
      }
    },
    {
      "id": "products",
      "type": "line_items_table",
      "frame": {
        "x_mm": 10,
        "y_mm": 80,
        "width_mm": 190
      },
      "columns": [
        {
          "binding": "product",
          "label": "Description",
          "width_percent": 45
        },
        {
          "binding": "quantity",
          "label": "Qty",
          "width_percent": 15
        },
        {
          "binding": "unit_price",
          "label": "Price",
          "width_percent": 20
        },
        {
          "binding": "subtotal",
          "label": "Total",
          "width_percent": 20
        }
      ]
    }
  ]
}
```

### 6.1 Schema requirements

The server must validate:

- Supported schema version.
- Supported paper profiles.
- Maximum component count.
- Unique component IDs.
- Allowed component types.
- Allowed field bindings.
- Allowed style properties and values.
- Numeric position and size limits.
- Components remain inside the paper boundary.
- Minimum and maximum font sizes.
- Image source belongs to the current business.
- Table column widths produce a valid total.
- Conditions use an approved declarative format.

Do not evaluate JavaScript expressions stored in the design.

## 7. Field Registry

Create a central allowlist of fields that can be dragged onto the canvas.

Example scalar bindings:

```text
business.name
business.logo
business.tax_number_1
business.tax_number_2
business.currency

location.name
location.address
location.mobile
location.email
location.website

customer.name
customer.address
customer.mobile
customer.email
customer.tax_number
customer.balance

invoice.invoice_no
invoice.reference_no
invoice.date
invoice.due_date
invoice.status
invoice.payment_status
invoice.salesperson
invoice.cashier
invoice.notes

totals.subtotal
totals.discount
totals.tax
totals.shipping
totals.rounding
totals.grand_total
totals.paid
totals.balance
totals.previous_balance
```

Example repeatable bindings:

```text
invoice.lines
invoice.payments
invoice.taxes
```

Existing `TransactionUtil::getReceiptDetails()` already prepares much of the required information. Introduce a data-binding adapter that converts the receipt object into a stable designer-data contract. The visual designer should bind to that contract instead of directly depending on every property of the current receipt object.

Example service:

```text
app/Services/InvoiceDesigner/DataBindingResolver.php
```

## 8. Custom Fields

Support three distinct concepts.

### 8.1 Static custom content

Content entered directly in the designer:

- Text
- Heading
- Legal notice
- Payment instructions
- Bank details
- Signature caption

Sanitize it and prohibit scripts, event attributes and unsafe URLs.

### 8.2 Existing UltimatePOS custom fields

Expose approved bindings for:

- Sale custom fields
- Customer/contact custom fields
- Product custom fields
- Location custom fields
- Service custom fields, where enabled

The designer should show the configured label and allow the user to choose whether to display:

- Label and value
- Value only
- Only when the value is not empty

### 8.3 New transaction-level fields

If users need completely new values entered during a sale, introduce dedicated field definitions and transaction values rather than embedding values in the template.

Possible future tables:

```text
custom_document_fields
custom_document_field_values
```

These fields require:

- Definition ownership by business.
- Data type validation.
- POS/sale form inputs.
- Transaction association.
- Reporting/export rules.
- Permission checks.

They should be a separate project phase.

## 9. Front-End Technology

### 9.1 Recommended approach

Build a purpose-specific editor and use `interact.js` for:

- Dragging
- Resizing
- Snap-to-grid
- Size restrictions
- Canvas-boundary restrictions

Official references:

- [interact.js documentation](https://interactjs.io/docs/)
- [Dragging](https://interactjs.io/docs/draggable/)
- [Snapping](https://interactjs.io/docs/snapping/)
- [Restrictions](https://interactjs.io/docs/restriction/)

The editor should own movement and state updates. `interact.js` reports pointer changes but intentionally leaves element positioning and state persistence to the application.

### 9.2 Alternative: GrapesJS

GrapesJS offers:

- Component management
- Style management
- Custom component types
- JSON project data
- Storage APIs

Official references:

- [GrapesJS getting started](https://grapesjs.com/docs/Home.html)
- [Component Manager](https://grapesjs.com/docs/modules/Components.html)
- [Style Manager](https://grapesjs.com/docs/modules/Style-manager.html)
- [Storage Manager](https://grapesjs.com/docs/modules/Storage.html)

GrapesJS is a general page builder. Using it would still require:

- A strict component allowlist.
- A custom data-binding system.
- Custom line-items and payments components.
- Removal of unsafe HTML/script editing.
- Paper profiles and print rules.
- Server-side JSON validation.
- A server renderer independent of GrapesJS-generated HTML.

For UltimatePOS, a focused editor is preferred because it gives tighter control over multitenancy, security, repeating tables, paper sizes and printing.

## 10. Designer User Experience

Recommended functionality:

- Add component from palette.
- Select component.
- Drag component.
- Resize component.
- Change style in property panel.
- Delete component.
- Duplicate component.
- Lock/unlock component.
- Bring forward/send backward.
- Align left, center, right, top and bottom.
- Distribute horizontally or vertically.
- Snap to grid.
- Toggle rulers and guides.
- Zoom without changing actual dimensions.
- Undo and redo.
- Keyboard movement for selected components.
- Keyboard delete with confirmation where appropriate.
- Copy and paste components.
- Preview using a selected transaction.
- Preview using sample data.
- Switch paper profile.
- Clone a template before editing.
- Save draft.
- Activate template.
- Revert to a previous version.

Recommended layout:

```text
Left panel:
- Component categories
- Search components

Center:
- Paper canvas
- Rulers
- Guides
- Page boundaries
- Preview

Right panel:
- Content/binding
- Position and size
- Typography
- Border and background
- Conditional visibility
- Table columns
```

## 11. Rendering Pipeline

Create a dedicated rendering layer:

```text
app/Services/InvoiceDesigner/
|-- ComponentRegistry.php
|-- DataBindingResolver.php
|-- DesignValidator.php
|-- HtmlRenderer.php
|-- PaperProfile.php
|-- TemplateRepository.php
`-- Components/
    |-- TextComponent.php
    |-- FieldComponent.php
    |-- ImageComponent.php
    |-- LineItemsTableComponent.php
    |-- PaymentsTableComponent.php
    |-- TotalsComponent.php
    `-- QrCodeComponent.php
```

Runtime flow:

```text
Transaction
    |
    v
TransactionUtil::getReceiptDetails()
    |
    v
DataBindingResolver
    |
    v
Validated design JSON
    |
    v
HtmlRenderer
    |
    +--> Browser print
    |
    `--> PDF renderer
```

Add one generic Blade view:

```text
resources/views/sale_pos/receipts/visual-designer.blade.php
```

The Blade view should render only server-approved component output. It should not execute template-provided PHP, Blade or JavaScript.

## 12. Integration With Existing Layouts

Existing invoice layouts must continue to work unchanged.

Suggested behaviour:

1. Add a new `visual-designer` design option or a dedicated designer-enabled relationship.
2. When a valid active designer template exists, use `HtmlRenderer`.
3. Otherwise, render the existing Blade template selected by `invoice_layouts.design`.
4. Allow users to deactivate the designer and return immediately to a legacy design.
5. Do not convert or overwrite legacy templates automatically.

Potential controller logic:

```php
if ($invoiceLayout->activeDesignerTemplate) {
    $output['html_content'] = $designerRenderer->render(
        $invoiceLayout->activeDesignerTemplate,
        $receiptDetails
    );
} else {
    $layout = ! empty($receiptDetails->design)
        ? 'sale_pos.receipts.'.$receiptDetails->design
        : 'sale_pos.receipts.classic';

    $output['html_content'] = view($layout, compact('receiptDetails'))->render();
}
```

The final implementation must follow the project's actual receipt variable names and coding conventions.

## 13. Browser Printing and CSS

Use physical CSS units for printable dimensions:

```css
@page {
    size: A4 portrait;
    margin: 0;
}

.invoice-page {
    position: relative;
    width: 210mm;
    min-height: 297mm;
}

.invoice-component {
    position: absolute;
}
```

Thermal example:

```css
@page {
    size: 80mm auto;
    margin: 0;
}

.thermal-receipt {
    width: 80mm;
}
```

Browser preview and physical output can differ because of printer margins, scaling settings, drivers and unsupported borderless printing. Provide a print calibration page and instruct users to print at 100% scale with browser headers and footers disabled.

## 14. PDF Rendering

The project currently uses mPDF for invoice PDFs.

Risks:

- Browser CSS and mPDF CSS support are not identical.
- Absolute positioning may behave differently.
- Multi-page repeating tables need special handling.
- Font availability can differ.
- Images and QR codes need server-accessible sources.

Recommended approach:

1. Define a deliberately limited print-CSS subset supported by both browser printing and mPDF.
2. Test all supported components in both renderers.
3. Use explicit page-break rules.
4. Keep repeating tables in normal document flow where possible.
5. Do not promise pixel-perfect browser/PDF parity until verified against representative templates.

## 15. Direct Receipt Printer Limitation

`SellPosController::receiptContent()` currently has two output paths:

```text
Browser printer:
- Render receipt Blade into HTML.

Configured/direct printer:
- Return printer configuration and structured receipt data.
```

The visual HTML designer will initially affect browser printing and PDF output only.

Direct ESC/POS or connector printing requires another renderer that converts supported components into printer commands or a printer-specific structured format. Direct printers typically cannot reproduce arbitrary:

- Absolute positioning
- Web fonts
- Complex borders
- Background colors
- Overlapping elements
- Browser CSS

Recommended rollout:

1. Browser print for A4 and 80 mm.
2. Browser print for 58 mm.
3. PDF rendering.
4. Restricted direct-printer layout support.

The UI must clearly state which output modes a template supports.

## 16. Security Requirements

Mandatory controls:

- Require existing `invoice_settings.access` permission for designer access.
- Consider separate permissions for design, preview and activation.
- Scope every template query by `business_id`.
- Validate every saved JSON document on the server.
- Allow only registered component types.
- Allow only registered field bindings.
- Allow only safe CSS properties.
- Reject JavaScript, Blade and PHP.
- Sanitize static rich text.
- Restrict image upload type and size.
- Verify image ownership by business.
- Protect save/activate/delete routes with CSRF.
- Use POST/PUT/DELETE for state changes.
- Log template activation and deletion.
- Keep version history or an audit trail.
- Rate-limit autosave endpoints.
- Limit JSON size and component count.
- Never trust coordinates, styles or bindings supplied by the browser.

Do not persist arbitrary generated HTML as the source of truth. Persist validated JSON and render it on the server.

## 17. Routes and Controllers

Possible routes:

```text
GET    /invoice-designer
POST   /invoice-designer
GET    /invoice-designer/{template}
PUT    /invoice-designer/{template}
DELETE /invoice-designer/{template}
POST   /invoice-designer/{template}/clone
POST   /invoice-designer/{template}/activate
POST   /invoice-designer/{template}/preview
GET    /invoice-designer/field-registry
```

Possible controller:

```text
app/Http/Controllers/InvoiceDesignerController.php
```

Every route must use:

- `web`
- `auth`
- Existing session/business middleware used by invoice settings
- `invoice_settings.access` or the future dedicated permission middleware

Use route-model binding only with additional business ownership enforcement.

## 18. Preview Strategy

Support two preview modes.

### 18.1 Sample-data preview

Advantages:

- Always available.
- Safe for a new business.
- Covers long names and large totals.
- Can deliberately test multi-page products.

Include sample cases:

- Short receipt
- Long receipt
- Large product description
- Discount and tax
- Multiple payments
- Amount due
- Customer custom fields
- Multi-page A4 invoice

### 18.2 Real-transaction preview

Allow the user to select a transaction from their own business.

Security requirements:

- Enforce business ownership.
- Require invoice/report access.
- Do not expose transactions from another tenant.
- Use a read-only endpoint.

## 19. Autosave and Concurrency

Recommended approach:

- Keep editor changes locally until the design is valid.
- Debounce autosave.
- Send a revision number with each update.
- Reject stale updates with a clear conflict message.
- Provide a visible state: `Unsaved`, `Saving`, `Saved`, or `Save failed`.
- Keep an explicit `Save` button even if autosave exists.
- Do not activate an unsaved or invalid draft.

Example fields:

```text
revision
last_saved_at
updated_by
```

## 20. Phased Delivery

### Phase 1: Browser-print MVP

- Migration and tenant-scoped model.
- Designer list/create/edit pages.
- A4 portrait and 80 mm paper profiles.
- Field registry.
- Text, bound field, image, line, totals and line-items components.
- Dragging and resizing.
- Thermal vertical ordering.
- Property editor.
- JSON validation.
- Save/load.
- Sample and real-transaction preview.
- Browser printing.
- Legacy-template fallback.
- Clone template.
- Activate/deactivate template.

### Phase 2: Extended design controls

- 58 mm paper.
- A4 landscape and Letter.
- Product/custom-field bindings.
- Payment and tax tables.
- Undo and redo.
- Rulers, guides and alignment tools.
- Component lock and layers.
- Conditional visibility.
- Version history.
- Import/export validated template JSON.

### Phase 3: Output parity

- PDF renderer parity.
- Multi-page product-table refinement.
- Repeating page headers and footers.
- Print calibration.
- Supported-font management.
- Restricted direct/ESC-POS printer renderer.

### Phase 4: Advanced custom data

- Business-defined document fields.
- POS/sale form inputs for those fields.
- Transaction-value storage.
- Reporting and exports.
- Per-document-type templates.
- Shared system template library that tenants can clone.

## 21. Testing Requirements

### 21.1 Unit tests

- JSON schema validation.
- Allowed and rejected components.
- Allowed and rejected bindings.
- CSS/style validation.
- Paper-boundary enforcement.
- Business ownership.
- Data binding.
- Conditional visibility.
- Line-item column validation.
- Schema migration between versions.

### 21.2 Feature tests

- Permission enforcement.
- Tenant isolation.
- Create, update, clone, activate and delete.
- Cannot activate invalid template.
- Preview only accesses the current business's transactions.
- Legacy fallback when designer is missing or inactive.
- Safe handling of malformed JSON.
- CSRF protection.
- Audit logging.

### 21.3 Rendering tests

- Empty and populated customer data.
- Zero, one and many products.
- Long product names.
- Multiple tax rates.
- Discounts.
- Multiple payments.
- Outstanding balance.
- Custom fields.
- 58 mm and 80 mm widths.
- A4 single page.
- A4 multiple pages.
- Logo and QR code.
- Browser and PDF comparison.

### 21.4 Security tests

- Script tags in custom text.
- Event attributes such as `onerror`.
- `javascript:` URLs.
- Unsupported bindings.
- Cross-tenant template access.
- Cross-tenant image access.
- Oversized JSON.
- Excessive component counts.
- Invalid positions and sizes.
- Unauthorized activation/deletion.

## 22. Deployment and Rollback

Before deployment:

1. Back up the database.
2. Test migrations on a copy.
3. Test existing invoice layouts.
4. Confirm no existing layout is automatically converted.
5. Test browser printing on representative printers.
6. Test PDFs.

Deployment:

```bash
php artisan migrate
php artisan optimize:clear
php artisan optimize
php artisan event:cache
php artisan view:cache
```

Rollback strategy:

- Deactivate visual designer templates.
- Existing locations continue using their selected legacy invoice layouts.
- Roll back code before removing designer data.
- Do not drop designer tables until rollback is confirmed.

The migration should not alter or delete existing `invoice_layouts` data.

## 23. Acceptance Criteria for the First Release

The first release is acceptable when:

- A permitted user can create a tenant-owned template.
- The user can add, drag, resize and remove supported components.
- The user can bind approved fields.
- The user can configure a product table.
- The user can preview with sample and real transaction data.
- The server rejects unsafe or invalid JSON.
- Another business cannot access the template or preview transaction.
- The user can activate and deactivate the template.
- Browser-printed A4 and 80 mm output is usable.
- Existing layouts remain unchanged.
- A rendering failure safely falls back to the selected legacy layout or returns a controlled error without breaking sale completion.
- Audit information identifies who changed and activated a template.

## 24. Recommended Initial Decision

Start with a constrained custom editor using `interact.js`.

The first implementation should support:

- Browser printing only.
- A4 portrait and 80 mm receipt profiles.
- Existing receipt-data bindings.
- Static text.
- Logo/image.
- Product table.
- Totals.
- Basic lines, boxes and styling.
- Real and sample preview.
- Legacy fallback.

Do not include unrestricted HTML, arbitrary JavaScript, direct-printer parity or completely new transaction data fields in the first phase.

