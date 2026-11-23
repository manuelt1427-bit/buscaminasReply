<?php
declare(strict_types=1);

namespace Mines\Repositories;

final class SessionGameRepository
{
    public function get(): ?array
    {
        return $_SESSION['game'] ?? null;
    }

    public function save(array $game): void
    {
        $_SESSION['game'] = $game;
    }
}
