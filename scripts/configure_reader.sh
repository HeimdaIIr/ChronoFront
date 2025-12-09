#!/bin/bash
# Script de configuration automatique des lecteurs RFID
# Usage: ./configure_reader.sh 120 chronofront.com

SERIAL=$1
CHRONOFRONT_URL=$2

if [ -z "$SERIAL" ] || [ -z "$CHRONOFRONT_URL" ]; then
    echo "Usage: $0 <serial> <chronofront_url>"
    echo "Example: $0 120 chronofront.com"
    exit 1
fi

READER_IP="10.8.0.${SERIAL}"
CONFIG_FILE="/etc/rfid-reader/config.json"  # À adapter selon votre système

echo "🔧 Configuration du lecteur ${SERIAL} (${READER_IP})..."

# Se connecter en SSH et modifier la config
ssh pi@${READER_IP} << EOF
    # Backup de la config actuelle
    cp ${CONFIG_FILE} ${CONFIG_FILE}.backup

    # Activer Upload 2 avec l'URL ChronoFront
    jq '.upload2.enabled = true |
        .upload2.url = "https://${CHRONOFRONT_URL}/api/raspberry" |
        .upload2.method = "PUT"' ${CONFIG_FILE} > ${CONFIG_FILE}.tmp

    mv ${CONFIG_FILE}.tmp ${CONFIG_FILE}

    # Redémarrer le service
    sudo systemctl restart rfid-reader

    echo "✅ Configuration mise à jour"
EOF

echo "✅ Lecteur ${SERIAL} configuré pour envoyer à ${CHRONOFRONT_URL}"
