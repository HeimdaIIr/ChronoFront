# ChronoFront - Documentation Complète de l'Application

## 🗄️ BASE DE DONNÉES - SCHÉMA COMPLET

### Table `events` (Événements sportifs)
- `id` - Primary key
- `name` - Nom de l'événement (200 chars)
- `date_start` - Date de début
- `date_end` - Date de fin
- `location` - Lieu (200 chars, nullable)
- `description` - Description (text, nullable)
- `is_active` - Événement actif (boolean, default: true)
- `created_at`, `updated_at` - Timestamps

### Table `categories` (Catégories FFA 2025)
- `id` - Primary key
- `name` - Nom catégorie (ex: SE-M, M0-F)
- `code` - Code catégorie
- `gender` - Sexe (M ou F)
- `age_min` - Âge minimum
- `age_max` - Âge maximum
- `color` - Couleur d'affichage (default: #3B82F6)
- `created_at`, `updated_at` - Timestamps

**36 Catégories FFA officielles 2025** (seedées automatiquement)

### Table `races` (Épreuves/Parcours)
- `id` - Primary key
- `event_id` - FK vers events (cascade delete)
- `display_order` - Ordre d'affichage (nullable)
- `name` - Nom du parcours (200 chars)
- `type` - Type: '1_passage', 'n_laps', 'infinite_loop' (default: '1_passage')
- `distance` - Distance en km (decimal 8,2, default: 0)
- `laps` - Nombre de tours (default: 1)
- `best_time` - Meilleur temps uniquement (boolean, default: false)
- `description` - Description (text, nullable)
- `start_time` - **Heure TOP DÉPART** (timestamp, nullable)
- `end_time` - Heure de fin (timestamp, nullable)
- `created_at`, `updated_at` - Timestamps

### Table `waves` (Vagues/SAS de départ)
- `id` - Primary key
- `race_id` - FK vers races (cascade delete)
- `wave_number` - Numéro de vague (nullable)
- `name` - Nom de la vague (100 chars)
- `start_time` - Heure de départ vague (timestamp, nullable)
- `end_time` - Heure de fin (timestamp, nullable)
- `is_started` - Vague démarrée (boolean, default: false)
- `created_at`, `updated_at` - Timestamps

### Table `entrants` (Participants/Coureurs)
- `id` - Primary key
- `firstname` - Prénom (100 chars)
- `lastname` - Nom (100 chars)
- `gender` - Sexe (M ou F)
- `birth_date` - Date de naissance (date, nullable)
- `email` - Email (200 chars, nullable)
- `phone` - Téléphone (50 chars, nullable)
- `rfid_tag` - Tag RFID (50 chars, nullable) - Format: "2000" + dossard
- `bib_number` - Numéro de dossard (20 chars, nullable)
- `category_id` - FK vers categories (set null)
- `race_id` - FK vers races (cascade delete, nullable)
- `wave_id` - FK vers waves (set null, nullable)
- `club` - Club (200 chars, nullable)
- `team` - Équipe (200 chars, nullable)
- `created_at`, `updated_at` - Timestamps

### Table `readers` (Lecteurs RFID Raspberry Pi)
- `id` - Primary key
- `serial` - Numéro de série (unique, ex: '107')
- `name` - Nom convivial (nullable)
- `event_id` - FK vers events (cascade delete)
- `race_id` - FK vers races (cascade delete, nullable)
- `location` - Emplacement (ex: 'ARRIVEE', 'DEPART', 'KM5')
- `anti_rebounce_seconds` - Secondes anti-rebond (default: 5)
- `date_min` - Date de début activation (datetime)
- `date_max` - Date de fin activation (datetime)
- `is_active` - Lecteur actif (boolean, default: true)
- `clone_reader_id` - ID lecteur cloné pour logging (nullable)
- `test_terrain` - A envoyé des données au moins une fois (boolean, default: false)
- `date_test` - **Dernière communication Raspberry** (datetime, nullable)
- `created_at`, `updated_at` - Timestamps

**DÉTECTION CONNEXION:** Un lecteur est "en ligne" si `date_test` existe et < 60 secondes

