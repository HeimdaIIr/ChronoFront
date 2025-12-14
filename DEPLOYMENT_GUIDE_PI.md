# 🚀 Guide de déploiement ChronoFront V2 sur Raspberry Pi

## 📋 Vue d'ensemble

Ce guide explique comment déployer ChronoFront V2 sur le Raspberry Pi 107 (lecteur principal).

**Deux méthodes disponibles :**
- ✅ **Méthode 1 : FTP + SSH** (RECOMMANDÉE si vous avez FTP)
- ✅ **Méthode 2 : SCP + SSH** (Alternative si pas de FTP)

---

## ✅ Méthode 1 : Déploiement via FTP (RECOMMANDÉ)

### Étape 1 : Préparer l'archive sur votre PC

```bash
cd /home/user/ChronoFront
chmod +x scripts/deploy_prepare.sh
./scripts/deploy_prepare.sh
```

Cela créera un fichier `chronofront-v2_YYYYMMDD_HHMMSS.tar.gz`

### Étape 2 : Transférer via FTP

**Option A : Avec FileZilla**

1. Ouvrir FileZilla
2. Se connecter au Raspberry Pi :
   - Hôte : `10.8.0.107` (ou `192.168.10.157` si en local)
   - Utilisateur : `pi` (ou votre utilisateur FTP)
   - Mot de passe : [votre mot de passe]
   - Port : `21` (ou `22` si SFTP)

3. Naviguer vers `/home/pi/`
4. Transférer les fichiers :
   - `chronofront-v2_YYYYMMDD_HHMMSS.tar.gz`
   - `scripts/deploy_install.sh`

**Option B : Avec ligne de commande FTP**

```bash
# Transférer l'archive
ftp 10.8.0.107
# Entrer identifiant et mot de passe
put chronofront-v2_YYYYMMDD_HHMMSS.tar.gz
put scripts/deploy_install.sh
bye
```

**Option C : Avec WinSCP (Windows)**

1. Ouvrir WinSCP
2. Nouvelle connexion :
   - Protocole : SFTP
   - Hôte : `10.8.0.107`
   - Utilisateur : `pi`
   - Mot de passe : [votre mot de passe]
3. Se connecter et glisser-déposer les fichiers

### Étape 3 : Installer via SSH

```bash
# Se connecter en SSH
ssh pi@10.8.0.107

# Vérifier que les fichiers sont présents
ls -lh chronofront-v2_*.tar.gz
ls -lh deploy_install.sh

# Rendre le script exécutable
chmod +x deploy_install.sh

# Lancer l'installation
sudo bash deploy_install.sh chronofront-v2_*.tar.gz
```

L'installation prendra environ 5-10 minutes selon la connexion internet de la Raspberry Pi.

---

## ✅ Méthode 2 : Déploiement via SCP

### Étape 1 : Préparer l'archive

```bash
cd /home/user/ChronoFront
chmod +x scripts/deploy_prepare.sh
./scripts/deploy_prepare.sh
```

### Étape 2 : Transférer via SCP

```bash
# Transférer l'archive
scp chronofront-v2_*.tar.gz pi@10.8.0.107:/home/pi/

# Transférer le script d'installation
scp scripts/deploy_install.sh pi@10.8.0.107:/home/pi/
```

### Étape 3 : Installer via SSH

```bash
# Se connecter en SSH
ssh pi@10.8.0.107

# Lancer l'installation
sudo bash deploy_install.sh chronofront-v2_*.tar.gz
```

---

## 🧪 Tests post-installation

### Test 1 : Vérifier l'accès web

```bash
# Depuis votre PC
curl -I http://107.course.ats-sport.com

# Ou depuis la Pi elle-même
curl -I http://localhost
```

**Résultat attendu :** HTTP 200 OK

### Test 2 : Vérifier l'API de santé

```bash
curl http://107.course.ats-sport.com/api/health
```

