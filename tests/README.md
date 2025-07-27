# Tests für Onboarder

## Übersicht

Das Projekt enthält PHPUnit Tests für verschiedene Komponenten:

- **EncryptSmtpPasswordsCommandTest**: Tests für das Command zur SMTP-Passwort-Verschlüsselung
- **PasswordEncryptionServiceTest**: Tests für den Verschlüsselungsservice
- **EmailServiceTest**: Tests für den E-Mail-Service
- **RoleValidationTest**: Tests für die Rollen-Validierung

## Test-Kommandos

### Grundlegende Test-Befehle

```bash
# Alle Tests ausführen
make test

# Tests mit detaillierter Ausgabe
make test-verbose

# Spezifische Tests ausführen
make test-filter FILTER=EncryptSmtpPasswordsCommandTest

# Tests mit Code Coverage
make test-coverage
```

### Quality Assurance

```bash
# Code-Quality-Checks (CS Fixer, Twig/YAML Linting)
make check

# Alle Checks und Tests zusammen
make check-all
```

### Erweiterte Test-Features

```bash
# Tests automatisch bei Dateiänderungen ausführen (benötigt fswatch)
make test-watch
```

## Test-Struktur

### Command Tests

Die `EncryptSmtpPasswordsCommandTest`-Klasse testet das `EncryptSmtpPasswordsCommand`:

- **Keine Passwörter**: Behandlung wenn keine SMTP-Passwörter vorhanden sind
- **Klartext-Passwörter**: Verschlüsselung von unverschlüsselten Passwörtern  
- **Bereits verschlüsselte Passwörter**: Überspringen bereits verschlüsselter Passwörter
- **Gemischte Passwörter**: Korrekte Behandlung von gemischten Passwort-Zuständen
- **Command-Metadaten**: Prüfung von Name und Beschreibung

### Service Tests

Die Service-Tests prüfen die Kernfunktionalität der verschiedenen Services:

- **Verschlüsselung/Entschlüsselung**: Korrekte Passwort-Verschlüsselung
- **Rückwärtskompatibilität**: Behandlung von Klartext-Passwörtern
- **Edge Cases**: Null/leere Werte, doppelte Verschlüsselung

## Konfiguration

Die Tests verwenden die `phpunit.dist.xml` Konfiguration und den `tests/bootstrap.php` für das Setup.

### Test-Umgebung

- **APP_ENV**: `test`
- **Datenbank**: Test-Datenbank (isoliert von Produktionsdaten)
- **Verschlüsselung**: Test-spezifische Schlüssel

## Best Practices

1. **Isolation**: Jeder Test ist unabhängig und verwendet Mocks für externe Abhängigkeiten
2. **Aussagekräftige Namen**: Test-Methoden beschreiben klar was getestet wird
3. **Arrange-Act-Assert**: Tests folgen dem AAA-Pattern
4. **Mocking**: Externe Services werden gemockt für deterministische Tests
5. **Edge Cases**: Tests decken sowohl Happy Path als auch Fehlerfälle ab

## CI/CD Integration

Die Tests sind in die Makefile integriert und können in CI/CD-Pipelines verwendet werden:

```bash
# Für CI/CD-Umgebungen
make ci-install  # Installation ohne interaktive Eingaben
make check-all   # Alle Checks und Tests
```
