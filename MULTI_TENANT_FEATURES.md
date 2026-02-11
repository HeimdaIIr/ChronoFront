# Fonctionnalités Multi-Tenant - ChronoFront

Ce document décrit les 3 nouvelles fonctionnalités implémentées pour le système multi-tenant de ChronoFront.

## 1. Système de Permissions par Rôle

### Rôles disponibles

Le système supporte 3 rôles distincts :

- **admin** : Accès complet à l'application + gestion des comptes
- **orga** (Organisateur) : Peut créer et modifier des événements, courses, participants et résultats
- **viewer** : Accès en lecture seule, peut seulement consulter les données

### Implémentation

#### Middleware RoleMiddleware
- Fichier : `app/Http/Middleware/RoleMiddleware.php`
- Usage : `Route::middleware(['role:admin,orga'])`
- Les admins ont toujours accès à toutes les routes

#### Trait HasRolePermissions
- Fichier : `app/Traits/HasRolePermissions.php`
- Méthodes disponibles :
  - `$account->isAdmin()` - Vérifie si l'utilisateur est admin
  - `$account->isOrga()` - Vérifie si l'utilisateur est organisateur
  - `$account->isViewer()` - Vérifie si l'utilisateur est viewer
  - `$account->canEdit()` - Vérifie si l'utilisateur peut modifier (admin ou orga)
  - `$account->hasRole(['admin', 'orga'])` - Vérifie si l'utilisateur a l'un des rôles

### Utilisation dans les vues

```blade
@if(auth()->user()->isAdmin())
    <!-- Contenu visible uniquement pour les admins -->
@endif

@if(auth()->user()->canEdit())
    <button>Modifier</button>
@endif
```

## 2. Création Automatique de DB Tenant avec Nom Logique

### Fonctionnement

Lors de la création d'un nouveau compte, une base de données SQLite est automatiquement créée avec un nom logique basé sur le nom du compte fourni.

### Exemple

- Nom du compte : **MHR_Montpellier**
- Fichier DB créé : `database/MHR_Montpellier.sqlite`

### Implémentation

#### AccountController
- Fichier : `app/Http/Controllers/AccountController.php`
- La méthode `store()` :
  1. Nettoie le nom du compte (caractères spéciaux, espaces → underscores)
  2. Génère le nom du fichier DB : `{nom_nettoyé}.sqlite`
  3. Vérifie l'unicité (ajoute un suffixe `_1`, `_2`, etc. si nécessaire)
  4. Crée le fichier DB et exécute les migrations tenant automatiquement

#### Routes
- `GET /accounts` - Liste des comptes (admin uniquement)
- `GET /accounts/create` - Formulaire de création
- `POST /accounts` - Créer un nouveau compte
- `GET /accounts/{id}/edit` - Formulaire d'édition
- `PUT /accounts/{id}` - Mettre à jour un compte
- `POST /accounts/{id}/toggle` - Activer/Désactiver un compte
- `DELETE /accounts/{id}` - Supprimer un compte

### Accès

Un lien "Comptes" apparaît dans le sidebar pour les utilisateurs ayant le rôle **admin**.

## 3. Base de Données Principale de Centralisation

### Vue d'ensemble

Une base de données SQLite centrale (`main.sqlite`) stocke toutes les données de tous les tenants avec référence au compte propriétaire.

### Structure

#### Tables principales

- `main_events` - Tous les événements de tous les comptes
- `main_races` - Toutes les courses
- `main_entrants` - Tous les participants
- `main_results` - Tous les résultats
- `main_waves` - Toutes les vagues

Chaque table contient :
- `account_id` : ID du compte propriétaire (référence vers `accounts.id`)
- `tenant_{table}_id` : ID original dans la DB tenant
- Données complètes de l'entité

### Synchronisation Automatique

#### Trait SyncToMainDatabase
- Fichier : `app/Traits/SyncToMainDatabase.php`
- Implémenté par : Event, Race, Entrant

#### Observer SyncToMainObserver
- Fichier : `app/Observers/SyncToMainObserver.php`
- Écoute les événements : `created`, `updated`, `deleted`
- Synchronise automatiquement vers `main.sqlite`

### Initialisation

Pour créer et initialiser la base de données principale :

```bash
php artisan main:init
```

Cette commande :
1. Crée le fichier `database/main.sqlite`
2. Exécute toutes les migrations dans `database/migrations/main/`

### Cas d'usage

La base de données principale permet :
- **Analyses globales** : Statistiques sur tous les comptes
- **Recherche cross-tenant** : Rechercher un participant dans tous les événements
- **Reporting** : Générer des rapports consolidés
- **Backup centralisé** : Une seule DB à sauvegarder pour toutes les données

### Requêtes d'exemple

```php
// Tous les événements de tous les comptes
$allEvents = DB::connection('main')->table('main_events')->get();

// Événements d'un compte spécifique
$accountEvents = DB::connection('main')
    ->table('main_events')
    ->where('account_id', 1)
    ->get();

// Statistiques globales
$stats = DB::connection('main')->table('main_entrants')
    ->select('account_id', DB::raw('COUNT(*) as total'))
    ->groupBy('account_id')
    ->get();
```

## Configuration

### Connexions de base de données

Les connexions sont définies dans `config/database.php` :

- `system` : Base de données système (comptes, authentification)
- `tenant` : Base de données tenant (données spécifiques au compte connecté)
- `main` : Base de données principale (toutes les données centralisées)

## Sécurité

- Le middleware `role` empêche l'accès non autorisé aux routes protégées
- Les viewers ne peuvent pas créer/modifier de données
- Seuls les admins peuvent gérer les comptes
- La synchronisation vers la DB principale se fait en arrière-plan sans impact sur les performances

## Tests

Pour tester le système complet :

1. Créer un compte admin
2. Se connecter et créer un nouveau compte (ex: Test_Account)
3. Vérifier que `database/Test_Account.sqlite` existe
4. Créer un événement dans le compte
5. Vérifier que l'événement apparaît dans `main_events` avec le bon `account_id`

## Maintenance

### Nettoyage des données

Pour nettoyer les données d'un compte supprimé de la DB principale :

```php
DB::connection('main')->table('main_events')->where('account_id', $deletedAccountId)->delete();
DB::connection('main')->table('main_races')->where('account_id', $deletedAccountId)->delete();
// etc.
```

### Re-synchronisation

Si la synchronisation échoue, vous pouvez créer une commande artisan pour re-synchroniser :

```bash
php artisan tenant:sync {account_id}
```

## Améliorations Futures

- Ajouter un tableau de bord admin avec statistiques globales
- Implémenter la synchronisation pour Results et Waves
- Ajouter un système de backup automatique
- Créer une API pour interroger la DB principale
