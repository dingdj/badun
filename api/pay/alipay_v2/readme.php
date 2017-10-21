<?php
/**
 * @name alipay 支付类
 */
 
/**
 * @name 配置
 * @desc 调用支付宝支付类的时候,你需要先做支付的基本配置
 */
 
//配置支持实例化的时候以数组形式传递
$config = [
    'partner'               => '2088221939797502',//合作身份者id，以2088开头的16位纯数字
    'private_key_path'      => 'alipay/key/rsa_private_key.pem',//商户的私钥（后缀是.pen）文件相对路径,默认Alipay/key/
    'ali_public_key_path'   => 'alipay/key/alipay_public_key.pem',//支付宝公钥（后缀是.pen）文件相对路径,默认Alipay/key/
    'sign_type'             => strtoupper('rsa'),//签名方式
    'input_charset'         => strtolower('utf-8'),//字符编码格式 目前支持 gbk 或 utf-8
    'cacert'                => 'alipay/cacert.pem',//ca证书路径地址，用于curl中ssl校验,默认Alipay的目录
    'transport'             => 'http',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    'notify_url'            => '',//支付回调通知地址
    'return_url'            => '',//同步跳转地址
];
//初始化一个支付类,并配置
 $alipayClass = new Alipay($config);
 //也可以采用动态配置,动态配置方法会自动检测该配置属性(config or data)
 //配置规则 $alipayClass->set+参数名称(参数值);
 $alipayClass->setPartner('12346789');//将合作伙伴变更为12346789
 $alipayClass->setSign_type('md5');//将签名验证方式改为MD5
 $alipayClass->setService('refund_fastpay_by_platform_pwd');//自定义调用ali的接口名称
 /**
  * @name 检查配置和参数是否正确
  * @desc 你可以通过获取的方法,取得你现在配置的支付宝的相关配置和请求参数
  */
 //获取当前的配置信息
 $alipayClass->getConfig();
 //获取当前的支付请求体数据包
 $alipayClass->getData();
 /**
  * @name 添加更多的配置或者请求体
  * @desc 如果添加的参数已存在,将会替换之前的参数值
  */
 //如果你需要传递更多的参数或者配置,可以调用以下方法
 $alipayClass->addConfig('a','123');//添加一个配置参数 a ,值为123,支持批量添加,以数组传递
 $alipayClass->addData('b','456');//添加一个请求参数 b ,值为456,支持批量添加,以数组传递
 
 /**
  * @name 获取错误信息
  * @desc 如果在调用alipay类的过程中出现错误(false),可以调用该方法获取错误信息
  */
 $alipayClass->getError();
 
 /**
  * @name 调用ali的服务
  * @desc 如果service参数没有设置,会根据你当前传入的返回类型进行检测
  * @param 调用服务返回的类型  可以是  api(返回app支付所需要的数据) , h5 (调用ali的h5支付页面), pc(pc端打开,默认)
  */
 $alipayClass->goAliService('api');
 /**
  * @name 回调通知
  */
$alipay_config = [
    'partner'   => '',//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
    'transport' => '',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    'cacert'    => '',//ca证书路径地址，用于curl中ssl校验
    'sign_type' => '',//签名方式 
    'ali_public_key_path' => '',//支付宝公钥（后缀是.pen）文件相对路径,
];
//计算得出通知验证结果,详情参考见notify_url.php文件
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();