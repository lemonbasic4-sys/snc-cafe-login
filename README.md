# SNC 2018 Café & Resto — Management System

### System Settings

Owners open **System Settings** in the sidebar to edit the business name, optional address/contact number, optional receipt header/footer, default 58 mm or 80 mm paper width, and enabled Cash/GCash/Card methods. The side preview updates unsaved text safely without changing any transaction. **Discard Changes** reloads saved values. At least one payment method is required. Saves require an active owner, CSRF and the current edit revision; stale edits are rejected. Actual changes record before/after values in Audit Trail, with a **View Settings History** link. Unchanged saves do not create extra events.

New checkout requests enforce enabled methods inside the checkout transaction. The POS refreshes available methods with its existing recent-order feed and preserves the cart, asking the cashier to review a changed selection. Unconfirmed checkout retries keep their original details; a completed payment can still be retrieved idempotently even if its method is later disabled. Historical payments and reports retain the original method.

New orders snapshot the receipt business details and header/footer in `orders.receipt_profile`, so reprints retain the text saved at checkout. Older orders without a snapshot keep the previous SNC 2018 receipt text. Paper width is the owner's current preview default for both old and new receipts; a cashier may change the width for one preview without changing that saved default. Existing browser-local paper preferences no longer override it. The existing transaction-summary/official-receipt notice remains fixed. These fields configure receipts; they do not rename account identities, order numbers, or application branding.

Existing installations need `database/system-settings.sql` after the audit migration. It adds the singleton settings row, receipt snapshot column and settings audit category; it is applied locally and included in the fresh schema. Run `D:/xampp/php/php.exe tests/system-settings.php` for validation, permissions, revisions, auditing, disabled methods, retry behavior and receipt snapshots. Checkout tests shadow settings with a connection-local temporary table; active business settings are preserved. Stock, void and audit regression checks passed. HTTP checks covered role/CSRF restrictions and receipt rendering; browser checks covered live preview, paper widths, discard and unchanged saves. Disposable QA records were removed.

### Analytics & Reports

Open **Analytics & Reports** in the owner sidebar. The default period is Last 7 Days; shortcuts provide Today and This Month, plus inclusive custom dates up to 366 days. The page shows Paid Sales, Paid Orders, Discounts, Average Paid Order, a daily sales chart with accessible data table, the top ten products ranked by sales or quantity, and Cash/GCash/Card sales with order counts and shares. Missing payment records appear as **Not recorded**, rather than being assigned a method. Empty periods display zero totals and zero-sales days.

The report shares the dashboard's paid-order reporting scope and exact discount allocation. Each order counts once using its earliest recorded payment, or order time when no payment exists, in UTC+08:00. Unpaid/voided orders and tendered cash/change are excluded; kitchen completion is not required. Recorded product names remain unchanged by menu renames. Zero-priced paid products retain their quantities. Summary, daily sales, products, and methods are read inside one report transaction. Product prices do not represent profit or ingredient cost.

**Export CSV** downloads summary, daily sales, all matching products, and payment breakdown for the chosen dates, with PHP currency labels and generated time. Stored product names are protected against spreadsheet formulas. **Print Report** uses the browser print dialog and a print layout that removes navigation/filter controls and includes all products plus the daily data table. **View Transactions** opens the same paid date range. Exports and pages require an active owner; no new database migration is needed.

Run `D:/xampp/php/php.exe tests/analytics-report.php` for date validation, reporting boundaries, discounts and fractional cents, duplicate payment records, unpaid/voided exclusion, zero-price products, missing payment methods, empty periods, dashboard agreement and CSV protection. All test data is rolled back. Authenticated HTTP checks verified page/export permissions, CSV headers/content and date errors; browser checks covered layout, date shortcuts, ranking and print-action preparation. Physical printing was not performed.

### In-App Notifications

Owners receive a notification for each new cashier void request. The requesting cashier receives approval or rejection decisions, including the owner's rejection reason. An owner's direct void also notifies the cashier when it closes a pending request. Delivery is recorded inside the same transaction as the request/decision, with a unique recipient/request/event key to prevent duplicates on retries. Existing requests and decisions are not backfilled.

**Notifications** is available in the owner sidebar/top bar and the cashier header. The inbox has All and Unread views, 20 records per page, individual **Mark as Read**, and **Mark All as Read**. Viewing a request/order marks its notification read and opens the relevant approval or cashier order details. Marking all covers records through the displayed inbox boundary; newer records remain unread. Messages stay in history after reading. Cashier header links open Notifications in another tab so the active POS cart is preserved; no cashier sidebar was added.

Unread badges refresh every 30 seconds while a page is visible and when focus returns. A new notification can show the existing in-app toast. Counts failing to load are labeled unavailable, rather than displayed as zero. This version delivers inside the app; it does not send email, SMS or browser push. Notification records are restricted to their recipient and intended account role; reading requires CSRF-protected POST requests. Links are generated from trusted request/order IDs, not stored external URLs.

