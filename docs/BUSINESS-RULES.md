# Business Rules

## Personal Purchase Planner

**Version:** 1.0
**Status:** MVP
**Related Documents:** `PRD.md`, `SHOPPING-ALGORITHM.md`, `DATABASE.md`

---

# 1. Purpose

Dokumen ini mendefinisikan aturan bisnis yang harus digunakan oleh backend dan frontend aplikasi Personal Purchase Planner.

Business rules merupakan **source of truth** untuk behavior aplikasi.

Frontend Vue tidak boleh membuat aturan bisnis sendiri yang bertentangan dengan dokumen ini.

Business logic utama harus diproses dan divalidasi oleh Laravel API.

---

# 2. Core Concepts

Aplikasi memiliki lima konsep utama:

1. **Wishlist Item**
2. **Budget**
3. **Priority**
4. **Purpose**
5. **Purchase**

Konsep recommendation seperti Priority First dan Budget Optimization menggunakan data dari konsep-konsep tersebut.

---

# 3. Wishlist Item

Wishlist Item merepresentasikan barang yang ingin dibeli user.

Minimal memiliki:

```text
name
category
priority
purpose
estimated_price
notes
status
```

---

## 3.1 Item Name

`name` wajib diisi.

Rules:

- Tidak boleh kosong.
- Harus berupa string.
- Harus memiliki panjang yang wajar.
- Leading/trailing whitespace harus dibersihkan.
- Nama item tidak harus unik.

Contoh:

```text
Sony WH-1000XM6
```

Dua item dengan nama sama tetap diperbolehkan apabila user memang menginginkannya.

---

# 4. Category

Category digunakan untuk mengelompokkan wishlist item.

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

Category tidak menentukan prioritas.

Category hanya digunakan untuk:

- grouping;
- filtering;
- searching;
- analytics di masa depan.

Satu wishlist item memiliki satu category.

Category dapat dikembangkan di masa depan tanpa mengubah business logic recommendation.

---

# 5. Purpose

Purpose menjelaskan apakah item merupakan kebutuhan atau keinginan.

Allowed values:

```text
NEED
WANT
```

---

## 5.1 NEED

`NEED` berarti barang dianggap sebagai kebutuhan oleh user.

Contoh:

- obat;
- perlengkapan kerja;
- barang pengganti yang rusak;
- kebutuhan sehari-hari.

---

## 5.2 WANT

`WANT` berarti barang merupakan keinginan atau discretionary purchase.

Contoh:

- parfum baru;
- headphone baru padahal headphone lama masih berfungsi;
- fashion item;
- gadget tambahan.

---

## 5.3 Purpose Tidak Menentukan Priority Secara Mutlak

`NEED` tidak otomatis berarti `HIGH`.

Contoh valid:

```text
Need + Low
Need + Medium
Need + High
```

Begitu juga:

```text
Want + Low
Want + Medium
Want + High
```

Priority dan Purpose merupakan dua dimensi berbeda.

Purpose digunakan sebagai salah satu faktor recommendation.

---

# 6. Priority

Priority menunjukkan seberapa penting item tersebut dibandingkan item lain.

Allowed values:

```text
HIGH
MEDIUM
LOW
```

Ranking dasar:

```text
HIGH
  ↓
MEDIUM
  ↓
LOW
```

---

## 6.1 HIGH

Item sangat penting dan sebaiknya dipertimbangkan terlebih dahulu.

Contoh:

```text
Laptop untuk pekerjaan
```

---

## 6.2 MEDIUM

Item cukup penting tetapi tidak mendesak.

---

## 6.3 LOW

Item memiliki urgensi rendah.

Biasanya merupakan pembelian yang dapat ditunda.

---

# 7. Priority dan Purpose

Priority dan Purpose tidak boleh dianggap sebagai field yang sama.

Contoh:

| Item   | Purpose | Priority |
| ------ | ------- | -------- |
| Obat   | Need    | High     |
| Mouse  | Need    | Medium   |
| Sepatu | Want    | High     |
| Parfum | Want    | Low      |

Sistem recommendation harus mempertimbangkan kedua faktor tersebut.

Detail weighting dan ranking algorithm didefinisikan dalam:

```text
SHOPPING-ALGORITHM.md
```

