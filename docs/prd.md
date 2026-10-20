# PRODUCT REQUIREMENTS: DT Medix

> **This is the file that changes for each product.** Keep the same section headings.

---

## 1. Vision

**DT Medix** is a cloud management system for small diagnostic centers and clinics in Bangladesh, a product of **Datatech BD Ltd** (datatechbd.com) — _"Software that runs business."_ Today these centers run patient registration, appointments, test orders, reports, payments, doctor commissions and patient history through disconnected tools (paper registers, spreadsheets, separate billing software). DT Medix connects them in one flow:

```text
Patient -> Appointment -> Test Order -> Lab Processing -> Report -> SMS / WhatsApp -> Payment
```

and adds doctor commission, referral tracking, collection tracking, due payments, report templates and patient history.

**Company:** Datatech BD Ltd, Dhaka.
**First customers:** standalone diagnostic centers and clinics with 5 to 30 beds. Large hospitals are not the first target.
**Languages:** English and Bangla (UI, receipts, messages). Clinical reports print in English by default, with Bangla patient names supported.
**Mobile apps:** native apps are a separate future project. This project exposes a documented `/api/v1` for them.

---

## 2. Business Model & Deployment _(decision)_

**One installation per center.** Datatech copies this codebase, creates that center's database, and points a web address at it. The app does not provision centers, and it does not bill Datatech's fee. If the center has several branches, those branches live in that same codebase and that same database. Datatech charges the customer outside this app (one-time or yearly, decided per deal).

There is no `tenant_id`, no shared database, no subdomain tenant resolver, no platform console, no impersonation, no trial, and no subscription ledger.

> Isolation, export and security rules are in `architecture.md` sections 11 and 12. Change this decision only if Datatech decides to build a shared SaaS; that changes the database design.

---

## 3. Roles

| Role               | Scope                         | Main needs                                                                                           |
| ------------------ | ----------------------------- | ---------------------------------------------------------------------------------------------------- |
| `owner`            | All branches of the center   | Dashboard, collections, dues, commissions, branch comparison, approvals, settings                    |
| `branch-manager`   | Assigned branch               | Daily operations, staff, discount and refund approval, cash closing review                           |
| `receptionist`     | Assigned branch               | Register patients, appointments, test orders, invoices, collect payments                             |
| `sample-collector` | Assigned branch               | Collect samples (counter and home), print labels                                                     |
| `lab-technician`   | Assigned departments          | Receive samples, enter results                                                                       |
| `pathologist`      | Assigned departments          | Verify, sign, amend reports (radiologists and cardiologists use the same role for their departments) |
| `doctor`           | Own patients and appointments | Queue, consultation notes, patient history, own commission statement                                 |
| `accountant`       | All branches (finance only)   | Collections, dues, commissions, payouts, expenses, financial reports                                 |

`super-admin` — hidden Datatech login on that server, seeded from `.env`.

**Referrers** (outside doctors, agents or institutions who refer patients) and **consultants** are records, not necessarily users. Referrers do not log in.
**Patients** are not staff. They use the patient portal (mobile OTP) and cannot reach `/admin`.

---

## 4. Modules

### A. Center Profile & Setup

- Center profile and logo, branches, document number formats, setup wizard with onboarding checklist.
- Import wizard (dry run, row-level errors, safe re-run) for patients, doctors and test catalogs from Excel.
- Datatech hand-over for a new center: deploy the installation, seed the `owner` and the hidden `super-admin` from `.env`, hand over the first owner login.

### B. Patients

- Registration: name, age (or date of birth), sex, mobile, address, blood group, optional NID, emergency contact, optional photo, notes and allergy alerts.
- **Patient ID** generated per center (`P-26-000123`); one mobile number may belong to several family members.
- **Duplicate detection** on mobile plus name similarity, with a clear warning and "Use existing".
- Search by mobile, name or patient ID in under 300 ms on 500,000 patients.
- Age shown as "32 Y 4 M". Every view of a patient record is written to the access log.

### C. Doctors & Appointments

- Doctor profiles: specialty, qualifications, BMDC registration number, consultation and follow-up fee, signature image.
- Weekly schedules per branch, slot length, leave days.
- Booking by reception (walk-in or phone), online booking through the public page, serial or token numbers.
- Status flow: `booked -> arrived -> in-consultation -> completed`, plus `no-show` and `cancelled`.
- Follow-up window (for example 7 days free or reduced fee, configurable).
- Doctor queue screen with "next patient" and waiting time.
- Simple **consultation notes and advice**, including "advised tests" that convert into a test order in one click, and a printable advice sheet.
- SMS or WhatsApp confirmation and reminder.

