# Production setup (one time)

Everything here is done once. After that, every push to `main` deploys on its
own (see [ADR 0002](decisions/0002-hosting-layout.md) and
[ADR 0003](decisions/0003-migrations-without-ssh.md)).

**Golden rule:** passwords, tokens and private keys go straight from your
machine into cPanel or GitHub. Never paste them into a chat with Claude, an
issue, a commit or a file inside OneDrive.

---

## 1. Database (cPanel → MySQL Databases)

1. Create a database: `mission_control` (cPanel adds your prefix, e.g.
   `codefhdb_mission_control`).
2. Create a user with a long generated password. Save it in your password
   manager.
3. Add the user to the database with **All Privileges**.
4. In phpMyAdmin, open the database → **Operations** → set the collation to
   `utf8mb4_unicode_ci`. (The server's default can be `latin1`, which breaks
   accents and emoji.)

## 2. Production environment secret

1. Locally, generate the two secrets (these print to your own terminal only):
    ```powershell
    php artisan key:generate --show                 # APP_KEY
    php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"  # DEPLOY_TOKEN
    ```
2. Open `.env.production.example`, copy its contents into a **new, unsaved**
   editor tab (don't save it inside the project or OneDrive), and fill in
   `APP_KEY`, the database name, user and password, and `DEPLOY_TOKEN`.

## 3. GitHub secrets

GitHub → `swens2005/mission-control` → Settings → Secrets and variables →
Actions → **New repository secret**:

| Secret                     | Value                                |
| -------------------------- | ------------------------------------ |
| `FTP_SERVER`               | Same as the portfolio repo           |
| `FTP_USERNAME`             | Same as the portfolio repo           |
| `FTP_PASSWORD`             | Same as the portfolio repo           |
| `DEPLOY_TOKEN`             | The token from step 2                |
| `ENV_MISSION_CONTROL_PROD` | The whole filled-in file from step 2 |

Close the unsaved editor tab without saving. Until all five secrets exist, the
deploy job skips with a warning instead of failing.

## 4. First deploy

Push to `main` (or run the workflow by hand: Actions → CI and deploy → Run
workflow). The job uploads to `~/mission-control-app/` and
`~/public_html/mission-control/`, runs migrations, and checks
`https://codelaunch.nl/mission-control/up`.

## 5. Cron (needed from Phase 1, for sandbox cleanup)

cPanel → Cron Jobs → every minute:

```
* * * * * php /home/<cpanel-user>/mission-control-app/artisan schedule:run >> /dev/null 2>&1
```

If `php` there is not 8.5, use the full path that cPanel's PHP Selector shows
(CloudLinux usually has `/opt/alt/php85/usr/bin/php`).

---

## Giving Claude server access (optional)

The steps above don't need it. Access only helps for debugging on the server
(reading logs, checking PHP settings). Both options below give access to the
**whole hosting account**, including codelaunch.nl, so revoke access when
you no longer need it.

### Option A: SSH key (recommended)

The private key stays on your machine. Claude runs `ssh codelaunch "..."` and
never sees the key. Namecheap shared hosting uses SSH port **21098**. If SSH
Access is missing in cPanel, ask Namecheap support to enable it.

Do this **on each computer separately**. Don't copy a private key between
machines or store one in OneDrive.

1. Create a key with a passphrase (PowerShell):
    ```powershell
    ssh-keygen -t ed25519 -f "$HOME\.ssh\codelaunch_cpanel" -C "mission-control $env:COMPUTERNAME"
    ```
2. Load it into the Windows ssh-agent, so the passphrase is asked once per
   login instead of on every command (the first line needs an admin
   PowerShell, once):
    ```powershell
    Get-Service ssh-agent | Set-Service -StartupType Automatic; Start-Service ssh-agent
    ssh-add "$HOME\.ssh\codelaunch_cpanel"
    ```
3. cPanel → **SSH Access** → Manage SSH Keys → **Import Key**. Name it
   `codelaunch_cpanel_<computer>`, paste the contents of
   `codelaunch_cpanel.pub` (the **.pub** file, the public half) into the public
   key box, and leave the private key box empty. Then **Manage** → **Authorize**.
4. Add this to `$HOME\.ssh\config` (find the server hostname under cPanel →
   General Information → Server Information):
    ```
    Host codelaunch
      HostName <server-hostname>
      Port 21098
      User <cpanel-user>
      IdentityFile ~/.ssh/codelaunch_cpanel
      IdentitiesOnly yes
    ```
5. Test it: `ssh codelaunch "php -v"`. Then tell Claude "SSH alias
   `codelaunch` is ready".
6. **Revoke:** cPanel → SSH Access → Manage SSH Keys → Deauthorize (or
   Delete).

### Option B: cPanel API token

Lets Claude call cPanel's API (databases, cron, files). cPanel tokens can't be
limited to certain features, so this is the more powerful of the two. Prefer
SSH.

1. cPanel → Security → **Manage API Tokens** → Create. Name:
   `mission-control-claude`. **Set an expiry date** (e.g. one week).
2. Copy the token once into a file **outside OneDrive** (PowerShell):
    ```powershell
    New-Item -ItemType Directory -Force "$HOME\.config\cpanel" | Out-Null
    Get-Clipboard | Set-Content -NoNewline -Encoding ascii "$HOME\.config\cpanel\token"
    ```
3. Tell Claude the cPanel username and server hostname (not the token). Claude
   reads the token from that file inside its commands without printing it, for
   example:
    ```
    curl -H "Authorization: cpanel <user>:$(cat ~/.config/cpanel/token)" https://<server>:2083/execute/Mysql/list_databases
    ```
4. **Revoke:** cPanel → Manage API Tokens → Revoke, and delete the file.
