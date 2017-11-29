<?php

/**
 * 云课堂服务层   大风车线上版
 * 实现一些公共服务，充值，提现，购买等等
 * @author xiewei <master@xiew.net>
 * @version 1.0
 */
class ZyServiceModel {

    /**
     * 申请提现
     * @param integer $uid 提现用户UID
     * @param integer $wnum 提现数量/金额
     * @param integer $bcard_id 提现银行卡ID
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:申请提现的学币不是系统指定的倍数，或小于0
     * 2:没有找到用户对应的提现银行卡/账户
     * 3:有未完成的提现记录，需要等待完成
     * 4:余额转冻结失败：可能是余额不足
     * 5:提现记录添加失败
     */
    public function applyWithdraw($uid, $wnum, $bcard_id) {

        //检查提现金额是否按照系统规定的倍数
        $wb = intval(getAppConfig('withdraw_basenum'));
        if ($wnum < 0 || $wnum < $wb || $wnum % $wb != 0) {
            return 1;
        }

        //检查用户是否拥有银行卡
        if (!D('ZyBcard')->hasBcard($bcard_id, $uid)) {
            return 2;
        }

        //检查是否已经有未完成的提现记录
        if (D('ZyWithdraw','classroom')->hasUnfinished($uid)) {
            return 3;
        }

        //model
        $zyLearnc = D('ZyLearnc','classroom');

        //余额转冻结
        if (!$zyLearnc->freeze($uid, $wnum)) {
            return 4;
        }

        //申请提现
        $id = D('ZyWithdraw','classroom')->apply($uid, $wnum, $bcard_id);
        if (!$id)
            return 5;

        //添加流水记录
        $zyLearnc->addFlow($uid, 2, $wnum, '申请提现', $id, 'zy_withdraw');

        return true;
    }

    /**
     * 设置提现状态
     * @param integer $id 需要设置的提现记录ID
     * @param integer $uid 该条提现记录对应的UID，后台操作设置为false
     * @param integer $status 要设置的状态 0:提交成功,1:正在处理,2:处理成功,3:处理失败,4:用户取消
     * @param string $reason 如果是失败或取消，那么输入原因
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:设置的状态不存在
     * 2:没有找到对应的提现记录
     * 3:学币冻结扣除失败
     * 4:学币解冻失败
     * 5:提现记录状态改变失败
     * 6:提现已完成或已经关闭
     */
    public function setWithdrawStatus($id, $uid, $status, $reason = '') {
        $ZyWithdraw = D('ZyWithdraw');
        //防止状态码溢出
        if (!$ZyWithdraw->statusExists($status))
            return 1;

        //查找数据记录，如果$uid为false，则不检查uid；
        //ps:uid可以防止前台用户操作非自己的提现记录
        $map['id'] = $id;
        if (false !== $uid)
            $map['uid'] = $uid;
        $rs = $ZyWithdraw->where($map)->find();
        if (!$rs)
            return 2;

        //当状态小于2时才能进行操作
        if ($rs['status'] < 2) {
            //学币及流水操作流程
            $zyLearnc = D('ZyLearnc');
            //提现成功则扣除冻结
            if ($status == 2) {
                $func = 'rmfreeze';
            } elseif ($status == 3 || $status == 4) {
                //如果是失败或用户自动取消，则将冻结转为余额
                $func = 'unfreeze';
            }
            //执行对应的操作
            if (isset($func) && !$zyLearnc->$func($rs['uid'], $rs['wnum'])) {
                return $status == 2 ? 3 : 4;
            }
            //保存记录状态
            $result = $ZyWithdraw->save(array(
                'id' => $id,
                'status' => $status,
                'reason' => ( $status == 2 ? '' : $reason ),
                'rtime' => ( $status < 2 ? 0 : time() ),
            ));
            if (false === $result)
                return 5;
            //添加流水记录
            if ($status == 2) {
                $type = 4;
                $note = '提现成功';
            } elseif ($status == 3) {
                $type = 3;
                $note = '提现失败';
            } elseif ($status == 4) {
                $type = 3;
                $note = '用户取消提现';
            }
            if (isset($type)) {
                $zyLearnc->addFlow(
                    $rs['uid'], $type, $rs['wnum'], $note, $rs['id'], 'zy_withdraw'
                );
            }
            return true;
        } else {
            return 6;
        }
    }

