# P1 · auth-service — ✅ SELESAI (referensi)

> Status: **selesai & teruji** jalan di Docker (`:8001`). Kode ada di folder `auth-service/`.
> File ini referensi saja. Koreksi yang pernah diberikan ada di `P1-FIX-revisi1.md` (sudah diterapkan).

## Peran
Single source of truth identitas: register/login/logout, terbitkan token Sanctum, `validate-token` untuk service lain, admin user, publish `user.registered` ke Redis.

## Endpoint (terbukti jalan)
- `POST /api/auth/register` `{name,email,password}` → `{token,user}` + publish `user.registered`
- `POST /api/auth/login` → `{token,user}`
- `POST /api/auth/logout` (Bearer) → 204
- `GET /api/auth/user` (Bearer)
- `GET /api/auth/validate-token` (Bearer) → `{id,email,name,tipe_akun,is_admin}` ← dipakai semua service
- `GET /api/auth/admin/users`, `PATCH /api/auth/admin/users/{id}/ban`
- GraphQL `/graphql`: `me`, `register`, `login`, `logout`

## Jalan
```bash
cd auth-service && docker compose up -d --build   # bikin network keuangan-net + redis
```

## Catatan
Stack: Laravel 9 + Sanctum + Lighthouse + predis. DB `auth_db` (MySQL, host port 33061).
Fix CRLF entrypoint sudah diterapkan (lihat `P1-FIX-revisi1.md`) — pastikan ikut ke-commit.
