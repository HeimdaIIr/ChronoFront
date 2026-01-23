# Comment redémarrer le serveur pour appliquer les modifications PHP

Les modifications de timezone et de format de date nécessitent un redémarrage du serveur web.

## Option 1: Serveur de développement Laravel (php artisan serve)

Si vous utilisez `php artisan serve`:

```bash
# 1. Arrêtez le serveur (Ctrl+C dans le terminal où il tourne)
# 2. Relancez-le:
cd /home/user/ChronoFront
php artisan serve
```

## Option 2: Docker

Si vous utilisez Docker Compose:

```bash
docker-compose restart
# ou
docker-compose down && docker-compose up -d
```

## Option 3: Apache

```bash
sudo service apache2 restart
# ou
sudo systemctl restart apache2
```

## Option 4: Nginx + PHP-FPM

```bash
sudo service php8.2-fpm restart
sudo service nginx restart
# ou
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

## Option 5: Autre serveur (XAMPP, WAMP, etc.)

Utilisez l'interface de contrôle pour redémarrer Apache/PHP-FPM.

## Vérification après redémarrage

Testez que le timezone est bien appliqué:

```bash
php artisan tinker --execute="echo now()->format('Y-m-d H:i:s') . PHP_EOL; echo config('app.timezone') . PHP_EOL;"
```

Devrait afficher l'heure locale (Europe/Paris).

## Ensuite, testez le TOP départ

1. Allez dans l'interface Waves
2. Créez une wave si vous n'en avez pas
3. Cliquez sur "TOP départ (NOW)"
4. Vérifiez que l'heure affichée est correcte (pas de décalage d'1h)
