# P1-FIX revisi 1 — ✅ SUDAH DITERAPKAN (catatan)

Koreksi untuk auth-service. **Semua sudah diterapkan & terbukti** (service jalan end-to-end di Docker). Disimpan sebagai catatan/riwayat.

1. **Backend-only:** `routes/web.php` dikosongkan, hapus `resources/views`, `vite.config.js`, `package.json`, dokumen monolit. (Penyebab `route:list`/`optimize` crash.)
2. **Seeder mati** (`DummyHelpSessionSeeder`, `LandingPageSettingsSeeder`) dihapus.
3. **Entrypoint:** tambah `cp .env.example .env` + `key:generate` sebelum `sed`.
4. **composer.lock:** set `config.platform.php = 8.2` + `composer update`.
5. **CRLF (ditemukan saat run di Docker):** `docker-entrypoint.sh` → LF, Dockerfile `RUN sed -i 's/\r$//' docker-entrypoint.sh`, `.gitattributes` `*.sh text eol=lf`.

> ⚠️ Fix #5 (3 file: `docker-entrypoint.sh`, `Dockerfile`, `.gitattributes`) masih **belum di-commit** di working tree. Pastikan ikut saat commit, kalau tidak build dari clone baru akan crash-loop lagi.
