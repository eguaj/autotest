<?php

$pid = file_get_contents("autotest.lock");
$isRunning = ($pid !== false);
$error = '';

if (isset($_REQUEST['rerun'])) {
	if ($isRunning) {
		$error = sprintf("Autotest already running (pid = %s)", $pid);
	}
	system("./autotest < /dev/null > /dev/null 2>&1 &");
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit(0);
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>autotest :: automated PU test report</title>
<style>
body {
	font-family: sans-serif;
}
.failed {
	color: red;
}
.succeeded {
	color: green;
}
</style>
<body>
<div class="failed">
<pre>
<?php	print(htmlspecialchars($error)) ?>
</pre>
</div>
<?php	if (!$isRunning) { ?>
[<a href="?rerun">Re-run now</a>]
<?php	} else { ?>
[Running...]
<?php	} ?>
</div>

<?
$db = fopen('logs/pu.db', 'r');
if ($db === false) {
	print sprintf("<h1>Error opening 'pu.db' for reading.</h1>\n");
	exit(1);
}

$lines = array();
while ($line = fgets($db)) {
	$lines []= $line;
}
$lines = array_reverse($lines);

print "<ul>\n";
for ($i = 0; $i < count($lines); $i++) {
	$line = $lines[$i];
	if (!preg_match('/^(?<time>[0-9T:-]+)\s(?<runid>.+?)\s(?<status>[a-zA-Z]+)$/', $line, $m)) {
		continue;
	}
	$url = urlencode(sprintf("run_%s_%s.log", $m['runid'], $m['time']));
	$status_class = ($m['status'] == 'FAILED') ? 'failed' : 'succeeded';
	print sprintf("<li>%s <a href=\"logs/%s\">%s</a> <span class=\"%s\">%s</span></li>\n", $m['time'], $url, $m['runid'], $status_class, $m['status']);
}
print "</ul>\n";
?>

</body>
</html>
