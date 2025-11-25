# ChronoFront

Application Laravel de chronométrage sportif pour la gestion d'événements, de parcours, de participants et de résultats en temps réel.

## 📋 Fonctionnalités

- **Gestion des événements** : Créer et gérer des événements sportifs
- **Gestion des parcours** : Définir les parcours (races) avec distances, types (1 passage, n tours, boucle infinie)
- **Gestion des vagues** : Organiser les départs par vagues
- **Gestion des participants** : Import CSV, attribution automatique des catégories FFA
- **Chronométrage** : Enregistrement des temps via RFID ou manuel
- **Résultats** : Calcul automatique des classements généraux et par catégorie
- **Catégories FFA** : 36 catégories officielles pré-configurées

## 🚀 Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0 ou supérieur
- Node.js & npm

### Étapes d'installation

1. **Installer les dépendances**
   ```bash
   composer install
   npm install
   ```

2. **Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Base de données**
   ```bash
   # Créer la base de données MySQL
   CREATE DATABASE ats_sport_chronofront CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   # Configurer .env
   DB_DATABASE=ats_sport_chronofront
   DB_USERNAME=root
   DB_PASSWORD=
   
   # Migrations
   php artisan migrate
   php artisan db:seed --class=CategorySeeder
   ```

4. **Lancer l'application**
   ```bash
   npm run dev
   php artisan serve
   ```

## 📚 API REST

Documentation complète disponible dans le fichier README.
