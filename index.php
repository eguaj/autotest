<?php

$pid = file_get_contents("autotest.lock");
$isRunning = ($pid !== false);
$error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';
$uptime = shell_exec("uptime");

if (isset($_REQUEST['rerun'])) {
	if ($isRunning) {
		$error = sprintf("Autotest already running (pid = %s)", trim($pid));
		header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode($error));
		exit(0);
	} else {
		$script = $_REQUEST['rerun'];
		if ($script == '') {
			system("./autotest < /dev/null > /dev/null 2>&1 &");
		} else {
			$script = preg_replace(':/:', '_', $script);
			system(sprintf("./autotest %s < /dev/null > /dev/null 2>&1 &", escapeshellarg($script)));
		}
		header('Location: ' . $_SERVER['PHP_SELF']);
		exit(0);
	}
}
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
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

<div id="error" class="failed">
<pre>
<?php	print(htmlspecialchars($error)) ?>
</pre>
</div>

<div id="uptime">
<pre>
<?php	print(htmlspecialchars($uptime)) ?>
</pre>
</div>

<div id="buttonbar">
<?php	if (!$isRunning) { ?>
[<a href="?rerun">Re-run now</a>]
<?php	} else { ?>
[Running...]
<?php	} ?>
</div>
<div id="results">

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
	if (!preg_match('/^(?<time>[0-9T:-]+)\s(?<script>.+?)\s(?<status>[a-zA-Z]+)$/', $line, $m)) {
		continue;
	}
	$url = urlencode(sprintf("%s_%s.log", $m['script'], $m['time']));
	$status_class = ($m['status'] == 'FAILED') ? 'failed' : 'succeeded';
	print sprintf("<li>");
	print sprintf("%s <span class=\"%s\">%s</span>", $m['time'], $status_class, $m['status']);
	print sprintf("&nbsp;<a href=\"logs/%s\" title=\"View log\">%s</a> ", $url, urlencode($m['script']));
	print sprintf("[");
	print sprintf("<a href=\"?rerun=%s\" title=\"Re-run single script\">r</a>", urlencode($m['script']));
	print sprintf(",");
	print sprintf("<a href=\"contexts/%s/\" target=\"_blank\" title=\"Open context\">o</a>]", urlencode($m['script']));
	print sprintf("</li>\n");
}
print "</ul>\n";
?>
</div>

</body>
</html>
