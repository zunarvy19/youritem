# Database Specification

## Personal Purchase Planner

**Version:** 1.0
**Status:** MVP
**Database:** PostgreSQL
**Backend:** Laravel
**Frontend:** Vue SPA

**Related Documents:**

- `PRD.md`
- `BUSINESS-RULES.md`
- `SHOPPING-ALGORITHM.md`
- `UI-UX.md`
- `DEVELOPMENT.md`

---

# 1. Database Philosophy

Database dirancang untuk menyimpan **source data** aplikasi.

Recommendation tidak disimpan sebagai permanent data.

Recommendation dihitung berdasarkan:

```text
User
+
Current Budget
+
Active Wishlist Items
+
Purchase History
```

Kemudian Laravel menghasilkan recommendation secara dynamic.

---

# 2. Entity Overview

MVP memiliki entity utama:

```text
users
  │
  ├── wishlist_items
  │       │
  │       ├── categories
  │       │
  │       └── purchases
  │
  ├── budgets
  │
  └── purchases
```

Secara relationship:

```text
User
 ├── hasMany WishlistItems
 ├── hasMany Budgets
 └── hasMany Purchases

Category
 └── hasMany WishlistItems

WishlistItem
 ├── belongsTo User
 ├── belongsTo Category
 └── hasOne Purchase
```

---

# 3. Users

Laravel authentication menggunakan tabel `users`.

## Schema

| Column            | Type         | Nullable | Description                    |
| ----------------- | ------------ | -------: | ------------------------------ |
| id                | BIGINT       |       No | Primary key                    |
| name              | VARCHAR(255) |       No | User name                      |
| email             | VARCHAR(255) |       No | Unique email                   |
| email_verified_at | TIMESTAMP    |      Yes | Laravel verification timestamp |
| password          | VARCHAR(255) |       No | Hashed password                |
| remember_token    | VARCHAR(100) |      Yes | Laravel auth                   |
| created_at        | TIMESTAMP    |      Yes | Created timestamp              |
| updated_at        | TIMESTAMP    |      Yes | Updated timestamp              |

Existing Laravel users migration should be reused if already present.

Do not recreate the users table if it already exists.

---

# 4. Categories

Category digunakan untuk mengelompokkan wishlist items.

## Schema

| Column     | Type         | Nullable | Description                      |
| ---------- | ------------ | -------: | -------------------------------- |
| id         | BIGINT       |       No | Primary key                      |
| name       | VARCHAR(100) |       No | Category name                    |
| is_active  | BOOLEAN      |       No | Whether category can be selected |
| created_at | TIMESTAMP    |      Yes | Created timestamp                |
| updated_at | TIMESTAMP    |      Yes | Updated timestamp                |

Default:

```text
is_active = true
```

---

# 5. Category Rules

Category name harus unique.

Contoh:

```text
Electronics
Makeup
Skincare
Fashion
Food
Hobby
Other
```

Category tidak memiliki ownership pada MVP.

Artinya category merupakan shared system data.

---

# 6. Category Deletion

Category yang masih digunakan oleh wishlist item **tidak boleh di-hard-delete**.

Contoh:

```text
Electronics
    ↓
Mouse
Keyboard
```

Category `Electronics` tidak dapat dihapus selama masih memiliki wishlist item.

Recommended behavior:

```text
DELETE category
        ↓
Check usage
        ↓
Still used?
   ├── YES → reject
   └── NO  → delete/archive
```

Untuk MVP, penggunaan `is_active` memungkinkan category dinonaktifkan tanpa menghapus historical references.

Inactive category:

- tidak muncul pada pilihan category baru;
- tetap dapat digunakan oleh existing wishlist items;
- tidak boleh dihapus jika masih direferensikan.

---

# 7. Wishlist Items

Wishlist item merupakan barang yang direncanakan untuk dibeli.

## Schema

