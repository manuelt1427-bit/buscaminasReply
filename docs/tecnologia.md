# Tecnologia

El juego utiliza PHP 8.5, sesiones PHP, HTML5, CSS y JavaScript nativo con Fetch. No necesita Composer, Node.js, framework ni base de datos para jugar.

En Windows, Jugar.cmd llama a Jugar.ps1, detecta PHP y abre el servidor local en 127.0.0.1. El lanzador busca un puerto disponible entre 8000 y 8010. En este equipo esta instalado PHP 8.5 de Herd.

Para inicio manual desde la raiz:

```sh
php -S 127.0.0.1:8000 -t source/public
```

Abre http://127.0.0.1:8000/ y conserva esa terminal abierta. Con Ctrl+C se detiene el servidor.

Las partidas se almacenan en la sesion del servidor. Nombre, tema y configuracion personalizada se guardan en localStorage del navegador. La duracion de la partida guardada depende de la configuracion de sesiones de PHP.

El codigo ya esta implementado. Consulta README.md para los controles y source/tests/game_test.php para las pruebas del motor.
