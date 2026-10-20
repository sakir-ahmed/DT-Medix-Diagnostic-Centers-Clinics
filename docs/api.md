# API SPECIFICATION: DT Medix

> Sections 1 and 2 are shared conventions. Sections 3 onward are specific to DT Medix.

---

## 1. Purpose & Conventions

**Who uses the API:** future native apps (patient and staff, built in a separate project), integrations, and real-time screens. **The admin web app uses Inertia controllers (session auth)** that call the _same Actions_ as the API. Do not duplicate business logic.

| Topic         | Rule                                                                                                                                                                                   |
| ------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Base path     | `/api/v1/...` in `routes/api.php`                                                                                                                                                      |
| Installation | One deployment is one center. The API never accepts a center id from the client.                                                                                                      |
| Auth          | Laravel Sanctum bearer tokens. Guards: `staff` and `patient`. Token abilities: `staff`, `patient`.                                                                                     |
| Docs          | OpenAPI generated automatically (Scramble) at `/api-docs`; every endpoint has request and response examples.                                                                           |
| Versioning    | No breaking changes inside `v1`: add fields freely, never rename or remove. Clients send `X-App-Version` and `X-Platform`.                                                             |
| Format        | JSON. Success: `{"success": true, "data": {...}, "message": "Localized message"}`. Error: `{"success": false, "error": {"code": "REPORT_LOCKED", "message": "...", "details": {...}}}` |
| Language      | `Accept-Language: en` or `bn`                                                                                                                                                          |
| Money         | Decimal **string** in BDT, e.g. `"1250.00"`                                                                                                                                            |
| Dates         | ISO 8601 with timezone (`Asia/Dhaka`)                                                                                                                                                  |
| Pagination    | `?page=1&per_page=25`; response includes `meta` (`current_page`, `last_page`, `total`)                                                                                                 |
| Filtering     | `?filter[status]=pending&filter[date_from]=2026-01-01&sort=-created_at`                                                                                                                |
| Idempotency   | `POST` of invoices, payments, imports and message sends requires an `Idempotency-Key` header. The same key within 24 hours returns the original response.                              |
| Rate limits   | Login, OTP and public report access: strict. Others: 60 per minute per token.                                                                                                          |
| Branch access | Every `/branches/{branch}/...` route checks the user's branch access, else **404**.                                                                                                    |
| Patient data  | No patient data in URLs or query strings. Use IDs.                                                                                                                                     |

### Error codes

| HTTP | Code                                    | When                                                                   |
| ---- | --------------------------------------- | ---------------------------------------------------------------------- |
| 401  | `UNAUTHENTICATED`                       | Missing or invalid token                                               |
| 403  | `FORBIDDEN`                             | Role lacks permission                                                  |
| 404  | `NOT_FOUND`                             | Missing, or belongs to another branch                                   |
| 422  | `VALIDATION_FAILED`                     | Invalid input, `details` has field errors                              |
| 409  | `DUPLICATE_PATIENT_SUSPECTED`           | Mobile and name match an existing patient (`details` lists candidates) |
| 409  | `SLOT_UNAVAILABLE`                      | Appointment slot taken                                                 |
| 409  | `SAMPLE_ALREADY_COLLECTED`              | Cancel or edit not allowed                                             |
| 409  | `INVOICE_ALREADY_PAID`                  | Payment on a settled invoice                                           |
| 422  | `PAYMENT_EXCEEDS_DUE`                   | Payment larger than the due amount                                     |
| 409  | `REPORT_LOCKED`                         | Editing a released report (use amendment)                              |
| 409  | `REPORT_NOT_VERIFIED`                   | Release before verification                                            |
| 409  | `REPORT_HELD_FOR_PAYMENT`               | Release blocked by the payment hold rule                               |
| 409  | `CRITICAL_NOT_ACKNOWLEDGED`             | Critical value needs acknowledgement                                   |
| 403  | `SELF_VERIFICATION_NOT_ALLOWED`         | Same user entered and verified                                         |
| 409  | `SHIFT_NOT_OPEN`                        | Cashier has no open cash shift                                         |
| 429  | `TOO_MANY_REQUESTS`                     | Rate limit hit                                                         |
| 502  | `PAYMENT_GATEWAY_ERROR`                 | bKash, Nagad or SSLCommerz failure                                     |
| 502  | `MESSAGE_CHANNEL_ERROR`                 | SMS or WhatsApp provider failure                                       |
| 500  | `SERVER_ERROR`                          | Unexpected (details never exposed)                                     |

---

## 2. Authentication

**Staff (`/api/v1/auth`)**

| Method | Path                        | Notes                                                                   |
| ------ | --------------------------- | ----------------------------------------------------------------------- |
| POST   | `/login`                    | `login` (username, email or mobile) + `password`; returns token         |
| POST   | `/logout`                   | Revokes current token                                                   |
| GET    | `/me`                       | User, roles, permissions, branches                                     |
| POST   | `/otp/send` / `/otp/verify` | Rate limited by IP and mobile; hashed code, 5-minute expiry, 5 attempts |

