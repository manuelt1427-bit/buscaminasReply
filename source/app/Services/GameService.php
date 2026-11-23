<?php
declare(strict_types=1);

namespace Mines\Services;

use Mines\Domain\Game;
use Mines\Repositories\SessionGameRepository;
use InvalidArgumentException;
use RuntimeException;

final class GameService
{
    public function __construct(private SessionGameRepository $repository) {}

    public function state(): array
    {
        $game = $this->repository->get();
        if ($game === null) {
            $game = Game::create('easy');
            $this->repository->save($game);
        }
        return Game::visible($game);
    }

    public function execute(string $action, array $input): array
    {
        if ($action === 'new') {
            if (!is_string($input['level'] ?? null)) {
                throw new InvalidArgumentException('Elige una dificultad.');
            }
            $game = Game::create($input['level'], $input);
        } else {
            $game = $this->repository->get();
            if ($game === null || ($input['gameId'] ?? null) !== $game['id']) {
                throw new RuntimeException('La partida cambió en otra pestaña. Actualiza para continuar.', 409);
            }
            if (!isset($input['row'], $input['col']) || !is_int($input['row']) || !is_int($input['col'])) {
                throw new InvalidArgumentException('Las coordenadas deben ser números enteros.');
            }
            Game::act($game, $action, $input['row'], $input['col']);
        }
        $this->repository->save($game);
        return Game::visible($game);
    }
}
