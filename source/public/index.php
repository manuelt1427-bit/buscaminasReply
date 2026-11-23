<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('X-Minas-Game: 1');

use Mines\Controllers\GameController;
use Mines\Repositories\SessionGameRepository;
use Mines\Services\GameService;

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($action !== null) {
    $routes = require dirname(__DIR__) . '/routes.php';
    if (!is_string($action) || !in_array($action, $routes, true)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'La acción no existe.']);
        exit;
    }
    (new GameController(new GameService(new SessionGameRepository())))->handle($action, $method);
    exit;
}
if ($method !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
require dirname(__DIR__) . '/app/Views/game.php';
