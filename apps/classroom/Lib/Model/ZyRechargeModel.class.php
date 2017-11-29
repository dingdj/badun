<?php
/**
 *
 * @author xiewei <master@xiew.net>
 * @version 1.0
 */
class ZyRechargeModel extends Model{

    public function __construct($name=''){
        parent::__construct($name);
        //自动删除3个月前的
        $time = strtotime('-3 month');
        $this->where("status=0 AND ctime<'{$time}'")->delete();
    }
    /**
     * 判断会员级别
     * @param array $data
     * @return integer 如果大于课程vip等级返回1否则返回0
     */
    public function getUserVipLevel($uid,$vip_level){
        $data=$this->where("type>0 and uid=".$uid)->order("stime desc")->field("type")->find();
        if($data && $data["type"]>=$vip_level){
            return 1;
        }else{
            return 0;
        }
    }
    /**
     * 添加充值记录
     * @param array $data
     * @return integer 如果成功返回记录号
     */
    public function addRechange($data){
        $data['ctime'] = time();
        $data['status'] = 0;
        $data['stime']  = 0;
        $data['pay_order']= '';
        $data['pay_data'] = '';
        $id = $this->add($data);
        return $id ? $id : false;
    }

    public function setSuccess($id, $order){
        $data = $this->find($id);
        if(!$data) return false;
        if($data['status'] == 0){
            $data['status'] = 1;
            $data['stime']  = time();
            $data['pay_order'] = $order;
            //修改充值记录状态
            $l = D('ZyLearnc',"classroom");
            if(false !== $this->save($data)){
                if( $data['type'] ){
                    //设置VIP
                    $type = $data['type'];
                    $time = $data['vip_length'];
                    if(!$l->setVip($data['uid'], $time, $type)){
                        return false;
                    }
                    $note = $type ? '充值年费VIP会员' : '充值普通会员';
                }else{
                    //添加学币
                    if(!$l->recharge($data['uid'], $data['money'])){
                        return false;
                    }
                    $note = '充值学币';
                }
                $s['uid']   = $data['uid'];
                $s['title'] = "恭喜您充值成功";
                $s['body']  = "恭喜您成功".$note."，花费".$data['money']."元";
                $s['ctime'] = time();
                model('Notify')->sendMessage($s);
                $l->addFlow($data['uid'], 1, $data['money'], $note, $data['id'], 'zy_rechange');
                return true;
            }

        }
        return $data['status']==1;
    }

    //修改购买支付记录状态
    public function setPaySuccess($pay_pass_num, $order,$status,$attach)
    {
        $recharge = M('zy_recharge')->where(array('pay_pass_num' => $pay_pass_num))->find();
        if (!$recharge) return false;
        if ($recharge['status'] == 0) {
            $data['status'] = $status;
            $data['stime'] = time();
            $data['pay_order'] = $order;
            if($attach){
                $data['note_wxpay'] = $attach;
            }

            //修改购买记录状态
            $rer = M('zy_recharge')->where(array('pay_pass_num' => $pay_pass_num))->save($data);
            if($rer){
                return true;
            }else{
                return false;
            }
        }
        return true;
    }

