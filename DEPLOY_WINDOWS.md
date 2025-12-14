# 🪟 Déploiement ChronoFront V2 depuis Windows

## 🚀 Méthode 1 : PowerShell (RECOMMANDÉE)

### Étape 1 : Créer l'archive

```powershell
# Ouvrir PowerShell dans le dossier du projet
cd C:\Users\VotreNom\ChronoFront

# Exécuter le script
.\scripts\deploy_prepare.ps1
```

**Note :** Si vous avez une erreur de sécurité PowerShell :
```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\scripts\deploy_prepare.ps1
```

### Étape 2 : Transférer via FTP

**Avec FileZilla :**
1. Ouvrir FileZilla
2. Connexion :
   - Hôte : `10.8.0.107` (ou `192.168.10.157`)
   - Utilisateur : `pi`
   - Port : `21` (ou `22` pour SFTP)
3. Transférer ces fichiers vers `/home/pi/` :
   - `chronofront-v2_YYYYMMDD_HHMMSS.tar.gz`
   - `scripts/deploy_install.sh`

**Avec WinSCP :**
1. Ouvrir WinSCP
2. Nouvelle connexion :
   - Protocole : SFTP
   - Hôte : `10.8.0.107`
   - Utilisateur : `pi`
3. Glisser-déposer les fichiers

### Étape 3 : Installer via SSH

**Avec PuTTY :**
1. Ouvrir PuTTY
2. Host : `10.8.0.107`
3. Se connecter

**Avec PowerShell (Windows 10+) :**
```powershell
ssh pi@10.8.0.107
```

**Puis exécuter :**
```bash
chmod +x deploy_install.sh
sudo bash deploy_install.sh chronofront-v2_*.tar.gz
```

---

## 🚀 Méthode 2 : Git Bash

Si vous avez Git installé sur Windows :

```bash
# Ouvrir Git Bash (clic droit → Git Bash Here)
cd /c/Users/VotreNom/ChronoFront
bash scripts/deploy_prepare.sh
```

Puis suivre les étapes 2 et 3 ci-dessus.

---

## 🚀 Méthode 3 : WSL (Windows Subsystem for Linux)

Si vous avez WSL installé :

```bash
# Ouvrir WSL (Ubuntu)
cd /mnt/c/Users/VotreNom/ChronoFront
bash scripts/deploy_prepare.sh
```

Puis suivre les étapes 2 et 3 ci-dessus.

---

## 🚀 Méthode 4 : Manuelle (sans script)

### Étape 1 : Créer l'archive manuellement

1. **Installer 7-Zip** depuis https://www.7-zip.org/

2. **Créer un dossier temporaire** `deploy_temp`

3. **Copier ces fichiers/dossiers** dans `deploy_temp` :
   ```
   app/
   bootstrap/ (vide les caches)
   config/
   database/ (sans database.sqlite)
   public/
   resources/
   routes/
   scripts/
   storage/ (vider logs, cache, sessions, views)
   artisan
   composer.json
   composer.lock
   package.json
   package-lock.json
   vite.config.js
   ```

4. **Créer un fichier `.env`** dans `deploy_temp` :
   ```env
   APP_NAME=ChronoFront
   APP_ENV=production
   APP_KEY=
   APP_DEBUG=false
   APP_URL=http://107.course.ats-sport.com

   LOG_CHANNEL=stack
   LOG_LEVEL=error

   DB_CONNECTION=sqlite
   DB_DATABASE=/var/www/chronofront-v2/database/database.sqlite

   BROADCAST_DRIVER=log
   CACHE_DRIVER=file
   FILESYSTEM_DISK=local
   QUEUE_CONNECTION=sync
   SESSION_DRIVER=file
   SESSION_LIFETIME=120
   ```

5. **Créer les dossiers vides** dans `deploy_temp/storage/` :
   ```
   storage/app/public/
   storage/framework/cache/data/
   storage/framework/sessions/
   storage/framework/views/
   storage/logs/
   ```

