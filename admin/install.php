<?php require 'config.php';
$done=false;
if($_SERVER['REQUEST_METHOD']=='POST'&&strlen($_POST['pw'])>=8){
$pdo->exec("CREATE TABLE IF NOT EXISTS admins(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) UNIQUE,pass VARCHAR(255))");
$pdo->exec("CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,sku VARCHAR(40),name VARCHAR(150),cost DECIMAL(12,2) DEFAULT 0,price DECIMAL(12,2) DEFAULT 0,qty INT DEFAULT 0,low_at INT DEFAULT 5,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS moves(id INT AUTO_INCREMENT PRIMARY KEY,product_id INT,type ENUM('in','sale','return','adjust'),qty INT,unit_price DECIMAL(12,2) DEFAULT 0,customer VARCHAR(120),note VARCHAR(255),created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(product_id),INDEX(type))");
$pdo->exec("CREATE TABLE IF NOT EXISTS partners(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120),phone VARCHAR(40))");
$pdo->exec("CREATE TABLE IF NOT EXISTS ledger(id INT AUTO_INCREMENT PRIMARY KEY,partner_id INT,type ENUM('owed','paid'),amount DECIMAL(12,2),note VARCHAR(255),created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(partner_id))");
$pdo->prepare("REPLACE INTO admins(id,username,pass) VALUES(1,?,?)")->execute([$_POST['u'],password_hash($_POST['pw'],PASSWORD_DEFAULT)]);
$done=true;}
?><meta name=viewport content="width=device-width,initial-scale=1"><body style="font-family:sans-serif;max-width:360px;margin:60px auto">
<?php if($done):?><h2>Installed ✔</h2><p>Now <b>delete install.php</b> from the server, then <a href="index.php">log in</a>.</p>
<?php else:?><h2>Spenzit setup</h2><form method=post>Username<br><input name=u value=admin required><br><br>Password (8+ chars)<br><input name=pw type=password required><br><br><button>Create database &amp; admin</button></form><?php endif?>