Apply `database/in-app-notifications.sql` after the void-request migration on existing installations; it is applied locally and included in the fresh schema. Run `D:/xampp/php/php.exe tests/in-app-notifications.php` for recipient isolation, delivery retries, approval/rejection messages, read state, pagination, role restrictions, and the mark-all boundary. Void-request regression tests and authenticated HTTP/browser checks passed. Temporary QA records and their notifications were removed.

### Void Requests and Approvals

Cashiers open **View Details → Request Void** in the modal footer. A compact form shows the order number, total and reason, with **Back to Details** and **Send Request** controls. Back preserves the draft reason. The order stays paid and active while pending. Submission returns to order details with **Void Request Pending**, without reloading or clearing the current cart. Reopen details or use **Refresh Status** to see a later decision. Cashier history and standalone order details provide the same workflow; the standalone form also works without JavaScript.

Owners open **Approvals** in the existing sidebar. The page starts with Pending requests and provides Approved, Rejected and All Requests views, order/cashier search, counts, and 20 records per page. **Review Request** opens a compact modal over the table with the requester, reason, current kitchen status, order total and a link to full order details. Approve and Reject are grouped in its footer and open separate confirmation steps in the same modal. Closing preserves filters and table position; direct request links also open the modal. Without JavaScript, links and server-rendered forms remain available below the table. **Approve Void** performs the existing void atomically. **Reject Request** requires an owner reason visible to the cashier and leaves the order unchanged. Cashiers may submit a new request after rejection; earlier requests remain in history.

Only one pending request can exist per order. Repeated submissions and decisions do not duplicate records or audit events; changed or conflicting decisions are rejected. If kitchen progress changes during approval, the owner must review the refreshed state before confirming. An owner's direct void action remains available and closes an existing pending request as Approved with an explicit direct-void note. Requests, approvals, rejections and the actual void are recorded in Audit Trail. Neither path processes refunds or returns ingredients.

Existing installations need `database/void-requests.sql` after the order-void and audit migrations; it has been applied locally and is included in the fresh schema. Run `D:/xampp/php/php.exe tests/void-requests.php` for request permissions, duplicate protection, rejection and resubmission, stale kitchen status, approval, sales and stock behavior, audit events, and direct void reconciliation. Authenticated HTTP and browser checks covered the native forms, cashier modal submission, status display and retained cart. Disposable QA records were removed.

### Owner Order Voids

Open **Sales & Transactions → View Details → Void Order**, enter a reason, and choose **Confirm Void**. Only an active owner can void a paid order directly. The confirmation explains that no refund or ingredient return is processed. Cashiers use the request and approval workflow above.

Voiding preserves original items, totals, payments, kitchen progress, and completion time. It records the owner's name, reason, timestamp, and kitchen status at void, with an **Order voided** audit event. The order remains visible in history and the Voided transaction filter, but is excluded from paid sales, dashboard reporting, the active kitchen queue, and receipt printing. Cashiers can read the void details but cannot void orders. Completed paid orders may also be voided; this does not reverse preparation or stock usage.

Concurrent kitchen changes require a fresh confirmation. Repeating the same confirmation returns the original result without another audit event. Checkout retries identify a subsequently voided order instead of treating it as a new paid checkout. The standalone owner details page also supports confirmation without JavaScript.

Existing installations need `database/order-voids.sql` after the audit migration; it has been applied locally and included in the fresh schema. Run `D:/xampp/php/php.exe tests/void-orders.php` for permissions, validation, stale progress, repeat submissions, retained records, sales exclusion, checkout retries, and unchanged ingredient balances. HTTP authentication, CSRF, receipt restrictions, and standalone confirmation were checked using disposable QA orders, then cleaned up.

### Owner Audit Trail

Open **Audit Trail** in the existing owner sidebar for read-only activity across authentication, staff, menu/categories, recipes, ingredients, stock movements, paid checkouts, and kitchen status changes. Search by the recorded user or affected record, filter by inclusive dates, user, or specific activity, and browse 25 records per page. All Time is the default; Reset clears the filters. Times use the application's UTC+08:00 database wall-clock convention.

**View Details** opens recorded before/after values in a modal, retaining the current filters. It falls back to a standalone record section without JavaScript. Order-related records link to owner order details. Failed sign-ins are explicitly labeled **Attempted** and do not identify an authenticated actor. Records preserve recorded names even when accounts or products are later renamed. Passwords, password hashes, and session/request tokens are excluded from audit details.

Existing `auth_audit_logs`, `staff_management_logs`, and `inventory_movements` are read directly into one timeline; they are not copied or duplicated. New menu/category, recipe, ingredient metadata, paid checkout, and kitchen changes write to `audit_events` inside the same transaction as their business change. A rollback removes both. No-op menu/ingredient changes and checkout/stock retries do not create duplicate change entries. Recipe snapshots include ingredient names, units, quantities, and tracking mode. Stock quantities continue to come from the original ledger.

