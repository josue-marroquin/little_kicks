# DEPLOYMENT.md — Kicks Production Setup

## Overview

Kicks is a PHP/Apache application. **Node.js is not required for production.**

The application is pre-built and ready to serve. All static assets (JavaScript, CSS) are compiled to `public/assets/`.

## Production Deployment

### Prerequisites

- Apache 2.4+ with `mod_rewrite` enabled
- PHP 8.2+
- Write permissions to `storage/` directory

### Steps

#### 1. Clone and install

```bash
git clone <repo-url> /opt/homebrew/var/www/Kicks
cd /opt/homebrew/var/www/Kicks
cp .env.example .env
php scripts/build.php
```

#### 2. Configure Apache VirtualHost

Create or update your Apache configuration for the Kicks application:

```apache
<VirtualHost *:80>
    ServerName kicks.example.com
    ServerAlias www.kicks.example.com
    
    DocumentRoot /opt/homebrew/var/www/Kicks/public
    
    <Directory /opt/homebrew/var/www/Kicks/public>
        AllowOverride All
        Require all granted
        
        # Enable URL rewriting
        <IfModule mod_rewrite.c>
            RewriteEngine On
        </IfModule>
    </Directory>
    
    # Restrict access to sensitive directories
    <Directory /opt/homebrew/var/www/Kicks/app>
        Deny from all
    </Directory>
    
    <Directory /opt/homebrew/var/www/Kicks/storage>
        Deny from all
    </Directory>
    
    # Logging (optional)
    ErrorLog ${APACHE_LOG_DIR}/kicks_error.log
    CustomLog ${APACHE_LOG_DIR}/kicks_access.log combined
</VirtualHost>
```

#### 3. Enable mod_rewrite

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 3.a Apply database migrations

The repository includes a SQL migration at `migrations/create_kicks_tables.sql` and a runner script at `scripts/migrate.php`.

Before running the migration, copy `.env.example` to `.env` and set your database credentials in `.env`.

Run the migration with:

```bash
php scripts/migrate.php
```

This will create the `little_kicks` database or the database defined in `DB_NAME`, and the required `sessions` and `movements` tables.


#### 4. Set permissions

```bash
# Allow PHP to write to storage directory (if needed for future features)
chmod 755 /opt/homebrew/var/www/Kicks/storage
```

#### 5. Verify installation

Visit `http://kicks.example.com` in your browser. You should see the Kicks application.

### File Structure in Production

Only the contents of `public/` should be directly accessible via HTTP. The directory structure should be:

```
/opt/homebrew/var/www/Kicks/
├── public/                 ← Apache DocumentRoot
│   ├── index.php
│   ├── router.php
│   ├── .htaccess          ← Routing rules
│   ├── api/
│   │   └── index.php
│   └── assets/            ← Compiled JavaScript and CSS
│       ├── app.js
│       ├── session-store.js
│       └── styles.css
├── app/                    ← NOT publicly accessible
├── api/                    ← NOT publicly accessible
├── src/                    ← Source files (optional in production)
├── scripts/
│   └── build.php
└── storage/               ← NOT publicly accessible
```

### Updating Assets

When source files in `src/` change, recompile the assets:

```bash
cd /opt/homebrew/var/www/Kicks
php scripts/build.php
```

The compiled files in `public/assets/` will be refreshed immediately.

## Security Notes

- The `.htaccess` file prevents direct access to PHP files outside `public/`.
- `mod_rewrite` rules redirect `/api/*` requests to `api/index.php`.
- The API returns sanitized JSON responses and validates all inputs.
- Never commit `.env` files or secrets to the repository.

## Troubleshooting

### 404 errors on all routes

Ensure:
- `mod_rewrite` is enabled: `apache2ctl -M | grep rewrite`
- `.htaccess` has `AllowOverride All` in the VirtualHost
- Apache has read permission on `/opt/homebrew/var/www/Kicks/public/.htaccess`

### Assets not loading (404 on `/assets/*`)

Ensure:
- `php scripts/build.php` has been run
- Files exist in `public/assets/`
- Apache has read permission on `public/assets/*`

### PHP errors

Check Apache error log:
```bash
tail -f /var/log/apache2/kicks_error.log
```

Verify PHP syntax:
```bash
find app api public -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Rolling Back

If a deployment breaks the application:

1. Revert the source files in `src/`
2. Run `php scripts/build.php` again
3. Restart Apache: `sudo systemctl restart apache2`
