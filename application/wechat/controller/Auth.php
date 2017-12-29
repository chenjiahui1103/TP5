<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/29
 * Time: 15:03
 */

namespace app\wechat\controller;

use app\wechat\common\service\UserService;
class Auth extends Base
{
    public function __construct()
    {
        parent::__construct();
        if(empty($this->wechatInfo)){
            $this->redirect('wechat/base/auth',['redirect'=>base64_encode($this->url)]);
        }

        if(empty($this->user)){
            $this->user = UserService::getUserInfo($this->wechatInfo['openid']);
        }

        if(empty($this->user)){
            $this->redirect('user/account/bind',['redirect'=>base64_encode($this->url)]);
        }
    }
}