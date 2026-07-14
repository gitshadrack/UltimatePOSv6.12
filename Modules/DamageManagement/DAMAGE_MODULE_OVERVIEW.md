# Damage Management Module - Complete Overview

## 📋 Module Information
- **Purpose**: Track damaged products, manage dispatches, and handle compensation claims
- **Author**: Munshi Sohag Hossen (Dedicated to Mr. Jalal)
- **Language**: PHP (Laravel Framework)
- **Installation**: Drop-in files + integration hooks

---

## 🎯 Module Purpose

The Damage Management module provides a comprehensive system to:
1. **Record damaged products** - Track when products are damaged with full details
2. **Manage compensation** - Calculate expected compensation based on purchase/sell value
3. **Dispatch handling** - Create dispatches to send damaged products to suppliers/manufacturers
4. **Track dispatch status** - Monitor not_dispatched, partial, and dispatched states
5. **Financial tracking** - Track purchase value, sell value, and compensation amounts
6. **Reporting** - Print damage records and dispatch documents

---

## 📁 File Structure

```
damage_managem/
├── Models (4)
│   ├── DamageRecord.php           # Main damage record model
│   ├── DispatchDamage.php        # Dispatch document model
│   └── DispatchDamageLine.php    # Dispatch line items
│
├── Controllers (2)
│   ├── DamageRecordController.php    # CRUD for damage records
│   └── DamageDispatchController.php  # CRUD for dispatches
│
├── Migrations (5)
│   ├── 2025_10_10_000001_create_damage_records_table.php
│   ├── 2025_10_10_000002_create_dispatch_damages_table.php
│   ├── 2025_10_10_000003_create_dispatch_damage_lines_table.php
│   ├── 2025_10_10_000004_add_given_compensation_to_damage_records_table.php
│   └── 2025_10_10_000005_add_indexes_to_contacts_and_dispatch_tables.php
│
├── Views (8 Blade files - NOTE: Should be in resources/views/damage/)
│   ├── record/
│   │   ├── index.blade.php    # Damage records listing
│   │   ├── create.blade.php    # Add new damage record
│   │   ├── edit.blade.php     # Edit damage record
│   │   ├── show.blade.php     # View damage details
│   │   └── print.blade.php    # Print damage record
│   └── dispatch/
│       ├── index.blade.php    # Dispatch listing
│       ├── create.blade.php   # Create dispatch
│       └── print.blade.php    # Print dispatch
│
├── JavaScript (4)
│   ├── damage_records.js          # Damage records page JS
│   ├── damage_records_form.js    # Create/edit form JS
│   ├── damage_dispatch.js         # Dispatch creation JS
│   └── damage_management_form.js  # Form utilities
│
├── Language Files (2)
│   ├── damage.php (English translations)
│   └── about.php (About modal)
│
└── README_DAMAGE_PATCH.md  # Integration guide
```

---

## 🗄️ Database Structure

### 1. **damage_records** Table
Main table for storing damage records.

