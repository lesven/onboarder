#!/bin/bash

# Dieses Skript führt die Initial-Setup-Schritte für das Onboarding-System aus
# Ausführung: docker-compose exec app /var/www/html/docker/setup-entities.sh

echo "=== Onboarding-System Setup ==="

# Prüfe ob bin/console existiert
if [ ! -f "bin/console" ]; then
    echo "FEHLER: bin/console nicht gefunden. Stelle sicher, dass Composer install ausgeführt wurde."
    exit 1
fi

echo "1. Führe ausstehende Migrationen aus..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "2. Cache leeren..."
php bin/console cache:clear

echo "3. Prüfe Datenbank-Schema..."
php bin/console doctrine:schema:validate

echo "4. Lade Fixtures (Demo-Daten)..."
if php bin/console doctrine:fixtures:load --no-interaction 2>/dev/null; then
    echo "   ✓ Demo-Daten wurden geladen"
else
    echo "   ⚠ Keine Fixtures gefunden oder Fehler beim Laden"
fi

echo "5. Erstelle Admin-Benutzer (falls noch nicht vorhanden)..."
# Hier könnte ein Admin-User erstellt werden
echo "   ℹ Admin-User Setup noch nicht implementiert"

echo ""
echo "=== Setup abgeschlossen! ==="
echo "Das Onboarding-System ist bereit zur Nutzung."
echo ""
echo "Nächste Schritte:"
echo "- Öffne http://localhost:8000 im Browser"
echo "- Erstelle BaseTypes und OnboardingTypes im Admin-Bereich"
echo "- Füge Rollen und TaskBlocks hinzu"
echo ""
