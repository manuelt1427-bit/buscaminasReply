# Arquitectura

Aplicacion monolitica en PHP 8.5 con JavaScript nativo. El juego ya esta implementado.

## Componentes

- source/public/index.php: entrada HTTP y despacho de acciones.
- source/bootstrap.php: carga de clases, sesion y cabeceras.
- source/routes.php: acciones permitidas.
- source/app/Controllers/GameController.php: valida metodo, JSON y token CSRF.
- source/app/Services/GameService.php: coordina operaciones y persistencia.
- source/app/Domain/Game.php: genera minas, calcula vecinos y aplica reglas.
- source/app/Repositories/SessionGameRepository.php: guarda una partida en la sesion PHP.
- source/app/Views/game.php y source/public/assets/: interfaz y controles.

Solo source/public se expone como raiz web. Las acciones state (GET), new, reveal, flag y chord (POST) devuelven el estado visible. Las minas permanecen ocultas en el servidor hasta terminar la partida.

## Reglas

El primer descubrimiento excluye minas de la casilla y sus vecinos. Las zonas vacias se abren automaticamente. Las banderas protegen casillas de clics accidentales. Se gana descubriendo todas las casillas sin minas. El despeje de un numero requiere tantas banderas vecinas como indique; una bandera equivocada puede causar derrota.

Las pestanas del mismo navegador comparten partida. Un identificador de partida permite detectar jugadas sobre un tablero reemplazado. El cronometro lo calcula el servidor; JavaScript lo actualiza visualmente entre jugadas.

## Verificacion

Ejecuta php source/tests/game_test.php desde la raiz. Cubre primer clic, minas, vecinos, banderas, apertura, victoria, derrota, despeje y limites personalizados.
