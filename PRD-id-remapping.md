# PRD: Identifier Remapping

**Dokument-Version:** 1.2  
**Erstellt am:** 24. März 2026  
**Zuletzt aktualisiert:** 24. März 2026  
**Status:** Final  
**Feature-Bereich:** Anonymisierungs-Engine  
**Priorität:** P0 – Launch-Blocker für Enterprise-Kunden

---

## 1. Executive Summary

Numerische und UUID-basierte Primary Keys stellen ein kritisches Re-Identifikationsrisiko dar: Wer weiß, dass `user_id = 1` in Produktion dem CEO gehört, kann diesen Datensatz auch nach Anonymisierung aller Klarnamen eindeutig identifizieren. Clonio muss daher alle Identifier konsequent remappen – und dabei die referenzielle Integrität über alle Tabellen hinweg erhalten.

**Kernaussage:** `user_id = 1` in Prod wird zu `user_id = 84291` in der anonymisierten DB. Alle `posts.user_id = 1` werden automatisch zu `posts.user_id = 84291`. Die Verknüpfung bleibt korrekt, der Rückschluss auf die Person ist unmöglich.

---

## 2. Problemdefinition

### 2.1 Das Re-Identifikationsrisiko durch Identifier

Aktuelle Anonymisierungstools – inklusive Clonio MVP – ersetzen Klartextfelder (Name, E-Mail, Telefon), lassen aber Primary Keys und Foreign Keys unverändert. Das reicht für viele Angriffsvektoren nicht aus:

- **Bekannte Entities:** „CEO hat immer `user_id = 1`" – nach Anonymisierung trotzdem identifizierbar
- **Kreuzreferenzierung:** Kombiniert mit externen Datensätzen (Anzahl Orders, Registrierungsdatum) können kleine IDs hochrangige Nutzer verraten
- **Sequentielle IDs als Metadaten:** Die Reihenfolge der Registrierung ist selbst ein personenbezogenes Datum
- **UUID-Wiederverwendung:** Gleiche UUID in Test und Prod erlaubt direkten Abgleich mit Production-Logs

**Konsequenz:** Enterprise-Kunden aus FinTech und HealthTech können Clonio ohne dieses Feature nicht einsetzen. Es ist ein Adoption-Blocker.

### 2.2 Das technische Kernproblem

Ein einfaches „ersetze alle IDs durch Zufallswerte" reicht nicht. Das Datenmodell hat Abhängigkeiten:

```
users.id = 1  ←──────────────┐
                              │ (Foreign Key)
posts.user_id = 1  ───────────┘
```

Nach dem Remapping muss gelten:

```
users.id = 84291  ←───────────┐
                               │ (Referenz bleibt erhalten)
posts.user_id = 84291  ────────┘
```

Der neue Wert muss konsistent in **allen** referenzierenden Tabellen erscheinen – auch über mehrere Ebenen hinweg, auch bei selbst-referenziellen Strukturen.

---

## 3. Regulatorischer Kontext & Compliance-Anforderungen

Das Identifier-Remapping ist nicht nur ein technisches Feature – es ist eine direkte Antwort auf konkrete regulatorische Anforderungen, mit denen Clonios Zielkunden konfrontiert sind. Dieser Abschnitt fasst die relevanten Vorschriften zusammen.

> **Hinweis:** Diese Übersicht dient der technischen Orientierung. Sie ersetzt keine Rechtsberatung. Die verlinkte Compliance-Referenzdatei (`clonio-compliance-reference.md`) enthält die vollständigen Details für jeden Standard.

### 3.1 DSGVO / GDPR (EU) — Primärer Rahmen

**Aktuelle Grundlage:** DSGVO in Kraft seit 2018; EDPB Guidelines 01/2025 zur Pseudonymisierung verabschiedet am 16. Januar 2025 (Konsultationsphase abgeschlossen Februar 2025, finale Version ausstehend).

| Artikel | Anforderung | Clonio-Relevanz |
|---|---|---|
| **Art. 5(1)(a)** | Rechtmäßigkeit, Zweckbindung – ungemaskierte Produktionsdaten im Test sind ein Verstoß | ID-Remapping schließt diese Lücke |
| **Art. 5(1)(c)** | Datensparsamkeit – nur notwendige Daten verarbeiten | Partial-Transfer (First/Last X Rows) unterstützt dies |
| **Art. 25** | Privacy by Design – Schutzmaßnahmen müssen technisch eingebaut sein | Clonios Anonymisierungs-Engine ist der technische Baustein |
| **Art. 30** | ROPA – Nachweis wo Daten liegen und wie geschützt | Audit Trail + PDF-Report adressiert dies |

