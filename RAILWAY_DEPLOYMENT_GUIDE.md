# 🚀 Guide de Déploiement Backend sur Railway

Ce guide vous explique comment déployer votre backend Laravel sur Railway avec Docker pour un accès 24/7.

## 📋 Prérequis

- Un compte Railway (gratuit) : https://railway.app
- Votre code sur GitHub (ou GitLab/Bitbucket)
- Les variables d'environnement configurées

## 🎯 Étape 1 : Préparer le Repository

Le projet est déjà configuré avec :
- ✅ `Dockerfile.railway` - Dockerfile optimisé pour Railway
- ✅ `nginx.conf` - Configuration Nginx
- ✅ `supervisord.conf` - Gestion des processus
- ✅ `start.sh` - Script de démarrage
- ✅ `.dockerignore` - Fichiers à exclure
- ✅ `railway.json` - Configuration Railway

## 🚂 Étape 2 : Créer un Projet sur Railway

1. Allez sur https://railway.app
2. Cliquez sur **"New Project"**
3. Sélectionnez **"Deploy from GitHub repo"**
4. Choisissez votre repository `Deploy_back`

## 🗄️ Étape 3 : Ajouter une Base de Données MySQL

1. Dans votre projet Railway, cliquez sur **"+ New"**
2. Sélectionnez **"Database"** → **"Add MySQL"**
3. Railway créera automatiquement une base de données et générera les variables :
   - `MYSQL_URL`
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`
   - `MYSQL_DATABASE`

## ⚙️ Étape 4 : Configurer les Variables d'Environnement

Dans votre service backend (pas la base de données), allez dans l'onglet **"Variables"** et ajoutez :

### Variables Essentielles
```bash
# Application
APP_NAME="AREA Backend"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VOTRE_CLE_ICI  # Railway peut la générer automatiquement

# Base de données (utiliser les références Railway)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

# URL de l'application (sera générée par Railway)
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache & Queue
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (optionnel, à configurer selon vos besoins)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@area.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Variables OAuth (si nécessaire)
```bash
# Google OAuth
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_secret
GOOGLE_REDIRECT_URI=${{RAILWAY_PUBLIC_DOMAIN}}/auth/google/callback

# Autres OAuth providers...
```

## 🔧 Étape 5 : Configuration du Build

1. Dans l'onglet **"Settings"** de votre service backend
2. Section **"Build"** :
   - **Root Directory** : Laisser vide ou mettre `/`
   - **Dockerfile Path** : `backend-area/Dockerfile.railway`
3. Section **"Deploy"** :
   - **Start Command** : Laissez vide (défini dans le Dockerfile)
   - **Healthcheck Path** : `/api/health` (si vous avez une route de health check)

## 🌐 Étape 6 : Configurer le Domaine Public

1. Allez dans l'onglet **"Settings"** de votre service
2. Section **"Networking"**
3. Cliquez sur **"Generate Domain"** pour obtenir un domaine Railway gratuit
4. Ou ajoutez votre propre domaine personnalisé

Vous obtiendrez une URL comme : `https://votre-app.up.railway.app`

## 🚀 Étape 7 : Déployer

1. Railway détectera automatiquement le `railway.json`
2. Le build démarre automatiquement
3. Surveillez les logs en cliquant sur **"View Logs"**

Le déploiement prend généralement 2-5 minutes.

## 📱 Étape 8 : Mettre à Jour les Applications Clientes

### Pour le Frontend (Nuxt)
Mettez à jour la variable d'environnement :
```bash
NUXT_PUBLIC_API_BASE=https://votre-backend.up.railway.app
```

### Pour le Mobile (Flutter)
Mettez à jour l'URL de base de l'API :
```dart
// lib/config/api_config.dart
const String API_BASE_URL = 'https://votre-backend.up.railway.app';
```

## 🔍 Vérification du Déploiement

Testez ces endpoints :
```bash
# Health check
curl https://votre-backend.up.railway.app/api/health

# Routes API
curl https://votre-backend.up.railway.app/api/users
```

## 📊 Surveillance et Logs

### Voir les Logs
Dans Railway, cliquez sur votre service puis sur **"View Logs"**

### Métriques
Railway affiche automatiquement :
- CPU usage
- Memory usage
- Network traffic
- Deployment history

## 🔄 Mises à Jour Automatiques

Railway redéploie automatiquement à chaque push sur votre branche principale :
```bash
git add .
git commit -m "Update backend"
git push origin masters
```

## 💰 Coûts et Limites

### Plan Gratuit (Hobby)
- $5 de crédit gratuit/mois
- Suffisant pour un backend léger
- Pas de carte bancaire requise

### Plan Payant (Developer)
- $20/mois
- Déploiements illimités
- Meilleure performance

## 🐛 Dépannage

### Le build échoue
```bash
# Vérifiez les logs de build
# Assurez-vous que composer.json est valide
# Vérifiez que toutes les dépendances sont installables
```

### L'application ne démarre pas
```bash
# Vérifiez les variables d'environnement
# Assurez-vous que DB_HOST pointe vers le service MySQL
# Vérifiez les logs de démarrage
```

### Erreur de connexion à la base de données
```bash
# Vérifiez que le service MySQL est démarré
# Vérifiez les variables DB_* dans les settings
# Assurez-vous d'utiliser les références : ${{MySQL.MYSQL_HOST}}
```

### Port issues
Railway assigne automatiquement la variable `$PORT`. Le Dockerfile est configuré pour l'utiliser.

## 🔐 Sécurité

1. **Ne commitez JAMAIS le fichier .env**
2. Utilisez des **secrets forts** pour APP_KEY
3. Configurez **APP_DEBUG=false** en production
4. Activez **HTTPS** (automatique sur Railway)
5. Limitez les **CORS** dans `config/cors.php`

## 📝 Commandes Utiles

### Exécuter des commandes Artisan
Depuis le dashboard Railway, allez dans **"View Logs"** puis utilisez le terminal :
```bash
php artisan migrate
php artisan db:seed
php artisan cache:clear
php artisan config:clear
```

### Redémarrer l'application
Dans Railway, cliquez sur **"⋯"** puis **"Restart"**

## 🎉 C'est Fait !

Votre backend est maintenant déployé et accessible 24/7 !

URL du backend : `https://votre-backend.up.railway.app`

## 📚 Ressources

- Documentation Railway : https://docs.railway.app
- Railway Discord : https://discord.gg/railway
- Laravel Deployment : https://laravel.com/docs/deployment

## 🆘 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs dans Railway
2. Consultez la documentation Railway
3. Rejoignez le Discord Railway pour de l'aide
