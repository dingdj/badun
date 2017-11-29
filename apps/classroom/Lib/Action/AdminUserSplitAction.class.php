<?php
/**
 * 分成列表信息管理控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminUserSplitAction extends AdministratorAction {
    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();

        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('classroom/AdminUserSplit/index'));
        $this->pageTab[] = array('title'=>'流水','tabHash'=>'flow','url'=>U('classroom/AdminUserSplit/flow'));
//        $this->pageTab[] = array('title'=>'课程分成明细列表','tabHash'=>'splitVideo','url'=>U('classroom/AdminUserSplit/splitVideo'));
//        $this->pageTab[] = array('title'=>'班级分成明细列表','tabHash'=>'splitAlbum','url'=>U('classroom/AdminUserSplit/splitAlbum'));
//        $this->pageTab[] = array('title'=>'直播课程分成明细列表','tabHash'=>'splitLive','url'=>U('classroom/AdminUserSplit/splitLive'));
    }
    
    /**
     * 分成列表信息管理
     * @return void
     */
    public function index(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('uname','realname','idcard','catagroy','school_title','balance','frozen','DOACTION');
        $this->pageTitle['index'] = '云课堂用户列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid','school_title');
        $school = model('School')->field('id,title')->findALL();
        $this->opt['school_title'] = $school ? array('0'=>'不限')+array_column($school,'title','id') : array('0'=>'不限');

        $this->searchPostUrl = U('classroom/AdminUserSplit/index', array('tabHash'=>'index'));
        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        if(!empty($_POST['school_title'])){
            $mhm_id = intval($_POST['school_title']);
            $uid = model('User')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            $new_uid = getSubByKey($uid,'uid');
            $map['uid'] = array('in', $new_uid);
        }

        $list = M('zy_split_balance')->where($map)->order('id DESC')->findPage();
        foreach($list['data'] as &$value){
            $user = M('user_verified')->where("uid=".$value["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $value['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$value['uid']] );
            $user_groups = '';
            foreach($user_group as &$val) {
                $user_groups .= $val['user_group_name'].'<br/>';
            }
            $value['realname']    = $user["realname"];
            $value['idcard']      = $user["idcard"];
            $value['catagroy']    = $user_groups;
            $value['uname']       = getUserSpace($value['uid'], null, '_blank');
            $value['balance']     = '<span style="color:green;">￥'.$value['balance'].'</span>';
            $value['frozen']      = '<span style="color:red;">￥'.$value['frozen'].'</span>';
            //处理机构信息
            $mhm_id = model('User')->where('uid='.$value['uid'])->getField('mhm_id');
            $s_map = array('id'=>$mhm_id);
            $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
            if($school){
                if(!$school['doadmin']){
                    $url = U('school/School/index', array('id' => $val['mhm_id']));
                }else{
                    $url = getDomain($school['doadmin']);
                }
                $value['school_title'] = getQuickLink($url,$school['title'],"平台所有");
            }else{
                $value['school_title'] = "<span style='color: red;'>平台所有</span>";
            }
            $value['DOACTION'] =  '<a href="'.U(APP_NAME.'/'.MODULE_NAME.'/edit', array('id'=>$value['id'], 'tabHash'=>'edit')).'">编辑</a>';
//            $value['DOACTION'] .= ' | <a href="'.U('classroom/AdminUserSplit/learn',array('uid'=>$value['uid'],'tabHash'=>'learn')).'">TA的学习记录</a>';
            $value['DOACTION'] .= ' | <a href="'.U('classroom/AdminUserSplit/uflow',array('uid'=>$value['uid'],'tabHash'=>'uflow')).'">TA的分成账户流水</a>';
        }

        $this->displayList($list);
    }

    public function splitVideo(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','vid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','mount_school_id','mount_school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitVideo'] = '课程分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项 搜索条件：课程ID、课程名称、课程价格、平台、平台机构、课程机构、购买机构、分享者、日期筛选
        $this->searchKey = array('vid','video_title');

        $this->searchPostUrl = U('classroom/AdminUserSplit/splitVideo', array('tabHash'=>'index'));

        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        $order = 'id desc';
        $list = $this->_list('zy_split_course',$map,$order,20);

        $this->displayList($list);
    }
    public function splitAlbum(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','aid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitAlbum'] = '班级分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid');

        $this->searchPostUrl = U('classroom/AdminUserSplit/splitAlbum', array('tabHash'=>'splitAlbum'));

        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        $order = 'id desc';
        $list = $this->_list('zy_split_album',$map,$order,20);

        $this->displayList($list);
    }

    public function splitLive(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','lid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitLive'] = '直播课程分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid');

        $this->searchPostUrl = U('classroom/AdminUserSplit/splitLive', array('tabHash'=>'splitLive'));

        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        $order = 'id desc';
        $list = $this->_list('zy_split_live',$map,$order,20);

        $this->displayList($list);
    }

    public function _list($type,$map,$order,$limit){
        $map['status'] = 1;
        if($type == 'zy_split_course'){
            $list = M('zy_split_course')->where($map)->order($order)->findPage($limit);
        }elseif($type == 'zy_split_album'){
            $list = M('zy_split_album')->where($map)->order($order)->findPage($limit);
        }elseif($type == 'zy_split_live'){
            $list = M('zy_split_live')->where($map)->order($order)->findPage($limit);
        }
        foreach ($list['data'] as $key => $val){
            if($type == 'zy_split_course'){
                $info_title = M('zy_video')->where('id = '.$val['vid'])->getField('video_title');
            } elseif($type == 'zy_split_live'){
                $info_title = M('zy_video')->where('id = '.$val['lid'])->getField('video_title');
            }elseif($type == 'zy_split_album'){
                $info_title = M('album')->where('id = '.$val['aid'])->getField('album_title');
            }
            $list['data'][$key]['video_title']   = $info_title;
            $list['data'][$key]['uid']   = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $list['data'][$key]['ltime'] = date('Y-m-d H:i:s',$val["ltime"]);
            $list['data'][$key]['pid']   = getUserSpace($val['pid'], null, '_blank');
            $list['data'][$key]['sid']   = getUserSpace($val['sid'], null, '_blank');
            $list['data'][$key]['mount_school_id']   = getUserSpace($val['mount_school_id'], null, '_blank');
            $list['data'][$key]['oschool_uid']   = getUserSpace($val['oschool_uid'], null, '_blank');
            $list['data'][$key]['share_id'] = getUserSpace($val['share_id'], null, '_blank');
            $list['data'][$key]['st_id'] = getUserSpace($val['st_id'], null, '_blank');
            $list['data'][$key]['platform_sum']       = "<span style='color: red;'>￥{$val['platform_sum']}</span>";
            $list['data'][$key]['ouats_ouschool_sum']       = "<span style='color: red;'>￥{$val['ouats_ouschool_sum']}</span>";
            $list['data'][$key]['school_sum']         = "<span style='color: red;'>￥{$val['school_sum']}</span>";
            $list['data'][$key]['mount_school_sum']         = "<span style='color: red;'>￥{$val['mount_school_sum']}</span>";
            $list['data'][$key]['share_sum'] = "<span style='color: red;'>￥{$val['share_sum']}</span>";
            $list['data'][$key]['school_teacher_sum'] = "<span style='color: red;'>￥{$val['school_teacher_sum']}</span>";
        }
        return $list;
    }
    /**
     * 编辑操作
     */
    public function edit(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST['balance'] = floatval($_POST['balance']);
            $_POST['frozen'] = floatval($_POST['frozen']);
            $set = array(
                'id' => intval($_POST['id']),
                'balance'    => $_POST['balance'],
                'frozen'     => $_POST['frozen'],
            );
            $name = getUserName( M('zy_split_balance')->where('id=' . intval($_POST['id']))->getField('uid') );
            if(false !== M('zy_split_balance')->save($set)){
                LogRecord('admin_classroom','editBalance',array('uname'=>$name,'balance'=>$_POST['balance']),true);
                $this->success('保存成功！');
            }else{
                $this->error('保存失败！');
            }
            exit;
        }
        $_GET['id'] = intval($_GET['id']);
        $this->pageTab[] = array('title'=>'查看/修改','tabHash'=>'edit','url'=>U(APP_NAME.'/'.MODULE_NAME.'/edit', array('id'=>$_GET['id'],'tabHash'=>'edit')));
        $this->pageTitle['edit'] = '用户信息查看/修改';
        $this->savePostUrl = U(APP_NAME.'/'.MODULE_NAME.'/edit');
        $this->submitAlias = '确 定';
        $this->pageKeyList = array('id','uid','balance','frozen');
        $data = M('zy_split_balance')->find($_GET['id']);
        $data['uid'] = getUserSpace($data['uid'], null, '_blank');
        $this->displayConfig($data);
    }

    /**
     * 流水列表
     */
    public function flow(){
        $this->_flow(false);
    }
    
    //学习记录
    public function learn($limit=20){
        $_REQUEST['tabHash'] = 'learn'; 
        $this->pageButton[] = array('title'=>'删除记录','onclick'=>"admin.delLearnAll('delArticle')");
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
        $this->pageKeyList  = array('id','uname','video_title','sid','time','ctime','DOACTION');
        $this->searchKey    = array('id','uid','video_title','sid');
        $uid = intval( $_GET['uid'] );
        $learn = M('learn_record')->where('uid='.$uid)->order("ctime DESC")->findPage($limit);
        foreach($learn['data'] as &$val){
            $val['ctime'] = date('Y-m-d',$val['ctime']) ;
            $val['uname']  = getUserSpace($val['uid']);
            $val['video_title'] = M('zy_video')->where(array('id'=>$id))->getField('video_title'); 
            if($val['is_del'] == 1) {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'显示\',\'学习记录\');">显示</a>';
            }else {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'隐藏\',\'学习记录\');">隐藏</a>';
            }
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle','学习记录--'.$val['name']);
        $this->displayList($learn);
    }

    //显示/隐藏 学习记录
    public function closelearn(){
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('learn_record')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('learn_record')->where($where)->save($data);

        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    /**
     * 用户流水列表
     */
    public function uflow(){
        $this->_flow(intval($_GET['uid']));
    }


    public function _flow($uid){
        
        $this->pageKeyList = array('id','uname','realname','idcard','catagroy','type','num','balance','rel_id','note','ctime');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageTitle[ACTION_NAME] = $uid?'账户流水-'.getUserName($uid):'所有流水记录';
        if($uid){
            $this->pageTab[]    = array('title'=>'账户流水-'.getUserName($_GET['uid']),'tabHash'=>ACTION_NAME,'url'=>U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME,array('uid'=>$uid)));
            $this->pageButton[] = array('title'=>'&lt;&lt;&nbsp;返回来源页','onclick'=>"admin.zyPageBack()");
            $this->searchKey    = array('type','note','startTime','endTime');
        }else{
            $this->searchKey    = array('uid','type','note','startTime','endTime');
        }

        $this->opt['type']  = array('全部','消费','充值','冻结','解冻','冻结扣除','分成收入');
        $this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));

        $map = array();
        if($uid){
            $map['uid'] = $uid;
        }elseif(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }

        if(!empty($_POST['type']) && $_POST['type']>0){
            $map['type'] = $_POST['type']-1;
        }
        if(!empty($_POST['note'])){
            $map['note'] = array('like', '%'.t($_POST['note']).'%');
        }
        //时间范围内进行查找
        if(!empty($_POST['startTime'])){
            $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
        }
        if(!empty($_POST['endTime'])){
            $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
        }

        $list = D('ZySplit')->flowModel()->where($map)->order('ctime DESC,id DESC')->findPage();
        $relTypes = D('ZySplit')->getRelTypes();
        foreach($list['data'] as $key=>$value){
            $user=D('user_verified')->where("uid=".$value["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $value['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$value['uid']] );
            $user_groups = '';
            foreach($user_group as &$val) {
                $user_groups .= $val['user_group_name'].'<br/>';
            }
            $list['data'][$key]['realname']  = $user["realname"];
            $list['data'][$key]['idcard']    = $user["idcard"];
            $list['data'][$key]['catagroy']  = $user_groups;
            $list['data'][$key]['uname']       = getUserSpace($value['uid'], null, '_blank');
            switch ($value['type']){
                case 0:$list['data'][$key]['type'] = "扣除";break;
                case 1:$list['data'][$key]['type'] = "增加";break;
                case 2:$list['data'][$key]['type'] = "冻结";break;
                case 3:$list['data'][$key]['type'] = "解冻";break;
                case 4:$list['data'][$key]['type'] = "冻结扣除";break;
                case 5:$list['data'][$key]['type'] = "分成收入";break;
            }
            if($value['ctime'] == 0){
                $list['data'][$key]['ctime']    =  '-';
            }else{
                $list['data'][$key]['ctime']    = date('Y-m-d H:i:s', $value['ctime']);
            }
            
            $list['data'][$key]['num']        = '<span style=color:red>￥'.$value['num'].'</span>';        
            $list['data'][$key]['balance']    = '<span style=color:green>￥'.$value['balance'].'</span>';
            $list['data'][$key]['rel_id']     = $value['rel_id']>0?$value['rel_id']:'-';
            if(isset($relTypes[$value['rel_type']])&&$value['rel_id']>0){
                $list['data'][$key]['rel_id'] = $relTypes[$value['rel_type']].'-ID:'.$value['rel_id'];
            }
        }
        $this->displayList($list);
    }

	public function recharge(){
		$this->pageTitle['recharge'] = '用户充值记录';
		$this->pageKeyList = array('id','uname','realname','idcard','catagroy','money','type','vip_length','note','ctime','status','stime','pay_order','pay_type');
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
		$this->searchKey    = array('uid','startTime','endTime');
		$this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));
		$recharge = D('ZyRecharge');
		$map['status'] = array('gt', 0);
		if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
		//时间范围内进行查找
        if(!empty($_POST['startTime'])){
            $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
        }
        if(!empty($_POST['endTime'])){
            $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
        }
		$data = $recharge->where($map)->order('stime DESC,id DESC')->findPage();
		$types = array('分成充值', '会员充值');
		$status= array('未支付', '已成功', '失败');
		$payType = array('alipay'=>'支付宝', 'unionpay'=>'银联');
		foreach($data['data'] as &$val){
            $user=D('user_verified')->where("uid=".$val["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $val['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$val['uid']] );
            $user_groups = '';
            foreach($user_group as &$value) {
                $user_groups .= $value['user_group_name'].'<br/>';
            }
            $val['realname'] = $user["realname"];
            $val['idcard']   = $user["idcard"];
            $val['catagroy'] = $user_groups;
			$val['uname']   = getUserSpace($val['uid'], null, '_blank');
			$val['ctime'] = friendlyDate($val['ctime']);
			$val['type']  = isset($types[$val['type']])?$types[$val['type']]:'-';
			$val['money'] = '￥'.$val['money'];
			$val['status']= $status[$val['status']];
			$val['stime'] = friendlyDate($val['stime']);
			$val['stime'] = $val['stime']?$val['stime']:'-';
			$val['pay_type']  = isset($payType[$val['pay_type']])?$payType[$val['pay_type']]:'-';
		}
		$this->displayList($data);
	}
}