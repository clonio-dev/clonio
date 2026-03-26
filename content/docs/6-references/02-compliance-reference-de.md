---
title: Reference for Compliance in Germany
excerpt: Technische Referenz für Clonio-Nutzer – welche regulatorischen Anforderungen gelten beim Einsatz von Produktionsdaten in Test- und Entwicklungsumgebungen, und wie erfüllt Clonio diese.
---

# Clonio – Compliance-Referenz: Produktionsdaten in Testumgebungen


> [!CAUTION]
> **Hinweis:** Dieses Dokument dient der technischen Orientierung. Es ersetzt keine Rechtsberatung. Prüfe die Anforderungen im Einzelfall mit deinem Datenschutzbeauftragten oder Rechtsberater.

---

## Inhaltsverzeichnis

1. [DSGVO / GDPR (EU)](#1-dsgvo--gdpr-eu)
2. [EDPB Guidelines 01/2025 – Pseudonymisierung](#2-edpb-guidelines-012025--pseudonymisierung)
3. [PCI DSS v4.0 (Zahlungsdaten)](#3-pci-dss-v40-zahlungsdaten)
4. [HIPAA (USA – Gesundheitsdaten)](#4-hipaa-usa--gesundheitsdaten)
5. [SOC 2 Type II](#5-soc-2-type-ii)
6. [ISO/IEC 27001 & 27701 / 29101](#6-isoiec-27001--27701--29101)
7. [Kombinierte Anforderungen nach Branche](#7-kombinierte-anforderungen-nach-branche)
8. [Clonio-Funktionen nach Compliance-Anforderung](#8-clonio-funktionen-nach-compliance-anforderung)
9. [Bekannte Lücken & Empfehlungen](#9-bekannte-lücken--empfehlungen)

---

## 1. DSGVO / GDPR (EU)

### Was die Verordnung verlangt

Die DSGVO (EU 2016/679) gilt für alle Organisationen, die personenbezogene Daten von EU-Bürgern verarbeiten – unabhängig vom Sitz der Organisation.

**Für Testumgebungen besonders relevant:**

| Artikel | Anforderung | Risiko bei Verstoß |
|---|---|---|
| **Art. 5(1)(a)** | Rechtmäßigkeit, Zweckbindung. Produktionsdaten dürfen nur für den Zweck genutzt werden, für den sie erhoben wurden. Testing ist kein ursprünglicher Zweck. | Bis zu 4% des globalen Jahresumsatzes oder 20 Mio. € |
| **Art. 5(1)(b)** | Zweckbindung – Weiterverarbeitung für andere Zwecke nur unter strengen Bedingungen | Wie Art. 5(1)(a) |
| **Art. 5(1)(c)** | Datensparsamkeit – nur die Datenmenge verwenden, die für den Zweck notwendig ist | Wie Art. 5(1)(a) |
| **Art. 5(1)(e)** | Speicherbegrenzung – Daten nicht länger speichern als notwendig | Mehrere Fälle mit €8–22 Mio. Strafe in 2024/25 |
| **Art. 25** | Privacy by Design – Datenschutz muss technisch eingebaut sein, nicht nachträglich | Dokumentationspflicht, Bußgelder |
| **Art. 30** | ROPA – Verarbeitungsverzeichnis: wo liegen welche Daten, wie sind sie geschützt | Bußgelder, Prüfungen |
| **Art. 32** | Technische und organisatorische Maßnahmen (TOM) – angemessener Schutz entsprechend dem Risiko | BfDI bestätigte: Dokumentation reicht nicht, technische Umsetzung ist Pflicht |

**Re-Identifikationsrisiken – drei Angriffsvektoren (EDPB-Definition):**

- **Singling out:** Kann eine Einzelperson aus dem Datensatz isoliert werden? → Kleine, bekannte IDs (z.B. user_id = 1 = CEO) ermöglichen das direkt.
- **Linkability:** Können Datensätze mit externen Quellen verknüpft werden? → Gleiche UUID in Prod-Logs und Testdaten erlaubt direkten Abgleich.
- **Inference:** Können Rückschlüsse auf eine Person gezogen werden? → Sequentielle IDs verraten Registrierungsreihenfolge und damit Metadaten.

### Wie Clonio diese Anforderungen erfüllt

| DSGVO-Anforderung | Clonio-Funktion |
|---|---|
| Zweckbindung (Art. 5(1)(b)) | Transfer nur in konfigurierte Target-DB; keine unkontrollierte Datenweitergabe |
| Datensparsamkeit (Art. 5(1)(c)) | Partial-Transfer (First/Last X Rows) überträgt nur notwendige Datenmenge |
| Privacy by Design (Art. 25) | Anonymisierungs-Engine ist technisch in den Transfer eingebaut, nicht optional nachträglich |
| Re-Identifikation verhindern | **Identifier Remapping:** Alle Primary und Foreign Keys werden durch nicht-rückführbare Zufallswerte ersetzt |
| ROPA / Nachweispflicht (Art. 30) | Audit Trail + PDF-Report dokumentiert vollständig: wer, wann, welche Daten, welche Methode |
| Speicherbegrenzung (Art. 5(1)(e)) | Mapping-Tabelle wird nach jedem Run garantiert gelöscht; kein persistiertes Mapping |
| TOM (Art. 32) | Verschlüsselte DB-Credentials; keine Übertragung sensibler Daten an Clonio-Server (Self-Hosted) |

---

## 2. EDPB Guidelines 01/2025 – Pseudonymisierung

### Status

**Verabschiedet:** 16. Januar 2025 (EDPB Plenary)  
**Konsultationsphase:** 17. Januar – **14. März 2025** (geschlossen)  
**Finale Version:** ausstehend (Stand März 2026 – noch nicht auf edpb.europa.eu veröffentlicht)  
**Dokument (Draft):** [edpb.europa.eu – Guidelines 01/2025](https://www.edpb.europa.eu/our-work-tools/documents/public-consultations/2025/guidelines-012025-pseudonymisation_en)

### Wichtigste Klarstellungen

**1. Pseudonymisierte Daten bleiben personenbezogen**
Die Guidelines stellen klar: Pseudonymisierte Daten, die mit zusätzlichen Informationen einer natürlichen Person zugeordnet werden könnten, sind weiterhin personenbezogene Daten im Sinne der DSGVO. Sie fallen nicht aus dem DSGVO-Anwendungsbereich heraus.

**2. Drei Schritte für wirksame Pseudonymisierung**
- Originaldaten so modifizieren, dass keine direkte Attribution mehr möglich ist
- Zusätzliche Informationen (Mapping-Keys) separat und gesichert aufbewahren
- Sicherstellen, dass das Mapping nicht innerhalb der Pseudonymisierungs-Domäne zugänglich ist

**3. Direkte Identifier müssen entfernt werden**
Die Guidelines halten fest (Paragraph 83): Pseudonymisierte Daten dürfen keine direkten Identifier (z.B. nationale ID-Nummern) enthalten, wenn diese in der Pseudonymisierungs-Domäne zur Zuordnung genutzt werden könnten.

**4. Quasi-Identifier beachten**
Auch wenn alle direkten Identifier entfernt wurden, können Kombinationen von Quasi-Identifiern (Alter, PLZ, Geschlecht usw.) zur Re-Identifikation führen. Dies adressiert das Konzept der **K-Anonymity** (Clonio Roadmap v2.0).

### Was das für Clonio bedeutet

Clonios Identifier-Remapping entspricht dem EDPB-Ansatz: Die Mapping-Tabelle (das "Zusatz-Informations-Schlüsselpaar") wird nach dem Run gelöscht und ist damit nicht in der Pseudonymisierungs-Domäne (Testumgebung) zugänglich. Das erfüllt technisch die EDPB-Anforderung der getrennten, gesicherten Verwahrung.

---

## 3. PCI DSS v4.0 (Zahlungsdaten)

### Relevante Requirements

**Requirement 6.5.5 (neu in v4.0, vorher 6.4.3):**
> Live PANs (Primary Account Numbers) dürfen nicht in Pre-Production-Umgebungen verwendet werden, außer diese Umgebungen sind selbst als CDE (Cardholder Data Environment) klassifiziert und erfüllen alle PCI-DSS-Anforderungen.

**Dies ist ein hartes Verbot**, keine Empfehlung. Ausnahmen erfordern, dass die komplette Pre-Production-Umgebung denselben PCI-DSS-Auflagen unterliegt wie Production.

**Requirement 6.5.3:**
> Pre-Production-Umgebungen müssen von Production-Umgebungen getrennt sein und die Trennung durch Zugriffskontrollen erzwungen werden.

**Requirement 6.5.4:**
> Rollen und Funktionen müssen zwischen Production- und Pre-Production-Umgebungen getrennt sein.

### Business Case für vollständige Maskierung

Wenn eine Umgebung **keinerlei** Cardholder-Daten (CHD) oder Sensitive Authentication Data (SAD) mehr enthält, kann sie vollständig aus dem PCI-DSS-Scope herausgenommen werden. Das bedeutet:

- Keine PCI-DSS-Audits für diese Umgebung
- Keine dedizierten Security Controls für diese Umgebung
- Erheblich reduzierter Compliance-Aufwand

**Dafür müssen folgende Datentypen vollständig entfernt oder maskiert sein:**

| Datentyp | Beschreibung | Clonio-Handlung |
|---|---|---|
| PAN | Primary Account Number (Kartennummer) | Faker-Ersatz + ID-Remapping |
| CVV/CVC | Prüfnummer | Faker-Ersatz |
| Ablaufdatum | Kombination mit PAN ist sensitiv | Faker-Datum |
| Karteninhaber-Name | In Kombination mit PAN | Faker-Name |
| PIN / PIN-Block | Immer entfernen | Leer/Null |

### Wie Clonio diese Anforderungen erfüllt

| PCI-DSS-Anforderung | Clonio-Funktion |
|---|---|
| Keine Live-PANs in Test (6.5.5) | Feldname-Erkennung (`pan`, `card_number`, `cc_number`) + Faker-Ersatz |
| Umgebungen trennen (6.5.3) | Clonio überträgt nur in konfigurierte Target-DB; Source bleibt unberührt |
| Vollständige Entfernung für Out-of-Scope | Kombination: Feldmasking + ID-Remapping entfernt alle direkten und indirekten Identifier |

**Empfehlung für PCI-DSS-Kunden:** Das PCI-DSS-Template im Script-Editor enthält alle relevanten Felderkennungen und Faker-Strategien vorkonfiguriert.

---

## 4. HIPAA (USA – Gesundheitsdaten)

### Anwendungsbereich

HIPAA gilt für **Covered Entities** (Krankenhäuser, Kliniken, Versicherungen) und ihre **Business Associates** (IT-Dienstleister, die PHI verarbeiten). Wer als Business Associate für einen US-Gesundheitsdienstleister arbeitet, unterliegt HIPAA.

### De-Identifikation: Die zwei anerkannten Methoden

**Methode 1: Safe Harbor**
Alle der folgenden 18 Identifier-Typen müssen entfernt oder generalisiert werden:

| # | Identifier-Typ | Beispiele | Clonio-Erkennung |
|---|---|---|---|
| 1 | Namen | Vor-, Nach-, Geburtsname | `name`, `first_name`, `last_name` |
| 2 | Geografische Angaben < Bundesstaat | Straße, Stadt, PLZ, Kreis | `zip`, `plz`, `city`, `street` |
| 3 | Alle Daten außer Jahr | Geburtsdatum, Aufnahmedatum, Entlassdatum | `dob`, `birth_date`, `admission_date` |
| 4 | Telefonnummern | Alle Telefonnummern | `phone`, `tel`, `mobile` |
| 5 | Faxnummern | Alle Faxnummern | `fax` |
| 6 | E-Mail-Adressen | Alle E-Mail-Adressen | `email` |
| 7 | Sozialversicherungsnummern | SSN, SV-Nummer | `ssn`, `social_security` |
| 8 | Krankenversicherungsnummern | Alle Versicherungs-IDs | `insurance_id`, `member_id` |
| 9 | Kontonummern | Bank- und sonstige Kontonummern | `account_number`, `iban` |
| 10 | Zertifikats-/Lizenznummern | Alle Lizenznummern | `license_number` |
| 11 | Fahrzeugnummern | Kennzeichen, VIN | `license_plate`, `vin` |
| 12 | Gerätekennungen | Seriennummern, IMEI | `device_id`, `serial_number` |
| 13 | Web-URLs | Alle Web-Adressen | `url`, `website` |
| 14 | IP-Adressen | IPv4 und IPv6 | `ip`, `ip_address` |
| 15 | Biometrische Kennzeichen | Fingerabdrücke, Stimme | Feld-by-Feld-Konfiguration |
| 16 | Fotos | Fotos des Gesichts | Nicht automatisierbar |
| 17 | **Alle anderen eindeutigen Identifiernummern** | **IDs, Nummern, Kennzeichen** | **→ ID-Remapping** |
| 18 | Sonstige einzigartige Merkmale | Alle anderen identifizierenden Merkmale | Konfigurierbar |

**Methode 2: Expert Determination**
Ein Statistiker oder Datenschutz-Experte bestätigt, dass das Risiko der Re-Identifikation sehr klein ist. Erfordert formale Dokumentation.

### Wie Clonio diese Anforderungen erfüllt

| HIPAA-Anforderung | Clonio-Funktion |
|---|---|
| Entfernung aller 18 Identifier (Safe Harbor) | Feldname-Erkennung deckt Kategorien 1–7, 10–11, 14 automatisch ab; ID-Remapping deckt Kategorie 17 (alle eindeutigen IDs) |
| Datumsfelder auf Jahr generalisieren | Faker-Datum-Strategie mit Konfiguration „Jahr beibehalten" |
| Geo-Identifier entfernen | Faker-Adresse oder Leer-Strategie für PLZ/Stadt |
| Dokumentation der De-Identifikation | Audit Trail + PDF-Report ist als Nachweis nutzbar |

**Wichtig:** HIPAA Safe Harbor erfordert, dass **alle** 18 Identifier-Typen adressiert sind. Clonio-Nutzer müssen sicherstellen, dass das Script alle relevanten Felder ihrer spezifischen Datenbank abdeckt. Das HIPAA-Template im Script-Editor enthält eine Checkliste hierfür.

---

## 5. SOC 2 Type II

### Was SOC 2 für Testdaten verlangt

SOC 2 schreibt keine spezifischen Techniken vor, operiert aber über die **Trust Service Criteria (TSC)**:

- **CC6.1 – Logical Access:** Zugriff auf sensible Daten muss auf autorisierte Personen beschränkt sein
- **CC6.7 – Data Transmission:** Datenübertragungen müssen kontrolliert und gesichert sein
- **P4 – Use of Personal Information:** Personenbezogene Daten dürfen nur für den vorgesehenen Zweck genutzt werden
- **P5 – Retention:** Daten müssen gemäß definierten Richtlinien aufbewahrt und gelöscht werden

**Für Testumgebungen relevant:** SOC-2-Auditoren erwarten zunehmend, dass Produktionsdaten in Testumgebungen anonymisiert sind. Fehlende Kontrollen werden als Finding dokumentiert.

### Wie Clonio SOC-2-Audits unterstützt

| SOC-2-Kriterium | Clonio-Nachweis |
|---|---|
| CC6.1 – Zugangskontrolle | DB-Credentials verschlüsselt gespeichert; kein Zugriff durch Dritte (Self-Hosted) |
| CC6.7 – Datenübertragung | Transfer nur zwischen konfigurierten Endpoints; Audit Trail dokumentiert jeden Transfer |
| P4 – Zweckbindung | Jede Config explizit konfiguriert; kein automatischer Datenaustausch |
| P5 – Retention | Mapping-Tabelle nach Run gelöscht; keine persistierten Roh-Daten |
| Nachweis der Kontrollen | PDF-Report mit vollständigem Audit Trail; direkt verwertbar für SOC-2-Audit |

**Empfehlung:** Den Clonio-PDF-Report als Teil der SOC-2-Kontrollnachweise archivieren. Jeder Transfer-Run dokumentiert automatisch Datum, Methode, betroffene Tabellen und Anonymisierungsstrategien.

---

## 6. ISO/IEC 27001 & 27701 / 29101

### ISO/IEC 27001 (Informationssicherheits-Management)

**Relevante Controls (Annex A):**
- **A.8.11 – Data Masking:** Daten müssen entsprechend der Datenschutzrichtlinie und den Geschäftsanforderungen maskiert werden
- **A.8.12 – Data Leakage Prevention:** Maßnahmen zur Verhinderung von Datenlecks müssen umgesetzt werden
- **A.5.34 – Privacy and PII Protection:** Datenschutz und Schutz personenbezogener Daten müssen entsprechend den Anforderungen implementiert werden

### ISO/IEC 27701 (Datenschutz-Management, Erweiterung zu 27001)

Erweitert ISO 27001 explizit um Datenschutzanforderungen. Für Testdaten: Personenbezogene Daten müssen durch technische Kontrollen geschützt werden, wenn sie in Umgebungen mit geringerem Schutzniveau verwendet werden.

### ISO/IEC 29101 (Privacy Architecture Framework)

Das **BfDI (Bundesbeauftragter für Datenschutz)** referenziert diesen Standard als technische Grundlage für Pseudonymisierung. Organisationen, die Art. 4(5) DSGVO (Pseudonymisierung als Schutzmaßnahme) geltend machen wollen, müssen nachweisen, dass ihre Pseudonymisierung diesen Standards entspricht – insbesondere:

- Key-Management-Praktiken (Verwaltung der Mapping-Schlüssel)
- Reversal-Controls (Kontrolle darüber, wer das Mapping umkehren kann)
- Technische Dokumentation der Pseudonymisierungs-Methode

### Wie Clonio diese Anforderungen erfüllt

| ISO-Anforderung | Clonio-Funktion |
|---|---|
| A.8.11 – Data Masking | Anonymisierungs-Engine mit konfigurierbaren Strategien pro Feld |
| A.8.12 – Data Leakage Prevention | Self-Hosted: kein Datenaustausch mit Clonio-Servern; Credentials verschlüsselt |
| ISO/IEC 29101 – Key Management | Mapping-Tabelle nur in Clonio App-DB; nach Run gelöscht; kein Zugriff aus Testumgebung |
| ISO/IEC 29101 – Reversal Controls | Kein persistiertes Mapping → Umkehrung nach Run physisch unmöglich |
| Technische Dokumentation | Audit Trail + PDF-Report dokumentiert Methode, Tabellen, Strategien |

---

## 7. Kombinierte Anforderungen nach Branche

| Branche | Typische Regularien | Clonio-Mindestanforderungen |
|---|---|---|
| **FinTech (EU)** | DSGVO, PCI DSS v4.0, ggf. DORA | Feldmasking aller PII + ID-Remapping + Audit Trail |
| **HealthTech (EU)** | DSGVO (Art. 9 besondere Kategorien), ggf. HIPAA | Vollständige Safe-Harbor-Abdeckung + ID-Remapping + HIPAA-Template |
| **HealthTech (USA)** | HIPAA, ggf. SOC 2 | Alle 18 Safe-Harbor-Identifier entfernen; Expert-Determination-Dokumentation empfohlen |
| **SaaS (global)** | DSGVO, SOC 2 Type II | Feldmasking + ID-Remapping + PDF-Reports für Audit-Archiv |
| **E-Commerce** | DSGVO, PCI DSS v4.0 | PCI-DSS-Template + Kartenfelderkennung + Feldmasking |
| **Versicherungen (EU)** | DSGVO, Solvency II | Feldmasking + ID-Remapping + Audit Trail; ggf. DPIA erforderlich |

---

## 8. Clonio-Funktionen nach Compliance-Anforderung

### Schnellübersicht: Was muss aktiviert sein?

| Compliance-Anforderung | Clonio-Funktion | Wo konfigurieren |
|---|---|---|
| **Keine PII in Klarnamen** | Script-Editor: Faker-Strategien | Config > Script |
| **Keine reversibler Identifier** | [Identifier Remapping](../2-clonings/05-key-remapping.md) (PK + FK) | Config > Options > Identifier Remapping |
| **Datensparsamkeit** | Partial-Transfer (First/Last X Rows) | Config > Options > Transfer Mode |
| **Nachweisbarkeit (ROPA, SOC 2)** | Audit Trail + PDF-Export | Transfer Run > Export |
| **PCI-DSS-Felder erkennen** | Feldname-Erkennung + PCI-Template | Config > Script > Templates |
| **HIPAA Safe Harbor** | HIPAA-Template + manuelle Vervollständigung | Config > Script > Templates |
| **Mapping-Cleanup** | Automatisch nach Run (nicht deaktivierbar) | Kein Handlungsbedarf |

### Compliance-Status pro Run (im PDF-Report)

Der Audit-Trail-Report enthält einen **Compliance-Status-Abschnitt**, der für den jeweiligen Run zusammenfasst:

```
COMPLIANCE-STATUS DIESES RUNS
──────────────────────────────────────────────
Identifier Remapping:     ✅ Aktiv (Random Integer)
PII-Felder maskiert:      ✅ 12 Felder (name, email, phone ...)
Mapping nach Run gelöscht:✅ Bestätigt
Partial Transfer:         ℹ Vollständig (kein Partial)

Hinweis: Dieser Report dokumentiert die technischen Maßnahmen.
Er ersetzt keine DPIA oder rechtliche Bewertung.
──────────────────────────────────────────────
```

---

## 9. Bekannte Lücken & Empfehlungen

### Was Clonio aktuell nicht automatisch abdeckt

| Lücke | Regulierung | Empfehlung |
|---|---|---|
| **Quasi-Identifier-Kombinationen** (K-Anonymity) | DSGVO, EDPB Guidelines | Manuell prüfen: Können z.B. Alter + PLZ + Geschlecht zusammen eine Person identifizieren? → Clonio v2.0 Roadmap |
| **HIPAA-Felder 15–18** (Biometrie, Fotos, sonstige Merkmale) | HIPAA Safe Harbor | Manuelle Konfiguration im Script-Editor erforderlich |
| **DPIA (Datenschutz-Folgenabschätzung)** | DSGVO Art. 35 | Clonio erstellt keinen DPIA-Bericht; empfohlen für HealthTech und FinTech-Kunden |
| **Consent-Management** | DSGVO Art. 6/7 | Clonio überträgt Daten, prüft aber nicht ob Einwilligung vorlag |
| **Cross-DB Foreign Keys** | Alle Standards | Manuell sicherstellen, dass FK-Referenzen über DB-Grenzen hinweg konsistent behandelt werden |

### Empfohlene Ergänzungen pro Regulierung

**Für DSGVO-Compliance:**
1. Clonio-Transfer-Config dokumentieren (ROPA-Eintrag)
2. PDF-Reports archivieren (mind. 3 Jahre)
3. Zielumgebung auf Zugriffsbeschränkungen prüfen (nur berechtigte Personen)

**Für PCI-DSS-Compliance:**
1. PCI-DSS-Template verwenden und anpassen
2. Sicherstellen, dass KEIN PAN-Feld unkonfiguriert bleibt
3. Zielumgebung nach erfolgreichem Transfer durch PCI-DSS-Scanner verifizieren lassen

**Für HIPAA-Compliance:**
1. HIPAA-Template verwenden
2. Alle 18 Safe-Harbor-Identifier manuell in der eigenen Schema-Struktur prüfen
3. Expert-Determination-Dokument erstellen (empfohlen für erhöhte Rechtssicherheit)

---

## Verwandte Dokumentation

- [Key Remapping](../2-clonings/05-key-remapping.md) — Wie Clonio Primary Keys und Foreign Keys während eines Cloning Runs ersetzt: Strategien, Bereichskonfiguration und Cleanup-Verhalten.

## Weiterführende Quellen

- [DSGVO Volltext – EUR-Lex](https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32016R0679)
- [EDPB Guidelines 01/2025 – Pseudonymisierung (Draft)](https://www.edpb.europa.eu/our-work-tools/documents/public-consultations/2025/guidelines-012025-pseudonymisation_en)
- [PCI DSS v4.0 – PCI Security Standards Council](https://www.pcisecuritystandards.org/document_library/)
- [HIPAA Safe Harbor De-Identification – HHS](https://www.hhs.gov/hipaa/for-professionals/privacy/special-topics/de-identification/index.html)
- [ISO/IEC 27001:2022 – Information Security](https://www.iso.org/standard/82875.html)
- [ISO/IEC 29101:2018 – Privacy Architecture Framework](https://www.iso.org/standard/45124.html)
