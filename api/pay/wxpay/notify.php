<?php
$path = dirname(__FILE__);
require_once (join(DIRECTORY_SEPARATOR, array($path, 'lib', 'WxPay.Api.php')));
require_once (join(DIRECTORY_SEPARATOR, array($path, 'lib', 'WxPay.Notify.php')));
require_once (join(DIRECTORY_SEPARATOR, array($path,'log.php')));
//初始化日志
$logHandler= new CLogFileHandler($path."/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);
class PayNotifyCallBack extends WxPayNotify
{
	protected $notify_urls = [
		'video' => '/index.php?app=classroom&mod=PayVideo&act=wxpay_success',
		'recharge' => '/index.php?app=classroom&mod=Pay&act=wxpay_success',
	];
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}

	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();

		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		$doadmin = 'http://'.strip_tags($_SERVER['HTTP_HOST']);
		if(stripos($data['out_trade_no'],'r') !== false){
			$type = 'recharge';
			$out_trade_no = stristr($data['out_trade_no'],'r',true);
		}else{
			$type = 'video';
			$out_trade_no = stristr($data['out_trade_no'],'link',true);
		}
		file_put_contents('wxpayre.txt',json_encode($data));
		$url = $doadmin.$this->notify_urls[$type].'&out_trade_no='.$out_trade_no.'&transaction_id='.$data['transaction_id'].'&openid='.$data['openid'].'&attach='.$data['attach'];
		Log::DEBUG('get_url:'.$url);
		file_get_contents($url);
		return true;
	}
}
Log::DEBUG("begin notify");
$notify = new PayNotifyCallBack();
$notify->Handle(false);