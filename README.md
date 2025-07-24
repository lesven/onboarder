# Onboarder MVP Setup

## 🚀 Schnellstart

### 1. Docker Container starten
```bash
docker-compose up -d
```

### 2. Symfony-Projekt wird automatisch erstellt
Der Container erstellt automatisch ein neues Symfony 7.3 Projekt mit allen benötigten Abhängigkeiten.

### 3. Entitäten und Grundstruktur erstellen
Nach dem ersten Start führen Sie diesen Befehl aus:
```bash
docker-compose exec app /var/www/html/docker/setup-entities.sh
```

### 4. Anwendung aufrufen
- **Hauptanwendung**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080 (User: onboarder, Password: password)

## 📝 Entwicklung

### Container-Befehle ausführen
```bash
# In den Container wechseln
docker-compose exec app sh

# Symfony-Befehle ausführen
docker-compose exec app php bin/console [befehl]

# Neue Entität erstellen
docker-compose exec app php bin/console make:entity

# Migration erstellen
docker-compose exec app php bin/console make:migration

# Migration ausführen
docker-compose exec app php bin/console doctrine:migrations:migrate

# Cache leeren
docker-compose exec app php bin/console cache:clear
```

### Logs anzeigen
```bash
# Alle Logs
docker-compose logs -f

# Nur App-Logs
docker-compose logs -f app
```

## 📁 MVP Struktur

Nach dem Setup erhalten Sie folgende Entitäten:
- `BaseType` - Basis-Templates für OnboardingTypes
- `OnboardingType` - Konfigurierte Onboarding-Vorlagen
- `TaskBlock` - Aufgaben-Gruppierungen (IT, HR, etc.)
- `Task` - Einzelne Onboarding-Aufgaben
- `Role` - Rollen mit E-Mail-Adressen
- `Onboarding` - Mitarbeiter-Onboarding-Instanzen
- `User` - System-Benutzer

## 🛠️ Nächste Schritte

1. Entitäten in `src/Entity/` anpassen und Beziehungen definieren
2. Forms für CRUD-Operationen erstellen
3. Templates für die Benutzeroberfläche entwickeln
4. E-Mail-System implementieren

## 📋 Code-Konventionen

- **Kommentare**: Deutsch
- **Methoden/Klassen**: Englisch
- **Clean Code**: Richtlinien befolgen
- **Infrastructure**: Alles läuft in Docker