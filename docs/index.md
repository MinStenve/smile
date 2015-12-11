Smile 手册
====

## 关于 Smile 框架
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
\Application::setRoutes(array(
			'/\//' => function()
			{
			echo 'Hello World.'; // 输出信息
			}
			));
\Application::start(); //运行应用
```
## 环境要求

Smile 需要运行在 PHP 5.3 以及以上版本，而且也能在 PHP 7里面很好的运行。
因为 Smile 的路由是 restful 风格的，运行的时候需要开启 pathinfo，
如果要是想使用 Smile 封装的 DB 操作，需要安装并启用 PDO 扩展。

## 命名空间与自动加载

### 使用框架类
Smile 框架封装的操作类因为在开发中经常用到，都在根命名空间里，这样开发中可以少打几个字符

```
<?php

namespace app\Handler;

class Indexhandler
{
	public function get($param)
	{
		\Cookie::set('foo', 'foo'); // 使用 Smile 封装的类
	}
}
```
### 自动加载

Smiel 默认加载方式遵守 [psr-4](http://www.php-fig.org/psr/psr-4/) 规范。默认命名空间前缀为`App`自动记载基础目录为常量 APP_PATH   

#### 说明

>1. 术语「类」是一个泛称；它包含类，接口，traits 以及其他类似的结构；
>
>2. 完全限定类名应该类似如下范例：
>
>    \<NamespaceName>(\<SubNamespaceNames>)*\<ClassName>
>
>    1. 完全限定类名必须有一个顶级命名空间（Vendor Name）；
>    2. 完全限定类名可以有多个子命名空间；
>    3. 完全限定类名应该有一个终止类名；
>    4. 下划线在完全限定类名中是没有特殊含义的；
>    5. 字母在完全限定类名中可以是任何大小写的组合；
>    6. 所有类名必须以大小写敏感的方式引用；
>
>3. 当从完全限定类名载入文件时：
>
>    1. 在完全限定类名中，连续的一个或几个子命名空间构成的命名空间前缀（不包括顶级命名空间的分隔符），至少对应着至少一个基础目录。
>    2. 在「命名空间前缀」后的连续子命名空间名称对应一个「基础目录」下的子目录，其中的命名空间分隔符表示目录分隔符。子目录名称必须和子命名空间名大小写匹配；
>    3. 终止类名对应一个以 `.php` 结尾的文件。文件名必须和终止类名大小写匹配；

*引用自[php-fig](https://github.com/php-fig/fig-standards/blob/master/accepted/zh_CN/PSR-4-autoloader.md#2-说明specification)*

#### 例子
Smile 默认命名空间前缀常量 `CLASS_PREFIX`。默认为 `App\ `。    
*不在命名空间前缀 `CLASS_PREFIX` 的不会去加载它，可以通过自定义实现其他命名空间前缀的自动加载*
自动加载基础目录为常量 `APP_PATH`。默认 APP_PATH 目录为当前文件执行的目。
如果 APP_PATH 的路径为 `/var/www/site/src/` 
下面的 php 文件路径为 `/var/www/site/src/Handler/IndexHandler.php`

```
<?php

namespace app\Handler;

class Indexhandler
{
	public function get($param)
	{
		\Cookie::set('foo', 'foo'); // 使用 Smile 封装的类
	}
}
```

|完整限定名|命名空间前缀|文件路径|
|-------|------|------|
|\App\Handler\IndexHandler|\App|/var/www/site/src/Handler/IndexHandler.php|
|\App\Model\UserModel|\App|/var/www/site/src/Model/UserModel.php|
|\App\Event\Login_LogoutEvent|\App|/var/www/site/src/Event/Login_LogoutEvent.php|
|\App\Model\Dao\UserTable|\App|/var/www/site/src/Model/Dao/UserTable.php|

#### 自定义

Smile 默认命名空间前缀 CLASS_PREFIX
如果想要改变命名空间前缀或者自动加载基础目录可以在引入框架文件前定义 `CLASS_PREFIX` `APP_PATH` 两个常量

```
<?php

