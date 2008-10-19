<?php
/*
AGPLMail, does what it says on the tin, an AGPL'd webmail app.
Copyright (C) 2008 Ben Webb <dreamer@freedomdreams.co.uk>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include "config.php";

$me = $_SERVER['SCRIPT_NAME'];

if ($_GET['do'] == "src") {
	header('Content-type: text/plain');
	$myself = file($_SERVER['SCRIPT_FILENAME']);
	foreach ($myself as $line)
		echo $line;
	die();
}

function nice_date($indate) {
	$now = time();
	return date("H:i j M",$indate);
}
function nice_view($f) {
	if ($f == "inbox") return "Inbox";
	elseif ($f == "arc") return "Archive";
	else return $f;
}
function nice_addr_list($list) {
	$strout = "";
	$first = true;
	foreach ($list as $item) {
		if ($first) $first = false;
		else $strout .= ", ";
		$strout .= $item->personal." &lt;".$item->mailbox."@".$item->host."&gt;";
	}
	return $strout;
}
function nice_re($sub) {
	if (ereg('Re: .*',$sub))
		return $sub;
	else return "Re: ".$sub;
}
function nice_subject($sub) {
	if ($sub) return $sub;
	else return "(no subject)";
}
function nice_s($num) {
	if ($num == 1)
		return "";
	else
		return "s";
}
function indent($mess) {
	return "> ".ereg_replace("\n","\n> ",$mess);
}
function enewtext($to, $cc, $bcc, $sub, $con) {
	return "<form method=\"post\" action=\"$me?do=send\" id=\"form\">
	To: <input name=\"to\" value=\"$to\"/><br/>
	CC: <input name=\"cc\" value=\"$cc\"/><br/>
	BCC: <input name=\"bcc\" value=\"$bcc\"/><br/>
	Subject: <input name=\"subject\" value=\"$sub\"><br/>
	<textarea rows=\"20\" cols=\"60\" name=\"content\">$con</textarea><br/>
	<button type=\"submit\">Send<button>
</form>";
}
function actions() {
	global $view;
	if ($view == "inbox")
		$atext = "<button type=\"button\" onClick=\"javascript:moreact('arc')\">Archive</button>";
	else
		$atext = "<button type=\"button\" onClick=\"javascript:moreact('unarc')\">Move to Inbox</button>";
	$atext .= " <button type=\"button\" onClick=\"javascript:moreact('del')\">Delete</button> <select><option>More Actions</option><option onClick=\"javascript:moreact('read')\">Mark as Read</option><option onClick=\"javascript:moreact('unread')\">Mark as Unread</option></select> <a href=\"$self\">Refresh</a>";
	return $atext;
}
function add_setting($name, $value) {
	global $con;
	global $db_prefix;
	global $user;
	if ($result = mysql_query("SELECT * FROM `".$db_prefix."settings` WHERE account='$user' AND name='$name'",$con)); else die(mysql_error());
	if (mysql_fetch_array($result)) {
		if (mysql_query("UPDATE `".$db_prefix."settings` SET value='$value' WHERE account='$user' AND name='$name'", $con)); else die(mysql_error());
	}
	else {
		if (mysql_query("INSERT INTO `".$db_prefix."settings` (account, name, value) VALUES('$user', '$name', '$value')", $con)); else die(mysql_error());
	}
}
function get_setting($name) {
	global $con;
	global $db_prefix;
	global $user;
	if ($result = mysql_query("SELECT * FROM `".$db_prefix."settings` WHERE account='$user' AND name='$name'",$con)); else die(mysql_error());
	if ($row=mysql_fetch_array($result)) {
		return $row["value"];
	}
}

$con = mysql_connect($db_host,$db_name,$db_pass);
if (!$con) {
  die('Could not connect: ' . mysql_error());
}
if (mysql_select_db($db_db, $con)); else die(mysql_error()); 
session_start();

if ($_POST['username']) {
	$_SESSION['username'] = $_POST['username'];
	$_SESSION['password'] = $_POST['password'];
}
$user = $_SESSION['username'].$userprefix;
$uname = $_SESSION['username'];
$pass = $_SESSION['password'];

$view = $_GET['view'];
if ($view) $_SESSION['view'] = $view;
else {
	$view = $_SESSION['view'];
	if (!$view) {
		$view = "inbox";
		$_SESSION['view'] = "inbox";
	}
}
$folder = "INBOX";

/*
$folder = $_GET['folder'];
if ($folder) $_SESSION['folder'] = $folder;
else {
	$folder = $_SESSION['folder'];
	if (!folder) {
		$folder = "INBOX";
		$_SESSION['folder'] = "INBOX";
	}
}
*/

