<?php
$menu = array(
    //后台头部TAB配置
	'admin_channel'	=>	array(
		'index'		=> '首页', //L('PUBLIC_SYSTEM'),
		'system'	=> L('PUBLIC_SYSTEM'),
		'statistics'=> '统计',
		'content'	=> '运营',//L('PUBLIC_CONTENT')
        'user'		=> L('PUBLIC_USER'),
		'classroom' => '课堂',
		'finance'   => '财务',
//		'exam'      => '考试系统',
//		'live'      => '直播系统',
		'mall'      => '商城',
		'school'    => '机构',
		//'apps'		=> L('PUBLIC_APPLICATION'),
//		'extends'	=> '扩展',//L('PUBLIC_EXPANSION'),
	),
	//后台菜单配置
	'admin_menu'	=> array(
		'index'	=> array(
			'首页'	=> array(
				'基本信息'	=>	'admin/Home/statistics',//L('PUBLIC_BASIC_INFORMATION')
				'缓存清理'	=>  'admin/Tool/cleancache',//L('PUBLIC_CLEANCACHE')
			)
		),
		'system'	=> array(
			'系统配置'	=>	array(//L('PUBLIC_SYSTEM_SETTING')
				'站点配置'      => 'admin/Config/site',//L('PUBLIC_WEBSITE_SETTING')
                '首页配置'      => 'admin/Config/setIndex',
                '注册配置'      => 'admin/Config/register',//L('PUBLIC_REGISTER_SETTING')
                '头部导航配置'  => 'admin/Config/nav',//L('PUBLIC_NAVIGATION_SETTING')
                '底部导航管理' 	=> 'admin/Single/index',
                '权限菜单配置'  => 'admin/Apps/setPermNode',
                '群发消息'      => 'admin/Home/message',
                '数据备份'      => 'admin/Tool/backup',
				//'邀请配置'    => 'admin/Config/invite',
				'短信接口配置'  => 'admin/Config/sms',
				'邮件配置'      => 'admin/Config/email',//L('PUBLIC_EMAIL_SETTING')
				'微信配置'      => 'admin/Config/weixin',//L('PUBLIC_EMAIL_SETTING')
				'附件配置'      => 'admin/Config/attach',//L('PUBLIC_FILE_SETTING')
                '附件管理'	    =>	'admin/Content/attach',//L('PUBLIC_FILE_MANAGEMENT')
                'APP下载地址'   => 'admin/Config/appConfig',
                'APP关于我们'   => 'admin/Application/about',
                '地区配置'      => 'admin/Config/area',
                '消息配置'      => 'admin/Config/notify',//L('PUBLIC_MAILTITLE_ADMIN')
                '缓存配置'      => 'admin/Home/cacheConfig',
                '过滤配置'      => 'admin/Config/audit',
                '管理日志'      => 'admin/Home/logs',
                '登录日志管理'  => 'admin/AdminLoginRecord/index',
                '评论开关管理'  => 'admin/Config/commentSwitch',
			),
            '支付管理' => array(
               // '银联配置'      =>  'admin/Config/unionpay',
                '支付宝支付配置'    =>  'admin/Config/alipay',
                '微信支付配置'  =>  'admin/Config/wxpay',
                '通用支付配置'  =>  'admin/Config/payConfig',
                '申请退款配置'  =>  'admin/Config/refundConfig',
            ),
            '应用及插件' => array(
                '已安装应用列表' => "admin/Apps/index",
                '未安装应用列表' => "admin/Apps/install",
                '广告位'        => "admin/Addons/admin?pluginid=3",
                '第三方登录'    => "admin/Addons/admin?pluginid=4",
            ),
            '其他配置'  => array(
                '存储配置'     	=> 'classroom/AdminConfig/index',
                '直播配置'      => 'live/AdminConfig/baseConfig',
                '分成配置' 		=> 'admin/Config/divideIntoConfig',
            ),
		),
        'statistics'=> array(
            "统计管理"	=>	array(
                '访问统计'	   =>	'admin/Home/visitorCount',//L('PUBLIC_VISIT_CALCULATION')
                '活跃度统计'  => 'admin/Home/studentActive',
                '学员信息统计'  => 'admin/Home/usersOrder',
                '订单统计'     => 'admin/Home/allOrder',
                '收益统计' => 'admin/Home/vipOrder',
				'学习记录统计' => 'classroom/AdminLearnRecord/index'
            ),
        ),

    	'user'	=>	array(
    		L('PUBLIC_USER')				=>	array(
    			'用户管理'        =>	'admin/User/index',//L('PUBLIC_USER_MANAGEMENT')
    			'用户组管理'      =>	'admin/UserGroup/index',//L('PUBLIC_USER_GROUP_MANAGEMENT')
    			'讲师认证'        => 'admin/User/verified',
                '讲师管理'        => 'classroom/AdminTeacher/index',
                //'用户学币管理' => 'classroom/AdminLearnc/index',
            ),
    	),
    	
    	'content'	=> array(
            '营销卡管理' => array(
                '卡券领取管理'  => 'classroom/AdminUserCard/index',
                '课程卡管理'    => 'classroom/AdminCourseCard/index',
                '优惠券管理'=> 'classroom/AdminVideoCoupon/index',
                '会员卡管理'    => 'classroom/AdminVipCard/index',
                '打折卡管理'    => 'classroom/AdminDiscount/index',
                //'充值卡管理'    => 'classroom/AdminRechargeCard/index',
                '实体卡管理'    => 'classroom/AdminEntityCard/index',
            ),
            '内容管理' => array(//L('PUBLIC_CONTENT_MANAGEMENT')
                '资讯管理'		=> 'admin/Topic/index',
                '小组管理'		=>	'group/Admin/index',
                //'咨询管理'      =>	'admin/Doubt/index',
                '公告管理'      =>	'admin/Notice/index',
                '私信管理'	    =>	'admin/Content/message',//L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT')
                '反馈管理'      =>	'admin/Suggest/index',
                '系统消息管理'  =>	'admin/SystemMessage/index',
//                '标签管理'		=>  'admin/Home/tag',//L('PUBLIC_TAG_MANAGEMENT')
                //'搜索关键字管理' =>  'admin/SearchKeywords/index',
                //'活动管理'		=>	'event/Admin/index',
				'验证码管理'    =>	'admin/Verify/index',
            ),
            '等级头衔管理' => array(
                '会员等级'       => 'classroom/AdminVip/index',
                //'机构等级'      => 'classroom/AdminSchoolVip/index',
                '讲师头衔'      => 'classroom/AdminTeacherVip/index',
            ),
//            '众筹'=> array(
//                '众筹申请管理'     => 'classroom/AdminCrow/index',
//                '众筹列表'     => 'classroom/AdminCrow/crowList',
//            ),
			'财务配置' => array(
				'积分规则配置'       => 'mall/AdminGlobalConfig/credit',
				'余额&积分配置' => 'admin/Config/rechargeIntoConfig',
				'会员模式管理' => 'admin/Config/vipPatternConfig',
			),
    	),

        'classroom' => array(
			'点播课管理' => array(
				'点播课管理'         => 'classroom/AdminVideo/index',
			),
			'直播课管理' => array(
				'直播间管理'         => 'live/AdminLive/index',
//                '平台剩余并发情况'   => 'live/AdminArrCourse/index',
//                '实际并发数'         => 'live/AdminActualCon/index',
//                '并发量管理'         =>	'live/AdminConcurrent/index',
//                '并发量折扣管理'     =>'live/AdminConPrice/index',
//                '排课截止时间管理'   => 'live/AdminLivetime/index',
//					'直播间管理2' => 'live/Admin/index',
			),
			'班级管理' => array(
				'班级管理'           => 'classroom/AdminAlbum/index',
			),
            '线下课管理' => array(
                '线下课管理'         => 'classroom/AdminLineClass/index',
            ),
            '机构挂载管理' => array(
                '点播挂载管理'  => 'classroom/AdminVideoMount/index',
                '直播挂载管理' => 'live/AdminLiveMount/index',
                '班级挂载管理'  => 'classroom/AdminAlbumMount/index',
			),
			/**
            '考试管理' => array(
                '题库管理'          => 'exam/AdminQuestion/index',
                '试卷管理'          => 'exam/AdminPaper/index',
                '考试管理'          => 'exam/AdminExam/index',
                '用户考试记录'      => 'exam/AdminUserExam/index',
                '考试分类配置'   => 'exam/AdminCategory/index',
            ),**/
			'考试管理' => array(
				'分类管理' => 'exams/AdminCategory/subject',
				'考点管理' => 'exams/AdminPoint/index',
				'试题管理' => 'exams/AdminQuestion/index',
				'试卷管理' => 'exams/AdminPaper/index',
				'成绩管理' => 'exams/AdminExamsUser/index'
			),
            '内容管理' => array(
                '问答管理'          =>'classroom/AdminWenda/index',
                '笔记管理'          => 'classroom/AdminNote/index',
                '提问管理'          => 'classroom/AdminQuestion/index',
                '点评管理'          => 'classroom/AdminReview/index',
                '文库管理'          => 'classroom/AdminLibrary/index',
//	 			'课程卡管理'         => 'classroom/AdminVideoCard/index',
                '分类配置'          => 'classroom/AdminClassroomCategory/index',
            ),
        ),

        'finance'=>array(
            '订单与账户' => array(
                '订单管理'         => 'classroom/AdminOrder/index',
                '申请退款管理'     =>  'classroom/AdminApplirefund/index',
                '提现申请'         => 'classroom/AdminWithdraw/index',
                '卡号列表'         => 'classroom/AdminCard/index',
                '支付记录'     => 'classroom/AdminRecharge/index',
            ),
            '财务明细管理' => array(
                '余额管理' => 'classroom/AdminLearnc/index',
                '分成管理'     => 'classroom/AdminUserSplit/index',
                '分成明细'     => 'classroom/AdminSplit/splitVideo',
                '积分管理'    => 'mall/AdminGlobal/index',
            ),
        ),

        'school' => array(
            '机构' => array(
                '机构管理'        => 'school/AdminSchool/index',
                '独立域名管理'	  => 'school/AdminDomaiName/index',
                //'视频空间管理' 	  => 'school/AdminVideoSpace/index',
                '独立财务账号管理' => 'school/AdminFinance/index',
            ),
            '内容管理' => array(
                '点播课管理'        => 'school/AdminVideo/index',
                '直播课管理'      => 'school/AdminLive/index',
                '班级课管理'        => 'school/AdminAlbum/index',
                '线下课管理'      => 'school/AdminLineClass/index',
            ),
            '用户'=>	array(
                '用户管理'        => 'school/AdminUser/index',
                //'用户组管理'      => 'school/AdminUserGroup/index',//L('PUBLIC_USER_GROUP_MANAGEMENT')
                '讲师管理'        => 'school/AdminTeacher/index',
            ),
            '数据统计' => array(
                '数据看板'        => 'school/AdminStatistics/showDataCount',
                '活跃度统计'    => 'school/AdminStatistics/studentActive',
                '收益统计'    => 'school/AdminStatistics/vipOrder',
                '订单统计'        => 'school/AdminStatistics/allOrder',
                '学习记录统计'    => 'school/AdminLearnRecord/index'
            ),
            '营销卡管理' => array(
				'卡券领取管理'  => 'school/AdminUserCard/index',
				'课程卡管理'    => 'school/AdminCourseCard/index',
				'优惠券管理'    => 'school/AdminVideoCoupon/index',
				'打折卡管理'    => 'school/AdminDiscount/index',
				//'充值卡管理'    => 'classroom/AdminRechargeCard/index',
				'实体卡管理'    => 'school/AdminEntityCard/index',
            ),
            '订单与账户' => array(
                '订单管理'    => 'school/AdminOrder/index',
//                '支出订单管理'    => 'school/AdminExpendOrder/index',
                '用户分成管理' => 'school/AdminSplit/splitVideo',
//                '用户购买课程收入分成管理' => 'school/AdminOSplit/splitVideo',
            ),
        ),

        'mall' => array(
            '商城管理' => array(
                '商品管理'       => 'mall/AdminGoods/index',
                '订单管理'       => 'mall/AdminGoodsOrder/index',
                '收货地址管理'       => 'mall/AdminGoodsAddress/index',
                //'商品评论管理' => 'mall/AdminGoodsComment/index',
                '商品分类配置'    => 'mall/AdminGoodsCate/index',
            ),
        ),
        

//    	'task'	=> array(
//			L('PUBLIC_TASK_INFO')			=> array(
//	 			L('PUBLIC_TASK_LIST')	=> 'admin/Task/index',
//	 			L('PUBLIC_TASK_REWARD') => 'admin/Task/reward',
//	 			'勋章列表'				=> 'admin/Medal/index',
//	 			'用户勋章'				=> 'admin/Medal/userMedal',
//				'任务配置'				=> 'admin/Task/taskConfig'
//	 		)
//	 	),
   	'apps'	=> array(
			L('PUBLIC_APP_MANAGEMENT')			=>	array(
	    		L('PUBLIC_INSTALLED_APPLIST')	=>	'admin/Apps/index',
	    		L('PUBLIC_UNINSTALLED_APPLIST')	=>	'admin/Apps/install',
	    	),
	 	),
//	    'extends'		=> array(
//	 		'插件管理' => array(
//
//    		),
//	 	),
    )
);

