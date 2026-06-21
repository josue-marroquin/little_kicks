# Kicks

Contador de movimientos con frontend JavaScript y un backend PHP mínimo.

## Funcionamiento

- El conteo activo existe únicamente en memoria mientras la página permanece abierta.
- Al finalizar, el frontend envía la sesión completa una sola vez a sessions.php.
- sessions.php guarda la sesión y sus movimientos en MySQL dentro de una transacción.
- El mismo archivo devuelve el historial mediante GET.
- No hay usuarios, autenticación, sesiones PHP, router, base local ni almacenamiento en el navegador.

## Configuración

La conexión utiliza exclusivamente el archivo .env:

    DB_HOST=192.168.1.25
    DB_PORT=3306
    DB_NAME=little_kicks
    DB_USER=...
    DB_PASS=...

Las tablas existentes requeridas son sessions y movements.

## Compilar y verificar

    php scripts/build.php
    npm test
    php -l index.php
    php -l sessions.php

Con Apache sirviendo /opt/homebrew/var/www, la aplicación se abre directamente en:

    http://localhost:8080/kicks/index.php