define('APP_PATH', '/var/site/php/'); // 定义自动加载基础目录
define('CLASS_PREFIX', 'Foo\\'); // 定义默认命名空间前缀
include 'smile.php'; // 引入框架。
......

\Application::start(); //运行应用

```

如果不想使用 Smile 默认的加载规则或者自定义另外一个命名前缀的加载规则，可以自定一个加载规则，自实现任何自动加载方式。这样做也很简单。只需要在引入框架文件前注册一个自动加载函数即可。
*这样多种加载规则并存*

```

<?php

//注册自动加载函数
spl_autoload_register(function($class)
	{
		$len = strlen('Foo//');
        if (strncmp('Foo//', $class, $len) !== 0)
        {
            return;
        }
		$relative_class = substr($class, $len);
        $file = APP_PATH . str_replace('\\', '/', $relative_class) . '.class..php';
        if (file_exists($file))
        {
            require $file;
        }	
	}
);
include 'smile.php'; // 引入框架。
......

\Application::start(); //运行应用

```

## 常量定义

在应用开始运行的时候会定义一些如应用目录，是否开启调试等常量。如果需要改变默认配置的话，请在引入框架文件前定义这些常量。  
如有时候配置项目文件不可通过 web 访问，仅有入口文件（如 index.php）和静态文件可以通过 web 方式访问，那么可以通过如下代码实现。  

```
<?php

define('APP_PATH', '/var/site/php/'); // 定义应用目录
include 'smile.php'; // 引入框架。

......

\Application::start(); //运行应用

```

如要修改 debug 模式，项目路径和日志文件路径等


```
<?php

define("DEBUG", true); // 开启 debug 便于开发调试。在引入框架前定义。
define('APP_PATH', '/var/site/php/'); // 定义应用目录
define('LOG_PATH', APP_PATH . 'Log/'); // 日志生成目录
define('LANG_PATH', APP_PATH . 'Lang/'); // 语言包目录
define('TPL_PATH', APP_PATH . 'TPL/'); // 模版文件目录
define('DEFAULT_LANG', 'zh-CN'); // 默认使用语言
define('DEFAULT_ERROR_MESSAGE', '网站暂时遇到一些问题'); // 遇到错误时默认提示信息
include 'smile.php'; // 引入框架。

......

\Application::start(); //运行应用

```

| 常量					| 作用   |默认值|
| ------				| ----- |-----|
| DEBUG					|   是否开启调试模式|    false     |
| VERSION				|   框架版本号|     跟随框架版本    |
| CLASS_PREFIX			|   命名空间前缀|   App\   |
| APP_PATH				|   应用目录| 当前脚本所在目录  |
| LOG_PATH				|  生成日志文件目录 |  根目录（APP_PATH）下 Log 目录 |
| LANG_PATH				|  多语言语言包所在目录 |  根目录（APP_PATH）下 Lang 目录 |
| TPL_PATH				|  模版文件所在目录 |  根目录（APP_PATH）下 TPL 目录 |
| DEFAULT_LANG			| 多语言默认语言  | zh-CN  |
| DEFAULT_ERROR_MESSAGE	|  网站遇到问题时，默认给出的提示 | We encountered some problems, please try again later.|


## 路由
Smile 的路由规则必须开启 pathinfo，根据正则，提供了灵活的路由规则，只要正则表达式做得到的，Smile 的路由规则也能做到。
支持 restful 风格
注：`?` 以后的不是 pathinfo 部分，不会参与正则匹配
引入框架文件以后首先要设定路由规则，

```
<?php

