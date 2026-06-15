# PROMPT — P4 · debt-notify-service (+ worker consumer + diagram laporan)

> **Cara pakai:** buat folder `debt-notify-service/` (sejajar dengan service lain di `keuangan-tubes/`), buka AI agent di situ, **salin SELURUH isi file ini**. Referensi monolit di `../monolith`; service yang sudah jadi di `../auth-service` & `../account-service` (boleh dicontek).

---

## 0. Peranmu
Bangun **debt-notify-service**: utang/piutang + notifikasi. Yang khas: kamu jalankan **worker consumer** (container terpisah) yang membaca event dari Redis dan mengubahnya jadi notifikasi — **ini bukti message broker async bekerja**. Kamu juga **pemilik diagram arsitektur + integration-flow** untuk laporan PDF (basisnya ada di `../docs/arsitektur/`).

## 1. KONTRAK BERSAMA (ringkas — lengkapnya di `_KONTRAK-BERSAMA.md`)
- Stack: Laravel 9 + Lighthouse + predis. Backend-only. DB sendiri (**MySQL** `debt_db`). Container service + container DB + **container worker**.
- **Cara tercepat: copy folder `../account-service`** (sudah pakai `remote.auth` + Broker helper + Dockerfile CRLF-safe + Lighthouse), lalu ganti model/controller/migrasi jadi utang+notifikasi.
- Port `8004→8000`. Network `keuangan-net` (external). **Jalankan auth-service dulu** (bikin network+redis).
- **Auth:** `remote.auth` sudah ikut tercopy dari account-service. Pastikan alias terdaftar di `Kernel.php` & Lighthouse pakai `'remote.auth'`. Ambil user via `$request->attributes->get('user_id')`.

### Broker (Redis, pola QUEUE = at-least-once, BUKAN pub/sub)
- **Consumer (inti tugasmu):** Artisan command `php artisan broker:listen` → loop `Redis::command('BLPOP',[env('NOTIFICATIONS_QUEUE'),5])` → buat baris `Notification` per event. Jalan sebagai **container kedua** `debt-notify-worker` (image sama, `command: php artisan broker:listen`).
- **Producer (punyamu):** saat bayar utang → `RPUSH` event `debt.payment_made = {event,user_id,utang_id,amount,sisa,occurred_at}` (pakai pola `../account-service/app/Support/Broker.php`).
- **Event yang KAMU konsumsi** (sebagian SUDAH ada di queue):
  - `transaction.created {type,rekening_id,amount,kategori,...}` (dari P3) → notif "Pemasukan/Pengeluaran Rp… tercatat".
  - `transfer.completed {from_rekening_id,to_rekening_id,amount,...}` (**sudah dipublish account-service**) → notif "Transfer Rp… berhasil".
  - `debt.payment_made {utang_id,amount,sisa}` (punyamu) → notif "Pembayaran utang Rp…, sisa Rp…".

### ENV
`AUTH_SERVICE_URL=http://auth-service:8000`, `REDIS_HOST=redis`, `NOTIFICATIONS_QUEUE=queue:notifications`.

## 2. TUGASMU
**Reuse dari monolit (`../monolith`):** `app/Models/Utang.php`, `RiwayatUtang.php`, `Notification.php`, `Tip.php`; `app/Http/Controllers/Api/UtangController.php` (index/store/bayar/getRiwayat/destroy), `Api/NotificationController.php`, `Admin/NotificationController.php` (broadcast), `Admin/TipController.php`. Migrasi: bagian `utang` dari `2024_01_01_000001` + `2025_12_25_162944_add_sisa_jumlah_to_utang` + `2025_12_26_012143_add_jenis_to_utang` + `2025_12_26_013349_create_riwayat_utang_table` + `2025_12_26_000000_create_notifications_table` + `2025_12_26_000002_create_tips_table`. Buang FK lintas-DB (`user_id` → integer index).

**Endpoint REST (`routes/api.php`, di belakang `remote.auth`):**
- `GET/POST /api/debts`, `GET /api/debts/{id}`, `DELETE /api/debts/{id}`.
- `POST /api/debts/{id}/payments` (reuse `bayar` — kurangi `sisa_jumlah`, catat `riwayat_utang`) → **publish `debt.payment_made`**.
- `GET /api/debts/{id}/history` (reuse `getRiwayat`).
- `GET /api/notifications`, `POST /api/notifications/{id}/read`, `DELETE /api/notifications/{id}`.
- `GET /api/tips`; `POST /api/notifications/broadcast` (admin).

**Worker `debt-notify-worker`:** command `broker:listen` (BLPOP loop, mapping event→Notification, error-tolerant + reconnect). Notifikasi di-scope `user_id` dari payload event (worker tak punya request user). Tambah service kedua di compose dgn `command: php artisan broker:listen`.

**GraphQL (Lighthouse, `/graphql`):** type `Debt`,`Notification`; query `debts`,`notifications`; mutation `payDebt`,`markRead`.

**Docker:** copy dari account-service; service `debt-notify-service` (`8004`) + `debt-db` (MySQL `debt_db`, port host `33063`) + `debt-notify-worker`. Network `keuangan-net` external.

## 3. Definition of Done (tes beneran via Docker)
- [ ] `docker compose up -d --build` → debt-notify-service `:8004` + debt-db + **worker** jalan.
- [ ] debts CRUD + `payments` (kurangi sisa, catat riwayat) + `history`.
- [ ] `payDebt` publish `debt.payment_made`.
- [ ] **Bukti async:** buat transfer di account-service (atau transaksi di P3) → cek `GET /api/notifications` muncul notifikasi otomatis hasil worker.
- [ ] (Demo kuat) matikan worker → buat transaksi → hidupkan worker → notif tetap muncul (queue at-least-once).
- [ ] GraphQL jalan. Postman + README + diagram arsitektur/flow untuk PDF.

## 4. Jebakan (WAJIB baca)
- **Worker harus container TERPISAH** (bukan thread di request). Pakai **`BLPOP` (queue)**, bukan `SUBSCRIBE` (pub/sub) — biar event tak hilang kalau worker sempat mati.
- Loop worker **try/catch per event** + reconnect Redis; 1 event rusak jangan matikan worker.
- **CRLF (Windows):** `docker-entrypoint.sh` (dan script worker kalau ada) HARUS LF + `*.sh text eol=lf` + `RUN sed -i 's/\r$//'` di Dockerfile. Kalau CRLF → container crash-loop `exec ...: no such file or directory`.
