# P2 · account-service — ✅ SELESAI (referensi)

> Status: **selesai & teruji** jalan di Docker (`:8002`). Kode ada di folder `account-service/`.
> Pemilik saldo otoritatif. DB `account_db` (MySQL, host port 33062).

## Endpoint (terbukti jalan, semua butuh Bearer token)
- `GET /api/health` (tanpa auth)
- `GET/POST /api/accounts`, `GET/PUT/DELETE /api/accounts/{id}`, `GET /api/accounts/{id}/balance`
- `POST /api/internal/accounts/{id}/adjust-balance` `{amount,direction,ref}` — lock + idempotent (dipanggil P3)
- `GET/POST /api/categories`, `GET /api/categories/defaults`, `POST /api/categories/sync-defaults`
- `GET/POST /api/transfers` — atomic + publish `transfer.completed`
- GraphQL `/graphql`: `accounts`, `account(id)`, `createAccount`

## Yang bisa di-reuse service lain
- **`app/Http/Middleware/RemoteAuth.php`** ← copy ini untuk P3/P4 (validasi token ke auth-service + cache).
- **`app/Support/Broker.php`** ← helper publish Redis.
- Pola `docker-compose.yml` (network `keuangan-net` external) & Dockerfile (CRLF-safe).

## Jalan
```bash
cd auth-service && docker compose up -d --build      # dulu
cd ../account-service && docker compose up -d --build
```

## Catatan desain
Tanpa FK lintas-DB. `adjust-balance` idempotent by `ref` (tabel `balance_adjustments`). Transfer atomic (2 rekening dalam 1 DB::transaction + lockForUpdate). Hasura ditunda (butuh DB Postgres P3).
