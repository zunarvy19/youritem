# UI/UX Specification

## Personal Purchase Planner

**Version:** 1.0
**Status:** MVP
**Related Documents:** `PRD.md`, `BUSINESS-RULES.md`, `SHOPPING-ALGORITHM.md`

---

# 1. Design Goal

Personal Purchase Planner harus terasa seperti **personal decision-making tool**, bukan e-commerce dan bukan sekadar CRUD wishlist.

Tujuan utama UI:

> Membantu user memahami barang apa yang sebaiknya dibeli, kenapa barang tersebut direkomendasikan, dan berapa dana yang masih tersedia.

UI harus membuat tiga informasi selalu mudah ditemukan:

1. **Apa yang gue punya?**
2. **Apa yang sebaiknya gue beli?**
3. **Berapa budget gue sekarang?**

---

# 2. UX Principles

## 2.1 Recommendation First

Shopping recommendation merupakan salah satu primary action aplikasi.

User tidak perlu membuka banyak halaman untuk mengetahui barang yang direkomendasikan.

---

## 2.2 Budget Visibility

Available budget harus selalu mudah ditemukan.

Contoh:

```text id="g8v3tz"
Shopping Budget

Rp1.500.000
```

Budget harus ditampilkan pada:

- Dashboard
- Shopping
- Purchase confirmation

---

## 2.3 Explainable Recommendation

Setiap recommendation harus menjawab:

> "Kenapa barang ini direkomendasikan?"

Contoh:

```text id="4z9psv"
✓ Need
✓ High Priority
✓ Affordable
```

---

## 2.4 Clear Priority

Priority harus mudah dibedakan secara visual.

Recommended semantic treatment:

```text id="rj8h3s"
HIGH
MEDIUM
LOW
```

Jangan hanya mengandalkan warna.

Priority harus tetap dapat dipahami melalui:

- text;
- badge;
- icon atau visual indicator.

---

## 2.5 Avoid E-commerce Patterns

Jangan membuat UI seperti:

- product marketplace;
- product card penuh gambar;
- shopping cart;
- promotional banners;
- discount badges;
- "Buy Now" yang terlalu agresif.

Aplikasi ini adalah **purchase planner**, bukan shopping platform.

---

# 3. Application Layout

Desktop layout:

```text id="g4g8tk"
┌─────────────────────────────────────────────────────┐
│ Logo                         User / Profile          │
├──────────────┬──────────────────────────────────────┤
│              │                                      │
│ Dashboard    │                                      │
│ Wishlist     │             Main Content             │
│ Shopping     │                                      │
│ Purchases    │                                      │
│              │                                      │
│              │                                      │
│ Settings     │                                      │
│              │                                      │
└──────────────┴──────────────────────────────────────┘
```

Sidebar navigation:

- Dashboard
- Wishlist
- Shopping
- Purchase History
- Settings

Mobile:

```text id="znx6h5"
Header
   ↓
Content
   ↓
Bottom Navigation
```

Mobile navigation minimal:

- Dashboard
- Wishlist
- Shopping
- Purchases

Settings dapat diakses melalui profile menu.

---

# 4. Visual Hierarchy

Prioritas informasi:

```text id="9t9qpj"
1. Available Budget
2. Primary Recommendation
3. Item Priority
4. Need / Want
5. Item Price
6. Supporting information
```

Harga tidak boleh secara visual lebih dominan daripada alasan recommendation.

---

# 5. Dashboard

Dashboard merupakan overview.

## 5.1 Header

Contoh:

```text id="w6ny7q"
Good afternoon, Arvy

Here's your purchase overview.
```

Greeting bersifat optional.

Yang lebih penting adalah summary.

---

# 6. Dashboard Summary Cards

Recommended:

