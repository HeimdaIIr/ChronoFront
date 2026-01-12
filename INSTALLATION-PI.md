# ChronoFront - Installation sur Raspberry Pi (Buster)

Guide complet pour installer ChronoFront sur Raspberry Pi sous Raspbian Buster avec PHP 7.3.

## 📋 Prérequis

- **Raspberry Pi** 3 ou 4
- **Raspbian Buster** (Debian 10)
- **Accès SSH** à la Pi
- **Connexion Internet** (pour télécharger les dépendances)
- **Numéro de série du lecteur** (ex: 107, 115, 120)

## 🚀 Installation Rapide

### 1. Transférer les fichiers sur la Pi

```bash
# Depuis votre machine de développement
cd /chemin/vers/ChronoFront
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' \
    . pi@adresse-ip-pi:/home/pi/chronofront/
```

### 2. Se connecter en SSH

```bash
ssh pi@adresse-ip-pi
```

### 3. Lancer l'installation

```bash
cd /home/pi/chronofront
chmod +x install-pi.sh verify-pi.sh
sudo ./install-pi.sh
```

Le script va vous demander le **numéro de série du lecteur**.

**Exemple** : Si vous entrez `107`, votre lecteur sera accessible via `http://107.course`

### 4. Vérifier l'installation

```bash
sudo ./verify-pi.sh
```

Ce script effectue **12 vérifications** :
1. ✅ Informations système
2. ✅ Installation PHP 7.3
3. ✅ Nginx
4. ✅ PHP-FPM
5. ✅ DNS (dnsmasq)
6. ✅ ChronoFront installé
7. ✅ Permissions fichiers
8. ✅ Base de données
9. ✅ Configuration Nginx
10. ✅ Dépendances Composer
11. ✅ Configuration Laravel
12. ✅ Connectivité HTTP

## 🔍 Tests de Connexion

### Test 1 : Via SSH sur la Pi

```bash
# Test localhost
curl -I http://localhost

# Test IP locale
curl -I http://$(hostname -I | awk '{print $1}')

# Test domaine .course (si dnsmasq configuré)
curl -I http://107.course
```

Si vous obtenez un code `200` ou `302`, l'installation fonctionne ! ✅

### Test 2 : Depuis votre ordinateur

**En WiFi** (si Access Point configuré) :
```
http://107.course
```

**En RJ45** (réseau local) :
```
http://192.168.1.XXX  # Remplacer par l'IP de la Pi
```

### Test 3 : Vérifier les services

```bash
# Statut des services
sudo systemctl status nginx
sudo systemctl status php7.3-fpm
sudo systemctl status dnsmasq

# Logs en temps réel
sudo journalctl -fu nginx
```

## 📁 Structure d'Installation

```
/var/www/chronofront/          # Répertoire principal
├── app/                        # Code Laravel
├── public/                     # Point d'entrée web
├── database/
│   └── database.sqlite        # Base de données
├── storage/                    # Fichiers générés
└── .env                        # Configuration
```

## 🔧 Configuration Post-Installation

### 1. Accéder à ChronoFront

Ouvrir dans un navigateur :
```
http://107.course
```
(Remplacer `107` par votre numéro de lecteur)

### 2. Importer les participants

1. Aller dans **"Participants"** → **"Importer CSV"**
2. Uploader votre fichier CSV
3. Vérifier l'import

### 3. Configurer le lecteur RFID

1. Aller dans **"Événements"** → **"Lecteurs RFID"**
2. Ajouter votre lecteur avec son numéro de série
3. Configurer l'endpoint : `http://192.168.4.1/api/raspberry`

### 4. Tester la communication RFID

Ouvrir `/rfidlive` pour voir les détections en temps réel :
```
http://107.course/rfidlive
```

## 🛠️ Dépannage

### Problème : "502 Bad Gateway"

**Cause** : PHP-FPM ne répond pas

**Solution** :
```bash
sudo systemctl restart php7.3-fpm
sudo systemctl status php7.3-fpm
```

### Problème : "Permission denied" sur database.sqlite

**Solution** :
```bash
sudo chown www-data:www-data /var/www/chronofront/database/database.sqlite
sudo chmod 664 /var/www/chronofront/database/database.sqlite
```

