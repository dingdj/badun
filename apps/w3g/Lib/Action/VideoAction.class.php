<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class VideoAction extends Action
{
	protected $video = null; // 课程模型对象
    protected $category = null; // 分类数据模型
	//初始化
	public function _initialize() { 
        $this->video = D ( 'ZyVideo' );
        $this->category = model ( 'VideoCategory' );
	}
	public function index() {
		$cateId = intval ( $_GET ['cateId'] );
        $selCat = $this->category->getTreeById ( $cateId, 1 );
        // 循环取出所有下级分类
        $datalist = array ();
        foreach ( $selCat ['list'] as &$val ) {
            $val ['childlist'] = $this->category->getChildCategory ( $val ['zy_video_category_id'], 1 );
            array_push ( $datalist, $val );
        }
        $this->assign ( 'selCate', $datalist );
        $this->assign ( 'mid', $this->mid );
        $this->display ();
	}
	/**
     * 课程详情页面
     * 
     * @return void
     */
    public function view() {
        $id = intval ( $_GET ['id'] );
        $map ['id'] = array (
                'eq',
                $id 
        );
        $data = D ( 'ZyVideo' )->where ( $map )->find ();
        if (! $data) {
            $this->assign ( 'isAdmin', 1 );
            echo "<script>alert('课程不存在');history.go(-1);</script>";
        }
        if($data['is_tlimit']==1 && $data['starttime'] < time() && $data['endtime'] > time() ){
            $data['is_tlimit']=1;
        }else{
            $data['is_tlimit']=0;
        }
        // 处理数据
        $data ['video_score'] = floor ( $data ['video_score'] / 20 ); // 四舍五入
        $data ['reviewCount'] = D ( 'ZyReview',"classroom" )->getReviewCount ( 1, intval ( $data ['id'] ) );
        $data ['video_category_name'] = getCategoryName ( $data ['video_category'], true );
        $data ['iscollect'] = D ( 'ZyCollection',"classroom" )->isCollect ( $data ['id'], 'zy_video', intval ( $this->mid ) );
        $data ['mzprice'] = getPrice ( $data, $this->mid, true, true );
        $data ['isSufficient'] = D ( 'ZyLearnc',"classroom" )->isSufficient ( $this->mid, $data ['mzprice'] ['price'] );
        $data ['isGetResource'] = isGetResource ( 1, $data ['id'], array (
                'video',
                'upload',
                'note',
                'question' 
        ) );
        $data ['user'] = M("zy_teacher")->where("id=".$data["teacher_id"])->find();
        // 课程标签
        $data ['video_str_tag'] = array_chunk ( explode ( ',', $data ['str_tag'] ), 3, false );
        
        // 是否已经加入了购物车
        $this->assign ( 'hasVideo', D ( 'ZyVideoMerge',"classroom" )->hasVideo ( $id, $this->mid, session_id () ) );
        $data['balance'] = D("zyLearnc","classroom")->getUser($this->mid);
        // 是否已购买
        $data['is_buy'] = D('ZyOrder','classroom')->isBuyVideo($this->mid ,$id );
        $this->assign ( 'id', $id );
        $this->assign ( 'jiam_str', sunjiami("{$id},zy_video","in_link") );
        $this->assign ( 'data', $data );
        $this->display ();
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
        if( floatval( $data[0]['v_price'] ) <= 0 ) {
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
     * 取得班级目录----课程标题
     * @param int $return 
     * @return void|array
     */
    public function getcatalog() {
        $limit = intval($_POST['limit']);
        $id = intval($_POST['id']);

        $sVideos = $this->album->getVideoId($id);
        $sVideos = getCsvInt($sVideos);
        $sql = 'SELECT `id`,`video_title` FROM ' . C("DB_PREFIX") . 'zy_video WHERE `id` IN (' . (string) $sVideos . ') and is_del=0 ORDER BY find_in_set(id,"' . (string) $sVideos . '")';
        $data = M('')->query($sql);        
        $this->assign('data', $data);
        $result = $this->fetch('_MuLu');
        exit( json_encode($result) );
    }
	/**
     * 取得课程列表
     * 
     * @param boolean $return
     *          是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
        // 销量和评论排序
        $orders = array (
                'default' => 'video_order_count DESC,video_score DESC,video_comment_count DESC',
                'saledesc' => 'video_order_count DESC',
                'saleasc' => 'video_order_count ASC',
                'scoredesc' => 'video_score DESC',
                'scoreasc' => 'video_score ASC' 
        );
        if (isset ( $orders [$_GET ['orderBy']] )) {
            $order = $orders [$_GET ['orderBy']];
        } else {
            $order = $orders ['default'];
        }
        
        $time = time ();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $_GET ['cateId'] = intval ( $_GET ['cateId'] );
        if ($_GET ['cateId'] > 0) {
            $idlist = implode ( ',', $this->category->getVideoChildCategory ( intval ( $_GET ['cateId'] ), 1 ) );
            if ($idlist)
                $where .= " AND video_category IN($idlist)";
        }

        if ($_GET ['pType'] == 3 || $_GET ['pType'] == 2) {
            $oc = $_GET ['pType'] == 3 ? '>' : '=';
            if (vipUserType ( $this->mid ) > 0) {
                $vd = floatval ( getAppConfig ( 'vip_discount', 'basic', 10 ) );
                $mvd = floatval ( getAppConfig ( 'master_vip_discount', 'basic', 10 ) );
                $isVip = 1;
            } else {
                $isVip = 0;
            }
            // 查询价格 $oc 于0的数据，当在限时折扣的时候
            $ptWhere = "(is_tlimit=1 AND starttime<{$time} AND endtime>{$time} AND t_price{$oc}0)";
            // 如果是VIP，那么则查询价格 $oc 于0的数据，当不在限时折扣的时候
            if ($isVip) {
                $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (is_offical=1 AND v_price*{$mvd}/10{$oc}0) OR (is_offical=0 AND v_price*{$vd}/10{$oc}0))";
            }
            // 查询价格 $oc 于0的数据，当不在限时折扣并且当前用户不是VIP的时候
            $ptWhere .= " OR ((is_tlimit<>1 OR starttime>{$time} OR endtime<{$time}) AND (0={$isVip}) AND v_price{$oc}0)";
            $where .= " AND ({$ptWhere})";
        }
        
        $size = intval ( getAppConfig ( 'video_list_num', 'page', 9 ) );
        $data = $this->video->where ( $where )->order ( $order )->findPage ( $size );
        if ($data ['data']) {
            $buyVideos = D ( 'zyOrder',"classroom" )->where ( "`uid`=" . $this->mid . " AND `is_del`=0" )->field ( 'video_id' )->select ();
            foreach ( $buyVideos as $key => &$val ) {
                $val = $val ['video_id'];
            }
            // 计算价格
            foreach ( $data ['data'] as $key => &$value ) {
                $value['mzprice'] = getPrice ( $value, $this->mid, true, true );
            }
            $this->assign ( 'buyVideos', $buyVideos );
            $vms = D ( 'ZyVideoMerge',"classroom" )->getList ( $this->mid, session_id () );
            $this->assign ( 'vms', getSubByKey ( $vms, 'video_id' ) );
            $this->assign ( 'listData', $data ['data'] );
            $this->assign ( 'orderBy', $_GET ['orderBy'] ); // 定义排序
            $this->assign ( 'cateId', $_GET ['cateId'] ); // 定义分类
            $this->assign ( 'pType', $_GET ['pType'] ); // 定义收费类型
            $html = $this->fetch ( 'index_list' );
        } else {
            $html = '暂无此类课程';
        }
        
        $data ['data'] = $html;
        
        if ($return) {
            return $data;
        } else {
            echo json_encode ( $data );
            exit ();
        }
    }
    // 购买课程
    /*
     * 1:可以直接观看，用户为管理员，限时免费,价格为0，已经购买过了
     * 2:找不到课程
     * 3:余额扣除失败，可能原因是余额不足
     * 4:购买记录/订单，添加失败
     */
    public function buyOperating() {
        if (! $this->mid)
            U('w3g/Passport/login','',true);
        $vid = intval ( $_POST ['id'] );
        $i = D ( 'ZyService',"classroom" )->buyVideo ( intval ( $this->mid ), $vid );
        if ($i === true) {
            // 记录购买的课程的ID
            session ( 'mzbugvideoid', $vid );
            exit(json_encode(array('status'=>'1','info'=>'购买成功!')));
        }
        if ($i === 1) {
            exit(json_encode(array('status'=>'0','info'=>'该课程你不需要购买!')));
        } else if ($i === 2) {
            exit(json_encode(array('status'=>'0','info'=>'找不到课程!')));
        } else if ($i === 3) {
            exit(json_encode(array('status'=>'0','info'=>'余额不足!')));
        } else if ($i === 4) {
            exit(json_encode(array('status'=>'0','info'=>'购买失败!')));
        }
    }
    /**
     * 获取限时免费列表
     */
    public function limit(){
        $limit = 30;
        $orders =' ORDER BY `ctime` DESC';
        $where = ' WHERE `is_del` = 0 AND `limit_discount`=0.00 AND `is_activity` = 1 AND (`uctime` > '.time().' AND listingtime < '.time().') AND `is_tlimit` = 1 AND (`starttime` < '.time().' AND `endtime` > '.time().')';
        $map['is_del'] = '0';
        $map['is_activity'] = '1';
        $map['uctime'] = array('GT',time());
        $map['listingtime'] = array('LT',time());
        $map['is_tlimit'] = '1';
        $map['limit_discount']=0.00;
        $map['starttime'] = array('LT',time());
        $map['endtime'] =array('GT',time());
        $sql = "SELECT * FROM ".C('DB_PREFIX').'zy_video'.$where.$orders;
        $data = M('')->query($sql);
        $this->assign("data",$data);
        $this->display();
    }
}