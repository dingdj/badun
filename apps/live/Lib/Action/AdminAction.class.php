<?php
/**
 * 后台直播管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAction extends AdministratorAction
{
	
	protected $base_config = array();
	protected $gh_config = array();
	protected $zshd_config = array();
	/**
	 * 初始化，
	 */
	public function _initialize() {
		$this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
		$this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
		$this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');

		if($this->base_config['live_opt'] == 1){//展示互动直播
			$this->pageTitle['index']      = '直播课堂列表';
//			$this->pageTitle['endZshd']    = '已结束直播课堂列表';
//			$this->pageTitle['closeZshd']  = '禁用直播课堂列表';
			$this->pageTitle['addZshd']    = '创建直播课堂';
//			$this->pageTitle['addLive']   = '修改直播课堂';
			$this->pageTitle['checkLive']  = '直播课堂信息';

			$this->pageTab[] = array('title'=>'实时直播课堂列表','tabHash'=>'index','url'=>U('live/Admin/index'));
//			$this->pageTab[] = array('title'=>'已结束直播课堂列表','tabHash'=>'end','url'=>U('live/Admin/endZshd'));
//			$this->pageTab[] = array('title'=>'禁用直播课堂列表','tabHash'=>'close','url'=>U('live/Admin/closeZshd'));
//			$this->pageTab[] = array('title'=>'创建直播课堂','tabHash'=>'addZshd','url'=>U('live/Admin/addZshd'));
		}else if($this->base_config['live_opt'] == 2){//三芒直播
			$this->pageTitle['index']  = '直播课堂管理';
			$this->pageTitle['addSm']  = '创建直播课堂';
			$this->pageTitle['editSm'] = '修改直播课堂';
			$this->pageTab[] = array('title'=>'直播2管理','tabHash'=>'sm','url'=>U('live/Admin/index'));
		}else if($this->base_config['live_opt'] == 3){//光慧直播
			$this->pageTitle['index']  = '直播课堂管理';
			$this->pageTitle['addGh']  = '创建直播课堂';
			$this->pageTitle['editGh'] = '修改直播课堂';
			$this->pageTab[] = array('title'=>'直播管理','tabHash'=>'gh','url'=>U('live/Admin/index'));
		}
        $this->pageTitle['addLive'] = '创建直播课堂';

        $this->pageTab[] = array('title'=>'创建直播课堂','tabHash'=>'addLive','url'=>U('live/Admin/addLive'));

		$this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
		$this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
		parent::_initialize();
	}

    /**
     * 创建课堂直播课堂
     */
    public function addLive(){
        if( isset($_POST) ) {
            if(empty($_POST['title'])){$this->error("名称不能为空");}
            if(empty($_POST['live_levelhidden'])){$this->error("请选择分类");}
            if(empty($_POST['cover'])){$this->error("请上传直播课堂封面");}
            if(empty($_POST['info'])){$this->error("直播课堂信息不能为空");}
            if($_POST['price'] == ''){$this->error("价格不能为空");}
            if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
            if(empty($_POST['beginTime'])){$this->error("上架时间不能为空");}
            if(empty($_POST['endTime'])){$this->error("下架时间不能为空");}

            $myAdminLevelhidden 		= getCsvInt(t($_POST['live_levelhidden']),0,true,true,',');  //处理分类全路径
            $fullcategorypath 			= explode(',',$_POST['live_levelhidden']);
            $category 					= array_pop($fullcategorypath);
            $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['fullcategorypath']	= $myAdminLevelhidden; //分类全路径
            $data['cate_id']		    = $category == '0' ? array_pop($fullcategorypath) : $category;

            $data['title']  = t($_POST['title']);
            $data['cover']  = intval($_POST['cover']);
            $data['info']   = $_POST['info'];
            $data['mhm_id'] = intval($_POST['mhm_id']);
            $data['score']  = intval($_POST['score']);
            $data['is_re']  = intval($_POST['is_re']);
            $data['is_charge']  = intval($_POST['is_charge']);
            $data['price']  = floatval($_POST['price']);
            $data['beginTime']  = strtotime($_POST['beginTime']);
            $data['endTime']    = strtotime($_POST['endTime']);

			dump($data);
			exit;
            $res = M('zy_live')->add($data);
            if($res){
                $this->assign('jumpUrl',U('live/Admin/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        } else {
            $_REQUEST['tabHash'] = 'addLive';

            $this->pageKeyList   = array('title','live_cate','cover','info','score','beginTime','endTime','mhm_id','price','is_re','is_charge');

//            $this->opt['speaker']= M('zy_teacher')->where('is_del=0')->getField('id,name');
            $this->onsubmit = 'admin.checkLive(this)';
            $this->opt['score']     = array('1'=>'★ （一星）','2'=>'★★ （二星）','3'=>'★★★ （三星）','4'=>'★★★★ （四星）','5'=>'★★★★★ （五星）');
            $this->opt['is_re']   = array('0'=>'否','1'=>'是');

            ob_start();
            echo W('CategoryLevel', array('table' => 'zy_live_category', 'id' => 'live_level'));
            $output = ob_get_contents();
            ob_end_clean();
            $this->savePostUrl = U('live/Admin/addLive');
            $this->displayConfig(array('live_cate'=>$output));
        }
    }


	//直播间列表-展示互动
	public function zshd(){
		$this->pageKeyList = array('number', 'subject', 'speaker', 'startDate', 'invalidDate', 'teacherJoinUrl', 'studentJoinUrl', 'teacherToken', 'assistantToken', 'studentClientToken', 'studentToken', 'clientJoin', 'webJoin', 'is_active', 'is_del', 'price', 'SDK_ID', 'DOACTION');
		//搜索字段
		$this->searchKey = array('number', 'subject', 'SDK_ID');
		$this->pageButton[] = array('title' => "搜索直播课堂", 'onclick' => "admin.fold('search_form')");
		// 数据的格式化
		if( isset($_POST) ) {
			$map['invalidDate']  = array('gt',time());
			if(!empty($_POST['number'])){$map['number'] = $_POST['number'];}
			if(!empty($_POST['subject'])){$map['subject'] = $_POST['subject'];}
			if(!empty($_POST['SDK_ID'])){$map['SDK_ID'] = $_POST['SDK_ID'];}
		}else{
			$map['invalidDate']  = array('gt',time());
		}
		$order = 'live_id desc';
		$list = $this->_getLiveList('index',20,$order,$map);
		foreach($list['data'] as &$val){
			$speaker = M('ZyTeacher')->where("id={$val['speaker']}")->field('name')->find();
			$val['speaker'] = $speaker['name'];
		}
		$this->displayList($list);
	}

	/**
	 * 已结束直播课堂列表（带分页）-展示互动
	 */
	public function endZshd(){
		$_REQUEST['tabHash'] = 'end';
		$this->pageKeyList = array('number', 'subject', 'speaker', 'startDate', 'invalidDate', 'teacherJoinUrl', 'studentJoinUrl', 'teacherToken', 'assistantToken', 'studentClientToken', 'studentToken', 'clientJoin', 'webJoin', 'is_active', 'is_del', 'price', 'SDK_ID', 'DOACTION');
		// 数据的格式化
		if( isset($_POST) ) {
			$map['invalidDate']  = array('lt',time());
			if(!empty($_POST['number'])){$map['number']   = $_POST['number'];}
			if(!empty($_POST['subject'])){$map['subject'] = $_POST['subject'];}
			if(!empty($_POST['SDK_ID'])){$map['SDK_ID']   = $_POST['SDK_ID'];}
		}else{
			$map['invalidDate']  = array('lt',time());
		}

		$order = 'live_id desc';
		$liveInfo = $this->_getLiveList('end',15,$order,$map);
		foreach($liveInfo['data'] as &$val){
			$speaker = M('ZyTeacher')->where("id={$val['speaker']}")->field('name')->find();
			$val['speaker'] = $speaker['name'];
		}
		$this->displayList($liveInfo);
	}

	/**
	 * 禁用直播课堂列表（带分页）-展示互动
	 */
	public function closeZshd(){
		$_REQUEST['tabHash'] = 'close';
		$this->pageKeyList = array('number', 'subject', 'speaker', 'startDate', 'invalidDate', 'teacherJoinUrl', 'studentJoinUrl', 'teacherToken', 'assistantToken', 'studentClientToken', 'studentToken', 'clientJoin', 'webJoin', 'is_active', 'is_del', 'price', 'SDK_ID', 'DOACTION');
		// 数据的格式化
		if( isset($_POST) ) {
			$map = 'is_del = 1';
			if(!empty($_POST['number'])){$map['number']   = $_POST['number'];}
			if(!empty($_POST['subject'])){$map['subject'] = $_POST['subject'];}
			if(!empty($_POST['SDK_ID'])){$map['SDK_ID']   = $_POST['SDK_ID'];}
		}else{
			$map = 'is_del = 1';
		}

		$order = 'live_id desc';
		$liveInfo = $this->_getLiveList('close',15,$order,$map);
		foreach($liveInfo['data'] as &$val){
			$speaker = M('ZyTeacher')->where("id={$val['speaker']}")->field('name')->find();
			$val['speaker'] = $speaker['name'];
		}
		$this->displayList($liveInfo);
	}
	
	//直播间列表-三芒
	public function sm(){
		$this->pageButton[] = array('title'=>'创建直播间','onclick'=>"admin.addLiveSm()");
		$this->pageKeyList = array('id','name','templateTypeTxt','barrage','DOACTION');
		$a = $this->config['api_url'];
		$this->displayList($list);
	}

	//直播列表-光慧
	public function gh(){
		$this->pageButton[] = array('title'=>'创建直播间','onclick'=>"admin.addLiveGh()");
		$this->pageKeyList = array('id','title','uname','price','account','passwd','maxNum','supportMobile','introduce','beginTime','endTime','DOACTION');
		$list = M('zy_live')->order('id desc')->findPage(20);
		foreach($list['data'] as &$val) {
			$val['uname'] = getUserName($val['uid']);
			$val['supportMobile'] = $val['supportMobile'] ? '支持' : '不支持';
			$val['beginTime'] = date('Y-m-d H:i' , $val['beginTime'] / 1000);
			$val['endTime']   = date('Y-m-d H:i' , $val['endTime'] / 1000);
			$val['DOACTION']  = '<a href="'.U('live/Admin/editGh',array('tabHash'=>'editGh','id'=>$val['id'])).'">修改</a> | ';
			$val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/student/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">学生观看</a> | ';
			$val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">老师讲课</a> | ';
			$val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/playback/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">回放观看</a> | ';
			$val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.delGh(\''.$val['id'].'\')">删除</a>';
		}
		$this->displayList($list);
	}
	
	//直播间列表
	public function index(){
		if($this->base_config['live_opt'] == 1){
			//展示互动
			$this->zshd();
		}else if($this->base_config['live_opt'] == 2){
			$this->sm();
			//三芒
		}else if($this->base_config['live_opt'] == 3){
			//光慧
			$this->gh();
		}
	}

	/**
	 * 获取直播间信息-展示互动
	 */
	public function checkLive(){
		$_REQUEST['tabHash'] = 'checkLive';

		$SDK_ID = $_REQUEST['SDK_ID'];
		$map = array(
			'SDK_ID' => $SDK_ID
		);
		$liveInfo = M( 'live' )->where ( $map )->find ();

		$speaker = M('ZyTeacher')->where("id={$liveInfo['speaker']}")->field('id,name,inro')->find();
		$liveInfo['speaker'] = $speaker['name'];
		$liveInfo['speakerInfo'] = $speaker['inro'];
		$liveInfo['startDate'] = date('Y-m-d H:i:s',$liveInfo["startDate"]);
		$liveInfo['invalidDate'] = date('Y-m-d H:i:s',$liveInfo["invalidDate"]);
		$this->assign('liveInfo', $liveInfo);
		$this->display();
	}

	/**
	 * 创建课堂直播间-展示互动
	 */
	public function addZshd(){
		if( isset($_POST) ) {
			$startDate = strtotime($_POST['startDate']);
			$invalidDate = strtotime($_POST['invalidDate']);
			$live_time = trim($_POST['startDate']);
			$liveTime = substr($live_time,11,5);

			$newTime = time();//当前时间加两个小时的时间+7200
			$map['subject'] = trim(t($_POST['subject']));
			$field = 'subject';
			$liveSubject = model('Live')->findLiveAInfo($map,$field);

			if(empty($_POST['subject'])){$this->error('课程名称不能为空');}
			if($_POST['subject'] == $liveSubject['subject']){$this->error('已有此直播课堂名称,请勿重复添加');}
			if(empty($_POST['speaker'])){$this->error('演讲人不能为空');}
			if(empty($_POST['cover'])){$this->error('封面照还没有上传');}
			if($_POST['price'] == ''){$this->error('价格不能为空');}
			if(!is_numeric($_POST['price'])){$this->error('价格必须为数字');}
			if(empty($_POST['myAdminLevelhidden'])){$this->error('请选择分类');}
			if($_POST['score'] == ''){$this->error('评分不能为空');}
			if(!is_numeric($_POST['score']) ){$this->error('评分必须为数字');}
			if(intval($_POST['score']) > 5 ){$this->error('评分不能大于5');}
			if(empty($startDate)){$this->error('开始时间不能为空');}
			if($startDate < $newTime ){$this->error('开始时间必须大于当前时间');}
			if(empty($invalidDate)){$this->error('结束时间不能为空');}
			if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
			if($_POST['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
			if($_POST['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}
			if(!$clientJoin && !$webJoin){$this->error('Web端学生加入或客户端开启学生加入必须开启其一');}
			if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
			if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
			if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
			if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
			if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
			if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
			if(empty($_POST['studentToken'])){$this->error('学生WEB端口令不能为空');}
			if(!is_numeric($_POST['studentToken'])){$this->error('学生WEB端口令必须为数字');}
			if(strlen($_POST['studentToken'])< 6 || strlen($_POST['studentToken']) >15 ){$this->error('学生WEB端口令只能为6-15位数字');}
			if($_POST['teacherToken'] == $_POST['assistantToken'] || $_POST['teacherToken'] == $_POST['studentClientToken'] || $_POST['teacherToken'] == $_POST['studentToken'] || $_POST['assistantToken'] == $_POST['studentClientToken']
				|| $_POST['assistantToken'] == $_POST['studentToken'] || $_POST['studentClientToken'] == $_POST['studentToken']){
				$this->error('四个口令的值不能相同');
			}
			if(empty($_POST['scheduleInfo'])){$this->error('课程安排信息不能为空');}
			if(empty($_POST['description'])){$this->error('课程信息不能为空');}

			$speaker = M('ZyTeacher')->where("id={$_POST['speaker']}")->field('id,name,inro')->find();
			$url   = $this->zshd_config['api_url'].'/room/created?';
			$param = 'subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
				'&invalidDate='.t($invalidDate*1000).'&teacherToken='.t($_POST['teacherToken']).
				'&assistantToken='.t($_POST['assistantToken']).'&studentClientToken='.t($_POST['studentClientToken']).
				'&studentToken='.t($_POST['studentToken']).'&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).
				'&description='.urlencode(t($_POST['description'])).'&clientJoin='.$clientJoin.'&webJoin='.$webJoin.
				'&speakerInfo='.urlencode(t($speaker['inro']));
			$hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
			$url   = $url.$hash;
			$addLive = $this->getDataByUrl($url);

			if($addLive['code'] == 0) {
				if(empty($addLive["number"])){$this->error('服务器创建失败');}
				//查此次插入数据库的课堂名称
				$url   = $this->zshd_config['api_url'].'/room/info?';
				$param = 'roomId='.$addLive["id"];
				$hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
				$url   = $url.$hash;
				$live = $this->getDataByUrl($url);
				if(empty($live["number"])){$this->error('服务器查询失败');}

				if($addLive["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
				if($addLive["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}

				$data["number"] = $addLive["number"];
				$data["subject"] = $live['subject'];
				$data["speaker"] = intval($_POST['speaker']);
				$data["price"] = intval($_POST['price']);
				$data["score"] = intval($_POST['score']);
				$data["startDate"] = $addLive["startDate"]/1000;
				$data["invalidDate"] = $addLive["invalidDate"]/1000;
				$data["teacherJoinUrl"] = $addLive["teacherJoinUrl"];
				$data["studentJoinUrl"] = $addLive["studentJoinUrl"];
				$data["teacherToken"] = $addLive["teacherToken"];
				$data["assistantToken"] = $addLive["assistantToken"];
				$data["studentClientToken"] = $addLive["studentClientToken"];
				$data["studentToken"] = $addLive["studentToken"];
				$data["scheduleInfo"] = t($_POST['scheduleInfo']);
				$data["description"] = t($_POST['description']);
				$data["live_time"] = $liveTime;
				$data["cover"] = intval($_POST['cover']);
				$data["cate_id"] = ','.$_POST['myAdminLevelhidden'].',';
				$data["clientJoin"] = $liveClientJoin;
				$data["webJoin"] = $liveWebJoin;
				$data["SDK_ID"] = $addLive["id"];
				$data["is_active"] = 1;
				$result = model('Live')->data($data)->add();
				if(!$result){$this->error('创建失败!');}
				$this->success('创建成功');
			} else {
				$this->error('服务器出错啦');
			}
		} else {
			$_REQUEST['tabHash'] = 'addZshd';
			$this->pageKeyList   = array('subject','speaker','cover','price','cate_id','score','startDate','invalidDate','clientJoin','webJoin','teacherToken','assistantToken','studentClientToken','studentToken'  ,'scheduleInfo','description');

			$this->opt['speaker']= M('zy_teacher')->where('is_del=0')->getField('id,name');
			$this->opt['clientJoin']        	= array('1'=>'开启','0'=>'不开启');
			$this->opt['webJoin'] 				= array('1'=>'开启','0'=>'不开启');

			ob_start();
			echo W('VideoLevel',array('type'=>3));
			$output = ob_get_contents();
			ob_end_clean();
			$this->savePostUrl = U('live/Admin/addZshd');
			$this->displayConfig(array('cate_id'=>$output));
		}
	}

	/**
	 * 编辑直播间-展示互动
	 */
	public function editZshd(){
		if( isset($_POST) ) {
			$startDate = strtotime($_POST['startDate']);
			$invalidDate = strtotime($_POST['invalidDate']);
			$live_time = trim($_POST['startDate']);
			$liveTime = substr($live_time,11,5);

			if(empty($_POST['subject'])){$this->error('课程名称不能为空');}
			if(empty($_POST['speaker'])){$this->error('演讲人不能为空');}
			if(empty($_POST['cover'])){$this->error('演讲人照片还没有上传');}
			if($_POST['price'] == ''){$this->error('价格不能为空');}
			if(!is_numeric($_POST['price']) ){$this->error('价格必须为数字');}
			if(empty($_POST['myAdminLevelhidden'])){$this->error('请选择分类');}
			if($_POST['score'] == ''){$this->error('评分不能为空');}
			if(!is_numeric($_POST['score']) ){$this->error('评分必须为数字');}
			if(intval($_POST['score']) > 5 ){$this->error('评分不能大于5');}
			if(empty($startDate)){$this->error('开始时间不能为空');}
			if(empty($invalidDate)){$this->error('结束时间不能为空');}
			if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
			if($_POST['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
			if($_POST['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}
			if(!$clientJoin && !$webJoin){$this->error('Web端学生加入或客户端开启学生加入必须开启其一');}
			if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
			if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
			if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
			if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
			if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
			if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
			if(empty($_POST['studentToken'])){$this->error('学生WEB端口令不能为空');}
			if(!is_numeric($_POST['studentToken'])){$this->error('学生WEB端口令必须为数字');}
			if(strlen($_POST['studentToken'])< 6 || strlen($_POST['studentToken']) >15 ){$this->error('学生WEB端口令只能为6-15位数字');}
			if($_POST['teacherToken'] == $_POST['assistantToken'] || $_POST['teacherToken'] == $_POST['studentClientToken'] || $_POST['teacherToken'] == $_POST['studentToken'] || $_POST['assistantToken'] == $_POST['studentClientToken']
				|| $_POST['assistantToken'] == $_POST['studentToken'] || $_POST['studentClientToken'] == $_POST['studentToken']){
				$this->error('四个口令的值不能相同');
			}
			if(empty($_POST['scheduleInfo'])){$this->error('课程安排信息不能为空');}
			if(empty($_POST['description'])){$this->error('课程信息不能为空');}

			$speaker = M('ZyTeacher')->where("id={$_POST['speaker']}")->field('id,name,inro')->find();
			$url   = $this->zshd_config['api_url'].'/room/modify?';
			$param = 'id='.t($_POST['SDK_ID']).'&subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
				'&invalidDate='.t($invalidDate*1000).'&teacherToken='.t($_POST['teacherToken']).
				'&assistantToken='.t($_POST['assistantToken']).'&studentClientToken='.t($_POST['studentClientToken']).
				'&studentToken='.t($_POST['studentToken']).'&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).
				'&description='.urlencode(t($_POST['description'])).'&clientJoin='.$clientJoin.'&webJoin='.$webJoin.
				'&speakerInfo='.urlencode(t($speaker['inro']));
			$hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
			$url   = $url.$hash;
			$upLive = $this->getDataByUrl($url);

			if($upLive['code'] == 0){
				//查此次插入数据库的课堂名称
				$url   = $this->zshd_config['api_url'].'/room/info?';
				$param = 'roomId='.$_POST['SDK_ID'];
				$hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
				$url   = $url.$hash;
				$live = $this->getDataByUrl($url);
				if(empty($live["number"])){$this->error('服务器查询失败');}

				if($live["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
				if($live["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}

				$data["subject"] = $live['subject'];
				$data["speaker"] = intval($_POST['speaker']);
				$data["price"] = intval($_POST['price']);
				$data["score"] = intval($_POST['score']);
				$data["startDate"] = $live['startDate']/1000;
				$data["invalidDate"] = $live['invalidDate']/1000;
				$data["teacherToken"] = $live['teacherToken'];
				$data["assistantToken"] = $live['assistantToken'];
				$data["studentClientToken"] = $live['studentClientToken'];
				$data["studentToken"] = $live['studentToken'];
				$data["scheduleInfo"] = t($_POST['scheduleInfo']);
				$data["description"] = t($_POST['description']);
				$data["live_time"] = $liveTime;
				$data["cover"] = intval($_POST['cover']);
				$data["cate_id"] = ','.$_POST['myAdminLevelhidden'].',';
				$data["clientJoin"] = $liveClientJoin;
				$data["webJoin"] = $liveWebJoin;

				$map = array('SDK_ID'=>t($_POST['SDK_ID']));
				$result = model('Live')->updLiveAInfo($map,$data);
				if( $result !== false) {
					$this->success('修改成功');
				} else {
					$this->error('修改失败!');
				}
				
			}else {
				$this->error('服务器出错啦');
			}
		} else {
			$SDK_ID = t($_REQUEST['SDK_ID']);
			$_REQUEST['tabHash'] = 'editZshd';

			// 数据的格式化
			$map = array(
				'SDK_ID' => $SDK_ID,
			);
			$liveInfo = M( 'live' )->where ( $map )->find ();
			$liveInfo['startDate'] = date('Y-m-d H:i:s',$liveInfo["startDate"]);
			$liveInfo['invalidDate'] = date('Y-m-d H:i:s',$liveInfo["invalidDate"]);
			if($liveInfo['webJoin'] == 1 && $liveInfo['clientJoin'] == 1){
				$this->pageKeyList   = array('SDK_ID','subject','speaker','cover','price','myAdminLevelhidden','score','startDate','invalidDate','clientJoin','webJoin','teacherToken','assistantToken','studentToken','studentClientToken','scheduleInfo','description');
			}else if($liveInfo['webJoin'] == 1){
				$this->pageKeyList   = array('SDK_ID','subject','speaker','cover','price','myAdminLevelhidden','score','startDate','invalidDate','clientJoin','webJoin','teacherToken','assistantToken','studentToken','scheduleInfo','description');
			}else if($liveInfo['clientJoin'] == 1){
				$this->pageKeyList   = array('SDK_ID','subject','speaker','cover','price','myAdminLevelhidden','score','startDate','invalidDate','clientJoin','webJoin','teacherToken','assistantToken','studentToken','studentClientToken','scheduleInfo','description');
			}
			$this->opt['clientJoin']        	= array('1'=>'开启','0'=>'不开启');
			$this->opt['webJoin'] 				= array('1'=>'开启','0'=>'不开启');
			$this->opt['speaker'] = M('zy_teacher')->where('is_del=0')->getField('id,name');

			ob_start();
			echo W('VideoLevel',array('type'=>3,'default'=>trim( $liveInfo['cate_id'] , ',')));
			$output = ob_get_contents();
			ob_end_clean();
			$liveInfo['myAdminLevelhidden'] = $output;

			$this->savePostUrl = U('live/Admin/editZshd');
			$this->displayConfig($liveInfo);
		}
	}

	/**
	 * 禁用直播间-展示互动
	 */
	public function closeLive(){
		$SDK_ID = t($_POST['SDK_ID']);
		$map = array('SDK_ID' => $SDK_ID,);
		$data = array('is_del'=>1,);
		$result = model('Live')->closeAndOpenLive($map,$data);

		if(!$result) {
			$return['status'] = 0;
			$return['info'] = '禁用失败';				// 操作失败
		} else {
			$return['status'] = 1;
			$return['info'] = '禁用成功';			// 操作成功
		}
		exit(json_encode($return));
	}

	/**
	 * 恢复直播间-展示互动
	 */
	public function openLive(){
		$SDK_ID = t($_POST['SDK_ID']);
		$map = array('SDK_ID' => $SDK_ID,);
		$data = array('is_del'=>0,);

		$result = model('Live')->closeAndOpenLive($map,$data);
		if(!$result) {
			$return['status'] = 0;
			$return['info'] = '恢复失败';
		} else {
			$return['status'] = 1;
			$return['info'] = '恢复成功';
		}
		exit(json_encode($return));
	}
	/**
	 * 激活直播间-展示互动
	 */
	public function activeLive(){
		$SDK_ID = t($_POST['SDK_ID']);
		$map = array('SDK_ID' => $SDK_ID,);
		$data = array('is_active'=>1,);

		$result = model('Live')->closeAndOpenLive($map,$data);
		if(!$result) {
			$return['status'] = 0;
			$return['info'] = '激活失败';
		} else {
			$return['status'] = 1;
			$return['info'] = '激活成功';
		}
		exit(json_encode($return));
	}

	/**
	 * 彻底关闭直播间-展示互动
	 */
	public function deleteZshd(){
		$SDK_ID = t($_POST['SDK_ID']);

		$url   = $this->zshd_config['api_url'].'/room/deleted?';
		$param = 'roomId='.$SDK_ID;
		$hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
		$url   = $url.$hash;
		$delLive = $this->getDataByUrl($url);

		if($delLive['code'] == 0) {
			$map = array('SDK_ID' => $SDK_ID,);
			$result = model('Live')->delALiveInfo($map);
			if($result){
				$return['status'] = 0;
				$return['info'] = "关闭失败";			// 数据库操作失败
			}
			$return['status'] = 1;
			$return['info'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');			// 操作成功
		} else {
			$return['status'] = 0;
			$return['info'] = L('PUBLIC_ADMIN_OPRETING_ERROR');				// 操作失败
		}
		exit(json_encode($return));
	}

	

	//创建直播间-三芒
	public function addSm(){
		$_REQUEST['tabHash'] = 'addSm';
		$this->savePostUrl = U('live/Admin/create');
		$this->displayConfig();
	}
	
	//创建直播间-光慧
	public function addGh(){
		if( isset($_POST) ) {
			$url  = $this->gh_config['api_url'].'/openApi/createLiveRoom';
			$data = $_POST;
			unset($data['systemdata_list']);
			unset($data['systemdata_key']);
			unset($data['pageTitle']);
			unset($data['mzLevelSelect2']);
			unset($data['mzLevelSelect3']);
			unset($data['myAdminLevelhidden']);
			$data['beginTime'] = strtotime($data['beginTime']) * 1000;
			$data['endTime']   = strtotime($data['endTime']) * 1000;
			$data['cate_id']   = ','.$_POST['myAdminLevelhidden'].',';
			$data['uid']       = $this->mid;
			$id = M('zy_live')->add($data);
			if( $id ) {
				$data['id'] = $id;
				$data['teachers']  = json_encode( array( array('account'=>$data['account'] , 'passwd'=>base64_encode( md5($data['passwd'] , true) ) , 'info'=>$data['info'] ) ) );
				$data = array_merge($data , $this->_ghdata() );
				$res = json_decode( request_post($url , $data) , true);
				if($res['code'] == 0) {
					$data['room_id'] = $res['liveRoomId'];
					M('zy_live')->where('id='.$id)->save( $data );
					$this->assign( 'jumpUrl', U('live/Admin/index') );
					$this->success('创建成功');
				} else {
					//删除本地数据
					M('zy_live')->where('id='.$id)->delete();
					$this->error('创建失败');
				}
			} else {
				$this->error('创建失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'addGh';
			$this->pageKeyList   = array('cate_id','title','introduce','price','maxNum','supportMobile','liveMode','score','cover','account','passwd','info','beginTime','endTime');
			$this->opt['supportMobile'] = array('0'=>'不支持','1'=>'支持');
			$this->opt['liveMode']      = array('1'=>'通用','2'=>'大视频','3'=>'1对1');
			ob_start();
			echo W('VideoLevel',array('type'=>3));
			$output = ob_get_contents();
			ob_end_clean();
			$this->savePostUrl = U('live/Admin/addGh');
			$this->displayConfig(array('cate_id'=>$output));
		}
	}
	
	//修改直播间-光慧
	public function editGh(){
		if( isset($_POST) ) {
			$url    = $this->gh_config['api_url'].'/openApi/updateLiveRoom';
			$data = $_POST;
			unset($data['systemdata_list']);
			unset($data['systemdata_key']);
			unset($data['pageTitle']);
			unset($data['mzLevelSelect2']);
			unset($data['mzLevelSelect3']);
			unset($data['myAdminLevelhidden']);
			$data['beginTime'] = strtotime($data['beginTime']) * 1000;
			$data['endTime']   = strtotime($data['endTime']) * 1000;
			$data['teachers']  = json_encode( array( array('account'=>$data['account'] , 'passwd'=>base64_encode( md5($data['passwd'] , true) ) , 'info'=>$data['info'] ) ) );
			$data = array_merge($data , $this->_ghdata() );
			$res  = json_decode( request_post($url , $data) , true);
			if($res['code'] == 0) {
				unset($data['teachers']);
				$data['cate_id']   = ','.$_POST['myAdminLevelhidden'].',';
				$res = M('zy_live')->where('id='.$data['id'])->save( $data );
				if( $res !== false ) {
					$this->assign( 'jumpUrl', U('live/Admin/index') );
					$this->success('修改成功');
				} else {
					$this->error('修改失败');
				}
				
			} else {
				$this->error('修改失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'editGh';
			$this->pageKeyList   = array('id','myAdminLevelhidden','title','introduce','price','maxNum','score','cover','account','passwd','info','beginTime','endTime');
			$this->savePostUrl = U('live/Admin/editGh');
			$data = M('zy_live')->where('id='.intval($_GET['id']) )->find();
			ob_start();
			echo W('VideoLevel',array('type'=>3,'default'=>trim( $data['cate_id'] , ',')));
			$output = ob_get_contents();
			ob_end_clean();
			$data['myAdminLevelhidden'] = $output;
				
			$data['beginTime'] = date('Y-m-d H:i:s' , $data['beginTime'] / 1000);
			$data['endTime']   = date('Y-m-d H:i:s' , $data['endTime'] / 1000);
			$this->displayConfig($data);
		}
	}
	
	//删除光慧直播
	public function delGh(){
		$data['id'] = intval($_POST['id']);
		$data = array_merge($data , $this->_ghdata() );
		$url  = $this->gh_config['api_url'].'/openApi/deleteLiveRoom';
		$res = json_decode( request_post($url , $data) , true);
		if($res['code'] == 0) {
			if( M('zy_live')->where('id='.$data['id'])->delete() ){
				$return['status'] = 1;
				$return['info']   = '删除成功';
			}else{
				$return['status'] = 0;
				$return['info']   = '删除失败';
			}
		} else {
			$return['status'] = 0;
			$return['info']   = '删除失败';
		}
		exit( json_encode($return) );
	}
	
	//获取光慧直播的公共参数
	private function _ghdata(){
		$data['customer']      = $this->gh_config['customer'];
		$data['timestamp']     = time() * 1000;
		$str = md5( $data['customer'] . $data['timestamp'] . $this->gh_config['secretKey'] );
		$data['s'] = substr($str , 0 , 10 ) . substr($str ,-10 );
		$data['fee'] = 0;
		return $data;
	}

	/**
	 * 解析直播列表数据
	 * @param integer $limit 结果集数目，默认为20
	 * @param $order 排序
	 * @return array 解析后的直播列表数据
	 */
	private function _getLiveList($type,$limit,$order,$map) {
		$liveInfo = model('Live')->getLiveInfo($limit,$order,$map);

		foreach($liveInfo['data'] as $key => $val){
			$liveInfo['data'][$key]['teacherToken'] = intval($val['teacherToken']);
			$liveInfo['data'][$key]['assistantToken'] = intval($val['assistantToken']);
			$liveInfo['data'][$key]['studentClientToken'] = intval($val['studentClientToken']);
			$liveInfo['data'][$key]['studentToken'] = intval($val['studentToken']);
			$liveInfo['data'][$key]['startDate'] = date('Y-m-d H:i:s',$val["startDate"]);
			$liveInfo['data'][$key]['invalidDate'] = date('Y-m-d H:i:s',$val["invalidDate"]);

			if ($val['clientJoin'] == 0) {$liveInfo['data'][$key]['clientJoin'] = '<span style="color: red;">关闭</span>';}else{$liveInfo['data'][$key]['clientJoin'] = '开启';}
			if ($val['webJoin'] == 0) {$liveInfo['data'][$key]['webJoin'] = '<span style="color: red;">关闭</span>';}else{$liveInfo['data'][$key]['webJoin'] = '开启';}
			if ($val['is_del'] == 0) {$liveInfo['data'][$key]['is_del'] = '开启';}else{$liveInfo['data'][$key]['is_del'] = '<span style="color: red;">关闭</span>';}
			if ($val['is_active'] == 1) {$liveInfo['data'][$key]['is_active'] = '已审核';}else{$liveInfo['data'][$key]['is_active'] = '<span title="激活此直播课堂" href="javascript:;" onclick="admin.activeLive(\''.$val['SDK_ID'].'\')" style="color: red;">未审核</span> ';}
			$liveInfo['data'][$key]['DOACTION'] = '<a title="查看直播课堂详细信息" href="'.U('live/Admin/checkLive',array('tabHash'=>'checkLive','SDK_ID'=>$val["SDK_ID"])).'">查看</a> | ';
			$liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/Admin/editZshd',array('SDK_ID'=>$val["SDK_ID"])).'">编辑</a> | ';
			if($val['is_del'] == 0){
				$liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.closeLive(\''.$val['SDK_ID'].'\')">'.L('PUBLIC_SYSTEM_NOUSE').'</a> | ';
			}else{
				$liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.openLive(\''.$val['SDK_ID'].'\')">'.L('PUBLIC_RECOVER').'</a> | ';
			}
			$liveInfo['data'][$key]['DOACTION'] .= '<a target="_blank" href="'.U('live/Index/live_teacher',array('SDK_ID'=>$val["SDK_ID"])).'">教师讲课</a>  | ';
			$liveInfo['data'][$key]['DOACTION'] .= '<a title="此操作会使数据找不回来哦" href="javascript:void(0)"  onclick="admin.delete(\''.$val['SDK_ID'].'\')">彻底关闭</a>  ';
			switch(strtolower($type)) {
				case 'index':
					break;
			}
		}
		return $liveInfo;
	}

	//根据url读取文本
	private function getDataByUrl($url , $type = true){
		return json_decode(file_get_contents($url) , $type);
	}
	
}