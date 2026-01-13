# Configuration MacBook pour Lecteurs RFID

Guide pour connecter vos lecteurs RFID à votre MacBook en développement.

## Étape 1 : Trouver votre adresse IP locale

### Méthode automatique (recommandé)

```bash
./get-local-ip.sh
```

Vous verrez quelque chose comme :
```
📶 WiFi (en0): 192.168.1.42

✅ Configurez vos lecteurs avec cette URL:
   http://192.168.1.42:8000/api/raspberry
```

### Méthode manuelle

#### Option A : Interface graphique
1. Cliquez sur l'icône WiFi dans la barre de menus
2. Ouvrez "Préférences Système" > "Réseau"
3. Sélectionnez votre connexion active (WiFi ou Ethernet)
4. Votre IP est affichée : `Adresse IP : 192.168.1.42`

#### Option B : Terminal
```bash
# WiFi
ipconfig getifaddr en0

# Ethernet (si connecté)
ipconfig getifaddr en1

# Ou voir toutes les interfaces
ifconfig | grep "inet " | grep -v 127.0.0.1
```

## Étape 2 : Lancer les serveurs sur toutes les interfaces

**IMPORTANT** : Les serveurs doivent écouter sur `0.0.0.0` (toutes les interfaces) et pas seulement `localhost` pour être accessibles depuis le réseau.

### Vérifier que le script lance bien sur 0.0.0.0

Ouvrez `start-dev-servers.sh` et vérifiez :
```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan serve --host=0.0.0.0 --port=8001
```

✅ Le `--host=0.0.0.0` est **déjà présent** dans le script !

### Lancer les serveurs

```bash
./start-dev-servers.sh
```

Vous devriez voir :
```
[1] Starting Main Server on port 8000...
[2] Starting SSE Server on port 8001...

SERVERS RUNNING!
Main App:    http://localhost:8000
```

## Étape 3 : Vérifier le pare-feu macOS

Par défaut, macOS peut bloquer les connexions entrantes.

### Désactiver temporairement le pare-feu (pour test)

1. Ouvrez "Préférences Système"
2. Allez dans "Sécurité et confidentialité"
3. Onglet "Pare-feu"
4. Cliquez sur le cadenas 🔒 et entrez votre mot de passe
5. Cliquez sur "Désactiver le pare-feu" (temporaire, pour test)

### Ou autoriser PHP spécifiquement (recommandé)

1. Pare-feu activé
2. "Options du pare-feu..."
3. Cliquez sur "+" et ajoutez `/usr/bin/php`
4. Choisissez "Autoriser les connexions entrantes"

## Étape 4 : Configurer vos lecteurs RFID

Utilisez l'IP trouvée à l'étape 1 :

```
URL:     http://[VOTRE_IP]:8000/api/raspberry
Method:  POST
Header:  Serial: [NUMERO_LECTEUR]
Body:    JSON array

Exemple:
URL:     http://192.168.1.42:8000/api/raspberry
Header:  Serial: 120
```

## Étape 5 : Test depuis un autre appareil

### Test depuis votre téléphone ou autre PC

Ouvrez un navigateur et allez sur :
```
http://[VOTRE_IP]:8000/rfidlive-ultra
```

Si la page charge, c'est que le serveur est accessible ! ✅

### Test avec curl (depuis un autre terminal)

```bash
curl http://[VOTRE_IP]:8000/api/raspberry \
  -X POST \
  -H "Serial: 120" \
  -d '[{"serial":"2000001","timestamp":1673524800}]'
```

Devrait retourner :
```json
{"success":true,"message":"Detection received..."}
```

## Étape 6 : Tester avec votre lecteur réel

1. Configurez le lecteur avec l'URL complète
2. Assurez-vous que le lecteur et le MacBook sont sur le **même réseau WiFi**
3. Passez une puce devant le lecteur
4. Vérifiez dans `/rfidlive-ultra` que la détection apparaît

## Problèmes fréquents

### ❌ Connection refused

**Cause** : Le serveur n'écoute pas sur `0.0.0.0`

