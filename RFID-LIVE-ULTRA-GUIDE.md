# 📡 Guide RFID Live Ultra - ChronoFront

## ✅ Corrections Apportées

### Problème 1 : Détections RFID invisibles dans rfidlive-ultra

**Cause :** Le `RaspberryController` (endpoint principal `/api/raspberry`) ne loguait PAS les détections dans le cache `rfid_raw_logs`.

**Solution :**
- Ajout de `RfidLogController::logRequest()` dans `RaspberryController::store()`
- Toutes les requêtes RFID sont maintenant loguées (succès ET erreurs)
- Les détections apparaissent instantanément dans `/rfidlive-ultra`

### Problème 2 : Détections en double

**Cause :** Aucune déduplication des requêtes identiques envoyées rapidement par le lecteur.

**Solution :**
- Ajout de déduplication dans `RfidLogController::logRequest()`
- Fenêtre de 2 secondes : si la même requête (serial + data + status) arrive 2 fois, seule la 1ère est loguée
- Les duplicatas sont bloqués et loggés dans `laravel.log` pour debug

### Problème 3 : Rate limiting (429 errors)

**Cause :** Le polling (500ms = 120 req/min) dépassait la limite Laravel (60 req/min).

**Solution :**
- Désactivation du throttle middleware pour tous les endpoints RFID
- Le polling fonctionne sans erreur 429

---

## 🚀 Démarrage Rapide

### 1. Démarrer le serveur Laravel

```bash
cd ~/ChronoFront  # ou le chemin vers ton projet
php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000
```

**Important :** Utiliser `--host=0.0.0.0` pour que le serveur soit accessible depuis le réseau (pour le lecteur RFID).

### 2. Ouvrir l'interface de monitoring

Dans ton navigateur :
```
http://localhost:8000/rfidlive-ultra
```

Ou depuis un autre appareil sur le même réseau WiFi :
```
http://TON_IP:8000/rfidlive-ultra
```

(Remplace `TON_IP` par ton adresse IP réseau, ex: `192.168.1.148`)

---

## 🧪 Tests

### Test 1 : Déduplication avec /api/rfid/debug

```bash
chmod +x test-rfid-deduplication.sh
./test-rfid-deduplication.sh
```

Ce script :
- ✅ Vide les logs existants
- ✅ Envoie 1 détection unique → doit créer 1 log
- ✅ Envoie 3x la même détection → doit créer 1 seul log (2 bloquées)
- ✅ Envoie 2 détections différentes → doit créer 2 logs

**Résultat attendu :** La déduplication bloque les requêtes identiques dans une fenêtre de 2s.

### Test 2 : Endpoint production /api/raspberry

```bash
chmod +x test-raspberry-endpoint.sh
./test-raspberry-endpoint.sh
```

Ce script :
- ✅ Teste l'endpoint exact utilisé par le lecteur RFID
- ✅ Vérifie que les détections sont loguées pour rfidlive-ultra
- ✅ Teste la déduplication sur l'endpoint production

**Note :** Ce test nécessite un lecteur RFID configuré avec `Serial: 200` dans l'interface ChronoFront.

### Test 3 : Test manuel avec curl

Envoie une détection comme le ferait le lecteur RFID :

```bash
curl -X PUT http://localhost:8000/api/raspberry \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"2000042","timestamp":1705234567.891}]'
```

Puis ouvre `http://localhost:8000/rfidlive-ultra` → la détection doit apparaître instantanément.

---

## 📱 Configuration du Lecteur RFID Portable

### Dans l'application Android du lecteur :

- **URL :** `http://TON_IP:8000/api/raspberry`
  - Remplace `TON_IP` par ton IP réseau (ex: `192.168.1.148`)
- **Méthode :** PUT
- **Port :** 8000
- **Headers :** Déjà configurés automatiquement (`Serial: 200`)

### Vérification réseau

Si le lecteur ne peut pas se connecter, exécute le diagnostic réseau :

