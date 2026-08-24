# Shopping Algorithm

## Personal Purchase Planner

**Version:** 1.0
**Status:** MVP
**Related Documents:** `PRD.md`, `BUSINESS-RULES.md`, `DATABASE.md`

---

# 1. Purpose

Dokumen ini mendefinisikan algoritma recommendation untuk fitur **Shopping**.

Aplikasi memiliki dua mode recommendation:

1. **Priority First** — mode utama.
2. **Budget Optimization** — mode alternatif.

Keduanya menggunakan wishlist aktif dan available budget sebagai input utama.

Prinsip utama:

> **Priority First menentukan apa yang paling penting untuk dibeli. Budget Optimization menentukan bagaimana budget dapat digunakan secara lebih optimal tanpa mengabaikan prioritas.**

---

# 2. Terminology

## 2.1 Available Budget

Dana yang dapat digunakan user untuk melakukan pembelian saat ini.

Contoh:

```text
Available Budget = Rp1.000.000
```

---

## 2.2 Wishlist Candidate

Wishlist item yang memenuhi:

```text
status = ACTIVE
```

Item dengan status:

```text
PURCHASED
ARCHIVED
```

tidak boleh menjadi candidate.

---

## 2.3 Affordable Item

Item dianggap affordable jika:

```text
estimated_price <= available_budget
```

Exact match diperbolehkan.

Contoh:

```text
Budget = Rp500.000
Price  = Rp500.000

Affordable = TRUE
```

---

## 2.4 Need

Purpose:

```text
NEED
```

merepresentasikan kebutuhan.

---

## 2.5 Want

Purpose:

```text
WANT
```

merepresentasikan keinginan.

---

## 2.6 Priority

Priority memiliki tiga level:

```text
HIGH
MEDIUM
LOW
```

Ranking dasar:

```text
HIGH > MEDIUM > LOW
```

---

# 3. Recommendation Architecture

Recommendation harus diproses oleh Laravel backend.

Vue SPA hanya menerima hasil recommendation dari API.

Architecture:

```text
Vue SPA
   ↓
Shopping API
   ↓
Recommendation Service
   ├── Priority First
   └── Budget Optimization
   ↓
Wishlist + Budget + Purchase Data
   ↓
Recommendation Result
```

Vue tidak boleh menghitung ranking recommendation secara authoritative.

---

# 4. Recommendation Input

Recommendation membutuhkan:

```text
User
Available Budget
Active Wishlist Items
Purchase State
```

Setiap active wishlist item minimal memiliki:

```text
id
name
category
priority
purpose
estimated_price
status
```

---

# 5. Common Filtering

Sebelum menjalankan algoritma apa pun:

### Step 1 — Ownership

Ambil hanya wishlist item milik authenticated user.

### Step 2 — Status

Ambil hanya:

```text
ACTIVE
```

### Step 3 — Valid Price

Item harus memiliki estimated price valid:

```text
estimated_price > 0
```

Item dengan invalid price tidak boleh masuk recommendation.

---

# 6. Priority First

Priority First adalah recommendation utama.

Tujuan:

> Menentukan urutan pembelian berdasarkan tingkat kepentingan item dan kemampuan budget.

Priority First tidak bertujuan memaksimalkan jumlah barang.

---

# 7. Priority First Ranking

Untuk MVP, ranking menggunakan urutan konseptual:

```text
1. Purpose
2. Priority
3. Affordability
4. Price
5. Creation date
```

Purpose memiliki ranking:

```text
NEED > WANT
```

Priority memiliki ranking:

```text
HIGH > MEDIUM > LOW
```

Price dan creation date digunakan sebagai tie-breaker.

---

# 8. Priority First Important Rule

**Affordability tidak digunakan untuk membuat item yang tidak mampu dibeli menjadi recommendation pembelian.**

Namun affordability digunakan untuk menentukan apakah item dapat masuk ke immediate purchase queue.

Contoh:

```text
Budget = Rp500.000

A:
Need + High
Rp5.000.000

B:
Need + High
Rp400.000
```

Hasil:

```text
Immediate Recommendation:
B

Can't Afford Yet:
A
```

---

# 9. Priority First Example

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
| Sepatu   | Want    | High     |    Rp400.000 |
| Parfum   | Want    | Low      |    Rp150.000 |

Priority First menghasilkan immediate queue berdasarkan ranking.

Contoh hasil:

```text
1. Mouse
2. Skincare
3. Sepatu
4. Parfum
```