**Patients (`/api/v1/patient/auth`)**: `POST /otp/send` and `POST /otp/verify` by mobile. One mobile may map to several family members; the response lists them and the app selects one.

---

## 3. Core Endpoints

Branch routes are prefixed `/api/v1/branches/{branch}`.

### Patients

| Method    | Path                                    | Notes                                                                           |
| --------- | --------------------------------------- | ------------------------------------------------------------------------------- |
| GET       | `/patients?filter[q]=`                  | Search by mobile, name or patient ID                                            |
| POST      | `/patients`                             | Returns `DUPLICATE_PATIENT_SUSPECTED` with candidates unless `confirm_new=true` |
| GET/PATCH | `/patients/{patient}`                   | Writes an access log row                                                        |
| GET       | `/patients/{patient}/timeline`          | Visits, tests, reports, payments                                                |
| GET       | `/patients/{patient}/trends?parameter=` | Values across visits with the reference band                                    |
| POST      | `/patients/{patient}/documents`         | Upload (private disk)                                                           |

### Doctors & Appointments

| Method | Path                                         | Notes                                                             |
| ------ | -------------------------------------------- | ----------------------------------------------------------------- |
| GET    | `/doctors` / `/doctors/{doctor}/slots?date=` | Doctors and free slots                                            |
| POST   | `/appointments`                              | Needs `Idempotency-Key`; may return `SLOT_UNAVAILABLE`            |
| PATCH  | `/appointments/{appointment}/status`         | `arrived`, `in-consultation`, `completed`, `no-show`, `cancelled` |
| GET    | `/doctor/queue`                              | The doctor's current queue                                        |
| POST   | `/appointments/{appointment}/consultation`   | Notes, advice, advised tests                                      |

### Catalog

| Method       | Path                                              | Notes                                   |
| ------------ | ------------------------------------------------- | --------------------------------------- |
| GET/POST/PUT | `/tests`, `/tests/{test}/parameters`, `/packages` | Test master, reference ranges, packages |
| GET/POST/PUT | `/report-templates`                               | Table and narrative templates           |

### Test Orders & Invoices

| Method | Path                                                                           | Notes                                                                                                  |
| ------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------ | --- |
| POST   | `/orders`                                                                      | Patient, tests or packages, referrer, urgency, discount. Creates the invoice. Needs `Idempotency-Key`. |
| GET    | `/orders/{order}`                                                              | Items with status per test                                                                             |
| POST   | `/orders/{order}/cancel`                                                       | Reason required; may need approval                                                                     |
| POST   | `/orders/{order}/discount-requests` and `/discount-requests/{request}/approve` | Approval above the limit                                                                               |
| GET    | `/invoices/{invoice}/print?format=a5                                           | thermal`                                                                                               | PDF |

### Samples & Lab

| Method  | Path                                                 | Notes                                           |
| ------- | ---------------------------------------------------- | ----------------------------------------------- |
| POST    | `/samples/{sample}/collect` / `/receive` / `/reject` | Reject needs a reason; can request recollection |
| GET     | `/lab/worklist?department=&status=`                  | With TAT countdown                              |
| GET/PUT | `/lab/results/{item}`                                | Draft results, auto flags, previous values      |
| POST    | `/lab/results/{item}/acknowledge-critical`           | Who was informed and when                       |
| POST    | `/lab/results/{item}/submit`                         | Moves to verification                           |
| GET     | `/samples/{sample}/label`                            | Label PDF with Code 128                         |

### Reports

| Method | Path                             | Notes                                                            |
| ------ | -------------------------------- | ---------------------------------------------------------------- |
| GET    | `/reports/verification-queue`    | Pathologist queue                                                |
| POST   | `/reports/{report}/verify`       | Signs; may return `SELF_VERIFICATION_NOT_ALLOWED`                |
| POST   | `/reports/{report}/return`       | Back to technician with a comment                                |
| POST   | `/reports/{report}/release`      | Enforces verification, payment hold and critical acknowledgement |
| POST   | `/reports/{report}/amendments`   | Creates a new version with a reason                              |
| GET    | `/reports/{report}/pdf?version=` | Authorized, access-logged                                        |
| POST   | `/reports/{report}/send-link`    | Sends a secure link through the chosen channel                   |

### Payments, Dues & Collection

| Method | Path                                     | Notes                                                                                   |
| ------ | ---------------------------------------- | --------------------------------------------------------------------------------------- | ------ | ------- | ------------------- |
| POST   | `/invoices/{invoice}/payments`           | One or many lines. Needs `Idempotency-Key`. Returns `PAYMENT_EXCEEDS_DUE` if too large. |
| GET    | `/dues?filter[aging]=`                   | Due list with aging                                                                     |
| POST   | `/dues/{invoice}/reminders`              | Sends a reminder message                                                                |
| POST   | `/refunds` / `/refunds/{refund}/approve` | Reason and approval                                                                     |
| POST   | `/shifts/open` / `/shifts/close`         | Close returns expected versus counted cash                                              |
| GET    | `/reports/collections?group_by=user      | shift                                                                                   | method | branch` | Collection tracking |

