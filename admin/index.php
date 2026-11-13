<?php require 'config.php';
$h=fn($s)=>htmlspecialchars((string)$s);
$m=fn($n)=>'GH₵ '.number_format($n,2);
if(isset($_GET['out'])){session_destroy();header('Location: index.php');exit;}
if(!isset($_SESSION['a'])){
 $err='';
 if($_SERVER['REQUEST_METHOD']=='POST'){$s=$pdo->prepare('SELECT * FROM admins WHERE username=?');$s->execute([$_POST['u']]);$r=$s->fetch();
  if($r&&password_verify($_POST['pw'],$r['pass'])){session_regenerate_id(true);$_SESSION['a']=1;$_SESSION['t']=bin2hex(random_bytes(16));header('Location: index.php');exit;}$err='Wrong username or password.';}
 ?><!doctype html><meta name=viewport content="width=device-width,initial-scale=1"><link rel=stylesheet href=admin.css><body class=login><form method=post class=card><h1>Spenzit Admin</h1><p class=muted>Sign in to manage stock</p><?=$err?"<p class=err>$err</p>":''?><input name=u placeholder=Username required autofocus><input name=pw type=password placeholder=Password required><button>Sign in</button></form><?php exit;}
$p=$_GET['p']??'dash';
if($_SERVER['REQUEST_METHOD']=='POST'){
 if(!hash_equals($_SESSION['t'],$_POST['t']??''))die('Bad token');
 $a=$_POST['do']??'';$q=fn($sql,$v)=>$pdo->prepare($sql)->execute($v);
 if($a=='addp'){$q('INSERT INTO products(sku,name,cost,price,qty,low_at) VALUES(?,?,?,?,?,?)',[$_POST['sku'],$_POST['name'],$_POST['cost'],$_POST['price'],(int)$_POST['qty'],(int)$_POST['low']]);
  $id=$pdo->lastInsertId();if((int)$_POST['qty']>0)$q("INSERT INTO moves(product_id,type,qty,unit_price,note) VALUES(?,'in',?,?,'Opening stock')",[$id,(int)$_POST['qty'],$_POST['cost']]);}
 if($a=='move'){$t=$_POST['type'];$n=max(1,(int)$_POST['qty']);$pid=(int)$_POST['pid'];
  $pr=$pdo->query("SELECT * FROM products WHERE id=$pid")->fetch();
  if($pr){$delta=$t=='sale'?-$n:$n;
   if($t=='sale'&&$pr['qty']<$n){$_SESSION['f']="Only {$pr['qty']} of {$pr['name']} in stock.";}
   else{$up=$_POST['price']!==''?$_POST['price']:($t=='in'?$pr['cost']:$pr['price']);
    $q('UPDATE products SET qty=qty+? WHERE id=?',[$delta,$pid]);
    $q('INSERT INTO moves(product_id,type,qty,unit_price,customer,note) VALUES(?,?,?,?,?,?)',[$pid,$t,$n,$up,$_POST['customer'],$_POST['note']]);
    $_SESSION['f']='Saved.';}}}
 if($a=='addpartner')$q('INSERT INTO partners(name,phone) VALUES(?,?)',[$_POST['name'],$_POST['phone']]);
 if($a=='ledger')$q('INSERT INTO ledger(partner_id,type,amount,note) VALUES(?,?,?,?)',[(int)$_POST['pid'],$_POST['type'],$_POST['amount'],$_POST['note']]);
 if($a=='delp'){$q('DELETE FROM products WHERE id=?',[(int)$_POST['id']]);}
 header('Location: index.php?p='.$p);exit;}
$flash=$_SESSION['f']??'';unset($_SESSION['f']);
$T="<input type=hidden name=t value='{$_SESSION['t']}'>";
$prods=$pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
$one=fn($sql)=>$pdo->query($sql)->fetchColumn()?:0;
?><!doctype html><html><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1"><title>Spenzit Admin</title><link rel=stylesheet href=admin.css>
<body><nav><b>Spenzit<i>.</i></b><?php foreach(['dash'=>'Dashboard','stock'=>'Stock','sales'=>'Sales & returns','partners'=>'Partners owing'] as $k=>$l)echo "<a href='?p=$k'".($p==$k?' class=on':'').">$l</a>"?><a href="?out=1" class=out>Log out</a></nav><main>
<?php if($flash)echo "<div class=flash>".$h($flash)."</div>";
if($p=='dash'):
 $sold=$one("SELECT SUM(qty*unit_price) FROM moves WHERE type='sale'");$ret=$one("SELECT SUM(qty*unit_price) FROM moves WHERE type='return'");
 $val=$one("SELECT SUM(qty*cost) FROM products");$owed=$one("SELECT SUM(IF(type='owed',amount,-amount)) FROM ledger");
 $units=$one("SELECT SUM(qty) FROM products");$ss=$one("SELECT SUM(qty) FROM moves WHERE type='sale'");$rr=$one("SELECT SUM(qty) FROM moves WHERE type='return'");
 $low=array_filter($prods,fn($x)=>$x['qty']<=$x['low_at']);?>
