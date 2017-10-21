<?php
/**
 * 讲师api
 * utime : 2016-03-06
 */

class TeacherApi extends Api{
     private $teacher;

    public function __construct(){
        parent::__construct();
        $this->teacher = M('ZyTeacher');

    }
    /**
     * Eduline获取讲师列表接口
     * 参数：
     * page 页数
     * count 每页条数
     * return   用户数据或者登录错误提示
     */
    public function getTeacherList(){
        $map = array('is_del'=>0);
        if(isset($this->data['school_id'])){
            $map['mhm_id'] = $this->school_id;
        }
		$teacherData = $this->teacher->where($map)->order('ctime DESC')->limit($this->_limit())->select();
		if( !$teacherData ) {
      		$this->exitJson( array() );
		}
        //计算每个教师的课程总数
		foreach($teacherData as &$val){ 
			$time  = time();
			$where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= ".$val['id'];
			$val['video_count'] = D('ZyVideo')->where($where)->count();
			$val['headimg']    = getCover($val['head_id'] , 150 , 150 );
            $val['follow_state'] = model('Follow')->getFollowState($this->mid , $val['uid']);
            $count_info['video_count'] = $val['video_count'];//(int)D('ZyVideo','classroom')->where(['teacher_id'=>$val['id']])->count() ?:'0';
            $val['title'] = M('zy_teacher_title_category')->where(['zy_teacher_title_category_id'=>$val['title']])->getField('title');
            $val['ext_info'] = model('User')->formatForApi($count_info,$val['uid'],$this->mid);
			$val['school_info'] = model('School')->getSchoolInfoById($val['mhm_id']);
		}
		$this->exitJson($teacherData);
    }
    
    /**
     * 搜索教师
     */
    public function searchTeacher(){
    	$map['name']   = array('like' , '%'.t($this->data['name']).'%');
    	$map['is_del'] = 0;
        if(isset($this->data['school_id'])){
            $map['mhm_id'] = $this->school_id;
        }
    	$teacherData = $this->teacher->where($map)->order('ctime DESC')->limit($this->_limit())->select();
    	if( !$teacherData ) {
			$this->exitJson( array() );
    	}
    	//计算每个教师的课程总数
    	foreach($teacherData as &$val){
    		$time  = time();
    		$where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= ".$val['id'];
    		$val['video_count']   = D('ZyVideo','classroom')->where($where)->count() ?:'0';
    		$val['headimg']    = getCover($val['head_id'] , 150 , 150 );
            $val['follow_state'] = model('Follow')->getFollowState($this->mid , $val['uid']);
            $count_info['video_count'] = $val['video_count'];//(int)D('ZyVideo','classroom')->where(['teacher_id'=>$val['id']])->count() ?:'0';
            $val['title'] = M('zy_teacher_title_category')->where(['zy_teacher_title_category_id'=>$val['title']])->getField('title');
            $val['ext_info'] = model('User')->formatForApi($count_info,$val['uid'],$this->mid);
			$val['school_info'] = model('School')->getSchoolInfoById($val['mhm_id']);
    	}
    	$this->exitJson($teacherData);
    }

    /**
     * Eduline获取讲师详情
     * teacher_id 讲师id
     * return   讲师的详情
     */
    public function  getTeacher(){
        $teacherId = intval($this->data['teacher_id']);//获取讲师id
        if(!$teacherId){
            $this->exitJson( array() ,10005,"没有讲师id");
        }
        $teacherInfo = $this->teacher->where(array('id'=>$teacherId,'is_del'=>0))->find();
        if($teacherInfo){
            //获取讲师评价
            //$inSql = "SELECT course_id FROM ".C('DB_PREFIX')."zy_teacher_course WHERE course_teacher=".$teacherInfo['uid'];
            //$where .= " AND course_id IN($inSql)";
            //$data=M("zy_teacher_review")->where($where)->order($order)->findPage(10);
            /**
            if($data['data']) {
                foreach ($data['data'] as $key => $value) {
                    $data['data'][$key]["course_info"]=M("zy_teacher_course")->where("course_id=".$value["course_id"])->find();
                    $data['data'][$key]["user_info"]=M("user")->where('uid='.$value["uid"])->field('uname')->find();
                }
               // $teacherInfo['comment_list'] = $data['data'];
            }else{
                //$teacherInfo['comment_list'] = [];
            }**/
            $time = time();
            $teacherInfo['headimg'] = getCover($teacherInfo['head_id'] , 150 , 150);
            $teacherInfo['follow_state'] = model('Follow')->getFollowState($this->mid , $teacherInfo['uid']);
            $follow_count = model('Follow')->getFollowCount($teacherInfo['uid']);
            foreach($follow_count as $k=>&$v){
                $teacherInfo['follow_state']['follower'] = $v['follower'];
            }
            $teacherInfo['ext_info'] = model('User')->formatForApi($count_info,$teacherInfo['uid'],$this->mid);
            $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= ".$teacherId;
            $teacherInfo['video_count']   = D('ZyVideo','classroom')->where($where)->count() ?:'0';
            $teacherInfo['title'] = M('zy_teacher_title_category')->where(['zy_teacher_title_category_id'=>$teacherInfo['title']])->getField('title');
			$teacherInfo['school_info'] = model('School')->getSchoolInfoById($teacherInfo['mhm_id']);
            $this->exitJson($teacherInfo);
        }
        $this->exitJson( array() ,10005,"没有该讲师信息");
    }

