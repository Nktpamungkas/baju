#!/bin/bash
# Jalankan di Mac tiap mau update STB: ./deploy.sh "pesan commit"
set -e
npm run build
git add -A
git commit -m "${1:-deploy}"
git push
echo "Selesai. Di STB tinggal: git pull && systemctl restart baju.service"