### Commission & Referrals (`commissions.view`)

| Method       | Path                                     | Notes                                                                    |
| ------------ | ---------------------------------------- | ------------------------------------------------------------------------ |
| GET/POST/PUT | `/referrers`                             | Referrer master                                                          |
| GET/POST/PUT | `/commission-rules`                      | Percent or fixed; referrer, department, test or package; effective dates |
| GET          | `/commissions?filter[referrer]=&period=` | Accrued commission per invoice with the rule used                        |
| POST         | `/commission-statements`                 | Generate for a period                                                    |
| POST         | `/commission-statements/{statement}/pay` | Marks paid and locks it                                                  |
| GET          | `/reports/referral-performance`          | Patients, revenue, commission per referrer                               |

### Home Collection & Transfers

| Method | Path                                                              | Notes                             |
| ------ | ----------------------------------------------------------------- | --------------------------------- |
| POST   | `/home-collections` / `/home-collections/{id}/assign` / `/status` | Request, assign collector, update |
| POST   | `/sample-transfers` / `/sample-transfers/{id}/receive`            | Manifest between branches         |

### Dashboard & Reports

| Method | Path                                        | Notes           |
| ------ | ------------------------------------------- | --------------- | ----- | ------------------------------------------------------------- |
| GET    | `/api/v1/dashboard?branch=all&period=today` | KPIs and alerts |
| GET    | `/reports/{type}?format=json                | pdf             | xlsx` | `sales`, `department-revenue`, `tat`, `dues`, `expenses`, ... |

---

## 4. Patient Portal API (`/api/v1/patient`)

A patient sees only their own records (and family members linked by the same mobile). Anything else returns 404.

| Method | Path                                   | Notes                                           |
| ------ | -------------------------------------- | ----------------------------------------------- |
| GET    | `/me`                                  | Profile and linked family members               |
| GET    | `/reports` / `/reports/{report}`       | Released reports only; each access is logged    |
| GET    | `/invoices` / `/invoices/{invoice}`    | Dues and payments                               |
| POST   | `/invoices/{invoice}/pay`              | Starts an online payment, returns a gateway URL |
| GET    | `/doctors` / `/doctors/{doctor}/slots` | Public doctor list and slots                    |
| POST   | `/appointments`                        | Book (Turnstile on the public page)             |
| POST   | `/devices`                             | Register a push token (for the future app)      |

**Public report access:** `GET /r/{signedToken}` (web route) asks for OTP or PIN, then shows or downloads the report. Tokens are signed, expire (default 30 days), are rate limited and logged. `GET /verify/{reportNumber}` shows only that a report is valid, with minimal details.

---

## 5. Payment Gateways

**Principle:** never trust the browser or the callback payload alone. Confirm the final status with the gateway server to server, then update the invoice inside a transaction. Confirmation is idempotent: a duplicate callback never double-pays. Callbacks and IPN are **public web routes** (CSRF-excluded), not Sanctum API routes. Credentials live in `.env` and `config/services.php`. Gateways implement one `PaymentGateway` interface.

- **bKash (tokenized):** `POST /api/v1/payments/bkash/create` (grant token, create payment, return URL), then `GET /payments/bkash/callback`, which runs **execute payment** and **query payment** before marking `completed`. Store `paymentID` and `trxID`.
- **SSLCommerz:** `POST /api/v1/payments/sslcommerz/init`, then `POST /payments/sslcommerz/ipn`, which calls the validation API with `val_id` and checks status, amount and currency against the local invoice. Success, fail and cancel pages never decide status.
- **Nagad:** same pattern as bKash, using the merchant documentation you receive. Until credentials exist, use a manual TrxID entry with later verification by a manager.

---

## 6. Messaging Webhooks

- **WhatsApp:** `POST /webhooks/whatsapp` receives delivery and read status. Verify the signature, ignore unknown payloads, update the message log, and answer quickly (heavy work in a queued job).
- **Opt-out:** inbound "STOP" style replies mark the patient as opted out of non-essential messages.
- Message logs store the template, channel and status, never the message body with patient data beyond the placeholders used.

---

## 7. Real-time (Reverb)

Private channels authorized by branch and permission. **Payloads contain IDs only.**

| Channel                       | Events                                                                        |
| ----------------------------- | ----------------------------------------------------------------------------- |
| `private-branch.{b}.lab`      | `SampleReceived`, `ResultSubmitted`, `ReportVerified`, `CriticalResultRaised` |
| `private-branch.{b}.reception` | `AppointmentUpdated`, `ReportReleased`, `PaymentReceived`                    |
| `private-doctor.{id}.queue`   | `QueueUpdated`                                                                |
| `private-branch.{b}.alerts`   | `TatBreached`, `DueOverdue`                                                   |
| `private-owner.alerts`        | Cross-branch alerts for owners                                                |
