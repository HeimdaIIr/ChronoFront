# 🔧 Guide de Debug - Accès Réseau sur macOS

## Problème actuel
- Le serveur Laravel fonctionne en `localhost` mais n'est pas accessible depuis le réseau
- Le lecteur RFID portable ne peut pas envoyer de données
- Un autre PC sur le même WiFi ne peut pas accéder à l'application

---

## ✅ ÉTAPE 1 : Trouver ton IP réseau

Exécute cette commande dans ton Terminal **macOS** :

```bash
ipconfig getifaddr en0
```

Si ça ne retourne rien, essaie :

```bash
ipconfig getifaddr en1
```

**Note ton IP** (ex: 192.168.1.148) - tu en auras besoin pour les étapes suivantes.

---

## ✅ ÉTAPE 2 : Vérifier le pare-feu macOS

### Option A - Interface graphique (RECOMMANDÉ)

1. Ouvrir **Réglages Système** (icône  dans le Dock)
2. Dans la barre latérale, chercher **"Réseau"** ou **"Network"**
3. Scroller en bas et cliquer sur **"Pare-feu"** ou **"Firewall"**
4. Si l'option est grisée, cliquer sur le cadenas 🔒 en bas et entrer ton mot de passe
5. **Tu as 2 choix :**
   - **Désactiver** le pare-feu temporairement (pour tester)
   - **OU** cliquer sur "Options..." et ajouter PHP à la liste des apps autorisées

### Option B - Ligne de commande

Pour **désactiver temporairement** le pare-feu (nécessite mot de passe admin) :

```bash
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off
```

Pour **vérifier le statut** du pare-feu :

```bash
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate
```

Pour **autoriser PHP** spécifiquement :

```bash
# Trouve où est PHP
which php

# Autorise PHP (remplace le chemin si différent)
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --add /usr/bin/php
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --unblockapp /usr/bin/php
```

---

## ✅ ÉTAPE 3 : Arrêter tous les serveurs Laravel existants

```bash
# Tue tous les processus PHP artisan serve
pkill -f "artisan serve"

# Vérifie qu'il n'y a plus rien sur les ports 8000 et 8001
lsof -i :8000
lsof -i :8001
```

Si tu vois encore des processus, note leur PID et tue-les :

```bash
kill -9 [PID]
```

---

## ✅ ÉTAPE 4 : Démarrer le serveur CORRECTEMENT

**IMPORTANT:** Le serveur doit écouter sur `0.0.0.0` pour être accessible depuis le réseau.

Dans ton Terminal, depuis le dossier ChronoFront :

```bash
cd ~/ChronoFront  # ou le chemin vers ton projet
php -d output_buffering=Off artisan serve --host=0.0.0.0 --port=8000
```

Tu devrais voir :

```
Laravel development server started: http://0.0.0.0:8000
```

**Laisse ce terminal ouvert** - c'est ton serveur qui tourne.

---

## ✅ ÉTAPE 5 : Tester l'accès réseau

Ouvre un **NOUVEAU terminal** (laisse le serveur tourner dans l'ancien).

### Test 1 - Depuis localhost

```bash
curl -X PUT http://localhost:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"TEST123"}]'
```

✅ **Résultat attendu :** Tu devrais voir un JSON avec `"success": true`

### Test 2 - Depuis ton IP réseau

Remplace `TON_IP` par l'IP trouvée à l'étape 1 (ex: 192.168.1.148) :

```bash
curl -X PUT http://TON_IP:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"NETWORK_TEST"}]'
```

✅ **Résultat attendu :** Le même JSON avec `"success": true`

❌ **Si ça échoue :**
- Vérifie que le pare-feu est bien désactivé/configuré (étape 2)
- Vérifie que le serveur écoute sur `0.0.0.0` (étape 4)

---

## ✅ ÉTAPE 6 : Tester depuis un autre PC

Sur un **autre ordinateur** connecté au même WiFi :

1. Ouvre un navigateur
2. Va sur : `http://TON_IP:8000/rfidlive-ultra` (remplace TON_IP)

✅ **Résultat attendu :** La page RFID Live Ultra s'affiche avec status "Connecté"

---

## ✅ ÉTAPE 7 : Tester les détections

Dans le terminal où tourne le serveur, tu devrais voir les requêtes qui arrivent.

Ouvre la page dans ton navigateur : `http://localhost:8000/rfidlive-ultra`

Dans un autre terminal, envoie une détection de test :

```bash
curl -X PUT http://localhost:8000/api/rfid/debug \
  -H "Content-Type: application/json" \
  -H "Serial: 200" \
  -d '[{"serial":"RFID_TAG_001"}]'
```

✅ **Résultat attendu :** La détection apparaît **instantanément** dans rfidlive-ultra

---

## ✅ ÉTAPE 8 : Configurer le lecteur RFID portable

Dans l'interface de configuration de ton lecteur (reader_id: 200) :

- **URL :** `http://TON_IP:8000/api/rfid/debug`
  - Remplace `TON_IP` par ton IP réseau (ex: `http://192.168.1.148:8000/api/rfid/debug`)
- **Méthode :** PUT
- **Port :** 8000

Le lecteur envoie déjà les bons headers (`Serial: 200`) d'après le code Java.

---

## 🐛 Debug si ça ne marche toujours pas

### Problème : Le serveur ne démarre pas

```bash
# Vérifie que le port n'est pas déjà utilisé
lsof -i :8000

# Si un processus utilise le port, tue-le
kill -9 [PID]
```

### Problème : Erreur 429 (Too Many Requests) dans la console

✅ **Déjà corrigé** - Le rate limiting a été désactivé pour les endpoints RFID

### Problème : Le pare-feu bloque toujours

Vérifie dans **Réglages Système > Réseau > Pare-feu > Options** :

- "Bloquer toutes les connexions entrantes" doit être **DÉCOCHÉ**
- PHP doit être dans la liste des applications autorisées

### Problème : Détections en double

**C'est le problème initial à corriger après avoir résolu l'accès réseau !**

---

## 📝 Commandes de debug rapides

```bash
# Voir ton IP
ipconfig getifaddr en0

# Voir le statut du pare-feu
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate

# Voir les processus PHP
lsof -i -P | grep php

# Tester l'API
curl -X PUT http://localhost:8000/api/rfid/debug -H "Serial: 200" -d '[{"serial":"TEST"}]'

# Voir les logs Laravel en temps réel
tail -f storage/logs/laravel.log
```

---

## ✅ Checklist complète

- [ ] J'ai trouvé mon IP réseau (étape 1)
- [ ] Le pare-feu est désactivé OU PHP est autorisé (étape 2)
- [ ] Tous les anciens serveurs sont arrêtés (étape 3)
- [ ] Le serveur tourne avec `--host=0.0.0.0` (étape 4)
- [ ] `curl localhost` fonctionne (étape 5, test 1)
- [ ] `curl MON_IP` fonctionne (étape 5, test 2)
- [ ] Je peux accéder depuis un autre PC (étape 6)
- [ ] Les détections de test s'affichent (étape 7)
- [ ] Le lecteur RFID est configuré avec la bonne URL (étape 8)

---

## 🎯 Prochaine étape

Une fois que tout cela fonctionne, on pourra s'attaquer au **vrai problème** : les **détections en double** !