//$app_list = model('App')->getConfigList();
//foreach($app_list as $k=>$v){
//	$menu['admin_menu']['apps'][L('PUBLIC_APP_MANAGEMENT')][$k] = $v;
//}
//$plugin_list = model('Addon')->getAddonsAdminUrl();
//foreach($plugin_list as $k=>$v){
//	$menu['admin_menu']['extends']['插件管理'][$k] = $v;
//}

//防护云激活代码
/*
//1.如果防护云库文件存在，但是配置不存在，新注册key
if(!file_exists(DATA_PATH.'/iswaf/config.php') && file_exists(ADDON_PATH.'/library/iswaf/iswaf.php')){
	$dir   =  SITE_PATH.'/data/iswaf';
	function iswaf_create_key() {
	    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	    $hash = '';
	    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
	    $max = strlen($chars) - 1;
	    for($i = 0; $i < 128; $i++) {
	        $hash .= $chars[mt_rand(0, $max)];
	    }
	    return md5($hash.rand(1,3000).print_r($_SERVER,1));
	}
	// 目录不存在则创建
	if(!is_dir($dir))  mkdir($dir,0777,true);
	$iswafKey = iswaf_create_key(SITE_URL);
	$iswafConfig = array(
		'iswaf_database' => $dir.'/',
		'iswaf_connenct_key' => $iswafKey,
		'iswaf_status' => 1,
		'defences'=>array(
					'callback_xss'=>'On',
					'upload'=>'On',
					'inject'=>'On',	
					'filemode'=>'On',
					'webshell'=>'On',
					'server_args'=>'On',
					'webserver'=>'On',
					'hotfixs'=>'On',
					)
	);
	//注册ts站点
	$context = stream_context_create(array(
	'http'=>array(
	  'method' => "GET",
	  'timeout' => 10, //超时30秒
	  'user_agent'=>"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
	  )));
	$url = 'http://www.fanghuyun.com/api.php?do=tsreg&IDKey='.$iswafKey.'&url='.SITE_URL.'&ip='.get_client_ip();
	$res = file_get_contents($url, false, $context);
	//dump($res);exit;
	file_put_contents($dir.'/config.php',"<?php\nreturn ".var_export($iswafConfig,true).";\n?>");
	$menu['admin_menu']['index']['首页']['安全防护'] = 'http://www.fanghuyun.com/?do=simple&IDKey='.md5($iswafKey);
//2.如果防护云配置文件存在，但是没有关闭，启用防护云
}else if(defined('iswaf_status') && iswaf_status!=0){
	$context = stream_context_create(array(
	'http'=>array(
	  'method' => "GET",
	  'timeout' => 10, //超时30秒
	  'user_agent'=>"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
	  )));
	$res = file_get_contents('http://www.fanghuyun.com/api.php?IDKey='.iswaf_connenct_key.'&url='.SITE_URL.'&ip='.get_client_ip(), false, $context);
	//dump($res);exit;
	$menu['admin_menu']['index']['首页']['安全防护'] = 'http://www.fanghuyun.com/?do=simple&IDKey='.md5(iswaf_connenct_key);
}
*/
return $menu;