    /**
     * 购买单个课程
     * @param $uid
     * @param $video_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到课程
     * 3:余额扣除失败，可能原因是余额不足
     * 4:购买记录/订单，添加失败
     */
    public function buyVideo($uid, $video_id) {
        if ($this->checkVideoAccess($uid, $video_id)) {
            return 1;
        }
        $time = time();
        //取得课程
        $video = D('ZyVideo')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            'listingtime' => array('lt', $time),
        ))->find();
        //找不到课程
        if (!$video)
            return 2;
        //取得价格
        $prices = getPrice($video, $uid, false, true);

        $learnc = D('ZyLearnc');
        if (!$learnc->consume($uid, $prices['price'])) {
            return 3; //余额扣除失败，可能原因是余额不足
        }
        //订单数据
        $order = D('ZyOrder');
        $data = array(
            'uid' => $uid,
            'video_id' => $video['id'],
            'old_price' => $prices['oriPrice'],
            'discount' => $prices['discount'],
            'discount_type' => $prices['dis_type'],
            'price' => $prices['price'],
            'order_album_id' => 0,
            'learn_status' => 0,
            'ctime' => $time,
            'pay_status' => 0,
        );
        $id = $order->add($data);
        if (!$id)
            return 4; //购买记录/订单，添加失败

        //更新订单数量
        D('ZyVideo')->where(array('id' => $video['id']))->save(
            array('video_order_count' => $order->where(
                array('video_id' => $video['id']))->count()));

        //添加流水记录
        $learnc->addFlow($uid, 0, $prices['price'], '购买课程<'.$video['video_title'].'>', $id, 'zy_order');
        return true;
    }

    /**
     * 购买一个班级
     */
    public function buyAlbum($uid, $album_id, $total_price) {
        //获取$uid的学币数量
        if (!D('ZyLearnc',"classroom")->isSufficient($uid, $total_price, 'balance')) {
            return array('status' => '3', 'info' => '可支配的学币不足');
            exit;
        }
        if (!D("ZyLearnc","classroom")->consume($uid, $total_price)) {
            return array('status' => '2', 'info' => '合并付款失败，请稍后再试');
            exit;
        }
        $cuid = M("album")->where('id=' . $album_id)->getField('uid');
        $data['uid'] = $uid;
        $data['cuid'] = $cuid;
        $data['album_id'] = $album_id;
        $data['price'] = $total_price;
        $data['ctime'] = time();
        $data['is_del'] = 0;
        $result = M("zy_order_album")->data($data)->add();
        if ($result) {
            return array('status' => '1', 'info' => '添加购买班级记录成功！', 'rid' => $result);
        }else{
            return array('status' => '0', 'info' => '付款失败，请稍后再试');
        }
    }

    /**
     * 在线直接购买单个课程
     * @param $uid
     * @param $video_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineVideo($uid, $video_id,$ext_data = array(),$pay_video_mount_school) {
        if ($this->checkVideoAccess($uid, $video_id)) {
            return 1;
        }
        $time = time();
        //取得课程
        $video = M('zy_video')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            'type'        => 1,
            'listingtime' => array('lt', $time),
        ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,
            endtime,starttime,limit_discount,term")->find();
        //找不到课程
        if (!$video){
            return 2;
        }
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程属于机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid,school_and_teacher,school_and_oschool')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');

//        //课程下讲师不存在
//        if(!$teacher_uid){
//            return 8;
//        }

//        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
//        if(is_array($school_and_teacher)){
//            $school_info['sat_school']  = floatval($school_and_teacher[0]);
//            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
//        }
//        //机构数据里的 机构与教师分成不存在
//        if(empty($school_and_teacher)){
//            return 9;
//        }
        $school_and_oschool  = array_filter(explode(':',$school_info['school_and_oschool']));
        if(is_array($school_and_oschool)){
            $school_info['mount_school']  = floatval($school_and_oschool[0]);
            $school_info['mount_oschool'] = floatval($school_and_oschool[1]);
        }
        //机构数据里的 机构与挂载机构分成不存在
        if(empty($school_and_oschool)){
            return 10;
        }
        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');
        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }

        //课程订单有效期不存在
        if(!$video['term']){
            return 12;
        }

        //取得价格
        $prices = getPrice($video, $uid, false, true);
        $prices['price'] = floatval($prices['price']);

        //生成状态为未支付的订单数据
        $order = M('zy_order_course');
        $data = array(
            'uid'           => $uid,
            'muid'          => $teacher_uid,
            'video_id'      => $video['id'],
            'old_price'     => $prices['oriPrice'],//10
            'discount'      => $ext_data['price'] ? round($prices['price'] - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'price'         => $ext_data['price'] ? : $prices['price'],
            'order_album_id'=> 0,
            'learn_status'  => 0,
            'ctime'         => $time,
            'order_type'    => 0,
            'is_del'        => 0,
            'pay_status'    => 1,
            'term'          => $video['term'],
            'time_limit'    => time() + 129600 * floatval($video['term']),
            'mhm_id'        => $video['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );

        $map['uid']         = $uid;
        $map['video_id']    = $video['id'];
        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }

        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }

        $video_spilt_status = $this->addVideoSplit($id,$uid,$video,$ext_data['price'] ? : $prices['price'],$pay_video_mount_school);

        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order_course')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 在线直接购买单个班级
     * @param $uid
     * @param $album_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到班级
     * 3:班级下没有课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineAlbum($uid, $album_id,$ext_data = array()) {
        $album = D("Album")->getAlbumOneInfoById($album_id,'id,price,mhm_id,album_title');
        //找不到班级
        if (!$album){
            return 2;
        }

        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成与分享者分成比例不存在
        if($proportion == 1 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //班级不属于机构
        if(!$album['mhm_id']) {
            return 6;
        }

        //班级属于机构 先平台和机构分成 再机构与教师分成
        $school_info = model('School')->where(array('id'=>$album['mhm_id']))->field('uid,school_and_teacher')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //班级所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        $albumId        = intval($album['id']);

        //获取班级下所有的课程ID
        $video_ids        = trim(D("Album")->getVideoId($albumId), ',');
        $v_map['id']      = array('in', array($video_ids));
        $v_map["is_del"]  = 0;
        $album_info       = M("zy_video")->where($v_map)->field("id,video_title,mhm_id,teacher_id,
                              v_price,t_price,vip_level,endtime,starttime,limit_discount")
            ->select();
        //班级下所有的课程数量 班级下没有课程
        $album['video_count'] = count($album_info);
//        if($album['video_count'] <= 0){
//            return 3;
//        }

        $illegal_count  = 0;
        $total_price    = 0;

        //通过课程取得专辑价格
        $video_id   = '';
        $tuid       = '';
        $oPrice = 0.00;
        foreach ($album_info as $key => $video) {
            $oPrice += $video['t_price'];
            if($video['mhm_id'] != $album['mhm_id']){
                $video_id       .= $video['id'].',';//课程和班级的机构id不一致
            }
            $album_info[$key]['price'] = getPrice($video, $this->mid, true, true);
            //价格为0的 限时免费的  不加入购物记录
            if($album_info[$key]['price']['oriPrice'] == 0){
                unset($album_info[$key]);
                continue;
            }
            $album_info[$key]['is_buy'] = D("ZyOrder")->isBuyVideo($this->mid, $video['id']);
            $total_price                += ($album_info[$key]['is_buy'] || $video['uid'] == $this->mid) ? 0 : round($album_info[$key]['price']['price'], 2);
            //判断是否有课程过期
            if ($video['uctime'] < time()) {
                $illegal_count  += 1;
                $video_gid       = $video['id'];//过期id
            }
            //判断班级下没有相关讲师用户不存在的课程id
            $teacher_info = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->field('uid,name')->find();
            $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
            if(!$teacher_uinfo['uid']){
                $tuid       .= $video['id'].',';//班级下没有相关讲师用户不存在的课程id
            }
            $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
            //拼接班级下所有讲师信息以便线下分成
            $album_info['tuid_str'] .= $teacher_uinfo['uid'].",";
        }

//        //班级中包含有 没有相关讲师用户不存在的课程id
//        if ($tuid) {
//            return 8;
//        }

        //班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
        if ($video_id) {
            return 9;
        }

//        //取得机构数据里的 机构与教师分成
//        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
//        if(is_array($school_and_teacher)){
//            $school_info['sat_school']  = floatval($school_and_teacher[0]);
//            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
//        }
//        //机构数据里的 机构与教师分成不存在
//        if(empty($school_and_teacher)){
//            return 10;
//        }
        //班级中包含有过期的课程，无法整辑购买
//        if ($illegal_count > 0) {
//            return 3;
//        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //无过期非法信息则生成状态为未支付的订单数据
        $order = D('ZyOrderAlbum');
        $data = array(
            'uid'           => $uid,
            'album_id'      => $albumId,
            'old_price'     => $album['price'],
            'discount'      => 0,
            'discount_type' => 0,
            'price'         => $ext_data['price'] ? : $album['price'],
            'learn_status'  => 0,
            'ctime'         => time(),
            'order_type'    => 1,
            'is_del'        => 0,
            'pay_status'    => 1,
            'mhm_id'        => $album['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'discount'      => $ext_data['price'] ? round($album['price'] - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );
        $map['uid']      = $uid;
        $map['album_id'] = $albumId;
        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }

        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }
        if($ext_data['price']){
            $album['price'] = $ext_data['price'];
        }
        $video_spilt_status = $this->addAlbumSplit($id,$uid,$album);

        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('ZyOrderAlbum')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 在线直接购买单个直播课堂
     * @param $uid
     * @param $album_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到直播课堂
     * 3:直播课程下所有的课程数量 直播课程下没有课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineLive($uid, $live_id,$ext_data,$pay_video_mount_school) {
        //取得直播课程
        $live_info = D('ZyVideo')->where(array(
            'id'          => $live_id,
            'is_del'      => 0,
            'is_activity' => 1,
            'type'        => 2,
            'listingtime' => array('lt', time()),
            'uctime' => array('gt', time()),
        ))->field("id,video_title,mhm_id,t_price,v_price,
            listingtime,uctime,live_type")->find();

        //找不到直播课程
        if (!$live_info){
            return 2;
        }
        $live_id = $live_info['id'];
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成自营与分享者分成比例为空
        if($proportion == 2 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            return 6;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid,school_and_teacher,school_and_oschool')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //获取直播课程下所有的课程ID
        $teacher_uinfo_uid = "";
        if($live_info['live_type'] == 1){
            $live_zshd_hour = M('zy_live_zshd')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();
            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_zshd_hour);
//            if($video_count <= 0){
//                return 3;
//            }

            $tuid = '';
            foreach ($live_zshd_hour as $key =>$val){
                //判断直播课堂下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//直播课堂下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接直播课堂下所有讲师信息以便线下分成
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }
            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_zshd_hour['video_count']   = $video_count;

            //直播课堂中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                return 8;
            }
        }elseif($live_info['live_type'] == 3){
            $live_gh_hour = M('zy_live_gh')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();

            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_gh_hour);
//            if($video_count <= 0){
//                return 3;
//            }

            $tuid = '';
            foreach ($live_gh_hour as $key =>$val){
                //判断直播课堂下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//直播课堂下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接直播课堂下所有讲师信息以便线下分成
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }

            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_gh_hour['video_count']    = $video_count;

            //直播课堂中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                return 8;
            }
        }

        //直播课程属于机构 先平台和机构分成 再机构与教师分成
        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
        if(is_array($school_and_teacher)){
            $school_info['sat_school']  = floatval($school_and_teacher[0]);
            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
        }

        //机构数据里的 机构与教师分成不存在
        if(empty($school_and_teacher)){
            return 9;
        }
        $school_and_oschool  = array_filter(explode(':',$school_info['school_and_oschool']));
        if(is_array($school_and_oschool)){
            $school_info['mount_school']  = floatval($school_and_oschool[0]);
            $school_info['mount_oschool'] = floatval($school_and_oschool[1]);
        }
        //机构数据里的 机构与挂载机构分成不存在
        if(empty($school_and_oschool)){
            return 10;
        }
        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');
        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //取得价格
        $prices = floatval($live_info['t_price']);
        //无过期非法信息则生成状态为未支付的订单数据
        $order = D('ZyOrderLive');
        $data = array(
            'uid'           => $uid,
            'live_id'      => $live_id,
            'old_price'     => $prices,
            'discount'      => $ext_data['price'] ? round($prices - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'price'         => $ext_data['price'] ? : $prices,
            'order_album_id'=> 0,
            'learn_status'  => 0,
            'ctime'         => time(),
            //'order_type'    => 2,
            'is_del'        => 0,
            'pay_status'    => 1,
            'mhm_id'        => $live_info['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );

        $map['uid']        = $uid;
        $map['live_id']    = $live_id;

        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }
        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }

        $video_spilt_status = $this->addLiveSplit($id,$uid,$live_info,$ext_data['price'] ? : $prices,$pay_video_mount_school);
        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order_live')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 直播课堂 分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $video 购买课程详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成自营与分享者分成比例为空
     * 6:直播课程不属于机构
     * 7:直播课程所绑定的机构管理员不存在
     * 8:直播课堂中包含有 没有相关讲师用户不存在的课程id
     * 9:机构数据里的 机构与教师分成不存在
     * 10:创建订单明细流水失败 并删除此订单
     */
    public function addLiveSplit($order_id,$uid,$live_info = array(),$prices,$pay_video_mount_school){
        //找不到直播课程
        if (!$live_info){
            return 2;
        }
        $live_id = $live_info['id'];

        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成自营与分享者分成比例为空
        if($proportion == 2 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            return 6;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid,school_and_teacher,school_and_oschool')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //取得分享者信息
        $vsu_map['tmp_id']   = session_id();
        $vsu_map['type']     = 2;
        $vsu_map['video_id'] = $live_id;
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //获取直播课程下所有的课程ID
        $teacher_user_info = "此直播课程下课程所有讲师信息分别为";
        $teacher_uinfo_uid = "";
        $share_str         = "";

        if($live_info['live_type'] == 1){
            $live_zshd_hour = M('zy_live_zshd')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();
            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_zshd_hour);
//            if($video_count <= 0){
//                return 3;
//            }

            $tuid = '';
            foreach ($live_zshd_hour as $key =>$val){
                //判断直播课堂下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('id,uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//直播课堂下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接直播课堂下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['id']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                    ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
                if($share_uid == $teacher_uinfo['uid']){
                    $share_str .= $val['id'].',';//分享者为当前班级下讲师
                }
            }
            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_zshd_hour['video_count']   = $video_count;
            $live_info['teacher_uinfo'] = $teacher_user_info;

            //直播课堂中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                return 8;
            }
        }elseif($live_info['live_type'] == 3){
            $live_gh_hour = M('zy_live_gh')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();

            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_gh_hour);
