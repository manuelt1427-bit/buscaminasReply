<?php
declare(strict_types=1);

namespace Mines\Domain;

use InvalidArgumentException;

final class Game
{
    public const LEVELS = [
        'easy' => ['rows' => 9, 'cols' => 9, 'mines' => 10],
        'medium' => ['rows' => 16, 'cols' => 16, 'mines' => 40],
        'expert' => ['rows' => 16, 'cols' => 30, 'mines' => 99],
    ];

    public static function create(string $level, array $custom = []): array
    {
        if ($level === 'custom') {
            foreach (['rows', 'cols', 'mines'] as $key) {
                if (!isset($custom[$key]) || !is_int($custom[$key])) {
                    throw new InvalidArgumentException('Usa números enteros para configurar el tablero.');
                }
            }
            $config = array_intersect_key($custom, array_flip(['rows', 'cols', 'mines']));
            if ($config['rows'] < 6 || $config['rows'] > 24 || $config['cols'] < 6 || $config['cols'] > 30) {
                throw new InvalidArgumentException('Elige entre 6 y 24 filas y entre 6 y 30 columnas.');
            }
            $maximum = (int) floor($config['rows'] * $config['cols'] * 0.4);
            if ($config['mines'] < 1 || $config['mines'] > $maximum) {
                throw new InvalidArgumentException("Este tablero admite entre 1 y {$maximum} minas.");
            }
        } else {
            $config = self::LEVELS[$level] ?? throw new InvalidArgumentException('La dificultad no es válida.');
        }
        return $config + [
            'id' => bin2hex(random_bytes(12)), 'level' => $level, 'status' => 'ready',
            'startedAt' => null, 'finishedAt' => null, 'exploded' => null,
            'cells' => array_fill(0, $config['rows'] * $config['cols'], [
                'mine' => false, 'number' => 0, 'revealed' => false, 'flagged' => false,
            ]),
        ];
    }

    public static function neighbors(array $game, int $index): array
    {
        $row = intdiv($index, $game['cols']);
        $col = $index % $game['cols'];
        $neighbors = [];
        for ($dr = -1; $dr <= 1; $dr++) {
            for ($dc = -1; $dc <= 1; $dc++) {
                $r = $row + $dr;
                $c = $col + $dc;
                if (($dr !== 0 || $dc !== 0) && $r >= 0 && $r < $game['rows'] && $c >= 0 && $c < $game['cols']) {
                    $neighbors[] = $r * $game['cols'] + $c;
                }
            }
        }
        return $neighbors;
    }

    public static function act(array &$game, string $action, int $row, int $col): void
    {
        if ($row < 0 || $col < 0 || $row >= $game['rows'] || $col >= $game['cols']) {
            throw new InvalidArgumentException('La casilla está fuera del tablero.');
        }
        if (!in_array($action, ['reveal', 'flag', 'chord'], true)) {
            throw new InvalidArgumentException('La acción no es válida.');
        }
        if (in_array($game['status'], ['won', 'lost'], true)) {
            return;
        }
        $index = $row * $game['cols'] + $col;
        $cell = $game['cells'][$index];
        if ($action === 'flag') {
            if (!$cell['revealed']) {
                $game['cells'][$index]['flagged'] = !$cell['flagged'];
            }
            return;
        }
        if ($action === 'chord') {
            if (!$cell['revealed'] || $cell['number'] === 0) {
                return;
            }
            $neighbors = self::neighbors($game, $index);
            $flags = count(array_filter($neighbors, fn (int $i): bool => $game['cells'][$i]['flagged']));
            if ($flags !== $cell['number']) {
                return;
            }
            foreach ($neighbors as $neighbor) {
                self::uncover($game, $neighbor);
                if ($game['status'] === 'lost') {
                    break;
                }
            }
        } else {
            if ($cell['revealed'] || $cell['flagged']) {
                return;
            }
            if ($game['status'] === 'ready') {
                self::plant($game, $index);
                $game['startedAt'] = time();
                $game['status'] = 'playing';
            }
            self::uncover($game, $index);
        }
        if ($game['status'] === 'playing') {
            $revealed = count(array_filter($game['cells'], fn (array $c): bool => $c['revealed']));
            if ($revealed === count($game['cells']) - $game['mines']) {
                $game['status'] = 'won';
                $game['finishedAt'] = time();
                foreach ($game['cells'] as &$remaining) {
                    if ($remaining['mine']) {
                        $remaining['flagged'] = true;
                    }
                }
                unset($remaining);
            }
        }
    }

    private static function plant(array &$game, int $first): void
    {
        $excluded = array_fill_keys([...self::neighbors($game, $first), $first], true);
        $available = array_values(array_filter(array_keys($game['cells']), fn (int $i): bool => !isset($excluded[$i])));
        for ($i = 0; $i < $game['mines']; $i++) {
            $pick = random_int($i, count($available) - 1);
            [$available[$i], $available[$pick]] = [$available[$pick], $available[$i]];
            $game['cells'][$available[$i]]['mine'] = true;
        }
        foreach ($game['cells'] as $i => $cell) {
            $game['cells'][$i]['number'] = count(array_filter(
                self::neighbors($game, $i), fn (int $n): bool => $game['cells'][$n]['mine']
            ));
        }
    }

    private static function uncover(array &$game, int $index): void
    {
        if ($game['cells'][$index]['revealed'] || $game['cells'][$index]['flagged']) {
            return;
        }
        if ($game['cells'][$index]['mine']) {
            $game['cells'][$index]['revealed'] = true;
            $game['status'] = 'lost';
            $game['finishedAt'] = time();
            $game['exploded'] = $index;
            return;
        }
        $queue = [$index];
        $game['cells'][$index]['revealed'] = true;
        for ($head = 0; $head < count($queue); $head++) {
            $current = $queue[$head];
            if ($game['cells'][$current]['number'] !== 0) {
                continue;
            }
            foreach (self::neighbors($game, $current) as $next) {
                $cell = $game['cells'][$next];
                if (!$cell['revealed'] && !$cell['flagged'] && !$cell['mine']) {
                    $game['cells'][$next]['revealed'] = true;
                    $queue[] = $next;
                }
            }
        }
    }

    public static function visible(array $game): array
    {
        $finished = in_array($game['status'], ['won', 'lost'], true);
        $cells = [];
        foreach ($game['cells'] as $i => $cell) {
            $public = ['revealed' => $cell['revealed'], 'flagged' => $cell['flagged']];
            if ($cell['revealed']) {
                $public['number'] = $cell['number'];
            }
            if ($finished) {
                $public['mine'] = $cell['mine'];
                $public['exploded'] = $game['exploded'] === $i;
            }
            $cells[] = $public;
        }
        return array_intersect_key($game, array_flip(['id', 'level', 'rows', 'cols', 'mines', 'status'])) + [
            'flags' => count(array_filter($game['cells'], fn (array $c): bool => $c['flagged'])),
            'elapsed' => $game['startedAt'] === null ? 0 : ($game['finishedAt'] ?? time()) - $game['startedAt'],
            'cells' => $cells,
        ];
    }
}
