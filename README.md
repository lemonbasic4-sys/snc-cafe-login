# SNC 2018 Café & Resto — Management System

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
