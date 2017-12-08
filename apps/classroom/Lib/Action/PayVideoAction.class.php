<?php
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class PayVideoAction extends CommonAction{
    protected $payConfig;

    /**
     * 初始化
     */
    public function _initialize(){
        $this->payConfig = model('Xdata')->get("admin_Config:payConfig");

        $this->assign('payConfig',$this->payConfig);
        parent::_initialize();
    }


    public function index(){
        if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            model('WxPay')->getWxUserInfo($_GET['code'], SITE_URL.'/pay/'.$_GET['vst'].'.html');
        }
        $str = $_GET['vst'];
        $data_arr = explode(',', $str);

        $cid = $data_arr[4];
        $pay_video_id = intval($data_arr[0]);

        $pay_video_type = t($data_arr[1]);
        $pay_video_type_arr = explode('_',$pay_video_type);

//        $pay_video_mount = t($data_arr[2]);
//        $pay_video_mount_school = explode('H', $pay_video_mount)[0];
        $pay_video_mount_school = t($data_arr[2]);

        $pay_gspay = t($data_arr[3]);

        if($pay_video_type_arr[1] == 'video' || $pay_video_type_arr[1] == 'album' || $pay_video_type_arr[1] == 'live'|| $pay_video_type_arr[1] == 'teacher'){
            $vtype = $pay_video_type_arr[1];
        }else{
            $vtype = false;
        }
        //$this->assign('jumpUrl', U('classroom/Index/index'));
        if(!is_numeric($pay_video_id) || $pay_video_type_arr[0] != 'zy' || !$vtype){
            $this->error("错误的购买参数");
        }

        if($pay_video_type == 'zy_video'){
            $video_map ['id']          = $pay_video_id;
            $video_map ['is_activity'] = ['in','1,5,6,7'];
            $video_map ['is_del']      = 0;
            $video_map ['uctime']      = array('gt',time());
            $video_map ['listingtime'] = array('lt',time());
            $data = D ( 'ZyVideo' )->where ( $video_map )->field("id,uid,video_title,mhm_id,teacher_id,cover,fullcategorypath,term,v_price,t_price,vip_level,
                    listingtime,uctime,limit_discount,term,type")->find ();
            if(!$data){
                $this->error("课程不存在");
            }
            $data['term_num']  = $data['term'] ? $data['term']: '永久';
            $data['video_section_num']  = M('zy_video_section')->where(array('vid'=>$data['id'],'pid'=>array('neq',0)))->field('id')->count();
            $data['moner_data'] = getPrice($data, $this->mid, false, true);
        }else if($pay_video_type == 'zy_live'){
            $live_map['id'] = $pay_video_id;
            $live_map['is_del'] = 0;
            $live_map['is_activity'] = ['in','1,5,6,7'];
            $live_map['type'] = 2;
            $live_map['listingtime'] = array('lt', time());
            $live_map['uctime'] = array('gt', time());
            $data = D('ZyVideo')->where($live_map)->field("id,uid,video_title,mhm_id,teacher_id,cover,fullcategorypath,term,v_price,t_price,vip_level,
                    listingtime,uctime,limit_discount,live_type,type")->find ();
            if(!$data){
                $this->error("直播课程不存在");
            }
            $data['moner_data'] = getPrice($data, $this->mid, false, true,$data['type']);
            //总课时
            $liveData = model('Live')->liveSpeed($data['live_type'],$data['id']);
            $data['video_section_num'] = $liveData['count'];
        }else if($pay_video_type == 'zy_album'){
            $map['id'] = array('eq', $pay_video_id);
            $map['status'] = 1;
            $data = D('Album')->where($map)->find();
            if (!$data) {
                $this->assign('isAdmin', 1);
                $this->error('班级不存在!');
            }

            $album_video = D('Album')->getVideoId($pay_video_id);
            $oPrice = 0.00;
            $videoData = array();
            if($album_video){
                $tMap['id'] = array('IN',trim( $album_video ,','));
                $tMap['is_del'] = array('eq',0);
                $tMap['teacher_id'] = array('NEQ',0);
                $videoData = D('ZyVideo')->where($tMap)->field('id,teacher_id,term,t_price,video_title,cover,video_intro')->select();
            }
            foreach($videoData as $key=>$val){
                $tch_ids[$key] = $val['teacher_id'];
                $oPrice += $val['t_price'];
            }

            //获取班级价格
            $data['moner_data']['oriPrice'] = ($oPrice - $data['price']) > 0 ? $oPrice : $data['price'];
            $data['moner_data']['price'] = $data['price'];
            $data['video_title'] = $data['album_title'];
            $data['fullcategorypath'] = $data['album_category'];
            $data['video_section_num'] = count($videoData);
        }elseif($pay_video_type == 'zy_teacher'){
            $video_map ['course_id']   = $pay_video_id;
            $video_map ['is_activity'] = 1;
            $video_map ['is_del']      = 0;
            $video_map ['uctime']      = array('gt',time());
            $video_map ['listingtime'] = array('lt',time());
            $data = M ( 'zy_teacher_course' )->where ( $video_map )->find ();
            if(!$data){
                $this->error("线下课程不存在");
            }
            $data['video_title']  = $data['course_name'];
            //$data['term_num']  = $data['term'] ? $data['term']: '永久';

            //线下课价格
            $data['t_price'] = $data['v_price']= $data['course_price'];
            $data['id']      = $data['course_id'];
            $data['moner_data'] = getPrice($data,$this->mid,true,true,4);

//            $ceta_data = array_filter(explode(',', $data['fullcategorypath']));
//            foreach ($ceta_data as $key => $val){
//                $cetas_data[$key]['cate_title'] = M('zy_currency_category')->where(array('zy_currency_category_id'=>$val))->getField('title');
//                $pid = M('zy_currency_category')->where('zy_currency_category_id = '.$val)->getField('pid');
//                $cetas_data[$key]['cate_id'] = $val;
//                if($pid){
//                    $cetas_data[$key]['cate_id'] = $pid.','.$val;
//                    $pid2 = M('zy_currency_category')->where('zy_currency_category_id = '.$pid)->getField('pid');
//                    if($pid2){
//                        $cetas_data[$key]['cate_id'] = $pid2.','.$pid.','.$val;
//                    }
//                }
//            }
//            $school_info = M('school')->where(array('id'=>$data['mhm_id']))->field('title,doadmin')->find();
//            $school_info['domain'] = getDomain($school_info['doadmin'],$data['mhm_id']);
//            $data['mhm_info'] = $school_info;
//            $data['cate_info'] = $cetas_data;
        }

        $ceta_data = array_filter(explode(',', $data['fullcategorypath']));
        foreach ($ceta_data as $key => $val){
            $cetas_data[$key]['cate_title'] = M('zy_currency_category')->where(array('zy_currency_category_id'=>$val))->getField('title');
            $pid = M('zy_currency_category')->where('zy_currency_category_id = '.$val)->getField('pid');
            $cetas_data[$key]['cate_id'] = $val;
            if($pid){
                $cetas_data[$key]['cate_id'] = $pid.','.$val;
                $pid2 = M('zy_currency_category')->where('zy_currency_category_id = '.$pid)->getField('pid');
                if($pid2){
                    $cetas_data[$key]['cate_id'] = $pid2.','.$pid.','.$val;
                }
            }
        }
        $school_info = M('school')->where(array('id'=>$data['mhm_id']))->field('title,doadmin')->find();
        $data['mhm_info'] = $school_info;
        $data['cate_info'] = $cetas_data;

        //查找此课程可用的优惠券、打折卡
        $coupon_id = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid,'status'=>2,'is_del'=>0,'etime'=>['gt',time()]))->getField('cid');
        $new_coupon = model('Coupon')->where(array('id'=>$coupon_id,'is_del'=>0))->find();
        if(!$new_coupon){
            if($pay_video_type == 'zy_video' || $pay_video_type == 'zy_live' || $pay_video_type == 'zy_album'){
                $vc_map = array('uid'=>$this->mid,'type'=>1,'sid'=>$data['mhm_id'],'coupon_type'=>0);
                $videoCoupon = model('Coupon')->getUserCouponList($vc_map,0);
                foreach ($videoCoupon['data'] as $key => $val) {
                    $videoCoupon['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
                    $videoCoupon['data'][$key]['price'] = floor($val['price']);
                    $videoCoupon['data'][$key]['maxprice'] = floor($val['maxprice']);
                    $videoCoupon['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
                    $videoCoupon['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
                }
                $vd_map = array('uid'=>$this->mid,'type'=>2,'sid'=>$data['mhm_id'],'coupon_type'=>0);
                $discount = model('Coupon')->getUserCouponList($vd_map,0);
                foreach ($discount['data'] as $key => $val) {
                    $discount['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
                    $time = time();
                    if($val['status'] == 0 && $val['etime'] < $time){
                        $discount['data'][$key]['status'] = 2;
                    }
                }
                $cc_map = array('uid'=>$this->mid,'type'=>5,'sid'=>$data['mhm_id'],'coupon_type'=>0);
                $courseCard = model('Coupon')->getUserCouponList($cc_map,0);
                foreach ($courseCard['data'] as $key => $val) {
                    $courseCard['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
                    $time = time();
                    if($val['status'] == 0 && $val['etime'] < $time){
                        $courseCard['data'][$key]['status'] = 2;
                    }
                    if($val['video_type'] == 3){
                        $courseCard['data'][$key]['video_name'] = D('Album')->getAlbumTitleById($val['video_id']);
                        $courseCard['data'][$key]['vtype'] = '班级';
                    }else{
                        $courseCard['data'][$key]['video_name'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                        if($val['video_type'] == 1){
                            $courseCard['data'][$key]['vtype'] = '点播';
                        }else{
                            $courseCard['data'][$key]['vtype'] = '直播';
                        }
                    }
                }
                $this->assign('discount', $discount['data']);
                $this->assign('videoCoupon', $videoCoupon['data']);
                $this->assign('courseCard', $courseCard['data']);
            }
        }else{
            $new_coupon['school_title'] = model('School')->where(array('id'=>$new_coupon['sid']))->getField('title');
            $new_coupon['stime'] = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid))->getField('stime');
            $new_coupon['etime'] = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid))->getField('etime');
            $this->assign('coupon',$new_coupon);
        }
        if($this->is_wap && strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')){
            $this->assign('is_wx',true);
        }

        //H5是有优惠券
        if($_GET['cid']) {
            //获取优惠券信息
            $uCoupon = M('coupon_user')->where(array('id'=>$_GET['cid'],'is_del'=>0,'etime'=>['gt',time()]))->find();
            if($uCoupon['status'] == 0){
                $coupon = model('Coupon')->getCouponInfoById($uCoupon['cid']);
            }
            switch ($coupon['type']) {
                case "1":
                    $minus_price = $coupon['price'];
                    break;
                case "2":
                    $minus_price = $data['moner_data']['price'] * $coupon['discount'] / 10;
                default:
                    break;
            }
        }

        $this->assign($data);
        $this->assign('pay_gspay',$pay_gspay);
        $this->assign('pay_video_mount_school',$pay_video_mount_school);
        $this->assign('pay_video_type',$pay_video_type);
        $this->assign('minus_price',$minus_price);
        $this->disPlay();
    }

    /*
     * @name 卡券选择页面
     */
    public function coupon(){
        $id = intval($_GET['id']);
        $mhm_id = D('ZyVideo')->where('id='.$id)->getField('mhm_id');
        $vc_map = array('uid'=>$this->mid,'type'=>1,'sid'=>$mhm_id,'coupon_type'=>0);
        $videoCoupon = model('Coupon')->getUserCouponList($vc_map,0);
        foreach ($videoCoupon['data'] as $key => $val) {
            $videoCoupon['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $videoCoupon['data'][$key]['price'] = floor($val['price']);
            $videoCoupon['data'][$key]['maxprice'] = floor($val['maxprice']);
            $videoCoupon['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $videoCoupon['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $vd_map = array('uid'=>$this->mid,'type'=>2,'sid'=>$mhm_id,'coupon_type'=>0);
        $discount = model('Coupon')->getUserCouponList($vd_map,0);
        foreach ($discount['data'] as $key => $val) {
            $discount['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $time = time();
            if($val['status'] == 0 && $val['etime'] < $time){
                $discount['data'][$key]['status'] = 2;
            }
            $discount['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $discount['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
        }
        $cc_map = array('uid'=>$this->mid,'type'=>5,'sid'=>$mhm_id,'coupon_type'=>0);
        $courseCard = model('Coupon')->getUserCouponList($cc_map,0);
        foreach ($courseCard['data'] as $key => $val) {
            $courseCard['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $time = time();
            if($val['status'] == 0 && $val['etime'] < $time){
                $courseCard['data'][$key]['status'] = 2;
            }
            $courseCard['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
            $courseCard['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
            if($val['video_type'] == 3){
                $courseCard['data'][$key]['video_name'] = D('Album')->getAlbumTitleById($val['video_id']);
                $courseCard['data'][$key]['vtype'] = '班级';
            }else{
                $courseCard['data'][$key]['video_name'] = D('ZyVideo')->getVideoTitleById($val['video_id']);
                if($val['video_type'] == 1){
                    $courseCard['data'][$key]['vtype'] = '点播';
                }else{
                    $courseCard['data'][$key]['vtype'] = '直播';
                }
            }
        }
        //课程价格
        $data = D ('ZyVideo')->where('id='.$id)->field("v_price,t_price,type")->find ();
        $moner_data = getPrice($data, $this->mid, false, true,$data['type']);
        //订单类型
        if($data['type'] == 1){
            $data['vtype'] = 'zy_video';
        }else{
            $data['vtype'] = 'zy_live';
        }

        $this->assign('videoCoupon', $videoCoupon['data']);
        $this->assign('discount', $discount['data']);
        $this->assign('courseCard', $courseCard['data']);
        $this->assign('data', $data);
        $this->assign('price', $moner_data);
        $this->disPlay();
    }
    /*
     * @name 获取卡券信息
     */
    public function getCouponInfo(){
        $id = intval($_POST['id']);
        $coupon = model('Coupon')->getCouponInfoById($id);
        if($coupon){
            $coupon['cuid'] = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['coupon_id']))->getField('id');
            echo json_encode($coupon);exit;
        }else{
            return false;
        }
    }

    /*
     * @name 获取优惠券信息
     */
    public function getExchangeCard(){
        $code = $_POST['code'];
        $sid = $_POST['mhm_id'];
        $map['code'] = $code;
        $map['sid'] = $sid;
        $map['coupon_type'] = 1 ;
        //$map['video_id'] = intval($_POST['vid']);
        $coupon = model('Coupon')->where($map)->find();
        if($coupon){
            if($coupon['type'] == 1 && $coupon['maxprice'] > $_POST['price']){
                $this->mzError('该实体卡不满足使用条件，请更换');
            }
            $couponUserId = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['id'],'status'=>0,'is_del'=>0,'etime'=>['gt',time()]))->getField('id');
            if(!$couponUserId){
                $res = model('Coupon')->grantCouponByCode($code);
            }else{
                $res = 'true';
            }
            if($res == 'true'){
                $coupon['coupon_id'] = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['id']))->getField('id');
                $coupon['status'] = 1;
                echo json_encode($coupon);exit;
            }else{
                $this->mzError('使用实体卡失败,请重新尝试');
            }
        }else{
            $this->mzError('该实体卡无法使用');
        }
    }
    /*
     * @name 取消使用优惠券
     */
    public function cancelExchangeCard(){
        $map['code'] = $_POST['code'];
        $map['sid'] = $_POST['mhm_id'];
        $data['status'] = 1;
        $result = model('Coupon')->where($map)->save($data);
        if($result){
            $coupon_id = model('Coupon')->where($map)->getField('id');
            $res = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->delete();
        }
        if($res){
            $this->mzSuccess('取消使用成功');
        }else{
            $this->mzError('取消使用失败,请重新尝试');
        }
    }

    /**
     * @name 检查使用优惠券
     */
    public function checkCoupon(){
        $coupon_id = intval($_POST['coupon_id']);
        $price = floatval($_POST['price']);
        if(session('get_preferential_operation') >= time()){
            $this->mzError('请勿频繁操作');
        }

        if($coupon_id && $price){
            //检测优惠券是否可以使用
            $uCoupon = M('coupon_user')->where(array('id'=>$coupon_id,'is_del'=>0,'etime'=>['gt',time()]))->find();
            if($uCoupon['status'] == 2){
                $coupon = model('Coupon')->getCouponInfoById($uCoupon['cid']);
            }else{
                $coupon = model('Coupon')->canUse($coupon_id,$this->mid);
            }

            if(!$coupon){
                $this->mzError('该卡券已经无法使用');
            }
            $this->coupon = $coupon;
            //优惠券类型是否符合
            if(!in_array($coupon['type'],[1,2])){
                $this->mzError('该卡券不能用于购买课程');
            }
            switch($coupon['type']){
                case "1":
                    //价格低于门槛价 || 至少支付0.01
                    /*if($coupon['maxprice'] != '0.00' && $price <= $coupon['maxprice']){
                        $this->mzError('该优惠券需要满'.$coupon['maxprice'].'元才能使用');
                    }*/
                    if($price < $coupon['maxprice']){
                        $this->mzError('该优惠券需要满'.$coupon['maxprice'].'元才能使用');
                    }
                    if($price < $coupon['price']){
                        $this->mzError('所支付的金额不满足使用优惠券条件');
                    }
                    $after_preferential = round($price-$coupon['price'],2);
                    $minus_price = $coupon['price'];
                    break;
                case "2":
                    $after_preferential = $price * $coupon['discount']/10;
                    $minus_price = $price - $after_preferential;
                default:
                    break;
            }
            $new_price['minus_price'] = $minus_price;
            $new_price['after_price'] = $after_preferential;

            //使用优惠券
            //if(M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1)){
            session('get_preferential_operation', time() + 3);
            $this->ajaxReturn($new_price,'获取卡券成功',1);
            //}
        }else{
            $this->mzError('使用卡券失败,请重新尝试');
        }
    }


    /**
     * 检查课程是否需要购买等
     */
    public function checkPay(){
        $this->mid = $this->mid ? $this->mid : intval($_POST['mid']);
        if(!$this->mid){
            $this->mzError('请先登录');
        }

        $check_type = t($_POST['check_type']);

        $vid = intval($_POST['vid']);

        if($check_type == 'zy_video') {
            if(session('purchase_pay_video_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
            $type_title = "课程";
        } else if ($check_type == 'zy_album') {
            if(session('purchase_pay_album_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
            $type_title = "班级";
        } else if ($check_type == 'zy_live') {
            if(session('purchase_pay_live_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
            $type_title = "直播";
        }
        if(!$vid){
            $this->mzError("请选择要购买的$type_title");
        }
        $uid = $this->mid;
        $money = floatval($_POST['money']);

        //检查是否可以购买
        if($check_type == 'zy_video') {
            $data = D('ZyVideo')->where(array('id' => $vid))->find();
            $old_price = getPrice($data, $this->mid, true, true);
            if (bccomp($money, $old_price['price'])) {
                $this->mzError("请勿篡改金额");
            }

            $is_buy = D('ZyService')->checkVideoAccess($uid, $vid);
            session('purchase_pay_video_operation', time() + 10);
        } else if ($check_type == 'zy_album') {
            $price = D("Album")->getAlbumOneInfoById($vid,'price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
            session('purchase_pay_album_operation', time() + 10);
        } else if ($check_type == 'zy_live') {
            $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
            session('purchase_pay_live_operation', time() + 10);
        }
        if($is_buy){
            $this->mzError("该{$type_title}不需要您购买！");
        }
        //检查是否加入订单
        // if(!$type){
        //     if($check_type == 'zy_video') {
        //         $pay_status = M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $vid,'is_del'=>0))->getField('pay_status');
        //     } else if ($check_type == 'zy_album') {
        //         $pay_status = M('zy_order_album')->where(array('uid' => intval($this->mid), 'album_id' => $vid,'is_del'=>0))->getField('pay_status');
        //     } else if ($check_type == 'zy_live') {
        //         $pay_status = M('zy_order_live')->where(array('uid' => intval($this->mid), 'live_id' => $vid,'is_del'=>0))->getField('pay_status');
        //     }
        // }
        // if ($pay_status == 1) {
        //     $this->mzError("课程订单未支付，请先到个人中心—订单操作！");
        // }else{
        //     $data['vid']  = $vid;
        //     $data['type'] = $check_type;
        //     $this->mzSuccess('');
        // }

        $data['vid']  = $vid;
        $data['type'] = $check_type;
        $this->mzSuccess('');
    }

    /**
     * 检查课程购买
     */
    public function checkPayOperat(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        $check_type = t($_POST['check_type']);

        //必须要先登陆才能进行操作
        $this->mid = $this->mid ? $this->mid : intval($_POST['mid']);
        if(!$this->mid){
            $this->mzError("请先登录再进行购买");
        }

        if($check_type == 'zy_video') {
            if(session('purchase_course_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
        }else if($check_type == 'zy_album') {
            if( session('purchase_album_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
        }else if($check_type == 'zy_live') {
            if( session('purchase_live_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
        }else if($check_type == 'zy_teacher') {
            if( session('purchase_teacher_operation') >= time()){
                $this->mzError('生成订单中，请稍后');
            }
        }

        $vid = intval($_POST['vid']);
        if(!$vid){
            $this->mzError("参数错误");
        }

        $pay_list = array('alipay','unionpay','wxpay','lcnpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->mzError("支付方式错误");
        }

        $money = floatval($_POST['money']);
        if($money <= 0){
            $this->mzError("请选择或填写购买金额");
        }
        //$rechange_base = getAppConfig('rechange_basenum');
        /*if($rechange_base > 0 && $money % $rechange_base != 0){
            if($rechange_base == 1){
                $this->mzError("购买金额必须为整数");
            }else{
                $this->mzError("购买金额必须为{$rechange_base}的倍数");
            }
        }*/

        $coupon_id = intval($_POST['coupon_id']);
        $dis_type = intval($_POST['discount_type']);

        $pay_video_mount_school = explode('H',t($_POST['pay_video_mount_school']))[0];
        if($check_type == 'zy_video') {
            $data = D('ZyVideo')->where(array('id' => $vid))->find();
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else{
                $old_price = getPrice($data, $this->mid, true, true);
                if (bccomp($money, $old_price['price'])) {
                    $this->mzError("请勿篡改金额");
                }
            }
            $pay_status = M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $vid,'is_del'=>0))->getField('pay_status');

            /* if ($pay_status == 1) {
                 $this->mzError("课程订单未支付，请先到个人中心—订单操作！");
             }else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此课程！");
            } else if ($pay_status == 1 || $pay_status == 2 || $pay_status == 5 || $pay_status == 7 || !$pay_status) {
                $cid = M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $vid))->getField('coupon_id');
                if($cid !== $coupon_id){
                    model('Coupon')->cancelExchangeCard($cid);
                }
                $i = D('ZyService')->buyOnlineVideo(intval($this->mid), $vid,$ext_data,$pay_video_mount_school);
            } else if ($pay_status == 4){
                $this->mzError("该课程正在申请退款");
            }
            if ($i === true) {
                // 记录购买的课程的ID
                session('purchase_course_operation', time() + 15);
                $this->mzSuccess('成功');
            } else if ($i === 1) {
                $this->mzError('该课程你不需要购买');
            } else if ($i === 2) {
                $this->mzError('找不到课程');
            } else if ($i === 4) {
                $this->ajaxReturn(null, "创建订单失败", 9);
            } else if ($i === 5) {
                $this->mzError('平台分成比例不存在');
            } else if ($i === 6) {
                $this->mzError('课程不属于机构');
            } else if ($i === 7) {
                $this->mzError('该课程机构不存在');
//            } else if ($i === 8) {
//                $this->mzError('课程下讲师不存在');
//            } else if ($i === 9) {
//                $this->mzError('机构与教师分成比例不存在');
            } else if ($i === 10) {
                $this->mzError('机构分成比例不存在');//机构与挂载分成比例不存在
            } else if ($i === 11) {
                $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
            } else if ($i === 12) {
                $this->mzError('课程有效期不存在');//课程订单有效期不存在
            } else if ($i === 13) {
                $this->ajaxReturn(null, "创建订单失败!", 9);
            }
        } else if ($check_type == 'zy_album') {
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else {
                $price = D("Album")->getAlbumOneInfoById($vid,'price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }
            $pay_status = M('zy_order_album')->where(array('uid' => intval($this->mid), 'album_id' => $vid))->getField('pay_status');

            /*if ($pay_status == 1) {
                $this->mzError("班级订单未支付，请先到个人中心—订单操作！");
            } else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此班级！");
            } else if ($pay_status == 1 || $pay_status == 2 || $pay_status == 5 || $pay_status == 7 || !$pay_status) {
                $cid = M('zy_order_album')->where(array('uid' => intval($this->mid), 'album_id' => $vid))->getField('coupon_id');
                if($cid !== $coupon_id){
                    model('Coupon')->cancelExchangeCard($cid);
                }
                $i = D('ZyService')->buyOnlineAlbum(intval($this->mid), $vid, $ext_data);
            } else if ($pay_status == 4){
                $this->mzError("该班级正在申请退款");
            }

            if ($i === true) {
                session('purchase_album_operation', time() + 15);
                $this->mzSuccess('成功');
            } else
//            if ($i === 1) {
//                $this->mzError('该班级你不需要购买');
//            } else
                if ($i === 2) {
                    $this->mzError('找不到班级');
                } else if ($i === 3) {
                    $this->mzError('班级下没有课程');
                } else if ($i === 4) {
                    $this->ajaxReturn(null, "创建订单失败", 9);
                } else if ($i === 5) {
                    $this->mzError('平台分成比例不存在');//平台与机构分成比例不存在
                } else if ($i === 6) {
                    $this->mzError('班级不属于机构');
                } else if ($i === 7) {
                    $this->mzError('该班级机构不存在');//班级所绑定的机构管理员不存在
                } else if ($i === 8) {
                    $this->mzError('该班级中包含有正在退款的课程/直播课程');
                } else if ($i === 9) {
                    $this->mzError('班级中包含有与班级的机构不一致的课程');
//            } else if ($i === 10) {
//                $this->mzError('机构与教师分成不存在');
                } else if ($i === 11) {
                    $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
                } else if ($i === 12) {
                    $this->ajaxReturn(null, "创建订单失败!", 9);
                }
        } else if ($check_type == 'zy_live') {
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else {
                $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }

            $pay_status = M('zy_order_live')->where(array('uid' => intval($this->mid), 'live_id' => $vid,'is_del'=>0))->getField('pay_status');

            /*if ($pay_status == 1) {
                $this->mzError("直播课程订单未支付，请先到个人中心—订单操作！！");
            } else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此直播课程！");
            } else if ($pay_status == 1 || $pay_status == 2 || $pay_status == 5 || $pay_status == 7 || !$pay_status) {
                $cid = M('zy_order_live')->where(array('uid' => intval($this->mid), 'live_id' => $vid))->getField('coupon_id');
                if($cid !== $coupon_id){
                    model('Coupon')->cancelExchangeCard($cid);
                }
                $i = D('ZyService')->buyOnlineLive(intval($this->mid), $vid,$ext_data,$pay_video_mount_school);
            } else if ($pay_status == 4){
                $this->mzError("该直播课程正在申请退款");
            }

            if ($i === true) {
                session('purchase_live_operation', time() + 15);
                $this->mzSuccess('成功');
            } else
                if ($i === 1) {
                    $this->mzError('该直播课程你不需要购买');
                } else if ($i === 2) {
                    $this->mzError('找不到直播课堂');
                }
//                else if ($i === 3) {
//                    $this->mzError('直播课程下没有课时');
//                }
                else if ($i === 4) {
                    $this->ajaxReturn(null, "创建订单失败", 9);
                } else if ($i === 5) {
                    $this->mzError('平台分成自营比例不存在');//平台与机构分成自营比例不存在
                } else if ($i === 6) {
                    $this->mzError('直播课程不属于机构');
                } else if ($i === 7) {
                    $this->mzError('该直播课程机构不存在');//直播课程所绑定的机构管理员不存在
                } else if ($i === 8) {
                    $this->mzError('直播课程中包含有 没有相关讲师用户的课程');
//                } else if ($i === 9) {
//                    $this->mzError('机构与教师分成不存在');
                } else if ($i === 10) {
                    $this->mzError('机构分成比例不存在');//机构数据里的 机构与挂载机构分成不存在
                } else if ($i === 11) {
                    $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
                } else if ($i === 12) {
                    $this->ajaxReturn(null, "创建订单失败!", 9);
                }
        }elseif($check_type == 'zy_teacher'){
            $data = M('zy_teacher_course')->where(array('course_id' => $vid))->find();
            if($dis_type){
                $ext_data = [
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else{
                //线下课价格
                $data['t_price'] = $data['course_price'];
                $old_price['price'] = getPrice($data,$this->mid);
                if (bccomp($money, $old_price['price'])) {
                    $this->mzError("请勿篡改金额");
                }
            }

            $pay_status = M('zy_order_teacher')->where(array('uid' => intval($this->mid), 'video_id' => $vid,'is_del'=>0))->getField('pay_status');
            /*if ($pay_status == 1) {
                $this->mzError("课程订单未支付，请先到个人中心—订单操作！");
            }else */
            if ($pay_status == 3) {
                $this->mzError("已购买此课程！");
            } else if ($pay_status == 1 || $pay_status == 2 || !$pay_status) {
                $i = D('ZyService')->buyOnlineTeacher(intval($this->mid), $vid,$ext_data);
            }
            if ($i === true) {
                // 记录购买的课程的ID
                session('purchase_teacher_operation', time() + 15);
                $this->mzSuccess('成功');
            } else if ($i === 1) {
                $this->mzError('该课程你不需要购买');
            } else if ($i === 2) {
                $this->mzError('找不到课程');
            } else if ($i === 4) {
                $this->ajaxReturn(null, "创建订单失败", 9);
            } else if ($i === 5) {
                $this->mzError('平台分成比例不存在');
            } else if ($i === 6) {
                $this->mzError('课程不属于机构');
            } else if ($i === 7) {
                $this->mzError('该课程机构不存在');
//            } else if ($i === 8) {
//                $this->mzError('课程下讲师不存在');
//            } else if ($i === 9) {
//                $this->mzError('机构与教师分成比例不存在');
            } else if ($i === 10) {
                $this->mzError('机构分成比例不存在');//机构与挂载分成比例不存在
            } else if ($i === 11) {
                $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
            } else if ($i === 12) {
                $this->mzError('课程有效期不存在');//课程订单有效期不存在
            } else if ($i === 13) {
                $this->ajaxReturn(null, "创建订单失败!", 9);
            }
        } else {
            $this->mzError('请勿篡改购买类型，我们会发现哦');
        }
    }

    /**
     * 加入看单 （订单）
     */
    public function add_order()
    {

        $video_id = intval($_POST['vid']);
        $vtype = t($_POST['vtype']);
        $coupon_id = intval($_POST['coupon_id']);
        if (!$this->mid) {
            $this->mzError("请先登录");
        }
        if ($vtype == 'zy_video') {
            //取得课程
            $video = M('zy_video')->where(array(
                'id' => $video_id,
                'is_del' => 0,
                'is_activity' => ['in','1,5,6,7'],
                'type' => 1,
                'listingtime' => array('lt', time()),
            ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,is_charge,
            endtime,starttime,limit_discount,term")->find();
            //找不到课程
            if (!$video) {
                $this->mzError("找不到该课程");
            }

            $is_buy = D('ZyOrderCourse','classroom')->isBuyVideo($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该课程");
            }

            //查询教师用户uid
            $teacher_uid = M('zy_teacher')->where(array('id' => $video['teacher_id']))->getField('uid');
            $teacher_uid = M('user')->where(array('uid' => $teacher_uid))->getField('uid');


            //取得价格
            $prices = getPrice($video, $this->mid, false, true);
            $prices['price'] = floatval($prices['price']);
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($video['uid']) || $tid == $video['teacher_id'] || $video['is_charge'] == 1)
            {
                $prices['price'] = 0;
            }

            if(!$coupon_id){
                if ($prices['price'] != 0  && $video['is_charge'] != 1) {
                    $this->mzError("该课程为收费课程");
                }
                $prices['now_price'] = $prices['price'];
            }else{
                $prices['now_price'] = 0;
            }

            //购买用户机构id
            $mhuid = M('user')->where('uid = '.$this->mid)->getField('mhm_id');
            $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

            $data = array(
                'uid' => $this->mid,
                'muid' => $teacher_uid,
                'video_id' => $video['id'],
                'old_price' => $prices['price'],//10
                'discount' => round($prices['oriPrice'] - $prices['price'], 2),
                'discount_type' => 3,
                'price' => $prices['now_price'],
                'coupon_id' => $coupon_id,
                'order_album_id' => 0,
                'learn_status' => 0,
                'ctime' => time(),
                'order_type' => 0,
                'is_del' => 0,
                'pay_status' => 3,
                'term' => $video['term'],
                'time_limit' => time() + 129600 * floatval($video['term']),
                'mhm_id' => $video['mhm_id'],
                'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
//                'coupon_id' => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
            );
            $order_id = M('zy_order_course')->where(array('uid'=>$this->mid,'video_id'=>$video['id']))->getField('id');
            if($order_id){
                $id = M('zy_order_course')->where(array('uid'=>$this->mid,'video_id'=>$video['id']))->save($data);
            }else{
                $id = M('zy_order_course')->add($data);
            }
            if ($id) {
                M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        } else if ($vtype == 'zy_live') {
            //取得直播课程
            $live_info = D('ZyVideo')->where(array(
                'id'          => $video_id,
                'is_del'      => 0,
                'is_activity' => ['in','1,5,6,7'],
                'type'        => 2,
                'listingtime' => array('lt', time()),
                'uctime' => array('gt', time()),
            ))->field("id,video_title,mhm_id,t_price,v_price,is_charge,teacher_id,uid,
            listingtime,uctime,live_type")->find();

            //找不到直播课程
            if (!$live_info){
                $this->mzError("找不到该直播课程");
            }

            $is_buy = D('ZyOrderLive','classroom')->isBuyLive($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该直播课程");
            }

            $mzprice = getPrice($live_info, $this->mid, true, true,2);

            if(!$coupon_id) {
                if ($mzprice['price'] != 0) {
                    $this->mzError("该直播课程为收费课程");
                }
                $live_info['now_price'] = $live_info['t_price'];
            }else{
                $live_info['now_price'] = 0;
            }

            //无过期非法信息则生成状态为已支付的订单数据
            $data = array(
                'uid'           => $this->mid,
                'live_id'       => $video_id,
                'old_price'     => $live_info['t_price'],
                'discount'      => round($live_info['v_price'] - $live_info['t_price'],2),
                'discount_type' => 3,
                'price'         => $mzprice['price'],
                'order_album_id'=> 0,
                'learn_status'  => 0,
                'ctime'         => time(),
                'is_del'        => 0,
                'pay_status'    => 3,
                'mhm_id'        => $live_info['mhm_id'],
                'coupon_id'     => $coupon_id,
                'rel_id'        => 0,
            );
            $order_id = D('ZyOrderLive')->where(array('uid'=>$this->mid,'live_id'=>$video_id))->getField('id');
            if($order_id){
                $id = D('ZyOrderLive')->where(array('uid'=>$this->mid,'live_id'=>$video_id))->save($data);
            }else{
                $id = D('ZyOrderLive')->add($data);
            }
            if ($id) {
                M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');

                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        }else if($vtype == 'zy_album'){
            $album = D("Album")->getAlbumOneInfoById($video_id,'id,price,mhm_id,album_title');
            $album['price'] = getAlbumPrice($album['id'],$this->mid)['price'];

            //找不到直播课程
            if (!$album){
                $this->mzError("找不到该班级课程");
            }

            $is_buy = D('ZyOrderAlbum')->isBuyAlbum($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该直播课程");
            }
            if(!$coupon_id){
                if($album['price'] != 0){
                    $this->mzError("该班级课程为收费课程");
                }
                $album['now_price'] = $album['price'];
            }else{
                $album['now_price'] = 0;
            }
            $oPrice = 0.00;
            foreach ($album as $key => $video) {
                $oPrice += $video['t_price'];
            }

            //无过期非法信息则生成状态为已支付的订单数据
            $data = array(
                'uid'           => $this->mid,
                'album_id'      => $video_id,
                'old_price'     => $album['price'],
                'discount'      => round($oPrice-$album['price'],2),
                'discount_type' => 3,
                'price'         => $album['now_price'],
                'learn_status'  => 0,
                'ctime'         => time(),
                'is_del'        => 0,
                'pay_status'    => 3,
                'mhm_id'        => $album['mhm_id'],
                'coupon_id'     => $coupon_id,
            );
            $order_id = D('ZyOrderAlbum')->where(array('uid'=>$this->mid,'album_id'=>$video_id))->getField('id');
            if($order_id){
                $id = D('ZyOrderAlbum')->where(array('uid'=>$this->mid,'album_id'=>$video_id))->save($data);
            }else{
                $id = D('ZyOrderAlbum')->add($data);
            }
            if ($id) {
                //批量添加班级下课程订单
                $video_ids      = trim(D("Album",'classroom')->getVideoId($video_id), ',');
                $v_map['id']        = array('in', array($video_ids));
                $v_map["is_del"]    = 0;
                $album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
                    ->select();

                $insert_live_value = "";
                $insert_course_value = "";
                $time = time();
                $pay_data =['pay_status'=>3,'order_album_id'=>$video_id,'rel_id'=>$data['rel_id'],'ptime'=>$time];
                foreach ($album_info as $key => $video) {
                    //如果已经购买 则销毁，已有订单则改为支付
                    if($video['type'] == 1) {
                        $video_pay_status = D("ZyOrderCourse",'classroom')->where(array('uid'=>$this->mid, 'video_id'=>$video['id']))->field('id,pay_status')->find();
                        if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                            unset($video);
                        }elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 5){
                            D("ZyOrderCourse",'classroom')->where(array('uid'=>$this->mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                            unset($video);
                        }
                    }
                    if($video['type'] == 2) {
                        $video_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$this->mid, 'live_id'=>$video['id']))->field('id,pay_status')->find();
                        if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                            unset($video);
                        }elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 5){
                            D("ZyOrderLive",'classroom')->where(array('uid'=>$this->mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                            unset($video);
                        }
                    }

                    $album_info[$key] = $video;
                }
                $album_info = array_filter($album_info);

                $order_mhm_id = model('User')->where('uid='.$this->mid)->getField('mhm_id');
                foreach ($album_info as $key => $video) {
                    if($video['type'] == 2){
                        $insert_live_value .= "('" . $this->mid . "','" . $video['id'] . "','" . $video['t_price'] . "','0.00','0','" . $video['t_price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','" .$order_mhm_id."'),";
                    }else{
                        $insert_course_value .= "('" . $this->mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','".$order_mhm_id."'),";
                    }
                }
                if($insert_live_value){
                    $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_live_value, ',');
                    M('zy_order_live')->execute($live_order_sql)? true : false;
                }
                if($insert_course_value){
                    $course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_course_value, ',');
                    M('zy_order_course')->execute($course_order_sql)? true : false;
                }

                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                M('album')->where(array('id' => $video_id))->setInc('order_count');

                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        }else if($vtype == 'zy_teacher'){
            //取得课程
            $video = M('zy_teacher_course')->where(array(
                'course_id' => $video_id,
                'is_del' => 0,
                'is_activity' => 1,
                'uctime' => array('gt', time()),
            ))->find();
            //找不到课程
            if (!$video) {
                $this->mzError("找不到该课程");
            }

            $is_buy = D('ZyOrder','classroom')->isBuyLineClass($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该课程");
            }

            //查询教师用户uid
            $teacher_uid = M('zy_teacher')->where(array('id' => $video['teacher_id']))->getField('uid');
            $teacher_uid = M('user')->where(array('uid' => $teacher_uid))->getField('uid');
            if($teacher_uid == $this->mid){
                $this->mzError("该课程不需要您购买");
            }
            //取得价格
            $video['t_price'] = $video['course_price'];
            $prices = getPrice($video, $this->mid, true, true);
            if ($prices['price'] != 0  && $video['is_charge'] != 1) {
                $this->mzError("该课程为收费课程");
            }

            //购买用户机构id
            $mhuid = M('user')->where('uid = '.$this->mid)->getField('mhm_id');
            $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

            //生成状态为支付的订单数据
            $order = M('zy_order_teacher');
            $data = array(
                'uid'           => $this->mid,
                'video_id'      => $video['course_id'],
                'price'         => $prices['price'],
                'ctime'         => time(),
                'is_del'        => 0,
                'pay_status'    => 3,
                'time_limit'    => time() + 129600 * floatval($video['term']),
                'mhm_id'        => $video['mhm_id'],//课程机构id
                'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
                'learn_status'  => 0,
                'tid'           => $video['teacher_id'],
            );
            $map['uid']         = $this->mid;
            $map['video_id']    = $video['course_id'];
            $order_id = $order->where($map)->getField('id');
            if($order_id){
                $id = $order->where($map)->save($data);
            }else{
                $id = $order->add($data);
            }
            if ($id) {
                M('zy_teacher_course')->where(array('course_id' => $video['course_id']))->setInc('course_order_count');
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        }
    }

    /**
     * 购买课程（点播、直播、班级）
     */
    public function payLibrary(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);
        $check_type = t($_POST['check_type']);

        //必须要先登陆才能进行操作
        $this->mid = $this->mid ? $this->mid : intval($_POST['mid']);
        if(!$this->mid){
            $this->error('请先登录再进行购买');
        }

        $vid = intval($_POST['vid']);
        if(!$vid){
            $this->error("参数错误");
        }

        $pay_list = array('alipay','unionpay','wxpay','lcnpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->error('支付方式错误');
        }

        $money = floatval($_POST['money']);
        $title = t($_POST['title']);
        $coupon_id = intval($_POST['coupon_id']);

        if($money <= 0){
            $this->error('请选择或填写购买金额');
        }
        $rechange_base = getAppConfig('rechange_basenum');
        /*if($rechange_base>0 && $money%$rechange_base != 0){
            if($rechange_base == 1){
                $this->error('购买金额必须为整数');
            }else{
                $this->error("购买金额必须为{$rechange_base}的倍数");
            }
        }*/

        if($check_type == 'zy_video'){
            if(!$coupon_id){
                $data = D('ZyVideo')->where(array('id' => $vid))->find();

                $old_price = getPrice($data, $this->mid, true, true);
                if (bccomp($money, $old_price['price'])) {
                    $this->mzError("请勿篡改金额");
                }
            }
        } else if ($check_type == 'zy_album') {
            $price = D("Album")->getAlbumOneInfoById($vid, 'price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
        } else if ($check_type == 'zy_live') {
            if (!$coupon_id) {
                $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }
        } else if ($check_type == 'zy_teacher') {
            $data = M('zy_teacher_course')->where(array('course_id' => $vid))->find();
            //线下课价格
            $data['t_price'] = $data['course_price'];
            $old_price['price'] = getPrice($data,$this->mid);
            if (bccomp($money, $old_price['price'])) {
                $this->mzError("请勿篡改金额");
            }
        }

        $re = D('ZyRecharge');

        if($_POST['pay'] == 'lcnpay') {
            //余额支付前校验余额
            $res = D('ZyLearnc')->isSufficient($this->mid, $money);
            if (!$res) {
                echo json_encode(['status' => 0, 'info' => "您的余额不够此次支付金额", 'data' => $this->mid]);
                exit;
            }
        }

        $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);

        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => 1,
            'money'    => $money,
            'note'     => "{$this->site['site_keyword']}在线教育-购买{$title}",
            'pay_type' => $_POST['pay'],
            'pay_pass_num'=>$pay_pass_num,
        ));

        if(!$id) $this->error("操作异常");

        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'vid'          => intval($_POST['vid']),
                'vtype'        => $check_type,
                'coupon_id'    => $coupon_id ? : 0,
                'out_trade_no' => $pay_pass_num,
                'total_fee'    => $money,
                'subject'      => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'vid'       => intval($_POST['vid']),
                'vtype'     => $check_type,
                'coupon_id' => $coupon_id ? : 0,
                'id'        => $id,
                'money'     => $money,
                'subject'   => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'wxpay'){
            $res = $this->wxpay(array(
                'vid'           => intval($_POST['vid']),
                'vtype'         => $check_type,
                'coupon_id'     => $coupon_id ? : 0,
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money * 100,//单位：分
                'subject'       => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
            if($res){
                if($this->is_pc){
                    $this->assign('url',$res);
                    $html = $this->fetch('wxpay');
                    $data = array('status'=>1,'data'=>['html'=>$html,'pay_pass_num'=>$pay_pass_num]);
                    echo json_encode($data);
                    exit;
                }else{
                    if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                        $data = array('status'=>1,'data'=>['html'=>$res,'pay_pass_num'=>$pay_pass_num]);
                        echo json_encode($data);
                    }else {
                        $redirect_url = 'http://' . strip_tags($_SERVER['HTTP_HOST']) . "/pay/{$_POST['vst']}/" . sunjiami(rand(100, 999), 'wx_pay') . "/{$pay_pass_num}.html";
                        $data = array('status' => 1, 'data' => ['html' => $res . "&redirect_url={$redirect_url}", 'pay_pass_num' => $pay_pass_num]);
                        echo json_encode($data);
                        exit;
                    }
                }
            }else{
                $data = array('status'=>0,'data'=>"微信支付异常，请稍后再试");
                echo json_encode($data);
                exit;
            }
        }elseif($_POST['pay'] == 'lcnpay'){
            $res = $this->lcnpay(array(
                'vid'           => intval($_POST['vid']),
                'vtype'         => $check_type,
                'coupon_id'     => $coupon_id ? : 0,
                'out_trade_no'  => $pay_pass_num,
                'total_fee'     => $money,
                'subject'       => $title,
            ));
            echo json_encode($res);exit;
        }
    }

    /**
     * 购买课程成功 修改购买支付状态以及生成分成明细、每个人分成
     */
    public function buyOperating($vid,$out_trade_no,$vtype){
        $data['ptime']      = time();
        $data['pay_status'] = 3;
        $data['rel_id']     = $out_trade_no ? $out_trade_no : 0;
        $this_mid = D('ZyRecharge')->where('pay_pass_num = '.$out_trade_no)->getField('uid');

        //修改订单支付类型并更新订单数量

        if($vtype == 'zy_video') {
            $term = M('zy_video')->where(array('id' => $vid))->getField('term');
            $data['term'] = $term ? : 0;
            $data['time_limit'] = $term ? time() + (86400 * floatval($term)) : 0;

            M('zy_order_course')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->save($data);
            $pay_status = M('zy_order_course')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->getField('pay_status');

            M('zy_video')->where(array('id' => $vid))->setInc('video_order_count');
        }elseif($vtype == 'zy_album'){
            M('zy_order_album')->where(array('uid'=>intval($this_mid),'album_id'=>$vid))->save($data);
            $pay_status = M('zy_order_album')->where(array('uid'=>intval($this_mid),'album_id'=>$vid))->getField('pay_status');

            M('album')->where(array('id' => $vid))->setInc('order_count');
            $video_ids      = trim(D("Album")->getVideoId($vid), ',');
            $a_map['id']      = array('in', array($video_ids));
            M('zy_video')->where($a_map)->setInc('video_order_count');
        }elseif($vtype == 'zy_live') {
            M('zy_order_live')->where(array('uid'=>intval($this_mid),'live_id'=>$vid))->save($data);
            $pay_status = M('zy_order_live')->where(array('uid'=>intval($this_mid),'live_id'=>$vid))->getField('pay_status');

            M('zy_video')->where(array('id' => $vid))->setInc('video_order_count');
        }elseif($vtype == 'zy_teacher') {
            M('zy_order_teacher')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->save($data);
            $pay_status = M('zy_order_teacher')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->getField('pay_status');

            M('zy_teacher_course')->where(array('course_id' => $vid))->setInc('course_order_count');
        }

        if($pay_status == 3){

            $map['uid'] = intval($this_mid);//购买用户ID

            $v_data['status'] = 1;//分成流水订单状态
            $v_data['ltime']  = time();
            if($vtype == 'zy_video'){
                $map['vid']  = intval($vid);
                $split_video = M('zy_split_course')->where($map) ->save($v_data);
            }elseif($vtype == 'zy_album'){
                $map['aid']  = intval($vid);
                $split_video = M('zy_split_album')->where($map) ->save($v_data);
            }elseif($vtype == 'zy_live'){
                $map['lid']  = intval($vid);
                $split_video = M('zy_split_live')->where($map) ->save($v_data);
            }elseif($vtype == 'zy_teacher'){
                $map['vid']  = intval($vid);
                $split_video = M('zy_split_teacher')->where($map) ->save($v_data);
            }
            $map['status'] = 1;

            if($split_video){
                $s['uid']=$this_mid;
                $split = D('ZySplit','classroom');

                if($vtype == 'zy_video'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $split->addVideoFlows($map, 5, 'zy_video_order');

                    $ouschool_buyer = model('User')->where(['uid'=>$this_mid])->getField('ouschool_buyer_num');
                    if($ouschool_buyer){
                        model('User')->where(['uid'=>$this_mid])->setDec('ouschool_buyer_num');
                    }

                    $video_order_info = M('zy_order_course')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->field('term,time_limit')->find();

                    if($video_order_info['term']) {
                        $video_order_info['time_limit'] = date('Y-m-d H:i:s',$video_order_info['time_limit']);
                        $ses_info = "，该课程的有效天数为{$video_order_info['term']}天,有效期截至：{$video_order_info['time_limit']}，请您务必在有效期内尽快学习";
                    }
                    $video_info = M('zy_video')->where(array('id' => $vid))->field('video_title,teacher_id')->find();
                    $s['title'] = "恭喜您购买课程成功";
                    $s['body'] = "恭喜您成功购买课程：{$video_info['video_title']}".$ses_info;

                    //添加积分操作
                    model('Credit')->getCreditInfo($this_mid,2);
                }elseif($vtype == 'zy_album'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $album = D("Album",'classroom')->getAlbumOneInfoById($vid,'id,price,mhm_id,album_title');
                    $video_ids      = trim(D("Album",'classroom')->getVideoId($vid), ',');
                    $v_map['id']        = array('in', array($video_ids));
                    $v_map["is_del"]    = 0;
                    $album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
                        ->select();

                    $insert_live_value = "";
                    $insert_course_value = "";
                    $time = time();
                    $pay_data =['pay_status'=>3,'order_album_id'=>$vid,'rel_id'=>$data['rel_id'],'ptime'=>$time];
                    foreach ($album_info as $key => $video) {
                        //如果已经购买 则销毁，已有订单则改为支付
                        if($video['type'] == 1) {
                            $video_pay_status = D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'video_id'=>$video['id']))->field('id,pay_status')->find();
                            if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                                unset($video);
                            }elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 5){
                                D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                                unset($video);
                            }
                        }
                        if($video['type'] == 2) {
                            $video_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'live_id'=>$video['id']))->field('id,pay_status')->find();
                            if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                                unset($video);
                            }elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 5){
                                D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                                unset($video);
                            }
                        }

                        $album_info[$key] = $video;
                    }
                    $album_info = array_filter($album_info);

                    $order_mhm_id = model('User')->where('uid='.$this_mid)->getField('mhm_id');
                    foreach ($album_info as $key => $video) {
                        if($video['type'] == 2){
                            $insert_live_value .= "('" . $this_mid . "','" . $video['id'] . "','" . $video['t_price'] . "','0.00','0','" . $video['t_price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','" .$order_mhm_id."'),";
                        }else{
                            $insert_course_value .= "('" . $this_mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','".$order_mhm_id."'),";
                        }
                    }
                    if($insert_live_value){
                        $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_live_value, ',');
                        M('zy_order_live')->execute($live_order_sql)? true : false;
                    }
                    if($insert_course_value){
                        $course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_course_value, ',');
                        M('zy_order_course')->execute($course_order_sql)? true : false;
                    }
                    $split->addVideoFlows($map, 5, 'zy_album_order');

                    $album_title = M('album')->where(array('id' => $vid))->getField('album_title');
                    $s['title'] = "恭喜您购买班级成功";
                    $s['body'] = "恭喜您成功购买班级：{$album_title}";

                    //添加积分操作
                    model('Credit')->getCreditInfo($this_mid,16);
                }elseif($vtype == 'zy_live'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $split->addVideoFlows($map, 5, 'zy_live_order');
                    $video_info = M('zy_video')->where(array('id' => $vid))->field('video_title,teacher_id')->find();
                    $s['title'] = "恭喜您购买直播课堂成功";
                    $s['body'] = "恭喜您成功购买直播课堂：{$video_info['video_title']}";

                    //添加积分操作
                    model('Credit')->getCreditInfo($this_mid,10);
                }elseif($vtype == 'zy_teacher'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $split->addVideoFlows($map, 5, 'zy_teacher_order');

                    $video_info = M('zy_teacher_course')->where(array('course_id' => $vid))->field('course_name,teacher_id')->find();
                    $s['title'] = "恭喜您购买线下课程成功";
                    $s['body'] = "恭喜您成功购买线下课程："."<a href='".U('classroom/LineClass/view',array('id'=>$vid))."' target='_blank'>{$video_info['course_name']}</a>";

                    //给老师发系统消息
                    $userInfo = model('User')->getUserInfo($this_mid);
                    $tea['uid'] = M('zy_teacher')->where(array('id'=>$video_info['teacher_id']))->getField('uid');
                    $tea['title'] = "用户已成功预约你的线下课程";
                    $tea['body'] = "用户"."<a href='".U('classroom/UserShow/index',array('uid'=>$this_mid))."' target='_blank' color='#333'>“{$userInfo['uname']}”</a>"."已成功预约你的线下课程："."<a href='".U('classroom/LineClass/view',array('id'=>$vid))."' target='_blank'>{$video_info['course_name']}</a>";
                    $tea['ctime'] = time();
                    model('Notify')->sendMessage($tea);

                    //添加积分操作
                    model('Credit')->getCreditInfo($this_mid,30);
                }
                $s['ctime'] = time();
                model('Notify')->sendMessage($s);
                return 1;//购买成功
            }else{
                return 0;//购买失败
            }
        }else{
            return 0;//购买失败
        }
    }

    /**
     * @name 阿里支付
     * @packages protected
     */
    protected function alipay($args){
        $passback_params = urlencode(sunjiami(json_encode(array('vid'=>$args['vid'],'vtype'=>$args['vtype'],'coupon_id'=>$args['coupon_id'])),"hll"));
        $notify_url = 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipay_alinu.html';//异步地址
        $return_url = 'http://'.strip_tags($_SERVER['HTTP_HOST'])."/alipay_aliru/$passback_params.html";//同步地址

        if($this->is_pc){

            //设置支付的Data信息
            $bizcontent  = array(
                "body"          => $args['subject'],//订单描述,
                "subject"       => $args['subject'],//订单名称
                "out_trade_no"  => $args['out_trade_no'],//商户网站订单系统中唯一订单号，必填
                "total_amount"  =>  "{$args['total_fee']}",//(string)$args['total_fee'],//付款金额 新版
                "product_code"  => 'FAST_INSTANT_TRADE_PAY',//销售产品码，与支付宝签约的产品码名称。 注：目前仅支持FAST_INSTANT_TRADE_PAY
                'passback_params' => $passback_params,
            );
            $alipay_type = 'pc';
        }elseif($this->is_wap){
            //设置支付的Data信息
            $bizcontent  = array(
                "body"          => $args['subject'],//订单描述,
                "subject"       => $args['subject'],//订单名称
                "out_trade_no"  => $args['out_trade_no'],//商户网站订单系统中唯一订单号，必填
                "total_amount"  =>  "{$args['total_fee']}",//(string)$args['total_fee'],//付款金额 新版
                "product_code"  => 'QUICK_WAP_WAY',//销售产品码，与支付宝签约的产品码名称。 注：目前仅支持QUICK_WAP_WAY
                'passback_params' => $passback_params,
            );
            $alipay_type = 'wap';
        }

        $response = model('AliPay')->aliPayArouse($bizcontent,$alipay_type,$notify_url,$return_url);

        echo $response;
        exit;
    }

    /**
     * @name 阿里支付回调 服务器异步通知页面路径
     * @packages public
     */
    public function alinu(){
        //获取阿里回调到服务器异步的参数
        $response = model('AliPay')->aliNotify();
        file_put_contents('logs/alipayre_success_video.txt',json_encode($response));

        //商户订单号
        $out_trade_no = t($response['out_trade_no']);
        //支付宝交易号
        $trade_no = $response['trade_no'];
        //交易状态
        $trade_status = $response['trade_status'];

        //自定义数据
        $passback_params = $response['passback_params'];
        $vid = intval($passback_params['vid']);
        $vtype = $passback_params['vtype'];
        $coupon_id = $passback_params['coupon_id'];

        $re = D('ZyRecharge');
        if ($trade_status == 'TRADE_SUCCESS'|| $trade_status == 'TRADE_FINISHED') {
            $result = $re->setNormalPaySuccess2($out_trade_no,$trade_no);

            if($result){
                $this_uid = $re->where('pay_pass_num = '.$out_trade_no)->getField('uid');
                //查询订单支付类型
                if($vtype == 'zy_video'){
                    $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>$vid))->getField('pay_status');
                }elseif($vtype == 'zy_album'){
                    $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>$vid))->getField('pay_status');
                }elseif($vtype == 'zy_live'){
                    $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>$vid))->getField('pay_status');
                }elseif($vtype == 'zy_teacher'){
                    $pay_status = M('zy_order_teacher')->where(array('uid'=>$this_uid,'video_id'=>$vid))->getField('pay_status');
                }

                if($pay_status == 3){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    echo 'success';
                }else{
                    $order_info = $this->buyOperating($vid,$out_trade_no,$vtype);
                    if($order_info == 1){
                        if($coupon_id){
                            M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                        }
                        echo 'success';
                    }else{
                        echo 'fail';
                    }
                }
            }else{
                echo 'fail';
            }
        }
        exit;
    }

    /**
     * @name 阿里支付回调 页面跳转同步通知页面路径
     * @packages public
     */
    public function aliru()
    {
        //支付宝同步通知不返回自定义参数 遂自己传参且只用此参数做回调查询通知 勿用此参数做任何逻辑处理
        $passback_params = json_decode(sunjiemi(urldecode($_GET['passback_params']),'hll'),true);

        $vid = intval($passback_params['vid']);
        $vtype = $passback_params['vtype'];

        if($vtype == 'zy_video'){
            $this->assign('jumpUrl', U('classroom/Video/view',array('id'=>$vid)));
        }elseif($vtype == 'zy_album'){
            $this->assign('jumpUrl', U('classroom/Album/view',array('id'=>$vid)));
        }elseif($vtype == 'zy_live'){
            $this->assign('jumpUrl', U('live/Index/view',array('id'=>$vid)));
        }elseif($vtype == 'zy_teacher'){
            $this->assign('jumpUrl', U('classroom/LineClass/view',array('id'=>$vid)));
        }

        //查询订单支付类型
        if($vtype == 'zy_video'){
            $pay_status = M('zy_order_course')->where(array('uid'=>intval($this->mid),'video_id'=>$vid))->getField('pay_status');
        }elseif($vtype == 'zy_album'){
            $pay_status = M('zy_order_album')->where(array('uid'=>intval($this->mid),'album_id'=>$vid))->getField('pay_status');
        }elseif($vtype == 'zy_live'){
            $pay_status = M('zy_order_live')->where(array('uid'=>intval($this->mid),'live_id'=>$vid))->getField('pay_status');
        }elseif($vtype == 'zy_teacher'){
            $pay_status = M('zy_order_teacher')->where(array('uid'=>intval($this->mid),'video_id'=>$vid))->getField('pay_status');
        }

        if($pay_status == 3) {
            $this->success('购买成功');
        }else{
            $this->error('购买失败');
        }
    }

    public function tradeRefundAndQuery(){
        if($_GET['sync_id'] !=9)exit('in_link');

        $trade_no = $_GET['tno'];
        if($_GET['type'] == 'alipay') {
            $bizcontent['refund_amount'] = $_GET['m'];
            $bizcontent['trade_no'] = $trade_no;
            $result = model('AliPay')->aliPayArouse($bizcontent, 'refund');
            dump($result);
            $_bizcontent['trade_no'] = $trade_no;
            $_bizcontent['out_request_no'] = $trade_no;
            $_refund = model('AliPay')->aliPayArouse($_bizcontent, '_refund');
            dump($_refund);
        }elseif($_GET['type'] == 'wxpay'){
            $htime = time();
            //设置支付的Data信息
            $refund = [
                'refund_amount' => 1,
                "transaction_id"=> "{$trade_no}",
//                "out_trade_no"  => "{$recharge_info['pay_pass_num']}",
                "out_refund_no" => $htime,
            ];
            if(!$refund['transaction_id']){
                unset($refund['transaction_id']);
            }
            if(!$refund['out_trade_no']){
                unset($refund['out_trade_no']);
            }
            dump($refund);
            $response = model('WxPay')->wxRefund($refund);
            dump($response);exit;
        }
    }

    /**
     * @name 微信支付
     * @packages protected
     */
    protected function wxpay($data){
        if ($data) {
            $notifyUrl = 'http://'.$_SERVER['HTTP_HOST'].'/wxpay_sunu.html';
            if($this->is_pc){
                $from = 'pc';
            }else{
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    $from = 'jsapi';
                }else{
                    $from = 'wap';
                }
            }
            $attr = urlencode(sunjiami(json_encode(array('vid'=>$data['vid'],'vtype'=>$data['vtype'],'coupon_id'=>$data['coupon_id'])),"hll"));

            $attributes = [
                'body' => isset($data['subject']) ? $data['subject'] :"{$this->site['site_keyword']}-购买",
                'out_trade_no' => "{$data['out_trade_no']}",
                'total_fee' => "{$data['total_fee']}",
                'attach' => $attr,//自定义参数 仅服务端异步可以接收9
            ];

            $wxPay = model('WxPay')->wxPayArouse($attributes, $from, $notifyUrl);

            if($this->is_pc && $wxPay['code_url']){
                if($wxPay['code_url']){
                    return $wxPay['code_url'];
                }
            }elseif($this->is_wap){
                if (strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    return $wxPay;
                }else{
                    return $wxPay['mweb_url'];
                }
            }
        }
        exit;
    }

    /**
     * @name 微信app支付回调
     */
    public function appWxpaySuccess(){
        //获取微信回调到服务器异步的参数
        $response = model('WxPay')->appWxNotify();
        file_put_contents('logs/wxpayre_success_app_video.txt', json_encode($response));

        if ($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS") {
            D('ZyRecharge')->setWxPaySuccess($response['out_trade_no'], $response['transaction_id'], $response['attach']);
        }
    }

    /**
     * @name 微信回调
     */
    public function wxpaySuccess(){
        //获取微信回调到服务器异步的参数
        $response = model('WxPay')->wxNotify();
        file_put_contents('logs/wxpayre_success_video.txt',json_encode($response));

        if($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS"){
            D('ZyRecharge')->setWxPaySuccess($response['out_trade_no'], $response['transaction_id'],$response['attach']);
        }
    }

    /**
     * @name 微信查询支付状态
     */
    public function getPayStatus(){
        $pay_pass_num = $_POST['pay_pass_num'];
        $data = M('zy_recharge')->where(['pay_pass_num'=>$pay_pass_num])->find();
        if($data['status'] == 1){
            $attach = json_decode(sunjiemi(urldecode($data['note_wxpay']),'hll'),true);
            $coupon_id = $attach['coupon_id'];
            $this_uid = $data['uid'];

            if($attach['vtype'] == 'zy_video'){
                $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>intval($attach['vid'])))->getField('pay_status');
            }elseif($attach['vtype'] == 'zy_album'){
                $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>intval($attach['vid'])))->getField('pay_status');
            }elseif($attach['vtype'] == 'zy_live'){
                $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>intval($attach['vid'])))->getField('pay_status');
            }elseif($attach['vtype'] == 'zy_teacher'){
                $pay_status = M('zy_order_teacher')->where(array('uid'=>$this_uid,'video_id'=>intval($attach['vid'])))->getField('pay_status');
            }

            if($pay_status == 3){
                $info = '购买成功';
            }else{
                $order_info = $this->buyOperating(intval($attach['vid']),$data['id'],$attach['vtype']);
                if($order_info == 1){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    $info = '购买成功';
                }else{
                    $info = '购买失败';
                }
            }
            $vid = intval($attach['vid']);
            $vtype = $attach['vtype'];
            if($vtype == 'zy_video'){
                $url = U('classroom/Video/view',array('id'=>$vid));
            }elseif($vtype == 'zy_album'){
                $url = U('classroom/Album/view',array('id'=>$vid));
            }elseif($vtype == 'zy_live'){
                $url = U('live/Index/view',array('id'=>$vid));
            }elseif($vtype == 'zy_teacher'){
                $url = U('classroom/LineClass/view',array('id'=>$vid));
            }

            echo json_encode(['status'=>1,'info'=>$info,'data'=>$url]);exit;
        }else{
            echo json_encode(['status'=>0]);exit;
        }
    }

    protected function lcnpay($args){
        $re = D('ZyRecharge');
        $out_trade_no = $args['out_trade_no'];
        $result = $re->setNormalPaySuccess2($out_trade_no,0);

        if($args['vtype'] == 'zy_video'){
            $uri = U('classroom/Video/view',array('id'=>$args['vid']));
        }elseif($args['vtype'] == 'zy_album'){
            $uri = U('classroom/Album/view',array('id'=>$args['vid']));
        }elseif($args['vtype'] == 'zy_live'){
            $uri = U('live/Index/view',array('id'=>$args['vid']));
        }elseif($args['vtype'] == 'zy_teacher'){
            $uri = U('classroom/LineClass/view',array('id'=>$args['vid']));
        }

        if($result){
            $this_uid = $re->where('pay_pass_num = '.$out_trade_no)->getField('uid');

            if (!D('ZyLearnc')->consume($this_uid, $args['total_fee'])) {
                return ['status'=>0,'info'=>"余额扣除失败",'data'=>$uri]; //余额扣除失败，可能原因是余额不足
            }

            //自定义数据
            $vid             = intval($args['vid']);
            $vtype           = $args['vtype'];
            $coupon_id       = $args['coupon_id'];

            //查询订单支付类型
            if($vtype == 'zy_video'){
                $status_info = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>$vid))->field('id,pay_status')->find();
                $relType = "zy_order_course";
            }elseif($vtype == 'zy_album'){
                $status_info = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>$vid))->field('id,pay_status')->find();
                $relType = "zy_order_album";
            }elseif($vtype == 'zy_live'){
                $status_info = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>$vid))->field('id,pay_status')->find();
                $relType = "zy_order_live";
            }elseif($vtype == 'zy_teacher'){
                $status_info = M('zy_order_teacher')->where(array('uid'=>$this_uid,'video_id'=>$vid))->field('id,pay_status')->find();
                $relType = "zy_order_teacher";
            }
            D('ZyLearnc')->addFlow($this_uid, 0, $args['total_fee'], "购买{$args['subject']}", $status_info['id'], $relType);

            if($status_info['pay_status'] == 3){
                if($coupon_id){
                    M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                }
                return ['status'=>1,'info'=>"购买成功",'data'=>$uri];
            }else{
                $order_info = $this->buyOperating($vid,$out_trade_no,$vtype);
                if($order_info == 1){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    return ['status'=>1,'info'=>"购买成功",'data'=>$uri];
                }else{
                    return ['status'=>0,'info'=>"购买失败",'data'=>$uri];
                }
            }
        }else{
            return ['status'=>0,'info'=>"余额支付异常",'data'=>$uri];
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

    //打赏
    public function dashang(){
        $t_uid = $_POST['t_uid'];
        if(empty($t_uid)){
            $return = ['status'=>0,'info'=>'打赏失败'];
            echo json_encode($return);exit;
        }
        $money = $_POST['money'];
        if(empty($money)){
            $return = ['status'=>0,'info'=>'金额不能为空'];
            echo json_encode($return);exit;
        }
        //查验余额
        $res = D('ZyLearnc')->isSufficient($this->mid, $money);
        if (!$res) {
            echo json_encode(['status' => 0, 'info' => "您的余额不够此次支付金额"]);
            exit;
        }
        if (!D('ZyLearnc')->consume($this->mid, $money)) {
            echo json_encode(['status'=>0,'info'=>"余额扣除失败"]); //余额扣除失败，可能原因是余额不足
        }else{
            $data = array(
                'ds_uid'    =>  $t_uid,
                'uid'   =>  $this->mid,
                'money' =>  $money,
                'add_time'  =>  time()
            );
            D('zy_dashang_log')->add($data);
            $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);
            $balance = D('zy_learncoin')->where(array('uid'=>$this->mid))->getField('balance');
            $log = array(
                'uid'   =>  $this->mid,
                'type'  =>  0,
                'num' =>  $money,
                'balance' =>  $balance,
                'note'  =>  '紫薇在线教育在线教育-打赏',
                'rel_id'    =>  1111,
                'rel_type'  =>  'zy_dashang',
                'ctime' =>  time()
            );
            D('zy_learncoin_flow')->add($log);
            //dd(D('zy_recharge')->getLastSql());
            echo json_encode(['status'=>1,'info'=>'打赏成功']);
        }
    }
}