```bash
./test-network-mac.sh
```

Ce script vérifie :
- ✅ Ton IP réseau
- 🛡️ Le statut du pare-feu
- 🔌 Si le serveur écoute sur `0.0.0.0` (accessible réseau)
- 🧪 La connectivité locale et réseau

**Si le pare-feu bloque :**

```bash
# Désactiver temporairement (pour tester)
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off

# Ou autoriser PHP seulement
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --add /usr/bin/php
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --unblockapp /usr/bin/php
```

---

## 🎯 Endpoints API

### `/api/raspberry` (POST/PUT)
- **Usage :** Endpoint principal pour les lecteurs RFID
- **Headers :** `Serial: [reader_id]`, `Content-Type: application/json`
- **Body :** `[{"serial":"2000042","timestamp":1705234567.891}]`
- **Réponse :** `{"success":true,"processed":1,"skipped":0,...}`

### `/api/rfid/detections` (POST/PUT)
- **Alias** de `/api/raspberry` (même fonctionnement)

### `/api/rfid/debug` (ANY)
- **Usage :** Endpoint de debug, accepte tout type de requête
- **Utile pour :** Tests manuels, vérifier que le serveur répond
- **Toujours** logue la requête dans rfidlive-ultra

### `/api/rfid/raw-logs` (GET)
- **Usage :** Récupère les logs RFID pour rfidlive-ultra
- **Paramètres :** `?since=[id]` pour récupérer seulement les nouveaux logs
- **Utilisé par :** Le polling JavaScript de rfidlive-ultra

### `/api/rfid/clear-logs` (POST)
- **Usage :** Vide tous les logs RFID du cache
- **Utile pour :** Nettoyer avant un test

---

## 📊 Interface rfidlive-ultra

### Fonctionnalités

- ✅ **Affichage en temps réel** des détections RFID (polling 500ms)
- ✅ **Statut de connexion** : Vert = connecté, Rouge = déconnecté
- ✅ **Compteur de détections** : Nombre total depuis le démarrage
- ✅ **Taux de détection** : Détections par seconde
- ✅ **Pause/Reprendre** : Mettre en pause l'affichage sans perdre les données
- ✅ **Vider** : Nettoyer l'affichage (ne supprime pas le cache serveur)
- ✅ **Timestamps haute précision** : HH:MM:SS.mmm (millisecondes)

### Affichage

Chaque ligne montre :
- **[TAG]** : Le numéro de série RFID (ex: `2000042` → dossard 42)
- **[TIME]** : Horodatage précis de la détection
- **[SERIAL]** : ID du lecteur RFID (ex: `200`)

### Limite de performance

- **MAX_LOGS = 50** : Seules les 50 dernières détections sont affichées
- **Polling = 500ms** : 2 requêtes par seconde
- **Pas de rate limiting** : Le throttle Laravel est désactivé

---

## 🔍 Debug & Logs

### Logs Laravel

Détections RFID, erreurs, duplicatas bloqués :

```bash
tail -f storage/logs/laravel.log
```

Recherche des logs de déduplication :

```bash
grep "duplicate detection blocked" storage/logs/laravel.log
```

### Vider le cache RFID

```bash
curl -X POST http://localhost:8000/api/rfid/clear-logs
```

### Test de santé du serveur

```bash
curl http://localhost:8000/api/health
```

---

## ⚙️ Architecture

### Flux de détection

```
Lecteur RFID Portable (Serial: 200)
         |
         | PUT http://IP:8000/api/raspberry
         | Header: Serial: 200
         | Body: [{"serial":"2000042","timestamp":1705234567.891}]
         |
         v
RaspberryController::store()
         |
         +---> Validation (Serial header, Reader config)
         |
         +---> Anti-rebounce (évite détections trop rapprochées)
         |
         +---> Création Result en base de données
         |
         +---> RfidLogController::logRequest()  ← NOUVEAU !
                     |
                     +---> Déduplication (2s window)  ← NOUVEAU !
                     |
                     +---> Cache::put('rfid_raw_logs', ...)
                     |
                     v
                Browser polling (500ms)
                     |
                     | GET /api/rfid/raw-logs?since=X
                     |
                     v
                rfidlive-ultra affiche la détection
```

