# Product Requirements Document

## Personal Purchase Planner

**Version:** 1.0
**Status:** Draft / MVP
**Stack:** Laravel API + Vue SPA + PostgreSQL

---

## 1. Product Overview

Personal Purchase Planner adalah aplikasi web personal untuk membantu pengguna mengelola daftar barang yang ingin dibeli, menentukan prioritas pembelian, mengatur dana belanja, dan mendapatkan rekomendasi barang yang paling tepat untuk dibeli berdasarkan dana yang tersedia.

Aplikasi ini bukan aplikasi e-commerce dan bukan inventory management.

Fokus utamanya adalah menjawab pertanyaan:

> **"Dengan dana yang gue punya sekarang, barang mana yang paling masuk akal untuk gue beli?"**

Aplikasi memiliki dua mode rekomendasi:

1. **Priority First** — rekomendasi utama berdasarkan prioritas barang.
2. **Budget Optimization** — rekomendasi alternatif untuk memaksimalkan penggunaan budget.

Priority First menjadi metode utama dan default.

---

# 2. Problem Statement

Pengguna sering memiliki banyak barang yang ingin dibeli, tetapi:

- tidak semua barang memiliki tingkat kepentingan yang sama;
- beberapa barang merupakan kebutuhan, sementara yang lain hanya keinginan;
- dana untuk membeli barang terbatas;
- pengguna sulit menentukan barang mana yang harus dibeli terlebih dahulu;
- wishlist biasa hanya menyimpan daftar barang tanpa membantu mengambil keputusan;
- pengguna dapat membeli barang yang kurang penting sementara barang yang lebih penting masih tertunda.

Aplikasi ini bertujuan mengubah wishlist statis menjadi **purchase planning system** yang membantu pengguna mengambil keputusan pembelian.

---

# 3. Product Goals

### Primary Goals

1. Membantu pengguna menyimpan dan mengelola wishlist.
2. Membantu pengguna menentukan prioritas setiap barang.
3. Membantu pengguna membedakan kebutuhan dan keinginan.
4. Membantu pengguna mengelola dana belanja.
5. Memberikan rekomendasi pembelian berdasarkan prioritas dan budget.
6. Menyediakan alternatif rekomendasi berdasarkan optimalisasi budget.
7. Mencatat barang yang sudah dibeli.

### Secondary Goals

1. Memberikan gambaran kondisi wishlist secara cepat.
2. Menunjukkan barang yang sudah mampu dibeli.
3. Menunjukkan berapa dana tambahan yang dibutuhkan untuk membeli barang tertentu.
4. Memberikan riwayat pembelian.

---

# 4. Non-Goals

Fitur berikut tidak termasuk dalam MVP:

- E-commerce
- Marketplace
- Payment gateway
- Integrasi toko online
- Automatic price tracking
- Price comparison antar toko
- Stock management
- Inventory management
- Bank account integration
- Automatic financial transaction import
- Cryptocurrency/investment tracking
- Multi-user collaboration
- Social features
- AI recommendation
- Automatic income tracking
- Monthly financial accounting

Fitur tersebut dapat dipertimbangkan pada versi berikutnya.

---

# 5. Target User

### Primary User

Individual user yang ingin mengatur barang-barang yang ingin dibeli secara lebih terencana.

Contoh:

> User memiliki 10 barang dalam wishlist dan dana Rp1.500.000. User ingin mengetahui barang mana yang sebaiknya dibeli terlebih dahulu.

---

# 6. Core User Journey

```text
Login
  ↓
Dashboard
  ↓
Set Shopping Budget
  ↓
Create Wishlist Items
  ↓
Set Priority & Need/Want
  ↓
Open Shopping
  ↓
View Priority First Recommendations
  ↓
Choose Item
  ↓
Purchase
  ↓
Budget Updated
  ↓
Purchase History
```

Alternative flow:

```text
Shopping
  ↓
Budget Optimization
  ↓
View alternative item combination
  ↓
Purchase selected items
```

---

# 7. Information Architecture

Aplikasi memiliki navigation utama:

```text
Dashboard
Wishlist
Shopping
Purchase History
Settings
```

Authentication:

```text
Login
Register
Logout
```

---

# 8. Feature Requirements

> **Authentication & Authorization**
>
> User harus dapat melakukan Register, Login, Logout, dan mengakses current authenticated user. Seluruh fitur utama aplikasi merupakan protected feature dan hanya dapat digunakan oleh authenticated user. Seluruh data wishlist, budget, recommendation, dan purchase harus terisolasi berdasarkan user yang sedang login.

## 8.1 Authentication

User harus dapat:

- Register
- Login
- Logout
- Mengakses data pribadi setelah login

Semua data wishlist, budget, dan purchase harus terisolasi berdasarkan user.

User A tidak boleh mengakses data User B.

---

# 9. Dashboard

Dashboard menjadi halaman utama setelah login.

### Required Information

#### Available Budget

Menampilkan dana yang saat ini tersedia untuk pembelian.

Contoh:

```text
Available Budget

Rp1.500.000
```

#### Wishlist Summary

```text
Total Items
12
```

#### High Priority Items

```text
High Priority
4
```

#### Affordable Items

Jumlah barang yang saat ini dapat dibeli berdasarkan available budget.

#### Priority First Recommendation

Menampilkan beberapa rekomendasi utama.

Contoh:

```text
Recommended to Buy

Mouse
Electronics · Need
High Priority
Rp450.000

[Buy]
```

#### Budget Optimization Preview

Menampilkan ringkasan rekomendasi alternatif.

---

# 10. Wishlist

Wishlist merupakan tempat utama untuk mengelola barang yang ingin dibeli.

## 10.1 Add Wishlist Item

User dapat membuat item baru.

Required fields:

### Name

Nama barang.

Contoh:

```text
Sony WH-1000XM6
```

### Category

Kategori barang.

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

Category harus dapat dikembangkan tanpa mengubah struktur utama wishlist.

### Priority

Pilihan:

```text
High
Medium
Low
```

### Purpose

Pilihan:

```text
Need
Want
```

### Estimated Price

Estimasi harga barang.

### Notes

Opsional.

Digunakan untuk informasi tambahan mengenai barang.

---

# 11. Wishlist Item Status

Wishlist item memiliki lifecycle.

Minimal status:

```text
ACTIVE
PURCHASED
ARCHIVED
```

### ACTIVE

Barang masih ingin dibeli.

### PURCHASED

Barang sudah dibeli.

### ARCHIVED

Barang tidak lagi ingin dibeli tetapi history data tetap dipertahankan.

Archived item tidak boleh muncul dalam recommendation.

Purchased item tidak boleh muncul sebagai active recommendation.

---

# 12. Wishlist List

Wishlist harus menyediakan:

### Search

Search berdasarkan nama barang.

### Filter

Filter berdasarkan:

- Category
- Priority
- Purpose
- Status

### Sort

Minimal:

- Priority
- Price
- Newest
- Oldest

---

# 13. Shopping Budget

User memiliki dana yang digunakan sebagai basis recommendation.

Contoh:

```text
Shopping Budget

Rp2.000.000
```

Budget harus dapat diubah oleh user.

Sistem harus selalu dapat menentukan:

```text
Available Budget
```

berdasarkan budget dan transaksi pembelian yang telah dilakukan.

---

# 14. Shopping Page

Shopping merupakan fitur utama aplikasi.

Halaman ini harus memberikan rekomendasi berdasarkan budget yang tersedia.

Struktur:

```text
Shopping

Available Budget
Rp1.500.000

Priority First
-----------------
Recommendation Queue

Budget Optimization
-----------------
Alternative Recommendations

Can't Afford Yet
-----------------
Items requiring additional budget
```

---

# 15. Priority First

Priority First adalah recommendation system utama.

Tujuannya:

> Menentukan barang paling penting yang dapat dibeli dengan dana yang tersedia.

Recommendation harus mempertimbangkan:

1. Purpose
2. Priority
3. Price
4. Current available budget

Secara konsep:

