# Session Recovery - 2025-11-28

## 🎯 Contexte de la session

Cette session est une continuation d'une session précédente où ChronoFront V2.0 a été implémenté avec migration de MySQL vers SQLite.

**Problème initial** : L'utilisateur ne pouvait pas ajouter de lecteurs RFID à son événement. Message d'erreur "Erreur lors du chargement des lecteurs".

---

## ✅ Problèmes résolus dans cette session

### 1. **Erreur /api/api/ en double** (RÉSOLU ✅)
**Symptôme** : Requêtes HTTP échouaient avec URL `/api/api/readers/event/2` au lieu de `/api/readers/event/2`

**Cause** : Dans `layout.blade.php` ligne 233, axios a `baseURL = '/api'`, mais dans `readers.blade.php` les URLs contenaient déjà `/api/`

**Solution** :
- Modifié `resources/views/chronofront/readers.blade.php`
- Retiré le préfixe `/api/` de toutes les requêtes axios :
  - `/api/readers/event/${eventId}` → `/readers/event/${eventId}`
  - `/api/races/event/${eventId}` → `/races/event/${eventId}`
  - `/api/readers` → `/readers`
  - `/api/readers/${id}` → `/readers/${id}`

**Commit** : `84fa27a` - "fix: Retrait du préfixe /api en double dans readers.blade.php"

---

### 2. **Colonnes date_min/date_max NOT NULL mais nullable** (RÉSOLU ✅)
**Symptôme** : Impossible de créer un lecteur sans remplir date_min et date_max

**Cause** : Migration `2025_11_28_122337_make_readers_date_columns_nullable.php` créée dans session précédente, mais le modèle Reader avait des méthodes qui ne géraient pas les valeurs NULL

**Solution** :
- Modifié `app/Models/Reader.php` :
  - Méthode `getActiveConfig()` : Accepte NULL pour date_min/date_max (= toujours actif)
  - Méthode `isCurrentlyActive()` : Même logique

**Code ajouté** :
```php
->where(function($q) use ($now) {
    // If date_min is NULL, no start restriction
    $q->whereNull('date_min')
      ->orWhere('date_min', '<=', $now);
})
->where(function($q) use ($now) {
    // If date_max is NULL, no end restriction
    $q->whereNull('date_max')
      ->orWhere('date_max', '>=', $now);
})
```

**Commit** : `ebe6832` - "fix: Gestion date_min/date_max nullable dans Reader"

---

### 3. **Système de ping des lecteurs RFID** (NOUVEAU ✅)
**Besoin** : L'application doit pouvoir vérifier si les Raspberry Pi sont en ligne en les "pingant"

**Solution complète** :

#### A. Nouvelle route API
- `routes/api.php` ligne 60 : `Route::post('readers/{reader}/ping', [ReaderController::class, 'ping']);`

#### B. Méthode ReaderController::ping()
- Calcule l'IP du lecteur : `192.168.10.1{50+XX}` où XX = 2 derniers chiffres du serial
  - Exemple : Serial 107 → IP 192.168.10.157
- Fait une requête HTTP vers `http://{readerIp}` avec timeout de 2 secondes
- Si réponse reçue :
  - Met à jour `date_test` avec `now()`
  - Met `test_terrain = true`
  - Retourne `success: true`
- Si pas de réponse : Retourne `success: false` avec HTTP 503

#### C. Interface readers.blade.php
- Bouton 🔊 (broadcast) à côté de chaque lecteur
- Fonction `pingReader()` modifiée pour appeler `/api/readers/{id}/ping`
- Affiche une alerte avec le résultat :
  - ✓ "Lecteur {location} ({ip}) est EN LIGNE !"
  - ✗ "Lecteur {location} ({ip}) est HORS LIGNE"
- Recharge la liste des lecteurs après le test

**Commit** : `31f19e8` - "feat: Ajout fonctionnalité ping des lecteurs RFID"

---

## 🔧 Fichiers modifiés

| Fichier | Lignes | Modifications |
|---------|--------|---------------|
| `resources/views/chronofront/readers.blade.php` | 231-248, 306-355 | Fix URLs /api/, ajout ping fonctionnel |
| `app/Models/Reader.php` | 61-99 | Gestion date_min/max nullable |
| `app/Http/Controllers/Api/ReaderController.php` | 154-202 | Méthode ping() |
| `routes/api.php` | 60 | Route POST readers/{reader}/ping |

---

## 📊 Statut actuel de l'application

### ✅ Ce qui fonctionne
- Interface configuration lecteurs (`/events/{id}/readers`)
- Ajout/Édition/Suppression de lecteurs RFID
- Calcul automatique de l'IP (192.168.10.1XX)
- Calcul automatique de checkpoint_order basé sur distance
- Bouton ping pour tester la connexion aux Raspberry Pi
- Détection "en ligne/hors ligne" basée sur date_test (< 60 secondes)

### ⚠️ Problème en cours (NON RÉSOLU)
**Interface chronométrage affiche "Aucun lecteur configuré"**

**Diagnostic partiel** :
- Fichier : `resources/views/chronofront/timing.blade.php`
- Message ligne 701 : `x-show="readers.length === 0"`
- Fonction `loadReaders()` ligne 1003-1023 :
  ```javascript
  const response = await axios.get(`/readers/event/${this.currentEventId}`);
  this.readers = response.data;
  ```
- `currentEventId` est initialisé à partir de l'événement **actif** (ligne 977)

**Hypothèse** : L'événement n'est pas marqué comme actif (`is_active = 0`)

**Solution à tester** :
```sql
UPDATE events SET is_active = 1 WHERE id = 2;
```