```text id="nhf1g6"
┌─────────────────┐
│ Available Budget│
│ Rp1.500.000     │
└─────────────────┘

┌─────────────────┐
│ Wishlist        │
│ 12 items        │
└─────────────────┘

┌─────────────────┐
│ High Priority   │
│ 4 items         │
└─────────────────┘

┌─────────────────┐
│ Purchased       │
│ 8 items         │
└─────────────────┘
```

Summary cards tidak perlu terlalu banyak.

---

# 7. Dashboard Priority First

Section utama:

```text id="2qfl7v"
Priority First

What should you buy first?
```

Tampilkan beberapa recommendation teratas.

Contoh card:

```text id="n2kj7w"
┌────────────────────────────────────┐
│ HIGH · NEED                        │
│                                    │
│ Logitech MX Master 3S              │
│ Electronics                        │
│                                    │
│ Rp1.250.000                        │
│                                    │
│ ✓ High priority                    │
│ ✓ Need                             │
│ ✓ Within your budget               │
│                                    │
│                    [ Buy ]         │
└────────────────────────────────────┘
```

---

# 8. Recommendation Card

Recommendation card minimal memiliki:

- Item name
- Category
- Priority
- Purpose
- Price
- Recommendation reason
- Buy action

Tidak perlu menampilkan seluruh informasi wishlist.

---

# 9. Budget Optimization Preview

Dashboard menampilkan preview:

```text id="m2m0ck"
Budget Optimization

Use your Rp1.500.000 more efficiently.

3 items
Rp1.350.000 total

[View Optimization]
```

Tujuannya mendorong user mengeksplorasi alternatif, bukan menggantikan Priority First.

---

# 10. Wishlist Page

Header:

```text id="8rv7g5"
Wishlist

Items you want to buy.

[ + Add Item ]
```

---

# 11. Wishlist Toolbar

Toolbar:

```text id="0n5wgj"
┌───────────────────────────────────────────────┐
│ Search items...                                │
│                                               │
│ Category ▼   Priority ▼   Purpose ▼   Sort ▼ │
└───────────────────────────────────────────────┘
```

Search dan filter harus mudah digunakan.

Mobile dapat menggunakan filter drawer.

---

# 12. Wishlist Table

Desktop recommended:

```text id="ml7xak"
┌────────────────────────────────────────────────────────────┐
│ Item       Category    Priority   Type   Price     Action  │
├────────────────────────────────────────────────────────────┤
│ Mouse      Electronics High       Need   Rp500k    •••     │
│ Parfum     Fragrance   Low        Want   Rp700k    •••     │
│ Skincare   Beauty      Medium     Need   Rp250k    •••     │
└────────────────────────────────────────────────────────────┘
```

Actions:

- Edit
- Archive
- Delete, jika diperbolehkan
- Buy

Jangan menaruh semua action pada UI sekaligus.

---

# 13. Wishlist Mobile

Gunakan card/list layout:

```text id="2of3n8"
┌───────────────────────────────┐
│ Mouse                         │
│ Electronics                   │
│                               │
│ HIGH · NEED                   │
│                               │
│ Rp500.000                     │
│                               │
│                    •••        │
└───────────────────────────────┘
```

---

# 14. Add Wishlist Item

Gunakan modal atau dedicated page tergantung existing UI architecture.

Form:

```text id="q1tyrq"
Add to Wishlist

Name *
[________________________]

Category *
[ Electronics ▼ ]

Priority *
[ High ▼ ]

Purpose *
( ) Need
( ) Want

Estimated Price *
[ Rp ______________ ]

Notes
[________________________]

              [Cancel] [Add Item]
```

---

# 15. Form UX

Validation harus inline.

Contoh:

```text id="39m7fc"
Estimated Price *
[ -50000 ]

Price must be greater than zero.
```

Jangan menunggu submit untuk semua validation jika error sederhana dapat ditampilkan lebih awal.

---

# 16. Shopping Page

Shopping adalah **primary decision page**.

Header:

```text id="x3k83j"
Shopping

Your recommendations based on
your current budget.
```

Budget prominently displayed:

