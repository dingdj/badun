<?php
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
require_once './api/qiniu/rs.php';
require_once './api/cc/notify.php';

class UserAction extends CommonAction{
    protected $base_config = array();
    protected $gh_config = array();
    protected $zshd_config = array();
    protected $user = array();
    protected $cc_video_config = array();  //定义cc配置

    /**
    * 初始化
    * @return void
    */
    public function _initialize() {
        $this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
        $this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
        $this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
        $this->cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');

        $this->user = $this->get('user');
        if($this->user['uid'] == $this->mid){
            $this->assign("is_me",true);
        }
        $this->assign("user_show_type", 'user');

        $learnInfo = model('User')->findUserLearnInfo();
        $tmp = getFollowCount(array($this->mid));
        $credit = model('Credit')->getUserCredit($this->mid);
        $is_teacher = D('ZyTeacher','classroom')->where('uid='.$this->mid)->getField('verified_status');

        $this->assign("is_teacher", $is_teacher);
        $this->assign("learn", $learnInfo);
        $this->assign("tmp", $tmp);
        $this->assign("credit", $credit);

        parent::_initialize();
    }

    public function index(){
        $uid        = $this->mid;
        $limit      = 3;
        //拼接两个表名
        $vtablename = C('DB_PREFIX').'zy_video';
        $order_course = C('DB_PREFIX').'zy_order_course';
        $order_live = C('DB_PREFIX').'zy_order_live';

        //拼接字段
        $fields = "{$order_course}.`learn_status`,{$order_course}.`uid`,{$order_course}.`id` as `oid`,";
        $fields .= "{$vtablename}.`teacher_id`,{$vtablename}.`mhm_id`,{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_binfo`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.`video_order_count`,{$vtablename}.`ctime`,{$vtablename}.`t_price`";
        //不是通过班级购买的
        //$where     = "{$order_course}.`is_del`=0 and {$order_course}.`order_album_id`=0 and {$order_course}.`uid`={$uid}";
        $where     = "{$order_course}.`is_del`=0 and {$order_course}.`pay_status`=3 and {$order_course}.`uid`={$uid}";
        $video_data = M('zy_order_course')->join("{$vtablename} on {$order_course}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        foreach($video_data['data'] as &$val){
            $val['teacher_neme'] = M('zy_teacher')->where(['id'=>$val['teacher_id']])->getField('name');
            $school_info = M('school')->where(['id'=>$val['mhm_id']])->field('title,doadmin')->find();
            $val['school_title'] = $school_info['title'];
            $val['school_url'] = getDomain($school_info['doadmin'],$val['mhm_id']);
        }

        $fields_live = "{$order_live}.`learn_status`,{$order_live}.`uid`,{$order_live}.`id` as `oid`,";
        $fields_live .= "{$vtablename}.`teacher_id`,{$vtablename}.`mhm_id`,{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_binfo`,";
        $fields_live .= "{$vtablename}.`cover`,{$vtablename}.`video_order_count`,{$vtablename}.`ctime`,{$vtablename}.`t_price`";
        $where_live  = "{$order_live}.`is_del`=0 and {$order_live}.`pay_status`=3 and {$order_live}.`uid`={$uid}";
        $live_data = M('zy_order_live')->join("{$vtablename} on {$order_live}.`live_id`={$vtablename}.`id`")->where($where_live)->field($fields_live)->findPage($limit);
        foreach($live_data['data'] as &$val){
            $val['teacher_neme'] = M('zy_teacher')->where(['id'=>$val['teacher_id']])->getField('name');
            $school_info = M('school')->where(['id'=>$val['mhm_id']])->field('title,doadmin')->find();
            $val['school_title'] = $school_info['title'];
            $val['school_url'] = getDomain($school_info['doadmin'],$val['mhm_id']);
            $val['video_order_count'] =  M('zy_order_live') -> where(array('live_id'=> $val['id'], 'is_del' => 0 ,'pay_status'=>3)) -> count();;
        }
        $videocont= D("zy_order_course")->where(array('uid'=>$this->mid,'is_del'=>0,'pay_status'=>3))->count() ? D("zy_order_course")->where(array('uid'=>$this->mid,'is_del'=>0,'pay_status'=>3))->count() : 0;//加载我的课程总数
        $this->assign('video_data',$video_data);
        $this->assign('live_data',$live_data);
        $this->assign('videocont',$videocont);
        $this->display();
    }

    public function recharge(){
        $data = M('zy_learncoin')->where(array('uid'=>$this->mid))->find();
        if($data['vip_type'] > 0 && $data['vip_expire'] >= time() ){
            $vip_info = M('user_vip')->where('is_del=0 and id=' . $data['vip_type'])->find();
        	$data['vip_type_txt'] = $vip_info['title'];
            $map['sort'] = array('egt' , $vip_info['sort']);
        } else {
        	$data['vip_type'] = 0;
        }

        $map['is_del'] = 0;
        $user_vip = M('user_vip')->where($map)->order('sort asc')->select();

        if($this->is_wap && strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')){
            $this->assign('is_wx',true);
        }

        $payConfig = model('Xdata')->get("admin_Config:payConfig");

        $this->assign('payConfig', $payConfig);
        $this->assign('learnc', $data);
        $this->assign('user_vip', $user_vip);
        $this->display();
    }

    public function buyConcurrent(){

        $res =M('Concurrent')->where("id = 1")->select();
        foreach ($res as $val) {
            $onemprice = $val['onemprice'];
            $threemprcie = $val['threemprcie'];
            $sixmprice = $val['sixmprice'];
            $oneyprice = $val['oneyprice'];
        }
        $this->assign('onemprice',$onemprice);
        $this->assign('threeprcie',$threemprcie);
        $this->assign('sixmprice',$sixmprice);
        $this->assign('oneyprice',$oneyprice);

        $this->display();
    }

    //用户账户余额管理
    public function account(){
        $account = M('zy_learncoin')->where('uid = '.$this->mid)->find();
        $account['balance'] = $account['balance'] ? floatval($account['balance']) : 0.00;
        $this->assign('userLearnc', $account);

        //选择模版
        $tab = intval($_GET['tab']);
        $tpls = array('index','income','pay','take_list','take','recharge','integral_list');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'account_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $this->$method();
        }

        if($tab == 0) {
            //读取系统设置的支付方式&支付配置
            $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $rechange_default = array_filter(explode("\n", $split_score['rechange_default']));
            foreach ($rechange_default as &$val) {
                $val = array_filter(explode('=>', $val));
            }
            $payConfig = model('Xdata')->get("admin_Config:payConfig");

            $this->assign('rechange_default', $rechange_default);
            $this->assign('payConfig', $payConfig);

            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                model('WxPay')->getWxUserInfo($_GET['code'], SITE_URL . '/my/account.html');
            }

