<?php
/**
 * 后台直播管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminConfigAction extends AdministratorAction
{
	
	protected $config = array();
	/**
	 * 初始化，
	 */
	public function _initialize() {
		$this->pageTitle['baseConfig'] = '基础配置';
		$this->pageTitle['zshdConfig'] = '展示互动配置';
		$this->pageTitle['smConfig']   = '三芒配置';
		$this->pageTitle['ghConfig']   = '光慧配置';
		$this->pageTitle['ccConfig']   = 'CClive配置';
		$this->pageTitle['whConfig']   = '微吼配置';

		$this->pageTab[] = array('title'=>'基础配置','tabHash'=>'baseConfig','url'=>U('live/AdminConfig/baseConfig'));
		$config = model('Xdata')->get('live_AdminConfig:baseConfig');
		if($config['live_opt'] == 1){
			$this->pageTab[] = array('title'=>'展示互动配置','tabHash'=>'zshdConfig','url'=>U('live/AdminConfig/zshdConfig'));
		}else if($config['live_opt'] == 2) {
			$this->pageTab[] = array('title' => '三芒配置', 'tabHash' => 'smConfig', 'url' => U('live/AdminConfig/smConfig'));
		}else if($config['live_opt'] == 3) {
			$this->pageTab[] = array('title' => '光慧配置', 'tabHash' => 'ghConfig', 'url' => U('live/AdminConfig/ghConfig'));
		}else if($config['live_opt'] == 4) {
			$this->pageTab[] = array('title' => 'CClive配置', 'tabHash' => 'ccConfig', 'url' => U('live/AdminConfig/ccConfig'));
		}else if($config['live_opt'] == 5) {
            $this->pageTab[] = array('title' => '微吼配置', 'tabHash' => 'whConfig', 'url' => U('live/AdminConfig/whConfig'));
        }
		parent::_initialize();
	}
	
	//直播配置
	public function baseConfig(){
		$_REQUEST['tabHash'] = 'baseConfig';
		$this->pageKeyList = array (
				'live_opt',
		);
		$this->opt ['live_opt'] =  array (
				'1' => '展示互动',//展示互动
//				'2' => '三芒',//三芒
//				'3' => '光慧',//光慧
				'4' => 'CClive',//CClive
				'5' => '微吼直播',//微吼直播
		);
		$this->displayConfig ();
	}
	
	//展视互动配置
	public function zshdConfig(){
		$_REQUEST['tabHash'] = 'zshdConfig';
		$this->pageKeyList = array (
				'api_key',
				'api_pwd',
				'api_url',
		);
		$this->displayConfig ();
	}
	
	//三芒配置
	public function smConfig(){
		$_REQUEST['tabHash'] = 'smConfig';
		$this->pageKeyList = array (
				'uname',
				'password',
				'api_url',
				'video_url',
		);
		$this->displayConfig ();
	}
	
	//光慧配置
	public function ghConfig(){
		$_REQUEST['tabHash'] = 'ghConfig';
		$this->pageKeyList = array (
				'customer',
				'secretKey',
				'api_url',
				'video_url',
		);
		$this->displayConfig ();
	}

	//CCLive配置
	public function ccConfig(){
		$_REQUEST['tabHash'] = 'ccConfig';
		$this->pageKeyList = array (
				'user_id',
				'api_key',
				'api_url',
		);
		$this->displayConfig ();
	}

    //微吼配置
    public function whConfig(){
        $_REQUEST['tabHash'] = 'whConfig';
        $this->pageKeyList = array (
            'api_key',
            'secretKey',
            'appSecretKey',
            'api_url',
        );
        $this->displayConfig ();
    }

}