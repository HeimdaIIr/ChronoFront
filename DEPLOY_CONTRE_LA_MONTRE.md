# Déploiement Support Contre-la-Montre (Départs Individuels)

**Date:** 14 Décembre 2025
**Pour:** Course du 14 décembre avec départs individuels

---

## 🎯 Fonctionnalité Ajoutée

**Support des départs individuels via colonne "TOP" dans le fichier CSV**

- ✅ Chaque coureur peut avoir sa propre heure de départ (format `HH:MM:SS` ou `HH:MM`)
- ✅ Calcul automatique du temps réel : `temps = heure_détection - heure_départ_individuelle`
- ✅ Priorité : `entrant.start_time` > `wave.start_time`
- ✅ Compatible avec le système de vagues existant

---

## 📦 Déploiement sur le VPS (URGENT - Avant la course)

### Étape 1 : Se connecter au VPS

```bash
ssh votre_user@87.106.13.88
cd /var/www/vhosts/recursing-nash.87-106-13-88.plesk.page/httpdocs
```

### Étape 2 : Pull des modifications depuis GitHub

```bash
# Récupérer les modifications
git fetch origin
git merge origin/claude/fix-rfid-duplication-01MRE3dhTfCMPgNFb14p1xsq

# OU si vous avez déjà poussé depuis le VPS (version la plus à jour)
# Les modifications sont déjà présentes !
```

### Étape 3 : Lancer la migration (Ajouter la colonne start_time)

```bash
# Connexion à la base de données MySQL
mysql -u votre_user -p

# Dans MySQL, exécuter :
USE votre_base_de_donnees;

ALTER TABLE entrants
ADD COLUMN start_time TIME NULL
COMMENT 'Heure de départ individuelle pour contre-la-montre (colonne TOP du CSV)'
AFTER wave_id;

exit;
```

**OU via artisan (si vendor/ installé) :**

```bash
php artisan migrate
```

### Étape 4 : Vérifier que tout fonctionne

```bash
# Tester l'import d'un CSV avec colonne TOP
# Voir section ci-dessous
```

---

## 📄 Format du fichier CSV avec colonne TOP

**Exemple de fichier CSV :**

```csv
prenom,nom,sexe,naissance,parcours,vague,cat,club,dossard,top
Jean,DUPONT,M,15/03/1985,10km,1,M0,ATS Sport,101,09:00:00
Marie,MARTIN,F,22/07/1990,10km,1,SE,Club Running,102,09:02:00
Paul,BERNARD,M,10/12/1978,10km,1,M2,,,103,09:04:00
Sophie,DUBOIS,F,05/08/1995,10km,1,SE,,,104,09:06:00
```

**Colonnes importantes :**
- `prenom`, `nom`, `parcours` : **OBLIGATOIRES**
- `top` : **OPTIONNELLE** - Si présente, utilisée pour le départ individuel
  - Format accepté : `HH:MM:SS` (ex: 09:00:00) ou `HH:MM` (ex: 09:00)
  - Si vide ou absente : utilise `wave.start_time` (comportement classique)

---

## 🏃 Utilisation pendant la course

### Import des participants

1. Aller sur l'interface ChronoFront
2. Menu **"Participants"** → **"Importer CSV"**
3. Uploader votre fichier CSV avec la colonne `TOP`
4. L'app détecte automatiquement la colonne et importe les heures de départ

### Chronométrage en temps réel

**Aucune action manuelle nécessaire !**

- ✅ Pas besoin de lancer le "TOP DÉPART" dans l'app
- ✅ Chaque détection RFID calcule automatiquement le temps basé sur `entrant.start_time`
- ✅ Les classements sont mis à jour en temps réel

**Exemple :**
```
Coureur 101 - Départ : 09:00:00
Passage détecté : 09:45:32
Temps calculé : 00:45:32 (45 min 32 sec)
```

### Configuration des lecteurs RFID

**URL à configurer sur vos Raspberry Pi :**

```
POST https://recursing-nash.87-106-13-88.plesk.page/api/raspberry
```

**Dans `/etc/spnet/spnet.conf` sur la Pi du lecteur :**

```ini
[UPLOAD]
ENABLE=1
METHOD=PUT
URL=https://recursing-nash.87-106-13-88.plesk.page/api/raspberry
```

---

## 🔧 Tests Recommandés (Avant la Course)

### Test 1 : Import CSV avec colonne TOP

```bash
# Créer un fichier test avec 2-3 coureurs
# Importer via l'interface
# Vérifier dans la base :

mysql -u votre_user -p
USE votre_base_de_donnees;

SELECT firstname, lastname, bib_number, start_time
FROM entrants
WHERE start_time IS NOT NULL
LIMIT 5;
```

