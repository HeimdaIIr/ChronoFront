# 📊 ChronoFront - État du Projet Phase 1

> Dernière mise à jour: **2025-12-02**
> Version actuelle: **1.5 - Phase 1 en cours**

---

## 🎯 OBJECTIF PHASE 1

Créer un système de chronométrage RFID fonctionnel avec:
- Détection automatique des passages via lecteurs Raspberry Pi
- Interface de chronométrage temps réel professionnelle
- Gestion manuelle des temps (bouton + import CSV/RFID)
- Calcul automatique des positions et catégories
- Export des résultats

---

## ✅ FONCTIONNALITÉS COMPLÉTÉES

### 🗄️ Base de données
- [x] **8 tables principales** (events, races, waves, categories, entrants, readers, results)
- [x] **36 catégories FFA 2025** seedées automatiquement
- [x] Relations Eloquent complètes
- [x] Migrations Laravel 11
- [x] Support SQLite avec gestion timezone manuelle

### 📦 Gestion de base
- [x] **Événements** - CRUD complet
- [x] **Épreuves/Parcours** - CRUD + ordre affichage
- [x] **Vagues/SAS** - CRUD + démarrage manuel
- [x] **Catégories FFA** - CRUD + init automatique
- [x] **Participants** - CRUD + recherche

### 📥 Import de données
- [x] **Import CSV participants** avec auto-création races/vagues
- [x] **Génération RFID automatique** (format "2000" + dossard)
- [x] **Attribution catégorie FFA** selon âge/sexe
- [x] **Import fichier RFID** depuis mémoire lecteur (.txt)
- [x] **Logique upsert** - Pas de doublons lors de l'import RFID

### 🔌 Lecteurs RFID
- [x] **Configuration lecteurs** avec serial, location, distance
- [x] **Calcul IP automatique** (192.168.10.1XX basé sur serial)
- [x] **Détection connexion temps réel** (is_online si date_test < 60s)
- [x] **Système de ping** (test connexion HTTP vers Raspberry)
- [x] **Endpoint réception RFID** (`POST /api/raspberry`)
- [x] **Anti-rebounce** (évite détections multiples)

### ⏱️ Chronométrage
- [x] **Interface fullscreen professionnelle** (dark theme)
- [x] **TOP DÉPART par course** avec modal
- [x] **Modification heure départ** (avec recalcul automatique tous résultats)
- [x] **Détection passages RFID automatique**
- [x] **Calcul temps automatique** (raw_time - start_time)
- [x] **Calcul vitesse** (si distance configurée)
- [x] **Support multi-tours** (lap_number)
- [x] **Auto-refresh résultats** (5 secondes)
- [x] **Filtre recherche** par dossard/nom (avec normalisation accents)
- [x] **Filtres catégorie et SAS**
- [x] **Colonnes Parcours** (affiche race.name)

### 🖊️ Chronométrage manuel
- [x] **Bouton stockage timestamps** (+ TEMPS MANUEL)
- [x] **Stockage illimité** (localStorage, pas de limite 100)
- [x] **Attribution CSV** (import masse dossards → timestamps)
- [x] **Bouton suppression rapide** (× sur badge) sans confirmation
- [x] **Import fichier RFID** avec sélection checkpoint

### 👤 Panel coureur détaillé
- [x] **Affichage infos coureur** (dossard, nom, catégorie, parcours)
- [x] **Timeline passages** avec checkpoints configurés
- [x] **Heure départ indépendante** du filtre course (bug fixé)
- [x] **Temps estimés** pour checkpoints non détectés
- [x] **Boutons édition** ±5s sur chaque passage
- [x] **Bouton suppression** passage
- [x] **Formulaire ajout temps intermédiaire**
- [x] **Logique upsert intelligente** (update si existe, create sinon)

### 🏆 Positions et classement
- [x] **Calcul positions automatique** (général + catégorie)
- [x] **Affichage colonnes Pos/Cat** dans chronométrage live
- [x] **Calcul dynamique frontend** lors du filtrage
- [x] **Recalcul backend automatique** après ajout/modif/suppression
- [x] **Bouton recalcul global** (toutes les courses)
- [x] **Page Résultats** avec affichage général et par catégorie

### 📤 Export et résultats
- [x] **Page Résultats** avec filtres événement/épreuve
- [x] **Affichage général** et par catégorie
- [x] **Statistiques** (participants, arrivés, temps moyen, vitesse)
- [x] **Export CSV** résultats
- [x] **Tri par position**

