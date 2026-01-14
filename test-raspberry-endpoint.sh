#!/bin/bash

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🧪 TEST ENDPOINT /api/raspberry (PRODUCTION)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${YELLOW}⚠️  NOTE: Ce test nécessite un lecteur RFID configuré dans la base${NC}"
echo -e "${YELLOW}   avec Serial: 200 et statut 'active'${NC}"
echo ""

# Vérifier que le serveur tourne
echo "📡 1. VÉRIFICATION DU SERVEUR"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if curl -s http://localhost:8000/api/health > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Serveur accessible sur localhost:8000${NC}"
else
    echo -e "${RED}❌ ERREUR: Le serveur ne répond pas sur localhost:8000${NC}"
    exit 1
fi

echo ""
echo "🧹 2. NETTOYAGE DES LOGS EXISTANTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -X POST http://localhost:8000/api/rfid/clear-logs > /dev/null
echo -e "${GREEN}✅ Logs RFID vidés${NC}"

echo ""
echo "📤 3. TEST ENDPOINT /api/raspberry (format production)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Format exact du lecteur RFID portable
RESPONSE=$(curl -s -X PUT http://localhost:8000/api/raspberry \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"2000042","timestamp":1705234567.891}]')

echo "Réponse de l'API:"
echo "$RESPONSE" | jq '.'

echo ""

# Vérifier si la requête a réussi
SUCCESS=$(echo "$RESPONSE" | jq -r '.success // "false"')
ERROR=$(echo "$RESPONSE" | jq -r '.error // "none"')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Détection traitée avec succès${NC}"

    PROCESSED=$(echo "$RESPONSE" | jq -r '.processed')
    SKIPPED=$(echo "$RESPONSE" | jq -r '.skipped')

    echo -e "${BLUE}   Détections traitées: $PROCESSED${NC}"
    echo -e "${BLUE}   Détections ignorées: $SKIPPED${NC}"
else
    echo -e "${RED}❌ Erreur: $ERROR${NC}"

    if echo "$ERROR" | grep -q "not configured"; then
        echo -e "${YELLOW}   Le lecteur Serial: 200 n'est pas configuré ou inactif${NC}"
        echo -e "${BLUE}   Solution: Configurer le lecteur dans l'interface ChronoFront${NC}"
    fi
fi

echo ""
echo "📋 4. VÉRIFICATION DES LOGS RFID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

LOGS_COUNT=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.count')
echo -e "${BLUE}Logs enregistrés: $LOGS_COUNT${NC}"

if [ "$LOGS_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✅ La requête a été loguée pour rfidlive-ultra !${NC}"
    echo ""
    echo "Détails du log:"
    curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.logs[0] | "  ID: \(.id)\n  Serial Reader: \(.serial)\n  Méthode: \(.method)\n  Status: \(.status)\n  Timestamp: \(.timestamp)"'
else
    echo -e "${RED}❌ ERREUR: Aucun log enregistré${NC}"
    echo -e "${BLUE}   Le logging n'a pas fonctionné${NC}"
fi

echo ""
echo "📤 5. TEST DÉDUPLICATION (3x la même requête)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

for i in {1..3}; do
    curl -s -X PUT http://localhost:8000/api/raspberry \
      -H "Content-Type: application/json" \
      -H "Serial: 200" \
      -d '[{"serial":"2000099","timestamp":1705234599.123}]' > /dev/null
    echo "  Envoi $i/3..."
done

echo ""

LOGS_COUNT=$(curl -s http://localhost:8000/api/rfid/raw-logs | jq -r '.count')
echo -e "${BLUE}Logs après 3 envois identiques: $LOGS_COUNT${NC}"

# On devrait avoir 2 logs au total (1 du test 3, 1 du test 5)
# Les 2 autres envois du test 5 doivent être bloqués par déduplication
EXPECTED=2

if [ "$LOGS_COUNT" -eq "$EXPECTED" ]; then
    echo -e "${GREEN}✅ Déduplication fonctionne ! (2 détections en double bloquées)${NC}"
elif [ "$LOGS_COUNT" -gt "$EXPECTED" ]; then
    DUPLICATES=$((LOGS_COUNT - EXPECTED))
    echo -e "${RED}❌ Déduplication échouée: $DUPLICATES détections en double${NC}"
else
    echo -e "${YELLOW}⚠️  Résultat inattendu: $LOGS_COUNT logs (attendu: $EXPECTED)${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  📊 RÉSUMÉ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${BLUE}✅ Tests de l'endpoint production terminés${NC}"
echo -e "${BLUE}📺 Ouvrir: http://localhost:8000/rfidlive-ultra${NC}"
echo -e "${BLUE}📝 Logs Laravel: storage/logs/laravel.log${NC}"
echo ""

if [ "$SUCCESS" != "true" ]; then
    echo -e "${YELLOW}⚠️  IMPORTANT:${NC}"
    echo -e "${YELLOW}   Pour que ce test fonctionne complètement, il faut:${NC}"
    echo -e "${YELLOW}   1. Un lecteur RFID configuré avec Serial: 200${NC}"
    echo -e "${YELLOW}   2. Un coureur avec dossard 42 (serial 2000042)${NC}"
    echo -e "${YELLOW}   3. Le lecteur en statut 'active'${NC}"
    echo ""
fi