            if($this->is_wap && strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')){
                $this->assign('is_wx',true);
            }
        }

        $this->assign('tab', $tab);
        if($this->is_wap){
            $templateFile = "account/{$tpls[$tab]}_w3g";
            echo $this->fetch($templateFile,'utf-8','text/html',true);
        }else{
            $this->display("account/{$tpls[$tab]}");
        }
    }

    //余额流水记录
    protected function account_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZyLearnc')->flowModel->where($map)->order('ctime DESC,id DESC')->findPage(12);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= D('ZyLearnc')->flowModel->where($map)->sum('num') ? : 0;
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //余额充值记录
    protected function account_recharge(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $map['status'] = array('gt',0);
        $data = D('ZyRecharge')->where($map)->order('stime DESC,id DESC')->findPage(12);
        $total= D('ZyRecharge')->where($map)->sum('money');
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //余额营收记录
    protected function account_income(){
        $map = array('muid'=>$this->mid);

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZyOrder')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        $total= D('ZyOrder')->where(array('muid'=>$this->mid))->sum('user_num');
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //余额支付记录
    protected function account_pay(){
        $map = array('uid'=>$this->mid);

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZyOrder')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        $total= D('ZyOrder')->where(array('uid'=>$this->mid))->sum('price');
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //余额申请提现页面
    protected function account_take(){
        $card = D('ZyBcard')->getUserOnly($this->mid);
        if(!$card){
            $this->assign('isAdmin', 1);
            $this->assign('jumpUrl', U('classroom/User/card'));
            $this->error('请先绑定银行卡！'); exit;
        }
        //申请提现
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $num = intval($_POST['num']);
            $result = D('ZyService')->applyWithdraw(
                $this->mid, $num, $card['id']);
            if(true === $result){
                $this->ajaxReturn(null, '', true);
            }else{
                $this->ajaxReturn(null, $result, false);
            }
            exit;
        }
        $ZyWithdraw = D('ZyWithdraw');
        $data = $ZyWithdraw->getUnfinished($this->mid,1);
        //读取系统配置的客服电话
        $tel = M('system_data')->where("`list`='admin_config' AND `key`='site'")->field('value')->find();
        $system_config = unserialize($tel['value']);
        $this->assign('sys_tel',$system_config['sys_tel']);
        $this->assign('data', $data);
    }

    //余额申请提现列表页面
    protected function account_take_list(){
        if(!empty($_GET['id'])){
            $id = intval($_GET['id']);
            $result = D('ZyService')->setWithdrawStatus($id, $this->mid, 4);
            if(true === $result){
                $this->ajaxReturn(null, null, true);
            }else{
                $this->ajaxReturn(null, $result, false);
            }
            exit;
        }

        $map['uid']   = $this->mid;
        $map['wtype'] = 2;

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';


        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }

        $data = D('ZyWithdraw')->order('ctime DESC, id DESC')
            ->where($map)->findPage(12);

        $total= D('ZyWithdraw')->where(array('uid'=>$this->mid,'status'=>2,'wtype'=>1))->sum('wnum');
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //用户账户收入余额
    public function spilt(){
        $spilt_balance = D('ZySplit')->where(['uid'=>$this->mid])->getField('balance');
        $spilt_balance = $spilt_balance ? floatval($spilt_balance) : 0.00;
        $this->assign('balance',$spilt_balance);

        //选择模版
        $tab = intval($_GET['tab']);
        $tpls = array('index','integral_list','take','take_list');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'spilt_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $this->$method();
        }

        if($tab == 0){
            $card_list = D('ZyBcard')->getUserBcard($this->mid,'id,accounttype,account,accountmaster');
            if($card_list){
                foreach($card_list as $key => &$val){
                    $val['card_info'] = $val['accounttype']." ".substr($val['account'],-4);//$val['accounttype']."(".hideStar($val['account']).")";
                }
                //$this->assign('card_id', $card_list[0]['id']);
                $this->assign('card_list', $card_list);
            }else{
                $this->assign('card_info', "未绑定");
            }
            $alipay_info = D('ZyBcard')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->field('id,account,accountmaster')->find();

            if($alipay_info){
                $alipay_info['accountmaster'] = hideStar($alipay_info['accountmaster']);
                $alipay_info['account'] = hideStar($alipay_info['account']);

                $this->assign('alipay_info', $alipay_info);
            }else{
                $alipay_info['account'] = "未绑定";
                $this->assign('alipay_info', $alipay_info);
            }
            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];
            $account_balance = M('zy_learncoin')->where('uid = '.$this->mid)->getField('balance');

            $this->assign('account_balance', $account_balance ? floatval($account_balance) : 0.00);
            $this->assign('withdraw_basenum', $withdraw_basenum);
        }

        $this->assign('tab', $tab);
        if($this->is_wap){
            $templateFile = "spilt/{$tpls[$tab]}_w3g";
            echo $this->fetch($templateFile,'utf-8','text/html',true);
        }else{
            $this->display("spilt/{$tpls[$tab]}");
        }
    }

    //支付宝账户
    public function alipay(){
        $alipay_info = D('ZyBcard')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->field('id,account,accountmaster')->find();

        if($alipay_info){
            $alipay_info['account'] = hideStar($alipay_info['account']);
            $alipay_info['accountmaster'] = hideStar($alipay_info['accountmaster']);
        }

        $this->assign('alipay_info',$alipay_info);
        $this->display();
    }

    //绑定、修改支付宝账号
    public function saveAlipay(){
        $real_name      = t($_POST['real_name']);
        $alipay_account = t($_POST['alipay_account']);
        if(!$real_name){
            $this->ajaxReturn([],'请输入真实姓名',0);
        }
        if(!$alipay_account){
            $this->ajaxReturn([],'请输入支付宝账号',0);
        }
        $phone = "/^1[3|4|5|7|8][0-9]\d{8}$/";
        $email = "/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/";
        if (!preg_match( $phone, $alipay_account) &&  !preg_match( $email, $alipay_account)){
            $this->ajaxReturn([],'请输入正确的支付宝账号',0);
        }

        $data['uid'] = $this->mid;
        $data['account'] = $alipay_account;
        $data['accountmaster'] = $real_name;
        $data['accounttype'] = "alipay";

        $is_bond = D('ZyBcard')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->getField('id');

        if(!$is_bond){
            $res = D('ZyBcard')->add($data);
        } else {
            $res = D('ZyBcard')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->save($data);
        }
        if($res){
            $this->ajaxReturn([],'操作成功',1);
        }else{
            $this->ajaxReturn([],'操作失败',0);
        }
    }

    //删除绑定支付宝账号
    public function doBondAlipay(){
        $bond = D('ZyBcard')->where(['uid'=>$this->mid,'accounttype'=>'alipay'])->delete();

        if($bond){
            $this->ajaxReturn([],'解绑成功',1);
        }else{
            $this->ajaxReturn([],'解绑失败',0);
        }
    }

    //处理收入 转为余额/提现
    public function applySpiltWithdraw(){
        $tw_type = t($_POST['tw_type']);
        $exchange_balance = intval($_POST['exchange_balance']);

        if($tw_type == 'lcnpay'){//收入兑换余额
            $balance = D('ZySplit')->where(['uid'=>$this->mid])->getField('balance');

            if($exchange_balance < 1){
                $this->mzError('兑换的数量最少为1');
            }

            if(!floatval($balance)){
                $this->mzError('您暂无可兑换的收入');
            }
            unset($balance);

            if(!D('ZySplit')->isSufficient($this->mid,$exchange_balance)){
                $this->mzError('您的收入余额不够此次兑换的数量');
            }

            //扣除分成收入
            $consume = D('ZySplit')->consume($this->mid,$exchange_balance);
            if(!$consume){
                $this->mzError('出错了，请稍后再试');
            }
            $split_flow = D('ZySplit')->addFlow($this->mid,0,$exchange_balance,'转账为余额：'.$exchange_balance,'','zy_learncoin_flow');

            //添加余额并加相关流水
            $learnc = D('ZyLearnc')->recharge($this->mid,$exchange_balance);
            if($learnc){
                $learnc_flow = D('ZyLearnc')->addFlow($this->mid,1,$exchange_balance,'收入转账为余额：'.$exchange_balance,$split_flow,'zy_split_balance');
                D('ZySplit')->flowModel->where(['id'=>$split_flow])->save(['rel_id'=>$learnc_flow]);

                $this->mzSuccess("转账成功");
            } else {
                $this->mzError("转账失败");
            }
        } else if($tw_type == 'unionpay') {//申请提现-银行卡
            $card = D('ZyBcard')->getUserOnly($this->mid);
            if (!$card[0]) {
                $this->mzError("请先绑定银行卡");
            }
            $card_id = intval($_POST['card_id']);

            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

            if($exchange_balance<0||$exchange_balance<$withdraw_basenum||$exchange_balance%$withdraw_basenum!=0){
                $this->mzError("只允许提现为{$withdraw_basenum}的倍数");
            }

            $result = D('ZyService')->applySpiltWithdraw($this->mid, $exchange_balance, $card_id);

            if (true === $result) {
                $this->mzSuccess("提现申请成功，请等待审核");
            } else {
                switch ($result) {
                    case 1:
                        $info = "申请提现的收入余额不是系统指定的倍数，或小于0";
                        break;
                    case 2:
                        $info = "没有找到用户对应的提现银行卡/账户";
                        break;
                    case 3:
                        $info = "有未完成的提现记录，需要等待完成";
                        break;
                    case 4:
                        $info = "余额转冻结失败：可能是余额不足";
                        break;
                    case 5:
                        $info = "提现记录添加失败";
                        break;
                }
                $this->mzError($info);
            }
        } else if($tw_type == 'alipay') {//申请提现-支付宝
            $card = D('ZyBcard')->getUserOnlyAliAccount($this->mid);
            if (!$card) {
                $this->mzError("请先绑定支付宝");
            }

            $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

            if ($exchange_balance < 0 || $exchange_balance < $withdraw_basenum || $exchange_balance % $withdraw_basenum != 0) {
                $this->mzError("只允许提现为{$withdraw_basenum}的倍数");
            }

            $result = D('ZyService')->applySpiltWithdraw($this->mid, $exchange_balance, $card['id']);

            if (true === $result) {
                $this->mzSuccess("提现申请成功，请等待审核");
            } else {
                switch ($result) {
                    case 1:
                        $info = "申请提现的收入余额不是系统指定的倍数，或小于0";
                        break;
                    case 2:
                        $info = "没有找到用户对应的提现银行卡/账户";
                        break;
                    case 3:
                        $info = "有未完成的提现记录，需要等待完成";
                        break;
                    case 4:
                        $info = "余额转冻结失败：可能是余额不足";
                        break;
                    case 5:
                        $info = "提现记录添加失败";
                        break;
                }
                $this->mzError($info);
            }
        }
    }

    //分成收入流水记录
    protected function spilt_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZySplit')->flowModel->where($map)->order('ctime DESC,id DESC')->findPage(12);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= D('ZySplit')->flowModel->where($map)->sum('num') ? : 0;
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //分成收入申请提现页面
    protected function spilt_take(){
        $card = D('ZyBcard')->getUserOnly($this->mid);
        if(!$card){
            $this->assign('isAdmin', 1);
            $this->assign('jumpUrl', U('classroom/User/card'));
            $this->error('请先绑定银行卡！'); exit;
        }
        //申请提现
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $num = intval($_POST['num']);
            $result = D('ZyService')->applySpiltWithdraw(
                $this->mid, $num, $card['id']);
            if(true === $result){
                $this->ajaxReturn(null, '', true);
            }else{
                $this->ajaxReturn(null, $result, false);
            }
            exit;
        }
        $ZyWithdraw = D('ZyWithdraw');
        $data = $ZyWithdraw->getUnfinished($this->mid,2);
        $recharge_intoConfig = model('Xdata')->get("admin_Config:rechargeIntoConfig");
        $withdraw_basenum = $recharge_intoConfig['withdraw_basenum'];

        $this->assign('withdraw_basenum', $withdraw_basenum);
        $this->assign('data', $data);
    }

    //分成收入申请提现列表页面
    protected function spilt_take_list(){
        if($_GET['id']){
            $id = intval($_GET['id']);
            $result = D('ZyService')->setWithdrawStatus($id, $this->mid, 4);
            if($result === true){
                $this->ajaxReturn(null, null, true);
            }else{
                $this->ajaxReturn(null, $result, false);
            }
            exit;
        }

        $map['uid']   = $this->mid;
        $map['wtype'] = 2;

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }

        $data = D('ZyWithdraw')->order('ctime DESC, id DESC')
            ->where($map)->findPage(12);

        $total= D('ZyWithdraw')->where(array('uid'=>$this->mid,'status'=>2,'wtype'=>2))->sum('wnum');
        $this->assign('data', $data);
        $this->assign('total', $total ? : 0);
    }

    //积分
    public function credit(){
        $credit = M('credit_user')->where('uid = '.$this->mid)->find();
        $credit['score'] = $credit['score'] ? $credit['score'] : 0;
        $this->assign('userLearnc', $credit);

        //选择模版
        $tab = intval($_GET['tab']);
        $tpls = array('index','income','pay','take_list','take','recharge','integral_list','exchange_record');
        if(!isset($tpls[$tab])) $tab = 0;
        $method = 'credit_'.$tpls[$tab];
        if(method_exists($this, $method)){
            $this->$method();
        }

        if($tab == 0){
            $account['learn'] = D('ZyLearnc')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
            $account['split'] = D('ZySplit')->where(['uid'=>$this->mid])->getField('balance') ? : 0;
            $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
            $sple_score = array_filter(explode(':',$split_score['sple_score']))[1]/array_filter(explode(':',$split_score['sple_score']))[0];

            $this->assign('sple_score',$sple_score);
            $this->assign('split_score_pro',$split_score['sple_score']);
            $this->assign('account',$account);
        }

        $this->assign('tab', $tab);
        if($this->is_wap){
            $templateFile = "credit/{$tpls[$tab]}_w3g";
            echo $this->fetch($templateFile,'utf-8','text/html',true);
        }else{
            $this->display("credit/{$tpls[$tab]}");
        }
    }

    //充值积分
    public function rechargeScore(){
        if(!$this->mid){
            $this->mzError('请先登录');
        }
        $type = t($_POST['re_type']);
        $exchange_score = intval(t($_POST['exchange_score']));

        //系统设置的计算分成、余额与积分的比例值
        $split_score = model('Xdata')->get("admin_Config:rechargeIntoConfig");
        $sple_score = array_filter(explode(':',$split_score['sple_score']))[1]/array_filter(explode(':',$split_score['sple_score']))[0];

        //需要扣除的余额/收入
        $exchange_balance = $exchange_score / $sple_score;

        if($type == 'lcnpay'){
            $balance = D('ZyLearnc')->where(['uid'=>$this->mid])->getField('balance');

            if($exchange_score < 1){
                $this->mzError('充值的数量最少为1');
            }

            if(!floatval($balance)){
                $this->mzError('您暂无可充值的余额');
            }
            unset($balance);

            if(!D('ZyLearnc')->isSufficient($this->mid,$exchange_balance)){
                $this->mzError('您的余额不够此次充值的数量');
            }

            //扣除账号余额
            $consume = D('ZyLearnc')->consume($this->mid,$exchange_balance);
            if(!$consume){
                $this->mzError('出错了，请稍后再试');
            }

            $lnc_flow = D('ZyLearnc')->addFlow($this->mid,0,$exchange_balance,'充值为积分：'.$exchange_score,'','credit_user_flow');

            //添加积分并加相关流水
            $credit = model('Credit')->recharge($this->mid,$exchange_score);
            if($credit){
                $credit_flow = model('Credit')->addCreditFlow($this->mid,1,$exchange_score,$lnc_flow,'zy_split_balance','余额充值积分：'.$exchange_score);
                D('ZyLearnc')->flowModel->where(['id'=>$lnc_flow])->save(['rel_id'=>$credit_flow]);

                $this->mzSuccess("充值成功");
            } else {
                $this->mzError("充值失败");
            }
        } else if($type == 'spipay'){
            $balance = D('ZySplit')->where(['uid' => $this->mid])->getField('balance');

            if ($exchange_score < 1) {
                $this->mzError('充值的数量最少为1');
            }

            if (!floatval($balance)) {
                $this->mzError('您暂无可充值的收入');
            }
            unset($balance);

            if (!D('ZySplit')->isSufficient($this->mid, $exchange_balance)) {
                $this->mzError('您的收入余额不够此次充值的数量');
            }

            //扣除分成收入
            $consume = D('ZySplit')->consume($this->mid, $exchange_balance);
            if (!$consume) {
                $this->mzError('出错了，请稍后再试');
            }

            //加相关分成扣除流水
            $split_flow = D('ZySplit')->addFlow($this->mid, 0, $exchange_balance, '充值为积分：' . $exchange_score, '', 'credit_user_flow');

            //添加积分并加相关流水
            $credit = model('Credit')->recharge($this->mid, $exchange_score);
            if ($credit) {
                $credit_flow = model('Credit')->addCreditFlow($this->mid, 1, $exchange_score, $split_flow, 'zy_split_balance', '收入充值积分：' . $exchange_score);
                D('ZySplit')->flowModel->where(['id' => $split_flow])->save(['rel_id' => $credit_flow]);

                $this->mzSuccess("充值成功");
            } else {
                $this->mzError("充值失败");
            }
        }
    }

    //积分流水记录
    protected function credit_integral_list(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = M('credit_user_flow')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        foreach($data['data'] as $key=>$value){
            switch ($value['type']){
                case 0:$data['data'][$key]['type'] = "消费";break;
                case 1:$data['data'][$key]['type'] = "充值";break;
                case 2:$data['data'][$key]['type'] = "冻结";break;
                case 3:$data['data'][$key]['type'] = "解冻";break;
                case 4:$data['data'][$key]['type'] = "冻结扣除";break;
                case 5:$data['data'][$key]['type'] = "分成收入";break;
                case 6:$data['data'][$key]['type'] = "增加积分";break;
                case 7:$data['data'][$key]['type'] = "扣除积分";break;
            }
        }
        $total= M('credit_user_flow')->where($map)->sum('num') ? : 0;
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //积分充值记录
    protected function credit_recharge(){
        $map = array('uid'=>$this->mid);  //获取用户id

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $map['type'] = 1;
        $data = D('credit_user_flow')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        foreach($data['data'] as $key=>$value){
            $data['data'][$key]['type'] = "充值";
        }
        $total= D('credit_user_flow')->where($map)->sum('num') ? : 0;
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //积分营收记录
    protected function credit_income(){
        $map = array('muid'=>$this->mid);

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $data = D('ZyOrder')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        $total= D('ZyOrder')->where(array('muid'=>$this->mid))->sum('user_num') ? : 0;
        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //积分支付记录
    protected function credit_pay(){
        $map = array('uid'=>$this->mid);

        $st = strtotime($_GET['st'])+0;
        $et = strtotime($_GET['et'])+0;
        if(!$st) $_GET['st'] = '';
        if(!$et) $_GET['et'] = '';

        if($_GET['st']){
            $map['ctime'][] = array('gt', $st);
        }
        if($_GET['et']){
            $map['ctime'][] = array('lt', $et);
        }
        $map['type'] = array('eq',0);
//        $data = D('ZyOrder')->where($map)->order('ctime DESC,id DESC')->findPage(12);
//        $total= D('ZyOrder')->where(array('uid'=>$this->mid))->sum('price') ? : 0;

        $data = D('credit_user_flow')->where($map)->order('ctime DESC,id DESC')->findPage(12);
        foreach($data['data'] as $key=>$val){
            $data['data'][$key]['goods_id'] = model('GoodsOrder')->where('id='.$val['rel_id'])->getField('goods_id');
        }
        $total= D('credit_user_flow')->where($map)->sum('num') ? : 0;

        $this->assign('data', $data);
        $this->assign('total', $total);
    }

    //积分兑换记录
    protected function credit_exchange_record(){
        $uid = intval($this->mid);
        $order = "ctime DESC";
        $goods = model('GoodsOrder')->getUserGoodsList($uid,$order);

        $this->assign("data", $goods);
        $this->assign("goods", $goods['data']);
    }

    //银行卡管理方法
    public function card(){
        $data = D('ZyBcard')->getUserOnly($this->mid,0);

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $set['uid'] = $this->mid;

            $card_count = D('ZyBcard')->where(array('uid'=>$this->mid,'is_school'=>0,'accounttype'=>['neq','alipay']))->field('id')->count();
            if($card_count >= 10){
                $this->ajaxReturn(null, '最多绑定10张银行卡', false);
            }
            $set['account'] = t($_POST['account']);
            $set['accountmaster'] = t($_POST['accountmaster']);
            $set['accounttype'] = t($_POST['accounttype']);
            $set['bankofdeposit'] = t($_POST['bankofdeposit']);
            $set['tel_num'] = t($_POST['tel_num']);
            $set['location'] = t($_POST['city_names']);
            $set['province'] = intval($_POST['province']);
            $set['area'] = intval($_POST['area']);
            $set['city'] = intval($_POST['city']);
            $set['is_school'] = 0;

            $res = M("zy_bcard")->where(['account'=>$set['account'],'uid'=>$this->mid])->find();

            if ($res) {
                $this->ajaxReturn(null, '该账号已存在,请重新输入！', false);
            }
            if(D('ZyBcard')->add($set)){
                $this->ajaxReturn(null, '添加成功', true);
            }else{
                $this->ajaxReturn(null, '添加失败', false);
            }
            exit;
        }

        $this->assign('isEditCard', $data);
        if(!$data){
            $array = array(
                'account'  => '',
                'tel_num'  => '',
                'location' => '',
                'province' => 0,
                'city'     => 0,
                'area'     => 0,
                'accountmaster' => '',
                'accounttype'   => '',
                'bankofdeposit' => '',
            );
        }
        $this->assign('card_data', $data);
        $this->assign('banks', D('ZyBcard')->getBanks());
        $this->display();
    }

    public function doBondUncard(){
        $id = intval($_POST['id']);

        $bond = D('ZyBcard')->where(['id'=>$id,'uid'=>$this->mid,'accounttype'=>['neq','alipay']])->delete();

        if($bond){
            $this->ajaxReturn([],'解绑成功',1);
        }else{
            $this->ajaxReturn([],'解绑失败',0);
        }
    }

    //优惠券管理方法
    public function videoCoupon(){
        $map = array('uid'=>$this->mid,'type'=>1,'coupon_type'=>0);
        $data = model('Coupon')->getUserCouponList($map);
        $time = time();
        $count['all'] = $data['count'];
        $count['use'] = 0;
        $count['used'] = 0;
        $count['past'] = 0;
        foreach ($data['data'] as $key => $val) {
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $data['data'][$key]['price'] = floor($val['price']);
            $data['data'][$key]['maxprice'] = floor($val['maxprice']);
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['past'] = 1;
                    $count['past'] += 1;
                }else{
                    $count['use'] +=1;
                }
            }
            $data['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $data['data'][$key]['etime'] = date("Y.m.d",$val['etime']);

        }
        $count['used'] = $count['all']-$count['use']-$count['past'];
        $this->assign('data', $data['data']);
        $this->assign('count', $count);
        $this->display();
    }
    //打折卡管理方法
    public function discount(){
        $map = array('uid'=>$this->mid,'type'=>2,'coupon_type'=>0);
        $data = model('Coupon')->getUserCouponList($map);
        $time = time();
        $count['all'] = $data['count'];
        $count['use'] = 0;
        $count['used'] = 0;
        $count['past'] = 0;
        foreach ($data['data'] as $key => $val) {
            if($val["status"] == 0 && $val["etime"] - time() <= 86400*2){
                $data['data'][$key]['is_out_time'] = true;
            }
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['status'] = -1;
                    $count['past'] += 1;
                }else{
                    $count['use'] +=1;
                }
            }
        }
        $count['used'] = $count['all']-$count['use']-$count['past'];
        $this->assign('data', $data['data']);
        $this->assign('count', $count);
        $this->display();
    }
    //会员卡管理方法
    public function vipCard(){
        $map = array('uid'=>$this->mid,'type'=>3,'coupon_type'=>0);
        $data = model('Coupon')->getUserCouponList($map);
        $time = time();
        $count['all'] = $data['count'];
        $count['use'] = 0;
        $count['used'] = 0;
        $count['past'] = 0;
        foreach ($data['data'] as $key => $val) {
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $data['data'][$key]['vip_grade'] = M('user_vip')->where(array('id'=>$val['vip_grade']))->getField('title');
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['status'] = -1;
                    $count['past'] += 1;
                }else{
                    $count['use'] +=1;
                }
            }
            $data['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $data['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $count['used'] = $count['all']-$count['use']-$count['past'];
        $this->assign('data', $data['data']);
        $this->assign('count', $count);
        $this->display();
    }
    //充值卡管理方法
    public function rechargeCard(){
        $map = array('uid'=>$this->mid,'type'=>4,'coupon_type'=>0);
        $data = model('Coupon')->getUserCouponList($map);
        $time = time();
        foreach ($data['data'] as $key => $val) {
            if($val["status"] == 0 && $val["etime"] - time() <= 86400*2){
                $data['data'][$key]['is_out_time'] = true;
            }
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['status'] = -1;
                }
            }
            $data['data'][$key]['recharge_price'] = floor($val['recharge_price']);
            $data['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $data['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $this->assign('data', $data['data']);
        $this->display();
    }
    //课程卡管理方法
    public function courseCard(){
        $map = array('uid'=>$this->mid,'type'=>5,'coupon_type'=>0);
        $data = model('Coupon')->getUserCouponList($map);
        $time = time();
        $count['all'] = $data['count'];
        $count['use'] = 0;
        $count['used'] = 0;
        $count['past'] = 0;
        foreach ($data['data'] as $key => $val) {
            if($val["status"] == 0 && $val["etime"] - time() <= 86400*2){
                $data['data'][$key]['is_out_time'] = true;
            }
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['status'] = -1;
                    $count['past'] += 1;
                }else{
                    $count['use'] +=1;
                }
            }
            if($val['video_type'] == 3){
                $data['data'][$key]['video_name'] = D('Album')->getAlbumTitleById($val['video_id']);
                $data['data'][$key]['vtype'] = '班级';
            }else{
                $data['data'][$key]['video_name'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                if($val['video_type'] == 1){
                    $data['data'][$key]['vtype'] = '点播';
                }else{
                    $data['data'][$key]['vtype'] = '直播';
                }
            }
            $data['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $data['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $count['used'] = $count['all']-$count['use']-$count['past'];
        $this->assign('data', $data['data']);
        $this->assign('count', $count);
        $this->display();
    }
    //卡券状态筛选
    public function choiceCard(){
        $type = intval($_POST['type']);
        $status = intval($_POST['orderby'])-1;

        $map['uid'] = $this->mid;
        $map['coupon_type'] = 0;
        $time = time();
        if($status == 2){
            $map['etime'] = array('lt',$time);
        }else if($status == '0'){
            $map['etime'] = array('gt',$time);
        }
        if($type > 0){
            switch($type){
                case 1:
                    $map['type'] = $type;
                    break;
                case 2:
                    $map['type'] = $type;
                    break;
                case 3:
                    $map['type'] = $type;
                    break;
                case 4:
                    $map['type'] = $type;
                    break;
                case 5:
                    $map['type'] = $type;
                    break;
                default;
            }
        }
        $data = model('Coupon')->getUserCouponList($map,$status);
        foreach ($data['data'] as $key => $val) {
            if($val["status"] == 0 && $val["etime"] - time() <= 86400*2){
                $data['data'][$key]['is_out_time'] = true;
            }
            $data['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $data['data'][$key]['price'] = floor($val['price']);
            $data['data'][$key]['maxprice'] = floor($val['maxprice']);
            $data['data'][$key]['vip_grade'] = M('user_vip')->where(array('id'=>$val['vip_grade']))->getField('title');
            $data['data'][$key]['recharge_price'] = floor($val['recharge_price']);
            $time = time();
            if($val['status'] == 0){
                if($val['etime'] < $time){
                    $data['data'][$key]['status'] = -1;
                }
            }
            if($val['video_type'] == 3){
                $data['data'][$key]['video_name'] = D('Album')->getAlbumTitleById($val['video_id']);
                $data['data'][$key]['vtype'] = '班级';
            }else{
                $data['data'][$key]['video_name'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                if($val['video_type'] == 1){
                    $data['data'][$key]['vtype'] = '点播';
                }else{
                    $data['data'][$key]['vtype'] = '直播';
                }
            }
            $data['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $data['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $this->assign('listData', $data['data']);
        $this->assign('data', $data);

        $html = $this->fetch('coupon_list');
        $data['data']=$html;
        exit( json_encode($data) );
    }
    //使用/领取 卡券页面
    public function exchangeCard(){
        $id = intval($_GET['id']);
        $uid = $this->mid;
        if($id){
            $coupon = model('Coupon')->canUse($id,$uid);
            if($coupon){
                $coupon['stime'] = date("Y.m.d",$coupon['ctime']);
                $end_time = $coupon['ctime']+($coupon['exp_date']*3600*24);
                $coupon['etime'] = date("Y.m.d",$end_time);
            }else{
                $this->error('该卡券数据有误');
            }
            $this->assign('coupon', $coupon);
        }
        $this->display();
    }
    //查询卡券
    public function getExchangeCard(){
        $code = $_POST['code'];
        $map['code'] = $code;
        $map['coupon_type'] = 1;
        $coupon = model('Coupon')->where($map)->find();
        $coupon['stime'] = date("Y.m.d",$coupon['ctime']);
        $end_time = $coupon['ctime']+($coupon['exp_date']*3600*24);
        $coupon['etime'] = date("Y.m.d",$end_time);
        $coupon['school_title'] = model('School')->where(['id'=>$coupon['sid']])->getField('title') ?:'';
        $coupon['recharge_price'] = floor($coupon['recharge_price']);
        $coupon['vip_grade'] = M('user_vip')->where(array('id'=>$coupon['vip_grade']))->getField('title');
        $coupon['price'] = floor($coupon['price']);
        $coupon['maxprice'] = floor($coupon['maxprice']);

        $this->assign('coupon', $coupon);
        $html = $this->fetch('exchangeCard_list');
        $coupon['data']=$html;
        echo json_encode($coupon);exit;
    }
    //使用卡券方法
    public function doExchange(){
        $id = intval($_GET['id']);
        $uid = $this->mid;
        if(!$this->is_pc){
            $coupon = model('Coupon')->canUse($id,$uid);
            $id = $coupon['coupon_id'];
        }
        $coupon = model('Coupon')->getCouponInfoById($id);
        if($coupon['type'] == 3){
            /*$map['uid'] = $uid;
            $data['vip_type'] = $coupon['vip_grade'];
            $time = time();
            $data['vip_expire'] = $coupon['vip_date']*3600*24 + $time;
            $res = model('Credit')->saveCreditUser($map,$data);*/

            $time = '+'.$coupon["vip_date"].' days';
            $res = D('ZyLearnc')->setVip($uid,$time,$coupon['vip_grade']);
            $url = U('classroom/User/vipCard');
        }else if($coupon['type'] == 4){
            $result = model('Credit')->recharge($uid,$coupon['recharge_price']);
            if($result == true){
                $res = model('Credit')->addCreditFlow($uid,1,$coupon['recharge_price']);
                $url = U('classroom/User/rechargeCard');
            }
        }
        model('Credit')->cleanCache($uid);
        if($res){
            $data['status'] = 1;
            $re = M('coupon_user')->where('cid='.$id)->save($data);
            if($re == true){
                $this->mzSuccess('兑换成功',$url);
            }
        }else{
            $this->mzError('兑换失败');
        }
    }
    //领取卡券方法
    public function convert(){
        $code = t($_POST['code']);
        $coupon = model('Coupon');
        $coupon->mid = $this->mid;
        $couponId = $coupon->where('code='.$code)->getField('id');
        $couponUserId = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$couponId,'status'=>0,'is_del'=>0,'etime'=>['gt',time()]))->getField('id');
        if(!$couponUserId){
            $res = $coupon->grantCouponByCode($code);
        }else{
            $res = true;
        }
        if($res == true){
            $this->mzSuccess('领取成功');
        }else{
            $this->mzError('领取失败');
        }

    }

    //删除兑换商品记录
    public function delGoodsOrder(){
        $id=intval($_POST['id']);
        $data['is_del']=1;
        $where=array(
            'id'=>$id,
            'uid'=>$this->mid
        );
        $res=model('GoodsOrder')->where($where)->save($data);
        if($res){
            echo 200;
            exit;
        }else{
            echo 500;
            exit;
        }
    }

    //查看更多
   public function getGoodsOrderList(){
       $uid = intval($this->mid);
       $order = "ctime DESC";
       $goods = model('GoodsOrder')->getUserGoodsList($uid,$order);

       $this->assign("data", $goods);
       $this->assign("goods", $goods['data']);
       $goods['data'] = $this->fetch('ajax_goodsOrder');
       echo json_encode($goods);
       exit;
    }
    //用户设置
    public function setInfo(){
        //用户信息
        $this->setUser();
        //认证
        $this->rz();
        //帐号绑定
        $bindData = array();
        Addons::hook('account_bind_after',array('bindInfo'=>&$bindData));
        $bindType = array();
        foreach($bindData as $k=>$rs) $bindType[$rs['type']] = $k;
        $verified_category = M("user_verified_category")->field("title,user_verified_category_id")->select();
        $this->assign("verified_category",$verified_category);
        $data['bindType']  = $bindType;
        $data['bindData']  = $bindData;
        $this->assign($data);
        $area = model('Area')->getAreaList(0);
        $this->assign('area',$area);
        $this->display();
    }
    //获取地区选择数据
    public function getAreaList(){
        $area = model('Area')->getAreaList($_POST['id']);
        echo json_encode(array('status'=>1,'data'=>$area));
        exit;
    }
    //获取地区信息
    public function getAreaInfo(){
        $province = model('Area')->getAreaById($_POST['province']);
        $city     = model('Area')->getAreaById($_POST['city']);
        $area     = model('Area')->getAreaById($_POST['area']);
        $data     = $province['title']." ".$city['title']." ".$area['title'];
        echo json_encode(array('status'=>1,'data'=>$data));
        exit;
    }
    //用户设置
    public function appcertific(){
        //用户信息
        $this->setUser();
        //认证
        $this->rz();
        //帐号绑定
        $bindData = array();
        Addons::hook('account_bind_after',array('bindInfo'=>&$bindData));
        $bindType = array();
        foreach($bindData as $k=>$rs) $bindType[$rs['type']] = $k;
        $verified_category = M("user_verified_category")->field("title,user_verified_category_id")->select();
        $this->assign("verified_category",$verified_category);
        $data['bindType']  = $bindType;
        $data['bindData']  = $bindData;
        $this->assign($data);
        $this->display();

    }


    public function getVerifyCategory(){
        $category = D('user_verified_category')->where('pid='.intval($_POST['value']))->findAll();
        $option = '';
        foreach($category as $k=>$v){
            $option .= '<option ';
            // if(intval($_POST['category_id'])==$v['user_verified_category_id']){
            //  $option[$v['pid']] .= 'selected';
            // }
            $option .= ' value="'.$v['user_verified_category_id'].'">'.$v['title'].'</option>';
        }
        echo $option;
    }

    public function saveUser(){

        //简介
        $save['intro'] = filter_keyword(t($_POST['intro']));
        //性别
        $save['sex']   = 1 == intval($_POST['sex']) ? 1 : 2;
        //位置信息
        $save['location'] = t($_POST['city_names']);
		//职业
		$save['profession'] = filter_keyword(t($_POST['profession']));
        //地区
//        $cityIds = t($_POST['city_ids']);
//        $cityIds = explode(',', $cityIds);
        $this->assign('isAdmin',1);
        $province = intval($_POST['province']);
        $city = intval($_POST['city']);
        $area = intval($_POST['area']);



        if(!$province || !$city) $this->error('请选择完整地区');
        isset($province) && $save['province'] = $province;
        isset($city) && $save['city'] = $city;
        isset($area) && $save['area'] = $area;

        //昵称
        $user = $this->get('user');
        $uname = t($_POST['uname']);
        $oldName = t($user['uname']);
        $save['uname'] = filter_keyword($uname);
        $res = model('Register')->isValidName($uname, $oldName);
        if(!$res) {
            $error = model('Register')->getLastError();
            return $this->ajaxReturn(null, model('Register')->getLastError(), $res);
        }
        //如果包含中文将中文翻译成拼音
        if ( preg_match('/[\x7f-\xff]+/', $save['uname'] ) ){
            //昵称和呢称拼音保存到搜索字段
            $save['search_key'] = $save['uname'].' '.model('PinYin')->Pinyin( $save['uname'] );
        } else {
            $save['search_key'] = $save['uname'];
        }
        $res = model('User')->where("`uid`={$this->mid}")->save($save);
        $res && model('User')->cleanCache($this->mid);

        $user_feeds = model('Feed')->where('uid='.$this->mid)->field('feed_id')->findAll();
        if($user_feeds){
            $feed_ids = getSubByKey($user_feeds, 'feed_id');
            model('Feed')->cleanCache($feed_ids,$this->mid);
        }
        $this->ajaxReturn(null, '', true);
    }

    protected function setUser(){
        $my_college = D('ZySchoolCategory')->getParentIdList($this->user['my_college']);
        $signup_college = D('ZySchoolCategory')->getParentIdList($this->user['signup_college']);
        $this->assign('user_show_type','user');
        $this->assign('my_college', $my_college?$my_college:'');
        $this->assign('signup_college', $signup_college?$signup_college:'');
    }

    //用户认证
    protected function rz(){
        $auType = model('UserGroup')->where('is_authenticate=1')->findall();
        $this->assign('auType', $auType);
        $verifyInfo = D('ZyTeacher','classroom')->where('uid='.$this->mid)->find();
        /*if($verifyInfo['identity_id']){
            $a = explode('|', $verifyInfo['identity_id']);
            foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['certification'] .= $attachInfo['save_name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }*/
        if($verifyInfo['attach_id']){
            $a = explode('|', $verifyInfo['attach_id']);
            foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['attachment'] .= $attachInfo['save_name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }
        /*if($verifyInfo['other_data']){
              $a = explode('|', $verifyInfo['other_data']);
              foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['other_data_list'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
              }
        }*/
        // 获取认证分类信息
        if(!empty($verifyInfo['user_verified_category_id'])) {
            $verifyInfo['category']['title'] = D('user_verified_category')->where('user_verified_category_id='.$verifyInfo['user_verified_category_id'])->getField('title');
        }

        switch ($verifyInfo['verified_status']) {
            case '1':
                $status = '<i class="ico-ok"></i>已认证 <!--<a href="javascript:void(0);" onclick="delverify()" style="color:#65addd">注销认证</a>-->';
                break;
            case '2':
                $status = '<i class="ico-wait"></i>已提交认证，等待审核';
                break;
            case '0':
                // 安全过滤
                $type = t($_GET['type']);
                if($type == 'edit'){
                    $status = '<i class="ico-no"></i>未通过认证，请修改资料后重新提交';
                    $this->assign('edit',1);
                    $verifyInfo['identityIds'] = str_replace('|', ',', substr($verifyInfo['identity_id'],1,strlen($verifyInfo['identity_id'])-2));
                    $verifyInfo['attachIds'] = str_replace('|', ',', substr($verifyInfo['attach_id'],1,strlen($verifyInfo['attach_id'])-2));
                    $verifyInfo['other_data_ids'] = str_replace('|', ',', substr($verifyInfo['other_data'],1,strlen($verifyInfo['other_data'])-2));
                }else{
                    $status = '<i class="ico-no"></i>未通过认证，请修改资料后重新提交 <a style="color:#65addd" href="'.U('classroom/User/setInfo',array('type'=>'edit', 'tab'=>4)).'">修改认证资料</a>';
                }
                break;
            default:
                //$verifyInfo['usergroup_id'] = 5;
                $status = '未认证';
                break;
        }
        //附件限制
        $attach = model('Xdata')->get("admin_Config:attachimage");
        $imageArr = array('gif','jpg','jpeg','png','bmp');
        foreach($imageArr as $v){
            if(strstr($attach['attach_allow_extension'],$v)){
                $imageAllow[] = $v;
            }
        }
        $attachOption['attach_allow_extension'] = implode(', ', $imageAllow);
        $attachOption['attach_max_size'] = $attach['attach_max_size'];
        $this->assign('attachOption',$attachOption);

        // 获取认证分类
        $category = D('user_verified_category')->findAll();
        foreach($category as $k=>$v){
            $option[$v['pid']] .= '<option ';
            if($verifyInfo['user_verified_category_id']==$v['user_verified_category_id']){
                $option[$v['pid']] .= 'selected';
            }
            $option[$v['pid']] .= ' value="'.$v['user_verified_category_id'].'">'.$v['title'].'</option>';
        }
        if($verifyInfo){
            $verifyInfo['school'] = model('School')->getSchooldStrByMap(array('id'=>$verifyInfo['mhm_id']),'title');
        }
        //获取认证讲师分类
        $cate = M('zy_teacher_category')->where(['zy_teacher_category_id'=>['in',trim($verifyInfo['fullcategorypath'],',')]])->field('title')->findAll();
        $cate = getSubByKey($cate,'title');
        $verifyInfo['category']  = implode('---',$cate);
        $verifyInfo['fullcategorypath'] = trim($verifyInfo['fullcategorypath'],',');

        $this->assign('option', json_encode($option));
        $this->assign('options', $option);
        $this->assign('category', $category);
        $this->assign('status' , $status);
        $this->assign('verifyInfo' , $verifyInfo);

        $user = model('User')->getUserInfo($this->mid);
        $province = model('Area')->getAreaById($user['province']);
        $city     = model('Area')->getAreaById($user['city']);
        $area     = model('Area')->getAreaById($user['area']);
        $user['position']  = $province['title']." ".$city['title']." ".$area['title'];
        $this->assign('user' , $user);

        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if(!empty($userCategory)) {
            foreach($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array($this->mid));
    }



    /**
     * 修改班级内容
     */
    public function album_edit(){
   		if(!intval($_GET['id'])){
   			$this->assign('isAdmin',1);
   			$this->error('输入参数出错!');
   		}
    	$get = $_GET;
    	$data = D("Album","classroom")->getAlbumById($_GET['id']);

		//print_r($data);
		$data['album_video']      = trim( D('Album','classroom')->getVideoId($data['id']) , ',');
    	$data['fullcategorypath'] = trim($data['fullcategorypath'],',');


		//print_r($data);
    	$this->assign($data);

    	$this->display();
    }

    /**
     * 保存班级修改
     */
    public function doAlbum_edit(){
		//必须要登录之后才能修改
		if(!intval($this->mid)){
			$this->mzError("未登录,不能修改!");
		}

		$data['id'] 		      = intval($_POST['id']);
		$data['album_title']      = t($_POST['album_title']);
		$data['album_intro']      = t($_POST['album_intro']);
		$data['fullcategorypath'] = t($_POST['fullcategorypath']);
		$data['cover']            = t($_POST['cover_ids']);
		$data['uctime']           = t($_POST['uctime'])?t($_POST['uctime']):0;
		$data['uctime']           = strtotime($data['uctime']);
		$album_tag 			      = explode(',',t($_POST['album_tag']));

		if(!$data['id']){
			$this->mzError("班级信息错误!");
		}
		//要检查是不是自己的
		$count = M('Album')->where(array('uid'=>intval($this->mid),'id'=>$data['id']))->count();
		if(!$count){
			$this->mzError("没有权限修改此班级,可能不是你创建的!");
		}
		//数据校验
		if(!trim($data['album_title'])){
			$this->mzError("班级标题不能为空!");
		}
		if(!trim($data['album_intro'])){
			$this->mzError("班级简介不能为空!");
		}
		if(!trim($data['fullcategorypath'])){
			$this->mzError("班级分类不能为空!");
		}
		if(!trim($data['cover'])){
			$this->mzError("请上传封面!");
		}

		if(empty($album_tag)){
			$this->mzError("班级标签不能为空!");
		}
		if(!$data['uctime']){
			$this->mzError("请选择下架时间!");
		}
		if($data['uctime'] <= time()){
			$this->mzError("下架时间应该大于当前时间!");
		}



		$i = M('Album')->where("id = {$data['id']}")->data($data)->save();
		if($i !== false){
			//先删除tag
			model('Tag')->setAppName('classroom')->setAppTable('album')->deleteSourceTag($data['id']);
			//再创建tag
			$tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('album')->addAppTags($data['id'],$album_tag);

			$_data['str_tag']   = implode(',' ,getSubByKey($tag_reslut,'name'));
			$_data['album_tag'] = ','.implode(',',getSubByKey($tag_reslut,'tag_id')).',';
			$_data['id']        = $data['id'];

			M('Album')->save($_data);
			$this->mzSuccess('修改成功!',U('classroom/Home/album'));
		}else{
			$this->mzError("修改失败!");
		}
    }

    public function sendEmailActivate(){
        $time = time();
        if(session('send_time') > $time){
            exit('请勿重复操作！');
        }
        $user = $this->get('user');
        $code = md5(md5($time.get_client_ip().$user['email']));
        session('email_activate', $code);
        session('send_time', $time+90);
        $url  = U('classroom/User/emailActivate', array('activeCode'=>$code));
        $body = "<p>{$user['uname']}，你好</p>
<p style=\"color:#666\">欢迎加入Eduline，请点击下面的链接地址来验证你的邮箱：</p>
<p><a href=\"{$url}\" target=\"_blank\" style=\"color:#06c\">$url</a></p>
<p style=\"color:#666\">如果链接无法点击，请将链接复制到浏览器的地址栏中进行访问。</p>";
        $res = model('Mail')->send_email($user['email'], '[Eduline] Email地址验证', $body);
        exit($res?'ok':'邮件投递失败！');
    }

    public function emailActivate(){
        $this->assign('isAdmin', 1);
        $this->assign('jumpUrl', U('classroom/User/setInfo'));
        $code = $_GET['activeCode'];
        if(!$code || $code!=session('email_activate')){
            $this->error('操作异常');
        }

        session('email_activate', null);
        session('send_time', null);
        $res = model('User')->where(array('uid'=>$this->mid))->save(array(
            'mail_activate' => 1,
        ));
        $res && model('User')->cleanCache($this->mid);
        if($res){
        	$this->assign('isAdmin',1);
            $this->success('Email地址验证成功！');
        }
    }

    //设置邮箱
    public function setEmail(){
        $email = $_POST['email'];
        $reg  = model('Register');
        $user = $this->get('user');
        if(!$reg->isValidEmail($email, $user['email'])){
            exit($reg->getLastError());
        }
		$save = array(
            'email' => $email,
            'mail_activate' => 0,
        );
		if($user['login']==$user['email']){
			$save['login'] = $email;
		}
        $res = model('User')->where(array('uid'=>$this->mid))->save($save);
        $res && model('User')->cleanCache($this->mid);
        if(false !== $res){
            exit('ok');
        }else{
            exit('Email修改失败');
        }
    }

    public function sendCode(){
        $time = time();
        $phoneCodes = session('phone_code');
        $user = $this->get('user');
        $old  = $user['phone'];
        if(!empty($_POST['phone'])){
            $phone = $_POST['phone'];
            if(!preg_match('/^1[3458]\d{9}$/', $phone)){
                exit('请输入正确的手机号码');
            }
            if($phone == $old) exit('输入的手机号和之前的相同');
            $id = model('User')->where(array('phone'=>$phone))->getField('uid');
            if($id > 0) exit('该手机号已被其他用户使用');
            $phoneCodes[$phone]['setd'] = true;
        }else{
            $phone = $old;
            if(!$phone) exit('还未设置手机号');
            $phoneCodes[$phone]['setd'] = false;
        }

        if($phoneCodes[$phone]['send_time'] > $time){
            exit('请勿频繁获取短信验证码');
        }

        $phoneCodes[$phone]['err'] = 0;
        $phoneCodes[$phone]['send_time'] = $time+90;

        $code = rand(100000, 999999);
        $phoneCodes[$phone]['code'] = md5($code);
        $res = model('Sms')->send($phone, $code);
        if($res){
            session('phone_code', $phoneCodes);
            exit('ok');
        }else{
            exit('发送失败');
        }
    }

    public function checkCode(){
        $time = time();
        $phoneCodes = session('phone_code');
		//print_r($phoneCodes);
        $user = $this->get('user');
        $old  = $user['phone'];
        $phone = empty($_POST['phone'])?$old:$_POST['phone'];
        $code  = md5($_POST['code']);

        //常规检查
        if(!empty($_POST['phone'])){
            $b1 = !preg_match('/^1[3458]\d{9}$/', $phone);
            $b2 = $phone == $old;
            $id = model('User')->where(array('phone'=>$phone))->getField('uid');
            $b3 = $id > 0;
            $b4 = $old&&empty($phoneCodes[$old]);
            if($b1 || $b2 || $b3 || $b4){
                exit('操作异常');
            }
        }

        //没有获取验证码
        if(!isset($phoneCodes[$phone])){
            exit('请先获取短信验证码');
        }
        $phoneCode = $phoneCodes[$phone];
        //允许尝试4次验证码
        if($code != $phoneCode['code']){
            $phoneCode['err'] += 1;
            if($phoneCode['err'] >= 4){
                $phoneCodes[$phone] = null;
                session('phone_code', $phoneCodes);
                exit('请重新获取短信验证码');
            }else{
                $phoneCodes[$phone] = $phoneCode;
                session('phone_code', $phoneCodes);
                exit('验证码错误，您还可以尝试'.(4-$phoneCode['err']).'次');
            }
        }

        if($phoneCode['setd']){
			$save = array(
                'phone' => $phone,
                'phone_activate' => 1,
            );
			if($user['login'] == $user['phone']){
				$save['login'] = $phone;
			}
            $res = model('User')->where(array('uid'=>$this->mid))->save($save);
            $res && model('User')->cleanCache($this->mid);
            if(false !== $res){
        		session('phone_code', null);
                exit('ok');
            }else{
                exit('手机号更改失败');
            }
        }
        exit('ok');
    }


	/**
	 * 邀请页面 - 页面
	 * @return void
	 */
	public function invite()
	{
		if( !CheckPermission('core_normal','invite_user') ){

				$this->assign('isAdmin',1);
			$this->error('对不起，您没有权限进行该操作！');
		}
		// 获取选中类型
		$type = isset($_GET['type']) ? t($_GET['type']) : 'link';
		$this->assign('type', $type);
		// 获取不同列表的相关数据
		switch($type) {
			case 'email':
				$this->_getInviteEmail();
				break;
			case 'link':
				$this->_getInviteLink();
				break;
		}
		$userInfo = model('User')->getUserInfo($this->mid);
		$this->assign('invite', $userInfo);
		$this->assign('config', model('Xdata')->get('admin_Config:register'));
		// 获取后台积分配置
		$creditRule = model('Credit')->getCreditRules();
		foreach ($creditRule as $v) {
			if ($v['name'] === 'core_code') {
				$applyCredit = abs($v['score']);
				break;
			}
		}
		$this->assign('applyCredit', $applyCredit);
		// 后台配置邀请数目
		$inviteConf = model('Xdata')->get('admin_Config:invite');
		$this->assign('emailNum', $inviteConf['send_email_num']);

		$this->display();
	}

    public function doFollow(){
        $uid=intval($_POST['uid']);//获取用户id
        if(empty($uid)){
            echo "关注失败！";
            exit;
        }
        //先查询是否已关注
        $map=array(
            'uid'=>$this->mid,
            'fid'=>$uid
        );
        $res=D('UserFollow')->where($map)->find();
        if($res){
            echo "您已关注对方！";
            exit;
        }
        $result=D('UserFollow')->add($map);
        if($result){
            echo "200";
            exit;
        }else{
            echo "关注失败！";
            exit;
        }

    }

	/**
	 * 邮箱邀请相关数据
	 * @return void
	 */
	private function _getInviteEmail()
	{
		// 获取邮箱后缀
		$config = model('Xdata')->get('admin_Config:register');
		$this->assign('emailSuffix', $config['email_suffix']);
		// 获取已邀请用户信息
		$inviteList = model('Invite')->getInviteUserList($this->mid, 'email');
		$this->assign('inviteList', $inviteList);
		// 获取有多少可用的邀请码
		$count = model('Invite')->getAvailableCodeCount($this->mid, 'email');
		$this->assign('count', $count);
	}

	/**
	 * 链接邀请相关数据
	 * @return void
	 */
	private function _getInviteLink()
	{
		// 获取邀请码列表
		$codeList = model('Invite')->getInviteCode($this->mid, 'link');
		$this->assign('codeList', $codeList);
		// 获取已邀请用户信息
		$inviteList = model('Invite')->getInviteUserList($this->mid, 'link');
		$this->assign('inviteList', $inviteList);
		// 获取有多少可用的邀请码
		$count = model('Invite')->getAvailableCodeCount($this->mid, 'link');
		$this->assign('count', $count);
	}

    /**
     * 教师课程主页
     * @return void
     */
    public function teacherVideo(){
        $this->assign('live_opt',$this->base_config['live_opt']);
        $this->display();
    }

    /**
     * 教师的录播课程
     * @return void
     */
    public function getTeacherVideo(){
        $uid        = intval($this->mid);
        $limit      = 9;
        $teacher_id = M('zy_teacher')->where('uid = '.$uid)->getField('id');
        $map['_string'] =  " uid = {$uid} OR teacher_id = $teacher_id";
        $map['is_del'] = 0;
        $map['type'] = 1;
        $data = M('zy_video')->where($map)->order('utime desc')->findPage($limit);

        //判断课程课时是否存在
        foreach($data['data'] as $key=>$val){
            $data['data'][$key]['section_count'] = M('zy_video_section')->where(['vid'=>$val['id'],'pid'=>['gt',0],'is_activity'=>['in','2,3']])->count();
        }
        //把数据传入模板
        $this->assign('data',$data['data']);

        if($this -> is_pc)
        {
            $this -> assign('pc',1);
        }

        //取得数据
        $data['data'] = $this->fetch('_teacher_video');
        echo json_encode($data);exit;
    }

    /**
     * 教师的直播课程
     * @return void
     */
    public function getTeacherLive(){
        $limit      = 9;

        $tid = M('ZyTeacher')->where("uid=".$this->mid)->getField('id');

        $vmap['is_del']         = 0;
        $vmap['is_activity']    = 1;
        $vmap['type']           = 2;
        $vmap['teacher_id']      = $tid;
        $vmap['listingtime']    = array('lt', time());
        $vmap['uctime']         = array('gt', time());

        $live_data = model('Live')->where($vmap)->order('ctime desc')->field('id,video_title,cover,live_type,video_order_count,video_collect_count')->select();

        $map['is_del']      = 0;
        $map['is_active']   = 1;
        $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'id'))),',');
        $map['live_id'] = ['in',$live_id];
        //$map['type']        = $this->base_config['live_opt'];
        $field = 'id,subject,roomid,startDate,invalidDate,teacherJoinUrl,studentJoinUrl,teacherToken,assistantToken,studentClientToken,live_id';
        $live_data = model('Live')->liveRoom->where($map)->order('invalidDate asc')->field($field)->select();

        $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
        $vmap['id'] = ['in',$live_id];
        $data = M('zy_video')->where($vmap)->field('id,video_title,cover,live_type,video_order_count,video_collect_count')->findPage($limit);

        //把数据传入模板
        $this->assign('data',$data['data']);
        $this->assign('live_opt',$this->base_config['live_opt']);

        if($this ->is_pc)
        {
            $this->assign('pc',1);
        }

        //取得数据
        $data['data'] = $this->fetch('_teacher_live');
        echo json_encode($data);exit;
    }

    //教师的面授课程
    public function getTeacherFace(){
        $old_num_list="";
        $mid = $this->mid;
        $tid =  M('zy_teacher') ->where('uid ='.$mid) ->getField('id');
        $course_list=M("zy_teacher_course")->where(array('course_teacher'=>$tid,'is_del'=>0))->order('ctime desc')->findAll();
        foreach ($course_list as $key => $value) {
            $num=$key+1;
            $old_num_list.=$num."-".$value["course_id"]."-0,";
        }
        $this->assign("course_list",$course_list);
        $this->assign("old_num_list",$old_num_list);
        //取得数据
        $data['data'] = $this->fetch('_teacher_face');
        echo json_encode($data);exit;
    }

    /**
     * 教师的录播课程
     * @return void
     */
    public function getTeacherLineClass(){
        $uid        = intval($this->mid);
        $limit      = 9;
        $teacher_id = M('zy_teacher')->where('uid = '.$uid)->getField('id');
        $map['_string'] =  " course_uid = {$uid} OR teacher_id = $teacher_id";
        $map['is_del'] = 0;
        $data = M('zy_teacher_course')->where($map)->order('ctime desc')->findPage($limit);

        //把数据传入模板
        $this->assign('data',$data['data']);

        if($this -> is_pc)
        {
            $this -> assign('pc',1);
        }

        //取得数据
        $data['data'] = $this->fetch('_teacher_lineClass');
        echo json_encode($data);exit;
    }

    //获取光慧直播的公共参数
    private function _ghdata(){
        $data['customer']      = $this->gh_config['customer'];
        $data['timestamp']     = time() * 1000;
        $str = md5( $data['customer'] . $data['timestamp'] . $this->gh_config['secretKey'] );
        $data['s']   = substr($str , 0 , 10 ) . substr($str ,-10 );
        $data['fee'] = 0;
        return $data;
    }

    //上传直播课程页面
    public function uploadLive(){
        if($this->base_config['live_opt'] == 1){
            $data['data'] = $this->fetch('_upload_zshdlive');
        }else if($this->base_config['live_opt'] == 2){
            $data['data'] = '还未开通';
        }elseif($this->base_config['live_opt'] == 3){
            $data['data'] = $this->fetch('_upload_live');
        }
        exit( json_encode($data) );
    }
    /**
     * 上传直播课程-光慧
     * @return void
     */
    public function doUploadZshdLive(){
        $startDate = strtotime($_POST['startDate']);
        $invalidDate = strtotime($_POST['invalidDate']);
        $live_time = trim($_POST['startDate']);
        $liveTime = substr($live_time,11,5);
        $newTime = time();//当前时间加两个小时的时间+7200
        if(empty($_POST['live_cover_ids'])){$this->mzError('封面照还没有上传');}
        if(empty($_POST['myAdminLevelhidden'])){$this->mzError('请选择分类');}
        if($startDate < $newTime ){$this->mzError('开始时间必须大于当前时间');}
        if($invalidDate < $startDate){$this->mzError('结束时间不能小于开始时间');}
        $studentToken = rand(111111,999999);

        if(t($_POST['id'])) {//修改
            $map['SDK_ID'] = t($_POST['id']);
            $liveInfo = M( 'live' )->where ( $map )->find ();
            if($liveInfo['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
            if($liveInfo['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}

            $speaker = M('ZyTeacher')->where("uid={$this->mid}")->field('id,name,inro')->find();
            $url   = $this->zshd_config['api_url'].'/room/modify?';
            $param = 'id='.t($_POST['id']).'&subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
                '&invalidDate='.t($invalidDate*1000).'&studentToken='.$studentToken.
                '&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).'&description='.urlencode(t($_POST['description'])).
                '&speakerInfo='.urlencode(t($speaker['inro'])).'&clientJoin='.$clientJoin.'&webJoin='.$webJoin;
            $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
            $url   = $url.$hash;
            $upLive = $this->getDataByUrl($url);

            if($upLive['code'] == 0){
                //查此次插入数据库的课堂名称
                $url   = $this->zshd_config['api_url'].'/room/info?';
                $param = 'roomId='.t($_POST['id']);
                $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
                $url   = $url.$hash;
                $live = $this->getDataByUrl($url);
                if(empty($live["number"])){$this->error('服务器查询失败');}
                if($live["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
                if($live["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}

                $data["subject"] = $live['subject'];
                $data["speaker"] = $speaker['id'];
                $data["price"] = floatval($_POST['price']);
                $data["startDate"] = $live['startDate']/1000;
                $data["invalidDate"] = $live['invalidDate']/1000;
                $data["teacherToken"] = $live['teacherToken'];
                $data["assistantToken"] = $live['assistantToken'];
                $data["studentClientToken"] = $live['studentClientToken'];
                $data["studentToken"] = $live['studentToken'];
                $data["scheduleInfo"] = t($_POST['scheduleInfo']);
                $data["description"] = t($_POST['description']);
                $data["live_time"] = $liveTime;
                $data["cover"] = intval($_POST['live_cover_ids']);
                $data["cate_id"] = ','.$_POST['myAdminLevelhidden'].',';
                $data["clientJoin"] = $liveClientJoin;
                $data["webJoin"] = $liveWebJoin;

                $map = array('SDK_ID'=>t($_POST['id']));
                $result = model('Live')->updLiveAInfo($map,$data);
                if(!$result){$this->error('修改失败!');}
                $this->success('修改成功');
            }else {
                $this->error('服务器出错啦');
            }
        }else{
            $map['subject'] = trim(t($_POST['subject']));
            $field = 'subject';
            $liveSubject = model('Live')->findLiveAInfo($map,$field);

            if($_POST['subject'] == $liveSubject['subject']){$this->error('已有此直播课堂名称,请勿重复添加');}
            if($_POST['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
            if($_POST['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}

            $speaker = M('ZyTeacher')->where("uid={$this->mid}")->field('id,name,inro')->find();
            $url   = $this->zshd_config['api_url'].'/room/created?';
            $param = 'subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
                '&invalidDate='.t($invalidDate*1000).'&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).
                '&description='.urlencode(t($_POST['description'])).'&speakerInfo='.urlencode(t($speaker['inro'])).
                '&studentToken='.$studentToken.'&clientJoin='.$clientJoin.'&webJoin='.$webJoin;
            $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
            $url   = $url.$hash;
            $addLive = $this->getDataByUrl($url);

            if($addLive['code'] == 0) {
                if(empty($addLive["number"])){$this->mzError('服务器创建失败');}
                //查此次插入数据库的课堂名称
                $url   = $this->zshd_config['api_url'].'/room/info?';
                $param = 'roomId='.$addLive["id"];
                $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
                $url   = $url.$hash;
                $live = $this->getDataByUrl($url);

                if(empty($live["number"])){$this->mzError('服务器查询失败');}
                if($addLive["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
                if($addLive["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}
                $data["number"] = $addLive["number"];
                $data["subject"] = $live['subject'];
                $data["speaker"] = $speaker['id'];
                $data["price"] = floatval($_POST['price']);
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
                $data["cover"] = intval($_POST['live_cover_ids']);
                $data["cate_id"] = ','.$_POST['myAdminLevelhidden'].',';
                $data["clientJoin"] = $liveClientJoin;
                $data["webJoin"] = $liveWebJoin;
                $data["score"] = intval($_POST['score']);
                $data["SDK_ID"] = $addLive["id"];
                $data["is_active"] = 0;
                $result = model('Live')->addLiveInfo($data);
                if(!$result){$this->mzError('创建失败!');}
                $this->mzSuccess('创建成功');
            } else {
                $this->mzError('服务器出错啦');
            }
        }
    }
    /**
     * 上传直播课程-光慧
     * @return void
     */
    public function doUploadLive(){
        $data = $_POST;
        $data['beginTime'] = strtotime($data['beginTime']) * 1000;
        $data['endTime']   = strtotime($data['endTime']) * 1000;
        $data['cate_id']   = ','.$_POST['myAdminLevelhidden'].',';
        $data['cover']     = $_POST['live_cover_ids'];
        $data['teachers']  = json_encode( array( array('account'=>$data['account'] , 'passwd'=>base64_encode( md5($data['passwd'] , true) ) , 'info'=>$data['info'] ) ) );
            $data = array_merge($data , $this->_ghdata() );

        if($data['id']) {//修改
            $url = $this->gh_config['api_url'].'/openApi/updateLiveRoom';
            $res = json_decode( request_post($url , $data) , true);
            if($res['code'] == 0) {
                unset($data['teachers']);
                $res = M('zy_live')->where('id='.$data['id'])->save( $data );
                if( $res !== false ) {
                    $this->assign( 'jumpUrl', U('classroom/User/teacherVideo') );
                    $this->success('修改成功');
                } else {
                    $this->error('修改失败');
                }
            } else {
                $this->error('修改失败');
            }
        } else {
            $url = $this->gh_config['api_url'].'/openApi/createLiveRoom';
            $data['uid'] = $this->mid;
            $id  = M('zy_live')->add($data);
            if( $id ) {
                $data['id'] = $id;
                $res  = json_decode( request_post($url , $data) , true);
                if($res['code'] == 0) {
                    $data['room_id'] = $res['liveRoomId'];
                    M('zy_live')->where('id='.$id)->save( $data );
                    $this->assign( 'jumpUrl', U('classroom/User/teacherVideo') );
                    $this->success('创建成功');
                } else {
                    //删除本地数据
                    M('zy_live')->where('id='.$id)->delete();
                    $this->error('创建失败');
                }
            } else {
                $this->error('创建失败');
            }
        }
    }

     /**
     * 修改直播课程页面
     * @return void
     */
    public function updateLive() {
        if($this->base_config['live_opt'] == 1) {
            $map['SDK_ID'] = t($_GET['id']);
            $data = M( 'live' )->where ( $map )->find ();
            $data['startDate'] = date('Y-m-d H:i:s',$data["startDate"]);
            $data['invalidDate'] = date('Y-m-d H:i:s',$data["invalidDate"]);
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            $this->assign($data);

            $this->display('update_zshdLive');
        }else if($this->base_config['live_opt'] == 2) {

        }else if($this->base_config['live_opt'] == 3) {
            $id   = intval( $_GET['id'] );
            $data = M('zy_live')->where('id='.$id)->find();
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            $this->assign($data);
            $this->display();
        }
    }

     /**
     * 上传录播课程页面
     * @return void
     */
    public function uploadVideo() {
        //生成上传凭证
        $bucket = getAppConfig('qiniu_Bucket','qiniuyun');
        Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
        $putPolicy = new Qiniu_RS_PutPolicy($bucket);
        $filename = "eduline".rand(5,8).time();
        $str = "{$bucket}:{$filename}";
        $entryCode = Qiniu_Encode($str);
        $putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
        $upToken = $putPolicy->Token(null);
        //获取配置上传空间   0本地 1七牛 2阿里云 3又拍云
        $upload_room = getAppConfig('upload_room','basic');
        $this->assign('upload_room' , $upload_room);
        $this->assign("uptoken",$upToken);
        $this->assign("filename",$filename);
        $this->display();
    }

    //修改录播课程
    public function updateVideo(){
        $id=intval($_GET['id']);
        if($_GET['id']){
            $data = D('ZyVideo','classroom')->getVideoById(intval($_GET['id']));
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            $this->assign('data',$data);
        }

        $this ->assign('id',$id);
        //生成上传凭证
        $bucket = getAppConfig('qiniu_Bucket','qiniuyun');
        Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
        $putPolicy = new Qiniu_RS_PutPolicy($bucket);
        $filename="chuyou".rand(5,8).time();
        $str="{$bucket}:{$filename}";
        $entryCode=Qiniu_Encode($str);
        $putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
        $upToken=$putPolicy->Token(null);
        $video_category=M("zy_video_category")->where("type=1")->findAll();
        //获取配置上传空间   0本地 1七牛 2阿里云 3又拍云
        $upload_room = getAppConfig('upload_room','basic');
        $this->assign('upload_room' , $upload_room);

        $this->assign("category",$video_category);
        $this->assign("uptoken",$upToken);
        $this->assign("filename",$filename);
        $this->display();
    }

    //修改线下课程
    public function updateLineClass(){
        $id=intval($_GET['id']);
        if($_GET['id']){
            $data = M('zy_teacher_course')->where('course_id='.$id)->find();
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            $data['course_price'] = floatval($data['course_price']);
            $this->assign('data',$data);
        }

        $this ->assign('id',$id);
        $this->display();
    }

    //章节管理
    public function mannageChapter ()
    {


        $id = intval($_GET['id']);

        if ($_GET['id']) {
            $map['vid'] = $id;
            $map['pid'] = 0;
            $res = M('zy_video_section')->where($map)-> select();
            foreach($res as $key=>$val)
            {
                $tmap['vid'] = $id;
                $tmap['pid'] = $val['zy_video_section_id'];

               $res[$key]['video_section'] = M('zy_video_section')->where($tmap) ->field('title')->select();
                foreach($val['video_section']  as $v)
                {

                }
            }
        }

    $this->assign('data',$res);
        $this->display();
    }
    /**
     * 上传录播课程
     * @return void
     */
    public function doAddVideo(){

       $post = $_POST;
        if(empty($post['video_title'])) exit(json_encode(array('status'=>'0','info'=>"课程标题不能为空")));
        if(empty($post['video_type'])) exit(json_encode(array('status'=>'0','info'=>"请选择课程类型")));
        if(empty($post['video_levelhidden'])) exit(json_encode(array('status'=>'0','info'=>"请选择课程分类")));
        if(empty($post['video_binfo'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程简介")));
        if(empty($post['video_intro'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程详情")));
        if($post['v_price'] == '') exit(json_encode(array('status'=>'0','info'=>"课程价格不能为空")));
        if(!is_numeric($post['v_price']) || preg_match("/^-[0-9]+(\.[0-9]+)?$/",$post['v_price']))exit(json_encode(array('status'=>'0','info'=>"课程价格格式错误")));
        //if(ereg("^[0-9]*[1-9][0-9]*$",$_POST['v_price'])!=1) exit(json_encode(array('status'=>'0','info'=>"课程价格必须为正整数")));
        if($post['t_price'] == '') exit(json_encode(array('status'=>'0','info'=>"销售价格不能为空")));
        if(!is_numeric($post['t_price']) || preg_match("/^-[0-9]+(\.[0-9]+)?$/",$post['t_price']))exit(json_encode(array('status'=>'0','info'=>"销售价格格式错误")));

        //if(empty($post['videokey'])) exit(json_encode(array('status'=>'0','info'=>"请上传视频")));
        if(empty($post['listingtime'])) exit(json_encode(array('status'=>'0','info'=>"上架时间不能为空")));
        if(empty($post['uctime'])) exit(json_encode(array('status'=>'0','info'=>"下架时间不能为空")));
        if(empty($post['cover_ids'])) exit(json_encode(array('status'=>'0','info'=>"课程封面不能为空")));

        //if($post['limit_discount'] > 1 || $post['limit_discount'] < 0){
        //    exit(json_encode(array('status'=>'0','info'=>'折扣的区间填写错误')));
        //}
       //if(intval($post['v_price']) > 1000) exit(json_encode(array('status'=>'0','info'=>"课程市场价格不能超过1000元")));
        $data['starttime']           = $post['starttime'] ? strtotime($post['starttime']) : 0; //限时开始时间
        $data['endtime']             = $post['endtime'] ? strtotime($post['endtime']) : 0; //限时结束时间
        $data['listingtime']         = $post['listingtime'] ? strtotime($post['listingtime']) : 0; //上架时间
        $data['uctime']              = $post['uctime'] ? strtotime($post['uctime']) : 0; //下架时间
        if($data['endtime'] < $data['starttime'] || $data['uctime'] < $data['listingtime']){
            exit(json_encode(array('status'=>'0','info'=>'结束时间不能小于开始时间')));
        }

        //格式化七牛数据
        $videokey=t($_POST['videokey']);
        //获取上传空间 0本地 1七牛 2阿里云 3又拍云
        if(getAppConfig('upload_room','basic') == 0 ) {
        	if( $post['attach'][0]) {
        		$video_address = getAttachUrlByAttachId( $post['attach'][0] );
        	} else {
        		$video_address = $_POST['video_address'];
        	}
        } else {
        	$video_address="http://".getAppConfig('qiniu_Domain','qiniuyun')."/".$videokey;
        }


        $myAdminLevelhidden         = getCsvInt(t($post['video_levelhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath             = explode(',',$post['video_levelhidden']);
        $category                     = array_pop($fullcategorypath);
        $category                    = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $data['fullcategorypath']     = $myAdminLevelhidden; //分类全路径
        $data['video_category']         = $category == '0' ? array_pop($fullcategorypath) : $category;

        $data['qiniu_key']=$videokey;
        $video_tag         = t($post['video_tag']);
        $data['video_title']         = t($post['video_title']); //课程名称
        $data['video_binfo']         = t($post['video_binfo']); //课程介绍
        $data['video_intro']         = $post['video_intro']; //课程介绍
        $data['v_price']             = round($post['v_price'],2); //市场价格
        $data['t_price']         = round($post['t_price'],2); //销售价格

        $data['video_address']       = $video_address;//正确的视频地址
        $data['cover']               = intval($post['cover_ids']); //封面
        $data['videofile_ids']       = isset($post['videofile_ids']) ? intval($post['videofile_ids']) : 0; //课件id
//        $data['is_tlimit']           = isset($post['is_tlimit']) ? intval($post['is_tlimit']) : 0; //限时打折
//        $data['limit_discount']      = isset($post['is_tlimit']) && ($post['limit_discount'] <= 1 && $post['limit_discount'] >= 0) ? $post['limit_discount'] : 1; //限时折扣
        $data["teacher_id"]          = M('zy_teacher')->where('uid='.$this->mid)->getField('id');
        $data["mhm_id"]              = M('zy_teacher')->where('uid='.$this->mid)->getField('mhm_id');

        $avinfo = json_decode(file_get_contents($video_address.'?avinfo') , true);

        $data['duration']       = number_format($avinfo['format']['duration']/60, 2, ':', '');

        $video_count = M('zy_video')->where('id = '.$post['id'])->count();
        /*if($post['id'] && $activity == 5){
            $data['is_activity'] = 1;
        }else{
            $data['is_activity'] = 4;
        }*/
        if($video_count == 0){
            $data['is_activity'] = 4;
        }
        if($post['id']){
            $data['utime'] = time();
            $result = M('zy_video')->where('id = '.$post['id'])->data($data)->save();
        } else {
            $data['ctime'] = time();
            $data['utime'] = time();
            $data['uid'] = $this->mid;
            $result = M('zy_video')->data($data)->add();
        }

        if($result){
            unset($data);
            if($post['id']){
                model('Tag')->setAppName('classroom')->setAppTable('zy_video')->deleteSourceTag($post['id']);
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($post['id'],$video_tag);
            } else {
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($result,$video_tag);
            }
            $video_tag         = t($post['video_tag']);
            $data['v_price']             = $post['v_price']; //市场价格
            $data['str_tag'] = implode(',' ,getSubByKey($tag_reslut,'name'));
            $data['listingtime']         = $post['listingtime'] ? strtotime($post['listingtime']) : 0; //上架时间
            $data['uctime']              = $post['uctime'] ? strtotime($post['uctime']) : 0; //下架时间
            $data['tag_id'] = ','.implode(',',getSubByKey($tag_reslut,'tag_id')).',';
            $map['id'] = $post['id'] ? $post['id'] : $result;
            M('zy_video')->where($map)->data($data)->save();

            if($post['id']){
                exit(json_encode(array('status'=>'1','info'=>'编辑成功','data'=>$map['id'])));
            } else {
                exit(json_encode(array('status'=>'1','info'=>'添加成功','data'=>$map['id'])));
            }
        } else {
            exit(json_encode(array('status'=>'0','info'=>'系统繁忙，请稍后再试')));
        }
    }

    /**
     * 上传线下课程
     * @return void
     */
    public function doAddTeacherCourse(){

        $post = $_POST;
        if(empty($post['video_title'])) exit(json_encode(array('status'=>'0','info'=>"课程标题不能为空")));
        if(empty($post['video_type'])) exit(json_encode(array('status'=>'0','info'=>"请选择课程类型")));
        if(empty($post['video_levelhidden'])) exit(json_encode(array('status'=>'0','info'=>"请选择课程分类")));
        if(empty($post['video_binfo'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程简介")));
        if(empty($post['video_intro'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程详情")));
        if(preg_match('/^\d+$/',$_POST['v_price']) == 0) exit(json_encode(array('status'=>'0','info'=>"课程价格必须为非负整数")));
        //if(empty($post['v_price'])) exit(json_encode(array('status'=>'0','info'=>"课程价格不能为空")));
        //if(ereg("^[0-9]*[1-9][0-9]*$",$_POST['v_price'])!=1) exit(json_encode(array('status'=>'0','info'=>"课程价格必须为正整数")));
        if(empty($post['listingtime'])) exit(json_encode(array('status'=>'0','info'=>"开课时间不能为空")));
        if(empty($post['uctime'])) exit(json_encode(array('status'=>'0','info'=>"结束时间不能为空")));
        if(empty($post['cover_ids'])) exit(json_encode(array('status'=>'0','info'=>"课程封面不能为空")));

        $data['listingtime']         = $post['listingtime'] ? strtotime($post['listingtime']) : 0; //开课时间
        $data['uctime']              = $post['uctime'] ? strtotime($post['uctime']) : 0; //结束时间
        if($data['uctime'] < $data['listingtime']) exit(json_encode(array('status'=>'0','info'=>'结束时间不能小于开课时间')));

        $myAdminLevelhidden         = getCsvInt(t($post['video_levelhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath           = explode(',',$post['video_levelhidden']);
        $category                   = array_pop($fullcategorypath);
        $category                   = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $data['fullcategorypath']   = $myAdminLevelhidden; //分类全路径
        $data['course_category']    = $category == '0' ? array_pop($fullcategorypath) : $category;

        $data['course_name']   =  t($post['video_title']); //课程名称
        $data['course_binfo']  =  t($post['video_binfo']); //课程介绍
        $data['course_intro']  =  $post['video_intro']; //课程介绍
        $data['course_price']  =  $post['v_price']; //价格
        $data['cover']         =   intval($post['cover_ids']); //封面
        $data["teacher_id"]    =   M('zy_teacher')->where('uid='.$this->mid)->getField('id');
        $data["mhm_id"]        =   M('zy_teacher')->where('uid='.$this->mid)->getField('mhm_id');
        $data['ctime']         = time();
        if($post['id']){
            $is_activity = M('zy_teacher_course')->where('course_id = '.$post['id'])->getField('is_activity');
            if($is_activity == -1){
                $data[is_activity]     = 0;
            }
            $result = M('zy_teacher_course')->where('course_id = '.$post['id'])->data($data)->save();
        } else {
            $data['course_uid'] = $this->mid;
            $result = M('zy_teacher_course')->data($data)->add();
        }

        if($result){
            if($post['id']){
                exit(json_encode(array('status'=>'1','info'=>'编辑成功')));
            } else {
                exit(json_encode(array('status'=>'1','info'=>'添加成功')));
            }
        } else {
            exit(json_encode(array('status'=>'0','info'=>'系统繁忙，请稍后再试')));
        }
    }

    //添加/修改教师的面授课程
    function doteachcourse(){
        if($_POST['num_list']){
            $num=explode(",",$_POST['num_list']);
            $mid = $this->mid;
            $tid =  M('zy_teacher') ->where('uid ='.$mid) ->getField('id');
            foreach ($num as $key =>$value) {
                if(!is_numeric($_POST['course_price_'.$value])){$this->error('价格必须为数字');}
               $map=array(
                    'course_name'=>$_POST['course_name_'.$value],
                    'course_teacher'=>$tid,
                    'course_price'=>$_POST['course_price_'.$value],
                    'course_inro'=>$_POST['course_inro_'.$value],
                    'ctime'=>time()
                );
               M('zy_teacher_course')->data($map)->add();
            }
        }
        if($_POST['old_num_list']){
            $mid = $this->mid;
            $tid =  M('zy_teacher') ->where('uid ='.$mid) ->getField('id');
            $old_num_list=explode(",",$_POST['old_num_list']);
            foreach ($old_num_list as $key => $value) {
                $list=explode("-",$value);
                if($list[2]==0){
                    $map=array(
                        'course_name'=>$_POST['course_name_'.$list[0]],
                        'course_teacher'=>$tid,
                        'course_price'=>$_POST['course_price_'.$list[0]],
                        'course_inro'=>$_POST['course_inro_'.$list[0]],
                        'ctime'=>time()
                    );
                   M('zy_teacher_course')->data($map)->where("course_id=".$list[1])->save();
                }else{
                    M('zy_teacher_course')->data('is_del=1')->where("course_id=".$list[1])->save();
                }
            }
        }
        exit(json_encode(array('status'=>'1','info'=>'操作成功')));
    }

    /**
     * 删除录播课程
     * @return void
     */
    public function delvideo(){
        $id   = $_POST["id"];
        $res  = M('zy_video')->where('id='.$id )->save( array('is_del'=>1) );
        if($res){
            exit(json_encode(array('status'=>'1','info'=>'已删除')));
        }else{
            exit(json_encode(array('status'=>'0','info'=> '操作繁忙,请稍后再试')));
        }
    }

    /**
     * 删除直播课程
     * @return void
     */
    public function dellive(){
        if($this->base_config['live_opt'] == 1){
            $SDK_ID = t($_POST['id']);

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
                $return['info'] = '删除成功';
            } else {
                $return['status'] = 0;
                $return['info'] = '删除失败';
            }
            exit(json_encode($return));
        }else if($this->base_config['live_opt'] == 2){

        }else if($this->base_config['live_opt'] == 3) {
            $data['id'] = intval($_POST['id']);
            $data = array_merge($data, $this->_ghdata());
            $url = $this->gh_config['api_url'] . '/openApi/deleteLiveRoom';
            $res = json_decode(request_post($url, $data), true);
                if (M('zy_video')->where('id=' . $data['id'] . ' and uid=' . $this->mid)->delete()) {
                    M('arrange_course')->where('course_id ='.$data['id'])->delete();
                    $return['status'] = 1;
                    $return['data'] = '删除成功';
                } else {
                    $return['status'] = 0;
                    $return['data'] = '删除失败';
                }
            exit( json_encode($return) );
        }
    }

    //删除教师的面授课程
    function delteachcourse(){
        $result = M('zy_teacher_course')->where('course_id='.$_POST['id'])->data(array('is_del'=>1))->save();
        if($result){
            exit(json_encode(array('status'=>'1','info'=>'已删除')));
        } else {
            exit(json_encode(array('status'=>'0','info'=> '操作繁忙,请稍后再试')));
        }
    }

    /**
     * 删除线下课程
     * @return void
     */
    public function delLineClass(){
        $id   = $_POST["id"];
        $res  = M('zy_teacher_course')->where('course_id='.$id )->save( array('is_del'=>1) );
        if($res){
            exit(json_encode(array('status'=>'1','info'=>'已删除')));
        }else{
            exit(json_encode(array('status'=>'0','info'=> '操作繁忙,请稍后再试')));
        }
    }

    public function checkDeatil(){
        $map = array("id"=>$_GET['id'],'is_del'=>0);

        $teacher_article = M("zy_teacher_article")->where($map)->find();
        if(!$teacher_article){
            $this->error("文章不存在");
        }
        $this->assign($teacher_article);
        $this->display();
    }

    //教师信息设置
    public function teacherDeatil(){
        $type = intval($_GET['type']);
		if($type<0 || $type>3 ){
			$type = 0;
		}
        //教师资料
        $teacher_info=M("zy_teacher")->where("uid=".$this->mid,'is_del=0')->find();
        $teacherschedule=$teacher_info["teacher_schedule"];
        $teacher_info["teacher_schedule"]=explode(",",$teacher_info["teacher_schedule"]);
        $teacher_schedule=M("zy_teacher_schedule")->where("pid=0")->findALL();
        $teacher_level=array();
        for ($i=0; $i <3 ; $i++) {
            foreach ($teacher_schedule as $key => $value) {
                $level=M("zy_teacher_schedule")->where("pid=".$value["id"])->findALL();
                $teacher_level[$i][]=$level[$i];
            }
        }
        $teacher_info['title'] = M( 'zy_teacher_title_category' )->where('zy_teacher_title_category_id='.$teacher_info['title'])->getField('title') ? : '普通讲师';
		$map = array("tid"=>$teacher_info['id'],'is_del'=>0);
        //教师文章
        $teacher_article=M("zy_teacher_article")->where($map)->findPage();
        //教师经历
        $teacher_details=M("zy_teacher_details")->where($map)->findPage();
        foreach ($teacher_details['data'] as $key => $val) {
            $teacher_details['data'][$key]['head_id'] = D('ZyTeacher')->where('uid='.$this->mid)->getField('head_id');
            if($val['type'] == 1){ $teacher_details['data'][$key]['type'] = '过往经历';}
            else if($val['type'] == 2){$teacher_details['data'][$key]['type'] = '相关案例';}
        }
        //教师相册
        $teacher_photos = D('ZyTeacherPhotos')->getPhotosAlbumByTid($teacher_info['id']);
        foreach ($teacher_photos['data'] as $key=>$val){
            //教师相册详情
            $photos_deatil = D('ZyTeacherPhotos')->getPhotoDataByPhotoId($val['id']);
        }
        foreach ($photos_deatil['data'] as $key => $val) {
            if($val['type'] == 2){$video_address = $val['resource'];}
        }
        //播放器
        $player_type = getAppConfig("player_type");

        $this->assign('title',$title);
        $this->assign('teacher_level',$teacher_level);
        $this->assign("teacher_schedule",$teacher_schedule);
        $this->assign("teacherschedule",$teacherschedule);
        $this->assign("teacher_info",$teacher_info);
        $this->assign("teacher_article",$teacher_article['data']);
        $this->assign("teacher_details",$teacher_details['data']);
        $this->assign("teacher_photos",$teacher_photos['data']);
        $this->assign("photos_deatil",$photos_deatil['data']);
        $this->assign("video_address",$video_address);
        $this->assign("player_type",$player_type);
        $this->assign("type",$type);
        $this->display();
    }

    //教师资料设置
    public function doteacherDeatil(){
        $id = intval($_POST['id']);
        //要添加的数据
        $data=array(
        'name'=>filter_keyword(t($_POST['name'])),
        'inro'=>filter_keyword($_POST['inro']),
        'title'=>filter_keyword(t($_POST['title'])),
        'ctime'=>time(),
        'online_price'=> t($_POST['online_price']),
        'offline_price'=> t($_POST['offline_price']),
        'teacher_age'=>t($_POST['teacher_age']),
        'label'=>filter_keyword(t($_POST['label'])),
        'high_school'=>t($_POST['high_school']),
        'teacher_schedule'=>t($_POST['teacher_schedule']),
        'graduate_school'=>filter_keyword(t($_POST['graduate_school'])),
        'teach_evaluation'=>t($_POST['teach_evaluation']),
        'teach_way'=>t($_POST['teach_way']),
        'Teach_areas'=>filter_keyword(t($_POST['Teach_areas'])),
        'details'=>$_POST['details'],
        'head_id'=>trim(t($_POST['large_cover'])),
        'background_id'=>trim(t($_POST['background'])),
        );
        $res=M('zy_teacher')->where("id=".$id)->save($data);
        if(!$res)exit(json_encode(array('status'=>'0','info'=>'编辑失败')));
        exit(json_encode(array('status'=>'1','info'=>'编辑成功')));
    }
	//编辑 讲师文章
    public function updateArticle(){
        $id=intval($_GET['id']);
        if(isset($id)){
            $article = M('zy_teacher_article')->where('id='.$id)->find();
            $this->assign("article",$article);
        }
        $this->display();
    }
    //添加 讲师相册
    public function uploadPhoto(){
        $id=intval($_GET['id']);

		if($id){
			$photo = M('zy_teacher_photos')->where('id='.$id)->find();
			$this->assign("photo",$photo);
		}
		if($_POST){
			$id=intval($_POST['id']);
			$data['tid'] = D('ZyTeacher')->where('uid='.$this->mid)->getField('id');
			$data['title'] = filter_keyword(t($_POST['title']));
			$data['ctime'] = time();
			$data['id'] = $id;

            $url = U('classroom/User/teacherDeatil',array('tab'=>2));
			if($id){
				$res = D('ZyTeacherPhotos')->savePhotoAlbum($data);
				if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起,修改相册失败！')));
				exit(json_encode(array('status'=>'1','info'=>'修改相册成功','url'=>$url)));
			}else{
				$res = D('ZyTeacherPhotos')->savePhotoAlbum($data);
				if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起,添加相册失败！')));
				exit(json_encode(array('status'=>'1','info'=>'添加相册成功','url'=>$url)));
			}
		}
        $this->display();
    }
    public function getPhotoList(){
        $photo_id = intval($_GET['photo_id']);

        //教师相册详情
        $photos_detail = D('ZyTeacherPhotos')->getPhotoDataByPhotoId($photo_id);

        $player_type = getAppConfig("player_type");

        $this->assign("photos_detail",$photos_detail['data']);
		$this->assign("photo_id",$photo_id);
        $this->assign("player_type",$player_type);
        $this->display('photo_list');
	}
    //视频播放
    public function getVideoAddress(){
        $pic_id = intval($_POST['pic_id']);
        $photo_data = D('ZyTeacherPhotos')->where('pic_id='.$pic_id)->field('resource,videokey,video_type')->find();
        if($photo_data['video_type'] == 1){
            // 七牛
            //域名防盗链
            Qiniu_SetKeys(getAppConfig('qiniu_AccessKey', 'qiniuyun'), getAppConfig('qiniu_SecretKey', 'qiniuyun'));
            $mod = new Qiniu_RS_GetPolicy();
            $photo_data['address'] = $mod->MakeRequest($photo_data['resource']);
        }else if($photo_data['video_type'] == 4){
            $photo_data['address'] = $this->cc_video_config;
            $photo_data['videokey'] = $photo_data['videokey'];
        }
        if($photo_data)exit(json_encode(array('status'=>'1','data'=>$photo_data)));
        exit(json_encode(array('status'=>'0','message'=>'视频加载失败')));
    }
	//添加 讲师相册详情
    public function updateStyle(){
        if($_POST){
            $id = intval($_GET['id']);
            $tid = D('ZyTeacher')->where('uid='.$this->mid)->getField('id');
            //格式化七牛数据
            $videokey=t($_POST['videokey']);
            //获取上传空间 0本地 1七牛 2阿里云 3又拍云 4CC
            if(getAppConfig('upload_room','basic') == 0 ) {
                if( $post['attach'][0]) {
                    $video_address = getAttachUrlByAttachId( $post['attach'][0] );
                } else {
                    $video_address = $_POST['video_address'];
                }
                $avinfo             = json_decode(file_get_contents($video_address.'?avinfo') , true);
                $file_size          = $avinfo['format']['size'];
                $data['video_type'] = 0;
                $data['is_syn']     = 1;
            }else if(getAppConfig('upload_room','basic') == 4 ) {
                $find_url  = $this->cc_video_config['cc_apiurl'].'video/v2?';
                $play_url  = $this->cc_video_config['cc_apiurl'].'video/playcode?';

                $query['videoid']   = urlencode(t($videokey));
                $query['userid']    = urlencode($this->cc_video_config['cc_userid']);
                $query['format']    = urlencode('json');

                $find_url    = $find_url.createVideoHashedQueryString($query)[1].'&time='.time().'&hash='.createVideoHashedQueryString($query)[0];
                $play_url    = $play_url.createVideoHashedQueryString($query)[1].'&time='.time().'&hash='.createVideoHashedQueryString($query)[0];

                $info_res     = getDataByUrl($find_url);
                $play_res   = getDataByUrl($play_url);

                $video_address  = $play_res['video']['playcode'];
                $file_size      = $info_res['video']['definition'][3]['filesize'] ? : 0;

                $data['video_type'] = 4;
                $data['is_syn']     = 0;
            } else {
                $video_address="http://".getAppConfig('qiniu_Domain','qiniuyun')."/".$videokey;
                $avinfo             = json_decode(file_get_contents($video_address.'?avinfo') , true);
                $file_size          = $avinfo['format']['size'];

                $data['video_type'] = 1;
                $data['is_syn']     = 1;
            }
            //要添加的数据
            $data['videokey']       = $videokey;
            $data['tid']            = intval($tid);
            $data['photo_id']       = intval(t($_POST['photo_id']));
            $data['title']          = t($_POST['title']);
            $data['type']           = t($_POST['type']);
            $data['cover']          = trim(t($_POST['cover_ids']),"|");
            $data['filesize']       = $file_size;

            if($_POST['type'] == 1){$data['resource'] = explode("|", $_POST['attach_ids']);}
            else if($_POST['type'] == 2) {$data['resource'] = $video_address;}

            if($data['video_type'] == 4 && !$data['resource']){
                $data['resource'] = '0';
                $res = D('ZyTeacherPhotos')->add($data);
            }else{
                $res = D('ZyTeacherPhotos')->addAllSource($data);
            }
            $url=U('classroom/User/teacherDeatil',array('tab'=>2));
			if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起,上传失败！')));
            exit(json_encode(array('status'=>'1','info'=>'上传成功','url'=>$url)));
        }
        //生成上传凭证
        $bucket = getAppConfig('qiniu_Bucket','qiniuyun');
        Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
        $putPolicy = new Qiniu_RS_PutPolicy($bucket);
        $filename = "eduline".rand(5,8).time();
        $str = "{$bucket}:{$filename}";
        $entryCode = Qiniu_Encode($str);
        $putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
        $upToken = $putPolicy->Token(null);
        //获取配置上传空间   0本地 1七牛 2阿里云 3又拍云
        $upload_room = getAppConfig('upload_room','basic');
        $this->assign('upload_room' , $upload_room);
        $this->assign("uptoken",$upToken);
        $this->assign("filename",$filename);
        $this->display();
    }
	//编辑 讲师经历
    public function updateDetails(){
        $id=intval($_GET['id']);
        if(isset($id)){
            $details = M('zy_teacher_details')->where('id='.$id)->find();
            $this->assign("details",$details);
        }
        $this->display();
    }
    //处理添加/修改 讲师文章
    public function doUpdateArticle(){
        $id = intval($_GET['id']);
		$tid = D('ZyTeacher')->where('uid='.$this->mid)->getField('id');
        $data = array(
			'tid'=>intval($tid),
            'cover'=>intval(t($_POST['article_cover'])),
            'art_title'=>filter_keyword(t($_POST['art_title'])),
            'article'=> filter_keyword($_POST['article']),
            'ctime'=>time(),
            );
        $url=U('classroom/User/teacherDeatil',array('tab'=>1));
        if(!$id){
            $res = M('zy_teacher_article')->add($data);
            if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起，添加讲师文章失败！')));
            exit(json_encode(array('status'=>'1','info'=>'添加讲师文章成功','url'=>$url)));
        }else{
            $res = M('zy_teacher_article')->where("id=$id")->save($data);
            if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起，修改讲师文章失败！')));
            exit(json_encode(array('status'=>'1','info'=>'修改讲师文章成功','url'=>$url)));
        }
    }
    //处理添加/修改 讲师经历
    public function doUpdateDetails(){
        $id = intval($_GET['id']);
		$tid = D('ZyTeacher')->where('uid='.$this->mid)->getField('id');
        $data = array(
			'tid'=>intval($tid),
            'Time'=>t($_POST['Time']),
            'title'=>filter_keyword(t($_POST['title'])),
            'content'=>filter_keyword(t($_POST['content'])),
            'type'=>t($_POST['type']),
            'ctime'=>time(),
            );
        $url=U('classroom/User/teacherDeatil',array('tab'=>2));
        if(!$id){
            $res = M('zy_teacher_details')->add($data);
            if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起，添加讲师经历失败！')));
			exit(json_encode(array('status'=>'1','info'=>'添加讲师经历成功','url'=>$url)));
        }else{
            $res = M('zy_teacher_details')->where("id=$id")->save($data);
            if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起，修改讲师经历失败！')));
			exit(json_encode(array('status'=>'1','info'=>'修改讲师经历成功','url'=>$url)));

        }
    }
    //删除 讲师文章/风采/经历
    public function delTeacherInfo(){
        $id=intval($_POST['id']);
        $category = t($_POST['category']);
        $where=array('id'=>$id);
        if($category == 'article'){
            $res=M('zy_teacher_article')->where($where)->delete();
            $url=U('classroom/User/teacherDeatil',array('tab'=>1));
        }else if($category == 'style'){
            $res=M('zy_teacher_photos')->where($where)->delete();
			$result=M('zy_teacher_photos_data')->where('photo_id='.$id)->delete();
            $url=U('classroom/User/teacherDeatil',array('tab'=>2));
        }else if($category == 'details'){
            $res=M('zy_teacher_details')->where($where)->delete();
            $url=U('classroom/User/teacherDeatil',array('tab'=>3));
        }
        if($res)exit(json_encode(array('status'=>'1','url'=>$url)));
        exit(json_encode(array('status'=>'0')));
    }
	//删除 讲师相册数据
    public function delTeacherphoto(){
        $id=intval($_POST['id']);
        $where=array('pic_id'=>$id);
		$res=M('zy_teacher_photos_data')->where($where)->delete();
        if($res){echo 200;exit;}else{echo 500;exit;}
    }

    //根据url读取文本
    private function getDataByUrl($url , $type = true){
        return json_decode(file_get_contents($url) , $type);
    }

    //删除关注讲师
    public function delFollow(){
        $id=intval($_POST['id']);
        $res=model('Follow')->where('follow_id='.$id)->delete();
        if($res){
            echo 200;
            exit;
        }else{
            echo 500;
            exit;
        }
    }

    //删除学习记录
    public function delLearn(){
        $id=intval($_POST['id']);
        $data['is_del']=1;
        $where=array(
            'id'=>$id,
            'uid'=>$this->mid
        );
        $res=M('learn_record')->where($where)->save($data);

        if($res){
            echo 200;
            exit;
        }else{
            echo 500;
            exit;
        }
    }

    //收货地址设置
    public function address(){
        $id=intval($_GET['id']);
        if($id){
            $map = array('id'=>$id,'is_del'=>0);
            $address = model('Address')->where($map)->find();
        }
        $map = array('uid'=>$this->mid,'is_del'=>0);
        $data = model('Address')->where($map)->order('ctime DESC')->findPage(10);
        $data['usedCounts'] = model('Address')->where($map)->count();
        $data['usableCounts'] = 10 - $data['usedCounts'];
        $this->assign("address",$address);
        $this->assign("data",$data);
        $this->display();
    }
    //处理添加/修改 收货地址
    public function updateAddress(){

        $id = $_POST['id'];
        $data = array(
            'uid'=>intval($this->mid),
            'name'=>filter_keyword(t($_POST['name'])),
            'phone'=>t($_POST['phone']),
            'province'=>intval(t($_POST['province'])),
            'city'=>intval(t($_POST['city'])),
            'area'=>intval(t($_POST['area'])),
            'address'=>filter_keyword(t($_POST['address'])),
            'location'=>t($_POST['city_names']),
            'is_default'=>intval($_POST['is_default']),
            'ctime'=>time(),
            );
        if(!empty($data['is_default']) && $data['is_default'] == '1'){
            M('Address')->where('uid='.$data['uid'])->setField('is_default',0);
        }
        if(!$id){
            $res = model('Address')->addAddress($data);
            if(!$res)$this->error("对不起，添加收货地址失败！");
            $this->success("添加收货地址成功!");
        }else{
            $map['id'] = $id;
            $res = model('Address')->updateAddress($map,$data);
            if(!$res)$this->error("对不起，修改收货地址失败！");
            $this->success("修改收货地址成功!");
        }
    }
    //删除收货地址
    public function delAddress(){
        $id = intval($_POST['id']);
        $data['is_del'] = 1;
        $where = array(
            'id'=>$id,
            'uid'=>$this->mid
        );
        $res = model('Address')->where($where)->delete();
        if($res){
            $this->ajaxReturn('','删除成功',1);
        }else{
            $this->ajaxReturn('','删除失败',0);
        }
    }

    public function addcourse()

    {
        if($_POST['chaptertitle']  || $_POST['couretitle']  || empty($_POST)==false ) {

            if( t($_POST['chaptertitle'])) {
                $title = t($_POST['chaptertitle']);
                $chapter['vid'] = t($_POST['id']);
                $pid = 0;
                $addchapter = D('VideoSection')->setTable('zy_video_section')->addTreeCategory($pid, $title, $chapter);

                if ($addchapter) {
                    exit(json_encode(array('status' => '1', 'info' => '添加成功')));
                } else {
                    exit(json_encode(array('status' => '0', 'info' => '添加失败')));
                }
            }else if($_POST['video_title'] && !$_POST['couretitle']){
                exit(json_encode(array('status' => '0', 'info' => '添加失败')));
            }

            //格式化七牛数据
            $videokey = t($_POST['videokey']);
            //获取上传空间 0本地 1七牛 2阿里云 4CC
            if (getAppConfig('upload_room', 'basic') == 0) {
                if ($_POST['attach_ids']) {
                    $video_address = getAttachUrlByAttachId($_POST['attach_ids']);
                } else {
                    $video_address = $_POST['video_address'];
                }
                $avinfo = json_decode(file_get_contents($video_address . '?avinfo'), true);
                //$duration = number_format($avinfo['format']['duration'] / 60, 2, ':', '');
                $duration = t($_POST['duration']) ?: number_format($avinfo['format']['duration'] / 60, 2, ':', '');
                $file_size = $avinfo['format']['size'];
                $data['video_type'] = 0;
                $data['is_syn'] = 1;
                $attach = model('Attach')->getAttachById($_POST['attach_ids']);
                $videokey = $attach['hash'];
            } else if (getAppConfig('upload_room', 'basic') == 4) {
                $find_url = $this->cc_video_config['cc_apiurl'] . 'video/v2?';
                $play_url = $this->cc_video_config['cc_apiurl'] . 'video/playcode?';

                $query['videoid'] = urlencode(t($videokey));
                $query['userid'] = urlencode($this->cc_video_config['cc_userid']);
                $query['format'] = urlencode('json');

                $find_url = $find_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];
                $play_url = $play_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];

                $info_res = getDataByUrl($find_url);
                $play_res = getDataByUrl($play_url);

                //$duration = secondsToHour($info_res['video']['duration']);
                $duration   = t($_POST['duration']) ?: secondsToHour($info_res['video']['duration']);

                $video_address = $play_res['video']['playcode'];
                $file_size = $info_res['video']['definition'][3]['filesize'] ?: 0;

                $data['video_type'] = 4;
                $data['is_syn'] = 0;
            } else {
                $video_address = "http://" . getAppConfig('qiniu_Domain', 'qiniuyun') . "/" . $videokey;
                $avinfo = json_decode(file_get_contents($video_address . '?avinfo'), true);

                //$duration = secondsToHour($avinfo['format']['duration']);
                $duration   = t($_POST['duration']) ?: secondsToHour($avinfo['format']['duration']) ;
                $file_size = $avinfo['format']['size'];

                $data['video_type'] = 1;
                $data['is_syn'] = 1;
            }

            $school_id = model('User')->where('uid='.$this->mid)->getField('mhm_id');

            $data['uid'] = $this->mid;
            $data['type'] = t($_POST['type']);
            $data['video_address'] = $video_address;
            $data['videokey'] = $videokey;
            $data['ctime'] = time();
            $data['title'] = t($_POST['myvideo_title']);
            $data['duration'] = $duration;
            $data['filesize'] = $file_size;
            $data['mhm_id']   = $school_id;

       if(t($_POST['couretitle'])  && $videokey )
       {
       $res = M('zy_video_data')->add($data);
       }
            if($res && t($_POST['courepid'] != 0)) {
                $data = t($_POST['couretitle']);
                if(empty($data))
                {
                    $this->error("课时标题不能为空");
                }

                if(getAppConfig('upload_room','basic') != 4 ){
                    $video_space = M('zy_video_space')->where('mhm_id='.$school_id)->find();
                    if($video_space){
                        $data['used_video_space'] = $video_space['used_video_space'] + $file_size;
                        M('zy_video_space')->where('mhm_id='.$school_id)->save($data);
                    }else{
                        $data['mhm_id'] = $school_id;
                        $data['used_video_space'] = $file_size;
                        M('zy_video_space')->add($data);
                    }
                }

                $result['vid'] = t($_POST['id']);
                $result['pid'] = t($_POST['courepid']);
                $result['title'] = t($_POST['couretitle']);
                $result['cid'] = $res;

                //判断是否为平台讲师
                $mhm_id = D('ZyTeacher')->getTeacherStrByMap(['uid'=>$this->mid],'mhm_id');
                if($mhm_id == 1){
                    $result['is_activity'] = 3;
                }else{
                    $result['is_activity'] = 2;
                }

                $video_section = M('zy_video_section')->where(array('vid'=>$result['vid'],'pid'=>['neq',0]))->count();
                $resadd = M('zy_video_section')->add($result);
                if ($res && $resadd) {
                    /*if($video_section == 0){
                        if($school_id == 1){
                            $videoData['is_activity'] = 3;
                        }else{
                            $videoData['is_activity'] = 2;
                        }
                    }else{
                        $videoData['is_activity'] = 5;
                    }*/
                    if($video_section){
                        $videoData['is_activity'] = 4;
                    }
                    $section = M('zy_video_section')->where(array('vid'=>$result['vid'],'pid'=>['neq',0],'is_activity'=>1))->count();
                    if($section){
                        $videoData['is_activity'] = 7;
                    }
                    //查询课程是否已经有课时审核通过
                    /*$semap['is_activity'] = 1;
                    $semap['vid'] = $result['vid'];
                    $semap['pid'] = ['gt',0];
                    $count = M('zy_video_section')->where($semap)->count();
                    if($count > 0){
                        if($mhm_id == 1){
                            $data['is_activity'] = 6;
                        }else{
                            $data['is_activity'] = 5;
                        }
                    }else{
                        $data['is_activity'] = 4;
                    }*/
                    $videoData['utime'] = time();
                    M('zy_video')->where('id = '.$_POST['id'])->data($videoData)->save();
                    exit(json_encode(array('status' => '1', 'info' => '添加成功')));
                } else {
                    exit(json_encode(array('status' => '0', 'info' => '添加失败')));
                }
            }
        }
         else {
         //获取配置上传空间   0本地 1七牛 2阿里云 4CC
         $upload_room = getAppConfig('upload_room','basic');
         if($upload_room == 1 ) {
             //生成上传凭证
             $bucket = getAppConfig('qiniu_Bucket','qiniuyun');
             Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
             $putPolicy = new Qiniu_RS_PutPolicy($bucket);
             $filename="{$this->site['site_keyword']}".rand(5,8).time();
             $str = "{$bucket}:{$filename}";
             $entryCode = Qiniu_Encode($str);
             $putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
             $upToken = $putPolicy->Token(null);
             $this->assign("uptoken" , $upToken);
         }else if($upload_room == 4 ) {
             $filename="{$this->site['site_keyword']}".rand(5,8).time();
             $this->assign("ccvideo_config" , $this->cc_video_config);
         }

        //格式化七牛数据
        $videokey=t($_POST['videokey']);
        $video_category=M("zy_video_category")->where("type=1")->findAll();
        $data['qiniu_key']=$videokey;

        $id = intval($_GET['id']);
        if ($_GET['id']) {
            $map['vid'] = $id;
            $map['pid'] = 0;
            $res = M('zy_video_section')->where($map)-> select();
            $issection = 0;
            foreach($res as $key=>$val)
            {
                $tmap['vid'] = $id;
                $tmap['pid'] = $val['zy_video_section_id'];
                $issection = 1;

                $res[$key]['video_section'] = M('zy_video_section')->where($tmap)->select();

                foreach ($res[$key]['video_section'] as  $keys=>$value)
                {
                    $res[$key]['video_section'][$keys]['videotitle']  = M('zy_video_data') ->where('id ='.$value['cid']) ->getField('title');
                }

                $res[$key]['coursecount'] = count($res[$key]['video_section']);

            }

        }

        $this->assign('data',$res);


        $id=intval($_GET['id']);
        if($_GET['id']){
            $data = D('ZyVideo','classroom')->getVideoById(intval($_GET['id']));
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            //判断课程课时是否存在
            $data['section_count'] = D('ZyVideo','classroom')->getVideoSectionCount($id);

            $this->assign($data);
        }

        $this->assign('upload_room' , $upload_room);
        $this->assign('issection' , $issection);

        $this->assign("category",$video_category);
        $this->assign("filename",$filename);
        $this->display();
    }
    }

    /***
     * 添加章节
     *
     */
    public function addchapter()
    {
         $chaptertitle =  $_REQUEST['id'];
        $this ->assign('chaptertitle',$chaptertitle);
            $this->display();
    }

    /***
     * 删除章节及其以下视频
     */
      function delchapter()
    {
        $id = intval($_POST['id']);

        $where = ("zy_video_section_id=$id OR pid=$id");
        $section = M('zy_video_section')->where($where)->field('cid')->findAll();
        $cid = getSubByKey($section,'cid');
        $map['id'] = array('in',$cid);
        
        $data_count = M('zy_video_data')->where($map)->count();
        M('')->startTrans();  
        $res = M('zy_video_section')->where($where)->delete();
        $result =  true;
        if($res && $data_count > 0){
            $result = (boolean)M('zy_video_data')->where($map)->delete();
        }
        if($result && $res){
            M('')->commit();
            echo json_encode(array('status'=>1,"message"=>''));exit;
        }else{
            M('')->rollback();
            echo json_encode(array('status'=>0,"message"=>''));exit;
        }
    }


    /***
     * 修改章节
     */
    function editchapter()
    {
        if($_POST)
        {
            $map['id'] = $_POST['id'];
            $data['title']= $_POST['content'];
            $res = M('zy_video_section')->where('zy_video_section_id ='. $map['id'] )->save($data);
            if($res)
            {
                $this->success("修改成功");
            }
            else
            {
                $this->success("修改失败");
            }

        }
    }


    public function doAddUserAttach(){
        $image['attach_type'] =  'event';
        $image['upload_type'] =  'image';
        $cover = model('attach')->upload($image);
        $data['background_id'] = $cover['info'][0]['attach_id'];
        $rst = M('user')->where(array('uid'=>$this->mid))->save($data);
//        $data['background'] = getCover($data['background_id'] , 1200,340);
        model('User')->cleanCache($this->mid);
        header('Location:'.U('classroom/User/index'));
        exit;
    }


    /**
     * @name 支付宝积分充值回调
     */
    public function aliAddScoreAnsy(){
        file_put_contents('alipayre.txt',json_encode($_POST));
        tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','AopClient.php')));
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = model('Xdata')->get('admin_Config:alipay')['public_key'];
        //此处验签方式必须与下单时的签名方式一致
        $_POST = json_decode(file_get_contents('alipayre.txt'),true);
        $verify_result = $aop->rsaCheckV1($_POST, NULL, "RSA");
        if(!$verify_result) exit('fail');

        //商户订单号
        $out_trade_no = stristr($_POST['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];
        //
        $extra_common_param = json_decode($_POST['passback_params'],true);

        $re = D('ZyRecharge','classroom');
        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
            $re->setNormalPaySuccess($out_trade_no,$trade_no);
            //添加积分
            $uid = $re->where(array('id'=>$out_trade_no))->getField('uid');
            if($uid){
                model('Credit')->setUserCredit($uid,array('score'=>$extra_common_param['score']));
            }
        }
        echo 'success';
        exit;

        $alipay_config = $this->getAlipayConfig();
        //引入类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','AlipayNotify.php')));
        //初始化
        //dump($_POST);exit;
        $alipayNotify = new \AlipayNotify($alipay_config);
        //验证结果
        $verify_result = $alipayNotify->verifyNotify();
        if(!$verify_result) exit('fail');

        //商户订单号
        $out_trade_no = stristr($_POST['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];
        //
        $extra_common_param = json_decode($_POST['extra_common_param'],true);
    }

    public function changepsw(){
        $this->display();
    }

    //课程提交审核
    public function SubmitAudit(){
        $id = $_POST['id'];
        $mhm_id = D('ZyTeacher')->getTeacherStrByMap(['uid'=>$this->mid],'mhm_id');
        //$mhm_id = model('User')->getUserInfoForSearch($this->mid,'mhm_id');
        //判断是否是机构下的讲师
        /*if($mhm_id == 1){
            $secdata['is_activity'] = 3;
        }else{
            $secdata['is_activity'] = 2;
        }
        $map['vid'] = $_POST['vid'];
        $map['pid'] = ['gt',0];
        M('zy_video_section')->where($map)->data($secdata)->save();*/
        //查询课程是否已经有课时审核通过
        $map['vid'] = $id;
        $map['pid'] = ['gt',0];
        $map['is_activity'] = 1;
        $count = M('zy_video_section')->where($map)->count();
        if($count > 0){
            if($mhm_id == 1){
                $data['is_activity'] = 6;
            }else{
                $data['is_activity'] = 5;
            }
        }else{
            if($mhm_id == 1){
                $data['is_activity'] = 3;
            }else{
                $data['is_activity'] = 2;
            }
            //$data['is_activity'] = $secdata['is_activity'];
        }
        $data['utime'] = time();
        $result = M('zy_video')->where('id = '.$id)->data($data)->save();
        if($result){
            exit(json_encode(array('status'=>'1','info'=>'提交审核成功')));
        } else {
            exit(json_encode(array('status'=>'0','info'=>'提交审核失败')));
        }
    }

    /**
     * 保存登录用户的头像设置操作
     * @return json 返回操作后的JSON信息数据
     */
    public function doSaveAvatar() {
        $attach_id = $_POST['attach_id'];
        $attach_info = model('Attach')->getAttachById($attach_id);
        $dAvatar = model('Avatar');
        $dAvatar->init($this->mid); 			// 初始化Model用户id
        // 安全过滤
        $step = t($_GET['step']);
        if('upload' == $step) {
            $result = $dAvatar->upload();
        } else if('save' == $step) {
            $result = $dAvatar->dosave();
        }
        model('User')->cleanCache($this->mid);
        $user_feeds = model('Feed')->where('uid='.$this->mid)->field('feed_id')->findAll();
        if($user_feeds){
            $feed_ids = getSubByKey($user_feeds, 'feed_id');
            model('Feed')->cleanCache($feed_ids,$this->mid);
        }
        $this->ajaxReturn($result['data'], $result['info'], $result['status']);
    }
}