Detailed change recording begins at the timestamp in `audit_recording_info`, shown on the page. Earlier unrecorded menu/recipe/order/kitchen changes are not reconstructed. Existing installations need `database/audit-trail.sql` after the staff, inventory, recipe, and automatic-stock migrations; it has been applied locally and included in the fresh schema. Audit pages and the GET-only detail API require an active owner session. There are no edit/delete controls or mutation endpoints for audit records. Database administrators retain their normal direct database access; this is not a cryptographically tamper-proof ledger.

Run `D:/xampp/php/php.exe tests/audit-trail.php` for rolled-back coverage of filters, pagination, details, source deduplication, redaction, and transaction rollback. Menu/checkout/kitchen HTTP workflows and owner-only permissions were also verified with temporary QA records, then cleaned up. Existing automatic-stock tests clean up only audit records associated with their own disposable fixtures.

### Owner Staff Management

Open **Staff Management** in the existing owner sidebar. It lists Cashier and Kitchen accounts, with name/username search, role/status filters, 25 accounts per page, last sign-in times, and summary counts across all staff. Owner accounts are excluded and cannot be edited, deactivated, or created through this module.

**Add Staff** requires a unique 3–60 character username, staff name, Cashier/Kitchen role, account status, and a matching initial password (at least 8 characters, up to 72 bytes). Passwords are hashed; there is no shared default. Share the chosen initial password directly with the staff member. **Edit** changes names, usernames, roles, and status without replacing passwords. **Activate/Deactivate** presents an account-specific confirmation; there is no delete control. Staff use the Staff Login portal.

Role changes and status changes advance the account's authorization version. Every authenticated request checks the current account, role, active status, and authorization version, so old sessions lose access on their next request. Reactivation requires a fresh login. Name-only edits update displayed identity without revoking sessions. Recorded transactions remain attached to the same user ID. An unavailable account lookup fails closed.

Staff mutations require an active owner and CSRF validation, use transactions and edit revisions to reject stale changes, and record before/after account details in `staff_management_logs`. Passwords and password hashes are never included in those audit records or page data. Existing installations need the additive `database/staff-management.sql` migration; it has been applied locally and is included in the fresh schema. Run `D:/xampp/php/php.exe tests/staff-management.php` for the rolled-back integration checks. Account/session behavior was also checked through the HTTP login and staff endpoints.

### Automatic Ingredient Deduction

Automatic deduction is **opt-in for each menu item**. Open **Menu Management → Manage Recipe**, enter quantities for one item, select **Deduct using recipe** under Stock tracking, and save. Existing recipes and items remain on **Manual tracking** until explicitly enabled. Coffee can be enabled independently of food. A purchased product tracked by pieces can use a recipe of one piece per item.

Only a successfully saved paid checkout deducts stock. Requirements multiply by sold quantities and aggregate shared ingredients across the whole order. The checkout locks stock balances, prevents negative stock, and rejects missing recipes or inactive ingredients for enabled items. Orders, payment records, recipe snapshots, and stock movements commit together or all roll back. Each deduction appears as **Sale Deduction** in Stock History with an order link. Later recipe edits and kitchen status changes do not repeat or rewrite these deductions. Prepared ingredients are not automatically restocked; use an explained manual correction when appropriate.

Checkout requires a unique request key. Concurrent retries return the same order and do not deduct again. If the connection result is unknown, the cashier page keeps the submitted details locked and offers **Retry Checkout** using the same request. Keep that page open until the result is confirmed. A definite validation/stock error unlocks the cart. Refresh old cashier tabs after updating this feature.

Existing installations need `database/automatic-stock.sql` after `database/inventory.sql` and `database/recipes.sql`. It has been applied locally and is included at the end of the fresh schema. The CLI integration check is `D:/xampp/php/php.exe tests/automatic-stock.php`; it uses uniquely named disposable fixtures and removes its own test orders, menu items, and ingredients. Concurrent retry and competing-stock requests were also checked through the HTTP endpoint.

### Menu Recipes

In **Menu Management**, choose **Manage Recipe** beside an item. Enter the ingredients and quantities required for **one menu item**, using each ingredient's fixed base unit (`g`, `ml`, or whole `pcs`). Add/remove rows and save the complete recipe. To clear a recipe, remove all rows and choose **Save Empty Recipe**. Menu items show their recipe ingredient count.

New links require active ingredients. Existing inactive links remain visible with a warning so the owner can replace or reactivate them. Duplicate ingredients, nonpositive quantities, excessive precision, and fractional pieces are rejected. Saves are transactional and use a revision check to reject stale edits from another tab. Recipe pages and saves require the owner role; POST saves require CSRF validation. The editor records the last save time and staff member.

