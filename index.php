<?php
// [ 应用入口文件 ]
header("Content-type: text/html; charset=utf-8"); 
//开启session
session_start();
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';