    //充值会员/积分成功
    public function setNormalPaySuccess($pay_pass_num, $order,$attach,$is_jiemi = true){
        $res = $this->setPaySuccess($pay_pass_num, $order,1);

        if($is_jiemi){
            $attach = json_decode(sunjiemi(urldecode($attach),'hll'),true);
        }

        if($res){
            $recharge = M('zy_recharge')->where(array('pay_pass_num'=>$pay_pass_num))->find();
            $recharge['type'] = explode(',',$recharge['type']);
            if( $recharge['type'][0] == 3 ){
                //设置VIP
                $type = $recharge['type'][1];
                $time = $recharge['vip_length'];
                $setVip = D('ZyLearnc','classroom')->setVip($recharge['uid'], $time, $type);
                if(!$setVip){
                    return false;
                }
                $title = M('user_vip')->where('id='.$type .' and is_del=0')->getField('title');
                $note = $type ? $title : '会员';
            }elseif( $recharge['type'][0] == 2 ){
                $money_arr = array_filter(explode('=>',$attach['money_str']));
                $balance = $money_arr[0] + $money_arr[1];
                //添加余额并加相关流水
                $learnc = D('ZyLearnc','classroom')->recharge($recharge['uid'],$balance);
                if(!$learnc) {
                    return false;
                }
                D('ZyLearnc','classroom')->addFlow($recharge['uid'], 1, $balance, '充值余额：' . $balance, $recharge['id'], 'zy_recharge');
                $note = '余额';
            }
            $s['uid']   = $recharge['uid'];
            $s['title'] = "恭喜您充值{$note}成功";
            $s['body']  = "恭喜您成功充值{$note}，花费{$recharge['money']}元";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);
            return true;
        }
        return false;
    }

    public function setNormalPaySuccess2($pay_pass_num, $order){
        $recharge_status = M('zy_recharge')->where(array('pay_pass_num'=>$pay_pass_num))->getField('status');
        if($recharge_status == 0){
            $data['status'] = 1;
            $data['stime']  = time();
            $data['pay_order'] = $order;
            //修改购买记录状态
            if(false !== M('zy_recharge')->where(array('pay_pass_num'=>$pay_pass_num))->save($data)){
                return true;
            }else{
                return false;
            }
        }else if($recharge_status == 1){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $id
     * @param $order
     * @param $note_wxpay
     * @return status
     */
    public function setWxPaySuccess($pay_pass_num, $order,$note_wxpay){
        $recharge = M('zy_recharge')->where(array('pay_pass_num'=>$pay_pass_num))->field('status')->find();

        if(!$recharge) return false;

        //查询订单支付类型
        $attach = json_decode(sunjiemi(urldecode($note_wxpay),'hll'),true);

        $re_data = M('zy_recharge')->where(['pay_pass_num'=>$pay_pass_num])->find();
        $coupon_id = $attach['coupon_id'];
        $vtype = $attach['vtype'];
        $this_uid = M('zy_recharge')->where('pay_pass_num = '.$pay_pass_num)->getField('uid');
        if($vtype == 'zy_video'){
            $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>intval($attach['vid'])))->getField('pay_status');
        }elseif($vtype == 'zy_album'){
            $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>intval($attach['vid'])))->getField('pay_status');
        }elseif($vtype == 'zy_live'){
            $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>intval($attach['vid'])))->getField('pay_status');
        }elseif($vtype == 'zy_teacher'){
            $pay_status = M('zy_order_teacher')->where(array('uid'=>$this_uid,'video_id'=>intval($attach['vid'])))->getField('pay_status');
        }
        if($pay_status == 3){
            return true;
        }else{
            if($recharge['status'] == 0){
                $data['status']     = 1;
                $data['stime']      = time();
                $data['pay_order']  = $order;
                $data['note_wxpay'] = $note_wxpay;
                //修改购买记录状态
                $status = M('zy_recharge')->where(array('pay_pass_num'=>$pay_pass_num))->save($data);
                if($status){
                    $order_info = $this->buyWxOperating(intval($attach['vid']),$re_data['id'],$attach['vtype']);
                    if($order_info == 1){
                        if($coupon_id){
                            M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                        }
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                $order_info = $this->buyWxOperating(intval($attach['vid']),$pay_pass_num,$attach['vtype']);
                if($order_info == 1){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

    /**
     * 购买课程成功 修改购买支付状态以及生成分成明细、每个人分成
     */
    public function buyWxOperating($vid,$out_trade_no,$vtype){
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
                unset($map['vid']);
            }elseif($vtype == 'zy_album'){
                $map['aid']  = intval($vid);
                $split_video = M('zy_split_album')->where($map) ->save($v_data);
                unset($map['aid']);
            }elseif($vtype == 'zy_live'){
                $map['lid']  = intval($vid);
                $split_video = M('zy_split_live')->where($map) ->save($v_data);
                unset($map['lid']);
            }elseif($vtype == 'zy_teacher'){
                $map['vid']  = intval($vid);
                $split_video = M('zy_split_teacher')->where($map) ->save($v_data);
                unset($map['vid']);
            }
            $map['status'] = 1;

//            $split_video = true;
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
                            }elseif($video_pay_status['pay_status'] == 1){
                                D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
                                unset($video);
                            }
                        }
                        if($video['type'] == 2) {
                            $video_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'live_id'=>$video['id']))->field('id,pay_status')->find();
                            if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
                                unset($video);
                            }elseif($video_pay_status['pay_status'] == 1){
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
}