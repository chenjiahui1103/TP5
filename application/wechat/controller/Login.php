<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/29
 * Time: 14:51
 */

namespace app\wechat\controller;
use think\console\command\make\Controller;
use think\Env;
use think\Session;
use Wechat\WechatOauth;
class Login extends Controller
{
    private $wechat='';
    public function __construct()
    {
        parent::__construct();
        $config=[
            'cachepath'=>RUNTIME_PATH."log_wechat",
            'appid'=>Env::get('wechat.appid',''),
            'appsecret'=>Env::get('wechat.appsecret',''),
            'encodingaeskey'=>Env::get('wechat.encodingaeskey',''),
            'token'=>Env::get('wechat.token','')
        ];
        $this->wechat = new WechatOauth($config);
    }
    /**
    * 授权跳转
    * @param  mixed $items
    * @return static
    */
    public function auth()
    {
        //授权回调地址
        $url = url('wechat/Login/authCallback');
        //state
        if(Session('state'))Session::set('state',time());
        $state = Session::get('state');
        //授权内容
        $scope = 'snsapi_userinfo';
        return redirect($this->wechat->getOauthRedirect($url,$state,$scope));
    }
    /**
    * 获取用户信息
    * @param  mixed $items
    * @return static
    */
    public function authCallback()
    {
        //state 验证
        if(Session::get('state')==$_GET['state']){
            $result = $this->wechat->getOauthAccessToken();
            //获取用户信息
            $userInfo = $this->wechat->getOauthUserInfo($result['access_token'],$result['openid']);
            //保存用户信息
            Session('wechat_info',$userInfo);
            //数据库不存在,则插入
            if(0){

            }
            $this->assign('user',$userInfo);
            $this->fetch();
        }else{
            return 0;
        }

    }
}