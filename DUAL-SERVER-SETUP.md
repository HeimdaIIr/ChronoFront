# Solution Dual-Server pour SSE

## Le Problème

`php artisan serve` est **mono-thread** : il ne peut traiter qu'**une seule requête à la fois**.

Quand vous ouvrez `/rfidlive-ultra` :
- La connexion SSE reste ouverte indéfiniment (`while(true)`)
- Elle monopolise le seul worker PHP disponible
- **Toutes les autres pages sont bloquées**

## La VRAIE Solution : 2 Serveurs PHP

Au lieu d'un seul serveur, on lance **DEUX serveurs PHP** sur des ports différents :

```
┌─────────────────────────────────────┐
│  Port 8000 : Serveur Principal      │
│  - Interface web (/events, etc.)    │
│  - API RFID (POST /api/raspberry)   │
│  - Pages normales                   │
│  ✅ JAMAIS BLOQUÉ                   │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  Port 8001 : Serveur SSE Dédié      │
│  - UNIQUEMENT /api/rfid/live-stream │
│  - Reste en while(true) en continu  │
│  ⚠️ PEUT être bloqué (mais on s'en │
│     fiche, il fait que du SSE !)    │
└─────────────────────────────────────┘
```

**Avantages** :
✅ L'application principale (port 8000) n'est **JAMAIS** bloquée
✅ Le SSE (port 8001) peut rester ouvert indéfiniment sans problème
✅ Vous pouvez ouvrir **plusieurs pages simultanément**
✅ **Zéro timeout**, **zéro reconnexion**, tout est fluide

## Installation

### 1. Configurer l'environnement

Ajoutez dans `.env` :
```env
SSE_SERVER_URL=http://localhost:8001
```

### 2. Lancer les 2 serveurs

#### Windows (PowerShell)
```powershell
.\start-dev-servers.ps1
```

#### Linux / macOS
```bash
./start-dev-servers.sh
```

Le script lance automatiquement :
- **Terminal 1** : `php artisan serve --port=8000` (app principale)
- **Terminal 2** : `php artisan serve --port=8001` (SSE uniquement)

### 3. Utiliser l'application

```
http://localhost:8000/rfidlive-ultra
```

Le frontend charge depuis le port 8000, mais le SSE se connecte automatiquement au port 8001.

## Comment ça marche ?

### Frontend (rfidlive-ultra.blade.php)

```javascript
// Utilise automatiquement le port 8001 pour SSE
const sseUrl = '{{ env("SSE_SERVER_URL", "http://localhost:8000") }}/api/rfid/live-stream';
eventSource = new EventSource(sseUrl);
```

### Flux des requêtes

```
┌──────────────┐
│  Navigateur  │
└──────┬───────┘
       │
       ├─── Chargement de la page ───────► Port 8000 (Main)
       │                                    - GET /rfidlive-ultra
       │                                    - Retourne HTML
       │
       ├─── Connexion SSE ──────────────► Port 8001 (SSE)
       │                                    - GET /api/rfid/live-stream
       │                                    - Reste ouvert en continu
       │
       └─── Détections RFID ────────────► Port 8000 (Main)
                                            - POST /api/raspberry
                                            - Écrit dans le cache
```

### Cache partagé

Les **deux serveurs partagent le même cache** :

```
┌────────────────────────────────┐
│  storage/framework/cache/      │
│                                │
│  rfid_raw_logs = [...]         │
└───────┬────────────────┬───────┘
        │                │
   ┌────▼─────┐    ┌────▼─────┐
   │ Port 8000│    │ Port 8001│
   │   WRITE  │    │   READ   │
   └──────────┘    └──────────┘
```

1. Le lecteur RFID envoie POST → Port 8000
2. Port 8000 écrit dans `Cache::put('rfid_raw_logs', ...)`
3. Port 8001 lit depuis `Cache::get('rfid_raw_logs')`
4. Port 8001 envoie via SSE au navigateur

**C'est instantané** car le cache est sur le disque local (ou Redis si configuré).

## Test Complet

### 1. Lancer les serveurs

```powershell
.\start-dev-servers.ps1
```

Vous devriez voir :
```
[1] Starting Main Server on port 8000...
[2] Starting SSE Server on port 8001...

SERVERS RUNNING!
Main App:    http://localhost:8000
SSE Stream:  http://localhost:8001/api/rfid/live-stream
```

### 2. Ouvrir l'interface RFID Live

```
http://localhost:8000/rfidlive-ultra
```

Vérifiez que le statut est **● Connecté** (vert).

### 3. Envoyer une détection

Dans un autre PowerShell :
```powershell
Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
    -Method POST `
    -Headers @{"Serial"="120"} `
    -Body '[{"serial":"2000001","timestamp":1673524800}]'
```

La détection devrait apparaître **instantanément** dans `/rfidlive-ultra`.

### 4. Vérifier que l'app n'est pas bloquée

Pendant que `/rfidlive-ultra` est ouvert, essayez d'accéder à :
```
http://localhost:8000/events
http://localhost:8000/races
http://localhost:8000/entrants
```

**Toutes les pages doivent charger instantanément !**

Avant (1 serveur) : ⏳ Timeout
Après (2 serveurs) : ✅ Instantané

## Utilisation Avancée

### Ouvrir plusieurs onglets SSE

Vous pouvez maintenant ouvrir **plusieurs onglets** simultanément :
```
http://localhost:8000/rfidlive-ultra
http://localhost:8000/rfidlive-instant
```

Le port 8001 peut gérer plusieurs connexions SSE si les clients se connectent à différents moments.

⚠️ **Limitation** : `php artisan serve` sur port 8001 reste mono-thread, donc :
- 1ère connexion SSE : ✅ Fonctionne
- 2ème connexion SSE : ⏳ En attente que la 1ère se libère