```text id="qgdq32"
Available Budget

Rp1.500.000

[ Edit Budget ]
```

---

# 17. Shopping Page Structure

Urutan:

```text id="g8l3fq"
Available Budget
       ↓
Priority First
       ↓
Budget Optimization
       ↓
Can't Afford Yet
```

Priority First harus berada paling atas.

---

# 18. Priority First Section

Header:

```text id="z1qvlq"
Priority First

Items you should consider buying first.
```

Jika terdapat recommendation:

```text id="b2d7oh"
┌────────────────────────────────────────┐
│ HIGH · NEED                            │
│                                        │
│ Logitech MX Master 3S                  │
│ Electronics                            │
│                                        │
│ Rp1.250.000                            │
│                                        │
│ Why this item?                         │
│ ✓ Need                                 │
│ ✓ High priority                        │
│ ✓ Affordable                           │
│                                        │
│                    [ Buy ]             │
└────────────────────────────────────────┘
```

---

# 19. Multiple Priority Recommendations

Jika terdapat beberapa item:

Gunakan vertical list atau grid.

Jangan membuat carousel sebagai primary interaction.

User harus dapat melihat ranking.

Contoh:

```text id="8t4v8h"
01  Mouse
    High · Need
    Rp500.000

02  Skincare
    Medium · Need
    Rp250.000

03  Parfum
    Low · Want
    Rp300.000
```

Nomor ranking membantu memperjelas urutan.

---

# 20. Remaining Budget

Setelah recommendation:

```text id="d9x0ga"
Recommended Total
Rp750.000

Remaining Budget
Rp750.000
```

Jika recommendation terdiri dari beberapa item, tampilkan totalnya.

---

# 21. Budget Optimization Section

Gunakan visual yang berbeda dari Priority First agar user memahami bahwa ini adalah **alternative strategy**.

Header:

```text id="d1js1p"
Budget Optimization

An alternative way to use your budget.
```

Example:

```text id="3l4n6r"
Your budget
Rp1.500.000

Recommended combination

✓ Mouse          Rp500.000
✓ Skincare       Rp250.000
✓ Keyboard       Rp600.000

Total            Rp1.350.000
Remaining        Rp150.000

[Review Selection]
```

---

# 22. Optimization Explanation

Tampilkan ringkasan:

```text id="y5h6h9"
Why this combination?

Higher overall priority value
while staying within your budget.
```

Jangan menggunakan istilah teknis seperti:

```text
Knapsack score = 14
```

kepada user.

Algorithm boleh kompleks.

UX harus sederhana.

---

# 23. Can't Afford Yet

Section:

```text id="5x9d7p"
Can't Afford Yet

Items you may want to save for.
```

Card:

```text id="q5g2nv"
┌───────────────────────────────────┐
│ iPhone                             │
│ Electronics · Want · High          │
│                                   │
│ Rp15.000.000                      │
│                                   │
│ Current budget                    │
│ Rp5.000.000                       │
│                                   │
│ Need Rp10.000.000 more            │
└───────────────────────────────────┘
```

Jangan tampilkan Buy button pada item yang belum affordable.

Action yang lebih sesuai:

```text id="89u0td"
[View Item]
```

---

# 24. Purchase Confirmation

Saat user memilih Buy:

Modal:

```text id="cn3m4f"
Confirm Purchase

Mouse

Estimated price
Rp500.000

Actual purchase price
[ Rp450.000 ]

Available budget
Rp1.000.000

Remaining budget
Rp550.000

[Cancel] [Confirm Purchase]
```

Remaining budget harus dihitung berdasarkan actual purchase price jika input actual price diperbolehkan.

---

# 25. Purchase Success

Setelah berhasil:

```text id="jz5w0j"
Purchase completed

Mouse has been added to your purchase history.

Rp450.000 spent.

Remaining budget
Rp550.000
```

Recommendation harus refresh setelah purchase.

---

# 26. Purchase History

Header:

```text id="2blbq8"
Purchase History

Your completed purchases.
```