**Re-Identifikationsrisiken nach EDPB:** Die Guidelines 01/2025 definieren drei Angriffsvektoren, gegen die Pseudonymisierung schützen muss: *Singling out* (Einzelperson isolierbar), *Linkability* (Datensätze verknüpfbar), *Inference* (Rückschlüsse aus Mustern). Sequentielle Integer-IDs adressieren alle drei Angriffsvektoren nicht – das Identifier-Remapping schließt diese Lücke.

### 3.2 PCI DSS v4.0 — Für Kunden mit Zahlungsdaten

**Requirement 6.5.5:** Live-PANs (Primary Account Numbers) dürfen nicht in Pre-Production-Umgebungen genutzt werden – dies ist ein **hartes Verbot**, keine Empfehlung.

**Business Case für Clonios Kunden:** Wenn alle Cardholder-Daten vollständig aus einer Umgebung entfernt werden, kann diese aus dem PCI-DSS-Scope herausgenommen werden – das reduziert Audit-Aufwand und Kosten erheblich. Clonio ermöglicht genau das.

### 3.3 HIPAA (USA) — Für HealthTech-Kunden

HIPAA verlangt De-Identifikation nach **Safe Harbor** (18 spezifische Identifier müssen entfernt werden, darunter alle IDs, Geburtsdaten außer dem Jahr, geografische Angaben kleiner als ein Bundesstaat) oder dem **Expert Determination Standard**. Sequentielle IDs fallen explizit unter die 18 zu entfernenden Identifier-Typen.

### 3.4 SOC 2 Type II — Für SaaS-Anbieter als Kunden

SOC 2 schreibt keine spezifischen Techniken vor, verlangt aber dokumentierte, nachvollziehbare Prozesse. Clonios Audit Trail und PDF-Report sind direkt verwertbar als SOC-2-Nachweise für den Umgang mit Produktionsdaten in Testumgebungen.

### 3.5 ISO/IEC 27001 & 29101

Das BfDI (deutsche Datenschutzbehörde) referenziert ISO/IEC 29101 (Privacy Architecture Framework) als technischen Standard für Pseudonymisierung. Clonios Ansatz – kein persistiertes Mapping, deterministisch reproduzierbare Werte, Cleanup-Garantie – ist mit diesen Anforderungen kompatibel.

---

### Ziele (In Scope)

- **Vollständiges Identifier-Remapping** für Integer- und UUID-Primary-Keys
- **Automatische FK-Propagation:** Alle Foreign Keys, die auf eine remappte PK zeigen, erhalten den neuen Wert
- **Selbst-referenzielle Tabellen** (z.B. `employees.manager_id → employees.id`) werden korrekt behandelt
- **UI-basierte Konfiguration** im bestehenden Config-Builder (kein Code nötig)
- **Zwei Remapping-Strategien:** Random Integer, neue UUID
- **Temporäres Mapping-Storage:** Dedizierte DB-Tabelle wird nach dem Run gelöscht
- **Audit Trail:** Remapping-Entscheidungen werden im Run-Log dokumentiert (ohne die Mapping-Tabelle selbst zu persistieren)

### Nicht-Ziele (Out of Scope für dieses Feature)

- Persistiertes Mapping über mehrere Runs hinweg (für inkrementelle Transfers – separates Feature)
- Composite Primary Keys (mehrspaltige PKs) – v2.0
- Remapping von nicht-ID-Feldern (z.B. `order_number`, `invoice_ref`) – separates Feature
- Cross-Database FK-Remapping (DB1.users → DB2.posts) – v2.0

---

## 4. User Stories

### Primary: Developer / DevOps

> **US-01:** Als Developer möchte ich, dass Clonio alle Primary Keys meiner User-Tabelle durch Zufallswerte ersetzt, damit niemand anhand der User-ID Rückschlüsse auf reale Personen ziehen kann.

> **US-02:** Als Developer möchte ich, dass alle Foreign Keys in abhängigen Tabellen automatisch aktualisiert werden, damit die Daten nach dem Transfer konsistent und nutzbar bleiben.

> **US-03:** Als Developer möchte ich das Identifier-Remapping über die bestehende Config-UI aktivieren können, ohne PHP-Code schreiben zu müssen.

### Secondary: Compliance Manager

