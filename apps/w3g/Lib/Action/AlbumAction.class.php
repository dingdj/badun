<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class AlbumAction extends Action
{
	protected $album = null; //课程模型对象
    protected $category = null; //分类数据模型
    protected $albumdata=null;
    protected $review=null;
	//初始化
	public function _initialize() { 
        $this->album = D('Album',"classroom");
        $this->review = D('ZyReview',"classroom");
        $this->category = model('VideoCategory');
        $type=intval($_GET['type']);//获取播放类型
        if($type==2){
	        $aid=intval($_GET['aid']);
	        $sVideos = $this->album->getVideoId($aid);
	        $sVideos = getCsvInt($sVideos);
	        $data = M("ZyVideo")->where(array('id' => array('in', (string) $sVideos),'is_del'=>0))->select();
	        foreach ($data as &$value) {
	            $value['mzprice'] = $value['price'] ? $value['price'] : getPrice($value, $this->mid, true, true);
	            $value['isBuyVideo'] = isBuyVideo($this->mid, $value['id']) ? 1 : 0;
	            $is_colle=D('ZyCollection')->where(array('uid'=>$this->mid,'source_id'=>$value['id'],'source_table_name'=>'zy_video'))->find();
	            if($is_colle){
	                $value['is_colle']=1;
	            }else{
	                $value['is_colle']=0;
	            }
	        }
        }
        $this->albumdata = $data;
	}
	public function index() {
		$cateId = intval($_GET['cateId']);
        $selCate = $this->category->getTreeById($cateId, 2);
        $datalist=array();
        foreach($selCate['list'] as &$val){
            $val['childlist']=$this->category->getChildCategory($val['zy_video_category_id'],2);
            array_push($datalist,$val);
        }
        $this->assign('selCate', $datalist);
        $this->display();
	}
	public function view() {
        $id = intval($_GET['id']);
        $map['id'] = array('eq', $id);
        //获取班级列表
        $sVideos = $this->album->getVideoId($id);
        $sVideos = getCsvInt($sVideos);
        $sql = 'SELECT `id`,`video_title` FROM ' . C("DB_PREFIX") . 'zy_video WHERE `id` IN (' . (string) $sVideos . ') and is_del=0 ORDER BY find_in_set(id,"' . (string) $sVideos . '")';
        $data = M('')->query($sql);   
        $this->assign("sVideos",$data);
        $data = $this->album->where($map)->find();
        if (!$data) {
            $this->assign('isAdmin', 1);
            echo "<script>alert('班级不存在');history.go(-1);</script>";
        }
        //处理数据
        $data['album_score'] = floor($data['album_score'] / 20); //四舍五入
        //获取评论数
        $data['reviewCount'] = $this->review->getReviewCount(2, intval($data['id']));
        $data['album_title'] = msubstr($data['album_title'], 0, 24);
        $data['album_intro'] = msubstr($data['album_intro'], 0, 100);
        //班级分类
        $data['album_category_name'] = getCategoryName($data['album_category'], true);
        $data['str_tag'] = array_chunk(explode(',', $data['str_tag']), 3, false);

        //是否收藏
        $data['iscollect'] = D('ZyCollection',"classroom")->isCollect($data['id'], 'album', intval($this->mid));
        
        //检查一个用户的余额/冻结的数量是否够支配
        $data['isSufficient'] = D('ZyLearnc',"classroom")->isSufficient($this->mid, $data['mzprice']['price']);
        //查询资源是否存在
        $data['isGetResource'] = isGetResource(2, $data['id'], array('video', 'upload', 'note', 'question'));

        $album_video = D('Album')->getVideoId($id);
        //获取班级价格
        $data['mzprice'] = $this->album->getAlbumMoeny( $album_video ,$this->mid);
        //查询所有讲师的id
        $tids = D('zyVideo')->where(array('id'=>array('IN',trim( $album_video ,',')),'teacher_id'=>array('NEQ','0')))->field('teacher_id')->select();
        foreach($tids as $key=>$val){
            $tids[$key] = $val['teacher_id'];
        }

        $tids=array_flip(array_flip($tids));//去掉重复讲师id
        $tidr=implode(",",$tids);
        $tdata=D('ZyTeacher')->where(array('id'=>array('IN',trim($tidr,","))))->field("id,name,inro,head_id")->select();
        //获取当前用户可支配的余额
        $data['balance'] = D("zyLearnc","classroom")->getUser($this->mid);
        $data['is_buy'] = D("ZyOrder","classroom")->isBuyAlbum($this->mid, $id);
        $this->assign('trlist',$tdata);
        $this->assign('data', $data);
        $this->assign('id', $id);
        $this->assign('uid',$this->mid);
        $this->display();
    }

    /**
     * 班级观看页面
     */
    public function watch() {
        include SITE_PATH . '/api/cc/spark_config.php';
        $this->assign('sp_config', $spark_config);
        $aid = intval($_GET['aid']);
        $type = intval($_GET['type']); //数据分类 1:课程;2:班级;
      
        if ($type == 1) { //课程
            $data = M("ZyVideo")->where(array('id' => array('eq', $aid)))->select();
            $data[0]['mzprice'] = getPrice($data[0], $this->mid, true, true);
            $data[0]['isBuyVideo'] = isBuyVideo($this->mid, $data[0]['id']) ? 1 : 0;
            $is_colle=D('ZyCollection')->where(array('uid'=>$this->mid,'source_id'=>$data[0]['id'],'source_table_name'=>'zy_video'))->find();
            if($is_colle){
            	$data[0]['is_colle']=1;
            }else{
            	$data[0]['is_colle']=0;
            }
            if (!isset($data[0]) && !$data[0]) {
                $this->assign('isAdmin', 1);
                echo "<script>alert('课程不存在');history.go(-1);</script>";
            }
          
        } else {
        	$data=$this->albumdata;
            if (!isset($data[0]) && !$data[0]) {
                $this->assign('isAdmin', 1);
                echo "<script>alert('没有要播放的视频');history.go(-1);</script>";
            }
        }
        //判断是否是限时免费
        $is_free=0;
        if($data[0]['is_tlimit']==1 && $data[0]['starttime'] < time() && $data[0]['endtime'] > time() && $data[0]['limit_discount']==0.00){
            $is_free=1;
        }

        if( floatval( $data[0]['price'] ) <= 0 || ( intval($data['vip_level'] ) && $data['user_vip_level'] > intval($data['vip_level'] ) ) )  {
        	$is_free = 1;
        }
        $is_buy = D("ZyOrder")->isBuyAlbum($this->mid, $aid);
        //查询登录
        $isadmin=D('UserGroupLink')->where(array('user_group_id'=>1,'uid'=>$this->mid))->find();
        //限时免费/已购买/上传者是自己/管理员  不用购买课程
        if($data[0]['isBuyVideo']==1 || model('UserGroup')->isAdmin($uid) ||$isadmin){
            $is_free = 1;
        }
        $test_time=getAppConfig("video_free_time");
        $balance = D("zyLearnc","classroom")->getUser($this->mid);
        $this->assign("video_address",$jmstr);
        $this->assign("test_time",$test_time);
        $this->assign('balance', $balance);
        $this->assign('is_free',$is_free);
        $this->assign('vid', $data[0]['id']);
        $this->assign('video_id', $data[0]['video_id']);
        $this->assign('video_title', $data[0]['video_title']);
        $this->assign('video_intro', $data[0]['video_intro']);
        $this->assign('video_order_count', $data[0]['video_order_count']);
        $this->assign('price', $data[0]['mzprice']['oriPrice']);
        $this->assign('is_colle',$data[0]['is_colle']);
        $this->assign('isBuyVideo', $data[0]['isBuyVideo']);
        $this->assign('utime', $data[0]['utime']);
        $this->assign('listingtime',$data[0]['listingtime']);
        $this->assign('cover',$data[0]['cover']);
        $this->assign("score",$data[0]['video_score']/20);
        $this->assign('data', $data);
        $this->assign('is_buy', $is_buy);
        $this->assign('aid', $aid);
        $this->assign('type', $type);
        $this->assign('isphone', isMobile() ? 1 : 0);
        $this->assign('mzbugvideoid', session('mzbugvideoid'));
        $this->assign('mid',$this->mid);
        $this->display();
    }
    /**
     * 同步播放视频
     */
    public function synvideo(){
    	$vid = intval($_GET['vid']);
    	$aid=intval($_GET['aid']);
    	$type=intval($_GET['type']);
    	$data = M("ZyVideo")->where(array('id' => array('eq', $vid)))->select();
    	$data[0]['mzprice'] = getPrice($data[0], $this->mid, true, true);
    	$is_colle=D('ZyCollection')->where(array('uid'=>$this->mid,'source_id'=>$data[0]['id'],'source_table_name'=>'zy_video'))->find();
    	if($is_colle){
    		$data[0]['is_colle']=1;
    	}else{
    		$data[0]['is_colle']=0;
    	}
    	if (!isset($data[0]) && !$data[0]) {
    		$this->assign('isAdmin', 1);
    		echo "<script>alert('课程不存在');history.go(-1);</script>";
    	}
    	//判断是否是限时免费
    	$is_free=0;
    	if($data[0]['is_tlimit']==1 && $data[0]['starttime'] < $nowtime && $data[0]['endtime'] > $nowtime && $data[0]['limit_discount']==0.00){
    		$is_free=1;
    	}
    	
    	//限时免费/已购买/上传者是自己/管理员  不用购买课程
    	if($data[0]['isBuyVideo']==1 || is_admin($this->mid) || intval($data[0]['mzprice']['oriPrice']) == 0 ){
    		$is_free = 1;
    	}
        $data[0]['isBuyVideo']= isBuyVideo($this->mid, $data[0]['id']) ? 1 : 0;
    	$test_time=getAppConfig("video_free_time");
    	$this->assign("video_address",$jmstr);
    	$this->assign("is_free",$is_free);
    	$this->assign("test_time",$test_time);
    	$this->assign('vid', $data[0]['id']);
    	$this->assign('video_id', $data[0]['video_id']);
    	$this->assign('video_title', $data[0]['video_title']);
    	$this->assign('video_order_count', $data[0]['video_order_count']);
    	$this->assign('price', $data[0]['mzprice']['oriPrice']);
    	$this->assign('is_colle',$data[0]['is_colle']);
    	$this->assign('isBuyVideo', $data[0]['isBuyVideo']);
    	$this->assign('utime', $data[0]['utime']);
    	$this->assign('listingtime',$data[0]['listingtime']);
    	$this->assign('cover',$data[0]['cover']);
    	$this->assign("score",$data[0]['video_score']/20);
    	$this->assign('data', $this->albumdata);
        $this->assign("aid",$aid);
    	$this->assign('aid', $aid);
    	$this->assign('type', $type);
    	$this->assign('album_address', $data[0]['video_address']);
        $this->assign('video_intro', $data[0]['video_intro']);
    	$this->display("watch");
    }
	 /**
     * 取得课程列表
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
        //排序
        $order = 'id DESC';
        $time = time();
        $where = "is_del=0 AND uctime>$time AND listingtime<$time";
        $_GET['cateId'] = intval($_GET['cateId']);
        if ($_GET['cateId'] > 0) {
            $idlist = implode(',', $this->category->getVideoChildCategory($_GET['cateId'], 2));
            if ($idlist)
                $where .= " AND album_category IN($idlist)";
        }
        $data = $this->album->where($where)->order($order)->findPage(30);
        //计算总课程
        foreach($data['data'] as &$val){
            $video_ids = $this->album->getVideoId($val['id']);
            $vids = D('zyVideo')->where(array('id'=>array('IN',$video_ids),'is_del'=>0))->field('id')->select();
            $val['video_cont'] = count($vids);
            //获取班级价格
            $val['mzprice'] = $this->album->getAlbumMoeny( $video_ids ,$this->mid);
            //格式化班级评分
            $val['score']    = round($val['album_score']/20);
        }
        if ($data['data']) {
            $this->assign('listData', $data['data']);
            $this->assign('cateId',$_GET['cateId']);//定义分类
            $html = $this->fetch('index_list');
        } else {
            $html = $this->fetch('index_list');
        }
        $data['data'] = $html;
        if ($return) {
            return $data;
        } else {
            echo json_encode($data);
            exit();
        }
    }
    /**
     * 购买班级操作
     */
    public function buyOperating() {
        if(!$this->mid){
            exit(json_encode(array('status'=>'0','info'=>'请先登录!')));
        }
        if (!$_POST['id']) {
            exit(json_encode(array('status'=>'0','info'=>'没有选择班级!')));
        }
        if (isBuyAlbum($this->mid, $_POST['id'])) {
            exit(json_encode(array('status'=>'0','info'=>'您已经买了本班级!')));
        }
        $album = D("Album","classroom")->getAlbumById($_POST['id']);
        $albumId = intval($_POST['id']);
        $video_ids = trim(D("Album","classroom")->getVideoId(intval($_POST['id'])), ',');
        $map['id'] = array('in', array($video_ids));
        $map["is_del"]=0;
        $album_info = M("zy_video")->where($map)->select();
        $illegal_count = 0;
        $total_price = 0;
        foreach ($album_info as $key => $video) {
            $album_info[$key]['price'] = getPrice($video, $this->mid, true, true);
            //价格为0的/限时免费的  不加入购物记录
            if($album_info[$key]['price']['price'] == 0){
                unset($album_info[$key]);
                continue;
            }
            $album_info[$key]['is_buy'] = D("ZyOrder","classroom")->isBuyVideo($this->mid, $video['id']);
            $total_price += ($album_info[$key]['is_buy'] || $video['uid'] == $this->mid) ? 0 : round($album_info[$key]['price']['price'], 2);
            if ($video['uctime'] < time()) {
                $illegal_count += 1;
                $video_id=$video['id'];
            }
        }
        if ($illegal_count > 0) {
            exit(json_encode(array('status'=>'0','info'=>'班级中包含有过期的课程，无法整辑购买!')));
        }

        if (!D('ZyLearnc',"classroom")->isSufficient($this->mid, $total_price, 'balance')) {
            exit(json_encode(array('status'=>'0','info'=>'可支配的学币不足!')));
        }
        //无过期非法信息则付款
        $pay_result = D("ZyService","classroom")->buyAlbum($this->mid, intval($_POST['id']), $total_price);
        if ($pay_result['status'] != '1') {
            exit(json_encode(array('status' => '0', 'info' =>"购买失败！")));
        }
        //订单数量加1
        M()->query("UPDATE `".C('DB_PREFIX')."album` SET `album_order_count`=`album_order_count`+1 WHERE `id`=$albumId");
        //添加消费记录
        M('ZyLearnc',"classroom")->addFlow($this->mid, 0, $total_price, $note = '购买班级<' . $album['album_title'] . '>', $pay_result['rid'], 'zy_order_album');
       //添加订单记录到课程
        $sql="update `".C('DB_PREFIX')."zy_video`  set video_order_count=video_order_count+1 where `id` in('$video_ids')";
        M()->query($sql);
        //添加班级中的课程购买记录
        $insert_value = "";
        foreach ($album_info as $key => $video) {
            if (!$video['is_buy']) {
                if($video['uid'] != $this->mid){
                    $insert_value .= "('" . $this->mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $pay_result['rid'] . "','0'," . time() . ",0),";
                }
            }
        }
        if(!empty($insert_value)){
            $query = "INSERT INTO " . C("DB_PREFIX") . "zy_order (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`ctime`,`is_del`) VALUE " . trim($insert_value, ',');
            $table = new Model();
            if ($table->query($query) !== false || $total_price == 0) {
                exit(json_encode(array('status'=>'0','info'=>'购买班级成功!')));
                foreach ($album_info as $key => $video) {
                    if (!$video['is_buy'] && ($this->mid != $video['uid'])) {
                        $rvid = M("zy_order")->where('video_id=' . $video['id'])->field("id")->find();
                        //添加消费记录
                        M('ZyLearnc',"classroom")->addFlow($video['uid'], 5, $video['price']['price'], $note = '卖出课程<' . $video['video_title'] . '>', $rvid['id'], 'zy_order_album');
                    }
                }
            }
        }
        else {
            $albumname= D("Album","classroom")->getAlbumTitleById($_POST['id']);
            $s['uid']=$this->mid;
            $s['title'] = "恭喜您购买班级成功";
            $s['body'] = "恭喜您成功购买班级：《".$albumname."》";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);
            exit(json_encode(array('status'=>'1','info'=>'购买班级成功!')));
        }
    }
}