Existing installations need `database/recipes.sql` after `database/inventory.sql`; both migrations have been applied locally. Fresh schema imports include both. Saving recipe configuration does not change existing stock, manual availability, or past orders. Enabled recipes are used by future paid checkouts as described above. Automatic tracking requires a nonempty recipe with active ingredients; switch to Manual tracking before clearing it.

### Owner Inventory

Open **Inventory** in the owner sidebar to create/edit ingredients, set opening stock and low-stock thresholds, activate/deactivate ingredients, and search or filter stock status. Base units are grams (`g`), milliliters (`ml`), or whole pieces (`pcs`). The base unit stays fixed after creation. Editing ingredient details never changes its stock.

**Adjust Stock** supports deliveries, manual usage, waste, and corrections that add or remove stock. Kilogram and liter entries convert to the ingredient's base unit. A live balance preview shows the converted adjustment and resulting stock before saving; the server checks the latest balance again when saving. Quantities support three decimal places; negative balances are rejected. Stock updates and ledger entries save together in a transaction. Request keys prevent duplicate stock changes when a submission is retried.

**Stock History** provides ingredient/date filters and records signed quantities, before/after balances, reasons, staff names, and timestamps. Correct a mistake with another movement; there is no history-delete control. Low Stock means a positive balance at or below the threshold; Out of Stock means zero. Summary counts cover every active ingredient regardless of list filters.

Existing installations need the additive `database/inventory.sql` migration, which has been applied locally. New installations include these tables in `database/schema.sql`. Inventory pages and mutations require an admin account; mutations also require CSRF validation. Recipe-based automatic deduction is available per item. Menu availability remains a separate manual setting.

### Connected Dashboard

The dashboard shows live Paid Sales, Paid Orders, Average Paid Order, Discounts, hourly/daily revenue, and the top five products for **Today**, **This Week** (Monday through today), or **This Month** (through today), using UTC+08:00. **View Transactions** opens the matching date range. Cards and charts share the reporting scope used by Sales & Transactions: earliest recorded payment date (order date if no payment exists), paid orders at any kitchen status, after discounts, excluding unpaid/voided orders and tendered-cash change. Missing hours/days appear as zero. The chart also provides an expandable data table.

Product sales use saved order-item names and prices. Each paid order total is distributed across its item line amounts proportionally; leftover cents use largest remainders so allocated item sales reconcile exactly with the order total. Products are grouped by their saved product ID/name. Owner Attention placeholders are omitted until approval/exception records are implemented. Kitchen queue counts always show the current queue, independent of the selected reporting period.

### Owner Sales & Transactions

Open **Sales & Transactions** in the owner sidebar for all-cashier transaction history, search by order/customer/cashier, date shortcuts and custom ranges, payment method/status filters, and 25 orders per page. Default and Reset show today (UTC+08:00). Dates include the full end day and use the earliest payment timestamp, falling back to order creation when there is no payment record.

**Paid Sales** sums saved `orders.total_amount` only for `payment_status = 'paid'`, regardless of kitchen status. That total already reflects discounts. Unpaid/voided orders and tendered-cash change do not contribute. Discounts and average paid order use the same qualifying orders. Summary cards cover all matching rows, not only the current page. Each order counts once even if multiple payment records exist; the earliest payment supplies the displayed method and date. This version follows the existing single-payment-per-order workflow.

Owners can view original item/payment details in a modal or standalone details page, with a separate confirmation for voiding paid orders. Sales pages require an admin account; the shared detail API allows admin and cashier roles. Cashiers can request a void but cannot directly change payment status. The dashboard uses the same reporting rules.

### Owner Menu Management

Open **Menu Management** from the existing owner sidebar. The page supports item creation/editing, positive prices with up to two decimal places, manual availability, category creation/renaming, search, category/availability filters, and 25 items per page. It has no permanent-delete control. Owner role checks and CSRF validation are enforced by `api/menu.php`.

Existing installations need the additive `database/menu_categories.sql` migration; it has been applied to this local database. New installations can import `database/schema.sql` and then `database/seed_menu.sql`. The migration imports existing category names without replacing menu items. Category renames update menu items transactionally; saved order items and payments are not rewritten.

Refresh an already-open cashier page to display newly edited items, prices, categories, and availability. The order API continues to validate current prices and availability when saving. Automatic stock-based availability remains outside this module.

### Cashier receipt and history usage

**View Details** and cashier order numbers open item and payment details in a modal, preserving the current cart. Paid orders include a **Request Void** action subject to owner approval. Close it with **Close** or Escape. If JavaScript is unavailable, these links open the standalone details page. **View History** opens all recorded orders with order/customer search, inclusive date filters, and 20 orders per page in another tab from the POS. History and details require a cashier session and use the existing design without a sidebar.

