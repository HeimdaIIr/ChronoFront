# RFID Live Instant - Configuration

Interface ultra-rapide pour afficher les détections RFID en temps réel avec Server-Sent Events (SSE).

## Améliorations par rapport à rfidlive-simple

✅ **Vraiment instantané** : SSE au lieu de polling (100ms au lieu de 1s)
✅ **Pas d'animation** : Affichage direct sans effet visuel
✅ **Format simplifié** : `[numero_puce] [timestamp] [Serial_lecteur]`
✅ **Performance** : Supporte des dizaines de détections par seconde
✅ **Reconnexion auto** : Se reconnecte automatiquement si déconnecté

## Installation

### 1. Ajouter la route dans `routes/web.php`

```php
// RFID Live - Interface instantanée
Route::get('/rfidlive-instant', function () {
    return view('chronofront.rfidlive-instant');
});
```

### 2. Ajouter la route SSE dans `routes/api.php`

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
http://localhost:8000/rfidlive-instant
```

### 5. Configurer le lecteur RFID

**URL :** `http://[IP_PC]:8000/api/raspberry`
**Méthode :** POST
**Header :** `Serial: 120` (votre numéro de lecteur)

## Format d'affichage

Chaque détection s'affiche comme :
```
[2000003] [14:30:45] [Serial: 120]
[2000125] [14:30:45] [Serial: 120]
[2000089] [14:30:46] [Serial: 120]
```

- **[2000003]** : Numéro de puce RFID
- **[14:30:45]** : Heure de réception
- **[Serial: 120]** : Numéro du lecteur

## Fonctionnement technique

### Server-Sent Events (SSE)
- Connexion HTTP persistante entre le navigateur et le serveur
- Le serveur pousse les données dès qu'elles arrivent
- Vérifie les nouvelles détections toutes les **100ms** (10 fois par seconde)
- Reconnexion automatique en cas de déconnexion

### Performance
- **Latence** : ~100ms (10x plus rapide que le polling à 1s)
- **Capacité** : Dizaines de détections par seconde sans problème
- **Mémoire** : Limite de 1000 entrées affichées

## Test rapide

```powershell
# Test 1 : Une seule détection
Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
    -Method POST `
    -Headers @{"Serial"="120"} `
    -Body '[{"serial":"2000001","timestamp":1673524800}]'

# Test 2 : Simulation de détections multiples rapides
for ($i=1; $i -le 20; $i++) {
    Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{\"serial\":\"200000$i\",\"timestamp\":$(Get-Date -UFormat %s)}]"
}
```

Vous devriez voir toutes les détections apparaître **instantanément** sans délai visible !

## Statut de connexion

En haut à droite de l'interface :
- **● Connecté** (vert) : SSE actif, reçoit les détections
- **● Déconnecté** (rouge) : SSE coupé, reconnexion en cours

## Dépannage

### Aucune détection n'apparaît

1. Vérifiez que le lecteur est configuré en **HTTP** (pas HTTPS)
2. Vérifiez que le Serial header est bien envoyé
3. Regardez les logs Laravel : `storage/logs/laravel.log`
4. Testez manuellement avec PowerShell

### Erreur "EventSource failed"

- Vérifiez que la route SSE est bien ajoutée dans `routes/api.php`
- Vérifiez que le serveur Laravel tourne
- Ouvrez la console navigateur (F12) pour voir l'erreur exacte

### Détections en retard

- SSE vérifie toutes les 100ms, donc latence max = 100ms
- Si vous voyez un retard > 100ms, rechargez la page
- Vérifiez que votre PC n'est pas surchargé

## Comparaison des interfaces

| Feature | /rfidlive | /rfidlive-simple | /rfidlive-instant |
|---------|-----------|------------------|-------------------|
| Source | DB (table results) | Cache (polling) | Cache (SSE) |
| Latence | 2s | 1s | 100ms |
| Format | Table complète | Logs détaillés | Minimaliste |
| Animation | Oui | Oui | Non |
| Filtres | Oui | Non | Non |
| Config requise | Oui (event/entrant) | Non | Non |
| Use case | Production | Debug | Debug rapide |

## Conseils d'utilisation

### Pour tester un lecteur
1. Ouvrez `/rfidlive-instant`
2. Passez des puces devant le lecteur
3. Vérifiez que toutes les puces apparaissent instantanément
4. Vérifiez le numéro de serial affiché

### Pour debugger des problèmes
1. Si des puces ne passent pas : vérifiez qu'elles apparaissent ici
2. Si elles apparaissent ici mais pas dans l'app : problème de traitement
3. Si elles n'apparaissent pas : problème réseau/lecteur

### Pour tester la performance
```powershell
# Envoyer 100 détections rapidement
for ($i=1; $i -le 100; $i++) {
    Start-Job -ScriptBlock {
        param($num)
        Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
            -Method POST `
            -Headers @{"Serial"="120"} `
            -Body "[{\"serial\":\"200$num\",\"timestamp\":$(Get-Date -UFormat %s)}]"
    } -ArgumentList $i
}
```

Toutes les 100 détections devraient apparaître sans perte !

## Limites

- **Limite d'affichage** : 1000 entrées max (les plus anciennes sont supprimées)
- **Cache** : Les logs sont gardés 1h en cache
- **Connexion** : Une connexion SSE par onglet (ne pas ouvrir 50 onglets)

## Prochaines étapes

Une fois que vous avez vérifié que les détections arrivent bien :
1. Créez un événement dans `/events`
2. Créez une course dans `/races`
3. Créez des entrants dans `/entrants` avec les bons numéros de dossard
4. Créez un lecteur dans `/readers` avec le serial 120
5. Les détections seront alors traitées et enregistrées en base