### Table `results` (Résultats/Détections de passages)
- `id` - Primary key
- `race_id` - FK vers races (cascade delete)
- `entrant_id` - FK vers entrants (cascade delete)
- `wave_id` - FK vers waves (set null, nullable)
- `reader_id` - FK vers readers (set null, nullable)
- `rfid_tag` - Tag RFID détecté (50 chars)
- `serial` - Serial complet du lecteur (nullable)
- `reader_location` - Emplacement de détection (nullable)
- `raw_time` - **Heure de passage brute** (timestamp)
- `calculated_time` - **Temps calculé en secondes** (integer, nullable)
- `lap_number` - Numéro de tour (default: 1)
- `lap_time` - Temps du tour en secondes (integer, nullable)
- `speed` - Vitesse moyenne km/h (decimal 8,2, nullable)
- `position` - Position scratch (integer, nullable)
- `category_position` - Position catégorie (integer, nullable)
- `status` - Statut: 'V', 'DNS', 'DNF', 'DSQ', 'NS' (default: 'V')
- `is_manual` - Ajout manuel ou RFID (boolean, default: false)
- `created_at`, `updated_at` - Timestamps
- **UNIQUE INDEX:** (race_id, entrant_id, lap_number) - évite doublons

---

## 📦 MODÈLES ELOQUENT

### Event
**Relations:**
- `hasMany` races
- `hasMany` screens
- `hasMany` readers

### Category
**Méthodes:**
- `entrants()` - HasMany entrants

**Seedées:** 36 catégories FFA 2025 officielles

### Race
**Relations:**
- `belongsTo` event
- `hasMany` waves
- `hasMany` entrants
- `hasMany` results
- `hasMany` screens
- `hasMany` classements

### Wave
**Relations:**
- `belongsTo` race
- `hasMany` entrants
- `hasMany` results

### Entrant
**Relations:**
- `belongsTo` category
- `belongsTo` race
- `belongsTo` wave
- `hasMany` results

**Attributs calculés:**
- `age` - Calcule l'âge depuis birth_date
- `full_name` - Retourne "firstname lastname"

**Méthodes:**
- `assignCategory()` - Assigne automatiquement catégorie FFA selon âge/sexe

### Reader
**Relations:**
- `belongsTo` event
- `belongsTo` race (nullable)

**Méthodes:**
- `isCurrentlyActive()` - Vérifie si actif selon date_min/date_max
- `getActiveConfig(serial)` - Static: récupère config active par serial
- `markAsTested()` - Met à jour test_terrain=true et date_test=now()

### Result
**Relations:**
- `belongsTo` race
- `belongsTo` entrant
- `belongsTo` wave
- `belongsTo` reader

**Attributs calculés:**
- `formatted_time` - Format HH:MM:SS du calculated_time
- `formatted_lap_time` - Format HH:MM:SS du lap_time

**Méthodes:**
- `calculateTime()` - **Calcule temps: raw_time - (wave.start_time OU race.start_time)**
- `calculateSpeed(distance)` - Calcule vitesse km/h
- Sauvegarde automatique après calculs

---

## 🌐 ROUTES API (routes/api.php)

### Events
- `GET /api/events` - Liste tous les événements
- `POST /api/events` - Créer événement
- `GET /api/events/{id}` - Détails événement
- `PUT /api/events/{id}` - Modifier événement
- `DELETE /api/events/{id}` - Supprimer événement

### Races (Épreuves)
- `GET /api/races` - Liste toutes les épreuves
- `GET /api/races/event/{eventId}` - Épreuves par événement
- `POST /api/races` - Créer épreuve
- `GET /api/races/{id}` - Détails épreuve
- `PUT /api/races/{id}` - Modifier épreuve
- `DELETE /api/races/{id}` - Supprimer épreuve
- **`POST /api/races/{id}/start` - Donner TOP DÉPART (enregistre start_time)**
- `POST /api/races/{id}/end` - Terminer épreuve
- `POST /api/races/update-order` - Modifier ordre affichage

### Waves (Vagues)
- `GET /api/waves` - Liste toutes les vagues
- `GET /api/waves/race/{raceId}` - Vagues par épreuve
- `POST /api/waves` - Créer vague
- `GET /api/waves/{id}` - Détails vague
- `PUT /api/waves/{id}` - Modifier vague
- `DELETE /api/waves/{id}` - Supprimer vague
- `POST /api/waves/{id}/start` - Démarrer vague
- `POST /api/waves/{id}/end` - Terminer vague
- `POST /api/waves/{id}/assign-all` - Assigner tous les participants

### Categories
- `GET /api/categories` - Liste catégories
- `POST /api/categories` - Créer catégorie
- `POST /api/categories/init-ffa` - Initialiser 36 catégories FFA 2025
- `GET /api/categories/{id}` - Détails catégorie
- `PUT /api/categories/{id}` - Modifier catégorie
- `DELETE /api/categories/{id}` - Supprimer catégorie

