<?php

define('VERSION', '0.1.0'); // 框架版本号
defined('DEBUG') or define('DEBUG', false); // 开发模式
defined('APP_PATH') or define('APP_PATH', dirname(__FILE__) . '/'); // 应用根目录
defined('CLASS_PREFIX') or define('CLASS_PREFIX', 'App\\'); // 命名空间前缀
defined('LOG_PATH') or define('LOG_PATH', APP_PATH . 'Log/'); // 日志生成目录
defined('LANG_PATH') or define('LANG_PATH', APP_PATH . 'Lang/'); // 语言包目录
defined('TPL_PATH') or define('TPL_PATH', APP_PATH . 'TPL/'); // 模版文件目录
defined('DEFAULT_LANG') or define('DEFAULT_LANG', 'zh-CN'); // 默认使用语言
defined('DEFAULT_ERROR_MESSAGE') or define('DEFAULT_ERROR_MESSAGE', 'We encountered some problems, please try again later.'); // 遇到错误时默认提示信息

/**
 * 应用池 ,保存和每个应用（Handler）
 */
class Application
{
    public static $Routes = array(); //记录 Handler 和 url 映射关系

    /**
     * 一些框架初始化操作
     */
    public static function init()
    {
        ob_start();
        //注册第一个自动加载函数
        spl_autoload_register(__NAMESPACE__ . '\ClassAgent::includeClass');
        // 所有错误跑出error 异常 统一处理
        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            if ($errno == E_NOTICE)
            {
                if (DEBUG)
                {
                    echo $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
                }
                return true;
            }
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        // 默认异常处理
        set_exception_handler(function (Exception $exception)
        {
            $msg = '<b>Fatal error</b>:  Uncaught exception \'' . get_class($exception) . '\' with message <b>';
            $msg .= $exception->getMessage() . '</b><br />';
            $msg .= 'Stack trace:<br><code>' . str_replace("\n", '<br />', $exception->getTraceAsString());
            $msg .= '</code><br />thrown in <b>' . $exception->getFile() . '</b> on line <b>' . $exception->getLine() . '</b>';
            Log::error($msg);
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal server error");
            ob_clean();
            DEBUG ? die($msg) : die(DEFAULT_ERROR_MESSAGE);
        });
    }

    /**
     * 传入 URl 和 Handler 对应关系
     * @param array $routes URl和 Handler 对应关系
     */
    public static function setRoutes($routes)
    {
        self::$Routes = $routes;
    }

    /**
     * 清空所有的handler
     * @param bool $really 最后确认一下需要清空
     */
    public static function clearRoutes($really)
    {
        if ($really)
        {
            self::$Routes = array();
        }
    }

    /**
     * 运行调度处理
     */
    public static function start()
    {
        //定义URL常量
        define('PATH_INFO', filter_input(INPUT_SERVER, 'PATH_INFO') ? filter_input(INPUT_SERVER, 'PATH_INFO') : '/');
        //获取此次请求的类型，如post，get，put，delete，update等
        define('REQUEST_METHOD', strtolower(filter_input(INPUT_SERVER, 'REQUEST_METHOD')));
        //匹配Handler，进行处理
        $result = self::dispatcher();
        //执行收尾操作
        self::finish($result);
    }

    /**
     *  执行一些收尾处理
     * @param bool $is404 是否是404 状态
     */
    public static function finish($is404)
    {
        $is404 or header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $time = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4);
        $mem = memory_get_peak_usage(true) / 1024;
        $mem = $mem > 1024 ? ($mem / 1024 . 'MB') : $mem . 'KB';
        ob_end_flush();
    }

    /**
     * 调度相应的handler负责处理，只能有一个Handler被调用
     * @return bool 是否找到 url Route
     */
    public static function dispatcher()
    {
        //查找URL相匹配的
        foreach (Application::$Routes as $url => $handler)
        {
            if (preg_match($url, PATH_INFO, $params))
            {
                array_shift($params); // 去除数组第一项
                $handlerRef = ClassAgent::getReflection($handler);
                //执行前置操作 如果方法不存在，不会自动执行的 (需要检测是不是闭包)
                !($handlerRef instanceof ReflectionClass) or ClassAgent::getAndInvoke($handlerRef, 'before', $params);
                //执行请求方法，或者默认any方法
                ClassAgent::getAndInvoke($handlerRef, REQUEST_METHOD, $params, 'any');
                //执行后置操作（检测是不是闭包）
                !($handlerRef instanceof ReflectionClass) or ClassAgent::getAndInvoke($handlerRef, 'finish', $params);
                return true; //成功匹配后终止循环
            }
        }
        return false; // 返回 false 表示没有找到匹配的模式 404
    }
}

