# PROMPT — Person 1 · auth-service (+ koordinator Docker)

> **Cara pakai:** buat folder kosong baru `auth-service/`, buka AI agent (Claude Code/dll) di situ, lalu **salin SELURUH isi file ini** sebagai prompt pertama. Sediakan juga repo monolit di `../monolith` sebagai referensi salin-kode.

---

## 0. Peranmu
Kamu membangun **auth-service**: satu-satunya sumber identitas (users) di sistem. Service lain memvalidasi token ke kamu. Kamu **juga koordinator infra**: kamu yang membuat `Dockerfile` standar + skeleton `docker-compose.yml` + network `keuangan-net` yang dipakai seluruh tim.

---

## 1. KONTRAK BERSAMA (berlaku untuk semua service — jangan diubah sepihak)

- Proyek: Tugas Besar *Integrasi Aplikasi Enterprise* — sistem keuangan **microservices, BACKEND ONLY**. 4 orang, 4 service, **DB terpisah**.
- Stack seragam: **Laravel 9 (PHP 8.3) + Sanctum**, GraphQL manual `nuwave/lighthouse`, broker **Redis** (`predis/predis`). Tiap service & tiap DB = **container terpisah**.
- Reuse kode dari monolit Laravel 9 *keuangan-app* (salin model/controller `Api/*`, buang Blade & route web).

**Topologi & port (host:container)** — network `keuangan-net`, URL antar service `http://<service>:8000`:
| service | port | DB | engine | db name |
|---|---|---|---|---|
| auth-service | 8001→8000 | auth-db | MySQL 8 | `auth_db` |
| account-service | 8002→8000 | account-db | MySQL 8 | `account_db` |
| transaction-service | 8003→8000 | transaction-db | PostgreSQL 16 | `transaction_db` |
| debt-notify-service | 8004→8000 | debt-db | MySQL 8 | `debt_db` |
| debt-notify-worker | — | — | — | consumer Redis |
| redis | 6379 | — | — | — |
| hasura | 8080 | baca transaction-db | — | — |

**Auth antar service:** hanya auth-service punya `users` & terbitkan token Sanctum. Service lain validasi via `GET {AUTH_SERVICE_URL}/api/auth/validate-token` (Bearer) → `200 {id,email,name,tipe_akun,is_admin}` | `401`.

**Kontrak REST internal account-service** (FYI): `POST /api/internal/accounts/{id}/adjust-balance {amount,direction,ref}` (lockForUpdate + idempotent).

**Broker (Redis, pola QUEUE at-least-once, BUKAN pub/sub):** producer `RPUSH queue:notifications "<json>"`; consumer `BLPOP queue:notifications 5`. Event format menyertakan `event,user_id,occurred_at`. Event kamu: **`user.registered` = `{event,user_id,email}`** (opsional, untuk seed kategori di account-service).

**Aturan migrasi:** hapus FK lintas-DB; simpan kolom id sebagai integer ber-index. (auth_db isinya `users` saja jadi minim FK.)

---

## 2. TUGASMU: auth-service

**Reuse dari monolit:** `app/Models/User.php`; `app/Http/Controllers/Api/AuthController.php`, `Api/PasswordResetController.php`, `Api/Admin/UserController.php`, `Api/Admin/SystemController.php`; logika Google OAuth dari web `AuthController` (opsional). Migrasi: `2014_10_12_000000_create_users_table`, `2019_12_14_000001_create_personal_access_tokens_table`, `2014_10_12_100000_create_password_resets_table`, `2019_08_19_000000_create_failed_jobs_table`, `2024_01_01_000000_add_fields_to_users_table`, `2025_12_26_000000_add_admin_fields_to_users_table`, `2025_12_26_060000_add_last_login_at_to_users_table`, dan migrasi nullable name/email.