### Entrants (Participants)
- `GET /api/entrants` - Liste participants (filtres: search, race_id)
- `POST /api/entrants` - Créer participant
- `GET /api/entrants/{id}` - Détails participant
- `PUT /api/entrants/{id}` - Modifier participant
- `DELETE /api/entrants/{id}` - Supprimer participant
- `GET /api/entrants/search?q=` - Recherche participants
- **`POST /api/entrants/import` - IMPORT CSV COMPLET**

### Results (Résultats)
- **`GET /api/results` - Tous les résultats (derniers 100)**
- `GET /api/results/race/{raceId}` - Résultats par épreuve
- **`POST /api/results/time` - Ajouter temps manuel**
- `PUT /api/results/{id}` - Modifier résultat
- `DELETE /api/results/{id}` - Supprimer résultat
- `POST /api/results/race/{raceId}/recalculate` - Recalculer positions
- `GET /api/results/race/{raceId}/export` - Export CSV résultats

### Readers (Lecteurs RFID)
- `GET /api/readers` - Liste lecteurs (avec is_online calculé)
- **`GET /api/readers/event/{eventId}` - Lecteurs par événement (avec is_online)**
- `POST /api/readers` - Créer lecteur
- `GET /api/readers/{id}` - Détails lecteur
- `PUT /api/readers/{id}` - Modifier lecteur
- `DELETE /api/readers/{id}` - Supprimer lecteur

### Raspberry (Réception données RFID)
- **`POST /api/raspberry` - Réception détections RFID depuis Raspberry Pi**
- **`PUT /api/raspberry` - Réception détections RFID (alias)**

**Format attendu:**
```json
Header: Serial: 107
Body: [
  {"serial": "2000003", "timestamp": 743084027.091},
  {"serial": "2000125", "timestamp": 743084028.234}
]
```

### Autres
- `GET /api/health` - Health check

---

## 🖥️ PAGES WEB (routes/web.php)

- `GET /` - Dashboard (chronofront.dashboard)
- `GET /events` - Gestion événements (chronofront.events)
- `GET /races` - Gestion épreuves (chronofront.races)
- `GET /entrants` - Gestion participants (chronofront.entrants)
- `GET /entrants/import` - Import CSV participants (chronofront.entrants-import)
- `GET /waves` - Gestion vagues (chronofront.waves)
- **`GET /timing` - Interface chronométrage temps réel (chronofront.timing)**
- `GET /results` - Résultats et classements (chronofront.results)
- `GET /categories` - Gestion catégories FFA (chronofront.categories)

---

## ⚙️ FONCTIONNALITÉS CLÉS

### 1. IMPORT CSV PARTICIPANTS
**Endpoint:** `POST /api/entrants/import`

**Colonnes CSV reconnues** (français/anglais):
- `nom` / `lastname` - Nom famille
- `prenom` / `firstname` - Prénom
- `sexe` / `gender` - M ou F
- `naissance` / `birth_date` - Date naissance (DD/MM/YYYY ou autre)
- `parcours` / `race` - Nom du parcours
- `vague` / `wave` - Nom ou numéro de vague
- `cat` / `category` - Code catégorie (optionnel)
- `club` - Club
- `dossard` / `bib` - Numéro de dossard

**Fonctionnement:**
1. Parse CSV, map colonnes
2. **Crée automatiquement races** si n'existent pas (colonne PARCOURS)
3. **Crée automatiquement vagues** si n'existent pas (colonne VAGUE)
4. **Génère RFID automatiquement** : "2000" + dossard
5. **Assigne catégorie FFA** selon âge/sexe automatiquement
6. Transaction atomique (rollback si erreur)
7. Retourne: imported, total_rows, races_created, waves_created, errors

### 2. DÉTECTION CONNEXION LECTEURS
**Logique implémentée:**

Quand Raspberry envoie données → `POST /api/raspberry`:
- Appelle `Reader::markAsTested()`
- Met à jour `test_terrain = true`
- Met à jour `date_test = now()`

Quand API retourne lecteurs → `GET /api/readers/event/{id}`:
- Calcule `is_online` en temps réel:
  - `is_online = true` si `date_test` existe ET < 60 secondes
  - `is_online = false` sinon
- Ajoute `connection_status`: 'never_connected', 'online', 'offline'
- Ajoute `last_seen` si offline

