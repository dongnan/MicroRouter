# MicroRouter - 一个PHP路由

MicroRouter 是一个简单的PHP路由，方便进行 `RESTful` WEB应用和接口开发，同时支持cli方式执行。

# 环境要求

- PHP >= 5.4

# 安装

## composer 安装
MicroRouter 可以通过 `composer` 安装，使用以下命令从 `composer` 下载安装 MicroRouter

``` bash
$ composer require dongnan/microrouter
```
## 手动下载安装
### 下载地址
- 在 `Git@OSC` 下载 http://git.oschina.net/dongnan/MicroRouter/tags
- 在 `GitHub` 下载 https://github.com/dongnan/MicroRouter/releases

### 安装方法
在你的入口文件中引入
```
<?php 
	//引入 MicroRouter 的自动加载文件
	include("path_to_linkcache/autoload.php");
```

# 如何使用

### *"Hello World"*

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$router = new \MicroRouter\Router();

$router->respond('GET', '/hello-world', function () {
    return 'Hello World!';
});

$router->dispatch();
```
### 响应所有`request_method`
```php
<?php
$router->respond('/hello-world', function () {
    return 'Hello World!';
});
```

### 参数命名
```php
<?php
$router->respond('/[:name]', function ($params) {
    return 'Hello ' . $params['name'];
});
```

### RESTful 
```php
<?php
$router->respond('GET', '/users', $callback);
$router->respond('POST', '/users', $callback);
$router->respond('PUT', '/users/[i:id]', $callback);
$router->respond('DELETE', '/users/[i:id]', $callback);
//匹配多个请求
$router->respond(array('GET','POST'), '/path', $callback);
```

# server配置
### nginx
在虚拟主机的配置的server内添加以下配置
```nginx
location / {
  try_files $uri $uri/ /index.php?$args;
}
```

# LICENSE

使用非常灵活宽松的 [New BSD License](http://opensource.org/licenses/BSD-3-Clause) 协议

