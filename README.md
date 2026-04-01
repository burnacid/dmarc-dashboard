# DMARC Dashboard

A Laravel 13 application for monitoring DMARC aggregate reports delivered to one or more IMAP inboxes.

## Included in this version

- Laravel 13 + Blade
- Breeze authentication (register, login, profile, logout)
- Tailwind-based UI with a dashboard and IMAP account management
- Multi-account IMAP configuration per user
- DMARC aggregate XML parsing
- Support for `.xml`, `.gz`, and `.zip` report attachments
- Scheduled polling command: `dmarc:poll`
- Parsed storage for reports and per-source record rows

## Tech stack

- PHP 8.3+
- Laravel 13
- SQLite by default for local development
- Tailwind CSS
- `webklex/php-imap` for mailbox access

## Data model

### `imap_accounts`
Stores mailbox connection settings for each signed-in user.

### `dmarc_reports`
Stores one imported DMARC aggregate report per mailbox/report id pair.

### `dmarc_records`
Stores parsed row-level DMARC data such as source IP, SPF, DKIM, and message count.

## Main application areas

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ImapAccountController.php`
- `app/Services/Dmarc/DmarcIngestionService.php`
- `app/Services/Dmarc/DmarcXmlParser.php`
- `app/Services/Dmarc/DmarcAttachmentExtractor.php`
- `app/Console/Commands/DmarcPollCommand.php`

## Local setup

From `D:\Workspace\DMARC-Dashboard`:

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Run locally

```powershell
php artisan serve
php artisan schedule:work
npm run dev
```

Open the app in your browser, register a user, and add one or more IMAP accounts from the dashboard.

## Poll DMARC reports manually

Poll all active IMAP accounts:

```powershell
php artisan dmarc:poll
```

Poll a single IMAP account by id:

```powershell
php artisan dmarc:poll --account=1
```

## Scheduler

The application schedules DMARC polling every five minutes in `routes/console.php`.

For production, wire Laravel's scheduler as usual:

```powershell
php artisan schedule:run
```

Or keep a foreground worker during development:

```powershell
php artisan schedule:work
```

## IMAP notes

For providers such as Microsoft 365 or Gmail, an app password is usually the simplest v1 setup if standard password auth is enabled for IMAP.

Recommended starter values:

- Port: `993`
- Encryption: `ssl`
- Folder: `INBOX`
- Search criteria: `UNSEEN`

## Testing

Run the automated test suite:

```powershell
php artisan test
```

## Roadmap

Planned next-step features:

- Alert rules based on SPF/DKIM/disposition thresholds
- Email notifications
- Report detail pages and drill-down charts
- OAuth-based mailbox authentication
- Background queue-based mailbox polling
