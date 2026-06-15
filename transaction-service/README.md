# Transaction Service

The **Transaction Service** is the core ledger and dashboard orchestrator for the Keuangan Microservices Application. 

This service:
1. Manages income (`pemasukan`) and expense (`pengeluaran`) transaction records in a PostgreSQL database.
2. Synchronizes account balances synchronously with `account-service` via REST APIs.
3. Publishes asynchronous event notifications (`transaction.created`) to Redis queue.
4. Exposes REST APIs, manual Lighthouse GraphQL resolvers, and connects to the Hasura GraphQL Engine for direct database introspection.

## Architecture Decisions

### 1. Separation of Databases & Missing Foreign Keys
Following the microservices layout, the Postgres database is isolated from all other databases. There are no foreign key constraints linking to external tables (e.g. `users` or `rekening`). Instead, `user_id` and `rekening_id` are integer columns equipped with database-level indexes (`->index()`). Business validations occur programmatically via remote REST endpoints.

### 2. Transaction Orchestration & Compensation Rollback Workflows
Because a local DB transaction cannot lock or manage state in the remote `account-service`, the following orchestration protocol is applied:
- **Creation Flow**:
  1. Call `account-service` `adjust-balance` endpoint with an idempotency key (`ref` - a UUID).
  2. If the balance update fails (e.g. `409` Insufficient Balance), do not write the transaction locally; return the error immediately.
  3. If the balance update succeeds (`200`), attempt to write the ledger record locally.
  4. If local DB write fails, trigger a compensation call to `account-service` in the opposite direction (using a new `ref`) to rollback the balance change.
  5. On success, publish `transaction.created` to Redis.

- **Deletion Flow**:
  1. Call `account-service` `adjust-balance` in the opposite direction of the original transaction (e.g. `debit` for deleting an income).
  2. If the balance adjustment fails (returns non-`200`), fail the deletion; do not delete the ledger record.
  3. If balance adjustment succeeds, delete the local ledger record.
  4. If local DB deletion fails, trigger a compensation call to revert the balance adjustment.

### 3. Remote Authentication Middleware
The service uses the `remote.auth` middleware (`App\Http\Middleware\RemoteAuth`). It intercepts Bearer tokens, validates them against the identity provider (`auth-service`), and caches responses for 60 seconds (utilizing SHA-256 hash keys) to prevent authentication bottlenecks.

### 4. Redis Event Publisher
Transactions are published using the queue-based Redis protocol. We use `RPUSH` to `queue:notifications` via `App\Support\Broker` to achieve at-least-once queue delivery.

---

## Port Configurations & Network

- **Network**: `keuangan-net` (External)
- **App Service Port**: `8003:8000`
- **Database Service Port**: `54323:5432` (PostgreSQL)
- **Hasura GraphQL Engine**: `8080:8080` (introspecting `transaction_db` using Postgres credentials)

---

## Environmental Overrides (`.env`)

```ini
APP_NAME=transaction-service
APP_ENV=local
APP_KEY=base64:m3o+1Lm3xFyQMU3CLlQH2tM+JTgiYOzqaN86R2IvJJg=
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=transaction-db
DB_PORT=5432
DB_DATABASE=transaction_db
DB_USERNAME=zenith
DB_PASSWORD=zenith

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CLIENT=predis
NOTIFICATIONS_QUEUE=queue:notifications

AUTH_SERVICE_URL=http://auth-service:8000
ACCOUNT_SERVICE_URL=http://account-service:8000
```

---

## Deployment & Setup

Ensure `auth-service` and `account-service` are running, as they establish the docker network `keuangan-net` and the `redis` container.

```bash
# Clone/Create folder and enter
cd transaction-service

# Build and stand up containers
docker compose up -d --build

# Run migrations (automatically run inside docker-entrypoint.sh, but can run manually)
docker compose exec transaction-service php artisan migrate --force
```

---

## API Specifications

### REST APIs

All endpoints require `Authorization: Bearer <token>` in request headers.

1. **Create Transaction**
   - **Method/URL**: `POST /api/transactions`
   - **Payload**:
     ```json
     {
       "type": "income" | "expense",
       "rekening_id": 1,
       "kategori": "Gaji",
       "amount": 5000000,
       "deskripsi": "Gaji bulanan",
       "tanggal": "2026-06-15"
     }
     ```
   - **Response (`201`)**: Returns created transaction model.
   - **Error (`409`)**: Returned when remote balance validation fails.

2. **Get All Transactions**
   - **Method/URL**: `GET /api/transactions`
   - **Query Parameters (Optional)**: `start_date`, `end_date` (format `YYYY-MM-DD`).
   - **Response (`200`)**: Returns array of transactions merged and sorted by date.

3. **Get Single Transaction**
   - **Method/URL**: `GET /api/transactions/{id}`
   - **Query Parameters**: `jenis` (`pemasukan` or `pengeluaran`) to speed up search and avoid ID clashes.
   - **Response (`200`)**: Returns transaction details.

4. **Delete Transaction**
   - **Method/URL**: `DELETE /api/transactions/{id}`
   - **Query Parameters (Required)**: `jenis` (`pemasukan` or `pengeluaran`).
   - **Response (`200`)**: `{"message": "Transaksi berhasil dihapus dan saldo diperbarui."}`

5. **Dashboard Summary**
   - **Method/URL**: `GET /api/dashboard/summary`
   - **Response (`200`)**:
     ```json
     {
       "bulan_ini": {
         "periode": "June 2026",
         "pemasukan": 5000000,
         "pengeluaran": 150000
       },
       "total_saldo": 4850000,
       "detail_rekening": [...]
     }
     ```

### GraphQL API

Lighthouse console endpoint is available at `/graphql`.

1. **Query Transactions**
   ```graphql
   query {
     transactions {
       id
       user_id
       rekening_id
       kategori
       deskripsi
       jumlah
       tanggal
       jenis
     }
   }
   ```

2. **Query Summary**
   ```graphql
   query {
     summary {
       bulan_ini {
         periode
         pemasukan
         pengeluaran
       }
       total_saldo
       detail_rekening {
         id
         nama_rekening
         tipe
         saldo
       }
     }
   }
   ```

3. **Mutation CreateTransaction**
   ```graphql
   mutation {
     createTransaction(
       type: "income"
       rekening_id: 1
       kategori: "Bonus"
       amount: 1000000
       deskripsi: "Bonus performa"
       tanggal: "2026-06-15"
     ) {
       id
       jumlah
       jenis
     }
   }
   ```
