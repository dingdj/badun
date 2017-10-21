<?php

require_once dirname(__FILE__) . '/phpqrcode/phpqrcode.php';
$url = urldecode($_GET["data"]);
QRcode::png($url, false, "L", 4,0);