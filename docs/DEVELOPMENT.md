# Development Guide

## Local development dengan Docker

Satu-satunya dependency di host adalah Docker Desktop (atau Docker Engine dengan
Compose). PHP, Composer, Node.js, dependency aplikasi, queue worker, dan PostgreSQL
berjalan di container.

```bash
docker compose up --build
```

Buka aplikasi di `http://localhost:8000`. Vite HMR berjalan di port `5173`, dan
migrasi serta data demo dijalankan otomatis ketika stack dinyalakan. Login demo:
`arvy@example.com` / `password123`.

Perintah Artisan atau test dapat dijalankan tanpa memasang PHP di host:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan db:seed
docker compose exec app php artisan migrate:fresh --seed
```

Hentikan container dengan `docker compose down`. Data PostgreSQL dan dependency
tetap tersimpan di Docker volumes. Gunakan `docker compose down --volumes` hanya
jika seluruh data development dan dependency volume memang ingin dihapus.

## Personal Purchase Planner

**Version:** 1.0
**Status:** MVP
**Related Documents:** `PRD.md`, `BUSINESS-RULES.md`, `SHOPPING-ALGORITHM.md`, `UI-UX.md`, `DATABASE.md`

---

# 1. Purpose

Dokumen ini mendefinisikan aturan development untuk Personal Purchase Planner.

Dokumen ini digunakan sebagai panduan bagi developer dan AI coding agent seperti OpenCode agar implementasi tetap konsisten dengan product requirements dan business rules.

---

# 2. Technology Stack

Project menggunakan:

### Backend

- Laravel
- PHP
- Laravel API
- Eloquent ORM

### Frontend

- Vue
- Vue SPA
- TypeScript jika sudah digunakan oleh existing project
- Existing frontend tooling harus dipertahankan

### Database

- PostgreSQL

### Testing

- Laravel testing framework yang tersedia pada project
- PHPUnit atau Pest sesuai existing setup

---

# 3. Existing Project First

Project Laravel sudah dibuat dan dikonfigurasi.

Developer atau AI agent **tidak boleh membuat project Laravel baru**.

Sebelum melakukan perubahan:

1. Inspect repository.
2. Inspect Laravel version.
3. Inspect PHP version.
4. Inspect Vue version.
5. Inspect package.json.
6. Inspect existing authentication setup.
7. Inspect routes.
8. Inspect migrations.
9. Inspect models.
10. Inspect controllers.
11. Inspect services.
12. Inspect Vue pages/components.
13. Inspect `.env.example`.
14. Inspect existing tests.

Existing architecture harus dipahami sebelum menambahkan feature.

---

# 4. Architecture

Architecture utama:

```text
Vue SPA
   ↓
HTTP / JSON API
   ↓
Laravel
   ├── Authentication
   ├── Controllers
   ├── Form Requests
   ├── Services
   ├── Models
   └── Policies
        ↓
   PostgreSQL
