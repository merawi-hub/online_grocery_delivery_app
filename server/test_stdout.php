<?php
echo "Before stdout: " . (headers_sent($f1, $l1) ? "sent at $f1:$l1" : "not sent") . "\n";
file_put_contents('php://stdout', "Testing stdout write\n");
echo "After stdout: " . (headers_sent($f2, $l2) ? "sent at $f2:$l2" : "not sent") . "\n";
