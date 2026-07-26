#!/bin/bash
# Jalankan di Mac tiap mau update STB: ./deploy.sh "pesan commit"
set -e
npm run build
git add -A
git commit -m "${1:-deploy}"
git push

echo "Push selesai. Update STB..."
ssh root@arm-nale "cd /opt/baju && git pull && (composer install --no-dev --optimize-autoloader --ignore-platform-reqs || true) && php artisan package:discover --ansi && systemctl restart baju.service"
echo "Selesai, STB udah update."
