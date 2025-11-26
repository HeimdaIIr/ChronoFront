# ChronoFront - Guide d'Architecture & Déploiement

> **⚠️ Document vivant** - À mettre à jour au fur et à mesure du développement

## 🎯 Vision du Projet

**Révolutionner le chronométrage sportif** avec une application multi-événements, temps réel, et 100% résiliente.

---

## 📊 Architecture Finale Multi-Événements

### Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│              Serveur Central ATS-Sport (Locaux)                  │
│  - MySQL Database (données + backups)                            │
│  - Dashboard supervision (monitoring multi-événements)           │
│  - Prise de contrôle à distance (accès Pi MASTER)               │
│  - API publique pour affichage live (ats-sport.com)             │
└─────────────────────────────────────────────────────────────────┘
              ▲           ▲           ▲           ▲           ▲
              │ 4G+VPN    │           │           │           │
         ┌────┴────┐ ┌───┴────┐ ┌────┴────┐ ┌───┴────┐ ┌────┴────┐
         │Marathon1│ │Marathon2│ │Marathon3│ │Marathon4│ │Marathon5│
         └─────────┘ └────────┘ └─────────┘ └────────┘ └─────────┘
```

### Par événement (exemple : Marathon de Paris)

```
┌──────────────────────────────────────────────────────────┐
│                    Pi MASTER (Arrivée)                    │
│  - ChronoFront complet (Laravel + Nginx + PHP + MySQL)   │
│  - Interface chronométrage complète                       │
│  - Gestion des résultats et classements                   │
│  - API pour écrans déportés                               │
│  - Connexion 4G + VPN → Serveur Central                  │
└──────────────────────────────────────────────────────────┘
              ▲                           ▲
              │ HTTP/API                  │ HTTP/API
              │                           │
    ┌─────────┴─────────┐       ┌────────┴────────┐
    │   Pi ESCLAVE      │       │   Pi ESCLAVE    │
    │   (Départ)        │       │ (Intermédiaire) │
    │ - Détection RFID  │       │ - Détection RFID│
    │ - Envoie vers     │       │ - Envoie vers   │
    │   Pi MASTER       │       │   Pi MASTER     │
    └───────────────────┘       └─────────────────┘
```

---

## 🔧 Composants Techniques

### Matériel

- **20x Raspberry Pi 4** (2GB+ RAM recommandé, 4GB idéal)
- **20x Lecteurs RFID Speedway** (Impinj ou équivalent)
- **20x Cartes 4G + VPN** (connexion vers serveur central)
- **1x Serveur Central** (VPS ou serveur dédié locaux ATS-Sport)

### Stack Technique

#### 🔴 IMPORTANT : Configuration Dynamique des Rôles

**Tous les Raspberry ont exactement la même installation !**

- ✅ Les 20 Raspberry sont **100% interchangeables**
- ✅ Le rôle (MASTER/ESCLAVE) est configuré **au début de chaque événement**
- ✅ Lecteur 107 peut être MASTER aujourd'hui, ESCLAVE demain
- ✅ Interface de configuration `/setup` au premier lancement

**Principe** :
```
Installation identique sur tous les Pi
         ↓
Au démarrage événement : Choix du rôle via interface web
         ↓
    MASTER (Arrivée)          ou          ESCLAVE (Départ/Inter)
    - App complète                       - Détection RFID uniquement
    - Base de données                    - Envoie vers MASTER
    - Interface chrono
