<?php
/**
 * 余额列表信息管理控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminLearncAction extends AdministratorAction {
    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('classroom/AdminLearnc/index'));
        $this->pageTab[] = array('title'=>'流水','tabHash'=>'flow','url'=>U('classroom/AdminLearnc/flow'));
    }
    
    /**
     * 余额列表信息管理
     * @return void
     */
    public function index(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('uname','realname','idcard','catagroy','balance','frozen','vip_type','vip_expire','DOACTION');
        $this->pageTitle['index'] = '列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid', 'vip_type');
        $vip_type = M('user_vip')->where('is_del=0')->getField('id,title');
        
        $this->opt['vip_type']    = $vip_type;
        $this->opt['vip_type'][0] = '不限';
        $this->searchPostUrl = U('classroom/AdminLearnc/index', array('tabHash'=>'index'));
        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        if(!empty($_POST['vip_type'])){
            $map['vip_type'] = $_POST['vip_type'];
        }

        $list = D('ZyLearnc')->where($map)->order('id DESC')->findPage();
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
            if( $value['vip_type'] ) {
                $value['vip_type'] = M('user_vip')->where('id= '.$value['vip_type'])->getField('title');
                $value['vip_expire']    = date('Y-m-d H:i:s',$value['vip_expire']);
            } else {
                $value['vip_type']    = '-';
                $value['vip_expire']    = "-";
            }
               
            $value['DOACTION']  = '<a href="'.U(APP_NAME.'/'.MODULE_NAME.'/edit', array('id'=>$value['id'], 'tabHash'=>'edit')).'">编辑</a>';
            $value['DOACTION'] .=   '| <a href="'.U('classroom/AdminLearnc/uflow',array('uid'=>$value['uid'],'tabHash'=>'uflow')).'">TA的余额账户流水</a>';
        }

        $this->displayList($list);
    }


    /**
     * 编辑操作
     */
    public function edit(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST['vip_type'] = intval($_POST['vip_type']);
            if($_POST['vip_type'] == 0){
                $_POST['vip_expire'] = 0;
            }else{
                $_POST['vip_expire'] = strtotime($_POST['vip_expire'])+0;
            }
            $_POST['balance'] = floatval($_POST['balance']);
            $_POST['frozen'] = floatval($_POST['frozen']);
            $set = array(
                'id' => intval($_POST['id']),
                'vip_type' => $_POST['vip_type'],
                'vip_expire' => $_POST['vip_expire'],
                'balance'    => $_POST['balance'],
                'frozen'     => $_POST['frozen'],
            );
            $name = getUserName( M('zy_learncoin')->where('id=' . intval($_POST['id']))->getField('uid') );
            if(false !== D('ZyLearnc')->save($set)){
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
        $this->pageKeyList = array('id','uid','balance','frozen','vip_type','vip_expire');
        $user_vip = M('user_vip')->where('is_del=0')->getField('id,title');
        $this->opt['vip_type'] = $user_vip ? $user_vip + [0=>'请选择'] : [0=>'请选择'];
        $data = D('ZyLearnc')->find($_GET['id']);
        $data['uid'] = getUserSpace($data['uid'], null, '_blank');
        $data['vip_expire'] = $data['vip_expire']>0?date('Y-m-d H:i:s', $data['vip_expire']):'';
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
            $val['video_title'] = model('ZyVideo')->getVideoTitleById($val['vid']); 
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
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
        $this->pageTitle[ACTION_NAME] = $uid?'账户流水-'.getUserName($uid):'流水';
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

        $list = D('ZyLearnc')->flowModel()->where($map)->order('ctime DESC,id DESC')->findPage();
        $relTypes = D('ZyLearnc')->getRelTypes();
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
                case 0:$list['data'][$key]['type'] = "消费";break;
                case 1:$list['data'][$key]['type'] = "充值";break;
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

}