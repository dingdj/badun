<?php

class weixin{
	private $_sina_akey;
	private $_sina_skey;

	public function __construct() {
		$this->_sina_akey = WX_APP_ID;
		$this->_sina_skey = WX_APP_SECRET;
	}

	public function landed(){
        //-------配置
        $callback  =  'http://demo.51eduline.com/smessage.html'; //回调地址

        //微信登录
        session_start();
        //-------生成唯一随机串防CSRF攻击
        $state  = md5(uniqid(rand(), TRUE));
        $_SESSION["wx_state"]    =   $state; //存到SESSION
        $callback = urlencode($callback);
        $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->WX_APP_ID."&redirect_uri=".$callback.
                 "&response_type=code&scope=snsapi_login&state=".$state."#wechat_redirect";

        header("Location: $wxurl");
    }

	public function smessage(){
		if($_GET['state']!=$_SESSION["wx_state"]){
			$this->assign('jumpUrl', U('classroom/Index/index'));;
			$this->error("获取用户信息失败");
		}

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->WX_APP_ID.'&secret='.$this->WX_APP_SECRET.
			   '&code='.$_GET['code'].'&grant_type=authorization_code';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$json =  curl_exec($ch);
		curl_close($ch);

		$arr = json_decode($json,1);

		//得到 access_token 与 openid
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$arr['access_token'].'&openid='.
			   $arr['openid'].'&lang=zh_CN';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$json =  curl_exec($ch);
		curl_close($ch);
        //得到 用户资料
        $user_info = json_decode($json,1);
//        //缺失access_token参数
//        if($user_info['errcode'] == 41001 || !$user_info){
//            $this->assign('jumpUrl', U('classroom/Index/index'));;
//            $this->error("获取用户信息失败");
//        }
        $uid = M('login')->where(array('oauth_token'=>$user_info['unionid'],'oauth_token_secret'=>$user_info['openid']))->getField('uid');
        if($uid){
            $login = M('user')->where(array('uid'=>$uid))->getField('login');
            $login = model('Passport')->loginLocalWithoutPassword($login);
            if($login){
                $this->assign('jumpUrl', U('classroom/Index/index'));;
                $this->success("同步登录成功");
            }
        }else{
            $this->assign($user_info);
            $this->display();
        }
	}

    public function authentication_wx(){
        $callback  =  'http://demo.51eduline.com/weChatCertified'; //回调地址

        //微信登录
        session_start();
        //-------生成唯一随机串防CSRF攻击
        $state  = md5(uniqid(rand(), TRUE));
        $_SESSION["wx_state"]    =   $state; //存到SESSION
        $callback = urlencode($callback);
        $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->WX_APP_ID."&redirect_uri=".$callback.
            "&response_type=code&scope=snsapi_login&state=".$state."#wechat_redirect";

        header("Location: $wxurl");
    }

    public function weChatCertified(){
        if($_GET['state']!=$_SESSION["wx_state"]){
            $this->assign('jumpUrl', U('classroom/Index/index'));
            $this->error("获取用户信息失败");
        }

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->WX_APP_ID.'&secret='.$this->WX_APP_SECRET.
            '&code='.$_GET['code'].'&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json =  curl_exec($ch);
        curl_close($ch);

        $arr = json_decode($json,1);

        //得到 access_token 与 openid
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$arr['access_token'].'&openid='.
            $arr['openid'].'&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json =  curl_exec($ch);
        curl_close($ch);
        //得到 用户资料
        $user_info = json_decode($json,1);

        $is_login = M('login')->where(array('uid'=>$this->mid , 'type'=>'weixin'))->find();
        if( !$is_login ) {
            $data['uid']  = $this->mid;
            $data['type'] = "weixin";
            $data['type_uid'] = $user_info['openid'];
            $data['oauth_token'] = $user_info['unionid'];
            $data['oauth_token_secret'] = $user_info['openid'];
            $res = M('login')->add($data);
            if ($res) {
                $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 4)));
                $this->success("绑定成功");
            } else {
                $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 4)));
                $this->error("绑定失败，请重试");
            }
        }else{
            $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 4)));
            $this->error("已绑定");
        }
    }
    
}