> **US-04:** Als Compliance Manager möchte ich im Audit Trail sehen, dass das Identifier-Remapping für einen Run aktiv war, damit ich gegenüber Auditoren nachweisen kann, dass keine Rückführung möglich ist.

> **US-05:** Als Compliance Manager möchte ich sicher sein, dass die Mapping-Tabelle nach dem Run vollständig gelöscht wird und kein Mapping persistiert wird.

---

## 5. Funktionale Anforderungen

### 5.1 Konfiguration (UI)

Das Identifier-Remapping wird pro Transfer-Config im Config-Builder konfiguriert – als neuer Abschnitt **„Identifier Remapping"** in Step 3 (Options).

**UI-Elemente:**

| Element | Beschreibung |
|---|---|
| **Toggle: Identifier Remapping aktivieren** | Master-Switch. Default: `off` |
| **Tabellen-Liste** | Alle Tabellen aus dem Script werden aufgelistet |
| **Pro Tabelle: Primary Key Spalte** | Dropdown mit erkannten Spalten (auto-suggest: `id`, `uuid`) |
| **Pro Tabelle: Remapping-Strategie** | `Random Integer` oder `New UUID` |
| **Pro Tabelle: Foreign Keys** | Automatisch erkannt via Schema-Analyse; manuell ergänzbar |

**Beispiel-UI-State (Step 3 – Identifier Remapping):**

```
☑ Identifier Remapping aktivieren

Tabelle: users
  Primary Key:  [id ▼]
  Strategie:    [Random Integer ▼]   Range: [100000 – 9999999]
  Foreign Keys erkannt in: posts.user_id, orders.customer_id, addresses.user_id

Tabelle: products
  Primary Key:  [uuid ▼]
  Strategie:    [New UUID ▼]
  Foreign Keys erkannt in: order_items.product_uuid

[+ Weitere Tabelle manuell hinzufügen]
```

**FK-Review-Schritt (neu, nach OQ-4):** Beim ersten Speichern einer Config mit aktivem Remapping erscheint ein dedizierter Review-Dialog, der alle auto-erkannten FKs auflistet. Der Nutzer muss diese explizit bestätigen oder korrigieren, bevor der erste Run gestartet werden kann. Bei Folge-Runs entfällt der Review (außer das Schema hat sich geändert).

**Compliance-Hinweise direkt in der UI (neu):** Auf der Config-Erstellen-Seite werden kontextuelle Compliance-Hinweise eingeblendet – als aufklappbarer Infobereich neben den Konfigurationsoptionen, nicht als modaler Dialog. Die Hinweise sind nach Regulierungsgruppe gegliedert und zeigen, welche Anforderung durch die jeweilige Einstellung adressiert wird:

```
ℹ Compliance-Kontext  [▼ einblenden]

  DSGVO / GDPR (EU)
  → Art. 5(1)(a): Ungemaskierte Produktions-IDs im Test sind ein Verstoß.
    Identifier Remapping schließt diese Lücke.
  → EDPB Guidelines 01/2025 (Draft): Sequentielle IDs gelten als
    Re-Identifikationsrisiko (Singling out, Linkability).

  PCI DSS v4.0
  → Req. 6.5.5: Live-PANs verboten in Pre-Production.
    Tipp: Vollständige Maskierung + ID-Remapping entfernt Umgebung
    aus dem PCI-DSS-Scope → weniger Audit-Aufwand.

  HIPAA (USA)
  → Safe Harbor: Alle IDs zählen zu den 18 zu entfernenden Identifiern.
    ID-Remapping erfüllt diese Anforderung technisch.

  [→ Vollständige Compliance-Referenz öffnen]
```

Die Hinweise sind nicht zwingend (kein Blocker), aber persistent sichtbar solange der Nutzer Konfigurationsoptionen setzt, die eine Regulierung betreffen.

**Validierungen:**

- Strategie `New UUID` nur verfügbar wenn die Spalte als `VARCHAR(36)` oder `CHAR(36)` erkannt wird
- Warnung wenn FK erkannt wird, dessen Parent-Tabelle nicht im Remapping ist
- **Warnung wenn FK-Tabelle nicht im Transfer:** Expliziter Warning-Banner im UI mit Option „Trotzdem ausführen" – kein harter Fehler, aber die Konsequenz (inkonsistente FKs in nicht-transferierten Tabellen) wird klar kommuniziert
- Fehler wenn ein PK als FK einer anderen gereampten Tabelle fehlt (Konsistenzlücke)