**Résultat attendu :**
```json
{
  "status": "ok",
  "database": "connected",
  "timestamp": "2025-12-09T15:30:00Z"
}
```

### Test 3 : Tester la réception RFID

```bash
# Depuis votre PC ou un autre lecteur
curl -X PUT http://107.course.ats-sport.com/api/raspberry \
  -H "Serial: 120" \
  -H "Content-Type: application/json" \
  -d '{
    "tag": "TEST123",
    "time": "2025-12-09T15:30:00Z"
  }'
```

**Résultat attendu :**
```json
{
  "success": true,
  "message": "Detection received",
  "detection": {...}
}
```

### Test 4 : Consulter les logs

```bash
# Sur la Raspberry Pi
sudo tail -f /var/www/chronofront-v2/storage/logs/laravel.log
```

### Test 5 : Accéder à l'interface web

Ouvrir un navigateur :
- Via VPN/4G : `http://107.course.ats-sport.com`
- Via RJ45 local : `http://192.168.10.157`

---

## 🔧 Configuration post-installation

### 1. Créer un événement de test

Via l'interface web ou l'API :

```bash
curl -X POST http://107.course.ats-sport.com/api/events \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Event",
    "date": "2025-12-09"
  }'
```

### 2. Ajouter le lecteur 107 (principal)

```bash
curl -X POST http://107.course.ats-sport.com/api/readers \
  -H "Content-Type: application/json" \
  -d '{
    "serial": "107",
    "name": "Lecteur ARRIVÉE",
    "network_type": "vpn",
    "event_id": 1,
    "location": "ARRIVÉE",
    "distance_from_start": 15000,
    "checkpoint_order": 3,
    "is_primary": true,
    "is_active": true
  }'
```

### 3. Ajouter le lecteur 120 (secondaire)

```bash
curl -X POST http://107.course.ats-sport.com/api/readers \
  -H "Content-Type: application/json" \
  -d '{
    "serial": "120",
    "name": "Lecteur KM5",
    "network_type": "vpn",
    "event_id": 1,
    "location": "KM5",
    "distance_from_start": 5000,
    "checkpoint_order": 2,
    "is_primary": false,
    "primary_reader_id": 1,
    "is_active": true
  }'
```

### 4. Configurer le lecteur 120 pour envoyer au lecteur 107

Obtenir les instructions :

```bash
curl http://107.course.ats-sport.com/api/readers/event/1/config-instructions
```

Suivre les instructions retournées pour configurer Upload 2 du lecteur 120.

---

## 🎯 Test complet bout-en-bout

### Scénario de test

1. **Lecteur 107 (ARRIVÉE)** : Déployé avec ChronoFront V2
2. **Lecteur 120 (KM5)** : Configuré pour envoyer à 107

### Test réel avec un badge RFID

1. Passer un badge devant le lecteur 120 (KM5)
2. Le lecteur 120 envoie la détection à `http://107.course.ats-sport.com/api/raspberry`
3. Le lecteur 107 reçoit et enregistre la détection
4. Vérifier dans l'interface ChronoFront que la détection apparaît

### Commande de vérification

```bash
# Consulter les détections reçues
curl http://107.course.ats-sport.com/api/detections
```

---

## 🆘 Troubleshooting

### Problème : L'installation échoue

**Vérifications :**

1. ✅ Espace disque suffisant :
```bash
df -h
```

2. ✅ Mémoire disponible :
```bash
free -h
```

3. ✅ Connexion internet :
```bash
ping -c 4 8.8.8.8
```

### Problème : Apache ne démarre pas

```bash
# Vérifier les logs Apache
sudo tail -f /var/log/apache2/error.log

# Vérifier la configuration
sudo apache2ctl configtest

# Redémarrer Apache
sudo systemctl restart apache2
```

### Problème : Erreur 500 Internal Server Error

