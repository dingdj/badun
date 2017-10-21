<?php
/**
 * @name 订单API
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class OrderApi extends Api{
    protected $mod = '';//当前操作的模型


    /**
     * 初始化模型
     */
    public function _initialize() {
        //$this->mod 	 = model('ZyOrder','classroom');
        model('Coupon')->checkOverdueOrder($this->mid);
    }

    /**
     * @name 获取订单
     */
    public function getOrder(){
        $type =  in_array($this->type,['course','exchange']) ? $this->type : 'course';
        $this->$type();
    }

    /**
     * @name 获取课程订单
     */
    private function course(){
        $stime = intval($this->start_time) ?:0;
        $limit = intval($this->count) ?: 20;
        $page = intval($this->page) ?:1;
        $pay_status = intval($this->pay_status);
        $subSql = '';
        if($pay_status == 4){
            $subSql = ' AND pay_status IN (4,6)';
        }else if($pay_status && in_array($pay_status,[1,2,3,4,5,6])){
            $subSql = ' AND pay_status = '.$pay_status;
        }
        //联查用户购买的记录，包括购买的直播课程,购买的点播课程
        $sql  = 'SELECT IFNULL(3,4) AS order_type,id,uid,live_id AS cid,old_price,discount,discount_type,price,order_album_id,learn_status,ctime,ptime,pay_status,mhm_id,refund_reason,reject_info FROM `'.C('DB_PREFIX').'zy_order_live` where uid = '.$this->mid.' AND ctime > '.$stime.' AND is_del = 0'.$subSql;
        $sql .= ' UNION ALL SELECT IFNULL(4,3) AS order_type,id,uid,video_id AS cid,old_price,discount,discount_type,price,order_album_id,learn_status,ctime,ptime,pay_status,mhm_id,refund_reason,reject_info FROM `'.C('DB_PREFIX').'zy_order_course` where (time_limit =0 or time_limit >= '.time().') AND uid = '.$this->mid.' AND ctime > '.$stime.' AND is_del = 0'.$subSql;
        $sql .= ' ORDER BY ctime DESC LIMIT '.($page-1) * $limit.','.$limit;
        $data = M('')->query($sql);
        if(empty($data)){
            $this->exitJson([],0,'暂时没有更多订单');
        }
        $data = $this->haddleData($data,2);
        $data ? $this->exitJson($data,1) : $this->exitJson([],0,'暂时没有订单');
    }

    /**
     * @name 获取兑换订单
     */
    private function exchange(){
        $stime = intval($this->start_time) ?:0;
        $limit = intval($this->count) ?: 20;
        $page = intval($this->page) ?:1;
        //联查用户兑换的记录，包括兑换积分商品，兑换的文库
        $sql  = 'SELECT id,cid,uid,price,ctime,IFNULL(0,1) as source_id FROM `'.C('DB_PREFIX').'doc_user` where uid = '.$this->mid.' AND ctime > '.$stime;
        $sql .= ' UNION ALL SELECT id,goods_id AS cid,uid,price,ctime,goods_id AS source_id from `'.C('DB_PREFIX').'goods_order` where uid = '.$this->mid.' AND ctime > '.$stime;
        $sql .= ' ORDER BY ctime DESC LIMIT '.($page-1) * $limit.','.$limit;
        $data = M('')->query($sql);
        if(empty($data)){
            $this->exitJson([],0,'暂时没有更多订单');
        }
        $data = $this->haddleData($data,1);
        $data ? $this->exitJson($data,1) : $this->exitJson([],0,'暂时没有订单');
    }

    /**
     * @name 处理兑换订单的数据
     */
    private function haddleData($data = [],$type = 1){
        $res = [];
        if(!empty($data)){
            switch($type){
                case 2:
                    foreach($data as $v){
                        $order_type = (intval($v['order_type']) == 3) ? 3 : 4;//3:直播  4:点播
                        $item = [
                            'order_type' => $order_type,
                            'order_id'   => intval($v['id']),
                            'ctime'      => $v['ctime'],
                            'ptime'      => $v['ptime'],
                            'old_price'  => $v['old_price'],
                            'price'      => $v['price'],
                            'discount'   => $v['discount'],//享受的折扣
                            'discount_type' => intval($v['discount_type']),//折扣类型,(0:未享受,1:折扣卡,2:优惠券)
                            'source_id'  => intval($v['cid']),
                            'pay_status' => intval($v['pay_status']),
                            'learn_status' => intval($v['learn_status']),
                            'refund_reason' => $v['refund_reason'] ?:'',
                            'reject_info'   => $v['reject_info'] ?: '',
                            'source_info' => $this->getSourceInfo($v['cid'],3)
                        ];
                        array_push($res,$item);
                    }
                    break;
                case 1:
                default:
                    foreach($data as $v){
                        $order_type = (intval($v['source_id']) == 0) ? 1 : 2;//1为文库  2为积分商品
                        $item = [
                            'order_type' => $order_type,
                            'order_id'   => intval($v['id']),
                            'ctime'      => $v['ctime'],
                            'price'      => intval($v['price']),
                            'source_id'  => intval($v['cid']),
                            'source_info' => $this->getSourceInfo($v['cid'],$order_type)
                        ];
                        array_push($res,$item);
                    }
                    break;
            }

        }
        return $res;

    }

    /**
     * @name 获取资源信息
     */
    protected function getSourceInfo($source_id = 0,$order_type = 1){
        $info = [];
        if($order_type == 1){
            //文库
            $this->mod = model('Doc');
            $this->mod->mid = $this->mid;
            $info = $this->mod->getDocById($source_id);
        }elseif($order_type == 2){
            //积分商品
            $this->mod = model('Goods');
            $this->mod->mid = $this->mid;
            $info = $this->mod->getInfoByGoodsId($source_id);
        }elseif($order_type == 3){
            //课程订单
            $info = D ( 'ZyVideo','classroom' )->where ( ['id'=>$source_id] )->find ();
            if ( $info) {
                // 处理数据
                //if($info['type'] == '2'){
                $info['live_id'] = intval($info['id']);
                //}
                $info ['video_score'] = round ( $info ['video_score'] / 20 ); // 四舍五入
                $info ['vip_level']   = intval($info ['vip_level']);
                $info ['reviewCount'] = D ( 'ZyReview' ,'classroom')->getReviewCount ( 1, intval ( $info ['id'] ) );
                //$info ['video_title'] = $info ['video_title'];
                //$info ['video_intro'] = $info ['video_intro'];
                $info ['video_category_name'] = getCategoryName ( $info ['video_category'], true );
                $info ['cover'] = getCover($info['cover'] , 280 , 160);
                $info ['iscollect'] = D ( 'ZyCollection' ,'classroom')->isCollect ( $info ['id'], 'zy_video', intval ( $this->mid ) );
                $info ['follower_count'] = (int)D('ZyCollection','classroom')->where(['source_table_name'=>'zy_video','source_id'=>$source_id])->count() ?:0;
                $info ['mzprice'] = getPrice ( $info, $this->mid, true, true );
                $info ['isSufficient'] = D ( 'ZyLearnc','classroom' )->isSufficient ( $this->mid, $info ['mzprice'] ['price'] );
                $info ['isGetResource'] = isGetResource ( 1, $info ['id'], array (
                    'video',
                    'upload',
                    'note',
                    'question'
                ) );
                $info['isBuy']= D ( 'ZyOrder','classroom' )->isBuyVideo($this->mid , $source_id);
                $info['is_buy'] = $info['isBuy'] ? 1 : 0;
                $info['is_play_all'] = ($info['isBuy'] || intval( $info ['mzprice']['price'] )  <= 0 ) ? 1 : 0;
                $info['school_info'] = model('School')->getSchoolInfoById($info['mhm_id']);

            }

        }
        return $info;
    }

    public function refund_info(){
        $id = intval($this->order_id);
        $type = t($this->order_type);

        if ($type == '0') {
            $table = "zy_order_course";
//        } elseif ($type == '1') {
//            $table = "zy_order_album";
        } elseif ($type == '2') {
            $table = "zy_order_live";
        }

        $order_info = M($table)->where(['id'=>$id])->field('price,rel_id')->find();
        $pay_type = M('zy_recharge')->where(['pay_pass_num'=>$order_info['rel_id']])->getField('pay_type');

        if(!$order_info || !$pay_type){
            $this->exitJson((object)[],0,'未能获取到该订单信息');
        }

        if($pay_type == 'alipay'){
            $pay_type = '支付宝';
        }else if($pay_type == 'wxpay' || $pay_type == 'wap_wxpay'){
            $pay_type = '微信';
        }else if($pay_type == 'unionpay'){
            $pay_type = '银联';
        }
        $refund_info['price'] = $order_info['price'];
        $refund_info['pay_type'] = $pay_type;
        $refund_info['type'] = $type;
        $refund_info['refundConfig'] = model('Xdata')->get('admin_Config:refundConfig')['refund_numday'];
        $refund_info['refund_numsty'] = model('Xdata')->get('admin_Config:refundConfig')['refund_numsty'];

        $this->exitJson($refund_info,1,'查询信息正常');
    }
    /**
     * @name 申请退款
     */
    public function refund(){
//        $order_type = in_array($this->order_type,[0,2]) ? (int)$this->order_type : 0;//只能取消直播和点播的购买的订单
        switch($this->order_type){
            case 0:
                $this->mod = M('zy_order_course');
                break;
            case 2:
                $this->mod = M('zy_order_live');
                break;
            default:
                $this->exitJson((object)[],0,'未能获取到该订单信息');
        }
        $order_info = $this->mod->where(['id'=>$this->order_id,'uid'=>$this->mid])->field('pay_status,ptime')->find();
        if ($this->order_type == 0 || $this->order_type == 2) {
            if($order_info['order_album_id']){
                $this->exitJson((object)[],0,'课程通过套餐购买，请通过套餐退款');
            }
        }

        $refundConfig = model('Xdata')->get('admin_Config:refundConfig');
        $order_ptime = time() - $order_info['ptime'];
        $refund_time = $refundConfig['refund_numday'] * 86400;
        if($order_ptime > $refund_time){
            $this->exitJson((object)[],0,"该课程已超过{$refundConfig['refund_numday']}天退款有效期");
        }

        if($order_info['pay_status'] != 3){
            //如果该订单状态不是已经支付的状态
            if($order_info['pay_status'] === null){
                $this->exitJson((object)[],0,'未能获取到该订单信息');
            }elseif($order_info['pay_status'] == 5){
                $this->exitJson((object)[],0,'该订单已经退款');
            }elseif($order_info['pay_status'] == 4){
                $this->exitJson((object)[],0,'您已经申请了该订单的退款,请耐心等待处理');
            }elseif($order_info['pay_status'] == 1){
                $this->exitJson((object)[],0,'该订单还未支付,无法申请退款');
            }elseif($order_info['pay_status'] == 2){
                $this->exitJson((object)[],0,'该订单已被取消,无法申请退款');
            }
        }

        $data['refund_reason'] = t($this->refund_reason);//退款原因
        $data['refund_note'] = t($this->refund_note);//退款说明
        $data['voucher'] = t($this->voucher);//退款凭证图片
        $data['order_id'] = $this->order_id;
        $data['refund_status'] = 0;
        $data['ctime'] = time();
        if(!t($this->refund_reason)){
            $this->exitJson((object)[],0,'请填写退款理由');
        }
        if(!t($this->refund_note)){
            $this->exitJson((object)[],0,'请填写退款说明');
        }
        $res = M('zy_order_refund')->add($data);
        if ($res) {
            $ret = $this->mod->where(['id'=>intval($this->order_id),'uid'=>$this->mid])->save(['pay_status'=>4]);
            if ($ret) {
                $this->exitJson(['order_id'=>intval($this->order_id)],1,'申请退款成功,请等待处理');
            } else {
                $this->mod->where(['id'=>intval($this->order_id),'uid'=>$this->mid])->save(['pay_status'=>3]);
                $this->exitJson((object)[],0,'申请退款失败,请稍后重试');
            }
        } else {
            $this->exitJson((object)[],0,'申请退款失败,请稍后重试');
        }
    }
    /**
     * @name 取消订单
     */
    public function cancel(){
        $order_type = in_array($this->order_type,[3,4]) ? (int)$this->order_type : 0;//只能取消直播和点播的购买的订单
        switch($this->order_type){
            case 3:
                $this->mod = M('zy_order_live');
                break;
            case 4:
                $this->mod = M('zy_order_course');
                break;
            default:
                $this->exitJson((object)[],0,'未能获取到该订单信息');
        }
        $pay_status = $this->mod->where(['id'=>$this->order_id,'uid'=>$this->mid])->getField('pay_status');
        if($pay_status === null){
            $this->exitJson((object)[],0,'未能获取到该订单信息');
        }
        if($pay_status != 1){
            $this->exitJson((object)[],0,'该订单已经处理过,无法再取消');
        }
        //更改状态
        $res = $this->mod->where(['id'=>intval($this->order_id),'uid'=>$this->mid])->setField('pay_status',2);
        if($res){
            $this->exitJson(['order_id'=>intval($this->order_id)],1,'取消成功');
        }
        $this->exitJson((object)[],0,'取消失败,请稍后重试');
    }

    /**
     * @name 继续支付
     */
    public function payOrder(){
        $order_type = in_array($this->order_type,[3,4]) ? (int)$this->order_type : 0;//只能直播和点播的购买的订单
        switch($this->order_type){
            case 3:
                $this->mod = M('zy_order_live');
                $vid = 'live_id';
                $vtype = 'zy_live';
                break;
            case 4:
                $this->mod = M('zy_order_course');
                $vid = 'video_id';
                $vtype = 'zy_video';
                break;
            default:
                $this->exitJson((object)[],0,'未能获取到该订单信息');
        }
        $order = $this->mod->where(['id'=>$this->order_id,'uid'=>$this->mid])->find();
        if(!$order){
            $this->exitJson((object)[],0,'未查询到原订单信息');
        }else if($order['pay_status'] != 1){
            $this->exitJson((object)[],0,'该订单已经处理过,无法继续支付');
        }
        //可以继续支付
        if($order){
            //购买订单生成成功
            $videodata = D ( 'ZyVideo','classroom' )->getVideoTitleById ( $order[$vid] );
            $pay_pass_num = date('YmdHis',time()).mt_rand(1000,9999).mt_rand(1000,9999);
            $pay_id = D('ZyRecharge','classroom')->addRechange(array(
                'uid'      => $this->mid,
                'type'     => 1,
                'money'    => $order['price'],
                'note'     => "购买课程:{$videodata}",
                'pay_type' => $this->pay_for,
                'pay_pass_num'=>$pay_pass_num,
            ));
            $pay_data['is_free'] = 0;
			$pay_for = in_array($this->pay_for,array('alipay','wxpay')) ? [$this->pay_for] : ['alipay','wxpay'];
			foreach($pay_for as $p){
				switch($p){
					case "alipay":
						$pay_data['alipay']  = $this->alipay(array(
							'vid'          => $order[$vid],
							'vtype'        => $vtype,
							'out_trade_no' => $pay_pass_num,
							'total_fee'    => $order['price'],
							'subject'      => "购买课程:{$videodata}",
						));
						break;
					case "wxpay":
						$pay_data['wxpay'] = $this->wxpay([
							'subject' => "购买课程:{$videodata}",
							'total_fee' => $order['price'] * 100,
							'out_trade_no' => $pay_pass_num,
							'vid'       => $order[$vid],
							'vtype'     => $vtype,
							'coupon_id' => $order['coupon_id']
						],'video');
						break;
				}
			}
            $this->exitJson($pay_data,1);

        }
        $this->exitJson((object)[],0,'未能获取到该订单信息');
    }

    /**
     * 删除订单
     */
    public function deleteOrder(){
        $order_type = in_array($this->order_type,[3,4]) ? (int)$this->order_type : 0;//只能直播和点播的购买的订单
        switch($this->order_type){
            case 3:
                $this->mod = M('zy_order_live');
                break;
            case 4:
                $this->mod = M('zy_order_course');
                break;
            default:
                $this->exitJson((object)[],0,'未能获取到该订单信息');
        }
        $order = $this->mod->where(['id'=>$this->order_id,'uid'=>$this->mid])->save(['is_del'=>1]);
        if($order){
            $this->exitJson(['order_id'=>(int)$this->order_id,'order_type'=>(int)$this->order_type],1);
        }
        $this->exitJson((object)[],0,'删除订单失败,请稍后重试');
    }
}