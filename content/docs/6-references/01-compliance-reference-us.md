---
title: Reference for Compliance in the U.S.
excerpt: Technical reference for Clonio users — which U.S. regulatory requirements apply when using production data in test and development environments, and how Clonio addresses them.
---

# Clonio – Compliance Reference: Production Data in Test Environments

> [!CAUTION]
> **Disclaimer** This document is for technical orientation only. It does not constitute legal advice. Consult your legal counsel, privacy officer, or compliance specialist to assess requirements for your specific situation.

---

## Table of Contents

1. [HIPAA (Health Data)](#1-hipaa-health-data)
2. [PCI DSS v4.0 (Payment Card Data)](#2-pci-dss-v40-payment-card-data)
3. [SOC 2 Type II](#3-soc-2-type-ii)
4. [CCPA / CPRA (California)](#4-ccpa--cpra-california)
5. [State Privacy Laws Overview](#5-state-privacy-laws-overview)
6. [NIST Privacy Framework & NIST SP 800-188](#6-nist-privacy-framework--nist-sp-800-188)
7. [Requirements by Industry](#7-requirements-by-industry)
8. [Clonio Features by Compliance Requirement](#8-clonio-features-by-compliance-requirement)
9. [Known Gaps & Recommendations](#9-known-gaps--recommendations)

---

## 1. HIPAA (Health Data)

### Who It Applies To

HIPAA (Health Insurance Portability and Accountability Act, 45 CFR Parts 160 and 164) applies to:
- **Covered Entities:** Healthcare providers, health plans, healthcare clearinghouses
- **Business Associates:** Any vendor or IT provider that creates, receives, maintains, or transmits Protected Health Information (PHI) on behalf of a covered entity

If you build software for U.S. healthcare organizations, you are likely a **Business Associate** and must sign a Business Associate Agreement (BAA).

### De-Identification: The Two Accepted Methods

Using PHI in test environments requires formal de-identification under one of two HHS-recognized methods.

#### Method 1: Safe Harbor (45 CFR §164.514(b))

All 18 of the following identifier types must be removed or generalized:

| # | Identifier Type | Examples | Clonio Auto-Detection |
|---|---|---|---|
| 1 | Names | First, last, maiden name | `name`, `first_name`, `last_name` |
| 2 | Geographic subdivisions smaller than state | Street, city, county, ZIP code | `zip`, `postal_code`, `city`, `street`, `address` |
| 3 | All dates except year | Birth date, admission date, discharge date, date of death | `dob`, `birth_date`, `admission_date`, `discharge_date` |
| 4 | Phone numbers | All telephone numbers | `phone`, `tel`, `mobile`, `cell` |
| 5 | Fax numbers | All fax numbers | `fax`, `fax_number` |
| 6 | Email addresses | All email addresses | `email`, `email_address` |
| 7 | Social Security numbers | SSN | `ssn`, `social_security`, `social_security_number` |
| 8 | Medical record numbers | All medical IDs | `mrn`, `medical_record_number`, `patient_id` |
| 9 | Health plan beneficiary numbers | Insurance member IDs | `member_id`, `insurance_id`, `beneficiary_number` |
| 10 | Account numbers | Bank and other account numbers | `account_number`, `account_id` |
| 11 | Certificate/license numbers | All license numbers | `license_number`, `license_id` |
| 12 | Vehicle identifiers | License plates, VIN | `vin`, `license_plate` |
| 13 | Device identifiers | Serial numbers, IMEI, MAC addresses | `device_id`, `serial_number`, `mac_address`, `imei` |
| 14 | Web URLs | All web addresses | `url`, `website` |
| 15 | IP addresses | IPv4 and IPv6 | `ip`, `ip_address`, `remote_addr` |
| 16 | Biometric identifiers | Fingerprints, voice prints, retinal scans | Field-by-field configuration required |
| 17 | **Full-face photographs and comparable images** | Patient photos | Not automatically detectable |
| 18 | **Any other unique identifying number, characteristic, or code** | **All IDs, reference numbers, record numbers** | [Identifier Remapping](../2-clonings/05-key-remapping.md) |

**Critical:** Identifier #18 explicitly covers all database IDs, record numbers, and unique codes. Sequential integer IDs and UUIDs fall directly under this category. **[Identifier Remapping](../2-clonings/05-key-remapping.md) is required for HIPAA Safe Harbor compliance.**

Additionally, the expert must verify that remaining data has no actual knowledge that could identify an individual.

#### Method 2: Expert Determination (45 CFR §164.514(b)(1))

A qualified statistician or privacy expert certifies that the risk of identifying an individual is "very small." Requires formal documentation and periodic re-assessment.

### Minimum Necessary Standard

Even when using de-identified data, HIPAA's Minimum Necessary principle (45 CFR §164.502(b)) applies to the process of creating that test data: only transfer the minimum amount of data necessary for the test purpose. Clonio's **Partial Transfer (First/Last X Rows)** directly supports this requirement.

### How Clonio Addresses HIPAA Requirements

| HIPAA Requirement | Clonio Feature |
|---|---|
| Safe Harbor identifier #18 – all unique IDs | [Identifier Remapping](../2-clonings/05-key-remapping.md) (PK + FK) with Random Integer or New UUID strategy |
| Safe Harbor identifiers #1–7, 10–11, 14–15 | Field-name detection + Faker strategies (name, email, phone, SSN, etc.) |
| Safe Harbor identifier #3 – dates | Faker date strategy with "preserve year" option |
| Safe Harbor identifier #2 – geographic subdivisions | Faker address or null strategy for ZIP/city |
| Minimum Necessary principle | Partial Transfer (First/Last X Rows) |
| De-identification documentation | Audit Trail + PDF Report — usable as technical evidence |
| BAA compliance support | Self-hosted deployment — PHI never leaves customer infrastructure |

**HIPAA Template:** The HIPAA template in Clonio's Script Editor pre-configures all automatically detectable identifier categories. Users must manually verify categories 16–17 and any domain-specific identifiers not covered by name-based detection.

---

## 2. PCI DSS v4.0 (Payment Card Data)

### Who It Applies To

Any organization that stores, processes, or transmits **Primary Account Numbers (PANs)** — credit and debit card numbers. This includes merchants, payment processors, and their service providers.

### Relevant Requirements for Test Environments

**Requirement 6.5.5** (new in v4.0, formerly 6.4.3):
> Live PANs must not be used in pre-production environments, except where those environments are included in the Cardholder Data Environment (CDE) and protected in accordance with all applicable PCI DSS requirements.

This is a **hard prohibition**, not a recommendation. The only exception requires the entire pre-production environment to meet full PCI DSS controls — which is typically cost-prohibitive and defeats the purpose.

**Requirement 6.5.3:** Pre-production environments must be separated from production environments with enforced access controls.

**Requirement 6.5.4:** Roles and functions must be separated between production and pre-production environments.

### The Scope Reduction Business Case

When an environment contains **no** Cardholder Data (CHD) or Sensitive Authentication Data (SAD) whatsoever, it is completely removed from PCI DSS scope. This means:
- No PCI DSS audits for that environment
- No dedicated security controls required
- Significantly reduced compliance cost and effort

**Data types that must be fully removed or masked for scope reduction:**

| Data Type | Description | Clonio Handling |
|---|---|---|
| PAN | Full credit/debit card number | Faker replacement + ID Remapping |
| CVV/CVC/CVV2 | Card verification value | Faker replacement |
| Expiration date | In combination with PAN, it is sensitive | Faker date |
| Cardholder name | In combination with PAN | Faker name |
| PIN / PIN block | Always remove | Null/empty strategy |

### How Clonio Addresses PCI DSS Requirements

| PCI DSS Requirement | Clonio Feature |
|---|---|
| No live PANs in test (6.5.5) | Field-name detection (`pan`, `card_number`, `cc_number`, `credit_card`) + Faker |
| Environment separation (6.5.3) | Transfer only to configured target DB; source untouched |
| Full scope removal | Combined: field masking + ID Remapping removes all direct and indirect identifiers |
| Audit evidence | PDF Report documents masking strategies per field per run |

**PCI DSS Template:** Available in the Script Editor. Pre-configures all commonly named payment data fields.

---

## 3. SOC 2 Type II

### What It Is

SOC 2 (System and Organization Controls 2) is an auditing framework developed by the AICPA (American Institute of Certified Public Accountants). It is based on five **Trust Service Criteria (TSC)**:

- **Security** (CC) — required for all SOC 2 reports
- **Availability** (A)
- **Processing Integrity** (PI)
- **Confidentiality** (C)
- **Privacy** (P)

SOC 2 Type II covers a period of time (typically 6–12 months) and tests the operational effectiveness of controls. It is widely required by enterprise customers evaluating SaaS vendors.

### Relevant Controls for Test Data

| TSC | Criteria | Requirement |
|---|---|---|
| **CC6.1** | Logical Access Controls | Access to sensitive data must be restricted to authorized individuals |
| **CC6.7** | Data Transmission | Transmissions of sensitive data must be controlled and secured |
| **P4** | Use of Personal Information | Personal information must only be used for its intended purpose |
| **P5** | Retention and Disposal | Data must be retained and disposed of per defined policies |
| **P6** | Disclosure and Notification | Personal information must not be disclosed to unauthorized parties |

**Auditor expectations:** SOC 2 auditors increasingly expect that production data used in test environments is anonymized. Absence of such controls is documented as a finding and may result in a qualified opinion.

### How Clonio Supports SOC 2 Audits

| SOC 2 Criterion | Clonio Evidence |
|---|---|
| CC6.1 – Access Controls | DB credentials encrypted; self-hosted (no third-party access) |
| CC6.7 – Data Transmission | Transfer only between configured endpoints; full audit trail |
| P4 – Use Limitation | Every config explicitly scoped; no automatic data sharing |
| P5 – Retention & Disposal | Mapping table deleted after run; no persisted raw data |
| P6 – Disclosure | Self-hosted: data never leaves customer infrastructure |
| Audit Evidence | PDF Report: date, method, tables, anonymization strategies per run |

**Recommendation:** Archive Clonio PDF Reports as part of SOC 2 control evidence. Each run automatically documents the what, when, how, and outcome of every data transfer.

---

## 4. CCPA / CPRA (California)

### Who It Applies To

The **California Consumer Privacy Act (CCPA)**, as amended by the **California Privacy Rights Act (CPRA)**, applies to for-profit businesses that:
- Have annual gross revenues > $25 million, OR
- Buy, sell, or share personal information of 100,000+ consumers or households, OR
- Derive 50%+ of annual revenue from selling or sharing personal information

### Relevant Provisions for Test Data

**Right to deletion (Cal. Civ. Code § 1798.105):** If a consumer requests deletion of their data, that deletion must propagate to all environments — including test systems. Using anonymized test data avoids this compliance burden entirely.

**Data minimization (CPRA addition):** Businesses must collect and retain only personal information that is "reasonably necessary and proportionate" to the disclosed purpose. Using production data in test systems is difficult to justify under this standard.

**De-identification under CCPA (§ 1798.140(m)):** Data is considered de-identified if it "cannot reasonably identify, relate to, describe, be capable of being associated with, or be linked, directly or indirectly, to a particular consumer." This standard is functionally similar to GDPR's anonymization standard.

### How Clonio Addresses CCPA/CPRA Requirements

| CCPA/CPRA Requirement | Clonio Feature |
|---|---|
| De-identification to remove consumer linkability | [Identifier Remapping](../2-clonings/05-key-remapping.md) + field-level Faker strategies |
| Data minimization | Partial Transfer (First/Last X Rows) |
| Deletion propagation risk avoidance | Using de-identified data eliminates deletion obligation for test environments |
| Audit trail for accountability | PDF Report per run |

---

## 5. State Privacy Laws Overview

As of early 2026, 20+ U.S. states have enacted comprehensive privacy laws. Most follow similar structures to CCPA/CPRA. For test data purposes, the common requirements are:

| State | Law | Test Data Relevance |
|---|---|---|
| California | CCPA/CPRA | De-identification standard, data minimization |
| Virginia | VCDPA | Similar de-identification standard |
| Colorado | CPA | Data minimization, de-identification |
| Connecticut | CTDPA | De-identification, purpose limitation |
| Texas | TDPSA | De-identification, data minimization |
| Florida | FDBR | For large platforms; similar structure |
| Oregon | OCPA | Strong de-identification requirements |
| Montana | MCDPA | Data minimization, purpose limitation |

**Common standard across all:** Data that has been de-identified such that it "cannot reasonably be used to infer information about" or "linked to" a consumer is generally excluded from the definition of personal data — removing the compliance burden.

Clonio's combination of [Identifier Remapping](../2-clonings/05-key-remapping.md) and field-level Faker anonymization is designed to meet this de-identification standard across all state laws.

---

## 6. NIST Privacy Framework & NIST SP 800-188

### NIST Privacy Framework (v1.1, 2023)

The NIST Privacy Framework is a voluntary tool for organizations to manage privacy risk. While not legally binding, it is widely used as a reference standard — especially in government contracting and by organizations seeking to demonstrate privacy maturity.

**Relevant functions for test data:**

- **IDENTIFY (ID):** Know what personal data exists, where it flows, and what the risks are
- **GOVERN (GV):** Establish policies and accountability for data use
- **CONTROL (CT):** Implement technical controls to limit data processing to what is authorized
- **PROTECT (PR):** Use de-identification and other techniques to reduce privacy risk

### NIST SP 800-188: De-Identifying Government Data Sets

NIST SP 800-188 provides technical guidance on de-identification, with direct applicability to database anonymization. Key concepts:

**Quasi-identifiers:** Attributes that, when combined, can identify an individual even if none identifies alone (e.g., ZIP + birth date + gender). NIST recommends assessing quasi-identifier combinations as part of any de-identification process.

**K-anonymity:** A formal privacy model requiring that each record be indistinguishable from at least k-1 other records. NIST SP 800-188 recommends k-anonymity or stronger models for sensitive data sets.

**Direct identifiers:** Must always be removed or transformed — corresponds directly to what Clonio's [Identifier Remapping](../2-clonings/05-key-remapping.md) addresses.

### How Clonio Addresses NIST Recommendations

| NIST Recommendation | Clonio Feature |
|---|---|
| Remove direct identifiers | [Identifier Remapping](../2-clonings/05-key-remapping.md) (PK/FK) + field-level Faker |
| Control data flows | Self-hosted; transfer only to configured target |
| Document de-identification | PDF Report per run |
| Minimize data collected | Partial Transfer (First/Last X Rows) |
| Quasi-identifier assessment | Manual process; Clonio v2.0 roadmap (K-anonymity support) |

---

## 7. Requirements by Industry

| Industry | Applicable Standards | Clonio Minimum Requirements |
|---|---|---|
| **Healthcare / HealthTech** | HIPAA, SOC 2, HL7/FHIR security | HIPAA Template + all 18 Safe Harbor identifiers + Identifier Remapping + Audit Trail |
| **Payments / FinTech** | PCI DSS v4.0, SOC 2, state privacy laws | PCI DSS Template + PAN field detection + Identifier Remapping + PDF Reports |
| **SaaS (B2B)** | SOC 2 Type II, CCPA, state laws | Field masking + Identifier Remapping + PDF Reports for audit archive |
| **Insurance** | HIPAA (if health data), state insurance regulations, SOC 2 | Full field masking + Identifier Remapping + Audit Trail |
| **Government / Defense** | FedRAMP, NIST 800-53, CMMC | NIST de-identification guidance; Clonio self-hosted supports air-gap requirements |
| **Retail / E-Commerce** | PCI DSS v4.0, CCPA, state laws | PCI DSS Template + Identifier Remapping + Faker for customer PII |
| **Education** | FERPA, COPPA (if <13), state laws | Field masking for student records + Identifier Remapping |

---

## 8. Clonio Features by Compliance Requirement

### Quick Reference: What Must Be Enabled?

| Compliance Requirement | Clonio Feature | Where to Configure |
|---|---|---|
| **No PII in plaintext** | Script Editor: Faker strategies | Config > Script |
| **No reversible identifiers** | [Identifier Remapping](../2-clonings/05-key-remapping.md) (PK + FK) | Config > Options > Identifier Remapping |
| **Data minimization** | Partial Transfer (First/Last X Rows) | Config > Options > Transfer Mode |
| **Audit evidence (SOC 2, HIPAA)** | Audit Trail + PDF Export | Transfer Run > Export |
| **PCI DSS field detection** | Field-name detection + PCI Template | Config > Script > Templates |
| **HIPAA Safe Harbor** | HIPAA Template + manual verification | Config > Script > Templates |
| **Mapping cleanup / no persistence** | Automatic after run (non-disableable) | No action required |

### Compliance Status in PDF Report

Every Clonio PDF Report includes a **Compliance Status section** summarizing the run:

```
COMPLIANCE STATUS — THIS RUN
─────────────────────────────────────────────
Identifier Remapping:     ✅ Active (Random Integer)
PII Fields Masked:        ✅ 12 fields (name, email, phone ...)
Mapping Deleted After Run:✅ Confirmed
Partial Transfer:         ℹ Full Transfer (no limit)

Note: This report documents technical measures taken.
It does not constitute a legal de-identification
certification or substitute for expert review.
─────────────────────────────────────────────
```

---

## 9. Known Gaps & Recommendations

### What Clonio Does Not Automatically Cover

| Gap | Regulation | Recommendation |
|---|---|---|
| **Quasi-identifier combinations** (K-anonymity) | NIST SP 800-188, HIPAA Expert Determination | Manual review: can ZIP + birth year + gender in combination identify someone? → Clonio v2.0 roadmap |
| **HIPAA identifiers #16–17** (biometrics, photos) | HIPAA Safe Harbor | Manual configuration in Script Editor required |
| **Expert Determination documentation** | HIPAA | Clonio produces technical evidence; a qualified expert must provide the formal certification |
| **BAA / DPA execution** | HIPAA | Clonio self-hosted means no BAA with Clonio is required; customers must manage BAAs with their own vendors |
| **Consumer deletion propagation** | CCPA/CPRA | Anonymized test data eliminates this risk; Clonio does not track consumer deletion requests |
| **FERPA / COPPA specifics** | Education, children's data | Custom field configuration required |
| **FedRAMP authorization** | U.S. federal government | Clonio self-hosted can support air-gapped deployments; FedRAMP authorization not currently in scope |

### Recommended Steps by Regulation

**For HIPAA Compliance:**
1. Use HIPAA Template as starting point
2. Manually verify all 18 Safe Harbor identifiers against your schema
3. Enable Identifier Remapping (required for identifier #18)
4. Archive PDF Reports as technical de-identification evidence
5. Consider engaging a statistician for Expert Determination documentation

**For PCI DSS Compliance:**
1. Use PCI DSS Template; customize for your schema
2. Verify no PAN field is left unconfigured
3. Enable Identifier Remapping for all record ID fields
4. Have a QSA (Qualified Security Assessor) validate scope reduction

**For SOC 2 Type II:**
1. Enable Identifier Remapping + field masking for all runs using production data
2. Archive PDF Reports in your evidence management system
3. Document the Clonio workflow in your security policies (CC6.1 control narrative)

**For CCPA / State Privacy Laws:**
1. Enable de-identification (Identifier Remapping + field masking) for all test data transfers
2. Document that test environments use de-identified data (removes deletion-propagation risk)
3. Review state-specific requirements if operating in multiple states

---

## Related Documentation

- [Key Remapping](../2-clonings/05-key-remapping.md) — How Clonio replaces primary and foreign keys during a cloning run, including strategy options, range configuration, and cleanup behaviour.

## Reference Documents

- [HIPAA De-Identification Guidance – HHS](https://www.hhs.gov/hipaa/for-professionals/privacy/special-topics/de-identification/index.html)
- [PCI DSS v4.0 – PCI Security Standards Council](https://www.pcisecuritystandards.org/document_library/)
- [SOC 2 Trust Services Criteria – AICPA](https://www.aicpa-cima.com/resources/download/2017-trust-services-criteria)
- [CCPA/CPRA Full Text – California AG](https://oag.ca.gov/privacy/ccpa)
- [NIST Privacy Framework v1.1](https://www.nist.gov/privacy-framework)
- [NIST SP 800-188 – De-Identifying Government Data Sets](https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-188.pdf)
- [IAPP US State Privacy Law Tracker](https://iapp.org/resources/article/us-state-privacy-legislation-tracker/)
