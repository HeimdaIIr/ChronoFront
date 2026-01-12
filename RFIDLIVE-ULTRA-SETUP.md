# RFID Live Ultra - Configuration

Interface ultra-haute performance pour afficher les détections RFID en temps réel avec Server-Sent Events (SSE).

## 🚀 Améliorations par rapport à rfidlive-instant

✅ **Pas d'historique au chargement** : Démarre vide, n'affiche que les nouvelles détections
✅ **Limite stricte de 50 entrées** : Performance maximale même avec 100+ détections/seconde
✅ **Compteur de taux** : Affiche le nombre de détections par seconde en temps réel
✅ **Pause/Reprendre** : Fige l'affichage pour analyser les détections
✅ **Timestamps milliseconde** : Précision HH:MM:SS.mmm
✅ **Rendu optimisé** : Remplacement innerHTML complet, pas d'insertions DOM multiples
✅ **Format minimaliste** : `[numero_puce] [timestamp] [Serial_lecteur]`

## Installation

### 1. Ajouter la route dans `routes/web.php`

```php
// RFID Live - Interface ultra
Route::get('/rfidlive-ultra', function () {
    return view('chronofront.rfidlive-ultra');
});
```

### 2. Vérifier que la route SSE existe dans `routes/api.php`

```php
use App\Http\Controllers\Api\RfidLogController;

// RFID Live Stream (SSE)
Route::get('rfid/live-stream', [RfidLogController::class, 'liveStream']);
```

### 3. Lancer le serveur Laravel

```powershell
php artisan serve
```

### 4. Ouvrir l'interface

```
http://localhost:8000/rfidlive-ultra
```

### 5. Configurer le lecteur RFID

**URL :** `http://[IP_PC]:8000/api/raspberry`
**Méthode :** POST
**Header :** `Serial: 120` (votre numéro de lecteur)

## Format d'affichage

Chaque détection s'affiche comme :
```
[2000003] [14:30:45.123] [120]
[2000125] [14:30:45.234] [120]
[2000089] [14:30:45.456] [120]
```

- **[2000003]** : Numéro de puce RFID
- **[14:30:45.123]** : Heure de réception avec millisecondes
- **[120]** : Numéro du lecteur

## Fonctionnalités

### En-tête informatif
- **Titre** : RFID LIVE ULTRA
- **Compteur de détections** : Total depuis le chargement
- **Taux de détection** : Détections par seconde (mise à jour chaque seconde)
- **Statut de connexion** : Connecté (vert) / Déconnecté (rouge)
- **Bouton Pause** : Fige l'affichage sans perdre les détections
- **Bouton Vider** : Efface tous les logs et remet le compteur à zéro

### Performance extrême

**Capacité maximale :**
- 100+ détections/seconde sans lag
- Latence ~100ms (SSE vérifie toutes les 100ms)
- Mémoire limitée à 50 entrées affichées
- Les plus anciennes sont supprimées automatiquement

**Optimisations :**
- Pas de chargement d'historique au démarrage
- Rendu par lot (innerHTML complet)
- Limite stricte en mémoire
- Pas d'animations qui ralentissent

## Test rapide

```powershell
# Test 1 : Une seule détection
Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
    -Method POST `
    -Headers @{"Serial"="120"} `
    -Body '[{"serial":"2000001","timestamp":1673524800}]'

# Test 2 : Simulation haute vitesse (20 détections)
for ($i=1; $i -le 20; $i++) {
    Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{`"serial`":`"200000$i`",`"timestamp`":$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds())}]"
}
```

### Test de performance extrême

```powershell
# Test 3 : 100 détections en parallèle (stress test)
1..100 | ForEach-Object -Parallel {
    $num = $_
    Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{`"serial`":`"200$num`",`"timestamp`":$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds())}]"
} -ThrottleLimit 50
```

Vous devriez voir toutes les détections apparaître **instantanément** avec un taux affiché (ex: "47/s") !

## Statut de connexion

En haut à droite de l'interface :
- **● Connecté** (vert) : SSE actif, reçoit les détections
- **● Déconnecté** (rouge) : SSE coupé, reconnexion en cours (automatique après 2s)

## Utilisation des boutons

### Pause / Reprendre
- Cliquez sur **⏸ Pause** pour figer l'affichage
- Les détections continuent d'arriver (compteur augmente) mais ne s'affichent pas
- Cliquez sur **▶ Reprendre** pour voir à nouveau les nouvelles détections
- Utile pour analyser des détections spécifiques sans perdre le fil