Item yang tidak mampu dibeli:

```text
Laptop
```

Namun urutan final harus mengikuti deterministic ranking rules yang diimplementasikan.

---

# 10. Priority First Purchase Queue

Priority First menggunakan konsep **sequential budget allocation**.

Pseudo-process:

```text
remaining_budget = available_budget

for item in ranked_candidates:

    if item.price <= remaining_budget:
        add item to recommendation
        remaining_budget -= item.price
    else:
        add item to can't_afford list
```

Dengan demikian recommendation dapat terdiri dari beberapa item.

Contoh:

```text
Budget = Rp1.000.000

A = Rp600.000
B = Rp500.000
C = Rp200.000
```

Jika ranking:

```text
A
B
C
```

maka:

```text
A → selected
B → skipped
C → selected
```

Total:

```text
Rp800.000
```

Remaining:

```text
Rp200.000
```

---

# 11. Important Priority First Behavior

Item yang tidak mampu dibeli **tidak otomatis menghabiskan budget secara virtual**.

Contoh:

```text
Budget = Rp500.000

A = High / Need / Rp2.000.000
B = Medium / Need / Rp300.000
```

Hasil:

```text
A → Can't Afford Yet
B → Recommended
```

B tetap dapat dibeli.

---

# 12. Why Recommendation

Setiap Priority First recommendation sebaiknya dapat memberikan explanation.

Contoh:

```text
Recommended because:

✓ Need
✓ High priority
✓ Within your current budget
```

Explanation merupakan presentation data berdasarkan hasil algorithm.

Explanation tidak boleh menjadi sumber ranking terpisah.

---

# 13. Budget Optimization

Budget Optimization adalah mode recommendation alternatif.

Tujuan:

> Mencari kombinasi item yang memberikan penggunaan budget lebih optimal daripada sekadar memilih item pertama berdasarkan priority.

Budget Optimization adalah **optimization problem** dengan constraint budget.

Secara konseptual:

```text
maximize usefulness
subject to:

total_selected_price <= available_budget
```

---

# 14. Budget Optimization Philosophy

Budget Optimization tidak berarti:

> "Pilih barang sebanyak mungkin."

Budget Optimization berarti:

> "Cari kombinasi barang yang paling bernilai berdasarkan priority, purpose, dan penggunaan budget."

Karena itu kombinasi:

```text
3 Want + Low
```

tidak boleh otomatis mengalahkan:

```text
1 Need + High
```

hanya karena jumlah item lebih banyak.

---

# 15. Budget Optimization Score

Untuk MVP, setiap item memiliki conceptual score.

Base priority score:

```text
HIGH   = 3
MEDIUM = 2
LOW    = 1
```

Purpose score:

```text
NEED = 2
WANT = 1
```

Base item score:

```text
priority_score × purpose_score
```

Contoh:

| Purpose | Priority | Score |
| ------- | -------- | ----: |
| Need    | High     |     6 |
| Need    | Medium   |     4 |
| Need    | Low      |     2 |
| Want    | High     |     3 |
| Want    | Medium   |     2 |
| Want    | Low      |     1 |

---

# 16. Budget Optimization Must Consider Price

Score tidak boleh menjadi satu-satunya faktor.

Contoh:

```text
A:
Need + High
Score = 6
Price = Rp950.000

B:
Need + High
Score = 6
Price = Rp300.000

C:
Need + Medium
Score = 4
Price = Rp300.000
```

Budget:

```text
Rp1.000.000
```

Budget Optimization harus mempertimbangkan kombinasi:

```text
B + C
```

dibanding:

```text
A
```

karena B + C memberikan total score:

```text
6 + 4 = 10
```

dengan penggunaan:

```text
Rp600.000
```

---

# 17. Optimization Objective

Untuk MVP, Budget Optimization harus melakukan optimasi berdasarkan beberapa objective secara berurutan:

```text
1. Maximize total recommendation score
2. Stay within available budget
3. Prefer higher budget utilization
4. Prefer fewer unnecessary low-value items
```

Objective pertama adalah yang paling penting.

Budget utilization digunakan sebagai tie-breaker, bukan tujuan tunggal.

---

# 18. Budget Utilization

Budget utilization:

```text
total_selected_price / available_budget
```

Contoh:

```text
Budget = Rp1.000.000
Selected = Rp900.000

Utilization = 90%
```

Jika dua kombinasi memiliki score yang sama, kombinasi dengan utilization lebih tinggi dapat diprioritaskan.

---