---

# 8. Estimated Price

`estimated_price` merupakan estimasi harga barang sebelum pembelian.

Rules:

- Wajib diisi.
- Harus berupa angka.
- Harus lebih besar dari `0`.
- Tidak boleh negatif.
- Menggunakan satuan mata uang yang sama dengan sistem.
- Tidak boleh menggunakan floating point untuk penyimpanan nominal uang.

Recommended database representation:

```text
NUMERIC / DECIMAL
```

Contoh:

```text
estimated_price = 500000
```

---

# 9. Estimated Price vs Purchase Price

Estimated price dan actual purchase price merupakan dua nilai berbeda.

Contoh:

```text
Estimated Price
Rp500.000

Actual Purchase Price
Rp450.000
```

`estimated_price` tetap menjadi informasi awal wishlist item.

Actual purchase price disimpan di purchase record.

---

# 10. Wishlist Item Status

Wishlist item memiliki status:

```text
ACTIVE
PURCHASED
ARCHIVED
```

---

## 10.1 ACTIVE

Item masih ingin dibeli.

Item `ACTIVE` dapat:

- muncul di wishlist;
- muncul di shopping recommendation;
- digunakan dalam Budget Optimization;
- dibeli.

---

## 10.2 PURCHASED

Item sudah dibeli.

Item `PURCHASED`:

- tidak boleh muncul sebagai active recommendation;
- tidak boleh dibeli kembali melalui purchase flow yang sama;
- tetap dapat muncul dalam purchase history.

---

## 10.3 ARCHIVED

Item tidak lagi ingin dibeli.

Item `ARCHIVED`:

- tidak muncul di recommendation;
- tidak dapat dibeli;
- tetap disimpan untuk menjaga data history.

---

# 11. Status Transition

Valid transitions:

```text
ACTIVE
  ├──→ PURCHASED
  └──→ ARCHIVED
```

Archived item dapat dikembalikan menjadi:

```text
ARCHIVED
  ↓
ACTIVE
```

Purchased item tidak boleh dikembalikan menjadi active melalui flow biasa.

Jika user ingin membeli kembali barang yang sama, user harus membuat wishlist item baru.

---

# 12. Budget

Budget merepresentasikan dana yang tersedia untuk pembelian.

Budget digunakan sebagai constraint utama recommendation.

Contoh:

```text
Available Budget
Rp1.500.000
```

---

# 13. Budget Rules

Budget tidak boleh:

- bernilai negatif;
- bernilai null ketika sistem membutuhkan available budget;
- menggunakan floating point untuk nominal uang.

Budget harus menggunakan integer/decimal yang aman untuk currency.

---

# 14. Available Budget

Available budget merupakan dana yang masih dapat digunakan untuk pembelian.

Secara konsep:

```text
Available Budget
=
Budget
-
Purchase Spending
```

Namun implementasi aktual harus mengikuti model data yang ditentukan dalam `DATABASE.md`.

---

# 15. Purchase

Purchase merepresentasikan transaksi pembelian wishlist item.

Minimal memiliki:

```text
wishlist_item_id
actual_price
purchased_at
```

---

# 16. Purchase Rules

Ketika user membeli item:

1. Item harus berstatus `ACTIVE`.
2. User harus memiliki akses terhadap item tersebut.
3. Actual purchase price harus valid.
4. Purchase record dibuat.
5. Wishlist item berubah menjadi `PURCHASED`.
6. Purchase masuk ke purchase history.
7. Available budget harus diperbarui secara konsisten.

Purchase harus dilakukan secara transactional agar tidak terjadi kondisi data setengah berhasil.

---

# 17. Purchase Price

Actual purchase price dapat:

- sama dengan estimated price;
- lebih rendah dari estimated price;
- lebih tinggi dari estimated price.

Contoh:

```text
Estimated: Rp500.000
Actual:    Rp550.000
```

tetap valid selama aturan budget/purchase mengizinkannya.

---

# 18. Purchase dan Budget

Purchase menggunakan **actual purchase price**, bukan estimated price, untuk mencatat pengeluaran aktual.

Contoh:

```text
Available Budget
Rp1.000.000

Estimated Price
Rp500.000

Actual Purchase Price
Rp450.000
```

