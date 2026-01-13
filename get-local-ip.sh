#!/bin/bash

echo "========================================="
echo "  Adresses IP de votre MacBook"
echo "========================================="
echo ""

# WiFi (en0)
WIFI_IP=$(ifconfig en0 2>/dev/null | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
if [ ! -z "$WIFI_IP" ]; then
    echo "📶 WiFi (en0): $WIFI_IP"
fi

# Ethernet (en1, en2, etc.)
ETH_IP=$(ifconfig en1 2>/dev/null | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
if [ ! -z "$ETH_IP" ]; then
    echo "🔌 Ethernet (en1): $ETH_IP"
fi

# VPN (utun0, tun0, etc.)
VPN_IP=$(ifconfig utun0 2>/dev/null | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
if [ ! -z "$VPN_IP" ]; then
    echo "🔒 VPN (utun0): $VPN_IP"
fi

echo ""
echo "========================================="
echo "  URL pour vos lecteurs RFID"
echo "========================================="
echo ""

# Utiliser la première IP trouvée (WiFi prioritaire)
MAIN_IP=$WIFI_IP
if [ -z "$MAIN_IP" ]; then
    MAIN_IP=$ETH_IP
fi

if [ ! -z "$MAIN_IP" ]; then
    echo "✅ Configurez vos lecteurs avec cette URL:"
    echo ""
    echo "   http://$MAIN_IP:8000/api/raspberry"
    echo ""
    echo "Header: Serial: [VOTRE_NUMERO_LECTEUR]"
    echo "Method: POST"
    echo "Body: JSON array avec les détections"
    echo ""
else
    echo "❌ Aucune adresse IP trouvée!"
    echo "Vérifiez que vous êtes connecté au réseau."
fi

echo "========================================="
echo ""
