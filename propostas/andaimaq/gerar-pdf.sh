#!/usr/bin/env bash
# Gera proposta.pdf e cronograma.pdf a partir dos .html (usa Chromium/Chrome headless).
# Para usar os logos reais, salve-os como logos/seoamplify.png e logos/andaimaq.png antes de rodar.
set -euo pipefail
cd "$(dirname "$0")"
CHROME="${CHROME:-$(command -v chromium || command -v chromium-browser || command -v google-chrome || echo /opt/pw-browsers/chromium-1194/chrome-linux/chrome)}"
for doc in proposta cronograma; do
  "$CHROME" --headless --no-sandbox --disable-gpu --no-pdf-header-footer \
    --virtual-time-budget=3000 --print-to-pdf="$PWD/$doc.pdf" "file://$PWD/$doc.html"
  echo "PDF gerado: $PWD/$doc.pdf"
done
