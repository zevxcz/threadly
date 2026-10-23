<?php
require_once __DIR__.'/bootstrap.php'; $user=current_user($pdo);
$tab=($_GET['tab']??'')==='following'?'following':'for-you';
$sql=feed_query().' WHERE p.parent_id IS NULL';
if($tab==='following' && $user) $sql.=' AND (p.user_id=:uid OR EXISTS(SELECT 1 FROM follows f WHERE f.follower_id=:uid2 AND f.following_id=p.user_id))';
$sql.=' ORDER BY p.created_at DESC,p.id DESC LIMIT 50';
$q=$pdo->prepare($sql);$params=['viewer'=>me()??0];if($tab==='following' && $user) {$params['uid']=me();$params['uid2']=me();}$q->execute($params);$posts=$q->fetchAll();
page_top('Home',$user); ?>
<header class="feed-header"><a class="<?= $tab==='for-you'?'active':'' ?>" href="index.php?tab=for-you">For you</a><a class="<?= $tab==='following'?'active':'' ?>" href="index.php?tab=following">Following</a></header>
<?php if($user): ?><form id="compose" class="compose" method="post" action="action.php"><div class="avatar"><?= e(mb_strtoupper(mb_substr($user['display_name'],0,1))) ?></div><div class="compose-content"><strong><?= e($user['username']) ?></strong><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="post"><textarea name="body" maxlength="500" rows="2" required placeholder="What's new?"></textarea><div class="compose-footer"><small class="counter">0 / 500</small><button class="pill" type="submit">Post</button></div></div></form>
<?php else: ?><div class="welcome"><h2>Join the conversation</h2><p>Share thoughts, reply to people, and find your community.</p><a class="pill" href="auth.php?mode=register">Create account</a></div><?php endif; ?>
<section aria-label="Posts"><?php foreach($posts as $post) render_post($post); if(!$posts): ?><div class="empty">No posts yet. Be the first to start a thread.</div><?php endif; ?></section>
<?php page_bottom(); ?>
