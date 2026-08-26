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
- Write reflections in the block editor with local recovery drafts, image uploads,
  SEO fields, preview links, and per-reflection comment controls
- Moderate reader discussion with threaded replies, reactions, reports, spam
  screening, blocked commenters, bulk actions, and public administrator replies
- See publication, journal, and unread-message totals

Admin passwords are stored using PHP's current `PASSWORD_DEFAULT` hash.

## Promotions, email, and subscriptions

The admin **Promotions & SMTP** screen controls the public announcement banner,
welcome popup, contact notification recipient, and SMTP delivery configuration.
It also controls the shared email signup copy and independently enables signup
placements on the journal, at the end of reflections, in the site footer, and in
a timed, dismissible bottom-page banner. SMTP credentials are saved in the local
`.env` file and are never displayed again in the admin.

The separate admin **Ads** workspace manages reusable homepage advertisements.
Each ad can be assigned to the full-width top zone or right sidebar and includes
an image upload or image URL, accessibility text, headline, message, offer badge,
button, destination, display order, and active/hidden status. Ads can optionally
publish individual detail pages under `/ads/{slug}/` with expanded content and
their original advertiser call-to-action. The Bible Memory promotion is supplied
as the default sidebar ad rather than a sitewide footer ad.

Browser notifications use the VAPID values `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`,
and `VAPID_PRIVATE_KEY` in `.env`. The private key stays server-side. Readers
can opt in from the blog, and browser subscriptions are stored in
`wts_push_subscriptions` for future push delivery.

Subscribers receive a confirmation link before becoming active. Every email
campaign includes a fresh unsubscribe link. Campaigns send only to active
subscribers and record delivery totals in the original database's
`notification_campaigns` table when that table is available.
Signup submissions return readers to the page and placement where they joined,
and the legacy subscriber schema records the originating placement as its source.

Set `APP_URL` in `.env` to the public HTTPS address before sending
confirmation or campaign email, so email links point to the live website.

For a subfolder installation, set `APP_BASE_PATH`, for example `/wordtruthspirit`.

## Brand sprites

The shared dove and winged-lamp artwork lives in `assets/images/spirit-dove.png`
and `assets/images/winged-lamp.png`. Reusable CSS treatments place these marks in
public and admin headers, hero accents, dark-section watermarks, and the global
footer divider.

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