<h1>Dashboard</h1><div class=grid>
<div class=stat><span>Stock on hand</span><b><?=$units?> units</b><small>worth <?=$m($val)?> at cost</small></div>
<div class=stat><span>Sold</span><b><?=$ss?> units</b><small><?=$m($sold)?></small></div>
<div class=stat><span>Returned</span><b><?=$rr?> units</b><small><?=$m($ret)?> refunded</small></div>
<div class="stat hot"><span>Partners owe us</span><b><?=$m($owed)?></b><small>outstanding balance</small></div></div>
<h2>Running low</h2><?php if(!$low)echo"<p class=muted>Everything is stocked above its alert level.</p>";else{echo'<table><tr><th>Item<th>Left<th>Alert at';foreach($low as $x)echo"<tr><td>".$h($x['name'])."<td><b class=err>{$x['qty']}</b><td>{$x['low_at']}";echo'</table>';}?>
<h2>Latest activity</h2><table><tr><th>When<th>Item<th>Type<th>Qty<th>Amount<th>Customer<?php foreach($pdo->query("SELECT m.*,p.name FROM moves m JOIN products p ON p.id=m.product_id ORDER BY m.id DESC LIMIT 10") as $r)echo"<tr><td>".substr($r['created'],0,16)."<td>".$h($r['name'])."<td><span class='tag {$r['type']}'>{$r['type']}</span><td>{$r['qty']}<td>".$m($r['qty']*$r['unit_price'])."<td>".$h($r['customer']);?></table>

<?php elseif($p=='stock'):?>
<h1>Stock</h1><details><summary>+ Add new product</summary><form method=post class=row><?=$T?><input type=hidden name=do value=addp><input name=sku placeholder="SKU / code"><input name=name placeholder="Product name" required><input name=cost type=number step=.01 placeholder="Cost per unit" required><input name=price type=number step=.01 placeholder="Selling price" required><input name=qty type=number placeholder="Opening qty" value=0><input name=low type=number placeholder="Low-stock alert" value=5><button>Save product</button></form></details>
<h2>Receive more stock</h2><form method=post class=row><?=$T?><input type=hidden name=do value=move><input type=hidden name=type value=in><select name=pid required><?php foreach($prods as $x)echo"<option value={$x['id']}>".$h($x['name'])."</option>"?></select><input name=qty type=number min=1 placeholder=Quantity required><input name=price type=number step=.01 placeholder="Unit cost (optional)"><input name=note placeholder="Supplier / shipment note"><button>Add to stock</button></form>
<h2>Inventory</h2><table><tr><th>SKU<th>Product<th>Cost<th>Price<th>In stock<th>Value<th><?php foreach($prods as $x)echo"<tr><td>".$h($x['sku'])."<td>".$h($x['name'])."<td>".$m($x['cost'])."<td>".$m($x['price'])."<td>".($x['qty']<=$x['low_at']?"<b class=err>{$x['qty']}</b>":$x['qty'])."<td>".$m($x['qty']*$x['cost'])."<td><form method=post onsubmit=\"return confirm('Delete this product?')\">$T<input type=hidden name=do value=delp><input type=hidden name=id value={$x['id']}><button class=link>Delete</button></form>"?></table>

<?php elseif($p=='sales'):?>
<h1>Sales &amp; returns</h1><form method=post class=row><?=$T?><input type=hidden name=do value=move><select name=type><option value=sale>Sale</option><option value=return>Customer return</option></select><select name=pid required><?php foreach($prods as $x)echo"<option value={$x['id']}>".$h($x['name'])." (".$x['qty']." left)</option>"?></select><input name=qty type=number min=1 placeholder=Quantity required><input name=price type=number step=.01 placeholder="Unit price (default: list price)"><input name=customer placeholder=Customer><input name=note placeholder=Note><button>Record</button></form>
<table><tr><th>When<th>Item<th>Type<th>Qty<th>Total<th>Customer<th>Note<?php foreach($pdo->query("SELECT m.*,p.name FROM moves m JOIN products p ON p.id=m.product_id WHERE m.type IN('sale','return') ORDER BY m.id DESC LIMIT 200") as $r)echo"<tr><td>".substr($r['created'],0,16)."<td>".$h($r['name'])."<td><span class='tag {$r['type']}'>{$r['type']}</span><td>{$r['qty']}<td>".$m($r['qty']*$r['unit_price'])."<td>".$h($r['customer'])."<td>".$h($r['note']);?></table>

<?php else:?>
<h1>Partners owing us</h1><details><summary>+ Add partner</summary><form method=post class=row><?=$T?><input type=hidden name=do value=addpartner><input name=name placeholder="Partner name" required><input name=phone placeholder=Phone><button>Save</button></form></details>
<form method=post class=row><?=$T?><input type=hidden name=do value=ledger><select name=pid required><?php foreach($pdo->query('SELECT * FROM partners ORDER BY name') as $x)echo"<option value={$x['id']}>".$h($x['name'])."</option>"?></select><select name=type><option value=owed>They took goods / owe us</option><option value=paid>They paid us</option></select><input name=amount type=number step=.01 placeholder=Amount required><input name=note placeholder="Items / note"><button>Record</button></form>
<table><tr><th>Partner<th>Phone<th>Owed<th>Paid<th>Balance<?php foreach($pdo->query("SELECT p.*,IFNULL(SUM(IF(l.type='owed',l.amount,0)),0) o,IFNULL(SUM(IF(l.type='paid',l.amount,0)),0) k FROM partners p LEFT JOIN ledger l ON l.partner_id=p.id GROUP BY p.id ORDER BY (o-k) DESC") as $r)echo"<tr><td>".$h($r['name'])."<td>".$h($r['phone'])."<td>".$m($r['o'])."<td>".$m($r['k'])."<td><b".($r['o']>$r['k']?' class=err':'').">".$m($r['o']-$r['k'])."</b>";?></table>
<h2>Recent entries</h2><table><tr><th>When<th>Partner<th>Type<th>Amount<th>Note<?php foreach($pdo->query("SELECT l.*,p.name FROM ledger l JOIN partners p ON p.id=l.partner_id ORDER BY l.id DESC LIMIT 50") as $r)echo"<tr><td>".substr($r['created'],0,16)."<td>".$h($r['name'])."<td><span class='tag {$r['type']}'>{$r['type']}</span><td>".$m($r['amount'])."<td>".$h($r['note']);?></table>
<?php endif?></main>
