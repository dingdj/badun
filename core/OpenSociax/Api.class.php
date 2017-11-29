<?php
/**
 * ThinkSNS API接口抽象类
 * @author lenghaoran
 * @version TS3.0
 */
abstract class Api
{
    protected $data = [];
    protected $site;
    // 微信支付IOS支付地址
    //protected $clientpay_url    = 'weixin://app/%s/pay/?nonceStr=%s&package=Sign%%3DWXPay&partnerId=%s&prepayId=%s&timeStamp=%s&sign=%s&signType=SHA1';
    private $_module_white_list = null; // 白名单模块

    /**
     * 架构函数
     * @param boolean $location 是否本机调用，本机调用不需要认证
     * @return void
     */
    public function __construct($location = false)
    {
        $this->mid = 0;
        //外部接口调用
        if ($location == false && !defined('DEBUG')) {
            $this->verifyUser();
        } else {
            //本机调用
            $this->mid = @intval($_SESSION['mid']);
        }

        $GLOBALS['ts']['mid'] = $this->mid;

        //默认参数处理
        $this->since_id  = $_REQUEST['since_id'] ? intval($_REQUEST['since_id']) : '';
        $this->max_id    = $_REQUEST['max_id'] ? intval($_REQUEST['max_id']) : '';
        $this->page      = $_REQUEST['page'] ? intval($_REQUEST['page']) : 1;
        $this->count     = $_REQUEST['count'] ? intval($_REQUEST['count']) : 20;
        $this->user_id   = $_REQUEST['user_id'] ? intval($_REQUEST['user_id']) : 0;
        $this->user_name = $_REQUEST['user_name'] ? h($_REQUEST['user_name']) : '';
        $this->id        = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;
        $this->data      = array_merge($_REQUEST, $this->data);
        // findPage
        $_REQUEST[C('VAR_PAGE')] = $this->page;

        //接口初始化钩子
        Addons::hook('core_filter_init_api');

        //载入站点配置全局变量
        $this->initSite();

        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

    }
    public function __get($name)
    {
        return $this->data[$name];
    }
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function initSite()
    {
        $this->site = model('Xdata')->get('admin_Config:site');
    }
    /**
     * 用户身份认证
     * @return void
     */
    private function verifyUser()
    {
        $canaccess = false;
        //ACL访问控制
        if (file_exists(SITE_PATH . '/config/api.inc.php')) {
            $acl = include SITE_PATH . '/config/api.inc.php';
        }
        if (!isset($acl['access'])) {
            $acl['access'] = array('Oauth/*' => true);
        }

        if (isset($acl['access'][MODULE_NAME . '/' . ACTION_NAME])) {
            $canaccess = (boolean) $acl['access'][MODULE_NAME . '/' . ACTION_NAME];
        } elseif (isset($acl['access'][MODULE_NAME . '/*'])) {
            $canaccess = (boolean) $acl['access'][MODULE_NAME . '/*'];
        } else {
            $canaccess = false;
        }
        //白名单无需认证
        if (!$canaccess) {
            //OAUTH_TOKEN认证
            if (isset($_REQUEST['oauth_token']) && !empty($_REQUEST['oauth_token']) && $_REQUEST['oauth_token'] != 'null') {
                $verifycode['oauth_token']        = h($_REQUEST['oauth_token']);
                $verifycode['oauth_token_secret'] = h($_REQUEST['oauth_token_secret']);
                $login                            = M('login')->where($verifycode)->find();
                if (isset($login['uid']) && $login['uid'] > 0) {
                    $this->mid       = (int) $login['uid'];
                    $_SESSION['mid'] = $this->mid;
                    $canaccess       = true;
                } else {
                    $canaccess = false;
                }
            }
        } else {
            if (isset($_REQUEST['oauth_token']) && !empty($_REQUEST['oauth_token']) && $_REQUEST['oauth_token'] != 'null') {
                $verifycode['oauth_token'] = h($_REQUEST['oauth_token']);
                $verifycode['oauth_token_secret'] = h($_REQUEST['oauth_token_secret']);
                $login = M('login')->where($verifycode)->find();
                if (isset($login['uid']) && $login['uid'] > 0) {
                    $this->mid = (int)$login['uid'];
                    $_SESSION['mid'] = $this->mid;
                }
            }
        }
        if (!$canaccess) {
            $this->verifyError();
        } else {
            return;
        }
    }