```

Business logic utama berada di Laravel.

Vue bertanggung jawab terhadap presentation dan user interaction.

---

# 5. Backend Responsibilities

Laravel bertanggung jawab atas:

- authentication;
- authorization;
- validation;
- database access;
- business rules;
- budget calculation;
- recommendation algorithms;
- purchase validation;
- data persistence.

---

# 6. Frontend Responsibilities

Vue bertanggung jawab atas:

- page rendering;
- component rendering;
- form interaction;
- client-side validation;
- API communication;
- loading state;
- error state;
- navigation;
- displaying recommendation;
- user feedback.

Frontend tidak boleh menjadi authoritative source untuk business logic.

---

# 7. API-First Development

Backend harus menyediakan API yang jelas untuk Vue SPA.

Recommended structure:

```text
/api
├── auth
├── user
├── wishlist-items
├── categories
├── budget
├── shopping
└── purchases
```

Exact endpoints harus ditentukan setelah existing project structure diinspeksi.

---

# 8. API Versioning

Untuk MVP, API versioning tidak wajib jika belum dibutuhkan oleh existing project.

Jika project sudah menggunakan versioning:

```text
/api/v1
```

pertahankan pola tersebut.

Jangan membuat API versioning baru hanya untuk mengikuti preferensi pribadi.

---

# 9. Authentication

Authentication menggunakan mekanisme yang sudah tersedia atau paling sesuai dengan existing Laravel SPA setup.

Jangan membuat custom authentication mechanism jika Laravel menyediakan solusi yang sesuai.

Protected resources harus membutuhkan authenticated user.

---

# 10. Authorization

Gunakan Laravel Policies atau authorization mechanism yang sesuai.

Contoh konsep:

```php
$user->wishlistItems()
```

Resource harus selalu di-scope berdasarkan authenticated user.

Jangan mengambil resource global berdasarkan ID lalu hanya mengandalkan frontend untuk membatasi akses.

---

# 11. Controllers

Controller harus tipis.

Controller bertanggung jawab untuk:

1. menerima request;
2. melakukan authorization;
3. memanggil validation;
4. memanggil business service;
5. mengembalikan response.

Controller tidak boleh mengandung recommendation algorithm.

Bad:

```php
public function shopping()
{
    // 100 lines of ranking logic
}
```

Better:

```php
public function shopping(ShoppingRecommendationService $service)
{
    return response()->json(
        $service->recommend($request->user())
    );
}
```

---

# 12. Form Requests

Gunakan Form Request untuk validation yang cukup kompleks.

Contoh:

```text
StoreWishlistItemRequest
UpdateWishlistItemRequest
UpdateBudgetRequest
StorePurchaseRequest
```

Validation harus dilakukan di backend.

Frontend validation boleh digunakan untuk UX tetapi tidak menggantikan backend validation.

---

# 13. Services

Gunakan service untuk business logic yang memiliki behavior atau workflow kompleks.

Recommended conceptual services:

```text
WishlistService
BudgetService
PurchaseService
ShoppingRecommendationService
```

Tidak semua CRUD harus memiliki service.

Service hanya digunakan ketika memang membantu separation of concerns.

---

# 14. Recommendation Services

Recommendation algorithm harus terisolasi dari Controller dan Vue.

Recommended conceptual structure:

```text
ShoppingRecommendationService
├── Priority First
└── Budget Optimization
```

Jika Strategy Pattern membantu readability, dapat digunakan.

Namun jangan membuat abstraction berlebihan.

---

# 15. Recommendation Rules

Implementasi recommendation harus mengikuti:

```text
BUSINESS-RULES.md
SHOPPING-ALGORITHM.md
```

Jika implementasi membutuhkan perubahan terhadap algorithm:

1. Jangan langsung mengubah behavior.
2. Jelaskan masalahnya.
3. Update documentation terlebih dahulu jika perubahan memang disetujui.
4. Update tests.
5. Baru update implementation.

---

# 16. Models

Model Laravel harus merepresentasikan domain secara jelas.

Expected entities:

```text
User
WishlistItem
Category
Budget
Purchase
```

Relationship harus didefinisikan menggunakan Eloquent.

Contoh konsep:

```text
User
 ├── hasMany WishlistItems
 ├── hasMany Purchases
 └── hasOne/hasMany Budgets
```

Exact relationship harus mengikuti `DATABASE.md`.

---

# 17. Database

Database menggunakan PostgreSQL.

Database design harus mengikuti:

```text
docs/DATABASE.md
```

Gunakan:

- foreign keys;
- indexes;
- appropriate numeric types;
- timestamps;
- constraints jika relevan.

Jangan menyimpan formatted currency sebagai string.

---

# 18. Money Handling

Nominal uang tidak boleh menggunakan floating point.

Gunakan representation yang aman untuk currency.

Contoh:

```text
1500000
```

atau PostgreSQL numeric/decimal sesuai schema.

Frontend bertanggung jawab melakukan formatting:

```text
1500000
↓
Rp1.500.000
```

---

# 19. Enum Values

Untuk field dengan pilihan terbatas, gunakan enum atau constrained values sesuai architecture project.

Contoh:

```text
Priority:
HIGH
MEDIUM
LOW

Purpose:
NEED
WANT

