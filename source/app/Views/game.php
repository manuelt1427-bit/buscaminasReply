<!doctype html>
<html lang="es" data-theme="garden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f5ef">
    <meta name="description" content="Tu pausa, tu tablero. Juega Buscaminas y personaliza cada partida.">
    <title>Minas · Un clásico, a tu manera</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/game.css">
    <script src="assets/js/game.js" defer></script>
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <a class="brand" href="./" aria-label="Minas, inicio"><span class="brand-icon" aria-hidden="true">✳</span> minas<span class="brand-dot">.</span></a>
        <div class="header-right"><span class="edition">UN CLÁSICO, A TU MANERA</span><button id="settings-open" class="button button-outline" type="button"><span aria-hidden="true">⚙</span> Personalizar</button></div>
    </header>
    <main>
        <section class="intro" aria-labelledby="page-title">
            <div class="eyebrow"><span class="status-dot"></span> MENOS PRISA. MÁS ESTRATEGIA.</div>
            <h1 id="page-title">Una casilla.<br>Mil posibilidades<span>.</span></h1>
            <p id="greeting">Despeja tu mente. Encuentra el camino entre las minas.</p>
        </section>
        <section class="game-section" aria-label="Juego Buscaminas">
            <div class="game-toolbar">
                <div class="difficulty" role="group" aria-label="Dificultad">
                    <button type="button" data-level="easy" aria-pressed="true">Fácil</button>
                    <button type="button" data-level="medium" aria-pressed="false">Medio</button>
                    <button type="button" data-level="expert" aria-pressed="false">Experto</button>
                    <button type="button" data-level="custom" aria-pressed="false">A tu medida <span aria-hidden="true">↗</span></button>
                </div>
                <span id="board-description" class="board-description">9 × 9 · 10 minas</span>
            </div>
            <div class="game-card" id="game-card">
                <div class="scoreboard">
                    <div class="metric"><span class="metric-icon" aria-hidden="true">⚑</span><div><span class="metric-label">MINAS RESTANTES</span><strong id="remaining">010</strong></div></div>
                    <button type="button" class="face" id="restart" title="Nueva partida" aria-label="Reiniciar partida">☺</button>
                    <div class="metric metric-time"><span class="metric-icon clock-icon" aria-hidden="true">◷</span><div><span class="metric-label">TIEMPO</span><strong id="timer">00:00</strong></div></div>
                </div>
                <div class="board-viewport" tabindex="0" role="region" aria-label="Tablero desplazable">
                    <div id="board" class="board" role="grid" aria-label="Tablero de Buscaminas" aria-busy="true"></div>
                </div>
                <div class="game-bottom"><div class="game-message"><span id="game-dot" class="status-dot"></span><span id="game-status" role="status" aria-live="polite">Preparando tu tablero…</span></div><button id="flag-mode" class="flag-toggle" type="button" aria-pressed="false">⚑ Modo bandera <span id="flag-mode-label">OFF</span></button></div>
            </div>
            <div id="result" class="result" hidden role="status"><div><strong id="result-title"></strong><p id="result-description"></p></div><button id="play-again" class="button button-primary" type="button">Otra partida <span aria-hidden="true">↗</span></button></div>
            <p id="error" class="error" role="alert" hidden></p>
            <div class="how-to"><span><span class="key-icon" aria-hidden="true">↖</span> Clic para descubrir</span><span><span class="key-icon" aria-hidden="true">⚑</span> Clic derecho para marcar</span><span><kbd>2×</kbd> Doble clic en un número para despejar</span><button id="help-open" type="button">¿Cómo se juega? <span aria-hidden="true">↗</span></button></div>
        </section>
        <aside class="little-note"><span aria-hidden="true">✧</span><p>Tu primer clic siempre está a salvo.<br><span>Lo demás, déjaselo a tu intuición.</span></p></aside>
    </main>
    <footer><span>Hecho para una buena pausa.</span><span>PHP 8.5 <span class="footer-dot">·</span> Sin prisa, sin anuncios.</span></footer>
</div>
<dialog id="settings-dialog" aria-labelledby="settings-title">
    <div class="dialog-heading"><div><span class="eyebrow">TU JUEGO, TUS REGLAS</span><h2 id="settings-title">Hazlo tuyo<span>.</span></h2></div><button class="close-dialog" type="button" data-close="settings-dialog" aria-label="Cerrar personalización">×</button></div>
    <label class="field">¿Cómo te llamas?<input id="player-name" type="text" maxlength="24" placeholder="Tu nombre (opcional)" autocomplete="nickname"></label>
    <fieldset class="theme-fieldset"><legend>Elige tu ambiente</legend><div class="theme-options"><button type="button" data-theme-choice="garden" aria-pressed="true"><span class="swatch swatch-garden"></span>Jardín</button><button type="button" data-theme-choice="ocean" aria-pressed="false"><span class="swatch swatch-ocean"></span>Océano</button><button type="button" data-theme-choice="night" aria-pressed="false"><span class="swatch swatch-night"></span>Noche</button></div></fieldset>
    <form id="custom-form">
        <div class="custom-heading"><h3>Un tablero a tu medida</h3><p>Ajusta el reto. Tú decides qué tan lejos llegar.</p></div>
        <div class="custom-fields"><label class="field">Filas<input id="custom-rows" name="rows" type="number" value="9" min="6" max="24" required></label><label class="field">Columnas<input id="custom-cols" name="cols" type="number" value="9" min="6" max="30" required></label><label class="field">Minas<input id="custom-mines" name="mines" type="number" value="10" min="1" max="32" required></label></div>
        <p id="custom-hint" class="field-hint">Entre 1 y 32 minas para este tablero.</p>
        <p id="custom-error" class="error" role="alert" hidden></p>
        <button class="button button-primary full-width" type="submit">Jugar con este tablero <span aria-hidden="true">↗</span></button>
    </form>
    <p class="settings-note">Tu nombre y tema se guardan en este navegador.</p>
</dialog>
<dialog id="help-dialog" aria-labelledby="help-title"><div class="dialog-heading"><h2 id="help-title">Pequeñas pistas<span>.</span></h2><button class="close-dialog" type="button" data-close="help-dialog" aria-label="Cerrar ayuda">×</button></div><ol class="help-list"><li><strong>Descubre sin pisar minas.</strong> Ganas al abrir todas las casillas que están a salvo.</li><li><strong>Los números son tus pistas.</strong> Indican cuántas minas hay en las ocho casillas de alrededor, incluidas las diagonales.</li><li><strong>Marca tus sospechas.</strong> Usa clic derecho o activa el modo bandera para colocar y quitar banderas.</li><li><strong>Despeja más rápido.</strong> Haz doble clic en un número descubierto cuando hayas marcado todas sus minas vecinas. Si tus banderas están mal, puedes perder.</li><li><strong>También con teclado.</strong> Muévete con las flechas. Enter o espacio descubre; F coloca una bandera; D despeja los vecinos de un número.</li></ol><p class="field-hint">Cambiar de dificultad o reiniciar reemplaza la partida actual. Las pestañas de este navegador comparten la misma partida.</p><button type="button" class="button button-primary full-width" data-close="help-dialog">¡A jugar!</button></dialog>
<noscript><p class="error">Activa JavaScript en tu navegador para jugar.</p></noscript>
</body>
</html>