**Solution** :
```bash
# Vérifier que le serveur écoute bien sur toutes les interfaces
lsof -i :8000
```

Devrait afficher :
```
php     12345 user   6u  IPv4 0x... 0.0.0.0:8000 (LISTEN)
```

Si vous voyez `127.0.0.1:8000` au lieu de `0.0.0.0:8000`, relancez avec :
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### ❌ Connection timeout

**Cause** : Pare-feu macOS bloque les connexions

**Solutions** :
1. Désactivez temporairement le pare-feu (voir Étape 3)
2. Ou autorisez PHP dans les exceptions du pare-feu

### ❌ Lecteur et MacBook pas sur le même réseau

**Cause** : Le lecteur est sur un réseau, le MacBook sur un autre

**Solution** :
1. Vérifiez que les deux sont sur le même WiFi
2. Ou utilisez un câble Ethernet entre les deux
3. Ou configurez un partage de connexion

### ❌ IP change à chaque redémarrage

**Cause** : DHCP attribue une IP différente

**Solution** : Configurez une IP statique sur le MacBook

1. Préférences Système > Réseau
2. WiFi > Avancé > TCP/IP
3. Configurer IPv4 : "Manuellement"
4. IP : `192.168.1.42` (choisissez une IP libre)
5. Masque : `255.255.255.0`
6. Routeur : `192.168.1.1` (votre box internet)

## Configuration complète exemple

### MacBook
```
IP WiFi:  192.168.1.42
Serveur:  php artisan serve --host=0.0.0.0 --port=8000
Pare-feu: PHP autorisé ou désactivé
```

### Lecteur RFID 120
```
URL:     http://192.168.1.42:8000/api/raspberry
Serial:  120
Method:  POST
Format:  JSON
```

### Test
```bash
# Depuis le MacBook
curl http://192.168.1.42:8000/api/raspberry \
  -X POST \
  -H "Serial: 120" \
  -d '[{"serial":"2000003","timestamp":1673524800}]'

# Devrait apparaître instantanément dans:
http://192.168.1.42:8000/rfidlive-ultra
```

## Réseau recommandé

### Option 1 : Même WiFi (le plus simple)
```
[MacBook WiFi] ←→ [Box Internet] ←→ [Lecteur WiFi]
192.168.1.42                        192.168.1.140
```

### Option 2 : Câble Ethernet direct
```
[MacBook en1] ←──── Ethernet ────→ [Lecteur]
192.168.2.1                         192.168.2.100
```

Nécessite de configurer un réseau sur l'interface Ethernet du MacBook.

### Option 3 : Partage de connexion MacBook
```
[MacBook WiFi] ←→ [Internet]
     ↓
[Partage] → [Lecteur se connecte au hotspot MacBook]
```

1. Préférences Système > Partage
2. Cochez "Partage Internet"
3. Partager depuis : WiFi
4. Vers : Ethernet ou autre interface

## Commandes utiles

```bash
# Voir toutes les IP du MacBook
ifconfig | grep "inet " | grep -v 127.0.0.1

# Voir qui écoute sur le port 8000
lsof -i :8000

# Tester si le serveur répond
curl http://localhost:8000

# Voir les logs du serveur Laravel
tail -f storage/logs/laravel.log

# Vider le cache RFID
curl http://localhost:8000/api/rfid/clear-logs
```

## URL finale pour vos lecteurs

Une fois que vous avez trouvé votre IP (ex: `192.168.1.42`), configurez vos lecteurs avec :

```
┌─────────────────────────────────────────────────┐
│  URL: http://192.168.1.42:8000/api/raspberry    │
│  Method: POST                                   │
│  Header: Serial: [VOTRE_NUMERO]                 │
│  Content-Type: application/json                 │
│  Body: [{"serial":"200XXXX","timestamp":...}]   │
└─────────────────────────────────────────────────┘
```

**Remplacez `192.168.1.42` par VOTRE IP trouvée avec `./get-local-ip.sh` !**

---

💡 **Astuce** : Une fois que tout fonctionne, notez votre configuration quelque part pour ne pas avoir à tout refaire à chaque session de dev !