### 🔧 Technique
- [x] **Laravel 11** + Eloquent ORM
- [x] **Alpine.js 3.x** pour réactivité
- [x] **Axios** pour requêtes HTTP
- [x] **Bootstrap Icons** pour iconographie
- [x] **API REST complète** (40+ endpoints)
- [x] **Gestion timezone** (Europe/Paris, SQLite)
- [x] **Transactions DB** pour imports batch
- [x] **Validation requests** complète

---

## 🚧 FONCTIONNALITÉS EN COURS / À FAIRE (Phase 1)

### 🔍 Chronométrage - Filtres & Tri (Priorité 1)
- [ ] **Filtre par parcours** dans dropdown
- [ ] **Filtre par checkpoint** (DEPART, INTER1, INTER2, ARRIVEE)
- [ ] **Tri par position** (croissant/décroissant)
- [ ] **Tri par temps** (plus rapide en premier)
- [ ] Options de tri persistantes

### 🔄 Refresh fluide (Priorité 2 - CRITIQUE)
- [ ] **Diff intelligent** au lieu de full reload
- [ ] **Animation fade-in** pour nouvelles lignes
- [ ] **Refresh seulement si nouveautés**
- [ ] Éliminer le "saut" visuel actuel
- [ ] Badge "X nouvelles détections" temporaire

### ⚠️ Alertes et validations (Priorité 3)
- [ ] **Alerte doublons** (<10s même checkpoint)
- [ ] **Alerte temps négatif** (horloge décalée)
- [ ] **Alerte vitesse aberrante** (>40 km/h)
- [ ] **Panel Alertes dédié** dans interface
- [ ] **Liseré vert discret** autour horloge lors détection

### 📝 Gestion avancée (Priorité 4)
- [ ] **Status ABD (Abandon)** - Interface marquage
- [ ] **Notes sur coureur** (doute classement, erreur aiguillage)
- [ ] **Historique modifications** - Traçabilité (qui/quand/quoi)
- [ ] Modal édition rapide statuts

### 🚀 Performance (Priorité 5)
- [ ] **Pagination** si >200 résultats affichés
- [ ] **Lazy loading** au scroll
- [ ] Optimisation requêtes SQL (eager loading)

### 📊 Exports multiples (Priorité 6)
- [ ] **Export Excel** (.xlsx)
- [ ] **Export PDF** (classement imprimable)
- [ ] **Backup complet JSON** (état course complète)
- [ ] **Export .sql** pour restauration
- [ ] Système backup automatique horaire

### 📺 Affichage live public (Priorité 7)
- [ ] **Page live display** pour spectateurs/animateur
- [ ] **Iframe embarquable** pour sites web
- [ ] **Mode plein écran** pour écran géant
- [ ] Auto-scroll classement

---

## 🎨 AMÉLIORATION VISUELLE (Phase 1.5)

### Objectif
Trouver un thème Bootstrap/Tailwind gratuit pour professionnaliser l'interface tout en **conservant la structure et positionnement actuels** (qui sont excellents).

### Thèmes suggérés
- **Tabler** (tabler.io) - Dashboard pro, dark mode natif ⭐ RECOMMANDÉ
- **CoreUI** - Très pro, utilisé en production
- **Volt** (themesberg.com) - Modern, gratuit
- **AdminLTE** - Classique, très stable

### À conserver
- ✅ Structure layout actuelle (sidebar 70px + content)
- ✅ Interface chronométrage fullscreen
- ✅ Panel détail coureur à droite (400px)
- ✅ Dark theme timing interface
- ✅ Organisation des éléments

### À améliorer
- [ ] Design système cohérent (couleurs, espacements)
- [ ] Composants UI plus modernes (cards, badges, modals)
- [ ] Animations micro-interactions
- [ ] Typographie professionnelle
- [ ] Mode sombre optionnel pour autres pages

---

## 📈 MÉTRIQUES ACTUELLES

### Code
- **Lignes de code backend:** ~2500 lignes (controllers + models)
- **Vues Blade:** 11 fichiers
- **Routes API:** 60+ endpoints
- **Migrations:** 15 fichiers
- **JavaScript (Alpine.js):** ~1800 lignes (timing.blade.php)

### Base de données
- **Tables:** 8 principales
- **Relations:** 20+ définies
- **Catégories FFA:** 36 seedées
- **Support:** MySQL + SQLite

