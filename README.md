# equiz — our own Kahoot (battle royale 🎮)

Classroom trivia game in **PHP + MySQL + vanilla HTML/CSS/JS**. No Node, no WebSockets, no build step. Ready to upload to InfinityFree (`/htdocs`).

- 📽️ Host projects `host.php` (PIN + QR, questions, results, survivors board)
- 📱 Players join at `join.php` with a 6-digit PIN (4 color buttons only)
- ❤️ Battle royale: 5 lives, wrong/no answer costs one — last one standing wins 👑
- ❓ Questions managed from `admin.php` (create / edit / delete / activate)

**Install:** import `htdocs/database.sql` in phpMyAdmin, fill credentials in
`htdocs/config/database.php` (see `config.example.php`), upload the contents
of `htdocs/` to `/htdocs`. Full steps in [`htdocs/README.txt`](htdocs/README.txt).
