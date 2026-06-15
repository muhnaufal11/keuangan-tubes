# PROMPT — P3 · transaction-service (jantung integrasi + producer event)

> **Cara pakai:** buat folder `transaction-service/` (sejajar dengan auth-service & account-service di dalam `keuangan-tubes/`), buka AI agent di situ, **salin SELURUH isi file ini**. Referensi monolit ada di `../monolith`, dan dua service yang SUDAH JADI ada di `../auth-service` & `../account-service` (boleh dicontek).

---

## 0. Peranmu
Bangun **transaction-service**: ledger pemasukan/pengeluaran + dashboard. Ini **pusat integrasi**: tiap transaksi memanggil account-service untuk ubah saldo, lalu **publish event** ke Redis. DB-mu **PostgreSQL** supaya **Hasura** bisa membacanya langsung (GraphQL cara ke-2). Kamu juga **narator demo**.

## 1. KONTRAK BERSAMA (ringkas — lengkapnya di `_KONTRAK-BERSAMA.md`)
- Stack: Laravel 9 + Lighthouse + predis. Backend-only. DB sendiri (**PostgreSQL** `transaction_db`). 1 container service + 1 container DB.
- **Cara tercepat: copy folder `../auth-service`** (sudah bersih + Dockerfile CRLF-safe + Lighthouse + predis), lalu ganti isinya. Buang model/controller auth, migrasi users, seeder admin.
- Port `8003→8000`. Network `keuangan-net` (external). **Jalankan auth-service & account-service dulu**, baru punyamu.
- **Auth:** COPY `../account-service/app/Http/Middleware/RemoteAuth.php` ke service-mu, daftarkan alias `remote.auth` di `app/Http/Kernel.php`, dan di `config/lighthouse.php` ganti `AttemptAuthentication::class` → `'remote.auth'`. Ambil user via `$request->attributes->get('user_id')`.
- **Broker:** copy pola `../account-service/app/Support/Broker.php`. Producer `RPUSH queue:notifications`.

### Kontrak yang KAMU panggil (sudah LIVE di account-service)
- `GET {ACCOUNT_SERVICE_URL}/api/accounts/{id}/balance`
- `POST {ACCOUNT_SERVICE_URL}/api/internal/accounts/{id}/adjust-balance` body `{amount, direction:"credit"|"debit", ref}` → `200 {rekening_id,saldo_baru}` | `409` saldo kurang. (Idempotent by `ref` — jadi aman kalau retry pakai `ref` sama.)
- Teruskan header `Authorization: Bearer <token>` user saat memanggilnya.

### Event yang KAMU publish
`transaction.created` = `{event,user_id,type:"income"|"expense",rekening_id,amount,kategori,deskripsi,transaction_id,tanggal,occurred_at}`

### ENV
`AUTH_SERVICE_URL=http://auth-service:8000`, `ACCOUNT_SERVICE_URL=http://account-service:8000`, `REDIS_HOST=redis`, `NOTIFICATIONS_QUEUE=queue:notifications`.

## 2. TUGASMU
**Reuse dari monolit (`../monolith`):** `app/Models/Pemasukan.php`, `Pengeluaran.php`; `app/Http/Controllers/Api/TransaksiController.php` (sudah ada DB::transaction+lockForUpdate — pelajari, tapi **logika saldo dipindah ke account-service**), `Api/DashboardController.php`. Skema pemasukan/pengeluaran ada di `database/migrations/2024_01_01_000001_create_keuangan_tables.php`.

**DB:** PostgreSQL. Set `DB_CONNECTION=pgsql`, host `transaction-db`, db `transaction_db`, user/pass `zenith`. Dockerfile sudah punya `pdo_pgsql` (warisan dari auth-service). Migrasi: tabel `pemasukan`, `pengeluaran` (buang FK lintas-DB → `user_id`,`rekening_id` integer index).

**Endpoint REST (`routes/api.php`, semua di belakang `remote.auth`):**
- `POST /api/transactions` `{type:"income"|"expense", rekening_id, kategori, amount, deskripsi, tanggal}`. **Alur:**
  1. `remote.auth` → `user_id`.
  2. `ref = (string) Str::uuid()`. Panggil account-service `adjust-balance` (`income`→`credit`, `expense`→`debit`) dengan `ref` + Bearer token user.
  3. `409` → balikan error, **jangan** tulis ledger.
  4. `200` → tulis baris ledger di `transaction_db`.
  5. **Kompensasi**: kalau tulis ledger gagal setelah saldo berubah → panggil `adjust-balance` arah kebalikan (ref baru) untuk balikin saldo.
  6. Sukses → `RPUSH transaction.created`.
- `GET /api/transactions` (list, scope user, filter tanggal), `GET /api/transactions/{id}`, `DELETE /{id}` (+ kompensasi saldo).
- `GET /api/dashboard/summary` → agregasi income/expense bulan ini (DB sendiri) + total saldo (`GET {ACCOUNT_SERVICE_URL}/api/accounts`).

**GraphQL DUA cara (nilai 20):**
- **(manual)** Lighthouse `/graphql`: `transactions`, `summary`, mutation `createTransaction`. (Resolver baca `user_id` dari `$context->request()->attributes`, lihat contoh di `../account-service/app/GraphQL/`.)
- **(Hasura)** DB Postgres → container Hasura konek ke `transaction_db`. Demo: query data yang sama lewat Lighthouse DAN Hasura console = "dua cara". (Hasura distandup P2/kamu; pakai kredensial `zenith/zenith`.)

**Docker:** copy Dockerfile + `docker-compose.yml` dari account-service, ganti: service `transaction-service` (`8003`), DB `transaction-db` pakai `image: postgres:16` (env `POSTGRES_USER=zenith POSTGRES_PASSWORD=zenith POSTGRES_DB=transaction_db`, port `5432`), `DB_CONNECTION=pgsql`. Network `keuangan-net` external.

## 3. Definition of Done (tes beneran via Docker)
- [ ] `docker compose up -d --build` → transaction-service `:8003` + transaction-db (Postgres) sehat.
- [ ] `POST /api/transactions` (income) → saldo di account-service nambah + ledger tertulis + `transaction.created` masuk Redis (`docker exec redis redis-cli LRANGE queue:notifications 0 -1`).
- [ ] income dgn rekening saldo kurang (expense) → 409, ledger tidak ketulis.
- [ ] `dashboard/summary` jalan.
- [ ] GraphQL manual jalan; Hasura bisa baca `transaction_db`.
- [ ] Postman + README + (commit).

## 4. Jebakan (WAJIB baca)
- **JANGAN klaim `DB::transaction` lokal melindungi saldo remote** — itu salah. Pakai pola: panggil account-service dulu → baru tulis ledger → kompensasi kalau gagal. Jelaskan di laporan.
- **Idempotensi:** kirim `ref` unik; saat retry pakai `ref` sama biar saldo tak dobel.
- **CRLF (Windows):** `docker-entrypoint.sh` HARUS LF + `*.sh text eol=lf` di `.gitattributes` + `RUN sed -i 's/\r$//' docker-entrypoint.sh` di Dockerfile. Kalau CRLF → `exec docker-entrypoint.sh: no such file or directory` (ini sempat bikin auth-service crash).
- **Postgres**, bukan MySQL — pastikan migrasi & tipe kompatibel; `pdo_pgsql` ada di image.
- Redis pakai **queue (RPUSH)**, bukan `PUBLISH`.
