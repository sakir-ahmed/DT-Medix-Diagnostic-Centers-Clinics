# ARCHITECTURE & CODING RULES: DT Medix

> Shared engineering rules for Datatech products, adapted for DT Medix (multi-tenant SaaS handling health data). Sections 11 to 13 are specific to this product.
> If this file conflicts with the product's `prd.md`, **the PRD wins** for business rules and roles. This file wins for code structure.

---

## 1. Tech Stack

| Area                    | Choice                                                                                                                                      |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Framework               | Laravel 13.x (latest stable), PHP 8.3+. Check with `composer show laravel/framework`.                                                       |
| Database                | MySQL 8                                                                                                                                     |
| Cache / Session / Queue | Redis (`CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` = `redis`)                                                                       |
| Frontend                | Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4, shadcn-vue (Radix Vue)                                                            |
| Toasts                  | `vue-sonner`                                                                                                                                |
| Auth                    | Staff: username / email / mobile login. Patients: mobile OTP (separate `patient` guard). Spatie Permission with **teams** (tenant as team). |
| Real-time               | Laravel Reverb (Pusher protocol): lab worklists, doctor queues, alerts. Payloads carry IDs only.                                            |
| Audit                   | `spatie/laravel-activitylog`                                                                                                                |
| Backup                  | `spatie/laravel-backup` (daily, via scheduler)                                                                                              |
| Localization            | `en` and `bn` (`lang/en`, `lang/bn`)                                                                                                        |
| SMS                     | BdBulkSMS via `SmsService` (section 5)                                                                                                      |
| Testing                 | Pest                                                                                                                                        |
| Code quality            | Laravel Pint, Larastan (level 5 minimum)                                                                                                    |
| Containers              | Docker Compose: nginx, php-fpm, mysql, redis, queue worker, scheduler, reverb                                                               |
| Icons                   | Font Awesome Free via `@fortawesome/vue-fontawesome` (tree-shaken SVG), wrapped in `AppIcon`                                                |
| Charts                  | `chart.js` + `vue-chartjs` (one library only)                                                                                               |
| Multi-tenancy           | One app, shared MySQL database, `tenant_id` on every tenant-owned table (section 11)                                                        |
| PDF                     | `spatie/laravel-pdf` with the Browsershot (headless Chromium) driver                                                                        |
| Barcode / QR            | `picqer/php-barcode-generator` (Code 128), `endroid/qr-code`                                                                                |
| Excel import / export   | `maatwebsite/excel`                                                                                                                         |
| WhatsApp                | WhatsApp Business Cloud API (Meta) behind `MessageChannel`                                                                                  |
| API docs                | `dedoc/scramble` (OpenAPI at `/api-docs`)                                                                                                   |
| Bot protection          | Cloudflare Turnstile on public booking and OTP forms                                                                                        |

**Rendering**

- Public pages (`Web/` controllers): tenant landing page, online booking page, report verification page. Inertia SSR with dynamic meta tags. The patient portal after login is client-side only.
- Admin panel (`/admin`): client-side SPA, **SSR disabled**. No Filament.

---

## 2. Folder Structure

```text
app/
├── Actions/            # One class = one business operation (CreateOrderAction)
├── Services/           # External integrations + orchestration (SmsService, BkashService)
├── DataTransferObjects/# Only when an Action has 4+ inputs
├── Enums/              # OrderStatus, PaymentMethod ...
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      # Inertia controllers for the SPA (POS included)
│   │   ├── Api/V1/     # JSON API (see api.md)
│   │   └── Web/        # Public SSR pages
│   ├── Requests/       # Form Requests (all validation lives here)
│   └── Resources/      # API Resources
└── Models/             # Relationships, scopes, casts, LogsActivity. No business logic.
```

**No Repository layer.** Eloquent is the data layer. Add an interface only when there are two or more real implementations. In this product that applies to exactly two places: `PaymentGateway` (bKash, Nagad, SSLCommerz) in `app/Services/Payments/` and `MessageChannel` (SMS, WhatsApp) in `app/Services/Messaging/`.

---

## 3. Core Rules

