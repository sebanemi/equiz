equiz — OUR OWN KAHOOT (PHP + MySQL, no Node, no WebSockets)
================================================================

WHAT IS THIS
-----------
A classroom trivia game. The teacher (host) projects host.php on the
screen. Students join from their phones at join.php with a 6-digit PIN
(or by scanning the QR code) and answer with 4 color buttons.
Phones NEVER show the question text — only the projector does.

FILES (upload the CONTENTS of this folder to /htdocs)
------------------------------------------------------
  index.php            landing: host vs join
  host.php             projector screen (lobby, PIN, QR, questions, results)
  join.php             player screen (join form + 4 color buttons)
  play.php             alias that redirects to join.php
  admin.php            question manager (password in config/database.php)
  database.sql         import once via phpMyAdmin (tables only, no questions)
  /config/database.php YOUR db credentials (edit this!)
  /config/config.example.php  template
  /includes/db.php, functions.php
  /api/state.php       polling endpoint (single endpoint = light traffic)
  /api/join.php        players join (lobby only, unique names)
  /api/answer.php      submit answer (server validates time + score)
  /api/host_action.php host controls (session-bound to creator browser)
  /assets/css/style.css, /assets/js/host.js, player.js

INSTALL ON INFINITYFREE (5 minutes)
-----------------------------------
 1. Create account + hosting site at infinityfree.com (free plan is fine).
 2. cPanel > MySQL Databases > create database + user. Write down:
    MySQL Host (e.g. sql123.infinityfree.com), Database, Username, Password.
 3. cPanel > phpMyAdmin > select your database > Import > choose
    database.sql > Go. You should see the games/players/questions/... tables
    (empty — you will add your own questions in step 7).
 4. On your PC open config/database.php and fill DB_HOST/DB_NAME/DB_USER/
    DB_PASS. Also change ADMIN_PASSWORD.
 5. cPanel > File Manager (or FTP) > open /htdocs > upload ALL files
    from this folder (keep the same structure).
 6. Open your domain:  https://YOUR-DOMAIN/
 7. Open admin.php and create your questions (text, 4 options, correct
    answer, time, order). Only questions marked Active enter the game.
 8. Open host.php > CREATE GAME > a 6-digit PIN + QR appear. Project it.
 9. Students open join.php (or scan QR), enter PIN + name, wait in lobby.
10. Host presses START GAME. Questions show on projector; phones show
    only 4 color buttons. Host reveals results, shows leaderboard,
    advances with NEXT QUESTION, finishes with Finish game.

HOW A ROUND WORKS
-----------------
 lobby -> START -> GET READY countdown (5s, configurable via
 COUNTDOWN_SECONDS in config/database.php) -> question (20s timer,
 server time) -> review
 (correct answer + bars A/B/C/D + eliminated count) -> survivors board
 -> next ... -> finished, winner crowned LAST ONE STANDING.

BATTLE ROYALE RULES (Blooket-style)
-----------------------------------
 - Everyone starts with 5 lives (START_LIVES in config/database.php).
 - Correct answer: life saved (+ speed points as tiebreak only).
 - Wrong answer: -1 life. No answer when time runs out: -1 life.
 - 0 lives = eliminated. Eliminated players spectate on the projector.
 - Ranking: lives first, then correct answers, then speed points.
 - Winner: last one standing (or top of the ranking at the end).

UPDATING FROM THE POINTS VERSION
--------------------------------
 If your database already exists (you imported database.sql before),
 run this ONCE in phpMyAdmin > your database > SQL tab:

   ALTER TABLE `players`
     ADD COLUMN `lives` INT NOT NULL DEFAULT 5,
     ADD COLUMN `correct_count` INT NOT NULL DEFAULT 0;

 Fresh installs don't need this: database.sql already includes them.

POLLING / PERFORMANCE
---------------------
 Single endpoint api/state.php polled every ~1000-1500 ms (750 ms for
 the host timer). One small JSON per poll, indexed queries, player
 names capped at 60, leaderboard capped at 10. Comfortable for 20-40
 players on free shared hosting.

QR CODE
-------
 The lobby builds join.php?pin=XXXXXX and tries to render a QR with the
 cdnjs qrcodejs library. If the classroom has no internet for the CDN,
 the plain URL is shown instead — the game itself never depends on it.

TROUBLESHOOTING
---------------
 - "Game not found": wrong PIN (PINs are 6 digits).
 - "Name taken": two students with same name — add a last initial.
 - "Only the host browser...": control the game from the same browser
   that pressed CREATE GAME (it stores a session cookie).
 - Blank page: check config/database.php credentials; enable PHP errors
   temporarily or check InfinityFree error logs.
 - Timer feels slow: free hosting + polling has ~1s granularity; this is
   normal and answers are validated against SERVER time, so it is fair.
