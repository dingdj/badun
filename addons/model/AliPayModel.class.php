<?php
/**
 * 阿里支付模型 - 数据对象模型
 * @author jason <in_link@yeah.net>
 */
class AliPayModel extends Model {


    /**
     * @param $bizcontent 阿里支付业务参数
     * @param $notifyUrl 异步通知回调地址
     * @param $from 来源 api为移动端，pc为服务端 wap为h5 refund为在线退款 _refund为在线退款查询 transfer_accounts为在线转账
     * @param $returnUrl 同步通知回调地址//用于非api的来源发起
     * @return string|提交表单HTML文本
     */
    public function aliPayArouse($bizcontent,$from,$notifyUrl,$returnUrl){
        $alipay_config = $this->getAlipayConfig();

        //初始化类
        tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','AopClient.php')));
        if($from == 'pc'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayTradePagePayRequest.php')));
        }elseif($from == 'wap'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayTradeWapPayRequest.php')));
        }elseif($from == 'api'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayTradeAppPayRequest.php')));
        }elseif($from == 'refund'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayTradeRefundRequest.php')));
        }elseif($from == '_refund'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayTradeFastpayRefundQueryRequest.php')));
        }elseif($from == 'transfer_accounts'){
            tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','request','AlipayFundTransToaccountTransferRequest.php')));
        }
        $aop = new \AopClient();

        //实际上线app id需真实的
        $aop->appId = $alipay_config['app_id'];
        $aop->rsaPrivateKey = $alipay_config['private_key_path'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->apiVersion = '1.0';
        $aop->signType = $alipay_config['sign_type'];
        $aop->alipayrsaPublicKey = $alipay_config['ali_public_key_path'];

        if($from == 'pc'){
            $request = new \AlipayTradePagePayRequest ();
        }elseif($from == 'wap'){
            $request = new \AlipayTradeWapPayRequest ();
        }elseif($from == 'api'){
            $request = new \AlipayTradeAppPayRequest ();
        }elseif($from == 'refund'){
            $request = new \AlipayTradeRefundRequest ();
        }elseif($from == '_refund'){
            $request = new \AlipayTradeFastpayRefundQueryRequest ();
        }elseif($from == 'transfer_accounts'){
            $request = new \AlipayFundTransToaccountTransferRequest ();
        }

        $tpay_switch = model('Xdata')->get("admin_Config:payConfig");

        if($from == 'refund' || $from == '_refund'){
            if($from == 'refund'){
                //$bizcontent['refund_amount']  = '0.01';//(string)测试付款金额 新版
            }
        }if($from == 'transfer_accounts'){
            if($tpay_switch['swn_switch']){
                $bizcontent['amount']  = '0.1';//(string)测试转账金额
            }
        }else{
            //支付宝回调
            $request->setNotifyUrl($notifyUrl);
            if($from != 'api'){
                $request->setReturnUrl($returnUrl);
            }
            //设置支付的Data信息 //(string)测试付款金额 新版
            if($tpay_switch['tpay_switch']){
                $bizcontent['total_amount']  = '0.01';
            }
        }

        $request->setBizContent(json_encode($bizcontent));

        if($from == 'pc' || $from == 'wap'){
            $response = $aop->pageExecute ($request);
        }elseif($from == 'api'){
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);
        }elseif($from == 'refund' || $from == '_refund' || $from == 'transfer_accounts'){
            $result = $aop->execute ( $request);

            $response[0] = $request;
            $response[1] = $result;
        }

        return $response;
    }

    /**
     * @return array
     *      out_trade_no 商户订单号
     *      trade_no 支付宝交易号
     *      trade_status 交易状态
     *      passback_params 自定义数据
     */
    public function aliNotify(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit('非法请求');
        //$_POST = json_decode(file_get_contents('logs/alipayre.txt'),true);
        unset($_POST['app'],$_POST['mod'],$_POST['act']);
        file_put_contents('logs/alipayre.txt',json_encode($_POST));
        tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','AopClient.php')));
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = model('Xdata')->get('admin_Config:alipay')['public_key'];
        //此处验签方式必须与下单时的签名方式一致
//        $_POST = json_decode(file_get_contents('logs/alipayre.txt'),true);
        $verify_result = $aop->rsaCheckV1($_POST, NULL, "RSA");
        if(!$verify_result) exit('fail');
        file_put_contents('logs/alipayre_success.txt',json_encode($_POST));

        $notify = [
            'out_trade_no'  => t($_POST['out_trade_no']),//商户订单号
            'trade_no'      => t($_POST['trade_no']),//支付宝交易号
            'trade_status'  => t($_POST['trade_status']),//交易状态
            'total_fee'     => $_POST['buyer_pay_amount'],//付款金额
        ];
        //修改购买记录状态 用户是否支付成功
        if (t($_POST['trade_status']) == 'TRADE_SUCCESS') {
            // 不是已经支付状态则修改为已经支付状态
            $status = 1;
        }else{//TRADE_FINISHED
            $status = 2;
        }
        D('ZyRecharge','classroom')->setPaySuccess(t($_POST['out_trade_no']),  t($_POST['trade_no']),$status);

        //自定义数据
        $passback_params = json_decode(sunjiemi(urldecode($_POST['passback_params']),'hll'),true);
        if($passback_params){
            $notify['passback_params'] = $passback_params;
        }
        return $notify;
    }
    /**
     * 获取阿里支付配置
     */
    private function getAlipayConfig(){
        $config = array(
            //'cacert' => join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','cacert.pem')),
            'sign_type' =>  strtoupper('RSA'),
        );
        $conf = unserialize(M('system_data')->where("`list`='admin_Config' AND `key`='alipay'")->getField('value'));

        if(is_array($conf)){
//        $path = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','key'));
            $config = array_merge($config, array(
//            'partner'   =>$conf['alipay_partner'],
//            'key'       =>$conf['alipay_key'],
//            'seller_id'=> $conf['alipay_partner'],
                'app_id' => $conf['app_id'],
                'private_key_path' => $conf['private_key'],
                'ali_public_key_path'    => $conf['public_key'],
                //'private_key_reallpath' => $path.DIRECTORY_SEPARATOR.'alipay_public_key.pem',
                //'ali_public_key_reallpath' => $path.DIRECTORY_SEPARATOR.'rsa_private_key.pem'
            ));
            //if(!is_dir($path)){
            //@mkdir($path,'0777',true);
            //chmod($path,777);
            //}
            // 生成公钥文件
            //if(!file_exists($config['ali_public_key_reallpath'])){
            // file_put_contents($config['ali_public_key_reallpath'],$config['ali_public_key_path']);
            //}
            // 生成私钥文件
            //if(!file_exists($config['private_key_reallpath'])){
            //file_put_contents($config['private_key_reallpath'],$config['private_key_path']);
            //}
        }
        return $config;
    }
}