<?php
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class PaydiscConAction extends CommonAction{

    /**
     * 购买排课课程
     */
    public function operatConOrder()
    {

        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        //必须要先登陆才能进行操作
        if (!$this->mid) {
            $this->error('请先登录再进行购买');
        }


        $pay_list = array('alipay', 'unionpay', 'wxpay');
        if (!in_array($_POST['pay'], $pay_list)) {
            $this->error('支付方式错误');
        }


        if ($_POST) ;
        {
        $buymannums = t($_POST['buymannums']);
        $timemoney = t($_POST['timemoney']);
        $money = floatval($timemoney* $buymannums);


        if ($money <= 0) {
            $this->error('金额不能为负数');
        }


        $title = t($_POST['title']).date("YmdH",$buymannums)."-".date("YmdH",$buymannums+3600)."时段的折扣并发数";

            $discmap['is_del'] = 0;
            $discmap['stime'] = t($_POST['buytime']);
            $buymannums = t($_POST['buymannums']);
            $timemoney = M('con_discount') -> where($discmap) -> getField('discount_price');
            if(!$timemoney) {
                $map['id'] = 1;
                $timemoney = M('concurrent')->where($map)->getField('onehprice');
            }

            $old_price = floatval($timemoney* $buymannums);
            if (bccomp($money, $old_price)) {
                $this->error("请勿篡改金额");
            }

            $money = 0.01;
            $re = D('ZyRecharge');
            $id = $re->addRechange(array(
                'uid'      => $this->mid,
                'type'     => 1,
                'money'    => $money,
                'note'     => "{$this->site['site_keyword']}在线教育-购买{$title}",
                'pay_type' => $_POST['pay'],
            ));

            $buytime = t($_POST['buytime']);
            $connums = t($_POST['buymannums']);

            if (!$id) $this->error("操作异常");

            $data['connums'] = $connums;
            $data['price'] = $money;
            $data['stime'] = $buytime-1;
            $data['etime'] = $buytime + 3601;
            $data['ctime'] = time();
            $data['uid'] = $this ->mid;
            $data['pay_status'] = 1;
            $data['rel_id'] = $id;
            $res = M('zy_order_concurrent')-> add($data);

            $buytime = t($_POST['buytime']);
            $connums = t($_POST['buymannums']);

            if (!$id) $this->error("操作异常");
            if ($_POST['pay'] == 'alipay') {
                $this->alipay(array(
                    'buytime' => $buytime,
                    'connums' => $connums,
                    'out_trade_no' => $id,
                    'total_fee' => $money,
                    'subject' => "Eduline-购买{$title}",
                ));
            } elseif ($_POST['pay'] == 'unionpay') {
                $this->unionpay(array(
                    'buytime' => $buytime,
                    'connums' => $connums,
                    'id' => $id,
                    'money' => $money,
                    'subject' => "Eduline-购买{$title}",
                ));
            } elseif ($_POST['pay'] == 'wxpay') {
                $res = $this->wxpay(array(
                    'buytime' => $buytime,
                    'connums' => $connums,
                    'out_trade_no' => $id,
                    'total_fee' => $money * 100,//单位：分
                    'subject' => "Eduline-购买{$title}",
                ));
                if($res){
                    $this->assign('url',$res);
                    $html = $this->fetch('wxpay');
                    $data = array('status'=>1,'data'=>['html'=>$html,'trade_no'=>$id]);
                    echo json_encode($data);
                    exit;
                }

            }else{
                $this->error("操作异常,请稍后重试");
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
            "notify_url"      => 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipayDiscConAnsy.html',
            "return_url"      => U('classroom/PaydiscCon/aliru'),//页面跳转同步通知页面路径
        ));

        $alipayClass->addData('extra_common_param',json_encode(array('buytime'=>$args['buytime'],'connums'=>$args['connums'],'total_fee'=>$args['total_fee'])));

        //调用阿里的服务,默认调用PC端支付
        $res = $alipayClass->goAliService();
        echo $res;exit;
    }

    /**
     * @name 阿里支付回调 服务器异步通知页面路径
     * @packages public
     */
    public function alipayDiscConAnsy(){
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
        $buytime      = intval($extra_common_param['buytime']);
        $connums    = $extra_common_param['connums'];
        $total_fee    = $extra_common_param['total_fee'];

        $re = D('ZyRecharge');
        if ($trade_status == 'TRADE_FINISHED') {
            $result = $re->setNormalPaySuccess2($out_trade_no,$trade_no);
            if ($result) {
                $this_uid = $re->where('id = '.$out_trade_no)->getField('uid');
                $ctime = $re->where('id = '.$out_trade_no)->getField('ctime');
                $pay_status = M('zy_order_concurrent')->where(array('uid'=>$this_uid,'stime'=>$buytime-1,'ctime'=>$ctime))->getField('pay_status');

                if($pay_status == 3){
                    echo '购买成功';
                }else{
                    $con_order_info = $this->buyOperating($buytime, $out_trade_no, $connums, $total_fee,$this_uid);
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
                $pay_status = M('zy_order_concurrent')->where(array('uid'=>$this_uid,'stime'=>$buytime-1,'ctime'=>$ctime))->getField('pay_status');

                if($pay_status == 3){
                    echo '购买成功';
                }else{
                    $con_order_info = $this->buyOperating($buytime, $out_trade_no, $connums, $total_fee,$this_uid);
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
        $buytime      = intval($extra_common_param['buytime']);
        $connums    = $extra_common_param['connums'];
        $total_fee    = $extra_common_param['total_fee'];

        $this->assign('jumpUrl', U('school/User/addArrCourse'));

        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

            $result = D('ZyRecharge')->setNormalPaySuccess2($out_trade_no, $trade_no);
            if ($result) {
                $this_uid = D('ZyRecharge')->where('id = '.$out_trade_no)->getField('uid');
                $ctime = D('ZyRecharge')->where('pay_order = '.$trade_no)->getField('ctime');

                $pay_status = M('zy_order_concurrent')->where(array('uid'=>$this_uid,'stime'=>$buytime-1,'ctime'=>$ctime))->getField('pay_status');

                if($pay_status == 3){
                    $this->success('购买成功');
                }else{
                    $con_order_info = $this->buyOperating($buytime, $out_trade_no, $connums, $total_fee,$this_uid);
                    if ($con_order_info == 1) {
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

    public function  buyOperating($buytime,$out_trade_no,$connums,$total_fee,$this_uid){


        $data['pay_status'] = 3;
        $data['rel_id'] = $out_trade_no;
        $data['stime'] = $buytime-1;
        $data['etime'] = $buytime+3601;

        $this_uid = D('ZyRecharge')->where('id = '.$out_trade_no)->getField('uid');
        $ctime = D('ZyRecharge')->where('id = '.$out_trade_no)->getField('ctime');

        $res = M('zy_order_concurrent')->where(array('uid'=>$this_uid,'stime'=>$buytime-1,'ctime'=>$ctime))->save($data);

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
            $attr  = json_encode(array('buytime'=>$data['buytime'],'connums'=>$data['connums'],'total_fee'=>$data['total_fee']));
            $body  = isset($data['subject']) ? $data['subject'] :"{$this->site['site_keyword']}-购买";
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
            $con_order_info = $this->buyOperating($attach['buytime'],$data['id'],$attach['connums'],$attach['total_fee']);
            if($con_order_info == 1){
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
}