### D. Test Catalog & Packages

- Test master: code, name, department (pathology, imaging, cardiology and others), sample type and container, price, turnaround time (TAT), preparation instructions, report template, home-collection eligibility.
- **Parameters** per test with unit, result type (number, text, choice, calculated), and reference ranges by age and sex, plus critical limits.
- **Packages** (for example "Diabetes Panel") with a package price.
- Outsourced tests (processed by another lab) with vendor and cost.
- Price lists per branch (optional).

### E. Test Orders & Invoicing (reception)

- Order tests and packages for a patient, optionally linked to an appointment and a referrer.
- Urgency: routine or urgent.
- Invoice with discount (percent or fixed), referrer discount, free or charity cases with a reason, and VAT or service charge only if enabled for the center.
- Prints invoice, money receipt and sample labels in one action.
- Cancel and refund flows with approval.

### F. Sample Collection & Home Collection

- Sample IDs with Code 128 barcode labels; collection time and collector recorded.
- Receive, reject (with reason) and recollect samples.
- **Home collection:** request with address and time slot, assign a collector, status tracking, collect payment, and reconcile the collector's cash.
- **Sample transfer** between branches with a manifest (for collection centers that send samples to a central lab).

### G. Lab Processing

- Worklists per department and per branch, with TAT countdown and urgent pinning.
- Result entry with automatic flags (normal, high, low, critical), previous result display and delta warning, calculated parameters, remarks.
- Critical value acknowledgement (who was informed and when).
- Imaging and narrative departments use text templates, images and PDF attachments.
- Analyzer interfacing is out of scope; results are entered manually or pasted.

### H. Reports

- **Report templates:** table-based for pathology, narrative (Findings and Impression) for imaging, with the center's header, footer and signatures; quick phrases for common findings.
- Workflow: `draft -> entered -> verified (signed) -> released`. Released reports are immutable; corrections are **amendments** that create a new version with a reason.
- A4 PDF with verification QR code; "print without header" for letterhead.
- **Release rule** (per center): hold until fully paid, allow with manager override, or always allow.
- Delivery: print, secure link by SMS or WhatsApp, patient portal download.

### I. Payments, Dues & Collection Tracking

- Payment methods: cash, bKash, Nagad, card or SSLCommerz, bank transfer. Split and partial payments.
- **Due tracking:** due list with aging, due collection screen, SMS reminders, due summary per patient.
- **Collection tracking:** collections by receptionist, counter, shift, branch and method; cash shift open and close with expected versus counted cash; daily collection report.
- Refunds with approval and reason.
- Online due payment from the patient portal (bKash, Nagad, SSLCommerz).

### J. Doctor Commission & Referral Tracking

- **Referrers:** doctor, agent or institution with contact, bank or bKash details, and notes.
- **Commission rules:** percentage or fixed amount, by referrer, department, test or package. Priority: test rule, then department rule, then referrer default, then center default.
- **Consultant share:** a split of the consultation fee for visiting doctors.
- Accrual basis is configurable: on billed amount or on paid amount; after discount; excluding VAT. Accrues when the invoice is fully paid (default) and reverses on refund.
- **Payouts:** monthly statements per referrer or doctor, mark as paid with method and reference, payable balance, history. Paid statements are immutable.
- Reports: referral performance (patients, revenue, commission) by referrer and period.
- Visible only to roles with the `commissions.view` permission (owner and accountant by default).

### K. Patient History

- Patient record with timeline of visits, tests, reports, payments and notes.
- **Parameter trends** across visits (for example HbA1c) with reference range band; side-by-side comparison of two reports.
- Documents (scanned prescriptions, outside reports) attached to the patient.

### L. Notifications (SMS and WhatsApp)

- Channels: BdBulkSMS (SMS) and WhatsApp Business Cloud API, behind one `MessageChannel` interface with SMS fallback.
- Messages: appointment confirmation and reminder, sample collected, report ready (secure link), payment receipt, due reminder, owner daily summary.
- **No test results or diagnoses in message text.** Only a short notice and a secure link.
- Templates per center in English and Bangla with placeholders, patient opt-out.

### M. Owner Dashboard & Reports

Per day, week or month and per branch:

- Patients, appointments, tests ordered, billed amount, collected amount, dues outstanding
- Department revenue, top tests, collection by payment method
- Pending reports, reports past TAT, critical results waiting, sample rejection rate
- Commission and referral payable
- Expenses and estimated net result
- Alerts for overdue dues and TAT breaches

Exports to PDF and Excel. Dashboard totals must equal the report totals.

### N. Simple Expenses & Staff

