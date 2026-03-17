# TELC B2 Exam Simulator (Laravel)

Dynamic Laravel web app for an online German exam simulator inspired by TELC B2.

## Stack

- Laravel 12
- Blade
- Tailwind CSS
- Alpine.js
- SortableJS (drag/drop for matching)
- MySQL (recommended runtime DB)

## Implemented Scope

- Full admin area for exam setup
- Student exam flow with persistent attempts and timer
- Fully implemented `Lesen > Teil 1` matching engine:
  - click title then text
  - click text then title
  - drag title to text
  - remove/reassign mappings
  - autosave + manual save
  - completion check icon on tab when fully answered
  - server-side timer enforcement and submit
- Generic schema ready for future parts:
  - Lesen
  - Sprachbausteine
  - Horen
  - Schreiben
- Fully implemented `Lesen > Teil 2` single long text MCQ engine:
  - long passage with clean paragraphs
  - 5 questions
  - 3 options per question
  - exactly one correct option per question
  - autosave/manual save/submit and scoring
- Fully implemented `Lesen > Teil 3` situations-to-ads engine:
  - 12 Anzeigen on the left
  - 10 Situationen on the right
  - one Anzeige can be used only once
  - per-situation `X` selection for no suitable Anzeige
  - click/click and drag/drop assignment
  - autosave/manual save/submit and scoring

## Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Configure environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telc_sim
DB_USERNAME=root
DB_PASSWORD=
```

4. Run migrations + seed:

```bash
php artisan migrate:fresh --seed
```

5. Build frontend assets:

```bash
npm run build
```

(For development use `npm run dev`.)

6. Start app:

```bash
php artisan serve
```

## Login Accounts (seeded)

- Admin
  - Email: `admin@telc-sim.local`
  - Password: `password`
- Student
  - Email: `student@telc-sim.local`
  - Password: `password`

## Main Routes

### Student

- `GET /dashboard` list exams
- `GET /exams/{exam}/start` start/reuse in-progress attempt
- `GET /attempts/{attempt}/part/{part}` open part page
- `POST /attempts/{attempt}/save` autosave/manual save
- `POST /attempts/{attempt}/submit` submit attempt

### Admin

- `GET /admin`
- `GET /admin/exams`
- `GET /admin/exams/create`
- `GET /admin/exams/{exam}/edit`
- `GET /admin/exams/{exam}/sections`
- `GET /admin/parts/{part}/edit`
- `GET /admin/parts/{part}/lesen-teil1`

## Admin Workflow

1. Login as admin.
2. Open `/admin/exams` and create exam.
3. Add sections (Lesen/Sprachbausteine/Horen/Schreiben).
4. Add parts to each section.
5. For `matching_titles_to_texts` part type, open `Lesen Teil 1 Editor`.
6. Enter texts/options and correct mappings, then save.
7. Publish exam and test from student dashboard.

## Architecture Notes

- Core models: `Exam`, `ExamSection`, `ExamPart`, `LesenMatchingText`, `LesenMatchingOption`, `LesenMatchingAnswer`, `ExamAttempt`, `AttemptAnswer`.
- `ExamAttemptService` handles:
  - timer synchronization
  - answer saves
  - submit + scoring
- `attempt_answers.answer_json` stores flexible payloads (including matching assignments), making future part types easy to add.
- Admin is protected by `auth` + `admin` middleware (`EnsureUserIsAdmin`).

## Extending for New Part Types

1. Add a new `part_type` usage in `exam_parts` (already generic string).
2. Add dedicated content tables if needed.
3. Create admin editor for the new part type.
4. Add student rendering block in `student.parts.show` (or split into per-part Blade partials).
5. Add save/score logic in `ExamAttemptService`.

## Validation and Security

- FormRequests for all admin and student save endpoints
- Attempt ownership checks before read/write
- Submitted/expired attempts are blocked from save
- Backend timer enforcement (frontend timer is only display)

## Verification Commands

```bash
php artisan route:list
php artisan migrate:fresh --seed
php artisan test
npm run build
```

## Important Note

Vite prints a warning with Node `22.11.0` in this environment. Build still succeeds, but upgrading to `22.12+` is recommended.