**Solution** : Utilisez Apache/Nginx en production (multi-thread).

### Test de performance extrême

```powershell
# Envoyer 100 détections en parallèle
1..100 | ForEach-Object -Parallel {
    Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
        -Method POST `
        -Headers @{"Serial"="120"} `
        -Body "[{`"serial`":`"200$_`",`"timestamp`":$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds())}]"
} -ThrottleLimit 50
```

Toutes les détections arrivent instantanément, et **l'app reste fluide** !

## Configuration Lecteur RFID

Configurez votre lecteur pour envoyer vers le **port 8000** (pas 8001) :

```
URL: http://192.168.1.100:8000/api/raspberry
Method: POST
Header: Serial: 120
```

⚠️ **N'envoyez PAS vers le port 8001** ! Le port 8001 est réservé au SSE.

## Déploiement sur Raspberry Pi

Sur Raspberry Pi, **pas besoin de 2 serveurs** car Apache est multi-thread.

Dans `.env` sur le Pi :
```env
# Pas besoin de SSE_SERVER_URL sur Raspberry Pi
# Apache gère tout sur le port 80
```

Le script d'installation (`install-laravel8-apache-auto.sh`) configure déjà Apache correctement.

## Comparaison

| Mode | Dev Local | Raspberry Pi |
|------|-----------|--------------|
| **Serveur Web** | php artisan serve | Apache |
| **Architecture** | Mono-thread | Multi-thread (workers) |
| **Ports** | 8000 + 8001 | 80 uniquement |
| **SSE** | Port dédié 8001 | Même port 80 |
| **Blocage** | ✅ Résolu (2 serveurs) | ✅ Jamais (multi-thread) |
| **Lancement** | `start-dev-servers.ps1` | `systemctl start apache2` |

## Dépannage

### Port 8000 ou 8001 déjà utilisé

**Windows** :
```powershell
# Voir qui utilise le port 8000
netstat -ano | findstr :8000

# Tuer le processus (remplacer PID)
taskkill /PID <PID> /F
```

**Linux/macOS** :
```bash
# Voir qui utilise le port 8000
lsof -i :8000

# Tuer le processus
kill -9 <PID>
```

Relancez ensuite `start-dev-servers.ps1`.

### SSE ne se connecte pas (statut rouge)

1. Vérifiez que les 2 serveurs sont bien lancés :
   ```powershell
   netstat -ano | findstr :8000
   netstat -ano | findstr :8001
   ```

2. Vérifiez `.env` :
   ```env
   SSE_SERVER_URL=http://localhost:8001
   ```

3. Testez manuellement le SSE :
   ```
   http://localhost:8001/api/rfid/live-stream
   ```

   Vous devriez voir :
   ```
   retry: 1000
   ```

4. Vérifiez la console du navigateur (F12) :
   ```javascript
   EventSource failed: ...
   ```

### Le cache ne se synchronise pas entre les 2 serveurs

Vérifiez que les 2 serveurs utilisent le **même dossier** :

```powershell
# Terminal 1 et 2 doivent être dans le même répertoire
cd C:\path\to\ChronoFront
.\start-dev-servers.ps1
```

Le cache est dans `storage/framework/cache/data/`, les 2 serveurs doivent pointer vers le même dossier.

### Les détections n'arrivent pas

1. Vérifiez que la détection arrive bien au port 8000 :
   ```powershell
   Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
       -Method POST `
       -Headers @{"Serial"="120"} `
       -Body '[{"serial":"2000001","timestamp":1673524800}]'
   ```

   Devrait retourner **200 OK**.

2. Vérifiez que le cache contient les logs :
   ```
   http://localhost:8000/api/rfid/raw-logs
   ```

   Devrait retourner un JSON avec les logs.

3. Vérifiez que le SSE lit le cache :
   Ouvrez `http://localhost:8001/api/rfid/live-stream` et envoyez une détection.

   Vous devriez voir apparaître :
   ```
   event: detection
   data: {"id":1,"serial":"120",...}
   ```

## Arrêter les serveurs

**Méthode 1** : Ctrl+C dans le terminal PowerShell
- Arrête proprement les 2 serveurs

**Méthode 2** : Tuer les processus manuellement
```powershell
# Windows
Get-Process php | Stop-Process -Force

# Linux/macOS
killall php
```

## Utilisation Quotidienne

### Routine de développement

1. **Matin** : Lancer les serveurs
   ```powershell
   .\start-dev-servers.ps1
   ```

2. **Pendant la journée** : Travailler normalement
   - L'app est sur `http://localhost:8000`
   - Le SSE fonctionne en arrière-plan sur port 8001
   - Tout reste fluide même avec `/rfidlive-ultra` ouvert

3. **Soir** : Arrêter les serveurs
   - Ctrl+C dans le terminal PowerShell

### Recommandations

- ✅ Gardez le terminal PowerShell ouvert pour voir les logs
- ✅ Un seul onglet `/rfidlive-ultra` à la fois (limitation mono-thread du port 8001)
- ✅ Vous pouvez ouvrir autant de pages normales que vous voulez (port 8000 reste dispo)
- ✅ Pour tester plusieurs lecteurs simultanément, utilisez Apache (voir section Raspberry Pi)

## Résumé

**Avant (1 serveur)** :
- ❌ SSE bloque tout
- ❌ Pages ne chargent pas
- ❌ App inutilisable

**Après (2 serveurs)** :
- ✅ SSE dédié sur port 8001
- ✅ App fluide sur port 8000
- ✅ Tout fonctionne en parallèle
- ✅ Zéro timeout, zéro lag

**C'est la vraie solution professionnelle pour le développement local !**
