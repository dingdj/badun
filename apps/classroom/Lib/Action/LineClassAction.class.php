<?php
/**
 * 云课堂课程(视频)控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload ( APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php' );
class LineClassAction extends CommonAction {
	protected $video = null; // 课程模型对象
	protected $category = null; // 分类数据模型
    protected $cc_video_config = array();  //定义cc配置

	/**
	 * 初始化
	 */
	public function _initialize() {
		$this->video = M ( 'zy_teacher_course' );
		$this->category = model ( 'VideoCategory' );
        $this->cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');
	}

	/**
	 * 课程详情页面
	 * 
	 * @return void
	 */
	public function view()
    {
        $this->view_info();
        $this->display ();
	}

    private function view_info(){
        $id = intval($_GET ['id']);
        $map ['course_id'] = $id;
        $map ['is_activity'] = 1;
        $map ['is_del'] = 0;
        $map ['uctime'] = array('gt', time());

        $data = $this->video->where($map)->find();
        if (!$data) {
            $this->assign('isAdmin', 1);
            $this->error('课程不存在~~~~!!');
        }
        //添加围观人数
        $this->video->where($map)->setInc('view_nums');
        //讲师信息
        $teacher = M("zy_teacher")->where("id=" . $data["teacher_id"])->find();
        if ($teacher) {
            $data['user'] = $teacher;
            //讲师等级
            $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $data['user']['title'])->find();
            $data['user']['teacher_title'] = $teacher_title['title'];
        }
        // 是否已购买
        $data['is_buy'] = M('zy_order_teacher')->where(array('uid'=>$this->mid, 'video_id'=>$id))->getField('pay_status') ? : 0;

        $mhm_id = $data['mhm_id'];
        if ($mhm_id) {
            //机构信息
            $mhmData = model('School')->getSchoolInfoById($mhm_id);
            //机构域名
            $mhmData['domain'] = getDomain($mhmData['doadmin'],$mhmData['school_id']);
        }
        //线下课价格
        $data['t_price'] = $data['course_price'];
        $data['mzprice']['price'] = getPrice($data,$this->mid);
        //查看购买状态
        $tid = M('zy_teacher')->where('uid =' .$this->mid)->getField('id');
        if ($tid != null  &&  $tid == $data['teacher_id']) {
            $mybuy = 1;
        }
        if (is_school($this->mid) == $data['mhm_id'] &&  $data['mhm_id'] != null) {
            $mybuy = 1;
        }
        if ($this->mid == $data['mhm_id'])
        {
            $mybuy = 1;
        }

        //讲师联系方式
        $contactWay = model('User')->where('uid='.$data['user']['uid'])->field('phone,email')->find();

		$url = U('classroom/Video/view',array('id'=>$id));
        $this->assign ( 'url', $url );
        $this->assign ( 'uid', $this->mid );
        $this->assign ( 'mybuy', $mybuy );
        $this->assign ( 'vid', $id );
        $this->assign ( 'mhmData', $mhmData );
        $this->assign ( 'data', $data );
        $this->assign('contactWay',$contactWay);
    }

	/**
	 * 课程(视频)首页页面
	 *
	 * @return void
	 */
    public function index() {
        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        if($cateId){
            $title=M("zy_currency_category")->where('zy_currency_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
        }
        $subject_category=M("zy_currency_category")->where('pid=0')->order('sort asc')->field("zy_currency_category_id,title")->select();
        $this->assign ( 'selCate', $subject_category );
        if($cateId) {
            $selCate = $this->category->getChildCategory($cateId[0]);
            $this->assign('cate',$selCate);
            $this->assign('cate_count',count($selCate));
        }
        if($cateId[1]){
            $selChildCate = $this->category->getChildCategory($cateId[1]);
            $this->assign('childCate',$selChildCate);
            $this->assign('childCate_count',count($selChildCate));
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        $orders = array (
            'default' => 'view_nums DESC,course_id DESC',
            'new' => 'ctime DESC',
            't_price' => 'course_price ASC',
            't_price_down' => 'course_price DESC',
            'hot' => 'course_order_count DESC,view_nums DESC',
        );
        if (isset ( $orders [$_GET ['orderBy']] )) {
            $order = $orders [$_GET ['orderBy']];
        } else {
            $order = $orders ['default'];
        }

        $time = time ();
        $where = "is_del=0 AND is_activity=1 ";
        if(isset($_GET['time'])){
            list($listingtime,$uctime) = explode(',',$_GET['time']);
            if($listingtime &&  $uctime){
                $where .= " AND ((( $listingtime<listingtime and listingtime<$uctime) or ( $listingtime<uctime and uctime<$uctime))
                or ($listingtime<listingtime and uctime<$uctime ) or (listingtime<$listingtime and $uctime<uctime ))";
            }
        }else{
            $where .= " AND uctime>$time ";
            $listingtime = strtotime(date('Y-m-d',time()));
            $uctime = $listingtime+86400;
        }
        if(isset($_GET['tid'])){
            $tid =  $_GET['tid'];
            $where .=" AND teacher_id = $tid";
        }

        if ($cateId > 0) {
            $video_category = implode(',',$cateId);
            $where .= " AND fullcategorypath like '%,$video_category,%'";
        }
        if ($_GET['search']){
            $search = t($_GET['search']);
            $where .= " AND (course_name like '%$search%' or course_binfo like '%$search%')";
        }
        if ($_GET['mhm_id'] > 0){
            $mhm_id = intval($_GET['mhm_id']);
            $where .= " AND mhm_id = $mhm_id ";
        }
        //机构名称
        $mhm_title = model('School')->where('id='.$mhm_id)->getField('title');
        /*if($this->is_pc){
            $size = intval ( getAppConfig ( 'video_list_num', 'page', 6 ) );
        }else{
            $size = 6;
        }*/
        $size = 6;

        $data = $this->video->where ( $where )->order ( $order )->findPage ( $size );
        if ($data ['data']) {
            $buyVideos = D ( 'zyOrder' )->where ( "`uid`=" . $this->mid . " AND `is_del`=0" )->field ( 'video_id' )->select ();
            foreach ( $buyVideos as $key => &$val ) {
                $val = $val ['video_id'];
            }
            // 计算价格
            foreach ( $data ['data'] as $key => &$value ) {
                //线下课价格
                $value['t_price'] = $value['course_price'];
                $value['course_price'] = getPrice($value,$this->mid);
                $value['teacher_uid'] = D('ZyTeacher')->where('id='.$value['teacher_id'])->getField('uid');
                $value['teach_areas'] = D('ZyTeacher')->where('id='.$value['teacher_id'])->getField('Teach_areas');
                // 是否已购买
                $value['is_buy'] = M('zy_order_teacher')->where(array('uid'=>$this->mid, 'video_id'=>$value['course_id']))->getField('pay_status');
                //教师用户uid
                $teacher_uid = M('zy_teacher')->where(array('id' => $value['teacher_id']))->getField('uid');
                $value['teacher_uid'] = M('user')->where(array('uid' => $teacher_uid))->getField('uid');
                //判断是否下架
                if($value['uctime'] < time()){
                    $this->video->where ( 'course_id='.$value['course_id'] )->save(array('is_del'=>1));
                }
            }
        }

        //获取机构列表
        $schoolMap = array(
            'status'=>1,
            'is_del'=>0,
        );
        $field = 'id,title';
        $school = model('School')->getAllSchol($schoolMap,$field);

        $this->assign ( 'listingtime', $listingtime );
        $this->assign ( 'uctime', $uctime );
        $this->assign ( 'orderBy', $_GET ['orderBy'] ); // 定义排序
        $this->assign ( 'lower', $_GET ['lower'] ); // 定义收费类型
        $this->assign ( 'search', $_GET ['search'] ); // 搜索
        $this->assign('mhm_id', $mhm_id);//机构ID
        $this->assign('mhm_title', $mhm_title);//机构ID
        $this->assign('data', $data);
        $this->assign('school', $school);

        $this->display();
    }

    /*public function index() {
        $this->display();
    }*/
	/**
	 * 取得课程分类
	 */
	public function getCategroy() {
		$id = intval ( $_GET['id'] );
		if ($id > 0) {
			$data = $this->category->getChildCategory($id);
		}
		if (empty ( $data )){
            $data = null;
        }else{
            if($_GET['lv'] == 0){
                foreach ($data as $k=>$v){
                    $data[$k]['selName'] = 'country';
                    $data[$k]['lv'] = intval($_GET['lv']) + 1;
                }
            }elseif($_GET['lv'] == 1){
                foreach ($data as $k=>$v){
                    $data[$k]['selName'] = 'pre';
                    $data[$k]['lv'] = intval($_GET['lv']) + 1;
                }
            }elseif ($_GET['lv'] == 2){
                foreach ($data as $k=>$v){
                    $data[$k]['selName'] = 'citys';
                    $data[$k]['lv'] = intval($_GET['lv']) + 1;
                }
            }
        }

		echo json_encode($data);
        exit;
	}

	/**
	 * 取得课程列表
	 *
	 * @param boolean $return
	 *        	是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
	 * @return void|array
	 */
	public function getVideoList($return = false)
    {
        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        if($cateId){
            $title=M("zy_currency_category")->where('zy_currency_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
        }
        $subject_category=M("zy_currency_category")->where('pid=0')->order('sort asc')->field("zy_currency_category_id,title")->select();
        $this->assign ( 'selCate', $subject_category );
        if($cateId) {
            $selCate = $this->category->getChildCategory($cateId[0]);
            $this->assign('cate',$selCate);
            $this->assign('cate_count',count($selCate));
        }
        if($cateId[1]){
            $selChildCate = $this->category->getChildCategory($cateId[1]);
            $this->assign('childCate',$selChildCate);
            $this->assign('childCate_count',count($selChildCate));
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        $orders = array (
            'default' => 'view_nums DESC,course_id DESC',
            'new' => 'ctime DESC',
            't_price' => 'course_price ASC',
            't_price_down' => 'course_price DESC',
            'hot' => 'course_order_count DESC,view_nums DESC',
        );
        if (isset ( $orders [$_GET ['orderBy']] )) {
            $order = $orders [$_GET ['orderBy']];
        } else {
            $order = $orders ['default'];
        }

        $time = time ();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time ";
		$cateId = intval ( $_GET ['cateId'] );
		if ($cateId > 0) {
            $where .= " AND fullcategorypath like '%,$cateId,%'";
        }
        if ($_GET['search']){
            $search = t($_GET['search']);
            $where .= " AND (course_name like '%$search%' or course_binfo like '%$search%')";
        }
        $mhm_id = intval($_GET['mhm_id']);
        if($mhm_id > 0){
            $where .= " AND mhm_id = $mhm_id ";
        }
		$size = 6;
        $data = $this->video->where ( $where )->order ( $order )->findPage ( $size );
        if ($data ['data']) {
            $buyVideos = D ( 'zyOrder' )->where ( "`uid`=" . $this->mid . " AND `is_del`=0" )->field ( 'video_id' )->select ();
            foreach ( $buyVideos as $key => &$val ) {
                $val = $val ['video_id'];
            }
            // 计算价格
            foreach ( $data ['data'] as $key => &$value ) {
                //线下课价格
                $value['t_price'] = $value['course_price'];
                $value['course_price'] = getPrice($value,$this->mid);
                $value['teacher_uid'] = D('ZyTeacher')->where('id='.$value['teacher_id'])->getField('uid');
                $value['teach_areas'] = D('ZyTeacher')->where('id='.$value['teacher_id'])->getField('Teach_areas');
                // 是否已购买
                $value['is_buy'] = M('zy_order_teacher')->where(array('uid'=>$this->mid, 'video_id'=>$value['course_id']))->getField('pay_status');
                //教师用户uid
                $teacher_uid = M('zy_teacher')->where(array('id' => $value['teacher_id']))->getField('uid');
                $value['teacher_uid'] = M('user')->where(array('uid' => $teacher_uid))->getField('uid');
                //判断是否下架
                if($value['uctime'] < time()){
                    $this->video->where ( 'course_id='.$value['course_id'] )->save(array('is_del'=>1));
                }
            }
            $this->assign ( 'listData', $data['data'] );
            $html = $this->fetch ( 'ajax_class' );
            $data['data']=$html;
        }
		if ($return) {
			return $data;
		} else {
			echo json_encode($data);
			exit ();
		}
	}

}