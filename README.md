# Peer Tutoring Management System (PTMS)

This is the PHP/PDO midterm implementation of Roniel C. Oporto's proposed Peer Tutoring Management System. The assignment asks for one main entity, so this version focuses on `TutoringSession` and implements Create, Read, Update, Delete, and multi-field Search.

## ERD alignment

The database now follows the embedded ERD. It contains `users`, `subjects`, `tutor_subjects`, `tutoring_sessions`, `bookings`, `payments`, `feedback`, and `notifications`. The main CRUD entity remains `TutoringSession`, as required by the midterm.

The main entity uses the ERD attributes `SessionID`, `TutorID`, `StudentID`, `SubjectID`, `SessionDate`, `TimeSlot`, `Status`, and `Location`. SQL uses conventional snake_case equivalents such as `session_id` while preserving the same meaning and relationships.

## Requirements covered

- PHP class and OOP structure
- PDO with native prepared statements for every database operation
- `addTutoringSession()`
- `getTutoringSessions()`
- `updateTutoringSession($id, $data)`
- `deleteTutoringSession($id)`
- `searchTutoringSessions($keyword)` across every displayed field
- Add/edit form, records table, delete confirmation, and search bar
- Database and application error handling
- Server-side validation, escaped output, and CSRF protection
- Three explicit PDO transactions for adding, updating, and deleting sessions
- Coordinator authentication using secure PHP sessions and hashed passwords
- Public Student/Tutor registration with Coordinator-only write permissions

## Database transactions

The three write operations use PDO transaction control:

- `addTutoringSession()` starts a transaction before inserting a session.
- `updateTutoringSession()` starts a transaction before saving changes.
- `deleteTutoringSession()` starts a transaction before removing a session.

Each method calls `commit()` when the operation succeeds. If an error occurs,
the method calls `rollBack()` so the database is not left with a partial change.

## Setup with XAMPP

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Copy the `ptms-php` folder into `C:\xampp\htdocs\`.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Create or select a database named `ptms`.
5. Choose **Import**, select `database/schema.sql`, and run it.
6. Visit `http://localhost/ptms-php/`.

For the PHP development server, import the SQL first, open a terminal in `ptms-php`, then run:

```text
php -S localhost:8000 -t public
```

Open `http://localhost:8000`.

## Deployment configuration

The application reads production database credentials from environment variables:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

Other hosting providers may supply these as environment variables. If none are
set, the application uses the normal local XAMPP defaults (`127.0.0.1`, port
`3306`, database `ptms`, user `root`, and a blank password).

### InfinityFree

1. Create the MySQL database in the InfinityFree control panel.
2. Open its phpMyAdmin and import `database/schema.sql` into that selected database.
3. Copy `config/credentials.example.php` to `config/credentials.php`.
4. Enter the exact hostname, database name, username, and password shown by InfinityFree.
5. Upload the project contents to `htdocs`. The root `index.php` redirects to the login page.

`config/credentials.php` is excluded by `.gitignore` and must never be uploaded
to GitHub. The root `.htaccess` blocks web requests to `classes`, `config`, and
`database` on Apache-compatible hosting.

## Authentication

Visitors are redirected to `public/login.php`. Successful login regenerates the
PHP session ID and stores only the user's ID, name, email, and role in the
session. Every account may view and search sessions, while write operations
require the `Coordinator` role. Logout is a POST request protected by the same
CSRF token used by the CRUD forms.

The project contains Coordinator accounts for `jasmine@ptms.test` and the
instructor evaluator account `carl.cosep@ptms.test`. Share their passwords
separately; never place plaintext passwords in the public GitHub repository.

The Create Account page allows new Student and Tutor accounts. Public signup
cannot create a Coordinator. Student and Tutor accounts may view and search
sessions, while Add, Edit, and Delete are enforced as Coordinator-only actions.

## Process explanations for the midterm

### Adding a record

The user selects an existing student, tutor, and subject, then enters the session date, time slot, status, and location. The form submits these ERD-aligned values with `action=add`. `public/index.php` verifies the CSRF token, validates every required value, and passes the form array to `addTutoringSession()`. The class prepares an `INSERT` statement with named placeholders and sends the values separately through `execute()`.

### Fetching records

When the page loads without a search keyword, `getTutoringSessions()` joins `tutoring_sessions` with `users` twice (for tutor and student) and with `subjects`. The page therefore displays readable names while the session table correctly stores foreign-key IDs.

### Updating a record

Clicking **Edit** adds the record ID to the URL. `getTutoringSession()` retrieves that one record and fills the form. After the edited form is submitted with `action=update`, the page validates the values and calls `updateTutoringSession()`. Its prepared `UPDATE` statement changes only the row whose ID matches the bound `:id` parameter.

### Deleting a record

Clicking **Delete** shows a browser confirmation. When confirmed, the form submits the record ID and CSRF token with `action=delete`. `deleteTutoringSession()` binds the ID to a prepared `DELETE` statement. MySQL permanently removes only that matching row.

### Searching records

The search form sends a keyword through the URL. If it is not empty, the page calls `searchTutoringSessions()`. The method surrounds the keyword with `%` wildcards and safely binds it to unique PDO placeholders. The joined query checks the session ID, student, tutor, subject, date, time slot, location, and status with `LIKE`, then returns all matching rows for display.

## File guide

- `database/schema.sql` - all eight ERD tables, relationships, and sample records
- `config/database.php` - reusable PDO connection
- `config/credentials.example.php` - safe template for InfinityFree credentials
- `classes/TutoringSession.php` - entity class containing all database operations
- `classes/User.php` - retrieves a user for secure login verification
- `config/session.php` - secure PHP session cookie configuration
- `config/auth.php` - login, role protection, and logout helpers
- `public/index.php` - controller, validation, forms, and records table
- `public/login.php`, `public/register.php`, and `public/logout.php` - authentication pages
- `public/styles.css` - responsive interface styling

## Suggested demonstration order

1. Show the `tutoring_sessions` table in phpMyAdmin.
2. Add a new session.
3. Show it in the records list.
4. Edit its status or schedule.
5. Search using the tutor, subject, or status.
6. Delete or cancel a test record.
7. Open `TutoringSession.php` and explain how PDO placeholders prevent values from being placed directly in SQL.