### 5.2 Feldname-Mapping zur automatischen PII-Erkennung (intensiviert)

Clonio besitzt bereits ein Mapping von Spalten-Namen auf PII-Typen (z.B. `email` → Faker-E-Mail). Dieses Mapping wird für das Identifier-Remapping **signifikant erweitert**, um PK- und FK-Spalten automatisch zu erkennen und direkt den richtigen Regulierungs-Hinweis anzuzeigen.

**Erkennungs-Logik (erweitert):**

```
Spaltenname enthält:                   → Erkannt als:            → Hinweis:
─────────────────────────────────────────────────────────────────────────────
id, _id, uuid, guid                    → Primary/Foreign Key     → ID-Remapping empfohlen
ssn, social_security, national_id      → Direkt-Identifier       → DSGVO Art. 9 / HIPAA Safe Harbor
dob, birth_date, birthdate, geburtstag → Quasi-Identifier        → HIPAA Safe Harbor (Datum)
zip, plz, postal_code, city            → Geo-Identifier          → HIPAA Safe Harbor (Geo)
ip, ip_address                         → Netzwerk-Identifier     → DSGVO-relevant
card_number, pan, cc_number            → Zahlungsdaten           → PCI DSS 6.5.5
```

**UI-Verhalten:** Wenn eine Spalte einem bekannten Muster entspricht, erscheint neben dem Feld-Selector ein farblicher Badge:

```
[email]  🟡 PII – DSGVO-relevant       → Faker-Email empfohlen
[id]     🔴 Identifier – Remapping      → ID-Remapping empfohlen
[pan]    🔴 PCI DSS – Live-PAN-Verbot   → Masking zwingend
[dob]    🟡 HIPAA Safe Harbor           → Generalisierung empfohlen
```

Die bestehende Erkennungs-Liste (aktuell primär auf Faker-Mapping ausgerichtet) wird in einer dedizierten `pii-field-registry.php` Konfigurationsdatei gepflegt, die sowohl von der Anonymisierungs-Engine als auch vom UI-Hint-System genutzt wird.

### 5.3 Maskierungs-Auswahl mit Regulierungs-Gruppierung (neu)

Wenn ein Nutzer eine Maskierungs-Strategie für ein Feld auswählt, sind die Optionen nach Regulierungs-Compliance gruppiert statt flach aufgelistet:

```
Maskierungs-Strategie für: users.email
────────────────────────────────────────
✅ DSGVO-konform
   ○ Faker E-Mail (neue, realistische E-Mail-Adresse)
   ○ Hash (deterministisch, nicht rückführbar)

⚠ Pseudonymisiert (DSGVO: weiterhin personenbezogen)
   ○ Präfix + ID  (z.B. user_12345@example.com)

❌ Nicht anonymisiert
   ○ Originalwert behalten
```

Analog für ID-Felder:

```
Maskierungs-Strategie für: users.id (Primary Key)
────────────────────────────────────────────────────
✅ Vollständig anonymisiert
   ○ Random Integer (neuer Zufallswert ≠ Original)
   ○ New UUID (neue UUID ≠ Original)

⚠ Nur verschoben (schwache Anonymisierung)
   ○ Offset (+1.000.000) – erkennbar sequentiell

❌ Kein Remapping
   ○ Originalwert behalten – Re-Identifikationsrisiko
```

Die Gruppierung ist rein informativ (kein Zwang), macht aber die Compliance-Konsequenz jeder Entscheidung für den Nutzer sofort sichtbar.

- Strategie `New UUID` nur verfügbar wenn die Spalte als `VARCHAR(36)` oder `CHAR(36)` erkannt wird
- Warnung wenn FK erkannt wird, dessen Parent-Tabelle nicht im Remapping ist
- **Warnung wenn FK-Tabelle nicht im Transfer:** Expliziter Warning-Banner im UI mit Option „Trotzdem ausführen" – kein harter Fehler, aber die Konsequenz (inkonsistente FKs in nicht-transferierten Tabellen) wird klar kommuniziert
- Fehler wenn ein PK als FK einer anderen gereampten Tabelle fehlt (Konsistenzlücke)

### 5.4 Remapping-Strategien

#### Strategy A: Random Integer

