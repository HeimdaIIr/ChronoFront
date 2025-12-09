# Compatibilité Multi-Réseaux - ChronoFront V2.0

## 🚨 Problème Résolu

### Avant (Incompatible avec VPN)
ChronoFront calculait toujours les IPs selon la formule :
```
192.168.10.{150 + last2digits(serial)}
```

**Exemple :**
- Serial 120 → IP: `192.168.10.170` ❌

### VPN ATS Sport (Réel)
Le VPN utilise un schéma d'adressage différent :
```
10.8.0.{serial}
```

**Exemple :**
- Serial 120 → IP: `10.8.0.120` ✅

**Résultat :** Le ping et la communication ne fonctionnaient pas !

---

## ✅ Solution Implémentée

ChronoFront V2.0 supporte maintenant **3 types de réseaux** :

### 1. Local (192.168.10.X)
**Usage :** Déploiement sur site avec réseau local

**Calcul IP :**
```
192.168.10.{150 + last2digits(serial)}
```

**Exemples :**
| Serial | IP Calculée |
|--------|-------------|
| 107 | 192.168.10.157 |
| 112 | 192.168.10.162 |
| 120 | 192.168.10.170 |

**Scénario typique :**
- Lecteurs connectés au même switch/routeur
- Tous les lecteurs sur le même LAN
- Accès direct sans VPN

---

### 2. VPN ATS Sport (10.8.0.X)
**Usage :** Courses avec intermédiaires distants (4G + VPN)

**Calcul IP :**
```
10.8.0.{serial}
```

**Exemples :**
| Serial | IP VPN | Webconfig | ChronoFront |
|--------|--------|-----------|-------------|
| 120 | 10.8.0.120 | http://120.conf.ats-sport.com/ | http://120.course.ats-sport.com/ |
| 107 | 10.8.0.107 | http://107.conf.ats-sport.com/ | http://107.course.ats-sport.com/ |

**Architecture :**
```
┌─────────────────────────────────────────────────────────┐
│                  COURSE MULTI-SITES                     │
└─────────────────────────────────────────────────────────┘

DÉPART (Serial 107)          KM10 (Serial 112)          ARRIVÉE (Serial 120)
    │                              │                            │
    │ Dongle 4G                    │ Dongle 4G                  │ Dongle 4G
    └──────────┬──────────────────┴──────────────┬─────────────┘
               │                                  │
               └────► VPN (vpn.ats-sport.com) ◄──┘
                              │
                         10.8.0.0/24
                              │
                              ▼
                      ChronoFront Server
```

**Scénario typique :**
- Semi-marathon avec points intermédiaires
- Lecteur DÉPART + KM10 + ARRIVÉE
- Chaque lecteur a une clé 4G
- VPN pour centraliser les données

---

### 3. Custom (IP Personnalisée)
**Usage :** Configuration avancée, réseaux spéciaux

**Calcul IP :** Saisie manuelle

**Exemples :**
- `10.8.0.250` (IP fixe sur VPN)
- `192.168.1.100` (autre réseau local)
- `172.16.0.50` (réseau entreprise)

**Scénario typique :**
- Infrastructure réseau existante
- IP allouées par un administrateur
- Configuration non-standard

---

## 📋 Configuration dans ChronoFront

### Ajouter un Lecteur Local

1. **Accéder à** `/events/{id}/readers`
2. **Cliquer sur** "Ajouter un lecteur"
3. **Remplir :**
   - Numéro de série : `107`
   - Type de réseau : `Local (192.168.10.X)`
   - IP calculée : `192.168.10.157` ✅ (auto)
   - Localisation : `DEPART`
   - Distance : `0`

### Ajouter un Lecteur VPN

1. **Accéder à** `/events/{id}/readers`
2. **Cliquer sur** "Ajouter un lecteur"
3. **Remplir :**
   - Numéro de série : `120`
   - Type de réseau : `VPN ATS Sport (10.8.0.X)`
   - IP calculée : `10.8.0.120` ✅ (auto)
   - Localisation : `KM10`
   - Distance : `10`

### Ajouter un Lecteur Custom

1. **Accéder à** `/events/{id}/readers`
2. **Cliquer sur** "Ajouter un lecteur"
3. **Remplir :**
   - Numéro de série : `199`
   - Type de réseau : `IP Personnalisée`
   - IP personnalisée : `10.8.0.250` (champ apparaît)
   - Localisation : `ARRIVEE`
   - Distance : `21`

---

## 🔧 Tests de Connexion

### Ping Individuel

Après configuration, testez la connexion :

1. **Dans l'interface** `/events/{id}/readers`
2. **Cliquer sur** 📡 à côté du lecteur
3. **Résultat attendu :**

```
✓ Lecteur KM10
IP: 10.8.0.120 (vpn)
Statut: EN LIGNE
```

### Ping Groupé

Testez tous les lecteurs d'un coup :

1. **Cliquer sur** "Ping All"
2. **Résultats :**

```json
{
  "success": true,
  "results": [
    {
      "reader_id": 1,
      "serial": "107",
      "ip": "192.168.10.157",
      "network_type": "local",
      "status": "online"
    },
    {
      "reader_id": 2,
      "serial": "120",
      "ip": "10.8.0.120",
      "network_type": "vpn",
      "status": "online"
    }
  ]
}
```

---

## 🎯 Cas d'Usage Réels

### Course Simple (Local)

**Événement :** 10km urbain
**Setup :**
- 1 lecteur DÉPART/ARRIVÉE
- Réseau local WiFi/Ethernet

**Configuration :**
```
Serial: 107
Type: Local
IP: 192.168.10.157
```

---

