# equiz 🎮 — nuestro propio Kahoot, estilo battle royale

Juego web multijugador estilo Kahoot pero propio, simple y moderno, pensado para
usar en clase: el profesor proyecta la partida y los alumnos participan desde
sus teléfonos. Optimizado para funcionar en el hosting gratuito de
**InfinityFree** (solo PHP + MySQL + HTML/CSS/JS, sin Node, sin WebSockets,
sin build).

## Cómo se juega

```
1. El host crea la partida y proyecta el PIN + QR
2. Los alumnos escanean el QR, ponen el PIN y su nombre
3. Esperan en el lobby hasta que el host inicia
4. "GET READY..." y aparece la pregunta SOLO en el proyector
5. Cada teléfono muestra únicamente 4 botones de colores 🔴🔵🟡🟢
6. Tocan su respuesta y queda bloqueada
7. Resultados, tabla de supervivientes, siguiente pregunta...
8. 👑 ¡Gana el último en pie!
```

## Modo battle royale (estilo Blooket)

- Cada jugador arranca con **5 vidas** (ajustable con `START_LIVES`).
- Respuesta correcta: conservás la vida.
- Respuesta incorrecta **o no responder a tiempo**: −1 vida.
- 0 vidas = **eliminado** (sigue mirando por el proyector).
- Ranking: vidas → respuestas correctas → velocidad.
- La velocidad solo suma puntos de **desempate**; todo lo calcula el servidor.

## Instalación en InfinityFree (5 minutos)

1. Creá la base MySQL desde el cPanel.
2. En phpMyAdmin → tu base → **Importar** → `htdocs/database.sql`.
3. Completá tus credenciales en `htdocs/config/database.php`
   (usá `htdocs/config/config.example.php` como plantilla).
4. Subí el **contenido** de `htdocs/` a `/htdocs` por FTP o File Manager.
5. Abrí `admin.php` y cargá tus preguntas.
6. Abrí `host.php` → **CREATE GAME** → proyectá el PIN.

👉 Guía paso a paso completa en [`htdocs/README.txt`](htdocs/README.txt).

> **Ya tenías una versión anterior instalada?** Si tu base existe de antes,
> ejecutá una vez en phpMyAdmin → SQL:
> ```sql
> ALTER TABLE `players`
>   ADD COLUMN `lives` INT NOT NULL DEFAULT 5,
>   ADD COLUMN `correct_count` INT NOT NULL DEFAULT 0;
> ```

## Estructura

```
htdocs/
├── index.php          portada (host vs unirse)
├── host.php           pantalla del proyector (PIN, QR, preguntas, resultados)
├── join.php           pantalla del jugador (móvil, 4 botones)
├── play.php           alias de join.php
├── admin.php          gestión de preguntas (crear/editar/eliminar/activar)
├── database.sql       tablas (sin preguntas precargadas: las creás vos)
├── README.txt         guía detallada de instalación y uso
├── config/            credenciales DB + ejemplo
├── includes/          conexión PDO + lógica del juego (el servidor manda)
├── api/               state (polling) · join · answer · host_action
└── assets/            css + js vanilla (sin dependencias)
```

## Sincronización

Sin WebSockets: polling liviano a un único endpoint (`api/state.php`),
cada ~1–1,5 s (500 ms durante la intro). El servidor es la autoridad en
tiempos, respuestas y vidas; el navegador nunca envía puntajes.

Hecho para una presentación universitaria 🎓
