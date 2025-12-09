# ChronoFront V2.0 - État d'avancement

Dernière mise à jour : 4 décembre 2025

## ✅ Fonctionnalités implémentées

### Interface de Chronométrage (timing.blade.php)

#### Filtres et Tri
- ✅ Filtres dynamiques par événement et point de passage
- ✅ Tri par colonne (dossard, nom, temps, vitesse, position)
- ✅ Recherche en temps réel par dossard ou nom
- ✅ Persistance du checkpoint sélectionné dans localStorage (clé: `chronofront_manual_checkpoint_${eventId}`)

#### Rafraîchissement Automatique
- ✅ Rafraîchissement smooth toutes les 3 secondes
- ✅ Défilement automatique vers les nouveaux passages
- ✅ Animation de surbrillance jaune pour les nouvelles entrées
- ✅ Système de timeline pour éviter les doublons (dernière MAJ affichée)

#### Système d'Alertes
- ✅ Alertes visuelles pour vitesses anormales (< 5 km/h ou > 25 km/h)
- ✅ Badge rouge "Lent" et bleu "Rapide" sur les lignes concernées
- ✅ Compteur d'alertes en temps réel dans l'en-tête
- ✅ Bouton "Afficher alertes" pour filtrer uniquement les passages suspects

#### Seuils de Vitesse
- ✅ Configuration par course des vitesses min/max
- ✅ Modal d'édition avec sauvegarde instantanée
- ✅ Affichage des seuils configurés dans l'en-tête

#### Statut Coureur
- ✅ Affichage du statut dans le tableau (Validé, DNF, DSQ, NS)
- ✅ Badge coloré selon le statut (vert, rouge, orange, gris)
- ✅ Compteurs par statut dans l'en-tête