| Column          | Type         | Nullable | Description                 |
| --------------- | ------------ | -------: | --------------------------- |
| id              | BIGINT       |       No | Primary key                 |
| user_id         | BIGINT       |       No | Owner                       |
| category_id     | BIGINT       |       No | Category                    |
| name            | VARCHAR(255) |       No | Item name                   |
| priority        | VARCHAR/ENUM |       No | HIGH, MEDIUM, LOW           |
| purpose         | VARCHAR/ENUM |       No | NEED, WANT                  |
| estimated_price | BIGINT       |       No | Estimated price in IDR      |
| notes           | TEXT         |      Yes | Optional notes              |
| status          | VARCHAR/ENUM |       No | ACTIVE, PURCHASED, ARCHIVED |
| created_at      | TIMESTAMP    |      Yes | Created timestamp           |
| updated_at      | TIMESTAMP    |      Yes | Updated timestamp           |

---

# 8. Wishlist Item Ownership

Every wishlist item belongs to exactly one user.

Relationship:

```text
users.id
   ↓
wishlist_items.user_id
```

Foreign key:

```text
wishlist_items.user_id
REFERENCES users(id)
```

Recommended behavior:

```text
ON DELETE CASCADE
```

If a user is permanently deleted, their private wishlist data can be deleted as well.

---

# 9. Category Relationship

Every wishlist item belongs to one category.

Relationship:

```text
categories.id
   ↓
wishlist_items.category_id
```

Because categories can be referenced by historical wishlist items, category deletion should be restricted when references exist.

Recommended foreign key behavior:

```text
ON DELETE RESTRICT
```

---

# 10. Priority

Allowed values:

```text
HIGH
MEDIUM
LOW
```

Recommended implementation:

Use PHP backed enum:

```text
Priority
```

Database representation may use:

```text
VARCHAR
```

rather than PostgreSQL native ENUM to keep Laravel migrations flexible.

---

# 11. Purpose

Allowed values:

```text
NEED
WANT
```

Recommended implementation:

PHP backed enum:

```text
Purpose
```

Database representation:

```text
VARCHAR
```

---

# 12. Wishlist Status

Allowed values:

```text
ACTIVE
PURCHASED
ARCHIVED
```

Recommended implementation:

PHP backed enum:

```text
WishlistStatus
```

Database representation:

```text
VARCHAR
```

---

# 13. Estimated Price

Estimated price represents the expected purchase price.

For MVP, all currency is IDR.

Recommended PostgreSQL representation:

```text
BIGINT
```

Example:

```text
1500000
```

represents:

```text
Rp1.500.000
```

---

# 14. Why BIGINT for Money?

The application currently handles Indonesian Rupiah without fractional currency.

Using `BIGINT` avoids floating-point precision issues.

Do not store:

```text
"Rp1.500.000"
```

in the database.

Store:

```text
1500000
```

Formatting happens in the frontend/presentation layer.

---

# 15. Price Constraints

`estimated_price` must satisfy:

```text
estimated_price > 0
```

The database should enforce this where practical.

Application validation must also enforce it.

---

# 16. Budgets

Budget represents the user's shopping fund.

For MVP, a user has **one active/current shopping budget**.

The budget itself represents the current amount of money available before purchases.

## Schema

| Column     | Type      | Nullable | Description                  |
| ---------- | --------- | -------: | ---------------------------- |
| id         | BIGINT    |       No | Primary key                  |
| user_id    | BIGINT    |       No | Budget owner                 |
| amount     | BIGINT    |       No | Current budget amount in IDR |
| created_at | TIMESTAMP |      Yes | Created timestamp            |
| updated_at | TIMESTAMP |      Yes | Updated timestamp            |

---

# 17. Budget Ownership

Every budget belongs to exactly one user.

Relationship:

```text
users.id
   ↓
budgets.user_id
```

Foreign key:

```text
budgets.user_id
REFERENCES users(id)
```

---

# 18. One Active Budget

MVP hanya membutuhkan satu active/current budget per user.

Conceptually:

```text
User
  ↓
Current Budget
```

Tidak perlu membuat monthly budget atau budget history pada MVP.

However, the schema should not prevent future budget history functionality.

---

# 19. Budget Amount Semantics

Important:

`budgets.amount` represents the **current available shopping budget**.

Example:

```text
Initial budget
Rp2.000.000
```

User purchases:

```text
Rp500.000
```

Budget becomes:

```text
Rp1.500.000
```

Therefore:

```text
current_budget = previous_budget - actual_purchase_price
```

