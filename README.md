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

For a subfolder installation, set `APP_BASE_PATH`, for example `/wordtruthspirit`.
