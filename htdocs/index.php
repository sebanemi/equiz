<?php // equiz landing ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>equiz — our own Kahoot</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar"><div class="logo">e<span>quiz</span></div><div style="font-weight:800">AI CHALLENGE · classroom trivia</div></div>
<div class="wrap">
  <div class="card center">
    <h1>Our own Kahoot 🎮</h1>
    <p class="muted">Battle royale: everyone starts with 5 lives — a wrong answer costs one. Last one standing wins! 👑</p>
    <div class="grid2" style="margin-top:18px">
      <div style="background:#f4f0ff;border-radius:14px;padding:22px">
        <h2>📽️ Host</h2>
        <p class="muted">Create a game, show the PIN + QR on the projector.</p>
        <a class="btn primary big" href="host.php">HOST A GAME</a>
      </div>
      <div style="background:#fff8e6;border-radius:14px;padding:22px">
        <h2>📱 Player</h2>
        <p class="muted">Scan the QR or enter the PIN to join.</p>
        <a class="btn dark big" href="join.php">JOIN A GAME</a>
      </div>
    </div>
    <p class="small muted" style="margin-top:16px">1. Scan QR &nbsp;·&nbsp; 2. Enter name &nbsp;·&nbsp; 3. Wait &nbsp;·&nbsp; 4. Look at projector &nbsp;·&nbsp; 5. Tap answer &nbsp;·&nbsp; 6. Repeat</p>
    <p class="small"><a href="admin.php">Manage questions (teacher)</a></p>
  </div>
</div>
</body>
</html>