---

# 20. Why Store Current Budget?

For MVP, storing the current budget makes recommendation queries simple and avoids recalculating the balance from the entire purchase history on every request.

Purchase history remains the historical record of actual spending.

However, budget updates and purchase creation must be handled transactionally.

---

# 21. Budget Update

When user manually changes their budget:

```text
Old:
Rp1.500.000

New:
Rp2.000.000
```

The current budget becomes:

```text
Rp2.000.000
```

The system does not treat the difference as a purchase or income transaction.

MVP does not maintain a budget adjustment history.

---

# 22. Budget Constraints

Budget amount must satisfy:

```text
amount >= 0
```

Zero is valid.

Example:

```text
Rp0
```

means the user currently cannot purchase any positive-priced wishlist item.

Negative budget is invalid.

---

# 23. Purchases

Purchase represents an actual completed purchase.

## Schema

| Column           | Type      | Nullable | Description             |
| ---------------- | --------- | -------: | ----------------------- |
| id               | BIGINT    |       No | Primary key             |
| user_id          | BIGINT    |       No | User who made purchase  |
| wishlist_item_id | BIGINT    |       No | Purchased wishlist item |
| actual_price     | BIGINT    |       No | Actual purchase price   |
| purchased_at     | TIMESTAMP |       No | Purchase timestamp      |
| created_at       | TIMESTAMP |      Yes | Created timestamp       |
| updated_at       | TIMESTAMP |      Yes | Updated timestamp       |

---

# 24. Purchase Ownership

Purchase belongs to a user.

Although the wishlist item also belongs to a user, `purchases.user_id` is explicitly stored.

This makes ownership queries and historical data access simpler.

However, backend validation must guarantee:

```text
purchase.user_id
=
wishlist_item.user_id
```

A user cannot create a purchase for another user's wishlist item.

---

# 25. Purchase and Wishlist Relationship

A wishlist item can have at most one purchase record in MVP.

Relationship:

```text
wishlist_item
      │
      └── hasOne purchase
```

Database constraint:

```text
UNIQUE(wishlist_item_id)
```

This prevents duplicate purchases for the same wishlist item.

---

# 26. Purchase Price

`actual_price` uses:

```text
BIGINT
```

Example:

```text
450000
```

represents:

```text
Rp450.000
```

Actual purchase price may differ from estimated price.

Valid:

```text
Estimated = Rp500.000
Actual    = Rp450.000
```

Also valid:

```text
Estimated = Rp500.000
Actual    = Rp550.000
```

---

# 27. Purchase Price Constraint

`actual_price` must satisfy:

```text
actual_price > 0
```

Zero-price purchase is not supported in MVP.

---

# 28. Purchase Transaction

Purchase creation must happen inside a database transaction.

Conceptually:

```text
BEGIN

1. Lock/check current budget
2. Verify wishlist ownership
3. Verify wishlist status = ACTIVE
4. Validate actual price
5. Validate available budget
6. Create purchase
7. Set wishlist status = PURCHASED
8. Decrease budget

COMMIT
```

If any operation fails:

```text
ROLLBACK
```

No partial state should remain.

---

# 29. Purchase and Budget

Purchase uses `actual_price`.

Example:

```text
Budget:
Rp1.000.000

Estimated:
Rp500.000

Actual:
Rp450.000
```

After successful purchase:

```text
Budget:
Rp550.000
```

Calculation:

```text
1.000.000 - 450.000 = 550.000
```

---

# 30. Purchase Eligibility

A purchase is valid only if:

```text
wishlist_item.status = ACTIVE
```

and:

```text
actual_price <= current_budget
```

Backend must validate these conditions again during purchase.

Frontend recommendation data is not authoritative.

---

# 31. Wishlist Status After Purchase

Successful purchase changes:

```text
ACTIVE
   ↓
PURCHASED
```

Purchased item must no longer appear in:

- Priority First;
- Budget Optimization;
- Can't Afford Yet;
- active wishlist recommendation.

It remains visible in:

- Wishlist history if applicable;
- Purchase History.

---

# 32. Recommendation Data

Recommendation results are **not stored in the database**.

The following are calculated dynamically:

