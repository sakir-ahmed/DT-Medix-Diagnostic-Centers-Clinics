# DT Medix

**Cloud management system for diagnostic centers and clinics in Bangladesh.**
Developed by **Datatech BD Ltd**, Dhaka.

DT Medix connects the whole diagnostic workflow in one place:

```text
Patient -> Appointment -> Test Order -> Lab Processing -> Report -> SMS / WhatsApp -> Payment
```

It also covers doctor commission, referral tracking, collection tracking, due payments, report templates and patient history. The interface is bilingual (English and Bangla). Built as a multi-tenant SaaS: many clinics, each with their own branches, on one platform.

> This project is built phase by phase from the specification in `docs/`. Start with `docs/roadmap.md`.

---

## Features

- **Patients:** registration, duplicate detection, family linking, allergy alerts, fast search
- **Appointments:** doctor schedules, token queue, follow-up rules, reminders
- **Test orders and billing:** fast reception counter, packages, discounts with approval, invoices, money receipts, sample barcode labels
- **Lab processing:** worklists with turnaround tracking, result entry with automatic flags, critical value acknowledgement
- **Reports:** table and narrative templates, verification and digital signature, amendments with versioning, A4 PDF with QR verification
- **Payments and dues:** cash, bKash, Nagad, SSLCommerz, partial payments, due list and reminders, cash shift closing
- **Collection tracking:** by user, counter, shift, method and branch
- **Doctor commission and referral tracking:** configurable rules, statements, payouts, referral performance
- **Patient history:** timeline, parameter trend charts, report comparison
- **Notifications:** SMS (BdBulkSMS) and WhatsApp Business, secure report links with OTP, no results in message text
- **Owner dashboard:** patients, billed vs collected, dues, department revenue, turnaround, commission payable
- **Platform console (Datatech):** tenants, plans, 30-day trial, module toggles, import wizard, impersonation with audit
- **Mobile-ready API:** documented, versioned `/api/v1` for future apps (the apps are a separate project)

Full scope and business rules: `docs/prd.md`.

---

## Tech Stack

| Area                  | Technology                                                                  |
| --------------------- | --------------------------------------------------------------------------- |
| Backend               | Laravel 13.x, PHP 8.3+                                                      |
| Database              | MySQL 8 (shared database, `tenant_id` isolation)                            |
| Cache, session, queue | Redis                                                                       |
| Frontend              | Inertia.js v2, Vue 3, Tailwind CSS v4, shadcn-vue                           |
| Icons and charts      | Font Awesome Free, Chart.js                                                 |
| Real-time             | Laravel Reverb                                                              |
| Auth and roles        | Laravel Sanctum, Spatie Permission (teams)                                  |
| Audit and backup      | Spatie Activitylog, Spatie Backup                                           |
| PDF, barcode, import  | spatie/laravel-pdf (Browsershot), picqer barcode, endroid QR, Laravel Excel |
| Messaging             | BdBulkSMS, WhatsApp Business Cloud API                                      |
| Testing and quality   | Pest, Laravel Pint, Larastan                                                |
| Containers            | Docker and Docker Compose                                                   |

---

## Documentation

AI coding agents read `CLAUDE.md` first.

| File                   | Purpose                                                                              |
| ---------------------- | ------------------------------------------------------------------------------------ |
| `docs/prd.md`          | Product requirements: roles, modules, business rules, scope, plans                   |
| `docs/architecture.md` | Stack, structure, coding rules, multi-tenancy, health data rules, Definition of Done |
| `docs/api.md`          | API conventions, endpoints, payment gateways, real-time channels                     |
| `docs/design.md`       | Design system, reception, lab and report screens, icons, sizing, tables              |
| `docs/roadmap.md`      | Build phases with acceptance criteria                                                |
| `docs/ui-preview.html` | Static visual mockup of the intended UI (open in a browser)                          |

---

## Getting Started

### Requirements

- Docker and Docker Compose, Git
- Node.js 20+ and npm (only if you run the frontend outside Docker)

### Setup

```sh
git clone <repository-url> dt-medix
cd dt-medix

cp .env.example .env
# Edit .env: database, Redis, mail, BdBulkSMS, WhatsApp, bKash, Nagad, SSLCommerz

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run dev
```

The service name `app` follows the Docker Compose file created in Phase 0; adjust if yours differs.

The platform administrator is created by the seeder from `.env` (never commit real values):

```text
PLATFORM_ADMIN_NAME=
PLATFORM_ADMIN_EMAIL=
PLATFORM_ADMIN_PASSWORD=
```

For local development, the seeder also creates two demo tenants so tenant isolation can be tested.

### Background processes

| Process                                                    | Command                     |
| ---------------------------------------------------------- | --------------------------- |
| Queue worker                                               | `php artisan queue:work`    |
| Scheduler (backups, reminders, trial expiry, report links) | `php artisan schedule:work` |
| Real-time server                                           | `php artisan reverb:start`  |

---

## Development Workflow

```sh
php artisan test            # or ./vendor/bin/pest
./vendor/bin/pint           # code style
./vendor/bin/phpstan        # static analysis
php artisan migrate:fresh --seed
```

Rules:

- Build one roadmap phase at a time; finish its acceptance criteria and the Definition of Done in `docs/architecture.md`.
- Every feature has Pest tests, including authorization, branch isolation and **cross-tenant isolation**.
- Never put patient data in logs, SMS or WhatsApp text, URLs or broadcast payloads.
- No emojis anywhere. Icons come from Font Awesome through `AppIcon`.
- Every text key exists in both `lang/en` and `lang/bn`.

---

## Roles

Tenant roles: `owner`, `branch-manager`, `receptionist`, `sample-collector`, `lab-technician`, `pathologist`, `doctor`, `accountant`. Platform role: `super-admin`. Patients use a separate portal login.

---

## Deployment Notes

- HTTPS only; `APP_ENV=production`, `APP_DEBUG=false`.
- Run the queue worker, scheduler and Reverb under a process supervisor.
- The PHP image must include Chromium and the Inter and Noto Sans Bengali fonts for PDF reports.
- Schedule encrypted daily backups and test a restore before going live.
- Confirm data protection, health records, referral commission and VAT or invoice rules with a legal advisor and the client's accountant.

---

## Support

Datatech BD Ltd, Dhaka, Bangladesh.

## License

Proprietary. All rights reserved by Datatech BD Ltd. Not for redistribution.