1. **Thin controllers.** Validate (Form Request) → call an Action → return a response.
2. **Admin controllers and API controllers call the same Actions.** Never duplicate business logic.
3. **Strict types.** `declare(strict_types=1);`, typed parameters, return types and properties everywhere.
4. **No over-engineering.** No factories, abstractions or patterns without a concrete need today.
5. **Money:** store as `decimal(12,2)` in BDT. Never use floats. API returns money as strings (`"1250.00"`).
6. **Invoices, payments, commissions, sample status changes and report release run inside `DB::transaction()`.** Use `lockForUpdate()` on counters (invoice numbers, sample IDs) and on balances.
7. **Tenant and branch scoping:** every tenant-owned model uses `BelongsToTenant`, and branch-owned models also use `BelongsToBranch` (section 11). A user must never read another tenant's data, and non-owner users must never read another branch's data. Test both.
8. **All user-facing text uses translation keys** (`__('orders.created')`), never hard-coded strings.
9. **Secrets only in `.env`**, accessed via `config()`. Never call `env()` outside config files.
10. **No emojis anywhere** (UI, translations, SMS, receipts, seeders, comments, commits). Icons come from Font Awesome through `AppIcon`; see `design.md` section 23.
11. **Eager load relations** to avoid N+1. Add DB indexes on foreign keys and common filters (patient mobile, name, patient code, invoice number, sample ID).
12. **Health data rules apply everywhere** (section 12): no patient data in logs, SMS text, URLs or broadcast payloads.

---

## 4. Roles & Permissions

Spatie Permission with **teams enabled** (`teams => true`, team key `tenant_id`), so every role and assignment belongs to one tenant. Roles are defined in `prd.md` and seeded per tenant when the tenant is created. `super-admin` is a platform role (Datatech staff) that is not tied to a tenant.

Use permissions (`reports.verify`, `reports.release`, `invoices.discount`, `commissions.view`), not role names, in policies and `@can` checks. The root platform account comes from `.env` values (never hard-code a password).

---

## 5. Authentication, OTP & SMS

### Login (username, email or mobile)

```php
public function getCredentials(): array
{
    $login = trim($this->string('login')->toString());

    $field = match (true) {
        filter_var($login, FILTER_VALIDATE_EMAIL) !== false => 'email',
        preg_match('/^(\+?88)?01[3-9]\d{8}$/', $login) === 1 => 'mobile',
        default => 'username',
    };

    if ($field === 'mobile') {
        $login = normalize_bd_mobile($login); // -> 8801XXXXXXXXX, store mobiles in this format
    }

    return [$field => $login, 'password' => $this->input('password')];
}
```

Throttle login with `RateLimiter` (5 attempts per minute per login + IP).

### SmsService (transport only)

```php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $mobile, string $message): bool
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post(config('services.bdbulksms.url'), [
                'to' => $mobile,
                'message' => $message,
                'token' => config('services.bdbulksms.token'),
            ]);

        // Confirm the exact success format in the BdBulkSMS API docs.
        $sent = $response->successful() && str_starts_with($response->body(), 'Ok');

        $sent
            ? Log::info('SMS sent.', ['mobile' => $mobile])
            : Log::error('SMS failed.', ['mobile' => $mobile, 'status' => $response->status()]);

        return $sent;
    }
}
```

- **Never disable SSL verification** (`'verify' => false` is forbidden).
- Config in `config/services.php`: `bdbulksms.url`, `bdbulksms.token`.
- Send SMS through a queued Job so a slow gateway never blocks a request.

### OtpService (rules)

- Generate with `random_int(100000, 999999)`.
- Store **hashed** (`Hash::make`) in Redis cache, key `otp:{mobile}`, **TTL 5 minutes**.
- Send limit: `RateLimiter` keyed by **IP + mobile** (max 5 per hour). Not session-based; clearing cookies must not bypass it.
- Verify limit: max 5 wrong attempts per code, then invalidate the code.
- Delete the code after successful verification.

---

## 6. Logging

Use `Log` with PSR-3 levels and context arrays. Never log passwords, OTPs, tokens, card or wallet numbers, or any patient data (section 12).

