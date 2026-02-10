# Système Multi-Tenant - Documentation

## Architecture

Le système utilise une architecture multi-tenant avec bases de données séparées :

- **Base système** (`database/system.sqlite`) : Contient les comptes utilisateurs
- **Bases tenant** (`database/account_*.sqlite`) : Une base de données par compte, contenant les données métier

## Structure des migrations

### Migrations système
Emplacement : `database/migrations/system/`
- `create_accounts_table.php` - Table des comptes utilisateurs

### Migrations tenant
Emplacement : `database/migrations/tenant/`
- Toutes les migrations métier (events, categories, races, waves, entrants, results, etc.)
- **Toutes les futures migrations métier doivent être placées dans ce dossier**

## Commandes Artisan

### Créer un nouveau compte tenant

```bash
php artisan tenant:create {username} {email} [--password=] [--seed]
```

**Exemples :**
```bash
# Créer un compte avec mot de passe par défaut
php artisan tenant:create john john@example.com

# Créer un compte avec mot de passe personnalisé
php artisan tenant:create jane jane@example.com --password=secret123

# Créer un compte et initialiser avec des données de test
php artisan tenant:create test test@example.com --seed
```

Cette commande :
1. Crée un nouveau compte dans la base système
2. Crée un nouveau fichier de base de données tenant
3. Applique toutes les migrations tenant
4. Optionnellement, initialise avec des données de test

### Migrer toutes les bases tenant

```bash
php artisan tenant:migrate [--fresh] [--seed]
```

**Options :**
- `--fresh` : Supprime toutes les tables et réapplique les migrations
- `--seed` : Initialise les données après migration

**Exemples :**
```bash
# Appliquer les nouvelles migrations
php artisan tenant:migrate

# Réinitialiser complètement toutes les bases tenant
php artisan tenant:migrate --fresh

# Réinitialiser et initialiser avec des données de test
php artisan tenant:migrate --fresh --seed
```

Cette commande :
1. Lit tous les comptes depuis la base système
2. Pour chaque compte, applique les migrations sur sa base de données
3. Affiche le résultat pour chaque base de données

## Workflow de développement

### Créer une nouvelle migration métier

1. Créer la migration dans le dossier tenant :
```bash
php artisan make:migration create_my_table --path=database/migrations/tenant
```

2. Éditer le fichier de migration généré

3. Appliquer la migration à toutes les bases tenant :
```bash
php artisan tenant:migrate
```

### Modifier une table existante

1. Créer une nouvelle migration :
```bash
php artisan make:migration add_column_to_my_table --path=database/migrations/tenant
```

2. Appliquer la migration :
```bash
php artisan tenant:migrate
```

## Structure garantie

Toutes les bases de données tenant ont exactement la même structure :

**Tables actuelles (11) :**
- categories
- classements
- entrants
- events
- migrations
- races
- readers
- results
- rfid_detections
- screens
- waves

Cette structure est garantie par le système de migrations centralisé dans `database/migrations/tenant/`.

## Important

- **TOUJOURS** placer les nouvelles migrations métier dans `database/migrations/tenant/`
- **JAMAIS** modifier directement une base de données tenant
- Utiliser `tenant:migrate` pour garantir la cohérence entre toutes les bases
- Utiliser `tenant:create` pour créer de nouveaux comptes
