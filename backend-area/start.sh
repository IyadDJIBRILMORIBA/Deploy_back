#!/bin/sh

set -e

echo "🚀 Démarrage de l'application Laravel sur Railway..."

# Créer le fichier .env depuis les variables d'environnement Railway
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env
fi

# Générer la clé d'application si elle n'existe pas
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Remplacer le PORT dans nginx.conf si la variable existe
if [ -n "$PORT" ]; then
    echo "🔧 Configuration du port $PORT pour Nginx..."
    sed "s/\${PORT:-8000}/$PORT/g" /etc/nginx/nginx.conf > /tmp/nginx.conf
    mv /tmp/nginx.conf /etc/nginx/nginx.conf
else
    echo "⚠️  Variable PORT non définie, utilisation du port 8000 par défaut"
    sed "s/\${PORT:-8000}/8000/g" /etc/nginx/nginx.conf > /tmp/nginx.conf
    mv /tmp/nginx.conf /etc/nginx/nginx.conf
fi

# Exécuter les migrations
echo "🗄️  Exécution des migrations..."
php artisan migrate --force

# Mettre en cache la configuration
echo "⚙️  Mise en cache de la configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan optimize

echo "✅ Configuration terminée!"
echo "🌐 Démarrage des services..."

# Démarrer supervisord qui gère PHP-FPM, Nginx et le scheduler
exec /usr/bin/supervisord -c /etc/supervisord.conf