### Fonctionnalités
- **Pages web:** 9 interfaces
- **Endpoints API:** 60+
- **Modèles Eloquent:** 8
- **Contrôleurs:** 7

---

## 🏗️ ARCHITECTURE TECHNIQUE

### Backend (Laravel 11)
```
app/
├── Http/Controllers/Api/
│   ├── EventController.php
│   ├── RaceController.php (+ updateStartTime, recalcul auto)
│   ├── WaveController.php
│   ├── CategoryController.php
│   ├── EntrantController.php (+ import CSV)
│   ├── ResultController.php (+ positions, manual batch, RFID batch)
│   ├── ReaderController.php (+ ping system)
│   └── RaspberryController.php (réception RFID)
└── Models/
    ├── Event.php
    ├── Race.php
    ├── Wave.php
    ├── Category.php
    ├── Entrant.php (+ assignCategory)
    ├── Reader.php (+ markAsTested, isOnline)
    └── Result.php (+ calculateTime, calculateSpeed, formatted_time)
```

### Frontend (Alpine.js)
```
resources/views/chronofront/
├── layout.blade.php (sidebar + nav)
├── timing-layout.blade.php (fullscreen pour chrono)
├── dashboard.blade.php
├── events.blade.php
├── races.blade.php
├── waves.blade.php
├── categories.blade.php
├── entrants.blade.php
├── entrants-import.blade.php
├── timing.blade.php ⭐ (2000+ lignes, interface principale)
└── results.blade.php (classements + export)
```

### Routes principales
```
Web:
GET /               → Dashboard
GET /timing         → Interface chronométrage ⭐
GET /results        → Résultats et classements
GET /events         → Gestion événements
GET /races          → Gestion épreuves
GET /entrants       → Gestion participants
GET /entrants/import → Import CSV

API:
POST /api/raspberry              → Réception RFID Raspberry
POST /api/races/{id}/start       → TOP DÉPART
PUT /api/races/{id}/start        → Modifier heure départ
POST /api/results/time           → Ajout temps manuel
POST /api/results/manual-batch   → Import CSV timestamps
POST /api/results/rfid-batch     → Import fichier RFID (upsert)
POST /api/results/recalculate-all → Recalcul toutes positions
GET /api/readers/event/{id}      → Lecteurs + statut online
POST /api/readers/{id}/ping      → Test connexion lecteur
```

---

## 🔄 WORKFLOW UTILISATEUR ACTUEL

### 1. Préparation course
1. Créer événement via `/events`
2. Importer participants CSV via `/entrants/import`
   - Auto-création races et vagues
   - Auto-génération RFID "2000" + dossard
   - Auto-attribution catégories FFA
3. Configurer lecteurs (serial, location, distance)

### 2. Jour de course
1. Ouvrir `/timing` (interface chronométrage)
2. Allumer Raspberry Pi (détection connexion auto)
3. Vérifier lecteurs "OK" (vert)
4. Cliquer "TOP DÉPART" pour lancer course
5. Chronométrage automatique via RFID

### 3. Gestion temps réel
- Auto-refresh résultats toutes les 5s
- Filtrage par catégorie/SAS/recherche
- Click coureur → Panel détail avec timeline
- Édition ±5s ou suppression passage si besoin
- Ajout temps intermédiaire si non détecté
- Stockage timestamps manuel si nécessaire

### 4. Import manuel
- Button "+ TEMPS MANUEL" pour stocker timestamps
- Import CSV ou fichier RFID pour attribution masse
- Pas de doublons (logique upsert)

### 5. Résultats
1. Aller sur `/results`
2. Cliquer "Recalculer TOUTES les positions"
3. Sélectionner épreuve
4. Voir classement général ou par catégorie
5. Exporter CSV

---

## 🐛 BUGS CONNUS CORRIGÉS

- ✅ ~~Double `/api/api/` dans URLs~~ (fixé 2894e68)
- ✅ ~~Timezone offset 1h (UTC vs local)~~ (fixé 7cd4871)
- ✅ ~~Timestamps perdus changement onglet~~ (fixé 0517ea9)
- ✅ ~~Filtres catégorie/SAS non fonctionnels~~ (fixé ffac7bb)
- ✅ ~~Import RFID crée doublons~~ (fixé ffff96a)
- ✅ ~~Heure départ change avec filtre course~~ (fixé 4401dd7)
- ✅ ~~Positions non calculées~~ (fixé 34450b0)
- ✅ ~~Recalcul positions seulement course sélectionnée~~ (fixé 2b00e43)

