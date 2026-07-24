# Word Truth Spirit PHP Site

A responsive multi-page PHP version of the Word Truth Spirit website, including
a filterable journal, a publications catalog, individual reflection pages,
contact handling, email subscriptions, administration, and MySQL persistence.

## Local use

Run `php -S localhost:8080` and open `http://localhost:8080`.

The website works without MySQL using bundled journal entries. Contact messages
fall back to `data/messages.jsonl`.

## Existing MySQL database

1. Run `database/schema.sql` against the shared database. Tables use the `wts_`
   prefix so they can coexist with other websites.
2. Copy `.env.example` to `.env` and provide the database values through your
   server environment. PHP reads `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and
   `DB_PASS`.
3. Insert or import published posts into `wts_posts`. Database posts replace the
   bundled fallback data automatically.

## Administration

After connecting MySQL and running `database/schema.sql`, visit `/admin/`.
The first visit opens a one-time setup screen for creating the first
administrator. After that, sign in to:

- Add, edit, publish, archive, reorder, or delete books
- Add, edit, publish, archive, or delete journal reflections
- See publication, journal, and unread-message totals

Admin passwords are stored using PHP's current `PASSWORD_DEFAULT` hash.

## Promotions, email, and subscriptions

The admin **Promotions & SMTP** screen controls the public announcement banner,
welcome popup, contact notification recipient, and SMTP delivery configuration.
SMTP credentials are saved in the local `.env` file and are never displayed
again in the admin.

Subscribers receive a confirmation link before becoming active. Every email
campaign includes a fresh unsubscribe link. Campaigns send only to active
subscribers and record delivery totals in the original database's
`notification_campaigns` table when that table is available.

Set `APP_URL` in `.env` to the public HTTPS address before sending
confirmation or campaign email, so email links point to the live website.

For a subfolder installation, set `APP_BASE_PATH`, for example `/wordtruthspirit`.

## Using `127_0_0_1.sql`

The PHP site automatically detects the schema in the supplied Node-site export.
When it is present, the site uses its existing:

- `posts` table for the public journal and admin journal editor
- `users` table for administrator login
- `contact_messages` table for contact submissions
- `email_subscribers` table for subscriptions

Import `127_0_0_1.sql`, set `DB_NAME=wts`, and then run
`database/127_0_0_1_compat.sql`. The compatibility migration adds only
`wts_books`, because the original export has no publications table. It does not
alter or duplicate the original posts, users, subscribers, or messages.

### Guided setup

For a fresh installation, open `/setup/` in the browser. The installer:

1. Tests the supplied MySQL credentials
2. Verifies that the target `wts` database is empty or does not exist
3. Validates and imports `127_0_0_1.sql`
4. Applies `database/127_0_0_1_compat.sql`
5. Writes the local `.env` connection file
6. Creates `.setup-complete` so the installer cannot run again accidentally

The MySQL account needs permission to create the `wts` database and its tables.
