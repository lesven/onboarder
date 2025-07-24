#!/bin/sh

# Warten bis die Datenbank verfügbar ist
echo "Warte auf MariaDB..."
while ! nc -z db 3306; do
  sleep 1
done
echo "MariaDB ist verfügbar!"

# Symfony-Projekt erstellen (falls noch nicht vorhanden)
if [ ! -f "composer.json" ]; then
    echo "Erstelle neues Symfony-Projekt..."
    composer create-project symfony/skeleton:"7.3.*" /tmp/symfony_project --no-interaction
    
    # Projekt-Dateien ins Working Directory kopieren
    cp -r /tmp/symfony_project/* .
    cp -r /tmp/symfony_project/.env . 2>/dev/null || true
    cp -r /tmp/symfony_project/.gitignore . 2>/dev/null || true
    
    # Zusätzliche Symfony-Pakete installieren
    composer require webapp --no-interaction
    composer require doctrine/doctrine-bundle --no-interaction
    composer require doctrine/orm --no-interaction
    composer require doctrine/doctrine-migrations-bundle --no-interaction
    composer require symfony/form --no-interaction
    composer require symfony/validator --no-interaction
    composer require twig/twig --no-interaction
    composer require symfony/security-bundle --no-interaction
    composer require symfony/mailer --no-interaction
    composer require --dev symfony/maker-bundle --no-interaction
    
    # .env Datei für Datenbank konfigurieren
    echo "" >> .env
    echo "# Database Configuration" >> .env
    echo "DATABASE_URL=\"mysql://onboarder:password@db:3306/onboarder\"" >> .env
fi

# Composer-Abhängigkeiten installieren
if [ -f "composer.json" ]; then
    composer install --optimize-autoloader --no-interaction
fi

# Datenbank-Setup
if [ -f "bin/console" ]; then
    echo "Erstelle Datenbank..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction
    
    # Migrationen ausführen falls vorhanden
    if [ -d "migrations" ] && [ "$(ls -A migrations)" ]; then
        php bin/console doctrine:migrations:migrate --no-interaction
    fi
fi

# Symfony-Server starten
echo "Starte PHP Built-in Server auf Port 8000..."
php -S 0.0.0.0:8000 -t public/
