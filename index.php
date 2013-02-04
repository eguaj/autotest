<?php

$pid = file_get_contents("autotest.lock");
$isRunning = ($pid !== false);
$error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';
$uptime = trim(shell_exec("uptime"));
$hostname = gethostname();

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
} else if (isset($_REQUEST['deleteall'])) {
	if ($isRunning) {
		$error = sprintf("Autotest is running (pid = %s)", trim($pid));
		header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode($error));
		exit(0);
	}
	$dir = opendir('logs');
	if ($dir === false) {
		$error = sprintf("Error opening 'logs' dir.");
		header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode($error));
		exit(0);
	}
	while (($file = readdir($dir)) !== false) {
		if ($file == '.' || $file == '..') {
			continue;
		}
		unlink('logs' . DIRECTORY_SEPARATOR . $file);
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
#results {
	margin: 1em;
}
.monospace {
	font-family: monospace;
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
<a href="<?php print($_SERVER['PHP_SELF']) ?>" title="Reload"><?php print(htmlspecialchars($hostname)) ?></a> | <?php print(htmlspecialchars($uptime)) ?>
</pre>
</div>

<div id="buttonbar-top">
<?php	if (!$isRunning) { ?>
[<a href="?rerun">Re-run now</a>]
<?php	} else { ?>
[Running...]
<?php	} ?>
</div>

<div id="results">

<?php
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
?>

<table>
<?php
for ($i = 0; $i < count($lines); $i++) {
	$line = $lines[$i];
	if (!preg_match('/^(?<time>[0-9T:-]+)\s(?<script>.+?)\s(?<status>[a-zA-Z]+)$/', $line, $m)) {
		continue;
	}
	$url = urlencode(sprintf("%s_%s.log", $m['script'], $m['time']));
	$status_class = ($m['status'] == 'FAILED') ? 'failed' : 'succeeded';
?>
	<tr>
		<td><?php print($m['time']) ?></td>
		<td><span class="<?php print($status_class) ?>"><?php print($m['status']) ?></span></td>
		<td><a href="<?php print('logs/' . $url) ?>" title="View log"><?php print(htmlspecialchars($m['script'])) ?></a></td>
		<td>(</td>
		<td><a class="monospace" href="?rerun=<?php print(urlencode($m['script'])) ?>" title="Re-run single script">r</a></td>
		<td>,</td>
		<td><a class="monospace" href="<?php print('contexts/' . urlencode($m['script']) . '/') ?>" title="Open context">O</a></td>
		<td>)</td>
	</tr>
<?php
}
?>
</table>
</div>

<div id="buttonbar-bottom">
<?php	if (!$isRunning) { ?>
[<a href="?deleteall" title="Delete all logs">Delete all logs</a>]
<?php	} ?>
</div>

</body>
</html>
