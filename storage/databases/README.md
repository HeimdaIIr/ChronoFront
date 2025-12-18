# Databases Multi-Tenant

Ce dossier contient les bases de données SQLite pour chaque tenant (sous-domaine).

## Structure

```
storage/databases/
├── chrono1.sqlite    → Base de données pour chrono1.ats-chronos.fr
├── chrono2.sqlite    → Base de données pour chrono2.ats-chronos.fr
├── chrono3.sqlite    → Base de données pour chrono3.ats-chronos.fr
└── ...
```

## Fonctionnement

Quand un utilisateur accède à `chrono1.ats-chronos.fr` :
1. Le middleware `TenantBySubdomain` détecte "chrono1"
2. Il switch automatiquement vers `storage/databases/chrono1.sqlite`
3. Si la DB n'existe pas, elle est créée et les migrations sont exécutées

## Archivage

Après un événement, pour réutiliser un sous-domaine :

```bash
# Archiver l'événement terminé
mv chrono1.sqlite archives/chrono1_pignan_20250118.sqlite

# Le sous-domaine chrono1 est maintenant réutilisable
# À la prochaine connexion, une nouvelle DB vide sera créée
```

## Backup

Pour sauvegarder toutes les bases de données actives :

```bash
tar -czf databases_backup_$(date +%Y%m%d).tar.gz *.sqlite
```
