<?php // equiz player screen: join + 4 color buttons only (no question text) ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>equiz player</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar"><div class="logo">e<span>quiz</span></div></div>
<div class="wrap" style="max-width:560px">

  <div id="v-join" class="card">
    <h1 class="center">Join game 📱</h1>
    <label>GAME PIN</label>
    <input id="pin" type="text" inputmode="numeric" maxlength="10" placeholder="482931" style="text-align:center;font-size:28px;letter-spacing:4px">
    <div style="height:10px"></div>
    <label>YOUR NAME</label>
    <input id="name" type="text" maxlength="20" placeholder="Sebastian">
    <div style="height:14px"></div>
    <button id="btn-join" class="btn primary big" style="width:100%">JOIN GAME</button>
    <p id="jerr" class="center" style="color:#c00;font-weight:800"></p>
  </div>

  <div id="v-lobby" class="card center" style="display:none">
    <h2>Waiting for host… ⏳</h2>
    <p>You are: <b id="me"></b></p>
    <p><span class="status-pill">Players connected: <span id="pcount">1</span></span></p>
    <p class="muted small">Look at the projector 👀</p>
  </div>

  <div id="v-ready" class="card center" style="display:none">
    <h2 id="ready-q">QUESTION 1</h2>
    <div style="font-size:26px;font-weight:900">GET READY! 👀</div>
    <div class="ready-num" id="ready-num">5</div>
    <p class="muted">Look at the projector…</p>
  </div>

  <div id="v-play" style="display:none" class="center">
    <h2 id="qnum">QUESTION 1</h2>
    <p class="status-pill">Look at the projector — tap your answer 👇</p>
    <div class="pad" id="pad">
      <button class="p0" data-s="0">🔴</button>
      <button class="p1" data-s="1">🔵</button>
      <button class="p2" data-s="2">🟡</button>
      <button class="p3" data-s="3">🟢</button>
    </div>
    <p id="perr" style="font-weight:800"></p>
  </div>

  <div id="v-lock" class="card center" style="display:none">
    <h2>Answer submitted ✅</h2><p class="muted">Waiting for results…</p>
  </div>

  <div id="v-dead" class="card center" style="display:none">
    <h1>💀 Eliminated!</h1><p>No lives left. Watch the projector and cheer! 🎉</p>
  </div>

  <div id="v-result" class="card center" style="display:none">
    <h1 id="rw"></h1><p id="rp" style="font-size:22px;font-weight:900"></p>
    <p class="muted small"><b id="rs"></b> · Rank: <b id="rr"></b></p>
  </div>

  <div id="v-board" class="card center" style="display:none">
    <h2>🏆 Leaderboard</h2><table class="board" id="board"></table>
  </div>

  <div id="v-end" class="card center" style="display:none">
    <h1>🏆 Game over!</h1><table class="board" id="final"></table>
    <p class="muted">Thanks for playing — AI CHALLENGE</p>
    <button class="btn primary" onclick="localStorage.clear();location.reload()">PLAY AGAIN</button>
  </div>

</div>
<script src="assets/js/player.js"></script>
</body>
</html>
