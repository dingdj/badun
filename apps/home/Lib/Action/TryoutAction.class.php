<?php
/**
 * 应用管理控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class TryoutAction extends CommonAction {

	/**
	 * 初始化控制器
	 */
	public function _initialize() {
		parent::_initialize();
	}
	
	public function index() {
		$this->display();
	}
	
	public function add(){
		$phone  = $_POST['phone'];
		$verify = intval($_POST['verify']);
		$verphone = $_SESSION['phone'];//取得获取验证码的手机
		$verifys  = $_SESSION['verify'];//取得验证码
		
		$result = M('zy_tryout')->where(array('phone'=>$phone))->find();
		if ($result) {
			$this->mzError('你已经申请过了，请耐心等候');
		}
		
		if( $phone != $verphone || $verify != $verifys ){
			$this->mzError("对不起，验证码不正确");
		}else{
			$data = $_POST;
			$data['ctime'] = time();
			$res = M('zy_tryout')->add($data);
			
			if( $res ) {
				$data['email'] = 'liwei@seition.com';
				$data['title'] = $data['phone'] . '用户申请试用';
				$data['body']  = $data['company'] . '申请免费试用，手机号为：'.$data['phone'];
				model('Notify')->sendEmail($data);
				$this->mzSuccess('申请成功，我们将在24小时内为您开通');
			} else {
				$this->mzError('申请失败');
			}
			
		}
	}
	
	/**
	 * 获取验证码
	 */
	public function getVerify(){
		$verifytime=$_SESSION['verifytime'];
		$nowtime=time();
		if($nowtime < $verifytime){
			$xctime = $verifytime-$nowtime;
			$this->mzError("请".$xctime."秒后重新获取！");
			exit();
		}
		$phone=$_POST['phone'];
		$res = M('zy_tryout')->where(array('phone'=>$phone))->find();
		if ($res) {
			$this->mzError('你已经申请过了，请耐心等候');
		}
		$rnum = rand(100000,999999);
		$sendres = model('Sms')->send($phone,$rnum);
		if($sendres){
			//将验证码存入session
			$_SESSION['verify'] = $rnum;
			//将号码存入session
			$_SESSION['phone'] = $phone;
			$nowtime+=60;
			$_SESSION['verifytime'] = $nowtime;
	
			//验证码入库
			$map['phone'] = $phone;
			$map['code']  = $rnum;
			$map['stime'] = time();
			M('ResphoneCode')->add($map);
			$this->mzSuccess("发送成功，请注意查收！");
		}else{
			$this->mzError(model('Sms')->getError());
		}
		 
	}



}