/**
 * 类代理 代为实例化，和执行操作
 */
class ClassAgent
{
    /**
     * 获取一个对象的代理
     * @param object|string|closure $object
     * @return \ReflectionClass|\ReflectionFunction|\ReflectionObject
     */
    public static function getReflection($object)
    {
        if ($object instanceof Closure)
        {
            //判断是不是闭包对象
            $reflection = self::callFunction($object);
        }
        elseif (is_object($object))
        {
            //如果是个对象就不用去实例化了
            $reflection = self::callObject($object);
        }
        else
        {
            //一律当作字符串处理
            $reflection = self::callClass($object);
        }
        return $reflection;
    }

    /**
     * 当是对象的时候
     * @param object $object 对象
     * @return \ReflectionObject
     */
    public static function callObject($object)
    {
        return new ReflectionObject($object);
    }

    /**
     * 当是闭包的时候。
     * @param closure $function 闭包对象
     * @return \ReflectionFunction
     */
    public static function callFunction($function)
    {
        return new ReflectionFunction($function);
    }

    /**
     * 当使用class名获取反射的时候
     * @param string $className 类的名字
     * @return \ReflectionClass|false
     */
    public static function callClass($className)
    {
        //如果没有加载进来类，会尝试使用自动加载。
        if (!class_exists($className))
        {
            return false;
        }
        return new ReflectionClass($className);
    }

    /**
     * 获取类方法的反射对象，方法不存在的时候返回null
     * @param ReflectionClass|ReflectionFunction $reflection 类反射的对象
     * @param string $method 需要获取的方法名字
     * @param string|null $default 如果指定方法不存在，就获取默认的方法名字
     * @return ReflectionMethod|ReflectionFunction|null
     */
    public static function getMethod($reflection, $method, $default = null)
    {
        if ($reflection instanceof ReflectionFunctionAbstract)
        {
            //如果已经是一个方法或者函数的反射，直接返回
            return $reflection;
        }
        if ($default && !$reflection->hasMethod($method))
        {
            $method = $default;
        }
        if ($reflection->hasMethod($method))
        {
            return $reflection->getMethod($method);
        }
        else
        {
            return null;
        }
    }

    /**
     * 自动获取并执行一个方法，如果是闭包对象，就会直接执行
     * 方法不存在的时候返回null 执行失败返回false，成功返回true
     * @param ReflectionClass|ReflectionFunction $reflection 类反射的对象
     * @param string $method 需要获取的方法名字
     * @param array $params 需要传入的参数
     * @param string|null $default 如果指定方法不存在，就获取默认的方法名字
     * @return null|bool
     */
    public static function getAndInvoke($reflection, $method = null, $params = array(), $default = null)
    {
        $method = self::getMethod($reflection, $method, $default);
        if (is_null($method))
        {
            return $method;
        }
        if ($reflection instanceof ReflectionClass)
        {
            //检查是不是一个对象的反射
            $method->invokeArgs($reflection->newInstance(), $params);
        }
        else
        {
            //闭包，直接执行
            $method->invokeArgs($params);
        }
        return true;
    }

    /**
     * 导入类文件
     * @param string $class 类的名字
     */
    public static function includeClass($class)
    {
        $len = strlen(CLASS_PREFIX);
        if (strncmp(CLASS_PREFIX, $class, $len) !== 0)
        {
            return;
        }
        $relative_class = substr($class, $len);
        $file = APP_PATH . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file))
        {
            require $file;
        }
    }
}

/**
 * Cookie 操作
 */