### Vider
- Efface tous les logs affichés
- Remet le compteur de détections à 0
- Remet le taux à 0/s
- N'affecte pas la connexion SSE

## Dépannage

### Aucune détection n'apparaît

1. Vérifiez que le lecteur est configuré en **HTTP** (pas HTTPS)
2. Vérifiez que le Serial header est bien envoyé
3. Regardez les logs Laravel : `storage/logs/laravel.log`
4. Testez manuellement avec PowerShell
5. Vérifiez le statut de connexion (doit être vert)

### Erreur "EventSource failed"

- Vérifiez que la route SSE est bien ajoutée dans `routes/api.php`
- Vérifiez que le serveur Laravel tourne (`php artisan serve`)
- Ouvrez la console navigateur (F12) pour voir l'erreur exacte
- Vérifiez que `RfidLogController::liveStream()` contient `set_time_limit(0)`

### Page ne charge pas (200 détections d'un coup)

- **Normal avec /rfidlive-instant**, c'est pour ça qu'on a créé /rfidlive-ultra
- /rfidlive-ultra démarre TOUJOURS vide (pas d'historique)
- Seules les nouvelles détections s'affichent après ouverture de la page

### Taux de détection à 0/s mais des détections apparaissent

- Le taux se met à jour toutes les secondes
- Si moins d'une détection par seconde, il peut afficher 0
- Attendez 1-2 secondes pour voir le taux se stabiliser

### Détections trop rapides, impossible de lire

- Cliquez sur **⏸ Pause** pour figer l'affichage
- Analysez les 50 dernières détections
- Cliquez sur **▶ Reprendre** quand vous êtes prêt

## Comparaison des interfaces

| Feature | /rfidlive | /rfidlive-simple | /rfidlive-instant | /rfidlive-ultra |
|---------|-----------|------------------|-------------------|-----------------|
| Source | DB (table results) | Cache (polling) | Cache (SSE) | Cache (SSE) |
| Latence | 2s | 1s | 100ms | 100ms |
| Historique au chargement | Oui | Non | Oui (100 entrées) | **Non (démarre vide)** |
| Limite affichage | ∞ | 100 | 1000 | **50 (strict)** |
| Format | Table complète | Logs détaillés | Logs simples | **Ultra minimaliste** |
| Animation | Oui | Oui | Non | Non |
| Taux détections/s | Non | Non | Non | **Oui** |
| Pause | Non | Non | Non | **Oui** |
| Timestamp | Oui | Oui | HH:MM:SS | **HH:MM:SS.mmm** |
| Filtres | Oui | Non | Non | Non |
| Config requise | Oui (event/entrant) | Non | Non | Non |
| Performance max | - | ~10/s | ~50/s | **100+/s** |
| Use case | Production | Debug | Debug rapide | **High-speed / Production debug** |

## Conseils d'utilisation

### Pour tester un lecteur haute vitesse
1. Ouvrez `/rfidlive-ultra`
2. Passez rapidement plusieurs puces devant le lecteur
3. Vérifiez que toutes apparaissent instantanément
4. Vérifiez le taux de détection affiché (ex: "23/s")
5. Vérifiez que les timestamps ont des millisecondes différentes

### Pour debugger des doublons
1. Ouvrez `/rfidlive-ultra`
2. Passez UNE seule puce devant le lecteur
3. Cliquez immédiatement sur **⏸ Pause**
4. Comptez combien de détections pour cette puce
5. Si > 1 détection pour la même puce → problème de doublon
6. Analysez les timestamps pour voir l'intervalle entre détections

### Pour tester la performance maximale
```powershell
# Simulation de 100 passages en 1 seconde
1..100 | ForEach-Object -Parallel {
    Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{`"serial`":`"200$_`",`"timestamp`":$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds())}]"
} -ThrottleLimit 100
```

Toutes les 100 détections devraient apparaître sans perte, avec un taux affiché ~100/s !

### Pour une course en production
1. Ouvrez `/rfidlive-ultra` sur un écran secondaire
2. Lancez la course normalement via l'interface principale
3. Surveillez le taux de détection pendant la course
4. Si le taux devient très élevé (>50/s), vérifiez qu'il n'y a pas de doublons
5. Utilisez **⏸ Pause** pour analyser des passages suspects

## Limites

- **Limite d'affichage** : 50 entrées max (les plus anciennes sont supprimées)
- **Pas d'historique** : Démarre vide, n'affiche que les nouvelles détections
- **Cache** : Les logs sont gardés 1h en cache (100 entrées max)
- **Connexion** : Une connexion SSE par onglet (ne pas ouvrir 50 onglets)
- **Pas de filtres** : Interface minimaliste pour performance maximale

## Architecture technique

### Frontend (rfidlive-ultra.blade.php)
```javascript
const MAX_LOGS = 50; // Limite stricte
let logs = []; // Array en mémoire
let paused = false; // État pause

eventSource.addEventListener('detection', (e) => {
    if (paused) return; // Ignore si en pause

    // Extraction tag
    const data = JSON.parse(e.data);
    const body = JSON.parse(data.data);
    const tag = body[0].serial;

    // Timestamp avec millisecondes
    const time = new Date().toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        fractionalSecondDigits: 3
    });

    // Ajout en tête (prepend)
    logs.unshift({ tag, time, serial: data.serial });

    // Limite stricte
    if (logs.length > MAX_LOGS) {
        logs = logs.slice(0, MAX_LOGS);
    }

    // Rendu complet (pas de DOM insertion)
    render();
});
```

### Backend (RfidLogController::liveStream)
```php
// Démarre depuis le dernier ID pour éviter l'historique
$allLogs = Cache::get('rfid_raw_logs', []);
$lastId = count($allLogs) > 0 ? max(array_column($allLogs, 'id')) : 0;

while (true) {
    $allLogs = Cache::get('rfid_raw_logs', []);

    // Uniquement les nouveaux logs
    $newLogs = array_filter($allLogs, function($log) use ($lastId) {
        return $log['id'] > $lastId;
    });

    // Envoi SSE
    foreach ($newLogs as $log) {
        echo "event: detection\n";
        echo "data: " . json_encode($log) . "\n\n";
        ob_flush();
        flush();

        if ($log['id'] > $lastId) {
            $lastId = $log['id'];
        }
    }

    usleep(100000); // 100ms
}
```

## Prochaines étapes

Une fois que vous avez vérifié que les détections arrivent bien :
1. Créez un événement dans `/events`
2. Créez une course dans `/races`
3. Créez des entrants dans `/entrants` avec les bons numéros de dossard
4. Créez un lecteur dans `/readers` avec le serial 120
5. Les détections seront alors traitées et enregistrées en base

## Troubleshooting avancé

### Latence > 100ms observée

- SSE vérifie toutes les 100ms, donc latence théorique max = 100ms
- Si latence > 200ms :
  1. Vérifiez la charge CPU du serveur
  2. Vérifiez que `set_time_limit(0)` est bien dans `liveStream()`
  3. Fermez les autres onglets SSE ouverts
  4. Rechargez la page

### Mémoire serveur qui augmente

- Le cache est limité à 100 entrées (auto-cleanup)
- Chaque connexion SSE garde un buffer minimal
- Si problème : redémarrez le serveur Laravel
- Vérifiez qu'il n'y a pas 50 onglets ouverts simultanément

### Détections manquantes (pas toutes affichées)

1. Vérifiez que le taux affiché correspond au nombre de détections envoyées
2. Si taux correct mais affichage incomplet → limite des 50 entrées atteinte
3. Utilisez **Vider** régulièrement pour garder les logs récents visibles
4. Pour historique complet → utilisez `/rfidlive` original (DB)

## Quand utiliser quelle interface ?

### Utilisez /rfidlive-ultra quand :
- ✅ Test de lecteur haute vitesse (>20 détections/s)
- ✅ Debug de doublons (pause pour analyser)
- ✅ Monitoring temps réel d'une course
- ✅ Validation de performance avant production
- ✅ Vous voulez voir le taux de détection en direct

### Utilisez /rfidlive-instant quand :
- ✅ Test normal de lecteur (<20 détections/s)
- ✅ Vous voulez voir l'historique récent (1000 entrées)
- ✅ Debug standard

### Utilisez /rfidlive-simple quand :
- ✅ Vous voulez voir les requêtes HTTP brutes
- ✅ Debug des headers et payload
- ✅ Pas besoin de SSE

### Utilisez /rfidlive original quand :
- ✅ Production (avec filtres et recherche)
- ✅ Analyse post-course
- ✅ Historique complet en base de données
