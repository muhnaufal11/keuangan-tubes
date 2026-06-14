# KONTRAK BERSAMA (WAJIB dipatuhi semua service)

> Blok ini sudah otomatis tertempel di tiap file prompt P1–P4. Jangan diubah sepihak —
> kalau perlu ganti port/format event, sepakati dulu bertfour lalu update semua file.

**Proyek:** Tugas Besar *Integrasi Aplikasi Enterprise* — sistem keuangan berbasis **MICROSERVICES**, **BACKEND ONLY** (tanpa frontend). Tim 4 orang, tiap orang 1 service dengan **database terpisah**.

**Sumber kode untuk di-reuse:** monolit Laravel 9 *"Zenith / keuangan-app"*. **Salin** model & controller yang relevan dari sana — **jangan tulis ulang dari nol** — tapi **buang semua view Blade & route web**, sisakan logika domain + controller `Api/*`. (Clone repo monolit ke folder referensi `../monolith` lalu salin file yang disebut di tugasmu.)

**Stack wajib seragam:** Laravel 9 (PHP 8.3) + Sanctum. GraphQL manual pakai `nuwave/lighthouse`. Broker pakai **Redis** (`predis/predis`). Docker: **tiap service 1 container, tiap DB 1 container**.

### Topologi & port (host : container)
| service | port | DB container | engine | nama db |
|---|---|---|---|---|
| auth-service | 8001→8000 | auth-db | MySQL 8 | `auth_db` |
| account-service | 8002→8000 | account-db | MySQL 8 | `account_db` |
| transaction-service | 8003→8000 | transaction-db | **PostgreSQL 16** | `transaction_db` |
| debt-notify-service | 8004→8000 | debt-db | MySQL 8 | `debt_db` |
| debt-notify-worker | (tanpa port) | — | — | consumer Redis |
| redis | 6379 | — | — | — |
| hasura | 8080 | (baca transaction-db) | — | — |

- Semua di docker network: **`keuangan-net`**. URL antar service di dalam network: `http://<service>:8000`.
- Tiap Laravel jalan dengan: `php artisan serve --host=0.0.0.0 --port=8000`. Entrypoint container: `php artisan migrate --force` lalu `serve`.
- Kredensial Postgres (transaction-db): user `zenith` / pass `zenith` / db `transaction_db` / port `5432`.

### Autentikasi antar service
- **Hanya auth-service** yang punya tabel `users` & menerbitkan token Sanctum.
- Service lain **tidak** share tabel users. Mereka validasi token via REST ke auth-service:
  `GET {AUTH_SERVICE_URL}/api/auth/validate-token` (header `Authorization: Bearer <token>`)
  → `200 {id,email,name,tipe_akun,is_admin}` | `401` kalau invalid.
- Tiap service non-auth bikin middleware **`remote.auth`** yang memanggil endpoint itu, **cache hasilnya 60 detik** (`Cache::remember` key = `sha256(token)`), lalu simpan `user_id` ke request. Semua data di-scope ke `user_id`.

### Kontrak REST yang dipanggil lintas service
1. **auth-service** → `GET /api/auth/validate-token` (lihat di atas).
2. **account-service** (dipanggil transaction-service) — endpoint internal:
   `POST /api/internal/accounts/{id}/adjust-balance`
   body: `{ "amount": number, "direction": "credit"|"debit", "ref": "string-idempotency-key" }`
   - **WAJIB** pakai `DB::transaction` + `lockForUpdate`, validasi saldo cukup untuk `debit`, **idempotent** berdasarkan `ref` (simpan ref yang sudah diproses, kalau ref sama → kembalikan hasil lama, jangan dobel).
   - return `200 {rekening_id, saldo_baru}` | `409` saldo kurang | `404`.
   - **account-service = PEMILIK SALDO yang otoritatif** (ini yang menyelesaikan masalah atomicity lintas service).

### Message Broker (Redis sebagai broker, pola QUEUE = at-least-once, BUKAN pub/sub)
- Producer push event JSON: `RPUSH queue:notifications "<json>"`.
- Consumer (debt-notify-worker): loop `BLPOP queue:notifications 5` lalu proses tiap event.
- **Format event** (selalu sertakan `event`, `user_id`, `occurred_at` ISO-8601):
  - `transaction.created` : `{event,user_id,type:"income"|"expense",rekening_id,amount,kategori,deskripsi,transaction_id,tanggal,occurred_at}`
  - `transfer.completed`  : `{event,user_id,from_rekening_id,to_rekening_id,amount,transfer_id,occurred_at}`
  - `debt.payment_made`   : `{event,user_id,utang_id,amount,sisa,occurred_at}`
  - *(opsional)* `user.registered` : `{event,user_id,email}` untuk seed kategori default.
- Aktifkan Redis `appendonly yes` untuk demo. **Jangan** pakai pub/sub `SUBSCRIBE` (event hilang kalau worker mati).

### ENV penting (samakan namanya di semua service)
```
AUTH_SERVICE_URL=http://auth-service:8000
ACCOUNT_SERVICE_URL=http://account-service:8000
REDIS_HOST=redis
REDIS_PORT=6379
NOTIFICATIONS_QUEUE=queue:notifications
```

### Aturan migrasi (PENTING — sering jadi sumber error)
Di monolit, FK lintas tabel ada di `database/migrations/2024_01_01_000001_create_keuangan_tables.php` (membuat `rekening + kategori + pemasukan + pengeluaran + transfer + utang` sekaligus dengan FK). Karena DB sekarang **terpisah per service**, **HAPUS semua foreign key lintas-DB**: `user_id` di semua tabel, dan `rekening_id` di `pemasukan/pengeluaran/transfer`. Simpan kolomnya sebagai integer biasa **ber-index** (`->index()`), integritas dijaga lewat validasi REST. Tulis keputusan ini di laporan.

### Deliverable tiap orang
(a) repo GitHub service-nya · (b) `Dockerfile` + blok untuk `docker-compose.yml` · (c) koleksi **Postman** · (d) skema **GraphQL** (`graphql/schema.graphql`) · (e) `README.md` cara menjalankan.
