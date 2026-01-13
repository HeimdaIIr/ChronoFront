#!/bin/bash

echo "🔍 DIAGNOSTIC RÉSEAU - ChronoFront RFID"
echo "========================================"
echo ""

echo "📍 1. ADRESSES IP DISPONIBLES:"
echo "--------------------------------"
echo "Interface en0 (Wi-Fi généralement):"
ipconfig getifaddr en0 2>/dev/null || echo "  ❌ Pas d'IP sur en0"

echo ""
echo "Interface en1:"
ipconfig getifaddr en1 2>/dev/null || echo "  ❌ Pas d'IP sur en1"

echo ""
echo "Toutes les interfaces:"
ifconfig 2>/dev/null | grep -A 1 "flags=" | grep "inet " || echo "  ⚠️  ifconfig non disponible"

echo ""
echo "📡 2. PARE-FEU macOS:"
echo "--------------------------------"
echo "Statut du pare-feu:"
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate 2>/dev/null || echo "  ⚠️  Nécessite sudo pour vérifier"

echo ""
echo "Applications autorisées:"
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --listapps 2>/dev/null | grep -i php || echo "  ⚠️  PHP n'est pas dans la liste"

echo ""
echo "🔌 3. PORTS EN ÉCOUTE:"
echo "--------------------------------"
echo "Port 8000:"
lsof -i :8000 2>/dev/null || echo "  ❌ Aucun processus sur le port 8000"

echo ""
echo "Port 8001:"
lsof -i :8001 2>/dev/null || echo "  ❌ Aucun processus sur le port 8001"

echo ""
echo "Tous les ports PHP:"
lsof -i -P | grep php || echo "  ❌ Aucun processus PHP en écoute"

echo ""
echo "🧪 4. TEST DE CONNECTIVITÉ:"
echo "--------------------------------"
IP=$(ipconfig getifaddr en0 2>/dev/null)
if [ -n "$IP" ]; then
    echo "Test depuis localhost:"
    curl -s -o /dev/null -w "  Status: %{http_code}\n" http://localhost:8000/api/rfid/debug 2>/dev/null || echo "  ❌ Serveur non accessible sur localhost"

    echo ""
    echo "Test depuis IP réseau ($IP):"
    curl -s -o /dev/null -w "  Status: %{http_code}\n" http://$IP:8000/api/rfid/debug 2>/dev/null || echo "  ❌ Serveur non accessible depuis $IP"
else
    echo "  ⚠️  Impossible de détecter l'IP pour les tests"
fi

echo ""
echo "✅ 5. INSTRUCTIONS PARE-FEU macOS:"
echo "--------------------------------"
echo "Option 1 - Via Interface graphique:"
echo "  1. Ouvrir 'Réglages Système' (System Settings)"
echo "  2. Aller dans 'Réseau' (Network)"
echo "  3. Cliquer sur 'Pare-feu' (Firewall)"
echo "  4. Déverrouiller avec votre mot de passe"
echo "  5. Désactiver temporairement OU ajouter PHP aux apps autorisées"
echo ""
echo "Option 2 - Via ligne de commande (nécessite mot de passe):"
echo "  sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off"
echo ""
echo "Option 3 - Autoriser PHP uniquement:"
echo "  sudo /usr/libexec/ApplicationFirewall/socketfilterfw --add /usr/bin/php"
echo "  sudo /usr/libexec/ApplicationFirewall/socketfilterfw --unblockapp /usr/bin/php"
echo ""
echo "📝 6. COMMANDES POUR DÉMARRER LE SERVEUR:"
echo "--------------------------------"
if [ -n "$IP" ]; then
    echo "Serveur principal (accessible sur le réseau):"
    echo "  php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000"
    echo ""
    echo "Configuration du lecteur RFID:"
    echo "  URL à configurer: http://$IP:8000/api/rfid/debug"
    echo "  Méthode: PUT"
    echo "  Header: Serial: 200"
    echo ""
    echo "Page de test:"
    echo "  http://$IP:8000/rfidlive-ultra"
else
    echo "  ⚠️  Détectez d'abord votre IP réseau"
fi

echo ""
echo "========================================"
echo "Diagnostic terminé."
