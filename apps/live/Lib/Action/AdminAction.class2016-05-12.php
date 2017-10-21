<?php
/**
 * 后台直播管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAction extends AdministratorAction
{
	
	protected $config = array();
	/**
	 * 初始化，
	 */
	public function _initialize() {
		$this->pageTitle['config'] = '直播配置';
		$this->pageTitle['index']  = '直播间列表';
		$this->pageTitle['create'] = '创建直播间';
		$this->pageTitle['update'] = '修改直播间';
		
		$this->pageTab[] = array('title'=>'直播间列表','tabHash'=>'index','url'=>U('live/Admin/index'));
		$this->pageTab[] = array('title'=>'创建直播间','tabHash'=>'create','url'=>U('live/Admin/create'));
		$this->pageTab[] = array('title'=>'直播配置','tabHash'=>'config','url'=>U('live/Admin/config'));
		
		$this->config =  model('Xdata')->get('live_Admin:config');
		parent::_initialize();
	}
	
	//直播配置
	public function config(){
		$_REQUEST['tabHash'] = 'config';
		$this->pageKeyList = array (
				'uname',
				'password',
				'api_url',
		);
		$this->displayConfig ();
	}
	
	//直播间列表（带分页）
	public function index(){
		$_REQUEST['tabHash'] = 'index';
		$this->pageKeyList = array('id','name','templateTypeTxt','barrage','DOACTION');
		$a = $this->config['api_url'];
		$this->displayList($list);
	}
	
	//创建直播间
	public function create(){
		if( isset($_POST) ) {
			$url    = $this->config['api_url'].'training/room/created';
			$data = $_POST;
			unset($data['systemdata_list']);
			unset($data['systemdata_key']);
			unset($data['pageTitle']);
			$data['loginName'] = $this->config['uname'];
			$data['password']  = $this->config['password'];
			
			$res = request_post($url , $data);
			dump($data);
			dump($res);exit;
			if($res['result'] == 'OK') {
				$this->assign( 'jumpUrl', U('live/Admin/index') );
				$this->success('创建成功');
			} else {
				$this->error('创建失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'create';
			$this->pageKeyList   = array('subject','teacherToken','studentToken','studentClientToken',
					'startDate','invalidDate','assistantToken','speakerInfo','scheduleInfo','webJoin',
					'clientJoin','description','duration','uiMode','uiColor','scene','uiWindow',
					'uiVideo','upgrade','sec','realtime','maxAttendees');
			$this->opt['webJoin']        = array('true'=>'是','false'=>'否');
			$this->opt['clientJoin']     = array('true'=>'是','false'=>'否');
			$this->opt['uiMode']     = array('1'=>'三分屏','2'=>'文档/视频为主','3'=>'两分屏','4'=>'互动增加');
			$this->opt['scene']     = array('0'=>'大讲堂','1'=>'小班课');
			
			
			$this->opt['foreignpublish'] = array('1'=>'开启','0'=>'不开启');
			$this->opt['authtype']       = array('0'=>'接口验证','1'=>'密码验证','2'=>'免密码验证');
			$this->savePostUrl = U('live/Admin/create');
			$this->displayConfig();
		}
		
	}
	
	//编辑直播间
	public function update(){
		if( isset($_POST) ) {
			$url    = C('API_URL').'room/update?';
			$param  = 'assistantpass='.t($_POST['assistantPass']).'&authtype='.t($_POST['authType']).'&barrage='.t($_POST['barrage']).'&checkurl='.t($_POST['checkUrl']).'&desc='.$_POST['desc'].'&name='.t($_POST['name']).'&playpass='.t($_POST['playPass']).'&publisherpass='.t($_POST['publisherPass']).'&roomid='.t($_POST['id']).'&userid='.C('USER_ID');
			$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
			$url    = $url.$param.'&time='.time().'&hash='.$hash;
			$res    = $this->getDataByUrl($url);
			if($res['result'] == 'OK') {
				$this->assign( 'jumpUrl', U('live/Admin/index') );
				$this->success('修改成功');
			} else {
				$this->error('修改失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'create';
			$this->pageKeyList = array('id','name','authType','publisherPass','assistantPass','playPass','checkUrl','barrage','desc');
			$this->opt['barrage']        = array('1'=>'开启','0'=>'不开启');
			$this->opt['foreignPublish'] = array('1'=>'开启','0'=>'不开启');
			$this->opt['authType']       = array('0'=>'接口验证','1'=>'密码验证','2'=>'免密码验证');
			
			$roomid = t($_REQUEST['roomid']);
			$list   = $this->roomInfo($roomid);
			$this->savePostUrl = U('live/Admin/update');
			$this->displayConfig($list['room']);
		}
	}
	
	//关闭直播间
	public function close(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'room/close?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$res = $this->getDataByUrl($url);
		if($res['result'] == 'OK') {
			$this->success('关闭成功');
		} else {
			$this->error('关闭失败');
		}
	}
	
	//直播间信息
	private function roomInfo($roomid){
		$url    = C('API_URL').'room/search?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		return $this->getDataByUrl($url);
	}
	
	//直播列表信息（带分页）
	public function info(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'live/info?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list   = $this->getDataByUrl($url);
		$this->assign('list',$list);
		$this->assign('type','info');
		$this->display('list');
	}
	
	//直播间连接数统计
	public function connections(){
	
	}
	
	//获取直播间代码
	public function getCode(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'room/code?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list   = $this->getDataByUrl($url);
		$this->assign('list',$list);
		$this->assign('type','code');
		$this->display('list');
	}
	
	//获取直播间内用户登录、退出行为统计
	public function useraction(){
	
	}
	
	//直播间模板信息
	public function templateInfo(){
		$url    = C('API_URL').'viewtemplate/info?';
		$param  = 'userid='.C('USER_ID');;
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list = $this->getDataByUrl($url);
		dump($list);exit;
	}
	
	//根据url读取文本
	private function getDataByUrl($url , $type = true){
		return json_decode(file_get_contents($url) , $type);
	}
}