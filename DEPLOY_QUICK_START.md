# 🚀 Déploiement ChronoFront V2 - Guide Express

## ⚡ Déploiement en 3 étapes

### 📦 Étape 1 : Créer l'archive (sur votre PC)

```bash
cd /home/user/ChronoFront
chmod +x scripts/deploy_prepare.sh
./scripts/deploy_prepare.sh
```

✅ **Résultat :** Fichier `chronofront-v2_YYYYMMDD_HHMMSS.tar.gz` créé

---

### 📤 Étape 2 : Transférer les fichiers via FTP

**Avec FileZilla / WinSCP / Autre client FTP :**

- **Hôte :** `10.8.0.107` (ou `192.168.10.157` si RJ45 local)
- **Utilisateur :** `pi`
- **Destination :** `/home/pi/`

**Fichiers à transférer :**
1. `chronofront-v2_YYYYMMDD_HHMMSS.tar.gz`
2. `scripts/deploy_install.sh`

---

### 🔧 Étape 3 : Installer via SSH

```bash
# Se connecter
ssh pi@10.8.0.107

# Installer
chmod +x deploy_install.sh
sudo bash deploy_install.sh chronofront-v2_*.tar.gz
```

⏱️ **Durée :** 5-10 minutes

---

## ✅ Vérifications post-installation

### 1. Tester l'accès web

```bash
curl -I http://107.course.ats-sport.com
```

**Attendu :** `HTTP/1.1 200 OK`

### 2. Tester l'API de santé

```bash
curl http://107.course.ats-sport.com/api/health
```

**Attendu :**
```json
{
  "status": "ok",
  "database": "connected",
  "app": "ChronoFront V2 Laravel",
  "version": "2.0.0"
}
```

### 3. Tester la réception RFID

```bash
curl -X PUT http://107.course.ats-sport.com/api/raspberry \
  -H "Serial: 120" \
  -H "Content-Type: application/json" \
  -d '{"tag":"TEST123","time":"2025-12-09T15:30:00Z"}'
```

**Attendu :**
```json
{
  "success": true,
  "message": "Detection received"
}
```

---

## 🎯 Configuration initiale

### 1. Créer un événement

```bash
curl -X POST http://107.course.ats-sport.com/api/events \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Event",
    "date": "2025-12-09"
  }'
```

### 2. Ajouter le lecteur 107 (principal - ARRIVÉE)

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

### 3. Ajouter le lecteur 120 (secondaire - KM5)

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

### 4. Obtenir les instructions de configuration pour le lecteur 120

```bash
curl http://107.course.ats-sport.com/api/readers/event/1/config-instructions
```

Suivez les instructions retournées pour configurer le lecteur 120.

---

## 🧪 Test réel avec badge RFID

1. **Configurer le lecteur 120** pour envoyer à :
   - URL : `http://107.course.ats-sport.com/api/raspberry`
   - Method : `PUT`
   - Module : `Upload 2`

2. **Passer un badge** devant le lecteur 120

3. **Vérifier la réception** :
```bash
# Consulter les logs
ssh pi@10.8.0.107
sudo tail -f /var/www/chronofront-v2/storage/logs/laravel.log

# Ou consulter via l'API
curl http://107.course.ats-sport.com/api/detections
```

---

## 🆘 Problèmes courants

### Erreur 500

```bash
# Vérifier les permissions
ssh pi@10.8.0.107
sudo chown -R www-data:www-data /var/www/chronofront-v2
sudo chmod -R 775 /var/www/chronofront-v2/storage
sudo chmod 664 /var/www/chronofront-v2/database/database.sqlite
```

### Base de données inaccessible

```bash
sudo chown www-data:www-data /var/www/chronofront-v2/database/database.sqlite
sudo chmod 664 /var/www/chronofront-v2/database/database.sqlite
```

### Apache ne démarre pas

```bash
sudo systemctl restart apache2
sudo systemctl status apache2
```

---

## 📞 Support

**Logs Laravel :**
```bash
sudo tail -f /var/www/chronofront-v2/storage/logs/laravel.log
```

**Logs Apache :**
```bash
sudo tail -f /var/log/apache2/error.log
```

**Restaurer l'ancienne version :**
```bash
sudo rm -rf /var/www/chronofront-v2
sudo mv /var/www/chronofront-backup-* /var/www/chronofront-v2
sudo systemctl restart apache2
```

---

## ✅ Checklist complète

- [ ] Archive créée
- [ ] Fichiers transférés via FTP
- [ ] Installation exécutée
- [ ] Test santé OK (curl health)
- [ ] Test réception RFID OK
- [ ] Interface web accessible
- [ ] Événement créé
- [ ] Lecteur 107 (principal) ajouté
- [ ] Lecteur 120 (secondaire) ajouté
- [ ] Lecteur 120 configuré (Upload 2)
- [ ] Test badge RFID réussi
- [ ] Détections visibles dans ChronoFront

---

## 📚 Documentation complète

Pour plus de détails, consultez :
- `DEPLOYMENT_GUIDE_PI.md` - Guide complet de déploiement
- `READER_DEPLOYMENT_GUIDE.md` - Guide de configuration des lecteurs
- `NETWORK_COMPATIBILITY.md` - Configuration réseau multi-lecteurs

---

🎉 **Bon déploiement !**
