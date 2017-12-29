<?php
/**
 * @author: Axios
 *
 * @email: axioscros@aliyun.com
 * @blog:  http://hanxv.cn
 * @datetime: 2017/5/25 10:01
 */
namespace app\wechat\controller;

use think\Controller;
use think\Env;
use think\Session;
use Wechat\WechatOauth;

class Base extends Controller{
    protected $Oauth;

    protected $url;

    protected $param;

    protected $config;

    protected $redirect;

    protected $wechatInfo ;

    protected $user ;


    public function __construct()
    {
        parent::__construct();

        $this->param = $_REQUEST;

        $this->config = [
            'cachepath'=>RUNTIME_PATH."log_wechat",
            'appid'=>Env::get('wechat.appid',''),
            'appsecret'=>Env::get('wechat.appsecret',''),
            'encodingaeskey'=>Env::get('wechat.encodingaeskey',''),
            'token'=>Env::get('wechat.token','')
        ];
        $this->Oauth = new WechatOauth($this->config);

        $this->url = $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

        $this->redirect = isset($this->param['redirect']) ? base64_decode($this->param['redirect']) : 'index/index/index';

        $this->wechatInfo = Session::get('wechat_info');

        $this->user = Session::get('user_info');


    }

}