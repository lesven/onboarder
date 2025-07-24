# 📋 User Stories – Priorisiert nach Phasen (Releases)

---

## 🟢 Phase 1 – MVP (Must Have)

### 🔐 Zugang & Setup

- **Als Nutzer** möchte ich mich mit Benutzername und Passwort einloggen können,  
  **damit unbefugte Personen keinen Zugriff auf die Anwendung haben.**

- **Als Administrator** möchte ich einen SMTP-Mailserver konfigurieren können (Host, Port, Benutzername, Passwort, Zertifikat ignorieren),  
  **damit Mails über unseren eigenen Server verschickt werden können.**

---

### 🧩 Stammdaten

- **Als Nutzer** möchte ich beim Anlegen eines Onboardings Vorname, Name, Eintrittsdatum, Tätigkeit, Team, Führungskraft und Buddy angeben können,  
  **damit alle relevanten Basisdaten für den Onboarding-Prozess vorhanden sind.**

- **Als Nutzer** möchte ich beim Anlegen eines Onboardings aus vorkonfigurierten Onboarding-Typen auswählen können,  
  **damit die passenden Aufgaben automatisch erzeugt werden.**

- **Als Nutzer** möchte ich den Onboarding-Typ nach dem Anlegen nicht mehr ändern können,  
  **damit die Konsistenz des zugrunde liegenden Prozesses gewahrt bleibt.**

---

### 🏗️ Onboarding-Typen & Aufgabenblöcke

- **Als Administrator** möchte ich beliebig viele Onboarding-Typen anlegen und verwalten können,  
  **damit ich unterschiedliche Rollen und Bereiche abbilden kann.**

- **Als Administrator** möchte ich Aufgabenblöcke (z. B. IT, HR) definieren und diesen Blöcken Aufgaben zuordnen,  
  **damit ich eine klare Struktur für die Aufgabenpflege habe.**

- **Als Administrator** möchte ich Aufgaben mit einem Titel, Fälligkeit, Zuständigkeit und Mailoptionen versehen können,  
  **damit ich den Ablauf des Onboardings präzise steuern kann.**

- **Als Administrator** möchte ich pro Aufgabe wählen können, ob das Fälligkeitsdatum fest oder relativ zum Eintrittsdatum ist,  
  **damit ich flexibel planen kann.**

---

### 📬 Mailversand

- **Als Administrator** möchte ich zu jeder Aufgabe ein individuelles HTML-Mailtemplate hochladen können,  
  **damit ich inhaltlich passende Mails gestalten kann.**

- **Als Administrator** möchte ich in Mailtemplates Variablen wie Name, Eintrittsdatum oder Führungskraft verwenden können,  
  **damit die Mails personalisiert sind.**

- **Als Aufgabenerlediger** möchte ich in der Mail einen Button oder Link erhalten, um die Aufgabe auch ohne Login als erledigt zu markieren,  
  **damit auch externe Personen Aufgaben abschließen können.**

---

### 📊 Aufgabenübersicht

- **Als Nutzer** möchte ich pro Mitarbeitendem eine Übersicht über alle Aufgaben samt Status, Fälligkeit und Zuständigkeit sehen,  
  **damit ich den Onboarding-Prozess im Blick habe.**

---

## 🟡 Phase 2 – Erweiterung (Should Have)

### 🛠️ Rollenverwaltung

- **Als Administrator** möchte ich Rollen mit einer hinterlegten E-Mail-Adresse definieren können,  
  **damit ich Zuständigkeiten zentral pflegen kann.**

- **Als Administrator** möchte ich Rollen bei Aufgaben als Empfänger auswählen können,  
  **damit ich nicht jedes Mal eine E-Mail-Adresse manuell eintragen muss.**

---

### 🧠 Aufgabenpflege & Ablaufsteuerung

- **Als Nutzer** möchte ich pro Onboarding einzelne Aufgaben löschen, ändern oder ergänzen können,  
  **damit ich auf individuelle Anforderungen reagieren kann.**

- **Als Administrator** möchte ich optional eine Erinnerungsmail mit eigenem Versandzeitpunkt festlegen können,  
  **um sicherzustellen, dass Aufgaben fristgerecht erledigt werden.**

- **Als Administrator** möchte ich Aufgaben mit Abhängigkeiten versehen können,  
  **damit bestimmte Aufgaben erst nach Abschluss anderer aktiviert werden.**

---

### 📊 Globale Übersicht & Monitoring

- **Als Nutzer** möchte ich eine globale Übersicht über alle Aufgaben aller Onboardings haben,  
  **damit ich erkennen kann, welche Abteilungen oder Rollen gerade stark belastet sind.**

- **Als Nutzer** möchte ich Aufgaben filtern oder gruppieren können (z. B. nach Rolle, Abteilung, Eintrittsdatum),  
  **damit ich effizient nach Problemen oder Zuständigkeiten suchen kann.**

- **Als Nutzer** möchte ich, dass überfällige Aufgaben visuell hervorgehoben werden,  
  **damit ich sofort erkenne, wo Handlungsbedarf besteht.**

---

## 🔵 Phase 3 – Komfort & Skalierung (Could Have)

### 🧱 Erweiterte Struktur

- **Als Administrator** möchte ich Onboarding-Typen auf Basis eines zentralen Basistyps erstellen können,  
  **damit ich wiederkehrende Aufgaben nicht mehrfach pflegen muss.**

- **Als Administrator** möchte ich, dass Änderungen am Basistyp automatisch in alle darauf basierenden Typen übernommen werden,  
  **damit alle Typen stets aktuell und konsistent bleiben.**

---

### 📊 Erweiterte Ansicht & Usability

- **Als Nutzer** möchte ich Aufgaben nach Abteilungen oder Zuständigen gruppieren können,  
  **um die Übersichtlichkeit bei vielen Aufgaben zu erhöhen.**

- **Als Nutzer** möchte ich Onboardings durchsuchen können (z. B. nach Namen oder Eintrittsdatum),  
  **damit ich einzelne Fälle schnell finde.**

---

## 🟣 Nicht vorgesehen (Out of Scope)

- Kommentare oder Notizen an Aufgaben → **nicht erforderlich**
- Protokoll, wer wann eine Aufgabe erledigt hat → **nicht erforderlich**
- Datenexport (Excel, PDF) → **nicht erforderlich**
- Mobiloptimierung / Responsive Design → **nicht erforderlich**
- Benutzerrollen mit unterschiedlichen Rechten → **nicht erforderlich**
