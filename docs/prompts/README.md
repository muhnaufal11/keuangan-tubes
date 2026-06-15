# Prompt Tim — Sistem Keuangan Microservices (Tugas Besar IAE)

Tiap orang pegang 1 service. Kasih file prompt orang itu ke AI agent-nya (Claude Code/dll) di folder service masing-masing.

## Status
| Orang | Service | Port | Status |
|---|---|---|---|
| P1 (Nadhif) | auth-service | 8001 | ✅ **SELESAI & teruji** (jalan di Docker) |
| P2 (Naufal) | account-service | 8002 | ✅ **SELESAI & teruji** (jalan di Docker) |
| P3 | transaction-service | 8003 | ⏳ belum mulai → pakai `P3-transaction-service.md` |
| P4 | debt-notify-service | 8004 | ⏳ belum mulai → pakai `P4-debt-notify-service.md` |

## File
- **`_KONTRAK-BERSAMA.md`** — aturan bersama (port, kontrak API, format event, CRLF). WAJIB dibaca semua.
- `P1-auth-service.md`, `P2-account-service.md` — referensi (sudah selesai).
- `P1-FIX-revisi1.md` — catatan koreksi P1 (sudah diterapkan).
- **`P3-transaction-service.md`**, **`P4-debt-notify-service.md`** — prompt untuk yang belum mulai.

## Cara menjalankan yang sudah jadi
```bash
# auth-service dulu (bikin network keuangan-net + redis)
cd auth-service && docker compose up -d --build
# lalu account-service
cd ../account-service && docker compose up -d --build
```
Sudah teruji end-to-end: register di auth (:8001) → token → buat rekening + transfer di account (:8002) → event `transfer.completed` masuk Redis.

## Pelajaran penting (sudah kejadian)
1. **CRLF**: semua file `.sh` HARUS LF, kalau tidak container crash-loop. Detail di `_KONTRAK-BERSAMA.md`.
2. **`remote.auth`** middleware sudah jadi di `account-service/app/Http/Middleware/RemoteAuth.php` — P3 & P4 tinggal **copy** (jangan bikin dari nol).
3. Cara tercepat bikin service baru: **copy dari `auth-service`** (sudah bersih), lalu ganti isinya — persis cara P2 dibuat.
