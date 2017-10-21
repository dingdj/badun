<?php
/**
 * 登录注册等公共模块控制器
 * @version GJW2.0
 */
class PassportAction extends Action
{
	private $passport;
	private $_user_model;
	private $_register_model;			// 注册模型字段
	private $_config;
	//初始化
	public function _initialize() { 
    	$this->passport = model('Passport');
    	$this->_user_model = model('User');
    	$this->_config = model('Xdata')->get('admin_Config:register');
    	$this->_register_model = model('Register');
	}
	
	//登录
	public function login() {
		$this->display();
	}
	/**
	 * 用户登录
	 * @return void
	 */
	public function doLogin() {
		$login 		= addslashes($_POST['login_email']);
		$password 	= trim($_POST['login_password']);
		$result= $this->passport->loginLocal($login,$password);
		if(!$result){
			echo $this->passport->getError();
            exit;
		}else{
			echo 1;
            exit;
		}
	}	
	public function logout(){
		$this->passport->logoutLocal();
		$this->display("login");
	}
	//注册
	public function reg() {
		$this->display();
	}
	/**
     * 异步注册
     */
    public function ajaxReg(){
    	$login=$_POST["login"];
		$password = trim($_POST["password"]);
		$verify=intval($_POST["verify"]);
		$type= 1 == $_POST['type'] ? 1 : 2;
		$sex = 1;
        if($type==1){
        	if(!$this->_register_model->isValidEmail($login)) {
        		echo $this->_register_model->getLastError();
        		exit();
        	}
        	$map['login']=$login;
        	$map['email']=$login;
        }else{
        	if(!preg_match("/^[1][358]\d{9}$/",$login)) {
        		echo ("手机号格式错误！");
        		exit();
        	}
        	if($login!=$_SESSION['getverphone'] || $verify!=$_SESSION['phoneverify']){
        		echo ("对不起，验证码错误，请重试！");
        		exit();
        	}
        	$map['phone']=$login;
        	$map['login']=$login;
        }
		if($password=="" ||strlen($password)<6 || strlen($password)>20){
			echo ("对不起，密码长度不正确");
			exit();
		}
		$login_salt = rand(11111, 99999);
		$map['sex'] = $sex;
		$map['login_salt'] = $login_salt;
		$map['password'] = md5(md5($password).$login_salt);
		$map['reg_ip'] = get_client_ip();
		$map['ctime'] = time();
		// 审核状态： 0-需要审核；1-通过审核
		$map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;
		// 需求添加 - 若后台没有填写邮件配置，将直接过滤掉激活操作
		$isActive = $this->_config['need_active'] ? 0 : 1;
		if ($isActive == 0) {
			$emailConf = model('Xdata')->get('admin_Config:email');
			if (empty($emailConf['email_host']) || empty($emailConf['email_account']) || empty($emailConf['email_password'])) {
				$isActive = 1;
			}
		}
		$map['is_active'] = $isActive;
		$uid = $this->_user_model->add($map);
		if($uid) {
			// 添加积分
			model('Credit')->setUserCredit($uid,'init_default');
			// 添加至默认的用户组
			$userGroup = model('Xdata')->get('admin_Config:register');
			$userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
			model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));
            $data['oauth_token']         = getOAuthToken($uid);//添加app认证
            $data['oauth_token_secret']  = getOAuthTokenSecret();
            $data['uid']                 = $uid;
            $savedata = $data;
            D('')->table(C('DB_PREFIX').'ZyLoginsync')->add($savedata);
			//判断是否需要审核
			        if($type==2){
						$email=$phone;
					}
					D('Passport')->loginLocal($email,$password);
					echo $uid;
					exit();


		} else {
			//echo L('PUBLIC_REGISTER_FAIL');			// 注册失败
			echo $this->getLastSql();
			exit();
		}
    }
    //手机注册下一步
    public function clickPhoneVer(){
    	$phone=$_POST['phone'];
    	$verify=intval($_POST['verify']);
    	$verphone=$_SESSION['getverphone'];//取得获取验证码的手机
    	$verifys=$_SESSION['phoneverify'];//取得验证码
    	if($phone!=$verphone || $verify!=$verifys){
    		echo "对不起，验证码错误，请重试！";
    		exit();
    	}else{
    		echo 1;
    		exit();
    	}
    }
	//重置密码-验证手机号
	public function repwd() {
		$this->display();
	}
	/**
     * 获取验证码
     */
    public function getVerify(){
    	$verifytime=$_SESSION['verifytime'];
    	$nowtime=time();
    	if($nowtime<$verifytime){
    		$xctime=$verifytime-$nowtime;
    		echo ("请".$xctime."秒后重新获取！");
    		exit();
    	}
    	$phone="18483681051";
    	$res=M('user')->where(array('phone'=>$phone))->find();
    	if ($res) {
    		echo('此手机号已被注册,请更换！');
    		exit();
    	}
    	$rnum = rand(100000,999900);
    	$sendres=model('Sms')->send($phone,$rnum);
    	if($sendres){
    		//将验证码存入session
    		$_SESSION['phoneverify']=$rnum;
    		//将号码存入session
    		$_SESSION['getverphone']=$phone;
    		$nowtime+=60;
    		$_SESSION['verifytime']=$nowtime;
    		
    		//验证码入库
    		$map['phone'] = $phone;
    		$map['code']  = $rnum;
    		$map['stime'] = time();
    		M('ResphoneCode')->add($map);
    		echo("发送成功，请注意查收！");
    		exit();
    	}else{
    		echo model('Sms')->getError();
    		exit();
    	}
    	
    }
	//重置密码-新密码
	public function reset() {
		$this->display();
	}
	/**
	 * 忘记密码   手机获取验证码 
	 */
	public function getVrifi(){
		$time = time();
		$phoneCode = session('phone_code'); //保存在session中的验证码
		$phone = $_POST['phone'];
		if(!$phone){
			 echo'手机号不能为空';exit();
		}
		if(!preg_match('/^1[3458]\d{9}$/', $phone)){
			echo '请输入正确的手机号码';
			exit();
		}
		if($_SESSION['repwdtime'] > $time){
			echo '请勿频繁获取手机验证码'; exit();	
		}
		//根据用户电话查询用户信息
		$user = model('User')->where(array('phone'=>$phone))->find();
		if(is_null($user) || empty($user)){
			echo '手机未绑定';exit();	
		}
		$phoneCode[$phone]['send_time'] = $time + 90;
        $_SESSION['repwdtime']=$phoneCode[$phone]['send_time'];
		$code = rand(100000,999999);//手机验证码
		$phoneCode[$phone]['code'] = md5($code);
		if(model('Sms')->send($phone,$code)){
			session('phone_code',$phoneCode); //相关信息保存session  时间的控制
			echo 1;
			exit();
		}else{
			echo model('Sms')->getError();
			exit();
		}
	}
	/**
	 * 修改密码操作
	 *   
	 */
	public function repwdhandle(){
		$phone = $_POST['phone'];
		$pwd = trim($_POST['pwd']);
		$repwd = trim($_POST['repwd']);
		$code = md5($_POST['code']);
		if(empty($phone)){
			echo "手机号不能为空";
			exit();
		}
		$phoneCodes = session('phone_code');
		//常规检查用户信息
		if(!empty($phone)){
			if(!preg_match('/^1[3458]\d{9}$/', $phone)){
				echo '异常操作';
				exit();
			}
		}
		//检查用户信息
		if(!isset($phoneCodes[$phone]) || empty($phoneCodes[$phone])){
			echo '请先获取验证码';
			exit();
		}
		$phoneCode = $phoneCodes[$phone];
		if($code !== $phoneCode['code']){
			$phoneCode['err'] += 1;
			if($phoneCode['err'] >= 4){
				$phoneCodes[$phone] = null;
				session('phone_code',$phoneCodes);
				echo '请重新获取短信验证码';
				exit();
			}else{
				$phoneCodes[$phone] = $phoneCode;
				session('phone_code', $phoneCodes);
				echo '验证码错误，你还可以尝试'.(4-$phoneCode['err']).'次';
				exit();
			}
		}
		if(!model('Register')->isValidPassword($pwd, $repwd)){
			echo model('Register')->getLastError();
			exit();
		}
		$uid = model("User")->where(array('phone'=>$phone))->getField('uid');
		if(empty($phone) || $uid<0){
			echo '异常操作';
			exit();
		}
		$salt = rand(10000,99999);
		$password = md5(md5( $pwd).$salt);
		$res= model('User')->where(array('phone'=>$phone))->save(array(
				'password'=>$password,
				'login_salt'=>$salt,
		));
		if($res !== false){
			//清楚用户的缓存
			model('User')->cleancache($uid);
			echo $res;
			exit();
		}else{
			echo "密码更改失败！";
			exit();
		}
	}
	/**
     *验证手机是否唯一
     * 异步方法
     */
    public function clickPhone(){
    	$phone=$_POST['phone'];
    	
    	$res=M('user')->where(array('phone'=>$phone))->find();
    	if ($res) {
    		echo 0;
    		exit;
    	}else{
    		echo 1;
    		exit;
    	}
    }
		
	
}