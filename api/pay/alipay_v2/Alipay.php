<?php
$path = dirname(__FILE__);
include_once(join(DIRECTORY_SEPARATOR, array($path, 'lib', 'funs.php')));
class Alipay
{
    protected $error = '';//错误信息
    protected $return_type = 'pc';
    protected $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
    //支付宝账号配置
    protected $config = [
        'private_key_path'      => '/alipay/key/rsa_private_key.pem',//商户的私钥（后缀是.pen）文件相对路径
        'ali_public_key_path'   => '/alipay/key/alipay_public_key.pem',//支付宝公钥（后缀是.pen）文件相对路径,
        'cacert'                => './cacert.pem',//ca证书路径地址，用于curl中ssl校验,请保证cacert.pem文件在当前文件夹目录中
        'key'                   => '',//秘钥
    ];//支付宝配置
    protected $data = [
        'service'               => '',
        'partner'               => '',//合作身份者id，以2088开头的16位纯数字
        'seller_id'             => '',//收款账号,一般情况下收款账号就是签约账号
        'payment_type'          => '1',//支付类型,无需修改
        'transport'             => 'http',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'notify_url'            => '',//支付回调通知地址
        'return_url'            => '',//同步跳转地址
        '_input_charset'         => 'utf-8',//字符编码格式 目前支持 gbk 或 utf-8
        'sign_type'             => 'RSA',//签名方式
        'subject'	            => '',//订单名称
        'total_fee'	            => '',//付款金额
		'show_url'        	    => '',//收银台页面上商品展示链接
        'body'                  => '',//商品描述
        
    ];//保存本次支付的数据信息
    /**
     * @name 架构函数
     */
    public function __construct($config = []){
        //初始化配置
        $this->_init();
        $this->setConfig($config);
        
    }
    /**
     * @name 初始化配置
     */
    private function _init(){
        $path = dirname(__FILE__);
        $this->setConfig('private_key_path',join(DIRECTORY_SEPARATOR, array($path,'key', 'rsa_private_key.pem')));
        $this->setConfig('ali_public_key_path',join(DIRECTORY_SEPARATOR, array($path,'key', 'ali_public_key_path.pem')));
        $this->setConfig('cacert',join(DIRECTORY_SEPARATOR, array($path,'cacert.pem')));
    }
    /**
     * @name 动态设置和获取信息
     */
    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 3)) == 'set') {
            // 设置
            $field         = self::parseName(substr($method, 3));
            $value = $args[0];
            return $this->setConfig($field,$value);
        }else if (strtolower(substr($method, 0, 3)) == 'get') {
            // 获取
            $name        = self::parseName(substr($method, 3));
            return $this->get($name);
        }else {
            return $this;
        }
    }
    /**
     * @name 设置[自动检测属于配置还是data]
     * @param string | array $param_key 配置的参数名,多个支持以数组传递
     * @param string $param_value 参数值,如果$param_key为数组,忽略该参数
     */
    public function setConfig($param_key,$param_value = null){
        if($param_key){
            if(is_array($param_key)){
                foreach($param_key as $config_name => $config_value){
                    $config_value && $this->setConfig($config_name,$config_value);
                }
            }else if(is_string($param_key) && !is_null($param_value)){
                if(array_key_exists($param_key,$this->config)){
                    $this->config[$param_key] = $param_value;
                }else{
                    $this->data[$param_key] = $param_value;
                }
            }
        }
        return $this;
    }
    /**
     * @name 给data添加额外的数据 支持以数组传递
     * @param string|array $name 字段名称
     * @param string|array $value 字段值
     */
    public function addData($name = null,$value = null){
        if($name){
            if(is_array($name)){
                foreach($name as $_name=>$_value){
                    $_value && $this->addData($_name,$_value);
                }
            }elseif(is_string($name) && !is_null($value)){
                $this->data[$name] = $value;
            }
        }
        return $this->data;
    }
    /**
     * @name 给config添加额外的配置 支持以数组传递
     * @param string|array $name 字段名称
     * @param string|array $value 字段值
     */
    public function addConfig($name = null,$value = null){
        if($name){
            if(is_array($name)){
                foreach($name as $_name=>$_value){
                    $_value && $this->addConfig($_name,$_value);
                }
            }elseif(is_string($name) && !is_null($value)){
                $this->config[$name] = $value;
            }
        }
        return $this->config;
    }
    /**
     * @name 获取当前配置
     * @param string $name 获取的数据类型 data || config
     * @return array 配置信息
     */
    protected function get($name = ''){
        return in_array($name,['config','data']) ? $this->$name : false;
    }
    /**
     * @name 返回最后的错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }
    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string  $name 字符串
     * @param integer $type 转换类型
     * @return string
     */
    protected function parseName($name, $type = 0)
    {
        if ($type) {
            return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name));
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }
    /**
     * @name 执行支付宝的服务
     * @param string $pay_type 调用的支付接口类型【pc:pc网页,h5:mobile网页,api:API数据】
     * @param string $btnTxt 按钮提示文字 ,如果是API,忽略该参数
     * @return string  || array API返回
     */
    public function goAliService($pay_type = 'pc',$btnTxt = '去支付'){
        $this->return_type = $pay_type;
        //检测必须参数是否都已经配置
        if(true !== $this->checkData()){
            return false;
        }
        $return = '';
        switch($this->return_type){
            case 'api':
                $paramsStr = createLinkstring($this->data);
                $return = [
                    'ios' => "alipay://alipayclient/?" . urlencode(json_encode(array('requestType' => 'SafePay', "fromAppUrlScheme" => "openshare", "dataString" => $paramsStr))),
                    'public' => $paramsStr
                ];
                break;
            case 'pc':
            case 'h5':
            default:
                $return = $this->buildRequestForm($this->data,'get',$btnTxt);
                break;
        }
        return $return;
    }
    
    /**
     * @name 执行检测配置
     */
    private function checkData(){
	
        //账号
        if(!$this->data['partner'] && !$this->data['app_id']){
            $this->error = 'You must set config partner';//参数为空
            return false;
        }
        if(!isset($this->data['refund_date']) && !$this->data['subject']){
            $this->error = 'You must set data subject';//参数为空
            return false;
        }
	
	//如果没有设置收款账号,默认为partner
        if(!isset($this->data['seller_email'])){
		$this->data['seller_email'] = $this->data['partner'];
	}
	if( !$this->data['seller_id']){
            $this->data['seller_id'] = $this->data['partner'];
        }
	
        if(!$this->data['service']){
            //没有设置调用的服务,进行自动检测并设置
            switch($this->return_type){
                case 'h5':
                    $this->data['service'] = 'alipay.wap.create.direct.pay.by.user';
                    break;
                case 'api':
                    $this->data['service'] = 'mobile.securitypay.pay';
                    break;
                case 'pc':
                default:
                    $this->data['service'] = 'create_direct_pay_by_user';
                    break;
            }
        }
        //单独检测并设置秘钥
        return $this->checkSign();
        
    }
    /**
     * @nmae 检测并设置秘钥
     */
    private function checkSign(){
        $sign_type = strtoupper(trim($this->data['sign_type']));
        if(!$sign_type){
            $this->error = 'You must set data sign_type';
            return false;
        }
        $sign_str = '';
        $this->data = argSort(paraFilter($this->data));
        switch($sign_type){
            case 'MD5':
				//使用MD5方式签名
                
                $prestr = createLinkstring($this->data);
                if($this->return_type == 'api'){
					$sign_str = urlencode(md5Sign($prestr,$this->config['key']));
				}else{
					$sign_str = md5Sign($prestr,$this->config['key']);
				}
                break;
            case 'RSA':
				//使用RSA方式加密
                $prestr = createLinkstring($this->data);
                if($this->return_type == 'api'){
                    $sign_str = urlencode(rsaSign($prestr,$this->config['private_key_path']));
                }else{
                    $sign_str = rsaSign($prestr,$this->config['private_key_path']);
                }
                break;
            default:
                $sign_str = '';
				break;
        }
        $this->data['sign'] = $sign_str;
        $this->data['sign_type'] = $sign_type;
        return true;
    }
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
	public function buildRequestForm($para_temp, $method, $button_name) {
		//待请求参数数组
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->data['input_charset']))."' method='".$method."'>";
		while (list ($key, $val) = each ($para_temp)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='".$button_name."' style='display:none;'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}

}
