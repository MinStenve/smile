<?php

define('DEBUG', true);
define('APP_PATH', dirname(__FILE__) . '/src/');
include '../../src/smile.php';
// 设置路由规则，并有一个匿名函数响应请求
\Application::setRoutes(array(
            '/^\/$/' => 'App\Handler\HomeHandler',
            '/\/Login/' => 'App\Handler\LoginHandler',
            '/\/Compose/' => 'App\Handler\ComposeHandler',
            ));
\Application::start(); //运行应用