### Déduplication

Deux niveaux de protection contre les duplicatas :

1. **Anti-rebounce (RaspberryController)** :
   - Empêche les détections trop rapprochées **du même coureur** sur le **même lecteur**
   - Fenêtre configurable (défaut: 5s pour 1_passage, 3s pour n_laps/infinite_loop)
   - Protection au niveau **base de données**

2. **Déduplication (RfidLogController)** :
   - Empêche les **requêtes HTTP identiques** (même serial, data, status)
   - Fenêtre fixe: 2 secondes
   - Protection au niveau **affichage** (cache rfid_raw_logs)

---

## 📝 Changelog

### [2025-01-14] - Fix RFID Logging & Deduplication

**Ajouté :**
- Logging automatique dans `RaspberryController::store()`
- Déduplication dans `RfidLogController::logRequest()` (fenêtre 2s)
- Scripts de test : `test-rfid-deduplication.sh`, `test-raspberry-endpoint.sh`
- Logging des erreurs (Serial manquant, Reader non trouvé)

**Corrigé :**
- Les détections RFID apparaissent maintenant dans rfidlive-ultra
- Les détections en double sont bloquées
- Rate limiting 429 errors (throttle désactivé)

**Technique :**
- Import `RfidLogController` dans `RaspberryController`
- Appel `logRequest()` après traitement des détections
- Boucle de déduplication avec comparaison serial + data + status
- Logging des duplicatas bloqués dans `laravel.log`

---

## 🆘 Dépannage

### Problème : Aucune détection ne s'affiche

**Solutions :**
1. Vérifier que le serveur tourne : `lsof -i :8000`
2. Vérifier que le lecteur est configuré : Réglages > Lecteurs RFID
3. Vérifier les logs Laravel : `tail -f storage/logs/laravel.log`
4. Tester manuellement : `./test-rfid-deduplication.sh`

### Problème : Détections en double

**Vérifications :**
1. La déduplication est-elle active ? → Chercher "duplicate detection blocked" dans les logs
2. Les duplicatas arrivent-ils à > 2s d'intervalle ? → Augmenter la fenêtre dans le code
3. Le lecteur renvoie-t-il vraiment les mêmes données ? → Comparer les requêtes dans les logs

### Problème : Le lecteur ne peut pas se connecter

**Solutions :**
1. Exécuter `./test-network-mac.sh` pour diagnostiquer
2. Vérifier que le serveur écoute sur `0.0.0.0` (pas `localhost`)
3. Désactiver le pare-feu macOS temporairement
4. Vérifier l'URL configurée dans le lecteur : `http://IP:8000/api/raspberry`

### Problème : Erreur 429 (Too Many Requests)

**Normalement corrigé**, mais si ça arrive encore :
1. Vérifier que `routes/api.php` contient `Route::withoutMiddleware(['throttle'])`
2. Redémarrer le serveur
3. Vider le cache : `php artisan cache:clear`

---

## 📚 Références

- **Interface :** `/rfidlive-ultra`
- **API Docs :** Voir les commentaires dans `routes/api.php`
- **Tests :** `test-rfid-deduplication.sh`, `test-raspberry-endpoint.sh`
- **Debug réseau :** `test-network-mac.sh`, `DEBUG-MAC.md`
- **Code source :**
  - `app/Http/Controllers/Api/RaspberryController.php` (endpoint principal)
  - `app/Http/Controllers/Api/RfidLogController.php` (logging & déduplication)
  - `resources/views/chronofront/rfidlive-ultra.blade.php` (interface)

---

**Version :** 2.0 - 2025-01-14
**Branche :** `claude/pi-installation-01MRE3dhTfCMPgNFb14p1xsq`