//            if($video_count <= 0){
//                return 3;
//            }

            $tuid = '';
            foreach ($live_gh_hour as $key =>$val){
                //判断直播课堂下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('id,uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//直播课堂下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接直播课堂下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['id']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                                        ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
                if($share_uid == $teacher_uinfo['uid']){
                    $share_str .= $val['id'].',';//分享者为当前班级下讲师
                }
            }

            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_gh_hour['video_count']    = $video_count;
            $live_info['teacher_uinfo']     = $teacher_user_info;

            //直播课堂中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                return 8;
            }
        }
        $teacher_user_info              .= "讲师名和用户名可能会有变动。";

        //直播课程属于机构 先平台和机构分成 再机构与教师分成
        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
        if(is_array($school_and_teacher)){
            $school_info['sat_school']  = floatval($school_and_teacher[0]);
            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
        }

        //机构数据里的 机构与教师分成不存在
        if(empty($school_and_teacher)){
            return 9;
        }
        $school_and_oschool  = array_filter(explode(':',$school_info['school_and_oschool']));
        if(is_array($school_and_oschool)){
            $school_info['mount_school']  = floatval($school_and_oschool[0]);
            $school_info['mount_oschool'] = floatval($school_and_oschool[1]);
        }

        //机构数据里的 机构与挂载机构分成不存在
        if(empty($school_and_oschool)){
            return 10;
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');

        //购买用户相应机构抽成班主任比例
        $school_pinclass = model('School')->where(array('id'=>$mhuid))->getField('school_pinclass');
        $school_pinclass  = array_filter(explode(':',$school_pinclass));
        if(is_array($school_pinclass)){
            $school_pinclass['school_pinclass_school']  = floatval($school_pinclass[0]);//购买机构比例
            $school_pinclass['school_pinclass_pinclass']  = floatval($school_pinclass[1]);//销课者比例（从购买机构扣除）
        }

        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //生成订单分成详细 取得价格
        $prices = floatval($prices);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($live_info['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_uid);//购买的用户机构id
        $data['lid']          = intval($live_info['id']);//所购买直播课程的id
        $data['note']         = t("购买直播课堂：{$live_info['video_title']}。");
        $data['sum']          = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['sss_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['sss_school'];//机构及分析者与教师分成的总金额

        $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
        $data['school_sum'] = round($school_num * $proportion['ouats_theschool'],2);//课程所属机构获得的金额

        $ouats_ouschool_sum = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        $mschool_uid = M('school')->where('id = '.$pay_video_mount_school)->getField('uid');
        $map['uid'] = intval($uid);//购买用户ID
        $vm_map['uid'] = $mschool_uid;//挂载此课程的机构用户ID
        $vm_map['mhm_id'] = $pay_video_mount_school;//挂载此课程的机构ID
        $map['lid'] = $vm_map['vid'] = intval($live_info['id']);//所购买课程的id(包括点播、直播、班级)

        //该课程是否挂载到其他机构 如果挂载按照挂载分销(课程所属机构提供分成比例)，否则按照普通分销(课程所属机构提供分成比例)
        $is_video_mount = M('zy_video_mount')->where($vm_map)->getField('id');

        if($is_video_mount){
            //课程所属机构参与分成部分2
            $school_theschool = $data['school_sum'];
            //课程所属机构的挂载分成金额
            $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
            $data['school_sum'] = round($school_theschool * $school_info['mount_school'], 2);//课程所属机构获得的金额

            //课程挂载机构的挂载分成金额
            $data['mount_school_id'] = $mschool_uid;//课程挂载机构分成的管理员用户id
            $data['mount_school_sum'] = round($school_theschool * $school_info['mount_oschool'], 2);//课程挂载机构获得的金额

            $vsu_map['tmp_id']   = session_id();
            $vsu_map['type']     = 2;
            $vsu_map['video_id'] = $live_info['id'];
            $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

            //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
            //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
                //如果是销课者  用购买机构的机构与销课者比例
                //否则为普通分享者
                if(is_pinclass($share_uid)){
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_pinclass'], 2);//平台分享者的金额
                }else{
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id hxb
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $proportion['sp_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $proportion['sp_share'], 2);//平台分享者的金额
                }
            } else {
                $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
                $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }

            //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
                $platform_sum_g_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
                $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_g_new3}元；";
                $data['platform_sum'] = $platform_sum_g_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
                unset($data['sid']);                //课程所属机构分成的管理员用户id
                unset($data['school_sum']);         //课程所属机构获得的金额
            }

            //如果购买用户的所属机构管理员和平台管理员相同 销毁购买用户的所属机构管理员的金额 直接加到平台管理员账户上
            if($data['oschool_uid'] == $data['pid']){
                $platform_sum_new = $data['platform_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "平台管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$platform_sum_new}元；";
                $data['platform_sum'] = $platform_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果购买用户的所属机构管理员和课程所属机构管理员相同 销毁购买用户的所属机构管理员的金额的金额 直接加到课程所属机构管理员账户上
            if($data['oschool_uid'] == $data['sid']){
                $school_sum_new = $data['school_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "课程所属机构管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$school_sum_new}元；";
                $data['school_sum'] = $school_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果购买用户的所属机构管理员和挂载课程机构管理员相同 销毁买用户的所属机构管理员的金额的金额的金额 直接加到挂载课程机构管理员账户上
            if($data['oschool_uid'] == $data['mount_school_id']){
                $mount_school_sum_new = $data['mount_school_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "课程挂载机构管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$mount_school_sum_new}元；";
                $data['mount_school_sum'] = $mount_school_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到机构管理员账户上
            if($data['pid'] == $data['sid']) {
                $platform_sum_new2 = $data['platform_sum'] + $data['school_sum'];
                $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['school_sum']}元之和{$platform_sum_new2}元；";
                $data['platform_sum'] = $platform_sum_new2;
                unset($data['sid']);
                unset($data['school_sum']);
            }
        }else {
            $vsu_map['tmp_id']   = session_id();
            $vsu_map['type']     = 2;
            $vsu_map['video_id'] = $live_info['id'];
            $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

            //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
            //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
                //如果是销课者  用购买机构的机构与销课者比例
                //否则为普通分享者
                if(is_pinclass($share_uid)){
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_pinclass'], 2);//平台分享者的金额
                }else{
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id hxb
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $proportion['sp_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $proportion['sp_share'], 2);//平台分享者的金额
                }
            } else {
                $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
                $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }

//            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
//                $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id hxb
//                $data['school_sum'] = round($ouats_theschool * $proportion['sp_school'], 2);//课程所属机构获得的金额
//
//                $data['share_id'] = $share_uid;//获得平台分享者用户id
//                $data['share_sum'] = round($ouats_theschool * $proportion['sp_share'], 2);//平台分享者的金额
//            } else {
//                $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
//                $data['school_sum'] = $ouats_theschool;//课程所属机构获得的金额
//            }

            //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同 销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
                $platform_sum_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
                $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_new3}元；";
                $data['platform_sum'] = $platform_sum_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
                unset($data['sid']);                //课程所属机构分成的管理员用户id
                unset($data['school_sum']);         //课程所属机构获得的金额
            }

            //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $school_uid) {
                $school_sum_add = $data['school_sum'];////机构获得的金额
                $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
                $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
                unset($data['sid']);        //课程所属机构分成的管理员用户id
                unset($data['school_sum']); //课程所属机构获得的金额
            }
            //如果平台管理员和购买用户机构与课程所属机构管理员相同 销毁购买用户机构与课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid']) {
                $ouats_ouschool_sum_add = $data['ouats_ouschool_sum'];
                $platform_sum_new2 = round($data['platform_sum'] + $ouats_ouschool_sum_add, 2);
                $data['note'] .= "平台管理员和购买用户的机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$ouats_ouschool_sum_add}元之和{$platform_sum_new2}元；";
                $data['platform_sum'] = $platform_sum_new2;//平台分成的金额 + 机构获得的金额
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }
            //如果购买用户的机构管理员与课程所属机构管理员相同 销毁购买用户机构管理员的金额 直接加到课程所属机构管理员账户上
            if ($data['oschool_uid'] == $school_uid) {
                $ouats_ouschool_sum_add2 = $data['ouats_ouschool_sum'];
                $school_sum_new = round($data['school_sum'] + $ouats_ouschool_sum_add2, 2);
                $data['note'] .= "购买用户的机构管理员与课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$ouats_ouschool_sum_add2}元之和{$school_sum_new}元；";
                $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }

            //老版本
//        $data['st_id']              = $teacher_uid;//机构教师分成的用户id
//        $data['school_teacher_sum'] = round($school_num * $school_info['sat_teacher'],2);//机构教师分成的金额

            //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
//        if($data['pid'] == $school_uid){
//            $platform_sum_add     =  round($school_num * $school_info['sat_school']);
//            $platform_sum_new     =  round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         .= "平台管理员和机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
//            unset($data['sid']);        //机构分成的管理员用户id
//            unset($data['school_sum']); //机构获得的金额
//        }

            //如果平台管理员和教师相同 销毁教师的金额 直接加到平台管理员账户上
//        if($data['pid'] == $teacher_uid){
//            $platform_sum_add     = round($school_num * $school_info['sat_teacher'],2);
//            $platform_sum_new     = round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         = "平台管理员和教师相同，分成金额为两者的分成金额{$data['platform_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 教师获得的金额
//
//            unset($data['st_id']);//机构教师分成的用户id
//            unset($data['school_teacher_sum']);//机构教师分成的金额
//        }

            //如果机构管理员和教师相同 销毁教师的金额 直接加到机构管理员账户上
//        if($school_uid == $teacher_uid){
//            $platform_sum_add     = round($school_num * $school_info['sat_teacher'],2);
//            $platform_sum_new     = round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         = "机构管理员和教师相同，分成金额为两者的分成金额{$data['school_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['sid']          = $school_uid;//机构分成的管理员用户id
//            $data['school_sum']   = $platform_sum_new;//机构获得的金额 + 机构教师分成的金额
//
//            unset($data['st_id']);              //机构教师分成的用户id
//            unset($data['school_teacher_sum']); //机构教师分成的金额
//        }

            //如果平台管理员、机构管理员、教师都相同 销毁机构管理员、教师的金额 直接加到平台管理员账户上
//        if($data['pid'] == $school_uid && $school_uid == $teacher_uid){
//            $data['note']         = "平台管理员、机构管理员、教师都相同，平台管理员获得所有分成金额，为{$prices}";
//            $data['platform_sum'] = $prices;//平台分成的金额 + 机构获得的金额 + 教师获得的金额
//
//            unset($data['sid']);//机构分成的管理员用户id
//            unset($data['school_sum']);//机构获得的金额
//            unset($data['st_id']);//机构教师分成的用户id
//            unset($data['school_teacher_sum']);//机构教师分成的金额
//        }
        }
//        $data['note']               .= $teacher_user_info;
        $data['ctime']              = time();

        $split_video = M('zy_split_live')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_live')->where($map)->save($data);
        } else {
            $res = M('zy_split_live')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order_live')->where(array('id' => $order_id))->delete();
            return 12;
        }
    }

    /**
     * 点播 分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $video 购买课程详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成比例不存在
     * 6:课程不属于机构
     * 7:机构管理员不存在
     * 8:课程下讲师不存在
     * 9:机构数据里的 机构与教师分成比例不存在
     * 10机构数据里的 机构与挂载机构分成不存在
     * 11购买用户的相应机构管理员不存在
     * 12:课程订单有效期不存在
     * 13:创建订单明细流水失败
     */
    public function addVideoSplit($order_id,$uid,$video = array(),$prices,$pay_video_mount_school){
        //找不到课程
        if (!$video){
            return 2;
        }
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成比例不存在
        if($proportion == 1 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程属于机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid,school_and_teacher,school_and_oschool')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');
        //课程下讲师不存在
        if(!$teacher_uid){
            return 8;
        }

//        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
//        if(is_array($school_and_teacher)){
//            $school_info['sat_school']  = floatval($school_and_teacher[0]);
//            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
//        }
//        //机构数据里的 机构与教师分成不存在
//        if(empty($school_and_teacher)){
//            return 9;
//        }
        $school_and_oschool  = array_filter(explode(':',$school_info['school_and_oschool']));
        if(is_array($school_and_oschool)){
            $school_info['mount_school']  = floatval($school_and_oschool[0]);
            $school_info['mount_oschool'] = floatval($school_and_oschool[1]);
        }

        //机构数据里的 机构与挂载机构分成不存在
        if(empty($school_and_oschool)){
            return 10;
        }
        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //购买用户相应机构抽成班主任比例
        $school_pinclass = model('School')->where(array('id'=>$mhuid))->getField('school_pinclass');
        $school_pinclass  = array_filter(explode(':',$school_pinclass));
        if(is_array($school_pinclass)){
            $school_pinclass['school_pinclass_school']  = floatval($school_pinclass[0]);//购买机构比例
            $school_pinclass['school_pinclass_pinclass']  = floatval($school_pinclass[1]);//销课者比例（从购买机构扣除）
        }

        ///购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //课程订单有效期不存在
        if(!$video['term']){
            return 12;
        }
        //生成订单分成详细 取得价格
        $prices = floatval($prices);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($video['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_id);//购买的机构用户id
        $data['vid']          = intval($video['id']);//所购买点播课程的id
        $data['note']         = t("购买课程：{$video['video_title']}。");
        $data['sum']          = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pac_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['pac_school'];//机构及分析者与教师分成的总金额

        $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
        $data['school_sum'] = round($school_num * $proportion['ouats_theschool'],2);//课程所属机构获得的金额

        //扣掉购买用户的机构用户分成金额后的总金额 剩余参与分成的金额//用来参与分享者扣除的金额 原为从课程所属机构扣除
        $ouats_theschool = round($school_num * $proportion['ouats_theschool'],2);//购买用户机构与课程所属机构分成比例-课程所属机构用户的金额

        $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
//        $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        $ouats_ouschool_sum = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        $mschool_uid = M('school')->where('id = '.$pay_video_mount_school)->getField('uid');
        $map['uid'] = intval($uid);//购买用户ID
        $vm_map['uid'] = $mschool_uid;//挂载此课程的机构用户ID
        $vm_map['mhm_id'] = $pay_video_mount_school;//挂载此课程的机构ID
        $map['vid'] = $vm_map['vid'] = intval($video['id']);//所购买课程的id(包括点播、直播、班级)

        //该课程是否挂载到其他机构 如果挂载按照挂载分销(课程所属机构提供分成比例)，否则按照普通分销(课程所属机构提供分成比例)
        $is_video_mount = M('zy_video_mount')->where($vm_map)->getField('id');

        if($is_video_mount){
            //课程所属机构参与分成部分2
            $school_theschool = $data['school_sum'];
            //课程所属机构的挂载分成金额
            $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
            $data['school_sum'] = round($school_theschool * $school_info['mount_school'], 2);//课程所属机构获得的金额

            //课程挂载机构的挂载分成金额
            $data['mount_school_id'] = $mschool_uid;//课程挂载机构分成的管理员用户id
            $data['mount_school_sum'] = round($school_theschool * $school_info['mount_oschool'], 2);//课程挂载机构获得的金额

            $vsu_map['tmp_id']   = session_id();
            $vsu_map['type']     = 0;
            $vsu_map['video_id'] = $video['id'];
            $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

            //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
            //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
                //如果是销课者  用购买机构的机构与销课者比例
                //否则为普通分享者
                if(is_pinclass($share_uid)){
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_pinclass'], 2);//平台分享者的金额
                }else{
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id hxb
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $proportion['sp_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $proportion['sp_share'], 2);//平台分享者的金额
                }
            } else {
                $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
                $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }

            //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
                $platform_sum_g_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
                $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_g_new3}元；";
                $data['platform_sum'] = $platform_sum_g_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
                unset($data['sid']);                //课程所属机构分成的管理员用户id
                unset($data['school_sum']);         //课程所属机构获得的金额
            }

            //如果购买用户的所属机构管理员和平台管理员相同 销毁购买用户的所属机构管理员的金额 直接加到平台管理员账户上
            if($data['oschool_uid'] == $data['pid']){
                $platform_sum_new = $data['platform_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "平台管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$platform_sum_new}元；";
                $data['platform_sum'] = $platform_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果购买用户的所属机构管理员和课程所属机构管理员相同 销毁购买用户的所属机构管理员的金额的金额 直接加到课程所属机构管理员账户上
            if($data['oschool_uid'] == $data['sid']){
                $school_sum_new = $data['school_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "课程所属机构管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$school_sum_new}元；";
                $data['school_sum'] = $school_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果购买用户的所属机构管理员和挂载课程机构管理员相同 销毁买用户的所属机构管理员的金额的金额的金额 直接加到挂载课程机构管理员账户上
            if($data['oschool_uid'] == $data['mount_school_id']){
                $mount_school_sum_new = $data['mount_school_sum'] + $data['ouats_ouschool_sum'];
                $data['note'] .= "课程挂载机构管理员和购买用户的所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元之和{$mount_school_sum_new}元；";
                $data['mount_school_sum'] = $mount_school_sum_new;
                unset($data['oschool_uid']);
                unset($data['ouats_ouschool_sum']);
            }
            //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到机构管理员账户上
            if($data['pid'] == $data['sid']) {
                $platform_sum_new2 = $data['platform_sum'] + $data['school_sum'];
                $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$data['school_sum']}元之和{$platform_sum_new2}元；";
                $data['platform_sum'] = $platform_sum_new2;
                unset($data['sid']);
                unset($data['school_sum']);
            }
        }else {
            $vsu_map['tmp_id']   = session_id();
            $vsu_map['type']     = 0;
            $vsu_map['video_id'] = $video['id'];
            $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

            //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
            //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
                //如果是销课者  用购买机构的机构与销课者比例
                //否则为普通分享者
                if(is_pinclass($share_uid)){
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_pinclass'], 2);//平台分享者的金额
                }else{
                    $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id hxb
                    $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $proportion['sp_school'], 2);//购买机构获得的金额

                    $data['share_id'] = $share_uid;//获得平台分享者用户id
                    $data['share_sum'] = round($ouats_ouschool_sum * $proportion['sp_share'], 2);//平台分享者的金额
                }
            } else {
                $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
                $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }
//            if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
//                $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id hxb
//                $data['school_sum'] = round($ouats_theschool * $proportion['sp_school'], 2);//课程所属机构获得的金额
//
//                $data['share_id'] = $share_uid;//获得平台分享者用户id
//                $data['share_sum'] = round($ouats_theschool * $proportion['sp_share'], 2);//平台分享者的金额
//            } else {
//                $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
//                $data['school_sum'] = $ouats_theschool;//课程所属机构获得的金额
//            }

            //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同 销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
                $platform_sum_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
                $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_new3}元；";
                $data['platform_sum'] = $platform_sum_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
                unset($data['sid']);                //课程所属机构分成的管理员用户id
                unset($data['school_sum']);         //课程所属机构获得的金额
            }

            //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $school_uid) {
                $school_sum_add = $data['school_sum'];////机构获得的金额
                $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
                $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
                unset($data['sid']);        //课程所属机构分成的管理员用户id
                unset($data['school_sum']); //课程所属机构获得的金额
            }
            //如果平台管理员和购买用户机构与课程所属机构管理员相同 销毁购买用户机构与课程所属机构管理员的金额 直接加到平台管理员账户上
            if ($data['pid'] == $data['oschool_uid']) {
                $ouats_ouschool_sum_add = $data['ouats_ouschool_sum'];
                $platform_sum_new2 = round($data['platform_sum'] + $ouats_ouschool_sum_add, 2);
                $data['note'] .= "平台管理员和购买用户的机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$ouats_ouschool_sum_add}元之和{$platform_sum_new2}元；";
                $data['platform_sum'] = $platform_sum_new2;//平台分成的金额 + 机构获得的金额
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }
            //如果购买用户的机构管理员与课程所属机构管理员相同 销毁购买用户机构管理员的金额 直接加到课程所属机构管理员账户上
            if ($data['oschool_uid'] == $school_uid) {
                $ouats_ouschool_sum_add2 = $data['ouats_ouschool_sum'];
                $school_sum_new = round($data['school_sum'] + $ouats_ouschool_sum_add2, 2);
                $data['note'] .= "购买用户的机构管理员与课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$ouats_ouschool_sum_add2}元之和{$school_sum_new}元；";
                $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
                unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
                unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            }

            //老版本
//        $data['st_id']              = $teacher_uid;//机构教师分成的用户id
//        $data['school_teacher_sum'] = round($school_num * $school_info['sat_teacher'],2);//机构教师分成的金额

            //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
//        if($data['pid'] == $school_uid){
//            $platform_sum_add     =  round($school_num * $school_info['sat_school']);
//            $platform_sum_new     =  round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         .= "平台管理员和机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
//            unset($data['sid']);        //机构分成的管理员用户id
//            unset($data['school_sum']); //机构获得的金额
//        }

            //如果平台管理员和教师相同 销毁教师的金额 直接加到平台管理员账户上
//        if($data['pid'] == $teacher_uid){
//            $platform_sum_add     = round($school_num * $school_info['sat_teacher'],2);
//            $platform_sum_new     = round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         = "平台管理员和教师相同，分成金额为两者的分成金额{$data['platform_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 教师获得的金额
//
//            unset($data['st_id']);//机构教师分成的用户id
//            unset($data['school_teacher_sum']);//机构教师分成的金额
//        }

            //如果机构管理员和教师相同 销毁教师的金额 直接加到机构管理员账户上
//        if($school_uid == $teacher_uid){
//            $platform_sum_add     = round($school_num * $school_info['sat_teacher'],2);
//            $platform_sum_new     = round($data['platform_sum'] + $platform_sum_add,2);
//            $data['note']         = "机构管理员和教师相同，分成金额为两者的分成金额{$data['school_sum']}
//                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['sid']          = $school_uid;//机构分成的管理员用户id
//            $data['school_sum']   = $platform_sum_new;//机构获得的金额 + 机构教师分成的金额
//
//            unset($data['st_id']);              //机构教师分成的用户id
//            unset($data['school_teacher_sum']); //机构教师分成的金额
//        }

            //如果平台管理员、机构管理员、教师都相同 销毁机构管理员、教师的金额 直接加到平台管理员账户上
//        if($data['pid'] == $school_uid && $school_uid == $teacher_uid){
//            $data['note']         = "平台管理员、机构管理员、教师都相同，平台管理员获得所有分成金额，为{$prices}";
//            $data['platform_sum'] = $prices;//平台分成的金额 + 机构获得的金额 + 教师获得的金额
//
//            unset($data['sid']);//机构分成的管理员用户id
//            unset($data['school_sum']);//机构获得的金额
//            unset($data['st_id']);//机构教师分成的用户id
//            unset($data['school_teacher_sum']);//机构教师分成的金额
//        }

        }

        $data['ctime'] = time();


        $split_video = M('zy_split_course')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_course')->where($map)->save($data);
        } else {
            $res = M('zy_split_course')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order_course')->where(array('id' => $order_id))->delete();
            return 13;
        }
    }

    /**
     * 班级分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $album 购买班级详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成比例不存在
     * 6:班级不属于机构
     * 7:班级所绑定的机构管理员不存在
     * 8：班级中包含有 没有相关讲师用户不存在的课程id
     * 9：班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
     * 10:机构数据里的 机构与教师分成不存在
     * 11:创建订单明细流水失败 并删除此订单
     */
    public function addAlbumSplit($order_id,$uid,$album = array())
    {
        //找不到班级
        if (!$album) {
            return 2;
        }

        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成比例不存在
        if ($proportion == 1 || $proportion == 3 || $proportion == 4) {
            return 5;
        }

        //班级不属于机构
        if (!$album['mhm_id']) {
            return 6;
        }

        //班级属于机构 先平台和机构分成 再机构与教师分成
        $school_info = model('School')->where(array('id' => $album['mhm_id']))->field('uid,school_and_teacher')->find();
        $school_uid = M('user')->where(array('uid' => $school_info['uid']))->getField('uid');
        //班级所绑定的机构管理员不存在
        if (!$school_uid) {
            return 7;
        }

        $albumId = intval($album['id']);

        //获取班级下所有的课程ID
        $video_ids = trim(D("Album")->getVideoId($albumId), ',');
        $v_map['id'] = array('in', array($video_ids));
        $v_map["is_del"] = 0;
        $album_info = M("zy_video")->where($v_map)->field("id,video_title,mhm_id,teacher_id,
                              v_price,t_price,vip_level,endtime,starttime,limit_discount,type,live_type")
            ->select();
        //班级下所有的课程数量 班级下没有课程
        $album['video_count'] = count($album_info);
//        if ($album['video_count'] <= 0) {
//            return 3;
//        }

        $illegal_count = 0;
        $total_price = 0;

        //取得分享者信息
        $vsu_map['tmp_id'] = session_id();
        $vsu_map['type'] = 1;
        $vsu_map['video_id'] = $albumId;
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //通过课程取得专辑价格
        $video_id = '';
        $tuid = '';
        $share_str = '';
        $teacher_user_info = "此班级下课程所有讲师信息分别为  ";
        foreach ($album_info as $key => $video) {

            if ($video['mhm_id'] != $album['mhm_id']) {
                $video_id .= $video['id'] . ',';//课程和班级的机构id不一致
            }
//            $album_info[$key]['price'] = getPrice($video, $this->mid, true, true);
//            //价格为0的 限时免费的  不加入购物记录
//            if ($album_info[$key]['price']['oriPrice'] == 0) {
//                unset($album_info[$key]);
//                continue;
//            }
//            $album_info[$key]['is_buy'] = D("ZyOrder")->isBuyVideo($this->mid, $video['id']);
//            $total_price += ($album_info[$key]['is_buy'] || $video['uid'] == $this->mid) ? 0 : round($album_info[$key]['price']['price'], 2);
            //判断是否有课程过期
            if ($video['uctime'] < time()) {
                $illegal_count += 1;
                $video_gid = $video['id'];//过期id
            }
            //判断班级下没有相关讲师用户不存在的课程id
            $teacher_info = M('zy_teacher')->where(array('id' => $video['teacher_id']))->field('id,uid,name')->find();
            $teacher_uinfo = M('user')->where(array('uid' => $teacher_info['uid']))->field('uid,uname')->find();
            if ($video['type'] != 2) {
                if (!$teacher_uinfo['uid']) {
                    $tuid .= $video['id'] . ',';//班级下没有相关讲师用户不存在的课程id
                }
            }

            $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
            //拼接班级下所有讲师信息以便线下分成
            $key = $key + 1;
            if ($video['type'] != 2) {
                $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_info['id'] . " ,讲师名字为：" . $teacher_info['name'] .
                    " ;其用户id为： " . $teacher_uinfo['uid'] . " ,用户名为： " . $teacher_uinfo['uname'] . " 。
                    ";
            }
            if ($share_uid == $teacher_uinfo['uid']) {
                $share_str .= $video['id'] . ',';//分享者为当前班级下讲师
            }

            if ($video['type'] == 2) {
                if ($video['live_type'] == 1) {
                    $live_zshd_hour = M('zy_live_zshd')->where('live_id=' . $video['id'])->field('id,speaker_id')->findAll();
                    //直播课程下所有的课程数量 直播课程下没有课程
                    $video_count = count($live_zshd_hour);
//                    if ($video_count <= 0) {
//                        return 3;
//                    }

                    $tuid = '';
                    foreach ($live_zshd_hour as $k => $v) {
                        //判断直播课堂下没有相关讲师用户不存在的课程
                        $teacher_live_info = M('zy_teacher')->where(array('id' => $v['speaker_id']))->field('id,uid,name')->find();
                        $teacher_live_uinfo = M('user')->where(array('uid' => $teacher_live_info['uid']))->field('uid,uname')->find();
                        if (!$teacher_live_uinfo['uid']) {
                            $tuid .= $v['id'] . ',';//直播课堂下没有相关讲师用户不存在的课程id
                        }
                        $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                        //拼接直播课堂下所有讲师信息以便线下分成
                        $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_live_info['id'] . " ,讲师名字为：" . $teacher_live_info['name'] .
                            " ;其用户id为： " . $teacher_live_uinfo['uid'] . " ,用户名为： " . $teacher_live_uinfo['uname'] . " 。
                            ";
                        if ($share_uid == $teacher_live_uinfo['uid']) {
                            $share_str .= $v['id'] . ',';//分享者为当前班级下讲师
                        }
                    }
                } elseif ($video['live_type'] == 3) {
                    $live_gh_hour = M('zy_live_gh')->where('live_id=' . $video['id'])->field('id,speaker_id')->findAll();
                    foreach ($live_gh_hour as $k => $v) {
                        //判断直播课堂下没有相关讲师用户不存在的课程
                        $teacher_live_info = M('zy_teacher')->where(array('id' => $v['speaker_id']))->field('id,uid,name')->find();
                        $teacher_live_uinfo = M('user')->where(array('uid' => $teacher_live_info['uid']))->field('uid,uname')->find();
                        if (!$teacher_live_uinfo['uid']) {
                            $tuid .= $v['id'] . ',';//直播课堂下没有相关讲师用户不存在的课程id
                        }
                        //拼接直播课堂下所有讲师信息以便线下分成
                        $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_live_info['id'] . " ,讲师名字为：" . $teacher_live_info['name'] .
                            " ;其用户id为： " . $teacher_live_uinfo['uid'] . " ,用户名为： " . $teacher_live_uinfo['uname'] . " 。
                                        ";
                    }
                    if ($share_uid == $teacher_live_uinfo['uid']) {
                        $share_str .= $v['id'] . ',';//分享者为当前班级下讲师
                    }
                }
            }
            $album_info['tuid_str'] .= $teacher_uinfo['uid'] . ",";
        }

        //班级中包含有 没有相关讲师用户不存在的课程id
        if ($tuid) {
            return 8;
        }

        //班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
        if ($video_id) {
            return 9;
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //购买用户相应机构抽成班主任比例
        $school_pinclass = model('School')->where(array('id'=>$mhuid))->getField('school_pinclass');
        $school_pinclass  = array_filter(explode(':',$school_pinclass));
        if(is_array($school_pinclass)){
            $school_pinclass['school_pinclass_school']  = floatval($school_pinclass[0]);//购买机构比例
            $school_pinclass['school_pinclass_pinclass']  = floatval($school_pinclass[1]);//销课者比例（从购买机构扣除）
        }

        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //生成订单分成详细 取得价格
        $prices = floatval($album['price']);
        $data['status'] = 0;
        $data['order_id'] = $order_id;
        $data['uid'] = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($album['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_id);//购买的用户机构id
        $data['aid'] = $albumId;//所购买课程的id
        $data['note'] .= t("购买班级：{$album['album_title']}。");
        $data['sum'] = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pac_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['pac_school'];//机构及分析者与教师分成的总金额

        $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
        $data['school_sum'] = round($school_num * $proportion['ouats_theschool'],2);//课程所属机构获得的金额

//        $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
//        $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        //扣掉购买用户的机构用户分成金额后的总金额 剩余参与分成的金额
        $ouats_ouschool_sum = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
        //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
        if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
            //如果是销课者  用购买机构的机构与销课者比例
            //否则为普通分享者
            if(is_pinclass($share_uid)){
                $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id
                $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_school'], 2);//购买机构获得的金额

                $data['share_id'] = $share_uid;//获得平台分享者用户id
                $data['share_sum'] = round($ouats_ouschool_sum * $school_pinclass['school_pinclass_pinclass'], 2);//平台分享者的金额
            }else{
                $data['oschool_uid'] = $oschool_uid;//购买机构分成的管理员用户id hxb
                $data['ouats_ouschool_sum'] = round($ouats_ouschool_sum * $proportion['sp_school'], 2);//购买机构获得的金额

                $data['share_id'] = $share_uid;//获得平台分享者用户id
                $data['share_sum'] = round($ouats_ouschool_sum * $proportion['sp_share'], 2);//平台分享者的金额
            }
        } else {
            $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
            $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
        }

        //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同 销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
            $platform_sum_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
            $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_new3}元；";
            $data['platform_sum'] = $platform_sum_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            unset($data['sid']);                //课程所属机构分成的管理员用户id
            unset($data['school_sum']);         //课程所属机构获得的金额
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }
        //如果平台管理员和购买用户机构与课程所属机构管理员相同 销毁购买用户机构与课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $data['oschool_uid']) {
            $ouats_ouschool_sum_add = $data['ouats_ouschool_sum'];
            $platform_sum_new2 = round($data['platform_sum'] + $ouats_ouschool_sum_add, 2);
            $data['note'] .= "平台管理员和购买用户的机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$ouats_ouschool_sum_add}元之和{$platform_sum_new2}元；";
            $data['platform_sum'] = $platform_sum_new2;//平台分成的金额 + 机构获得的金额
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
        }
        //如果购买用户的机构管理员与课程所属机构管理员相同 销毁购买用户机构管理员的金额 直接加到课程所属机构管理员账户上
        if ($data['oschool_uid'] == $school_uid) {
            $ouats_ouschool_sum_add2 = $data['ouats_ouschool_sum'];
            $school_sum_new = round($data['school_sum'] + $ouats_ouschool_sum_add2, 2);
            $data['note'] .= "购买用户的机构管理员与课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$ouats_ouschool_sum_add2}元之和{$school_sum_new}元；";
            $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
        }

        $data['ctime'] = time();

        $map['uid'] = intval($uid);//购买用户ID
        $map['aid'] = $albumId;//所购买班级的id

        $split_video = M('zy_split_album')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_album')->where($map)->save($data);
        } else {
            $res = M('zy_split_album')->add($data);
        }
        if ($res) {
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('ZyOrderAlbum')->where(array('id' => $order_id))->delete();
            return 12;
        }

        exit;

        //第二次大改老版本
        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['pac_school'];//机构及分析者与教师分成的总金额

        $data['oschool_uid']        = $oschool_uid;//购买用户机构与课程所属机构分成比例-购买用户机构用户id
        $data['ouats_ouschool_sum'] = round($school_num * $proportion['ouats_ouschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        //扣掉购买用户的机构用户分成金额后的总金额 剩余参与分成的金额
        $ouats_theschool = round($school_num * $proportion['ouats_theschool'],2);//购买用户机构与课程所属机构分成比例-购买用户机构用户的金额

        //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，则参与分享者分成 否则直接加到课程所属机构管理员账户
        if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && !$share_str) {
            $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
            $data['school_sum'] = round($ouats_theschool * $proportion['sp_school'], 2);//课程所属机构获得的金额

            $data['share_id'] = $share_uid;//获得平台分享者用户id
            $data['share_sum'] = round($ouats_theschool * $proportion['sp_share'], 2);//平台分享者的金额
        } else {
            $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
            $data['school_sum'] = $ouats_theschool;//课程所属机构获得的金额
        }

        //如果平台管理员、购买用户的机构管理员、课程所属机构管理员都相同 销毁购买用户机构管理员、课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $data['oschool_uid'] && $data['pid'] == $school_uid) {
            $platform_sum_new3 = round($data['platform_sum'] + $data['ouats_ouschool_sum'] + $data['school_sum'], 2);
            $data['note'] .= "平台管理员、购买用户的机构管理员、课程所属机构管理员都相同，分成金额为三者的分成金额{$data['platform_sum']}元
                                     加上{$data['ouats_ouschool_sum']}元和{$data['school_sum']}元之和{$platform_sum_new3}元；";
            $data['platform_sum'] = $platform_sum_new3;//平台管理员、购买用户的机构管理员、课程所属机构管理员之和
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
            unset($data['sid']);                //课程所属机构分成的管理员用户id
            unset($data['school_sum']);         //课程所属机构获得的金额
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }
        //如果平台管理员和购买用户机构与课程所属机构管理员相同 销毁购买用户机构与课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $data['oschool_uid']) {
            $ouats_ouschool_sum_add = $data['ouats_ouschool_sum'];
            $platform_sum_new2 = round($data['platform_sum'] + $ouats_ouschool_sum_add, 2);
            $data['note'] .= "平台管理员和购买用户的机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$ouats_ouschool_sum_add}元之和{$platform_sum_new2}元；";
            $data['platform_sum'] = $platform_sum_new2;//平台分成的金额 + 机构获得的金额
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
        }
        //如果购买用户的机构管理员与课程所属机构管理员相同 销毁购买用户机构管理员的金额 直接加到课程所属机构管理员账户上
        if ($data['oschool_uid'] == $school_uid) {
            $ouats_ouschool_sum_add2 = $data['ouats_ouschool_sum'];
            $school_sum_new = round($data['school_sum'] + $ouats_ouschool_sum_add2, 2);
            $data['note'] .= "购买用户的机构管理员与课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$ouats_ouschool_sum_add2}元之和{$school_sum_new}元；";
            $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['oschool_uid']);        //购买用户机构与课程所属机构分成比例-购买用户机构用户id
            unset($data['ouats_ouschool_sum']); //购买用户机构与课程所属机构分成比例-购买用户机构用户的金额
        }

        //老版本
