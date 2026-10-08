/* equiz player.js — phones show ONLY 4 color buttons during questions. Never question text. */
(function () {
  "use strict";
  var $ = function (id) { return document.getElementById(id); };
  var pin = "", token = "", name = "", answeredSel = -1, lastKey = "";
  // restore session + ?pin= from QR
  try {
    var q = new URLSearchParams(location.search);
    if (q.get("pin")) $("pin").value = q.get("pin");
    var saved = JSON.parse(localStorage.getItem("equiz") || "{}");
    if (saved.pin && saved.token) { pin = saved.pin; token = saved.token; name = saved.name || ""; boot(); }
  } catch (e) {}

  function view(id) {
    ["v-join", "v-lobby", "v-ready", "v-dead", "v-play", "v-lock", "v-result", "v-board", "v-end"].forEach(function (v) {
      $(v).style.display = v === id ? "" : "none";
    });
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function hearts(n) { n = Math.max(0, Math.min(10, n | 0)); return n > 0 ? "❤️".repeat(n) : "💀"; }
  function boardHTML(list) {
    return (list || []).map(function (p, i) {
      var m = ["🥇", "🥈", "🥉"][i] || ((i + 1) + ".");
      var c = i === 0 ? " 👑" : "";
      return "<tr><td>" + m + "</td><td>" + esc(p.name) + c + "</td><td style='white-space:nowrap'>" + hearts(p.lives) + "</td></tr>";
    }).join("");
  }

  $("btn-join").onclick = function () {
    pin = $("pin").value.trim(); name = $("name").value.trim();
    $("jerr").textContent = "";
    var fd = new FormData();
    fd.append("pin", pin); fd.append("name", name);
    fetch("api/join.php", { method: "POST", body: fd }).then(function (r) { return r.json().then(function (d) { return { s: r.status, d: d }; }); }).then(function (x) {
      if (!x.d.ok) { $("jerr").textContent = x.d.error || "Could not join"; return; }
      token = x.d.token; pin = x.d.pin;
      try { localStorage.setItem("equiz", JSON.stringify({ pin: pin, token: token, name: name })); } catch (e) {}
      boot();
    });
  };

  document.querySelectorAll("#pad button").forEach(function (b) {
    b.onclick = function () {
      if (answeredSel !== -1) return;
      var s = parseInt(b.getAttribute("data-s"), 10);
      var fd = new FormData();
      fd.append("pin", pin); fd.append("token", token); fd.append("selected", s);
      fetch("api/answer.php", { method: "POST", body: fd }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.ok) { answeredSel = s; view("v-lock"); }
        else { $("perr").textContent = d.error || "Error"; }
      });
    };
  });

  function boot() {
    $("me").textContent = name;
    view("v-lobby");
    setInterval(poll, 1000);
    poll();
  }

  function poll() {
    if (!pin) return;
    fetch("api/state.php?pin=" + encodeURIComponent(pin) + "&token=" + encodeURIComponent(token)).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) return;
      var key = d.status + "|" + d.order + "|" + (d.answered ? "a" : "q") + "|" + (d.players_count || "") + "|" + (d.countdown_ms ? Math.ceil(d.countdown_ms / 1000) : "");
      if (key === lastKey && d.status !== "question") return;
      if (key === lastKey) return;
      lastKey = key;
      if (d.status === "lobby") { answeredSel = -1; view("v-lobby"); $("pcount").textContent = d.players_count || "?"; }
      else if (d.eliminated && (d.status === "countdown" || d.status === "question")) { view("v-dead"); return; }
      else if (d.status === "countdown") {
        answeredSel = -1; view("v-ready");
        $("ready-q").textContent = "QUESTION " + d.order;
        $("ready-num").textContent = Math.max(1, Math.ceil(d.countdown_ms / 1000));
      }
      else if (d.status === "question") {
        if (d.answered) { view("v-lock"); }
        else {
          answeredSel = -1; view("v-play");
          $("qnum").textContent = "QUESTION " + d.order;
          $("pad").classList.remove("locked");
          $("perr").textContent = "";
        }
      } else if (d.status === "review") {
        if (d.my) {
          view("v-result");
          $("rw").textContent = d.my.correct ? "✅ Correct!" : "❌ -1 life!";
          $("rp").textContent = d.my.correct ? "Life saved! ❤️" : "Ouch 💔";
          $("rs").textContent = d.lives + " lives left";
          $("rr").textContent = "#" + (d.my_rank || "?");
        } else if (d.eliminated) view("v-dead");
        else view("v-lock");
      } else if (d.status === "leaderboard") {
        view("v-board");
        $("board").innerHTML = boardHTML(d.leaderboard);
      } else if (d.status === "finished") {
        view("v-end");
        $("final").innerHTML = boardHTML(d.leaderboard);
      }
    });
  }
})();
