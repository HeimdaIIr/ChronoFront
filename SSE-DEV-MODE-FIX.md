# Fix SSE Blocking avec php artisan serve

## Le Problème

`php artisan serve` est **mono-thread** : il ne peut traiter qu'**une seule requête à la fois**.

Quand vous ouvrez `/rfidlive-instant` ou `/rfidlive-ultra` :
- La connexion SSE reste ouverte indéfiniment (`while(true)`)
- Elle monopolise le seul worker PHP disponible
- **Toutes les autres requêtes sont bloquées** (pages qui ne chargent pas, lag)

## La Solution

### Option 1 : Timeout automatique (RECOMMANDÉ pour dev local)

Le SSE se ferme automatiquement après 30 secondes et reconnecte automatiquement.

**Déjà implémenté** dans `RfidLogController.php` :
```php
$maxDuration = env('SSE_MAX_DURATION', 30); // 30s par défaut
```

**Pour l'activer**, ajoutez dans `.env` :
```env
SSE_MAX_DURATION=30
```

Relancez le serveur :
```bash
php artisan serve
```

**Avantages** :
- ✅ Libère le serveur toutes les 30s
- ✅ Reconnexion automatique invisible
- ✅ Pas de blocage total

**Inconvénients** :
- ⚠️ Pendant les 30s, le serveur reste bloqué
- ⚠️ Si vous ouvrez plusieurs onglets SSE simultanément, le serveur reste bloqué

### Option 2 : Utiliser uniquement /rfidlive-simple (polling)

**Fermez tous les onglets avec** :
- `/rfidlive-instant`
- `/rfidlive-ultra`

**Utilisez uniquement** :
- `/rfidlive-simple` (polling toutes les 1 seconde, pas de blocage)

**Avantages** :
- ✅ Aucun blocage
- ✅ Fonctionne parfaitement avec `php artisan serve`

**Inconvénients** :
- ⚠️ Latence 1 seconde (vs 100ms pour SSE)
- ⚠️ Moins performant pour haute vitesse

### Option 3 : Utiliser Apache/Nginx (RECOMMANDÉ pour production)

Sur Raspberry Pi avec Apache (ou Nginx + PHP-FPM), il y a plusieurs workers PHP.

**Configuration pour production** dans `.env` :
```env
SSE_MAX_DURATION=0
```

Cela désactive le timeout → connexion SSE illimitée, pas de problème car multi-thread.

**Sur Raspberry Pi avec Apache** :
```bash
# Apache gère plusieurs requêtes simultanées
# Une connexion SSE n'en bloque pas d'autres
```

## Marche à suivre MAINTENANT

### 1. Vérifier si des pages SSE sont ouvertes

Fermez TOUS les onglets de navigateur avec :
- `http://localhost:8000/rfidlive-instant`
- `http://localhost:8000/rfidlive-ultra`

### 2. Ajouter la config dans .env

```bash
# Éditez .env
echo "SSE_MAX_DURATION=30" >> .env
```

### 3. Relancer le serveur

```bash
php artisan serve
```

### 4. Tester avec une seule page à la fois

```bash
# Ouvrez UNE SEULE page
http://localhost:8000/rfidlive-ultra
```

**NE PAS ouvrir plusieurs onglets SSE simultanément avec php artisan serve !**

### 5. Vérifier que ça fonctionne

Dans un autre terminal, envoyez une détection :
```powershell
Invoke-WebRequest -Uri http://localhost:8000/api/raspberry `
    -Method POST `
    -Headers @{"Serial"="120"} `
    -Body '[{"serial":"2000001","timestamp":1673524800}]'