Status:
ACTIVE
PURCHASED
ARCHIVED
```

Jangan menggunakan string arbitrary jika business rule mengharuskan nilai tertentu.

---

# 20. Migrations

Migration harus:

- deterministic;
- reversible jika memungkinkan;
- menggunakan foreign keys;
- memiliki index yang diperlukan;
- mengikuti PostgreSQL compatibility.

Jangan mengubah migration lama yang sudah digunakan pada environment bersama tanpa alasan kuat.

Untuk perubahan schema setelah migration sudah digunakan:

> Buat migration baru.

---

# 21. Seeders

Seeder harus menyediakan data development yang realistis.

Minimal dapat mencakup:

- categories;
- demo wishlist items;
- different priorities;
- Need/Want;
- different prices;
- purchased items;
- budget scenarios.

Seeder tidak boleh digunakan sebagai source of business logic.

---

# 22. Factories

Gunakan factories untuk testing dan development data jika project membutuhkannya.

Factory harus menghasilkan data yang valid berdasarkan business rules.

Contoh:

```text
WishlistItemFactory
PurchaseFactory
CategoryFactory
```

Factory harus dapat menghasilkan berbagai combinations:

```text
HIGH + NEED
HIGH + WANT
MEDIUM + NEED
MEDIUM + WANT
LOW + NEED
LOW + WANT
```

---

# 23. API Response

API response harus konsisten.

Recommended structure:

```json
{
    "data": {},
    "message": "..."
}
```

Untuk collection:

```json
{
    "data": [],
    "meta": {}
}
```

Namun jika existing API architecture menggunakan format berbeda, pertahankan existing convention.

---

# 24. Validation Errors

Validation error harus menggunakan HTTP:

```text
422 Unprocessable Entity
```

Frontend harus dapat memetakan error ke field terkait.

Example:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

Actual response format harus mengikuti existing Laravel setup.

---

# 25. HTTP Status Codes

Gunakan status code secara semantik.

```text
200 OK
201 Created
204 No Content
401 Unauthorized
403 Forbidden
404 Not Found
422 Unprocessable Entity
500 Internal Server Error
```

Jangan menggunakan `200` untuk semua kondisi error.

---

# 26. Transactions

Gunakan database transaction untuk operation yang mengubah beberapa resource sekaligus.

Purchase merupakan contoh wajib.

Conceptual flow:

```text
BEGIN TRANSACTION

Validate item
Validate ownership
Validate budget
Create purchase
Update wishlist status

COMMIT
```

Jika salah satu operation gagal:

```text
ROLLBACK
```

---

# 27. Purchase Concurrency

Purchase harus divalidasi ulang di backend.

Recommendation sebelumnya tidak boleh dianggap sebagai guarantee.

Contoh:

```text
User sees:
Budget = Rp1.000.000

User clicks Buy

Backend:
Check current budget
Check item status
Check ownership
Perform purchase atomically
```

Frontend tidak boleh menentukan final purchase eligibility.

---

# 28. Soft Delete vs Archive

Wishlist lifecycle menggunakan:

```text
ACTIVE
PURCHASED
ARCHIVED
```

Jangan menggunakan hard delete jika dapat menyebabkan hilangnya historical information.

Archive merupakan business concept, bukan sekadar database deletion.

---

# 29. Query Scoping

Semua user-owned queries harus menggunakan authenticated user scope.

Prefer:

```php
$request->user()
    ->wishlistItems()
    ->findOrFail($id);