```bash
# Vérifier les permissions
sudo chown -R www-data:www-data /var/www/chronofront-v2
sudo chmod -R 755 /var/www/chronofront-v2
sudo chmod -R 775 /var/www/chronofront-v2/storage
sudo chmod -R 775 /var/www/chronofront-v2/bootstrap/cache

# Vérifier les logs Laravel
sudo tail -f /var/www/chronofront-v2/storage/logs/laravel.log
```

### Problème : Base de données non accessible

```bash
# Vérifier les permissions du fichier SQLite
ls -la /var/www/chronofront-v2/database/database.sqlite

# Corriger si nécessaire
sudo chown www-data:www-data /var/www/chronofront-v2/database/database.sqlite
sudo chmod 664 /var/www/chronofront-v2/database/database.sqlite
```

### Problème : Les détections n'arrivent pas

**Vérifications :**

1. ✅ Le lecteur émetteur est actif dans ChronoFront
2. ✅ L'URL est correcte dans la config du lecteur émetteur
3. ✅ Le lecteur émetteur a accès réseau (4G/VPN)
4. ✅ Le pare-feu ne bloque pas les connexions

**Test manuel :**

```bash
# Depuis le lecteur 120, tester la connectivité
curl -I http://107.course.ats-sport.com/api/raspberry
```

---

## 🔄 Restaurer l'ancienne version

Si quelque chose ne va pas, l'ancienne version a été sauvegardée :

```bash
# Sur la Raspberry Pi
sudo systemctl stop apache2

# Supprimer la nouvelle version
sudo rm -rf /var/www/chronofront-v2

# Restaurer l'ancienne version
sudo mv /var/www/chronofront-backup-YYYYMMDD_HHMMSS /var/www/chronofront-v2

# Redémarrer Apache
sudo systemctl start apache2
```

---

## 📊 Surveillance et performances

### Surveiller l'utilisation CPU/Mémoire

```bash
# Surveiller en temps réel
htop

# Ou avec top
top
```

### Surveiller les logs en temps réel

```bash
# Logs Apache
sudo tail -f /var/log/apache2/chronofront-access.log

# Logs Laravel
sudo tail -f /var/www/chronofront-v2/storage/logs/laravel.log
```

### Nettoyer les logs anciens

```bash
# Nettoyer les logs Laravel (si trop volumineux)
sudo truncate -s 0 /var/www/chronofront-v2/storage/logs/laravel.log
```

---

## 📞 Support

En cas de problème :

1. Consulter les logs Laravel : `/var/www/chronofront-v2/storage/logs/laravel.log`
2. Consulter les logs Apache : `/var/log/apache2/error.log`
3. Vérifier l'état du service : `sudo systemctl status apache2`
4. Tester la connectivité réseau avec `ping` et `curl`

---

## ✅ Checklist de déploiement

- [ ] Archive créée avec `deploy_prepare.sh`
- [ ] Fichiers transférés via FTP/SCP
- [ ] Script d'installation exécuté avec succès
- [ ] Test 1 : Accès web OK (HTTP 200)
- [ ] Test 2 : API de santé OK
- [ ] Test 3 : Réception RFID OK
- [ ] Interface web accessible
- [ ] Événement de test créé
- [ ] Lecteurs configurés (107 et 120)
- [ ] Test bout-en-bout avec badge RFID réussi
- [ ] Surveillance des logs active
- [ ] Documentation consultée

---

## 🎉 Prochaines étapes

Une fois le déploiement validé sur le lecteur 107 :

1. **Configurer tous les lecteurs secondaires** pour envoyer au 107
2. **Tester avec plusieurs badges simultanément** pour valider les performances
3. **Déployer sur les autres Raspberry Pi** si nécessaire (lecteurs 112, 120, etc.)
4. **Finaliser l'interface frontend** pour l'affichage multi-lecteurs
5. **Préparer la documentation client** pour l'utilisation en production

Bonne chance ! 🚀