- Generiert einen zufälligen Integer-Wert innerhalb des konfigurierbaren Bereichs
- **Einzige Bedingung:** Der neue Wert darf nicht identisch mit dem Original-Wert aus der Source-DB sein – es gibt keine weitere Einschränkung auf bestimmte Zahlenräume
- **Globaler Default-Range:** 100.000 – 9.999.999 (in globalen Transfer-Optionen einstellbar)
- **Pro-Tabelle-Überschreibung:** Optional im UI pro Tabelle überschreibbar
- **Kollisions-Handling:** Generierter Wert wird gegen alle bereits vergebenen neuen Werte im Mapping geprüft. Bei Kollision (identischer neuer Wert bereits vergeben): neuen Wert ziehen (max. 10 Versuche, dann Exception). Bei Gleichheit mit Original-Wert: immer neu ziehen.
- Beibehaltung des Datentyps: `BIGINT` bleibt `BIGINT`, `INT` bleibt `INT`

```
Prod:  user_id = 1    →  Dev: user_id = 847291   (≠ 1 ✓)
Prod:  user_id = 2    →  Dev: user_id = 193847   (≠ 2 ✓)
Prod:  user_id = 1000 →  Dev: user_id = 5502931  (≠ 1000 ✓)
```

#### Strategy B: New UUID

- Generiert eine neue, kryptografisch sichere UUID v4 für jeden Datensatz
- **Einzige Bedingung:** Die neue UUID darf nicht identisch mit der Original-UUID sein – statistisch nahezu ausgeschlossen, wird aber explizit geprüft
- Beibehaltung des Datentyps: `VARCHAR(36)` bleibt `VARCHAR(36)`

```
Prod:  uuid = "550e8400-e29b-41d4-a716-446655440000"
  →
Dev:   uuid = "7f3d9a12-bc45-4e78-9f01-23456789abcd"  (≠ Original ✓)
```

### 5.5 Pre-Processing Job: ID Mapping

Vor den eigentlichen Tabellen-Transfer-Jobs wird ein dedizierter **`BuildIdMappingJob`** ausgeführt.

**Ablauf:**

```
Phase 0: BuildIdMappingJob
  ↓
  1. Alle konfigurierten Tabellen lesen
  2. Pro Tabelle: dieselbe Row-Selektion wie der spätere ProcessTableJob verwenden
     – Full Transfer:  SELECT primary_key FROM table
     – First X Rows:   SELECT primary_key FROM table ORDER BY id ASC  LIMIT X
     – Last X Rows:    SELECT primary_key FROM table ORDER BY id DESC LIMIT X
  3. Pro ID: neuen Wert generieren (Random Int oder UUID)
  4. Mapping in temporärer Tabelle `_clonio_id_mapping_{run_id}` in Clonio App-DB speichern
  5. Job abgeschlossen → Transfer-Jobs starten

Phase 1–N: ProcessTableJob (wie bisher)
  ↓
  Für jede Row: mapping_table nachschlagen, PK und alle FKs ersetzen

Phase N+1: CleanupJob
  ↓
  DROP TABLE `_clonio_id_mapping_{run_id}`
  Log: "Mapping table deleted"
```

**Wichtig – Konsistenz zwischen Mapping und Transfer:** Der `BuildIdMappingJob` muss exakt dieselbe Row-Selektion verwenden wie der nachfolgende `ProcessTableJob`. Weicht die Selektion ab (z.B. durch zwischenzeitliche Inserts in der Source-DB), können FKs auf IDs zeigen, für die kein Mapping existiert. Das wird beim FK-Lookup als `[WARNING] unmapped_fk_value` im Run-Log protokolliert – der Run wird nicht abgebrochen, der FK-Wert bleibt in diesem Fall unverändert.

### 5.6 Mapping-Tabelle (temporär auf Clonio App-DB)

```sql
-- Auf der Clonio App-DB (nicht auf Source oder Target!)
CREATE TABLE _clonio_id_mapping_{run_id} (
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    table_name  VARCHAR(255) NOT NULL,
    column_name VARCHAR(255) NOT NULL,
    old_value   VARCHAR(255) NOT NULL,  -- immer als String gespeichert
    new_value   VARCHAR(255) NOT NULL,
    INDEX idx_lookup (table_name, column_name, old_value)
);
```

**Begründung Clonio App-DB:** Source und Target bleiben vollständig unberührt. Kein zusätzlicher Zugriff auf Kundendatenbanken außer dem eigentlichen Transfer. Die Mapping-Tabelle ist damit auch bei Netzwerkproblemen zur Target-DB noch erreichbar.

**Audit Trail:** Die Mapping-Tabelle selbst wird nicht exportiert. Im PDF-Report erscheint nur: *„Identifier Remapping: aktiv. Mapping-Tabelle nach Run gelöscht."*

