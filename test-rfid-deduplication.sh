#!/bin/bash

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🧪 TEST DÉDUPLICATION RFID - rfidlive-ultra"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier que le serveur tourne
echo "📡 1. VÉRIFICATION DU SERVEUR"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if curl -s http://localhost:8000/api/health > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Serveur accessible sur localhost:8000${NC}"
else
    echo -e "${RED}❌ ERREUR: Le serveur ne répond pas sur localhost:8000${NC}"
    echo -e "${BLUE}   Démarrer avec: php artisan serve --host=0.0.0.0 --port=8000${NC}"
    exit 1
fi

echo ""
echo "🧹 2. NETTOYAGE DES LOGS EXISTANTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -X POST http://localhost:8000/api/rfid/clear-logs > /dev/null
echo -e "${GREEN}✅ Logs RFID vidés${NC}"

echo ""
echo "📤 3. TEST ENVOI UNIQUE (1 détection)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

curl -s -X PUT http://localhost:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"TEST_001","timestamp":1234567890.123}]' | jq -r '.success'

echo -e "${GREEN}✅ 1 détection envoyée${NC}"

# Vérifier combien de logs sont enregistrés
LOGS_COUNT=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.count')
echo -e "${BLUE}   Logs enregistrés: $LOGS_COUNT (attendu: 1)${NC}"

if [ "$LOGS_COUNT" -eq 1 ]; then
    echo -e "${GREEN}   ✅ Correct !${NC}"
else
    echo -e "${RED}   ❌ Erreur: devrait être 1${NC}"
fi

echo ""
echo "📤 4. TEST DÉDUPLICATION (3x la même détection)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Envoyer 3 fois la même requête rapidement
for i in {1..3}; do
    curl -s -X PUT http://localhost:8000/api/rfid/debug \
      -H "Content-Type: application/json" \
      -H "Serial: 200" \
      -d '[{"serial":"DUPLICATE_TEST","timestamp":1234567891.456}]' > /dev/null
    echo "  Envoi $i/3..."
done

echo ""

# Vérifier combien de logs sont enregistrés
LOGS_COUNT=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.count')
echo -e "${BLUE}   Logs enregistrés: $LOGS_COUNT (attendu: 2 - 1 de test 3 + 1 dupliqué bloqué)${NC}"

if [ "$LOGS_COUNT" -eq 2 ]; then
    echo -e "${GREEN}   ✅ Déduplication fonctionne ! (2 détections bloquées)${NC}"
elif [ "$LOGS_COUNT" -eq 4 ]; then
    echo -e "${RED}   ❌ Déduplication NE fonctionne PAS ! (toutes les requêtes ont été loguées)${NC}"
else
    echo -e "${YELLOW}   ⚠️  Résultat inattendu: $LOGS_COUNT logs${NC}"
fi

echo ""
echo "📤 5. TEST DÉTECTIONS DIFFÉRENTES (2 tags différents)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

curl -s -X PUT http://localhost:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"TAG_A","timestamp":1234567892.111}]' > /dev/null

curl -s -X PUT http://localhost:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"TAG_B","timestamp":1234567893.222}]' > /dev/null

echo -e "${GREEN}✅ 2 détections différentes envoyées${NC}"

LOGS_COUNT=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.count')
echo -e "${BLUE}   Logs enregistrés: $LOGS_COUNT (attendu: 4)${NC}"

if [ "$LOGS_COUNT" -eq 4 ]; then
    echo -e "${GREEN}   ✅ Correct ! Les détections différentes sont bien loguées${NC}"
else
    echo -e "${YELLOW}   ⚠️  Résultat inattendu: $LOGS_COUNT logs${NC}"
fi

echo ""
echo "📋 6. AFFICHAGE DES LOGS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

LOGS=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.logs')
echo "$LOGS" | jq -r '.[] | "  ID: \(.id) | Serial: \(.serial) | Data: \(.data | fromjson | .[0].serial)"'

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  📊 RÉSUMÉ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${BLUE}Ouvrez http://localhost:8000/rfidlive-ultra pour voir les détections${NC}"
echo -e "${BLUE}Relancez ce script pour tester à nouveau${NC}"
echo ""
echo "✅ Tests terminés !"
echo ""