Setelah purchase:

```text
Available Budget
Rp550.000
```

---

# 19. Recommendation Eligibility

Sebuah wishlist item hanya boleh menjadi recommendation apabila:

```text
status = ACTIVE
```

Item berikut tidak eligible:

```text
PURCHASED
ARCHIVED
```

---

# 20. Affordable Item

Item dianggap affordable apabila:

```text
estimated_price <= available_budget
```

Contoh:

```text
Budget = Rp500.000
Item = Rp500.000

Affordable = TRUE
```

Jika:

```text
Budget = Rp500.000
Item = Rp500.001

Affordable = FALSE
```

Exact budget match dianggap affordable.

---

# 21. Can't Afford Yet

Item yang:

```text
estimated_price > available_budget
```

dianggap belum mampu dibeli.

Additional required budget:

```text
estimated_price - available_budget
```

Contoh:

```text
Item
Rp1.500.000

Budget
Rp1.000.000

Additional Required
Rp500.000
```

Item tetap dapat ditampilkan dalam bagian "Can't Afford Yet".

---

# 22. Priority First

Priority First merupakan recommendation mode utama.

Tujuan:

> Memprioritaskan barang yang paling penting terlebih dahulu dengan tetap mempertimbangkan kemampuan budget.

Priority First harus:

- hanya menggunakan ACTIVE items;
- mempertimbangkan Need/Want;
- mempertimbangkan Priority;
- mempertimbangkan affordability;
- tidak merekomendasikan item yang tidak dapat dibeli ketika recommendation membutuhkan immediate purchase;
- mengabaikan item PURCHASED dan ARCHIVED.

Detail ranking harus didefinisikan di:

```text
SHOPPING-ALGORITHM.md
```

---

# 23. Priority First dan Expensive High Priority Item

High Priority tidak berarti item harus selalu direkomendasikan.

Contoh:

```text
Budget
Rp500.000

Laptop
High + Need
Rp10.000.000

Mouse
High + Need
Rp400.000
```

Laptop tidak dapat dibeli dengan budget saat ini.

Mouse dapat dibeli.

Maka Mouse dapat menjadi immediate recommendation.

Laptop dapat masuk:

```text
Can't Afford Yet
```

---

# 24. Budget Optimization

Budget Optimization merupakan recommendation mode alternatif.

Tujuannya:

> Mencari kombinasi pembelian yang menggunakan budget secara lebih optimal.

Budget Optimization:

- tidak boleh mengabaikan priority;
- tidak boleh mengabaikan Need/Want;
- tidak hanya memaksimalkan jumlah barang;
- tidak boleh melebihi available budget;
- hanya menggunakan ACTIVE items.

Detail algorithm ditentukan dalam:

```text
SHOPPING-ALGORITHM.md
```

---

# 25. Recommendation Tidak Mengubah Data

Recommendation merupakan hasil perhitungan.

Recommendation tidak boleh:

- mengubah priority;
- mengubah purpose;
- mengubah estimated price;
- mengubah status;
- membuat purchase record.

Recommendation hanya menghasilkan informasi.

Purchase baru terjadi ketika user secara eksplisit melakukan action purchase.

---

# 26. Recommendation Recalculation

Recommendation harus dianggap dynamic.

Perubahan berikut dapat menyebabkan recommendation berubah:

- budget berubah;
- item baru ditambahkan;
- priority berubah;
- purpose berubah;
- estimated price berubah;
- item dibeli;
- item diarsipkan.

Sistem tidak perlu menyimpan recommendation sebagai data permanen kecuali terdapat kebutuhan teknis yang jelas.

---

# 27. Purchase Action

Purchase action harus dilakukan secara eksplisit.

Recommendation tidak otomatis membeli item.

User harus melakukan:

```text
Buy
  ↓
Confirmation
  ↓
Purchase
```

---

# 28. Purchase Confirmation

Sebelum purchase, user harus dapat melihat:

```text
Item
Estimated Price
Actual Purchase Price
Current Available Budget
Remaining Budget
```

Contoh:

```text
Mouse
Estimated: Rp500.000
Purchase:  Rp450.000

Available Budget:
Rp1.000.000

Remaining:
Rp550.000
```

---

# 29. Insufficient Budget During Purchase