**Interface timing ping toutes les 10 secondes** pour rafraîchir statut

### 3. TOP DÉPART
**Endpoint:** `POST /api/races/{id}/start`

**Fonctionnement:**
1. Vérifie race pas déjà démarrée
2. Met à jour `race.start_time = now()`
3. Retourne race avec start_time

**Interface:** Modal dans timing.blade.php avec liste races, bouton TOP DÉPART par race

### 4. DÉTECTION PASSAGES RFID
**Endpoint:** `POST /api/raspberry`

**Header requis:** `Serial: 107` (numéro série lecteur)

**Fonctionnement RaspberryController:**
1. Récupère serial du header
2. Cherche config lecteur active: `Reader::getActiveConfig(serial)`
3. Marque lecteur testé: `markAsTested()` → date_test = now()
4. Parse détections JSON
5. Pour chaque détection:
   - Convertit serial RFID → bib_number (enlève "200" préfixe)
   - Cherche entrant par bib_number
   - Vérifie anti-rebounce (secondes depuis dernier passage même lecteur)
   - Calcule numéro passage (lap_number)
   - Crée Result avec raw_time, reader_id, reader_location
   - **Appelle calculateResult()** qui calcule temps et vitesse
6. Retourne: processed, skipped, results

### 5. CALCUL TEMPS COUREURS
**Méthode:** `Result::calculateTime()`

**Logique:**
```php
if (wave.start_time existe) {
    startTime = wave.start_time
} else if (race.start_time existe) {
    startTime = race.start_time  // TOP DÉPART
} else {
    return (pas de temps de référence)
}

calculated_time = raw_time - startTime (en secondes)
```

**Méthode:** `Result::calculateSpeed(distance)`
```php
speed (km/h) = distance / (calculated_time / 3600)
```

**Calcul lap_time:**
- Si lap_number = 1: lap_time = calculated_time
- Sinon: lap_time = calculated_time - calculated_time_tour_precedent

**Formatage:**
- `formatted_time` - Attribut calculé: sprintf('%02d:%02d:%02d', heures, minutes, secondes)

### 6. INTERFACE CHRONOMÉTRAGE
**Route:** `GET /timing`
**Layout:** timing-layout.blade.php (sans sidebar Bootstrap, fullscreen)
**Vue:** timing.blade.php

**Composants:**
- **Sidebar navigation** (70px) - Liens vers toutes les pages
- **Top bar:**
  - Nom événement (chargé depuis DB)
  - Badge statut ("Course en cours" si races démarrées, "En attente" sinon)
  - Indicateur synchro (vert si tous lecteurs online, orange sinon)
  - Bouton retour dashboard
- **Zone horloge:**
  - Grande horloge temps réel (8rem, update chaque seconde)
  - Statuts lecteurs par événement:
    - "OK" (vert) si is_online = true
    - "Hors ligne" (orange) si is_online = false
  - Message si aucun lecteur configuré
- **Barre filtres:**
  - Recherche par dossard/nom
  - Filtres catégorie, SAS
  - **Bouton TOP DÉPART** → Ouvre modal
- **Tableau résultats:**
  - Colonnes: Dossard, Nom, Catégorie, SAS, Lecteur, **Temps calculé**, Heure détection
  - Auto-refresh toutes les 5 secondes
  - Click ligne → Affiche détail participant
- **Panneau détail** (400px droite):
  - Dossard, Nom
  - Épreuve, Vague, Lecteur
  - Heure détection
  - **TEMPS TOTAL** (formatted_time, gros chiffres verts)
  - Vitesse moyenne si disponible
  - Formulaire ajout temps manuel
- **Alert bar** (bas):
  - Affichée seulement si lecteurs hors ligne détectés
  - Message nombre lecteurs problématiques
  - Bouton fermer
- **Toast notifications:**
  - Succès/erreur actions (TOP DÉPART, temps manuel, etc.)
  - Auto-disparition 3 secondes

**Modal TOP DÉPART:**
- Liste toutes les races de l'événement
- Bouton vert "Donner le TOP" par race
- Disabled si déjà démarrée (affiche heure départ)
- Confirmation avant démarrage
- Appelle `POST /api/races/{id}/start`

**Alpine.js Data:**
```javascript
{
  eventName, currentEventId, currentTime,
  races, readers, results, displayedResults,
  selectedResult, searchQuery, filters,
  loading, saving, startingRace,
  alertMessage, toastMessage, showTopDepartModal,
  manualBib, intervals...
}
```

