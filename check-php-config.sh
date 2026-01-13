#!/bin/bash

echo "========================================="
echo "  Diagnostic Configuration PHP pour SSE"
echo "========================================="
echo ""

echo "1️⃣  Version PHP:"
php -v | head -n 1
echo ""

echo "2️⃣  Configuration critique pour SSE:"
echo ""

# Check output_buffering
OUTPUT_BUFFERING=$(php -r "echo ini_get('output_buffering');")
echo "   output_buffering = $OUTPUT_BUFFERING"
if [ "$OUTPUT_BUFFERING" != "0" ] && [ "$OUTPUT_BUFFERING" != "" ]; then
    echo "   ❌ PROBLÈME: output_buffering est activé!"
    echo "   Cela empêche le SSE de fonctionner."
else
    echo "   ✅ OK"
fi
echo ""

# Check max_execution_time
MAX_EXEC=$(php -r "echo ini_get('max_execution_time');")
echo "   max_execution_time = $MAX_EXEC secondes"
if [ "$MAX_EXEC" -lt 300 ] && [ "$MAX_EXEC" != "0" ]; then
    echo "   ⚠️  WARNING: Trop court pour SSE longue durée"
else
    echo "   ✅ OK"
fi
echo ""

# Check implicit_flush
IMPLICIT_FLUSH=$(php -r "echo ini_get('implicit_flush');")
echo "   implicit_flush = $IMPLICIT_FLUSH"
echo ""

echo "3️⃣  Localisation du fichier php.ini:"
PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
echo "   $PHP_INI"
echo ""

echo "========================================="
echo "  SOLUTION"
echo "========================================="
echo ""

if [ "$OUTPUT_BUFFERING" != "0" ] && [ "$OUTPUT_BUFFERING" != "" ]; then
    echo "❌ Le SSE ne peut PAS fonctionner avec output_buffering activé!"
    echo ""
    echo "OPTIONS:"
    echo ""
    echo "Option 1: Désactiver output_buffering dans php.ini (RECOMMANDÉ)"
    echo "  1. Ouvrir: $PHP_INI"
    echo "  2. Chercher: output_buffering"
    echo "  3. Modifier en: output_buffering = Off"
    echo "  4. Relancer les serveurs"
    echo ""
    echo "Option 2: Lancer les serveurs avec flag -d (RAPIDE)"
    echo "  Terminal 1:"
    echo "    php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000"
    echo ""
    echo "  Terminal 2:"
    echo "    php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8001"
    echo ""
    echo "Option 3: Utiliser /rfidlive-simple au lieu de SSE"
    echo "  (polling toutes les 1 seconde, pas de SSE)"
    echo ""
else
    echo "✅ Configuration PHP semble correcte pour SSE"
    echo ""
    echo "Le problème vient d'ailleurs. Vérifiez:"
    echo "  - Les serveurs tournent bien sur 8000 et 8001"
    echo "  - Les routes sont bien chargées"
    echo "  - Pas de middleware qui bloque les SSE"
    echo ""
fi
