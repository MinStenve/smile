# 关于 Smile 框架
Smile 是一个轻量级的 PHP 框架，代码精炼只需要一个 php 文件即可运行。框架封装了开发过程中所需要基本操作，代码不超过 1000 行，去除注释后不到 500 行。  
Smile 也是一个非常自由的框架，没有强制的继承，命名等要求。可以自由的修改默认值，自由的使用命名规则。  
也可以完全使用自己的自动加载规则，这样很多东西都可以自定。这使得 Smile 更像一个提供了封装好操作的工具包。

## 获取 Smile 

[https://github.com/laomafeima/smile](https://github.com/laomafeima/smile)

## Hello World
这里以一个简单的例子让大家了解 Smile

```
<?php

define("DEBUG", true); // 开启 debug 便于开发调试。在引入框架前定义。
include 'smile.php'; // 引入框架。
// 设置路由规则，并有一个匿名函数响应请求
\Application::setRoutes(array('/\//' => function()
    {
    	echo 'Hello World.'; // 输出信息
	}
));
\Application::start(); //运行应用
```