define("DEBUG", true); // 开启 debug 便于开发调试。在引入框架前定义。
include 'smile.php'; // 引入框架。
// 设置路由规则，并有一个匿名函数响应请求
\Application::setRoutes(array(
	// 设置匿名函数响应请求
	'/\//' => function()
			{
			echo 'Hello World.'; // 输出信息
			},
	// 设定一个类的完整限定名，自动实例化这个类相应请求
	'/\/test/' => 'App\Handler\TestHandler',
	// 用一个对象去相应请求
	'/\/test2/' => new App\Handler\TestHandler(),
	// 用一个对象去相应请求，并获取参数
	'/\/test3/(\d*)/(.*)' => new App\Handler\TestParamHandler(),
	)
);
\Application::start(); //运行应用
```

如果设置用匿名函数来响应请求，那么符合 URL 规则的请求，无论是 get，post 请求等。都会用这个匿名函数去处理请求  

如果设置指定一个对象去响应 URL 符合规则的请求，框架会根据当前是 get，post，delete，put 等请求方式分别去调用对象的 `$object->get()` 的方法。方法名全小写。  

如果想一个方法相应所有的请求方式，只需要在类里面添加 `any` 方法，无论 get 还是 post 请求。框架都会去调用 `any` 方法去相应这个请求。 any 方法的优先级低于其他方法，比如，get 请求时，如果对象包含 get 方法，那么就会动用 get 方法去处理请求。而不会去掉用 any 方法。  

指定一个类名去相应请求的时候。框架会在每次请求到来的时候自动去实例化这个对象。然后和指定对象一样的规则去处理请求。  
如果想获取 URL 里面的参数，可以使用正则表达式去捕获参数。框架会在调用处理函数/方法的时候自动传参。如 URL `/test3/12/read`  

根据上面设置的路由规则，可以在 Handler 中这样获取参数。  

```
<?php

namespace app\Handler;

class TestParamHandler
{
	public function get($id, $param)
	{
		var_dump($id, $param);
	}
}
```
正则匹配参数不会影响到 get `?` 以后的参数使用。依然可以在 Handler 中获取 get 参数 如 URL `/test3/12/read?user=smile`

```
<?php

namespace app\Handler;

class TestParamHandler
{
	public function get($id, $param)
	{
		var_dump($id, $param);
		var_dump($_GET['user']);
		var_dump(\Request::get('user'));
	}
}
```
如果想在开始处理请求前做一些处理或者在请求结束后做一些操作。框架会在调用对象的处理方法前会调用 `before` 方法。在处理结束后会调用 `finish` 方法，在处理开始和结束的调用。

```
<?php

namespace app\Handler;

class TestParamHandler
{
	/**
	 * 前置操作。
	 */	
	public function before()
	{
		echo 'Before';
	}
	/**
	 * 响应 get 请求
	 */	
	public function get($id, $param)
	{
		var_dump($id, $param);
		var_dump($_GET['user']);
		var_dump(\Request::get('user'));
	}
	/**
	 * 响应 post 请求
	 */	
	public function post($id, $param)
	{
		var_dump($id, $param);
		var_dump(\Request::post('user'));
	}
	/**
	 * 收尾操作。
	 */	
	public function finish()
	{
		echo 'Finish';
	}
}
```
这里例子里面的`before`,`finish`方法，会在 get 与 post 请求到达的时候都会被调用。用法和 `post()` 方法一样，可以自由的使用，也可以获取 URL 参数。

## 参数获取

Request 类封装了获取外部数据的一些操作，比如 `\Request::get()`可以获取 GET 参数，是`?`之后的参数，即 URL 里面的 Query 字段参数如 URL `http://test.com/read?id=3`  可以通过`\Requst::get()` 获取参数  

Smile 封装了 `\Request::get()`,`\Request::post()`，分别用以获取 get，post 参数。  
另外还有一个 `\Request::param()` 方法，会自动根据参数名字检查是 get 或是 post 参数，并返回。如果 get 和 post 都有这个参数，那么有限返回 post 参数。  

