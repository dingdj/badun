<?php
/**
 * 众筹控制器
 * @author ezhu <ezhufrank@qq.com>
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class CrowAction extends CommonAction {
    
    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
    }

    
    /**
     * 众筹列表
     */
    public function index() {
        $category = $this->getCategory();
        $cid = t($_GET['cid']);
        $sort = t($_GET['sort']);
        $where = '`status` IN (1,2,3,4)'; //众筹成功
        $where .= ' AND is_del=0 '
        $cid && $where.= 'AND FIND_IN_SET("'.$cid.'",`category`)';
        //$cid && $map['category'] = array('eq',$cid);
        $order = !empty($sort) ? $sort.' DESC' : '`num` DESC,`collect_count` DESC';
        $limit = 12;
        $list = D('Crow')->getList($where,$field=true,$order,$limit);

        foreach($list['data'] as $keys=>&$vals){
            //是否收藏
            $vals['iscollect'] = D('ZyCollection')->isCollect($vals['id'], 'crowdfunding', intval($this->mid));
            //是否已参加众筹
            $vals['isCrow'] = D('Crow')->isCrow($vals['id'], intval($this->mid));
        }
        //推荐众筹
        $recoMap = array();
        $recoMap['recommend'] = array('neq',0);
        $recoMap['status'] = array('eq',2);
        $recoField = 'id,title';
        $recomOrder = '`recommend` DESC,`ctime` DESC';
        $recomLimit = 5;
        $recomList = M('Crowdfunding')->where($recoMap)->field($recoField)->order($recomOrder)->limit($recomLimit)->select();
        
        $this->assign('recomList',$recomList);
        $this->assign('cid',$cid);
        $this->assign('sort',$sort);
        $this->assign('list',$list);
        $this->assign('category',$category);
        $this->display();
    }
    
    
    /**
     * 获取众筹分类
     */
    private function getCategory(){
        //$category = model('CategoryTree')->setApp('classroom')->setTable('ZyVideoCategory')->getNetworkList();
        $category = M("zy_currency_category")->where('pid=0')->order('sort asc')->findALL();
        return $category;
    }
    
    
    /**
     * 发布众筹页面
     */
    public function addCrow(){
        if(empty($this->mid)){
            $this->assign('jumpUrl',U('public/Passport/login_g'));
            $this->error('请先登录！');
        }
        $category = $this->getCategory();
        $this->assign('category',$category);
        $this->display();
    }
    
    
    /**
     * 发布众筹操作
     */
    public function doAddCrow(){
        if(empty($this->mid)){
            $this->assign('jumpUrl',U('public/Passport/login_g'));
            $this->error('请先登录！');
        }
        $isTeacher = M('zy_teacher')->where(array('uid'=>$this->mid,'is_del'=>0))->find();
        $data['title'] = t(trim($_POST['title']));
        $data['sid'] = model('User')->where(array('uid'=>$this->mid))->getField('mhm_id') ? : 1;
        $data['category'] = t($_POST['video_levelhidden']);
        $data['cover'] = t(trim($_POST['attach_ids'],'|'));
        $data['num'] = intval(t($_POST['num']));
        $data['price'] = floatval(t($_POST['price']));
        $data['info'] = t($_POST['info']);
        $data['user_type'] = $isTeacher ? 2 : 1;
        $data['ctime'] = time();
        //empty($data['sid']) && $this->error('必须输入机构id！');
        empty($data['title']) && $this->error('标题必须！');
        empty($data['category']) && $this->error('分类必须选择！');
        empty($data['cover']) && $this->error('封面必须上传！');
        empty($data['price']) && $this->error('价格必须收入且为数字！');
        empty($data['num']) && $this->error('众筹人数必须收入且为数字！');
        empty($data['info']) && $this->error('简介必须填写！');
        if(iconv_strlen($data['title']) > 20){
            $this->error('标题不能超过20个字符');
        }
        if(strlen($data['info']) < 10){
            $this->error('简介不能少于10个字符');
        }
        
        if($data){
            $data['uid'] = $this->mid;
            $rst = M('Crowdfunding')->add($data);
            if($rst){
                $this->success('操作成功，等待管理员审核！');
            }else{
                $this->error('操作失败，请重新提交！');
            }
        }else{
            $this->error(D('Crow')->getError());
        }
        
    }
    
    
    /**
     * 众筹详细信息
     */
    public function view(){
        $map = array();
        $id = intval(t($_GET['id']));
        $map['id'] = array('eq',$id);
        $field = 'id,uid,title,category,cover,num,price,info,stime,etime,vstime,vetime,collect_count,status';
        $data = D('Crow')->getInfo($map,$field);
        empty($data) && $this->error('众筹不存在！');
        $data['averagePrice'] = sprintf("%.2f",substr(sprintf("%.3f", $data['price']/$data['num']), 0, -1));
        
        //获取用户关注状态
        $fstatus=M('UserFollow')->where(array('uid'=>$this->mid,'fid'=>$data['uid']))->find();
        $isFollow = $fstatus ? 1 : 0;
        //发起人信息
        $initator = model('User')->getUserInfo($data['uid']);
        $tmp = getFollowCount(array($data['uid']));
        $initator['follower'] = $tmp[$data['uid']]['follower'];
        //获取众筹用户
        $user = D('Crow')->userList($id);
        if(!empty($user)){
            $followUids = getSubByKey($user,'uid');
            if($data['status'] != 3){
                if(in_array($this->mid,$followUids)){
                    $data['status'] = 5; //等待众筹
                }
            }
            $follow = getFollowCount($followUids);
            foreach ($user as $key=>$val){
                $user[$key]['uinfo'] = model('User')->getUserInfo($val['uid']);
                $user[$key]['follower'] = $follow[$val['uid']]['follower'];
            }
        }
        //众筹失败
        if( $data['status'] == 2 && $data['etime'] < time()){
            $data['status'] = 6;
        }
        $this->assign("isfollow",$isFollow);
        $this->assign('initator',$initator);
        $this->assign('user',$user);
        $this->assign('data',$data);
        $this->display();
    }
    
    
    /**
     * 加入众筹页面
     */
    public function joinCrow(){
        $id = t($_GET['id']);
        $isCrow = D('Crow')->isCrowing($id);
        if(!$isCrow) $this->error(D('Crow')->getError());
        
        //TODO:用户余额计算
        $balance = D("zyLearnc")->getUser($this->mid);
        if(IS_POST){
            if($balance['balance'] < $isCrow['averagePrice']){
                $this->error('余额不足！');
            }
            
            //TODO:冻结资金
            
        }
        
        $this->assign('balance',$balance);
        $this->assign('id',$id);
        $this->assign('crowUser',$isCrow);
        $this->display();
    }
    
    
    
    /**
     * 专题
     */
    public function special(){
        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 15);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();
        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);
        //热门众筹
        $model = M('Crowdfunding');
        $hot = $model->where(array('status'=>2))->order('collect_count DESC , recommend DESC')->limit(15)->select();
        $hotIds = getSubByKey($hot,'id');
        $category = $this->getCategory();
        $map['status'] = 2;
        $map['id'] = array('not in',$hotIds);
        foreach ($category as $key=>&$val){
            $map['category'] = array('eq',$val['zy_currency_category_id']);
            $val['list'] = $model->where($map)->limit(3)->order('sort DESC,recommend DESC')->select();
            if(!empty($val['list'])){
                foreach ($val['list'] as $k=>$v){
                    //是否收藏
                    $val['list'][$k]['isCollect'] = D('ZyCollection')->isCollect($v['id'], 'crowdfunding', intval($this->mid));
                }
            }else{
                unset($category[$key]);
            }
        }
        
        $this->assign('hot',$hot);
        $this->assign('ad_list',$ad_list);
        $this->assign('cate',$category);
        $this->display();
    }
    
    
    /**
     * 评论操作
     */
    public function addComment(){
        $data['uid'] = $this->mid;
        $data['fid'] = intval(t($_POST['to_uid']));
        $data['app_id'] = intval(t($_POST['app_id']));
        $data['app_uid'] = intval(t($_POST['app_uid']));
        $data['app_table'] = t($_POST['app_table']);
        $data['to_comment_id'] = intval(t($_POST['to_comment_id']));
        $model = M('zy_comment');
        if(!empty($data['to_comment_id'])){
            $comment = $model->where(array('id'=>$data['to_comment_id'],'is_del'=>0))->find();
            if(empty($comment)){
                $rtn['status'] = 0;
                $rtn['info'] = '评论内容不存在！';
                exit(json_encode($rtn));
            }
            $data['info'] = $comment['to_comment'];
        }
        $data['to_comment'] = t($_POST['content']);
        $data['ctime'] = time();
        $rst = $model->add($data);
        if($rst){
            $rtn['status'] = 1;
            $rtn['info'] = '评论成功！';
        }else{
            $rtn['status'] = 0;
            $rtn['info'] = '评论失败！';
        }
        exit(json_encode($rtn));
    }
    
    
    /**
     * 众筹失败定时任务，退换用户金额
     */
    public function crontab(){
        $time = time();
        $map['etime'] = array('lt',$time);
        $map['status'] = 2;
        $model = M('crowdfunding');
        $list = $model->where($map)->select();
        $cids = getSubByKey($list,'id');
        $users = array();
        if($cids){
            foreach ($list as $k=>$v){
                $newArr[$v['id']] = $v;
            }
            $users = M('crowdfunding_user')->where(array('cid'=>array('in',$cids)))->select();
            $uids = implode(',',getSubByKey($users,'uid'));
            
            if(!empty($users)){
                $model->startTrans();
                $rst = $model->where($map)->save(array('status'=>4));
                $addArr = array();
                if($rst){
                    // 退回金额，加入到用户余额当中
                    $sql = "UPDATE `".C('DB_PREFIX')."zy_learncoin` SET `balance` = CASE `uid` ";
                    foreach ($users as $key=>$val){
                        $sql .= sprintf("WHEN %d THEN %s ", $val['uid'], "`balance`+ '".$newArr[$val['cid']]['price']."'");
                    }
                    $sql .= "END WHERE `uid` IN ($uids)";
                    $res = M()->query($sql);
                    if($res !== false){
                        $model->commit();
                    }else{
                        $model->rollback();
                    }
                }else{
                    $model->rollback();
                }
            }else{
                $rst = $model->where($map)->save(array('status'=>4));
            }
        }
        
        
    }
    
    
    /**
     * 保留2位小数点，且不四舍五入
     * @param int $num
     * @return int
     */
    public function getFloatNum($num){
        $num = sprintf("%.2f",substr(sprintf("%.3f", $num), 0, -1));
        return $num;
    }
    
    
    
    /**
     * 检查课程购买
     */
    public function checkPayOperat(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;
    
        //使用后台提示模版
        $this->assign('isAdmin', 1);
        //必须要先登陆才能进行操作
        if(!$this->mid){
            $this->mzError("请先登录再进行购买");
        }
        $vid = intval($_POST['vid']);
        $check_type = $_POST['check_type'];
        if(!$vid){
            $this->mzError("参数错误");
        }
        $pay_list = array('alipay','unionpay','wxpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->mzError("支付方式错误");
        }
         
        if($check_type == 'crowdfunding'){ //众筹订单
            $crowMap['id'] = array('eq',$vid);
            $field = 'id,uid,title,cover,num,price,collect_count,status';
            $crow = D('Crow')->getInfo($crowMap,$field);
            $crowedUser = D('Crow')->joinUser($vid);
            empty($crow) && $this->mzError('众筹不存在！');
            if($crow['num'] <= $crowedUser){
                $this->mzError('众筹已结束！');
            }
            if($crow['status'] != 2){
                $this->mzError('不可众筹！');
            }
            //是否已购买验证
            $user = D('Crow')->userList($vid);
            if(!empty($user)){
                $followUids = getSubByKey($user,'uid');
                if(in_array($this->mid,$followUids)){
                    $this->mzError('你已购买！');
                }
            }
            //添加众筹记录
//            $addData['uid'] = $this->mid;
//            $addData['crow_id'] = $vid;
//            $addData['price'] = $this->getFloatNum($crow['price']/$crow['num']);
//            $addData['ctime'] = time();
//            $rst = M('zy_order_crow')->add($addData);
//            if(!$rst){
//                $this->mzError('众筹记录添加失败！');
//            }
            $this->mzSuccess('成功');
        } else {
            $this->mzError('请勿篡改购买类型，我们会发现哦');
        }
    }
    
    
    /**
     * 购买众筹
     */
    public function payLibrary(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;
    
        //使用后台提示模版
        $this->assign('isAdmin', 1);
        $check_type = $_POST['check_type'];
        //必须要先登陆才能进行操作
        if(!$this->mid){
            $this->error('请先登录再进行购买');
        }
        $vid = intval($_POST['vid']);
        if(!$vid){
            $this->error("参数错误");
        }
    
        $pay_list = array('alipay','unionpay','wxpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->error('支付方式错误');
        }
    
        //TODO:计算价格
        $crowMap['id'] = array('eq',$vid);
        $field = 'id,uid,title,cover,num,price,collect_count,status';
        $crow = D('Crow')->getInfo($crowMap,$field);
        if(!$crow){
            $this->error('众筹课程不存在');
        }
        
        $money = $this->getFloatNum($crow['price']/$crow['num']);
        $title = t($_POST['title']);
    
        $money = floatval($money);
        $money = 0.01;



        $re = D('ZyRecharge');
        $id = $re->addRechange(array(
                'uid'      => $this->mid,
                'type'     => 1,
                'money'    => $money,
                'note'     => "跟我学-购买{$title}",
                'pay_type' => $_POST['pay'],
        ));

        
        if(!$id) $this->error("操作异常");

        $data['uid'] = $this->mid;
        $data['crow_id'] = intval($_POST['vid']);
        $data['ctime'] =  time();
        $data['order_id'] = $id;
        $data['status'] = 1;
        $data['price'] = $money;
        $data['is_del'] = 0;
        $res = M('zy_order_crow') ->add($data);
        
        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                    'vid'          => intval($_POST['vid']),
                    'vtype'        => $check_type,
                    'out_trade_no' => $id,
                    'total_fee'    => $money,
                    'subject'      => "跟我学-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                    'vid'       => intval($_POST['vid']),
                    'vtype'     => $check_type,
                    'id'        => $id,
                    'money'     => $money,
                    'subject'   => "跟我学-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'wxpay'){
            $res = $this->wxpay(array(
                    'vid'           => intval($_POST['vid']),
                    'vtype'         => $check_type,
                    'out_trade_no'  => $id,
                    'total_fee'     => $money * 100,//单位：分
                    'subject'       => "跟我学-购买{$title}",
            ));
            if($res){
                $this->assign('url',$res);
                $html = $this->fetch('wxpay');
                $data = array('status'=>1,'data'=>['html'=>$html,'trade_no'=>$id]);
                echo json_encode($data);
                exit;
            }
        }
    }


    /**
     * @name 阿里支付
     * @packages protected
     */
    protected function alipay($args){
        $alipay_config = $this->getAlipayConfig();
        //初始化类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','Alipay.php')));
        $alipayClass = new \Alipay($alipay_config);

        //设置支付的Data信息
        $alipayClass->setConfig(array(
            "out_trade_no"  => $args['out_trade_no'].'h'.date('YmdHis',time()).mt_rand(1000,9999),//商户网站订单系统中唯一订单号，必填
            "subject"   => $args['subject'],//订单名称
            "total_fee" => $args['total_fee'],//付款金额
            "body"  => isset($args['body'])?$args['body']:'',//订单描述
            "show_url"  => isset($args['show_url'])?$args['show_url']:'',//商品展示地址
            "exter_invoke_ip" => get_client_ip(),//客户端的IP地址
            "_input_charset"  => trim(strtolower($alipay_config['input_charset'])),
            "notify_url"      => 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipayCrowAnsy.html',
            "return_url"      => U('classroom/Crow/aliru'),//页面跳转同步通知页面路径
        ));

        $alipayClass->addData('extra_common_param',json_encode(array('vid'=>$args['vid'],'type'=>$args['type'],'total_fee'=>$args['total_fee'])));

        //调用阿里的服务,默认调用PC端支付
        $res = $alipayClass->goAliService();
        echo $res;exit;
    }

    /**
     * @name 阿里支付回调 服务器异步通知页面路径
     * @packages public
     */
    public function alipayCrowAnsy(){
        file_put_contents('alipayre.txt',json_encode($_POST));
        $alipay_config = $this->getAlipayConfig();
        //引入类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','AlipayNotify.php')));
        //初始化
        $alipayNotify = new \AlipayNotify($alipay_config);
        //验证结果
        $verify_result = $alipayNotify->verifyNotify();
        if(!$verify_result) exit('fail');
        file_put_contents('alipay_success.txt',json_encode($_POST));
        //商户订单号
        $out_trade_no = stristr($_POST['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];

        //自定义数据
        $extra_common_param = json_decode($_POST['extra_common_param'],true);
        $vid      = intval($extra_common_param['vid']);
        $type    = $extra_common_param['type'];
        $total_fee    = $extra_common_param['total_fee'];

        $re = D('ZyRecharge');
        if ($trade_status == 'TRADE_FINISHED') {
            $result = $re->setNormalPaySuccess2($out_trade_no,$trade_no);
            if ($result) {
                $this_uid = $re->where('id = '.$out_trade_no)->getField('uid');
                $ctime = $re->where('id = '.$out_trade_no)->getField('ctime');
                $pay_status = M('zy_order_crow')->where(array('uid'=>$this_uid,'ctime'=>$ctime))->getField('status');

                if($pay_status == 3){
                    echo '购买成功';
                }else{
                    $con_order_info = $this->buyOperating($vid, $out_trade_no, $type);
                    if ($con_order_info == 1) {
                        echo '购买成功';
                    } else {
                        echo '购买失败';
                    }
                }
            }else{
                echo '购买失败';
            }
        }elseif($trade_status == 'TRADE_SUCCESS'){
            $result = $re->setNormalPaySuccess($out_trade_no,$trade_no);
            if ($result) {
                $this_uid = $re->where('id = '.$out_trade_no)->getField('uid');
                $ctime = $re->where('id = '.$out_trade_no)->getField('ctime');
                $pay_status = M('zy_order_crow')->where(array('uid'=>$this_uid,'ctime'=>$ctime))->getField('status');

                if($pay_status == 3){
                    echo '购买成功';
                }else{
                    $con_order_info = $this->buyOperating($vid, $out_trade_no, $type);
                    if ($con_order_info == 1) {
                        echo '购买成功';
                    } else {
                        echo '购买失败';
                    }
                }
            }else{
                echo '购买失败';
            }
        }
        echo 'success';

    }

    /**
     * @name 阿里支付回调 页面跳转同步通知页面路径
     * @packages public
     */
    public function aliru()
    {
        unset($_GET['app'], $_GET['mod'], $_GET['act']);
        $alipay_config = $this->getAlipayConfig();
        //引入类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','AlipayNotify.php')));
        //初始化
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        $this->assign('isAdmin', 1);

        //        if(!$verify_result) $this->error('操作异常');
        //商户订单号
        $out_trade_no = stristr($_GET['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_GET['trade_no'];
        //交易状态
        $trade_status = $_GET['trade_status'];

        //自定义数据
        $extra_common_param = json_decode($_GET['extra_common_param'],true);


        $vid = intval($extra_common_param['vid']);
        $vtype = $extra_common_param['vtype'];
        $coupon_id = $extra_common_param['coupon_id'];


        $this->assign('jumpUrl', U('classroom/Crow/view',array('id'=>$vid)));

        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

            $result = D('ZyRecharge')->setNormalPaySuccess2($out_trade_no, $trade_no);
            if ($result) {
                $this_uid = D('ZyRecharge')->where('id = '.$out_trade_no)->getField('uid');
                $ctime = D('ZyRecharge')->where('pay_order = '.$trade_no)->getField('ctime');


                $pay_status = M('zy_order_crow')->where(array('uid'=>$this_uid,'ctime'=>$ctime))->getField('status');

                if($pay_status == 3){
                    $this->doSaveCrowdfunding($vid);
                    $this->success('购买成功');
                }else{
                    $con_order_info =  $this->buyOperating($vid, $out_trade_no, $vtype);
                    if ($con_order_info == 1) {
                        $this->doSaveCrowdfunding($vid);
                        $this->success('购买成功');
                    } else {
                        $this->error('购买失败!');
                    }
                }
            }else{
                $this->error('购买失败');
            }
        }

    }

    public function  buyOperating($vid,$out_trade_no,$vtype){
        
        $data['ptime']      = time();
        $data['status'] = 3;
        $data['order_id'] = $out_trade_no;
        $data['corw_id'] = $vid;

        $res = M('zy_order_crow')->where(array('order_id'=>$out_trade_no,'crow_id'=>$vid))->save($data);

        if($res)
        {
            return 1;//购买成功
        }
        else{
            return 0;//购买失败
        }

    }

    /**
     * @name 银联支付
     * @packages protected
     */
    protected function unionpay($args){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        $param['transType']     = quickpay_conf::CONSUME;  //交易类型，CONSUME or PRE_AUTH
        $param['commodityName'] = $args['subject'];
        $param['orderAmount']   = $args['money']*100;        //交易金额
        $param['orderNumber']   = $args['id']+10000000; //订单号，必须唯一
        $param['orderTime']     = date('YmdHis');   //交易时间, YYYYmmhhddHHMMSS
        $param['orderCurrency'] = quickpay_conf::CURRENCY_CNY;  //交易币种，CURRENCY_CNY=>人民币
        $param['customerIp']    = get_client_ip();//客户端的IP地址
        //$param['frontEndUrl']   = SITE_URL.'/classroom/Pay/unionru';    //前台回调URL
        //$param['backEndUrl']    = SITE_URL.'/classroom/Pay/unionnu';    //后台回调URL
        $param['frontEndUrl']   = U('classroom/Pay/unionru');    //前台回调URL
        $param['backEndUrl']    = U('classroom/Pay/unionnu');    //后台回调URL
        //print_r($param);exit;
        $pay_service = new quickpay_service($param, quickpay_conf::FRONT_PAY);
        $html = $pay_service->create_html();
        header("Content-Type: text/html; charset=" . quickpay_conf::$pay_params['charset']);
        echo $html; //自动post表单
    }

    /**
     * @name 银联支付回调
     * @packages public
     */
    public function unionru(){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        $this->assign('isAdmin', 1);
        $this->assign('jumpUrl', U('classroom/User/recharge'));
        try {
            $response = new quickpay_service($_POST, quickpay_conf::RESPONSE);
            if ($response->get('respCode') != quickpay_service::RESP_SUCCESS) {
                $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
                throw new Exception($err);
            }
            $arr_ret = $response->get_args();
            $id = $arr_ret['orderNumber']-10000000;
            $qid = $arr_ret['qid'];
            $re = D('ZyRecharge');
            $result = $re->setSuccess($id, $qid);
            if($result){
                $this->success('充值成功！');
            }else{
                $this->error('充值失败！');
            }
        }catch(Exception $exp) {
            $this->error('操作异常！');
            //$str .= var_export($exp, true);
            //die("error happend: " . $str);
        }
    }

    /**
     * @name 微信支付
     * @packages protected
     */
    protected function wxpay($data){
        $url = '';
        if($data){
            require_once SITE_PATH.'/api/pay/wxpay/WxPay.php';
            $input = new WxPayUnifiedOrder();
            $attr  = json_encode(array('vid'=>$data['vid'],'vtype'=>$data['vtype'],'total_fee'=>$data['total_fee']));
            $body  = isset($data['subject']) ? $data['subject'] :"跟我学-购买";
            $out_trade_no = $data['out_trade_no'].'h'.date('YmdHis',time()).mt_rand(1000,9999);//stristr
            $input->SetBody($body);
            $input->SetAttach($attr);//自定义数据
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($data['total_fee']);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url('http://'.$_SERVER['HTTP_HOST'].'/api/pay/wxpay/notify.php');
            $input->SetTrade_type("NATIVE");
            $input->SetProduct_id($data['out_trade_no']);
            $notify = new NativePay();
            $result = $notify->GetPayUrl($input);
            $url = $result["code_url"];
        }
        return $url;
    }


    /**
     * @name 微信回调
     */
    public function wxpay_success(){
        if($_GET['openid']){
            $re = D('ZyRecharge');
            $out_trade_no = stristr($_GET['out_trade_no'],'h',true);
            $re->setWxPaySuccess($out_trade_no, $_GET['transaction_id'],$_GET['attach']);
//            $da['vip_length']=$_GET['out_trade_no'];
//            $da['note']=$_GET['transaction_id'];
//            $da['pay_order']=$_GET['openid'];
//            $da['pay_type']=$_GET['attach'];
//            D('ZyRecharge')->add($da);
        }
    }

    /**
     * @name 微信查询支付状态
     */
    public function getPayStatus(){
        $id = $_POST['order'];
        $data = M('zy_recharge')->where(['id'=>$id])->find();
        if($data['status'] == 1){
            $attach = json_decode($data['note_wxpay'],true);
            $order_info = $this->buyOperating($attach['vid'],$data['id'],$attach['vtype']);
            if($order_info == 1){
                $this->doSaveCrowdfunding($attach['vid']);
                $info = '购买成功!';
            }else{
                $info = '购买失败';
            }
            echo json_encode(['status'=>1,'info'=>$info]);exit;
        }else{
            echo json_encode(['status'=>0]);exit;
        }
    }

    /**
     * 获取阿里支付配置
     */
    protected function getAlipayConfig(){
        $config = array(
            'cacert' => join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','cacert.pem')),
            'input_charset'    => strtolower('utf-8'),
            'sign_type' =>  strtoupper('RSA'),
        );
        $conf = unserialize(M('system_data')->where("`list`='admin_Config' AND `key`='alipay'")->getField('value'));
        if(is_array($conf)){
            $config = array_merge($config, array(
                'partner'   =>$conf['alipay_partner'],
                'key'       =>$conf['alipay_key'],
                'seller_email'=> $conf['seller_email'],
                'private_key_path' => $conf['private_key'],
                'ali_public_key_path'    => $conf['public_key']
            ));
        }
        return $config;
    }

    /**
     *  @name 加入众筹
     */
    public function doSaveCrowdfunding($cid){

        $data['uid'] = $this->mid;
        $data['cid'] = $cid;
        $data['ctime'] = time();

        if($cid && $data['uid']){
            $re = M('crowdfunding_user')->add($data);
        }
        if($re){
            $crowMap['id'] = $cid;
            $crow = D('Crow')->where($crowMap)->getField('num');
            $crowedUser = D('Crow')->joinUser($cid);
            if($crow <= $crowedUser){
                M('crowdfunding')->where(array('id'=>$cid))->save(array('status'=>3));
            }
        }
        return $res;
    }
}