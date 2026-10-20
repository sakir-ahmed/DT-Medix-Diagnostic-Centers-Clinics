# DT Medix: Development Handover

Date: 16 Oct 2026. Branch: `main`. Last pushed commit: `470ab21` (Phase 2).

This note is for the next developer or AI agent. Read `CLAUDE.md` and the files in `docs/` first (prd, architecture, api, design, roadmap). The roadmap in `docs/roadmap.md` marks finished phases with `[x]`.

---

## 1. Status at a glance

| Phase | Title | State |
| ----- | ----- | ----- |
| 0 | Foundation, tenancy and UI kit | Done, tested, pushed (`e87e003`) |
| 1 | Branches, staff, doctors and patients | Done, tested, pushed (`7251ac9`) |
| 2 | Test catalog, packages and report templates | Done, tested, pushed (`470ab21`) |
| 3 | Appointments and reception billing (cash) | Done in the working tree, not committed |
| 4 to 12 | Lab, reports, payments, messaging, commission, history, dashboard, subscription, home collection | Not started |

Test suite at the last commit: **165 tests passing** (`php artisan test`), plus the separate performance test (`php artisan test --group=performance`, 500,000 patients searched in under 300 ms). Pint and PHPStan (level 5) were clean.

---

## 2. What is built

### Phase 0: foundation
- **Tenancy:** `tenants` table, `TenantContext` (scoped singleton), `BelongsToTenant` and `BelongsToBranch` traits, `ResolveTenant` middleware (host `{slug}.TENANCY_BASE_DOMAIN`, custom domain, or the signed-in user on central hosts), `EnsureKnownHost` (unknown subdomains return 404).
- **Queued jobs** carry `tenant_id` in the payload and restore it (`app/Providers/TenancyServiceProvider.php`).
- **Cache, Redis keys and storage paths** are prefixed through `TenantContext::key()` and `TenantContext::path()`; files live on the private `local` disk under `tenants/{id}/`.
- **Roles and permissions:** Spatie Permission with teams (`tenant_id`). Permission catalogue and role defaults in `app/Support/Permissions.php`. Platform `super-admin` uses team ID `0` (`User::PLATFORM_TEAM_ID`).
- **Auth:** staff login by username, email or mobile (`AuthenticateStaffAction`, `LoginRequest`), throttling, idle timeout (`IdleTimeout`, default 15 minutes, warning dialog in the UI), optional TOTP two-step verification, `OtpService` (hashed code, 5 minute TTL, 5 sends per hour per IP and mobile, 5 attempts), Sanctum API tokens with ability `staff`.
- **Platform console** `/platform`: tenant list, create (owner, main branch, roles, defaults), plan, limits, modules, trial dates, suspend and resume, impersonation with banner and audit, data export (`tenant:export` command, zip on the private disk).
- **PlanGate** (`app/Services/PlanGate.php`): branch and user limits, module toggles, message credits. Errors `PLAN_LIMIT_REACHED`, `MODULE_DISABLED`. `EnsureTenantIsActive` handles `TENANT_SUSPENDED` and `TENANT_READ_ONLY`.
- **API conventions:** JSON envelope (`app/Http/Responses/ApiResponse.php`), error rendering in `bootstrap/app.php`, `DomainException` with stable error codes, `EnsureIdempotency` middleware (alias `idempotent`, reserve-first, 24 hours), OpenAPI by Scramble at `/api-docs`.
- **Activity log** (`App\Models\Activity`, tenant-scoped) and `patient_access_logs`.
- **Backups:** spatie/laravel-backup scheduled in `routes/console.php`, encrypted with `BACKUP_ARCHIVE_PASSWORD`.
- **Frontend:** Inertia v3, Vue 3, Tailwind v4 tokens (`resources/css/app.css`), dark mode, `en`/`bn` translations shared as Inertia props, language switch, Font Awesome through `AppIcon`, vue-sonner toasts (`resources/js/lib/notify.js`).
- **UI kit** in `resources/js/components/ui-kit/` (all components from design.md section 22) and the hidden showcase `/admin/ui-kit`.
- **Docker Compose** (`docker-compose.yml`, `docker/`), GitHub Actions CI (`.github/workflows/ci.yml`).

### Phase 1: branches, staff, doctors, patients
- Branch CRUD with plan limit and typed confirmation to deactivate; tenant settings page (center details and business rules stored in `tenants.settings`, defaults in `Tenant::DEFAULT_SETTINGS`).
- Staff accounts (one role, branches, plan user limit, managers limited to their branches and roles, role changes logged).
- Doctors (BMDC, fees, follow-up window, private signature image, weekly schedules per branch with overlap check, leave days).
- Patients: gapless `P-YY-NNNNNN` codes (`SequenceGenerator`), duplicate detection (same mobile plus similar name, "Use existing"), family linking by mobile, allergies, private photo, encrypted NID, search by mobile, name prefix or ID, access log on every view, `PatientBanner`.
- Referrers (master data, encrypted payment account visible only with `commissions.view`).
- Admin pages and `/api/v1` endpoints for patients, doctors, referrers; command palette search.

