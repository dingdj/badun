<?php
/**
 * @name 直播API
 */

class LiveApi extends Api
{
    protected $mod      = ''; //当前操作的模型
    protected $error    = ''; //错误信息
    protected $coupon   = []; //当前优惠券信息
    private $order_list = array(
        'default'      => 'video_order_count DESC,video_score DESC,video_comment_count DESC',
        'saledesc'     => 'video_order_count DESC', //订单量递减
        'saleasc'      => 'video_order_count ASC', //订单量递增
        'scoredesc'    => 'video_score DESC',
        'scoreasc'     => 'video_score ASC',
        't_price'      => 't_price ASC',
        't_price_down' => 't_price DESC',
        'new'          => 'ctime desc',
    );
    /**
     * 初始化模型
     */
    public function _initialize()
    {
        //$this->mod      = D('ZyGoods','classroom');
        $this->mod      = model('Live');
        $this->mod->mid = $this->mid;
    }
    /**
     * @name 获取直播分类
     */
    public function getLiveCategoryList()
    {
        $cate_id = (int) $this->cate_id ?: 0;
        $list    = $this->mod->getCategoryList($cate_id);
        $list ? $this->exitJson($list, 1) : $this->exitJson([], 0, '暂时没有直播分类');
    }

    /**
     * @name 获取直播课程列表
     */
    public function getLiveList()
    {
        $map                                  = [];
        $this->cate_id && $map['cate_id']     = intval($this->cate_id);
        $this->school_id && $map['mhm_id']    = intval($this->school_id); //机构ID
        $this->is_free && $map['is_charge']   = intval($this->is_free); //是否免费 1:是 0:否
        $this->keyword && $map['video_title'] = array('like', '%' . h($this->keyword) . '%');

        // 如果设置了讲师ID
        if ($this->teacher_id) {
            $cc_live_ids = M('zy_live_cc')->where(array('speaker_id' => $this->teacher_id))->field('live_id')->select();
            $gh_live_ids = M('zy_live_zshd')->where(array('speaker_id' => $this->teacher_id))->field('live_id')->select();
            $live_ids    = array_merge($cc_live_ids, $gh_live_ids);
            if ($live_ids) {
                $live_ids = array_unique(getSubByKey($live_ids, 'live_id'));
            }
            $map['id'] = array('in', $live_ids);
        }
        if ($this->status == 'living') {
            // 正在直播
            $cc_live_ids = M('zy_live_cc')->where(array('startDate' => array('elt', time()), 'invalidDate' => array('egt', time())))->field('live_id')->select();
            $gh_live_ids = M('zy_live_zshd')->where(array('startDate' => array('elt', time()), 'invalidDate' => array('egt', time())))->field('live_id')->select();
            $live_ids    = array_merge($cc_live_ids, $gh_live_ids);
            if ($live_ids) {
                $live_ids = isset($map['id']) ? array_unique(array_merge(getSubByKey($live_ids, 'live_id'), $map['id'][1])) : array_unique(getSubByKey($live_ids, 'live_id'));
            }
            $map['id'] = array('in', $live_ids);
        } elseif ($this->status == 'free') {
            $map['_string'] = 'is_charge = 1 AND t_price = 0';
        }
        $order = isset($this->order_list[$this->order]) ? $this->order_list[$this->order] : $this->order_list['default'];
        $data  = $this->mod->getLiveList($map, $order, $this->count);
        if ($data['gtLastPage'] === true) {
            $this->exitJson([], 1, '暂时没有更多直播课程');
        }
        $data['data'] ? $this->exitJson($data['data'], 1) : $this->exitJson([], 1, '暂时没有更多直播课程');

    }
    /**
     * @name 获取直播课程详情
     * @param int live_id 直播课程ID
     * @param int has_user_info 是否需要获取用户信息  1:是 0；否 default:0
     */
    public function getDetail()
    {
        $info = [];
        if ((int) $this->live_id) {
            $has_user_info = $this->has_user_info == 1 ? true : false;
            $info          = $this->mod->getLiveInfoById($this->live_id, $has_user_info);
        }
        $info ? $this->exitJson($info, 1) : $this->exitJson((object) [], 0, '未能查询到直播课程信息');
    }
    /**
     * @name 获取直播地址
     * @param int section_id 直播课程的章节编号ID
     */
    public function getLiveUrl()
    {
        $data = [];
        if ((int) $this->section_id && is_numeric($this->live_id)) {
            $data = $this->mod->getLiveUrlBySectionId($this->live_id, $this->section_id);
            $data ? $this->exitJson($data, 1) : $this->exitJson((object) [], 0, $this->mod->getError());
        }
        $this->exitJson((object) [], 0, '未能查询到直播课程信息');
    }

