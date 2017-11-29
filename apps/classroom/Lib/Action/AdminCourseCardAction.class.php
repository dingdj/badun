<?php
/**
 * 课程卡管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminCourseCardAction extends AdministratorAction{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

/**
     * 初始化专题配置
     * 
     * @return void
     */
    private function _initTabSpecial() {
        // Tab选项
        $this->pageTab [] = array (
                'title' => '列表',
                'tabHash' => 'index',
                'url' => U ( 'classroom/AdminCourseCard/index' )
        );
        $this->pageTab [] = array (
                'title' => '添加',
                'tabHash' => 'addCoupon',
                'url' => U ( 'classroom/AdminCourseCard/addCoupon' )
        );
    }

    public function index(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','列表');
        //页面配置
        $id     =  intval($_POST['id']);
        $sid    =  intval($_POST['sid']);
        $code   =  intval($_POST['code']);
        $video_type = intval($_POST['video_type']);
        $this->pageKeyList = array('id','school_title','code','video_type','video_title','exp_date','status','count','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','sid','code','video_type');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delCoupon')");
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        $this->opt['video_type'] = array('1'=>'点播','2'=>'直播','3'=>'班级');
        $map = array('type'=>5,'coupon_type'=>0);
        if(!empty($id))$map['id']=$id;
        if(!empty($sid))$map['sid']=$sid;
        if(!empty($code))$map['code']=$code;
        if(!empty($video_type))$map['video_type']=$video_type;
		$coupon = model('Coupon');
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] = 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
				$coupon->setStatus($val['id'],0);
            }
            $school = model('School')->where(array('id'=>$val['sid']))->field('id,doadmin,title')->find();
            $url = getDomain($school['doadmin'],$school['id']);
            $val['school_title'] = getQuickLink($url,$school['title'],"未知机构");

            if($val['video_type'] == 1){
                $val['video_title'] = M('zy_video')->where(array('id'=>$val['video_id']))->getField('video_title');
                $url = U('classroom/Video/view', array('id' => $val['video_id']));
            }else if($val['video_type'] == 2){
                $val['video_title'] = M('zy_video')->where(array('id'=>$val['video_id']))->getField('video_title');
                $url = U('live/Index/view', array('id' => $val['video_id']));
            }else{
                $val['video_title'] = M('album')->where(array('id'=>$val['video_id']))->getField('album_title');
                $url = U('classroom/Album/view', array('id' => $val['video_id']));
            }
            $val['video_title'] = getQuickLink($url,$val['video_title'],"未知课程");

            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
			$val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);

            if($val['video_type'] == 1){
                $val['video_type'] = "点播";
            }else if($val['video_type'] == 2){
                $val['video_type'] = "直播";
            }else{
                $val['video_type'] = "班级";
            }

            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未领取</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已领取</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'课程卡\');">启用</a>';
            }else {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'课程卡\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminCourseCard/addCoupon',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }
    /**
     * 添加课程卡
     * Enter description here ...
     */
    public function addCoupon(){
        $id   = intval($_GET['id']);

        $this->_initTabSpecial();
        $map['is_del'] = 0;
        $map['status'] = 1;
        $field = 'id,title';
        $school = model('School')->getAllSchol($map,$field);
        $this->assign('school',$school);
        if($id){
            $coupon = model('Coupon')->where( 'id=' .$id )->find ();
            if($coupon['video_type'] == 3){
                $video = M('album')->where(array('id'=>$coupon['video_id']))->field('id,album_title')->find();
                $video['title'] = $video['album_title'];
            }else{
                $video = M('zy_video')->where(array('id'=>$coupon['video_id']))->field('id,video_title')->find();
                $video['title'] = $video['video_title'];
            }
            $coupon['end_time'] = date("Y-m-d H:i:s",$coupon['end_time']);
            $this->assign('id',$id);
            $this->assign('pageTitle','修改');
            $this->assign('coupon',$coupon);
            $this->assign('video',$video);
        }else{
			$this->assign('pageTitle','添加');
        }
        $this->display();
    }

    /**
     * 加载点播/直播/班级数据
     */
    public function getVideoInfoList(){
        $video_type = intval($_POST['video_type']);
        $mhm_id = intval($_POST['mhm_id']);

        if($video_type == 3){
            $where = "status=1 AND is_mount=1 AND mhm_id=$mhm_id";
            $table = 'album';
        }else{
            $time = time();
            $where = "is_del=0 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time AND type=$video_type AND mhm_id=$mhm_id";
            $table = 'zy_video';
        }
        if($table == 'zy_video'){
            $video = M($table)->where($where)->field('id,video_title')->findALL();
            $data['data'] = array_column($video, 'video_title', 'id');
        }else{
            $album = M($table)->where($where)->field('id,album_title')->findALL();
            $data['data'] = array_column($album, 'album_title', 'id');
        }
        if($data['data']){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        echo json_encode($data);
        exit;
    }
    /**
     * 处理添加课程卡
     */
    public function doAddCoupon(){
        $id		=	intval($_POST['id']);

        /*$count  = 	intval($_POST['counts']);
        if($count == 0){
            $count = 1;
        }*/
        $params = array(
            'type'=> 5,
            'sid'=>intval($_POST['mhm_id']),
            'video_type'=>intval($_POST['video_type']),
            'video_id'=>intval($_POST['video_id']),
            //'coupon_type'=>1,
            'exp_date'=>intval($_POST['exp_date']),
            'end_time'=>strtotime($_POST['end_time']),
            'count'=>intval($_POST['count']),
            'ctime'=>time(),
        );
//        if($params['type'] == 1){
//            $params['maxprice'] = t($_POST['maxprice']);
//            $params['price'] = t($_POST['price']);
//            $params['discount'] = '0.00';
//        }else{
//            $params['discount'] = t($_POST['discount']);
//            $params['maxprice'] = '0.00';
//            $params['price'] = '0.00';
//        }

        //要添加的数据

        if(!$id){
			/*$num = 0;
			$couponModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
				$params['code'] = $this->create_code();
				$params['ctime'] = time();
				$couponModel->add($params);
				$num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个课程卡！");*/
            $params['code'] = $this->create_code();
            $res=model('Coupon')->add($params);
            if(!$res)$this->error("对不起，添加失败！");
            $this->success("添加课程卡成功!");
        }else{
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改课程卡成功!");
        }
    }
	/**
	  * @name 生成code方法
	  * 
	  */
	private function create_code(){
		static $codeList;
		$code = date('ydis',time()).substr(time(),-3).mt_rand(10000,99999);
		//检测code是否已经存在
		if(in_array($code,$codeList)){
			return $this->create_code();
		}
		$codeList[] = $code;
		return $code;
	}
     /**
     * 禁用/启用 课程卡
     */
    public function closeCoupon()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('coupon')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('coupon')->where($where)->save($data);

        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    /**
     * 批量禁用优惠券
     * @return void
     */
    public function delCoupon(){
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where=array(
            'id'=>array('in',$ids)
        );
        $is_del = M('coupon')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res =  M('coupon')->where($where)->save($data);
        
        if($res!==false){
            $msg['data']="操作成功";
            $msg['status']=1;
            echo json_encode($msg);
        }else{
            $msg['data']="操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    /*
    *
    *彻底删除优惠券
    */
    /*public function delCouponCard(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        $res = M('coupon')->where($where)->delete();
        
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }*/
}