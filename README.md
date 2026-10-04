# Mission Control

Mission 05 on [codelaunch.nl](https://codelaunch.nl): the tool I wish I'd had
during 14 years of running a web design business. A web project moves from
proposal to brand kit to design review to launch, with a studio portal
(Mission Control) and a client portal (Launchpad).

Work in progress. The recruiter README, live demo and screenshots arrive in
the final phase.

- Live (soon): https://codelaunch.nl/mission-control
- Decisions: [`docs/decisions/`](docs/decisions/)
- User stories: [`docs/stories/`](docs/stories/)

## Run it locally

Requires PHP 8.5 (with `intl`, `gd`, `bcmath`, `pdo_mysql`), Composer, Node 24
and MariaDB.

```
composer install
npm install
cp .env.example .env        # then fill in the database settings
php artisan key:generate
php artisan migrate
composer dev
```