For cash payments, **Exact**, **₱500**, and **₱1,000** set the amount tendered (they do not add to it) and recalculate change. Shortcuts are disabled for empty carts and non-cash payments. The server still validates payment before saving.

After payment, use **Print Receipt** in the confirmation or Recent Orders. Receipts open in a separate tab, including for kitchen-completed orders. No cashier sidebar is added.

Choose **58 mm** or **80 mm**, then click **Print Receipt**. Select your installed thermal printer and matching roll size in the print dialog. Use 100% scale, no margins, and disable browser headers and footers. Paper width is remembered in that browser. The printer driver controls continuous-paper length; silent printing and automatic cutting are not implemented.

Receipts use saved item names, prices, and payment amounts and require a cashier session. Printing or cancelling does not modify orders or payments. Check physical output with the actual printer and driver.

**Project:** SNC 2018 Café & Resto Management System  
**Technology stack:** PHP, HTML5, CSS3, JavaScript, MariaDB/MySQL (planned)  
**Development environment:** XAMPP  
**Design identity:** Bistre × Dreamcatcher  
**Project standard:** Compact · Stable · Fast · Accessible · Secure · Maintainable

> **Implementation status:** The provided project package is described as containing the frontend-only Login V7. The Owner Dashboard V5.1 and its sidebar are **approved design references**, not verified implemented code. Live Operations requirements are **under discussion**. Do not present prototypes, sample values, or planned backend features as deployed functionality.

---

## 1. Project Overview

The SNC 2018 Café & Resto Management System is a custom-built, web-based application intended to centralize the café's business oversight and daily operations, including cashier transactions, kitchen order processing, menu administration, inventory, sales records, and analytics.

The system has **three distinct roles**:

| Role | Responsibility | Operational boundary |
| --- | --- | --- |
| **Admin / Business Owner** | Oversees the business, reviews financial performance, manages staff access and business configuration, and reviews sensitive exceptions | **Not part of the workforce**; does not perform routine cashier or kitchen work |
| **Cashier** | Handles customer orders, payments, receipts, and cashier operations | No owner-only administration |
| **Kitchen Staff** | Receives preparation requests and updates kitchen order progress | No payment or owner-only administration |

Role permissions must be enforced by the **server**, not inferred from selected login tabs, client-side state, or hidden fields.

### Development priorities

- Consistent, premium but **compact** interface.
- Fast and predictable navigation and interactions.
- **No unnecessary layout shifts** or jumping text, cards, buttons, charts, or other entities.
- Readable text and sufficient contrast across every module.
- Organized, maintainable external CSS and JavaScript.
- Responsive laptop/desktop and tablet experiences.
- Secure authentication, authorization, and transactional integrity.
- Clear distinction between approved designs, proposed requirements, and implemented functionality.

### Requirements and implementation status

| Area | Status | Notes |
| --- | --- | --- |
| Login V7 | **Approved design; frontend-only package described** | No database authentication or demo credentials |
| Owner Dashboard V5.1 | **Approved design** | Financial-first; compact; readability corrections; no backend integration verified |
| Sidebar navigation | **Approved design** | Fully hides on desktop; overlay drawer on tablet; persistent toggle |
| Live Operations | **Under discussion** | Owner monitoring only is confirmed; detailed order views and alerts remain proposed |
| POS, Kitchen, Menu, Inventory, Analytics, Staff, Approvals, Audit, Settings | **Planned modules** | Implementation and detailed workflows must be verified independently |

Never interpret an approved ChatGPT prototype as proof that a PHP page, API, database, device integration, or business workflow already works.

---

## 2. Mandatory CSS Development Protocol

All developers and AI coding assistants **MUST** follow these rules for the delivered project source.

### 2.1 Strict separation of styling

All CSS belongs in external `.css` files under `assets/css/`.

**Prohibited:**

- Inline HTML styles (`style="..."`).
- Internal `<style>` blocks.
- JavaScript assignments to `element.style`, `style.cssText`, or equivalent inline-style injection.
- Unjustified hardcoded colors, spacing, or dimensions repeated across files.
- Duplicated or contradictory rules without a documented reason.

**Required:**

```html
<link rel="stylesheet" href="assets/css/snc-tokens.css">
<link rel="stylesheet" href="assets/css/snc-login.css">
```

```css
.snc-login-panel {
    background-color: var(--snc-color-bistre);
    border-radius: var(--snc-radius-lg);
}

.snc-login-panel--staff {
    background-color: var(--snc-color-mocha);
}
```

Use classes or state attributes to change presentation:

```js
panel.classList.toggle('snc-login-panel--staff', selectedPortal === 'staff');
```

Do **not** use `panel.style.backgroundColor = '#665044'`.

### 2.2 CSS organization

Create module stylesheets **only when the module is actually implemented**. A possible structure is:

