# Installation ChronoFront Laravel 8 sur Raspberry Pi (Apache)

Guide d'installation pour remplacer l'ancien système CodeIgniter par Laravel 8, tout en gardant Apache et la même configuration réseau.

## Prérequis

- Raspberry Pi sous Raspbian Buster
- Accès SSH avec droits sudo
- Backup de l'image SD (déjà fait ✓)
- Apache2 déjà installé et configuré

## Fichiers fournis

1. **install-laravel8-apache.sh** - Script d'installation complet
2. **verify-laravel8-apache.sh** - Script de vérification post-installation
3. **CURRENT-SYSTEM-ANALYSIS.md** - Analyse du système actuel

## Installation

### Étape 1 : Copier les scripts sur la Raspberry Pi

```bash
scp install-laravel8-apache.sh pi@<IP_RASPBERRY>:~/
scp verify-laravel8-apache.sh pi@<IP_RASPBERRY>:~/
```

### Étape 2 : Se connecter en SSH

```bash
ssh pi@<IP_RASPBERRY>
```

### Étape 3 : Exécuter l'installation

```bash
sudo bash install-laravel8-apache.sh
```

Le script va vous demander :
- **Numéro du lecteur RFID** (ex: 107)

Ensuite il va :
1. ✓ Sauvegarder l'ancien système dans `/home/dev/web.backup.YYYYMMDD_HHMMSS`
2. ✓ Installer PHP 7.3 et tous les modules nécessaires
3. ✓ Installer Composer
4. ✓ Cloner ChronoFront Laravel 8
5. ✓ Installer les dépendances
6. ✓ Créer la base SQLite
7. ✓ Configurer Apache (modifier 000-course.conf)
8. ✓ Mettre à jour /etc/hosts
9. ✓ Redémarrer Apache

**Durée estimée : 5-10 minutes**

### Étape 4 : Vérifier l'installation

```bash
sudo bash verify-laravel8-apache.sh
```

Ce script vérifie :
- PHP 7.3 et modules
- Apache et modules
- ChronoFront installé
- Permissions correctes
- Base de données SQLite
- Configuration Apache
- Routes Laravel
- Connectivité HTTP

## Ce qui est conservé du système actuel

✓ Apache2 (pas de changement de serveur web)
✓ Configuration réseau (lan, wlan0, ap0, tun0)
✓ WiFi Access Point (ATS_TP107)
✓ dnsmasq pour DHCP
✓ Résolution DNS via Apache ServerAlias
✓ Même système de domaines : `XXX.course.ats-sport.com`
✓ /etc/hosts pour résolution locale

## Ce qui change

- **Framework** : CodeIgniter 3 → Laravel 8
- **DocumentRoot** : `/home/dev/web` → `/var/www/chronofront/public`
- **Base de données** : MySQL → SQLite
- **Endpoint RFID** : `/chrono/index.php/results/newtime` → `/api/raspberry`
- **Architecture** : VueJS séparé → Laravel avec API

## Accès après installation

L'application sera accessible via :

- `http://107.course.ats-sport.com` (domaine complet)
- `http://107.course` (domaine court)
- `http://localhost`
- `http://192.168.10.157` (IP du bridge lan)

## Endpoint RFID

**Nouvelle URL :** `POST http://107.course.ats-sport.com/api/raspberry`

**Format de données attendu :**
```json
{
  "serial": "30000001",
  "timestamp": 1234567890
}
```

**⚠️ Important** : Les lecteurs RFID devront être reconfigurés pour pointer vers `/api/raspberry` au lieu de `/chrono/index.php/results/newtime`

## Interface RFID Live

Accessible via : `http://107.course.ats-sport.com/rfidlive`

## Restauration de l'ancien système

Si besoin de revenir en arrière :

```bash
# Restaurer la configuration Apache
sudo cp /etc/apache2/sites-available/000-course.conf.backup /etc/apache2/sites-available/000-course.conf
sudo systemctl restart apache2

# Restaurer l'ancien code
sudo rm -rf /home/dev/web
sudo cp -r /home/dev/web.backup.YYYYMMDD_HHMMSS /home/dev/web
```

## Vérifications post-installation

### Test 1 : Page d'accueil

```bash
curl http://localhost
```

Devrait retourner du HTML (code 200 ou 302)

### Test 2 : Endpoint RFID

```bash
curl -X POST http://localhost/api/raspberry \
  -H "Content-Type: application/json" \
  -d '{"serial":"30000001","timestamp":1234567890}'
```

Devrait retourner une réponse JSON

### Test 3 : Routes Laravel

```bash
cd /var/www/chronofront
sudo -u www-data php artisan route:list | grep raspberry
```

Devrait afficher la route `/api/raspberry`

### Test 4 : Logs Apache

```bash
tail -f /var/log/apache2/chronofront-error.log
```

Vérifier qu'il n'y a pas d'erreurs PHP

## Dépannage

### Erreur 500 - Internal Server Error

```bash
# Vérifier les permissions
sudo chown -R www-data:www-data /var/www/chronofront
sudo chmod -R 755 /var/www/chronofront
sudo chmod -R 775 /var/www/chronofront/storage
sudo chmod -R 775 /var/www/chronofront/bootstrap/cache

# Vérifier les logs
tail -50 /var/log/apache2/chronofront-error.log
tail -50 /var/www/chronofront/storage/logs/laravel.log
```

### Erreur 403 - Forbidden

```bash
# Vérifier la config Apache
cat /etc/apache2/sites-available/000-course.conf

# Vérifier que mod_rewrite est activé
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Base de données inaccessible

```bash
# Vérifier que la base existe
ls -la /var/www/chronofront/database/database.sqlite

# Recréer si nécessaire
cd /var/www/chronofront
sudo -u www-data touch database/database.sqlite
sudo -u www-data php artisan migrate --force
sudo chmod 664 database/database.sqlite
```

### Application ne charge pas

```bash
# Vider les caches Laravel
cd /var/www/chronofront
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Regénérer les caches
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Module Apache manquant

```bash
# Activer mod_rewrite
sudo a2enmod rewrite

# Activer mod_php
sudo a2enmod php7.3

# Redémarrer Apache
sudo systemctl restart apache2
```

## Commandes utiles

```bash
# Status Apache
sudo systemctl status apache2

# Redémarrer Apache
sudo systemctl restart apache2

# Voir la configuration Apache
apache2ctl -S

# Tester la config Apache
apache2ctl configtest

# Voir les logs en temps réel
tail -f /var/log/apache2/chronofront-error.log

# Voir les routes Laravel
cd /var/www/chronofront && php artisan route:list

# Vérifier PHP
php -v
php -m  # modules

# Tester la base SQLite
sqlite3 /var/www/chronofront/database/database.sqlite ".tables"
```

## Support

En cas de problème :

1. Exécuter `verify-laravel8-apache.sh` pour un diagnostic complet
2. Consulter les logs Apache : `/var/log/apache2/chronofront-error.log`
3. Consulter les logs Laravel : `/var/www/chronofront/storage/logs/laravel.log`
4. Vérifier la configuration Apache : `apache2ctl -S`

## Prochaines étapes

Une fois l'installation vérifiée :

1. ✓ Tester l'interface web
2. ✓ Reconfigurer les lecteurs RFID pour pointer vers `/api/raspberry`
3. ✓ Tester une détection RFID réelle
4. ✓ Vérifier l'interface `/rfidlive`
5. ✓ Migrer les données si nécessaire