`\Request::get()`,`\Request::post()`,`\Request::param()` 三个方法用法一样，分别有三个参数，第一个参数是要获取的参数名字。第二个参数是默认值，如果这个变量不存在就返货默认值，默认值为 `null`，第三个参数为过滤规则，可以根据自己的需要传入参数。规则与 `filter_input` 一致。参见 [php.net](http://php.net/manual/zh/filter.constants.php)  

如果要获取的参数是个数组，不用担心，框架会根据参数类型自动转换的，如果是个数组，那么这三个方法也会返回一个数组的。  

```
<?php

.......
$data = \Request::get('foo');
var_dump($data);
$data = \Request::post('foo');
var_dump($data);
$data = \Request::param('foo');
var_dump($data);
$test = \Request::get('test', 100);
var_dump($test);
$email = \Request::get('email', false, FILTER_VALIDATE_EMAIL);
var_dump($email);
```

除此之外。Request 类海封装了。`isAjax`,`getClientIP`等方法，方便开发。

|方法名字|函数作用|其他|
|--------|--------|----|
|isAjax|是否是 Ajax 请求|如果传入一个参数名字，只要这个请求中参数存在且值为 true 也会通过这个判断|
|getClientIP|返回用户 IP||
|getAcceptLang|返回用户可以接受的语言|多语言情况下根据用户返回不同语言|
|getAccept|返回用户接受的文档类型|在 restful 风格下，可以判断需要返回的文件类型|

+------------------------+------------+----------+----------+
| Header row, column 1   | Header 2   | Header 3 | Header 4 |
| (header rows optional) |            |          |          |
+========================+============+==========+==========+
| body row 1, column 1   | column 2   | column 3 | column 4 |
+------------------------+------------+----------+----------+
| body row 2             | ...        | ...      |          |
+------------------------+------------+----------+----------+

## 模版
Smile 没有去实现一套模版语言，直接在模版文件中使用 php 代码。这样使用起来既灵活又方便，不需要增加额外的学习负担。

项目中模版文件存在 `TPL_PATH` 中，默认在 `APP_PATH . '/TPL'` 目录中。可以通过定义常量 `TPL_PATH` 改变目录位置。
使用时，可以使用 `\TPL::assgin()` 方法给模版复制，因为处于变量安全的考虑，模版中只可使用允许使用的变量。

然后可以调用 `\TPL::render()` 方法来渲染模版。需要在参数中指定模版文件的名字。这个模版文件位于常量 `TPL_PATH` 定义的目录下。也可以指定使用其子目录的模版文件。只需要在参数中指明即可，如 `\TPL::render('User/login.html')`，那么就会去使用 `{TPL_PATH}/User/login.html` 的模版文件。

```
<?php

......
\TPL::assign('data', $data); // 给模版文件复制一个变量
\TPL::render('index.html'); // 指定渲染一个模版文件
//这个模版文件路径就是 {TPL_PATH}/index.html

\TPL::render('User/login.html') //模版文件支持多目录。
//这个模版文件路径是 {TPL_PATH}/User/login.html 
......

```

此外 `render` 方法还有第二个参数，这个参数必须是个数组，是模版文件可以使用的变量。这个数组索引为何变量名字，值是变量，相当与 `assign` 方法。

```
\TPL::assign('data', $data); // 给模版文件复制一个变量
\TPL::render('index.html'); // 指定渲染一个模版文件
```

相当与

```
\TPL::render('index.html', array('data', $data)); // 指定渲染一个模版文件
```

以上这两种用法作用是一样的。都可以在模版中使用变量 `$data` 了

```
<html>
	<header>
	......
	</header>
	<body>
	......
	<?php echo $data;?>
	......
	</body>
</html>
```

另外 TPL 还封装了一个`write` 函数，如果传入的参数是一个字符串，会被直接输出。`\TPL::write($data)` 如果传入的是一个数组或者是对象会被转化成 json 字符串后在输出。  

## 数据库操作

Smile 封装了一个数据库类 `\DB`，轻量级，使用便捷。支持 MySQL，SQLite，MSSQL 等数据库。在使用这个类连接数据库之前，请开启数据库类型对应的 `php_pdo_xxx` 扩展。

### 连接数据库
连接数据，只需要实例化一个 `\DB` 对象即可。

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

```
`\DB` 构造方法需要三个参数。第一个参数必须 `$dsn` 数据源名称或叫做 DSN，包含了请求连接到数据库的信息，如 `mysql:dbname=testdb;host=127.0.0.1`。DSN 由 PDO 驱动名、紧随其后的冒号、以及具体 PDO 驱动的连接语法组成。  

第二个参数是用户名，第三个参数是密码。可以根据需要决定是否传入这两个参数。  
第四个参数是连接选项的设置，必须是一个选项设置键=>值数组，如：

```
<?php

include 'smile.php'; // 引入框架。
// 不需要设定连接参数。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

// 如果需要设定参数。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password', array('charset' => 'utf-8'));

```

### 操作数据库

`get()` 方法查询数据库并返回一条数据。第一个参数 `$sql` 是 SQL 语句，支持参数绑定。第二个参数可选参数，是绑定的参数，必须是数组。第三个参数是，查询选项设定，一般留空就可。如：

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

var_dump($db->get('SELECT * FROM test'));
// 支持参数绑定的使用方法
var_dump($db->get('SELECT * FROM test WHERE id = :id'), array('id' => 15));


// 支持 '?' 做占位符。
$sqlitedb = new \DB('sqlite:example.db');
$sqlitedb->get('SELECT * FROM test WHERE id = ?', array(15));
// 会自动替换变量执行 SQL SELECT * FROM test WHERE id = 15

```

`query()` 方法用法与 `get()` 用法一直，区别就是此方法会返回多条数据。

`insert()` 方法执行插入数据。会返回插入数据的最后一个ID。支持批量插入。但是只会返回最后一条数据的 ID 。第一个参数是 SQL 语句，支持参数绑定。第二个参数是绑定的参数。

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

// 插入一条新数据
var_dump($db->insert('INSERT INTO  test VALUES(:name, :age)'), array('name' => 'test', 'age' => 15));
var_dump($db->insert('INSERT INTO  test VALUES('test', 15)'));
// 两行代码都会向数据库插入一条新数据。

```

`delete()` 删除数据，用法和参数与 `get()`, `query()` 一样，只是返回的值不同，`delete()` 会返回被删除的条数。如果没有被删除的数据会返回 0 。

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

// 删除数据
var_dump($db->delete('DELETE FROM test WHERE id = :id'), array('id' => 15));

```

`update()` 更新数据，用法和参数与 `get()`, `query()` 一样。`update()` 方法会返回被更新的数据行数，即受影响行数。

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

// 更新数据
var_dump($db->update('UPDATE test SET name = :name WHERE id = :id'), array('name' => 'testname', 'id' => 15));

```

`execute()` 如果你需要自定去处理返回结果。那么可以使用 `execute()` ，用法和参数与 `get()`, `query()` 一样。可以执行任何类型的 SQL 语句。此方法会返回 `PDOStatement` 对象。可以自行对结果进行自由的处理。

```
<?php

include 'smile.php'; // 引入框架。
$db = new \DB('mysql:dbname=testdb;host=127.0.0.1', 'user', 'password');

// 更新数据
$sth = $db->execute('UPDATE test SET name = :name WHERE id = :id'), array('name' => 'testname', 'id' => 15);

var_dump($sth->rowCount()); // 打印受影响的行数。

```

## Cookie 操作
Smile 封装对 Cookie 的操作。开发过程中可以方便的对 Cookie 进行操作。
`\Cookie::set()` 方法可以设置一个 cookie 值。第一个参数为 Cookie 名字，第二个参数为 Cookie 的值，这两个是必须参数。
第三个参数是 Cookie 有效时间事件，单位是秒，只需要传来一个数字即可，默认为0
第四个参数为 Cookie 的路径，第五个参数为 Cookie 允许使用的域名。  

`\Cookie::get()`可以获取一个 Cookie 值，只需要参数里传递 Cookie 的名字即可
`\Cookie::delete()` 删除一个 Cookie ，参数里指定 Cookie 名字即可删除 Cookie
`\Cookie::clear()` 调用此方法可以删除所有的 Cookie

## 事件广播机制

在一个项目里，一个特定的操作，会经常触发要一些列的操作。比如，当用户登录的时候，
1.会去检查用户有没有未读的消息。
2.检查是不是有未完成订单，提醒支付。
3.检查 IP 是不是常用 IP。
4.添加登录日志。
等等操作。

如果未来用户登录的时候要添加其他功能。那就只有去修改原来的逻辑代码。
如果我们在用户登录的时候触发一个名字叫 `user.login` 的事件。在用户登录的时候需要处理的操作都去关注这个事件。当这个事件发生的时候，会触发所有的操作。当添加或者删除功能的时候，只需要添加或移除处理代码即可。当然事件广播机制还有更多的用处。

### 注册监听事件

首先要在 `\Application::start()` 之前调用`\Event::on()`注册要监听的事件。然后在代码里面触发这个时间即可。框架会自动调用处理方法的。

`\Event::on()` 方法有三个参数，第一个是要监听的时间的名字，字符串形式，如：`user.login`。第二个参数是，负责处理事件的闭包或者对。如果是个闭包。在触发这个事件的时候会自动调用这个闭包。如果是一个对象，框架会调用对象的`answer`方法去处理这个时间。如果是个类的完整限定名。框架会去实例化这个类，并调用它的`answer`方法。  

同一个事件，可以有多个多次被注册监听。在触发的这个时间的时候，会按照顺序逐个调用响应函数。
第三个参数，是次数限制。比如这个响应函数只允许被调用一次，参数传`1`。那么这个事件即使触发了10次。那么这个响应函数只会被调用一次。这个限制不影响其他观察者对这个事件的监听。

```
<?php

define("DEBUG", true); // 开启 debug 便于开发调试。在引入框架前定义。
include 'smile.php'; // 引入框架。
// 设置路由规则，并有一个匿名函数响应请求
\Application::setRoutes(array(
	// 设定一个类的完整限定名，自动实例化这个类相应请求
	'/\/test/' => 'App\Handler\TestHandler',
	)
);
\Event::on('test1', function(){echo 'Be called.';}); // 用闭包会响应这个事件
\Event::on('test1', new \App\Event\TestEvent()); // 框架会调用用对象的 answer 方法去响应事件
\Event::on('test1', '\App\Event\TestEvent'); // 框架会自动实例化这个类，并调用 answer 方法去响应事件
\Event::on('test1', '\App\Event\TestTimesEvent', 1); // 无论 test1 时间被触发了多次。这个方法只会被掉用一次。并不影响上面三个观察者的调用。仍会被多次调用。

\Event::trigger('test1'); 触发这个事件。这个时候框架会自动调用已经注册的观察者去处理这个事件。
\Application::start(); //运行应用
```
有时候观察者只允许被掉用一次。为了方便这种操作，Event 还封装了`\Event::one()` 方法。这个其实相当于调用`\Event::on()`,并且最后一个参数传自动设成1而已。

### 触发事件
时间只有先注册，后触发时间才有作用。
在`\Event::trigger()`的时候，框架会调用所有的观察着去处理这个请求。  

`\Event::trigger()` 方法有两个参数，第一个参数是触发的事件名字比如`user.login`，那么框架就会调用左右已经注册监听`user.login`时间的函数。  

如果在处理事件的时候需要传递参数。可以在`\Event::trigger()` 方法的第二参数传递过去，如果有多个参数，可以以数组的形式传递过去，框架在调用观察者处理事件的时候，会把第二个参数传给观察者作为参数去处理。

```
<?php

define("DEBUG", true); // 开启 debug 便于开发调试。在引入框架前定义。
include 'smile.php'; // 引入框架。
// 设置路由规则，并有一个匿名函数响应请求
\Application::setRoutes(array(
	// 设定一个类的完整限定名，自动实例化这个类相应请求
	'/\/test/' => 'App\Handler\TestHandler',
	)
);
\Event::on('test1', function($data){echo $data;}); // 用闭包会响应这个事件，并且接收参数。

\Event::trigger('test1', 'Test Param'); 触发这个事件。这个时候框架会自动调用已经注册的观察者去处理这个事件。并把参数传奇过去。
\Application::start(); //运行应用
```

## 错误与异常处理
Smile 内置了错误和异常处理。如果在运行的过程中遇到了错误，框架会把错误转化成`ErrorException`错误异常并抛出。交由异常处理，做统一处理。  

如果错误是`E_NOTICE`级别的。框架在开始调试模式的时候(即`define('DEBUG', true);`)的时候。会输出提示信息。并不会终止执行。当关闭调试模式的时候，框架并不会对这个级别的错误输出信息。

如果在运行的过程中遇到了其他级别错误或未被捕获的异常，框架或终止终止执行。并输出信息。开启调试模式的时候，会输出友好的调试信息，便于调试。如：

```
Fatal error: Uncaught exception 'ErrorException' with message strpos() expects at least 2 parameters, 0 given
Stack trace:
#0 [internal function]: Application::{closure}(2, 'strpos() expect...', 'D:\\git\\code\\php...', 11, Array)
#1 D:\git\code\php\Handler\IndexHandler.php(11): strpos()
#2 [internal function]: App\Handler\Indexhandler->get('')
#3 D:\git\code\php\smile.php(243): ReflectionMethod->invokeArgs(Object(App\Handler\Indexhandler), Array)
#4 D:\git\code\php\smile.php(121): ClassAgent::getAndInvoke(Object(ReflectionClass), 'get', Array, 'any')
#5 D:\git\code\php\smile.php(87): Application::dispatcher()
#6 D:\git\code\php\index.php(5): Application::start()
#7 {main}
thrown in D:\git\code\php\Handler\IndexHandler.php on line 11
```

如果没有开启调试模式。框架会终止运行。并输出错误提示信息。`We encountered some problems, please try again later.`。即常量`DEFAULT_ERROR_MESSAGE`，如果要修改默认的提示信息。只需要自定义这个常量即可,如

```
<?php

define('DEFAULT_ERROR_MESSAGE', '网站暂时遇到一些问题'); // 遇到错误时默认提示信息
include 'smile.php'; // 引入框架。

......

\Application::start(); //运行应用
```
无论是否开启调试模式，框架在遇到错误或者未捕获的异常的时候，都会写入一条错误日志。会记录当前用户 IP，错误级别。以及错误堆栈信息。方便排查和处理错误或异常。

## 日志
Smile 提供了便捷的日志功能。可以在代码里方便的记录日志，方便调试和发现问题。  
日志文件会按每天一个生成，日志文件目录在常量 `LOG_PATH` 中定义。默认是`{APP_PATH}/Log/`目录。可以在引入框架文件前自定义这个常量，来修改日志文件的路径。

```
<?php

define('LOG_PATH', '/var/log/site/'); // 修改日志文件路径。
include 'smile.php'; // 引入框架。

......

\Application::start(); //运行应用

```

### 日志使用
Smile 的日志默认分为 `ERROR`,`WARNING`,`NOTICE`,`INFO`,`DEBUG` 五种日志级别，方便对日志进行筛选和分类。其中 `DEBUG` 级别的日志只有在调试模式下才会被写入到文件。  
为了方便调用的，框架已经封装了，这五种级别操作。`\Log::error()` 对应 `ERROR` 级别错误，`\Log::info()` 对应`INFO` 级别错误。

```
<?php

define('DEBUG', true); // 开启调试模式。
include 'smile.php'; // 引入框架。
\Log::error('Error Logs'); // 记录一条 error 级别的错误
\Log::warning('Warning Logs'); // 记录一条 warning 级别的错误
\Log::notice('Notice Logs'); // 记录一条 notice 级别的错误
\Log::info('Info Logs'); // 记录一条 info 级别的错误
\Log::debug('Debug Logs'); // 记录一条 debug 级别的错误

```

如果以上五中日志级别不能满足使用，框架有封装`\Log::write()` 方法，这个方法有两个参数，第一个参数是日志的内容。第二个参数是日志的分类或者级别标识。`\Log::error()`方法其实就是`\Log::write($msg, 'ERROE')`这样去实现的。要添加一个日志级别，在第二个参数里添加即可。

```
<?php

define('DEBUG', true); // 开启调试模式。
include 'smile.php'; // 引入框架。
\Log::error('Error Logs'); // 记录一条 error 级别的错误
\Log::write('Error Logs', 'ERROE'); // 和\Log::error('Error Logs');的作用是一样的。
\Log::write('Error Logs', ''); // 和\Log::error('Error Logs');的作用是一样的。
\Log::write('Admin login','Admin'); // 添加一个新的日志，日志类型为 Admin

```
### 日志内容
日志会按天生成一个文件，会自动在当天的日志文件里追歼日志。每条日志是一行。格式类似  

```
[2015-12-09 09:46:01][127.0.0.1][DEBUG] debug message
[2015-12-09 09:46:51][127.0.0.1][ERROR] error message
```

日志的每一条都是由`[写入时间][客户端 IP ][日志级别或分类] 日志内容` 四个元素组成。可以按照这个规则去解析或者批处理日志文件。

## 多语言

如果你的产品是面向国际用户的。Smile 也提供了多语言的支持。当使用不同语言的用户访问的时候，框架会自动选择适合的语言给用用户。只需要你给对应的语言提供翻译包就可。  
在 http 协议中，每种语言都有自己的代号，表示自己是哪种语言。比如`zh-CN`代表中文，`en-US`代表英文。可以通过`Request::getAcceptLang()` 方法查看用户使用的语言。只需要在配置好多语言包，框架会自动帮你做这些工作。  

### 目录结构
语言包都在常量`LANG_PATH` 目录下，默认是`{APP_PATH}/Lang`目录。可以在引入框架文件前，定义`LANG_PATH` 这个常量来改变语言包的路径。  
语言包的名字必须和语言代码完全一致，大小写敏感。目录结构如

```
── Lang/
    ├── zh-CN.php
    ├── zh-TW.php
    └── en-US.php
```
### 文件内容格式
语言包文件内容必须是 return 一个数组给 include 如：

```
<?php
return array(
	'hello'=>'你好'
);
```

这个数组索引 (key) 是个字符串，就是语言的标记。如：在 `zh-CN.php` 中 `'hello' => '你好'`。在 `en-US.php` 中可以是 `'hello'=>'Hello'`，这样在输出的时候框架会自动选择语言包里 `hello` 标识对应的输出。
如果，要输出的内容不确定，需要根据情况发生变化。可以在设置的时候做好占位符。在输出的时候框架会用变量替换掉这个占位符如：

```
<?php
return array(
	'hello.man' => '你好，name'
);
```
在使用的时候这样调用

```
<?php

......
echo \Lang::get('hello.man', array('name' => '张三'));
......
```

这样打印出来的内容就是`你好，张三`。如果设置了占位符，在调用的时候没有给复制。那么就会直接输出占位符，如：`你好，name`。  
这个占位符可以是任何字符串标识，可以自行选择适当的字符串做占位符，可以充当默认输出。

### 使用多语言

框架封装了两个方法`\Lang::get()`，`\Lang::say()`。第一个方法的作用是把翻译后的内容返回给调用者，并不会主动输出。`\Lang::say()` 不会返回信息给调用者。会把翻译后的内容直接输出。相当于`echo \Lang::get('hello.man')`；这样在开发过程中可以根据情况，使用两个方法。两个方法的调用和参数是一致的。  

两个方法都有两个参数。第一个参数是要调用的标识，即语言包数组里面的索引(key)，第二个参数是可选参数，如果想把索引对应的值提替换掉。只需要传第一个数组，且这个数组的索引就是要替换掉的关键字，数组的值是用来替换的字符。支持同时替换多个关键字。

```
<?php

......
echo \Lang::get('hello.man', array('name' => '张三', 'face' => '^_^'));
......
```

## 建议反馈
