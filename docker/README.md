# Docker Deployment

This project can run as containers using `docker-compose.yml`.

## Services
- `app`: Apache + PHP 8.3 serving Laravel from `public/` on port `8080`.
- `scheduler`: runs `php artisan schedule:work`.
- `queue`: runs `php artisan queue:work`.

## Quick Start
1. Ensure `.env` exists (copy from `.env.example` if needed).
2. Set secure production values in `.env` (especially `APP_KEY`, mailbox credentials).
3. Build and start:

```powershell
docker compose up --build -d
```

4. Open:
- http://localhost:8080

## Notes
- SQLite data persists in Docker volume `dmarc_database`.
- Storage (logs/cache/uploads) persists in Docker volume `dmarc_storage`.
- Migrations run automatically in `app` container startup (`RUN_MIGRATIONS=true`).
- To disable auto-migrate, set `RUN_MIGRATIONS=false` for `app` in `docker-compose.yml`.

## MySQL example
- Environment example: `.env.mysql.example`
- Compose example: `docker-compose.mysql.example.yml`

Copy and adapt:

```powershell
Copy-Item .env.mysql.example .env
docker compose -f docker-compose.mysql.example.yml up --build -d
```

## Useful Commands
```powershell
docker compose logs -f app
docker compose logs -f scheduler
docker compose logs -f queue
docker compose exec app php artisan about
docker compose down
```

