<?php
/**
 * 后台积分管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGlobalAction extends AdministratorAction {


    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('mall/AdminGlobal/index'));

        $this->pageTab[] = array('title'=>'流水','tabHash'=>'flow','url'=>U('mall/AdminGlobal/flow'));
        $this->pageTab[] = array('title'=>'设置用户积分','tabHash'=>'user','url'=>U('mall/AdminGlobal/creditUser'));

    }
    /**
     * 积分列表管理
     */
    public function index(){
        $this->pageTitle['index']      = '列表';


        $this->pageKeyList = array('id', 'uid', 'uname','score','DOACTION');//,'vip_type'
        //搜索字段
        $this->searchKey = array('id', 'uid', 'uname', 'score');
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        // 数据的格式化
        $order = 'id desc';
        $list = $this->_getGreditUser('index',null,$order,20);

        $this->displayList($list);
    }

    /**
     *  批量用户积分设置
     */
    public function creditUser(){
        $id = intval($_GET['id']);
        if($id){
            $crrdit_user = M('credit_user')->where(array('id'=>$id))->find();
            $crrdit_user['uname'] = M('user')->where(array('uid'=>$crrdit_user['uid']))->getField('uname');
            $crrdit_user['is_active'] = M('user')->where(array('uid'=>$crrdit_user['uid']))->getField('is_active');
            $this->assign('crrdit_user',$crrdit_user);
        }
        $user_group = model('UserGroup')->getUserGroupByMap('','user_group_id, user_group_name');
        $creditType = M('credit_type')->order('id ASC')->findAll();

        $vip_title = M('user_vip')->where(array('is_del'=>0))->findAll();
        
        $this->assign('vip_title',$vip_title);
        $this->assign('creditType',$creditType);
        $this->assign('grounlist',$user_group);
        $this->display();
    }

    /**
     * 设置用户积分操作
     */
    public function doCreditUser(){
        set_time_limit(0);
        //查询用户ID
        $_POST['uId'] && $map['uid'] = array('in',array_filter(explode(',',t($_POST['uId']))));
        $_POST['gId']!='all' && $map['user_group_id'] = intval(t($_POST['gId']));
        // $_POST['active']!='all' && $map['is_active'] = intval($_POST['active']);

        $user = M('user_group_link')->where($map)->field('DISTINCT uid')->findAll();
        if($user == false){
            $this->error('查询失败，没有这样条件的人');
        }
        $_POST['uId'] && $c_map['uid'] = array('in',array_filter(explode(',',t($_POST['uId']))));
        $res = M('credit_user')->where($c_map)->field('uid,score')->select();
        if($_POST['score'] != 0){
            $c_uid = array();
            $c_res = null;
            foreach ($res as $key =>$val){
                if($_POST['score'] <= 0 ){
                    if($val['score'] <= 0 || ($val['score']) - (-$_POST['score']) < 0 ){
                        $c_uid[] = $val['uid'];
                        $c_res = implode(',',array_filter($c_uid));
                    }
                }
            }
            if($c_res){
                $this->error('uid为'.$c_res."的用户积分不能为0或更低");
            }

            //组装积分规则
            $setCredit = model('Credit');
            $creditType = $setCredit->getCreditType();
            foreach($creditType as $v){
                $action[$v['name']] = intval($_POST[$v['name']]);
            }

            if($_POST['action'] == 'set'){//积分修改为
                foreach($user as $v){
                    if($v['uid'] != 0){
                        $setCredit->setUserCredit($v['uid'],$action,'reset');
                        // if($setCredit->getInfo()===false)$this->error('保存失败');
                    }
                }
            }else{//增减积分
                foreach($user as $v){
                    if($v['uid'] != 0){
                        $setCredit->setUserCredit($v['uid'],$action);
                        if($setCredit->getInfo()===false)$this->error('保存失败');
                    }
                }
            }
        }else{
            foreach($res as $k=>$v){
                $data['score'] = $v['score'];
            }
        }
        if($_POST['vip_type'] > 0) {
            $map['uid'] = intval($_POST['uId']);
            $data['vip_type'] = t($_POST['vip_type']);
            $data['vip_expire'] = strtotime($_POST['vip_expire']);
            $data['ttime'] = time();
            if ($data['vip_type'] == 0) {
                unset($data['vip_expire']);
            }
            $res = model('Credit')->saveCreditUser($map, $data);
            if (!$res) {
                $this->error('保存失败！');
            }
        }
		
        $this->assign('jumpUrl', U('mall/AdminGlobal/index'));

        $_LOG['uid'] = $this->mid;
        $_LOG['type'] = '1';
        if( $_POST['action'] == 'set' ){
            $data[] = '全局 - 积分配置 - 设置用户积分 - 积分修改操作 ';
        }else{
            $data[] = '全局 - 积分配置 - 设置用户积分 - 积分增减操作 ';
        }
        $data['1'] = $action;
        $data['1']['uid'] = t($_POST['uId']);
        $data['1']['gId'] = t($_POST['gId']);
        $data['1']['active'] = t($_POST['active']);
        $data['1']['action'] = t($_POST['action']);
        $_LOG['data'] = serialize($data);
        $_LOG['ctime'] = time();
        M('AdminLog')->add($_LOG);

        $this->success('保存成功');
    }

    private function _getGreditUser($type,$map,$order,$limit){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['uid'] && $map['uid'] = intval(t($_POST['uid']));
            $_POST['uname'] && $map['uid'] = intval(t($_POST['uname']));
            $_POST['score'] && $map['score'] = floatval(t($_POST['score']));
        }
        $crrdit_user = model('Credit')->getGreditUser($map,$order,$limit);
        foreach($crrdit_user['data'] as $key => $val){
            $user = M('user')->where(['uid'=>$val['uid']])->getField('uid');
            $crrdit_user['data'][$key]['uname']     = getUserSpace($val['uid'], null, '_blank');
            $crrdit_user['data'][$key]['vip_type']  = M('user_vip')->where('id='.$val['vip_type'])->getField('title') ? : '非会员';
            $crrdit_user['data'][$key]['DOACTION']  = '<a href="'.U('mall/AdminGlobal/creditUser',array('id'=>$val['id'])).'">编辑</a> | <a href="'.U('mall/AdminGlobal/uflow',array('uid'=>$val['uid'],'tabHash'=>'uflow')).'">TA的积分流水</a>';
            if(!$user){
                $crrdit_user['data'][$key]['DOACTION']  .= " | <a href='javascript:;'onclick='admin.delUserGreditAFlow({$val['id']})' title='此操作会彻底删除之前用户的所有积分及其关联的积分流水'>彻底删除</a>";
            }
        }
        switch(strtolower($type)){
            case 'index':
                break;
        }
        return $crrdit_user;
    }

    public function delUserGreditAFlow(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );

        $uid = M('credit_user')->where($where)->getField('uid');
        if(!$uid){
            $msg['data'] = "未找到相关记录";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
        $user = M('user')->where(['uid'=>$uid])->getField('uid');
        if($user){
            $msg['data'] = "此用户还存在于系统，不能进行彻底删除!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }

        $res = M('credit_user')->where($where)->delete();

        if( $res !== false){
            M('credit_user_flow')->where(['uid'=>$uid])->delete();
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }


    /**
     * 用户流水列表
     */
    public function uflow(){
        $this->_flow(intval(t($_GET['uid'])));

    }

    /**
     * 流水列表
     */
    public function flow(){
        $this->_flow(false);
    }

    public function _flow($uid){

        $this->pageKeyList = array('id','uname','catagroy','type','num','balance','rel_id','note','ctime');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageTitle[ACTION_NAME] = $uid?'积分流水-'.getUserName($uid):'流水';
        if($uid){
            $this->pageTab[]    = array('title'=>'积分流水-'.getUserName($_GET['uid']),'tabHash'=>ACTION_NAME,'url'=>U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME,array('uid'=>$uid)));
            $this->pageButton[] = array('title'=>'&lt;&lt;&nbsp;返回来源页','onclick'=>"admin.zyPageBack()");
            $this->searchKey    = array('id','type','rel_id','note','startTime','endTime');
        }else{
            $this->searchKey    = array('id','uid','type','note','startTime','endTime');
        }

        $this->opt['type']  = array('全部','消费','充值','冻结','解冻','冻结扣除','分成收入','操作增加','操作减少');
        $this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));

        $map = array();
        if($uid){
            $map['uid'] = $uid;
        }elseif(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        if(!empty($_POST['id']))
        {
            $map['id'] = t($_POST['id']);
        }
        if(!empty($_POST['rel_id']))
        {
            $map['rel_id'] = t($_POST['rel_id']);
        }
        if(!empty($_POST['type']) && $_POST['type']>0){
            $map['type'] = t($_POST['type'])-1;
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
        $list = M('credit_user_flow')->where($map)->order('ctime DESC,id DESC')->findPage();
        foreach($list['data'] as $key=>$value){
            $user=D('user_verified')->where("uid=".$value["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $value['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$value['uid']] );
            $user_groups = '';
            foreach($user_group as &$val) {
                $user_groups .= $val['user_group_name'].'<br/>';
            }
            $list['data'][$key]['catagroy']  = $user_groups;
            $list['data'][$key]['uname']    = getUserSpace($value['uid'], null, '_blank');
            switch ($value['type']){
                case 0:$list['data'][$key]['type'] = "消费";break;
                case 1:$list['data'][$key]['type'] = "充值";break;
                case 2:$list['data'][$key]['type'] = "冻结";break;
                case 3:$list['data'][$key]['type'] = "解冻";break;
                case 4:$list['data'][$key]['type'] = "冻结扣除";break;
                case 5:$list['data'][$key]['type'] = "分成收入";break;
                case 6:$list['data'][$key]['type'] = "操作增加";break;
                case 7:$list['data'][$key]['type'] = "操作减少";break;
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