---

## 📅 COMMITS RÉCENTS (Session actuelle)

```
2b00e43 - feat: Recalcul global de toutes les positions (toutes courses)
fec981d - feat: Bouton recalcul positions dans onglet Résultats
34450b0 - feat: Calcul et affichage des positions (général et catégorie)
4401dd7 - fix: Affichage heure départ coureur indépendant du filtre course
d6ae17b - refactor: Renommage bouton import RFID en IMPORTER HEURES
ffff96a - fix: Import RFID évite les doublons avec logique upsert
00fba22 - feat: Outil import fichier détections RFID
13189c9 - fix: Recalcul automatique temps/vitesse lors modification départ
cc1bced - feat: Modification temps de départ dans modal TOP DÉPART
32fe74b - feat: Bouton suppression rapide des temps manuels stockés
f66faff - fix: Modification au lieu de création lors ajout temps existant
c8bcbc5 - fix: Quatre corrections panel édition coureur
cf9db25 - feat: Transformation outil ajout temps en édition passages
```

---

## 🎯 PRIORITÉS IMMÉDIATES

### Cette session
1. ✅ **Documentation Phase 1** (ce fichier)
2. ⏳ **Filtres parcours + checkpoint** (chrono live)
3. ⏳ **Tri par position/temps** (chrono live)
4. ⏳ **Refresh fluide sans saut** (CRITIQUE UX)
5. ⏳ **Alertes doublons + temps aberrant**
6. ⏳ **Liseré vert lors détection**

### Prochaine session
7. Historique modifications + statuts ABD
8. Notes sur coureur
9. Pagination performance
10. Choix et intégration thème

---

## 💬 NOTES DÉVELOPPEMENT

### Points forts actuels
- ✅ Architecture solide et extensible
- ✅ Séparation claire frontend/backend
- ✅ Code bien organisé et commenté
- ✅ Logique métier robuste (upsert, recalcul auto)
- ✅ Interface utilisateur intuitive
- ✅ Performance correcte (<500 résultats)

### Points d'amélioration identifiés
- ⚠️ Refresh brutal (saut visuel) → À refaire en priorité
- ⚠️ Pas de pagination → Problème si >500 résultats
- ⚠️ Pas d'alertes automatiques → Manque sécurité
- ⚠️ Design "fait par IA" → Besoin polish professionnel
- ⚠️ Pas d'historique modifs → Manque traçabilité

### Décisions techniques
- ✅ SQLite choisi pour portabilité (vs MySQL corruption)
- ✅ Alpine.js pour réactivité légère (vs Vue/React overhead)
- ✅ Gestion timezone manuelle (SQLite limitations)
- ✅ localStorage pour timestamps manuels (persistance)
- ✅ Auto-refresh 5s (compromis réactivité/charge)

---

## 📊 ESTIMATION COMPLÉTUDE

### Phase 1 globale: **~75% complété**

#### Par domaine:
- **Base de données:** 100% ✅
- **Backend API:** 95% ✅
- **Import données:** 100% ✅
- **Chronométrage base:** 90% ✅
- **Positions/Classement:** 100% ✅
- **Export résultats:** 70% (CSV OK, manque Excel/PDF)
- **Interface UX:** 65% (fonctionnel mais à améliorer)
- **Alertes/Validations:** 0% ❌
- **Historique/Traçabilité:** 0% ❌
- **Design professionnel:** 40% (structure OK, visuel à refaire)

---

## 🚀 PROCHAINES PHASES

### Phase 1.5 - Polish & Finition
- Filtres avancés chronométrage
- Refresh fluide
- Alertes intelligentes
- Design professionnel
- Export multiples formats

### Phase 2 - Avancé (Futur)
- WebSocket temps réel (push au lieu de poll)
- Multi-événements simultanés
- Interface mobile responsive
- Écrans affichage public
- Photos participants
- Statistiques avancées

---

**🎯 Objectif Phase 1:** Interface chronométrage complète, robuste et professionnelle
**📅 Cible:** Fin décembre 2025
**👨‍💻 Développement:** Claude + Utilisateur en pair programming

---

*Document généré automatiquement - À mettre à jour après chaque session majeure*
