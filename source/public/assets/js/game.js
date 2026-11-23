'use strict';

(() => {
    const $ = (id) => document.getElementById(id);
    const board = $('board');
    let game = null;
    let csrf = '';
    let busy = false;
    let flagMode = false;
    let elapsedBase = 0;
    let syncedAt = Date.now();
    let focusedIndex = 0;
    let builtFor = '';
    let prefs = { theme: 'garden', name: '', custom: { rows: 9, cols: 9, mines: 10 } };

    try {
        const saved = JSON.parse(localStorage.getItem('minas.preferences') || '{}');
        if (['garden', 'ocean', 'night'].includes(saved.theme)) prefs.theme = saved.theme;
        if (typeof saved.name === 'string') prefs.name = saved.name.slice(0, 24);
        if (saved.custom && ['rows', 'cols', 'mines'].every((key) => Number.isInteger(saved.custom[key]))) {
            prefs.custom = saved.custom;
        }
    } catch { /* El juego funciona aunque el almacenamiento local esté bloqueado. */ }

    function savePrefs() {
        try { localStorage.setItem('minas.preferences', JSON.stringify(prefs)); } catch { /* Opcional. */ }
    }

    function applyPrefs() {
        document.documentElement.dataset.theme = prefs.theme;
        document.querySelectorAll('[data-theme-choice]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.themeChoice === prefs.theme));
        });
        $('greeting').textContent = prefs.name.trim()
            ? `${prefs.name.trim()}, esta pausa es tuya. Encuentra el camino entre las minas.`
            : 'Despeja tu mente. Encuentra el camino entre las minas.';
    }

    function showError(message, element = $('error')) {
        element.textContent = message;
        element.hidden = !message;
    }

    async function request(action, input = null) {
        if (busy) return false;
        busy = true;
        board.setAttribute('aria-busy', 'true');
        const timeout = new AbortController();
        const timer = setTimeout(() => timeout.abort(), 15000);
        showError('');
        try {
            const options = { credentials: 'same-origin', cache: 'no-store', signal: timeout.signal };
            if (input !== null) {
                options.method = 'POST';
                options.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf };
                options.body = JSON.stringify(input);
            }
            const response = await fetch(`index.php?action=${encodeURIComponent(action)}`, options);
            const result = await response.json();
            if (!response.ok || !result.ok) {
                if (response.status === 409) setTimeout(() => request('state'), 0);
                throw new Error(result.message || 'Algo salió mal. Inténtalo de nuevo.');
            }
            csrf = result.csrf;
            game = result.game;
            elapsedBase = game.elapsed;
            syncedAt = Date.now();
            render();
            return true;
        } catch (error) {
            showError(error.name === 'AbortError' ? 'La conexión tardó demasiado. Inténtalo de nuevo.' : (error instanceof SyntaxError || error instanceof TypeError ? 'No pudimos conectar con el juego. Verifica que PHP siga ejecutándose e inténtalo de nuevo.' : error.message));
            return false;
        } finally {
            clearTimeout(timer);
            busy = false;
            board.setAttribute('aria-busy', 'false');
        }
    }

    function newGame(level = game?.level || 'easy', custom = null) {
        const config = custom || (level === 'custom' && game ? { rows: game.rows, cols: game.cols, mines: game.mines } : {});
        return request('new', { level, ...config });
    }

    function act(action, index) {
        if (!game || busy || ['won', 'lost'].includes(game.status)) return;
        return request(action, { gameId: game.id, row: Math.floor(index / game.cols), col: index % game.cols });
    }

    function formatTime(seconds) {
        return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    }

    function updateTimer() {
        const seconds = elapsedBase + (game?.status === 'playing' ? Math.floor((Date.now() - syncedAt) / 1000) : 0);
        $('timer').textContent = formatTime(seconds);
    }

    function buildBoard() {
        board.replaceChildren();
        board.classList.toggle('medium', game.cols > 10 && game.cols <= 16);
        board.classList.toggle('wide', game.cols > 16);
        board.setAttribute('aria-rowcount', game.rows);
        board.setAttribute('aria-colcount', game.cols);
        const fragment = document.createDocumentFragment();
        for (let row = 0; row < game.rows; row++) {
            const line = document.createElement('div');
            line.className = 'board-row';
            line.setAttribute('role', 'row');
            for (let col = 0; col < game.cols; col++) {
                const cell = document.createElement('button');
                cell.type = 'button';
                cell.setAttribute('role', 'gridcell');
                cell.dataset.index = row * game.cols + col;
                cell.dataset.alt = (row + col) % 2 ? '1' : '0';
                cell.tabIndex = -1;
                line.append(cell);
            }
            fragment.append(line);
        }
        board.append(fragment);
        focusedIndex = 0;
        builtFor = game.id;
    }

    function render() {
        if (builtFor !== game.id) buildBoard();
        const ended = ['won', 'lost'].includes(game.status);
        board.querySelectorAll('.board-row > button').forEach((button, index) => {
            const cell = game.cells[index];
            const wrong = ended && cell.flagged && !cell.mine;
            const showMine = ended && cell.mine && !cell.flagged;
            const classes = ['cell'];
            if (button.dataset.alt === '1') classes.push('alt');
            if (cell.revealed) classes.push('revealed');
            if (cell.flagged) classes.push('flagged');
            if (showMine) classes.push('mine');
            if (cell.exploded) classes.push('exploded');
            if (wrong) classes.push('wrong');
            button.className = classes.join(' ');
            button.textContent = wrong ? '×' : cell.flagged ? '⚑' : showMine ? '✳' : cell.revealed && cell.number ? cell.number : '';
            button.dataset.number = cell.revealed ? cell.number : '';
            button.tabIndex = index === focusedIndex ? 0 : -1;
            const description = wrong ? 'bandera incorrecta' : cell.flagged ? 'bandera' : showMine ? 'mina' : cell.revealed ? (cell.number ? `${cell.number} minas alrededor` : 'vacía') : 'sin descubrir';
            button.setAttribute('aria-label', `Fila ${Math.floor(index / game.cols) + 1}, columna ${index % game.cols + 1}: ${description}`);
            button.setAttribute('aria-disabled', String(ended));
        });
        $('remaining').textContent = String(game.mines - game.flags).padStart(3, '0');
        $('board-description').textContent = `${game.rows} × ${game.cols} · ${game.mines} minas`;
        document.querySelectorAll('[data-level]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.level === game.level)));
        const messages = { ready: 'Tu primer clic está a salvo.', playing: 'Una casilla a la vez.', won: '¡Tablero despejado!', lost: 'Una mina. Otra oportunidad.' };
        $('game-status').textContent = messages[game.status];
        $('restart').textContent = game.status === 'won' ? '☀' : game.status === 'lost' ? '☹' : '☺';
        $('result').hidden = !ended;
        if (ended) {
            $('result-title').textContent = game.status === 'won' ? '¡Lo resolviste! Bien jugado.' : 'Hasta los mejores pisan una mina.';
            $('result-description').textContent = game.status === 'won' ? `${game.rows} × ${game.cols} casillas. ${game.mines} minas. Tu tiempo: ${formatTime(game.elapsed)}.` : 'Respira, reinicia y prueba un nuevo camino.';
        }
        updateTimer();
    }

    board.addEventListener('click', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button || !game) return;
        const index = Number(button.dataset.index);
        focusedIndex = index;
        if (flagMode) { act('flag', index); return; }
        if (game.cells[index].revealed) return;
        act('reveal', index);
    });
    board.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        const button = event.target.closest('[data-index]');
        if (button) { focusedIndex = Number(button.dataset.index); act('flag', focusedIndex); }
    });
    board.addEventListener('dblclick', (event) => {
        const button = event.target.closest('[data-index]');
        if (button && !flagMode) act('chord', Number(button.dataset.index));
    });
    board.addEventListener('focusin', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button) return;
        board.querySelector('[tabindex="0"]')?.setAttribute('tabindex', '-1');
        focusedIndex = Number(button.dataset.index);
        button.tabIndex = 0;
    });
    board.addEventListener('keydown', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button || !game) return;
        const index = Number(button.dataset.index);
        const row = Math.floor(index / game.cols);
        const col = index % game.cols;
        let next = index;
        if (event.key === 'ArrowLeft') next = row * game.cols + Math.max(0, col - 1);
        else if (event.key === 'ArrowRight') next = row * game.cols + Math.min(game.cols - 1, col + 1);
        else if (event.key === 'ArrowUp') next = Math.max(0, row - 1) * game.cols + col;
        else if (event.key === 'ArrowDown') next = Math.min(game.rows - 1, row + 1) * game.cols + col;
        else if (event.key.toLowerCase() === 'f') { event.preventDefault(); act('flag', index); return; }
        else if (event.key.toLowerCase() === 'd') { event.preventDefault(); act('chord', index); return; }
        else return;
        event.preventDefault();
        board.querySelector(`[data-index="${next}"]`)?.focus();
    });

    $('restart').addEventListener('click', () => newGame());
    $('play-again').addEventListener('click', () => newGame());
    $('flag-mode').addEventListener('click', () => {
        flagMode = !flagMode;
        $('flag-mode').setAttribute('aria-pressed', String(flagMode));
        $('flag-mode-label').textContent = flagMode ? 'ON' : 'OFF';
    });
    document.querySelectorAll('[data-level]').forEach((button) => button.addEventListener('click', () => {
        if (button.dataset.level === 'custom') openSettings();
        else newGame(button.dataset.level);
    }));

    function updateCustomMaximum() {
        const max = Math.floor(Number($('custom-rows').value) * Number($('custom-cols').value) * 0.4);
        const validMax = Number.isFinite(max) && max > 0 ? max : 1;
        $('custom-mines').max = validMax;
        $('custom-hint').textContent = `Entre 1 y ${validMax} minas para este tablero (máximo 40%).`;
    }
    function openSettings() {
        const config = game?.level === 'custom' ? game : prefs.custom;
        $('player-name').value = prefs.name;
        for (const key of ['rows', 'cols', 'mines']) $(`custom-${key}`).value = config[key];
        updateCustomMaximum();
        showError('', $('custom-error'));
        $('settings-dialog').showModal();
    }
    $('settings-open').addEventListener('click', openSettings);
    $('help-open').addEventListener('click', () => $('help-dialog').showModal());
    document.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => $(button.dataset.close).close()));
    document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    }));
    $('player-name').addEventListener('input', () => { prefs.name = $('player-name').value.slice(0, 24); savePrefs(); applyPrefs(); });
    document.querySelectorAll('[data-theme-choice]').forEach((button) => button.addEventListener('click', () => { prefs.theme = button.dataset.themeChoice; savePrefs(); applyPrefs(); }));
    $('custom-rows').addEventListener('input', updateCustomMaximum);
    $('custom-cols').addEventListener('input', updateCustomMaximum);
    $('custom-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!$('custom-form').reportValidity() || busy) return;
        const custom = Object.fromEntries(['rows', 'cols', 'mines'].map((key) => [key, Number($(`custom-${key}`).value)]));
        const submit = $('custom-form').querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            if (await newGame('custom', custom)) {
                prefs.custom = custom;
                savePrefs();
                $('settings-dialog').close();
            } else {
                showError($('error').textContent, $('custom-error'));
            }
        } finally { submit.disabled = false; }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && !busy) request('state'); });
    applyPrefs();
    setInterval(updateTimer, 1000);
    request('state');
})();
