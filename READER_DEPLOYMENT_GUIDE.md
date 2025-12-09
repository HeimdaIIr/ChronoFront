# 📡 Guide de déploiement des lecteurs RFID - Location sans configuration

## 🎯 Objectif

Permettre au client de **brancher le lecteur 4G et qu'il fonctionne immédiatement** sans configuration manuelle.

---

## 🔍 Architecture actuelle du lecteur

D'après l'interface web du lecteur (capture d'écran fournie), chaque lecteur peut envoyer vers **4 URLs simultanément** :

- **Upload 1** : URL locale (test)
- **Upload 2** : ⚠️ **SLOT LIBRE** - À utiliser pour ChronoFront
- **Upload 3** : chrono.ats-sport.com
- **Upload 4** : live.pointcourse.com

**Méthode HTTP** : `PUT` (pas POST)
**Anti-rebounce** : Configurable (ex: 1 seconde)

---

## ✅ Solution 1 : Pré-configuration manuelle (RECOMMANDÉE)

### 👉 La plus simple et la plus fiable

**Avant de louer le matériel :**

### Étape 1 : Accéder à l'interface du lecteur

Via VPN ou en local :
```
http://10.8.0.120/
ou
http://192.168.10.170/  (si en local)
```

**Identifiants** : Utilisez les credentials HTTP que vous connaissez

### Étape 2 : Configurer Upload 2

Dans l'interface web du lecteur :

```
☑️ Enable Upload 2 module
Upload 2 URL: https://votre-domaine.com/api/raspberry
Method 2: PUT
```

**Exemple concret :**
```
Upload 2 URL: https://chronofront.com/api/raspberry
```

### Étape 3 : Sauvegarder et redémarrer

Cliquez sur "Save" / "Enregistrer" dans l'interface.

### Étape 4 : Tester l'envoi

Passez un badge RFID devant le lecteur et vérifiez que les données arrivent dans ChronoFront.

---

## ✅ Configuration côté ChronoFront (une seule fois)

### 1. Créer l'événement

Dans ChronoFront, créer votre événement (ex: "Trail des 3 Sommets")

### 2. Ajouter le lecteur

```
Serial: 120
Type de réseau: VPN ATS Sport
Emplacement: KM10
Distance depuis départ: 10 km
```

### 3. Activer le lecteur

Cocher "Actif" pour que le lecteur puisse envoyer des données.

---

## 🎁 Pour le client final

**Instructions pour le client (ultra-simple) :**

1. ✅ Brancher le lecteur 4G à l'alimentation
2. ✅ Attendre 30 secondes (démarrage + connexion 4G)
3. ✅ Dans ChronoFront, créer l'événement avec le serial du lecteur (120)
4. ✅ Passer des badges devant le lecteur
5. ✅ Les détections apparaissent automatiquement dans ChronoFront ! 🎉

**Aucune configuration IP, aucune interface web, rien !**

---

## 🚀 Solution 2 : Auto-configuration via endpoint (AVANCÉ)

Si vous souhaitez que le lecteur se reconfigure automatiquement au démarrage.

### Endpoint disponible

ChronoFront expose maintenant :

```http
GET https://chronofront.com/api/raspberry/config
Header: Serial: 120
```

**Réponse :**
```json
{
  "target_url": "https://chronofront.com/api/raspberry",
  "target_method": "PUT",
  "serial": "120",
  "event_id": 5,
  "event_name": "Trail des 3 Sommets",
  "location": "KM10",
  "anti_rebounce_seconds": 5,
  "configured_at": "2025-12-09T10:30:00Z"
}
```

### Script de démarrage sur le Raspberry Pi

Créer `/etc/rc.local` ou un service systemd :

```bash
#!/bin/bash
# Auto-configuration au démarrage

SERIAL=$(cat /etc/rfid-reader/serial.txt)
CONFIG_URL="https://chronofront.com/api/raspberry/config"

# Attendre connexion 4G
sleep 30

# Récupérer la configuration
CONFIG=$(curl -s -H "Serial: $SERIAL" "$CONFIG_URL")

if [ $? -eq 0 ]; then
    TARGET_URL=$(echo "$CONFIG" | jq -r '.target_url')

    # Mettre à jour le fichier de config du lecteur
    # (adapter selon votre système)
    sed -i "s|UPLOAD2_URL=.*|UPLOAD2_URL=$TARGET_URL|g" /etc/rfid-reader/config
    sed -i "s|UPLOAD2_ENABLED=.*|UPLOAD2_ENABLED=true|g" /etc/rfid-reader/config

    # Redémarrer le service
    systemctl restart rfid-reader

    logger "✅ Lecteur $SERIAL configuré automatiquement"
else
    logger "⚠️ Échec auto-config, utilisation config par défaut"
fi
```

**Avantages :**
- Le lecteur se reconfigure à chaque démarrage
- Toujours la bonne URL même si elle change
- Récupère l'anti-rebounce depuis ChronoFront

---

## 🔧 Solution 3 : Configuration via SSH (BATCH)

Si vous avez accès SSH aux lecteurs, utilisez le script fourni :

```bash
cd /home/user/ChronoFront/scripts
chmod +x configure_reader.sh

# Configurer le lecteur 120
./configure_reader.sh 120 chronofront.com

# Configurer plusieurs lecteurs
for SERIAL in 107 112 120; do
    ./configure_reader.sh $SERIAL chronofront.com
done
```

**Prérequis :**
- Accès SSH aux Raspberry Pi
- Connaissance de l'emplacement du fichier de config sur le Pi

---

## 📊 Comparatif des solutions

| Solution | Complexité | Fiabilité | Plug & Play client |
|----------|------------|-----------|-------------------|
| **Solution 1 (Manuelle)** | ⭐ Très simple | ⭐⭐⭐ Excellente | ✅ Total |
| **Solution 2 (Pull auto)** | ⭐⭐⭐ Avancée | ⭐⭐ Bonne | ✅ Total |
| **Solution 3 (SSH batch)** | ⭐⭐ Moyenne | ⭐⭐⭐ Excellente | ✅ Total |

---

## 🎯 Recommandation

**Pour la location de matériel :** Utilisez la **Solution 1** (pré-configuration manuelle)

**Pourquoi ?**
- ✅ Configuration une seule fois avant location
- ✅ Aucun script à maintenir sur les Raspberry Pi
- ✅ Fonctionne même si ChronoFront est temporairement hors ligne
- ✅ Zéro risque de bug d'auto-configuration
- ✅ Le client n'a RIEN à faire

**Workflow idéal :**

1. **Vous** : Pré-configurer tous vos lecteurs avec Upload 2 → ChronoFront
2. **Client** : Brancher le lecteur → Créer l'événement dans ChronoFront → Ça marche ! 🎉

---

## 🧪 Tests de validation

### Test 1 : Vérifier la réception des données

```bash
# Sur votre serveur ChronoFront
tail -f storage/logs/laravel.log | grep raspberry
```

Passez un badge devant le lecteur, vous devriez voir :
```
[2025-12-09 10:30:45] Received RFID detection from reader 120: TAG_ABC123
```

### Test 2 : Vérifier l'endpoint de config

```bash
curl -H "Serial: 120" https://chronofront.com/api/raspberry/config
```

Devrait retourner la configuration du lecteur.

---

## 🆘 Troubleshooting

### Problème : Le lecteur n'envoie rien

**Vérifications :**
1. ✅ Le lecteur est branché et allumé (LED verte ?)
2. ✅ La 4G fonctionne (LED bleue ?)
3. ✅ Upload 2 est **coché** dans l'interface
4. ✅ L'URL est correcte (https, pas http)
5. ✅ Le lecteur est **activé** dans ChronoFront

**Test manuel :**
```bash
# Simuler un envoi depuis le lecteur
curl -X PUT https://chronofront.com/api/raspberry \
  -H "Serial: 120" \
  -H "Content-Type: application/json" \
  -d '{
    "tag": "TEST123",
    "time": "2025-12-09T10:30:00Z"
  }'
```

### Problème : Erreur 403 sur le ping

**C'est NORMAL !** Le ping HTTP est bloqué par le proxy VPN Envoy.
Le ping sert juste à vérifier la connectivité réseau.
**Les données RFID passent quand même !**

---

## 📞 Questions ?

Si vous avez des questions sur :
- L'emplacement du fichier de config sur le Raspberry Pi
- La structure du JSON de config du lecteur
- Comment tester l'envoi des données
- L'intégration avec votre système existant

N'hésitez pas à demander !
