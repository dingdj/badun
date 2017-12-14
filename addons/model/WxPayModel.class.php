<?php
/**
 * 微信支付模型 - 数据对象模型
 * @author jason <in_link@yeah.net>
 */
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use GuzzleHttp\Client;
class WxPayModel extends Model {


    /**
     * @param $attributes 业务参数
     * @param $from 来源 api为移动端，pc为服务端 wap为wap refund为在线退款 _refund为在线退款查询
     * @param $notifyUrl 异步通知回调地址
     * @return array|\EasyWeChat\Support\Collection
     */
        public function wxPayArouse($attributes,$from,$notifyUrl){
        //配置
        $app = new Application($this->getWxpayConfig($from));
        $payment = $app->payment;

        //统一下单
        if($from == 'pc'){
            $trade_type = 'NATIVE';
        }elseif($from == 'wap'){
            $trade_type = 'MWEB';
        }elseif($from == 'jsapi'){
            $trade_type = 'JSAPI';
			$auth = session("wx_auth_{$this->mid}");
            $attributes['openid'] = $auth ? $auth['openid'] : '';
        }elseif($from == 'api'){
            $trade_type = 'APP';
        }
        $attributes = array_merge($attributes,[
            'notify_url'       => "$notifyUrl", // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'spbill_create_ip' => getIp(),//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'trade_type'       => $trade_type,//JSAPI，MWEB，NATIVE，APP
        ]);
        $tpay_switch = model('Xdata')->get("admin_Config:payConfig")['tpay_switch'];

        //设置支付的Data信息 //(string)测试付款金额 新版单位：分
        if($tpay_switch){
            $attributes['total_fee']  = 1;
        }
        dd($_SESSION,$auth);
        $order = new Order($attributes);
        $result = $payment->prepare($order);
		if($result->return_code != 'SUCCESS'){
			return false;
		}

        if($from == 'api'){
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
                $prepayId = $result->prepay_id;
            }
            return $payment->configForAppPayment($prepayId);;
        }elseif($from == 'jsapi'){
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
                $prepayId = $result->prepay_id;
            }
            return $payment->configForPayment($prepayId);
        }else{
            return $result;
        }
    }

    /**
     * 申请退款
     * @param $refund
     * @return \EasyWeChat\Support\Collection
     */
    public function wxRefund($refund,$from){
        $refund['refund_amount'] = 1;// (int)测试付款金额 新版单位：分
        //配置
        $app = new Application($this->getWxpayConfig($from));
        $payment = $app->payment;

        if($refund['out_trade_no']){
            # 1. 使用商户订单号退款
            $result = $payment->refund($refund['out_trade_no'], $refund['out_refund_no'], $refund['refund_amount']); // 总金额 100 退款 100，操作员：商户号
        }elseif($refund['transaction_id']){
            // 2. 使用 TransactionId 退款
            $result = $payment->refundByTransactionId($refund['transaction_id'], $refund['out_refund_no'], $refund['refund_amount']); // 总金额 100 退款 100，操作员：商户号
        }
        return $result;
    }

    /**
     * app支付回调临时处理
     */
    public function appWxNotify(){
        return $this->wxNotify('api');
    }

    public function wxNotify($from){
        if($_SERVER['REQUEST_METHOD']!='POST') exit('非法请求');
        //$_POST = json_decode(file_get_contents('logs/wxpayre.txt'),true);
        unset($_POST['app'],$_POST['mod'],$_POST['act']);

        $app = new Application($this->getWxpayConfig($from));
        $response = $app->payment->handleNotify(function($notify, $successful){
            file_put_contents('logs/wxpayre.txt',json_encode($notify));

            //$notify = json_decode(file_get_contents('logs/wxpayre.txt'),true);
            if($notify["return_code"] != "SUCCESS" && $notify["result_code"] != "SUCCESS") exit('fail');
            file_put_contents('logs/wxpayre_success.txt',json_encode($notify));

            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $recharge = M('zy_recharge')->where(array('pay_pass_num'=>$notify['out_trade_no']))->find();
            if (!$recharge) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($recharge['status']) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }

            // 用户是否支付成功
            if ($successful) {
                // 不是已经支付状态则修改为已经支付状态
                $status = 1;
            }else{
                $status = 2;
            }
            //修改购买记录状态
            D('ZyRecharge','classroom')->setPaySuccess($notify['out_trade_no'], $notify['transaction_id'],$status,$notify['attach']);
            return true; // 返回处理完成
        });
        $response->send();//通知微信
        return $app->payment->getNotify()->getNotify();
    }
    /**
     * 获取微信支付配置
     */
    private function getWxpayConfig($from){
        $pageKeyData = model('Xdata')->get('admin_Config:wxpay');

        if($from == 'api'){
            $pageKeyData['apiclient_cert'] = array_filter(explode('|',$pageKeyData['apiclient_cert_ids']))[1];
            $pageKeyData['apiclient_key'] = array_filter(explode('|',$pageKeyData['apiclient_key_ids']))[1];
            $apiclient_cert = model('Attach')->getFilePath($pageKeyData['apiclient_cert']);
            $apiclient_key = model('Attach')->getFilePath($pageKeyData['apiclient_key']);

            $options = [
                'app_id' => "{$pageKeyData['APPID']}",
                // ...
                // payment
                'payment' => [
                    'merchant_id'        => "{$pageKeyData['MCHID']}",//商户号
                    'key'                => "{$pageKeyData['KEY']}",//md5加密key
                    'cert_path'          => "{$apiclient_cert}", // 私钥: 绝对路径！！！！
                    'key_path'           => "{$apiclient_key}",      // 公钥: 绝对路径！！！！
                ],
            ];
        }else{
            $pageKeyData['mp_apiclient_cert'] = array_filter(explode('|',$pageKeyData['mp_apiclient_cert_ids']))[1];
            $pageKeyData['mp_apiclient_key'] = array_filter(explode('|',$pageKeyData['mp_apiclient_key_ids']))[1];
            $mp_apiclient_cert = model('Attach')->getFilePath($pageKeyData['mp_apiclient_cert']);
            $mp_apiclient_key = model('Attach')->getFilePath($pageKeyData['mp_apiclient_key']);

            $options = [
                'app_id' => "{$pageKeyData['mp_appid']}",
                // ...
                // payment
                'payment' => [
                    'merchant_id'        => "{$pageKeyData['mp_mchid']}",//商户号
                    'key'                => "{$pageKeyData['mp_api_key']}",//md5加密key
                    'cert_path'          => "$mp_apiclient_cert", // 私钥: 绝对路径！！！！
                    'key_path'           => "$mp_apiclient_key", // 公钥: 绝对路径！！！！
                ],
            ];
        }

        return $options;
    }

    /**
     * 初始化微信用户
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-06-20
     * @return [type] [description]
     */
    public function getWxUserInfo($code,$url)
    {
        $wx_config = model('Xdata')->get('admin_Config:wxpay');
        if (!$code) {
            //回调地址--将用户引导到微信登录页面
            $pageUrl = urlencode($url);
            //微信接口请求地址
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $wx_config['mp_appid'] . '&redirect_uri=' . $pageUrl . '&response_type=code&scope=snsapi_base#wechat_redirect';
            header("location:$url");
            exit;
        } else {
            //通过code换取网页授权access_token
            $params['query'] = [
                'appid'      => $wx_config['mp_appid'],
                'secret'     => $wx_config['mp_api_secret'],
                'code'       => $code,
                'grant_type' => 'authorization_code',
            ];
            $client = new Client();
            $res    = $client->request('get', 'https://api.weixin.qq.com/sns/oauth2/access_token', $params);
            $res    = $res->getBody()->getContents();
            $access = json_decode($res, true);
			if(!isset($access['errcode'])){
				session("wx_auth_{$this->mid}",$access);
			}
            return $access;
        }
    }

}