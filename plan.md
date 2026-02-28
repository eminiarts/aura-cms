# Aura CMS – Analyse & Verbesserungsplan

> Erstellt: 2026-02-28 | Branch: `develop`
> Repo: Laravel TALL-Stack CMS Package | Namespace: `Aura\Base`

---

## 1. Code-Qualität

### 🔴 Kritisch

- [ ] **PluginsPage: Hardcoded Pfade & Sicherheitsrisiko** – `src/Livewire/PluginsPage.php` enthält hardcoded `/opt/homebrew/bin/php /usr/local/bin/composer` Pfade (Zeile ~131) und führt `composer update` über `exec()` aus. Sicherheitsrisiko und funktioniert nur auf dem Entwickler-Mac.
- [ ] **Team-Views sind Platzhalter** – `resources/views/teams/team-member-manager.blade.php`, `create-team-form.blade.php` und `delete-team-form.blade.php` enthalten nur Platzhalter-Text ("team member manager", "create team todo", "delete team form"), keine echte Implementierung.
- [ ] **Commented-out Gate logic** – `AuraServiceProvider.php` Zeile ~95: `Gate::before()` ist leer mit auskommentiertem `isSuperAdmin()` Check. Entweder implementieren oder entfernen.

### 🟡 Wichtig

- [ ] **Debug-Statements entfernen** – Auskommentierte `dump()`, `ray()` Aufrufe in:
  - `src/Traits/SaveFields.php:116`
  - `src/Traits/InputFieldsHelpers.php:158`
  - `src/Livewire/Modals.php:61`
  - `src/Livewire/Table/Traits/Search.php:15`
- [ ] **TODO im Code** – `src/Fields/Repeater.php:16`: `// TODO: $showChildrenOnIndex should be applied to children` – Unfertig.
- [ ] **Leere Views** – `resources/views/navigation/after.blade.php`, `components/fields/view-hidden.blade.php`, `components/fields/hidden.blade.php` sind 0 Bytes. Intentional oder vergessen?
- [ ] **Kleine/fast leere Views** – `resources/views/aura/icons/mp3.blade.php` (<20 Bytes) – prüfen ob vollständig.

### 🟢 Nice-to-have

- [ ] **AuraModelConfig.php** hat 77 Funktionen – Refactoring in kleinere Traits/Concerns erwägen
- [ ] **Livewire Component-Map Duplikation** – `AuraServiceProvider::bootLivewireComponents()` registriert jeden Component 2-3x (aura::, dot-notation, etc.). Könnte mit einer Loop vereinfacht werden.

---

## 2. Tests

### 🔴 Kritisch

- [ ] **Massive Test-Coverage-Lücken bei Fields** – 43 Field-Typen existieren, aber nur ~15 haben dedizierte Tests. **Fehlende Tests für:**
  - `BelongsTo`, `BelongsToMany`, `HasOne` (Relation Fields!)
  - `Code`, `Color`, `Datetime`, `Time`
  - `Embed`, `File`, `Image`
  - `Group`, `Repeater` (komplexe verschachtelte Fields)
  - `Hidden`, `Heading`, `HorizontalLine`, `ID`
  - `Json`, `LivewireComponent`
  - `Panel`, `Tab`, `Tabs` (Layout Fields)
  - `Permissions`, `Roles`
  - `Phone`, `Select`, `Status`, `Textarea`, `View`, `ViewValue`, `Wysiwyg`

### 🟡 Wichtig

- [ ] **Keine Tests für Widgets** – Nur `SparklineTest` und `ValueWidgetTest`. Fehlend: `Bar`, `Donut`, `Pie`, `SparklineArea`, `SparklineBar`, `Widgets` (Container).
- [ ] **Keine Tests für MediaManager/MediaUploader** – MediaManager Livewire-Component ungetestet.
- [ ] **Keine Tests für GlobalSearch, BookmarkPage, Notifications, SlideOver, Modal** – Livewire-Components ohne Tests.
- [ ] **Keine API-Tests** – `FieldsController` hat keine Tests.

### 🟢 Nice-to-have

- [ ] **PHPStan Baseline ist leer** – `phpstan-baseline.neon` ist 0 Bytes. PHPStan Analyse ausführen und Baseline erstellen.
- [ ] **Dusk/Browser-Tests** – `laravel/dusk` ist Dev-Dependency, aber keine Dusk-Tests vorhanden.

---

## 3. Dokumentation

### 🟡 Wichtig

- [ ] **README.md** – Nur 107 Zeilen. Braucht: Quickstart, Screenshots, Feature-Liste, Badges.
- [ ] **Docs-Verzeichnis unverlinkt** – 34 Markdown-Dateien in `docs/`, aber kein Hosting (keine VitePress/Docusaurus Config).
- [ ] **todo.md ist Doku-Plan** – Die `todo.md` (12KB) ist kein Task-Tracker, sondern ein Doku-Verbesserungsplan. Umbenennen/verschieben.
- [ ] **CHANGELOG.md** – Nur bis Version 2.x. Aktuelle Version unklar.