**Méthodes clés:**
- `loadEvent()` - Charge événement actif
- `loadRaces()` - Charge toutes les races
- `loadReaders()` - Charge lecteurs par événement (avec is_online)
- `loadAllResults()` - Charge derniers 100 résultats
- `topDepart(race)` - Donner TOP DÉPART
- `addManualTime()` - Ajouter temps manuel par dossard
- `startReaderPing()` - Ping lecteurs toutes les 10s
- `startAutoRefresh()` - Refresh résultats toutes les 5s

### 7. RECALCUL POSITIONS
**Endpoint:** `POST /api/results/race/{raceId}/recalculate`

**Logique:**
1. Récupère tous results status='V' pour la race
2. Groupe par entrant_id
3. Si race.best_time: garde meilleur temps, sinon dernier tour
4. Tri par calculated_time croissant
5. Assigne position scratch (1, 2, 3...)
6. Groupe par category_id
7. Assigne category_position par catégorie
8. Transaction atomique

---

## 🔄 WORKFLOW COMPLET DE L'APPLICATION

### Scénario: Course avec 1 lecteur ARRIVEE

**PHASE 1: PRÉPARATION**
1. Créer événement via `/events`
   - Remplir nom, dates, lieu
   - API: `POST /api/events`

2. Importer participants CSV via `/entrants/import`
   - Upload fichier avec colonnes: nom, prenom, sexe, naissance, parcours, vague, club, dossard
   - API: `POST /api/entrants/import`
   - **Résultat automatique:**
     - Races créées selon colonne PARCOURS
     - Vagues créées selon colonne VAGUE
     - RFID générés: "2000" + dossard
     - Catégories FFA assignées selon âge/sexe

3. Configurer lecteur via interface (page `/readers` à créer ou DB directe)
   - serial: '107'
   - event_id: ID événement
   - location: 'ARRIVEE'
   - date_min: début événement
   - date_max: fin événement
   - is_active: true

**PHASE 2: CHRONOMÉTRAGE**
4. Aller sur `/timing`
   - Interface charge:
     - Événement actif
     - Lecteurs configurés pour événement (1 seul: 107 ARRIVEE)
     - Statut lecteur: "ARRIVEE: Hors ligne" (pas encore allumé)

5. Allumer Raspberry Pi 107
   - Raspberry envoie heartbeat → `POST /api/raspberry`
   - `markAsTested()` → date_test = now()
   - Interface ping 10s après → "ARRIVEE: OK" (vert)

6. Donner TOP DÉPART
   - Click bouton "TOP DÉPART" dans interface timing
   - Modal s'ouvre avec liste races
   - Click race "10km" → Confirmation
   - API: `POST /api/races/{id}/start`
   - Backend: `race.start_time = now()` (ex: 08:00:00)
   - Interface: Badge "Course en cours" apparaît

**PHASE 3: DÉTECTIONS**
7. Coureur #003 franchit ligne arrivée à 08:23:45
   - Raspberry détecte RFID "2000003"
   - Raspberry: `POST /api/raspberry` avec header Serial:107, body [{serial: "2000003", timestamp: ...}]
   - RaspberryController:
     - Trouve Reader 107
     - `markAsTested()` → date_test = now()
     - Convertit "2000003" → bib_number: 3
     - Trouve Entrant bib=3
     - Crée Result:
       - raw_time = 08:23:45
       - reader_id = 107
       - reader_location = "ARRIVEE"
     - `calculateResult()`:
       - `calculateTime()`: 08:23:45 - 08:00:00 = 1425 secondes (23min 45s)
       - `calculateSpeed()`: si distance=10km → vitesse = 25.26 km/h
       - Sauvegarde formatted_time = "00:23:45"
   - Retourne succès

8. Interface timing auto-refresh 5s
   - `loadAllResults()` → GET /api/results
   - Tableau affiche:
     - Dossard: **3**
     - Nom: Prénom Nom
     - Catégorie: SE-M
     - SAS: 1
     - Lecteur: **ARRIVEE**
     - Temps: **00:23:45** (vert, gros)
     - Détection: 08:23:45

9. Click sur ligne coureur #3
   - Panneau détail droite affiche:
     - Dossard: #3
     - Nom: Prénom Nom
     - Épreuve: 10km
     - Vague: SAS 1
     - Lecteur: ARRIVEE
     - Détection: 08:23:45
     - **TEMPS TOTAL: 00:23:45** (gros chiffres verts)
     - Vitesse moyenne: 25.26 km/h

