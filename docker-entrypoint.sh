#!/bin/bash
set -e

DB_NAME="${DB_NAME:-db_prakerin}"
DB_USER="${DB_USER:-siprakerin}"
DB_PASS="${DB_PASS:-siprakerin123}"
MYSQL_DATA="/var/lib/mysql"
INIT_FLAG="$MYSQL_DATA/.siprakerin_initialized"

echo "======================================"
echo "  SiPrakerin — All-in-One Container"
echo "======================================"

# ── Pastikan folder log supervisor ada ────────────────────────────────────────
mkdir -p /var/log/supervisor

# ── Inisialisasi MySQL data directory jika belum ada ─────────────────────────
if [ ! -d "$MYSQL_DATA/mysql" ]; then
    echo "[DB] Inisialisasi MySQL data directory..."
    mysqld --initialize-insecure --user=mysql --datadir="$MYSQL_DATA"
fi

# ── Jalankan MySQL sementara untuk setup ─────────────────────────────────────
echo "[DB] Menjalankan MySQL sementara..."
mysqld --user=mysql --skip-networking=0 --daemonize --pid-file=/tmp/mysql-setup.pid
sleep 3

# ── Setup database & user jika belum pernah diinisialisasi ───────────────────
if [ ! -f "$INIT_FLAG" ]; then
    echo "[DB] Membuat database dan user..."
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%';
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

    echo "[DB] Mengimport schema..."
    for f in /docker-initdb/*.sql; do
        echo "[DB]   → $f"
        mysql -u root "$DB_NAME" < "$f" || echo "[DB]   ⚠ Warning: $f ada error (mungkin sudah ada)"
    done

    touch "$INIT_FLAG"
    echo "[DB] ✓ Database siap!"
else
    echo "[DB] Database sudah ada, skip inisialisasi."
fi

# ── Matikan MySQL sementara ───────────────────────────────────────────────────
echo "[DB] Mematikan MySQL sementara..."
mysqladmin -u root shutdown || true
sleep 2

# ── Update config/database.php agar pakai env vars ───────────────────────────
# (sudah dilakukan saat build, tapi pastikan env var masuk)
export DB_HOST="${DB_HOST:-127.0.0.1}"

echo "[APP] Memulai Apache + MySQL via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/siprakerin.conf
