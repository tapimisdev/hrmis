# AI UI Guidelines

## Layout

* Use `container-fluid` for application pages.
* Use Bootstrap Grid only.
* Avoid custom layout systems.
* Prefer 12-column layouts.
* Use `row g-3` or `row g-4`.

---

## Responsive Rules

* Design primarily for `md`, `lg`, and `xl`.
* Do not create special layouts for `xxl`.
* Use Bootstrap defaults for `xxl`.
* Mobile layouts should stack vertically.
* Tables should scroll horizontally on small screens.

Preferred breakpoints:

```html
col-12 col-md-6 col-lg-4
```

Avoid:

```html
col-xxl-*
```

---

## Cards

* Use Bootstrap cards for all major sections.
* Use `card-header` and `card-body`.
* Keep cards flat and professional.
* Avoid excessive shadows.
* Avoid decorative borders.

Preferred:

```html
<div class="card h-100">
```

---

## Page Structure

Order:

1. Header
2. Summary
3. Filters
4. Main Content
5. Details Panel

Example:

```text
Page Header
Summary Cards
Filters
Table/List
Details
```

---

## Spacing

Use Bootstrap spacing utilities only.

Preferred:

```html
mb-3
mb-4
mt-3
mt-4
p-3
p-4
gap-2
gap-3
```

Avoid custom spacing classes.

---

## Forms

* Always use labels.
* Group related fields.
* Use Bootstrap validation.
* Prefer 2-column layouts on desktop.
* Stack fields on mobile.

Preferred:

```html
col-12 col-md-6
```

---

## Tables

* Use Bootstrap tables.
* Support search.
* Support pagination.
* Support sorting.
* Always provide empty states.

Preferred:

```html
table
table-hover
table-striped
align-middle
```

---

## Filters

Place filters above tables.

Typical order:

```text
Search
Status
Category
Division
Date Range
```

---

## Buttons

Primary action:

```html
btn btn-primary
```

Secondary action:

```html
btn btn-secondary
```

Danger action:

```html
btn btn-danger
```

Success action:

```html
btn btn-success
```

Do not invent custom button colors unless required by branding.

---

## Icons

Use Bootstrap Icons only.

Examples:

```html
bi-person
bi-people
bi-folder
bi-file-earmark
bi-cash
bi-graph-up
```

---

## Colors

Use Bootstrap theme variables.

Preferred:

```css
var(--bs-primary)
var(--bs-secondary)
var(--bs-body-bg)
var(--bs-body-color)
var(--bs-border-color)
```

Avoid hardcoded colors.

---

## Dark Mode

* Must support Bootstrap dark theme.
* Use Bootstrap variables.
* Avoid fixed background colors.
* Avoid fixed text colors.

---

## Details Panel

For management pages:

```text
List Panel | Details Panel
```

Desktop:

```html
col-lg-8
col-lg-4
```

Mobile:

```html
col-12
```

---

## Empty States

Every component must support:

```text
No records found.
No data available.
Nothing selected.
```

Never leave empty containers.

---

## What To Avoid

* Glassmorphism
* Neumorphism
* Excessive gradients
* Large animations
* Fancy dashboards
* Custom utility frameworks
* col-xxl layouts
* Hardcoded colors
* Overly nested cards

---

## Target Look

Generate interfaces similar to:

* HRIS
* ERP
* Procurement Systems
* Accounting Systems
* Project Management Systems
* Government Information Systems

Focus on readability, consistency, and professional enterprise design.
