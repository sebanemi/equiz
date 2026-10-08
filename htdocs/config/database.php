<?php
// equiz — centralized DB + app configuration.
// 1) Copy config.example.php to database.php on first install.
// 2) Fill in the InfinityFree MySQL credentials (see cPanel > MySQL Databases).
// 3) Upload htdocs. Nothing else to install.

define('DB_HOST', 'sqlXXX.infinityfree.com'); // <-- CHANGE ME (MySQL Host from InfinityFree)
define('DB_NAME', 'if0_XXXXXXXX_equiz');      // <-- CHANGE ME (MySQL Database name)
define('DB_USER', 'if0_XXXXXXXX');            // <-- CHANGE ME (MySQL Username)
define('DB_PASS', 'YOUR_PASSWORD_HERE');      // <-- CHANGE ME (MySQL Password)
define('DB_CHARSET', 'utf8mb4');

// Simple password protecting admin.php (question editor). Change it!
define('ADMIN_PASSWORD', 'admin123');

// How long (seconds) a game PIN stays joinable in lobby before cleanup (not enforced strictly).
define('GAME_PIN_LENGTH', 6);

// Seconds of "GET READY" intro shown before each question (3-10).
define('COUNTDOWN_SECONDS', 5);

// Battle royale: lives every player starts with (1-10).
define('START_LIVES', 5);