    /**
     * 获取讲师相关课程列表
     * 参数：
     * teacher_id 讲师id
     * return   讲师相关课程列表详情
     */
    public function teacherVideoList(){
        $teacher_id = intval( $this->data['teacher_id'] );//获取讲师id
        if(!$teacher_id){
            $this->exitJson( array() ,10005,"没有讲师id");
        }
        $order = 'id DESC';
        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND is_mount = 1 AND uctime>$time AND listingtime<$time AND teacher_id= $teacher_id";
        $data = D('ZyVideo')->where($where)->order($order)->limit($this->_limit())->select();
        if( !$data ) {
        	$this->exitJson( array() );
        }
		foreach($data as $key => &$val){
            $val['teacher_name'] = $this->teacher->where('id='.$teacher_id)->getField('name');
            $val['video_section_count'] = M('zy_video_section')->where(array('vid'=>$val['id'],'pid'=>['gt','0']))->count();
            $val['imageurl']   = getCover( $val['cover'] , 280 , 160);
            $val['mzprice'] = getPrice ( $val, $this->mid, true, true );
        }
        $this->exitJson($data);
    }
    
    //获取套餐讲师列表
    public function groupTeacherList(){
        $albumid = intval($this->data['id']);//获取课程/套餐的id
        //查询套餐中的课程
        $videoStr = trim( D('Album', 'classroom')->getVideoId( $albumid ) , ',');
        $where['id']         = array('in',$videoStr);
        $where['teacher_id'] = array("gt",0);
        $videoList = M('ZyVideo')->where($where)->field("teacher_id")->select();
		foreach($videoList as $key=>$val){
			$videoList[$key] = $val['teacher_id'];
        }
      	$videoList = array_flip(array_flip($videoList));//去掉重复讲师id
      	$videoList = implode(',' , $videoList);
        $str = trim($videoList, ',');
        $teacherList = M('ZyTeacher')->where(array("id"=>array('in',trim($str,','))))->select();
        foreach($teacherList as &$val){
            $val['headimg'] = getCover($val['head_id'] , 150 , 150);
            $val['follow_state'] = model('Follow')->getFollowState($this->mid , $val['uid']);
            $val['title'] = M('zy_teacher_title_category')->where(['zy_teacher_title_category_id'=>$val['title']])->getField('title');
        }
        $this->exitJson($teacherList,1);
    }
    