- Expense entry by category with attachment, branch and date; staff profiles and shifts.
- Used for the net result on the dashboard. Full double-entry accounting is out of scope.

### O. Data Import & Onboarding

- Import wizard for patients, doctors, referrers, tests with parameters, and opening dues from CSV or Excel.
- Column mapping, **dry-run validation**, duplicate handling choices, row-level error report, safe re-run, and an import summary.
- Downloadable templates and a checklist for setting up a new center.

### P. Patient Portal & API

- Public booking page and patient portal (mobile OTP login): reports, due payment, appointments.
- Report verification page (shows only that a report is valid, with minimal details).
- Everything a future patient or staff app needs is available through `/api/v1` (see `api.md`), with auto-generated OpenAPI documentation.

---

## 5. Business Rules

| Topic                    | Rule                                                                                                                                                                       |
| ------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Patient ID**           | Sequential per center and year (`P-26-000123`). Never reused.                                                                             |
| **Invoice numbers**      | Sequential per branch and year with no gaps (`INV-BR01-26-000123`). Confirm legal requirements with the client's accountant.                                               |
| **Sample IDs**           | Unique per branch per day (`BR01-260115-0042`), generated under a lock.                                                                                                    |
| **Pricing**              | An invoice stores a price snapshot per test. Later price changes never change old invoices.                                                                                |
| **Discounts**            | Percent or fixed. Above the center's limit (default 10%) needs manager approval. Referrer discounts and free cases need a reason. Every override is logged.                 |
| **VAT / service charge** | Off by default and configurable per center and per test category. Confirm applicable rules with the client's accountant.                                                   |
| **Payments**             | Partial payments allowed; paid amount can never exceed the total; the due balance is always calculated, never typed.                                                       |
| **Refunds**              | Allowed only through a refund record with a reason and approval. Collected samples may be non-refundable by policy. Refunds reverse commissions.                           |
| **Cancel**               | Tests not yet collected can be cancelled. After collection, cancellation needs approval and follows the refund policy.                                                     |
| **Release rule**         | Configurable per center: hold until fully paid (default), manager override, or always release. A report can never be released before it is verified.                       |
| **Verification**         | The person who entered a result cannot verify the same result unless the center explicitly allows it.                                                                      |
| **Critical values**      | A critical result requires an acknowledgement (who was informed, when) before release.                                                                                     |
| **Immutability**         | Released reports are never edited. Amendments create a new version with a reason; old versions are kept.                                                                   |
| **Reference ranges**     | A result stores the range and flag used at entry time.                                                                                                                     |
| **Commission**           | Calculated on net amount after discount, excluding VAT, accrued when fully paid by default, reversed on refund. Rule changes are logged and apply only to future invoices. |
| **Messages**             | Never include results or diagnoses. Report links are signed, expire (default 30 days) and need OTP or PIN.                                                                 |
| **Records**              | Patient and clinical records are never hard-deleted. Patients can request export; anonymization follows the center's retention policy and legal advice.                    |
| **Access log**           | Opening a patient record or report is logged with user, time and IP.                                                                                                       |
| **Branch isolation**     | Non-owner users must never read another branch's data. Tested for every branch-scoped model and endpoint.                                                                  |

> Referral commission practices are ethically and legally sensitive. Confirm the applicable rules with the client or a legal advisor. The feature is optional, permission-protected and can be disabled per center.

---

## 6. Hardware

- 80mm thermal printer for invoices and receipts, A4 printer for reports, label printer (40x25mm or 50x30mm) for sample barcodes.
- Barcode scanner (keyboard-wedge), optional webcam for patient photo, tablet for sample collection.
- Optional large screen for the token display.

---

## 7. Non-Functional Requirements

- **Localization:** UI, receipts and messages in `en` and `bn`.
- **Performance:** patient search under 300 ms; invoice save under 1 second; single report PDF under 5 seconds; dashboard under 2 seconds for one year of data.
- **Security:** HTTPS only, encrypted sensitive fields, private file storage, access logs, rate-limited OTP and login, optional two-factor for owners and pathologists, idle timeout.
- **Backup:** daily encrypted backup of the database and files, tested restore, center data export.
- **Availability:** target 99.5% monthly; planned maintenance outside clinic hours.
- **Audit:** activity log for discounts, cancellations, refunds, report release and amendment, commission rule changes, payouts, role changes.
- **Browsers and devices:** latest Chrome and Edge, Android tablets and phones (Chrome), iOS Safari for the patient portal.
- **Connectivity:** the app shows a clear offline banner and never loses a form silently; full offline mode is out of scope.

---

## 8. Screen List (per role)

