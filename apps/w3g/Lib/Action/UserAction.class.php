<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class UserAction extends Action
{
	//初始化
	public function _initialize() {

        if($this->mid==0){
            U('w3g/Passport/login','',true);
        }
    }
	public function index() {
		$twcont=D("ZyQuestion","classroom")->where(array('uid'=>$this->mid))->count();//加载提问数量
        $videocont=D("ZyOrder","classroom")->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的课程总数
        $commcont=M("ZyWendaComment","classroom")->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的评论
        $wdcont=M('ZyWenda',"classroom")->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的问答数量
        $note=M('ZyNote',"classroom")->where(array('uid'=>$this->mid))->count();
        //查询某个用户的学币
        $balance=0;
        $user = D('ZyLearnc',"classroom")->getUser($this->mid);
	    if ($user) {
	        $balance=$user['balance'];
	    }
        $this->assign("videocont",$videocont);
        $this->assign("twcont",$twcont);
        $this->assign("commcont",$commcont);
        $this->assign("note",$note);
        $this->assign("balance",$balance);
        $this->assign("credits", model('Credit')->getUserCredit($this->mid));
        $this->assign("tmp",model('Follow')->getFollowCount($this->mid));
		$this->display();
	}
    //用户设置
    public function setInfo(){
        //用户信息
        $this->setUser();
        $this->display();
    }
     public function saveUser(){
        //简介
        $save['intro'] = t($_POST['intro']);
        //性别
        $save['sex']   = 1 == intval($_POST['sex']) ? 1 : 2;
        //位置信息
        $save['location'] = t($_POST['city_names']);
        //职业
        $save['profession'] = t($_POST['profession']);
        //地区
        $cityIds = t($_POST['city_ids']);
        $cityIds = explode(',', $cityIds);
       
                $this->assign('isAdmin',1);
        if(!$cityIds[0] || !$cityIds[1]) $this->error('请选择完整地区');
        isset($cityIds[0]) && $save['province'] = intval($cityIds[0]);
        isset($cityIds[1]) && $save['city'] = intval($cityIds[1]);
        isset($cityIds[2]) && $save['area'] = intval($cityIds[2]);
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
        $user = $this->get('user');
        $my_college = D('ZySchoolCategory',"classroom")->getParentIdList($user['my_college']);
        $signup_college = D('ZySchoolCategory',"classroom")->getParentIdList($user['signup_college']);
        $this->assign('my_college', $my_college?$my_college:'');
        $this->assign('signup_college', $signup_college?$signup_college:'');
    }
    public function verified(){
        //认证
        $this->rz();
        $verified_category=M("user_verified_category")->field("title,user_verified_category_id")->select();
        $this->assign("verified_category",$verified_category);
        $this->display();
    }
    //用户认证
    protected function rz(){
        $auType = model('UserGroup')->where('is_authenticate=1')->findall();
        $this->assign('auType', $auType);
        $verifyInfo = D('user_verified')->where('uid='.$this->mid)->find();
        if($verifyInfo['attach_id']){
              $a = explode('|', $verifyInfo['attach_id']);
              foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
              }
        }
        if($verifyInfo['other_data']){
              $a = explode('|', $verifyInfo['other_data']);
              foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['other_data_list'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
              }
        }
        // 获取认证分类信息
        if(!empty($verifyInfo['user_verified_category_id'])) {
            $verifyInfo['category']['title'] = D('user_verified_category')->where('user_verified_category_id='.$verifyInfo['user_verified_category_id'])->getField('title');
        }

        switch ($verifyInfo['verified']) {
            case '1':
                $status = '<i class="ico-ok"></i>已认证 <a href="javascript:void(0);" onclick="delverify()" style="color:#65addd">注销认证</a>';
                break;
            case '0':
                $status = '<i class="ico-wait"></i>已提交认证，等待审核';
                break;
            case '-1':
                // 安全过滤
                $type = t($_GET['type']);
                if($type == 'edit'){
                    $status = '<i class="ico-no"></i>未通过认证，请修改资料后重新提交';
                    $this->assign('edit',1);
                    $verifyInfo['attachIds'] = str_replace('|', ',', substr($verifyInfo['attach_id'],1,strlen($verifyInfo['attach_id'])-2));
                    $verifyInfo['other_data_ids'] = str_replace('|', ',', substr($verifyInfo['other_data'],1,strlen($verifyInfo['other_data'])-2));
                }else{
                    $status = '<i class="ico-no"></i>未通过认证，请修改资料后重新提交 <a style="color:#65addd" href="'.U('classroom/User/setInfo',array('type'=>'edit', 'tab'=>3)).'">修改认证资料</a>';
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
        //dump($option);exit;
        $this->assign('option', json_encode($option));
        $this->assign('options', $option);
        $this->assign('category', $category);
        $this->assign('status' , $status);
        $this->assign('verifyInfo' , $verifyInfo);
        //dump($verifyInfo);exit;

        $user = model('User')->getUserInfo($this->mid);

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
    //银行卡管理方法
    public function card(){
        $data = D('ZyBcard',"classroom")->getUserOnly($this->mid);
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $set['uid'] = $this->mid;
            $set['account'] = t($_POST['account']);
            $set['accountmaster'] = t($_POST['accountmaster']);
            $set['accounttype'] = t($_POST['accounttype']);
            $set['bankofdeposit'] = t($_POST['bankofdeposit']);
            $set['tel_num'] = t($_POST['tel_num']);
            $set['location'] = t($_POST['city_names']);
            $set['province'] = intval($_POST['province']);
            $set['area'] = intval($_POST['area']);
            $set['city'] = intval($_POST['city']);
            if($data){
                $set['id'] = $data['id'];
                if(false !== D('ZyBcard',"classroom")->save($set)){
                    $this->ajaxReturn(null, '', true);
                }else{
                    $this->ajaxReturn(null, '', false);
                }
            }else{
                if(D('ZyBcard',"classroom")->add($set) > 0){
                    $this->ajaxReturn(null, '', true);
                }else{
                    $this->ajaxReturn(null, '', false);
                }
            }
            exit;
        }
        $this->assign('isEditCard', !$data || $_GET['edit']=='yes');
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
        $this->assign('data', $data);
        $this->assign('banks', D('ZyBcard',"classroom")->getBanks());
        $this->display();
    }
	/**
    * 会员中心课程--列表处理
    * @return void
    */

    public function video(){
        $this->display();
    }
	
	/**
    * 异步加载我购买的课程
    * @return void
    */
    public function getbuyvideoslist(){
        $limit      = intval($_POST['limit']);
        $uid        = intval($this->mid);
        $limit      = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX').'zy_video';
        $otablename = C('DB_PREFIX').'zy_order';
        //拼接字段
        $fields     = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_intro`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.video_order_count";
        //不是通过班级购买的

        $where     = "{$otablename}.`is_del`=0 and {$otablename}.`uid`={$uid}";
        $data = M('ZyOrder',"classroom")->join("{$vtablename} on {$otablename}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        $vms = D('ZyVideoMerge',"classroom")->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
    
        //把数据传入模板
        $this->assign('listData',$data['data']);
        //取得数据
        $data['data'] = $this->fetch('_video_my_buy');
        echo json_encode($data);exit;
    }
	/**
    * 异步加载我收藏的课程
    * @return void
    */
    public function getcollectvideolist(){
        //获取购物车参数
        $vms = D('ZyVideoMerge',"classroom")->getList($this->mid, session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //获取已购买课程id
        $buyVideos = D('zyOrder',"classroom")->where("`uid`=".$this->mid." AND `is_del`=0")->field('video_id')->select();
            foreach($buyVideos as $key=>$val){
                $buyVideos[$key] = $val['video_id'];
            }
        $this->assign('buyVideos',$buyVideos);

        $limit =9;

        $uid        = intval($this->mid);
        //拼接两个表名
        $vtablename = C('DB_PREFIX').'zy_video';
        $ctablename = C('DB_PREFIX').'zy_collection';
        
        $fields     = '';
        $fields .= "{$ctablename}.`uid`,{$ctablename}.`collection_id` as `cid`,";
        
        $fields .="{$vtablename}.*";
        //拼接条件
        $where      = "{$ctablename}.`source_table_name`='zy_video' and {$ctablename}.`uid`={$uid}";
        //取数据
        $data = D('ZyCollection',"classroom")->join("{$vtablename} on {$ctablename}.`source_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        //循环计算课程价格
        foreach($data['data'] as &$val){
            $val['money']=getPrice($val,$this->mid);
        }
  
        $vms = D('ZyVideoMerge',"classroom")->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        //把数据传入模板
        $this->assign('listData',$data['data']);
  
        
        //取得数据
        $data['data'] = $this->fetch('_video_my_collect');
        echo json_encode($data);exit;
    }
    /**
     * 修改登录用户帐号密码操作
     * @return json 返回操作后的JSON信息数据
     */
    public function doModifyPassword() {
        // 验证信息
        if ($_POST['oldpassword'] === '') {
            echo '请填写原始密码'; exit();
        }
        if ($_POST['password'] === '') {
            echo '请填写新密码'; exit();
        }
        if ($_POST['repassword'] === '') {
            echo '请填写确认密码'; exit();
        }
        if($_POST['password'] != $_POST['repassword']) {
           echo L('PUBLIC_PASSWORD_UNSIMILAR'); exit();          // 新密码与确认密码不一致
        }
        if(strlen($_POST['password']) < 6) {
           echo '密码太短了，最少6位'; exit();             
        }
        if(strlen($_POST['password']) > 15) {
            echo '密码太长了，最多15位'; exit();                
        }
        if($_POST['password'] == $_POST['oldpassword']) {
            echo L('PUBLIC_PASSWORD_SAME'); exit();                // 新密码与旧密码相同
        }

        $user_model = model('User');
        $map['uid'] = $this->mid;
        $user_info = $user_model->where($map)->find();
        if($user_info['password'] == $user_model->encryptPassword($_POST['oldpassword'], $user_info['login_salt'])) {
            $data['login_salt'] = rand(11111, 99999);
            $data['password'] = $user_model->encryptPassword($_POST['password'], $data['login_salt']);
            $res = $user_model->where("`uid`={$this->mid}")->save($data);
            if($res){
                echo $res;
                exit();
            }else{
                echo L('PUBLIC_PASSWORD_MODIFY_FAIL');
                exit();
            }
        } else {
            echo L('PUBLIC_ORIGINAL_PASSWORD_ERROR');
            exit();
        }
    }
    public function bind(){
        //帐号绑定
        $bindData = array();
        Addons::hook('account_bind_after',array('bindInfo'=>&$bindData));
        $bindType = array();
        foreach($bindData as $k=>$rs) $bindType[$rs['type']] = $k;
        $verified_category=M("user_verified_category")->where("pid=3")->field("title,user_verified_category_id")->select();
        $this->assign("verified_category",$verified_category);
        $data['bindType']  = $bindType;
        $data['bindData']  = $bindData;
        $this->assign($data);
        $this->display();
    }
    public function recharge(){
        $user_vip = M('user_vip')->where('is_del=0')->order('sort asc')->select();
        $learnc = D('ZyLearnc',"classroom");
        $data = $learnc->getUser($this->mid);
        if($data['vip_type'] > 0 && $data['vip_expire'] >= time() ){
            $data['vip_type_txt'] = M('user_vip')->where('is_del=0 and id=' . $data['vip_type'])->getField('title');
        } else {
            $data['vip_type'] = 0;
        }
        $this->assign('learnc', $data);
        $this->assign('user_vip', $user_vip);
        $this->display();
    }
    /**
     * 充值学币
     */
    public function dorecharge(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        //必须要先登陆才能进行操作
        if($this->mid <= 0){
          echo '请先登录在进行充值';
          exit();  
        } 
        if($_POST['pay']!='alipay'&&$_POST['pay']!='unionpay'){
            echo '支付方式错误';
            exit();  
        }

        $money = floatval($_POST['money']);
        if($money <= 0){
            echo '请选择或填写充值金额';
            exit(); 
        }
        $rechange_base = getAppConfig('rechange_basenum');
        if($rechange_base>0 && $money%$rechange_base != 0){
            if($rechange_base == 1){
                echo '充值金额必须为整数';
                exit();
            }else{
                echo '充值金额必须为{$rechange_base}的倍数';
                exit();
            }
        }
        //$money = 0.01;
        $re = D('ZyRecharge','classroom');
        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => '0',
            'money'    => $money,
            'note'     => "Eduline-学币充值-{$money}元",
            'pay_type' => $_POST['pay'],
        ));
        if(!$id) $this->error("操作异常");
        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'out_trade_no' => $id,
                'subject'      => 'Eduline-学币充值',
                'total_fee'    => $money,
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'id' => $id,
                'money' => $money,
                'subject' => 'Eduline-学币充值'
            ));
        }
    }
     /**
     * 充值VIP
     */
    public function rechargeVip(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        //必须要先登陆才能进行操作
        if($this->mid <= 0) $this->error('请先登录在进行充值');

        //检查支付方式
        if($_POST['pay']!='alipay'&&$_POST['pay']!='unionpay'){
            $this->error('支付方式错误');
        }

        //检查充值类型
        if($_POST['type']!=1 && $_POST['type']!=0){
            $this->error('支付类型错误');
        }
        $type = intval($_POST['user_vip']);
        $vip_length = "+1 year";
        $vip = M('user_vip')->where('id=' . $type)->find();
        $money = $vip['vip_year'];
        
        //$money = 0.01;
        $re = D('ZyRecharge',"classroom");
        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => $type,
            'vip_length' => $vip_length,
            'money'    => $money,
            'note'     => "Eduline-{$vip['title']}充值",
            'pay_type' => $_POST['pay'],
        ));
        if(!$id) $this->error("操作异常");
        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'out_trade_no' => $id,
                'subject'      => "Eduline-{$vip['title']}充值",
                'total_fee'    => $money,
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'id' => $id,
                'money' => $money,
                'subject' => "Eduline-{$vip['title']}充值",
            ));
        }
    }
    protected function alipay($args){
        include SITE_PATH.'/api/pay/alipay/alipay.php';
        
        //获取后台配置的支付宝接口数据
        $alipay_config = $this->getAlipayConfig($alipay_config);
        //构造要请求的参数数组，无需改动
        $parameter = array(
                "service" => "create_direct_pay_by_user",
                "partner" => trim($alipay_config['partner']),
                "payment_type"  => '1',//支付类型
                //"notify_url"    => SITE_URL.'/classroom/Pay/alinu',//服务器异步通知页面路径
                //"return_url"    => SITE_URL.'/classroom/Pay/aliru',//页面跳转同步通知页面路径
                "notify_url"    => U('w3g/user/alinu'),//服务器异步通知页面路径
                "return_url"    => U('w3g/user/aliru'),//页面跳转同步通知页面路径
                //读取支付宝卖家账户配置
                "seller_email"  => $alipay_config['seller_email'],//卖家支付宝帐户
                "out_trade_no"  => $args['out_trade_no'],//商户网站订单系统中唯一订单号，必填
                "subject"   => $args['subject'],//订单名称
                "total_fee" => $args['total_fee'],//付款金额
                "body"  => isset($args['body'])?$args['body']:'',//订单描述
                "show_url"  => isset($args['show_url'])?$args['show_url']:'',//商品展示地址
                "anti_phishing_key" => '',//防钓鱼时间戳
                "exter_invoke_ip"   => get_client_ip(),//客户端的IP地址
                "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get");
        echo $html_text;
    }
    protected function getAlipayConfig($config){
        $conf = unserialize(M('system_data')->where("`list`='admin_Config' AND `key`='alipay'")->getField('value'));
        if(is_array($conf)){
            $config = array_merge($config, array(
                'partner'=>$conf['alipay_partner'],
                'key'=>$conf['alipay_key'],
                'seller_email'=> $conf['seller_email'],
            ));
        }
        return $config;
    }
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
        $param['frontEndUrl']   = U('w3g/User/unionru');    //前台回调URL
        $param['backEndUrl']    = U('w3g/User/unionnu');    //后台回调URL
        //print_r($param);exit;
        $pay_service = new quickpay_service($param, quickpay_conf::FRONT_PAY);
        $html = $pay_service->create_html();
        header("Content-Type: text/html; charset=" . quickpay_conf::$pay_params['charset']);
        echo $html; //自动post表单
    }
    public function alinu(){
        include SITE_PATH.'/api/pay/alipay/alipay.php';
        $alipay_config = $this->getAlipayConfig($alipay_config);
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if(!$verify_result) exit('fail');
        //商户订单号
        $out_trade_no = $_POST['out_trade_no'];
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];
        $re = D('ZyRecharge','classroom');
        if($trade_status == 'TRADE_FINISHED') {
            $re->setSuccess($out_trade_no, $trade_no);
        }elseif($trade_status == 'TRADE_SUCCESS'){
            $re->setSuccess($out_trade_no, $trade_no);
        }
        echo 'success';
    }
    public function aliru(){
        unset($_GET['app'],$_GET['mod'],$_GET['act']);
        include SITE_PATH.'/api/pay/alipay/alipay.php';
        $alipay_config = $this->getAlipayConfig($alipay_config);
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        $this->assign('isAdmin', 1);
        $this->assign('jumpUrl', U('w3g/User/recharge'));
        if(!$verify_result) $this->error('操作异常');
        //商户订单号
        $out_trade_no = $_GET['out_trade_no'];
        //支付宝交易号
        $trade_no = $_GET['trade_no'];
        //交易状态
        $trade_status = $_GET['trade_status'];
        $re = D('ZyRecharge',"classroom");
        if($trade_status == 'TRADE_FINISHED'||$trade_status == 'TRADE_SUCCESS') {
            $result = $re->setSuccess($out_trade_no, $trade_no);
        }
        if($result){
            $this->success('充值成功！');
        }else{
            $this->error('充值失败！');
        }
    }
    public function unionru(){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        $this->assign('isAdmin', 1);
        $this->assign('jumpUrl', U('w3g/User/recharge'));
        try {
            $response = new quickpay_service($_POST, quickpay_conf::RESPONSE);
            if ($response->get('respCode') != quickpay_service::RESP_SUCCESS) {
                $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
                throw new Exception($err);
            }
            $arr_ret = $response->get_args();
            $id = $arr_ret['orderNumber']-10000000;
            $qid = $arr_ret['qid'];
            $re = D('ZyRecharge','classroom');
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
    public function unionnu(){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        try {
            $response = new quickpay_service($_POST, quickpay_conf::RESPONSE);
            if ($response->get('respCode') != quickpay_service::RESP_SUCCESS) {
                $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
                throw new Exception($err);
            }

            $arr_ret = $response->get_args();
            $id = $arr_ret['orderNumber']-10000000;
            $qid = $arr_ret['qid'];
            $re = D('ZyRecharge','classroom');
            $result = $re->setSuccess($id, $qid);
            if($result){
                echo 'success';
            }else{
                echo 'fail';
            }
        }catch(Exception $exp) {
            exit('fail');
            //后台通知出错
            //file_put_contents('notify.txt', var_export($exp, true));
        }
    }
    public function teacher(){
        $teacher_info=M("zy_teacher")->where("uid=".$this->mid)->find();
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
        $this->assign('teacher_level',$teacher_level);
        $this->assign("teacher_schedule",$teacher_schedule);
        $this->assign("teacherschedule",$teacherschedule);
        $this->assign("teacher_info",$teacher_info);
        $this->display(); 
    }

    //教师资料设置
    function doteacherDeatil(){
        $id = intval($_POST['id']);
        //要添加的数据
        $map=array(
        'name'=>t($_POST['name']),
        'inro'=>t($_POST['inro']),
        'title'=>t($_POST['title']),
        'ctime'=>time(),
        'teacher_age'=>t($_POST['teacher_age']),
        'label'=>t($_POST['label']),
        'high_school'=>t($_POST['high_school']),
        'teacher_schedule'=>t($_POST['teacher_schedule']),
        'graduate_school'=>t($_POST['graduate_school']),
        'teach_evaluation'=>t($_POST['teach_evaluation']),
        'teach_way'=>t($_POST['teach_way']),
        'Teach_areas'=>t($_POST['Teach_areas'])
        );
        $res=D('ZyTeacher')->where("id=".$id)->save($map);
        if(!$res)exit(json_encode(array('status'=>'0','info'=>'编辑失败')));
        exit(json_encode(array('status'=>'1','info'=>'编辑成功')));
    }
   /**
     * 教师的录播课程
     * @return void
     */
    public function getTeacherVideo(){
        $uid        = intval($this->mid);
        $limit      = 30;
        $data = M('zy_video')->where("uid=".$uid . ' and is_del=0')->order('utime desc')->findPage($limit);
    
        //把数据传入模板
        $this->assign('data',$data['data']);

        //取得数据
        $data['data'] = $this->fetch('_teacher_video');
        echo json_encode($data);exit;
    }
    /**
    * 异步加载我的约课
    * @return void
    */
    public function getbuycourselist(){
        $limit      = 9;
        $uid        = intval($this->mid);
        //拼接字段
        $fields= 'o.`uid`,o.`teach_way`,o.`id`'; 
        $fields .= ",c.`course_id`,c.`course_name`,c.`course_price`,c.`course_teacher`,c.`course_inro`";
        //不是通过班级购买的
        $where     = "o.`is_del`=".intval($_POST["is_del"])." and o.`uid`={$uid}";
        $data = M('zy_order_course o')->join("`".C('DB_PREFIX')."zy_teacher_course` c on c.`course_id`=o.`course_id`")->where($where)->field($fields)->findPage($limit);
        foreach ($data["data"] as $key => $value) {
            $data["data"][$key]["teacher_info"]=M("zy_teacher t")->join("`".C('DB_PREFIX')."user` u on u.`uid`=t.`uid`")->where("id=".$value["course_teacher"])->field("phone,name,reservation_count")->find();
            $data["data"][$key]["course_info"]=M("zy_teacher_course")->where("course_id=".$value["course_id"])->find();
        }
        //把数据传入模板
        $this->assign('data',$data['data']);
        //取得数据
        $data['data'] = $this->fetch('_course_my');
        echo json_encode($data);exit;
    }
}