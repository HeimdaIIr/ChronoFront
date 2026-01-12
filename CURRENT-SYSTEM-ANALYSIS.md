# Analyse du système ChronoFront actuel (Raspberry Pi 107)

## Architecture découverte

### 1. Stack technique
- **OS** : Raspbian GNU/Linux 10 (Buster)
- **Serveur web** : Apache2 (pas Nginx)
- **Backend** : CodeIgniter 3.x (dans `/home/dev/web/chrono/`)
- **Frontend** : VueJS (SPA compilée dans `/home/dev/web/`)
- **Base de données** : MySQL (pas SQLite)
- **PHP** : 7.3.27

### 2. Configuration réseau

**Interfaces :**
- `lan` (bridge) : 192.168.10.157/24
- `wlan0` : 192.168.1.190/24
- `ap0` (WiFi AP) : bridgé sur `lan`
- `tun0` (VPN) : 10.8.0.107

**WiFi Access Point :**
- SSID : `ATS_TP107`
- Interface : `ap0` bridgée sur `lan`
- DHCP range : 192.168.10.200-250

### 3. Résolution DNS

**Méthode utilisée : /etc/hosts (pas de wildcard dnsmasq)**

```
192.168.10.157     107.course
192.168.10.157     www.107.course
```

**Apache gère le wildcard via ServerAlias :**

```apache
ServerName 000.course
ServerAlias *.course
ServerAlias *.course.ats-sport.com
```

### 4. Configuration Apache

**VirtualHost actif : 000-course.conf**

```apache
<VirtualHost *:80>
    ServerName 000.course
    ServerAlias *.course
    ServerAlias *.course.ats-sport.com

    DocumentRoot /home/dev/web

    <Directory /home/dev/web/>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 5. Structure de l'application

```
/home/dev/web/
├── index.html          # VueJS SPA (point d'entrée)
├── js/                 # Bundle JavaScript VueJS
├── css/                # Styles
├── config.json         # Config : {"BACK": "/chrono/index.php"}
├── chrono/             # Backend CodeIgniter
│   ├── application/
│   │   ├── controllers/
│   │   │   ├── Results.php     # Gestion des détections RFID
│   │   │   ├── Entrants.php
│   │   │   ├── Events.php
│   │   │   └── ...
│   │   ├── models/
│   │   └── config/
│   ├── system/
│   └── index.php       # Point d'entrée CodeIgniter
└── public/
```

### 6. Endpoint RFID (système actuel)

**URL :** `http://107.course.ats-sport.com/chrono/index.php/results/newtime`

**Méthode :** POST

**Format de données :**
```json
[
  {
    "serial": "30000001",
    "timestamp": 1234567890
  }
]
```

**Traitement :**
- Controller : `Results.php::newtime()`
- Lecture via : `file_get_contents("php://input")`
- Décodage JSON : `json_decode($data, true)`
- Extraction du numéro de dossard depuis le serial (format : `[30000001]`)
- Filtrage des puces parasites (ne traite que les serials contenant "000")

**Format serial RFID :**
- Format Chronospeedway/Raspberry : `[30000001]:a20171201102839001`
- Le système extrait le numéro entre crochets et retire les zéros de tête

### 7. Services actifs

- ✓ Apache2
- ✓ dnsmasq (DHCP uniquement)
- ✓ hostapd (WiFi AP)
- ✗ Nginx (non installé)
- ✗ PHP-FPM (non utilisé, Apache utilise mod_php)

## Différences avec le système Laravel 8 prévu

| Composant | Système actuel | Laravel 8 prévu |
|-----------|---------------|-----------------|
| Serveur web | Apache2 | Nginx |
| Framework backend | CodeIgniter 3 | Laravel 8 |
| PHP | mod_php | PHP-FPM |
| Base de données | MySQL | SQLite |
| DocumentRoot | /home/dev/web | /var/www/chronofront |
| Endpoint RFID | /chrono/index.php/results/newtime | /api/raspberry |
| Format réponse | CodeIgniter routing | Laravel API routes |
| Architecture | VueJS SPA séparé | Laravel + VueJS intégré |

## Questions pour la migration

1. **Serveur web** : Rester sur Apache ou migrer vers Nginx ?
2. **Base de données** : Migrer de MySQL vers SQLite ?
3. **DocumentRoot** : Garder `/home/dev/web` ou utiliser `/var/www/chronofront` ?
4. **Architecture** : Garder VueJS séparé ou intégrer dans Laravel ?
5. **Endpoint RFID** : Les lecteurs RFID peuvent-ils être reconfigurés pour pointer vers `/api/raspberry` ?

## Recommandations

### Option A : Migration minimale (moins de risques)
- Garder Apache2
- Installer Laravel 8 dans `/home/dev/web/chronofront`
- Créer un VirtualHost Apache pour Laravel
- Migrer uniquement le backend vers Laravel
- Garder le frontend VueJS actuel (pointer config.json vers Laravel)

### Option B : Migration complète (système prévu)
- Migrer vers Nginx + PHP-FPM
- Installer Laravel 8 dans `/var/www/chronofront`
- Reconfigurer les lecteurs RFID pour le nouveau endpoint
- Intégrer VueJS dans Laravel (optionnel)

### Option C : Installation parallèle (tests sans risques)
- Installer Nginx sur port 8080
- Installer Laravel 8 dans `/var/www/chronofront`
- Tester sans toucher au système actuel
- Basculer progressivement