```text
assets/css/
├── snc-tokens.css
├── snc-global.css
├── snc-components.css
├── snc-layout.css
├── snc-login.css
├── snc-dashboard.css
├── snc-navigation.css
├── snc-live-operations.css
├── snc-pos.css
├── snc-kitchen.css
├── snc-menu.css
├── snc-inventory.css
└── snc-analytics.css
```

Avoid premature files, redundant variants, and fragmentation for trivial components.

### 2.3 Design tokens

Centralize design constants and reuse them in all modules:

```css
:root {
    --snc-color-bistre: #3D2B1F;
    --snc-color-mocha: #665044;
    --snc-color-ivory: #F8F1E9;
    --snc-color-gold: #D8B88A;

    --snc-text-primary: #382419;
    --snc-text-secondary: #685344;
    --snc-text-on-dark: #FFF8EF;

    --snc-radius-sm: 6px;
    --snc-radius-md: 8px;
    --snc-radius-lg: 13px;

    --snc-spacing-xs: 4px;
    --snc-spacing-sm: 8px;
    --snc-spacing-md: 16px;
    --snc-spacing-lg: 24px;

    --snc-transition-standard: 250ms ease;
}
```

Add tokens only when they serve a real shared need. Maintain accessible contrast on light and dark surfaces.

### 2.4 CSS naming

Use the `snc-` prefix and consistent component-oriented names:

```css
.snc-login {}
.snc-login__branding {}
.snc-login__role-button {}
.snc-login__role-button--active {}
.snc-dashboard__kpi {}
.snc-sidebar--closed {}
```

Avoid names such as `.box1`, `.left`, `.test`, `.new-style`, or `.final-final`.

### 2.5 Compactness and layout stability — mandatory

**Compact does not mean cramped.** Prioritize efficient information density, comfortable reading, and predictable component geometry.

- Keep card, table, input, button, and toolbar dimensions consistent.
- Reserve sufficient space for dynamic numbers, labels, loading states, and status changes.
- Use stable line heights and appropriate font weights; avoid text wrapping that unexpectedly changes surrounding card heights.
- Use `font-variant-numeric: tabular-nums` for KPI values and monetary figures where appropriate.
- Set predictable chart containers and prevent charts from changing the page's vertical geometry during refresh.
- Avoid horizontal overflow, overlapping text, clipped controls, and unnecessarily tall empty sections.
- Use truncation or wrapping deliberately; do not silently hide critical information.
- Keep filter controls and action buttons in stable positions when their state changes.
- Use placeholders or skeletons with **reserved dimensions** during loading.
- Keep errors, validation messages, and alerts in planned regions rather than pushing unrelated controls around unexpectedly.
- Do not cause unrelated page elements to move when switching Login V7 portals or refreshing dashboard values.

**Acceptance goal:** No unexpected visual movement during normal interaction. The approved sidebar may intentionally reveal or hide navigation when the owner requests it; content reflow for a genuine viewport change is allowed, but should remain predictable and smooth.

---

## 3. Mandatory JavaScript Development Protocol

### 3.1 External scripts only

All application JavaScript must be in external `.js` files under `assets/js/`.

**Prohibited:**

- HTML event attributes such as `onclick`, `onchange`, or `onload`.
- Inline executable JavaScript inside `<script>` tags.
- PHP templates containing embedded executable JavaScript logic.
- Direct inline-style manipulation through JavaScript.
- Unnecessary global variables and duplicate listeners.
- Hardcoded credentials, bypasses, or simulated authentication success.

**Required:**

```html
<script src="assets/js/snc-login.js" defer></script>
```

```js
const roleButtons = document.querySelectorAll('.snc-login__role-button');

roleButtons.forEach((button) => {
    button.addEventListener('click', () => {
        // Update visual selection only; authentication remains server-side.
    });
});
```

### 3.2 JavaScript organization

Suggested module-specific files, created as needed:

```text
assets/js/
├── snc-login.js
├── snc-navigation.js
├── snc-dashboard.js
├── snc-live-operations.js
├── snc-pos.js
├── snc-kitchen.js
├── snc-menu.js
├── snc-inventory.js
├── snc-analytics.js
└── snc-notifications.js
```

Shared utilities belong in clearly named reusable modules, not copied across pages.

### 3.3 Responsibilities and security boundaries

JavaScript may handle UI events, visual state, usability checks, accessible disclosure controls, and asynchronous requests. **The backend remains authoritative** for authentication, authorization, prices, discounts, payment state, financial summaries, stock movements, and audit records.

### 3.4 Performance-first interaction rules

- Update **only affected DOM regions** when practical; do not rebuild the entire dashboard for a single changing KPI.
- Avoid unnecessary polling, duplicate requests, repeated event binding, and large blocking tasks.
- Debounce or throttle expensive user-driven actions when appropriate.
- Prevent stale network responses from overwriting newer data.
- Preserve filter, scroll, focus, and sidebar state when refreshing data.
- Show truthful loading, empty, error, and disconnected states without claiming success prematurely.
- Keep charts and counters within stable containers while loading and updating.
- Use efficient backend pagination/filtering for large tables and transaction histories.
- Measure performance instead of claiming a page is fast without testing it.

