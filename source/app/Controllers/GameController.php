<?php
declare(strict_types=1);

namespace Mines\Controllers;

use Mines\Services\GameService;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

final class GameController
{
    public function __construct(private GameService $service) {}

    public function handle(string $action, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $expected = $action === 'state' ? 'GET' : 'POST';
            if ($method !== $expected) {
                header('Allow: ' . $expected);
                $this->respond(['ok' => false, 'message' => 'Método no permitido.'], 405);
                return;
            }
            if ($action === 'state') {
                $game = $this->service->state();
            } else {
                if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) {
                    $this->respond(['ok' => false, 'message' => 'La petición es demasiado grande.'], 413);
                    return;
                }
                $input = json_decode(file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
                if (!is_array($input)) {
                    throw new InvalidArgumentException('La petición no es válida.');
                }
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                if (!hash_equals($_SESSION['csrf'], $token)) {
                    $this->respond(['ok' => false, 'message' => 'La sesión cambió. Recarga la página para continuar.'], 403);
                    return;
                }
                $game = $this->service->execute($action, $input);
            }
            $this->respond(['ok' => true, 'game' => $game, 'csrf' => $_SESSION['csrf']]);
        } catch (InvalidArgumentException | JsonException $error) {
            $this->respond(['ok' => false, 'message' => $error instanceof JsonException ? 'El formato JSON no es válido.' : $error->getMessage()], 422);
        } catch (RuntimeException $error) {
            if ($error->getCode() === 409) {
                $this->respond(['ok' => false, 'message' => $error->getMessage()], 409);
            } else {
                error_log((string) $error);
                $this->respond(['ok' => false, 'message' => 'Algo salió mal. Inténtalo de nuevo.'], 500);
            }
        } catch (Throwable $error) {
            error_log((string) $error);
            $this->respond(['ok' => false, 'message' => 'Algo salió mal. Inténtalo de nuevo.'], 500);
        }
    }

    private function respond(array $body, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