if ($_GET['do'] == "ajax") {
	$msgno = $_POST["msgno"];
	$mbox = @imap_open("{".$server."/imap/notls}".$folder, $user, $pass);
	$header = imap_headerinfo($mbox,$msgno);
	$body = imap_body($mbox, $msgno);
	echo enewtext($header->reply_toaddress,"","",nice_re($header->subject),"On ".date("j F Y H:i",$header->udate).", ".$header->fromaddress." wrote:\n".indent($body));
	$_SESSION["headers"] = "In-Reply-To: ".$header->message_id."\n";
	die();
}

?>
<html>
<head>
<title>AGPLMail</title>
<style>
body {
	font-family: arial, helvetica, sans-serif;
}
a {
	color: blue;
}
h1, h2 {
	font-family: serif;S
}
h1 {
	display: inline;
}
#intro {
	margin-bottom: 16px;
}
#sidebar {
	float: left;
	width: 100px;
	margin-right: 27px;
	margin-top: 16px;
}
#sidebar h2 {
	margin-bottom: 0px;
}
#main {
	margin-left: 127px;
}
#main h2 {
	text-align: center;
	margin: 0px;
}
.ehead, .efoot {
	font-style: italic;
	background-color: #AAFFAA;
	padding: 7px;
}
#esend, .econ {
	margin-left: 16px;
}
.emess {
	border-left: medium solid #AAFFAA;
	border-right: medium solid #AAFFAA;
	margin-bottom: 15px;
}
#reply {
	visibility: hidden;
	position: absolute;
	top: 0;
	left: 0;
}
#list {
	border-left: thin solid black;
	border-right: thin solid black;
	border-top: thin solid black;
	border-spacing: 0px;
}
#list td {
	border-bottom: thin solid black;
	padding: 3px;
}
tr.read {
	background-color: #DDFFDD;
}
tr.read_sel, tr.unread_sel {
	background-color: #FFFFDD;	
}
tr.header {
	background-color: #AAFFAA;
}
#notif {
	background-color: #FFFF55;
}
</style>
<script type="text/javascript" src="ajax.js"></script>
</head>
<body>

<h1>AGPLMail</h1>
<?php