**Cleanup:** Der `CleanupMappingJob` löscht die Tabelle aus der Clonio App-DB – garantiert auch bei Run-Fehlern über einen `finally`-Block.

### 5.7 Row-Processing mit Mapping

Im `ProcessTableJob` wird jede Row vor dem Insert durch den `IdRemappingService` verarbeitet:

```
Row aus Source:  { id: 1, user_id: 42, name: "..." }
                         ↓
IdRemappingService.applyMapping(row, tableConfig)
  → lookup: users.id=1      → new_value=847291
  → lookup: users.id=42     → new_value=193847  (via FK-Config: user_id→users.id)
                         ↓
Row für Target:  { id: 847291, user_id: 193847, name: "..." }
```

### 5.8 Selbst-referenzielle Tabellen

**Problem:** `employees.manager_id` verweist auf `employees.id`. Beim Verarbeiten einer Row kann der `manager_id`-Wert auf eine Row verweisen, die noch nicht verarbeitet wurde.

**Lösung:** Zweistufiges Processing für selbst-referenzielle Tabellen:

```
Schritt 1: INSERT mit manager_id = NULL (oder temporärem Wert)
Schritt 2: Nach vollständigem Insert aller Rows:
           UPDATE employees SET manager_id = new_mapped_value WHERE ...
```

**Konfiguration:** Selbst-referenzielle Spalten werden automatisch erkannt (wenn FK auf dieselbe Tabelle zeigt) und im UI mit einem Hinweis versehen:

```
⚠ Selbst-referenzielle FK erkannt: employees.manager_id → employees.id
  Verarbeitung erfolgt in zwei Phasen (INSERT + UPDATE).
```

### 5.9 Batch-Integration

Der `BuildIdMappingJob` wird als **erster Job im Batch** hinzugefügt, bevor die `ProcessTableJob`-Instanzen dispatcht werden:

```php
Bus::batch([
    new BuildIdMappingJob($config, $run),     // Phase 0
    new ProcessTableJob($config, $run, 'users'),   // Phase 1
    new ProcessTableJob($config, $run, 'posts'),   // Phase 2
    // ...
    new CleanupMappingJob($config, $run),     // Phase N+1
])
->name("Transfer Run #{$run->id}")
->allowFailures(false)  // Bei Mapping-Fehler: gesamter Run abbrechen
->dispatch();
```

**Wichtig:** `allowFailures(false)` für Runs mit aktivem Remapping – ein partieller Fehler würde inkonsistente Daten erzeugen.

---

## 6. Nicht-Funktionale Anforderungen

| Anforderung | Zielwert | Begründung |
|---|---|---|
| **Performance: Mapping-Build** | < 30 Sek. für 1 Mio. IDs | Akzeptabler Overhead vor dem eigentlichen Transfer |
| **Memory-Verbrauch** | Konstant (kein In-Memory-Map) | Mapping in DB, nicht im PHP-Heap |
| **Kollisionsrate** | < 0,001% | Bei Range 100k–9,9M und typischen Datensatzgrößen |
| **Cleanup-Garantie** | Mapping-Tabelle immer gelöscht | Auch bei Run-Failure (finally-Block) |
| **Rückwärtskompatibilität** | Bestehende Configs unberührt | Feature ist opt-in, Default: off |

---

## 7. Technische Architektur

### 7.1 Neue Services

| Service | Verantwortlichkeit |
|---|---|
| `IdRemappingService` | Zentraler Service: Mapping generieren, anwenden, löschen |
| `IdMappingRepository` | DB-Zugriff auf `_clonio_id_mapping_{run_id}` |
| `ForeignKeyDetector` | Erkennt FKs aus Schema (bestehender `DependencyResolver` wird erweitert) |
| `SelfReferentialDetector` | Erkennt selbst-referenzielle Spalten |

### 7.2 Neue Jobs

| Job | Timing | Beschreibung |
|---|---|---|
| `BuildIdMappingJob` | Phase 0, vor allen Transfer-Jobs | Liest alle PKs, generiert neue Werte, befüllt Mapping-Tabelle |
| `CleanupMappingJob` | Letzte Phase, nach allen Transfer-Jobs | Löscht `_clonio_id_mapping_{run_id}` |

### 7.3 Geänderte Jobs / Services

