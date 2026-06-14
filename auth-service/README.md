# Auth-Service (Identity & Access Provider)

Layanan **auth-service** bertanggung jawab sebagai *Single Source of Truth* untuk manajemen data pengguna, login, registrasi, password reset, verifikasi token, dan administrasi user (banned/unbanned).

Layanan ini dibangun menggunakan **Laravel 9** + **Sanctum** + **Lighthouse GraphQL** + **Redis Event Queue**.

---

## 🚀 Persyaratan Sistem
- **PHP 8.2+** (dengan ekstensi `pdo_mysql`, `openssl`, `mbstring`, `bcmath`, `gd`)
- **Composer 2.x**
- **MySQL 8.0**
- **Redis Server** (sebagai event broker)
- **Docker** (jika ingin dijalankan via container)

---

## 🛠️ Cara Menjalankan Layanan Secara Lokal (Tanpa Docker)

1. **Konfigurasi Environment (.env)**
   Salin berkas `.env.example` menjadi `.env` lalu sesuaikan kredensial basis data MySQL dan host Redis Anda:
   ```bash
   cp .env.example .env
   ```
   Secara default, `.env` diatur untuk menggunakan host `127.0.0.1` pada port MySQL dan Redis lokal Anda.

2. **Migrasi dan Seed Database**
   Pastikan MySQL sudah berjalan dan basis data `auth_db` sudah dibuat. Jalankan perintah migrasi dan seed:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   *Catatan: Seed database akan membuat akun administrator default:*
   - **Email:** `admin@example.com`
   - **Password:** `Admin1234`
   - **Tipe Akun:** `admin`

3. **Jalankan Aplikasi**
   Jalankan server pengembangan Laravel pada port `8001` (sesuai spesifikasi topologi):
   ```bash
   php artisan serve --port=8001
   ```
   Layanan kini dapat diakses di `http://localhost:8001`.

---

## 🐳 Cara Menjalankan Layanan Menggunakan Docker (Rekomendasi)

1. **Jalankan Stack Docker Compose**
   Gunakan file `docker-compose.yml` yang terletak di root direktori `auth-service` untuk menjalankan container DB, Redis, dan App:
   ```bash
   docker compose up -d --build
   ```

2. **Verifikasi Container**
   Pastikan container `auth-service`, `auth-db`, dan `redis` berjalan normal:
   ```bash
   docker compose ps
   ```
   Layanan akan otomatis termigrasi dan ter-seed saat container dijalankan, dan dapat diakses pada port `8001`.

---

## 📡 Daftar REST Endpoints (`/api`)

| Method | Endpoint | Auth | Request Body | Response / Keterangan |
|---|---|---|---|---|
| **POST** | `/api/auth/register` | Publik | `{name?, username?, email, password, security_question?, security_answer?}` | `201 {token, user}` + Kirim event `user.registered` ke Redis queue. |
| **POST** | `/api/auth/login` | Publik | `{email, password}` | `200 {token, user}` (update `last_login_at`). |
| **POST** | `/api/auth/logout` | Bearer | — | `204 No Content` (menghapus token saat ini). |
| **GET** | `/api/auth/user` | Bearer | — | `200 {user}` (profil pengguna terautentikasi). |
| **GET** | `/api/auth/validate-token` | Bearer | — | `200 {id, email, name, tipe_akun, is_admin}` (endpoint internal untuk divalidasi service lain). |
| **POST** | `/api/auth/password/question` | Publik | `{identifier}` | `200 {security_question}` |
| **POST** | `/api/auth/password/reset` | Publik | `{identifier, security_answer, password, password_confirmation}` | `200 {message}` |
| **GET** | `/api/auth/admin/users` | Bearer (Admin) | — | `200 {users_pagination}` (hanya bisa diakses admin). |
| **PATCH** | `/api/auth/admin/users/{id}/ban` | Bearer (Admin) | — | `200 {message, user}` (blokir/buka blokir pengguna). |

---

## 🧬 GraphQL API (`/graphql`)

Expose endpoint GraphQL pada `POST http://localhost:8001/graphql`.

### 1. Registrasi
```graphql
mutation {
  register(name: "John Doe", username: "johndoe", email: "john@example.com", password: "password123") {
    token
    user {
      id
      username
      email
      is_admin
    }
  }
}
```

### 2. Login
```graphql
mutation {
  login(email: "john@example.com", password: "password123") {
    token
    user {
      id
      username
      email
      is_admin
    }
  }
}
```

### 3. Query User Info (Protected)
*Harus mengirimkan Header: `Authorization: Bearer <token>`*
```graphql
query {
  me {
    id
    username
    email
    is_admin
  }
}
```

### 4. Logout (Protected)
*Harus mengirimkan Header: `Authorization: Bearer <token>`*
```graphql
mutation {
  logout {
    status
    message
  }
}
```

---

## 📬 Postman Collection
Gunakan file [auth.postman_collection.json](./auth.postman_collection.json) di folder ini untuk mengimpor seluruh koleksi REST & GraphQL request ke dalam Postman Anda. Sesuaikan variabel global `BASE_URL` dan `TOKEN` di dalam koleksi.
