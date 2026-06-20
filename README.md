# Kicks

Contador web gratuito de movimientos durante el embarazo. Funciona sin cuenta y guarda la información únicamente en el navegador.

## Requisitos

- PHP 8.2+
- Apache 2.4+ con módulo `mod_rewrite` habilitado

## Instalación en producción

1. **Clonar o descargar el repositorio** en `/opt/homebrew/var/www/Kicks` (o la ruta elegida).

2. **Crear el archivo de entorno**.
   ```bash
   cp .env.example .env
   ```
   Luego edita `.env` con tus credenciales de MySQL:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`

3. **Compilar los assets** (una única vez):
   ```bash
   php scripts/build.php
   ```
   Esto copia los archivos fuente de `src/` a `public/assets/`.

4. **Configurar Apache** para que `DocumentRoot` apunte a `public/`:
   ```apache
   <VirtualHost *:80>
       ServerName kicks.local
       DocumentRoot /opt/homebrew/var/www/Kicks/public
       <Directory /opt/homebrew/var/www/Kicks/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

5. **Habilitar mod_rewrite** (si no está activo):
   ```bash
   sudo a2enmod rewrite
   sudo systemctl reload apache2
   ```

6. **Aplicar migraciones**:
   ```bash
   php scripts/migrate.php
   ```

7. **Acceder** a la aplicación en el navegador (ej. `http://kicks.local`).

**No se requiere Node.js en producción.** Los assets están pre-compilados y listos para servir.

## Desarrollo local

Requisitos: PHP 8.2+.

```bash
# Copia el archivo de entorno de ejemplo y ajusta si lo necesitas
cp .env.example .env

# Compilar assets (después de cambios en src/)
php scripts/build.php

# Ejecutar servidor local de PHP
php -S 127.0.0.1:8086 -t public public/router.php
```

Abrir `http://127.0.0.1:8086`.

## Verificación

Validar sintaxis PHP:
```bash
find app api public -name '*.php' -print0 | xargs -0 -n1 php -l
```

Verificar que los assets se hayan compilado correctamente:
```bash
ls -la public/assets/
```