### 3.5 Animation rules

Animations must be purposeful, subtle, and non-disruptive. Prefer CSS `transform` and `opacity` rather than animating layout-heavy properties where avoidable. Respect `prefers-reduced-motion`.

For **Login V7**:

1. Only the dreamcatcher emblem performs a 3D flip on portal switching.
2. The branding background transitions between Bistre and Mocha.
3. Portal identification and supporting copy update without moving the form.
4. Role buttons keep identical dimensions.
5. Rapid repeated clicks do not break the animation or interface state.

For the **approved sidebar**:

1. A persistent top-left toggle opens and closes the sidebar.
2. On desktop, closing the sidebar removes its occupied width **completely**; do not substitute an icon-only rail.
3. On tablet/narrow layouts, the sidebar opens as an overlay with a dismissible backdrop.
4. The dreamcatcher and full navigation labels remain visible in the expanded sidebar.
5. The toggle stays accessible in both states, including keyboard operation and an accurate `aria-expanded` value.
6. No unintended jumping, text flickering, or disruptive transitions.

### 3.6 Naming convention

Use descriptive camelCase identifiers such as `selectedPortal`, `roleButtons`, `loginForm`, `dashboardDateRange`, `sidebarToggle`, `updatePortalState()`, and `triggerDreamcatcherFlip()`.

---

## 4. Mandatory UI/UX Design System

### 4.1 Visual identity

| Element | Standard |
| --- | --- |
| Primary | Bistre Brown `#3D2B1F` |
| Secondary | Mocha `#665044` |
| Background | Warm Ivory `#F8F1E9` |
| Accent | Muted Gold `#D8B88A` |
| Typography | Readable sans-serif; selective serif for SNC branding/headings |
| Icons | Consistent SVG icon family, preferably Lucide |
| Corners | Consistent soft radii |
| Density | Compact, structured, professional |
| Motion | Subtle, user-initiated, reduced-motion compatible |

Avoid random gradients, unrelated visual languages, decorative emoji icons, and excessive effects.

### 4.2 Readability and contrast

The Owner Dashboard V5.1 explicitly requires the **Business Overview** heading to be highly legible:

- Heading: dark Bistre `#3D2B1F`, prominent weight, approximately **26px desktop / 22px tablet** as a design reference.
- Supporting text: dark Mocha `#665044`.
- Background: warm Ivory `#F8F1E9` or another contrast-tested light surface.
- Use consistent heading hierarchy, sufficient contrast, and stable line heights.

Apply the same readability standard to all future modules; do not use pale-on-pale text.

### 4.3 Responsive design

**Desktop/laptop:** efficient multi-column financial dashboards, full-width content when sidebar is closed, stable controls and charts.

**Tablet:** touch-friendly controls, stacked layouts where necessary, overlay sidebar with backdrop, readable data tables, no accidental horizontal scrolling.

Mobile may be supported where practical, but desktop and tablet are the primary targets.

### 4.4 Accessibility

- Semantic landmarks and headings.
- Visible keyboard focus and keyboard-operable controls.
- Associated labels and meaningful validation messages.
- Accessible names for icon-only buttons.
- Status indicators that do not rely on color alone.
- Reduced-motion support.
- Sufficient contrast and legible text sizes.

---

## 5. Approved Login V7 Design

**Status: approved visual baseline; current package is frontend-only.**

### Left branding panel

- Dreamcatcher emblem.
- SNC 2018 name and Café & Resto branding.
- Management System label.
- Compact, equally sized **Admin Login** and **Staff Login** buttons.

### Right authentication panel

- Portal identification.
- Stable **Welcome Back** heading.
- Role-specific supporting text.
- Username and password fields.
- Password visibility toggle.
- Sign In button.
- Authorized-personnel notice.

### Role switching

Switching portals flips **only the dreamcatcher**, transitions the branding background, updates portal copy, and preserves form alignment and button dimensions.

### Authentication status

No demo accounts, hardcoded passwords, fake login success, or database authentication are included in the described frontend-only package. In production, the Staff portal must authenticate Cashier/Kitchen users and route them based on **verified server-side roles**. The Admin portal is for the business owner, not a workforce account.

---

## 6. Approved Owner Dashboard V5.1

**Status: approved UI/UX design reference; implementation not verified.**

### 6.1 Purpose and role boundary

The Owner Dashboard is a **financial-first business oversight interface**. It is not a POS terminal and not a kitchen order workstation. The owner reviews information and follows management links rather than performing daily cashier or kitchen operations.

### 6.2 Dashboard information hierarchy