if ($_GET['do'] == logout) {
	session_destroy(); ?>
<h2>Logged out</h2> <a href="<?php echo $me ?>">Return to login</a>?
<?php }
elseif (!$_SESSION['username']) {
?>

<h2>Login</h2>
<form method="post" action="<?php echo $me ?>">
	User: <input name="username"></input><br/>
	Password: <input name="password" type="password"></input><br/>
	<button type="submit">Submit</button>
</form>

<?php }
else {

$mbox = @imap_open("{".$server."/imap/notls}".$folder, $user, $pass);

if (!$mbox) {
	session_destroy(); ?>
<h2>Sorry login failed</h2> <a href="<?php echo $me ?>">Try again</a>?
<?php }
else {

echo "<div id=\"intro\">Welcome ".$uname." it is ".date("H:i").". <a href=\"$me?do=logout\">Logout</a>?</div>";

echo "<div id=\"sidebar\">";
echo "<a href=\"$me?do=new\">New Email</a>";
echo "<h2>Folders</h2>\n";
/*
$folders = imap_list($mbox, "{".$server."}", "*");

foreach ($folders as $f) {
	$f = ereg_replace("\{.*\}","",$f);
    echo "<a href=\"$me?do=list&folder=".$f."\">".nice_folder($f)."</a><br />\n";
}
*/
?>
<a href="<?php echo $me ?>?do=list&pos=0&view=inbox">Inbox</a><br/>
<a href="<?php echo $me ?>?do=list&pos=0&view=arc">Archive</a><br/>
<br/>
<a href="<?php echo $me ?>?do=settings">Settings</a><br/>
<?php

echo "</div><div id=\"main\">";

if ($_GET['do'] == "listaction" || $_GET['do'] == "messaction") {
	$convos = $_SESSION['convos'];
	$selection = split(",",$_GET['range']);
	if ($_GET['type'] == "del") {
		foreach ($selection as $convo) {
			foreach ($convos[$convo] as $msgno) {
				imap_delete($mbox,$msgno);
			}
		}
		imap_expunge($mbox);
		$notif = sizeof($selection)." message".nice_s(sizeof($selection))." deleted FOREVER.";
	}
	elseif ($_GET['type'] == "arc") {
		foreach ($selection as $convo) {
			foreach ($convos[$convo] as $msgno) {
				if ($result = mysql_query("SELECT * FROM `".$db_prefix."mess` WHERE account='$user' AND msgno=$msgno",$con)); else die(mysql_error());
				if (mysql_fetch_array($result)) {
					if (mysql_query("UPDATE `".$db_prefix."mess` SET archived=true WHERE account='$user' AND msgno=$msgno", $con)); else die(mysql_error());
				}
				else {
					if (mysql_query("INSERT INTO `".$db_prefix."mess` (account, msgno, archived) VALUES('$user', $msgno, true)", $con)); else die(mysql_error());
				}
			}
		}
		$notif = sizeof($selection)." message".nice_s(sizeof($selection))." sent to archive";
	}
	elseif ($_GET['type'] == "unarc") {
		foreach ($selection as $convo) {
			foreach ($convos[$convo] as $msgno) {
				if (mysql_query("UPDATE `".$db_prefix."mess` SET archived=false WHERE account='$user' AND msgno=$msgno", $con)); else die(mysql_error());
			}
		}
		$notif = sizeof($selection)." message".nice_s(sizeof($selection))." returned to inbox";
	}
	else {
		$msglist = "";
		$first = true;
		foreach ($selection as $convo) {
			foreach ($convos[$convo] as $msgno) {
				if ($first) $first = false;
				else $msglist .= ",";
				$msglist .= $msgno;
			}
		}
		if ($_GET['type'] == "read") {
			imap_setflag_full($mbox,$msglist,"\\Seen");
			$notif = sizeof($selection)." message".nice_s(sizeof($selection))." marked as read.";
		}
		if ($_GET['type'] == "unread") {
			imap_clearflag_full($mbox,$msglist,"\\Seen");
			$notif = sizeof($selection)." message".nice_s(sizeof($selection))." marked as unread.";
		}
	}
}

if ($_GET['do'] == "messaction") {
	if ($_GET['type'] != "del" && $_GET['type'] != "read" && $_GET['type'] != "unread") {
		$_GET['do'] = "message";
	}
	if ($_GET['type'] == "arc") {
		$view = "arc";
		$_SESSION['view'] = "arc";
	}
	if ($_GET['type'] == "unarc") {
		$view = "inbox";
		$_SESSION['view'] = "inbox";
	}
}

$archived = array();
if ($result = mysql_query("SELECT * FROM `".$db_prefix."mess` WHERE account='$user'",$con)); else die(mysql_error());
while ($row = mysql_fetch_array($result)) {
	$archived[$row["msgno"]] = $row['archived'];
}

if ($_GET['do'] == "settings") {
	if ($_POST['name']) {
		add_setting("name",$_POST['name']);
	}
	?>
<h2>Settings</h2>
<form method="post" action="<?php echo $me ?>?do=settings">
	Name: <input name="name" value="<?php echo get_setting("name"); ?>"></input><br/>
	<button type="submit">Submit</button>
</form>
	<?php
}
elseif ($_GET['do'] == "send") {
#	print_r($_POST);
	imap_mail($_POST["to"], $_POST["subject"], $_POST["content"], $_SESSION["headers"]."Content-Type: text/plain; charset=\"utf-8\"\n", $_POST["cc"], $user.", ".$_POST["bcc"], get_setting("name")." <$user>");
	$_SESSION["headers"] = "";
?>
<h2>Message Sent</h2>
<a href="<?php echo $me ?>">Return to inbox</a>?
<?php }
elseif ($_GET['do'] == "new") {
	echo "<h2>New Email</h2>";
	echo enewtext("","","","","");
}
elseif ($_GET['do'] == "message") {
	if ($_GET['range']) {
		$convo = $_GET['range'];
	}
	else {
		$convo = $_GET['convo'];
	}
	$convos = $_SESSION['convos'];
?>
<script>
function moreact(value) {
	location.href = "<?php echo $me ?>?do=messaction&type="+value+"&range="+"<?php echo $convo ?>";
}
</script>	
<?php
	echo "<a href=\"$me?do=list\">&laquo; Back to ".nice_view($view)."</a> ".actions()."<br>";
	$header = imap_headerinfo($mbox,$convos[$convo][0]);
	echo "<h2>".$header->subject."</h2>";
	foreach ($convos[$convo] as $key => $msgno) {
		$header = imap_headerinfo($mbox,$msgno);
		$body = nl2br(htmlspecialchars(imap_body($mbox, $msgno)));
		echo "<div class=\"emess\"><div class=\"ehead\">From: ".nice_addr_list($header->from)."<br/>";
		if ($header->to) echo "To: ".nice_addr_list($header->to)."<br/>";
		if ($header->cc) echo "CC: ".nice_addr_list($header->cc)."<br/>";
		echo "Date: ".date("j F Y H:i",$header->udate)."<br/>";
		echo "Subject: ".$header->subject."</div><br/>";
#		print_r($header);
		echo "<div class=\"econ\">".$body."</div>"; ?>
	<script language="javascript">
function reply<?php echo $e ?>() {
	ajax("msgno=<?php echo $msgno ?>", "esend<?php echo $e ?>", false);
}
</script>
<br/><div class="efoot"><a href="javascript:reply<?php echo $e ?>()">Reply</a> Reply to All Forward</div><div id="esend<?php echo $e ?>"></div></div>
	<?php
		$e++;
	}
}
else {
	echo "<h2>".nice_view($view)."</h2>\n";

	if ($notif) {
		echo "<div id=\"notif\">".$notif."</div>";
	}

	$status = imap_status($mbox, "{".$server."}".$folder, SA_ALL);
#	echo "There are ".$status->messages." messages in the ".nice_inf($folder).".<br><br>\n";
	if ($status->messages != 0) {
		$threads = imap_thread($mbox);
		$self = "$me?do=list&folder=$folder";
?>
<script language="javascript">
	function hili(num,base) {
		if (document.getElementById("tick"+num).checked == true) {
			document.getElementById("mess"+num).className = base+"_sel";
		}
		else {
			document.getElementById("mess"+num).className = base;
		}
	}
	function tick(chk,val) {
		if (chk) {
			for (i = 0; i < chk.length; i++) {
				chk[i].checked = val;
				chk[i].onchange();
			}
		}
	}
	function selall() {
		tick(document.form.check_read,true);
		tick(document.form.check_unread,true);
	}
	function selnone() {
		tick(document.form.check_read,false);
		tick(document.form.check_unread,false);
	}
	function selread() {
		tick(document.form.check_read,true);
		tick(document.form.check_unread,false);
	}
	function selunread() {
		tick(document.form.check_unread,true);
		tick(document.form.check_read,false);
	}
	function moreact(value) {
		range=""
		i=0;
		first = true;
		// Big HACK
		while (i < 1000) {
			if (document.getElementById("tick"+i)) {
				if (document.getElementById("tick"+i).checked) {
					if (first) {
						first = false;
					}
					else {
						range += ","
					}
					range += i;
				}
			}
			i++;
		}
		if (range == "") {
			alert("Please select one or more messages.");
		}
		else {
			location.href = "<?php echo $me ?>?do=listaction&type="+value+"&range="+range;
		}
	}
</script>
<?php
		$threadlen = 0;
		$convos = array();
		$i = 0;
		$seen = true;
		$allarchived = true;
		$messrows = array();
		foreach ($threads as $key => $val) {
			$tree = explode('.', $key);
			if ($tree[1] == 'num' && $val != 0) {
				$tmpheader = imap_headerinfo($mbox, $val);
				if ($tmpheader->Unseen == "U" || $tmpheader->Recent == "N") $seen = false;
				if($threadlen == 0) {
					$header = $tmpheader;
				}
				if ($archived[$val] != 1) $allarchived = false;
				$threadlen++;
				$convos[$i][] = $val;
			} elseif ($tree[1] == 'branch') {
				if ($threadlen != 0) {
					if ( (!$allarchived && $view == "inbox") || ($allarchived && $view == "arc") ) {
						if ($seen) $class = "read";
						else $class = "unread";
						$messrows[] = "<tr class=\"$class\" id=\"mess$i\"><td><input type=\"checkbox\" id=\"tick$i\" name=\"check_$class\" onchange=\"javascript:hili($i,'$class')\"></td><td width=\"30%\">".$header->fromaddress." (".$threadlen.")</td><td><a href=\"$me?do=message&convo=$i\" width=\"55%\">".nice_subject($header->subject)."</a></td><td width=\"15%\">".nice_date($header->udate)."</td></tr>\n";
					}
					$i++;
				}
				$threadlen = 0;
				$seen = true;
				$allarchived = true;
			}
		}
		$messrows = array_reverse($messrows);
		if ($_GET['pos']) {
			$liststart = $_GET['pos'];
			$_SESSION['pos'] = $_GET['pos'];
		}
		elseif ($_SESSION['pos']) {
			$liststart = $_SESSION['pos'];
		}
		else {
			$liststart = 0;
		}
		$listlen = 50;
		if (sizeof($messrows) > $liststart+$listlen) {
			$listend = $liststart+$listlen;
			$next = true;
		}
		else {
			$listend = sizeof($messrows);
		}
		echo "<table width=\"100%\" id=\"list\"><form name=\"form\">";
		echo "<tr class=\"header\"><td colspan=\"3\">".actions()."<br/>Select: <a href=\"javascript:selall()\">All</a>, <a href=\"javascript:selnone()\">None</a>, <a href=\"javascript:selread()\">Read</a>, <a href=\"javascript:selunread()\">Unread</a></td>";
		echo "<td>".($liststart+1)." - $listend of ".sizeof($messrows)."<br/>";
		if ($liststart > 0) echo "<a href=\"$me?do=list&view=$view&pos=".($liststart-$listlen)."\">&larr;Prev</a> ";
		if ($next) echo "<a href=\"$me?do=list&view=$view&pos=$listend\">Next&rarr;</a>";
		echo "</td></tr>";
		for ($i=$liststart; $i<$listend; $i++) {
			echo $messrows[$i];
		}
		echo "</form></table>";
		$_SESSION['convos'] = $convos;
		
/*		
		echo "<h3>Threads</h3>";
		print_r($threads);

		echo "<h3>Convos</h3>";
		print_r($_SESSION['convos']);
*/

	}	
}

echo "</div>";

imap_close($mbox);

} } ?>

<br/><br/>AGPLMail is released under the <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">AGPL v3</a>. Care to see the <a href="<?php echo $me ?>?do=src">Source Code</a>?

</body>
</html>
