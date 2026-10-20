# DT Medix: Development Handover

Date: 21 Oct 2026. Branch: `main`.

This note is for the next developer or AI agent. Read `CLAUDE.md` and the files in `docs/` first (prd, architecture, api, design, roadmap). The roadmap in `docs/roadmap.md` marks finished phases with `[x]`.

---

## 1. Status at a glance

| Phase | Title | State |
| ----- | ----- | ----- |
| 0 | Foundation and UI kit | Done, tested |
| 1 | Branches, staff, doctors and patients | Done, tested |
| 2 | Test catalog, packages and report templates | Done, tested |
| 3 | Appointments and reception billing (cash) | Done, tested |
| 4 | Sample collection and lab processing | Done, tested |
| 5 to 12 | Verification, payments, messaging, commission, history, dashboard, import, home collection | Not started |

Test suite: `php artisan test` (185 tests), plus `php artisan test --group=performance`. Pint and PHPStan (level 5) clean.

---

## 2. Architecture: one installation per center

As decided in `docs/prd.md` section 2 and `docs/architecture.md` section 11, this codebase runs **one center per installation**: one Laravel app, one database, no `tenant_id`, no shared database, no platform console and no plan or subscription state. Datatech deploys a copy per customer.

- **Center profile:** one row in `centers` (`App\Models\Center`) holding the name, address, contact and the business-rule `settings` (`Center::DEFAULT_SETTINGS`). Read it anywhere with `center()`; `forget_center()` drops the per-request cache (called automatically when the row is saved).
- **Branches:** `branches` table. `BelongsToBranch` scopes branch-owned models to the branches the signed-in user may access; owners and `branches.all` see everything. `App\Support\BranchContext` (scoped singleton) holds the allowed branch IDs and the current branch, filled by the `branch.scope` middleware (`ResolveBranch`) from the user's assignments and the session branch.
- **Roles:** Spatie Permission **without teams**, one role set per installation. Permission catalogue and role defaults in `app/Support/Permissions.php`; `SyncPermissionsAction` creates them. The hidden Datatech login is a user with the `super-admin` role, seeded from `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD`; `Gate::before` grants it everything.
- **Storage:** private disk (`config('center.storage_disk')`), plain paths such as `patients/{id}/...`, `results/{item}/...`, `doctors/{id}/...`.
- **Real-time:** channels `branch.{id}.lab`, `branch.{id}.reception`, `branch.{id}.alerts`, `doctor.{id}.queue`, `owner.alerts` (payloads carry IDs only).
- **Export:** `php artisan center:export` writes every business table as JSON plus the private files into a zip on the private disk.
- **Demo mode:** `IS_DEMO=true` (never in production) lists the seeded demo accounts on the login page for one-click sign-in (`demo.mode` middleware, `DemoLoginController`).

Architecture tests fail if a model with `branch_id` lacks `BelongsToBranch` (exceptions in `config/center.php` `cross_branch_models`).

---

## 3. What is built

See the roadmap for scope. Highlights per phase:

- **Phase 0:** `BelongsToBranch`, branch-aware jobs and storage, staff auth (username / email / mobile), OTP service, idle timeout, optional TOTP, activity log, `patient_access_logs`, encrypted backups, JSON API envelope (`ApiResponse`), `DomainException` with stable codes, `EnsureIdempotency`, OpenAPI at `/api-docs`, Inertia v3 + Vue 3 + Tailwind v4 UI kit and the `/admin/ui-kit` showcase.
- **Phase 1:** branch CRUD, center settings, staff accounts, doctors with schedules and leave, patients with gapless `P-YY-NNNNNN` codes, duplicate detection, family linking, encrypted NID, private photo, access log, `PatientBanner`, referrers.
- **Phase 2:** departments, tests, parameters, reference ranges by sex and age, critical limits, packages, branch price lists, report templates (table and narrative) with quick phrases, message templates (en / bn).
- **Phase 3:** appointments (slots, tokens, status flow, follow-up fee, doctor queue), reception counter, orders and invoices with price snapshots, discount approval, cash and partial payments, money receipts, cancel and refund with approval, A5 / 80mm prints and Code 128 labels.
- **Phase 4:** sample collect / receive / reject with recollection, department worklist with TAT countdown and Reverb refresh, result entry with server-computed flags and range snapshots, calculated parameters, previous values, critical acknowledgement gate, narrative editor with attachments, `/api/v1` lab endpoints.

---

## 4. Conventions to keep

- Flow per feature: migration, model (with `BelongsToBranch` when it has `branch_id`), Form Request, Action, Admin and API controllers calling the same Action, Vue page, tests.
- Money: `decimal(12,2)`, string arithmetic through `App\Support\Money` (bcmath), API returns strings.
- Business errors: `throw DomainException::make('ERROR_CODE', $status)`. The API gets the JSON envelope; Inertia requests get a flash error toast and `error_code` in the session.
- Every user-facing string goes in both `lang/en/*.php` and `lang/bn/*.php`. `TranslationParityTest` checks both files have the same keys and that every `t('...')` key used in Vue exists.
- No emojis or Unicode symbol icons anywhere (`NoEmojiTest`). Icons only through `AppIcon`; add new Font Awesome icons to `resources/js/lib/icons.js`.
- Never `window.alert/confirm/prompt`; use `useConfirm()` and `ConfirmHost`.
- In tests, `makeCenter()` creates the center, roles and `BR01`; `makeUser(role, branch, attributes)` creates staff; `TestCase::call()` resets the branch context and the center cache around every request.
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

- Set `CENTER_*`, `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD` and `DEMO_PASSWORD` in `.env` before seeding. Without the passwords the super-admin and demo accounts are skipped.
- Demo accounts (password `DEMO_PASSWORD`): `demo.owner`, `demo.manager`, `demo.reception`, `demo.collector`, `demo.tech`, `demo.pathologist`, `demo.doctor`, `demo.accountant`. With `IS_DEMO=true` they appear on the login page for one-click sign-in.
- `.env.example` targets Docker (MySQL 8, Redis). The local `.env` used during development is SQLite with file cache and sessions; tests use in-memory SQLite.
- Quality checks: `./vendor/bin/pint`, `./vendor/bin/phpstan analyse --memory-limit=1G`, `php artisan test`, `php artisan test --group=performance`.

---

## 6. Known gaps and notes

- Inertia **v3** is used because Inertia v2 does not support Laravel 13 (docs mention v2).
- SSR entry (`resources/js/ssr.js`) builds, but SSR is disabled by default (`INERTIA_SSR_ENABLED=false`); it is meant for public pages only.
- The notification bell still reads a `notifications` prop that is not shared yet.
- Dashboard (`Admin/Dashboard`) is a setup checklist placeholder until Phase 10.
- Patient Documents and Timeline tabs stay empty until Phase 9.
