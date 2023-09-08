<?php
require_once __DIR__.'/class/operate.php';

if (isset($argv[1])) {
    $param1 = $argv[1]; // 第一个命令行参数
    $it = explode("=", $param1);
    $_GET[$it[0]] = $it[1];
} else {
    echo "No parameter provided.\n";
}

$qb = new operate();
$fun = $_GET['method'];
$qb->$fun();