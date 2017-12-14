<?php
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class PayAction extends CommonAction{

    /**
     * 充值余额
     */
    public function recharge(){
        ini_set('display_errors', '1');
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        //必须要先登陆才能进行操作
        if($this->mid <= 0) $this->error('请先登录在进行充值');
        $pay_list = array('alipay','unionpay','wxpay','cardpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->error('支付方式错误');
        }

        $money_str = t($_POST['money']);
        $money = array_filter(explode('=>',$money_str))[0] ? : 0;
        if ($_POST['pay'] != 'cardpay' && $money <= 0) {
            $this->error('请选择或填写充值金额');
        }

        $re = D('ZyRecharge');
        $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);

        if ($_POST['pay'] == 'cardpay'){
            $note = "{$this->site['site_keyword']}-余额充值：充值卡 ".t($_POST['card_number']);
        }else{
            $note = "{$this->site['site_keyword']}-余额充值：{$money_str}元";
        }
        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => 2,
            'money'    => $money,
            'note'     => $note,
            'pay_type' => $_POST['pay'],
            'pay_pass_num'=>$pay_pass_num,
        ));
        if(!$id) $this->error("操作异常");

        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'out_trade_no' => $pay_pass_num,
                'total_fee'    => $money,
                'subject'      => "{$this->site['site_keyword']}-余额充值",
                'money_str'    => $money_str,
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'out_trade_no' => $pay_pass_num,
                'money' => $money,
                'subject' => "{$this->site['site_keyword']}-余额充值",
                'money_str'    => $money_str,
            ));
        }elseif($_POST['pay'] == 'wxpay'){
            $res = $this->wxpay(array(
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money * 100,//单位：分
                'subject'       => "{$this->site['site_keyword']}-余额充值",
                'money_str'    => $money_str,
            ));
            if($res){
                if($this->is_pc){
                    $this->assign('url',$res);
                    $html = $this->fetch('wxpay');
                    $data = array('status'=>1,'data'=>['html'=>$html,'pay_pass_num'=>$pay_pass_num]);
                    echo json_encode($data);
                    exit;
                }else{
                    if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                        $data = array('status'=>1,'data'=>['html'=>$res,'pay_pass_num'=>$pay_pass_num]);
                        echo json_encode($data);
                    }else {
                        $redirect_url = 'http://' . strip_tags($_SERVER['HTTP_HOST']) . "/my/account/" . sunjiami(rand(100, 999), 'wx_pay') . "/{$pay_pass_num}.html";
                        $data = array('status' => 1, 'data' => ['html' => $res . "&redirect_url={$redirect_url}", 'pay_pass_num' => $pay_pass_num]);
                        echo json_encode($data);
                        exit;
                    }
                }
            }else{
                $data = array('status'=>0,'data'=>"微信支付异常，请稍后再试");
                echo json_encode($data);
                exit;
            }
        } elseif ($_POST['pay'] == 'cardpay'){
            $res = $this->cardpay(array(
                'out_trade_no' => $pay_pass_num,
                'card_number'  => t($_POST['card_number']),
                'subject'      => "{$this->site['site_keyword']}-充值卡充值余额",
            ));
            echo json_encode($res);exit;
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
        if($_POST['pay']!='alipay'&&$_POST['pay']!='unionpay' && $_POST['pay']!='wxpay'){
            $this->error('支付方式错误');
        }

        //检查充值类型
        if($_POST['type']!=1 && $_POST['type']!=0){
            $this->error('支付类型错误');
        }
        $type = intval($_POST['user_vip']);
        
        $vip = M('user_vip')->where('id=' . $type)->find();

        $vip_type_time  = $_POST['vip_type_time'];
        $vip_time       = $_POST['vip_time'] >= 1 ? $_POST['vip_time'] : 1 ;
        if($vip_type_time == 'year'){
            $vip_length = "+{$vip_time} year";
            $money      = $vip['vip_year']*$vip_time;
            $vip_length_info = "{$vip_time}年";
        } else {
            $vip_length = "+{$vip_time} month";
            $money      = $vip['vip_month']*$vip_time;
            $vip_length_info = "{$vip_time}月";
        }

        $re = D('ZyRecharge');
        $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);

        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => "3,{$type}",
            'vip_length' => $vip_length,
            'money'    => $money,
            'note'     => "{$this->site['site_keyword']}-{$vip['title']}充值+$vip_length_info",
            'pay_type' => $_POST['pay'],
            'pay_pass_num'=>$pay_pass_num,
        ));
        if(!$id) $this->error("操作异常");

        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'out_trade_no' => $pay_pass_num,
                'subject'      => "{$this->site['site_keyword']}-{$vip['title']}充值",
                'total_fee'    => $money,
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'out_trade_no' => $pay_pass_num,
                'money' => $money,
                'subject' => "{$this->site['site_keyword']}-{$vip['title']}充值",
            ));
        }elseif($_POST['pay'] == 'wxpay'){
            $res = $this->wxpay(array(
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money * 100,//单位：分
                'subject' => "{$this->site['site_keyword']}-{$vip['title']}充值",
            ));
            if($res){
                if($this->is_pc){
                    $this->assign('url',$res);
                    $html = $this->fetch('wxpay');
                    $data = array('status'=>1,'data'=>['html'=>$html,'pay_pass_num'=>$pay_pass_num]);
                    echo json_encode($data);
                    exit;
                }else{
                    if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                        $data = array('status'=>1,'data'=>['html'=>$res,'pay_pass_num'=>$pay_pass_num]);
                        echo json_encode($data);
                    }else {
                        $redirect_url = 'http://' . strip_tags($_SERVER['HTTP_HOST']) . "/my/recharge/" . sunjiami(rand(100, 999), 'wx_pay') . "/{$pay_pass_num}.html";
                        $data = array('status' => 1, 'data' => ['html' => $res . "&redirect_url={$redirect_url}", 'pay_pass_num' => $pay_pass_num]);
                        echo json_encode($data);
                        exit;
                    }
                }
            }else{
                $data = array('status'=>0,'data'=>"微信支付异常，请稍后再试");
                echo json_encode($data);
                exit;
            }

        }
    }

    protected function alipay($args){
        $notify_url = 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipay_alinu_scvp.html';//异步地址
        $return_url = 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipay_aliru_scvp.html';//同步地址
        $passback_params = urlencode(sunjiami(json_encode(array('money_str'=>$args['money_str'])),"hll"));

        if($this->is_pc){
            //设置支付的Data信息
            $bizcontent  = array(
                "body"          => $args['subject'],//订单描述,
                "subject"       => $args['subject'],//订单名称
                "out_trade_no"  => $args['out_trade_no'],//商户网站订单系统中唯一订单号，必填
                "total_amount"  =>  "{$args['total_fee']}",//(string)$args['total_fee'],//付款金额 新版
                "product_code"  => 'FAST_INSTANT_TRADE_PAY',//销售产品码，与支付宝签约的产品码名称。 注：目前仅支持FAST_INSTANT_TRADE_PAY
                'passback_params' => $passback_params,//自定义参数 仅服务端异步可以接收
            );
            $alipay_type = 'pc';
        }elseif($this->is_wap){
            //设置支付的Data信息
            $bizcontent  = array(
                "body"          => $args['subject'],//订单描述,
                "subject"       => $args['subject'],//订单名称
                "out_trade_no"  => $args['out_trade_no'],//商户网站订单系统中唯一订单号，必填
                "total_amount"  =>  "{$args['total_fee']}",//(string)$args['total_fee'],//付款金额 新版
                "product_code"  => 'QUICK_WAP_WAY',//销售产品码，与支付宝签约的产品码名称。 注：目前仅支持QUICK_WAP_WAY
                'passback_params' => $passback_params,//自定义参数 仅服务端异步可以接收
            );
            $alipay_type = 'wap';
        }

        $response = model('AliPay')->aliPayArouse($bizcontent,$alipay_type,$notify_url,$return_url);

        echo $response;
        exit;
    }

    public function alinu(){
        //获取阿里回调到服务器异步的参数
        $response = model('AliPay')->aliNotify();
        file_put_contents('logs/alipayre_success_account.txt',json_encode($response));

        //商户订单号
        $out_trade_no = t($response['out_trade_no']);
        //支付宝交易号
        $trade_no = $response['trade_no'];
        //交易状态
        $trade_status = $response['trade_status'];

        $re = D('ZyRecharge','classroom');
        if ($trade_status == 'TRADE_SUCCESS'|| $trade_status == 'TRADE_FINISHED') {
            $res = $re->setNormalPaySuccess($out_trade_no,$trade_no,$response['passback_params'],false);
            if($res){
                echo 'success';
            }else{
                echo 'fail';
            }
        }
    }

    public function aliru(){
        unset($_GET['app'],$_GET['mod'],$_GET['act']);

        $this->assign('jumpUrl', U('classroom/Vip/index'));
        //商户订单号
        $out_trade_no = $_GET['out_trade_no'];
        //支付宝交易号
        $trade_no = $_GET['trade_no'];

        $re = D('ZyRecharge');
        $result = $re->setPaySuccess($out_trade_no,$trade_no);

        if($result){
            $this->success('充值成功！');
        }else{
            $this->error('充值失败！');
        }
    }

    /**
     * @name 微信支付
     * @packages protected
     */
    protected function wxpay($data)
    {
        if ($data) {
            $notifyUrl = 'http://'.$_SERVER['HTTP_HOST'].'/wxpay_success.html';
            if($this->is_pc){
                $from = 'pc';
            }else{
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    $from = 'jsapi';
                }else{
                    $from = 'wap';
                }
            }

            $attr = urlencode(sunjiami(json_encode(array('money_str'=>$data['money_str'])),"hll"));
            $attributes = [
                'body' => isset($data['subject']) ? $data['subject'] : '充值中心',
                'out_trade_no' => "{$data['out_trade_no']}",
                'total_fee' => "{$data['total_fee']}",
                'attach' => $attr,//自定义参数 仅服务端异步可以接收9
            ];

            $wxPay = model('WxPay')->wxPayArouse($attributes, $from, $notifyUrl);

            if($this->is_pc && $wxPay['code_url']){
                if($wxPay['code_url']){
                    return $wxPay['code_url'];
                }
            }elseif($this->is_wap){
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    return $wxPay;
                }else{
                    return $wxPay['mweb_url'];
                }
            }
        }
    }

    /**
     * @name 微信app支付回调
     */
    public function appwxpay_success(){
        //获取微信回调到服务器异步的参数
        $response = model('WxPay')->appWxNotify();
        file_put_contents('logs/wxpayre_success_app_pay.txt',json_encode($response));

        //商户订单号out_tr ade_no
        if($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS"){
            D('ZyRecharge','classroom')->setNormalPaySuccess($response['out_trade_no'], $response['transaction_id'],$response['attach']);
        }
    }

    /**
     * @name 微信回调
     */
    public function wxpay_success()
    {
        //获取微信回调到服务器异步的参数
        $response = model('WxPay')->wxNotify();
        file_put_contents('logs/wxpayre_success_pay.txt',json_encode($response));

        //商户订单号
        if($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS"){
            D('ZyRecharge','classroom')->setNormalPaySuccess($response['out_trade_no'], $response['transaction_id'],$response['attach']);
        }
    }

    /**
     * @name 查询支付状态
     */
    public function getPayStatus(){
        $pay_pass_num = $_POST['pay_pass_num'];
        $status_info = M('zy_recharge')->where(['pay_pass_num'=>$pay_pass_num])->field('status,type')->find();
        if($status_info['type'] == 2){
            $url = U("classroom/User/account");
        }else{
            $url = U("classroom/User/recharge");
        }
        if($status_info['status'] == 1){
            echo json_encode(['status'=>1,'info'=>"",'data'=>$url]);exit;
        }else{
            echo json_encode(['status'=>0]);exit;
        }
    }

    protected function cardpay($arge){
        $coupon_model = model('Coupon');
        $res = $coupon_model->grantCouponByCode($arge['card_number']);

        if($res){
            $coupon_info = $coupon_model->where(['code'=>$arge['card_number']])->field('id,recharge_price')->find();
            //添加余额并加相关流水
            $learnc = D('ZyLearnc','classroom')->recharge($this->mid,$coupon_info['recharge_price']);
            if(!$learnc) {
                return false;
            }
            //D('ZyLearnc','classroom')->addFlow($this->mid, 1, $coupon_info['recharge_price'], '充值卡充值余额：' . $coupon_info['recharge_price'], $coupon_info['id'], 'coupon');
            D('ZyLearnc','classroom')->addFlow($this->mid, 1, $coupon_info['recharge_price'], '充值卡充值余额：' . $coupon_info['recharge_price'], $arge['out_trade_no'], 'zy_recharge');

            M('coupon_user')->where(['cid'=>$coupon_info['id']])->setField('status',1);

            $s['uid']   = $this->mid;
            $s['title'] = "恭喜您使用充值卡充值余额成功";
            $s['body']  = "恭喜您使用充值卡充值余额{$coupon_info['recharge_price']}成功";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);

            return ['status'=>1,'info'=>"充值成功",'data'=>''];
        }else{
            return ['status'=>0,'info'=>$coupon_model->getError(),'data'=>''];
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
            $re = D('ZyRecharge');
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


    /**
     * @name 微信支付
     * @packages protected
     */
    protected function dswxpay($data)
    {
        if ($data) {
            $notifyUrl = 'http://'.$_SERVER['HTTP_HOST'].'/dswxpay_success.html';
            if($this->is_pc){
                $from = 'pc';
            }else{
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    $from = 'jsapi';
                }else{
                    $from = 'wap';
                }
            }

            $attr = urlencode(sunjiami(json_encode(array('money_str'=>$data['money_str'])),"hll"));
            $attributes = [
                'body' => isset($data['subject']) ? $data['subject'] : '充值中心',
                'out_trade_no' => "{$data['out_trade_no']}",
                'total_fee' => "{$data['total_fee']}",
                'attach' => $attr,//自定义参数 仅服务端异步可以接收9
            ];
            //dd($attributes,$from,$notifyUrl);
            $wxPay = model('WxPay')->wxPayArouse($attributes, $from, $notifyUrl);
            //dd($wxPay);
            if($this->is_pc && $wxPay['code_url']){
                if($wxPay['code_url']){
                    return $wxPay['code_url'];
                }
            }elseif($this->is_wap){
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    return $wxPay;
                }else{
                    return $wxPay['mweb_url'];
                }
            }
        }
    }

    /**
     * @name 微信回调
     */
    public function dswxpay_success()
    {
        //获取微信回调到服务器异步的参数
        $response = model('WxPay')->wxNotify();
        file_put_contents('logs/wxpayre_success_pay.txt',json_encode($response));

        //商户订单号
        if($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS"){
            //D('ZyRecharge','classroom')->setNormalPaySuccess($response['out_trade_no'], $response['transaction_id'],$response['attach']);
            $order = D('zy_dashang_log')->where(array('pay_pass_num'=>$response['out_trade_no']))->find();
            if(!$order) return false;
            $update = array(
                'state' =>  1
            );
            $res = D('zy_dashang_log')->where(array('pay_pass_num'=>$response['out_trade_no']))->save($update);
            return true;
        }
    }
    /**
     * @name 查询打赏状态
     */
    public function getDsStatus(){
        $pay_pass_num = $_POST['pay_pass_num'];
        $status_info = M('zy_dashang_log')->where(['pay_pass_num'=>$pay_pass_num])->field('status,type')->find();

        if($status_info['status'] == 1){
            echo json_encode(['status'=>1,'info'=>"打赏成功"]);exit;
        }else{
            echo json_encode(['status'=>0]);exit;
        }
    }
    /**
     * 打赏
     */
    public function dashang(){
        ini_set('display_errors', '1');
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        //必须要先登陆才能进行操作
        if($this->mid <= 0) $this->error('请先登录在进行充值');
        $t_uid = $_POST['t_uid'];
        if($t_uid <= 0) $this->error('打赏失败');
        $pay_list = array('alipay','unionpay','wxpay','cardpay');
        if(!in_array($_POST['pay_type'],$pay_list)){
            $this->error('支付方式错误');
        }

        $money_str = t($_POST['money']);
        $money = array_filter(explode('=>',$money_str))[0] ? : 0;
        if ($money <= 0) {
            $this->error('请选择或填写充值金额');
        }

        $re = D('ZyRecharge');
        $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);

        $note = "{$this->site['site_keyword']}-余额充值：{$money_str}元";

        $data = array(
            'ds_uid'    =>  $t_uid,
            'uid'   =>  $this->mid,
            'money' =>  $money,
            'add_time'  =>  time(),
            'pay_pass_num'  =>  $pay_pass_num
        );
        $id = D('zy_dashang_log')->add($data);
        if(!$id) $this->error("操作异常");

       if($_POST['pay_type'] == 'wxpay'){
            $res = $this->dswxpay(array(
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money * 100,//单位：分
                'subject'       => "{$this->site['site_keyword']}-打赏",
                'money_str'    => $money_str,
            ));
            if($res){
                if($this->is_pc){
                    $this->assign('url',$res);
                    $html = $this->fetch('wxpay');
                    $data = array('status'=>1,'data'=>['html'=>$html,'pay_pass_num'=>$pay_pass_num]);
                    echo json_encode($data);
                    exit;
                }else{
                    if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                        $data = array('status'=>1,'data'=>['html'=>$res,'pay_pass_num'=>$pay_pass_num]);
                        echo json_encode($data);
                    }else {
                        $redirect_url = 'http://' . strip_tags($_SERVER['HTTP_HOST']) . "/my/account/" . sunjiami(rand(100, 999), 'wx_pay') . "/{$pay_pass_num}.html";
                        $data = array('status' => 1, 'data' => ['html' => $res . "&redirect_url={$redirect_url}", 'pay_pass_num' => $pay_pass_num]);
                        echo json_encode($data);
                        exit;
                    }
                }
            }else{
                $data = array('status'=>0,'data'=>"微信支付异常，请稍后再试");
                echo json_encode($data);
                exit;
            }
        }
    }
}