class Cookie
{
    /**
     * 设置一个cookie
     * @param string $key cookie 名字
     * @param string|int|array|object $value cookie值
     * @param int $expire cookie 的生命周期，单位是秒
     * @param string $path cookie 路径
     * @param null $domain 允许使用的 域名
     */
    public static function set($key, $value, $expire = 0, $path = null, $domain = null)
    {
        $expire = $expire ? time() + $expire : 0;
        setcookie($key, $value, $expire, $path, $domain);
    }

    /**
     * 获取一个Cookie
     * @param string $key cookie 名字
     * @return null|string|int|array|object cookie的值
     */
    public static function get($key)
    {
        if (isset($_COOKIE[$key]))
        {
            return filter_input(INPUT_COOKIE, $key);
        }
        return null;
    }

    /**
     * 删除一个Cookie
     * @param string $key cookie 名字
     * @return bool
     */
    public static function delete($key)
    {
        self::set($key, '', -3600);
        return true;
    }

    /**
     * 清理所有Cookie
     */
    public static function clear()
    {
        $cookies = filter_input_array(INPUT_COOKIE);
        foreach ($cookies as $key => $one)
        {
            self::set($key, '', -3600);
        }
        return false;
    }
}

/**
 * 数据库操作驱动 , PDO方式
 */
class DB
{
    /**
     * @var null|PDO 数据库连接
     */
    private $connection = null;

    /**
     * 构造函数，自动去连接数据库
	 * @param string $dsn 数据源名称
	 * @param string $user 数据库用户名
	 * @param string $password 用户密码
	 * @param array $options 数据库连接设置项。
     */
    public function __construct($dsn, $user = '', $password = '', $options = array())
    {
        $this->connection = new PDO($dsn, $user, $password, $options);
	}
	