```text
Need
  ↓
Priority
  ↓
Affordable
  ↓
Price
```

Detail algoritma harus didefinisikan secara terpisah di:

```text
docs/SHOPPING-ALGORITHM.md
```

PRD ini hanya mendefinisikan product requirement.

---

# 16. Priority First Example

Available budget:

```text
Rp1.000.000
```

Wishlist:

| Item     | Purpose | Priority |        Price |
| -------- | ------- | -------- | -----------: |
| Laptop   | Need    | High     | Rp10.000.000 |
| Mouse    | Need    | High     |    Rp500.000 |
| Skincare | Need    | Medium   |    Rp200.000 |
| Parfum   | Want    | Low      |    Rp300.000 |

Recommendation:

```text
1. Mouse
2. Skincare
```

Laptop tidak direkomendasikan sebagai immediate purchase karena tidak affordable.

Parfum memiliki prioritas lebih rendah.

---

# 17. Budget Optimization

Budget Optimization adalah recommendation mode alternatif.

Tujuannya:

> Menggunakan dana yang tersedia secara lebih optimal dengan mempertimbangkan beberapa barang sekaligus.

Contoh:

```text
Budget
Rp1.000.000
```

Pilihan:

```text
A = Rp900.000
B = Rp400.000
C = Rp300.000
D = Rp300.000
```

Priority First dapat memilih:

```text
A
```

Budget Optimization dapat menghasilkan:

```text
B + C + D
= Rp1.000.000
```

Namun optimization tidak boleh hanya mengejar jumlah barang terbanyak.

Recommendation harus tetap mempertimbangkan:

- Need vs Want
- Priority
- Total cost
- Budget utilization

Detail algorithm harus didefinisikan di:

```text
docs/SHOPPING-ALGORITHM.md
```

---

# 18. Can't Afford Yet

Shopping page harus memberikan informasi mengenai barang yang belum mampu dibeli.

Contoh:

```text
iPhone
Rp15.000.000

Available budget
Rp5.000.000

Additional budget needed
Rp10.000.000
```

Informasi ini membantu user mengetahui target dana yang masih diperlukan.

---

# 19. Purchase Flow

User dapat membeli item dari Shopping atau Wishlist.

Ketika user melakukan purchase:

1. Item ditandai sebagai `PURCHASED`.
2. Purchase record dibuat.
3. Purchase price dicatat.
4. Purchase date dicatat.
5. Available budget diperbarui.
6. Item tidak lagi muncul dalam active recommendation.

Purchase price dapat berbeda dengan estimated price.

Contoh:

```text
Estimated Price
Rp500.000

Actual Purchase Price
Rp450.000
```

Sistem harus menggunakan **actual purchase price** untuk pencatatan pengeluaran.

---

# 20. Purchase History

Menampilkan seluruh barang yang sudah dibeli.

Informasi minimal:

- Item name
- Category
- Purpose
- Priority
- Estimated price
- Actual purchase price
- Purchase date

User dapat melihat detail purchase.

---

# 21. Budget Behavior

Budget harus menjadi sumber perhitungan recommendation.

Contoh:

```text
Initial Budget
Rp2.000.000

Purchase
Rp500.000

Available Budget
Rp1.500.000
```

Purchase tidak boleh mengubah estimated price wishlist item.

Actual purchase price disimpan pada purchase record.

---

# 22. Data Ownership

Semua data harus dimiliki oleh authenticated user.

Data utama:

```text
User
 ├── Wishlist Items
 ├── Budgets
 └── Purchases
```

Authorization wajib diterapkan pada API.

User tidak boleh:

- melihat wishlist user lain;
- mengubah wishlist user lain;
- menghapus wishlist user lain;
- membuat purchase untuk wishlist user lain;
- membaca budget user lain.

---

# 23. API Requirements

Laravel bertindak sebagai API backend.

Vue SPA bertindak sebagai frontend client.

Business logic recommendation berada di Laravel.

Vue tidak boleh menjadi source of truth untuk:

- priority ranking;
- budget calculation;
- purchase calculation;
- recommendation algorithm.

Frontend hanya menampilkan hasil dari API.

