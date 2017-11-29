<?php
/**
 * @name 获取配置接口
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class ConfigApi extends Api
{
    public function __construct(){
        parent::__construct(true);
    }
    /**
     * @name 初始化检测方法
     */
    protected function _initialize(){
        //加密算法验证
		$ctime = hexdec($this->hextime);
		//收到请求的时间不能大于60s
		if (time() - 60 > $ctime) {
			$this->exitJson((object)[],0,'口令已经失效');
		}
		//转为小写
		$_token = strtolower(md5($ctime . $this->hextime));
		if (strtolower($this->token != $_token)) {
			$this->exitJson((object)[],0,'口令非法');
		}
    }
    /**
     * @name 获取API下载地址
     */
    public function getAppVersion(){
        $down_url = model('Xdata')->getconfig('download_url','appConfig');
        if($down_url){
        preg_match('/version=([^&]+)/',$down_url,$data);
            $version = $data[1] ?: '';
            $return = [
                'down_url' => $down_url,
                'version'  => $version
            ];
            $this->exitJson($return,1);
        }
        $this->exitJson((object)[],0,'暂时不能获取该配置');
    }

    //支付开关配置
    public function paySwitch(){
        $payConfig = model('Xdata')->get("admin_Config:payConfig");
        $config['pay'] = $payConfig['pay'];

        $this->exitJson($config,1);
    }
}