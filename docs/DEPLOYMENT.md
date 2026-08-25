# WiseBuy Production Deployment Journal & Runbook

Dokumen ini mencatat deployment WiseBuy yang berhasil dilakukan ke VPS pada
25 Agustus 2026. Dokumen ini juga menjadi runbook untuk deployment ulang,
maintenance, troubleshooting, dan update berikutnya.

> Semua password dan application key di dokumen ini adalah placeholder. Jangan
> menyimpan secret production di Git.

## Ringkasan hasil deployment

- Domain: `https://wisebuy.zvy.my.id`
- Application runtime: PHP 8.4 FPM
- Frontend: Vue + Vite production build
- Application web server: Nginx container `wisebuy_web`
- Queue worker: container `wisebuy_queue`
- Database: PostgreSQL existing container `postgres_shared`
- Public gateway: existing container `gateway_nginx`
- TLS: Let's Encrypt menggunakan webroot Certbot
- Gateway direload secara graceful sehingga website existing tidak mengalami downtime

## Arsitektur production

```text
Internet
   |
   | HTTP/HTTPS :80/:443
   v
gateway_nginx (existing dan critical)
   |
   | Docker network: shared_net
   v
wisebuy_web (Nginx khusus WiseBuy)
   |
   | Docker network: wisebuy_internal
   v
wisebuy_app (PHP-FPM :9000)
   |
   | Docker network: shared_pg_network
   v
postgres_shared (PostgreSQL :5432)

wisebuy_queue -----------------------> postgres_shared
```

`gateway_nginx` hanya berfungsi sebagai reverse proxy dan TLS termination untuk
WiseBuy. Static assets dan komunikasi FastCGI ditangani oleh `wisebuy_web`, sehingga
aplikasi WiseBuy dapat dibuild atau direstart tanpa me-restart gateway.

## Infrastruktur existing yang digunakan

### Container

```text
gateway_nginx    nginx:alpine
postgres_shared  postgres:15-alpine
```

### External Docker networks

```text
shared_net         gateway_nginx <-> wisebuy_web
shared_pg_network  wisebuy_app/queue <-> postgres_shared
```

### Gateway mounts

```text
/home/arvy/projects/nginx-infra/conf -> /etc/nginx/conf.d
/home/arvy/projects/nginx-infra/www  -> /var/www/certbot
/etc/letsencrypt                     -> /etc/letsencrypt
```

## File deployment di repository

| File | Fungsi |
| --- | --- |
| `Dockerfile.production` | Multi-stage build PHP, Composer, Node, frontend assets, app, dan Nginx |
| `compose.production.yaml` | Menjalankan app, web, queue, serta migration job |
| `.env.production.example` | Template environment tanpa secret |
| `docker/production/app-nginx.conf` | Nginx internal untuk assets dan PHP-FPM |
| `docker/production/php.ini` | Konfigurasi PHP production dan OPcache |
| `docker/production/gateway-wisebuy.conf.example` | Contoh reverse proxy pada gateway |

## Deployment pertama

### 1. Verifikasi DNS

```bash
dig +short wisebuy.zvy.my.id
curl -4 ifconfig.me
```

IP hasil DNS harus mengarah ke public IP VPS.

### 2. Ambil source code

```bash
cd ~/projects/youritem
git pull
```

Verifikasi file deployment:

```bash
ls Dockerfile.production compose.production.yaml .env.production.example
```

### 3. Buat database user khusus

Generate password kuat. Format hexadecimal memudahkan penggunaan di `.env` dan SQL:

```bash
openssl rand -hex 32
```

Masuk ke PostgreSQL:

```bash
docker exec -it postgres_shared psql -U postgres
```

Buat role dan database:

```sql
CREATE USER wisebuy WITH PASSWORD 'REPLACE_WITH_A_STRONG_PASSWORD';
CREATE DATABASE wisebuy OWNER wisebuy;
\q
```

Jangan gunakan superuser `postgres` sebagai user aplikasi.

Jika database sudah ada tetapi owner belum benar:

```bash
docker exec postgres_shared psql -U postgres \
  -c 'ALTER DATABASE wisebuy OWNER TO wisebuy;'
```

### 4. Tes credential database