    /**
     * @name 获取讲师文章列表
     */
    public function getArticleList(){
        $map['tid'] = $this->teacher_id;
        $map['is_del'] = 0;
        $article = M('zy_teacher_article')->where($map)->field(['id','cover','art_title','ctime'])->findPage($this->count);
        if($article['data'] && $article['gtLastPage'] !== true){
            foreach($article['data'] as $k=>$v){
                $article['data'][$k]['id'] = (int)$v['id'];
                $article['data'][$k]['cover'] = (int)$v['cover'];
                $article['data'][$k]['image'] = getCover($v['cover']);
            }
            $this->exitJson($article['data'],1);
        }
        $this->exitJson([],0,'该讲师暂时没有文章');
    }
    /**
     *@name 获取讲师点评列表
     */
    public function  getCommentList(){
        $teacherId = intval($this->data['teacher_id']);//获取讲师id
        if(!$teacherId){
            $this->exitJson( array() ,10005,"没有该讲师信息");
        }
        //获取讲师评价
        //$inSql = "SELECT id FROM ".C('DB_PREFIX')."zy_video WHERE teacher_id=".$teacherId;
        //$where = "oid IN($inSql) AND is_del = 0";
        $where['tid'] = $teacherId;
        $data = M("zy_review")->where($where)->order('ctime desc')->findPage(10);
		
        if($data['data'] && $data['gtLastPage'] !== true) {
			$zyVoteMod = D('ZyVote','classroom');
            foreach ($data['data'] as $key => $value) {
				$data['data'][$key]['strtime'] = friendlyDate($value['ctime']);
                $data['data'][$key]["course_title"]=M("zy_video")->where("id=".$value["oid"])->getField('video_title');
                $data['data'][$key]["username"] = getUsername($value['uid']);
                $data['data'][$key]['userface'] = getUserFace($value['uid'],'m');
                $data['data'][$key]["skill"] = (int)$data['data'][$key]["skill"] ? : 1;
                $data['data'][$key]["professional"] = (int)$data['data'][$key]["professional"] ? : 1;
                $data['data'][$key]["attitude"] = (int)$data['data'][$key]["attitude"] ? : 1;
                $data['data'][$key]["count"] = (int)M("zy_review")->where(array('parent_id'=>$value['id']))->count();
				$data['data'][$key]['star']     = round($value['star']/20);
                //判断时候已经投票了
                $data['data'][$key]['isvote']   = $zyVoteMod->isVote($value['id'],'zy_review',$this->mid) ? 1:0;
				unset($data['data'][$key]['is_del'],$data['data'][$key]['yong'],$data['data'][$key]['tid']);
            }
            $this->exitJson($data['data'],1);
        }
        $this->exitJson([],0,'暂时没有评价');
    }
    /**
     * @name 获取讲师相册
     */
    public function getTeacherPhotos(){
        $teacherId = intval($this->data['teacher_id']);//获取讲师id
        if(!$teacherId){
            $this->exitJson( array() ,10005,"没有该讲师信息");
        }
        $data = D('ZyTeacherPhotos','classroom')->getPhotosAlbumByTid($teacherId);
        if($data['data'] && $data['gtLastPage'] !== true) {
            $this->exitJson($data['data'],1);
        }
        $this->exitJson([],0,'暂时没有相册');
    }
    /**
     * @name 获取讲师相册内的数据
     */
    public function getTeacherPhotosInfo(){
        $teacherId = intval($this->data['teacher_id']);//获取讲师id
        if(!$teacherId){
            $this->exitJson( array() ,10005,"没有该讲师信息");
        }
        $photos_id = intval($this->data['photos_id']);//获取讲师id
        if(!$photos_id){
            $this->exitJson( array() ,10005,"没有该讲师相册信息");
        }
        $photos = D('ZyTeacherPhotos','classroom')->getPhotosAlbumInfo($photos_id,$teacherId);
        if($photos){
            $list = D('ZyTeacherPhotos','classroom')->getPhotoDataByPhotoId($photos_id);
            if($list['data'] && $list['gtLastPage'] !== true){
                foreach($list['data'] as $k=>$val){
                    ($val['type']==1) && $list['data'][$k]['cover'] = getCover($val['resource'],'auto','auto');
                    ($val['type']==2) && $list['data'][$k]['cover'] = getCover($val['cover'],'auto','auto');
                }
                $info = $list['data'];
            }else{
                $info = [];
            }
        }
        $info ? $this->exitJson($info,1) : $this->exitJson([],0,'未能获取到相册信息');
    }
    
