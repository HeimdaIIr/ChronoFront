# Dépannage : Aucune détection affichée

Guide étape par étape pour diagnostiquer pourquoi les détections n'apparaissent pas dans `/rfidlive-ultra`.

## Étape 1 : Vérifier la configuration

```bash
./check-config.sh
```

Ce script vérifie :
- ✅ Fichier `.env` existe
- ✅ `SSE_SERVER_URL` est défini
- ✅ Les 2 serveurs tournent (ports 8000 et 8001)
- ✅ Adresses IP disponibles
- ✅ Répertoire de cache accessible
- ✅ Endpoint debug répond

**Si des ❌ apparaissent**, suivez les instructions affichées.

## Étape 2 : Tester manuellement l'endpoint

```bash
./test-manual.sh
```

Ce script envoie une détection de TEST vers `localhost:8000/api/rfid/debug`.

**Résultat attendu** :
```json
{
  "success": true,
  "message": "Debug data logged successfully"
}
```

## Étape 3 : Vérifier que rfidlive-ultra reçoit la détection

1. **Ouvrez dans votre navigateur** :
   ```
   http://localhost:8000/rfidlive-ultra
   ```

2. **Vérifiez le statut de connexion** (en haut à droite) :
   - ✅ **● Connecté** (vert) → SSE fonctionne
   - ❌ **● Déconnecté** (rouge) → SSE ne fonctionne pas

3. **Si "Déconnecté"**, ouvrez la console du navigateur (F12) :
   - Cherchez des erreurs de type "EventSource failed"
   - Vérifiez l'URL de connexion SSE

4. **Relancez le test** :
   ```bash
   ./test-manual.sh
   ```

5. **Dans rfidlive-ultra**, vous DEVRIEZ voir apparaître** :
   ```
   [TEST_MANUAL] [14:32:15.123] [200]
   ```

### ✅ Si la détection TEST apparaît

**Excellent !** L'endpoint fonctionne, le SSE fonctionne.

Le problème vient de votre **lecteur portable** :
- Il n'envoie pas de requêtes
- Ou il envoie vers une mauvaise URL
- Ou il n'est pas sur le même réseau

→ **Passez à l'Étape 4**

### ❌ Si la détection TEST n'apparaît PAS

Le problème vient du **serveur ou du SSE** :
- Les serveurs ne tournent pas correctement
- Le cache ne fonctionne pas
- Le SSE ne se connecte pas

→ **Passez aux Solutions ci-dessous**

## Étape 4 : Tester depuis un autre appareil (téléphone)

Pour vérifier que le serveur est accessible depuis le réseau :

1. **Trouvez votre IP MacBook** :
   ```bash
   ./get-local-ip.sh
   ```
   Exemple : `192.168.1.42`

2. **Sur votre téléphone** (connecté au même WiFi), ouvrez :
   ```
   http://192.168.1.42:8000/rfidlive-ultra
   ```

3. **Depuis votre Mac**, envoyez une détection de test :
   ```bash
   ./test-manual.sh
   ```

4. **Sur votre téléphone**, la détection devrait apparaître !

### ✅ Si ça fonctionne depuis le téléphone

Votre serveur est accessible depuis le réseau !

**Configurez maintenant votre lecteur portable** :
```
URL: http://192.168.1.42:8000/api/rfid/debug
```

### ❌ Si ça ne fonctionne pas depuis le téléphone

Le serveur n'est **pas accessible depuis le réseau**.

**Causes possibles** :
- Pare-feu macOS bloque
- Serveur n'écoute pas sur `0.0.0.0`
- Téléphone pas sur le même réseau

→ **Voir solutions ci-dessous**

## Solutions aux problèmes courants

### Problème : SSE déconnecté (● rouge)

**Symptôme** : Dans `/rfidlive-ultra`, le statut affiche "● Déconnecté" (rouge)

**Causes** :
1. Le serveur sur port 8001 ne tourne pas
2. La variable `SSE_SERVER_URL` n'est pas dans `.env`
3. Le navigateur ne peut pas se connecter au port 8001

**Solutions** :

```bash
# 1. Vérifier que port 8001 écoute
lsof -i :8001

# Si rien n'apparaît, relancer les serveurs
./start-dev-servers.sh

# 2. Vérifier .env
grep SSE_SERVER_URL .env

# Si vide, ajouter :
echo "SSE_SERVER_URL=http://localhost:8001" >> .env

# 3. Tester manuellement le SSE
curl http://localhost:8001/api/rfid/live-stream

# Devrait afficher : retry: 1000
```

**Puis rechargez** `http://localhost:8000/rfidlive-ultra`

### Problème : Pare-feu bloque les connexions

**Symptôme** : `test-manual.sh` fonctionne, mais le lecteur ne peut pas envoyer

**Solution** :

1. **Désactiver temporairement le pare-feu** :
   - Préférences Système → Sécurité et confidentialité
   - Onglet "Pare-feu"
   - Cliquer sur le cadenas 🔒
   - "Désactiver le pare-feu"

