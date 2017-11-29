<?php
/**
 * 云课堂设置项
 * 注意，请勿随意改动：配置项所在的Tab，Tab名称，配置名称，以免影响程序中现有配置调用
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminConfigAction extends AdministratorAction{

    protected $tabs = array(
        'basic' => array(
            'name'    => '基本设置',
            'keyList' => array(
            	'upload_room',  	 	  //上传空间
                'player_type',            //播放器类型
                //'master_uid',             //Eduline对应的用户，后台发布的课程或班级都属于当前设置的用户
                //'vip_discount',			  //vip折扣，取值范围 0.00~10，请勿乱填
                //'master_vip_discount',	  //Eduline产品vip折扣，取值范围 0.00~10，请勿乱填
                //'withdraw_basenum', 	  //提现的倍数，实际提现为该数的一倍及以上才会通过
                //'rechange_basenum', 	  //充值的倍数，实际充值为该数的一倍及以上才会通过，取值>=0.01
                //'rechange_default', 	  //充值默认金额
                //'vip_price',        	  //VIP月单价
                //'vip_year_price',   	  //包年VIP价格
                'video_free_time',  	  //课程免费观看时长
            ),
        ),
		
        //七牛云设置
        'qiniuyun' => array(
            'name'    => '七牛云存储配置',
            'keyList' => array(
                'qiniu_AccessKey',
                'qiniu_SecretKey',
            	'qiniu_Domain',
                'qiniu_Bucket',
            ),
        ),
        //CC设置
        'ccyun' => array(
            'name'    => 'CC存储配置',
            'keyList' => array(
                'cc_userid',
                'cc_apikey',
                'cc_apiurl',
                'cc_delid',
            ),
        ),
        /*
    	//阿里云设置
    	'aliyun' => array(
    			'name'    => '阿里云存储配置',
    			'keyList' => array(
    				'ali_AccessKey',
    				'ali_SecretKey',
    				'ali_Domain',
    				'ali_Bucket',
    			),
    	),
    		
    	//又拍云设置
    	'up' => array(
    			'name'    => '又拍云存储配置',
    			'keyList' => array(
    				'up_AccessKey',
    				'up_SecretKey',
    				'up_Domain',
    				'up_Bucket',
    			),
    	),*/
    );

    /**
     * 初始化选项卡和页面标题
     * @return void
     */
    public function _initialize(){

        //设置选项卡和页面标题
        foreach($this->tabs as $key => $val){
            //选项卡
            $this->pageTab[] = array(
                'title'   => $val['name'],
                'tabHash' => $key,
                'url'     => U('classroom/AdminConfig/'.$key)
            );
           // $this->opt['upload_room'] = array('0'=>'本地' , '1'=>'七牛' , '2'=>'阿里云' , '3'=>'又拍云');
			$this->opt['upload_room'] = array('0'=>'本地' , '1'=>'七牛','4'=>'CC' );
            $this->opt['player_type'] = array('cu'=>'播放器1' , 'ck'=>'播放器2' );
            //页面标题
            $this->pageTitle[$key] = '云课堂 - '.$val['name'];
        }

        parent::_initialize();
    }


    /**
     * 云课堂配置分类实现
     * @return void
     */
    public function _empty($method, $parms = null){
        if(!isset($this->tabs[$method])){
            $this->error('没有这个配置类！');
            getAppConfig();
        }

        if(isset($this->tabs[$method]['saveUrl'])){
            $this->savePostUrl = $this->tabs[$method]['saveUrl'];
        }

        $this->pageKeyList = $this->tabs[$method]['keyList'];

        $this->displayConfig();
    }


    /**
     * 云课堂基本设置调用，请勿修改
     * @return void
     */
    public function index(){
        //此处一定要是跳转，不能直接调用
        $this->redirect(APP_NAME.'/'.MODULE_NAME.'/basic');
    }

    public function ccyun(){
        //CC设置
        $this->pageKeyList = array (
            'cc_userid',
            'cc_apikey',
            'cc_apiurl',
            'cc_delid',
        );
        $this->savePostUrl = U('classroom/AdminConfig/saveCcyunConfig');
        $this->displayConfig ();
    }

    public function saveCcyunConfig(){
        $apiclient_cert = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api', 'cc' ,'spark_config.php'));
        //$spark_config数组是经过特定排序拼接，请勿随意修改
        $spark_config = "<?php\n".
            '   $spark_config'." = array(\n".
            "       'user_id' => "."'{$_POST['cc_userid']}'".",//CC视频用户id\n".
            "       'key' => "."'{$_POST['cc_apikey']}'".",//CC视频用户key\n".
            "       'charset' => 'utf-8',\n".
            "       'api_videos' => 'http://spark.bokecc.com/api/videos',//api获取多个视频信息接口\n".
            "       'api_user' => 'http://spark.bokecc.com/api/user',//api获取用户信息接口\n".
            "       'api_playcode' => 'http://spark.bokecc.com/api/video/playcode',//api获取视频播放代码接口\n".
            "       'api_deletevideo' => 'http://spark.bokecc.com/api/video/delete',//api删除视频接口\n".
            "       'api_editvideo' => 'http://spark.bokecc.com/api/video/update',//api编辑视频接口\n".
            "       'api_video' => 'http://spark.bokecc.com/api/video',//api获取单一视频接口\n".
            "       'api_category' => 'http://spark.bokecc.com/api/video/category',//api获取视频分类接口\n".
            "       'notify_url' => '".'http://' . $_SERVER['HTTP_HOST'] . '/api/cc/notify.php\',//验证地址'."\n".
            '   );';
        file_put_contents($apiclient_cert,$spark_config);
        $this->saveConfigData();
    }
}