---

## 🔍 Architecture système - Lecteurs RFID

### Flux de détection RFID (Raspberry Pi → Laravel)

```
1. Raspberry Pi détecte un tag RFID
2. Raspberry envoie POST/PUT à /api/raspberry
   - Header: Serial: 107
   - Body: [{"serial": "20000042", "timestamp": 743084027.091}]
3. RaspberryController::store()
   - Trouve le lecteur via Reader::getActiveConfig(serial)
   - Appelle $reader->markAsTested() → met à jour date_test
   - Convertit serial → bib_number (retire préfixe "2000")
   - Trouve l'Entrant via bib_number
   - Crée un Result avec raw_time
4. Interface chronométrage se rafraîchit toutes les 10s (timing.blade.php ligne 1027)
```

### Flux de ping (Laravel → Raspberry Pi)

```
1. Utilisateur clique sur bouton 🔊 dans /events/{id}/readers
2. Frontend appelle POST /api/readers/{id}/ping
3. ReaderController::ping()
   - Calcule IP depuis serial
   - Tente connexion HTTP vers http://{ip}
   - Si succès : met à jour date_test
4. Retourne résultat au frontend
5. Frontend recharge la liste des lecteurs
```

### Calcul de l'IP lecteur

**Formule** : `192.168.10.{150 + last2digits(serial)}`

| Serial | Last 2 digits | Calcul | IP finale |
|--------|---------------|--------|-----------|
| 107 | 07 | 150 + 7 = 157 | 192.168.10.157 |
| 112 | 12 | 150 + 12 = 162 | 192.168.10.162 |
| 7 | 07 | 150 + 7 = 157 | 192.168.10.157 |

**Code** :
```php
$lastTwoDigits = substr((string)$reader->serial, -2);
$ipSuffix = 150 + (int)$lastTwoDigits;
$readerIp = "192.168.10.{$ipSuffix}";
```

### Statut "En ligne / Hors ligne"

Un lecteur est considéré **EN LIGNE** si :
- `date_test` existe (not NULL)
- ET `now() - date_test < 60 secondes`

Logique dans `ReaderController::byEvent()` lignes 131-144 :
```php
if (!$reader->date_test) {
    $reader->is_online = false;
    $reader->connection_status = 'never_connected';
} elseif (now()->diffInSeconds($reader->date_test) < 60) {
    $reader->is_online = true;
    $reader->connection_status = 'online';
} else {
    $reader->is_online = false;
    $reader->connection_status = 'offline';
    $reader->last_seen = $reader->date_test->diffForHumans();
}
```

---

## 🚀 Pour continuer dans une nouvelle session

### 1. Récupérer le contexte
- Lire ce fichier `SESSION_RECOVERY_2025-11-28.md`
- Vérifier les commits récents : `git log --oneline -10`

### 2. Résoudre le problème "Aucun lecteur configuré" dans chronométrage

**Étapes de diagnostic** :
```sql
-- Dans DB Browser, vérifier :
SELECT id, name, is_active FROM events WHERE id = 2;
-- Si is_active = 0, changer en 1

SELECT * FROM readers WHERE event_id = 2;
-- Vérifier qu'il y a bien des lecteurs
```

**Si is_active = 0** :
```sql
UPDATE events SET is_active = 1 WHERE id = 2;
```

**Vérifier dans le navigateur** :
- F12 > Console
- Chercher les erreurs lors du chargement de `/chronometrage`
- Vérifier Network > XHR pour voir si `/api/readers/event/2` est appelé

### 3. Configuration Raspberry Pi

Pour que le ping fonctionne, le Raspberry Pi doit :
- Avoir un serveur web actif (Apache, nginx, Python SimpleHTTPServer)
- Répondre sur le port 80
- Être accessible depuis le PC qui lance Laravel

**Test rapide** :
```bash
# Sur le PC Windows
curl http://192.168.10.157
# Si réponse = Raspberry OK
```

### 4. Tests complets

1. Ajouter un lecteur avec serial 107, location "DEPART", distance 0
2. Cliquer sur le bouton 🔊 → doit afficher "EN LIGNE" si Raspberry répond
3. Aller dans `/chronometrage` → doit afficher le lecteur
4. Configurer le Raspberry Pi pour envoyer des détections RFID à `/api/raspberry`

---

## 📝 Commits de cette session

```
31f19e8 - feat: Ajout fonctionnalité ping des lecteurs RFID
ebe6832 - fix: Gestion date_min/date_max nullable dans Reader
84fa27a - fix: Retrait du préfixe /api en double dans readers.blade.php
```

---

## 💡 Notes importantes

- **Base de données** : SQLite (fichier `database/database.sqlite`)
- **Environnement utilisateur** : Windows PC avec XAMPP/Laravel local
- **Environnement distant** : Conteneur Linux (ce qui a causé la confusion lors du "moment de panique")
- **Ne jamais faire** : `php artisan migrate:fresh` sur l'environnement de l'utilisateur sans backup !
- **Les données utilisateur sont sur son PC**, pas dans l'environnement distant

---

## 🔗 Fichiers de référence

- **Documentation complète** : `DOCUMENTATION_COMPLETE.md`
- **Routes API** : `routes/api.php`
- **Contrôleur lecteurs** : `app/Http/Controllers/Api/ReaderController.php`
- **Contrôleur Raspberry** : `app/Http/Controllers/Api/RaspberryController.php`
- **Modèle Reader** : `app/Models/Reader.php`
- **Interface lecteurs** : `resources/views/chronofront/readers.blade.php`
- **Interface chronométrage** : `resources/views/chronofront/timing.blade.php`
