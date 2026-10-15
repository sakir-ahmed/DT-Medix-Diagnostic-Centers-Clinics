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
| 3 | Appointments and reception billing (cash) | **In progress, not committed** (about 30% done, backend only) |
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

## 3. Phase 3 work in progress (uncommitted files)

These files exist in the working tree but are **not committed and not tested**:

- Migration `database/migrations/2026_10_18_000001_create_reception_tables.php`: `appointments`, `orders`, `invoices`, `samples`, `order_items`, `payments`, `discount_requests`, `refunds`.
- Enums: `AppointmentStatus` (with allowed transitions), `InvoiceStatus`, `OrderItemStatus`, `SampleStatus`, `PaymentMethod`, `ApprovalStatus`.
- Models: `Appointment`, `Order`, `OrderItem`, `Invoice`, `Sample`, `Payment`, `DiscountRequest`, `Refund`.
- Services:
  - `Services/Billing/DocumentNumbers.php`: invoice `INV-BR01-26-000123`, receipt `MR-BR01-26-000045`, sample `BR01-261015-0042`, all through `SequenceGenerator` under a row lock.
  - `Services/Billing/InvoiceCalculator.php`: recalculates subtotal, discount (allocated to items as `net_amount`), VAT and service charge (if enabled), total, paid, refunded, due and status. The due is never typed.
  - `Services/ApprovalService.php`: manager approval by login plus approval PIN and a permission, rate limited, error `APPROVAL_INVALID`.
  - `Services/SlotService.php`: free slots from schedules minus leave, booked slots and `max_patients`.
- Actions: `Appointments/BookAppointmentAction.php` (lock on doctor, `SLOT_UNAVAILABLE`, token per doctor, branch and day, follow-up type and fee), `Appointments/ChangeAppointmentStatusAction.php` (transitions, timestamps, `next()` for the doctor queue).
- Event `Events/AppointmentUpdated.php` (broadcast with IDs only).

### Phase 3: remaining tasks
1. **Billing actions** in `app/Actions/Billing/`:
   - `CreateOrderAction`: patient, tests and packages (package price allocated across its tests), referrer, urgency, optional appointment, price snapshots via `PriceResolver`, samples grouped by sample type and container, invoice number, discount check (above `discount_limit_percent` needs `discounts.approve` or an approver through `ApprovalService`, else `DISCOUNT_LIMIT_EXCEEDED` or a pending `DiscountRequest`), referrer discounts and free cases need a reason, initial payments, recalculation. All in one `DB::transaction()`.
   - `CollectPaymentAction`: one or more lines, `PAYMENT_EXCEEDS_DUE`, `INVOICE_ALREADY_PAID`, money receipt number per line.
   - Discount request and decision actions (activity log).
   - `CancelItemsAction`: items not yet collected cancel directly; collected items need approval (`cancellations.approve`) and follow `collected_samples_refundable`; excess paid creates a pending refund.
   - Refund request and decision (`refunds.approve`), only up to the overpaid amount (`InvoiceCalculator::overpaid()`), activity log.
2. **Form requests, admin controllers and routes** (`routes/admin.php`): appointments (day view, slots, book, status), doctor queue (`admin.doctor.queue`, next patient), reception counter (`admin.reception.index`), orders index and show, payments, cancel, refunds, approvals page (`admin.approvals.index`).
3. **API** (`routes/api_v1.php`, branch routes under `/branches/{branch}`): `/doctors/{doctor}/slots`, `/appointments` (idempotent), `/appointments/{id}/status`, `/doctor/queue`, `/orders` (idempotent), `/orders/{order}`, `/orders/{order}/cancel`, discount requests, `/invoices/{invoice}/payments` (idempotent), `/refunds`, `/invoices/{invoice}/print?format=a5|thermal` (PDF).
4. **Printing:** Blade print views for invoice (A5 and 80mm), money receipt and sample labels (Code 128 with `picqer/php-barcode-generator`, portal QR with `endroid/qr-code`); "Save and print" prints all three. PDF for the API through spatie/laravel-pdf (use `Pdf::fake()` in tests).
5. **Vue pages:** reception counter (design.md section 6, shortcuts `/`, F2, F3, F4, F9, Esc), appointments day view and queue list, doctor "My queue", orders list and detail, approvals.
6. **Translations** for every new key in `lang/en` and `lang/bn`.
7. **Tests:** booking and slots, follow-up fee, status flow, order creation with package allocation and price snapshot, discount limit and approval, partial payment and calculated due, `PAYMENT_EXCEEDS_DUE`, cancel and refund flows, cross-tenant and cross-branch 404 for every endpoint, and an **invoice number concurrency test** (suggested: forked processes against a temporary SQLite file, in a separate `concurrency` group).
8. Run Pint, PHPStan, the full suite, mark Phase 3 `[x]` in `docs/roadmap.md`, commit and push.

Phases 4 to 12 follow `docs/roadmap.md` unchanged.

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
- The Reverb client (`resources/js/lib/echo.js`) exists but no page subscribes yet; the notification bell reads a `notifications` prop that is not shared yet.
- Dashboard (`Admin/Dashboard`) is a setup checklist placeholder until Phase 10.
- Patient record tabs other than Overview show an empty state until Phases 3 and 9.
- `.agents/` and `.junie/` folders come from Laravel Boost; Boost skills could not be installed into `.claude/skills` because of sandbox restrictions.
