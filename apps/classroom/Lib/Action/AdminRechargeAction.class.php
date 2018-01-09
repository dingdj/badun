<?php
/**
 * 支付列表信息管理控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminRechargeAction extends AdministratorAction {
    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
//		$this->pageTab[] = array('title'=>'用户充值记录', 'tabHash'=>'recharge', 'url'=>U('classroom/AdminRecharge/index'));
    }
    
    /**
     * 支付列表信息管理
     * @return void
     */
    public function index(){
        $this->pageTitle['index'] = '支付记录';
        $this->pageKeyList = array('id','uname','catagroy','money','type','note','ctime','status','stime','pay_order','pay_type');//,'realname','idcard','vip_length'
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->searchKey    = array('uid','startTime','endTime');
        $this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));

        $recharge = D('ZyRecharge');
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

        //充值的类型(0:学币,1:购买,2:积分,3:vip会员)
        $types = array('','课程购买','余额充值', '会员充值');
        $status= array('未支付', '成功', '失败');
        $payType = array('alipay'=>'支付宝支付', 'unionpay'=>'银联支付', 'wxpay'=>'微信支付','lcnpay'=>'余额支付');
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
            $val['uname']    = getUserSpace($val['uid'], null, '_blank');
            $val['ctime']    = friendlyDate($val['ctime']);
            $val['type']     = array_filter(explode(',',$val['type']))[0];
            $val['type']     = isset($types[$val['type']]) ? $types[$val['type']]:'-';
            $val['money']    = '￥'.$val['money'];
            $val['status']   = $status[$val['status']];
            $val['stime']    = friendlyDate($val['stime']);
            $val['stime']    = $val['stime']?$val['stime']:'-';
            $val['pay_type'] = isset($payType[$val['pay_type']])?$payType[$val['pay_type']]:'-';
        }
        $this->displayList($data);
    }

    //打赏记录
    public function dashang(){
        $this->pageTitle['index'] = '打赏记录';
        $this->pageKeyList = array('id','t_name','name','money','add_time','pay_pass_num','state');//,'realname','idcard','vip_length'
        $data = D('zy_dashang_log')->where(array('state'=>1))->findPage();dd($data);
        foreach($data['data'] as &$log){
            $t_name = D('user')->where(array('uid'=>$log['ds_uid']))->getField('uname');
            $name = D('user')->where(array('uid'=>$log['uid']))->getField('uname');
            $log['t_name'] = $t_name;
            $log['name'] = $name;
            $log['add_time'] = date('Y-m-d H:i:s',$log['add_time']);
            $log['state']  = '已支付';
        }
        //dd($data);
        $this->displayList($data);
    }
}