### Semi-Marathon Multi-Sites (VPN)

**Événement :** Semi 21km avec intermédiaires
**Setup :**
- Lecteur DÉPART (Serial 107)
- Lecteur KM10 (Serial 112)
- Lecteur ARRIVÉE (Serial 120)
- Dongles 4G sur chaque lecteur
- VPN ATS Sport actif

**Configuration :**

| Point | Serial | Type | IP |
|-------|--------|------|-----|
| DEPART | 107 | VPN | 10.8.0.107 |
| KM10 | 112 | VPN | 10.8.0.112 |
| ARRIVEE | 120 | VPN | 10.8.0.120 |

**Vérification :**
- ✅ http://107.conf.ats-sport.com/ → Accès interface lecteur DÉPART
- ✅ http://120.conf.ats-sport.com/ → Accès interface lecteur ARRIVÉE
- ✅ Ping fonctionne vers toutes les IPs

---

### Configuration Hybride (Mixed)

**Événement :** Trail 30km
**Setup :**
- DÉPART/ARRIVÉE : réseau local (LAN)
- KM15 : lecteur isolé avec 4G (VPN)

**Configuration :**

| Point | Serial | Type | IP | Réseau |
|-------|--------|------|-----|---------|
| DEPART | 107 | Local | 192.168.10.157 | LAN WiFi |
| KM15 | 199 | VPN | 10.8.0.199 | 4G + VPN |
| ARRIVEE | 120 | Local | 192.168.10.170 | LAN WiFi |

---

## 🔍 Debugging

### Lecteur "Hors ligne" mais fonctionnel

**Symptôme :** Le badge est rouge mais les temps arrivent

**Cause :** Le ping HTTP ne fonctionne pas (firewall, pas de serveur web)

**Solution :** C'est normal ! Le statut "online" indique juste la réponse HTTP. Si les données RFID arrivent via `POST /api/raspberry`, le lecteur fonctionne.

---

### IP Calculée Incorrecte

**Symptôme :** L'IP affichée ne correspond pas

**Vérifications :**
1. **Type de réseau :** Local, VPN ou Custom ?
2. **Serial correct :** Vérifier le numéro exact
3. **VPN actif :** Si type=VPN, le VPN doit être UP

**Test :**
```bash
# Depuis le serveur ChronoFront
ping 10.8.0.120  # Devrait répondre si VPN actif
```

---

### Données RFID Non Reçues

**Symptôme :** Pas de résultats malgré lectures

**Checklist :**
1. ✅ Lecteur configuré dans `/events/{id}/readers`
2. ✅ `network_type` correct
3. ✅ Lecteur actif (`is_active` = true)
4. ✅ Endpoint configuré : `POST https://votre-domaine.com/api/raspberry`
5. ✅ Header `Serial: XXX` présent dans les requêtes

**Test manuel :**
```bash
curl -X POST https://votre-domaine.com/api/raspberry \
  -H "Serial: 120" \
  -H "Content-Type: application/json" \
  -d '[{"serial":"2000125","timestamp":1733745027.091}]'
```

---

## 📊 API Référence

### GET /api/readers/event/{eventId}

Récupère tous les lecteurs avec IP calculée

**Response :**
```json
[
  {
    "id": 1,
    "serial": "120",
    "network_type": "vpn",
    "custom_ip": null,
    "location": "KM10",
    "distance_from_start": 10,
    "calculated_ip": "10.8.0.120",  // ← Calculé automatiquement
    "web_config_url": "http://120.conf.ats-sport.com/",
    "chronofront_url": "http://120.course.ats-sport.com/",
    "is_online": true,
    "date_test": "2025-12-09 10:30:00"
  }
]
```

### POST /api/readers/{id}/ping

Teste la connexion vers un lecteur

**Response Success :**
```json
{
  "success": true,
  "message": "Reader is online",
  "ip": "10.8.0.120",
  "network_type": "vpn",
  "reader": { ... }
}
```

**Response Offline :**
```json
{
  "success": false,
  "message": "Reader is offline or unreachable",
  "ip": "10.8.0.120",
  "network_type": "vpn"
}
```

---

## 🔐 Sécurité

### VPN ATS Sport

**Points de sécurité :**
- ✅ Tunnel chiffré (OpenVPN)
- ✅ Authentification par certificat
- ✅ Réseau privé (10.8.0.0/24)
- ✅ Pas d'exposition internet directe

### Recommandations

1. **HTTPS Obligatoire** pour `/api/raspberry`
2. **Firewall** : autoriser uniquement IPs VPN
3. **Rate Limiting** : limiter requêtes /api/raspberry
4. **Monitoring** : alertes si lecteur offline > 5 min

---

## 📝 Notes de Migration

### Depuis Version Précédente

**Rétrocompatibilité :**
- ✅ Lecteurs existants : `network_type='local'` par défaut
- ✅ Ancien calcul IP : toujours fonctionnel
- ✅ API inchangée : `/api/raspberry` compatible

**Migration automatique :**
```sql
-- Tous les lecteurs existants passent en mode 'local'
ALTER TABLE readers ADD COLUMN network_type ENUM('local', 'vpn', 'custom') DEFAULT 'local';
```

**Pas d'action requise !** Le système continue de fonctionner.

---

## 📞 Support

**VPN ATS Sport :**
- Interface : http://vpn.ats-sport.com/
- Format lecteurs : `{serial}.conf.ats-sport.com`

**ChronoFront :**
- Documentation : `/MULTI_READER_GUIDE.md`
- Issues : GitHub repository

---

**Dernière mise à jour : 9 décembre 2025**
**Version ChronoFront : 2.0**
**Statut : Production Ready ✅**
