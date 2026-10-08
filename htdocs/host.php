<?php // equiz host screen — projector view ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>equiz host</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar"><div class="logo">e<span>quiz</span></div><div style="font-weight:800">HOST · projector view</div>
<div style="margin-left:auto"><a href="index.php" class="btn ghost">Home</a></div></div>
<div class="wrap">

  <!-- CREATE -->
  <div id="view-create" class="card center">
    <h1>AI Quiz — Host</h1>
    <p class="muted">Project this screen. Students join from their phones.</p>
    <button id="btn-create" class="btn primary big">CREATE GAME</button>
    <p class="small muted">Questions + PIN are managed on the server. No login needed.</p>
  </div>

  <!-- LOBBY -->
  <div id="view-lobby" class="center" style="display:none">
    <div style="font-size:28px;font-weight:900;letter-spacing:2px">GAME PIN</div>
    <div><span id="pin" class="pin-big">------</span></div>
    <div style="margin-top:14px">
      <span class="qrbox"><span id="qr"></span><br><small id="join-url"></small></span>
    </div>
    <h2 style="margin-top:14px">Players: <span id="pcount">0</span></h2>
    <div id="plist" class="chips"></div>
    <div class="toolbar"><button id="btn-start" class="btn primary big">START GAME</button></div>
  </div>

  <!-- GET READY INTRO -->
  <div id="view-ready" class="center" style="display:none">
    <h2 id="ready-q">QUESTION 1</h2>
    <div style="font-size:30px;font-weight:900;letter-spacing:3px">GET READY!</div>
    <div class="ready-num" id="ready-num">5</div>
    <p style="font-weight:800;color:#cfc8ff">Look at the screen… the question is coming 👀</p>
  </div>

  <!-- QUESTION -->
  <div id="view-q" style="display:none">
    <div class="qmeta"><span id="qnum">QUESTION 1/10</span><span class="timer" id="timer">20</span><span id="ans-count"></span></div>
    <div class="qtext" id="qtext"></div>
    <div class="opts" id="qopts"></div>
    <div class="toolbar"><button id="btn-reveal" class="btn ghost">Close answers &amp; reveal</button></div>
  </div>

  <!-- REVIEW -->
  <div id="view-review" style="display:none">
    <h1 class="center" id="r-title">CORRECT ANSWER</h1>
    <div class="card"><div id="r-correct" style="font-size:26px;font-weight:900"></div>
      <div id="r-counts" style="margin:8px 0;font-weight:800"></div><div id="r-bars"></div></div>
    <div class="toolbar"><button id="btn-to-board" class="btn primary big">LEADERBOARD</button></div>
  </div>

  <!-- LEADERBOARD -->
  <div id="view-board" style="display:none">
    <h1 class="center">❤️ SURVIVORS</h1>
    <div class="card"><table class="board" id="board"></table></div>
    <div class="toolbar"><button id="btn-next" class="btn primary big">NEXT QUESTION</button>
    <button id="btn-finish" class="btn ghost">Finish game</button></div>
  </div>

  <!-- FINISHED -->
  <div id="view-end" style="display:none" class="center">
    <h1>👑 LAST ONE STANDING</h1>
    <div class="card"><table class="board" id="final-board"></table>
      <h2>THANK YOU FOR PLAYING!<br><small class="muted">AI CHALLENGE</small></h2>
      <button id="btn-new" class="btn primary big">NEW GAME</button></div>
  </div>

</div>
<script>window.EQUIZ_PIN = "";</script>
<script src="assets/js/host.js"></script>
</body>
</html>