**Résultat attendu :**
```
+-----------+----------+------------+------------+
| firstname | lastname | bib_number | start_time |
+-----------+----------+------------+------------+
| Jean      | DUPONT   | 101        | 09:00:00   |
| Marie     | MARTIN   | 102        | 09:02:00   |
+-----------+----------+------------+------------+
```

### Test 2 : Ajout manuel d'un temps

```bash
# Via l'interface, ajouter un temps pour un coureur avec start_time
# Vérifier que calculated_time est correct

SELECT
    e.firstname,
    e.lastname,
    e.start_time,
    r.raw_time,
    r.calculated_time,
    SEC_TO_TIME(r.calculated_time) as temps_affiche
FROM results r
JOIN entrants e ON r.entrant_id = e.id
WHERE e.start_time IS NOT NULL
LIMIT 5;
```

---

## ⚠️ Points d'Attention

### Cas d'usage mixte (vagues + départs individuels)

**Comportement du système :**

1. **Si `entrant.start_time` existe** → Utilise cette heure (priorité)
2. **Sinon, si `wave.start_time` existe** → Utilise l'heure de la vague
3. **Sinon** → Pas de calcul de temps (raw_time uniquement)

### Format d'heure dans le CSV

**Formats acceptés pour la colonne TOP :**
- ✅ `09:00:00` (HH:MM:SS)
- ✅ `09:00` (HH:MM - converti en 09:00:00)
- ✅ `9:00` (H:MM - converti en 09:00:00)
- ❌ `9h00` (non supporté)
- ❌ `09:00:00 AM` (non supporté)

### Compatibilité avec l'ancien système

**Rétrocompatible à 100% :**
- ✅ Si pas de colonne TOP → Fonctionne comme avant (vagues)
- ✅ Courses classiques non impactées
- ✅ Départs groupés toujours supportés

---

## 🐛 Dépannage

### Problème : La colonne TOP n'est pas importée

**Solution :**
```bash
# Vérifier que la colonne existe dans le CSV
head -1 fichier.csv | grep -i "top"

# Vérifier la migration
mysql -u user -p
SHOW COLUMNS FROM entrants LIKE 'start_time';
```

### Problème : Les temps calculés sont incorrects

**Vérifications :**
```sql
-- Vérifier les start_time importées
SELECT firstname, lastname, start_time, bib_number
FROM entrants
WHERE start_time IS NOT NULL;

-- Vérifier un résultat spécifique
SELECT
    e.firstname,
    e.start_time as depart,
    r.raw_time as detection,
    r.calculated_time as temps_secondes,
    SEC_TO_TIME(r.calculated_time) as temps_affiche
FROM results r
JOIN entrants e ON r.entrant_id = e.id
WHERE r.id = 123; -- Remplacer par l'ID du résultat
```

### Problème : Migration échoue

**Erreur possible :** `Column 'start_time' already exists`

**Solution :**
```sql
-- Vérifier si la colonne existe déjà
SHOW COLUMNS FROM entrants LIKE 'start_time';

-- Si elle existe déjà, skip la migration
-- Sinon, exécuter :
ALTER TABLE entrants ADD COLUMN start_time TIME NULL AFTER wave_id;
```

---

## 📞 Support Urgent (Jour J)

Si problème pendant la course :

1. **Les détections fonctionnent mais temps incorrects :**
   - Vérifier `entrant.start_time` dans la base
   - Forcer un recalcul : `POST /api/results/race/{raceId}/recalculate`

2. **Import CSV échoue :**
   - Vérifier le format de la colonne TOP (HH:MM:SS)
   - Importer sans la colonne TOP temporairement

3. **Rollback d'urgence :**
   ```sql
   -- Supprimer les start_time si problème
   UPDATE entrants SET start_time = NULL;

   -- Le système reviendra au calcul par vague
   ```

---

## ✅ Checklist Pré-Course

- [ ] Code déployé sur le VPS
- [ ] Migration `start_time` exécutée
- [ ] Test import CSV avec colonne TOP
- [ ] Test calcul temps sur 2-3 coureurs
- [ ] Lecteurs RFID configurés avec bonne URL
- [ ] Fichier CSV de la course prêt avec colonne TOP
- [ ] Backup base de données effectué

---

**Bonne course ! 🏃‍♂️🏆**

---

**Dernière mise à jour :** 14 Décembre 2025 - 11h00
**Version :** 1.0
**Auteur :** Claude AI + Heimdallr
