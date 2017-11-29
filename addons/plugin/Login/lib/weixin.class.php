<?php
/**
 * 微信第三方登陆
 */
class weixin
{
    private $wx_config;
    /**
     * 初始化方法
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-22
     */
    public function __construct()
    {
        $this->wx_config = model('Xdata')->lget('login');
    }
    /**
     * 获取登陆授权跳转地址
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-22
     * @param  string $callback [description]
     * @return [type] [description]
     */
    public function getUrl($callback = '')
    {
        $_SESSION['weixin']['wx_auth'] = null;
        //-------配置
        $AppID    = $this->wx_config['wx_app_id'];
        $callback = $callback ?: U('public/Widget/displayAddons', array('addon' => 'Login', 'hook' => 'no_register_display', 'type' => 'weixin'));

        //微信登录
        session_start();
        //-------生成唯一随机串防CSRF攻击
        $state                = md5(uniqid(rand(), true));
        $_SESSION["wx_state"] = $state; //存到SESSION
        $callback             = urlencode($callback);
        $wxurl                = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $AppID . "&redirect_uri=" . $callback . "&response_type=code&scope=snsapi_login&state=" . $state . "#wechat_redirect";
        return $wxurl;
    }

    /**
     * 验证微信用户授权
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-22
     * @return [type] [description]
     */
    public function checkUser()
    {
        $wx_auth = $_SESSION['weixin']['wx_auth'];
        // 如果没有授权信息，通过code兑换
        if (!$wx_auth && $_GET['code']) {
            $AppID     = $this->wx_config['wx_app_id'];
            $AppSecret = $this->wx_config['wx_app_secret'];

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $AppID . '&secret=' . $AppSecret .
                '&code=' . $_GET['code'] . '&grant_type=authorization_code';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            $wx_auth                        = json_decode($json, 1);
            $_SESSION['weixin']['wx_auth']  = $wx_auth;
            $_SESSION['open_platform_type'] = 'weixin';
        }
        return $wx_auth;
    }

    /**
     * 返回用户信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-22
     * @return [type] [description]
     */
    public function userInfo()
    {
        $me               = $this->getWxUserInfo();
        $user['id']       = $me['unionid'];
        $user['uname']    = $me['nickname'];
        $user['province'] = $me['province'];
        $user['city']     = $me['city'];
        $user['userface'] = $me['headimgurl'];
        $user['sex']      = ($me['sex'] == '1') ? 1 : 0;
        //$user['wx_user']  = $me;
        return $user;
    }
    /**
     * 获取微信用户信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-22
     * @return [type] [description]
     */
    private function getWxUserInfo()
    {
        $wx_auth = $this->checkUser();
        $user_info = session($wx_auth['openid']);
        if (!$user_info || $user_info['user_info_time'] <= time()) {
            //通过  access_token 与 openid 重新获取用户信息
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $wx_auth['access_token'] . '&openid=' .
                $wx_auth['openid'] . '&lang=zh_CN';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            //得到 用户资料
            $user_info                   = json_decode($json, 1);
            $user_info['user_info_time'] = time() + 1800;
            session($wx_auth['openid'], $user_info);
        }
        $_SESSION['weixin']['access_token']['oauth_token']        = $user_info['unionid'];
        $_SESSION['weixin']['access_token']['oauth_token_secret'] = $user_info['openid'];
        return $user_info;
    }

}
