<?php
require_once __DIR__.'/bootstrap.php';
if (me()) go('index.php');
$error=''; $register=($_GET['mode']??'')==='register';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf(); $register=($_POST['mode']??'')==='register';
 $username=strtolower(trim((string)($_POST['username']??''))); $password=(string)($_POST['password']??'');
 if ($register) {
   $name=trim((string)($_POST['display_name']??''));
   if (!preg_match('/^[a-z0-9_]{3,30}$/',$username)) $error='Username must be 3–30 letters, numbers, or underscores.';
   elseif(mb_strlen($name)<1 || mb_strlen($name)>80) $error='Display name must be 1–80 characters.';
   elseif(strlen($password)<8) $error='Password must have at least 8 characters.';
   else {
     try { $q=$pdo->prepare('INSERT INTO users(username,display_name,password_hash) VALUES(?,?,?)');$q->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT)]);$_SESSION['uid']=(int)$pdo->lastInsertId();session_regenerate_id(true);go('index.php'); }
     catch(PDOException $e) { if ($e->getCode()==='23000') $error='That username is already taken.'; else throw $e; }
   }
 } else {
   $q=$pdo->prepare('SELECT id,password_hash FROM users WHERE username=?');$q->execute([$username]);$row=$q->fetch();
   if (!$row || !password_verify($password,$row['password_hash'])) $error='Incorrect username or password.';
   else { session_regenerate_id(true);$_SESSION['uid']=(int)$row['id'];go('index.php'); }
 }
}
page_top($register?'Sign up':'Log in',null); ?>
<section class="auth-box"><div class="auth-logo">@</div><h1><?= $register?'Join Threadly':'Welcome back' ?></h1><p class="muted">A little space for big conversations.</p>
<?php if($error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="mode" value="<?= $register?'register':'login' ?>">
<?php if($register): ?><label>Display name<input name="display_name" maxlength="80" required value="<?= e($_POST['display_name']??'') ?>" placeholder="Your name"></label><?php endif; ?>
<label>Username<input name="username" maxlength="30" required autocomplete="username" value="<?= e($_POST['username']??'') ?>" placeholder="username"></label>
<label>Password<input name="password" type="password" required autocomplete="<?= $register?'new-password':'current-password' ?>" minlength="<?= $register?'8':'1' ?>" placeholder="Password"></label>
<button class="primary" type="submit"><?= $register?'Create account':'Log in' ?></button></form>
<p class="auth-switch"><?= $register?'Already have an account?':'New to Threadly?' ?> <a href="auth.php<?= $register?'':'?mode=register' ?>"><?= $register?'Log in':'Sign up' ?></a></p></section>
<?php page_bottom(); ?>