    /**
     * 输出API认证失败信息
     * @return  object|json
     */
    protected function verifyError()
    {
        $message['message'] = '认证失败';
        $message['code']    = '00001';
        exit(json_encode($message));
    }

    /**
     * 通过api方法调用API时的赋值
     * api('WeiboStatuses')->data($data)->public_timeline();
     * @param array $data 方法调用时的参数
     * @return void
    public function data($data){
    if(is_object($data)){
    $data   =   get_object_vars($data);
    }
    $this->since_id   = $data['since_id']   ? intval( $data['since_id'] ) : '';
    $this->max_id     = $data['max_id']     ? intval( $data['max_id'] )   : '';
    $this->page       = $data['page']       ? intval( $data['page'] )     : 1;
    $this->count      = $data['count']      ? intval( $data['count'] )    : 20;
    $this->user_id    = $data['user_id']    ? intval( $data['user_id'])   : $this->mid;
    $this->user_name  = $data['user_name']  ? h( $data['user_name'])      : '';
    $this->id         = $data['id']         ? intval( $data['id'])        : 0;
    //$this->data = $data;
    return $this;
    }
     **/
    /**
     * api返回数据的标准格式
     * @param  [type] $data 待返回的数据
     * @param  [type] $code 错误码
     * @param  [type] $msg  提示信息
     * @return [json]       如果是本地调用返回数据数组.否则返回json
     */
    protected function exitJson($data = null, $code = 0, $msg = 'ok')
    {
        if ($this->isLocation) {
            return $data;
        }

        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode(array(
            'data' => $data,
            'code' => intval($code),
            'msg'  => $msg,
        )));
    }

    //组合limit
    protected function _limit()
    {
        return ((($this->page - 1) * $this->count) . ',' . $this->count);
    }

    /**
     * @name 阿里支付
     * @packages protected
     */
    protected function alipay($args, $type = 'video')
    {
        $notify_url = [
            'video'   => [
                'sync' => 'alipay_alinu.html',
                'pbps' => array('vid' => $args['vid'], 'vtype' => $args['vtype'], 'coupon_id' => $args['coupon_id']),
            ],
            'account' => [
                'sync' => 'alipay_alinu_scvp.html',
                'pbps' => array('money_str' => $args['money_str']),
            ],
            'vip'     => [
                'sync' => 'alipay_alinu_scvp.html',
                'pbps' => array(),
            ],
        ];
        //设置支付的Data信息
        $bizcontent = array(
            "body"            => $args['subject'], //订单描述,
            "subject"         => $args['subject'], //订单名称
            "out_trade_no"    => $args['out_trade_no'], //商户网站订单系统中唯一订单号，必填
            "total_amount"    => $args['total_fee'], //(string),//付款金额 新版
            "product_code"    => 'QUICK_MSECURITY_PAY', //销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
            'passback_params' => urlencode(sunjiami(json_encode($notify_url[$type]['pbps']), "hll")),
        );
        $notify_url = 'http://' . strip_tags($_SERVER['HTTP_HOST']) . '/' . $notify_url[$type]['sync'];

        //dump($bizcontent);
        $response = model('AliPay')->aliPayArouse($bizcontent, 'api', $notify_url);
        return [
            'ios'    => "alipay://alipayclient/?" . urlencode(json_encode(array('requestType' => 'SafePay', "fromAppUrlScheme" => "openshare", "dataString" => $response))),
            'public' => $response,
        ];
    }

    /**
     * 微信支付
     */
    protected function wxpay($args, $type)
    {
        $notify_url = [
            'video'   => [
                'sync' => 'http://' . $_SERVER['HTTP_HOST'] . '/appwxpay_sunu.html',
                'pbps' => array('vid' => $args['vid'], 'vtype' => $args['vtype'], 'coupon_id' => $args['coupon_id']),
            ],
            'account' => [
                'sync' => 'http://' . $_SERVER['HTTP_HOST'] . '/appwxpay_success.html',
                'pbps' => array('money_str' => $args['money_str']),
            ],
            'vip'     => [
                'sync' => 'http://' . $_SERVER['HTTP_HOST'] . '/appwxpay_success.html',
                'pbps' => [],
            ],
        ];

        $attributes = [
            'body'         => isset($args['subject']) ? $args['subject'] : "{$this->site['site_keyword']}-购买",
            'out_trade_no' => "{$args['out_trade_no']}",
            'total_fee'    => "{$args['total_fee']}",
            'attach'       => urlencode(sunjiami(json_encode($notify_url[$type]['pbps']), "hll")),
        ];

        $wxPay = model('WxPay')->wxPayArouse($attributes, 'api', $notify_url[$type]['sync']);

        $return = [
            'ios'    => $wxPay,
            'public' => $wxPay,
        ];

        return $return;
    }

    protected function lcnpay($args)
    {
        $re           = D('ZyRecharge', 'classroom');
        $out_trade_no = $args['out_trade_no'];
        $result       = $re->setNormalPaySuccess2($out_trade_no, 0);

        if ($result) {
            $this_uid = $re->where('pay_pass_num = ' . $out_trade_no)->getField('uid');

            if (!D('ZyLearnc', 'classroom')->consume($this_uid, $args['total_fee'])) {
                return "余额扣除失败"; //余额扣除失败，可能原因是余额不足
            }

            //自定义数据
            $vid       = intval($args['vid']);
            $vtype     = $args['vtype'];
            $coupon_id = $args['coupon_id'];

            //查询订单支付类型
            if ($vtype == 'zy_video') {
                $status_info = M('zy_order_course')->where(array('uid' => $this_uid, 'video_id' => $vid))->field('id,pay_status')->find();
                $relType     = "zy_order_course";
            } elseif ($vtype == 'zy_album') {
                $status_info = M('zy_order_album')->where(array('uid' => $this_uid, 'album_id' => $vid))->field('id,pay_status')->find();
                $relType     = "zy_order_album";
            } elseif ($vtype == 'zy_live') {
                $status_info = M('zy_order_live')->where(array('uid' => $this_uid, 'live_id' => $vid))->field('id,pay_status')->find();
                $relType     = "zy_order_live";
            } elseif ($vtype == 'zy_teacher') {
                $status_info = M('zy_order_teacher')->where(array('uid' => $this_uid, 'video_id' => $vid))->field('id,pay_status')->find();
                $relType     = "zy_order_teacher";
            }
            D('ZyLearnc')->addFlow($this_uid, 0, $args['total_fee'], "{$args['subject']}", $status_info['id'], $relType);

            if ($status_info['pay_status'] == 3) {
                if ($coupon_id) {
                    M('coupon_user')->where(['id' => $coupon_id])->setField('status', 1);
                }
                return true;
            } else {
                $order_info = D('ZyRecharge', 'classroom')->buyWxOperating($vid, $out_trade_no, $vtype);
                if ($order_info == 1) {
                    if ($coupon_id) {
                        M('coupon_user')->where(['id' => $coupon_id])->setField('status', 1);
                    }
                    return true;
                } else {
                    return "购买失败";
                }
            }
        } else {
            return "余额支付异常";
        }
    }

    /**
     * 充值卡充值余额
     */
    protected function cardpay($arge)
    {
        $coupon_model = model('Coupon');
        $res          = $coupon_model->grantCouponByCode($arge['card_number']);

        if ($res) {
            $coupon_info = $coupon_model->where(['code' => $arge['card_number']])->field('id,recharge_price')->find();
            //添加余额并加相关流水
            $learnc = D('ZyLearnc', 'classroom')->recharge($this->mid, $coupon_info['recharge_price']);
            if (!$learnc) {
                return false;
            }
            //D('ZyLearnc','classroom')->addFlow($this->mid, 1, $coupon_info['recharge_price'], '充值卡充值余额：' . $coupon_info['recharge_price'], $coupon_info['id'], 'coupon');
            D('ZyLearnc', 'classroom')->addFlow($this->mid, 1, $coupon_info['recharge_price'], '充值卡充值余额：' . $coupon_info['recharge_price'], $arge['out_trade_no'], 'zy_recharge');

            M('coupon_user')->where(['cid' => $coupon_info['id']])->setField('status', 1);

            $s['uid']   = $this->mid;
            $s['title'] = "恭喜您使用充值卡充值余额成功";
            $s['body']  = "恭喜您使用充值卡充值余额{$coupon_info['recharge_price']}成功";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);

            return true;
        } else {
            return $coupon_model->getError();
        }
    }
}