    /**
     * 获取多行结果
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @return array 结果集
     */
    public function query($sql, $params = array(), $options = array())
    {
        $sth = $this->execute($sql, $params, $options);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取一行结果
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @return array 结果集
     */
    public function get($sql, $params = array(), $options = array())
    {
        $sth = $this->execute($sql, $params, $options);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新数据
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @return array 受影响行数
     */
    public function update($sql, $params = array(), $options = array())
    {
        $sth = $this->execute($sql, $params, $options);
        return $sth->rowCount();
    }

    /**
     * 添加数据
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @param string $name 应该返回ID的那个序列对象的名称(为了兼容性)
     * @return array 插入的最后一个ID
     */
    public function insert($sql, $params = array(), $options = array(), $name = null)
    {
        $this->execute($sql, $params, $options);
        return $this->connection->lastInsertId($name);
    }

    /**
     * 删除数据
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @return array 受影响行数
     */
    public function delete($sql, $params = array(), $options = array())
    {
        return $this->update($sql, $params, $options);
    }

    /**
     * 负责执行所有的SQL语句
     * @param string $sql SQL 语句，支持预编译
     * @param array $params 预编译参数
     * @param array $options sql 设置选项
     * @return PDOStatement 返回一个 PDOStatement 对象
     */
    public function execute($sql, $params = array(), $options = array())
    {
        $sth = $this->connection->prepare($sql, $options);
        $sth->execute($params);
        return $sth;
    }
}

/**
 * 事件广播中心
 */
class Event
{
    /**
     * @var array 记录事件和回调
     * 结构：
     * [事件名字][callback]=array(
     *      0=>array(
     *          'times'=>int, 回调的执行次数
     *          'callback'=>'doMyEvent', 回调
     *          'done'=>int,已经执行的次数
     *      ),
     * )
     * [事件名字][happen] = int 事件已经发生的次数
     * [事件名字][…] 其他属性
     *
     */
    public static $event = array();

    /**
     * 绑定一个事件
     * @param string $event 事件的名称（类型）
     * @param object|string|Closure $callback 处理事件的函数或者对象回调
     * @param int $times 时间执行的次数
     * @return bool 是否绑定成功
     */
    public static function on($event, $callback, $times = 0)
    {
        if (!isset(self::$event[$event]))
        {
            //处理首次绑定事件
            self::$event[$event]['callback'] = array();
            self::$event[$event]['happen'] = 0;
        }
        $temp = array('times' => $times, 'callback' => $callback, 'done' => 0,);
        self::$event[$event]['callback'][] = $temp;
        return true;
    }

    /**
     * 绑定只处理一次的事件
     * @param string $event 事件的名称（类型）
     * @param object|string|Closure $callback 处理事件的函数或者对象回调
     * @return bool 是否绑定成功
     */
    public static function one($event, $callback)
    {
        return self::on($event, $callback, 1);
    }

    /**
     * 解除绑定
     * 解除事件所有回调，使用**
     * @param string $event 事件名字
     * @param object|string|Closure $callback 需要取消的事件回调 所有事件，请使用**
     * @return bool 是否解除成功
     */
    public static function off($event, $callback)
    {
        if (isset(self::$event[$event]))
        {
            if ($callback == '**')
            {
                unset(self::$event[$event]);
                return true;
            }
            foreach (self::$event[$event]['callback'] as $k => $temp)
            {
                if ($callback == $temp['callback'])
                {
                    unset(self::$event[$event]['callback'][$k]);
                }
            }
        }
        return true;
    }

    /**
     * 触发一个事件
     * @param string $event 事件名字
     * @param array $params 处理这个事件的时候传给回调的参数
     */
    public static function trigger($event, $params = array())
    {
        if (isset(self::$event[$event]))
        {
            //发生次数+1
            self::$event[$event]['happen']++;
            foreach (self::$event[$event]['callback'] as $k => $callback)
            {
                //次数限制
                if ($callback['times'] && $callback['done'] >= $callback['times'])
                {
                    continue;
                }
                $callbackRef = ClassAgent::getReflection($callback['callback']);
                //answer 方法
                ClassAgent::getAndInvoke($callbackRef, 'answer', $params);
                //已经执行次数+1
                self::$event[$event]['callback'][$k]['done']++;
            }
        }
    }
}

/**
 * 日志模块
 */
class Log
{

    /**
     * 写入日志
     * @param string $message 日志内容
     * @param int $level 日志级别
     */
    public static function write($message, $level)
    {
        if (strtoupper($level) != 'DEBUG' || DEBUG)
        {
            $message = '[' . $level . ']' . $message;
            $message = '[' . Request::getClientIP() . ']' . $message;
            $message = '[' . date('Y-m-d H:i:s') . ']' . $message . "\n";
			file_exists(LOG_PATH) or mkdir(LOG_PATH);
            file_put_contents(LOG_PATH . date('Y-m-d') . '.log', $message, FILE_APPEND);
        }
    }

    /**
     * 写入日志 ERROR
     * @param string $message 日志内容
     */
    public static function error($message)
    {
        self::write($message, 'ERROR');
    }

    /**
     * 写入日志 WARNING
     * @param string $message 日志内容
     */
    public static function warning($message)
    {
        self::write($message, 'WARNING');
    }

    /**
     * 写入日志 Notice
     * @param string $message 日志内容
     */
    public static function notice($message)
    {
        self::write($message, 'NOTICE');
    }

    /**
     * 写入日志 INFO
     * @param string $message 日志内容
     */
    public static function info($message)
    {
        self::write($message, 'INFO');
    }

    /**
     * 写入日志 DEBUG
     * @param string $message 日志内容
     */
    public static function debug($message)
    {
        self::write($message, 'DEBUG');
    }
}

/**
 * 多语言处理
 */
class Lang
{
    /**
     * 应用语言包
     */
    public static $APPLang = false;

    /**
     * 自动获取多语言处理
     * @param string $message 要输出的内容标记
     * @param null|array $content 要替换的内容
     * @return mixed 替换后的内容
     */
    public static function get($message, $content = null)
    {
        if (self::$APPLang === false)
        {
            self::initLang();
        }
        $message = isset(self::$APPLang[$message]) ? self::$APPLang[$message] : $message;
        if ($content)
        {
            return str_replace(array_keys($content), $content, $message);
        }
        else
        {
            return $message;
        }
    }

    /**
     * 自动获取多语言处理，并直接输出
     * @param string $message 要输出的内容标记
     * @param null|array $content 要替换的内容
     */
    public static function say($message, $content = null)
    {
        echo self::get($message, $content);
    }

    /**
     *初始化语言包
     */
    public static function initLang()
    {
        $langFilePath = LANG_PATH . Request::getAcceptLang() . '.php';
        if (file_exists($langFilePath))
        {
            self::$APPLang = include $langFilePath;
        }
        elseif (file_exists(LANG_PATH . DEFAULT_LANG . '.php'))
        {
            self::$APPLang = include LANG_PATH . DEFAULT_LANG . '.php';
        }
        else
        {
            self::$APPLang = array();
        }
    }
}

/**
 * 处理，过滤请求信息
 */
class Request
{
    /**
     * 只取get传递的值
     * @param string $name 获取参数的名字
     * @param mixed $default 获取不到参数的默认参数
     * @param int $filter 参数过滤器
     * @return mixed 传递过来的值
     */
    public static function get($name, $default = null, $filter = FILTER_DEFAULT)
    {
        if (isset($_GET[$name]))
        {
            $value = is_array($_GET[$name]) ? filter_input_array(INPUT_GET, $name, $filter) : filter_input(INPUT_GET, $name, $filter);
            return $value;
        }
        else
        {
            return $default;
        }
    }

    /**
     * 只取post的值
     * @param string $name 获取参数的名字
     * @param mixed $default 获取不到参数的默认参数
     * @param int $filter 参数过滤器
     * @return mixed 传递过来的值
     */
    public static function post($name, $default = null, $filter = FILTER_DEFAULT)
    {
        if (isset($_POST[$name]))
        {
            $value = is_array($_POST[$name]) ? filter_input_array(INPUT_POST, $name, $filter) : filter_input(INPUT_POST, $name, $filter);
            return $value;
        }
        else
        {
            return $default;
        }
    }

    /**
     * 优先获取post的值，然后是get
     * @param string $name 获取参数的名字
     * @param mixed $default 获取不到参数的默认参数
     * @param int $filter 参数过滤器
     * @return mixed 传递过来的值
     */
    public static function param($name, $default = null, $filter = FILTER_DEFAULT)
    {
        if (isset($_POST[$name]))
        {
            return self::post($name, $default, $filter);
        }
        elseif (isset($_GET[$name]))
        {
            return self::get($name, $default, $filter);
        }
        else
        {
            return $default;
        }
    }

    /**
     * 判断是不是Ajax请求
     * @param string $param 如果传递过来的这个参数 则表示是Ajax请求
     * @return bool 是否是Ajax 请求
     */
    public static function isAjax($param = null)
    {
        $value = filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') ? ('xmlhttprequest' == strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'))) : false;
        $value = $value ? $value : self::param($param);
        return (bool)$value;
    }

    /**
     * 获取客户端IP
     */
    public static function getClientIP()
    {
        static $ip = null;
        if (null !== $ip)
        {
            return $ip;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
            {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 接收语言
     * @return string 语言标记
     */
    public static function getAcceptLang()
    {
        $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) : array(DEFAULT_LANG);
        return trim(reset($lang));
    }

    /**
     * 返回的文件类型。
     * @param bool $all 返回一项还是多项
     * @return string|array 语言标记
     */
    public static function getAccept($all = false)
    {
        $accept = explode(',', $_SERVER['HTTP_ACCEPT']);
        foreach ($accept as $key => $one)
        {
            $one = explode(';', trim($one));
            $accept[$key] = reset($one);
        }
        return $all ? $accept : reset($accept);
    }
}

/**
 * 模版类
 */
class TPL
{
    /**
     * 变量寄存处
     */
    public static $params = array();

    /**
     * 解析 html 模版，
     * @param string $file 模版名字
     * @param array $params 模版里面可以使用的变量
     */
    public static function render($file, $params = null)
    {
        $filePath = TPL_PATH . $file;
        if ($params)
        {
            self::$params = array_merge(self::$params, $params);
        }
        extract(self::$params, EXTR_OVERWRITE);
        include $filePath;
    }

    /**
     * 输出信息到页面，如果是数组或者对象自动转化为 json 字符串
     * @param mixed $message 信息
     */
    public static function write($message)
    {
        if (is_array($message) || is_object($message))
        {
            echo json_encode($message);
        }
        else
        {
            echo $message;
        }
    }

    /**
     * 变量保护，指定一个模版可以使用的变量
     * @param string $key 变量相等名字
     * @param mixed $value 模版里面可以使用的变量
     */
    public static function assign($key, $value)
    {
        self::$params[$key] = $value;
    }
}

Application::init(); //初始化项目
