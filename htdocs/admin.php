<?php
// equiz admin — simple question manager (single password in config/database.php).
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$authed = isset($_SESSION['admin_ok']) && $_SESSION['admin_ok'] === true;
if (isset($_POST['pw'])) {
    if (hash_equals((string)ADMIN_PASSWORD, (string)$_POST['pw'])) { $_SESSION['admin_ok'] = true; $authed = true; }
    else $login_err = 'Wrong password.';
}
if (isset($_GET['logout'])) { unset($_SESSION['admin_ok']); $authed = false; }

$msg = '';
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qaction'])) {
    try {
        $pdo = db();
        $a = $_POST['qaction'];
        if ($a === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $qt = trim(mb_substr($_POST['question_text'] ?? '', 0, 500));
            $oa = trim(mb_substr($_POST['option_a'] ?? '', 0, 255));
            $ob = trim(mb_substr($_POST['option_b'] ?? '', 0, 255));
            $oc = trim(mb_substr($_POST['option_c'] ?? '', 0, 255));
            $od = trim(mb_substr($_POST['option_d'] ?? '', 0, 255));
            $co = max(0, min(3, (int)($_POST['correct_option'] ?? 0)));
            $tl = max(5, min(120, (int)($_POST['time_limit'] ?? 20)));
            $so = (int)($_POST['sort_order'] ?? 0);
            $ac = isset($_POST['is_active']) ? 1 : 0;
            if ($qt === '' || $oa === '' || $ob === '' || $oc === '' || $od === '') throw new RuntimeException('All fields are required.');
            if ($id > 0) {
                $pdo->prepare("UPDATE questions SET question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_option=?,time_limit=?,sort_order=?,is_active=? WHERE id=?")
                    ->execute([$qt,$oa,$ob,$oc,$od,$co,$tl,$so,$ac,$id]);
                $msg = 'Question updated.';
            } else {
                $pdo->prepare("INSERT INTO questions (question_text,option_a,option_b,option_c,option_d,correct_option,time_limit,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$qt,$oa,$ob,$oc,$od,$co,$tl,$so,$ac]);
                $msg = 'Question created.';
            }
        } elseif ($a === 'delete') {
            $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([(int)$_POST['id']]);
            $msg = 'Question deleted.';
        } elseif ($a === 'toggle') {
            $pdo->prepare("UPDATE questions SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_POST['id']]);
            $msg = 'Toggled.';
        }
    } catch (Throwable $e) { $msg = 'Error: ' . $e->getMessage(); }
}

$questions = [];
$edit = null;
if ($authed) {
    $pdo = db();
    $questions = $pdo->query("SELECT * FROM questions ORDER BY sort_order ASC, id ASC")->fetchAll();
    if (isset($_GET['edit'])) {
        $st = $pdo->prepare("SELECT * FROM questions WHERE id = ? LIMIT 1");
        $st->execute([(int)$_GET['edit']]);
        $edit = $st->fetch() ?: null;
    }
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>equiz admin</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="topbar"><div class="logo">e<span>quiz</span></div><div style="font-weight:800">TEACHER · questions</div>
<div style="margin-left:auto"><a href="index.php" class="btn ghost">Home</a></div></div>
<div class="wrap">
<?php if (!$authed): ?>
  <div class="card center" style="max-width:420px;margin:0 auto">
    <h2>Teacher login</h2>
    <?php if (!empty($login_err)) echo '<p style="color:#c00;font-weight:800">'.h($login_err).'</p>'; ?>
    <form method="post"><label>Password</label><input type="password" name="pw" autofocus>
    <div style="height:10px"></div><button class="btn primary" style="width:100%">ENTER</button></form>
    <p class="small muted">Default: admin123 — change ADMIN_PASSWORD in config/database.php</p>
  </div>
<?php else: ?>
  <?php if ($msg) echo '<div class="card" style="padding:12px;margin-bottom:12px">'.h($msg).'</div>'; ?>
  <div class="card">
    <h2><?= $edit ? 'Edit question #' . (int)$edit['id'] : 'New question' ?></h2>
    <form method="post">
      <input type="hidden" name="qaction" value="save">
      <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
      <label>Question</label><textarea name="question_text" rows="2" required><?= h($edit['question_text'] ?? '') ?></textarea>
      <div class="grid2">
        <div><label>Option A 🔴</label><input type="text" name="option_a" required value="<?= h($edit['option_a'] ?? '') ?>"></div>
        <div><label>Option B 🔵</label><input type="text" name="option_b" required value="<?= h($edit['option_b'] ?? '') ?>"></div>
        <div><label>Option C 🟡</label><input type="text" name="option_c" required value="<?= h($edit['option_c'] ?? '') ?>"></div>
        <div><label>Option D 🟢</label><input type="text" name="option_d" required value="<?= h($edit['option_d'] ?? '') ?>"></div>
      </div>
      <div class="grid2" style="margin-top:10px">
        <div><label>Correct answer</label><select name="correct_option">
          <?php foreach (['A','B','C','D'] as $i=>$L): ?>
          <option value="<?= $i ?>" <?= ($edit && (int)$edit['correct_option']===$i)?'selected':'' ?>><?= $L ?></option>
          <?php endforeach; ?></select></div>
        <div><label>Time (s)</label><input type="number" name="time_limit" min="5" max="120" value="<?= h($edit['time_limit'] ?? 20) ?>"></div>
        <div><label>Order</label><input type="number" name="sort_order" value="<?= h($edit['sort_order'] ?? 0) ?>"></div>
        <div><label>&nbsp;</label><div><input type="checkbox" name="is_active" value="1" <?= (!$edit || $edit['is_active'])?'checked':'' ?>> Active</div></div>
      </div>
      <div style="margin-top:12px"><button class="btn primary">SAVE</button>
      <?php if ($edit): ?><a class="btn ghost" href="admin.php">Cancel</a><?php endif; ?>
      <a class="btn ghost" href="admin.php?logout=1" style="float:right">Logout</a></div>
    </form>
  </div>
  <div class="card" style="margin-top:14px">
    <h2>Questions (<?= count($questions) ?>)</h2>
    <table class="admin-table">
      <tr><th>#</th><th>Question</th><th>Correct</th><th>Time</th><th>Active</th><th></th></tr>
      <?php foreach ($questions as $q): ?>
      <tr>
        <td><?= (int)$q['sort_order'] ?></td>
        <td><?= h(mb_strimwidth($q['question_text'],0,60,'…')) ?></td>
        <td><?= ['A','B','C','D'][(int)$q['correct_option']] ?></td>
        <td><?= (int)$q['time_limit'] ?>s</td>
        <td><?= $q['is_active'] ? '✅' : '❌' ?></td>
        <td style="white-space:nowrap">
          <a href="admin.php?edit=<?= (int)$q['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="qaction" value="delete"><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><button>Del</button></form>
          <form method="post" style="display:inline"><input type="hidden" name="qaction" value="toggle"><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><button><?= $q['is_active']?'Off':'On' ?></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>
</div>
</body>
</html>
