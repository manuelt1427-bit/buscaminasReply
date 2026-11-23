<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Domain/Game.php';

use Mines\Domain\Game;

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function rejects(callable $operation): void
{
    try {
        $operation();
    } catch (InvalidArgumentException) {
        check(true, 'Entrada inválida rechazada.');
        return;
    }
    check(false, 'Se aceptó una entrada inválida.');
}

foreach (['easy', 'medium', 'expert', 'custom'] as $level) {
    for ($attempt = 0; $attempt < 8; $attempt++) {
        $game = Game::create($level, ['rows' => 6, 'cols' => 6, 'mines' => 14]);
        check(count($game['cells']) === $game['rows'] * $game['cols'], 'Dimensiones incorrectas.');
        $row = $attempt % $game['rows'];
        $col = ($attempt * 3) % $game['cols'];
        $index = $row * $game['cols'] + $col;
        Game::act($game, 'reveal', $row, $col);
        check($game['status'] !== 'lost', 'El primer clic causó una derrota.');
        check($game['cells'][$index]['number'] === 0, 'El primer clic no dejó un área segura.');
        check(count(array_filter($game['cells'], fn ($c) => $c['mine'])) === $game['mines'], 'Cantidad de minas incorrecta.');
        foreach ($game['cells'] as $i => $cell) {
            $expected = count(array_filter(Game::neighbors($game, $i), fn ($n) => $game['cells'][$n]['mine']));
            check($cell['number'] === $expected, 'Conteo de minas vecinas incorrecto.');
        }
        $visible = Game::visible($game);
        foreach ($visible['cells'] as $cell) {
            check(!array_key_exists('mine', $cell), 'Se filtraron minas al cliente.');
            if (!$cell['revealed']) check(!array_key_exists('number', $cell), 'Se filtró un número oculto.');
        }
        foreach ($game['cells'] as $i => $cell) {
            if (!$cell['mine']) Game::act($game, 'reveal', intdiv($i, $game['cols']), $i % $game['cols']);
        }
        check($game['status'] === 'won', 'No se detectó la victoria.');
        check(Game::visible($game)['flags'] === $game['mines'], 'No se marcaron las minas al ganar.');
        $finished = $game;
        Game::act($game, 'flag', 0, 0);
        check($finished === $game, 'Se permitió modificar una partida terminada.');
    }
}

$game = Game::create('easy');
Game::act($game, 'flag', 0, 0);
Game::act($game, 'reveal', 0, 0);
check($game['status'] === 'ready' && !$game['cells'][0]['revealed'], 'Se descubrió una casilla con bandera.');
Game::act($game, 'flag', 0, 0);
Game::act($game, 'reveal', 0, 0);
$mine = array_find_key($game['cells'], fn ($c) => $c['mine']);
Game::act($game, 'reveal', intdiv($mine, $game['cols']), $mine % $game['cols']);
check($game['status'] === 'lost' && $game['exploded'] === $mine, 'No se detectó la derrota.');
check(Game::visible($game)['cells'][$mine]['mine'], 'No se revelaron las minas al perder.');

// Tablero conocido para verificar despeje correcto y banderas equivocadas.
function fixture(): array
{
    $game = Game::create('easy');
    $game['status'] = 'playing';
    $game['startedAt'] = time();
    $game['mines'] = 1;
    $game['cells'][0]['mine'] = true;
    foreach ($game['cells'] as $i => $_) {
        $game['cells'][$i]['number'] = in_array(0, Game::neighbors($game, $i), true) ? 1 : 0;
    }
    $game['cells'][1]['revealed'] = true;
    return $game;
}
$game = fixture();
Game::act($game, 'chord', 0, 1);
check(!$game['cells'][2]['revealed'], 'Se despejó sin suficientes banderas.');
Game::act($game, 'flag', 0, 0);
Game::act($game, 'chord', 0, 1);
check($game['status'] === 'won', 'No se despejaron los vecinos con banderas correctas.');
$game = fixture();
Game::act($game, 'flag', 0, 2);
Game::act($game, 'chord', 0, 1);
check($game['status'] === 'lost', 'Una bandera equivocada no detonó la mina al despejar.');

rejects(fn () => Game::create('invalid'));
rejects(fn () => Game::create('custom', ['rows' => 5, 'cols' => 6, 'mines' => 1]));
rejects(fn () => Game::create('custom', ['rows' => 6, 'cols' => 31, 'mines' => 1]));
rejects(fn () => Game::create('custom', ['rows' => 6, 'cols' => 6, 'mines' => 15]));
rejects(fn () => Game::create('custom', ['rows' => 6, 'cols' => 6, 'mines' => 0]));
rejects(fn () => Game::create('custom', ['rows' => '6', 'cols' => 6, 'mines' => 1]));
$game = Game::create('easy');
rejects(function () use (&$game) { Game::act($game, 'reveal', -1, 0); });
rejects(function () use (&$game) { Game::act($game, 'reveal', 0, 9); });
$maximum = Game::create('custom', ['rows' => 24, 'cols' => 30, 'mines' => 288]);
Game::act($maximum, 'reveal', 12, 15);
check($maximum['status'] !== 'lost', 'Falló el tablero máximo.');
echo "OK: {$checks} comprobaciones del motor de Buscaminas.\n";
