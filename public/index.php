<?php

declare(strict_types=1);

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3c5d4b">
    <meta name="description" content="Contador de patadas gratuito, privado y sin anuncios.">
    <title>Kicks — Contador de patadas</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <main class="app-shell">
        <header class="brand">
            <span class="brand__mark" aria-hidden="true">K</span>
            <div>
                <p class="brand__name"><strong>Kicks</strong></p>
                <p class="brand__tagline">Simple. Privado. Siempre gratis.</p>
            </div>
        </header>

        <section id="counter-view">
            <div class="hero">
                <p class="eyebrow">Un momento para conectar</p>
                <h1>Cuenta cada<br>movimiento</h1>
                <p class="hero__copy">Registra los movimientos de forma sencilla y guarda un historial en este dispositivo.</p>
            </div>

            <div id="empty-state" class="panel empty-state">
                <div class="empty-state__icon" aria-hidden="true">✦</div>
                <h2>¿Lista para comenzar?</h2>
                <p>Inicia una sesión cuando quieras prestar atención a los movimientos.</p>
                <button id="start-session" class="primary-button" type="button">Iniciar conteo</button>
            </div>

            <div id="active-state" class="panel" hidden>
                <div class="counter-meta">
                    <div>
                        <span class="counter-meta__label">Movimientos</span>
                        <strong id="kick-count">0</strong>
                    </div>
                    <div class="timer">
                        <span class="timer__label">Tiempo</span>
                        <strong id="timer">00:00</strong>
                    </div>
                </div>

                <button id="record-kick" class="kick-button" type="button">
                    <span class="kick-button__icon" aria-hidden="true">✦</span>
                    <span class="kick-button__label">Sentí una patada</span>
                    <span class="kick-button__hint">Toca para registrar</span>
                </button>

                <label class="note-field">
                    Nota opcional
                    <textarea id="session-note" maxlength="500" placeholder="¿Cómo te sentiste?"></textarea>
                </label>

                <div class="actions">
                    <button id="undo-kick" class="secondary-button" type="button">Deshacer</button>
                    <button id="finish-session" class="secondary-button secondary-button--finish" type="button">Finalizar y guardar</button>
                </div>
            </div>

            <p class="privacy-note">Tus registros permanecen en este navegador. Kicks no sustituye la orientación de un profesional de salud.</p>
        </section>

        <section id="history-view" hidden>
            <header class="history-header">
                <p class="eyebrow">Tus registros</p>
                <h2>Historial</h2>
                <p>Sesiones guardadas en este dispositivo.</p>
            </header>
            <div id="history-empty" class="history-empty">Aún no hay sesiones guardadas.</div>
            <div id="history-list" class="history-list"></div>
        </section>
    </main>

    <nav class="nav" aria-label="Navegación principal">
        <button class="nav__button nav__button--active" type="button" data-view="counter" aria-current="page">Contador</button>
        <button class="nav__button" type="button" data-view="history" aria-current="false">Historial</button>
    </nav>

    <p id="status-message" class="sr-only" role="status" aria-live="polite"></p>
    <script type="module" src="/assets/app.js"></script>
</body>
</html>
