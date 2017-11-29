<?php
/**
 * @name 机构API
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */

class SchoolApi extends Api{
    protected $mod = '';//当前操作的模型
    

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->mod 	 = model('School');
        $this->mod->mid = $this->mid;
    }

    /**
     * Eduline获取机构分类接口
     * 参数：
     * return   分类数据或者错误提示
     */
    public function getSchoolCategory(){
        $selCate = model('CategoryTree')->setTable('school_category')->getNetworkList(0,1);
        $selCate ? $this->exitJson($selCate) : $this->exitJson([],0,'未能获取到分类信息');
    }
    /**
     * @name 获取机构列表
     */
    public function getSchoolList(){
        if($this->user_id){
            $this->getFollowList($this->user_id);
        }
        $map['status'] = 1;
        $this->keyword && $map['title'] = array('like','%'.h($this->keyword).'%');

        $cateId = intval($this->data['cateId']);
        if ($cateId > 0) {
            $cateIds = model('CategoryTree')->setTable('school_category')->getSubCateIdByPid($cateId);
            $idlist = implode(',',$cateIds);
            $category = $cateId.','.$idlist;
            $map['school_category'] = ['in',trim($category,',')];
        }
        $data = $this->mod->getList($map,$this->count);
        if($data['gtLastPage'] === true){
            $this->exitJson([],1,'暂时没有更多机构');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson([],1,'暂时没有更多机构');
    }
    /**
     * @name 获取我关注的机构
     */
    public function getFollowList($uid = 0){
        $uid = $uid ? $uid : $this->mid;
        $data = $this->mod->getFollowList($uid,$this->keyword);
        if($data['gtLastPage'] === true){
            $this->exitJson([],1,'暂时没有关注更多机构');
        }
        $data['data'] ? $this->exitJson($data['data'],1) : $this->exitJson([],1,'暂时没有关注更多机构');
    }
    /**
     * @name 获取单个机构的详情
     */
    public function getSchoolInfo(){
        $info = $this->mod->getSchoolInfoById($this->school_id);
        if($info){
            $this->mod->addViewCount($this->school_id);
            $info['recommend_list'] = $this->recommend();
        }
        $info ? $this->exitJson($info,1) : $this->exitJson((object)[],0,'未查询到相关信息');
    }
    /**
     * @name 获取机构排课
     */
    public function getArrange(){
        $list = [];
        if(is_numeric($this->school_id)){
            $nums = M('concurrent')->where('id = 1')->getField('Concurrent_nums');
            if(isset($this->data['timespan'])){
                $time = strtotime(date('Y-m-d',$this->timespan));
            }else{
                $time = strtotime(date('Y-m-d'));
            }
            $map['mhm_id'] = $this->school_id;
            $map['start'] = array('BETWEEN', array($time, $time+86400));
            $map['is_activity'] = 1;
            $map['is_del'] = 0;
            $data = M('arrange_course')->where($map)->findPage($this->count);
            if($data['data']){
                foreach($data['data'] as $v){
                    $item = [
                        'video_title' => $v['video_title'],
                        'course_id'   => (int)$v['course_id'],
                        'start_time'  => (int)$v['start'],
                        'end_time'    => (int)$v['start']+3600,
                        'beginTime'   => (int)$v['beginTime'],
                        'endTime'     => (int)$v['endTime'],
                        'concurrent_nums' => $nums - $v['maxmannums'] .'/'.$nums,
                        'teacher_id'    => (int)$v['speaker_id'],
                        'teacher_name'  => D('ZyTeacher','classroom')->where(array('id'=>$v['speaker_id']))->getField('name'),
                        'buy_count' => (int)D('ZyOrderLive','classroom')->where(array('live_id'=>$v['course_id']))->count()
                    ];
                    array_push($list,$item);
                }
            }
        }
        if($list){
            $this->exitJson($list,1);
        }else{
            $this->exitJson([],0,'暂时没有排课信息');
        }
    }
    
    /**
     * @name 申请成为机构
     */
    public function apply(){
        //title,uid,logo,cover,cate_id,domain,videoSpace,school_and_teacher,info,type
        $status = $this->mod->where(array('uid'=>$this->mid))->getField('status');
        if($status == 1){
            $this->exitJson((object)[],0,'你已成为机构,无需再申请');
        }elseif($status == 2){
            $this->exitJson((object)[],0,'你已申请过机构');
        }elseif($status !== null && $status == '0'){
            $this->exitJson((object)[],0,'你已经提交过申请,请等待审核');
        }
        $data = [
            'title'             => filter_keyword($this->title),
            'uid'               => $this->mid,
            'logo'              => intval($this->logo_id),
            //'cover'             => intval($this->cover_id),
            'school_category'   => $this->cate_id,
            //'doadmin'   => t($this->domain),
            //'videoSpace' => intval($this->videoSpace),
            //'school_and_teacher' => t($this->ratio) ?: '1:0',
            'info'              => filter_keyword($this->info),
            //'type'  => $this->type,
            'fullcategorypath'  => model('CategoryTree')->setTable('zy_currency_category')->getFullCateGoryPath($this->cate_id),//分类全路径
            'ctime'             => time(),
            'idcard'            => t($this->idcard),
            'phone'             => t($this->phone),
            //'province'          => intval($this->province),
            //'city'              => intval($this->city),
            //'area'              => intval($this->area),
            
            'attach_id'         => t($this->attach_id),
            'identity_id'       => t($this->identity_id),
            'reason'            => filter_keyword(t($this->reason)),
            'cuid'              => $this->mid,
        ];
        //检测机构名称是否可用
        if($this->mod->where(['title'=>$data['title']])->count() > 0) $this->exitJson((object)[],0,'机构名称已被占用');
        //检测机构域名是否可用
        if($this->mod->where(['doadmin'=>$data['doadmin']])->count() > 0) $this->exitJson((object)[],0,'机构域名已被占用');
        //$sat = explode(':',$data['school_and_teacher']);
        //if(floatval($sat[0]) + floatval($sat[1]) != 1){
           // $this->exitJson((object)[],0,'机构与教师分成比例之和须为1');
        //}
        if(in_array(' ',$data)){
            $this->exitJson((object)[],0,'请将信息填写完整');
        }
        if($address = t($this->address)){
            $data['address'] =filter_keyword($address);
        }
        if($id = $this->mod->add($data)){
            $this->exitJson(['status'=>$this->mod->getStatusByUid($this->mid)],1,'申请成功,请等待审核');
        }
        $this->exitJson((object)[],0,'申请失败,请稍后重试');
    }
    /**
     * @name 检测用户的机构状态
     */
    public function getStatus(){
        $this->exitJson(['status'=>$this->mod->getStatusByUid($this->mid),'school_id'=>$this->mod->getSchoolByUid($this->mid)],1);
    }
    /**
     * @name 添加机构浏览量
     */
    public function addViewCount(){
        if(!$this->school_id){
            $this->exitJson((object)[],0,'未能查询到机构信息'); 
        }
        $this->exitJson(['view_count' => $this->mod->addViewCount($this->school_id)],1);
    }
    
    /**
     * @name 获取机构的推荐课程
     */
    public function recommend(){
        if(!$this->school_id){
            $this->exitJson((object)[],0,'未能查询到机构信息'); 
        }
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['is_best'] = 1;
        $map['uctime']      = array('GT',time());
        $map['listingtime'] = array('LT',time());
        $map['mhm_id'] = intval($this->school_id);
        $data = D('ZyVideo','classroom')->where($map)->order('endtime,starttime,limit_discount')->limit(3)->select();
        foreach ($data as $key => $val){
             $data[$key]['price']       = getPrice ( $val, $this->mid );// 计算价格
             $data[$key]['imageurl']    = getCover($val['cover'] , 280 , 160);
             $data[$key]['video_score'] = round ( $val['video_score'] / 20 ); // 四舍五入
        }
        return $data ?: [];
    }
     
    /**
     * 获取机构的订单列表
     */
    public function getOrderInfo(){
        if(!$this->school_id){
            $this->exitJson((object)[],0,'未能查询到机构信息'); 
        }
        $limit = intval($this->count) ?: 20;
        $page = intval($this->page) ?:1;
        
        $subSql = '';
        $pay_status = (int)$this->pay_status;
        $map['mhm_id'] = $this->school_id;
        // 1未支付,2已取消,3已支付;4申请退款;5退款成功
        if($pay_status && in_array($pay_status,[1,2,3,4,5,6])){
            $subSql = ' AND pay_status = '.$pay_status;
            $map['pay_status'] = $pay_status;
        }
        //联查用户购买的记录，包括购买的直播课程,购买的点播课程
        $sql  = 'SELECT IFNULL(3,4) AS order_type,id,uid,live_id AS cid,old_price,discount,discount_type,price,order_album_id,learn_status,ctime,ptime,pay_status,mhm_id,refund_reason,reject_info FROM `'.C('DB_PREFIX').'zy_order_live` where mhm_id = '.$this->school_id.' AND is_del = 0'.$subSql;
        $sql .= ' UNION ALL SELECT IFNULL(4,3) AS order_type,id,uid,video_id AS cid,old_price,discount,discount_type,price,order_album_id,learn_status,ctime,ptime,pay_status,mhm_id,refund_reason,reject_info FROM `'.C('DB_PREFIX').'zy_order_course` where time_limit >= '.time().' AND mhm_id = '.$this->school_id.' AND is_del = 0'.$subSql;
        $sql .= ' ORDER BY ctime DESC LIMIT '.($page-1) * $limit.','.$limit;
        $data = M('')->query($sql);
        if(empty($data)){
            $this->exitJson([],0,'暂时没有订单');
        }
        $data = $this->haddleData($data);
        if($data){
            $list['list'] = $data;
            M('zy_order_live')->where($map)->count();
            $list['order_count'] = (int)M('zy_order_live')->where($map)->count();
            $map['time_limit'] = ['egt',time()];
            $list['order_count'] += (int)M('zy_order_course')->where($map)->count();
            
            $this->exitJson($list,1);
        }
        $this->exitJson([],0,'暂时没有订单');
    }
    
    /**
     * 获取当前机构某一个月每天排课的课程数量
     */
    public function getMonthsCourseCount(){
        $data = $this->mod->getMonthsCourseCount($this->school_id,$this->timespan);
        if($data){
            $this->exitJson($data,1);
        }else{
            $this->exitJson((object)[],0,'当月没有排课信息');
        }
    }
    
    /**
     * 获取指定机构的优惠券列表
     */
    public function getCouponList(){
        $count = (int)$this->count ?: 20;
        $type = in_array($this->type,[1,2,3,4]) ? (int)$this->type : 0; 
        $map = [
            'sid' => $this->school_id,
            'is_del' => 0,
            'status' => 1,
            'end_time' => ['egt',time()]
        ];
        if($type){
            $map['type'] = $type;
        }
        $list = model('Coupon')->getList($map,'ctime desc',$count);
        if($list['data']){
            $this->exitJson($list['data'],1);
        }
        $this->exitJson((object)[],0,'没有优惠券信息');
    }
    
    /**
     * @name 处理兑换订单的数据
     */
    private function haddleData($data = []){
        $res = [];
        if(!empty($data)){
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
                    'source_info' => $this->getSourceInfo($v['cid'])
                ];
                array_push($res,$item);
            }
        }
        return $res;
        
    }
    
    /**
     * @name 获取资源信息
     */
    protected function getSourceInfo($source_id = 0){
        $info = [];
        //课程订单
        $info = D ( 'ZyVideo','classroom' )->where ( ['id'=>$source_id] )->find ();
        if ( $info) {
            // 处理数据
            $info['live_id'] = intval($info['id']);
            $info ['video_score'] = round ( $info ['video_score'] / 20 ); // 四舍五入
            $info ['vip_level']   = intval($info ['vip_level']);
            $info ['reviewCount'] = D ( 'ZyReview' ,'classroom')->getReviewCount ( 1, intval ( $info ['id'] ) );
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
            $info['is_play_all'] = ($info['isBuy'] || floatval( $info ['mzprice']['price'] )  <= 0 ) ? 1 : 0;
            $info['school_info'] = model('School')->getSchoolInfoById($info['mhm_id']);
			
        }
        return $info;
    }
}