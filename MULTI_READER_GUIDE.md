# Guide Multi-Lecteurs RFID avec Connexion 4G + VPN

## Vue d'ensemble

ChronoFront V2.0 est **100% compatible** avec votre infrastructure de courses multi-sites :
- ✅ Lecteurs Raspberry Pi avec dongles 4G
- ✅ Accès distant via VPN (vpn.ats-sport.com)
- ✅ Points de passage intermédiaires (KM5, KM10, etc.)
- ✅ Synchronisation temps réel

---

## Architecture de Connexion

```
┌─────────────────────────────────────────────────────────────┐
│                    COURSE MULTI-SITES                       │
└─────────────────────────────────────────────────────────────┘

DÉPART (Lecteur 107)           KM5 (Lecteur 112)           ARRIVÉE (Lecteur 115)
     │                              │                             │
     │ Dongle 4G                    │ Dongle 4G                   │ Dongle 4G
     └──────────┬───────────────────┴──────────────┬──────────────┘
                │                                   │
                └───────────► VPN (vpn.ats-sport.com) ◄───────────┘
                                      │
                                      │ HTTPS
                                      ▼
                          ┌─────────────────────┐
                          │   ChronoFront API   │
                          │   /api/raspberry    │
                          └─────────────────────┘
```

---

## Configuration des Lecteurs

### 1. Accéder à l'interface de gestion

Depuis ChronoFront, pour chaque événement :
```
Navigation → Événements → [Votre événement] → "Lecteurs RFID"
URL: /events/{id}/readers
```

### 2. Ajouter un lecteur

**Informations requises :**
- **Numéro de série** : Ex: 107, 112, 115
  - Détermine automatiquement l'IP : `192.168.10.{150 + XX}`
  - Serial 107 → IP 192.168.10.157
  - Serial 112 → IP 192.168.10.162
  - Serial 115 → IP 192.168.10.165

- **Localisation** : DEPART, KM5, KM10, ARRIVEE, etc.

- **Distance depuis départ** : En kilomètres (ex: 0, 5, 10, 21)
  - Calcule automatiquement l'ordre des checkpoints
  - Utilisé pour générer les temps intermédiaires

- **Anti-rebond** : 3 secondes par défaut
  - Évite les lectures multiples du même dossard

- **Parcours associé** (optionnel) : Si le lecteur n'est que pour un parcours spécifique

### 3. Statut de connexion

Chaque lecteur affiche son statut en temps réel :

| Badge | Statut | Description |
|-------|--------|-------------|
| 🔘 Gris | Jamais connecté | Aucune donnée reçue |
| 🟢 Vert | En ligne | Dernière connexion < 20 secondes |
| 🔴 Rouge | Hors ligne | Dernière connexion > 20 secondes |

---

## Fonctionnement avec 4G + VPN

### Workflow de connexion

1. **Lecteur Raspberry Pi** (sur site distant)
   - Dongle 4G branché → connexion internet
   - Connexion VPN automatique → vpn.ats-sport.com
   - Adresse IP locale : 192.168.10.{150+XX}

2. **Détection RFID**
   - Coureur passe devant le lecteur
   - Tag RFID détecté : ex: `2000125` (dossard 125)
   - Timestamp précis enregistré

3. **Envoi vers ChronoFront**
   ```http
   POST https://votre-domaine.com/api/raspberry
   Header: Serial: 107
   Body:
   [
     {"serial": "2000125", "timestamp": 743084027.091}
   ]
   ```

4. **Traitement automatique**
   - ✅ Vérification lecteur actif (Serial: 107)
   - ✅ Conversion serial → dossard (2000125 → 125)
   - ✅ Recherche participant (dossard 125)
   - ✅ Vérification anti-rebounce (pas de double lecture)
   - ✅ Création résultat avec temps calculé depuis départ
   - ✅ Calcul vitesse (si distance configurée)
   - ✅ Mise à jour statut lecteur (date_test = now)
   - ✅ Affichage immédiat sur écran speaker

---

## Test de Connexion (Ping)

### Ping individuel
1. Accéder à `/events/{id}/readers`
2. Cliquer sur l'icône 📡 à côté du lecteur
3. ChronoFront envoie requête HTTP vers IP calculée
4. Résultat :
   - ✅ **En ligne** : Lecteur répond (date_test mis à jour)
   - ❌ **Hors ligne** : Timeout après 2 secondes

### Ping groupé
Bouton "Ping All" → teste tous les lecteurs de l'événement en parallèle

**Important** : Le ping fonctionne parfaitement via VPN si :
- ✅ Le lecteur est accessible via son IP sur le réseau VPN
- ✅ Le port HTTP (80) est ouvert
- ✅ Le serveur ChronoFront peut joindre le réseau VPN

---

## Points Intermédiaires

### Configuration
Les points intermédiaires sont **automatiquement générés** selon :
1. `checkpoint_order` (calculé depuis distance_from_start)
2. Lecteurs triés par ordre croissant de distance