### 🟢 Nice-to-have

- [ ] **Inline-Docs** – Viele PHP-Klassen ohne DocBlocks. Besonders `Resource.php` (395 Zeilen, 28 Methoden) und Traits.
- [ ] **AI-Dateien im Root** – `CLAUDE.md`, `CLAUDE_backup.md`, `AGENTS.md` im Repo-Root. In `.ai/` verschieben.

---

## 4. Features: Definiert vs. Implementiert

### 🔴 Kritisch

- [ ] **Teams-Feature unvollständig** – Routes, Migrations, Policies existieren, aber die 3 Team-Views sind nur Platzhalter. Team-Member-Manager, Create-Team, Delete-Team nicht implementiert.
- [ ] **Plugins-System fragil** – `PluginsPage` läuft `composer require` als Shell-Befehl. Kein Sandbox, kein Rollback, hardcoded Pfade.

### 🟡 Wichtig

- [ ] **API unvollständig** – In `docs/api-reference.md` dokumentiert, aber nur ein Endpunkt existiert: `POST /api/fields/values`. Keine REST-API für Resources.
- [ ] **Flows nicht implementiert** – `docs/flows.md` existiert, aber kein `src/Flows/` Verzeichnis.
- [ ] **Notifications minimal** – `Livewire/Notifications.php` existiert, DB-Tabelle wird erstellt, aber keine Notification-Klassen.

---

## 5. UI/Views

### 🟡 Wichtig

- [ ] **Keine Übersetzungsdateien** – `__()` wird in Views verwendet, aber kein `resources/lang/` Verzeichnis. Keine i18n-Support.
- [ ] **Team-Views sind Platzhalter** (siehe Code-Qualität)
- [ ] **7 fast-leere Blade-Files** – Team-Views und mp3-Icon unfertig.

### 🟢 Nice-to-have

- [ ] **301 Blade-Views** – Keine automatisierten View-Render-Tests.
- [ ] **Stubs prüfen** – `stubs/` Templates auf Aktualität prüfen.

---

## 6. Migrations/DB

### 🟡 Wichtig

- [ ] **Gefährliches `dropIfExists('users')`** – Migration-Stub löscht bestehende Users-Tabelle in `up()`.
- [ ] **Redundante Tabellen** – `password_resets` UND `password_reset_tokens` werden beide erstellt. Laravel 10+ nutzt nur letztere.
- [ ] **Keine FK-Constraints** – `posts.user_id`, `posts.parent_id`, `posts.team_id` haben nur Indexes, keine Foreign Keys.
- [ ] **Sessions-Tabelle inkonsistent** – Wird in `down()` gedroppt aber nie in `up()` erstellt.

### 🟢 Nice-to-have

- [ ] **MySQL-spezifischer Index** – `CREATE INDEX ... value(255)` funktioniert nur auf MySQL, nicht SQLite/PostgreSQL.

---

## 7. Package-Qualität

### 🟡 Wichtig

- [ ] **`minimum-stability: beta`** – Sollte `stable` sein für Production.
- [ ] **Service Provider Reihenfolge** – `Lab404\Impersonate\ImpersonateServiceProvider` wird vor eigenen Providern geladen.
- [ ] **`intervention/image` nur in require-dev** – Wird aber für `GenerateImageThumbnail` Job in Production gebraucht. Nach `require` verschieben.

### 🟢 Nice-to-have

- [ ] **GitHub Actions prüfen** – CI/CD Workflows auf Korrektheit prüfen.
- [ ] **Laravel 10/11/12 Kompatibilität** – Tests gegen alle Versionen laufen lassen.
- [ ] **Publishing prüfen** – Alle publishbaren Assets korrekt registriert?

---

## Zusammenfassung

| Priorität | Anzahl |
|-----------|--------|
| 🔴 Kritisch | 5 |
| 🟡 Wichtig | 16 |
| 🟢 Nice-to-have | 12 |

### Top 5 nächste Schritte

1. **Team-Views implementieren** – 3 Platzhalter-Views sind der offensichtlichste unfertige Teil
2. **Field-Tests schreiben** – 28+ Field-Typen ohne Tests, besonders Relations (BelongsTo, HasMany, etc.)
3. **PluginsPage fixen** – Hardcoded Pfade entfernen, Security-Check einbauen
4. **Übersetzungsdateien erstellen** – `resources/lang/en/` mit allen Strings
5. **Migration-Stub bereinigen** – `dropIfExists('users')` entfernen, redundante Tabellen fixen
