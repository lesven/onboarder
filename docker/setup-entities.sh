#!/bin/bash

# Dieses Skript erstellt die Kern-Entitäten und die Grundstruktur des Onboarding-Systems
# Ausführung: docker-compose exec app /var/www/html/docker/setup-entities.sh

echo "=== Erstelle Onboarding-System Entitäten ==="

# Entitäten erstellen
echo "Erstelle BaseType Entität..."
php bin/console make:entity BaseType --no-interaction

echo "Erstelle OnboardingType Entität..."
php bin/console make:entity OnboardingType --no-interaction

echo "Erstelle TaskBlock Entität..."
php bin/console make:entity TaskBlock --no-interaction

echo "Erstelle Task Entität..."
php bin/console make:entity Task --no-interaction

echo "Erstelle Role Entität..."
php bin/console make:entity Role --no-interaction

echo "Erstelle Onboarding Entität..."
php bin/console make:entity Onboarding --no-interaction

echo "Erstelle User Entität für Authentifizierung..."
php bin/console make:entity User --no-interaction

echo "=== Erstelle Controller ==="
php bin/console make:controller OnboardingController --no-interaction
php bin/console make:controller AdminController --no-interaction
php bin/console make:controller DashboardController --no-interaction

echo "=== Erstelle Sicherheitssystem ==="
php bin/console make:user User --no-interaction

echo "=== Erstelle Migration ==="
php bin/console make:migration --no-interaction

echo "=== Führe Migration aus ==="
php bin/console doctrine:migrations:migrate --no-interaction

echo "=== Setup abgeschlossen! ==="
echo "Sie können jetzt die Entitäten in src/Entity/ anpassen und weitere Migrationen erstellen."