```

#### Stack Technique (Identique sur tous les Pi)

- **OS** : Raspbian OS Lite (64-bit)
- **Web Server** : Nginx
- **Backend** : PHP 8.2 + Laravel 11 (complet)
- **Base de données** : MySQL 8.0 (installé partout, actif uniquement si MASTER)
- **Frontend** : Alpine.js + Bootstrap 5
- **Monitoring** : Supervisor (pour Laravel Queue)

**Configuration runtime** :
- Fichier `storage/app/config/reader.json` définit le rôle actuel
- Interface `/setup` pour changer de rôle à tout moment

#### Serveur Central (Locaux ATS-Sport)
- **OS** : Ubuntu Server 22.04 LTS
- **Web Server** : Nginx
- **Base de données** : MySQL 8.0
- **Dashboard** : Laravel + Vue.js (à développer)
- **VPN** : WireGuard ou OpenVPN
- **Monitoring** : Grafana + Prometheus (optionnel)

---

## 🚀 Phases de Développement

### ✅ Phase 0 - Fondations (ACTUEL)

**Objectif** : App fonctionnelle sur PC pour tests

**Déjà fait** :
- ✅ Structure Laravel complète
- ✅ Modèles : Event, Race, Wave, Entrant, Result, Reader, Category
- ✅ API REST complète (CRUD)
- ✅ Interface chronométrage avec Alpine.js
- ✅ Réception détections RFID (endpoint `/api/raspberry`)
- ✅ Monitoring lecteurs (badges, heartbeat 10s)
- ✅ Mode hors ligne (LocalStorage)
- ✅ Import CSV participants
- ✅ Calcul temps et classements

**À faire (Phase 0)** :
- [ ] Affichage écran déporté (speaker arrivée)
- [ ] Calculs classements avancés (scratch, catégories, équipes)
- [ ] Export résultats (PDF, CSV, Excel)
- [ ] Interface administration avancée
- [ ] Gestion des vagues (départs multiples)
- [ ] Tests de charge (10000+ participants simulés)

---

### 🔄 Phase 1 - Préparation Raspberry (FUTUR)

**Objectif** : Préparer l'app pour déploiement Raspberry

**À faire** :
- [ ] **Interface `/setup`** : Configuration rôle MASTER/ESCLAVE
  - Détection auto du numéro de lecteur (serial)
  - Sélection rôle (boutons MASTER/ESCLAVE)
  - Si ESCLAVE : saisie IP du MASTER
  - Sauvegarde dans `storage/app/config/reader.json`
  - Redémarrage services selon le rôle
- [ ] Script d'installation automatique Raspberry Pi
- [ ] Configuration optimisée pour Raspberry (performance)
- [ ] Mode déconnecté avancé (buffer local SQLite)
- [ ] Endpoint API pour Pi ESCLAVES
- [ ] Tests sur 1 Raspberry (prototype)

---

### 📡 Phase 2 - Multi-Événements (FUTUR)

**Objectif** : Système centralisé + monitoring multi-événements

**À faire** :
- [ ] Connexion DB centrale (configuration via `.env`)
- [ ] Sauvegarde automatique (cron toutes les X minutes)
- [ ] Restauration depuis backup central
- [ ] Dashboard supervision (monitoring 5+ marathons)
- [ ] Système d'alertes (lecteur déconnecté, anomalie)
- [ ] API publique pour affichage live (ats-sport.com)

---

### 🖥️ Phase 3 - Contrôle à Distance (FUTUR)

**Objectif** : Prise de contrôle et gestion à distance

**À faire** :
- [ ] Installation VNC/RealVNC sur Pi MASTER
- [ ] Configuration VPN sécurisé
- [ ] Interface web de gestion (optionnel)
- [ ] Logs centralisés (debugging à distance)
- [ ] Système de mise à jour OTA (Over-The-Air)

---

### 📺 Phase 4 - Écrans & Affichage (FUTUR)

**Objectif** : Affichage temps réel pour speakers et public

**À faire** :
- [ ] Page écran déporté (`/screen/arrivals`)
- [ ] WebSocket pour mise à jour temps réel
- [ ] Design plein écran optimisé TV
- [ ] Mode speaker (dernières arrivées + infos coureur)
- [ ] Intégration API vers ats-sport.com (live)

---

### 📦 Phase 5 - Archivage & Export (FUTUR)

**Objectif** : Gestion fin de course et archivage

**À faire** :
- [ ] Bouton "Terminer la course"
- [ ] Validation arbitres (interface)
- [ ] Export complet vers DB centrale
- [ ] Publication automatique sur ats-sport.com
- [ ] Archivage automatique (compression + stockage)
- [ ] Purge données locales post-événement

---

### 🎓 Phase 6 - Formation & Production (FUTUR)

**Objectif** : Déploiement sur les 20 Raspberry + formation équipe

**À faire** :
- [ ] Documentation utilisateur complète
- [ ] Vidéos de formation
- [ ] Checklist pré-événement
- [ ] Procédures de dépannage
- [ ] Test grandeur nature (vraie course)
- [ ] Déploiement sur les 20 Raspberry

---

## 🏗️ Principes d'Architecture

### Configuration Flexible

**Tout doit être configurable via `.env`** :

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=vpn.ats-sport.com      # Serveur central ou localhost
DB_PORT=3306
DB_DATABASE=ats_sport_chronofront
DB_USERNAME=chronofront
DB_PASSWORD=***

# Reader Configuration
READER_MODE=master              # master ou slave
READER_SERIAL=107               # Numéro du lecteur
MASTER_API_URL=http://192.168.10.157:8000/api  # Pour Pi ESCLAVES

# Backup Configuration
BACKUP_ENABLED=true
BACKUP_INTERVAL=5               # minutes
BACKUP_SERVER=https://backup.ats-sport.com

# VPN Configuration
VPN_ENABLED=true
VPN_SERVER=vpn.ats-sport.com
```