### Phase 2: catalog
- Departments, tests, parameters (number, text, choice, calculated with validated formulas), reference ranges by sex and age band (unit days, months or years; min inclusive, max exclusive) with critical limits, outsourced flag, optional branch price lists.
- `ReferenceRangeResolver`, `ResultFlagger` (server) and `resources/js/lib/flags.js` (client, same rules).
- Packages expanding into tests (`admin.tests.lookup` returns tests and packages for the counter).
- Report templates (table and narrative) with quick phrases and live preview.
- Message templates in English and Bangla with a placeholder whitelist per message type (`App\Enums\MessageKey`, no patient names or results), SMS segment counter.
- Tenant defaults seeded on creation (`SeedTenantDefaultsAction`): departments, two report templates, all message templates.

---

## 3. Phase 3: appointments and cash billing

Implemented in the working tree. Not committed yet. Phases 4 to 12 are unchanged.

- Appointments: day view, free slots, booking, token numbers, follow-up fee, status flow, doctor queue with Next patient. The day view and the queue subscribe to `AppointmentUpdated` (IDs only) when Reverb is configured.
- Cash billing: `CreateOrderAction`, `CollectPaymentAction`, discount request and decision, `CancelItemsAction`, refund request and decision. Money goes through `App\Support\Money`. Over-limit discounts wait for approval unless the cashier can approve or a manager passes login and PIN. Collected items cancel only when `collected_samples_refundable` is on and `cancellations.approve` is verified (`COLLECTED_NOT_REFUNDABLE` otherwise).
- Package price is split across its tests. A test appears once; the package wins over a standalone line.
- Saving an order inserts pending sample rows and prints Code 128 labels. Collection stays in Phase 4.
- Admin screens: reception counter (cash only, change calculator, previous due shown and not collected), appointments, doctor queue, orders, approvals, refunds. Patient Visits, Tests, and Billing tabs are filled. Documents and Timeline stay empty.
- Print: A5, 80mm, money receipt, and sample label. No portal QR. The API returns a PDF (`spatie/laravel-pdf`).
- Tests cover booking, follow-up fee, status flow, package allocation, discount approval, partial payment, `PAYMENT_EXCEEDS_DUE`, cancel and refund, cross-tenant and cross-branch 404, and invoice numbers under forked processes (`concurrency` group). Pint and PHPStan were clean on this tree.

Left for later: sample collection and lab work, payment gateways, due collection, shifts, SMS, the patient portal and its QR, commission, the week view, and consultation-fee collection.

---

## 4. Conventions to keep

- Flow per feature: migration, model (with `BelongsToTenant`, and `BelongsToBranch` when it has `branch_id`), Form Request, Action, Admin and API controllers calling the same Action, Vue page, tests.
- Architecture tests fail if a model with `tenant_id` lacks `BelongsToTenant` or a model with `branch_id` lacks `BelongsToBranch` (exceptions go in `config/tenancy.php` `cross_branch_models`).
- Money: `decimal(12,2)`, string arithmetic through `App\Support\Money` (bcmath), API returns strings.
- Business errors: `throw DomainException::make('ERROR_CODE', $status)`. The API gets the JSON envelope; Inertia requests get a flash error toast and `error_code` in the session.
- Every user-facing string goes in both `lang/en/*.php` and `lang/bn/*.php`. `TranslationParityTest` checks both files have the same keys and that every `t('...')` key used in Vue exists.
- No emojis or Unicode symbol icons anywhere (`NoEmojiTest`). Icons only through `AppIcon`; add new Font Awesome icons to `resources/js/lib/icons.js`.
- Never `window.alert/confirm/prompt`; use `useConfirm()` (levels simple, reason, approval, typed) and `ConfirmHost`.
- Inertia pages need a single root element. Use `DataTable`, `FilterBar`, `SlideOver`, `FormField` and the other kit components.
- Pitfall: `Job::dispatch()` inside `TenantContext::run()` dispatches after the closure returns (PendingDispatch destructor). Use `Bus::dispatch(new Job(...))` there.
- In tests, `withToken()` persists for later requests in the same test; call `withoutToken()` before switching back to `actingAs()`.

---

## 5. Running the project

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

- Set `PLATFORM_ADMIN_EMAIL`, `PLATFORM_ADMIN_PASSWORD` and `DEMO_PASSWORD` in `.env` before seeding. Without them the platform admin and demo tenants are skipped.
- Demo users (password `DEMO_PASSWORD`): `demo.owner`, `demo.manager`, `demo.reception`, `demo.collector`, `demo.tech`, `demo.pathologist`, `demo.doctor`, `demo.accountant` (second tenant uses the `citycare.` prefix).
- `.env.example` targets Docker (MySQL 8, Redis). The local `.env` used during development is SQLite with file cache and sessions; tests use in-memory SQLite.
- Quality checks: `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`, `php artisan test`, `php artisan test --group=performance`.

---

## 6. Known gaps and notes

- Inertia **v3** is used because Inertia v2 does not support Laravel 13 (docs mention v2).
- SSR entry (`resources/js/ssr.js`) builds, but SSR is disabled by default (`INERTIA_SSR_ENABLED=false`); it is meant for public pages only.
- The Reverb client (`resources/js/lib/echo.js`) is used by the appointments day view and the doctor queue. The notification bell still reads a `notifications` prop that is not shared yet.
- Dashboard (`Admin/Dashboard`) is a setup checklist placeholder until Phase 10.
- Patient Visits, Tests, and Billing are filled. Documents and Timeline stay empty until Phase 9.
- `.agents/` and `.junie/` folders come from Laravel Boost; Boost skills could not be installed into `.claude/skills` because of sandbox restrictions.