Table:

```text id="zqv8t6"
┌─────────────────────────────────────────────────────┐
│ Item       Category      Price       Purchased      │
├─────────────────────────────────────────────────────┤
│ Mouse      Electronics   Rp450k      Aug 23, 2026  │
│ Skincare   Beauty        Rp200k      Aug 20, 2026  │
└─────────────────────────────────────────────────────┘
```

---

# 27. Purchase Detail

Optional detail drawer/modal:

```text id="x4ly8e"
Mouse

Category
Electronics

Purpose
Need

Priority
High

Estimated Price
Rp500.000

Actual Price
Rp450.000

Purchased
23 August 2026
```

---

# 28. Budget Management

Budget dapat diubah melalui:

- Dashboard
- Shopping

Prefer dedicated modal:

```text id="b8b2rs"
Shopping Budget

Current budget
Rp1.500.000

New budget
[ Rp ______________ ]

[Cancel] [Save]
```

---

# 29. Budget Feedback

Setelah budget berubah:

```text id="p3gjq8"
Budget updated.

New available budget:
Rp2.000.000
```

Recommendation harus otomatis refresh.

---

# 30. Empty State — Wishlist

```text id="w8n6p3"
Your wishlist is empty.

Start adding things you want to buy
and we'll help you prioritize them.

[ Add Your First Item ]
```

---

# 31. Empty State — Shopping

Jika tidak ada affordable item:

```text id="2f0s1j"
Nothing to buy right now.

You don't currently have enough
budget for any active wishlist item.

[ View Wishlist ]
[ Manage Budget ]
```

---

# 32. Empty State — Purchase History

```text id="q1n8we"
No purchases yet.

Items you buy will appear here.
```

---

# 33. Loading State

API request harus memiliki loading state.

Contoh:

```text id="m7s0by"
Shopping

Available Budget
████████

Priority First
████████████
████████████
```

Gunakan skeleton untuk content-heavy pages.

Button mutation:

```text id="9z3k2r"
[ Purchasing... ]
```

Button tidak boleh dapat diklik berkali-kali ketika request sedang berlangsung.

---

# 34. Error State

API error:

```text id="o3d7na"
Something went wrong.

We couldn't load your shopping recommendations.

[ Try Again ]
```

Network error harus dibedakan dari validation error jika memungkinkan.

---

# 35. Authorization Error

Jika session expired:

```text id="x8zq8x"
Your session has expired.

Please log in again.
```

Kemudian redirect ke Login.

---

# 36. Login Page

Simple authentication screen.

```text id="z3o7ay"
Welcome back

Email
[________________]

Password
[________________]

[ Sign In ]

Don't have an account?
Create account
```

---

# 37. Register Page

```text id="8e3y17"
Create your account

Name
[________________]

Email
[________________]

Password
[________________]

Confirm Password
[________________]

[ Create Account ]

Already have an account?
Sign in
```

---

# 38. Navigation State

Navigation harus menunjukkan current page.

Contoh:

```text id="z3dqf7"
Dashboard
Wishlist
▶ Shopping
Purchase History
```

Active state harus dapat dikenali tanpa hanya mengandalkan warna.

---

# 39. Responsive Behavior

Desktop:

- Sidebar
- Table
- Multi-column cards

Tablet:

- Collapsible sidebar
- Responsive grids

Mobile:

- Bottom navigation
- Cards instead of tables
- Filter drawer
- Full-width forms
- Sticky budget/action area jika diperlukan

---

# 40. Mobile Shopping UX

Shopping page pada mobile harus tetap menampilkan budget secara jelas.

Recommended:

```text id="19t0sy"
┌───────────────────────────┐
│ Available                 │
│ Rp1.500.000               │
└───────────────────────────┘

Priority First
──────────────

01
Mouse
High · Need
Rp500.000

[Buy]
```

Budget tidak boleh hilang ketika user scroll terlalu jauh.

---

