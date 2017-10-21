<?php
/**
 * 后台众筹申请数据
 * @author ezhu <ezhufrank@qq.com>
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminCrowAction extends AdministratorAction{

    //众筹模型对象
    protected $model = null;
    //状态html，请勿添加引号
    protected $statusHtml = array(
        '<span style=color:red>待审核</span>',
        '<span style=color:gray>申请失败</span>',
        '<span style=color:green>申请成功</span>',
        '<span style=color:green>众筹成功</span>',
        '<span style=color:gray>众筹失败</span>',
        '<span style=color:gray>众筹失败（过期）</span>',
    );

    /**
     * 初始化，配置页面标题；创建模型对象
     * @return void
     */
    public function _initialize(){
        parent::_initialize();
        $this->pageTab[] = array('title'=>'待审核','tabHash'=>'index','url'=>U('classroom/AdminCrow/index'));
        $this->pageTab[] = array('title'=>'申请成功','tabHash'=>'successful','url'=>U('classroom/AdminCrow/successful'));
        $this->pageTab[] = array('title'=>'申请失败','tabHash'=>'failure','url'=>U('classroom/AdminCrow/failure'));
        $this->pageTitle['index'] = '众筹申请列表 - 待审核';
        $this->pageTitle['successful'] = '众筹申请列表 - 申请成功';
        $this->pageTitle['failure'] = '众筹申请列表 - 申请失败';
        $this->model = D('Crow');
    }


    /**
     * 众筹申请列表 - 待审核
     * @return void
     */
    public function index(){
        $listData = $this->_list('index');
        $this->displayList($listData);
    }


    /**
     * 众筹申请列表 - 申请成功
     * @return void
     */
    public function successful(){
        $listData = $this->_list('successful');
        $this->displayList($listData);
    }


    /**
     * 众筹申请列表 - 申请失败
     * @return void
     */
    public function failure(){
        $listData = $this->_list('failure');
        $this->displayList($listData);
    }


    /**
     * 设置列表基本信息及取得列表数据
     * @param string $type 列表类型
     * @return array
     */
    protected function _list($type){
        //页面配置
        $this->pageKeyList = array('id','uid','school_title','title','price','ctime','status');
        if(in_array($type,array('failure'))){
            $this->pageKeyList[] = 'rtime';
            $this->pageKeyList[] = 'reason';
        }
        //成功了
        if(in_array($type,array('successful'))){
//             $this->pageKeyList[] = 'vstime';
//             $this->pageKeyList[] = 'vetime';
            $this->pageKeyList[] = 'stime';
            $this->pageKeyList[] = 'num';
            $this->pageKeyList[] = 'etime';
        }
        $this->pageKeyList[] = 'DOACTION';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //$this->pageButton[] = array('title'=>'删除记录','onclick'=>"admin.delWithdraw()");
        $this->pageButton[]  = array('title'=>'删除','onclick'=>"admin.delCrowAll('delCrow')");
        //搜索项
        $this->searchKey = array('uid', 'sid', 'startTime', 'endTime');
        $this->searchPostUrl = U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('tabHash'=>ACTION_NAME));

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        /*查找数据*/
        //列表对应到状态
        $status = array(
            'index'      => 0,
            'failure'    => 1,
            'successful' => array('egt',2),
        );
        $map['status'] = $status[$type];
        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        //根据机构查找
        if(!empty($_POST['sid'])){
            $_POST['sid'] = t($_POST['sid']);
            $map['sid'] = array('in', $_POST['sid']);
        }
        //时间范围内进行查找
        if(!empty($_POST['startTime'])){
            $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
        }
        if(!empty($_POST['endTime'])){
            $map['etime'][] = array('lt', strtotime($_POST['endTime']));
        }

        //列表排序
        if($status[$type] < 2){
            $order = 'ctime DESC,id DESC';
        }else{
            $order = 'rtime DESC,ctime DESC,id DESC';
        }

        $map['is_del'] = 0;
        //取得数据列表
        $listData = $this->model->where($map)->order($order)->findPage();

        //整理列表显示数据
        foreach($listData['data'] as $key => $val){
        	$etime = $val['etime'];
            $val['uid']      = getUserSpace($val['uid'], null, '_blank');
            $val['stime']    = friendlyDate($val['stime']);
            $val['ctime']    = date('Y-m-d H:i',$val['ctime']);
            $val['etime']    = date('Y-m-d H:i',$val['etime']);
            $val['rtime']    = $val['rtime']?friendlyDate($val['rtime']):'-';
            $val['price']     = '<span style="color:blue">￥'.$val['price'].'</span>';
            $val['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            switch ($val['status']){
                case '0':  //众筹申请待管理员审核
                    $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/operate',
                            array('id'=>$val['id'],'tabHash'=>'operate')).'">查看/操作</a>';
                    break;
                case '1':  //众筹申请失败
                    $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/view', 
                        array('id'=>$val['id'],'tabHash'=>'view')).'">查看详细</a>';
                    break;
                case '2':  //众筹申请成功
                    $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/view', 
                        array('id'=>$val['id'],'tabHash'=>'view')).'">查看详细</a>';
                    if($etime < time()){
                    	$val['status'] = 5;
                    }
                    break;
                case '3':  //众筹成功
                    $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/view', 
                        array('id'=>$val['id'],'tabHash'=>'view')).'">查看申请</a>';
                    
                    $val['DOACTION'].= '&nbsp|&nbsp<a href="'.U('classroom/AdminCrow/viewUser',
                        array('id'=>$val['id'],'tabHash'=>'viewUser')).'">查看参加众筹用户</a>';
                    
                    $val['DOACTION'].= '&nbsp|&nbsp<a href="'.U('live/AdminLive/addLive',
                            array('crow_id'=>$val['id'],'cid'=>$val['category'],'tabHash'=>'addLive')).'">添加众筹课程</a>';
                    break;
                case '4':  //众筹失败
                    $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/view', 
                        array('id'=>$val['id'],'tabHash'=>'view')).'">查看申请</a>';
                        
                    $val['DOACTION'].= '&nbsp|&nbsp<a href="'.U('classroom/AdminCrow/viewUser',
                        array('id'=>$val['id'],'tabHash'=>'viewUser')).'">查看参加众筹用户</a>';
                    break;
                
            }
            //$val['DOACTION'] .= '　<a href="javascript:;" onclick="admin.delWithdraw('.$val['id'].')">删除</a>';
            $val['status']  = $this->statusHtml[$val['status']];
            $val['reason']  = $val['reason']?$val['reason']:'-';
            $val['num']     = $val['num'] ? $val['num'].'人' : '-';
            $listData['data'][$key] = $val;
        }
        return $listData;
    }

    /**
     * 众筹申请 - 查看详细
     * @return void
     */
    public function viewUser(){
        unset($this->pageTab);
        unset($this->pageTitle);
        $this->pageTab[] = array('title'=>'众筹列表','tabHash'=>'crowList','url'=>U('classroom/AdminCrow/crowList'));
        $this->pageTitle['crowList'] = '众筹列表';
        $this->pageTab[] = array('title'=>'众筹用户','tabHash'=>'viewUser','url'=>U('classroom/AdminCrow/viewUser'));
        $this->pageTitle['viewUser'] = '众筹用户';
        $this->pageKeyList = array('uid','uname','avatar','phone','location','ctime','reg_ip');
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid');
        $this->searchPostUrl = U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('tabHash'=>ACTION_NAME));
        
        $map['cid'] = intval(t($_GET['id']));
        //取得数据列表
        $listData = M('CrowdfundingUser')->where($map)->findPage(10);
        
        foreach ($listData['data'] as $key=>&$val){
            $userInfo = model('User')->getUserInfo($val['uid']);
            $val['uname'] = $userInfo['uname'];
            $val['avatar'] = '<img src="'.$userInfo['avatar_small'].'" />';
            $val['phone'] = $userInfo['phone'];
            $val['location'] = $userInfo['location'];
            $val['ctime'] = date('Y-m-d H:i',$val['ctime']);
            $val['reg_ip'] = $userInfo['reg_ip'];
            
        }
        $this->displayList($listData);
    }

    /**
     * 众筹申请 - 查看详细
     * @return void
     */
    public function view(){
        //$this->onsubmit = 'admin.zyPageBack()';
        $this->displayConfig($this->_view('view', '查看详细'));
    }


    /**
     * 众筹申请 - 查看和操作
     * @return void
     */
    public function operate(){
        $this->opt['status'] = array('1'=>'申请失败','2'=>'申请成功');
        $this->displayConfig($this->_view('operate', '查看/操作'));
    }


    /**
     * 众筹申请
     * return void
     */
    public function dispose(){
        $id = intval($_POST['id']);
        $status = intval($_POST['status']);
        $recommend = intval($_POST['recommend']);
        $reason = t($_POST['reason']);
        $etime = t($_POST['etime']);
        $data['status'] = $status;
        $data['reason'] = $reason;
        // 观看
        if(t($_GET['urlType']) == 'view'){
            $this->jumpUrl = U('classroom/AdminCrow/index');
            $this->success('正在跳转');
            exit();
        }
        if($status != 1 && empty($etime)){
            $this->error('请选择结束时间');
        }
        if(!empty($etime) && $status != 1){
            if(strtotime($etime) < time()){
                $this->error('众筹结束时间必须大于当前时间');
            }
            $data['etime'] = strtotime($etime);
            $data['stime'] = time();
            $data['recommend'] = $recommend;
        }
        // 驳回
        if($status == 1){
            $data['rtime'] = time();
        }
        $result = M('Crowdfunding')->where(array('id'=>$id))->save($data);
        if($result !== false){
            $this->assign('isAdmin',1);
            $this->success('操作成功');
        }else{
            $this->assign('isAdmin',1);
            $this->error('操作失败');
        }
    }


    /**
     * 详细内容基本信息
     * @param string $type 视图类型
     * @param string $name 视图名称
     * return array
     */
    public function _view($type, $name){
        $_GET['id'] = intval($_GET['id']);
        $data = $this->model->find($_GET['id']);
        $this->assign('isAdmin',1);
        if(!$data) $this->error('没有找到记录');
        
        $this->pageTab[] = array('title'=>$name,'tabHash'=>$type,'url'=>U('classroom/AdminCrow/'.$type, array('id'=>$_GET['id'],'tabHash'=>$type)));
        $this->pageTitle[$type] = '众筹申请 - '.$name;
        $this->submitAlias = '确 定';
        $this->pageKeyList = array('id','uid','title','info','ctime','price','status','reason');
        $data['uid'] = getUserName($data['uid']).'(id:'.$data['uid'].')';
        $data['ctime'] = date('Y-m-d H:i:s', $data['ctime']);
        $data['etime'] = empty($data['etime']) ? '' : date('Y-m-d', $data['etime']);
        switch ($type){
            case 'view':
                if($data['status'] == 1){
                    $this->pageKeyList[] = 'rtime';
                    $data['rtime']  = !$data['rtime'] ? '-' : date('Y-m-d H:i:s', $data['rtime']);
                }
                //$this->opt['status'] = array('待审核','申请失败','申请成功');
                $data['statusCode'] = $data['status'];
                $data['status'] = $this->statusHtml[$data['status']];
                $data['reason'] = $data['reason']?$data['reason']:'-';
                // 查看
                $this->savePostUrl = U('classroom/AdminCrow/dispose',array('urlType'=>'view'));
                break;
            case 'operate':
                $this->pageKeyList = array('id','uid','title','info','ctime','price','status','etime','recommend','reason');
                $this->opt['status'] = array(1=>'申请驳回',2=>'申请成功');
                // 操作
                $this->savePostUrl = U('classroom/AdminCrow/dispose',array('urlType'=>'dispose'));
                break;
        }
        $data['price'] = '<span style=color:blue>￥'.$data['price'].'</span>';
        return $data;
    }

    /**
     * 删除众筹记录
     * @return void
     */
    public function del(){
        //TODO 不能删除众筹记录，如需要删除，还需要添加页面按钮及列表删除链接
        $this->assign('isAdmin',1);
        $this->error('不能删除众筹记录');exit;
        if(is_array($_POST['id'])){
            $_POST['id'] = implode(',', $_POST['id']);
        }
        $id = "'".str_replace(array("'", ','), array('', "','"), $_POST['id'])."'";
        if($this->model->where("id IN($id)")->delete()){
            $this->ajaxReturn('删除成功');
        }else{
            $this->ajaxReturn('删除失败');
        }
    }
    
    
    /**
     * 众筹列表
     */
    public function crowList(){
        unset($this->pageTab);
        unset($this->pageTitle);
        $this->pageTab[] = array('title'=>'众筹列表','tabHash'=>'crowList','url'=>U('classroom/AdminCrow/crowList'));
        $this->pageTitle['crowList'] = '众筹列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'删除','onclick'=>"admin.delCrowAll('delCrow')");
        $this->pageKeyList = array('id','title','category','uname','num','price','stime','etime','vstime','vetime','status_text','DOACTION');

        //搜索项
        $this->searchKey = array('uid','title','status');
        $this->opt['status'] = array('2'=>'申请成功','3'=>'众筹成功','4'=>'众筹失败');
        $this->searchPostUrl = U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('tabHash'=>ACTION_NAME));
        
        $uid = intval(t($_POST['uid']));
        $status = intval(t($_POST['status']));
        $title = t($_POST['title']);
        $map['status'] = array('egt',2);
        $uid && $map['uid'] = $uid;
        $status && $map['status'] = $status;
        $title && $map['title'] = array('like','%'.$title.'%');
        $map['is_del'] = 0;
        //取得数据列表
        $listData = M('Crowdfunding')->where($map)->findPage(20);
        
        foreach ($listData['data'] as $key=>&$val){
            $userInfo = model('User')->getUserInfo($val['uid']);
            $cid = $val['category'];
            //获取分类名称
            $cname = '';
            if(strpos($val['category'],',') !== false){
                $cate_ids = explode(',',$val['category']);
                foreach ($cate_ids as $k=>$v){
                    $cname.='　'.getCategoryName($v,false);
                }
            }else{
                $cname.='　'.getCategoryName($val ['category'],true);
            }
            $val['category']  = $cname;
            $val['uname']  = $userInfo['uname'];
            if($val['status'] == 2 && $val['etime'] <= time()){ //众筹过期
            	$val['status'] = 5;
            }
            //$val['cover']  = '<img src="'.getCover($val['cover'],50,50).'" />';
            $val['stime']  = $val['stime'] ? date('Y-m-d H:i',$val['stime']) : '';
            $val['etime']  = $val['etime'] ? date('Y-m-d H:i',$val['etime']) : '无限制';
            $val['vstime'] = $val['vstime'] ? date('Y-m-d H:i',$val['vstime']) : '';
            $val['vetime'] = $val['vetime'] ? date('Y-m-d H:i',$val['vetime']) : '';
            $val['status_text'] = $this->statusHtml[$val['status']];
            $val['DOACTION'] = '<a href="'.U('classroom/AdminCrow/viewUser',
                    array('id'=>$val['id'],'tabHash'=>'viewUser')).'">查看众筹用户</a>';
            if(empty($val['video_id']) && $val['status'] == 3){
                $val['DOACTION'].= '&nbsp|&nbsp<a href="'.U('live/AdminLive/addLive',
                        array('crow_id'=>$val['id'],'tabHash'=>'addLive','cid'=>$cid,'sid'=>$val['sid'],'price'=>$val['price'],'num'=>$val['num'])).'">添加众筹课程</a>';
            }else if(!empty($val['video_id']) && $val['status'] == 3){
                $val['DOACTION'] .= ' | <a target="_blank" href=" '.U('live/Index/view',array('id'=>$val['video_id'])).' ">查看课程</a>';
            }
        }
        $this->displayList($listData);
    }

    /*
    *删除 众筹
    */
    public function delCrow(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );

        $data['is_del'] = 1;
        $res = M('Crowdfunding')->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
}