### Exemple : Semi-Marathon (21km)

| Lecteur | Distance | Ordre | Localisation |
|---------|----------|-------|--------------|
| 107 | 0 km | 1 | DEPART |
| 112 | 5 km | 2 | KM5 |
| 108 | 10 km | 3 | KM10 |
| 115 | 21 km | 4 | ARRIVÉE |

### Affichage sur écran speaker

Pour le coureur **Dossard 125** :
```
Intermédiaires : KM5: 00:23:45 | KM10: 00:48:12
Temps Final : 01:42:30
```

Les temps intermédiaires s'affichent automatiquement si au moins un lecteur a `checkpoint_order` configuré.

---

## Format des Tags RFID

### Convention de nommage
```
Format: 200XXXX
        │  │
        │  └──> Numéro de dossard (avec zéros devant)
        └─────> Préfixe fixe
```

### Exemples
| Tag RFID | Dossard |
|----------|---------|
| 2000001 | 1 |
| 2000125 | 125 |
| 2001234 | 1234 |

Le système enlève automatiquement le préfixe "200" et les zéros à gauche.

---

## Troubleshooting

### Le lecteur n'apparaît pas "En ligne"

**Vérifications :**
1. ✅ Lecteur ajouté dans `/events/{id}/readers` avec bon serial
2. ✅ Lecteur configuré comme `actif` (is_active = true)
3. ✅ Dongle 4G connecté et VPN actif
4. ✅ IP calculée correcte (vérifier les 2 derniers chiffres du serial)
5. ✅ Tester le ping depuis l'interface

**Debug :**
```bash
# Vérifier dernière connexion
GET /api/readers/event/{eventId}

# Voir date_test et connection_status de chaque lecteur
```

### Les détections RFID n'arrivent pas

**Checklist :**
1. ✅ Header `Serial` présent dans la requête
2. ✅ Format JSON valide : `[{"serial": "...", "timestamp": ...}]`
3. ✅ Dossard existe dans les participants de l'événement
4. ✅ Anti-rebounce respecté (3 secondes minimum entre 2 lectures)
5. ✅ Lecteur actif pour l'événement

**Logs :**
```bash
# Consulter les logs Laravel
GET /api/debug/logs

# Logs RFID spécifiques
storage/logs/rfid/reader-{serial}-{date}.txt
```

### Temps intermédiaires ne s'affichent pas

**Vérifications :**
1. ✅ Plusieurs lecteurs configurés avec distances différentes
2. ✅ `checkpoint_order` calculé automatiquement (basé sur distance)
3. ✅ Au moins 2 résultats pour le même coureur (passage intermédiaire + arrivée)
4. ✅ Statut résultat = 'V' (validé)

---

## API Endpoints Clés

### Gestion lecteurs
```http
GET    /api/readers/event/{eventId}          # Liste lecteurs
POST   /api/readers                          # Créer lecteur
PUT    /api/readers/{reader}                 # Modifier lecteur
DELETE /api/readers/{reader}                 # Supprimer lecteur
POST   /api/readers/{reader}/ping            # Ping individuel
POST   /api/readers/event/{eventId}/ping-all # Ping groupé
```

### Réception RFID
```http
POST   /api/raspberry
Header: Serial: 107
Body: [{"serial": "2000125", "timestamp": 743084027.091}]
```

### Live Feed (écran speaker)
```http
GET    /api/results/live-feed
# Retourne 50 derniers résultats avec temps intermédiaires
```

---

## Sécurité

### Recommandations
1. ✅ **HTTPS obligatoire** en production pour /api/raspberry
2. ✅ **Authentification** possible via middleware Laravel
3. ✅ **Whitelist IP** des lecteurs dans le firewall (optionnel)
4. ✅ **VPN sécurisé** pour l'accès distant
5. ✅ **Rate limiting** sur /api/raspberry pour éviter spam

### Configuration VPN
```
VPN: vpn.ats-sport.com
- Lecteurs se connectent automatiquement
- IP locales maintenues : 192.168.10.{150+XX}
- ChronoFront accessible via domaine public ou IP VPN
```

---

## Support Multi-Événements

ChronoFront supporte **plusieurs événements simultanés** :
- Chaque événement a ses propres lecteurs
- Filtrage automatique par `event_id`
- Isolation complète des données
- Gestion indépendante des statuts

---

## Prochaines Évolutions Possibles

### À court terme
- [ ] Dashboard temps réel des lecteurs (carte géographique)
- [ ] Alertes SMS/email si lecteur hors ligne
- [ ] Export logs RFID en CSV
- [ ] Interface mobile pour gestion terrain

### À moyen terme
- [ ] Support WebSocket pour push temps réel
- [ ] Système de backup automatique multi-site
- [ ] Statistiques détection par lecteur (taux lecture, erreurs)
- [ ] Interface admin pour configuration VPN

---

**Documentation à jour : 9 décembre 2025**
**ChronoFront V2.0 - Production Ready**
