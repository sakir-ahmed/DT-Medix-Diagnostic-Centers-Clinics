# ROADMAP: DT Medix

> The AI builds **one phase at a time**, runs tests, and stops. Each phase must satisfy the Definition of Done in `architecture.md`.
> **First sellable release = Phases 0 to 7.** Phases 8 to 12 are later modules.

Mark finished phases with `[x]`.

---

## Phase 0: Foundation & UI Kit

- [x] Docker Compose (nginx, php-fpm with Chromium, mysql, redis, queue, scheduler, reverb), `.env.example`
- [x] Branch scoping: `BelongsToBranch`, branch-aware jobs, channels and storage
- [x] Architecture test that fails when a branch-owned model lacks the trait
- [x] Inertia + Vue 3 + Tailwind v4 tokens (`design.md`), Sonner, dark mode, SSR on public pages only
- [x] Localization middleware, `en` / `bn` files, language switcher
- [x] Staff auth (username / email / mobile), OTP service, rate limits, idle timeout, optional TOTP for owners
- [x] Spatie Permission without teams, one role set per installation, hidden `super-admin` seeded from `.env`
- [x] Activity log, `patient_access_logs`, encrypted backups, center export command
- [x] **Shared UI kit** from `design.md` section 22 (dialogs, forms, tables, filters, charts, empty and error states) with `AppIcon` and `NoEmojiTest` in CI
- [x] OpenAPI docs generator at `/api-docs`
- [x] A hidden `/admin/ui-kit` showcase page that renders every component in light, dark, `en` and `bn`