- Priority First recommendations;
- Budget Optimization recommendations;
- Affordable items;
- Unaffordable items;
- Remaining budget in recommendation result;
- Optimization score;
- Budget utilization.

---

# 33. Recommendation Inputs

Recommendation service reads:

```text
users
budgets
wishlist_items
categories
purchases
```

However, purchases are primarily used to maintain/verify current state.

Active recommendation candidates are:

```text
wishlist_items.status = ACTIVE
```

---

# 34. Recommendation JSON Contract

The API should return recommendation data in a structure conceptually equivalent to:

```json id="4a7e9w"
{
    "available_budget": 1500000,
    "priority_first": {
        "items": [],
        "total": 750000,
        "remaining_budget": 750000
    },
    "budget_optimization": {
        "items": [],
        "total": 1350000,
        "remaining_budget": 150000,
        "score": 10,
        "utilization": 0.9
    },
    "unaffordable": []
}
```

---

# 35. Recommendation Contract Definitions

## available_budget

Current user's budget amount.

Type:

```text
integer
```

---

## priority_first.items

Array of wishlist items recommended by Priority First.

Each item should include enough information for the UI to render:

- id;
- name;
- category;
- priority;
- purpose;
- estimated_price;
- recommendation reason if provided by API.

---

## priority_first.total

Total estimated price of selected Priority First items.

```text
SUM(items.estimated_price)
```

---

## priority_first.remaining_budget

Budget remaining after the recommended Priority First items.

```text
available_budget - priority_first.total
```

---

## budget_optimization.items

Selected item combination from Budget Optimization.

---

## budget_optimization.total

Total estimated price of the optimization combination.

---

## budget_optimization.remaining_budget

Remaining budget after optimization selection.

---

## budget_optimization.score

Total optimization score defined by `SHOPPING-ALGORITHM.md`.

---

## budget_optimization.utilization

Budget utilization:

```text
total / available_budget
```

If available budget is zero:

```text
utilization = 0
```

to avoid division by zero.

---

## unaffordable

Active wishlist items that cannot currently be purchased because:

```text
estimated_price > available_budget
```

The API may additionally provide:

```text
amount_needed
```

for UI convenience.

---

# 36. Indexes

Recommended indexes:

### wishlist_items

```text
INDEX(user_id)
INDEX(category_id)
INDEX(status)
INDEX(priority)
INDEX(purpose)
INDEX(user_id, status)
```

### purchases

```text
INDEX(user_id)
INDEX(purchased_at)
UNIQUE(wishlist_item_id)
```

### budgets

```text
UNIQUE(user_id)
```

because MVP allows one current budget per user.

---

# 37. Foreign Key Summary

```text
wishlist_items.user_id
    → users.id

wishlist_items.category_id
    → categories.id

budgets.user_id
    → users.id

purchases.user_id
    → users.id

purchases.wishlist_item_id
    → wishlist_items.id
```

---

# 38. Delete Behavior

## User

If a user is deleted:

```text
User
 ├── Wishlist Items → CASCADE
 ├── Budgets        → CASCADE
 └── Purchases      → CASCADE
```

This assumes permanent user deletion is allowed by the application.

---

## Category

If category is referenced:

```text
DELETE → RESTRICT
```

Use `is_active = false` when the category should no longer be available for new items.

---

## Wishlist Item

Hard deletion of a purchased item is discouraged.

Recommended behavior:

```text
ACTIVE → ARCHIVED
```

Purchased items should remain available for purchase history.

---

# 39. Soft Delete

MVP does not require Laravel `SoftDeletes` for every entity.

Business-level archiving is handled using:

```text
wishlist_items.status = ARCHIVED
```

Do not add soft deletes simply because it is a common Laravel pattern.

---

# 40. Timestamps

Entities should use Laravel timestamps where applicable:

```text
created_at
updated_at
```

Purchase also stores:

```text
purchased_at
```

`purchased_at` represents when the actual purchase occurred.

---

# 41. Timezone

Database timestamps should follow the application's Laravel timezone configuration.

Frontend is responsible for formatting timestamps for display.

Purchase history should display dates in the user's expected timezone.

---

# 42. Data Integrity Rules

The database should enforce integrity where practical.

Important constraints:

```text
estimated_price > 0
actual_price > 0
budget.amount >= 0

wishlist_items.user_id → users.id
wishlist_items.category_id → categories.id

budgets.user_id → users.id

purchases.user_id → users.id
purchases.wishlist_item_id → wishlist_items.id

purchases.wishlist_item_id UNIQUE
budgets.user_id UNIQUE
categories.name UNIQUE
```

Business rules that cannot reasonably be represented as database constraints must be enforced by Laravel.

---

# 43. Database vs Application Rules

### Database should enforce:

- foreign keys;
- uniqueness;
- basic numeric constraints;
- required fields;
- referential integrity.

### Laravel should enforce:

- user ownership;
- recommendation logic;
- purchase eligibility;
- wishlist state transitions;
- budget validation during purchase;
- category business rules;
- authentication/authorization.

---

# 44. Example Data

Example user:

```text id="vgt5yt"
User
name: Arvy
email: arvy@example.com
```

Category:

```text id="6vub19"
Electronics
```

Budget:

```text id="g3m3nw"
amount: 1500000
```

Wishlist:

```text id="4z3m5u"
Mouse
category: Electronics
priority: HIGH
purpose: NEED
estimated_price: 500000
status: ACTIVE
```

Purchase:

```text id="ph4m2c"
Mouse
actual_price: 450000
```

Budget after purchase:

```text id="0w8z2y"
1050000
```

---

# 45. Future Compatibility

The MVP schema should leave room for future features:

- budget history;
- monthly budgets;
- target purchase dates;
- price history;
- multiple currencies;
- external product links;
- purchase notes;
- spending analytics.

These features must **not** be implemented in MVP unless explicitly added to the PRD.

---

# 46. Important Decision: No Recommendation Table

Do not create:

```text
recommendations
```

table for MVP.

Recommendation is derived state.

Store source data, not calculated recommendation output.

---

# 47. Important Decision: No Shopping Cart

Do not create:

```text
shopping_carts
cart_items
```

tables.

Shopping in this application means **purchase recommendations**, not an e-commerce cart.

---

# 48. Important Decision: No Product Table

Do not create a generic:

```text
products
```

table.

A wishlist item is already the user's planned purchase entity.

A separate product catalog is unnecessary for MVP.

---

# 49. Important Decision: One Budget

MVP uses:

```text
User
  ↓
One Current Budget
```

Do not implement:

- monthly budgets;
- category budgets;
- multiple wallets;
- multiple accounts;
- budget history.

These can be introduced later without changing the core wishlist/purchase concept.

---

# 50. Final Entity Relationship

```text
┌─────────────┐
│    users    │
└──────┬──────┘
       │
       ├───────────────┐
       │               │
       ▼               ▼
┌─────────────┐   ┌─────────────┐
│   budgets   │   │  purchases  │
└─────────────┘   └──────┬──────┘
                         │
                         │
       ┌─────────────────┘
       │
       ▼
┌──────────────────┐
│ wishlist_items   │
└────────┬─────────┘
         │
         ▼
┌─────────────┐
│ categories  │
└─────────────┘
```

Relationship summary:

```text
User
 ├── hasOne Budget
 ├── hasMany WishlistItems
 └── hasMany Purchases

Category
 └── hasMany WishlistItems

WishlistItem
 ├── belongsTo User
 ├── belongsTo Category
 └── hasOne Purchase

Purchase
 ├── belongsTo User
 └── belongsTo WishlistItem
```

---

# 51. Source of Truth

Database implementation must follow this document together with:

```text
PRD.md
BUSINESS-RULES.md
SHOPPING-ALGORITHM.md
DEVELOPMENT.md
```

If implementation encounters an ambiguity:

1. Do not silently invent a business rule.
2. Identify the ambiguity.
3. Propose the smallest reasonable solution.
4. Update the relevant documentation before implementing a business-critical change.

---

# 52. Database Design Principle

The database should follow:

> **Store facts, not recommendations.**

Facts:

- user;
- wishlist item;
- category;
- budget;
- purchase.

Derived information:

- recommendation;
- ranking;
- affordability;
- optimization;
- remaining recommendation budget.

Derived information should be calculated by Laravel from the current source data.
