# Debug Lecteur RFID - Découvrir le format des requêtes

Votre lecteur portable (`reader_id: 200`) n'envoie peut-être pas les données dans le format attendu. Utilisez l'endpoint de debug pour voir **exactement** ce qu'il envoie.

## 🔍 Étape 1 : Trouver votre IP MacBook

```bash
./get-local-ip.sh
```

Exemple de résultat : `192.168.1.42`

## 🎯 Étape 2 : Configurer le lecteur sur l'endpoint DEBUG

Dans la configuration de votre lecteur portable :

```
URL: http://192.168.1.42:8000/api/rfid/debug
Method: POST (ou ce que permet le lecteur)
```

**N'utilisez PAS `/api/raspberry` pour le moment, utilisez `/api/rfid/debug` !**

## 📺 Étape 3 : Ouvrir l'interface de monitoring

Dans votre navigateur :
```
http://localhost:8000/rfidlive-ultra
```

Laissez cette page ouverte.

## 🏷️ Étape 4 : Passer une puce devant le lecteur

1. Assurez-vous que le lecteur est allumé et connecté au même WiFi
2. Passez une puce RFID devant le lecteur
3. Regardez `/rfidlive-ultra` → vous devriez voir la requête apparaître !

## 🔬 Étape 5 : Analyser les données reçues

Dans `/rfidlive-ultra`, vous verrez apparaître quelque chose comme :

```json
{
  "method": "POST",
  "headers": {
    "content-type": ["application/json"],
    "reader-id": ["200"],
    "serial": ["120"],
    ...
  },
  "body_raw": "{\"tags\":[{\"id\":\"2000003\",\"time\":1234567890}]}",
  "body_json": {
    "tags": [
      {"id": "2000003", "time": 1234567890}
    ]
  }
}
```

**Points à vérifier** :

### 1️⃣ Comment le lecteur s'identifie ?

Cherchez dans `headers` :
- `"serial"` ?
- `"reader-id"` ?
- `"reader_serial"` ?
- `"x-reader-id"` ?

**Votre lecteur** : Vous avez dit `reader_id: 200` donc il envoie probablement :
```json
"headers": {
  "reader-id": ["200"]
}
```

### 2️⃣ Quel est le format du body ?

Cherchez dans `body_json` :
- Format array simple : `[{"serial": "2000003", "timestamp": 123}]` ?
- Format avec clé : `{"tags": [...]}` ?
- Format avec clé : `{"detections": [...]}` ?
- Format différent ?

### 3️⃣ Comment sont nommés les champs ?

Dans chaque détection :
- Numéro de puce : `"serial"` ? `"tag"` ? `"id"` ? `"epc"` ?
- Timestamp : `"timestamp"` ? `"time"` ? `"datetime"` ?

## 📝 Étape 6 : Adapter le code pour votre lecteur

Une fois que vous savez le format exact, on peut créer un nouveau controller ou adapter `RaspberryController2` pour accepter ce format.

### Exemple 1 : Header `reader-id` au lieu de `Serial`

Si votre lecteur envoie `reader-id: 200` au lieu de `Serial: 200` :

```php
// Dans RaspberryController2.php
$readerSerial = $request->header('Serial') ?? $request->header('reader-id');
```

### Exemple 2 : Body avec clé `tags` au lieu d'array direct

Si votre lecteur envoie :
```json
{
  "tags": [
    {"id": "2000003", "time": 1234567890}
  ]
}
```

Au lieu de :
```json
[
  {"serial": "2000003", "timestamp": 1234567890}
]
```

Adapter le code :
```php
$data = $request->json()->all();

// Si c'est un array direct
if (isset($data[0])) {
    $detections = $data;
}
// Si c'est dans une clé "tags"
elseif (isset($data['tags'])) {
    $detections = $data['tags'];
}
// Si c'est dans une clé "detections"
elseif (isset($data['detections'])) {
    $detections = $data['detections'];
}
```

### Exemple 3 : Champs différents

Si votre lecteur utilise `id` au lieu de `serial` :

```php
foreach ($detections as $detection) {
    $tagSerial = $detection['serial'] ?? $detection['id'] ?? $detection['tag'] ?? $detection['epc'];
    $timestamp = $detection['timestamp'] ?? $detection['time'] ?? time();
}
```

## 🚀 Étape 7 : Tester avec l'endpoint final

Une fois qu'on a adapté le code, changez l'URL du lecteur vers l'endpoint réel :

```
URL: http://192.168.1.42:8000/api/raspberry
```

Et vérifiez que les détections arrivent bien dans `/rfidlive-ultra` avec le bon format !

## 🛠️ Dépannage

### Aucune requête n'apparaît dans /rfidlive-ultra

1. **Vérifiez que les serveurs tournent** :
   ```bash
   ./start-dev-servers.sh
   ```

2. **Vérifiez que le lecteur et le MacBook sont sur le même réseau** :
   ```bash
   # Sur le MacBook
   ping [IP_DU_LECTEUR]
   ```

3. **Vérifiez l'URL configurée dans le lecteur** :
   - Doit commencer par `http://` (pas `https://`)
   - Doit pointer vers l'IP du MacBook (pas `localhost`)
   - Doit finir par `/api/rfid/debug`

4. **Vérifiez le pare-feu macOS** :
   - Préférences Système → Sécurité → Pare-feu → Désactivé (pour test)

### La requête apparaît mais avec des erreurs

C'est normal ! L'endpoint de debug accepte **TOUT** et retourne toujours 200 OK.

Regardez le contenu de la requête pour comprendre le format du lecteur.

### Le lecteur affiche une erreur

Vérifiez les logs du serveur :
```bash
tail -f storage/logs/laravel.log
```

Ou testez manuellement :
```bash
curl http://localhost:8000/api/rfid/debug \
  -X POST \
  -H "reader-id: 200" \
  -d '{"test": "hello"}'
```

Devrait retourner :
```json
{
  "success": true,
  "message": "Debug data logged successfully",
  "received": {...}
}
```

## 📋 Checklist avant de configurer le lecteur réel

- [ ] Les 2 serveurs sont lancés (`./start-dev-servers.sh`)
- [ ] `/rfidlive-ultra` est ouvert dans le navigateur
- [ ] L'IP du MacBook est trouvée (`./get-local-ip.sh`)
- [ ] Le pare-feu macOS est désactivé ou PHP autorisé
- [ ] Le lecteur et le MacBook sont sur le même réseau WiFi
- [ ] L'URL configurée dans le lecteur : `http://[IP]:8000/api/rfid/debug`

## 📸 Exemple de ce que vous devriez voir

Dans `/rfidlive-ultra`, après avoir passé une puce :

```
[DEBUG DATA] [14:32:15.456] [200]

Headers:
  content-type: application/json
  reader-id: 200

Body:
  {
    "tags": [
      {"id": "2000003", "time": 1673524800}
    ]
  }
```

**Envoyez-moi cette sortie**, et je vais adapter le code pour que votre lecteur fonctionne avec `/api/raspberry` !

## 🔄 Workflow complet

```
1. Configure lecteur → http://[IP]:8000/api/rfid/debug
2. Passe une puce
3. Regarde /rfidlive-ultra pour voir le format
4. Envoie-moi le format exact
5. J'adapte RaspberryController2 pour ton lecteur
6. Change URL lecteur → http://[IP]:8000/api/raspberry
7. ✅ Ça fonctionne !
```

---

**Prêt à tester ?** Lance les serveurs et configure ton lecteur sur `/api/rfid/debug` !
