<?php
$ch = curl_init('http://127.0.0.1:8000/test_stdout.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
echo "Response:\n" . $res . "\n";