6. **Avec 7-Zip, créer une archive .tar.gz** :
   - Clic droit sur `deploy_temp`
   - 7-Zip → Add to archive
   - Format : tar
   - Créer `chronofront-v2.tar`
   - Puis compresser `chronofront-v2.tar` en gzip
   - Renommer en `chronofront-v2_20251209.tar.gz`

7. **Transférer via FTP** (voir Méthode 1 - Étape 2)

8. **Installer via SSH** (voir Méthode 1 - Étape 3)

---

## 🧪 Tests post-installation (depuis Windows)

### Test 1 : Vérifier l'accès web

```powershell
# PowerShell
Invoke-WebRequest -Uri http://107.course.ats-sport.com/api/health | Select-Object -ExpandProperty Content
```

Ou dans le navigateur : `http://107.course.ats-sport.com`

### Test 2 : Tester l'API de santé

```powershell
curl.exe http://107.course.ats-sport.com/api/health
```

### Test 3 : Tester la réception RFID

```powershell
$body = @{
    tag = "TEST123"
    time = (Get-Date -Format "yyyy-MM-ddTHH:mm:ssZ")
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://107.course.ats-sport.com/api/raspberry" `
    -Method PUT `
    -Headers @{"Serial"="120"; "Content-Type"="application/json"} `
    -Body $body
```

---

## 🔧 Outils recommandés pour Windows

### Clients FTP/SFTP
- **FileZilla** (gratuit) - https://filezilla-project.org/
- **WinSCP** (gratuit) - https://winscp.net/
- **Cyberduck** (gratuit) - https://cyberduck.io/

### Clients SSH
- **PuTTY** (gratuit) - https://www.putty.org/
- **Windows Terminal** + SSH natif (Windows 10+)
- **MobaXterm** (gratuit/pro) - https://mobaxterm.mobatek.net/

### Outils de compression
- **7-Zip** (gratuit) - https://www.7-zip.org/
- **WinRAR** (essai gratuit) - https://www.rarlab.com/

### Terminal amélioré
- **Windows Terminal** (gratuit, Microsoft Store)
- **Git Bash** (inclus avec Git)
- **WSL** (Ubuntu sur Windows)

---

## 🆘 Problèmes courants sous Windows

### PowerShell : "Impossible d'exécuter ce script"

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
```

### "tar command not found"

- **Solution 1 :** Utiliser Windows 10 version 1803 ou supérieure (tar inclus)
- **Solution 2 :** Installer 7-Zip et utiliser la méthode manuelle
- **Solution 3 :** Utiliser Git Bash ou WSL

### Chemins avec espaces

Toujours utiliser des guillemets :
```powershell
cd "C:\Users\Mon Nom\Documents\ChronoFront"
```

### FileZilla : "Connection refused"

- Vérifier que vous utilisez le bon port (21 pour FTP, 22 pour SFTP)
- Vérifier que le Raspberry Pi est accessible (ping 10.8.0.107)
- Essayer avec WinSCP en mode SFTP

### SSH : "Connection timed out"

```powershell
# Tester la connectivité
Test-NetConnection -ComputerName 10.8.0.107 -Port 22
```

---

## ✅ Checklist Windows

- [ ] PowerShell script exécuté OU archive créée manuellement
- [ ] FileZilla/WinSCP installé et configuré
- [ ] Archive transférée vers `/home/pi/`
- [ ] Script `deploy_install.sh` transféré
- [ ] Connexion SSH établie (PuTTY/PowerShell)
- [ ] Installation exécutée sur la Pi
- [ ] Tests de santé réussis
- [ ] Interface web accessible depuis navigateur Windows

---

## 📚 Documentation complémentaire

- **`DEPLOY_QUICK_START.md`** - Guide rapide universel
- **`DEPLOYMENT_GUIDE_PI.md`** - Guide complet de déploiement
- **`READER_DEPLOYMENT_GUIDE.md`** - Configuration des lecteurs

---

🎉 **Bon déploiement depuis Windows !**