| Komponente | Änderung |
|---|---|
| `ProcessTableJob` | Nutzt `IdRemappingService.applyMapping()` wenn Remapping aktiv |
| `TransferService::startTransfer()` | Prepended `BuildIdMappingJob`, appended `CleanupMappingJob` wenn Feature aktiv |
| `SchemaReplicator` | Muss beim Schema-Kopieren den neuen Typ berücksichtigen (für UUID→UUID, bleibt gleich) |
| `DependencyResolver` | Gibt jetzt auch FK-Spalten-Namen zurück (bisher nur Tabellen-Namen) |

### 7.4 Config-Datenmodell (Erweiterung)

Das bestehende `configs.options` JSON wird erweitert:

```json
{
  "replicate_schema": true,
  "clear_strategy": "truncate",
  "chunk_size": 1000,
  "id_remapping": {
    "enabled": true,
    "tables": [
      {
        "table": "users",
        "primary_key": "id",
        "strategy": "random_integer",
        "range_min": 100000,
        "range_max": 9999999,
        "foreign_keys": [
          { "table": "posts", "column": "user_id" },
          { "table": "orders", "column": "customer_id" },
          { "table": "employees", "column": "manager_id", "self_referential": true }
        ]
      },
      {
        "table": "products",
        "primary_key": "uuid",
        "strategy": "new_uuid",
        "foreign_keys": [
          { "table": "order_items", "column": "product_uuid" }
        ]
      }
    ]
  }
}
```

---

## 8. Edge Cases & Lösungen

| Edge Case | Lösung |
|---|---|
| **Selbst-referenzielle FK** | Zweistufiges Processing: INSERT mit NULL, dann UPDATE |
| **Generierter Wert = Original-Wert** | Immer neu ziehen – Gleichheit mit Source ist verboten, unabhängig vom Range |
| **ID-Kollision (neuer Wert bereits vergeben)** | Max. 10 Retry-Versuche, dann Exception + Run-Abbruch |
| **FK-Tabelle nicht im Transfer** | Warnung in UI; Run trotzdem möglich (FK bleibt unverändert – Entwickler-Entscheidung) |
| **Unmapped FK-Wert zur Laufzeit** | `[WARNING] unmapped_fk_value` im Log, FK-Wert bleibt unverändert, Run läuft weiter |
| **First/Last X Rows: FK zeigt auf nicht-transferierte Row** | Erwartetes Verhalten bei Partial-Transfer; Warnung im Run-Log, FK-Wert bleibt unverändert |
| **Run-Abbruch nach Mapping, vor Cleanup** | `CleanupMappingJob` wird in `finally`-Block von `TransferService` registriert |
| **Sehr große Tabellen (>10 Mio. IDs)** | `BuildIdMappingJob` verarbeitet IDs in Chunks von 10.000 |
| **Gemischte Strategies in einem Run** | Vollständig unterstützt; pro Tabelle unabhängig konfigurierbar |
| **NULL-Werte in FK-Spalten** | NULL bleibt NULL, kein Mapping-Lookup |
| **Composite PKs** | Nicht unterstützt (MVP), Warnung in UI wenn erkannt |

---

## 9. Audit Trail & Compliance

Das Remapping wird im **Run-Log** dokumentiert. Es wird ausschließlich protokolliert, *dass* und *wie viele* Mappings erstellt wurden – niemals der konkrete Mapping-Inhalt (kein old_value → new_value).

```
[INFO] id_remapping_started      { tables: ["users", "products"], strategies: { users: "random_integer", products: "new_uuid" } }
[INFO] mapping_built             { table: "users", transfer_mode: "full", ids_mapped: 12450, duration_ms: 4200 }
[INFO] mapping_built             { table: "users", transfer_mode: "first_x", limit: 1000, ids_mapped: 1000, duration_ms: 380 }
[INFO] mapping_built             { table: "products", transfer_mode: "last_x", limit: 500, ids_mapped: 500, duration_ms: 190 }
[INFO] table_completed           { table: "posts", rows: 45230, remapped_fks: 45230 }
[WARNING] unmapped_fk_value      { table: "comments", column: "user_id", count: 3 }
[INFO] mapping_table_deleted     { mapping_table: "_clonio_id_mapping_42", status: "success" }
```

**Kein Mapping-Display in der UI:** Die konkreten Zuordnungen (original ID → neue ID) werden nirgendwo angezeigt – weder im Run-Detail, noch im Log-Viewer, noch im PDF-Export. Der Audit Trail bestätigt die Durchführung des Remappings, schafft aber keine Möglichkeit zur Rückführung.

Im **PDF-Report** erscheint ein dedizierter Abschnitt:

