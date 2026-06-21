# Tutorial Uji Sistem via Postman

Panduan menguji seluruh sistem keuangan microservices (auth, account, transaction, debt-notify + GraphQL + Hasura) pakai **Postman**.

## 0. Prasyarat
1. **Postman** terpasang (download gratis di postman.com/downloads) — boleh juga ekstensi **Thunder Client** di VS Code.
2. Semua service **jalan** (cek: `docker ps` harus ada auth/account/transaction/debt + worker + redis + db). Kalau belum:
   ```
   cd auth-service && docker compose up -d
   cd ../account-service && docker compose up -d
   cd ../transaction-service && docker compose up -d
   cd ../debt-notify-service && docker compose up -d
   ```
3. (Opsional) **Hasura admin secret** kalau mau uji Hasura Cloud.

## 1. Import Collection
1. Buka Postman → tombol **Import** (kiri atas).
2. Pilih file: **`docs/postman/Keuangan-Microservices.postman_collection.json`**.
3. Muncul collection **"Keuangan Microservices (IAE)"** dengan 4 folder.

## 2. Isi Variable (sekali saja)
1. Klik kanan collection → **Edit** → tab **Variables**.
2. URL sudah terisi (`auth_url=http://localhost:8001`, dst). Biarkan kalau port-mu sama.
3. Khusus uji Hasura: isi **`hasura_admin_secret`** dengan admin secret project Hasura Cloud-mu (ada di Hasura → Project Settings → Env Vars → `HASURA_GRAPHQL_ADMIN_SECRET`).
4. **Save** (Ctrl+S).

> Variable lain (`token`, `rekening_id`, dll) **terisi otomatis** saat kamu menjalankan request tertentu — nggak usah diisi manual.

## 3. Cara pakai — jalankan BERURUTAN
Urutannya penting karena token & id saling nyambung. Klik tiap request → tombol **Send** (biru).

### Folder `0. Auth Service`
1. **Register (simpan token)** → Send. Status `201`. Token otomatis tersimpan ke `{{token}}`.
   *(atau **Login** kalau sudah punya akun — ganti email/password di tab Body dulu.)*
2. **Login ADMIN** → Send. Menyimpan `{{admin_token}}` (untuk fitur admin).
3. Coba: **Get Profil**, **Validate Token**, **Password - Get Question/Reset**, **Admin - List Users**.

### Folder `1. Account Service`
4. **Buat Rekening** → Send (`201`). `{{rekening_id}}` tersimpan.
5. **Buat Rekening ke-2** → Send. `{{rekening_id_2}}` tersimpan (untuk transfer).
6. Coba: **List/Detail/Update/Cek Saldo**, **Adjust Balance CREDIT**, **Adjust Balance DEBIT (409)**, **Kategori**, **Transfer**, **GraphQL accounts**.

### Folder `2. Transaction Service`
7. **Catat INCOME** → Send (`201`) — saldo rekening otomatis naik (lewat account-service) + kirim event.
8. **Catat EXPENSE**, **List Transaksi**, **Dashboard Summary**.
9. **GraphQL manual (Lighthouse)** + **GraphQL via HASURA CLOUD** (butuh `hasura_admin_secret`).

### Folder `3. Debt & Notify Service`
10. **Buat Utang** → Send. `{{debt_id}}` tersimpan.
11. **Bayar Utang** → Send (kirim event `debt.payment_made`).
12. **Notifikasi - List** → Send → muncul notifikasi yang **dibuat otomatis oleh worker** dari event (Pemasukan/Transfer/Pembayaran Utang). *(beri jeda ~5 detik setelah transaksi/bayar, lalu Send lagi.)*
13. Coba: **Mark Dibaca**, **Hapus**, **Tips**, **Admin Broadcast**, **GraphQL**.

## 4. (Cepat) Jalankan semua sekaligus — Collection Runner
1. Klik kanan collection → **Run collection**.
2. Pilih urutan default (0→1→2→3) → **Run Keuangan Microservices**.
3. Lihat hasil semua request (hijau = sukses). *Catatan: request "saldo kurang (409)" memang sengaja gagal 409 — itu benar.*

## 5. Apa yang dibuktikan (poin ke dosen)
- **RESTful**: semua endpoint CRUD tiap service.
- **Komunikasi antar service**: transaksi di transaction-service mengubah saldo di account-service; semua service validasi token ke auth-service.
- **Message broker**: bayar utang / transaksi → muncul **notifikasi otomatis** (worker consumer).
- **GraphQL 2 cara**: manual (Lighthouse, di tiap service `/graphql`) **dan Hasura Cloud** (`{{hasura_url}}/v1/graphql`, hosted di web).
- **Database terpisah & Docker per-service**: tiap service port berbeda, DB sendiri.

## 6. Troubleshooting
| Masalah | Solusi |
|---|---|
| `401 Unauthenticated` | Jalankan **Register/Login** dulu (mengisi `{{token}}`). Untuk endpoint admin pakai **Login ADMIN**. |
| `Could not get response` | Service belum jalan → `docker ps`, lalu `docker compose up -d`. |
| Hasura `access denied` | Isi variable `hasura_admin_secret` (Collection → Variables). |
| `404` saat transfer/transaksi | Jalankan **Buat Rekening** dulu agar `{{rekening_id}}` terisi. |
| Token tidak tersimpan | Pastikan request **Register/Login** sukses (cek tab **Console** Postman: "token saved"). |