#### Saisie Manuelle de Temps
- ✅ Modal compact (500px) avec formulaire optimisé
- ✅ Import CSV de temps avec validation
- ✅ Support des formats : `Dossard,Temps` ou `Dossard;Temps`
- ✅ Validation des dossards (existence dans l'événement)
- ✅ Preview des entrées avant soumission
- ✅ Suppression individuelle d'une entrée
- ✅ Affichage scrollable avec max-height pour 3+ entrées

#### Système ABD (Abandon)
- ✅ Option "ABD (Abandon)" intégrée directement dans le dropdown "Point de passage"
- ✅ Saisie manuelle par dossard (un par ligne)
- ✅ Import CSV de dossards en abandon
- ✅ Validation et marquage automatique des coureurs en DNF
- ✅ Gestion des erreurs (dossards non trouvés)

### Gestion des Participants (entrants.blade.php)

#### Import CSV
- ✅ Import de participants avec création automatique des races
- ✅ Détection et mise à jour des participants existants (pas de doublons)
- ✅ Vérification par `bib_number` + `event_id`
- ✅ Attribution automatique de `event_id` lors de l'import
- ✅ Génération automatique des RFID tags (format: 2000XXXX)
- ✅ Attribution automatique des catégories FFA

#### Gestion en Masse
- ✅ Bouton "Supprimer Tous" avec double confirmation
- ✅ API endpoint `DELETE /entrants/delete-all`
- ✅ Sécurité : confirmation obligatoire avant suppression

### Base de Données

#### Migrations
- ✅ Ajout de la colonne `event_id` dans la table `entrants`
- ✅ Contrainte de clé étrangère vers `events.id`
- ✅ Cascade on delete pour maintenir l'intégrité référentielle

#### Modèles
- ✅ `event_id` ajouté au `$fillable` du modèle Entrant
- ✅ Contrainte unique sur `results` : (`race_id`, `entrant_id`, `lap_number`)

### Écran Speaker/Animateur (speaker.blade.php)

#### Affichage Live
- ✅ Route `/screens/speaker` pour affichage déporté
- ✅ Flux live ultra-rapide (rafraîchissement toutes les 2 secondes)
- ✅ API endpoint `/api/results/live-feed` pour les derniers résultats

#### Design et Typographie
- ✅ Police professionnelle Bebas Neue (style timing4you)
- ✅ Design noir/doré très sport et professionnel
- ✅ Affichage plein écran sans distractions

#### Affichage Adaptatif
- ✅ Tailles configurables : 5, 10 ou 20 lignes
- ✅ Sizing viewport-based pour adaptation à toutes résolutions
- ✅ Calcul dynamique des hauteurs : `calc((100vh - header) / nombre_lignes)`
- ✅ Fonts adaptatifs avec `calc(100vh / diviseur)`

#### Informations Affichées
- ✅ Colonnes : Dossard / Pos / Pos/Cat / Nom et Prénom / Cat. / Sexe / Parcours / Club / Temps
- ✅ Colonne Intermédiaires (affichée uniquement si checkpoints configurés)
- ✅ Temps intermédiaires automatiques depuis les lecteurs RFID
- ✅ Tri par ordre de passage (plus récent en haut)
- ✅ Animation de surbrillance pour nouveaux passages

#### Backend Intermédiaires
- ✅ Calcul automatique des temps intermédiaires par checkpoint
- ✅ Utilisation de `checkpoint_order` et `distance_from_start` des readers
- ✅ Format : location + temps (ex: "KM5: 00:23:45")
- ✅ Tri automatique par ordre de checkpoints

### Système Multi-Lecteurs RFID (readers.blade.php)

#### Gestion des Lecteurs
- ✅ Interface complète à `/events/{id}/readers`
- ✅ CRUD complet : création, modification, suppression de lecteurs
- ✅ Configuration par lecteur :
  - Numéro de série (détermine l'IP automatiquement)
  - Localisation (DEPART, KM5, ARRIVEE, etc.)
  - Distance depuis le départ (calcule l'ordre automatiquement)
  - Anti-rebond (secondes entre 2 lectures du même dossard)
  - Association à un parcours spécifique (optionnel)
  - Statut actif/inactif

#### Calcul Automatique de l'IP
- ✅ Formule : `192.168.10.{150 + XX}` où XX = 2 derniers chiffres du serial
- ✅ Exemples :
  - Serial 107 → IP 192.168.10.157
  - Serial 112 → IP 192.168.10.162
- ✅ Affichage en temps réel dans l'interface

#### Statut de Connexion
- ✅ **Jamais connecté** (badge gris) : Aucune donnée reçue (`date_test` = NULL)
- ✅ **En ligne** (badge vert) : Dernière connexion < 20 secondes
- ✅ **Hors ligne** (badge rouge) : Dernière connexion > 20 secondes
- ✅ Affichage du dernier passage (ex: "il y a 2 minutes")

#### Fonction Ping
- ✅ Ping individuel via bouton sur chaque lecteur
- ✅ Ping groupé pour tous les lecteurs d'un événement
- ✅ Test de connexion HTTP vers l'IP calculée
- ✅ **Compatible connexion distante** (4G + VPN)
- ✅ Mise à jour automatique du statut après ping
- ✅ Timeout configurable (2s individuel, 1s groupé)

#### Réception RFID Automatique
- ✅ Endpoint : `POST/PUT /api/raspberry`
- ✅ Header requis : `Serial: XXX` (identifie le lecteur)
- ✅ Format JSON compatible Impinj Speedway :
  ```json
  [
    {"serial": "2000003", "timestamp": 743084027.091},
    {"serial": "2000125", "timestamp": 743084028.234}
  ]
  ```
- ✅ Traitement automatique :
  - Conversion serial → dossard (enlève préfixe "200")
  - Vérification anti-rebounce
  - Création résultat avec calcul temps + vitesse
  - Mise à jour `date_test` du lecteur (tracking connexion)
  - Gestion des passages multiples (lap_number)

#### Compatibilité Connectivité Distante
- ✅ **Aucune restriction IP locale** : fonctionne avec n'importe quelle IP accessible
- ✅ **Compatible 4G** : lecteurs avec dongles 4G
- ✅ **Compatible VPN** : accès via vpn.ats-sport.com
- ✅ **Ping distant** : test de connexion fonctionne sur VPN
- ✅ **Réception RFID distante** : endpoint accessible depuis internet
- ✅ **Multi-site** : plusieurs lecteurs à différents endroits (intermédiaires)

### API Backend

#### Endpoints Lecteurs RFID
- ✅ `GET /readers` - Liste tous les lecteurs
- ✅ `GET /readers/event/{eventId}` - Lecteurs d'un événement spécifique
- ✅ `POST /readers` - Créer un nouveau lecteur
- ✅ `PUT /readers/{reader}` - Modifier un lecteur
- ✅ `DELETE /readers/{reader}` - Supprimer un lecteur
- ✅ `POST /readers/{reader}/ping` - Tester connexion d'un lecteur
- ✅ `POST /readers/event/{eventId}/ping-all` - Tester tous les lecteurs d'un événement

#### Endpoints RFID (Raspberry Pi)
- ✅ `POST /raspberry` - Réception des détections RFID
- ✅ `PUT /raspberry` - Réception des détections RFID (alias)
  - Header requis : `Serial: XXX`
  - Format : Array de `{serial, timestamp}`
  - Traitement automatique avec anti-rebounce
  - Mise à jour `date_test` pour tracking connexion

#### Endpoints ABD
- ✅ `POST /results/mark-abd` - Marquer des coureurs en abandon
  - Validation : `event_id`, `bib_numbers[]`
  - Création automatique de résultats DNF avec :
    - `rfid_tag` (depuis entrant ou 'ABD' par défaut)
    - `raw_time` (timestamp actuel)
    - `is_manual: true`
    - `status: DNF`
  - Mise à jour si résultat existant
  - Recalcul automatique des positions

#### Endpoints Debug (temporaires)
- ✅ `POST /debug/fix-event-ids` - Peupler event_id pour participants existants
- ✅ `GET /debug/logs` - Consulter les 100 dernières lignes du log Laravel

#### Endpoints Entrants
- ✅ `DELETE /entrants/delete-all` - Supprimer tous les participants

#### Endpoints Live Feed
- ✅ `GET /results/live-feed` - Flux live pour écran speaker
  - Retourne les 50 derniers résultats validés
  - Inclut les temps intermédiaires calculés par checkpoint
  - Relations : entrant, category, race, reader

## 🔧 Correctifs Appliqués

### Session actuelle (9 décembre 2025)
1. **Documentation multi-lecteurs complétée** - Système RFID multi-sites documenté
2. **Compatibilité distante validée** - Support 4G + VPN confirmé
3. **Colonne Vitesse ajoutée** - Affichage vitesse sur écran speaker
4. **Chargement nom événement corrigé** - Support réponses paginées et arrays

### Session précédente (5 décembre 2025)
1. **Écran speaker créé** - Interface live pour animateur avec design professionnel
2. **Font Bebas Neue** - Typographie sport professionnelle style timing4you
3. **Sizing adaptatif** - Viewport-based responsive design (5/10/20 lignes exactes)
4. **Temps intermédiaires** - Calcul automatique et affichage des checkpoints

### Session précédente (4 décembre 2025)
1. **Checkpoint non persistant** - Ordre de chargement corrigé dans `loadEvent()`
2. **Modal trop grande** - Dimensions réduites (600px → 500px) avec scroll interne
3. **Import créant des doublons** - Vérification `bib_number` + `event_id` avant création
4. **Colonne event_id manquante** - Migration ajoutée pour table entrants
5. **event_id non sauvegardé** - Ajout dans `$fillable` du modèle Entrant
6. **ABD échouant silencieusement** - Champs requis `rfid_tag` et `raw_time` ajoutés

## 📋 Architecture Technique

### Frontend
- **Framework** : Alpine.js 3.x pour la réactivité
- **UI** : Bootstrap 5 avec personnalisation
- **Storage** : localStorage pour persistance des préférences utilisateur
- **HTTP** : Axios pour les requêtes API

### Backend
- **Framework** : Laravel 11
- **Base de données** : SQLite
- **Validation** : Laravel Request Validation
- **Transactions** : DB::beginTransaction() pour opérations atomiques

### Points de Passage Supportés
- DÉPART (start line)
- Points intermédiaires (customisables)
- ARRIVÉE (finish line)
- ABD (abandon/DNF)

## 🎯 Points Clés de Qualité

### UX/UI
- Interface responsive et moderne
- Feedback visuel immédiat sur toutes les actions
- Animations fluides pour les mises à jour
- Confirmations doubles pour actions destructives

### Performance
- Rafraîchissement optimisé (toutes les 3s)
- Pagination côté client (50 entrées/page)
- Requêtes API groupées quand possible

### Fiabilité
- Validation stricte des données (frontend + backend)
- Gestion d'erreurs exhaustive avec logs
- Transactions DB pour opérations critiques
- Prévention des doublons multi-niveaux

## 📝 Notes de Développement

### Conventions
- Commits en français, descriptifs
- Branche de développement : `claude/recover-chronofront-session-01GYDhsf53gAK1DJ6DSkNXx9`
- Format de commit : `fix:`, `feat:`, `refactor:`

### Environnement
- PHP 8.x
- Laravel 11
- SQLite
- Node.js pour assets (si nécessaire)

## 🚀 Prochaines Étapes Potentielles

### Améliorations UX
- [ ] Édition inline des temps manuels
- [ ] Export des résultats (PDF, Excel)
- [ ] Graphiques de performance en temps réel

### Administration
- [ ] Gestion des utilisateurs et permissions
- [ ] Audit trail des modifications
- [ ] Backup/restore automatique

### Performance
- [ ] Cache Redis pour résultats fréquents
- [ ] WebSocket pour push temps réel
- [ ] Optimisation des requêtes N+1

---

**Version** : 2.0
**Statut** : Production Ready
**Dernière contribution** : Session de récupération et améliorations ABD
