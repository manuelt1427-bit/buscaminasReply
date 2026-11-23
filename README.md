# Videojuego Buscaminas

Juego local en PHP 8.5, HTML, CSS y JavaScript. Sin framework, base de datos ni compilacion de recursos.

## Jugar en Windows

Haz doble clic en **Jugar.cmd**, en la carpeta del proyecto. Detecta PHP, inicia el servidor local y abre el navegador. Si Windows oculta las extensiones, elige Jugar de tipo **Script de comandos de Windows**.

La direccion inicial es http://127.0.0.1:8000/. Si el puerto esta ocupado por otra aplicacion, busca uno libre hasta el 8010. Puedes volver a ejecutar Jugar.cmd para abrir el juego cuando el servidor siga activo. Reiniciar Windows detiene el servidor; ejecuta Jugar.cmd de nuevo para jugar.

El lanzador busca PHP en PATH, Herd para PHP 8.5 y las ubicaciones habituales de XAMPP. En este equipo se utiliza PHP 8.5 de Herd.

## Inicio manual

Desde la raiz del proyecto:

```sh
php -S 127.0.0.1:8000 -t source/public
```

Abre http://127.0.0.1:8000/ y conserva la terminal abierta. Ctrl+C detiene el servidor. No abras index.php directamente como archivo.

## Controles

| Accion | Control |
| --- | --- |
| Descubrir casilla | Clic izquierdo, Enter o espacio |
| Colocar o quitar bandera | Clic derecho, F o modo bandera |
| Despejar alrededor de un numero | Doble clic o D, con sus banderas colocadas |
| Moverse por el tablero | Flechas del teclado |
| Nueva partida | Boton central con la cara |

Incluye dificultades facil (9x9, 10 minas), media (16x16, 40 minas), experta (16x30, 99 minas) y tableros personalizados. El primer descubrimiento y sus vecinos son seguros. Ganas descubriendo todas las casillas sin minas. Puedes cambiar tema y nombre desde Personalizar.

La partida se guarda en una sesion PHP. Las pestanas del mismo navegador comparten partida. El nombre y el tema se guardan en ese navegador.

## Codigo y comprobaciones

| Ruta | Responsabilidad |
| --- | --- |
| source/public/ | Entrada web y recursos publicos |
| source/app/Domain/Game.php | Reglas del tablero |
| source/app/Controllers/ | Validacion de peticiones y respuestas JSON |
| source/app/Services/ | Acciones de la partida |
| source/app/Repositories/ | Persistencia en sesion |
| source/app/Views/ | Interfaz |
| source/tests/game_test.php | Pruebas del motor |
| docs/ | Documentacion del proyecto |

```sh
php source/tests/game_test.php
```

Las pruebas cubren minas, conteos de vecinos, primer clic seguro, banderas, victoria, derrota, despeje y entradas invalidas. El servidor conserva las minas ocultas y valida las acciones mediante un token de sesion.