    public function getWhUserId(){
        $this->exitJson(['user_id'=>model('Live')->operWhUser($this->mid)],1);
    }

    /**
     * @name 获取指定用户可使用的优惠券
     */
    public function getCanUseCouponList()
    {
        $list = $this->mod->getCanuseCouponList($this->live_id,$this->canot);
        $list ? $this->exitJson($list, 1) : $this->exitJson([], 0, '没有可用优惠券');
    }
    /**
     * @name 购买直播课程
     */
    public function buyOperating()
    {
        $vid = intval($this->live_id);
        $uid = $this->mid;
        if (empty($vid)) {
            $this->exitJson((object) [], 0, '请选择要购买的直播课程');
        }

        $total_price = 0;
        $pay_status  = M('zy_order_live')->where(array('uid' => $uid, 'video_id' => $vid))->getField('pay_status');
        if ($pay_status == 3) {
            $this->exitJson((object) [], 0, '该直播课程你已经购买,无需重复购买');
        } else if ($pay_status == 4){
            $this->exitJson((object) [], 0, '该直播课程正在申请退款');
        }
        $avideos = $this->mod->findLiveAInfo(['id' => $vid]);

        if (!$avideos) {
            $this->exitJson((object) [], 0, '购买的直播课程不存在');
        }
        $avideos['price'] = getPrice($avideos, $uid, true, true);
        $videodata        = $avideos['video_title'];
        if ($avideos['price']['price'] <= 0 || $avideos['is_charge'] == 1) {
            $this->addOrder($avideos, $avideos['price'], array());
        } else {
            // 当购买过之后，或者课程的创建者是当前购买者的话，价格为0
            $is_buy      = D("ZyOrderLive", 'classroom')->isBuyLive($uid, $vid);
            $total_price = ($is_buy || $avideos['uid'] == $uid) ? 0 : round($avideos['price']['price'], 2);
        }
        //需要付费
        if ($total_price > 0) {
            //同步更新过期的支付订单
            model('Coupon')->checkOverdueOrder($this->mid);
            //使用优惠券
            $this->coupon_id = (int) $this->coupon_id ?: 0;
            if ($this->coupon_id) {
                $total_price = $this->useCoupon($this->coupon_id, $total_price);
                if ($total_price === false) {
                    $this->exitJson((object) [], 0, $this->error);
                }
                if($total_price === 0){
                    $coupon_id = M('coupon_user')->where(['id'=>$this->coupon_id])->getField('cid');
                    $vtype = 'zy_live';
                    $res = D('ZyVideo','classroom')->addOrder($vid,$vtype,$coupon_id);
                    if($res){
                        $order_info = $this->mod->getLiveInfoById($vid);
                        $this->exitJson(['is_free' => 1, 'order_info' => $order_info], 1, '购买成功');
                    } else {
                        $this->exitJson((object) [], 0, '购买失败');
                    }
                }
            }
            $ext_data = [
                'coupon_id' => $this->coupon_id,
                'dis_type'  => $this->coupon['type'],
                'price'     => $total_price,
            ];
            $order = D('ZyService', 'classroom')->buyOnlineLive(intval($this->mid), $vid, $ext_data);

            if ($order === true) {
                $pay_pass_num = date('YmdHis', time()) . mt_rand(1000, 9999) . mt_rand(1000, 9999);

                //购买订单生成成功
                $pay_id = D('ZyRecharge', 'classroom')->addRechange(array(
                    'uid'          => $this->mid,
                    'type'         => 1,
                    'money'        => $total_price,
                    'note'         => "{$this->site['site_keyword']}在线教育-购买直播课程：{$videodata}",
                    'pay_type'     => $this->pay_for == 'wxpay' ? 'app_wxpay' : $this->pay_for,
                    'pay_pass_num' => $pay_pass_num,
                ));
                if (!$pay_id) {
                    $this->exitJson(array(), 0, '操作异常');
                }

                $pay_data['is_free'] = 0;
                $pay_for             = in_array($this->pay_for, array('alipay', 'wxpay','lcnpay')) ? [$this->pay_for] : ['alipay', 'wxpay','lcnpay'];
                foreach ($pay_for as $p) {
                    switch ($p) {
                        case "alipay":
                            $pay_data['alipay'] = $this->alipay(array(
                                'vid'          => $vid,
                                'total_fee'    => $total_price,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_live',
                                'subject'      => "{$this->site['site_keyword']}在线教育-购买直播课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ), 'video');
                            break;
                        case "wxpay":
                            $pay_data['wxpay'] = $this->wxpay([
                                'vid'          => $vid,
                                'total_fee'    => $total_price * 100,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_live',
                                'subject'      => "{$this->site['site_keyword']}在线教育-购买直播课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ], 'video');
                            break;
                        case "lcnpay":
                            $res = $this->lcnpay([
                                'vid'          => $vid,
                                'total_fee'        => $total_price,
                                'out_trade_no' => $pay_pass_num,
                                'vtype'        => 'zy_live',
                                'subject'      => "购买直播课程：{$videodata}",
                                'coupon_id'    => $this->coupon_id,
                            ], 'live');
                            if($res === true){
                                $this->exitJson([], 1,"购买成功");
                            }else{
                                $this->exitJson([], 0,$res);
                            }
                            break;
                    }
                }
                $this->exitJson($pay_data, 1);

            }
            $this->exitJson((object) [], 0, '订单生成失败,请重新尝试');
        }
        /**
        // 获取$uid的学币数量
        if ( !D ( 'ZyLearnc','classroom' )->isSufficient ( $uid, $total_price, 'balance' )) {
        $this->exitJson( array(),10041, '可支配的学币不足' );
        }
        if ( !D ( "ZyLearnc",'classroom' )->consume ( $uid, $total_price )) {
        $this->exitJson( array(),10041, '合并付款失败，请稍后再试' );
        }**/

        // 添加课程购买记录
        //$time = time ();
        //foreach ( $avideos as $key => $val ) {
        //$insert_value .= "('" . $this->mid . "','" . $val ['uid'] . "','" . $val ['id'] . "','" . $val ['v_price'] . "','" . ($val ['price'] ['discount'] / 10) . "','" . $val ['price'] ['dis_type'] . "','" . $val ['price'] ['price'] . "','0'," . $time . ",0),";
        //}
        $order_id = D('ZyService', 'classroom')->buyOnlineVideo(intval($this->mid), $vid);
        if ($order_id) {
            $s['uid']     = $this->mid;
            $s['is_read'] = 0;
            $s['title']   = "恭喜您购买直播课程成功";
            $s['body']    = "恭喜您成功购买直播课程：" . trim($videodata, ",");
            $s['ctime']   = time();
            model('Notify')->sendMessage($s);
            $order_info = $this->mod->getLiveInfoById($vid);
            //操作积分
            model('Credit')->getCreditInfo($this->mid, 10);
            $this->exitJson(['is_free' => 1, 'order_info' => $order_info], 1, '购买成功');
        } else {
            $this->exitJson((object) [], 0, '购买失败');
        }
    }

    private function addOrder($live_info)
    {
        //无过期非法信息则生成状态为已支付的订单数据
        $data = array(
            'uid'            => $this->mid,
            'live_id'        => $live_info['id'],
            'old_price'      => $live_info['v_price'],
            'discount'       => round($live_info['v_price'] - $live_info['t_price'], 2),
            'discount_type'  => 3,
            'price'          => $live_info['v_price'],
            'order_album_id' => 0,
            'learn_status'   => 0,
            'ctime'          => time(),
            'is_del'         => 0,
            'pay_status'     => 3,
            'mhm_id'         => $live_info['mhm_id'],
            'coupon_id'      => 0,
            'rel_id'         => 0,
        );
        return D('ZyOrderLive', 'classroom')->add($data);
    }
    /**
     * @name 使用优惠券
     */
    private function useCoupon($coupon_id, $price)
    {
        if ($coupon_id && $price) {
            //检测优惠券是否可以使用
            $coupon = model('Coupon')->canUse($coupon_id, $this->mid);
            if (!$coupon) {
                $this->error = '该优惠券已经无法使用';
                return false;
            }
            $this->coupon = $coupon;
            //优惠券类型是否符合
            if (!in_array($coupon['type'], [1, 2, 5])) {
                $this->error = '该优惠券不能用于购买课程';
                return false;
            }
            switch ($coupon['type']) {
                case "1":
                    //价格低于门槛价 || 至少支付0.01
                    if ($coupon['maxprice'] != '0.00' && $price <= $coupon['maxprice']) {
                        $this->error = '该优惠券需要满' . $coupon['maxprice'] . '元才能使用';
                        return false;
                    }
                    if ($price <= $coupon['price']) {
                        $this->error = '所支付的金额不满足使用优惠券条件';
                        return false;
                    }
                    $price = round($price - $coupon['price'], 2);
                    break;
                case "2":
                    $price = $price * $coupon['discount'] / 10;
                    break;
                case "5":
                    $price = 0;
                    break;
                default:
                    break;
            }
            //使用优惠券
            //if(M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1)){
            return $price;
            //}
        }
        $this->error = '使用优惠券失败,请重新尝试';
        return false;
    }
    /**
     * @name 获取我购买的直播课程
     */
    public function getMyLiveList()
    {
        $this->cate_id && $map['cate_id'] = intval($this->cate_id);
        $this->keyword && $map['title']   = array('like', '%' . h($this->keyword) . '%');
        $data                             = $this->mod->getMyLiveList($map, $this->count);
        if ($data['gtLastPage'] === true) {
            $this->exitJson([], 1, '暂时没有更多直播课程');
        }
        $data['data'] ? $this->exitJson($data['data'], 1) : $this->exitJson([], 1, '暂时没有更多直播课程');
    }

    /**
     * 获取时间段内的直播
     */
    public function getLiveByTimespan()
    {
        $count = (int) $this->count ?: 20;
        if (!$this->begin_time || $this->end_time) {
            switch ($this->strtime) {
                case 'tomorrow':
                    $initial_time = strtotime(date('Y-m-d', time()));
                    $day          = $initial_time + 86400;
                    break;
                default:
//                    $day = [time(),time()+86400];
                    $day = time();
                    break;
            }

        } else {
            $day = [$this->begin_time, $this->end_time];
        }
//        $page = $this->page ? intval($this->page) : 1;
        //        $data = $this->mod->getLiveByTimespan($day[0],$day[1],$count,$page);
        $data = $this->mod->getLiveByTime($day, $count);

        $this->exitJson($data, 1);
    }

    /**
     * 获取直播讲师列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-08-10
     * @return [type] [description]
     */
    public function getLiveTeachers()
    {
        $teacher = M('ZyTeacher')->where('is_del=0')->field('id,name')->findPage($this->count);
        if (!$teacher['data'] || $teacher['gtLastPage'] === true) {
            $this->exitJson([], 1);
        }
        $this->exitJson($teacher['data'], 1);
    }
}