### API REST Complète

**Toutes les opérations via API** :
- ✅ CRUD Events, Races, Waves, Entrants, Results
- ✅ Import CSV
- ✅ Détections RFID
- ✅ Monitoring lecteurs
- 🔄 Backup/Restore
- 🔄 Synchronisation multi-Pi

### Mode Dégradé

**L'app doit fonctionner sans Internet** :
- ✅ LocalStorage pour temps en attente
- ✅ Synchronisation auto quand connexion revient
- 🔄 SQLite local pour buffer (phase 1)
- 🔄 Reprise automatique après panne

### Modularité

**Composants indépendants** :
- Backend API (Laravel)
- Frontend Interface (Alpine.js)
- Écrans déportés (page séparée)
- Dashboard supervision (app séparée)
- Scripts Pi ESCLAVES (Python/PHP)

---

## 📋 Checklist Technique

### Compatibilité Raspberry

- [x] Configuration via `.env` (pas de chemins en dur)
- [x] API REST (découplage frontend/backend)
- [x] Mode hors ligne (LocalStorage)
- [ ] Optimisation mémoire (Raspberry 2GB RAM)
- [ ] Logs rotatifs (éviter saturation SD card)
- [ ] Monitoring ressources (CPU, RAM, disque)

### Sécurité

- [ ] Authentification API (Laravel Sanctum)
- [ ] VPN sécurisé (WireGuard)
- [ ] HTTPS (certificats Let's Encrypt)
- [ ] Backup chiffrés
- [ ] Logs sécurisés (pas d'infos sensibles)

### Performance

- [ ] Cache Redis (optionnel)
- [ ] Queue Laravel (jobs asynchrones)
- [ ] Index MySQL optimisés
- [ ] Compression données (backup)
- [ ] Tests charge (10000+ participants)

---

## 🔌 Architecture Réseau

### Connexion Pi ESCLAVES → Pi MASTER

```
Pi ESCLAVE (Départ)         Pi MASTER (Arrivée)
192.168.10.101       →      192.168.10.157:8000
   |                              |
   |  POST /api/raspberry         |
   |  {                           |
   |    "serial": "2000101",      |
   |    "timestamp": 1234567.089  |
   |  }                           |
   └─────────────────────────────→
```

### Connexion Pi MASTER → Serveur Central

```
Pi MASTER               VPN/4G              Serveur Central
192.168.10.157    ←→ WireGuard VPN ←→   vpn.ats-sport.com
    |                                         |
    |  Sauvegarde toutes les 5 min           |
    |  POST /api/backup                      |
    |  { event_id, data }                    |
    └────────────────────────────────────────→
```

---

## 🎯 Cas d'Usage

### Scénario 1 : Événement Standard

**Marathon avec 3 lecteurs (départ, inter, arrivée)** :

1. **Setup** :
   - 3 Raspberry (1 MASTER + 2 ESCLAVES)
   - Connexion câble réseau (RJ45)
   - 4G + VPN pour MASTER

2. **Configuration** :
   - Pi MASTER : `READER_MODE=master`, `DB_HOST=vpn.ats-sport.com`
   - Pi ESCLAVES : `READER_MODE=slave`, `MASTER_API_URL=http://192.168.10.157:8000`

3. **Déroulement** :
   - Les 3 Pi détectent les passages RFID
   - Pi ESCLAVES envoient vers Pi MASTER
   - Pi MASTER calcule les temps et envoie vers DB centrale
   - Écrans speakers affichent les arrivées en temps réel
   - Dashboard central monitore l'événement

4. **Fin de course** :
   - Export complet vers DB centrale
   - Validation arbitres
   - Publication sur ats-sport.com
   - Archivage automatique

### Scénario 2 : Multi-Événements Simultanés

**5 marathons le même week-end** :

1. **Setup** :
   - 15 Raspberry (5 événements × 3 lecteurs)
   - Tous connectés au serveur central via 4G+VPN

2. **Monitoring** :
   - Dashboard central affiche les 5 marathons
   - Alertes si lecteur déconnecté
   - Agent ATS peut prendre contrôle à distance (VNC vers Pi MASTER)

3. **Fin de journée** :
   - Export des 5 événements vers DB centrale
   - Archivage et purge
   - Préparation pour le lendemain

---

## 🛠️ Outils & Scripts

### Script d'Installation (à créer)

```bash
# install-chronofront.sh
#!/bin/bash

# Installation automatique ChronoFront sur Raspberry Pi
# Usage: ./install-chronofront.sh [master|slave]

MODE=$1

echo "🚀 Installation ChronoFront - Mode: $MODE"

# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install dependencies
sudo apt install -y nginx php8.2-fpm php8.2-mysql php8.2-cli \
    php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip \
    mysql-client git composer

# 3. Clone repository
cd /var/www
sudo git clone https://github.com/HeimdaIIr/ChronoFront.git chronofront

# 4. Install Laravel dependencies
cd chronofront
composer install --no-dev --optimize-autoloader

# 5. Configure .env
if [ "$MODE" == "master" ]; then
    sudo cp .env.master.example .env
else
    sudo cp .env.slave.example .env
fi

# 6. Generate key
php artisan key:generate

# 7. Run migrations (master only)
if [ "$MODE" == "master" ]; then
    php artisan migrate --force
    php artisan db:seed --class=CategorySeeder
    php artisan db:seed --class=ReaderSeeder
fi

# 8. Configure Nginx
# [Configuration Nginx ici]

echo "✅ Installation terminée !"
```

---

## 📝 Notes Importantes

### Points d'Attention

1. **Ne pas coder en dur l'IP du serveur** → Toujours via `.env`
2. **Prévoir mode dégradé** → LocalStorage + SQLite
3. **Logs rotatifs** → SD card Raspberry = espace limité
4. **Tests de charge** → Simuler 20000 participants avant prod
5. **Documentation utilisateur** → Interface intuitive mais doc complète

### Décisions Techniques à Valider

- [ ] MySQL local sur Pi MASTER ou uniquement DB centrale ?
- [ ] WebSocket ou polling pour écrans temps réel ?
- [ ] VNC ou TeamViewer pour prise de contrôle ?
- [ ] Redis cache sur Raspberry ? (besoin ?)
- [ ] Compression backup (gzip, bz2) ?

---

## 📞 Contacts & Ressources

### Documentation Technique

- Laravel 11 : https://laravel.com/docs/11.x
- Raspberry Pi : https://www.raspberrypi.com/documentation/
- Nginx : https://nginx.org/en/docs/
- WireGuard VPN : https://www.wireguard.com/

### Support ATS-Sport

- **Email** : support@ats-sport.com
- **Repo GitHub** : https://github.com/HeimdaIIr/ChronoFront
- **Dashboard** : https://dashboard.ats-sport.com (à créer)

---

## 🏁 Conclusion

**Ce document est un work-in-progress**. Il sera mis à jour au fur et à mesure du développement.

**Prochaine étape** : Finir Phase 0 (app sur PC) → Tester sur vraie course → Phase 1 (Raspberry)

---

**Dernière mise à jour** : 2025-11-26
**Version** : 0.1 (Draft Initial)
**Auteur** : Équipe ATS-Sport + Claude AI
