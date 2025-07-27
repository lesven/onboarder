# API Task E-Mail-Adress Problem - Lösung

## Problem

Beim Verwenden von E-Mail-Adressen in API-Tasks (curl-Kommandos) wurden diese URL-codiert (`@` wurde zu `%40`), was zu Validierungsfehlern auf der API-Seite führte:

```
Email "fk.heising%40gmail.com" does not comply with addr-spec of RFC 2822.
```

## Lösung

Die `renderUrlEncodedTemplate`-Methode im `EmailService` wurde modifiziert, um E-Mail-Adressen **nicht** automatisch URL-zu-codieren.

### Verfügbare Template-Variablen

#### Normale E-Mail-Variablen (nicht URL-codiert)
- `{{managerEmail}}` - E-Mail des Managers (z.B. `manager@example.com`)
- `{{buddyEmail}}` - E-Mail des Buddys (z.B. `buddy@test.org`)

#### URL-codierte E-Mail-Variablen (für spezielle Fälle)
- `{{managerEmailEncoded}}` - URL-codierte Manager-E-Mail (z.B. `manager%40example.com`)
- `{{buddyEmailEncoded}}` - URL-codierte Buddy-E-Mail (z.B. `buddy%40test.org`)

### Beispiel-Verwendung

#### ✅ Korrekt für normale API-Calls
```bash
curl -X POST "https://api.example.com/user" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "{{firstName}} {{lastName}}",
    "manager_email": "{{managerEmail}}",
    "buddy_email": "{{buddyEmail}}"
  }'
```

**Resultat:**
```bash
curl -X POST "https://api.example.com/user" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Max%20M%C3%BCller",
    "manager_email": "manager@example.com",
    "buddy_email": "buddy@test.org"
  }'
```

#### ✅ Korrekt für URL-Parameter
```bash
curl "https://api.example.com/user?email={{managerEmailEncoded}}&name={{firstName}}"
```

**Resultat:**
```bash
curl "https://api.example.com/user?email=manager%40example.com&name=Max%20M%C3%BCller"
```

## Technische Details

### Code-Änderung in `EmailService::renderUrlEncodedTemplate()`

```php
$placeholders = [
    // Andere Felder werden URL-codiert
    '{{firstName}}'    => rawurlencode($onboarding?->getFirstName() ?? ''),
    '{{lastName}}'     => rawurlencode($onboarding?->getLastName() ?? ''),
    
    // E-Mail-Adressen werden NICHT URL-codiert
    '{{managerEmail}}'      => $onboarding?->getManagerEmail() ?? '',
    '{{buddyEmail}}'        => $onboarding?->getBuddyEmail() ?? '',
    
    // URL-codierte Varianten für spezielle Fälle
    '{{managerEmailEncoded}}'=> rawurlencode($onboarding?->getManagerEmail() ?? ''),
    '{{buddyEmailEncoded}}' => rawurlencode($onboarding?->getBuddyEmail() ?? ''),
];
```

### Tests

Es wurden umfassende Tests hinzugefügt, die sicherstellen:
- ✅ E-Mail-Adressen werden nicht URL-codiert
- ✅ Namen und andere Felder werden weiterhin URL-codiert  
- ✅ Spezielle `*Encoded`-Varianten funktionieren korrekt
- ✅ Bestehende Funktionalität bleibt unverändert

## Migration

### Bestehende API-Tasks prüfen
Wenn Sie bereits API-Tasks verwenden, prüfen Sie diese:

1. **Funktionieren weiterhin:** Tasks die E-Mail-Adressen in JSON-Body verwenden
2. **Könnten Anpassung brauchen:** Tasks die E-Mail-Adressen in URL-Parametern verwenden

### Empfohlene Aktion
- Für URL-Parameter: Wechseln Sie zu `{{managerEmailEncoded}}` und `{{buddyEmailEncoded}}`
- Für JSON/Form-Data: Verwenden Sie weiterhin `{{managerEmail}}` und `{{buddyEmail}}`

Diese Lösung ist rückwärtskompatibel und behebt das ursprüngliche Problem mit RFC 2822-konformen E-Mail-Adressen in API-Calls.