```bash
docker run --rm \
  --network shared_pg_network \
  -e PGPASSWORD='REPLACE_WITH_THE_DATABASE_PASSWORD' \
  postgres:15-alpine \
  psql -h postgres_shared -U wisebuy -d wisebuy -c 'SELECT 1;'
```

Expected output mencantumkan nilai `1`.

Jika muncul `password authentication failed`, reset password role:

```bash
docker exec -it postgres_shared psql -U postgres
```

```sql
ALTER ROLE wisebuy WITH LOGIN PASSWORD 'REPLACE_WITH_A_NEW_STRONG_PASSWORD';
\q
```

Password role PostgreSQL dan nilai `DB_PASSWORD` harus identik.

### 5. Siapkan environment production

```bash
cp .env.production.example .env.production
openssl rand -base64 32
nano .env.production
chmod 600 .env.production
```

Konfigurasi minimum:

```dotenv
APP_NAME=WiseBuy
APP_ENV=production
APP_KEY=base64:OUTPUT_DARI_OPENSSL
APP_DEBUG=false
APP_URL=https://wisebuy.zvy.my.id

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=postgres_shared
DB_PORT=5432
DB_DATABASE=wisebuy
DB_USERNAME=wisebuy
DB_PASSWORD=REPLACE_WITH_THE_DATABASE_PASSWORD

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=wisebuy.zvy.my.id
SANCTUM_STATEFUL_DOMAINS=wisebuy.zvy.my.id

CACHE_STORE=database
QUEUE_CONNECTION=database
```

Laravel menggunakan `DB_USERNAME`, bukan `DB_USER`.

Pastikan environment tidak terlacak Git:

```bash
git status --short
```

### 6. Verifikasi external networks

```bash
docker network inspect shared_net >/dev/null
docker network inspect shared_pg_network >/dev/null
```

Kedua perintah harus selesai tanpa error.

### 7. Build production images

```bash
docker compose -f compose.production.yaml build
```

Build ini menjalankan Composer install tanpa development dependencies, Vite production
build, dan membuat image terpisah untuk PHP-FPM serta Nginx internal.

### 8. Jalankan migration dan initial seeder

```bash
docker compose -f compose.production.yaml run --rm migrate
```

Pada deployment pertama saja, isi default categories:

```bash
docker compose -f compose.production.yaml run --rm migrate \
  php artisan db:seed --class=CategorySeeder --force
```

Jangan menjalankan `DemoDataSeeder` di production.

### 9. Jalankan application services

```bash
docker compose -f compose.production.yaml up -d app web queue
docker compose -f compose.production.yaml ps
```

Expected services:

```text
wisebuy_app    healthy
wisebuy_web    healthy
wisebuy_queue  running
```

Periksa log jika dibutuhkan:

```bash
docker compose -f compose.production.yaml logs --tail=100 app web queue
```

### 10. Verifikasi koneksi internal

Dari Nginx internal:

```bash
docker exec wisebuy_web wget -qO- http://127.0.0.1/up
```

Dari gateway:

```bash
docker exec gateway_nginx wget -qO- http://wisebuy_web/up
```

Laravel 13 mengembalikan halaman HTML berjudul `WiseBuy` dengan tulisan
`Application up`. Ini adalah response health check yang normal.

## Konfigurasi public gateway tanpa downtime

### 1. Buat konfigurasi HTTP

File gateway:

```bash
sudo nano /home/arvy/projects/nginx-infra/conf/wisebuy.conf
```

