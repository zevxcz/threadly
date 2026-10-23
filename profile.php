<?php
require_once __DIR__.'/bootstrap.php';$user=current_user($pdo);
$username=(string)($_GET['u']??($user['username']??''));
$q=$pdo->prepare('SELECT id,username,display_name,bio,created_at FROM users WHERE username=?');$q->execute([$username]);$profile=$q->fetch();
if(!$profile){http_response_code(404);page_top('Not found',$user);echo '<div class="empty">User not found.</div>';page_bottom();exit;}
$q=$pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id=?');$q->execute([$profile['id']]);$followers=(int)$q->fetchColumn();
$q=$pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id=? AND following_id=?');$q->execute([me()??0,$profile['id']]);$following=(bool)$q->fetchColumn();
$q=$pdo->prepare(feed_query().' WHERE p.user_id=:owner AND p.parent_id IS NULL ORDER BY p.created_at DESC,p.id DESC LIMIT 50');$q->execute(['viewer'=>me()??0,'owner'=>$profile['id']]);$posts=$q->fetchAll();
page_top($profile['username'],$user); ?>
<div class="heading"><a href="index.php">←</a><strong>Profile</strong></div><section class="profile-top"><div class="profile-main"><div><h1><?= e($profile['display_name']) ?></h1><p>@<?= e($profile['username']) ?></p></div><div class="avatar large"><?= e(mb_strtoupper(mb_substr($profile['display_name'],0,1))) ?></div></div><p class="bio"><?= e($profile['bio']) ?></p><div class="profile-footer"><span class="muted"><?= $followers ?> followers</span><?php if($user && (int)$profile['id']!==(int)$user['id']): ?><form method="post" action="action.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="follow"><input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>"><button class="pill <?= $following?'secondary':'' ?>"><?= $following?'Following':'Follow' ?></button></form><?php elseif($user): ?><a class="pill secondary" href="settings.php">Edit profile</a><?php endif; ?></div></section><div class="section-label">Threads</div>
<?php foreach($posts as $post) render_post($post);if(!$posts) echo '<div class="empty">No threads posted yet.</div>';page_bottom(); ?>