### Problème : Le domaine XXX.course ne fonctionne pas

**Vérifier dnsmasq** :
```bash
sudo systemctl status dnsmasq
cat /etc/dnsmasq.conf | grep course
```

**Vérifier la résolution DNS** :
```bash
nslookup 107.course
```

### Problème : Page blanche / erreur 500

**Voir les logs Laravel** :
```bash
sudo tail -f /var/www/chronofront/storage/logs/laravel.log
```

**Voir les logs Nginx** :
```bash
sudo tail -f /var/log/nginx/error.log
```

### Problème : Composer install échoue

**Augmenter la mémoire PHP** :
```bash
sudo php -d memory_limit=-1 /usr/bin/composer install --no-dev
```

## 📊 Commandes Utiles

### Redémarrer les services
```bash
sudo systemctl restart nginx
sudo systemctl restart php7.3-fpm
sudo systemctl restart dnsmasq
```

### Vider le cache Laravel
```bash
cd /var/www/chronofront
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
```

### Re-optimiser Laravel
```bash
cd /var/www/chronofront
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Sauvegarder la base de données
```bash
sudo cp /var/www/chronofront/database/database.sqlite \
       /var/www/chronofront/database/database.sqlite.backup
```

### Restaurer la base de données
```bash
sudo cp /var/www/chronofront/database/database.sqlite.backup \
       /var/www/chronofront/database/database.sqlite
```

## 🔐 Sécurité

### Changer les permissions par défaut
```bash
sudo chown -R www-data:www-data /var/www/chronofront
sudo chmod -R 755 /var/www/chronofront
sudo chmod -R 775 /var/www/chronofront/storage
sudo chmod -R 775 /var/www/chronofront/bootstrap/cache
```

### Bloquer l'accès externe (optionnel)
Si vous voulez que ChronoFront soit accessible **uniquement en local** :

```nginx
# Dans /etc/nginx/sites-available/chronofront
server {
    listen 80;

    # Autoriser seulement le réseau local
    allow 192.168.0.0/16;
    allow 127.0.0.1;
    deny all;

    # ... reste de la config
}
```

## 📱 Configuration WiFi Access Point (Optionnel)

Si vous voulez que la Pi crée son propre réseau WiFi :

### 1. Configurer hostapd

```bash
sudo nano /etc/hostapd/hostapd.conf
```

```ini
interface=wlan0
driver=nl80211
ssid=ChronoFront-107  # Remplacer 107 par votre numéro
hw_mode=g
channel=7
wmm_enabled=0
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_passphrase=chronofront  # Changez ce mot de passe !
wpa_key_mgmt=WPA-PSK
wpa_pairwise=TKIP
rsn_pairwise=CCMP
```

### 2. Configuration réseau

```bash
sudo nano /etc/dhcpcd.conf
```

Ajouter à la fin :
```
interface wlan0
    static ip_address=192.168.4.1/24
    nohook wpa_supplicant
```

### 3. Activer hostapd

```bash
sudo systemctl unmask hostapd
sudo systemctl enable hostapd
sudo systemctl start hostapd
```

## ✅ Checklist Finale

Avant de déployer en production :

- [ ] `verify-pi.sh` passe tous les tests
- [ ] Accessible via `http://XXX.course`
- [ ] Base de données créée et migrations exécutées
- [ ] Import CSV de participants fonctionne
- [ ] Lecteur RFID configuré
- [ ] Test de détection RFID fonctionnel (`/rfidlive`)
- [ ] Écran speaker accessible (`/screens/speaker`)
- [ ] Services configurés pour démarrage automatique
- [ ] Sauvegarde de la base de données effectuée

## 🆘 Support

Si vous rencontrez un problème non couvert par ce guide :

1. Exécuter `sudo ./verify-pi.sh` et noter les erreurs
2. Vérifier les logs : `sudo tail -f /var/log/nginx/error.log`
3. Vérifier les logs Laravel : `sudo tail -f /var/www/chronofront/storage/logs/laravel.log`

---

**Version** : Laravel 8 - PHP 7.3 (Buster compatible)
**Dernière mise à jour** : Janvier 2026