```
─────────────────────────────────────────
IDENTIFIER REMAPPING
─────────────────────────────────────────
Status:       Aktiv
Tabellen:     users (Random Integer), products (New UUID)
IDs gemappt:  13.700
FK-Updates:   45.230
Mapping-DB:   Gelöscht nach Run-Abschluss ✓

Compliance-Hinweis:
Alle Primary und Foreign Keys wurden durch nicht-rückführbare
Zufallswerte ersetzt. Kein Mapping wird persistiert.
─────────────────────────────────────────
```

---

## 10. Erfolgskriterien

| Kriterium | Messung |
|---|---|
| **Referenzielle Integrität** | Alle FK-Constraints auf Target-DB sind nach Transfer gültig |
| **Keine ID-Überschneidungen mit Prod** | Kein remappter Wert ist identisch mit dem Original |
| **Cleanup-Garantie** | Mapping-Tabelle existiert nach Run nicht mehr (automatisierter Test) |
| **Performance-Overhead** | `BuildIdMappingJob` < 10% der Gesamt-Run-Zeit |
| **Selbst-referenzielle Konsistenz** | `employees.manager_id` zeigt nach Transfer korrekt auf neuen ID-Wert |
| **UX: Konfigurierbar in < 3 Min.** | Nutzerstudie mit 5 Devs |

---

## 11. Design-Entscheidungen (alle geklärt)

Alle offenen Fragen wurden entschieden. Die Entscheidungen sind direkt in die Spezifikation eingeflossen.

| # | Frage | Entscheidung |
|---|---|---|
| **OQ-1** | Range für Random Integer konfigurierbar? | ✅ Globaler Default (100k–9,9M) mit optionaler Pro-Tabelle-Überschreibung im UI |
| **OQ-2** | FK-Tabelle nicht im Transfer, aber auf gereampte PK zeigend? | ✅ Warnung im UI + explizite Option „Trotzdem ausführen" – kein harter Fehler |
| **OQ-3** | Wo wird die temporäre Mapping-Tabelle angelegt? | ✅ Clonio App-DB – Source und Target bleiben vollständig unberührt |
| **OQ-4** | FK-Erkennung: automatisch oder manuell? | ✅ Auto-Detect aus Schema + manuelles Review-Schritt im UI vor dem ersten Run |
| **OQ-5** | Verhalten bei inkrementellen Transfers? | ✅ Komplett ausgeklammert – separates PRD wenn inkrementelle Transfers gebaut werden |

---

## 12. Implementierungs-Roadmap

### Sprint A: Pre-Processing & Mapping-Core (Woche 1–2)

- `BuildIdMappingJob` implementieren
- `IdRemappingService` mit beiden Strategien
- `IdMappingRepository` (temporäre Tabelle)
- `CleanupMappingJob`
- Unit-Tests: Mapping-Generierung, Kollisions-Handling
- Integration: `TransferService` Batch-Erweiterung

### Sprint B: FK-Propagation & Edge Cases (Woche 3)

- `ForeignKeyDetector` (Erweiterung von `DependencyResolver`)
- `ProcessTableJob`: `applyMapping()` integrieren
- Selbst-referenzielle Tabellen: zweistufiges Processing
- NULL-Handling in FK-Spalten
- Integration-Tests: Voller Transfer mit FK-Chain

### Sprint C: UI & Config (Woche 4)

- Config-Builder: Neuer Abschnitt „Identifier Remapping"
- Auto-Detect FKs aus Schema in UI
- Warnungen bei Konfigurationslücken
- Audit Trail & PDF-Report-Erweiterung
- E2E-Tests: Vollständiger User-Flow

---

## 13. Abhängigkeiten

| Abhängigkeit | Status | Notiz |
|---|---|---|
| `DependencyResolver` (bestehend) | ✅ Vorhanden | Muss FK-Spalten-Namen zurückgeben (nicht nur Tabellen) |
| `ProcessTableJob` (bestehend) | ✅ Vorhanden | Erweiterung um `applyMapping()`-Hook |
| `TransferService::startTransfer()` | ✅ Vorhanden | Erweiterung um Pre/Post-Jobs |
| Laravel Batch Jobs | ✅ Vorhanden | Wird genutzt wie bisher |
| `transfer_run_logs` | ✅ Vorhanden | Kein Schema-Change nötig |
| Config-Builder Vue-Komponente | ✅ Vorhanden | Erweiterung um neuen Step-3-Abschnitt |

---

*Ende des PRD – Identifier Remapping v1.2*