**Acceptance:** `migrate:fresh --seed` works; branch isolation tested (a branch user cannot see another branch's data); each role sees only its menu; the UI-kit page renders without errors; a form with server errors shows messages under fields and focuses the first one.

## Phase 1: Branches, Staff, Doctors & Patients

- [x] Branch CRUD and branch scoping, center settings
- [x] Staff and doctors (BMDC number, specialty, fees, signature image), weekly schedules and leave
- [x] Patients: registration, patient ID, duplicate detection, family linking by mobile, allergy alerts, photo, search by mobile, name or ID
- [x] `PatientBanner` and the patient access log
- [x] Referrer records (master data only)

**Acceptance:** search returns in under 300 ms on 500,000 seeded patients; duplicate warning works; opening a patient writes an access log row; cross-branch tests pass.

## Phase 2: Test Catalog, Packages & Report Templates

- [x] Departments, tests, parameters, units, result types, reference ranges by age and sex, critical limits
- [x] Packages, outsourced test flag, optional branch price lists
- [x] Report template builder: table templates (pathology) and narrative templates (imaging) with quick phrases
- [x] Message templates (English and Bangla) with SMS segment counter

**Acceptance:** the correct reference range is chosen for age and sex (tested with edge ages); templates render in a preview; a package expands into its tests.

## Phase 3: Appointments & Reception Billing (cash)

- [x] Appointment booking, token numbers, doctor queue, status flow, follow-up window
- [x] Reception counter screen from `design.md` section 6
- [x] Test orders and invoices with price snapshots, discount rules and approval, numbering under lock
- [x] Cash payment, partial payment, due calculation, money receipt
- [x] Invoice and receipt printing (A5 and 80mm), sample labels with Code 128
- [x] Cancel and refund with approval and reasons

**Acceptance:** a returning patient is billed in under 90 seconds; invoice numbers never duplicate under concurrent requests (tested); the due is always calculated, never typed.

## Phase 4: Sample Collection & Lab Processing

- [x] Sample IDs, collection, receive, reject with reason and recollect
- [x] Department worklists with TAT countdown, urgent pinning, scan to open
- [x] Result entry grid with flags, previous results, delta warning, calculated parameters, critical acknowledgement
- [x] Imaging and narrative result editor with attachments
- [x] Real-time worklist updates through Reverb (IDs only)

**Acceptance:** keyboard-only entry of a 20-parameter test works; flags match reference ranges (tested); critical values cannot proceed without acknowledgement; results store range and flag snapshots.

## Phase 5: Verification, Reports & Release

- [ ] Verification queue, sign with name and BMDC number, return to technician with comment
- [ ] Separation rule (entered-by cannot verify, configurable)
- [ ] A4 PDF reports via Browsershot, bilingual fonts, QR verification page, header options
- [ ] Release rules (hold until paid, override, always) in `ReleaseReportAction`
- [ ] Amendments with versioning, "AMENDED REPORT" print, immutable released versions

**Acceptance:** an unverified or unpaid-held report cannot be released through any route (web or API, tested); amendments keep every version; a report PDF renders in under 5 seconds; Bangla patient names render correctly in the PDF.

## Phase 6: Payments, Dues & Collection Tracking

- [ ] bKash, Nagad (or manual TrxID fallback) and SSLCommerz with server-side verification and idempotency
- [ ] Split payments, bank transfer entry
- [ ] Due list with aging, due collection screen, due reminders
- [ ] Cash shift open and close with expected versus counted cash
- [ ] Collection reports by user, counter, shift, method and branch

**Acceptance:** duplicate gateway callbacks never double-pay; the sum of collection report rows equals the sum of payments exactly; closing a shift shows the cash difference.

## Phase 7: Notifications & Patient Delivery

- [ ] `MessageChannel` with `SmsChannel` (BdBulkSMS) and `WhatsAppChannel` (Cloud API templates), SMS fallback
- [ ] Appointment confirmation and reminder, sample collected, report ready, payment receipt, owner daily summary
- [ ] Secure report links with OTP or PIN, expiry, rate limiting, access logging
- [ ] Minimal patient portal: login by OTP, reports, report verification page
- [ ] Tests that no message contains results or diagnoses

**Acceptance:** a report-ready message contains only a notice and a link; expired or wrong-OTP access is refused and logged. **This completes the first sellable release.**

## Phase 8: Doctor Commission & Referral Tracking

- [ ] Commission rules (percent or fixed, by referrer, department, test, package) with priority order and effective dates
- [ ] Consultant fee share for visiting doctors
- [ ] Accrual on paid or billed basis (configurable), reversal on refund, rule change log
- [ ] Statements, payouts, payable balances, locked paid statements
- [ ] Referral performance reports; doctor's own commission statement

**Acceptance:** commission amounts match hand-calculated examples for every rule type (tested); refunds reduce commission; a paid statement cannot be edited; only permitted roles can see commission data.

## Phase 9: Patient History, Trends & Portal Extras

- [ ] Patient timeline, documents, side-by-side report comparison
- [ ] Parameter trend charts with reference band
- [ ] Consultation notes, advice sheet, advised tests converting into a test order
- [ ] Portal: appointment booking, due payment, public booking page with Turnstile
- [ ] Token display screen

**Acceptance:** a trend chart for a parameter across five visits matches stored results; booking from the public page respects slots.

## Phase 10: Owner Dashboard, MIS Reports & Expenses

- [ ] Dashboard: patients, billed, collected, dues, department revenue, top tests, TAT and sample rejection, commission payable
- [ ] Alerts: TAT breaches, critical results waiting, overdue dues
- [ ] Reports with PDF and Excel export following `design.md` section 17
- [ ] Simple expenses and staff shifts, net result estimate

**Acceptance:** dashboard totals equal report totals exactly; the dashboard loads in under 2 seconds on one year of seeded data.

## Phase 11: Import Wizard & Onboarding

- [ ] Import wizard for patients, doctors, referrers, tests and opening dues with dry run, row-level error report and safe re-run
- [ ] Center onboarding checklist and downloadable import templates

**Acceptance:** importing the same file twice creates no duplicates; a dry run saves nothing; the row-level error report lists each failing row with a reason.

## Phase 12: Home Collection, Sample Transfer & Polish

- [ ] Home collection requests, assignment, status, collector cash reconciliation
- [ ] Sample transfer manifests between branches, outsourced test tracking
- [ ] API review for future mobile apps, OpenAPI examples, contract tests that keep `v1` stable
- [ ] Security review (access logs, encryption, backups restore drill), activity log viewer, accessibility pass
- [ ] User guide (English and Bangla), onboarding guide for Datatech staff, deployment guide

**Acceptance:** full test suite green, Pint and Larastan clean, a restore drill passes, and a mobile developer can build against the OpenAPI docs without asking backend questions.

---

## Phase 13: HR, Attendance & Payroll

- [x] Employees, work shifts, holidays, leave requests with approval
- [x] Attendance: manual entry, ZKTeco ADMS push endpoint, CSV file import, daily calculation (late, early, overtime), manual edits protected
- [x] Monthly attendance sheet and summary report
- [x] Payroll runs per branch and month, payslips with snapshots, finalize (immutable) and mark paid, payroll register and printable payslip
- [x] Permissions, navigation, English and Bangla, demo data, cross-branch tests

**Acceptance:** device punches are deduplicated; the same punch sent twice creates one record; manual attendance is never overwritten by device data; a finalized payroll run cannot change; a branch user gets 404 for another branch's employees, attendance and payroll.

---

## Later Modules (not in this project scope)

Inpatient (IPD) and bed management, pharmacy, corporate and insurance billing, reagent inventory, analyzer interfacing, PACS viewer, full accounting, native mobile apps, offline mode.
