<?php

/**
 * MicroRouter - 一个小型的PHP路由
 *
 * @author      Dong Nan <hidongnan@gmail.com>
 * @copyright   (c) Dong Nan http://idongnan.cn All rights reserved.
 * @link        http://git.oschina.net/dongnan/MicroRouter
 * @license     BSD (http://opensource.org/licenses/BSD-3-Clause)
 */

namespace microRouter;

/**
 * Route
 */
class Route {

    /** 默认配置 */
    private $conf = [
        //获取当前请求地址的系统变量 默认为REQUEST_URI
        'URL_REQUEST_URI' => 'REQUEST_URI',
        //URL伪静态后缀设置
        'URL_HTML_SUFFIX' => 'html',
        //默认的AJAX提交变量
        'PARAMS_AJAX_SUBMIT' => 'ajax',
    ];

    /** 路由规则 */
    private $rules = [];
    private $is_win = null;
    private $is_cli = null;
    private $is_cgi = null;
    private $http_host = '';
    private $request_uri = '';
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
            $this->http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $this->request_uri = isset($_REQUEST['REQUEST_URI']) ? $_REQUEST['REQUEST_URI'] : '';
            $this->request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
            $this->is_ajax = ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[$this->conf['PARAMS_AJAX_SUBMIT']]) || !empty($_GET[$this->conf['PARAMS_AJAX_SUBMIT']])) ? true : false;
            $this->is_get = $this->request_method === 'GET' ? true : false;
            $this->is_post = $this->request_method === 'POST' ? true : false;
            $this->is_put = $this->request_method === 'PUT' ? true : false;
            $this->is_delete = $this->request_method === 'DELETE' ? true : false;
        } else {
            $opt = getopt('r:', [$this->conf['URL_REQUEST_URI'] . ':']);
            $this->request_uri = !empty($opt['r']) ? $opt['r'] : (!empty($opt[$this->conf['URL_REQUEST_URI']]) ? $opt[$this->conf['URL_REQUEST_URI']] : '');
        }
    }

    /**
     * 添加路由规则
     * @param array $rules
     */
    public function addRules($rules) {
        foreach ($rules as $rule => $rval) {
            if (!isset($this->rules[$rule])) {
                $this->rules[$rule] = [];
            }
            $this->rules[$rule][] = $rval;
        }
    }

    public function response($request_method, $rule, $callback) {
        if (!isset($this->rules[$rule])) {
            $this->rules[$rule] = [];
        }
        $this->rules[$rule][] = ['request_method' => $request_method, 'callback' => $callback];
    }

    public function get($rule, $callback) {
        $this->response('GET', $rule, $callback);
    }

    public function post($rule, $callback) {
        $this->response('POST', $rule, $callback);
    }

    public function put($rule, $callback) {
        $this->response('PUT', $rule, $callback);
    }

    public function delete($rule, $callback) {
        $this->response('DELETE', $rule, $callback);
    }

    public function dispatch() {
        
    }

}
