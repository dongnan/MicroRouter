<?php

/**
 * MicroRouter - 一个小型的PHP路由
 *
 * @author      Dong Nan <hidongnan@gmail.com>
 * @copyright   (c) Dong Nan http://idongnan.cn All rights reserved.
 * @link        http://git.oschina.net/dongnan/MicroRouter
 * @license     BSD (http://opensource.org/licenses/BSD-3-Clause)
 */

namespace MicroRouter;

/**
 * Router
 */
class Router {

    /** 默认配置 */
    private $conf = [
        //多域名部署
        'MULTI_DOMAIN_DEPLOY' => false,
        //多域名部署规则
        'MULTI_DOMAIN_RULES' => [],
        'DEFAULT_MODULE' => 'home',
        //获取当前请求地址的系统变量 默认为REQUEST_URI
        'URL_REQUEST_URI' => 'REQUEST_URI',
        //URL伪静态后缀设置
        'URL_HTML_SUFFIX' => 'html',
        //默认的AJAX提交变量
        'PARAMS_AJAX_SUBMIT' => 'ajax',
    ];
    private $match_types = [
        'i' => '[0-9]+',
        'a' => '[0-9A-Za-z]+',
        'h' => '[0-9A-Fa-f]+',
        's' => '[0-9A-Za-z-_]+',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/]+?'
    ];
    private $segments = [];
    private $route_rules = [];
    private $path_prefix = '';
    private $is_win = null;
    private $is_cli = null;
    private $is_cgi = null;
    private $http_host = '';
    private $request_uri = '';
    private $request_path = '';
    private $query_string = '';
    private $request_method = '';
    private $is_ajax = null;
    private $is_get = null;
    private $is_post = null;
    private $is_put = null;
    private $is_delete = null;

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct($config = array()) {
        if (!empty($config)) {
            $this->conf = array_merge($this->conf, $config);
        }
        //初始化
        $this->init();
    }

    /** 初始化 */
    private function init() {
        $this->is_win = strstr(PHP_OS, 'WIN') ? true : false;
        $this->is_cli = PHP_SAPI == 'cli' ? true : false;
        $this->is_cgi = (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? true : false;
        if (!$this->is_cli) {
            $this->http_host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
            $this->request_uri = isset($_REQUEST['REQUEST_URI']) ? $_REQUEST['REQUEST_URI'] : '';
            $pos = strpos('?', $this->request_uri);
            $this->request_path = $pos === false ? $this->request_uri : substr($this->request_uri, 0, $pos);
            $this->query_string = isset($_REQUEST['QUERY_STRING']) ? $_REQUEST['QUERY_STRING'] : '';
            $this->request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
            $this->is_ajax = ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[$this->conf['PARAMS_AJAX_SUBMIT']]) || !empty($_GET[$this->conf['PARAMS_AJAX_SUBMIT']])) ? true : false;
            $this->is_get = $this->request_method === 'GET' ? true : false;
            $this->is_post = $this->request_method === 'POST' ? true : false;
            $this->is_put = $this->request_method === 'PUT' ? true : false;
            $this->is_delete = $this->request_method === 'DELETE' ? true : false;
        } else {
            $opt = getopt('r:', [$this->conf['URL_REQUEST_URI'] . ':']);
            $this->request_uri = !empty($opt['r']) ? $opt['r'] : (!empty($opt[$this->conf['URL_REQUEST_URI']]) ? $opt[$this->conf['URL_REQUEST_URI']] : '');
            $pos = strpos('?', $this->request_uri);
            $this->request_path = $pos === false ? $this->request_uri : substr($this->request_uri, 0, $pos);
            $this->query_string = $pos === false ? '' : substr($this->request_uri, $pos + 1);
        }
    }

    public function is_win() {
        return $this->is_win;
    }

    public function is_cli() {
        return $this->is_cli;
    }

    public function is_cgi() {
        return $this->is_cgi;
    }

    public function is_ajax() {
        return $this->is_ajax;
    }

    public function is_get() {
        return $this->is_get;
    }

    public function is_post() {
        return $this->is_post;
    }

    public function is_put() {
        return $this->is_put;
    }

    public function is_delete() {
        return $this->is_delete;
    }

    public function respond($path, $callable) {
        $method = '*';
        if (func_num_args() === 3) {
            $method = func_get_arg(0);
            $path = func_get_arg(1);
            $callable = func_get_arg(2);
        }
        $url = $this->path_prefix . $path;

        $match_types = $this->match_types;
        $url = preg_replace_callback('`(?:\[([^:\]]*)(?::([^:\]]*))?\])`', function ($match) use ($match_types) {
            list(, $type, $param) = $match;
            if (isset($match_types[$type])) {
                $type = $match_types[$type];
            }
            $pattern = '(' . ($param !== '' ? "?P<$param>" : null) . $type . ')';
            return $pattern;
        }, $url);
        $regex = "`^{$url}$`";

        if (!isset($this->route_rules[$regex])) {
            $this->route_rules[$regex] = [];
        }
        $this->route_rules[$regex][$method] = $callable;
        return $this;
    }

    public function domain($domain, $callback) {
        if (strtolower($domain) == $this->http_host) {
            if (is_string($callback)) {
                $callback($this);
            } else {
                call_user_func($callback, $this);
            }
        }
        return $this;
    }

    public function with($path, $callback) {
        $tmp_path = $this->path_prefix;
        $this->path_prefix .= $path;
        if (is_string($callback)) {
            $callback($this);
        } else {
            call_user_func($callback, $this);
        }
        $this->path_prefix = $tmp_path;
        return $this;
    }

    public function dispatch() {
        $params = array();
        $matched_count = 0;
        foreach ($this->route_rules as $regex => $rules) {
            $match = preg_match($regex, $this->request_uri, $params);
            if ($match) {
                foreach ($rules as $method => $callback) {
                    if ($method === '*') {
                        call_user_func($callback, $params);
                        $matched_count++;
                    } elseif (strcasecmp($method, $this->request_method)) {
                        call_user_func($callback, $params);
                        $matched_count++;
                    }
                }
            }
        }
        if (!$matched_count) {
            //TODO
        }
    }

}
