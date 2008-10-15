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
function nice_folder($f) {
	if ($f == "INBOX") return "Inbox";
	else return $f;
}
function nice_inf($f) {
	if ($f == "INBOX") return "inbox";
	else return "folder";
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
function indent($mess) {
	return "> ".ereg_replace("\n","\n> ",$mess);
}
function enewtext($to, $cc, $bcc, $sub, $con) {
	return "<form method=\"post\" action=\"index.php?do=send\" id=\"form\">
	To: <input name=\"to\" value=\"$to\"/><br/>
	CC: <input name=\"cc\" value=\"$cc\"/><br/>
	BCC: <input name=\"bcc\" value=\"$bcc\"/><br/>
	Subject: <input name=\"subject\" value=\"$sub\"><br/>
	<textarea rows=\"20\" cols=\"60\" name=\"content\">$con</textarea><br/>
	<button type=\"submit\">Send<button>
</form>";
}
session_start();

if ($_POST['username']) {
	$_SESSION['username'] = $_POST['username'];
	$_SESSION['password'] = $_POST['password'];
}
$server = "localhost";
$user = $_SESSION['username']."@freedomdreams.co.uk";
$uname = $_SESSION['username'];
$pass = $_SESSION['password'];

$folder = $_GET['folder'];
if ($folder) $_SESSION['folder'] = $folder;
else {
	$folder = $_SESSION['folder'];
	if (!folder) {
		$folder = "INBOX";
		$_SESSION['folder'] = "INBOX";
	}
}

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
a {
	color: blue;
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
tr.header {
	background-color: #AAFFAA;
}
</style>
<script type="text/javascript" src="ajax.js"></script>
</head>
<body>

<h1>AGPLMail</h1>
<?php

if ($_GET['do'] == logout) {
	session_destroy(); ?>
Succesfully logged out, <a href="index.php">return to login</a>?
<?php }
elseif (!$_SESSION['username']) {
?>

<h2>Login</h2>
<form method="post" action="index.php">
	User: <input name="username"></input><br/>mess
	Password: <input name="password" type="password"></input><br/>
	<button type="submit">Submit</button>
</form>

<?php }
else {

echo "<div id=\"intro\">Welcome ".$uname." it is ".date("H:i").". <a href=\"index.php?do=logout\">Logout</a>?</div>";

$mbox = @imap_open("{".$server."/imap/notls}".$folder, $user, $pass);

echo "<div id=\"sidebar\">";
echo "<a href=\"index.php?do=new\">New Email</a>";
echo "<h2>Folders</h2>\n";
$folders = imap_list($mbox, "{".$server."}", "*");

foreach ($folders as $f) {
	$f = ereg_replace("\{.*\}","",$f);
    echo "<a href=\"index.php?do=list&folder=".$f."\">".nice_folder($f)."</a><br />\n";
}

echo "</div><div id=\"main\">";

if ($_GET['do'] == "send") {
#	print_r($_POST);
	imap_mail($_POST["to"], $_POST["subject"], $_POST["content"], $_SESSION["headers"], $_POST["cc"], $_POST["bcc"], $user);
	$_SESSION["headers"] = "";
?>
<h2>Message Sent</h2>
<a href="index.php">Return to inbox</a>?
<?php }
elseif ($_GET['do'] == "del") {
	imap_delete($mbox,$_GET['msgno']);
	imap_expunge($mbox)
?>
<h2>Message Deleted</h2>
<a href="index.php">Return to inbox</a>?
<?php }
elseif ($_GET['do'] == "new") {
	echo "<h2>New Email</h2>";
	echo enewtext("","","","","");
}
elseif ($_GET['do'] == "message") {
	$convo = $_GET['convo'];
	$convos = $_SESSION['convos'];
	echo "<a href=\"index.php?do=list\">Back to ".nice_inf($folder)."</a> <a href=\"index.php?do=del&convo=$convo\">Delete</a><br>";
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
	#	print_r($header);
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
	echo "<h2>".nice_folder($folder)."</h2>\n";

	$status = imap_status($mbox, "{".$server."}".$folder, SA_ALL);
#	echo "There are ".$status->messages." messages in the ".nice_inf($folder).".<br><br>\n";
	if ($status->messages != 0) {
		$threads = imap_thread($mbox);

		echo "<table width=\"100%\" id=\"list\">";
		echo "<tr class=\"header\"><td width=\"30%\">From</td><td width=\"55%\">Subject</td><td width=\"15%\">Date</td></tr>";
		$threadlen = 0;
		$convos = array();
		$i = 0;
		foreach ($threads as $key => $val) {
#			$convos[$i] = array();
			$tree = explode('.', $key);
			if ($tree[1] == 'num' && $val != 0) {
				if($threadlen == 0) {
					$header = imap_headerinfo($mbox, $val);
				}
				$threadlen++;
				$convos[$i][] = $val;
			} elseif ($tree[1] == 'branch') {
				if ($threadlen != 0) {
					echo "<tr class=\"read\"><td>".$header->fromaddress." (".$threadlen.")</td><td><a href=\"index.php?do=message&convo=$i\">".$header->subject."</a></td><td>".nice_date($header->udate)."</td></tr>\n";
					$i++;
				}
				$threadlen = 0;
			}
		}
		echo "</table>";
		$_SESSION['convos'] = $convos;
		
		
		echo "<h3>Threads</h3>";
		print_r($threads);

		echo "<h3>Convos</h3>";
		print_r($_SESSION['convos']);

	}	
}

echo "</div>";

imap_close($mbox);

} ?>

<br/>AGPLMail is released under the <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">AGPL v3</a>. Care to see the <a href="index.php?do=src">Source Code</a>?

</body>
</html>
