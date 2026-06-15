# KONTRAK BERSAMA (WAJIB dipatuhi semua service)

**Proyek:** Tugas Besar *Integrasi Aplikasi Enterprise* — sistem keuangan **MICROSERVICES**, **BACKEND ONLY**. 4 orang, 4 service, **database terpisah**.

**Stack seragam:** Laravel 9 (PHP 8.3) + Sanctum, GraphQL manual `nuwave/lighthouse`, broker **Redis** (`predis/predis`). Tiap service & tiap DB = **container terpisah**.

**Cara tercepat bikin service baru:** copy folder `auth-service` (sudah bersih & teruji), lalu ganti model/controller/migrasi/route-nya. (P2 dibuat persis begini.)

### Topologi & port (host : container) — network `keuangan-net`
| service | port | DB | engine | db name | status |
|---|---|---|---|---|---|
| auth-service | 8001→8000 | auth-db | MySQL 8 | `auth_db` | ✅ jadi |
| account-service | 8002→8000 | account-db | MySQL 8 | `account_db` | ✅ jadi |
| transaction-service | 8003→8000 | transaction-db | **PostgreSQL 16** | `transaction_db` | ⏳ |
| debt-notify-service | 8004→8000 | debt-db | MySQL 8 | `debt_db` | ⏳ |
| debt-notify-worker | — | (pakai debt-db) | — | consumer Redis | ⏳ |
| redis | 6379 | — | — | (dibuat auth-service) | ✅ |
| hasura | 8080 | baca transaction-db | — | — | ⏳ |

URL antar service di dalam network: `http://<service>:8000`. Tiap Laravel jalan `php artisan serve --host=0.0.0.0 --port=8000` (entrypoint: migrate lalu serve).
Postgres (transaction-db, milik P3): user `zenith` / pass `zenith` / db `transaction_db` / port `5432`.

### Docker compose (pola yang dipakai)
Tiap service punya `docker-compose.yml` sendiri yang memakai network bersama:
```yaml
networks:
  keuangan-net:
    external: true
    name: keuangan-net
```
Redis & network dibuat oleh **auth-service**, jadi urutan jalan: **auth-service dulu**, baru service lain. (account-service contohnya sudah begini.)

### Autentikasi antar service
- Hanya **auth-service** yang punya tabel `users` & menerbitkan token Sanctum.
- Service lain validasi token via `GET {AUTH_SERVICE_URL}/api/auth/validate-token` (Bearer) → `200 {id,email,name,tipe_akun,is_admin}` | `401`.
- **Middleware `remote.auth` SUDAH JADI** di `account-service/app/Http/Middleware/RemoteAuth.php` (panggil validate-token + cache 60 dtk + set `user_id` ke request). **P3 & P4 tinggal COPY file itu** + daftarkan alias `remote.auth` di `app/Http/Kernel.php`. Untuk GraphQL: di `config/lighthouse.php`, ganti `AttemptAuthentication::class` jadi `'remote.auth'`.

### Kontrak REST lintas service (SUDAH LIVE)
1. **auth-service**: `GET /api/auth/validate-token` (lihat atas).
2. **account-service** (dipanggil transaction-service): **sudah jalan & teruji**
   `POST /api/internal/accounts/{id}/adjust-balance`
   body: `{ "amount": number, "direction": "credit"|"debit", "ref": "idempotency-key" }`
   → `200 {rekening_id, saldo_baru}` | `409` saldo kurang | `404`. (lockForUpdate + idempotent by `ref`.)
   Saldo cek: `GET /api/accounts/{id}/balance`.

### Message Broker (Redis, pola QUEUE = at-least-once, BUKAN pub/sub)
- Producer: `RPUSH queue:notifications "<json>"`. Consumer (debt-notify-worker): `BLPOP queue:notifications 5`.
- Format event (selalu ada `event`, `user_id`, `occurred_at`):
  - `transaction.created` : `{event,user_id,type:"income"|"expense",rekening_id,amount,kategori,deskripsi,transaction_id,tanggal,occurred_at}` — **publisher: P3**
  - `transfer.completed`  : `{event,user_id,from_rekening_id,to_rekening_id,amount,transfer_id,occurred_at}` — **publisher: account-service (sudah jalan)**
  - `debt.payment_made`   : `{event,user_id,utang_id,amount,sisa,occurred_at}` — **publisher: P4**
  - `user.registered`     : `{event,user_id,email,occurred_at}` — **publisher: auth-service (sudah jalan)**
- Helper publish contoh: `account-service/app/Support/Broker.php` (boleh copy).

### ENV penting (samakan namanya)
```
AUTH_SERVICE_URL=http://auth-service:8000
ACCOUNT_SERVICE_URL=http://account-service:8000
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CLIENT=predis
NOTIFICATIONS_QUEUE=queue:notifications
```

### Aturan migrasi
Hapus semua FK lintas-DB. `user_id` & `rekening_id` jadi integer **ber-index** (`->index()`); integritas via validasi REST. Tulis di laporan sebagai keputusan desain.

### ⚠️ Windows: file `.sh` HARUS LF (sudah terbukti jadi bug)
File shell CRLF bikin container **crash-loop**: `exec docker-entrypoint.sh: no such file or directory`. Cegah dengan **ketiga**-nya:
1. `.gitattributes`: `*.sh text eol=lf`
2. Dockerfile sebelum chmod: `RUN sed -i 's/\r$//' docker-entrypoint.sh && chmod +x docker-entrypoint.sh`
3. Karena compose me-mount `.:/var/www/html` (file host menimpa image), **simpan `docker-entrypoint.sh` sebagai LF** (VS Code: klik "CRLF" pojok kanan bawah → "LF").

### Deliverable tiap orang
repo/folder service · Dockerfile + blok compose · koleksi Postman · skema GraphQL · README.
