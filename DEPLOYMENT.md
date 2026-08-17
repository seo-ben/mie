# 🚀 Guide de Déploiement Automatique - GitHub Actions → cPanel

## Architecture du déploiement

```
┌──────────────┐    push main    ┌──────────────────┐    SSH    ┌──────────────────┐
│   Votre PC   │ ──────────────► │  GitHub Actions  │ ───────► │  Serveur cPanel  │
│  (dev local) │                 │  (CI/CD Runner)  │          │  (production)    │
└──────────────┘                 └──────────────────┘          └──────────────────┘
                                        │                              │
                                   Tests & Build              git pull + migrate
                                   des assets                  + cache + restart
```

## 📋 Prérequis

- Compte GitHub avec le repository configuré
- Accès SSH au serveur cPanel
- PHP 8.2+ et Composer installés sur le serveur
- Git installé sur le serveur

---

## 🔧 Configuration étape par étape

### Étape 1 : Préparer le serveur cPanel

#### 1.1 Se connecter en SSH au serveur

```bash
ssh votre_user@votre_serveur.com
```

#### 1.2 Configurer Git sur le serveur

```bash
# Aller dans le dossier de l'application (ajuster le chemin)
cd ~/public_html
# OU
cd ~/votre-domaine.com

# Cloner le repository pour la première fois
git clone git@github.com:VOTRE_USERNAME/VOTRE_REPO.git .
# OU si le dossier existe déjà :
git init
git remote add origin git@github.com:VOTRE_USERNAME/VOTRE_REPO.git
git fetch origin
git checkout main
```

#### 1.3 Générer une clé SSH sur le serveur (pour que le serveur puisse pull depuis GitHub)

```bash
# Sur le serveur cPanel
ssh-keygen -t ed25519 -C "deploy@votre-serveur.com"

# Afficher la clé publique
cat ~/.ssh/id_ed25519.pub
```

> 📌 **Ajoutez cette clé publique** comme **Deploy Key** dans votre repository GitHub :
> - GitHub → Repository → Settings → Deploy keys → Add deploy key
> - Coller la clé publique
> - Cocher "Allow write access" (optionnel)

#### 1.4 Configurer le fichier `.env` sur le serveur

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer avec vos valeurs de production
nano .env
```

Configurez au minimum :
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=votre_base
DB_USERNAME=votre_user
DB_PASSWORD=votre_mot_de_passe
```

#### 1.5 Premier déploiement manuel

```bash
# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Créer le lien de stockage
php artisan storage:link

# Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Étape 2 : Configurer les Secrets GitHub

Allez dans votre repository GitHub :
**Settings → Secrets and variables → Actions → New repository secret**

Ajoutez les secrets suivants :

| Secret | Description | Exemple |
|--------|-------------|---------|
| `SSH_HOST` | Adresse IP ou domaine du serveur | `123.456.789.0` ou `votre-serveur.com` |
| `SSH_USERNAME` | Nom d'utilisateur SSH | `votreutilisateur` |
| `SSH_PRIVATE_KEY` | Clé privée SSH complète | Contenu de `~/.ssh/id_rsa` ou `id_ed25519` |
| `SSH_PORT` | Port SSH (si différent de 22) | `22` |
| `APP_PATH` | Chemin absolu de l'app sur le serveur | `/home/user/public_html` |

#### Comment obtenir la clé privée SSH :

```bash
# Sur votre PC local (pas le serveur !)
# Si vous n'avez pas encore de clé SSH :
ssh-keygen -t ed25519 -C "github-actions-deploy"

# Afficher la clé privée (à copier dans SSH_PRIVATE_KEY)
cat ~/.ssh/id_ed25519

# Afficher la clé publique (à ajouter sur le serveur)
cat ~/.ssh/id_ed25519.pub
```

#### Ajouter la clé publique sur le serveur :

```bash
# Sur le serveur cPanel
echo "VOTRE_CLE_PUBLIQUE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

---

### Étape 3 : Tester le déploiement

```bash
# Sur votre PC local
git add .
git commit -m "🚀 Setup CI/CD pipeline"
git push origin main
```

Allez ensuite sur GitHub → votre repository → **Actions** pour voir le déploiement en cours.

---

## 📁 Structure des fichiers de déploiement

```
├── .github/
│   └── workflows/
│       └── deploy.yml          ← Workflow GitHub Actions
├── deploy.sh                   ← Script de déploiement manuel (backup)
├── DEPLOYMENT.md               ← Cette documentation
└── .gitignore                  ← Fichiers exclus du versioning
```

---

## 🔍 Dépannage

### Le déploiement échoue avec "Permission denied"
- Vérifiez que la clé SSH est correctement ajoutée dans les secrets GitHub
- Vérifiez que la clé publique est dans `~/.ssh/authorized_keys` sur le serveur
- Vérifiez les permissions : `chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys`

### "Host key verification failed"
Le workflow utilise `StrictHostKeyChecking=no` par défaut avec `appleboy/ssh-action`.

### Les migrations échouent
- Vérifiez que le fichier `.env` est correctement configuré sur le serveur
- Vérifiez la connexion à la base de données

### Les assets ne se chargent pas
- Vérifiez que `npm run build` a généré les fichiers dans `public/build`
- Vérifiez les chemins dans `vite.config.js`

---

## 🔄 Déploiement manuel (si nécessaire)

Si GitHub Actions ne fonctionne pas, vous pouvez déployer manuellement :

```bash
# Se connecter au serveur
ssh votre_user@votre_serveur.com

# Aller dans le dossier de l'app
cd /chemin/vers/votre/app

# Exécuter le script de déploiement
bash deploy.sh
```

---

## 🛡️ Bonnes pratiques

1. **Ne jamais committer le `.env`** — Il contient des informations sensibles
2. **Toujours tester localement** avant de pusher sur `main`
3. **Utiliser des branches** pour le développement et merger via Pull Request
4. **Sauvegarder la base de données** avant les migrations importantes
5. **Vérifier les logs** après chaque déploiement : `storage/logs/laravel.log`
