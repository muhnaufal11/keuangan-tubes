# account-service (P2)

Microservice **akun/rekening + kategori + transfer** untuk sistem keuangan (Tugas Besar IAE).
Backend-only (Laravel 9). Database sendiri: **`account_db` (MySQL 8)**. Service ini adalah **pemilik saldo yang otoritatif**.

## Peran dalam sistem
- Menyimpan rekening, saldo, kategori, dan transfer milik tiap user.
- Tidak punya tabel `users`. Token Bearer divalidasi ke **auth-service** lewat middleware `remote.auth` (`GET {AUTH_SERVICE_URL}/api/auth/validate-token`, hasil di-cache 60 dtk).
- Menyediakan endpoint internal `adjust-balance` (lock + idempotent) yang dipanggil **transaction-service (P3)**.
- Mem-*publish* event `transfer.completed` ke Redis (queue `queue:notifications`) yang dikonsumsi **debt-notify-worker (P4)**.

## Endpoint REST (semua butuh header `Authorization: Bearer <token>`)
| Method | Path | Keterangan |
|---|---|---|
| GET | `/api/health` | health check (tanpa auth) |
| GET | `/api/accounts` | daftar rekening user |
| POST | `/api/accounts` | buat rekening `{nama_rekening, tipe, saldo, no_rekening?, minimum_saldo?}` |
| GET | `/api/accounts/{id}` | detail rekening |
| PUT | `/api/accounts/{id}` | update (saldo TIDAK bisa diubah di sini) |
| DELETE | `/api/accounts/{id}` | hapus rekening |
| GET | `/api/accounts/{id}/balance` | saldo rekening |
| POST | `/api/internal/accounts/{id}/adjust-balance` | **internal** `{amount, direction:"credit"\|"debit", ref}` — lock + idempotent (409 jika saldo kurang) |
| GET | `/api/categories?type=income\|expense` | daftar kategori user |
| POST | `/api/categories` | buat kategori `{nama_kategori, type}` |
| GET | `/api/categories/defaults` | kategori default sistem |
| POST | `/api/categories/sync-defaults` | salin default ke kategori user |
| GET | `/api/transfers` | riwayat transfer |
| POST | `/api/transfers` | transfer `{rekening_sumber_id, rekening_tujuan_id, jumlah, deskripsi?, tanggal?}` — atomic + publish `transfer.completed` |

## GraphQL (manual / Lighthouse) — `POST /graphql`
```graphql
query  { accounts { id nama_rekening saldo tipe } }
mutation { createAccount(nama_rekening:"BCA", tipe:"BANK", saldo:100000) { id saldo } }
```
> Endpoint `/graphql` juga butuh header `Authorization: Bearer <token>` (middleware `remote.auth`).

## Menjalankan (Docker)
account-service memakai network & Redis milik auth-service, jadi **jalankan auth-service dulu**:
```bash
# 1) di folder auth-service (membuat network 'keuangan-net' + redis + auth-service)
cd ../auth-service && docker compose up -d --build
# 2) lalu account-service
cd ../account-service && docker compose up -d --build
```
- account-service: http://localhost:8002
- account-db (MySQL): host port 33062

## Catatan desain
- **Tidak ada FK lintas-DB.** `user_id` & `rekening_id` integer ber-index; integritas dijaga via validasi REST.
- `adjust-balance` idempotent berdasarkan kolom unik `ref` (tabel `balance_adjustments`) supaya retry dari transaction-service tidak menggandakan saldo.
- Transfer ditangani di sini (bukan dipecah lewat HTTP) agar **atomic** dalam satu `DB::transaction` + `lockForUpdate`.