    /**
     * @name 预约试听
     */
    public function bespeak(){
        if(!$this->mid){
             $this->exitJson((object)array(),0,'请登录后再预约');
        }
        $tid = intval($this->teacher_id);
        if(!$tid){
            $this->exitJson((object)array(),0,'请选择你要预约的讲师');
        }
        $type = t($this->type);
        if(!in_array($type,array('online','offline'))){
            $this->exitJson((object)array(),0,'请选择正确的预约试听方式');
        }
        
        if ($type == "offline") {
            $type = 2;
            $teacherInfo = M('zy_teacher')->where('id =' . $tid)->field(array('offline_price','name'))->find();
            $money = $teacherInfo['offline_price'];
            $tname = $teacherInfo['name'];
            $title = $tname . "讲师的线下试听";
        } else{
            $type = 1;
            $teacherInfo = M('zy_teacher')->where('id =' . $tid)->field(array('online_price','name'))->find();
            $money = $teacherInfo['online_price'];
            $tname = $teacherInfo['name'];
            $title = $tname . "讲师的在线试听";
        }
        $tdata = array(
            'type'       => $type,
            'price'      => $money,
            'ctime'      => time(),
            'uid'        => $this->mid,
            'pay_status' => 1,//默认未支付
            'tid'        => $tid,
            'words'      => t($this->word)
        );
        if ($money == 0 || $money == '0.00') {
            //免费
            $tdata['pay_status'] = 3;
            $res = M('zy_order_teacher')->add($tdata);
            if($res){
                $this->exitJson(array('teacher_id'=>$tid,'is_free'=>1),1,'预约成功');
            }else{
                $this->exitJson((object)array(),0,'预约失败');
            }
        }else{
            $res = M('zy_order_teacher')->add($tdata);
            //需要收费
            $re = D('ZyRecharge','classroom');
            $pay_id = $re->addRechange(array(
                'uid' => $this->mid,
                'type' => 1,
                'money' => $money,
                'note' => "预约：{$title}",
                'pay_type' => $this->pay_for,
            ));
            if(in_array($this->pay_for,array('alipay','wxpay'))){
                if($this->pay_for == 'alipay'){
                    $pay_data['alipay']  = $this->alipay(array(
                        'out_trade_no' => $pay_id,
                        'type'         => $type,
                        'tid'          => $tid,
                        'total_fee'    => $money,
                        'order_id'     => $res,
                        'subject'      => "预约：{$title}",
                    ));
                }elseif($this->pay_for == 'wxpay'){
                    $pay_data['wxpay'] = $this->wxpay([
                        'out_trade_no' => $pay_id,
                        'type'         => $type,
                        'tid'          => $tid,
                        'total_fee'    => $money,
                        'order_id'     => $res,
                        'subject'      => "预约：{$title}",
                    ]);
                }
            }else{
                $pay_data['alipay']  = $this->alipay(array(
                    'out_trade_no' => $pay_id,
                    'type'         => $type,
                    'tid'          => $tid,
                    'total_fee'    => $money,
                    'order_id'     => $res,
                    'subject'      => "预约：{$title}",
                ));
                $pay_data['wxpay'] = $this->wxpay([
                    'out_trade_no' => $pay_id,
                    'type'         => $type,
                    'tid'          => $tid,
                    'total_fee'    => $money,
                    'order_id'     => $res,
                    'subject'      => "预约：{$title}",
                ]);
            }
            $pay_data['is_free'] = 0;
            $this->exitJson($pay_data,1);
        }
    
    }
    /**
     * 微信支付
     */
    protected function wxpay($data){
        require_once SITE_PATH.'/api/pay/wxpay/WxPay.php';
        $input = new WxPayUnifiedOrder();
        $attr  = json_encode(array('type'=>$data['type'],'total_fee'=>$data['total_fee'],'tid'=>$data['tid']));
        $body  = isset($data['subject']) ? $data['subject'] :"跟我学-购买";
        $out_trade_no = $data['out_trade_no'].'h'.date('YmdHis',time()).mt_rand(1000,9999);//stristr
        $input->SetBody($body);
        $input->SetAttach($attr);//自定义数据
        $input->SetDevice_info('APP');
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($data['total_fee'] * 100);
        $input->SetNotify_url('http://'.$_SERVER['HTTP_HOST'].'/api/pay/wxpay/notify.php');
        $input->SetTrade_type('APP');
        $notify = new NativePay();
        $result = $notify->GetPayUrl($input);
        if(!$result['prepay_id']){
            $this->exitJson((object)[],0,'暂不能使用微信支付');
        }
        $input = array(
			'noncestr'  => ''.$result['nonce_str'],
			'prepayid'  => ''.$result['prepay_id'], //下单时微信服务器得到nonce_str和prepay_id参数。
			'appid'     => WxPayConfig::APPID,
			'package'   => 'Sign=WXPay',
			'partnerid' => WxPayConfig::MCHID,
			'timestamp' => time(),
        );
        
        $input['sign'] = $this->wxSignWithMd5($input,WxPayConfig::KEY);
        
        $iosPay = sprintf($this->clientpay_url, $input['appid'], $input['noncestr'], $input['partnerid'], $input['prepayid'], $input['timestamp'], $input['sign']);
        $return = [
            'ios' => $iosPay,
            'public' => $input
        ];
        return $return;
    }
    //微信sign加密方法
    private function wxSignWithMd5($param, $wxkey)
    {
        ksort($param);
        $sign = '';
        foreach ($param as $key => $value) {
            if ($value && $key != 'sign' && $key != 'key') {
                $sign .= $key.'='.$value.'&';
            }
        }
        $sign .= 'key='.$wxkey;
        $sign = strtoupper(md5($sign));

        return $sign;
    }
    /**
     * @name 阿里支付
     * @packages protected
     */
    protected function alipay($args){
        $alipay_config = $this->getAlipayConfig();
        //初始化类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','Alipay.php')));
        $alipayClass = new \Alipay($alipay_config);
        //设置支付的Data信息
        $alipayClass->setConfig(array(
            "title" => $args['subject'],//订单名称
            "body"  => $args['subject'],//订单描述,
            "out_trade_no"  => $args['out_trade_no'].'h'.date('YmdHis',time()).mt_rand(1000,9999),//商户网站订单系统中唯一订单号，必填
            "subject"   => $args['subject'],//订单名称
            "total_fee" => (string)$args['total_fee'],//付款金额
            "input_charset"    => trim(strtolower($alipay_config['input_charset'])),
            "notify_url"      => 'http://'.strip_tags($_SERVER['HTTP_HOST']).'/alipayBespeakAnsy.html',
            "transport"       => "http"
        ));
        //追加参数信息
        $alipayClass->addData('extra_common_param',json_encode(array('type'=>$args['type'],'tid'=>$args['tid'])));
        //调用API服务
        return $alipayClass->goAliService('api');
    }
    