```

daripada:

```php
WishlistItem::findOrFail($id);
```

kemudian berharap frontend mengirim ID yang benar.

---

# 30. N+1 Prevention

Gunakan eager loading ketika relationship diperlukan.

Contoh:

```php
WishlistItem::with('category')
```

Jangan melakukan query relationship berulang di loop tanpa alasan.

Namun jangan eager-load relationship yang tidak digunakan.

---

# 31. Pagination

List dengan potensi jumlah besar harus menggunakan pagination.

Contoh:

```text
GET /api/wishlist-items?page=1
```

Dashboard recommendation dapat menggunakan limit jika hanya membutuhkan beberapa item.

---

# 32. Search and Filtering

Filtering dilakukan di backend jika dataset mulai besar.

Minimal wishlist mendukung:

```text
search
category
priority
purpose
status
sort
```

Frontend bertanggung jawab mengirim query parameter.

Backend bertanggung jawab memvalidasi dan memproses filter.

---

# 33. Vue Architecture

Vue SPA harus mengikuti existing project conventions.

Jika project menggunakan:

```text
src/
├── pages/
├── components/
├── layouts/
├── services/
├── composables/
└── types/
```

pertahankan struktur tersebut.

Jangan membuat struktur baru tanpa kebutuhan.

---

# 34. Vue Components

Component harus memiliki responsibility yang jelas.

Contoh:

```text
BudgetCard
RecommendationCard
WishlistItemCard
PriorityBadge
PurposeBadge
PurchaseDialog
BudgetOptimization
```

Jangan membuat satu component besar yang menangani seluruh Shopping page.

---

# 35. API Client

API communication harus dipusatkan pada layer service/client yang sudah digunakan project.

Contoh konseptual:

```text
wishlistService
budgetService
shoppingService
purchaseService
authService
```

Vue component sebaiknya tidak mengandung banyak raw HTTP request.

---

# 36. State Management

Gunakan state management yang sudah tersedia pada project jika ada.

Jangan menambahkan Pinia/Vuex hanya jika kebutuhan dapat ditangani dengan composables/local state.

Global state sebaiknya hanya digunakan untuk data yang benar-benar shared.

Contoh kandidat:

- authenticated user;
- authentication state;
- global UI state.

Wishlist list dan recommendation tidak harus menjadi global state jika tidak dibutuhkan.

---

# 37. Type Safety

Jika project menggunakan TypeScript:

Gunakan type/interface untuk:

- API response;
- WishlistItem;
- Category;
- Budget;
- Purchase;
- Recommendation;
- User.

Hindari:

```typescript
any;
```

kecuali benar-benar diperlukan.

---

# 38. API Types

Frontend types harus merepresentasikan API response.

Contoh conceptual type:

```typescript
interface WishlistItem {
    id: number;
    name: string;
    category: Category;
    priority: Priority;
    purpose: Purpose;
    estimatedPrice: number;
    status: WishlistStatus;
}
```

Naming harus konsisten dengan API contract.

---

# 39. Loading States

Setiap asynchronous operation harus memiliki state:

```text
idle
loading
success
error
```

Button mutation harus disabled selama request berlangsung.

---

# 40. Error Handling

Central API error handling lebih disukai jika project sudah memiliki API client abstraction.

Frontend harus menangani:

```text
401
403
404
422
500
network error
```

401 harus dapat memicu authentication state reset.

---

# 41. Testing Strategy

Testing dibagi menjadi:

```text
Unit Tests
Feature Tests
Frontend Tests
```

Prioritas utama adalah backend business logic.

---

# 42. Backend Unit Tests

Wajib test:

### Priority First

- priority ranking;
- Need vs Want;
- affordability;
- multiple items;
- insufficient budget;
- exact budget;
- zero budget;
- purchased exclusion;
- archived exclusion;
- deterministic tie-breaking.

### Budget Optimization

- single-item optimization;
- multi-item combination;
- budget constraint;
- Need/Want;
- priority;
- utilization;
- deterministic result.

---

# 43. Backend Feature Tests

Wajib test:

### Authentication

- register;
- login;
- logout;
- unauthorized access.

### Authorization

- user cannot access another user's wishlist;
- user cannot access another user's budget;
- user cannot create purchase for another user's item.

### Wishlist

- create;
- update;
- archive;
- validation.

### Budget

- create/update;
- invalid values;
- ownership.

### Purchase

- valid purchase;
- insufficient budget;
- purchased item cannot be purchased again;
- budget update;
- transaction rollback.

---

# 44. Frontend Testing

Frontend testing dapat fokus pada critical interactions:

- login;
- wishlist form;
- filters;
- recommendation rendering;
- purchase confirmation;
- error states.

Jangan mengejar 100% coverage secara artificial.

Prioritaskan behavior yang berpengaruh terhadap user.

---

# 45. Test Data

Test data harus mencakup realistic combinations.

Example:

```text
Budget = 1.000.000

A = Need / High / 500.000
B = Need / Medium / 300.000
C = Want / High / 400.000
D = Want / Low / 100.000
E = Need / High / 2.000.000
```

Test harus dapat memverifikasi bahwa recommendation sesuai dengan documented algorithm.

---

# 46. Documentation-Driven Development

Sebelum implementasi feature:

1. Baca `PRD.md`.
2. Baca `BUSINESS-RULES.md`.
3. Baca `SHOPPING-ALGORITHM.md`.
4. Baca `UI-UX.md`.
5. Baca `DATABASE.md`.

Jika behavior belum didefinisikan:

> Jangan membuat business-critical assumption secara diam-diam.

Tandai ambiguity dan minta keputusan.

---

# 47. Development Workflow

Recommended workflow:

```text
Understand
   ↓
Plan
   ↓
Implement
   ↓
Test
   ↓
Review
   ↓