**Key Fields:**
- `id` - Primary key
- `business_id` - Foreign key to business
- `location_id` - Location where damage occurred
- `product_id` - Damaged product
- `variation_id` - Product variation
- `brand_id`, `category_id`, `unit_id` - Product details
- `customer_id`, `supplier_id` - Related contacts
- `reference_no` - Auto-generated (DMG-YYYYMMDD-####)
- `dispatch_status` - Enum: 'not_dispatched', 'partial', 'dispatched'
- `reported_at` - When damage was reported
- `quantity` - Damaged quantity
- `dispatched_quantity` - How much has been dispatched
- `unit_purchase_price`, `unit_sell_price` - Unit prices
- `purchase_value`, `sell_value` - Total values
- `expected_compensation` - Calculated compensation
- `given_compensation` - Actually given compensation (added later)
- `compensation_basis` - Enum: 'purchase', 'sell', 'manual'
- `notes` - Additional notes
- `created_by` - User who created the record

**Relationships:**
- belongsTo(Business)
- belongsTo(BusinessLocation)
- belongsTo(Product)
- belongsTo(Variation)
- belongsTo(Brand)
- belongsTo(Category)
- belongsTo(Unit)
- belongsTo(Contact) - customer
- belongsTo(Contact) - supplier
- hasMany(DispatchDamageLine)

---

### 2. **dispatch_damages** Table
Dispatch documents that group multiple damage records.

**Key Fields:**
- `id` - Primary key
- `business_id` - Foreign key to business
- `location_id` - Location
- `reference_no` - Auto-generated (DDG-YYYYMMDD-####)
- `dispatched_at` - Dispatch date
- `created_by` - User who created
- `total_purchase_value` - Sum of all purchase values
- `total_sell_value` - Sum of all sell values
- `total_compensation_value` - Sum of all compensations
- `notes` - Dispatch notes
- `status` - Enum: 'draft', 'final'

**Relationships:**
- belongsTo(Business)
- belongsTo(BusinessLocation)
- belongsTo(User) - createdBy
- hasMany(DispatchDamageLine) - lines

---

### 3. **dispatch_damage_lines** Table
Line items linking dispatches to damage records.

**Key Fields:**
- `id` - Primary key
- `dispatch_damage_id` - Foreign key to dispatch
- `damage_record_id` - Foreign key to damage record
- `dispatched_quantity` - Quantity dispatched in this line
- `purchase_value` - Calculated purchase value
- `sell_value` - Calculated sell value
- `compensation_amount` - Compensation for this line
- `created_at`, `updated_at`

**Relationships:**
- belongsTo(DispatchDamage)
- belongsTo(DamageRecord)

**Constraints:**
- Unique constraint on (dispatch_damage_id, damage_record_id)

---

## 🔑 Key Features

### 1. **Damage Record Management**

#### Creating Damage Records
- Multi-line support (multiple products in one record)
- Auto-detects product details (brand, category, unit)
- Auto-calculates values based on unit prices
- Compensation calculation based on:
  - Purchase value (compensation_basis = 'purchase')
  - Sell value (compensation_basis = 'sell')
  - Manual entry (compensation_basis = 'manual')
- Reference number generation: `DMG-YYYYMMDD-####`

#### Editing Damage Records
- Can edit until fully dispatched
- Cannot change quantity below dispatched quantity
- Auto-recalculates values
- Updates dispatch status

#### Deleting Damage Records
- Can only delete if not dispatched
- Fully dispatched records cannot be deleted

---

### 2. **Dispatch Management**

#### Creating Dispatches
- Select from available (not fully dispatched) damage records
- Filter by location
- Multiple records can be dispatched together
- Specify dispatch quantity per line
- Enter compensation amount per line
- Auto-generates reference: `DDG-YYYYMMDD-####`
- Calculates totals automatically

#### Dispatch Status Tracking
- **not_dispatched** - No quantities dispatched
- **partial** - Some quantity dispatched, remainder available
- **dispatched** - All quantity dispatched

#### Deleting Dispatches
- Rolls back damage record status
- Updates dispatched_quantity
- Updates dispatch_status
- Decrements given_compensation

---

### 3. **Compensation Management**

#### Expected Compensation
- Calculated on damage record creation
- Based on compensation_basis (purchase/sell/manual)
- Can be overridden manually

#### Given Compensation
- Tracked separately from expected
- Can be provided through dispatches
- Multiple dispatches can contribute to given compensation
- Shown as cumulative amount

---

## 🔐 Permissions

The module requires 7 permissions to be added to the database:

### Damage Record Permissions:
- `damage_record.view` - View damage records
- `damage_record.create` - Create damage records
- `damage_record.update` - Edit damage records
- `damage_record.delete` - Delete damage records

### Dispatch Permissions:
- `damage_dispatch.view` - View dispatches
- `damage_dispatch.create` - Create dispatches
- `damage_dispatch.delete` - Delete dispatches

---

## 🛣️ Routes

### Damage Records
```
GET  /damage-records                      # Index (list all)
POST /damage-records                       # Store new record
GET  /damage-records/create                # Create form
GET  /damage-records/{id}                  # Show details
GET  /damage-records/{id}/edit            # Edit form
PUT  /damage-records/{id}                  # Update
DELETE /damage-records/{id}                # Delete
GET  /damage-records/{id}/print            # Print
GET  /damage-records/variation-search      # Search variations
GET  /damage-records/variation/{id}        # Get variation details
GET  /damage-records/contacts              # Search contacts
```

### Dispatches
```
GET  /damage-dispatches                   # Index (list all)
POST /damage-dispatches                   # Store new dispatch
GET  /damage-dispatches/create            # Create form
GET  /damage-dispatches/{id}              # Show details
DELETE /damage-dispatches/{id}            # Delete
GET  /damage-dispatches/{id}/print        # Print
GET  /damage-dispatches/available-records # Get available records
```

---

## 💻 Controller Logic

### DamageRecordController
- **index()** - Filtered listing with DataTables
- **create()** - Form with locations and compensation basis
- **store()** - Batch creates multiple records, auto-calculates values
- **show()** - Displays full details with relationships
- **edit()** - Edit form with pre-filled data
- **update()** - Updates record, validates dispatched quantity
- **destroy()** - Deletes if not dispatched
- **print()** - Print view
- **searchContacts()** - AJAX endpoint for contact search
- **searchVariations()** - AJAX endpoint for product variation search
- **getVariationDetails()** - Get product details for selected variation

### DamageDispatchController
- **index()** - Filtered dispatch listing
- **availableRecords()** - Get records available for dispatching
- **create()** - Form with locations and available records table
- **store()** - Creates dispatch with lines, updates damage records
- **show()** - Display dispatch with all lines
- **print()** - Print dispatch document
- **destroy()** - Deletes dispatch, rolls back damage record status

---

## 🎨 Frontend Features

### DataTables Integration
- Server-side processing
- Advanced filtering:
  - Location
  - Brand
  - Category
  - Customer
  - Supplier
  - Product
  - Dispatch Status
  - Date Range

### Modal Forms
- Create/Edit damage records in modals
- View details in modals
- AJAX submissions

### Auto-calculation
- JavaScript updates values automatically
- Quantity × price = value
- Automatic compensation calculation

### Print Functionality
- Professional printable layouts
- Includes all relevant details
- Suitable for documentation

---

## 🔄 Business Flow

### 1. Damage Discovery
1. Staff discovers damaged product
2. Create damage record via Damage Records → Add
3. Select product variation
4. Enter quantity
5. System auto-fills prices and calculates values
6. Choose compensation basis
7. Save record

### 2. Dispatching Damaged Items
1. Go to Damage Dispatches → Create Dispatch
2. Filter available records by location
3. Select records to dispatch
4. Enter dispatch quantities
5. Enter compensation amounts
6. System creates dispatch document
7. Status updates to partial/dispatched

### 3. Tracking
- View all damage records with filters
- Check dispatch status
- Monitor compensation given vs expected
- Print documents for supplier/manufacturer

---

## 🔗 Integration Requirements

### Files to Add to Existing Project:

1. **Models**: Copy 3 model files to `app/`
2. **Controllers**: Copy 2 controllers to `app/Http/Controllers/`
3. **Migrations**: Copy 5 migrations to `database/migrations/`
4. **Views**: Copy views to `resources/views/damage/`
5. **JavaScript**: Copy 4 JS files to `public/js/`
6. **Language**: Copy 2 lang files to `resources/lang/en/`

### Code to Add to Existing Files:

1. **Routes** (`routes/web.php`) - Add damage routes
2. **Middleware** (`AdminSidebarMenu.php`) - Add sidebar menu
3. **Permissions** (`PermissionsTableSeeder.php`) - Add permissions
4. **Footer** (`footer.blade.php`) - Add about modal and keyboard shortcut

---

## 🎯 Use Cases

### Scenario 1: Customer Returns Damaged Item
1. Customer returns damaged product
2. Create damage record
3. Link to customer in damage record
4. Calculate expected compensation
5. Dispatch to supplier
6. Track given compensation

### Scenario 2: Warehouse Stock Damage
1. Inspect warehouse, find damaged items
2. Create damage records for all damaged items
3. Group by supplier
4. Create dispatch per supplier
5. Send dispatch document
6. Recover compensation

### Scenario 3: Transit Damage
1. Product damaged during delivery
2. Create damage record
3. Link to supplier
4. Dispatch damaged item back
5. Claim transit insurance
6. Track compensation

---

## 📊 Key Calculations

### Damage Record
```php
purchase_value = quantity × unit_purchase_price
sell_value = quantity × unit_sell_price

if (compensation_basis == 'purchase') {
    expected_compensation = purchase_value
} else if (compensation_basis == 'sell') {
    expected_compensation = sell_value
} else {
    expected_compensation = manual_input
}
```

### Dispatch
```php
line_purchase_value = dispatched_quantity × unit_purchase_price
line_sell_value = dispatched_quantity × unit_sell_price

total_purchase_value = sum of all line_purchase_values
total_sell_value = sum of all line_sell_values
total_compensation_value = sum of all compensation_amounts
```

### Damage Record Update (after dispatch)
```php
given_compensation += line_compensation_amount
dispatched_quantity += line_dispatched_quantity

if (dispatched_quantity >= quantity) {
    dispatch_status = 'dispatched'
} else if (dispatched_quantity > 0) {
    dispatch_status = 'partial'
}
```

---

## 🎨 UI/UX Features

### Search & Filter
- Location dropdown
- Brand/Category filters
- Customer/Supplier search (with Select2)
- Product search
- Status filter
- Date range picker

### Data Display
- Formatted numbers (currency with symbols)
- Formatted dates
- Status badges
- Remaining quantity calculation
- Action buttons (View, Edit, Delete, Print)

### Validation
- Quantity must be > 0
- Dispatch quantity cannot exceed remaining quantity
- Cannot edit/delete fully dispatched records
- Reference number auto-generation

---

## 🔧 Technical Details

### Eloquent Relationships
```php
// DamageRecord Model
- belongsTo(Business, Location, Product, Variation, Brand, Category, Unit)
- belongsTo(Contact) x2 (customer, supplier)
- hasMany(DispatchDamageLine)

// DispatchDamage Model
- belongsTo(Business, Location, User)
- hasMany(DispatchDamageLine)

// DispatchDamageLine Model
- belongsTo(DispatchDamage, DamageRecord)
```

### Form Helpers
- Uses Laravel collective forms
- Select2 for dropdowns
- jQuery for AJAX
- DataTables for listings
- Datetime picker for dates

### Reference Generation
```php
// Damage Record
DMG-YYYYMMDD-####

// Dispatch
DDG-YYYYMMDD-####
```

### Number Formatting
Uses `TransactionUtil::num_f()` for:
- Formatting currency values
- Formatting quantities
- Proper decimal handling

---

## 📦 Installation Steps

1. **Copy Files**
   - Copy models, controllers to app/
   - Copy migrations to database/migrations/
   - Copy views to resources/views/damage/
   - Copy JS to public/js/
   - Copy lang files to resources/lang/en/

2. **Add Routes**
   - Add damage routes to `routes/web.php`

3. **Add Menu**
   - Add sidebar menu to `AdminSidebarMenu.php`

4. **Add Permissions**
   - Add to `PermissionsTableSeeder.php`

5. **Add Footer Code**
   - Add modal and keyboard shortcut to footer

6. **Run Migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed Permissions**
   ```bash
   php artisan db:seed --class=PermissionsTableSeeder
   ```

8. **Clear Cache**
   ```bash
   php artisan optimize:clear
   ```

9. **Assign Permissions**
   - Assign permissions to roles in admin panel

---

## 🧪 Testing Checklist

- [ ] Create single-line damage record
- [ ] Create multi-line damage record
- [ ] Edit damage record (before dispatch)
- [ ] Try editing dispatched record (should fail)
- [ ] Delete damage record (not dispatched)
- [ ] Create dispatch with single record
- [ ] Create dispatch with multiple records
- [ ] Partial dispatch (not all quantity)
- [ ] Full dispatch
- [ ] Delete dispatch (should rollback)
- [ ] All filters work correctly
- [ ] Print functionality works
- [ ] Permissions enforced correctly
- [ ] Search contacts works
- [ ] Search products works
- [ ] Keyboard shortcut (Ctrl+Shift+D) works

---

## 💡 Key Concepts

### Compensation Basis
- **Purchase**: Expected compensation = purchase value
- **Sell**: Expected compensation = sell value
- **Manual**: User enters expected compensation

### Dispatch Status
- **not_dispatched**: Nothing dispatched yet
- **partial**: Some quantity dispatched, remainder available
- **dispatched**: All quantity has been dispatched

### Given Compensation
Added in later migration to track actually paid compensation (separate from expected). Allows multiple dispatches to contribute to total given compensation.

---

## 🔍 Summary

The Damage Management module provides:
- ✅ Full CRUD for damage records
- ✅ Multi-line damage records
- ✅ Dispatch management
- ✅ Compensation tracking
- ✅ Status tracking (not_dispatched, partial, dispatched)
- ✅ Expected vs Given compensation
- ✅ Print functionality
- ✅ Advanced filtering
- ✅ Permission-based access
- ✅ Auto-calculations
- ✅ Data validation
- ✅ Professional UI

Perfect for tracking damaged products, managing returns to suppliers, and handling compensation claims.

