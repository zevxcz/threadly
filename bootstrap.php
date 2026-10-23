<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_start();
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    exit('Database connection failed. Check config.php and import schema.sql.');
}
function e(?string $text): string { return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function me(): ?int { return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null; }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Invalid session token.'); } }
function go(string $path='index.php'): never { header('Location: '.$path, true, 303); exit; }
function require_login(): int { if (!me()) go('auth.php'); return me(); }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function current_user(PDO $pdo): ?array {
    if (!me()) return null;
    $s=$pdo->prepare('SELECT id,username,display_name,bio FROM users WHERE id=?'); $s->execute([me()]); return $s->fetch() ?: null;
}
function time_ago(string $date): string {
    $seconds=max(0,time()-strtotime($date));
    if ($seconds < 60) return 'now';
    foreach ([['y',31536000],['mo',2592000],['d',86400],['h',3600],['m',60]] as [$unit,$size]) if ($seconds >= $size) return floor($seconds/$size).$unit;
    return 'now';
}
function feed_query(): string { return 'SELECT p.*,u.username,u.display_name,
 (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
 (SELECT COUNT(*) FROM posts r WHERE r.parent_id=p.id) AS reply_count,
 EXISTS(SELECT 1 FROM likes mine WHERE mine.post_id=p.id AND mine.user_id=:viewer) AS liked
 FROM posts p JOIN users u ON u.id=p.user_id'; }
function render_post(array $p): void {
    $id=(int)$p['id']; $liked=(bool)$p['liked'];
    ?>
    <article class="post" id="post-<?= $id ?>">
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['display_name'],0,1))) ?></div>
      <div class="post-content">
        <div class="post-head"><a class="user-link" href="profile.php?u=<?= rawurlencode($p['username']) ?>"><?= e($p['username']) ?></a><span class="muted">· <?= e(time_ago($p['created_at'])) ?></span></div>
        <p class="post-body"><?= nl2br(e($p['body'])) ?></p>
        <div class="post-actions">
          <form method="post" action="action.php" class="inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="like"><input type="hidden" name="post_id" value="<?= $id ?>"><button title="<?= $liked ? 'Unlike' : 'Like' ?>" class="icon-button <?= $liked?'liked':'' ?>" <?= me() ? '' : 'disabled' ?> type="submit"><span><?= $liked?'♥':'♡' ?></span> <small><?= (int)$p['like_count'] ?></small></button></form>
          <a class="icon-button" href="post.php?id=<?= $id ?>" title="Replies"><span>♧</span> <small><?= (int)$p['reply_count'] ?></small></a>
          <button type="button" class="icon-button share" data-url="post.php?id=<?= $id ?>" title="Copy link">↗</button>
          <?php if (me() === (int)$p['user_id']): ?><form class="inline" action="action.php" method="post" onsubmit="return confirm('Delete this post?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="post_id" value="<?= $id ?>"><button class="icon-button delete" title="Delete post">×</button></form><?php endif; ?>
        </div>
      </div>
    </article>
    <?php
}
function page_top(string $title,?array $user): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101010"><title><?= e($title) ?> · Threadly</title><link rel="stylesheet" href="assets/style.css"></head><body>
<aside class="rail"><a href="index.php" class="brand" aria-label="Threadly home">@</a><nav aria-label="Main navigation"><a href="index.php" title="Home">⌂</a><a href="search.php" title="Search">⌕</a><a href="<?= $user?'profile.php?u='.rawurlencode($user['username']):'auth.php' ?>" title="Profile">♙</a><a href="<?= $user?'index.php#compose':'auth.php' ?>" title="Write">✎</a></nav><div class="rail-bottom"><?php if($user): ?><form method="post" action="action.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="logout"><button title="Log out" type="submit">⇥</button></form><?php endif; ?></div></aside>
<main class="shell"><div class="mobile-top"><a href="index.php" class="mobile-brand">@</a><span>Threadly</span><a href="<?= $user?'profile.php?u='.rawurlencode($user['username']):'auth.php' ?>">◎</a></div>
<?php if(isset($_SESSION['flash'])): ?><div class="flash" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
<?php }
function page_bottom(): void { ?>
</main><nav class="mobile-nav" aria-label="Mobile navigation"><a href="index.php">⌂</a><a href="search.php">⌕</a><a href="index.php#compose">✎</a><a href="<?= me()?'profile.php':'auth.php' ?>">♙</a></nav><script src="assets/app.js"></script></body></html>
<?php }