# 41. Accessibility

UI harus mempertimbangkan:

- keyboard navigation;
- visible focus states;
- semantic HTML;
- accessible labels;
- sufficient contrast;
- button states;
- error messages yang terhubung dengan input;
- tidak hanya menggunakan warna untuk status.

---

# 42. Destructive Actions

Delete/archive harus memiliki confirmation jika action berpotensi menyebabkan kehilangan data.

Contoh:

```text id="c1myk9"
Archive this item?

This item will no longer appear
in your shopping recommendations.

[Cancel] [Archive]
```

Untuk purchased item, jangan menyediakan destructive action yang dapat merusak purchase history.

---

# 43. Notifications

Gunakan toast untuk feedback singkat:

Success:

```text id="8b8l5d"
Wishlist item added.
```

Error:

```text id="apq72k"
Failed to save wishlist item.
```

Toast tidak boleh menjadi satu-satunya tempat untuk informasi penting.

---

# 44. Visual Language

UI sebaiknya menggunakan visual hierarchy yang clean dan modern.

Semantic colors:

```text id="8d4x77"
High priority
→ strong warning/attention visual

Medium
→ moderate visual emphasis

Low
→ subtle visual emphasis

Need
→ positive/important semantic

Want
→ neutral semantic

Affordable
→ positive semantic

Can't afford
→ muted/warning semantic
```

Jangan menggunakan warna sebagai satu-satunya indicator.

---

# 45. Price Formatting

Semua nominal ditampilkan dalam format Indonesia:

```text id="04q52m"
Rp500.000
Rp1.500.000
Rp10.000.000
```

Tidak menampilkan:

```text id="fqkj7k"
500000
1,500,000
```

Database/API tetap menggunakan numeric value.

---

# 46. Interaction Rules

Setiap mutation harus memberikan feedback:

```text id="e0m0o1"
Create
→ loading
→ success/error

Update
→ loading
→ success/error

Archive
→ confirmation
→ loading
→ success/error

Purchase
→ confirmation
→ loading
→ success/error
→ refresh recommendations
```

---

# 47. Recommendation Refresh

Recommendation harus diperbarui setelah:

- budget update;
- wishlist create;
- wishlist update;
- wishlist archive;
- purchase;
- item price update;
- priority update;
- purpose update.

Frontend tidak boleh mempertahankan recommendation lama jika data yang menjadi input telah berubah.

---

# 48. API and UI Responsibility

### Laravel API

Bertanggung jawab atas:

- authentication;
- authorization;
- validation;
- budget calculation;
- recommendation;
- priority ranking;
- optimization;
- purchase validation;
- data persistence.

### Vue SPA

Bertanggung jawab atas:

- rendering;
- form interaction;
- client-side UI validation;
- loading state;
- error state;
- navigation;
- displaying recommendation;
- user interaction.

Vue tidak boleh menjadi source of truth untuk business logic.

---

# 49. Primary CTA

Setiap page sebaiknya memiliki satu primary action.

Dashboard:

```text id="6q0e6m"
View Shopping
```

Wishlist:

```text id="p8nyj8"
Add Item
```

Shopping:

```text id="kqz4e8"
Buy
```

Purchase History:

Tidak perlu primary CTA besar.

---

# 50. Important UX Rule

Jangan membuat semua item terlihat sama pentingnya.

UI harus membantu user memahami hierarchy:

```text id="xpx2ra"
What matters most
        ↓
What I can afford
        ↓
What I can buy
        ↓
What I may buy later
```

---

# 51. Core UX Principle

Seluruh interface harus mengikuti prinsip:

> **Show me what matters, explain why, and make the next action obvious.**

User harus dapat membuka aplikasi dan dalam beberapa detik memahami:

1. Berapa budget gue?
2. Apa yang paling direkomendasikan?
3. Kenapa barang itu direkomendasikan?
4. Apa yang bisa gue beli sekarang?
5. Apa yang belum mampu gue beli?