| Role                | Screens                                                                                                                                                                  |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| owner               | Dashboard, collections, dues, commissions and payouts, referral performance, branches, staff and roles, settings (release rule, templates), access log, approvals |
| branch-manager      | Branch dashboard, approvals (discount, refund, cancel), cash closing review, staff, doctors and schedules, test catalog (if allowed)                                     |
| receptionist        | Reception counter (search, order, invoice, pay), appointments, dues collection, shift open and close, patient list                                                       |
| sample-collector    | Collection worklist, home collection list, label printing                                                                                                                |
| lab-technician      | Receive samples, department worklist, result entry                                                                                                                       |
| pathologist         | Verification queue, report amendment, template management, critical result log                                                                                           |
| doctor              | My queue, consultation, patient history, my commission statement                                                                                                         |
| accountant          | Collections, dues, commissions and payouts, expenses, financial reports                                                                                                  |
| patient (portal)    | Login, reports, due payment, appointments                                                                                                                                |

---

## 9. Out of Scope

- **Inpatient (IPD) management:** beds, admission, discharge, ward and IPD billing. Clinics with 5 to 30 beds will ask for this; plan it as a later module.
- Pharmacy and medicine stock, operation theatre, and full EMR with e-prescriptions.
- Analyzer interfacing (HL7, ASTM) and PACS or DICOM viewing.
- Reagent and consumables inventory.
- Corporate and insurance panel billing.
- Full double-entry accounting, payroll payment processing, NBR e-filing.
- Telemedicine and video consultation.
- Native mobile apps (separate project).
- Full offline mode.
- Multi-tenant SaaS, a shared database, a platform console, subscription billing, trials, plan limits, impersonation.

---

## 10. Key User Stories & Acceptance Criteria

1. **Register patient.** _As a receptionist, I register a patient without creating duplicates._
   - Done when: entering an existing mobile and similar name shows a duplicate warning, and "Use existing" opens the existing record.
2. **Book appointment.** _As a receptionist, I book a doctor slot._
   - Done when: only free slots are offered, a token number is issued, a confirmation message is sent, and the doctor's queue updates within 3 seconds.
3. **Fast counter.** _As a receptionist, I bill a returning patient._
   - Done when: search, test selection, payment and "Save and print" take under 90 seconds, and invoice, receipt and sample labels print together.
4. **Sample tracking.** _As a lab technician, I scan a sample label._
   - Done when: scanning opens the right sample, status moves to Received, and a wrong-branch sample shows a clear message.
5. **Result entry.** _As a technician, I enter results quickly._
   - Done when: Tab and Enter move through fields, flags appear instantly with text and icon, critical values require acknowledgement, and previous results are visible.
6. **Verify and release.** _As a pathologist, I sign a report._
   - Done when: only verified reports can be released, the signer's name and BMDC number appear, and an entered-by user cannot verify their own result (unless allowed).
7. **Amendment.** _As a pathologist, I correct a released report._
   - Done when: a new version is created with a reason, the old version stays viewable, the printout shows "AMENDED REPORT", and the patient can be notified.
8. **Report delivery.** _As a patient, I get my report securely._
   - Done when: a message with a link (no results in the text) arrives, the link needs OTP or PIN, expires after the configured time, and each access is logged.
9. **Partial payment and due.** _As a receptionist, I take part payment now and the rest later._
   - Done when: the due is calculated automatically, shown on the next visit, collectable with a money receipt, and a held report is released once the due is cleared (if hold-until-paid is on).
10. **Collection tracking.** _As an owner, I see who collected what._
    - Done when: collections by user, shift, method and branch match the invoice payments exactly, and cash shift differences are shown.
11. **Commission.** _As an accountant, I pay a referrer._
    - Done when: the statement for the period lists each invoice, rule used, and commission; refunds reduce it; marking paid locks the statement.
12. **Referral performance.** _As an owner, I compare referrers._
    - Done when: patients, revenue and commission per referrer and period match the invoices.
13. **Patient history.** _As a doctor, I see a patient's history._
    - Done when: the timeline lists visits, tests and reports, a trend chart shows a chosen parameter over time with the reference band, and the view is access-logged.
14. **Import.** _As Datatech support, I import a clinic's old data._
    - Done when: a dry run reports errors per row without saving, the real import is repeatable without duplicates, and a summary lists created, skipped and failed rows.
15. **Branch isolation.** _As an owner, my branch staff see only their own branch._
    - Done when: automated tests prove that every branch-scoped model and endpoint returns 404 across branches for non-owner users.

---

## 11. Phasing

See `roadmap.md`. Build phase by phase; do not implement later phases early. The first sellable release is Phases 0 to 7.