Konfigurasi awal:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name wisebuy.zvy.my.id;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/certbot;
        default_type text/plain;
    }

    location / {
        proxy_pass http://wisebuy_web;
        proxy_http_version 1.1;

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Selalu validasi sebelum reload:

```bash
docker exec gateway_nginx nginx -t
```

Hanya jika validasi sukses, lakukan graceful reload:

```bash
docker exec gateway_nginx nginx -s reload
```

`nginx -s reload` tidak menghentikan container. Worker lama menyelesaikan koneksi
existing sementara worker baru menggunakan konfigurasi baru.

Jangan gunakan perintah berikut untuk perubahan konfigurasi biasa:

```text
docker restart gateway_nginx
docker stop gateway_nginx
docker compose down
```

### 2. Verifikasi ACME webroot

```bash
sudo mkdir -p /home/arvy/projects/nginx-infra/www/.well-known/acme-challenge
echo 'wisebuy-acme-ok' | sudo tee \
  /home/arvy/projects/nginx-infra/www/.well-known/acme-challenge/wisebuy-test

curl http://wisebuy.zvy.my.id/.well-known/acme-challenge/wisebuy-test
```

Expected output:

```text
wisebuy-acme-ok
```

### 3. Terbitkan certificate Let's Encrypt

Dengan Certbot di host:

```bash
sudo certbot certonly \
  --webroot \
  --webroot-path /home/arvy/projects/nginx-infra/www \
  --domain wisebuy.zvy.my.id
```

Atau dengan Certbot container:

```bash
docker run --rm \
  -v /etc/letsencrypt:/etc/letsencrypt \
  -v /home/arvy/projects/nginx-infra/www:/var/www/certbot \
  certbot/certbot certonly \
  --webroot \
  --webroot-path /var/www/certbot \
  --email REPLACE_WITH_AN_ACTIVE_EMAIL \
  --agree-tos \
  --no-eff-email \
  -d wisebuy.zvy.my.id
```

Verifikasi certificate:

```bash
sudo ls -la /etc/letsencrypt/live/wisebuy.zvy.my.id
```

### 4. Aktifkan HTTPS

Backup konfigurasi yang sudah bekerja:

```bash
sudo cp /home/arvy/projects/nginx-infra/conf/wisebuy.conf \
  /home/arvy/projects/nginx-infra/conf/wisebuy.conf.backup
```

Isi final `wisebuy.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name wisebuy.zvy.my.id;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/certbot;
        default_type text/plain;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;

    server_name wisebuy.zvy.my.id;

    ssl_certificate /etc/letsencrypt/live/wisebuy.zvy.my.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wisebuy.zvy.my.id/privkey.pem;

    location / {
        proxy_pass http://wisebuy_web;
        proxy_http_version 1.1;

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port 443;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

Validasi dan graceful reload:

```bash
docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```

Hapus ACME test file:

```bash
sudo rm /home/arvy/projects/nginx-infra/www/.well-known/acme-challenge/wisebuy-test
```

## Verifikasi akhir

```bash
docker compose -f compose.production.yaml ps
curl -I http://wisebuy.zvy.my.id
curl -I https://wisebuy.zvy.my.id
curl https://wisebuy.zvy.my.id/up
```

Periksa log:

```bash
docker compose -f compose.production.yaml logs --tail=100 app web queue
docker logs gateway_nginx --tail=100
```

Checklist selesai:

- HTTP redirect ke HTTPS.
- HTTPS menggunakan certificate valid untuk `wisebuy.zvy.my.id`.
- `/up` menampilkan health page Laravel.
- Login, session, API, dan frontend assets bekerja.
- `wisebuy_app` dan `wisebuy_web` healthy.
- Queue worker tetap running.
- Website lain pada `gateway_nginx` tidak terganggu.

## Deployment setelah ada perubahan code

Perubahan harus sudah di-commit dan di-push ke repository sebelum dijalankan di VPS.
Semua perintah berikut dijalankan dari directory project:

```bash
cd ~/projects/youritem
```

### Alur update standar

Gunakan alur ini jika tidak yakin apakah update berisi perubahan database:

```bash
git pull
docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml run --rm migrate
docker compose -f compose.production.yaml up -d --remove-orphans
docker compose -f compose.production.yaml ps
```

Migration Laravel bersifat idempotent: migration yang sudah pernah dijalankan tidak
akan dijalankan ulang. Perintah ini aman digunakan meskipun update tidak membawa
migration baru.

Proses tersebut akan:

1. Mengambil commit terbaru.
2. Membuat ulang production image dan frontend assets.
3. Menjalankan migration yang belum pernah dijalankan.
4. Mengganti container WiseBuy dengan image terbaru.
5. Membiarkan `gateway_nginx` dan `postgres_shared` tetap berjalan.

### Hanya PHP, Vue, TypeScript, atau CSS

```bash
git pull
docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml up -d app web queue
```

Production tidak menggunakan Vite HMR. Perubahan frontend selalu membutuhkan image
build baru agar output `public/build` ikut diperbarui.

### Terdapat migration database

```bash
git pull
docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml run --rm migrate
docker compose -f compose.production.yaml up -d app web queue
```

Jangan menjalankan `migrate:fresh` di production karena perintah itu menghapus seluruh
table dan data.

### Hanya `.env.production` yang berubah

Image tidak perlu dibuild ulang. Recreate container yang membaca environment:

```bash
nano .env.production
chmod 600 .env.production
docker compose -f compose.production.yaml up -d --force-recreate app queue
```

Jika perubahan environment memengaruhi Nginx internal atau frontend build, jalankan
alur update standar.

### Dependency Composer atau NPM berubah

Jika `composer.lock`, `package-lock.json`, atau Dockerfile berubah:

```bash
git pull
docker compose -f compose.production.yaml build --pull
docker compose -f compose.production.yaml run --rm migrate
docker compose -f compose.production.yaml up -d --remove-orphans
```

### Konfigurasi gateway berubah

Gateway tidak perlu disentuh untuk update source aplikasi biasa. Jika file
`wisebuy.conf` memang berubah, selalu validasi sebelum graceful reload:

```bash
docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```

Jangan me-restart `gateway_nginx` untuk deployment WiseBuy biasa.

### Verifikasi setiap update

```bash
docker compose -f compose.production.yaml ps
curl -fsS https://wisebuy.zvy.my.id/up >/dev/null && echo 'WiseBuy healthy'
docker compose -f compose.production.yaml logs --tail=100 app web queue
```

Lakukan smoke test manual:

1. Buka `https://wisebuy.zvy.my.id`.
2. Login.
3. Pastikan frontend assets termuat tanpa error.
4. Uji fitur utama yang berubah.
5. Periksa browser console dan application logs.

Database tetap berada di `postgres_shared` dan tidak dihapus ketika container app,
web, atau queue diganti.

## Certificate renewal

Jika Certbot terpasang di host:

```bash
sudo certbot renew --dry-run
sudo certbot renew
docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```

Jika menggunakan Certbot container:

```bash
docker run --rm \
  -v /etc/letsencrypt:/etc/letsencrypt \
  -v /home/arvy/projects/nginx-infra/www:/var/www/certbot \
  certbot/certbot renew --webroot --webroot-path /var/www/certbot

docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```

Certificate renewal sebaiknya dijadwalkan melalui systemd timer atau cron. Reload
Nginx diperlukan setelah certificate diperbarui agar worker baru menggunakan file
certificate terbaru.

## Troubleshooting cepat

### `password authentication failed for user "wisebuy"`

Penyebab: password role PostgreSQL berbeda dari `DB_PASSWORD`.

Solusi:

1. Reset password role `wisebuy`.
2. Update `.env.production`.
3. Tes menggunakan disposable PostgreSQL client.
4. Recreate service agar environment baru dibaca:

```bash
docker compose -f compose.production.yaml up -d --force-recreate app queue web
```

### Gateway tidak dapat resolve `wisebuy_web`

```bash
docker network inspect shared_net
docker inspect wisebuy_web --format '{{json .NetworkSettings.Networks}}'
docker inspect gateway_nginx --format '{{json .NetworkSettings.Networks}}'
```

Kedua container harus tergabung dalam `shared_net`.

### `wisebuy_web` berstatus `health: starting`

```bash
docker inspect wisebuy_web \
  --format '{{range .State.Health.Log}}{{println .Output}}{{end}}'

docker exec wisebuy_web wget -qO- http://127.0.0.1/up
```

### Perubahan `.env.production` belum terbaca

```bash
docker compose -f compose.production.yaml up -d --force-recreate app queue
```

Jangan menampilkan hasil `docker compose config` ke tempat publik karena output dapat
mengandung nilai environment dan secret.

### Gateway config bermasalah

Jangan reload bila `nginx -t` gagal. Jika diperlukan, pulihkan backup:

```bash
sudo cp /home/arvy/projects/nginx-infra/conf/wisebuy.conf.backup \
  /home/arvy/projects/nginx-infra/conf/wisebuy.conf

docker exec gateway_nginx nginx -t
docker exec gateway_nginx nginx -s reload
```
