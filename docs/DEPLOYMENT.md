# Production deployment

## Prerequisites

- DNS `wisebuy.zvy.my.id` mengarah ke VPS.
- External Docker networks `shared_net` dan `shared_pg_network` sudah tersedia.
- Container PostgreSQL dapat diakses sebagai `postgres_shared` pada `shared_pg_network`.
- Gateway Nginx dapat mengakses container sebagai `wisebuy_web` pada `shared_net`.

## 1. Buat database user khusus

Masuk ke PostgreSQL:

```bash
docker exec -it postgres_shared psql -U postgres
```

Jalankan dengan password kuat yang baru:

```sql
CREATE USER wisebuy WITH PASSWORD 'REPLACE_WITH_A_STRONG_PASSWORD';
CREATE DATABASE wisebuy OWNER wisebuy;
\q
```

Jangan gunakan superuser `postgres` sebagai user aplikasi.

## 2. Siapkan environment

```bash
cp .env.production.example .env.production
openssl rand -base64 32
nano .env.production
chmod 600 .env.production
```

Isi `APP_KEY` dengan prefix `base64:`, misalnya:

```dotenv
APP_KEY=base64:OUTPUT_DARI_OPENSSL
```

Masukkan password user `wisebuy` yang dibuat pada langkah pertama. Jangan commit
`.env.production`.

## 3. Build, migrate, dan start

```bash
docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml run --rm migrate
docker compose -f compose.production.yaml run --rm migrate php artisan db:seed --class=CategorySeeder --force
docker compose -f compose.production.yaml up -d app web queue
docker compose -f compose.production.yaml ps
```

Seeder category cukup dijalankan pada deployment pertama. Demo data tidak dijalankan
di production.

## 4. Hubungkan gateway Nginx

Temukan directory config yang di-mount:

```bash
docker inspect gateway_nginx --format '{{json .Mounts}}'
```

Salin isi `docker/production/gateway-wisebuy.conf.example` ke directory config
gateway. Untuk HTTPS, ikuti pola certificate dan `listen 443` dari virtual host
existing. Setelah itu:

```bash
docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```

## Update berikutnya

```bash
git pull
docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml run --rm migrate
docker compose -f compose.production.yaml up -d --remove-orphans
docker image prune -f
```

Periksa deployment:

```bash
docker compose -f compose.production.yaml ps
docker compose -f compose.production.yaml logs --tail=100 app web queue
curl -I https://wisebuy.zvy.my.id/up
```

