<!DOCTYPE HTML>
<html>
<head>
<title>autotest :: automated PU test report</title>
<style>
span.failed {
	color: red;
}
span.succeeded {
	color: green;
}
</style>
<body>
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