```

La détection devrait apparaître dans `/rfidlive-ultra` en ~100ms.

### 6. Vérifier que le serveur n'est plus bloqué

Après avoir ouvert `/rfidlive-ultra`, essayez d'ouvrir :
```
http://localhost:8000/events
```

- **AVANT le fix** : Page ne charge pas (timeout)
- **APRÈS le fix** : Page charge après max 30s

## Comportement avec le timeout

```
0s     : Ouvre /rfidlive-ultra → SSE se connecte
0-30s  : Serveur BLOQUÉ pour autres requêtes (mais SSE fonctionne)
30s    : SSE se ferme automatiquement
30.1s  : SSE reconnecte automatiquement
30-60s : Serveur BLOQUÉ à nouveau
60s    : SSE se ferme et reconnecte
...
```

**Pendant les fenêtres de 30s**, le serveur est bloqué mais au moins il se libère régulièrement.

## Recommandations par environnement

| Environnement | Configuration | Interface recommandée |
|---------------|---------------|----------------------|
| **Dev local (VSCode)** | `SSE_MAX_DURATION=30` | `/rfidlive-simple` (polling) |
| **Test lecteur (local)** | `SSE_MAX_DURATION=30` | `/rfidlive-ultra` (1 onglet max) |
| **Raspberry Pi (Apache)** | `SSE_MAX_DURATION=0` | `/rfidlive-ultra` (illimité) |
| **Production (Nginx)** | `SSE_MAX_DURATION=0` | `/rfidlive-ultra` (illimité) |

## Vérifier que le problème est résolu

### Test 1 : Une seule page SSE

```bash
# Terminal 1
php artisan serve

# Terminal 2 (PowerShell)
Start-Process "http://localhost:8000/rfidlive-ultra"

# Terminal 3 (après 5 secondes)
Invoke-WebRequest http://localhost:8000/events
```

**Résultat attendu** :
- Après max 30s, `/events` devrait charger
- Avant : timeout indéfiniment

### Test 2 : Plusieurs onglets (déconseillé)

```bash
# Ouvrir 3 onglets simultanément :
http://localhost:8000/rfidlive-ultra
http://localhost:8000/rfidlive-instant
http://localhost:8000/rfidlive-simple
```

**Résultat attendu** :
- Les 2 premiers (SSE) vont se bloquer mutuellement
- Le 3ème (polling) devrait marcher
- **Mieux : NE PAS faire ça avec php artisan serve**

## Pourquoi Apache/Nginx ne bloque pas ?

### php artisan serve (développement)
```
[Client 1] → [php artisan serve] → [Worker PHP unique]
                                         ↓
                                    SSE en while(true)
                                         ↓
[Client 2] → [php artisan serve] → ⏳ BLOQUÉ (en attente du worker)
```

### Apache + mod_php (production)
```
[Client 1] → [Apache] → [Worker PHP 1] → SSE en while(true)
[Client 2] → [Apache] → [Worker PHP 2] → Traite la requête normalement
[Client 3] → [Apache] → [Worker PHP 3] → Traite la requête normalement
```

Apache peut créer plusieurs processus PHP simultanément.

### Nginx + PHP-FPM (production)
```
[Client 1] → [Nginx] → [PHP-FPM worker 1] → SSE en while(true)
[Client 2] → [Nginx] → [PHP-FPM worker 2] → Traite la requête normalement
[Client 3] → [Nginx] → [PHP-FPM worker 3] → Traite la requête normalement
```

PHP-FPM gère un pool de workers (par défaut 5-10).

## Migration vers production

Sur Raspberry Pi, l'installation script configure Apache correctement :

```bash
# install-laravel8-apache-auto.sh fait déjà :
sudo apt-get install apache2 libapache2-mod-php php7.3-fpm
```

Avec Apache, **pas besoin de timeout SSE**, donc dans `.env` sur le Pi :

```env
SSE_MAX_DURATION=0
```

## Résumé

**Problème** : SSE bloque `php artisan serve` (mono-thread)

**Solution dev** : Timeout 30s → reconnexion auto

**Solution prod** : Apache/Nginx multi-thread → pas de timeout

**Utilisation actuelle** :
- Dev local : `/rfidlive-simple` (polling, pas de blocage)
- Test lecteur : `/rfidlive-ultra` avec `SSE_MAX_DURATION=30`
- Raspberry Pi : `/rfidlive-ultra` avec `SSE_MAX_DURATION=0`
