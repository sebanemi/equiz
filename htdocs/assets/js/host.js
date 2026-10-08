/* equiz host.js — projector controller. Polls api/state.php?host=1 every 1000ms. */
(function () {
  "use strict";
  var pin = "", timerInt = null, lastStatus = "", lastOrder = -1;
  var $ = function (id) { return document.getElementById(id); };
  function show(id) {
    ["view-create", "view-lobby", "view-ready", "view-q", "view-review", "view-board", "view-end"].forEach(function (v) {
      $(v).style.display = v === id ? "" : "none";
    });
    if (id === "view-lobby" || id === "view-create") $(id).classList.add("card");
  }
  function post(action, extra) {
    var fd = new FormData();
    fd.append("action", action);
    if (pin) fd.append("pin", pin);
    if (extra) for (var k in extra) fd.append(k, extra[k]);
    return fetch("api/host_action.php", { method: "POST", body: fd }).then(function (r) { return r.json(); });
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }

  $("btn-create").onclick = function () {
    post("create").then(function (d) {
      if (!d.ok) { alert(d.error || "Error"); return; }
      pin = d.pin;
      $("pin").textContent = pin;
      var url = location.origin + location.pathname.replace(/host\.php.*$/, "") + "join.php?pin=" + pin;
      $("join-url").textContent = url;
      drawQR(url);
      show("view-lobby");
      loop();
    });
  };
  $("btn-start").onclick = function () { post("start").then(handleAction); };
  $("btn-reveal").onclick = function () { post("reveal").then(handleAction); };
  $("btn-to-board").onclick = function () { post("to_leaderboard").then(handleAction); };
  $("btn-next").onclick = function () { post("next").then(handleAction); };
  $("btn-finish").onclick = function () {
    if (confirm("Finish the game?")) post("finish").then(handleAction);
  };
  $("btn-new").onclick = function () { location.reload(); };
  function handleAction(d) { if (!d.ok) alert(d.error || "Error"); else loop(true); }

  // QR: try CDN lib, otherwise show URL only (game never depends on it)
  function drawQR(url) {
    var el = $("qr"); el.innerHTML = "";
    var s = document.createElement("script");
    s.src = "https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js";
    s.onload = function () {
      try { new QRCode(el, { text: url, width: 140, height: 140 }); } catch (e) { el.textContent = url; }
    };
    s.onerror = function () { el.innerHTML = "<b style='font-size:13px'>" + esc(url) + "</b>"; };
    document.head.appendChild(s);
    setTimeout(function () { if (!el.innerHTML) el.innerHTML = "<b style='font-size:13px'>" + esc(url) + "</b>"; }, 4000);
  }

  var medals = ["🥇", "🥈", "🥉"];
  function hearts(n) { n = Math.max(0, Math.min(10, n | 0)); return n > 0 ? "❤️".repeat(n) : "💀"; }
  function boardHTML(list, crown) {
    return list.map(function (p, i) {
      var m = medals[i] || (i + 1) + ".";
      var c = (crown && i === 0) ? " 👑" : "";
      return "<tr><td>" + m + "</td><td>" + esc(p.name) + c + "</td><td style='white-space:nowrap'>" + hearts(p.lives) + "</td><td style='text-align:right'>✔ " + (p.correct_count | 0) + "</td></tr>";
    }).join("");
  }

  function loop(now) {
    if (!pin) return;
    fetch("api/state.php?pin=" + encodeURIComponent(pin) + "&host=1").then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) return;
      lastStatus = d.status;
      if (d.status === "lobby") {
        show("view-lobby");
        $("pin").textContent = d.pin;
        $("pcount").textContent = d.players_count;
        $("plist").innerHTML = (d.players || []).map(function (n) { return "<span class='chip'>👤 " + esc(n) + "</span>"; }).join("");
      } else if (d.status === "countdown") {
        stopTimer();
        show("view-ready");
        $("ready-q").textContent = "QUESTION " + d.order + "/" + d.total;
        $("ready-num").textContent = Math.max(1, Math.ceil(d.countdown_ms / 1000));
      } else if (d.status === "question") {
        show("view-q");
        if (d.order !== lastOrder) { lastOrder = d.order; }
        $("qnum").textContent = "QUESTION " + d.order + "/" + d.total;
        $("qtext").textContent = d.question.text;
        var sym = ["🔴", "🔵", "🟡", "🟢"];
        $("qopts").innerHTML = d.question.options.map(function (t, i) {
          return "<div class='opt o" + i + "'><span>" + sym[i] + " " + "ABCD"[i] + "</span><span>" + esc(t) + "</span></div>";
        }).join("");
        var total = d.stats ? d.stats.total : 0, pt = d.stats ? (d.stats.players_total || 0) : 0;
        $("ans-count").textContent = total + " / " + pt + " answered";
        startTimer(d.time_left_ms);
      } else if (d.status === "review") {
        stopTimer();
        show("view-review");
        var letters = ["A", "B", "C", "D"], sym2 = ["🔴", "🔵", "🟡", "🟢"];
        var c = d.question.correct;
        $("r-correct").textContent = sym2[c] + " " + letters[c] + ". " + d.question.options[c];
        var dead = (d.leaderboard || []).filter(function (p) { return p.lives <= 0; }).length;
        $("r-counts").textContent = d.stats.total + " answered · " + d.stats.correct + " correct · " + d.stats.incorrect + " wrong" + (dead ? " · 💀 " + dead + " eliminated" : "");
        var max = Math.max(1, Math.max.apply(null, d.stats.dist));
        var colors = ["#e21b3c", "#1368ce", "#d89e00", "#26890c"];
        $("r-bars").innerHTML = d.stats.dist.map(function (n, i) {
          var w = Math.round(100 * n / max);
          return "<div class='bar-row'><b style='width:34px'>" + letters[i] + "</b><div class='bar'><i style='width:" + w + "%;background:" + colors[i] + "'></i></div><b>" + n + "</b></div>";
        }).join("");
        if (d.leaderboard) { /* preview not needed */ }
      } else if (d.status === "leaderboard") {
        stopTimer();
        show("view-board");
        $("board").innerHTML = boardHTML(d.leaderboard || [], false);
        var isLast = d.order >= d.total;
        $("btn-next").style.display = isLast ? "none" : "";
        $("btn-next").textContent = isLast ? "—" : "NEXT QUESTION (" + (d.order + 1) + "/" + d.total + ")";
      } else if (d.status === "finished") {
        stopTimer();
        show("view-end");
        $("final-board").innerHTML = boardHTML(d.leaderboard || [], true);
      }
    });
    clearTimeout(loop._t);
    // light adaptive polling: countdown 500ms, question 1000ms, rest 1500ms
    var delay = lastStatus === "countdown" ? 500 : (lastStatus === "question" ? 1000 : 1500);
    loop._t = setTimeout(function () { loop(); }, delay);
  }

  function startTimer(msLeft) {
    stopTimer();
    var tick = function () {
      fetch("api/state.php?pin=" + encodeURIComponent(pin) + "&host=1").then(function (r) { return r.json(); }).then(function (d) {
        if (!d.ok || d.status !== "question") { loop(true); return; }
        var s = Math.ceil(d.time_left_ms / 1000);
        var t = $("timer");
        t.textContent = s;
        t.classList.toggle("low", s <= 5);
        if (d.stats) $("ans-count").textContent = d.stats.total + " answered";
      });
    };
    tick();
    timerInt = setInterval(tick, 750);
  }
  function stopTimer() { if (timerInt) clearInterval(timerInt); timerInt = null; }
})();