1. **Business Overview** heading, clear subtitle, and reporting period controls.
2. Compact financial KPI cards.
3. Prominent revenue-performance chart, alongside **Owner Attention**.
4. Best-selling products and read-only live operations summary.
5. Recent business activity and navigation to detailed reports.

### 6.3 Approved dashboard elements

| Element | Expected behavior | Implementation note |
| --- | --- | --- |
| Reporting period | Today, This Week, This Month; custom range may be added later | Period selection should update related metrics consistently |
| Net Sales | Sales net of applicable discounts and refunds under a documented business rule | Do not equate revenue with profit |
| Completed Orders | Count according to the agreed order/transaction completion definition | Payment and kitchen fulfillment are distinct states |
| Average Order Value | Net Sales ÷ qualifying orders | Handle zero orders safely |
| Owner Attention | Summary of unresolved issues and approval requests | Avoid counting duplicate notifications as separate issues |
| Revenue chart | Financial trend for selected period; optional comparison | Reserve chart dimensions; calculate comparisons from actual data |
| Best-Selling Products | Ranking by a defined metric, such as sales revenue | Label the ranking criterion |
| Live Operations summary | Read-only view of operational state | No cashier payment or kitchen status controls |
| Recent Activity | Brief audit highlights linking to detailed records | Never fabricate operational history |

### 6.4 Financial integrity

- Define when a transaction counts toward sales; payment and order fulfillment are separate statuses.
- Exclude voided transactions appropriately; account for discounts and refunds consistently.
- Use a documented business-day boundary and timezone.
- Show `₱0` or a truthful empty state when no data exists.
- Do not show net profit without reliable cost and expense data.
- Show **No comparison available** when the baseline is zero or unavailable.
- Display data freshness/last-updated information once a backend is connected.

### 6.5 Dashboard interaction and performance

- Keep KPI card geometry fixed or predictably sized even when values change.
- Use tabular numerals for monetary values and counts.
- Keep headings, filters, buttons, and charts from jumping on refresh.
- Do not re-render the whole page when one metric changes.
- Preserve selected period, sidebar state, and user focus.
- Use real database values only in production; label mock figures **Demo Data** in design prototypes.
- Enforce owner-only drill-down permissions on the backend.

---

## 7. Sidebar and Navigation Standard

**Status: approved UI design.**

The sidebar follows the behavior demonstrated by the ChatGPT app's open/closed navigation drawer:

- **Expanded desktop:** dark Bistre panel with dreamcatcher, SNC name, grouped labels, and full navigation.
- **Closed desktop:** sidebar disappears **entirely** and the main workspace fills the available width; **no collapsed icon rail**.
- **Always-visible control:** upper-left sidebar toggle remains available when open or closed.
- **Tablet/narrow screen:** drawer overlays content with a dimmed, clickable backdrop; dismiss on backdrop interaction.
- **Transitions:** smooth and brief; no unexpected movement of labels or content outside the intentional drawer change.

### Proposed owner navigation organization

These navigation names are design references, not evidence of implemented modules:

```text
OVERVIEW
  Dashboard
  Live Operations

MANAGEMENT
  Menu Management
  Inventory
  Staff Management
  Approvals

BUSINESS INSIGHTS
  Sales & Transactions
  Analytics & Reports

SYSTEM
  Notifications
  Audit Trail
  System Settings
```

The owner sidebar should **not** include routine **Point of Sale** or **Kitchen Orders** workspaces as owner tasks. Those belong to workforce interfaces. The owner may monitor relevant activity through Live Operations.

---

## 8. Live Operations — Requirements Under Discussion

**Confirmed:** The Admin is the business owner and requires monitoring visibility, not routine Cashier or Kitchen controls.

**Not yet approved:** Exact detail level, order timeline, delay thresholds, cashier session tracking, real-time delivery mechanism, and final layout.

Possible capabilities for review (do **not** implement without confirmation):

- Summary counts for active/preparing/ready orders.
- Read-only individual order details or a hybrid summary/detail view.
- Cashier activity and kitchen progress indicators.
- Order timeline and configured delay warnings.
- Monitoring filters and historical views.

**Do not invent** order statuses, preparation targets, staff procedures, or automated alerts as confirmed SNC business rules. Confirm the actual workflow first.

---

## 9. Project Directory Structure and Naming

### Current described Login V7 package

```text
snc-cafe-login/
├── index.php
├── assets/
│   ├── css/
│   │   └── snc-login.css
│   ├── js/
│   │   └── snc-login.js
│   └── images/
│       └── snc-dreamcatcher-emblem.svg
└── README.md
```

Future modules should extend the structure without unnecessary renames or unused placeholder files.

**Naming rules:** lowercase, kebab-case filenames, `snc-` prefixes for branded assets and CSS classes, "# snc-cafe-login" 


usernames
admin
cashier1
kitchen1

lahat ng users password and password by default
