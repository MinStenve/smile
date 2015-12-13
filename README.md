# 关于 Smile 框架
Smile 是一个轻量级的 PHP 框架，代码精炼只需要一个 php 文件即可运行。框架封装了开发过程中所需要基本操作，代码不超过 1000 行，去除注释后不到 500 行。  
Smile 也是一个非常自由的框架，没有强制的继承，命名等要求。可以自由的修改默认值，自由的使用命名规则。  

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

## 更多信息

想要了解更多的信息，请访问 [smile.laoma.im](http://smile.laoma.im)

##意见与反馈
如果有建议可以通过 [Issues](https://github.com/laomafeima/smile/issues) 或者 [Email](mailto:laomafeima@gmail.com) 进行反馈。
