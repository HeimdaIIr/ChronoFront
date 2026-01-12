# Configuration RFID Live Simple

Interface simplifiée pour afficher les requêtes HTTP RFID brutes en temps réel.

## Fichiers créés

1. **resources/views/chronofront/rfidlive-simple.blade.php** - Interface simplifiée
2. **app/Http/Controllers/Api/RfidLogController.php** - Controller pour gérer les logs
3. **app/Http/Controllers/Api/RaspberryController2.php** - Modifié pour logger les requêtes

## Installation

### 1. Ajouter les routes dans `routes/web.php`

```php
// RFID Live - Interface simplifiée
Route::get('/rfidlive-simple', function () {
    return view('chronofront.rfidlive-simple');
});
```

### 2. Ajouter les routes API dans `routes/api.php`

```php
use App\Http\Controllers\Api\RfidLogController;

// RFID Raw Logs API
Route::get('rfid/raw-logs', [RfidLogController::class, 'getRawLogs']);
Route::post('rfid/clear-logs', [RfidLogController::class, 'clearLogs']);
```

### 3. S'assurer que RaspberryController2 est utilisé

Dans `routes/api.php`, vérifiez que vous avez :

```php
use App\Http\Controllers\Api\RaspberryController2;

// RFID Raspberry Reader Routes
Route::match(['post', 'put'], 'raspberry', [RaspberryController2::class, 'store']);
```

### 4. Tester

**Accédez à l'interface :**
```
http://127.0.0.1:8000/rfidlive-simple
```

**Envoyez une requête de test :**
```powershell
Invoke-WebRequest -Uri http://127.0.0.1:8000/api/raspberry -Method POST -Headers @{"Serial"="999"; "Content-Type"="application/json"} -Body '[{"serial":"30000001","timestamp":1673524800}]'
```

Vous devriez voir la requête apparaître instantanément dans l'interface !

## Fonctionnalités

- ✅ **Affichage en temps réel** : Polling toutes les secondes
- ✅ **Pas besoin de configuration** : Affiche toutes les requêtes HTTP reçues
- ✅ **Style terminal** : Fond noir, texte vert, style hacker
- ✅ **Auto-scroll** : Défilement automatique vers le haut
- ✅ **Limite de 100 entrées** : Évite la surcharge mémoire
- ✅ **Animation** : Les nouvelles entrées sont surlignées

## Format des logs

Chaque log affiche :
- **Timestamp** : Date et heure de réception
- **Méthode HTTP** : POST, PUT, etc.
- **Serial** : Numéro du lecteur RFID
- **Status** : Code HTTP (200, 400, 404)
- **Données brutes** : Le contenu JSON de la requête

## Stockage

Les logs sont stockés dans le **cache Laravel** (pas en base de données) :
- Limite : 100 dernières requêtes
- Durée : 1 heure
- Aucune impact sur la performance

## Différences avec /rfidlive original

| Feature | /rfidlive original | /rfidlive-simple |
|---------|-------------------|------------------|
| Affichage | Table de résultats DB | Requêtes HTTP brutes |
| Filtres | Oui (recherche, statut) | Non |
| Données | Depuis table `results` | Depuis cache |
| Configuration | Nécessite reader, entrants | Aucune |
| Use case | Production | Debug/Test |

## Utilisation

### Debug lecteur RFID
Parfait pour tester si un lecteur RFID envoie correctement ses données :
1. Ouvrez `/rfidlive-simple`
2. Configurez le lecteur pour pointer vers `/api/raspberry`
3. Observez les requêtes arriver en temps réel

### Test d'intégration
Testez l'endpoint API sans avoir à créer des événements/courses/entrants :
```powershell
# Envoyez plusieurs requêtes
for ($i=1; $i -le 10; $i++) {
    Invoke-WebRequest -Uri http://127.0.0.1:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{\"serial\":\"200000$i\",\"timestamp\":$(Get-Date -UFormat %s)}]"
    Start-Sleep -Seconds 1
}
```

Regardez-les défiler dans l'interface !

## Dépannage

### Aucun log n'apparaît
- Vérifiez que RaspberryController2 est bien utilisé dans les routes
- Vérifiez que le cache Laravel fonctionne (`php artisan cache:clear`)
- Regardez les logs Laravel : `storage/logs/laravel.log`

### Erreur 404 sur /api/rfid/raw-logs
- Vérifiez que la route est ajoutée dans `routes/api.php`
- Exécutez `php artisan route:list` pour voir toutes les routes

### Erreur "Class RfidLogController does not exist"
- Vérifiez que le fichier existe dans `app/Http/Controllers/Api/RfidLogController.php`
- Exécutez `composer dump-autoload`
