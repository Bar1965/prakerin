# 🚀 SiPrakerin — Panduan Deploy Docker

Ada **2 pilihan** deploy:

---

## 🥇 Pilihan A: All-in-One (1 container, paling mudah dipindah)
> PHP + Apache + MySQL semuanya dalam **satu image**.  
> Cocok untuk VPS kecil, demo, atau mau pindah server dengan mudah.

### Build image
```bash
docker build -f Dockerfile.allinone -t siprakerin .
```

### Jalankan
```bash
docker run -d \
  -p 80:80 \
  -v siprakerin_data:/var/lib/mysql \
  -v siprakerin_uploads:/var/www/html/uploads \
  --name siprakerin \
  --restart unless-stopped \
  siprakerin
```

### Dengan password custom
```bash
docker run -d \
  -p 80:80 \
  -e DB_PASS=passwordku123 \
  -v siprakerin_data:/var/lib/mysql \
  -v siprakerin_uploads:/var/www/html/uploads \
  --name siprakerin \
  --restart unless-stopped \
  siprakerin
```

### Pindah ke server lain
```bash
# Di server lama — export image
docker save siprakerin | gzip > siprakerin_image.tar.gz

# Di server baru — load dan jalankan
docker load < siprakerin_image.tar.gz
docker run -d -p 80:80 -v siprakerin_data:/var/lib/mysql -v siprakerin_uploads:/var/www/html/uploads --name siprakerin --restart unless-stopped siprakerin
```

---

## 🥈 Pilihan B: Docker Compose (2 container terpisah)
> App dan MySQL berjalan terpisah — lebih fleksibel untuk production.

```bash
cp .env.example .env   # edit password jika perlu
docker compose up -d --build
```

---

## 🔐 Akun Default

| Role        | Username      | Password  |
|-------------|---------------|-----------|
| Admin       | admin         | admin123  |
| Guru        | guru1         | 123456    |
| Siswa       | siswa1        | 123456    |
| Instruktur  | instruktur1   | 123456    |

> ⚠️ **Ganti semua password default setelah login pertama!**

---

## 🛠️ Perintah Berguna

```bash
# Cek log all-in-one
docker logs -f siprakerin

# Masuk ke container
docker exec -it siprakerin bash

# Masuk ke MySQL dalam container
docker exec -it siprakerin mysql -u siprakerin -psiprakerin123 db_prakerin

# Stop
docker stop siprakerin

# Hapus container (data aman di volume)
docker rm siprakerin

# Hapus semua termasuk data (hati-hati!)
docker rm -f siprakerin && docker volume rm siprakerin_data siprakerin_uploads
```

---

## 📁 File Penting

| File | Fungsi |
|------|--------|
| `Dockerfile.allinone` | Build image all-in-one (PHP+Apache+MySQL) |
| `Dockerfile` | Build image app saja (untuk docker-compose) |
| `docker-compose.yml` | Orchestrasi 2 container |
| `docker-entrypoint.sh` | Script inisialisasi DB otomatis |
| `supervisord.conf` | Jalankan MySQL + Apache bersamaan |
| `init-db/*.sql` | Schema + data awal DB |

---

## ⚠️ Catatan Penting
- Fitur kamera selfie butuh **HTTPS** — gunakan Nginx + Certbot untuk SSL
- Data tersimpan di Docker **volume**, aman meski container dihapus
- Pertama kali jalan, DB otomatis diimport (~10-15 detik)
