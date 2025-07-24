#!/bin/bash

echo "=== Onboarding System Setup ==="

# Migration erstellen und ausführen
echo "Erstelle und führe Migration aus..."
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction

# Admin-User erstellen
echo "Erstelle Admin-Benutzer..."
php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures nicht verfügbar"

echo "=== Setup abgeschlossen! ==="
echo ""
echo "🚀 Öffnen Sie http://localhost:8000 um die Anwendung zu nutzen"
echo "🔧 phpMyAdmin: http://localhost:8080 (User: onboarder, Pass: password)"
echo ""
echo "Nächste Schritte:"
echo "1. Controller und Views erstellen"
echo "2. Formulare für CRUD-Operationen"
echo "3. E-Mail-Templates implementieren"