    public function addReview(){
        $tid = (int)$this->teacher_id;
        if(!$this->mid){
            $this->exitJson( array() ,0,'评价需要先登录');
        }
        if(!$tid){
            $this->exitJson( array() ,0,'请选择要评价的讲师');
        }
        //每个人只能点评一次
        $count = M('ZyReview')
            ->where(
                array(
                    'oid'=>0,
                    'parent_id'=>0,
                    'pid'=>0,
                    'uid'=>$this->mid,
                    'type'=>1,
                    'tid'=>$tid
                )
            )->count();
        if($count){
            $this->exitJson( array() ,0,'你已经点评了');
        }

        $data['parent_id']           = 0;
        $data['pid']                 = 0;
        $data['star']		         = intval($this->data['score'])*20;//分数
        $data['type']		         = 1;
        $data['uid'] 			     = intval($this->mid);
        $data['is_secret'] 			 = intval($this->data['is_secret']);
        $data['oid'] 			     = 0;
        $data['review_source'] 	     = '手机客户端';
        $data['review_description']  = filter_keyword(t($this->data['content']));
        $data['ctime']			     = time();
        $data['tid'] = $tid;
        $data['skill'] = in_array((int)$this->data['skill'],[1,2,3]) ? (int)$this->data['skill'] : 1;
        $data['professional'] = in_array((int)$this->data['professional'],[1,2,3]) ? (int)$this->data['professional'] : 1;
        $data['attitude'] = in_array((int)$this->data['attitude'],[1,2,3]) ? (int)$this->data['attitude'] : 1;
        
        if(!$data['star']){
            $this->exitJson( array() ,0,'请给讲师打分');
        }
        if(!$data['review_description']){
            $this->exitJson( array() ,0,'请输入评价内容');
        }

        $i = M('ZyReview')->add($data);
        if($i){
            //点评之后 要计算此套餐的总评分
            $star = M('ZyReview')->where(array('tid'=>$tid))->Avg('star');
            $star = round($star/20);
            //操作积分
            model('Credit')->getCreditInfo($this->mid,29);
            $this->exitJson(array('teacher_id'=>$tid,'comment_star'=>$star),1,'点评成功');
        }else{
            $this->exitJson( array() ,0,'点评失败');
        }
    }
}