Refactor
```

Untuk setiap feature:

1. Understand requirements.
2. Identify affected files.
3. Plan changes.
4. Implement smallest coherent change.
5. Run tests.
6. Fix failures.
7. Review against documentation.
8. Refactor only when useful.

---

# 48. Git Workflow

Gunakan commit yang menggambarkan perubahan secara jelas.

Contoh:

```text
feat: add wishlist item CRUD
feat: add budget management
feat: implement priority first recommendations
feat: implement budget optimization
feat: add purchase flow
test: cover recommendation edge cases
fix: prevent duplicate purchase
```

Hindari commit seperti:

```text
update
fix
changes
final
final2
```

---

# 49. Environment

Environment-specific configuration harus berada di `.env`.

Jangan hardcode:

- database credentials;
- API URLs;
- secret keys;
- authentication secrets.

`.env.example` harus diperbarui jika menambahkan environment variable baru.

---

# 50. PostgreSQL

Development dan production harus menggunakan PostgreSQL-compatible queries.

Hindari query yang hanya bekerja di MySQL jika tidak diperlukan.

Perhatikan:

- numeric types;
- UUID jika digunakan;
- case-insensitive search;
- indexing;
- PostgreSQL-specific behavior.

---

# 51. Security

Developer wajib mempertimbangkan:

- authentication;
- authorization;
- mass assignment;
- SQL injection;
- XSS;
- CSRF sesuai SPA architecture;
- validation;
- sensitive data exposure;
- insecure direct object reference;
- password security.

Jangan mengembalikan password atau secret ke frontend.

---

# 52. Mass Assignment

Model harus menggunakan `$fillable` atau `$guarded` dengan benar.

Jangan melakukan mass assignment terhadap ownership field dari frontend.

Contoh buruk:

```php
WishlistItem::create($request->validated());
```

jika request dapat menentukan:

```text
user_id
```

Ownership harus ditentukan dari authenticated user.

---

# 53. API Security Principle

Frontend adalah untrusted client.

Jangan percaya:

```text
user_id
priority
budget
ownership
purchase eligibility
```

hanya karena frontend mengirim value tersebut.

Backend harus memvalidasi semua critical state.

---

# 54. Performance

MVP tidak membutuhkan premature optimization.

Prioritas:

1. Correctness
2. Maintainability
3. Security
4. Testability
5. Performance

Optimization dilakukan jika terdapat masalah nyata.

---

# 55. Recommendation Performance

Budget Optimization dapat memiliki kombinatorial complexity.

Untuk MVP:

- gunakan algorithm sederhana dan deterministic;
- batasi input jika diperlukan;
- jangan melakukan expensive computation pada setiap unrelated request;
- gunakan caching hanya jika memang diperlukan.

Jangan menambahkan Redis atau queue hanya karena "mungkin nanti dibutuhkan".

---

# 56. Code Quality

Code harus:

- readable;
- explicit;
- maintainable;
- mengikuti framework conventions;
- memiliki naming yang jelas;
- tidak memiliki duplicated business logic.

Prefer simple code dibanding abstraction yang tidak diperlukan.

---

# 57. Naming

Gunakan naming yang konsisten.

Backend:

```text
WishlistItem
WishlistItemController
StoreWishlistItemRequest
ShoppingRecommendationService
PurchaseService
```

Frontend:

```text
WishlistPage
ShoppingPage
RecommendationCard
BudgetCard
PurchaseDialog
```

Gunakan terminology yang sama dengan documentation.

---

# 58. Do Not Over-Engineer

Jangan menambahkan:

- repository layer tanpa kebutuhan;
- generic CRUD abstraction;
- event-driven architecture;
- microservices;
- CQRS;
- message broker;
- Redis;
- background workers;

untuk kebutuhan MVP yang dapat diselesaikan dengan Laravel secara sederhana.

Architecture harus mengikuti kebutuhan produk.

---

# 59. Definition of Done

Feature dianggap selesai apabila:

- implementation mengikuti documentation;
- validation tersedia;
- authorization tersedia;
- relevant tests tersedia;
- tests passing;
- API response sesuai contract;
- frontend menangani loading/error/success;
- responsive behavior tidak rusak;
- tidak ada business logic duplicate antara backend dan frontend;
- tidak ada obvious security issue;
- code telah direview terhadap business rules.

---

# 60. AI Coding Agent Rules

Jika project dikerjakan menggunakan OpenCode atau AI coding agent:

### Before coding

AI agent wajib:

1. Inspect repository.
2. Read documentation.
3. Understand existing architecture.
4. Identify affected files.
5. Explain implementation plan.

### During coding

AI agent harus:

- membuat perubahan incremental;
- mengikuti existing conventions;
- tidak mengganti stack;
- tidak menghapus existing functionality tanpa alasan;
- tidak membuat undocumented features.

### After coding

AI agent harus:

1. Run relevant tests.
2. Check lint/type errors.
3. Check migrations.
4. Review API behavior.
5. Review authorization.
6. Review business rules.
7. Report modified files.

---

# 61. Change Management

Jika requirement berubah:

```text
Requirement change
       ↓
Update documentation
       ↓
Update tests
       ↓
Update implementation
       ↓
Verify behavior
```

Jangan mengubah implementation terlebih dahulu kemudian membiarkan documentation menjadi stale.

---

# 62. Final Development Principle

Development harus mengikuti prinsip:

> **Simple architecture, strong business rules, secure API, explainable recommendations.**

Project ini tidak membutuhkan architecture yang kompleks.

Nilai utama aplikasi berada pada:

```text
Good Data
    +
Clear Business Rules
    +
Correct Recommendation Algorithm
    +
Simple UX
```

Bukan pada jumlah abstraction atau teknologi yang digunakan.