**Endpoint REST (`routes/api.php`):**
- `POST /api/auth/register` `{name,email,password}` → `{token,user}` + **publish `user.registered`** ke Redis.
- `POST /api/auth/login` `{email,password}` → `{token,user}` (update `last_login_at`).
- `POST /api/auth/logout` (Bearer) → `204`.
- `GET /api/auth/user` (Bearer) → user.
- `GET /api/auth/validate-token` (Bearer) → `200 {id,email,name,tipe_akun,is_admin}` | `401`. **← endpoint paling penting, dipakai semua service.**
- Admin: `GET /api/auth/admin/users` (list, butuh is_admin), `PATCH /api/auth/admin/users/{id}/ban`.
- *(Opsional)* reset password via security question (reuse `PasswordResetController`).

**GraphQL manual (Lighthouse, `/graphql`):** type `User`; query `me`; mutation `register`, `login`, `logout`. Pasang `nuwave/lighthouse`, taruh `graphql/schema.graphql`.

**Tugas koordinator infra (punyamu):**
- Buat **`Dockerfile` standar** (PHP 8.3-fpm + ekstensi: `pdo_mysql`, `pdo_pgsql`, `mbstring`, `bcmath`, `gd`; install composer; `composer install --no-dev --optimize-autoloader` **di dalam image**; entrypoint: `migrate --force` lalu `serve --host=0.0.0.0 --port=8000`). Bagikan template ini ke tim.
- Buat **`docker-compose.yml` root** (skeleton) berisi network `keuangan-net`, service `redis` (`redis:7-alpine` + `--appendonly yes`), `auth-service` + `auth-db`. Sediakan placeholder/komentar agar P2/P3/P4 menambahkan blok service+db mereka.

---

## 3. Langkah kerja
1. `composer create-project laravel/laravel . "^9.0"` (atau salin kerangka monolit lalu rapikan). Install `laravel/sanctum`, `nuwave/lighthouse`, `predis/predis`.
2. Salin `User` model + Api controller auth dari monolit; **hapus** semua Blade/route web/controller non-auth.
3. Pindahkan hanya migrasi terkait users; jalankan `php artisan migrate` ke `auth_db` (MySQL lokal dulu, lalu di container).
4. Wire `routes/api.php` sesuai daftar endpoint. Pastikan `validate-token` mengembalikan field persis sesuai kontrak.
5. Tambah helper publish Redis (`RPUSH`) + panggil saat register.
6. Pasang Lighthouse, tulis `schema.graphql`, tes `POST /graphql`.
7. Tulis `Dockerfile` + entrypoint; bangun `docker-compose.yml` root (network + redis + auth-service + auth-db). Verifikasi `docker compose up -d --build` → auth-service sehat di `:8001`.
8. Buat koleksi Postman (`auth.postman_collection.json`), README, push ke GitHub.

## 4. Definition of Done
- [ ] `docker compose up` → auth-service jalan di `:8001`, auth-db sehat, migrate sukses.
- [ ] `register/login/logout/user/validate-token` jalan & teruji di Postman.
- [ ] `validate-token` mengembalikan `{id,email,name,tipe_akun,is_admin}`.
- [ ] `POST /graphql` melayani `me/login/register`.
- [ ] `user.registered` ter-publish ke `queue:...` saat register.
- [ ] Dockerfile standar + compose skeleton + network siap dipakai tim.
- [ ] Postman + README + repo GitHub.

## 5. Jebakan yang wajib dihindari
- **`validate-token` harus ringan & stabil** — ini dipanggil tiap request service lain. Jangan ada query berat.
- **Google OAuth = alur redirect web**, agak bentrok dengan "backend only". Boleh **di-skip** untuk fokus rubrik; kalau dipakai, siapkan penjelasan saat demo.
- Jangan bocorkan tabel `users` ke service lain — mereka **hanya** boleh tahu via `validate-token`.
- Pakai Redis **queue (RPUSH)**, bukan `PUBLISH`.