```php
Log::info('Order created.', ['order_id' => $order->id, 'branch_id' => $order->branch_id]);
Log::warning('Stock below threshold.', ['ingredient_id' => $id]);
Log::error('Payment failed.', ['order_id' => $id, 'error' => $e->getMessage()]);
```

Do not log the stack trace in every message; the exception handler already does it. Log with `'exception' => $e` only when needed.

---

## 7. Activity Log & Backup

`LogsActivity` is a **trait**, not an interface:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'mobile', 'username'])
            ->logOnlyDirty();
    }
}
```

- Use `logOnly([...])`, never `logAll()` (it can log password hashes).
- Explicitly log sensitive actions with `activity()->causedBy($user)->performedOn($model)->log('order.voided')`.
- Backup: `backup:run --only-db` daily via scheduler to S3 or a mounted volume, `backup:clean` weekly, and notify on failure.

---

## 8. Localization

- Files: `lang/en/*.php` and `lang/bn/*.php`. Every key must exist in both.
- A middleware sets the locale from user preference → session → default `en`.
- Vue receives translations through Inertia shared props.
- Dates and numbers: format through one helper so Bangla digits can be toggled.

---

## 9. Frontend Rules

- `<script setup>` only. One component = one responsibility.
- Show feedback on every save/delete/error with `toast.success()` / `toast.error()`.
- Handle loading, empty and error states in every list and form.
- Inertia forms use `useForm`. Show server validation errors beside fields.
- Visual and interaction rules are in `design.md`. Dialogs, confirmations, validation messages, dashboards, charts, reports and tables **must** follow sections 12 to 22 there.
- Build the shared UI kit (`resources/js/components/ui-kit/`) first and reuse it. Never use `window.alert()`, `confirm()` or `prompt()`, and never build a one-off dialog, table or chart inside a page.
- Validation lives in Form Requests. Display errors with the `FormField` component, focus the first invalid field, and never clear the form on error.

---

## 10. Testing & Definition of Done

A feature is **done** only when all of these are true:

- [ ] Pest feature tests cover the happy path and main failure cases
- [ ] Authorization tested (allowed role passes, other role gets 403, other branch gets 404, **other tenant gets 404**)
- [ ] No patient data in logs, SMS or WhatsApp text, URLs or broadcast payloads (section 12)
- [ ] `./vendor/bin/pint` is clean and `./vendor/bin/phpstan` passes
- [ ] Both `en` and `bn` translations added
- [ ] Sensitive actions write an activity log entry
- [ ] `NoEmojiTest` passes (no emojis or symbol icons in code, lang files, views or docs)
- [ ] The UI Definition of Done checklist in `design.md` (section 22) passes
- [ ] Migrations run fresh (`migrate:fresh --seed`) without errors
- [ ] The acceptance criteria for the feature in `roadmap.md` are met

---

## 11. Multi-Tenancy (SaaS)

- **Model:** one application and one shared database. Every tenant-owned table has a non-null, indexed `tenant_id`, and unique keys include it (for example `unique(tenant_id, patient_code)`).
- **Tenant resolution:** a `ResolveTenant` middleware reads the host (`{slug}.dtmedix.example` or a custom domain). API requests take the tenant from the authenticated token. Never trust a `tenant_id` sent by the client.
- **Traits:** `BelongsToTenant` adds a global scope and fills `tenant_id` on create. `BelongsToBranch` adds branch scoping for non-owner users. An architecture test fails if a model with a `tenant_id` column lacks the trait.
- **Queues and scheduler:** every queued job, notification and listener carries the tenant ID and restores the tenant context before running. Per-tenant scheduled tasks loop over active tenants.
- **Isolation by prefix:** cache keys, Redis keys, Reverb channels (`private-tenant.{id}.branch.{id}.lab`) and storage paths (`tenants/{id}/...` on a private disk) all include the tenant ID.
- **Platform area (`/platform`)** for Datatech only: create tenants, plans, trial dates, module toggles, limits, suspend or resume, usage, and support impersonation. Impersonation shows a permanent banner and is written to the audit log.
- **Plan limits** are enforced in one place, a `PlanGate` service (branches, users, modules, message credits). It returns `PLAN_LIMIT_REACHED` or `MODULE_DISABLED`. Never check plan limits in scattered controllers.
- **Tests:** every model and every endpoint has a cross-tenant test (tenant A cannot read or write tenant B, and gets 404).
- **Export and exit:** a command exports all of one tenant's data (CSV or JSON plus files). Tenant deletion is scheduled and only runs after the retention period.

---

## 12. Health Data (PHI) & Clinical Record Rules

Patient data is sensitive health data. Design for least access, auditing and no leaks.

- **Never put patient data** (names, mobile numbers, NID, diagnoses, results) in logs, exception messages, error trackers, URLs, query strings, SMS or WhatsApp text, or Reverb and push payloads. Use IDs only.
- **Encrypt sensitive fields** such as NID and passport numbers with Laravel's `encrypted` cast. Searchable fields (name, mobile, patient code) stay plain so they can be indexed.
- **Files** (report PDFs, scanned documents, imaging attachments) live on a private disk and are served only through authorized controllers or short-lived signed URLs. Never use public storage.
- **Access log:** opening a patient record, report or history writes a row to `patient_access_logs` (user, patient, action, IP, time). Owners can review it. Bulk exports need a permission and are logged.
- **No hard deletes of clinical records.** Use soft delete or voiding with a reason.
- **Released reports are immutable.** A correction creates a new **version** (amendment) with a reason and an approver. Old versions stay viewable to authorized staff. This rule has its own tests.
- **Snapshots:** a result stores its value, unit, reference range and flag at the time of entry, so later changes to the test catalog never alter old reports.
- **Release rules** (verified, payment hold, critical value acknowledged) are enforced in `ReleaseReportAction`, never only in the UI.
- **Patient access to reports:** signed link plus OTP or PIN, rate limited, expiring (default 30 days, configurable), every access logged.
- **Sessions:** idle timeout (default 15 minutes in the admin), strong password policy, optional TOTP two-factor for owners and pathologists.
- **Backups** are encrypted, and a restore drill is documented and tested.
- **Regulatory:** health data and referral commission rules vary. Confirm applicable data protection, health records and professional ethics rules with a legal advisor before launch. The system must support consent capture, access logs, export and retention settings.

---

## 13. Reports, PDFs, Barcodes, Imports & Messaging

- **PDF:** use `spatie/laravel-pdf` with the Browsershot (headless Chromium) driver, because simple PDF libraries mis-render Bangla conjunct letters. The Docker image includes Chromium and the fonts (Inter, Noto Sans Bengali). Layouts are HTML with CSS `@page` (A4). A single report must generate in under 5 seconds; batches run in a queue.
- **Barcode and QR:** Code 128 sample labels with `picqer/php-barcode-generator`; QR codes with `endroid/qr-code` for report verification.
- **Import:** `maatwebsite/excel`, run in a queue with chunking, a dry-run validation step, a downloadable row-level error report, and a safe re-run (match on an external key). Never leave a half-imported dataset without a summary.
- **Messaging:** a `MessageChannel` interface with `SmsChannel` and `WhatsAppChannel`. Templates are stored per tenant, bilingual, with placeholders. WhatsApp templates must match the names approved by Meta. Fall back to SMS when WhatsApp fails. Meter usage per tenant (credits).
- **SMS length:** Bangla SMS is Unicode (70 characters per segment). Keep templates short and show the segment count and cost estimate in the template editor.
- **Message content:** messages never contain results or diagnoses. They contain only a short notice and a secure link.

---

## 14. Working Method (for the AI)

1. Read `docs/prd.md`, `docs/api.md`, `docs/design.md`, `docs/roadmap.md`.
2. Build **one phase at a time** from `roadmap.md`. Do not start the next phase.
3. For each feature: migration → model → Form Request → Action → controller → Vue page → tests.
4. If something in the docs is unclear or contradictory, **ask before assuming**.
5. Run tests and fix failures before reporting a feature as finished.