---

# 24. Frontend Requirements

Vue SPA harus menyediakan:

- Responsive layout
- Dashboard
- Wishlist management
- Shopping recommendations
- Purchase history
- Budget management
- Loading states
- Empty states
- Error states
- Confirmation dialog untuk destructive actions
- Success feedback setelah mutation

UI harus mengutamakan:

- informasi budget yang jelas;
- priority yang mudah dipahami;
- recommendation yang mudah dipindai;
- action pembelian yang jelas.

---

# 25. Important UX Principle

Aplikasi harus selalu membuat user memahami:

### Why?

Kenapa barang tersebut direkomendasikan.

Contoh:

```text
Recommended because:

✓ Need
✓ High Priority
✓ Affordable
```

Untuk item yang belum mampu dibeli:

```text
Not affordable yet

Rp1.500.000 needed
```

Recommendation tidak boleh terasa seperti keputusan misterius dari sistem.

---

# 26. Empty States

Setiap halaman harus memiliki empty state.

Contoh Wishlist:

```text
Your wishlist is empty.

Add something you want to buy.
[Add Item]
```

Shopping:

```text
No items can be purchased right now.

Add more budget or review your wishlist.
```

Purchase History:

```text
No purchases yet.
```

---

# 27. Error Handling

API harus memberikan error response yang konsisten.

Frontend harus menangani:

- validation error
- unauthorized
- forbidden
- not found
- server error
- network error

User harus mendapatkan feedback yang mudah dipahami.

---

# 28. MVP Scope

### Must Have

- Authentication
- Wishlist CRUD
- Category
- Priority
- Need/Want
- Estimated price
- Budget management
- Priority First
- Budget Optimization
- Purchase flow
- Purchase history
- Dashboard
- Search/filter/sort
- Authorization
- Automated tests untuk business logic

### Should Have

- Archive wishlist item
- "Can't afford yet" calculation
- Recommendation explanation
- Responsive UI

### Later

- Price history
- Target purchase date
- Monthly budget
- Budget history
- Price tracking
- External shopping links
- Advanced analytics
- AI recommendations

---

# 29. Success Criteria

MVP dianggap berhasil apabila user dapat:

1. Membuat wishlist item.
2. Menentukan Need/Want.
3. Menentukan priority.
4. Menentukan estimasi harga.
5. Menentukan shopping budget.
6. Melihat rekomendasi Priority First.
7. Melihat rekomendasi Budget Optimization.
8. Membeli item melalui aplikasi.
9. Melihat budget setelah pembelian.
10. Melihat purchase history.
11. Memahami alasan sebuah item direkomendasikan.
12. Tidak dapat mengakses data user lain.

---

# 30. Technical Constraints

Backend:

```text
Laravel
```

Frontend:

```text
Vue SPA
```

Database:

```text
PostgreSQL
```

Laravel digunakan sebagai API/backend.

Vue digunakan sebagai SPA frontend.

Jangan mengganti stack tanpa alasan teknis yang kuat dan persetujuan terlebih dahulu.

---

# 31. Documentation Dependencies

PRD ini mendefinisikan product scope.

Detail berikut harus didefinisikan dalam dokumen terpisah:

```text
docs/BUSINESS-RULES.md
```

Berisi aturan bisnis.

```text
docs/SHOPPING-ALGORITHM.md
```

Berisi algoritma Priority First dan Budget Optimization.

```text
docs/DATABASE.md
```

Berisi database schema dan relationships.

```text
docs/UI-UX.md
```

Berisi struktur halaman dan UX behavior.

```text
docs/DEVELOPMENT.md
```

Berisi coding conventions, testing strategy, API conventions, dan development workflow.

---

# 32. Product Principle

Prinsip utama aplikasi:

> **Don't help users buy more. Help them buy better.**

Aplikasi tidak bertujuan membuat user membeli sebanyak mungkin barang.

Aplikasi bertujuan membantu user menentukan:

> **barang mana yang paling layak dibeli sekarang berdasarkan kebutuhan, prioritas, dan kemampuan finansial.**
