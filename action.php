<?php
require_once __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
check_csrf();
$action=(string)($_POST['action'] ?? '');
if ($action === 'logout') { $_SESSION=[]; session_regenerate_id(true); go('auth.php'); }
$uid=require_login();
if ($action === 'post') {
    $body=trim((string)($_POST['body'] ?? ''));
    $parent=filter_var($_POST['parent_id'] ?? null,FILTER_VALIDATE_INT) ?: null;
    if ($body==='' || mb_strlen($body)>500) { flash('Posts must be 1–500 characters.'); go('index.php'); }
    if ($parent) { $q=$pdo->prepare('SELECT id FROM posts WHERE id=?'); $q->execute([$parent]); if (!$q->fetch()) { http_response_code(404); exit('Post not found'); } }
    $q=$pdo->prepare('INSERT INTO posts(user_id,parent_id,body) VALUES(?,?,?)'); $q->execute([$uid,$parent,$body]);
    go($parent ? 'post.php?id='.$parent : 'index.php');
}
if (in_array($action,['like','delete'],true)) {
    $id=filter_var($_POST['post_id']??null,FILTER_VALIDATE_INT); if (!$id) { http_response_code(400); exit('Invalid post'); }
    if ($action==='like') {
        $q=$pdo->prepare('SELECT 1 FROM likes WHERE user_id=? AND post_id=?'); $q->execute([$uid,$id]);
        if($q->fetch()) { $q=$pdo->prepare('DELETE FROM likes WHERE user_id=? AND post_id=?'); $q->execute([$uid,$id]); }
        else { $q=$pdo->prepare('INSERT IGNORE INTO likes(user_id,post_id) VALUES(?,?)'); $q->execute([$uid,$id]); }
    } else { $q=$pdo->prepare('DELETE FROM posts WHERE id=? AND user_id=?'); $q->execute([$id,$uid]); }
    $back=$_SERVER['HTTP_REFERER']??''; $path=parse_url($back,PHP_URL_PATH) ?: '';
    $page=basename($path);
    go(in_array($page,['index.php','profile.php','post.php','search.php'],true) ? $page.(($query=parse_url($back,PHP_URL_QUERY))?'?'.$query:'') : 'index.php');
}
if ($action==='follow') {
    $target=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);
    if (!$target || $target===$uid) go('index.php');
    $q=$pdo->prepare('SELECT 1 FROM follows WHERE follower_id=? AND following_id=?'); $q->execute([$uid,$target]);
    if($q->fetch()) { $q=$pdo->prepare('DELETE FROM follows WHERE follower_id=? AND following_id=?'); $q->execute([$uid,$target]); }
    else { $q=$pdo->prepare('INSERT IGNORE INTO follows(follower_id,following_id) VALUES(?,?)'); $q->execute([$uid,$target]); }
    $q=$pdo->prepare('SELECT username FROM users WHERE id=?');$q->execute([$target]);$targetUser=$q->fetchColumn();go($targetUser?'profile.php?u='.rawurlencode($targetUser):'index.php');
}
http_response_code(400); echo 'Unknown action';