Sistem harus melakukan validasi ulang ketika purchase dilakukan.

Frontend recommendation tidak boleh dianggap sebagai jaminan bahwa budget masih tersedia.

Contoh:

```text
User A membuka Shopping
Budget = Rp1.000.000

Recommendation:
Mouse = Rp500.000
```

Kemudian terjadi purchase lain sehingga budget berubah.

Saat user melakukan purchase, backend harus menghitung ulang budget.

Jika budget tidak mencukupi:

```text
Purchase rejected
```

Frontend harus menerima error yang jelas.

---

# 30. Concurrent Purchase

Purchase operation harus aman terhadap race condition sejauh memungkinkan dalam architecture aplikasi.

Backend harus memastikan dua purchase tidak secara tidak sengaja menggunakan budget yang sama.

Purchase dan budget update harus menggunakan database transaction.

Jika sistem menggunakan balance/budget field yang mutable, update harus dilakukan secara atomic atau menggunakan locking strategy yang sesuai.

---

# 31. User Data Isolation

Semua resource harus scoped terhadap authenticated user.

Contoh:

```text
User A
 ├── Wishlist A
 ├── Budget A
 └── Purchases A

User B
 ├── Wishlist B
 ├── Budget B
 └── Purchases B
```

User A tidak boleh mengakses resource User B hanya dengan mengganti ID pada API request.

---

# Authentication & Authorization

Authentication merupakan requirement wajib pada MVP.

## Registration

User dapat membuat akun dengan:

- name
- email
- password
- password confirmation

Rules:

- name wajib diisi.
- email wajib diisi.
- email harus valid.
- email harus unik.
- password wajib memenuhi minimum security requirement Laravel.
- password harus di-hash menggunakan mekanisme Laravel.
- Password tidak boleh pernah dikembalikan melalui API response.

## Login

User dapat login menggunakan:

- email
- password

Jika credentials valid, user mendapatkan authenticated session sesuai mekanisme authentication yang digunakan oleh existing Laravel SPA setup.

Jika credentials tidak valid, API harus mengembalikan unauthorized response tanpa mengungkapkan apakah email atau password yang salah.

## Logout

Authenticated user dapat melakukan logout.

Setelah logout:

- authenticated session harus invalid.
- protected API endpoints tidak dapat diakses.
- user harus diarahkan kembali ke login page.

## Current User

Frontend harus dapat mengambil informasi authenticated user melalui endpoint current-user.

Minimal informasi:

- id
- name
- email

Password tidak boleh dikembalikan.

## Protected Routes

Semua endpoint berikut harus membutuhkan authentication:

- Wishlist
- Budget
- Shopping recommendations
- Purchases
- Purchase history
- Dashboard
- User profile

Public endpoints hanya:

- Register
- Login
- Authentication status yang memang diperlukan oleh frontend

## Authorization

Authentication dan authorization adalah dua hal berbeda.

Authenticated user belum tentu memiliki akses ke semua resource.

Setiap resource harus diverifikasi berdasarkan ownership.

Contoh:

User A tidak boleh mengakses:

- Wishlist Item milik User B
- Budget milik User B
- Purchase milik User B
- Recommendation berdasarkan data User B

Mengubah resource ID pada URL tidak boleh dapat digunakan untuk mengakses data user lain.

## User Ownership

Relationship utama:

User:

- hasMany WishlistItems
- hasMany Budgets atau memiliki budget sesuai desain database
- hasMany Purchases

Setiap query terhadap user-owned resource harus menggunakan authenticated user sebagai scope.

Contoh konsep:

```php
auth()->user()->wishlistItems()
```

# 32. Delete Rules

Wishlist item yang sudah memiliki purchase record sebaiknya tidak dihapus secara hard delete.

Recommended behavior:

```text
ACTIVE → ARCHIVED
```

Untuk purchased item:

```text
PURCHASED
```

tetap dipertahankan agar purchase history tidak rusak.

Hard delete hanya boleh dilakukan apabila tidak menyebabkan orphaned records atau kehilangan historical data.

---

# 33. Category Deletion

Jika category memiliki wishlist item:

Category tidak boleh dihapus secara sembarangan jika foreign key akan menyebabkan data invalid.

Possible behavior:

- prevent deletion;
- archive category;
- reassign items to `Other`.

Implementasi final ditentukan di `DATABASE.md`.

---

# 34. Search Rules

Search wishlist berdasarkan nama item.

Search:

- case-insensitive;
- dapat menggunakan partial match;
- tidak boleh mengubah recommendation ranking.

Search/filter pada wishlist hanya memengaruhi tampilan wishlist kecuali user secara eksplisit menggunakan filter pada recommendation page.

---

# 35. Sorting Rules

Wishlist dapat di-sort berdasarkan:

```text
Priority
Price
Newest
Oldest
```

Sorting pada wishlist UI tidak boleh mengubah priority sebenarnya.

---

# 36. Recommendation vs Wishlist Sorting

Sorting wishlist dan recommendation merupakan dua hal berbeda.

Contoh:

```text
Wishlist sorting:
Price ASC
```

tidak berarti:

```text
Shopping recommendation:
Price ASC
```

Recommendation harus selalu menggunakan algorithm yang didefinisikan dalam `SHOPPING-ALGORITHM.md`.

---

# 37. Data Consistency

Backend harus menjadi source of truth.

Frontend tidak boleh dipercaya untuk menentukan:

- available budget;
- purchase eligibility;
- purchase price validity;
- recommendation ranking;
- ownership.

Semua critical business rules harus divalidasi ulang di backend.

---

# 38. API Behavior

API harus mengembalikan error yang konsisten.

Minimal:

```text
400
401
403
404
422
500
```

Validation error harus menggunakan format yang dapat diproses Vue SPA.

---

# 39. Currency

MVP menggunakan satu currency.

Default:

```text
IDR
```

Formatting:

```text
Rp1.500.000
```

Namun database tidak menyimpan string:

```text
"Rp1.500.000"
```

Database menyimpan numeric value:

```text
1500000
```

Formatting dilakukan pada presentation layer.

---

# 40. Date and Time

Purchase date harus disimpan sebagai timestamp.

Backend menjadi source of truth untuk timestamp.

Frontend bertanggung jawab atas formatting tanggal untuk user.

Timezone aplikasi harus konsisten.

---

# 41. Validation Principles

Validation harus dilakukan di backend.

Minimal:

### Wishlist Item

```text
name             required
category         required
priority         required
purpose          required
estimated_price  required
```

### Purchase

```text
actual_price     required
purchased_at     valid datetime
```

### Budget

```text
amount           required
amount >= 0
```

Exact validation limits seperti maximum string length harus ditentukan pada implementation/database layer.

---

# 42. Auditability

MVP tidak membutuhkan full audit log.

Namun purchase history harus immutable secara konsep.

Purchase record merepresentasikan kejadian bahwa pembelian pernah dilakukan.

Perubahan data wishlist setelah purchase tidak boleh menghilangkan fakta bahwa purchase pernah terjadi.

---

# 43. Business Rule Priority

Jika terjadi konflik antara informasi dari frontend dan backend:

```text
Backend > Frontend
```

Jika terjadi konflik antara implementasi dan business rules:

```text
Business Rules > Implementation
```

Jika terjadi konflik antara PRD dan Business Rules:

```text
Business Rules memberikan detail behavior.
PRD memberikan product scope.
```

Jika sebuah rule belum didefinisikan:

> Jangan membuat asumsi business-critical secara diam-diam. Dokumentasikan ambiguity dan minta keputusan sebelum implementasi.

---

# 44. Future Extension Compatibility

Business rules MVP harus memungkinkan pengembangan fitur berikut tanpa redesign besar:

- target purchase date;
- monthly shopping budget;
- budget history;
- price history;
- price tracking;
- external product links;
- spending analytics;
- advanced recommendation scoring.

Namun fitur tersebut **tidak boleh diimplementasikan dalam MVP hanya untuk future-proofing**.

---

# 45. Core Product Rule

Seluruh sistem harus mengikuti prinsip:

> **The application helps the user buy better, not buy more.**

Priority First harus menjadi recommendation utama.

Budget Optimization hanya menjadi alternatif untuk membantu user melihat kemungkinan penggunaan budget lainnya.

Sistem tidak boleh mendorong pembelian hanya karena masih terdapat budget.

---