//        //如果分享者有且不为平台管理员、机构管理员和分享者不为当前班级下讲师，则参与分享者分成
//        if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && !$share_str) {
//            $data['ps_id'] = $share_uid;//获得平台分享者用户id
//            $data['platform_share_sum'] = round($prices * $proportion['ps_share'], 2);//分享者的金额
//            $prices = round($prices * $proportion['ps_platform'], 2);//平台与分享者-平台
//        }
//
//        $data['pid'] = 1;//获得平台分成的管理员用户id
//        $data['platform_sum'] = round($prices * $proportion['pac_platform'], 2);//平台分成的金额
//
//        $school_num = $prices * $proportion['pac_school'];//机构及教师分成的总金额
//
////        //取得机构数据里的 机构与教师分成
////        $school_and_teacher = array_filter(explode(':', $school_info['school_and_teacher']));
////        if (is_array($school_and_teacher)) {
////            $school_info['sat_school'] = floatval($school_and_teacher[0]);
////            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
////        }
////        //机构数据里的 机构与教师分成不存在
////        if (empty($school_and_teacher)) {
////            return 10;
////        }
//
//        //生成分成流水详情
//        $data['sid'] = $school_uid;//机构分成的管理员用户id
//        $data['school_sum'] = round($school_num * $school_info['sat_school'], 2);//机构获得的金额
//
//        $data['st_id'] = 0;//trim($album_info['tuid_str'],',') 机构教师分成的所有教师用户id
//        $data['school_teacher_sum'] = round($school_num * $school_info['sat_teacher'], 2);//机构教师分成所有的金额
//
//        //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
//        if ($data['pid'] == $school_uid) {
//            $platform_sum_add = round($school_num * $school_info['sat_school'], 2);
//            $platform_sum_new = round($data['platform_sum'] + $platform_sum_add, 2);
//            $data['note'] .= "平台管理员和机构管理员为同一人，分成金额为两者的分成金额{$data['platform_sum']}
//                             加上{$platform_sum_add}之和{$platform_sum_new}元；";
//            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
//
//            unset($data['sid']);        //机构分成的管理员用户id
//            unset($data['school_sum']); //机构获得的金额
//        }
//        $data['note'] .= $teacher_user_info;
        $data['ctime'] = time();

        $map['uid'] = intval($uid);//购买用户ID
        $map['aid'] = $albumId;//所购买班级的id

        $split_video = M('zy_split_album')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_album')->where($map)->save($data);
        } else {
            $res = M('zy_split_album')->add($data);
        }
        if ($res) {
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('ZyOrderAlbum')->where(array('id' => $order_id))->delete();
            return 12;
        }
    }

    /**
     * 班级、点播 普通分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $album 购买班级详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:课程不属于机构
     * 6:机构管理员不存在
     * 7:平台与机构分成比例为空
     * 8:机构数据里的 机构与教师分成不存在
     * 9:创建订单明细流水失败
     */
    public function addAlbumBakSplit($order_id,$uid,$album = array()){
        //找不到班级
        if (!$album){
            return 2;
        }
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成比例为空
        if($proportion == 1){
            return 7;
        }
        //生成订单分成详细 取得价格
        $prices = floatval($album['total_price']);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($album['mhm_id']);//机构ID
        $data['vid']          = intval($album['id']);//所购买课程的id(包括点播、直播、班级)
        $data['note']         = t("购买班级：{$album['album_title']}。");
        $data['sum']          = $prices;//购买金额
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pac_platform']);//平台分成的金额

        $school_num = $prices * $proportion['pac_school'];//机构及教师分成的总金额
        //课程是否属于机构
        if($album['mhm_id']){
            //课程属于机构 先平台和机构分成 再机构与教师分成
            $school_info = model('School')->where(array('id'=>$album['mhm_id']))->field('uid,school_and_teacher')->find();
            $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

            if(!$school_uid){
                //机构管理员不存在
                return 6;
            }

            //取得机构数据里的 机构与教师分成
            $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
            if(is_array($school_and_teacher)){
                $school_info['sat_school']  = floatval($school_and_teacher[0]);
                $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
            }
            //机构数据里的 机构与教师分成不存在
            if(empty($school_and_teacher)){
                return 8;
            }

            $albumId        = intval($album['id']);

            //获取班级下所有的课程ID
            $video_ids      = trim(D("Album")->getVideoId($albumId), ',');
            $v_map['id']      = array('in', array($video_ids));
            $v_map["is_del"]  = 0;
            $album_info     = M("zy_video")->where($v_map)->field("id,video_title,mhm_id,teacher_id,
                              v_price,t_price,vip_level,endtime,starttime,limit_discount")
                ->select();
            //班级下所有的课程数量 班级下没有课程
            $album['video_count'] = count($album_info);
//            if($album['video_count'] <= 0){
//                return 3;
//            }

            //班级不属于机构
            if(!$album['mhm_id']){
                return 5;
            }

            $illegal_count  = 0;
            $total_price    = 0;

            //通过课程取得专辑价格
            $video_id   = '';
            $tuid       = '';
            $tuid_str   = '';
            $tuid_pcount = 0;
            $tuid_scount = 0;
            foreach ($album_info as $key => $video) {
                if($video['mhm_id'] != $album['mhm_id']){
                    $video_id       .= $video['id'].',';//课程和班级的机构id不一致
                }
                $album_info[$key]['price'] = getPrice($video, $this->mid, true, true);
                //价格为0的 限时免费的  不加入购物记录
                if($album_info[$key]['price']['oriPrice'] == 0){
                    unset($album_info[$key]);
                    continue;
                }
                $album_info[$key]['is_buy'] = D("ZyOrder")->isBuyVideo($this->mid, $video['id']);
                $total_price                += ($album_info[$key]['is_buy'] || $video['uid'] == $this->mid) ? 0 : round($album_info[$key]['price']['price'], 2);
                //判断是否有课程过期
                if ($video['uctime'] < time()) {
                    $illegal_count  += 1;
                    $video_gid       = $video['id'];//过期id
                }

                //判断班级下课程是否有是平台管理员或者机构管理员的 有就把相应的那一份分到其账户下
                $t_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
                $teacher_uid = M('user')->where(array('uid'=>$t_uid))->getField('uid');
                $album_info[$key]['teacher_uid'] = $teacher_uid;

                //判断班级下没有相关讲师用户不存在的课程id
                if(!$teacher_uid){
                    $tuid       .= $video['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                //判断讲师用户id是否与平台管理员(为1)一样 返回tuid_pcount+1
                if($teacher_uid == 1){
                    $tuid_pcount += 1;
                    unset($album_info[$key]['teacher_uid']);
                    //判断讲师用户id是否与班级机构管理员(为1)一样 有scount+1
                }
                if($teacher_uid == $school_uid){
                    $tuid_scount += 1;
                    unset($album_info[$key]['teacher_uid']);
                }
            }

            //获得剩余的讲师用户id
            foreach ($album_info as $key => $video){
                $tuid_str .= $video['teacher_uid'].',';
            }
            $album_info['tuid_str'] = $tuid_str;

            //班级中包含有 没有相关讲师用户不存在的课程
            if ($tuid) {
                return 0;
            }
            //判断讲师用户id是否有与平台管理员相同的课程 有直接把讲师的那一份加到平台管理员上 $tuid_pcount为几份
            if ($tuid_pcount > 0) {
                $album['tuid_pcount'] = $tuid_pcount;
            }
            //判断讲师用户id是否有与班级机构管理员 有直接把讲师的那一份加到班级机构管理员上 $tuid_scount为几份
            if ($tuid_scount > 0) {
                $album['tuid_scount'] = $tuid_scount;
            }

            $data['sid']                = $school_uid;//机构分成的管理员用户id
            $data['school_sum']         = $school_num;//机构获得的金额


//            $data['school_sum']         = $school_num * $school_info['sat_school'];//机构获得的金额

//            $data['st_id']              = trim($album_info['tuid_str'],',');//机构教师分成的所有教师用户id
//            $data['school_teacher_sum'] = $school_num * $school_info['sat_teacher'];//机构教师分成的金额

            //取得每个教师获得的金额
            $each_teacher_num = round($data['school_teacher_sum']/$album['video_count'],2);

            //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
            if($data['pid'] == $school_uid){
                $platform_sum_add = $school_num * $school_info['sat_school'];
                $platform_sum_new = $data['platform_sum'] + $platform_sum_add;
                $data['note']     .= "平台管理员和机构管理员为同一人，分成金额为两者的分成金额{$data['platform_sum']}
                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额

                unset($data['sid']);        //机构分成的管理员用户id
                unset($data['school_sum']); //机构获得的金额
            }

            //如果有为平台管理员的课程 直接把讲师的那一份加到平台管理员上 $tuid_pcount为几份
            if($tuid_pcount > 0){
                $platform_sum_add = $each_teacher_num * $tuid_pcount;//课程所得金额
                $platform_sum_new = $data['platform_sum'] + $platform_sum_add;
                //剩余分成讲师人数
                $teacher_num = $album['video_count'] - $tuid_pcount;
                //剩余讲师分成总金额
                $school_teacher_surplus_sum = $data['school_teacher_sum'] - $platform_sum_add;
                //剩余讲师分成人均金额
                $school_teacher_each_surplus_sum = $school_teacher_surplus_sum / $teacher_num;

                $data['note']     .= "此班级下平台管理员有{$tuid_pcount}堂课程，平台管理员分成的金额为{$data['platform_sum']}元
                                     加上课程所得金额{$platform_sum_add}元,共计{$platform_sum_new}元；,讲师分成总金额为{$data['school_teacher_sum']}，
                                     剩余讲师分成金额为{$school_teacher_surplus_sum}元，剩余讲师分成人数为{$teacher_num}人，
                                     人均{$school_teacher_each_surplus_sum}；";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 课程所得金额
                $data['school_teacher_sum'] = $school_teacher_each_surplus_sum;//机构教师分成的剩余人均金额
            }

            //如果有为机构管理员的课程 直接把讲师的那一份加到机构管理员上 $tuid_scount为几份
            if($tuid_scount > 0){
                $platform_sum_add = $each_teacher_num * $tuid_scount;
                $platform_sum_new = $data['school_sum'] + $platform_sum_add;
                //剩余分成讲师人数
                $teacher_num = $album['video_count'] - $tuid_scount;
                //剩余讲师分成总金额
                $school_teacher_surplus_sum = $data['school_teacher_sum'] - $platform_sum_add;
                //剩余讲师分成人均金额
                $school_teacher_each_surplus_sum = $school_teacher_surplus_sum / $teacher_num;

                $data['note']     .= "此班级下平台管理员有{$tuid_pcount}堂课程，机构管理员分成的金额为{$data['platform_sum']}元
                                     加上课程所得金额{$platform_sum_add}元,共计{$platform_sum_new}元；,讲师分成总金额为{$data['school_teacher_sum']}，
                                     剩余讲师分成金额为{$school_teacher_surplus_sum}元，剩余讲师分成人数为{$teacher_num}人，
                                     人均{$school_teacher_each_surplus_sum}；";

                $data['sid']          = $school_uid;//机构分成的管理员用户id
                $data['school_sum']   = $platform_sum_new;//平台分成的金额 + 机构获得的金额
                $data['school_teacher_sum'] = $school_teacher_each_surplus_sum;//机构教师分成的剩余人均金额
            }

            //如果平台管理员和机构管理员相同 并且所有课程都属于平台管理员
            if($tuid_pcount == $album['video_count'] && $data['pid'] == $school_uid){
                $data['note'] .= "平台管理员和机构管理员为同一人，并且此班级下所有课程都是平台管理员
                                  获得此班级全部分成金额收人，为{$prices}元；";
                $data['platform_sum'] = $prices;//获得所有分成金额
            }
        } else {
            //班级不属于机构
            return 5;
        }
        $data['ctime'] = time();//机构教师分成的金额

        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = intval($albumId);//所购买课程的id(包括点播、直播、班级)
        $split_video = M('zy_split_video')->where($map)->getField('id');
        if ($split_video) {
            $res = M('zy_split_video')->where($map)->save($data);
        } else {
            $res = M('zy_split_video')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order')->where(array('id' => $order_id))->delete();
            return 9;
        }
    }

    /**
     * 检查一个用户是否可以直接观看某个课程
     * @param integer $uid 用户ID
     * @param integer $video_id 课程ID
     * @return boolean 可以直接观看返回true，否则返回false
     */
    public function checkVideoAccess($uid, $video_id) {
        $time = time();
        //取得课程
        $video = D('ZyVideo')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            //'uctime' => array('gt', $time),
            //'listingtime' => array('lt', $time),
        ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,
                    vip_level,listingtime,uctime,limit_discount,uid,teacher_id")->find();

        //找不到课程
        if (!$video){
            return false;
        }
        //管理员
        if (model('UserGroup')->isAdmin($uid)) {
            return true;
        }
        //如果是机构管理员 该机构的课程
        if($video['mhm_id'] && is_school($uid) == $video['mhm_id']){
            return true;
        }
        $teacher_id = D('ZyTeacher','classroom')->getTeacherStrByMap((array('uid'=>$uid)),'id');
        //自己的课程
        if($uid || $video['uid']){
            if ($video['uid'] == $uid ){
                return true;
            }
        }
        if($video['teacher_id'] || $teacher_id) {
            if ($video['teacher_id'] == $teacher_id) {
                return true;
            }
        }
        $order = D('ZyOrderCourse','classroom');
        //检查是否已经购买过了
        if ($order->isBuyVideo($uid, $video_id)){

            return true;
        }

        $order = D('ZyOrderLive','classroom');
        if ($order->isBuyVideo($uid, $video_id)){

            return true;
        }

        //取得价格
        $prices = getPrice($video, $uid, true, true);
        //限时免费和价格为0的  不需购买
        if ($prices['price'] <= 0 || $video['is_charge'] == 1){
            return true;
        }

        return false;
    }

}
