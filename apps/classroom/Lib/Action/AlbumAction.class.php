<?php
/**
 * 云课堂点播(班级)控制器
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class AlbumAction extends CommonAction {
    protected $album = null; //课程模型对象
    protected $category = null; //分类数据模型
    protected $albumdata=null;
    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        $this->album = D('Album');
        $this->category = model('VideoCategory');
    }


    /**
     * 课程班级班课
     */
    public function index() {
        $cateId = intval($_GET['cid']);

        $cid = intval(t($_GET['cid']));
        $tid = intval(t($_GET['tid']));
        $vid = intval(t($_GET['vid']));
        $time = intval(t($_GET['ctime']));

        $map = ' `status`=1 AND `is_mount`=1 AND `is_del`=0';
        $cid && $map .= ' AND FIND_IN_SET('.$cid.', album_category) ';
        if(!empty($time)){
            $ctime = strtotime($time.'-1-1 0:0:0');
            $map.= ' AND `ctime` < "'.strtotime('+1 year',$ctime).'" AND `ctime` >= "'.strtotime('-1 year',$ctime).'"' ;
        }

        if(!empty($vid)){
            $map.= ' AND `vip_level` = "'.$vid.'"';
        }

        /*TODO:讲师，会员类型、班型。先读取满足条件的课程，再读取班级*/
        if(!empty($tid)){
            $vMap['is_del'] = 0;
            $vMap['teacher_id'] = $tid;
            $video_teacher_list = M('zy_video')->where($vMap)->field('id')->limit(100)->select();

            $video_ids = getUniqure(getSubByKey($video_teacher_list,'id'));
            if($video_ids){
                $linkMap['video_id'] = array('in',$video_ids);
                $albums = M('album_video_link')->where($linkMap)->field('album_id')->limit(100)->select();
                $album_ids = getUniqure(getSubByKey($albums,'album_id'));
                $str = '';
                if(!empty($album_ids)){
                    foreach ($album_ids as $key=>$val){
                        $str.= $val.',';
                    }
                    $str = rtrim($str,',');
                    $map.= ' AND `id` in ('.$str.')';
                }else{
                    $map = '1=0';
                }
            }else{
                $map = '1=0';
            }
        }

        // 排序
        $order = '';
        $sort = t($_GET['sort']);
        $orderBy = t($_GET['order']);
        list($lower,$toper) = explode(',',t($_GET['lower']));

        if($orderBy && $sort){
            switch ($sort){
                case 'price_up':
                    $order .= '`price` ASC';
                    break;
                case 'price_down':
                    $order .= '`price` DESC';
                    break;
            }
            $order .= ',`ctime` DESC';
        }elseif($orderBy == 'new'){
            $order .= ' `ctime` DESC';
        }elseif($sort){
            switch ($sort){
                case 'price_up':
                    $order .= '`price` ASC';
                    break;
                case 'price_down':
                    $order .= '`price` DESC';
                    break;
            }
        }

        if($toper && $lower >= 1){
            $map .= " AND `price` >= ".$lower;
        }
        if($toper){
            $map .= " AND `price` <= ".$toper;
        }

        $album_list = D('Album')->getList($map,$order,6);
        foreach ($album_list['data'] as $key=>$val){
            $all_price = getAlbumPrice($val['id'],$this->mid);
            $album_list['data'][$key]['price'] = $all_price['price'];
            $album_list['data'][$key]['oPrice'] = $all_price['oriPrice'];
            $album_list['data'][$key]['disPrice'] = $all_price['disPrice'];
            $album_list['data'][$key]['isBuy'] =  D('ZyOrderAlbum')->isBuyAlbum($this->mid ,$val['id'] ) ? 1 : 0;
        }

        $this->assign('lower',t($_GET['lower']));
        $this->assign('album_list',$album_list);

        $cateMap['pid'] = 0;
        $category = M('zy_package_category')->where($cateMap)->order('sort desc')->limit(10)->select();
        /*教室列表*/
        $teachers = $this->teachers();
        /*会员等级*/
        $uMap['is_del'] = 0;
        $userLeve = M('user_vip')->where($uMap)->order("sort DESC")->limit(100)->select();

        /*按年份*/
        $years = $list['time'];
        $like = $this->getLike();
        /*接收到的参数*/
        $this->assign('cid',$cateId);
        $this->assign('tid',$tid);
        $this->assign('vid',$vid);
        $this->assign('time',$time);

        $this->assign('category', $category);
        $this->assign('user_vip', $userLeve);
        $this->assign('teachers',$teachers);
        $this->assign('years',$years);
        $this->assign('like',$like);
        $this->assign('list',$list);
        $this->display();
    }


    /**
     * 取得课程分类
     */
    public function getCategroy() {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $data = $this->category->getChildCategory($id, 2);
        }
        if (empty($data))
            $data = null;
        $this->ajaxReturn($data);
    }


    /**
     * 获取教师列表
     * @return number
     */
    public function teachers(){
        $map['is_del'] = 0;
        $order = ' `views` DESC';
        $data = M('zy_teacher')->where($map)->order($order)->limit(100)->select();
        return $data;
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
        $_GET['tagId'] = intval($_GET['tagId']);
        if ($_GET['cateId'] > 0) {
            $idlist = implode(',', $this->category
                            ->getVideoChildCategory($_GET['cateId'], 2));
            if ($idlist)
                $where .= " AND album_category IN($idlist)";
        }

        if ($_GET['tagId'] > 0) {
            $inSql = "SELECT row_id FROM ".C('DB_PREFIX')."app_tag WHERE app='classroom' AND `table`='album' AND `is_del`=0 AND tag_id={$_GET['tagId']}";
            $where .= " AND id IN($inSql)";
        }
        $size = 16;
        $data = $this->album->where($where)->order($order)->findPage($size);
        //计算总课程
        foreach($data['data'] as &$val){
            $video_ids = D('Album')->getVideoId($val['id']);
            $vids = D('zyVideo')->where(array('id'=>array('IN',$video_ids),'is_del'=>0))->field('id')->select();
            $val['video_cont'] = count($vids);
            if( intval($val['price']) ) {
                $price = is_admin($this->mid) ? 0 : $val['price'];
                $val['mzprice'] = array('overplus'=>$price);
            } else {
                //获取班级价格
                $val['mzprice'] = $this->album->getAlbumMoeny( $video_ids ,$this->mid);
            }

            //格式化班级评分
            $val['score']    = round($val['album_score']/20);
        }

        if ($data['data']) {
            $this->assign('listData', $data['data']);
            $this->assign('tagId',$_GET['tagId']);//定义排序
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
     * 班级详情页
     */
    public function view() {
        $this->view_info();
        $this->display();
    }

    private function view_info(){
        $id = intval($_GET['id']);

        $map['id'] = array('eq', $id);
        $map['status'] = 1;
        $album_data = D('Album')->where($map)->find();

        //设置seo详情
        $this->seo['_title'] = $album_data['album_intro'] ? $album_data['album_title'].' — '.$album_data['album_intro'] : $album_data['album_title'].$this->seo['_bak_title'];
        $this->seo['_meta'] = $album_data['album_intro'] ? : $this->seo['_meta'];

        $share_url = $this->addVideoShare($id,1);
        $code = t ( $_GET ['code'] );
        if($code){
			$code = explode('@@@', $code);
			$code = $code[0];
            $video_share = M('zy_video_share')->where(array('tmp_id'=>$code))->field('uid,video_id,type,share_url')->find();
            if($video_share) {
                $data['type']       = $map['type'] = $video_share['type'];
                $data['uid']        = $map['uid'] = $video_share['uid'];
                $data['use_uid']    = $this->mid ? $this->mid : 0;
                $data['video_id']   = $map['video_id'] = $video_share['video_id'];
                $data['ctime']      = time();
                $data['share_url'] = $video_share['share_url'];
                $data['tmp_id'] = $map['tmp_id'] = session_id();
                if($video_share['uid'] != $this->mid) {
                    $result = M('zy_video_share_user')->where($map)->select();
                    if ($result) {
                        M('zy_video_share_user')->where($map)->save($data);
                    } else {
                        M('zy_video_share_user')->add($data);
                    }
                }
            }
            $mhm_id = M('user')->where('uid = '.$video_share['uid'])->getField('mhm_id');
            $this_mhm_id = M('school')->where('id = '.$mhm_id)->getField('id');
            $this->assign ('this_mhm_id', $this_mhm_id );
            unset($data);
            unset($map);
        }
        $data = $album_data;
        //计算价格
        $all_price = getAlbumPrice($data['id'],$this->mid);
        $data['price'] = $all_price['price'];
        $data['oPrice'] = $all_price['oriPrice'];
        $data['disPrice'] = $all_price['disPrice'];

        if (!$data) {
             $this->assign('isAdmin', 1);
            $this->error('班级不存在!');
        }
        //获取评论数
        $data['reviewCount'] = D('ZyReview')->getReviewCount(2, intval($data['id']));

        $data['album_title'] = msubstr($data['album_title'], 0, 24);
        $data['album_intro'] = msubstr($data['album_intro'], 0, 100);
        //班级分类
        //$data['album_category_name'] = getCategoryName($data['album_category'], true);

        //是否收藏
        $data['iscollect'] = D('ZyCollection')->isCollect($data['id'], 'album', intval($this->mid));

        //检查一个用户的余额/冻结的数量是否够支配
        $data['isSufficient'] = D('ZyLearnc')->isSufficient($this->mid, $data['price']);

        //查询资源是否存在
        $data['isGetResource'] = isGetResource(2, $data['id'], array('video', 'upload', 'note', 'question'));

        $album_video = D('Album')->getVideoId($id);

        //获取所有视频信息
        $videoData = array();
        if($album_video){
            $tMap['id'] = array('IN',trim( $album_video ,','));
            $tMap['is_del'] = array('eq',0);
//            $tMap['teacher_id'] = array('NEQ',0);
            $videoData = D('zyVideo')->where($tMap)->field('id,teacher_id,t_price,video_title,cover,video_intro,type,live_type')->select();
        }

        $tch_ids = array();
        //原价
        foreach($videoData as $key=>$val) {
            $tch_ids[$key] = $val['teacher_id'];
            $oPrice += $val['t_price'];
            //总课时
            if ($val['type'] == 1) {
                $videoData[$key]['sectionNum'] = 0;
                $secmap = array();
                $secmap['vid'] = $val['id'];
                $secmap['pid'] = array('neq', 0);
                $secmap['is_activity'] = 1;
                $count = M('zy_video_section')->where($secmap)->count();
                $videoData[$key]['sectionNum'] = $count;
            }

            if ($val['type'] == 2) {
                $videoData[$key]['sectionNum'] = 0;

                $videoData[$key]['sectionNum'] = model('Live')->liveRoom->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->count();
                if (!$videoData[$key]['sectionNum']) {
                    $videoData[$key]['sectionNum'] = 0;
                }
                $live_id[$key] = model('Live')-> where(array('id'=>$val['id'],'is_del'=> 0,'is_active'=>1))->getField('teacher_id');
            }
        }
        if($live_id){
            $tch_ids = array_merge($tch_ids,$live_id);
        }

        $data['video_count'] = count($videoData);

        $tids = array_unique(array_filter($tch_ids));//去掉重复讲师id
        $tidr = implode(",",$tids);
        /*讲师信息*/
        $tdata=D('ZyTeacher')->where(array('id'=>array('IN',trim($tidr,","))))->select();
        $teachers = '';

        foreach ($tdata as $key=>$val){
            $teachers .= $val['name'].'　';
            if($key == 0){
                $tdata[$key]['fans_count'] = model('Follow')->where(array('tid'=>$val['id']))->count();
                $fans_state = M('UserFollow')->where(array('uid'=>$this->mid,'tid'=>$val['id']))->find();
                $tdata[$key]['fans_state'] = $fans_state ? 1 : 0;

                $teacher_vip = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$val['title'])->find();
                if($teacher_vip['cover'] > 0){
                    $tdata[$key]['teacher_vip_cover'] = getCover($teacher_vip['cover'],19,19);
                }
            }
        }

        /*机构信息*/
        $mhm_id = $data['mhm_id'];
        if($mhm_id){
            //机构信息
            $mhmData =  model('School')->getSchoolInfoById($mhm_id);
            //课程数
            $mhmData['count'] = D('ZyVideo')->where(array('mhm_id'=>$mhm_id))->count();
            //机构学生数量
            $mhmData['student']=model('Follow')->where(array('fid'=>$mhmData['uid']))->count();
            //当前用户关注状态
            $mhmData['state']=model('Follow')->getFollowState($this->mid,$mhmData['uid']);
            //域名跳转处理
            $mhmData['domain'] = getDomain($mhmData['doadmin'],$mhm_id);
        }
        //获取当前用户可支配的余额
        $data['balance'] = D("zyLearnc")->getUser($this->mid);
        // 是否已购买
        $data['is_buy'] = D('ZyOrderAlbum')->isBuyAlbum($this->mid ,$id ) ? 1 : 0;
        //资源收藏人
        $source = D ( 'ZyCollection' )->field('uid')->where(array('source_id'=>$id,'source_table_name'=>'album'))->order('ctime desc')->limit(8)->select();
        if($source){
            foreach ($source as $item => $value){
                $userInfo = model('User')->getUserInfo($value['uid']);
                $source[$item]['user']['uname'] = $userInfo['uname'];
                $source[$item]['user']['avatar_big'] = $userInfo['avatar_big'];
                unset($userInfo);
            }
        }
        if($_GET['code']){
            $uid = M('zy_video_share')->where(array('tmp_id' => $_GET['code']))->getField('uid');
            $mhm_id = model('User')->where('uid='.$uid)->getField('mhm_id');
        }

		$commentSwitch = model('Xdata')->get('admin_Config:commentSwitch');
		$switch = $commentSwitch['album_switch'];

        $like = $this->getLike();
        $this->assign('like',$like);
        $this->assign( 'mhmData', $mhmData );
        $this->assign('trlist',$tdata);
        $this->assign('data', $data);
        $this->assign('aid', $id);
        $this->assign('this_mhm_id',$mhm_id);

        $this->assign('videos',$videoData);
        $this->assign('switch',$switch);
        $this->assign('uid',$this->mid);
        $this->assign('teachers',$teachers);
        $this->assign ( 'source', $source );
		$this->assign('share_url',$share_url);
    }

    /**
     * 班级详情页
     */
    public function view_mount() {
        $this->view_info();
        $id = intval($_GET ['id']);
        $mid = explode('L',t($_GET['mid']))[0];
        if($mid){
            $mount = M( 'zy_video_mount')->where (['aid'=>$id,'mhm_id'=>$mid])->getField('aid');
            if(!$mount){
                $this->error("出错啦。。");
            }
        }
        $chars = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        for ( $i = 0; $i < 4; $i++ ){
            $mount_url_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        $this->assign('mount_str',$mid.'H'.$mount_url_str);
        $this->display ('view');
    }

    /**
     * 取得班级目录----课程标题
     * @param int $return
     * @return void|array
     */
    public function getcatalog() {
        $limit = intval($_POST['limit']);
        $id = intval($_POST['id']);

        $sVideos = D('Album')->getVideoId($id);
        $sVideos = getCsvInt($sVideos);
        $sql = 'SELECT `id`,`video_title` FROM ' . C("DB_PREFIX") . 'zy_video WHERE `id` IN (' . (string) $sVideos . ') and is_del=0 ORDER BY find_in_set(id,"' . (string) $sVideos . '")';
        $data = M('')->query($sql);
        $this->assign('data', $data);
        $result = $this->fetch('_MuLu');
        exit( json_encode($result) );
    }

    /**
     * 加载课程/班级笔记
     */
    public function getnotelist(){
        $type=intval($_REQUEST['type']);//获取笔记类型 【1:课程;2:班级;】
        $oid=intval($_REQUEST['oid']);//获取对应的栏目id
        $map=array(
            'type'=>$type,
            'oid'=>$oid,
            'parent_id'=>0,
            'uid'=>$this->mid
        );
        $data=D('ZyNote')->where($map)->order("ctime DESC")->findPage(6);
        //格式化昵称
        foreach($data['data'] as &$val){
            $val['uname']=getUserName($val['uid']);
        }

        $this->assign("data",$data['data']);
        $this->assign('oid',$oid);
        $data['data']=$this->fetch("note");
        echo json_encode($data);exit;
    }
    /**
     * 加载课程/班级提问
     */
    public function getquestionlist(){
        $type=intval($_REQUEST['type']);//获取笔记类型 【1:课程;2:班级;】
        $oid=intval($_REQUEST['oid']);//获取对应的栏目id
        $map=array(
                'type'=>$type,
                'oid'=>$oid,
                'parent_id'=>0,
                'uid'=>$this->mid
        );
        $data=D('ZyQuestion')->where($map)->order("ctime DESC")->findPage(6);
        //echo M()->getlastsql();
        $this->assign("data",$data['data']);
        $this->assign('oid',$oid);
        $data['data']=$this->fetch("question");
        echo json_encode($data);exit;
    }



    /**
     * 班级观看页面
     */
    public function watch() {
        $aid = intval($_GET['aid']);
        $type = intval($_GET['type']); //数据分类 1:课程;2:班级;
        if ($type == 1) { //课程
            $data = M("ZyVideo")->where(array('id' => array('eq', $aid)))->select();
            $data[0]['mzprice'] = getPrice($data[0], $this->mid, true, true);
            $data[0]['isBuyVideo'] = isBuyVideo($this->mid, $data[0]['id']) ? 1 : 0;
            //会员等级是否比当前课程所需会员等级高
            $data[0]['user_vip_level'] = D('ZyRecharge','classroom')->getUserVipLevel($this->mid ,intval($data["vip_level"]));
            $is_colle=D('ZyCollection')->where(array('uid'=>$this->mid,'source_id'=>$data[0]['id'],'source_table_name'=>'zy_video'))->find();
            if($is_colle){
                $data[0]['is_colle']=1;
            }else{
                $data[0]['is_colle']=0;
            }
            if (!isset($data[0]) && !$data[0]) {
                $this->assign('isAdmin', 1);
                $this->error('课程不存在!');
            }

        } else {
            $data=$this->albumdata;
            if (!isset($data[0]) && !$data[0]) {
                $this->assign('isAdmin', 1);
                $this->error('没有要播放的视频!');
            }
        }
        //判断是否是限时免费
        $is_free=0;
        if($data[0]['is_tlimit']==1 && $data[0]['starttime'] < time() && $data[0]['endtime'] > time() && $data[0]['limit_discount']==0.00){
            $is_free=1;
        }
        if( floatval( $data[0]['mzprice']['price'] ) <= 0 || ( intval($data['vip_level'] ) && $data['user_vip_level'] > intval($data['vip_level'] ) ) )  {
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
        //播放器
        $player_type = getAppConfig("player_type");

        $balance = D("zyLearnc")->getUser($this->mid);

        $this->assign("video_address",$jmstr);
        $this->assign("test_time",$test_time);
        $this->assign("player_type",$player_type);
        $this->assign('balance', $balance);
        $this->assign('is_free',$is_free);
        $this->assign('vid', $data[0]['id']);
        $this->assign('video_id', $data[0]['video_id']);
        $this->assign('video_title', $data[0]['video_title']);
        $this->assign('video_order_count', $data[0]['video_order_count']);
        $this->assign('price', $data[0]['mzprice']['price']);
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

    //获取视频地址
    public function sourse(){
        $id   = intval( t($_GET['id']) );
        echo M('zy_video')->where('id='.$id . ' and is_del=0')->getField('video_address');
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
            $this->error('课程不存在!');
        }
        //判断是否是限时免费
        $is_free=0;
        if($data[0]['is_tlimit']==1 && $data[0]['starttime'] < $nowtime && $data[0]['endtime'] > $nowtime && $data[0]['limit_discount']==0.00){
            $is_free=1;
        }

        $data[0]['isBuyVideo']= isBuyVideo($this->mid, $data[0]['id']) ? 1 : 0;
        //限时免费/已购买/上传者是自己/管理员  不用购买课程
        if($data[0]['isBuyVideo']==1 || is_admin($this->mid) || intval($data[0]['mzprice']['price']) == 0 ){
            $is_free = 1;
        }

        $test_time=getAppConfig("video_free_time");
        //播放器
        $player_type = getAppConfig("player_type");

        $this->assign("video_address",$jmstr);
        $this->assign("is_free",$is_free);
        $this->assign("test_time",$test_time);
        $this->assign("player_type",$player_type);
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
        $this->display("watch");
    }


    /**
     * 获取解密后的url
     */
    public function getvideo(){
        $info=t($_REQUEST['video']);//获取加密的视频
        if(empty($info)){
            echo "非法请求1！";
            exit;
        }
        $keyinfo=explode("|",sunjiemi($info));//解密分割数组
        $nowtime=time();
        if($keyinfo[0]<$nowtime){
            echo "非法请求！";
            exit;
        }
        $address=M('ZyVideo')->where(array('id'=>$keyinfo[1]))->getField("video_address");
        if($address){
            echo $address;
            exit;
        }else{
            echo "非法请求！";
            exit;
        }


    }

    /**
     * 获取随堂课题
     *
     */
    public function lesson() {
        //用户权限控制
        $vid = intval($_POST['vid']);
        $questions = M('zyQuestions')->where(array('video_id' => $vid))->field('qid,q_title,q_options')->order('qid asc')->limit(5)->select();
        $show_time = M('zyVideo')->where(array('id' => $vid))->getField('show_time');
        foreach ($questions as $key => $val) {
            //反序列化选项
            $options = unserialize($val['q_options']);
            $questions[$key]['q_options'] = $options;
        }
        $json['questions'] = $questions;
        $json['showtime'] = $show_time;
        echo json_encode($json);
    }

    /**
     * 处理随堂课题
     *
     */
    public function exercises() {
        //转换成A B C D数组
        $arr = array('A', 'B', 'C', 'D');
        $selectIds = $_POST['ids'];
        $vid = $_POST['vid'];
        if (empty($selectIds)) {
            $this->assign('isAdmin', 1);
            $this->error('异常操作');
        };
        $times = 0;
        $uid = $this->uid;

        /**
         * 根据题  拼接选项答案和数据库的作判断
         */
        foreach ($selectIds as $key => $val) {
            $tarr = explode('_', $val);
            $where[$key] = $qid = $tarr['0'];
            $answer = $arr[$tarr['1']];
            //把多选的  拼接查询条件
            if (in_array($qid, $where)) {
                $seek[$qid] .= $answer . ',';
            } else {
                $seek[$qid] = $answer;
            }
        }
        //去除数组重复的题id
        $where = array_flip(array_flip($where));
        //根据题的id逐个去验证答题是否正确
        foreach ($where as $k => $v) {
            $seek[$v] = substr($seek[$v], 0, -1);
            $values .= "($uid,$v,$vid,'$seek[$v]'),";
            $rtn = M('zyQuestions')->where(array('qid' => $v, 'q_answer' => $seek[$v]))->find();
            //如果选择正确
            if (!is_null($rtn)) {
                $times += 1;
                $ex = explode(',', $seek[$v]);
                if (is_array($ex)) {
                    foreach ($ex as $keys => $vals) {
                        $key = array_search($vals, $arr);
                        $data[] = $v . '_' . $key;
                    }
                } else {
                    $key = array_search($seek[$v], $arr);
                    $data[] = $v . '_' . $key;
                }
            }
        }
        $values = substr($values, 0, -1);
        $sql = "insert into `".C('DB_PREFIX')."zy_answers` (`uid`,`qid`,`video_id`,`a_answer`) values $values";
        $result = M('zyAnswers')->query($sql);
        $json['data'] = $data;
        $json['times'] = $times;
        echo json_encode($json);
    }

    /**
     *   观看记录保存到session中
     */
    public function save_session() {
        $vid = intval($_POST['vid']); //获取观看视频id
        $uid = $this->mid; //用户id
        if (!empty($uid)) {
            $watch_history = array();
            $watch_history = session('watch_history');
            if (count($watch_history) <= 0) {
                $session_data[] = $vid;
                $watch_history = $session_data;
            } elseif (!in_array($vid, $watch_history)) {
                array_unshift($watch_history, $vid);
            }
            if (count($watch_history) > 3) {
                array_splice($watch_history, 3);
            }
            session('watch_history', $watch_history);
        }

    }

    public function addLearnRecord() {
        $vid = $_GET['vid'];
        $uid = $this->mid;
        $time= getStatus('time');
        $data = M('learn_record')->$where(array('vid'=>$vid,'uid'=>$uid))->getField('id');
        $map=array(
            'uid'=>intval($uid),
            'vid'=>intval($vid),
            'sid'=>t(intval($_POST['sid'])),
            'type'=>t(intval($_POST['type'])),
            'time'=>t($time),
            'ctime'=>time()
         );
        if (!$id) {
            $i = M('learn_record')->where($map)->add($data);
        } else {
            $i = M('learn_record')->where($map)->save($data);
        }
        if ($i === false) {
            $this->mzError('保存失败');
        } else {
            $this->mzSuccess('保存成功');
        }
    }

    /**
     * 删除操作
     * 1:购买的;2:收藏的;3：上传的---审核中;4:上传的---已发布
     * @param int $return
     * @return void|array
     */
    public function delalbum() {
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        $rtype = intval($_POST['rtype']);
        if ($rtype == 1) {
            $this->delbuyalandvi($id, $type);
        } else if ($rtype == 2) {
            $this->delcollectalandvi($id, $type);
        } else if ($rtype == 3) {
            $this->delalbumorvideo($id, $type);
        } else if ($rtype == 4) {
            $this->delalbumorvideo($id, $type);
        } else if ($rtype == 5) {
            $this->delalbumorlive($id, $type);
        }
    }

    /**
     * 删除购买的班级和课程 <!--type   1:课程;2:班级;3;直播-->
     * @param int $return
     * @return void|array
     */
    private function delbuyalandvi($id, $type) {
        $map['id'] = array('eq', $id);
        $data['is_del'] = 1;
        if ($type == 1) {
            $i = M('zy_order_course')->where($map)->save($data);
        } else if ($type == 2) {
            $i = M('zy_order_album')->where($map)->save($data);
        }else {
            $i = M('zy_order_live')->where($map)->save($data);
        }
        if ($i === false) {
            $this->mzError('删除失败');
        } else {
            $this->mzSuccess('删除成功');
        }
    }

    /**
     * 删除购买的直播 <!--type   1:课程;2:班级;-->
     * @param int $return
     * @return void|array
     */
    private function delalbumorlive($id, $type) {
        $map['id'] = array('eq', $id);
        $data['is_del'] = 1;
            $i = M('zy_order_live')->where($map)->save($data);

        if ($i === false) {
            $this->mzError('删除失败');
        } else {
            $this->mzSuccess('删除成功');
        }
    }

    /**
     * 删除收藏的班级和课程 <!--type   1:课程;2:班级;-->
     * @param int $return
     * @return void|array
     */
    private function delcollectalandvi($id, $type) {
        $map['collection_id'] = array('eq', $id);
        if($type == 1){
            $vid = M('ZyCollection')->where($map)->getField('source_id');
            $video_type = M('zy_video')->where('id='.$vid)->getField('type');
            if($video_type == 1){
                $credit = M('credit_setting')->where(array('id'=>49,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $ctype = 7;
                    $note = '取消收藏课程扣除的积分';
                }
            }else{
                $credit = M('credit_setting')->where(array('id'=>50,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $ctype = 7;
                    $note = '取消收藏直播扣除的积分';
                }
            }
        }else{
            $credit = M('credit_setting')->where(array('id'=>48,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] < 0){
                $ctype = 7;
                $note = '取消收藏班级扣除的积分';
            }
        }
        model('Credit')->addUserCreditRule($this->mid,$ctype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

        $i = M('ZyCollection')->where($map)->delete();
        if ($i === false) {
            $this->mzError('删除失败');
        } else {
            $this->mzSuccess('删除成功');
        }
    }

    /**
     * 删除上传的班级和课程 <!--type   1:课程;2:班级;-->
     * @param int $return
     * @return void|array
     */
    private function delalbumorvideo($id, $type) {
        $data['is_del'] = 1;
        $map['id'] = array('eq', $id);

        if ($type == 1) {
            $i = M('ZyVideo')->where($map)->save($data);
        } else {
            $i = M('Album')->where($map)->save($data);
        }

        if ($i === false) {
            $this->mzError('删除失败');
        } else {
            $this->mzSuccess('删除成功');
        }
    }

    /**
     * 把班级分享到点播去
     * @param int $return
     * @return void|array
     */
    public function sharetodianbo() {
        $id = intval($_POST['id']);

        $data['is_share'] = 1;
        $map['id'] = array('eq', $id);

        $i = M('Album')->where($map)->save($data);
        if ($i === false) {
            $this->mzError('分享失败');
        } else {
            $this->mzSuccess('分享成功');
        }
    }

    /**
     * 前台创建班级
     */
    public function creat_album() {
        $post = $_POST;
        $data['album_title'] = $post['album_title'];
        $data['album_intro'] = $post['album_intro'];
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['album_videos'] = $post['album_videos'];
        $data['is_offical'] = '0';
        $data['is_share'] = '0';
        $total_price_post = floatval($post['total_price']);
        if (empty($post['album_videos']))
            exit(json_encode(array('status' => '999', 'info' => '说好的课程呢？')));
        $avideos['data'] = explode(',', $post['album_videos']);
        $total_price = 0;
        foreach ($avideos['data'] as $key => $value) {
            $avideos['data'][$value]['video_info'] = D("ZyVideo")->getVideoById($value);
            $avideos['data'][$value]['is_buy'] = D("ZyOrder")->isBuyVideo($this->mid, $avideos['data'][$value]['video_info']['id']);
            $avideos['data'][$value]['price'] = getPrice($avideos['data'][$value]['video_info'], $this->mid, true, true);

            //当购买过之后，或者课程的创建者是当前购买者的话，价格为0
            $total_price += ($avideos['data'][$value]['is_buy'] || $value['uid'] == $this->mid) ? 0 : round($avideos['data'][$value]['price']['price'], 2);
            $avideos['data'][$value]['legal'] = $avideos['data'][$value]['video_info']['uctime'] > time() ? 1 : 0;
            if ($avideos['data'][$value]['video_info']['is_offical']) {
                $avideos['data'][$value]['percent'] = 0;
            } else {
                $avideos['data'][$value]['percent'] = getUserIncomePercent($avideos['data'][$value]['video_info']['uid']);
            }
            $avideos['data'][$value]['user_num'] = $avideos['data'][$value]['percent'] * $avideos['data'][$value]['video_info']['v_price'];
            $avideos['data'][$value]['master_num'] = $avideos['data'][$value]['price']['price'] - $avideos['data'][$value]['user_num'];
            unset($avideos['data'][$key]);
        }
        //前台post的价格和后台计算的价格不相等，防止篡改价格
        if (bccomp($total_price, $total_price_post) != 0) {
            exit(json_encode(array('status' => '999', 'info' => '亲，可不要随便改价格哦，我们会发现的!')));
        }
        //创建班级
        $create_result = M("Album")->data($data)->add();
        $total_price = floatval($total_price);

        //创建班级失败
        if (!$create_result)
            exit(json_encode(array('status' => '0', 'info' => '创建班级失败，请稍后再试')));

        //创建班级之后付款，并且添加班级购买记录 不成功则向前台发送对应的错误信息
        $pay_result = D("ZyService")->buyAlbum($this->mid, $create_result, $total_price);
        if ($pay_result['status'] != '1') {
            M("album")->where(' id = ' . $create_result)->delete();
            exit(json_encode(array('status' => $pay_result['status'], 'info' => $pay_result['info'])));
        }
        //添加消费记录
        M('ZyLearnc')->addFlow($this->mid, 0, $total_price, $note = '购买班级<' . $data['album_title'] . '>', $pay_result['rid'], 'zy_order_album');


        //添加班级中的课程购买记录
        $insert_value = "";
        foreach ($avideos['data'] as $key => $video) {
            if (!$video['is_buy']) {
                if($video['video_info']['uid'] != $this->mid){
                //卖家分成
                    D('ZyLearnc')->income($video['video_info']['uid'], $video['user_num']);
                    $insert_value .= "('" . $this->mid . "','" . $video['video_info']['uid'] . "','" . $video['video_info']['id'] . "','" . $video['video_info']['v_price'] . "','" . ($video['video_info']['price']['discount'] / 10) . "','" . $video['video_info']['price']['dis_type'] . "','" . $video['video_info']['price']['price'] . "','" . $create_result . "','" . $video['percent'] . "','" . $video['user_num'] . "','" . $video['master_num'] . "','0'," . time() . ",0),";
                }
            }
        }
        if(!empty($insert_value)){
            $query = "INSERT INTO " . C("DB_PREFIX") . "zy_order (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`percent`,`user_num`,`master_num`,`learn_status`,`ctime`,`is_del`) VALUE " . trim($insert_value, ',');
            $table = new Model();
            if ($table->query($query) !== false || $total_price == 0) {
                echo(json_encode(array('status' => '1', 'info' => '创建班级成功', 'album_id' => $create_result)));
                foreach ($avideos['data'] as $key => $video) {
                    if (!$video['is_buy'] && ($this->mid != $video['video_info']['uid'])) {
                        $rvid = M("zy_order")->where('video_id=' . $video['video_info']['id'])->field("id")->find();
                        //添加消费记录
                        D('ZyLearnc')->addFlow($video['video_info']['uid'], 5, $video['user_num'], $note = '卖出课程<' . $video['video_info']['video_title'] . '>', $rvid['id'], 'zy_order_album');
                    }
                    D('ZyVideoMerge')->delVideo($video['video_info']['id'], $this->mid);
                }
            }
        } else {
            echo(json_encode(array('status' => '1', 'info' => '创建班级成功', 'album_id' => $create_result)));
        }

    }

    public function buyOnlineLive($uid, $live_id) {
        $live_id = 353;
        $uid = 9;
        //取得直播课程
        $live_info = D('ZyVideo')->where(array(
            'id'          => $live_id,
            'is_del'      => 0,
            'is_activity' => 1,
            'type'        => 2,
            'listingtime' => array('lt', time()),
        ))->field("id,video_title,mhm_id,t_price,
            listingtime,uctime,live_type")->find();
        //找不到直播课程
        if (!$live_info){
            echo 2;
        }
        $live_id        = intval($live_info['id']);

        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            echo 5;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid,school_and_teacher')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            echo 6;
        }
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成自营比例为空
        if($proportion == 3){
            echo 7;
        }

        //直播课程属于机构 先平台和机构分成 再机构与教师分成
        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
        if(is_array($school_and_teacher)){
            $school_info['sat_school']  = floatval($school_and_teacher[0]);
            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
        }

        //机构数据里的 机构与教师分成不存在
        if(empty($school_and_teacher)){
            echo 8;
        }
        //获取直播课程下所有的课程ID
        $teacher_user_info = "此直播课程下课程所有讲师信息分别为";
        $teacher_uinfo_uid = "";
        if($live_info['live_type'] == 1){
            $live_zshd_hour = M('zy_live_zshd')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();
            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_zshd_hour);
            if($video_count <= 0){
                echo 6;
            }

            $tuid = '';
            foreach ($live_zshd_hour as $key =>$val){
                //判断班级下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接班级下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['uid']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                    ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }
            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_zshd_hour['video_count']   = $video_count;
            $live_info['teacher_uinfo'] = $teacher_user_info;

            //班级中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                echo 9;
            }
        }elseif($live_info['live_type'] == 3){
            $live_gh_hour = M('zy_live_gh')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();

            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_gh_hour);
            if($video_count <= 0){
                echo 6;
            }

            $tuid = '';
            foreach ($live_gh_hour as $key =>$val){
                //判断班级下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接班级下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['uid']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                                        ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }

            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_gh_hour['video_count']    = $video_count;
            $live_info['teacher_uinfo']     = $teacher_user_info;

            //班级中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                echo 9;
            }
        }
        //无过期非法信息则生成状态为未支付的订单数据
        $order = D('ZyOrder');
        $data = array(
            'uid'           => $uid,
            'video_id'      => $live_id,
            'old_price'     => $live_info['t_price'],
            'discount'      => 0,
            'discount_type' => 0,
            'price'         => $live_info['t_price'],
            'order_album_id'=> 0,
            'learn_status'  => 0,
            'ctime'         => time(),
            'order_type'    => 2,
            'is_del'        => 0,
            'pay_status'    => 1,
            'mhm_id'        => $live_info['mhm_id'],
        );
        $id = $order->add($data);
        //购买记录/订单，添加失败
        if (!$id){
            echo 4;
        }

        $video_spilt_status = $this->addLiveSplit($id,$uid,$live_info);
        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order')->where(array('id' => $id))->delete();
            echo $video_spilt_status;
        }else{
            echo true;
        }
    }

    public function addLiveSplit($order_id,$uid,$live_info = array()){
//        //取得直播课程
//        $live_info = D('ZyVideo')->where(array(
//            'id'          => 353,
//            'is_del'      => 0,
//            'is_activity' => 1,
//            'type'        => 2,
//            'listingtime' => array('lt', time()),
//        ))->field("id,video_title,mhm_id,t_price,
//            listingtime,uctime,live_type")->find();

        //找不到直播课程
        if (!$live_info){
            echo 2;
        }
        $live_id = $live_info['id'];


        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            echo 5;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid,school_and_teacher')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            echo 6;
        }
        //取得分成比例
        $proportion = getAllProportion();
        //平台与机构分成自营比例为空
        if($proportion == 3){
            echo 7;
        }
        //生成订单分成详细 取得价格
        $prices = floatval($live_info['t_price']);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($live_info['mhm_id']);//机构ID
        $data['vid']          = intval($live_info['id']);//所购买课程的id(包括点播、直播、班级)
        $data['note']         = t("购买直播课堂：{$live_info['video_title']}。");
        $data['split_type']   = 2;//分成类型 0课程,1班级,2直播
        $data['sum']          = $prices;//购买金额
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = $prices * $proportion['sss_platform'];//平台分成的自营金额

        $school_num = $prices * $proportion['sss_school'];//机构及教师分成的自营总金额


        //直播课程属于机构 先平台和机构分成 再机构与教师分成
        $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
        if(is_array($school_and_teacher)){
            $school_info['sat_school']  = floatval($school_and_teacher[0]);
            $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
        }

        //机构数据里的 机构与教师分成不存在
        if(empty($school_and_teacher)){
            return 8;
        }
        //获取直播课程下所有的课程ID
        $teacher_user_info = "此直播课程下课程所有讲师信息分别为";
        $teacher_uinfo_uid = "";
        if($live_info['live_type'] == 1){
            $live_zshd_hour = M('zy_live_zshd')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();
            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_zshd_hour);
            if($video_count <= 0){
                echo 6;
            }

            $tuid = '';
            foreach ($live_zshd_hour as $key =>$val){
                //判断班级下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接班级下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['uid']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                    ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }
            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_zshd_hour['video_count']   = $video_count;
            $live_info['teacher_uinfo'] = $teacher_user_info;

            //班级中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                echo 9;
            }
        }elseif($live_info['live_type'] == 3){
            $live_gh_hour = M('zy_live_gh')->where('live_id='.$live_id)->field('id,speaker_id')->findAll();

            //直播课程下所有的课程数量 直播课程下没有课程
            $video_count = count($live_gh_hour);
            if($video_count <= 0){
                echo 6;
            }

            $tuid = '';
            foreach ($live_gh_hour as $key =>$val){
                //判断班级下没有相关讲师用户不存在的课程
                $teacher_info = M('zy_teacher')->where(array('id'=>$val['speaker_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid .= $val['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接班级下所有讲师信息以便线下分成
                $key = $key+1;
                $teacher_user_info .= "【{$key}】 讲师id为: ".$teacher_info['uid']." ,讲师名字为：".$teacher_info['name'].
                    " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。
                                        ";
                $teacher_uinfo_uid .= $teacher_uinfo['uid'].",";
            }

            $live_zshd_hour['tuid_str'] = $teacher_uinfo_uid;
            $live_gh_hour['video_count']    = $video_count;
            $live_info['teacher_uinfo']     = $teacher_user_info;

            //班级中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                echo 9;
            }
        }
        $teacher_user_info              .= "讲师名和用户名可能会有变动。";

        $data['note']               = $teacher_user_info;
        $data['ctime']              = time();
        $data['sid']                = $school_uid;//机构分成的管理员用户id
        $data['school_sum']         = $school_num * $school_info['sat_school'];//机构获得的金额

        $data['st_id']              = 0;//机构教师分成的用户id
        $data['school_teacher_sum'] = round($school_num * $school_info['sat_teacher'],2);//机构教师分成的金额


        //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
        if($data['pid'] == $school_uid){
            $platform_sum_add     = $school_num * $school_info['sat_school'];
            $platform_sum_new     = round($data['platform_sum'] + $platform_sum_add,2);
            $data['note']         .= "平台管理员和机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}
                                     加上{$platform_sum_add}之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //机构分成的管理员用户id
            unset($data['school_sum']); //机构获得的金额
        }
        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = intval($live_info['id']);//所购买课程的id(包括点播、直播、班级)
        $map['split_type'] = 2;
        $split_video = M('zy_split_video')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_video')->where($map)->save($data);
        } else {
            $res = M('zy_split_video')->add($data);
        }
        if ($res){
            echo true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order')->where(array('id' => $order_id))->delete();
            echo 9;
        }
    }


    /**
     * 购买班级操作
     */
    public function buyOperating($order_id,$uid,$album = array()) {
        $_POST['id'] = 18;
        $uid = 3;
        $order_id = 1;
        $album = D("Album")->getAlbumOneInfoById($_POST['id'],'id,price,mhm_id,album_title');
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
        $prices = floatval($album['price']);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($album['mhm_id']);//机构ID
        $data['vid']          = intval($album['id']);//所购买课程的id(包括点播、直播、班级)
        $data['note']         = t("购买班级：{$album['album_title']}。");
        $data['split_type']   = 1;//分成类型 0课程,1班级,2直播
        $data['sum']          = $prices;//购买金额
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = $prices * $proportion['pac_platform'];//平台分成的金额

        $school_num = $prices * $proportion['pac_school'];//机构及教师分成的总金额
        //课程是否属于机构
        if($album['mhm_id']){
            //课程属于机构 先平台和机构分成 再机构与教师分成
            $school_info = model('School')->where(array('id'=>$album['mhm_id']))->field('uid,school_and_teacher')->find();
            $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

            //班级所绑定的机构管理员不存在
            if(!$school_uid){
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
            if($album['video_count'] <= 0){
                return 3;
            }

            $illegal_count  = 0;
            $total_price    = 0;

            //通过课程取得专辑价格
            $video_id   = '';
            $tuid       = '';
            $teacher_user_info = "此班级下课程所有讲师信息分别为  ";
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
                //判断班级下没有相关讲师用户不存在的课程id
                $teacher_info = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->field('uid,name')->find();
                $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
                if(!$teacher_uinfo['uid']){
                    $tuid       .= $video['id'].',';//班级下没有相关讲师用户不存在的课程id
                }
                $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                //拼接班级下所有讲师信息以便线下分成
                $teacher_user_info .= "讲师id为: ".$teacher_info['uid']." ,讲师名字为：".$teacher_info['name'].
                            " ;其用户id为： ".$teacher_uinfo['uid']." ,用户名为： ".$teacher_uinfo['uname']." 。";
                $album_info['teacher_uinfo'] = $teacher_user_info;
                $album_info['tuid_str'] .= $teacher_uinfo['uid'].",";
            }

            //班级中包含有 没有相关讲师用户不存在的课程id
            if ($tuid) {
                return 9;
            }

            //班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
            if ($video_id) {
                return 10;
            }

            //生成分成流水详情
            $data['sid']                = $school_uid;//机构分成的管理员用户id
            $data['school_sum']         = $school_num * $school_info['sat_school'];//机构获得的金额

//            $data['st_id']              = trim($album_info['tuid_str'],',');//机构教师分成的所有教师用户id
            $data['school_teacher_sum'] = $school_num * $school_info['sat_teacher'];//机构教师分成所有的金额

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
            $data['note'] .= $album_info['teacher_uinfo'];
        } else {
            //班级不属于机构
            return 5;
        }
        $data['ctime'] = time();//机构教师分成的金额

        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = intval($albumId);//所购买课程的id(包括点播、直播、班级)
        $map['split_type'] = 1;
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
            return 11;
        }
    }

    public function addAlbumSplit($order_id,$uid,$album = array()){
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
        $data['vid']          = intval($album['id']);//所购买课程的id(包括点播、直播、班级)
        $data['note']         = t("购买班级：{$album['album_title']} ");
        $data['split_type']   = 1;//分成类型 0课程,1班级,2直播
        $data['sum']          = $prices;//购买金额
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = $prices * $proportion['pac_platform'];//平台分成的金额

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
            if($album['video_count'] <= 0){
                return 3;
            }

            //班级不属于机构
            if(!$album['mhm_id']){
                return 5;
            }

            $illegal_count  = 0;
            $total_price    = 0;

            $school_info = model('School')->where(array('id'=>$album['mhm_id']))->field('uid,school_and_teacher')->find();
            $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

            //课程属于机构 先平台和机构分成 再机构与教师分成
            $school_and_teacher  = array_filter(explode(':',$school_info['school_and_teacher']));
            if(is_array($school_and_teacher)){
                $school_info['sat_school']  = floatval($school_and_teacher[0]);
                $school_info['sat_teacher'] = floatval($school_and_teacher[1]);
            }

            //机构数据里的 机构与教师分成不存在
            if(empty($school_and_teacher)){
                return 7;
            }

            //班级所绑定的机构管理员不存在
            if(!$school_uid){
                return 8;
            }

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
                if($album_info[$key]['price']['price'] == 0){
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
            $data['school_sum']         = $school_num * $school_info['sat_school'];//机构获得的金额

            $data['st_id']              = trim($album_info['tuid_str'],',');//机构教师分成的所有教师用户id
            $data['school_teacher_sum'] = $school_num * $school_info['sat_teacher'];//机构教师分成的金额

            $school_uid = 1;
            //取得每个教师获得的金额
            $each_teacher_num = round($data['school_teacher_sum']/$album['video_count'],2);

            //如果平台管理员和机构管理员相同 销毁机构的金额 直接加到平台管理员账户上
            if($data['pid'] == $school_uid){
                $platform_sum_add = $school_num * $school_info['sat_school'];
                $platform_sum_new = $data['platform_sum'] + $platform_sum_add;
                $data['note']         .= "，平台管理员和机构管理员为同一人，分成金额为两者的分成金额{$data['platform_sum']}加上{$platform_sum_add}之和{$platform_sum_new}元";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额

                unset($data['sid']);        //机构分成的管理员用户id
                unset($data['school_sum']); //机构获得的金额
            }

            //如果有为平台管理员的课程 直接把讲师的那一份加到平台管理员上 $tuid_pcount为几份
            if($tuid_pcount > 0){
                $platform_sum_add = $each_teacher_num * $tuid_pcount;
                $platform_sum_new = $data['platform_sum'] + $platform_sum_add;
                $data['note']         .= "；此班级下平台管理员有{$tuid_pcount}堂课程，分成的金额为{$data['platform_sum']}元加上课程所得金额{$platform_sum_add}元,共计{$platform_sum_new}元";
                $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            }

            //如果有为机构管理员的课程 直接把讲师的那一份加到机构管理员上 $tuid_scount为几份
            if($tuid_scount > 0){
                $platform_sum_add = $each_teacher_num * $tuid_scount;
                $platform_sum_new = $data['school_sum'] + $platform_sum_add;
                $data['note']         .= "；此班级下机构管理员有{$tuid_pcount}堂课程，分成的金额为{$data['school_sum']}元加上课程所得金额{$platform_sum_add}元,共计{$platform_sum_new}元";
                $data['school_sum']   = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            }

        } else {
            //班级不属于机构
            return 5;
        }
        $data['ctime'] = time();//机构教师分成的金额

        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = intval($albumId);//所购买课程的id(包括点播、直播、班级)
        $map['split_type'] = 1;
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
     * 评论操作
     */
    public function addComment(){

        $data['uid'] = $this->mid;
        $data['fid'] = intval(t($_POST['to_uid']));
        $data['app_id'] = intval(t($_POST['app_id']));
        $data['app_uid'] = intval(t($_POST['app_uid']));
        $data['app_table'] = t($_POST['app_table']);
        $data['to_comment_id'] = intval(t($_POST['to_comment_id']));
        $model = M('zy_comment');
        if(!empty($data['to_comment_id'])){
            $comment = $model->where(array('id'=>$data['to_comment_id'],'is_del'=>0))->find();
            if(empty($comment)){
                $rtn['status'] = 0;
                $rtn['info'] = '评论内容不存在！';
                exit(json_encode($rtn));
            }
            $data['info'] = $comment['to_comment'];
        }
        $data['to_comment'] = filter_keyword(t($_POST['content']));
        $data['ctime'] = time();
        $rst = $model->add($data);
        if($rst){
            M('Album')->where(array('id'=>$data['app_id']))->setInc('comment_count',1);
            $credit = M('credit_setting')->where(array('id'=>19,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $type = 6;
                $note = '班级点评获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            $rtn['status'] = 1;
            $rtn['info'] = '评论成功！';
        }else{
            $rtn['status'] = 0;
            $rtn['info'] = '评论失败！';
        }
        exit(json_encode($rtn));
    }
    
    
    /**
     * 猜你喜欢
     */
    public function getLike(){
        $map = array();
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
        $map['uctime'] = array('GT',time());
        $datas = D('ZyGuessYouLike','classroom')->getGYLData(0,$this->mid,10);
        foreach ($datas as $key=> $val){
            $section = M('zy_video_section')->where(['pid'=>['neq',0],'vid'=>$val['id']])->field('is_free,vid')->findAll();
            foreach ($section as $k => $v){
                if($v['is_free'] == 1){
                    $datas[$key]['free_status'] = '可试听';
                }
            }
            $mhmName = model('School')->getSchoolInfoById($val['mhm_id']);
            $datas[$key]['mhmName'] = $mhmName['title'];
            //教师头像和简介
            $teacher = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->find();
            $datas[$key]['teacherInfo']['name'] = $teacher['name'];
            $datas[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $datas[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $datas[$key]['live']['count'] = $live_data['count'];
                $datas[$key]['live']['now'] = $live_data['now'];
            }
        }
        return $datas;
    }
    
    
    //直播数据处理
    protected function live_data($live_type,$id){
        $count = 0;
        //第三方直播类型
        if($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 3){
            $live_data = M('zy_live_gh')->where(array('live_id'=>$id,'is_del'=>0))->order('endTime asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['endTime'] < time()){
                        $count = $count + 1 ;
                    }
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;
    
        return $live_data;
    }
    
    
    /**
     * 异步加载班级列表
     */
    public function ajaxList(){
        $cid = intval(t($_GET['cid']));
        $tid = intval(t($_GET['tid']));
        $vid = intval(t($_GET['vid']));
        $time = intval(t($_GET['ctime']));
        
        $map = ' `status`=1 AND `is_mount`=1';
        $cid && $map .= ' AND FIND_IN_SET('.$cid.', album_category) ';
        if(!empty($time)){
            $ctime = strtotime($time.'-1-1 0:0:0');
            $map.= ' AND `ctime` < "'.strtotime('+1 year',$ctime).'" AND `ctime` >= "'.strtotime('-1 year',$ctime).'"' ;
        }
        
        if(!empty($vid)){
            $map.= ' AND `vip_level` = "'.$vid.'"';
        }

        /*TODO:讲师，会员类型、班型。先读取满足条件的课程，再读取班级*/
        if(!empty($tid)){
            $vMap['is_del'] = 0;
            $vMap['teacher_id'] = $tid;
            $video_teacher_list = M('zy_video')->where($vMap)->field('id')->limit(100)->select();
            
            $video_ids = getUniqure(getSubByKey($video_teacher_list,'id'));
            if($video_ids){
                $linkMap['video_id'] = array('in',$video_ids);
                $albums = M('album_video_link')->where($linkMap)->field('album_id')->limit(100)->select();
                $album_ids = getUniqure(getSubByKey($albums,'album_id'));
                $str = '';
                if(!empty($album_ids)){
                    foreach ($album_ids as $key=>$val){
                        $str.= $val.',';
                    }
                    $str = rtrim($str,',');
                    $map.= ' AND `id` in ('.$str.')';
                }else{
                    $map = '1=0';
                }
            }else{
                $map = '1=0';
            }
        }
        
        // 排序
        $order = '';
        $sort = t($_GET['sort']);
        $lower = intval(t($_GET['lower']));
        $toper = intval(t($_GET['toper']));

        switch ($sort){
            case 'new' :
                $order = ' `ctime` DESC';
                break;
            case 'price':
                if(!empty($lower) && !empty($toper)){
                    $map .= ' AND `price` >= "'.$lower.'" AND `price` <= "'.$toper.'"';
                    $order = ' `price` ASC';
                }
            break;
            case 'price_up':
                $order = ' `price` ASC';
                break;
            case 'price_down':
                $order = ' `price` DESC';
                break;
        }
        
        $limit = 6;
        $list = D('Album')->getList($map,$order,$limit);
        if($list['data']){
            $this->assign('list',$list);
            $html = $this->fetch ( 'ajaxList' );
        }else{
            $html = '暂无此类班级';
        }
        
        $list['data'] = $html;
        exit(json_encode($list));
        
    }


	//将课程添加到购物车
	public  function  addVideoShare($nvid,$ntype)
	{
		$vid = $nvid;
		$type = $ntype;

		if ($this->mid) {

		$map ['id'] = $vid;
		$map ['status'] = 1;

		$video_id = M('album')->where($map)->getField('id');


		$chars = 'JMRZaNTUbNOXcABIdFVWXeSAYlKLMmDEGInYZfDElCFGoPQjOPQkKLRSsGIJtTUgiVqBCJrWpELMuFKvPTUVwQRSxCDNOyBWXzAYZ';
		$share_str = $type . "H";
		for ($i = 0; $i < 4; $i++) {
			$share_str .= $chars[mt_rand(0, strlen($chars) - 1)];
		}
		$share_str .= "H" . $vid . "H";
		for ($i = 0; $i < 5; $i++) {
			$share_str .= $chars[mt_rand(0, strlen($chars) - 1)];
		}

		$share_url = U('classroom/Album/view', array('id' => $vid, 'code' => $share_str.'@@@'));

    //		$data['uid'] = $this->mid;
    //		$data['video_id'] = $video_id;
    //		$data['type'] = $type;
    //		$data['ctime'] = time();
    //		$data['tmp_id'] = $share_str;
    //		$data['share_url'] = $share_url;
    //
    //		$share_id = M('zy_video_share')->where(array('uid' => $this->mid, 'video_id' => $video_id))->getField('id');
    //		if ($share_id) {
    //			$res = M('zy_video_share')->where('id=' . $share_id)->save($data);
    //		} else {
    //			$res = M('zy_video_share')->add($data);
    //		}
        }else{
                $share_url = U('classroom/Album/view',array('id'=>$vid));
        }
		return $share_url;
	}
}