# 19. Avoiding Low-Value Item Padding

Sistem tidak boleh menambahkan item low-priority hanya untuk meningkatkan budget utilization jika item tersebut tidak memberikan nilai yang berarti.

Contoh:

```text
Budget = Rp1.000.000

A:
Need + High
Rp900.000
Score 6

B:
Want + Low
Rp50.000
Score 1
```

Sistem tidak wajib memilih:

```text
A + B
```

hanya karena total utilization menjadi 95%.

---

# 20. Budget Optimization Example

Budget:

```text
Rp1.000.000
```

Candidates:

| Item | Purpose | Priority | Price | Score |
| ---- | ------- | -------- | ----: | ----: |
| A    | Need    | High     |  900k |     6 |
| B    | Need    | High     |  400k |     6 |
| C    | Need    | Medium   |  300k |     4 |
| D    | Want    | Low      |  100k |     1 |

Possible combinations:

```text
A
Score = 6
Cost = 900k

B + C
Score = 10
Cost = 700k

B + C + D
Score = 11
Cost = 800k

B + D
Score = 7
Cost = 500k

C + D
Score = 5
Cost = 400k
```

Budget Optimization:

```text
B + C + D
```

karena memiliki total score tertinggi (11) tanpa melebihi budget.

Catatan: kombinasi dengan total score tertinggi selalu menang selama masih
dalam batas budget (objective #1 pada bagian 17). Utilization hanya digunakan
sebagai tie-breaker ketika dua kombinasi memiliki total score yang sama.
Menambahkan item low-value diperbolehkan apabila item tersebut tetap
meningkatkan total score dan tidak melebihi budget.

---

# 21. Recommendation Output

Backend recommendation service sebaiknya mengembalikan struktur konseptual:

```json
{
    "available_budget": 1000000,
    "priority_first": {
        "items": [],
        "total": 800000,
        "remaining_budget": 200000
    },
    "budget_optimization": {
        "items": [],
        "total": 900000,
        "remaining_budget": 100000,
        "score": 10,
        "utilization": 0.9
    },
    "can't_afford": []
}
```

Actual API contract harus mengikuti API design yang ditentukan kemudian.

---

# 22. Recommendation Result Must Be Deterministic

Untuk input data yang sama, recommendation harus menghasilkan output yang sama.

Contoh:

```text
Same budget
+
Same wishlist
+
Same item states
=
Same recommendation
```

Random recommendation tidak diperbolehkan.

---

# 23. Tie Breaking

Jika dua item memiliki ranking/score yang sama, gunakan tie-breaker secara deterministic.

Recommended order:

```text
1. Higher purpose score
2. Higher priority score
3. Lower price
4. Older creation date
5. Lower ID
```

Lower ID digunakan sebagai final deterministic fallback jika diperlukan.

---

# 24. Zero Budget

Jika:

```text
available_budget = 0
```

maka:

```text
Priority First immediate recommendations = []
Budget Optimization recommendations = []
```

Semua active items yang memiliki price > 0 dapat masuk:

```text
Can't Afford Yet
```

---

# 25. Empty Wishlist

Jika tidak ada active wishlist item:

```text
Priority First = []
Budget Optimization = []
Can't Afford = []
```

Frontend harus menampilkan empty state.

---

# 26. No Affordable Item

Jika semua active items lebih mahal daripada available budget:

```text
Priority First = []
Budget Optimization = []
Can't Afford = all active items
```

Frontend harus memberikan informasi bahwa belum ada item yang dapat dibeli dengan budget saat ini.

---

# 27. Exact Budget Match

Item dengan harga sama persis dengan available budget dianggap affordable.

Contoh:

```text
Budget = Rp500.000
Item = Rp500.000
```

Item dapat dipilih.

Setelah purchase:

```text
Remaining Budget = Rp0
```

---

# 28. Budget Changes

Recommendation harus dihitung ulang ketika:

- budget berubah;
- wishlist item ditambahkan;
- wishlist item diubah;
- wishlist item dihapus/diarsipkan;
- purchase dilakukan.

Tidak boleh mengandalkan recommendation lama jika underlying data berubah.

---

# 29. Price Changes

Jika estimated price berubah, recommendation harus dihitung ulang.

Contoh:

```text
Before:
Mouse = Rp400.000

Budget = Rp500.000

Affordable = TRUE
```

Jika price berubah:

```text
Mouse = Rp600.000
```

maka:

```text
Affordable = FALSE
```

---

# 30. Purchased Items

Purchased item tidak boleh masuk candidate list.

Contoh:

```text
Mouse
Status = PURCHASED
```

tidak boleh muncul lagi di:

```text
Priority First
Budget Optimization
Can't Afford Yet
```

Namun tetap muncul di Purchase History.

---

# 31. Archived Items

Archived item tidak boleh masuk recommendation.

Jika user ingin membeli kembali item tersebut:

```text
ARCHIVED
    ↓
New Wishlist Item
```

atau item dapat diaktifkan kembali sesuai business rule.

---

# 32. Recommendation Does Not Create Purchase

Recommendation hanya memberikan suggestion.

Flow:

```text
Recommendation
      ↓
User chooses
      ↓
Purchase confirmation
      ↓
Backend validates budget again
      ↓
Purchase created
```

Tidak ada automatic purchase.

---

# 33. Backend Validation Before Purchase

Walaupun item muncul sebagai affordable recommendation, backend harus melakukan pengecekan ulang ketika purchase.

Check:

```text
1. User authenticated
2. Item belongs to user
3. Item status = ACTIVE
4. Actual price valid
5. Budget sufficient according to purchase rules
```

Jika gagal:

```text
Purchase rejected
```

---

# 34. Algorithm Separation

Priority First dan Budget Optimization harus diimplementasikan sebagai business logic yang terpisah.

Recommended conceptual structure:

```text
RecommendationService
├── PriorityFirstStrategy
└── BudgetOptimizationStrategy
```

Namun implementation tidak harus menggunakan Strategy Pattern jika project terlalu sederhana.

Yang paling penting:

- logic tidak berada di Controller;
- logic tidak berada di Vue;
- kedua algorithm dapat ditest secara independen.

---

# 35. Algorithm Testing

Automated tests wajib mencakup:

## Priority First

- empty wishlist;
- zero budget;
- exact price match;
- insufficient budget;
- high priority affordable item;
- high priority unaffordable item;
- multiple affordable items;
- mixed Need/Want;
- mixed High/Medium/Low;
- purchased items;
- archived items;
- budget changes;
- deterministic tie breaking.

## Budget Optimization

- empty wishlist;
- zero budget;
- exact budget;
- one-item optimal solution;
- multi-item optimal solution;
- item combination;
- Need vs Want;
- priority differences;
- budget utilization;
- no affordable combination;
- purchased items excluded;
- archived items excluded;
- deterministic result.

---

# 36. Algorithm Complexity

Budget Optimization merupakan variasi knapsack problem.

Untuk MVP, implementation boleh menggunakan brute-force/combinational approach jika jumlah wishlist item dibatasi dan tetap dalam batas performa yang wajar.

Jangan melakukan premature optimization.

Jika jumlah wishlist item berkembang besar dan kombinatorial explosion menjadi masalah, algorithm dapat dioptimalkan pada tahap berikutnya.

---

# 37. Recommendation Limits

Frontend tidak harus menampilkan seluruh recommendation result.

API dapat mengembalikan full result atau menggunakan limit/pagination sesuai kebutuhan.

Untuk dashboard, recommendation dapat dibatasi ke beberapa item.

Shopping page dapat menampilkan hasil yang lebih lengkap.

---

# 38. Important Distinction

Priority First:

> **"Apa yang paling penting untuk gue beli sekarang?"**

Budget Optimization:

> **"Kalau gue ingin menggunakan budget gue dengan lebih optimal, kombinasi apa yang paling bernilai?"**

Kedua mode tidak harus menghasilkan hasil yang sama.

Contoh:

```text
Priority First
→ 1 barang penting

Budget Optimization
→ 2–3 barang dengan total value lebih tinggi
```

Perbedaan tersebut merupakan behavior yang disengaja.

---

# 39. No Recommendation Manipulation

Algorithm tidak boleh sengaja:

- menaikkan ranking barang mahal;
- menurunkan ranking barang murah;
- memprioritaskan Want hanya karena murah;
- memilih barang hanya untuk menghabiskan budget;
- memilih jumlah item terbanyak tanpa mempertimbangkan value.

Tujuan recommendation adalah membantu keputusan pembelian, bukan memaksimalkan spending.

---

# 40. Core Algorithm Principle

Seluruh recommendation system harus mengikuti prinsip:

> **Priority First tells the user what matters most. Budget Optimization tells the user what combination makes the most sense.**

Budget merupakan constraint.

Priority dan Need/Want merupakan value signals.

Purchase tetap merupakan keputusan user.

---