2. **Ou autoriser PHP** :
   - Pare-feu → Options
   - Ajouter `/usr/bin/php`
   - Autoriser les connexions entrantes

### Problème : Serveur n'écoute pas sur 0.0.0.0

**Symptôme** : `localhost` fonctionne, mais pas depuis le réseau

**Vérification** :
```bash
lsof -i :8000
```

Devrait afficher :
```
php ... *:8000 (LISTEN)
```

Si vous voyez `127.0.0.1:8000` au lieu de `*:8000`, le serveur n'écoute que localement.

**Solution** :
```bash
# Tuer les serveurs
killall php

# Relancer avec --host=0.0.0.0
./start-dev-servers.sh
```

Le script `start-dev-servers.sh` utilise déjà `--host=0.0.0.0`, donc ça devrait fonctionner.

### Problème : Cache ne fonctionne pas

**Symptôme** : Le test manuel dit "success" mais rien n'apparaît dans rfidlive

**Vérification** :
```bash
# Vérifier que le cache est accessible
ls -la storage/framework/cache/

# Vérifier les permissions
ls -ld storage/framework/cache/
```

**Solution** :
```bash
# Réparer les permissions
chmod -R 775 storage/
chown -R $(whoami) storage/

# Vider le cache
php artisan cache:clear

# Réessayer
./test-manual.sh
```

### Problème : Lecteur pas sur le même réseau

**Symptôme** : Tout fonctionne en local, mais le lecteur ne se connecte pas

**Vérification** :
```bash
# 1. Quelle est l'IP du MacBook ?
./get-local-ip.sh

# 2. Quelle est l'IP du lecteur ?
# (regarder sur l'écran du lecteur ou dans sa config)

# 3. Sont-elles dans la même plage ?
# Exemple :
#   MacBook : 192.168.1.42
#   Lecteur : 192.168.1.140
#   → Même réseau ✅
#
#   MacBook : 192.168.1.42
#   Lecteur : 10.0.0.50
#   → Réseaux différents ❌
```

**Solution** :
- Connecter le lecteur au même WiFi que le MacBook
- Ou créer un hotspot WiFi sur le MacBook et y connecter le lecteur

### Problème : Lecteur envoie vers la mauvaise URL

**Vérification** :

Dans la configuration du lecteur, l'URL doit être **EXACTEMENT** :
```
http://[VOTRE_IP]:8000/api/rfid/debug
```

**Erreurs courantes** :
- ❌ `https://...` (HTTPS au lieu de HTTP)
- ❌ `http://localhost:8000/...` (localhost au lieu de l'IP)
- ❌ `http://192.168.1.42/api/rfid/debug` (manque le port :8000)
- ❌ `http://192.168.1.42:8000/raspberry` (mauvais endpoint)

## Checklist complète

Avant de dire "aucune détection", vérifiez :

- [ ] Les 2 serveurs tournent (`lsof -i :8000` et `lsof -i :8001`)
- [ ] `SSE_SERVER_URL=http://localhost:8001` dans `.env`
- [ ] `/rfidlive-ultra` affiche "● Connecté" (vert)
- [ ] `./test-manual.sh` envoie une détection qui apparaît dans `/rfidlive-ultra`
- [ ] Le pare-feu macOS est désactivé ou PHP autorisé
- [ ] L'IP du MacBook est trouvée (`./get-local-ip.sh`)
- [ ] Le lecteur et le MacBook sont sur le même réseau WiFi
- [ ] L'URL dans le lecteur est : `http://[IP]:8000/api/rfid/debug`
- [ ] Le lecteur utilise HTTP (pas HTTPS)

## Test final

Une fois TOUTE la checklist validée :

1. **Ouvrez** : `http://localhost:8000/rfidlive-ultra`
2. **Vérifiez** : Statut = "● Connecté" (vert)
3. **Passez une puce** devant le lecteur portable
4. **Regardez** si une détection apparaît

### ✅ Si ça fonctionne

Félicitations ! Maintenant on peut analyser le format exact des données envoyées par le lecteur et adapter le code pour que `/api/raspberry` fonctionne aussi.

### ❌ Si ça ne fonctionne toujours pas

**Vérifiez les logs du serveur en temps réel** :

```bash
# Terminal 1 : Regarder les logs Laravel
tail -f storage/logs/laravel.log

# Terminal 2 : Passer une puce devant le lecteur
```

**Si des lignes apparaissent** → Le lecteur envoie bien, mais le format est peut-être incompatible
**Si rien n'apparaît** → Le lecteur n'envoie rien du tout

**Contactez-moi avec** :
- Les résultats de `./check-config.sh`
- Le statut dans `/rfidlive-ultra` (Connecté ou Déconnecté)
- Les logs du serveur
- La configuration exacte du lecteur (URL, méthode, headers)

Et on va résoudre ça ensemble ! 🔧