10. Ajout temps manuel si besoin
    - Formulaire panneau détail: saisir dossard
    - API: `POST /api/results/time` avec bib_number
    - Même logique calculateTime()

**PHASE 4: RÉSULTATS**
11. Recalculer positions
    - API: `POST /api/results/race/{raceId}/recalculate`
    - Assigne position scratch et catégorie

12. Export CSV
    - API: `GET /api/results/race/{raceId}/export`
    - Télécharge CSV avec tous résultats

---

## 🎨 DESIGN SYSTEM

### Interface Chronométrage (Dark Theme)
- Background principal: `#1a1d2e`
- Background secondaire: `#0f1117`
- Bordures: `#2a2d3e`
- Texte: `#e4e4e7`
- Texte secondaire: `#a1a1aa`
- Succès (OK, temps): `#22c55e`
- Warning (offline, attention): `#f59e0b`
- Erreur: `#ef4444`
- Primaire (boutons): `#3b82f6`

### Typography
- Font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif
- Horloge: 8rem, font-weight 200
- Temps coureur: 1.5rem, font-weight 700, vert
- Titres: font-weight 600

### Layout
- Sidebar: 70px fixe
- Top bar: 70px hauteur
- Panneau détail: 400px largeur
- Grid responsive

---

## 🔧 TECHNOLOGIES

- **Backend:** Laravel 11
- **Base de données:** MySQL
- **Frontend:** Alpine.js 3.x
- **HTTP Client:** Axios
- **Icons:** Bootstrap Icons 1.11
- **CSS:** Custom (pas de framework, pure CSS)
- **Markdown:** CommonMark spec pour affichage

---

## 📊 STATISTIQUES CODE

- **Migrations:** 15 fichiers
- **Modèles:** 9 modèles Eloquent
- **Contrôleurs API:** 7 contrôleurs, 1581 lignes total
- **Vues Blade:** 11 fichiers
- **Routes API:** 40+ endpoints
- **Routes Web:** 9 pages

---

## ✅ FONCTIONNALITÉS COMPLÈTES ET TESTÉES

1. ✅ Gestion événements (CRUD)
2. ✅ Gestion catégories FFA 2025 (36 catégories seedées)
3. ✅ Gestion épreuves/parcours (CRUD, ordre affichage)
4. ✅ Gestion vagues/SAS (CRUD, démarrage)
5. ✅ **Import CSV participants complet** (auto-création races/vagues, catégories)
6. ✅ Gestion participants (CRUD, recherche)
7. ✅ **Configuration lecteurs RFID**
8. ✅ **Détection connexion lecteurs temps réel** (date_test < 60s)
9. ✅ **Interface chronométrage fullscreen** (dark theme pro)
10. ✅ **TOP DÉPART par course** (modal, enregistrement start_time)
11. ✅ **Réception détections Raspberry** (POST /api/raspberry)
12. ✅ **Calcul temps automatique** (raw_time - race.start_time)
13. ✅ **Calcul vitesse** (si distance configurée)
14. ✅ **Affichage temps réel** (auto-refresh 5s)
15. ✅ **Ajout temps manuel** par dossard
16. ✅ Recalcul positions scratch et catégorie
17. ✅ Export CSV résultats
18. ✅ Anti-rebounce lecteurs
19. ✅ Support multi-tours (lap_number)
20. ✅ Gestion statuts (V, DNS, DNF, DSQ, NS)

---

## 🚀 PROCHAINES ÉTAPES POSSIBLES

- [ ] Page configuration lecteurs UI (actuellement DB directe)
- [ ] Statistiques dashboard temps réel
- [ ] Gestion écrans affichage public
- [ ] Classements temps réel
- [ ] WebSocket pour push temps réel (au lieu de polling 5s)
- [ ] Interface mobile responsive
- [ ] Multi-événements simultanés
- [ ] Historique modifications résultats
- [ ] Photos participants

---

**Version:** 1.0
**Dernière mise à jour:** 2025-11-27
**Commits récents:**
- `4109b99` - Statut lecteur explicite (jamais connecté vs hors ligne)
- `2a36846` - Détection RÉELLE connexion lecteurs via date_test
- `0f3040c` - Lecteurs par événement + calcul temps réel
- `55fd7fc` - Interface chronométrage données réelles uniquement
- `9f24606` - Correction import CSV
