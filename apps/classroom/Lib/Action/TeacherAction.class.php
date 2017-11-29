<?php
/**
 * Created by Ashang.
 * 云课堂教师风采控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
require_once './api/qiniu/rs.php';
require_once './api/cc/notify.php';

class TeacherAction extends CommonAction{
    protected $teacher = null;//讲师模型对象
    protected $passport=null;
    protected $base_config = array();
    protected $gh_config = array();
    protected $zshd_config = array();
    protected $user = array();
    protected $cc_video_config = array();  //定义cc配置
    public function _initialize(){
        $this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
        $this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
        $this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
        $this->cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');

        $this->teacher=D('ZyTeacher');
        $this->passport = model('Passport');
    }
    /**
     * 教师首页显示方法
     */
    public function index(){
        $where="t.is_del=0 AND t.is_reject=0 AND t.verified_status =1";
        $order="t.collect_num desc,t.views desc";
        //$subject_category= intval($_GET['subject_category']);

        $sort_type= intval($_GET['sort_type']);
        $course_type = $_GET['course_type'];
        $live_type = $_GET['live_type'];
        $area_id= intval($_GET['area']);

        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        if($cateId){
            $title=M("zy_teacher_category")->where('zy_teacher_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
        }
        $subject_category= M("zy_teacher_category")->where('pid=0')->order('sort asc')->field("zy_teacher_category_id,title")->select();
        $this->assign ( 'selCate', $subject_category );
        if($cateId) {
            $selCate = M("zy_teacher_category")->where(array('pid'=>$cateId[0]))->field("zy_teacher_category_id,title")->findALL();
            $this->assign('cate',$selCate);
        }
        if($cateId[1]){
            $selChildCate = M("zy_teacher_category")->where(array('pid'=>$cateId[1]))->field("zy_teacher_category_id,title")->findALL();
            $this->assign('childCate',$selChildCate);
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);
        $mhm_id = intval($_GET['mhm_id']);
        $search = t($_GET['search']);
        if($search){
            $where .= " AND (t.name like '%$search%' or t.inro like '%$search%' or t.details like '%$search%')";
        }else if($mhm_id > 0){
            $where .= " and t.mhm_id = $mhm_id";
        }
//        else if($this->getVisitCity()){
//            $where.= " AND u.city = " . $this->getVisitCity();
//        }

        if($sort_type>0){
            switch ($sort_type) {
                case 1:
                    $order="t.review_count desc , t.reservation_count desc , t.views desc";
                    break;
                case 2:
                    $order="t.ctime desc";
                    break;
                default;
            }
        }
        if ($cateId > 0) {
            $subject_category = implode(',',$cateId);
            $where .= " AND t.fullcategorypath like '%,$subject_category,%'";
        }
        if($course_type && !$live_type){
            $where .= " AND v.type = {$course_type}" ;
//            $where .= " AND v.type IN (". implode(',',$course_type) . " )" ;
        }

        if($area_id>0){
            $where.= " AND u.area = {$area_id}";
        }
        /*if($cateId){
            $this->assign('showNowArea',model('CategoryTree')->setTable('zy_currency_category')->getCategoryList($cateId));
        }*/
        $size=12;

        //获取科目列表
        $subject_category=M("zy_teacher_category")->where('pid=0')->findALL();
        //区域列表
        $area   = M("area")->where("pid=".$this->getVisitCity() ?:'110100')->findALL();
        //讲师列表
        if($live_type && !$course_type){
            //传入总记录数进行数据分页
            $data = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid ')
                ->where($where)
                ->field('t.id')
                ->order($order)
                ->findALL();

            $map['is_del']      = 0;
            $map['is_active']   = 1;
            $time = time();
            $where = "is_del=0 AND is_mount = 1 AND is_activity IN (1,5,6,7) AND uctime>$time AND listingtime<$time ";
            foreach($data as $key=>$value){
                $where .= "AND teacher_id = {$value['id']}";

                $live_true = M('zy_video')->where($where)->getField('teacher_id');
                if($live_true){
                    $data[$key]['live_true'] = 1;
                }
            }
            foreach($data as $key=>$value){
                if($value['live_true'] && $value['live_true'] == 1){
                    $mhm_id = D('ZyTeacher')->getTeacherStrByMap(array('id'=>$value['id']),'mhm_id');
                    $school = model('School')->where(array('id'=>$mhm_id))->field('status,is_del')->find();
                    if($school['status'] != 1 || $school['is_del'] == 1){
                        unset($data[$key]);
                    }
                }else{
                    unset($data[$key]);
                }
            }
            if($sort_type>0){
                switch ($sort_type) {
                    case 1:
                        $order="review_count desc , reservation_count desc , views desc";
                        break;
                    case 2:
                        $order="ctime desc";
                        break;
                    default;
                }
            }else{
                $order = "review_count desc,collect_num desc ,course_count desc ,views desc";
            }
            $data = D('ZyTeacher','classroom')->where(array('id'=>array('in',getSubByKey($data,'id'))))->order($order)->findPage($size);

        }else{
            $_count = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid LEFT JOIN '.C('DB_PREFIX').'zy_video as v ON t.id = v.teacher_id')
                ->where($where)
                ->field('count(distinct t.id) as count')
                ->order($order)
                ->find();
            //传入总记录数进行数据分页
            $data = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid LEFT JOIN '.C('DB_PREFIX').'zy_video as v ON t.id = v.teacher_id')
                ->where($where)
                ->field('t.id,count(distinct t.id) as count')
                ->group('t.id')
                ->order($order)
                ->findPage($size,$_count['count']);

            foreach($data['data'] as $key=>$value){
                $mhm_id = D('ZyTeacher')->getTeacherStrByMap(array('id'=>$value['id']),'mhm_id');
                $status = model('School')->getSchooldStrByMap(array('id'=>$mhm_id),'status');
                if($status != 1){
                    unset($data['data'][$key]);
                }
            }

            if($data){
                if($sort_type>0){
                    switch ($sort_type) {
                        case 1:
                            $order="review_count desc , reservation_count desc , views desc";
                            break;
                        case 2:
                            $order="ctime desc";
                            break;
                        default;
                    }
                }else{
                    $order = "review_count desc,collect_num desc ,course_count desc ,views desc";
                }
            }

            $data['data'] = D('ZyTeacher','classroom')->where(array('id'=>array('in',getSubByKey($data['data'],'id'))))->order($order)->select();
        }

        if ($data['data']) {
            foreach ($data['data'] as $key => &$value) {
                $max_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price desc")->field("course_price")->find();
                $min_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price")->field("course_price")->find();
                $value["max_price"]=$max_price ? $max_price["course_price"]: 0;
                $value["min_price"]=$min_price ? $min_price["course_price"]: 0;
                $value["video"] = M('zy_video')->where('is_del=0 and teacher_id='.$value['id'])->order('video_order_count desc')->field('id,video_title,t_price,video_order_count')->find();
                $value['teach_areas']=explode(",",$value['teach_areas']);
                $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$value['title'])->find();
                $value['teacher_title_category'] = $teacher_title['title'] ?: '普通讲师';
                if($teacher_title['cover']){
                    $value['teacher_title_cover'] = getCover($teacher_title['cover'],19,19);
                    $value['teacher_title_cover_id'] = $teacher_title['cover'];
                }
                $user = model('User')->getUserInfo($value['uid']);
                $value['Teacher_areas'] = $user['location'];
                //是否已经收藏讲师
                $isExist = D('ZyCollection')->where(array('source_id'=>$value['id'],'uid'=>$this->mid,'source_table_name'=>'zy_teacher'))->count();

                if($isExist){
                    $value['collection'] = 1;
                }else{
                    $value['collection'] = 0;
                }
                //机构名称
                $map=array('id'=>$value['mhm_id']);
                $value['school'] = model('School')->getSchooldStrByMap($map,'title') ? : '平台讲师';
                
                $star = M('zy_review')->where('tid='.$value['id'])->avg('star');
                $value['review_count']   =  M('zy_review')->where('tid='.$value['id'])->count();
                $value['star']           = round($star/20);
                //关注
                $follow_count = model('Follow')->getFollowCount($value['uid']);
                foreach($follow_count as $k=>&$v){
                    $value['follow_count'] = $v['follower'];
                }
                if(!$value['follow_count']){
                    $value['follow_count'] = '0';
                }
                //讲师课程
                $value['video_count']    = D('ZyVideo')->where('teacher_id='.$value['id'])->count();
                //讲师标签
                $value['label'] = array_filter(explode(",", $value['label']));
            }
        }
        //精选课程
        $maps ['is_mount']    = 1;
        $maps ['is_activity'] = 1;
        $maps ['is_del']      = 0;
        $maps ['type']        = 1;
        $maps ['uctime']      = array('gt',time());
        $maps ['listingtime'] = array('lt',time());
        $video  = D('ZyVideo')->where($maps)->order('video_comment_count desc','video_collect_count desc','video_score desc','video_order_count desc')->findALL();
        foreach ($video as $key=>$val) {
            $video[$key]['school_title'] = model('School')->getSchooldStrByMap('id='.$val['mhm_id'],'title');

            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $video[$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] ) {
                $video[$key]['t_price'] = 0;

            }
            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($video[$key]['uid']) || $thistid == $video[$key]['teacher_id'])
            {
                $video[$key]['t_price'] = 0;
            }

            if($thistid == $video[$key]['teacher_id'] &&  $video[$key]['teacher_id'] )
            {
                $video[$key]['t_price'] = 0;
            }
        }
        $maps['is_best'] = 1;
        $bestVideo  = D('ZyVideo')->where($maps)->order('video_comment_count desc','video_collect_count desc','video_score desc','video_order_count desc')->limit(3)->findALL();
        foreach ($bestVideo as $key=>$val) {
            $bestVideo[$key]['school_title'] = model('School')->getSchooldStrByMap('id='.$val['mhm_id'],'title');

            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $bestVideo[$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] ) {
                $bestVideo[$key]['t_price'] = 0;

            }
            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($bestVideo[$key]['uid']) || $thistid == $bestVideo[$key]['teacher_id'])
            {
                $bestVideo[$key]['t_price'] = 0;
            }

            if($thistid == $bestVideo[$key]['teacher_id'] &&  $bestVideo[$key]['teacher_id'] )
            {
                $bestVideo[$key]['t_price'] = 0;
            }
        }
        /*$senhotwd=M("zy_wenda_comment")->query("select * from ".C('DB_PREFIX')."zy_wenda  where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= ctime and is_del=0 ORDER BY wd_comment_count DESC");
        $teacher_schedule=M("zy_teacher_schedule")->where("pid=0")->findALL();*/
        //猜你喜欢
        $guess_you_like = D('ZyGuessYouLike')->getGYLData(0,$this->mid,7);
        foreach ($guess_you_like as $key=> $val){
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
            $guess_you_like[$key]['teacherInfo']['name'] = $teacher['name'];
            $guess_you_like[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $guess_you_like[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }

            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $guess_you_like[$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] ) {
                $guess_you_like[$key]['t_price'] = 0;

            }
            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($guess_you_like[$key]['uid']) || $thistid == $guess_you_like[$key]['teacher_id'])
            {
                $guess_you_like[$key]['t_price'] = 0;
            }

            if($thistid == $guess_you_like[$key]['teacher_id'] &&  $guess_you_like[$key]['teacher_id'] )
            {
                $guess_you_like[$key]['t_price'] = 0;
            }
        }

        $this->assign('listData', $data['data']);
        $this->assign('data', $data);
        $this->assign('area_id', $area_id);
        $this->assign('subject_category', $subject_category);
        $this->assign('mhm_id', $mhm_id);
        $this->assign('search', $search);
        $this->assign('reservation', $reservation);
        $this->assign('sort_type', $sort_type);
        $this->assign('course_type', $course_type);
        $this->assign('live_type', $live_type);

        //$this->assign("subject_category",$subject_category);
        //$this->assign("title_category",$title_category);
        $this->assign("area",$area);
        $this->assign("video",$video);
        $this->assign("guess_you_like",$guess_you_like);
        $this->assign("bestVideo",$bestVideo);
        //$this->assign("senhotwd",$senhotwd);
        //$this->assign("teacher_schedule",$teacher_schedule);
        $this->display();
    }

    public  function getTeacherList(){
        $where="t.is_del=0 AND t.is_reject=0 ";
        $order="t.collect_num desc,t.views desc";
        $sort_type= intval($_GET['sort_type']);
        $course_type = $_GET['course_type'];
        $area_id= intval($_GET['area']);

        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
        }
        if($cateId){
            $title=M("zy_currency_category")->where('zy_currency_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
        }
        $subject_category= M("zy_currency_category")->where('pid=0')->order('sort asc')->field("zy_currency_category_id,title")->select();
        $this->assign ( 'selCate', $subject_category );
        if($cateId) {
            $selCate = M("zy_currency_category")->where(array('pid'=>$cateId[0]))->field("zy_currency_category_id,title")->findALL();
            $this->assign('cate',$selCate);
        }
        if($cateId[1]){
            $selChildCate = M("zy_currency_category")->where(array('pid'=>$cateId[1]))->field("zy_currency_category_id,title")->findALL();
            $this->assign('childCate',$selChildCate);
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        $search = t($_GET['search']);
        if($search){
            $where .= " AND (t.name like '%$search%' or t.inro like '%$search%' or t.details like '%$search%')";
        }
//        else if($this->getVisitCity()){
//            $where.= " AND u.city = " . $this->getVisitCity();
//        }

        if($sort_type>0){
            switch ($sort_type) {
                case 1:
                    $order="t.review_count desc , t.reservation_count desc , t.views desc";
                    break;
                case 2:
                    $order="t.ctime desc";
                    break;
                default;
            }
        }
        if ($cateId > 0) {
            $subject_category = implode(',',$cateId);
            $where .= " AND t.fullcategorypath like '%,$subject_category,%'";
        }
        if($course_type && !$live_type){
            $where .= " AND v.type = {$course_type}" ;
        }
        $mhm_id = intval($_GET['mhm_id']);
        if($mhm_id > 0){
            $where .= " AND mhm_id = $mhm_id ";
        }
        if($area_id>0){
            $where.= " AND u.area = {$area_id}";
        }
        $size=10;
        //讲师列表
        if($live_type && !$course_type){
            //传入总记录数进行数据分页
            $data = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid ')
                ->where($where)
                ->field('t.id')
                ->order($order)
                ->findALL();

            $map['is_del']      = 0;
            $map['is_active']   = 1;
            foreach($data as $key=>$value){
                $map['speaker_id']  = $value['id'];
                $zshd = M('zy_live_zshd')->where($map)->field('speaker_id')->select();
                if($zshd){
                    $data[$key]['live_true'] = 1;
                }else{
                    $gh = M('zy_live_gh')->where($map)->field('speaker_id')->select();
                    if($gh){
                        $data[$key]['live_true'] = 1;
                    }else {
                        $cc = M('zy_live_cc')->where($map)->field('speaker_id')->select();
                        if($cc){
                            $data[$key]['live_true'] = 1;
                        }
                    }
                }
            }
            foreach($data as $key=>$value){
                if($value['live_true'] && $value['live_true'] == 1){
                    $mhm_id = D('ZyTeacher')->getTeacherStrByMap(array('id'=>$value['id']),'mhm_id');
                    $school = model('School')->where(array('id'=>$mhm_id))->field('status,is_del')->find();
                    if($school['status'] != 1 || $school['is_del'] == 1){
                        unset($data[$key]);
                    }
                }else{
                    unset($data[$key]);
                }
            }
            if($sort_type>0){
                switch ($sort_type) {
                    case 1:
                        $order="review_count desc , reservation_count desc , views desc";
                        break;
                    case 2:
                        $order="ctime desc";
                        break;
                    default;
                }
            }else{
                $order = "review_count desc,collect_num desc ,course_count desc ,views desc";
            }
            $data = D('ZyTeacher','classroom')->where(array('id'=>array('in',getSubByKey($data,'id'))))->order($order)->findPage($size);

        }else{
            $_count = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid LEFT JOIN '.C('DB_PREFIX').'zy_video as v ON t.id = v.teacher_id')
                ->where($where)
                ->field('count(distinct t.id) as count')
                ->order($order)
                ->find();
            //传入总记录数进行数据分页
            $data = M('zy_teacher t')
                ->join('LEFT JOIN '.C('DB_PREFIX').'user as u ON t.uid = u.uid LEFT JOIN '.C('DB_PREFIX').'zy_video as v ON t.id = v.teacher_id')
                ->where($where)
                ->field('t.id,count(distinct t.id) as count')
                ->group('t.id')
                ->order($order)
                ->findPage($size,$_count['count']);

            foreach($data['data'] as $key=>$value){
                $mhm_id = D('ZyTeacher')->getTeacherStrByMap(array('id'=>$value['id']),'mhm_id');
                $status = model('School')->getSchooldStrByMap(array('id'=>$mhm_id),'status');
                if($status != 1){
                    unset($data['data'][$key]);
                }
            }

            if($data){
                if($sort_type>0){
                    switch ($sort_type) {
                        case 1:
                            $order="review_count desc , reservation_count desc , views desc";
                            break;
                        case 2:
                            $order="ctime desc";
                            break;
                        default;
                    }
                }else{
                    $order = "review_count desc,collect_num desc ,course_count desc ,views desc";
                }
            }

            $data['data'] = D('ZyTeacher','classroom')->where(array('id'=>array('in',getSubByKey($data['data'],'id'))))->order($order)->select();
        }
        if ($data['data']) {
            foreach ($data['data'] as $key => &$value) {
                $max_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price desc")->field("course_price")->find();
                $min_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price")->field("course_price")->find();
                $value["max_price"]=$max_price ? $max_price["course_price"]: 0;
                $value["min_price"]=$min_price ? $min_price["course_price"]: 0;
                $value["video"] = M('zy_video')->where('is_del=0 and teacher_id='.$value['id'])->order('video_order_count desc')->field('id,video_title,t_price,video_order_count')->find();
                $value['teach_areas']=explode(",",$value['teach_areas']);
                $user = model('User')->getUserInfo($value['uid']);
                $value['Teacher_areas'] = $user['location'];
                //机构名称
                $map=array('id'=>$value['mhm_id']);
                $value['school'] = model('School')->getSchooldStrByMap($map,'title');

                $star = M('zy_review')->where('tid='.$value['id'])->avg('star');
                $value['review_count']   =  M('zy_review')->where('tid='.$value['id'])->count();
                $value['star']           = round($star/20);
                //关注
                $follow_count = model('Follow')->getFollowCount($value['uid']);
                foreach($follow_count as $k=>&$v){
                    $value['follow_count'] = $v['follower'];
                }
                if(!$value['follow_count']){
                    $value['follow_count'] = '0';
                }
                //讲师课程
                $value['video_count']    = D('ZyVideo')->where('teacher_id='.$value['id'])->count();
                //讲师标签
                $value['label'] = array_filter(explode(",", $value['label']));
            }
        }
        $this->assign('listData', $data['data']);
        $this->assign('data', $data);

        $html = $this->fetch('ajax_teacher');
        $data['data']=$html;
        exit( json_encode($data) );
    }
    //收藏讲师
    public function collect(){
        $zyCollectionMod = D('ZyCollection');
        $type   = intval($_POST['type']);//0:取消收藏;1:收藏;
        $source_id = intval($_POST['source_id']);//讲师ID

        if($_POST['uid']){
            $data['uid'] = intval($_POST['uid']);
        }else{
            $data['uid'] = intval($this->mid);
        }
        $map['id']=$source_id;
        $uid = D('ZyTeacher')->getTeacherStrByMap($map,'uid');
        if($data['uid'] == $uid){
            $this->mzError('不能收藏自己!');
        }
        $data['source_id'] = intval($source_id);
        $data['source_table_name'] = 'zy_teacher';
        $data['ctime'] = time();
        if(!$type){
            $i = $zyCollectionMod->delcollection($data['source_id'],$data['source_table_name'],$data['uid']);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                $credit = M('credit_setting')->where(array('id'=>55,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $ctype = 7;
                    $note = '取消收藏讲师扣除的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$ctype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                $this->mzSuccess('取消收藏成功!');
            }
        }else{
            $i = $zyCollectionMod->addcollection($data);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                $credit = M('credit_setting')->where(array('id'=>56,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $ctype = 6;
                    $note = '收藏讲师获得的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$ctype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                $this->mzSuccess('收藏成功!');
            }
        }
    }

    public function teacher_view(){
        $id   = intval($_GET['id']);
        $map = array('tid'=>$id,'is_del'=>0);
        M('zy_teacher')->where('id='.$id)->setInc('views');
        $teaMap = array('id'=>$id,'is_del'=>0,'is_reject'=>0,'verified_status'=>1);
        $data = $this->teacher->getTeacherInfoByMap($teaMap);
        if(!$data){
            $this->assign('isAdmin', 1);
            $this->assign('jumpUrl', U('classroom/Teacher/index'));
            $this->error('没有找到该教师!');
        }
        $data['Teacher_areas'] = model('User')->where(array('uid'=>$data['uid']))->getField('location');
        //讲师详情 背景
        $data['background'] = getAttachUrlByAttachId($data['background_id']);
        //猜你喜欢讲师数据
        D('ZyGuessYouLike')->opTypeGYL(3,reset(array_filter(explode(',',$data['teacher_category']))),$this->mid);
        $location =  M('user')->where('uid ='.$data['uid'])->getField('location');
        $user=model("User")->where("uid=".$data["uid"])->find();
        $data["sex"]=$user["sex"];
        if($data["sex"] == 1){$data["sex"] = "男";}
        else if($data["sex"] == 2){$data["sex"] = "女";}
        //所属机构
        $school = model('School')->getSchoolInfoById($data['mhm_id']);
        if($school){
            $mhm_id = $data['mhm_id'];
            $school['type'] = M('zy_currency_category')->where('zy_currency_category_id='.$school['school_category'])->getField('title');
            //当前用户关注状态
            $school['state'] = model('Follow')->getFollowState($this->mid, $school['uid']);
            //机构域名
            if($school['doadmin']){
                $school['domain'] = getDomain($school['doadmin']);
            }else{
                $school['domain'] = U('school/School/index',array('id'=>$school['school_id']));
            }
            // 处理数据
            $school ['reviewRate'] = D ( 'ZyReview' )->getCommentRate ( 1, intval ( $data ['id'] ) );
            //课程数
            $school['video_count'] = $school['count']['video_count'];
            //机构学生数量
//            $student = $school['count']['follower_count'];
            $student = model('Follow')->where(array('fid' => $school['uid']))->count();

            $user = model('User')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            $video = M('zy_order_course')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            foreach($video as $v){
                $v = implode(',',$v);
                $list[] = $v;
            }
            foreach($user as $v){
                $v = implode(',',$v);
                $new[] = $v;
            }
            $user_count = array_merge($list,$new);
            $user_count = count(array_unique($user_count)) ? : 1;

            $school['student'] = $student+$user_count;
        }

        //关注
        $follow_count = model('Follow')->getFollowCount($data['uid']);
        foreach($follow_count as $k=>&$v){
            $follow = $v['follower'];
        }
        if(!$follow){
            $follow = '0';
        }

        $data['label'] =  explode(",", $data['label']);
        //教师评价
        $star = M('zy_review')->where($map)->avg('star');
        $data['star'] = round($star/20);
        $data['favorable_rate'] = round($star,2).'%';

        if($data['teach_way'] == 0){$data['teach_way'] = '';}
        else if($data['teach_way'] == 1){$data['teach_way'] = '在线授课';}
        else if($data['teach_way'] == 2){$data['teach_way'] = '线下授课';}
        else if($data['teach_way'] == 3){$data['teach_way'] = '在线/线下授课均可';}

        //教师相册
        $photos = D('ZyTeacherPhotos')->getPhotosAlbumByTid($data['id']);
        foreach ($photos['data'] as $key=>$val){
            $data['video_count']   += $val['video_count'] ;
            $data['picture_count'] += $val['picture_count'];
        }
        //教师相册详情
        $tids = getSubByKey($photos['data'],'id');
        $photos_deatil = D('ZyTeacherPhotos')->where(array('tid'=>$data['id'],'is_del'=>0,'photo_id'=>['in',$tids]))->order('ctime desc')->findPage(8);


        //教师文章
        $article= M('zy_teacher_article')->where($map)->findPage();
        //教师资料--过往经历
        $map['type'] = 1;
        $experience = M('zy_teacher_details')->where($map)->findALL();
        //教师资料--相关案例
        $map['type'] = 2;
        $case = M('zy_teacher_details')->where($map)->findALL();
        //讲师课程
        $time = time();
        $where = "teacher_id=$id AND is_del=0 AND type=1 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $data['video'] = D('ZyVideo')->where($where)->count();
        if(!$data['video']){
            $data['video'] = 0;
        }
        //教师试听课程
        $tcourse = D("ZyVideo")->where($where)->order('rand()')->limit(1)->getField('id');
        //3g 页面 讲师课程
        if(!($this->is_pc)){
            $video_list = D('ZyVideo')->where($where)->findPage(20);
        }
        //讲师等级
        $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$data['title'])->find();
        $data['teacher_title_category'] = $teacher_title['title'] ?: '普通讲师';
        if($teacher_title['cover']){
            $data['teacher_title_cover'] = getCover($teacher_title['cover'],19,19);
        }

        //获取用户关注状态
        $fstatus=M('UserFollow')->where(array('uid'=>$this->mid,'tid'=>$id))->find();
        if($fstatus){
            $this->assign("isfollow",true);
        }
        $star = M('zy_review')->where('tid ='.$map['tid'])->Avg('star');
        $star = $star/20;
        $star = round($star,0);
        $staro = $star.".0";
        $count = M('zy_review')->where('tid ='.$map['tid'])->count();
        $fivemap['tid'] =$map['tid'];
        $fivemap['star'] =100;
        $fivestarcount =  M('zy_review')->where($fivemap)->count();
        $fourmap['tid'] =$map['tid'];
        $fourmap['star'] =80;
        $fourstarcount =  M('zy_review')->where($fourmap)->count();
        $threemap['tid'] =$map['tid'];
        $threemap['star'] =60;
        $threestarcount =  M('zy_review')->where($threemap)->count();
        $twomap['tid'] =$map['tid'];
        $twomap['star'] =40;
        $twostarcount =  M('zy_review')->where($twomap)->count();
        $onemap['tid'] =$map['tid'];
        $onemap['star'] =20;
        $onestarcount =  M('zy_review')->where($onemap)->count();
        $fivestar = ($fivestarcount/$count)*100;
        $fivestar = round($fivestar,0)."%";
        $fourstar = ($fourstarcount/$count)*100;
        $fourstar = round($fourstar,0)."%";
        $threestar =($threestarcount/$count)* 100;
        $threestar = round($threestar,0)."%";
        $twostar = ($twostarcount/$count)* 100;
        $twostar = round($twostar,0)."%";
        $onestar =  ($onestarcount/$count)* 100;
        $onestar = round($onestar,0)."%";
        $url = U('classroom/Teacher/view',array('id'=>$id));

        $ordermap['tid'] = $id;
        $ordermap['uid'] = $this -> mid;
        $ordermap['is_del'] = 0;
        $ordermap['pay_status'] = 3;
        $ordermap['type'] = 1;
        $onlineorder = M('zy_order_teacher') -> where($ordermap) -> getField('id');

        $ordermap['type'] = 2;
        $offorder = M('zy_order_teacher') -> where($ordermap) -> getField('id');

        $meetcoursemap['teacher_id'] = $id;
        $meetcoursemap['is_del'] = 0;
        $meetcoursemap['is_activity'] = 1;
        $meetcoursecount = M("zy_teacher_course")->where($meetcoursemap)-> count();

        $meetonemap['tid'] =$id;
        $meetonemap['is_del'] = 0;
        $meetonemap['pay_status'] = 3;
        $meetonecount = M("zy_order_teacher")->where($meetonemap)->count();


        $vmap['is_del'] = 0;
        $vmap['is_activity'] = 1;
        $vmap['type'] = 2;
        $vmap['listingtime'] = array('lt', time());
        $vmap['uctime'] = array('gt', time());
        $vmap['teacher_id'] = $id;
        $livecount = M('zy_video')->where($vmap)-> count();

        if(!$livecount)
        {
            $livecount = 0;
        }

        $time = time();
        $tuid  =  M('zy_teacher')->where('id='.$id)->getField('uid');

        $videowhere = " (uid = {$tuid} OR teacher_id=$id) AND is_del=0 AND type=1 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $coursecount =M("zy_video")->where($videowhere)->count();

        $this->assign ( 'onlineorder', $onlineorder );
        $this->assign ( 'coursecount', $coursecount );
        $this->assign ( 'meetonecount', $meetonecount );
        $this->assign ( 'meetcoursecount', $meetcoursecount );
        $this->assign ( 'livecount', $livecount );
        $this->assign ( 'offorder', $offorder );
        $this->assign ( 'offorder', $offorder );
        $this->assign ( 'url', $url );
        $this->assign ( 'location', $location );
        $this->assign("school",$school);
        $this->assign("follow",$follow);
        $this->assign("experience",$experience);
        $this->assign("case",$case);
        $this->assign("photos",$photos['data']);
        $this->assign("photos_deatil",$photos_deatil['data']);
        $this->assign("article",$article);
        $this->assign("data",$data);
        $this->assign("fstatus",$fstatus);
        $this->assign("star", $star);
        $this->assign("staro", $staro);
        $this->assign("fivestar", $fivestar);
        $this->assign("fourstar", $fourstar);
        $this->assign("threestar", $threestar);
        $this->assign("twostar", $twostar);
        $this->assign("onestar", $onestar);
        $this->assign("count", $count);
        $this->assign("video_list", $video_list);
        $this->assign("teauid", $data['uid']);
        $this->assign("tcourse", $tcourse);
    }
    public function getPhotoList(){
        $photo_id = intval($_GET['photo_id']);
        //教师相册详情
        $photos_deatil = D('ZyTeacherPhotos')->getPhotoDataByPhotoId($photo_id);

        $player_type = getAppConfig("player_type");

        $this->teacher_view();
        $this->assign("photos_deatil",$photos_deatil['data']);
        $this->assign("player_type",$player_type);
        $this->display('photo_list');
    }
    //视频播放
    public function getVideoAddress(){
        $pic_id = intval($_POST['pic_id']);
        $photo_data = D('ZyTeacherPhotos')->where('pic_id='.$pic_id)->field('resource,videokey,video_type')->find();
        if($photo_data['video_type'] == 1){
            // 七牛
            //域名防盗链
            Qiniu_SetKeys(getAppConfig('qiniu_AccessKey', 'qiniuyun'), getAppConfig('qiniu_SecretKey', 'qiniuyun'));
            $mod = new Qiniu_RS_GetPolicy();
            $photo_data['address'] = $mod->MakeRequest($photo_data['resource']);
        }else if($photo_data['video_type'] == 4){
            $photo_data['address'] = $this->cc_video_config;
            $photo_data['videokey'] = $photo_data['videokey'];
        }
        if($photo_data)exit(json_encode(array('status'=>'1','data'=>$photo_data)));
        exit(json_encode(array('status'=>'0','message'=>'视频加载失败')));
    }
    public function view(){
        $this->teacher_view();
        $this->display('view_new');
    }
    public function getVideoCourseList(){
        $tid = intval($_GET['tid']);
        $time = time();
        $uid   =   M('zy_teacher')->where('id ='.$tid)->getField('uid');
        $where = " (uid = {$uid} OR teacher_id=$tid) AND is_del=0 AND type=1 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $course = M("zy_video")->where($where)->order('rand()')->findPage(2);
        foreach ($course['data'] as $key => $val) {
            $count = M('zy_video_section')->where('vid='.$val['id'])->count();
            $course['data'][$key]['section_num'] = $count;
            $changeprice = getPrice($val, $this->mid, true, true);
            $course['data'][$key]['price'] = $changeprice['price'];
        }

        $this->assign("course",$course);
        if($this->is_pc){
            $html = $this->fetch('course_list');
        }else{
            $html = $this->fetch('course_w3g_list');
        }

        $course['data'] = $html;
        echo json_encode($course);
        exit;
    }


    public function getmeetCourseList(){
        $tid = intval($_GET['tid']);
        $map['teacher_id'] = $tid;
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
        $course = M("zy_teacher_course")->where($map)->order('rand()')->findPage(2);
        foreach ($course['data'] as $key => $val) {
            $teacher = M("zy_teacher")->where('id='.$tid)->field('name,teach_areas')->find();
            $course['data'][$key]['teach_areas']  = $teacher['teach_areas'];
            $course['data'][$key]['price'] = $val['course_price'];
            $val['t_price'] = $val['course_price'];
            $course['data'][$key]['price'] = getPrice($val,$this->mid);
        }
        $this->assign("course",$course);
        if($this->is_pc){
            $html = $this->fetch('meetcourse_list');
        }else{
//            $html = $this->fetch('course_w3g_list');
        }
        $course['data'] = $html;
        echo json_encode($course);
        exit;

    }


    public function getmeetoneCourseList(){

        $map['tid'] = intval($_GET['tid']);
        $map['is_del'] = 0;
        $map['pay_status'] = 3;
        $course = M("zy_order_teacher")->where($map)->order('rand()')->findPage(2);
        foreach ($course['data'] as $key => $val) {
            if($val['type'] == 1)
            {
                $course['data'][$key]['type'] = "在线试听";
            }
            if($val['type'] == 2)
            {
                $course['data'][$key]['type'] = "线下试听";
            }
        }

        $this->assign("course",$course);
        if($this->is_pc){
            $html = $this->fetch('meetone_list');
        }else{

        }
        $course['data'] = $html;
        echo json_encode($course);
        exit;
    }



    public function getliveCourseList(){

        $limit      = 9;
        $tid = intval($_GET['tid']);

        $vmap['is_del'] = 0;
        $vmap['is_activity'] = 1;
        $vmap['type'] = 2;
        $vmap['listingtime'] = array('lt', time());
        $vmap['uctime'] = array('gt', time());
        $vmap['teacher_id'] = $tid;
        $data = M('zy_video')->where($vmap)->order('rand()')->findPage(2);

        foreach($data['data'] as $key =>$val) {
            $count = M('zy_video_section')->where(array('vid' => $val['id'], 'pid' => ['gt', '0']))->count();
            $data['data'][$key]['section_num'] = $count;
            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $data['data'][$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] ) {
                $data['data'][$key]['t_price'] = 0;

            }
            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($data['data'][$key]['uid']) || $thistid == $data['data'][$key]['teacher_id'])
            {
                $data['data'][$key]['t_price'] = 0;
            }

            if($thistid == $data['data'][$key]['teacher_id'] &&  $data['data'][$key]['teacher_id'] )
            {
                $data['data'][$key]['t_price'] = 0;
            }


            if ($val['live_type'] == 1) {
                $livetall = M('zy_live_zshd')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($val['live_type'] == 3) {
                $livetall = M('zy_live_gh')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($val['live_type'] == 4) {
                $livetall = M('zy_live_cc')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($thistid) {
                $tids = trim(implode(',', array_unique(getSubByKey($livetall, 'speaker_id'))), ',');
                $tids = "," . $tids . ',';

                $chtid = ',' . $thistid . ',';

                if (strstr($tids, $chtid)) {
                    $data['data'][$key]['t_price'] = 0;
                }
            }
        }

        $this->assign("live",$data);
        if($this->is_pc){
            $html = $this->fetch('live_list');
        }else{
//            $html = $this->fetch('course_w3g_list');
        }
        $data['data'] = $html;
        echo json_encode($data);exit;
        exit;
    }



    public function article(){
        $this->teacher_view();
        $this->display();
    }
    public function checkDeatil(){
        $map = array("id"=>$_GET['aid'],'is_del'=>0);
        $teacher_article = M("zy_teacher_article")->where($map)->find();
        if(!$teacher_article){
            $this->error("文章不存在");
        }
        $this->teacher_view();
        $this->assign($teacher_article);
        $this->display();
    }
    public function video(){
        $this->teacher_view();


        $tid = intval($_GET['id']);
        $time = time();
        $uid   =   M('zy_teacher')->where('id ='.$tid)->getField('uid');
        $where = " (uid = {$uid} OR teacher_id=$tid) AND is_del=0 AND type=1 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $course = M("zy_video")->where($where)->order('ctime desc')->findPage(6);
        foreach ($course['data'] as $key => $val) {
            $count = M('zy_video_section')->where(array('vid'=>$val['id'],'pid'=>['gt','0']))->count();
            $course['data'][$key]['section_num'] = $count;
            $changeprice = getPrice($val, $this->mid, true, true);
            $course['data'][$key]['price'] = $changeprice['price'];
        }

        //教师直播课程

        if($this->base_config['live_opt'] == 1){
            $map['speaker_id']  = $tid;
            $map['is_del']      = 0;
            $map['is_active']   = 1;
            $field = 'id,subject,roomid,startDate,invalidDate,teacherJoinUrl,studentJoinUrl,teacherToken,assistantToken,studentClientToken,live_id';
            $live_data = M('zy_live_zshd')->where($map)->order('invalidDate asc')->field($field)->select();
            $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
            $vmap['id'] = ['in',$live_id];
            $vmap['is_del'] = 0;
            $vmap['is_activity'] = 1;
            $vmap['type'] = 2;
            $vmap['listingtime'] = array('lt', time());
            $vmap['uctime'] = array('gt', time());
            $data = M('zy_video')->where($vmap)->order('ctime desc')->findPage(6);

        }else if($this->base_config['live_opt'] == 2){

        }else if($this->base_config['live_opt'] == 3){
            $map['speaker_id']  = $tid;
            $map['is_del']      = 0;
            $map['is_active']   = 1;
            $field = 'live_id';
            $live_data = M('zy_live_gh')->where($map)->order('invalidDate asc')->field($field)->select();

            $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
            $vmap['id'] = ['in',$live_id];
            $vmap['is_del'] = 0;
            $vmap['is_activity'] = 1;
            $vmap['type'] = 2;
            $vmap['listingtime'] = array('lt', time());
            $vmap['uctime'] = array('gt', time());
            $data = M('zy_video')->where($vmap)->order('ctime desc')->findPage(6);

//            $val['url'] = $this->gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0';
        }else if($this->base_config['live_opt'] == 4){
            $map['speaker_id']  = $tid;
            $map['is_del']      = 0;
            $map['is_active']   = 1;
            $field = 'id,subject,roomid,startDate,invalidDate,teacherJoinUrl,studentJoinUrl,teacherToken,assistantToken,studentClientToken,studentToken,live_id';
            $live_data = M('zy_live_cc')->where($map)->order('invalidDate asc')->field($field)->select();
            $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
            $vmap['id'] = ['in',$live_id];
            $vmap['is_del'] = 0;
            $vmap['is_activity'] = 1;
            $vmap['type'] = 2;
            $vmap['listingtime'] = array('lt', time());
            $vmap['uctime'] = array('gt', time());
            $data = M('zy_video')->where($vmap)->order('ctime desc')->findPage(6);

        }

        foreach($data['data'] as $key =>$val) {
            $count = M('zy_video_section')->where(array('vid'=>$val['id'],'pid'=>['gt','0']))->count();
            $data['data'][$key]['section_num'] = $count;
            //如果为管理员/机构管理员自己机构的课程 则免费
            if (is_admin($this->mid) || $val['is_charge'] == 1) {
                $data['data'][$key]['t_price'] = 0;
            }
            if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] != null) {
                $data['data'][$key]['t_price'] = 0;
            }

            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($data['data'][$key]['uid']) || $thistid == $data['data'][$key]['teacher_id'])
            {
                $data['data'][$key]['t_price'] = 0;
            }

            if($thistid == $data['data'][$key]['teacher_id'] &&  $data['data'][$key]['teacher_id'] != null)
            {
                $data['data'][$key]['t_price'] = 0;
            }

            if ($val['live_type'] == 1) {
                $livetall = M('zy_live_zshd')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($val['live_type'] == 3) {
                $livetall = M('zy_live_gh')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($val['live_type'] == 4) {
                $livetall = M('zy_live_cc')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
            }
            if ($thistid) {
                $tids = trim(implode(',', array_unique(getSubByKey($livetall, 'speaker_id'))), ',');
                $tids = "," . $tids . ',';

                $chtid = ',' . $thistid . ',';

                if (strstr($tids, $chtid)) {
                    $data['data'][$key]['t_price'] = 0;
                }
            }
        }








        $meetonemap['tid'] = $tid;
        $meetonemap['is_del'] = 0;
        $meetonemap['pay_status'] = 3;
        $meetone = M("zy_order_teacher")->where($meetonemap)->order('ctime desc')->findPage(6);
        foreach ($meetone['data'] as $key => $val) {
            if($val['type'] == 1)
            {
                $meetone['data'][$key]['type'] = "在线试听";
            }
            if($val['type'] == 2)
            {
                $meetone['data'][$key]['type'] = "线下试听";
            }
        }

        //线下课程
        $mtmap['teacher_id'] = $tid;
        $mtmap['is_del'] = 0;
        $mtmap['is_activity'] = 1;
        $meetcourse = M("zy_teacher_course")->where($mtmap)->order('ctime desc')->findPage(6);
        foreach ($meetcourse['data'] as $key => $val) {
            $teacher = M("zy_teacher")->where('id='.$tid)->field('name,teach_areas')->find();
            $meetcourse['data'][$key]['teach_areas']  = $teacher['teach_areas'];
            $meetcourse['data'][$key]['price'] = $val['course_price'];
            $val['t_price'] = $val['course_price'];
            $meetcourse['data'][$key]['price'] = getPrice($val,$this->mid);
        }

        $this->assign("meetcourse",$meetcourse);
        $this->assign("meetone",$meetone);
        $this->assign('mid',$this ->mid);
        $this->assign("live",$data);
        $this->assign("course",$course);
        $this->display();
    }




    public function getCourseList(){



        $tid   = intval($_GET['tid']);
        $type = intval(t($_GET['type']));
        $time = time();


        $order = ($_GET['orderby'] == 'hot') ? 'video_order_count desc' : 'ctime desc';
        if($type == 3){
            //教师点播课程
            $uid =  M('zy_teacher') ->where('id='.$_GET['tid'])->getField('uid');
            $teacher_id = intval($_GET['tid']);
            $videomap['_string'] =  " uid = {$uid} OR teacher_id = {$teacher_id}";
            $videomap['is_del'] = 0;
            $videomap['type'] = 1;
            $videomap['is_activity'] = 1;
            $videomap['is_mount'] = 1;
            $videomap['uctime']    = array('gt',time());
            $videomap['listingtime'] = array('lt',time());


            $course = D("ZyVideo")->where($videomap)->order($order)->findPage(6);
            foreach ($course['data'] as $key => $val) {
                $section['vid'] = $val['id'];
                $count = M('zy_video_section')->where(array('vid'=>$val['id'],'pid'=>['gt','0']))->count();
                $course['data'][$key]['section_num'] = $count;
                $changeprice = getPrice($val, $this->mid, true, true);
                $course['data'][$key]['price'] = $changeprice['price'];
            }
            $this->assign("course",$course);
            $html = $this->fetch('course_list');
            $course['data'] = $html;
            echo json_encode($course);
            exit;
        }else  if($type == 1){
            //教师直播课程



            if($this->base_config['live_opt'] == 1){
                $map['speaker_id']  = $tid;
                $map['is_del']      = 0;
                $map['is_active']   = 1;
                $field = 'id,subject,roomid,startDate,invalidDate,teacherJoinUrl,studentJoinUrl,teacherToken,assistantToken,studentClientToken,live_id';
                $live_data = M('zy_live_zshd')->where($map)->order('invalidDate asc')->field($field)->select();
                $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
                $vmap['id'] = ['in',$live_id];
                $vmap['is_del'] = 0;
                $vmap['is_activity'] = 1;
                $vmap['type'] = 2;
                $vmap['listingtime'] = array('lt', time());
                $vmap['uctime'] = array('gt', time());
                $live = M('zy_video')->where($vmap)->order($order)->findPage(6);

            }else if($this->base_config['live_opt'] == 2){

            }else if($this->base_config['live_opt'] == 3){
                $map['speaker_id']  = $tid;
                $map['is_del']      = 0;
                $map['is_active']   = 1;
                $field = 'live_id';
                $live_data = M('zy_live_gh')->where($map)->order('invalidDate asc')->field($field)->select();

                $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
                $vmap['id'] = ['in',$live_id];
                $vmap['is_del'] = 0;
                $vmap['is_activity'] = 1;
                $vmap['type'] = 2;
                $vmap['listingtime'] = array('lt', time());
                $vmap['uctime'] = array('gt', time());
                $live = M('zy_video')->where($vmap)->order($order)->findPage(6);

//            $val['url'] = $this->gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0';
            }else if($this->base_config['live_opt'] == 4){
                $map['speaker_id']  = $tid;
                $map['is_del']      = 0;
                $map['is_active']   = 1;
                $field = 'id,subject,roomid,startDate,invalidDate,teacherJoinUrl,studentJoinUrl,teacherToken,assistantToken,studentClientToken,studentToken,live_id';
                $live_data = M('zy_live_cc')->where($map)->order('invalidDate asc')->field($field)->select();
                $live_id = trim(implode(',',array_unique(getSubByKey($live_data,'live_id'))),',');
                $vmap['id'] = ['in',$live_id];
                $vmap['is_del'] = 0;
                $vmap['is_activity'] = 1;
                $vmap['type'] = 2;
                $vmap['listingtime'] = array('lt', time());
                $vmap['uctime'] = array('gt', time());
                $live = M('zy_video')->where($vmap)->order($order)->findPage(6);

            }

            foreach($live['data'] as $key =>$val) {
                $count = M('zy_video_section')->where(array('vid'=>$val['id'],'pid'=>['gt','0']))->count();
                $live['data'][$key]['section_num'] = $count;
                //如果为管理员/机构管理员自己机构的课程 则免费
                if (is_admin($this->mid) || $val['is_charge'] == 1) {
                    $live['data'][$key]['t_price'] = 0;
                }
                if (is_school($this->mid) == $val['mhm_id'] && $val['mhm_id'] ) {
                    $live['data'][$key]['t_price'] = 0;

                }
                //如果是讲师自己的课程 则免费

                $mid = $this -> mid;
                $thistid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
                if($mid == intval($live['data'][$key]['uid']) || $thistid == $live['data'][$key]['teacher_id'])
                {
                    $live['data'][$key]['t_price'] = 0;
                }

                if($thistid == $live['data'][$key]['teacher_id'] &&  $live['data'][$key]['teacher_id'] )
                {
                    $live['data'][$key]['t_price'] = 0;
                }

                if ($val['live_type'] == 1) {
                    $livetall = M('zy_live_zshd')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
                }
                if ($val['live_type'] == 3) {
                    $livetall = M('zy_live_gh')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
                }
                if ($val['live_type'] == 4) {
                    $livetall = M('zy_live_cc')->where(array('live_id' => $val['id'], 'is_del' => 0, 'is_active' => 1))->field('speaker_id')->select();
                }
                if ($thistid) {
                    $tids = trim(implode(',', array_unique(getSubByKey($livetall, 'speaker_id'))), ',');
                    $tids = "," . $tids . ',';

                    $chtid = ',' . $thistid . ',';

                    if (strstr($tids, $chtid)) {
                        $live['data'][$key]['t_price'] = 0;
                    }
                }
            }

            $this->assign("live",$live);
            $html = $this->fetch('live_list');
            $live['data'] = $html;
            echo json_encode($live);
            exit;
        }else  if($type == 4){

            $order = ($_GET['orderby'] == 'hot') ? 'id desc' : 'ctime desc';
            $map['tid'] = intval($_GET['tid']);
            $map['is_del'] = 0;
            $map['pay_status'] = 3;
            $course = M("zy_order_teacher")->where($map)->order('rand()')->findPage(6);
            foreach ($course['data'] as $key => $val) {
                if($val['type'] == 1)
                {
                    $course['data'][$key]['type'] = "在线试听";
                }
                if($val['type'] == 2)
                {
                    $course['data'][$key]['type'] = "线下试听";
                }
            }

            $this->assign("course",$course);

            $html = $this->fetch('meetone_list');
            $live['data'] = $html;
            echo json_encode($course);
            exit;

        }else if($type == 2)  {
            $order = ($_GET['orderby'] == 'hot') ? 'course_order_count desc' : 'ctime desc';
            $coursmap['teacher_id'] = $tid;
            $coursmap['is_del'] = 0;
            $coursmap['is_activity'] = 1;
            $course = M("zy_teacher_course")->where($coursmap)->order($order)->findPage(6);
            foreach ($course['data'] as $key => $val) {
                $teacher = M("zy_teacher")->where('id='.$tid)->field('name,teach_areas')->find();
                $course['data'][$key]['teach_areas']  = $teacher['teach_areas'];
                $course['data'][$key]['price'] = $val['course_price'];
                $val['t_price'] = $val['course_price'];
                $course['data'][$key]['price'] = getPrice($val,$this->mid);
            }

            $this->assign("course",$course);
            $html = $this->fetch('meetcourse_list');
            $course['data'] = $html;
            echo json_encode($course);
            exit;

        }







    }
    public function style(){
        $this->teacher_view();
        $this->display();
    }
    public function details(){
        $this->teacher_view();
        $this->display();
    }
    public function evaluate(){
        $this->teacher_view();
        $this->display();
    }

    /**
     * 获取讲师课程方法
     */
    public function getVideoList(){
        $teacher_id=intval($_GET['tid']);
        $order="id DESC";
        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= $teacher_id";
        $size=12;
        $data = D('ZyVideo')->where($where)->order($order)->findPage($size);
        foreach($data['data'] as &$val){
            $val['imageurl']=getAttachUrlByAttachId($val['cover']);

        }
        if ($data['data']) {
            $this->assign('listData', $data['data']);
            $html = $this->fetch('video_list');
        }else{
            $html="";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }
    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 3){
            $live_data = M('zy_live_gh')->where(array('live_id'=>$id,'is_del'=>0))->order('endTime asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['endTime'] < time()){
                        $count = $count + 1 ;
                    }
                    $startDate   = $value['beginTime'];
                    $invalidDate = $value['endTime'];
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                    $startDate   = $value['startDate'];
                    $invalidDate = $value['invalidDate'];
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;
        $live_data['startDate'] = $startDate;
        $live_data['invalidDate'] = $invalidDate;

        return $live_data;
    }

    public function getTeachNote(){
        $where="is_del=0";
        $order="ctime desc";
        $course_teacher = intval($_GET['id']);
        $teacher_info = $this->teacher->getTeacherInfo($course_teacher);
        $inSql = "SELECT course_id FROM ".C('DB_PREFIX')."zy_teacher_course WHERE course_teacher=".$teacher_info['uid'];
        $where .= " AND course_id IN($inSql)";
        $data=M("zy_teacher_review")->where($where)->order($order)->findPage(10);
        if($data['data']) {
            foreach ($data['data'] as $key => $value) {
                $data['data'][$key]["course_info"]=M("zy_teacher_course")->where("course_id=".$value["course_id"])->find();
                $data['data'][$key]["user_info"]=M("user")->where('uid='.$value["uid"])->field('uname')->find();
            }
            $this->assign('listData', $data['data']);
            $this->assign('course_teacher', $course_teacher);
            $this->assign('uid', $this->mid);
            $html = $this->fetch('teacher_note');
        }else{
            $html="<div style=\"margin-top:20px;\">对不起，暂无评论信息T_T</div>";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }
    /**
     * 讲师详情页面(老版)
     */
    public function viewdeatil(){
        $id=intval($_GET['id']);
        $teacher_info=$this->teacher->getTeacherInfo($id);
        if(!empty($teacher_info['uid'])){
            $this->assign("is_user",true);
        }
        //查询讲师最近课程
        $videoinfo=D('ZyVideo')->where(array('teacher_id'=>$id,'is_del'=>0))->field('id,video_title')->order('ctime DESC')->find();
        //查询相关课程个数
        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= $id";
        $count=D('ZyVideo')->where($where)->count();
        $this->assign('videoinfo',$videoinfo);
        $this->assign('count',$count);
        $this->assign("data",$teacher_info);
        $this->display();
    }
    public function addreview(){
        //要添加的数据
        $map=array(
            'course_id'=>intval($_POST['id']),
            'description'=>t($_POST['description']),
            'skill'=>t($_POST['skill']),
            'ctime'=>time(),
            'Professional'=>t($_POST['Professional']),
            'attitude'=>t($_POST['attitude']),
            'star'=>t($_POST['star']),
            'uid'=>$this->mid
        );
        $res=M("zy_teacher_review")->data($map)->add();

        if($res){
            $_data['review_count'] = array('exp','`review_count` + 1');
            //班级
            M('zy_teacher')->where(array('id'=>array('eq',intval($_POST["teacher_id"]))))->save($_data);
            exit(json_encode(array('status'=>'1','info'=>'评论成功')));
        }else{
            exit(json_encode(array('status'=>'0','info'=>'评论失败')));
        }
    }
    public function delreview(){
        $id=intval($_GET["id"]);
        $res=M("zy_teacher_review")->where("id=".$id)->data(array("is_del"=>1))->save();
        if($res){
            $_data['review_count'] = array('exp','`review_count` - 1');
            //班级
            M('zy_teacher')->where(array('id'=>array('eq',intval($_GET["teacher_id"]))))->save($_data);
            exit(json_encode(array('status'=>'1','info'=>'删除成功')));
        }else{
            exit(json_encode(array('status'=>'0','info'=>'删除失败')));
        }
    }
    public function buyCourse(){
        $map=array(
            'uid'=>$this->mid,
            'course_id'=>intval($_POST["course_id"]),
            'course_price'=>$_POST["course_price"],
            'teacher_id'=>intval($_POST["teacher_id"]),
            'teach_way'=>intval($_POST["teach_way"]),
            'ctime'=>time()
        );
        $course_name=$_POST["course_name"];
        if(!$this->mid){
            $this->ajaxReturn('', '请先登录', '1');
        }
        if (!$_POST['course_id']) {
            $this->ajaxReturn('', '没有选择课程', '1');
        }
        if (M("zy_order_course")->where(array("uid"=>$this->mid,"course_id"=>intval($_POST['course_id'])))->find()) {
            $this->ajaxReturn('', '您已预约此课程', '1');
        }
        if (!D('ZyLearnc')->isSufficient($this->mid, $_POST["course_price"], 'balance')) {
            $this->ajaxReturn('', '可支配的学币不足', '3');
        }
        $res=M("zy_order_course")->add($map);
        if($res){
            M()->query("UPDATE `".C('DB_PREFIX')."zy_teacher` SET `reservation_count`=`reservation_count`+1 WHERE `id`=".intval($_POST["teacher_id"]));
            //发送系统消息
            $teacher = M('zy_teacher')->where('id='.$_POST['teacher_id'].' and is_del=0')->field("name,uid")->find();
            //扣除学币
            D("ZyLearnc","classroom")->consume($this->mid, $_POST["course_price"]);
            //给学生发送信息
            $s['uid']   = $this->mid;
            $s['title'] = "恭喜您约课成功";
            $s['body']  = "恭喜您成功预约".$teacher["name"]."老师的课".',联系方式：'.getUserContact($teacher["uid"]);
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);
            //给老师发送信息
            $t['uid']   = $teacher["uid"];
            $t['title'] = "有学生约您课了！";
            $t['body']  = "您的课程".$course_name."被".getUserName($this->mid)."预约！联系方式：".getUserContact($this->mid);
            $t['ctime'] = time();
            model('Notify')->sendMessage($t);
            exit(json_encode(array('status'=>'1','info'=>'约课成功')));
        }else{
            exit(json_encode(array('status'=>'0','info'=>'约课失败')));
        }
    }
    public function doLogin(){
        $login 		= addslashes($_POST['login_email']);
        $password 	= trim($_POST['login_password']);
        $remember	= intval($_POST['login_remember']);
        $result 	= $this->passport->loginLocal($login,$password,$remember);
        if(!$result){
            $status = 0;
            $info	= $this->passport->getError();
            $data 	= 0;
        }else{
            $this->redirect('/');
        }
    }


    /**
     * 取得评论列表
     *
     * @param boolean $return
     *        	是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getreList($return = false)
    {
        $map['tid'] = t($_GET['tid']);
        if (isset($_GET['type'])) {
            $map['type'] = t($_GET['type']);
            if($map['type'] == 0)
            {
                unset($map['type']);
            }
        }
        if (isset($_GET['order'])) {
            $order = t($_GET['order']);
            if($order == "default")
            {
                $order = "review_collect_count DESC";
            }
            if($order == "rentime")
            {
                $order = "ctime DESC";
            }
        }
        $size = intval ( getAppConfig ( 'video_list_num', 'page', 10 ) );
        $data =M('zy_review')->where ($map)->order($order)->findPage ( 3 );
        if ($data ['data']) {
            foreach ($data['data'] as $k=>$v) {
                $v['ctime']=getDateDiffer($v['ctime']);//格式化时间数据
                if ($v['type'] == 1) {
                    //$data['data'][$k]['oid'] = getVideoNameForID($v['oid']);
                    $data['data'][$k]['type_name'] = '视频课程';
                } else if ($v['type'] == 2) {
                    $data['data'][$k]['type_name'] = '班级课程';
                }else if ($v['type'] == 3) {
                    //$data['data'][$k]['oid'] = getAlbumNameForID($v['oid']);
                    $data['data'][$k]['type_name'] = '线下课程';
                } else if ($v['type'] == 4) {
                    $data['data'][$k]['type_name'] = '讲师评价';
                }
                $data['data'][$k]['tname'] = M('zy_teacher')->where('id ='.$v['tid'])->getField('name');
                $data['data'][$k]['star'] = $v['star']/20;
            }
            if($data['html'] == null)
            {
                $data['html'] ='';
            }
            $this->assign ( 'listData', $data['data'] );
            $html = $this->fetch ('view_list');
        } else {
            $html = '暂无此类评价';
            if($data['html'] == null)
            {
                $data['html'] ='';
            }
        }
        $data ['data'] = $html;
        if ($return) {
            return $data;
        } else {
            echo json_encode($data);
            exit ();
        }

    }
    /**
     * 设置赞+1
     */
    public function addZan(){
        $id=intval($_POST['id']);
        $map=array(
            'uid'=>$this->mid,
            'article_id'=>$id
        );
        $res=M('zy_article_praise')->where($map)->find();
        if($res){
            echo "500";
            exit;
        }else{
            M('zy_article_praise')->add($map);
            M('zy_teacher_article')->where('id='.$id)->setInc('praise');
            echo 200;
            exit;
        }
    }


    /**
     * 设置有用+1
     */
    public function addyong(){
        $id=intval($_POST['id']);
        $map=array(
            'uid'=>$this->mid,
            'review_id'=>$id
        );
        $res=M('zy_review_help')->where($map)->find();
        if($res){
            echo "500";
            exit;
        }else{
            M('zy_review_help')->add($map);
            M('zy_review')->where('id='.$id)->setInc('yong');
            echo 200;
            exit;
        }

    }

    /***
     * 添加评价
     */
    public function add()
    {
        if (isset($_POST)) {
            $data['star'] = t($_POST['score'])*20;
            $data['review_description'] = filter_keyword(t($_POST['content']));
            $data['Professional'] = t($_POST['Professional']);
            $data['attitude'] = t($_POST['attitude']);
            $data['skill'] = t($_POST['skill']);
            $data['tid'] = t($_POST['tid']);
            $data['ctime'] = time();
            $data['uid'] = t($_POST['review_uid']);
            $data['oid'] = t($_POST['tid']);
            $data['type'] = 4;
            $data['review_source'] = 'web网页';
        }
        $res = M('zy_review')->add($data);
        if ($res) {
            $credit = M('credit_setting')->where(array('id'=>29,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $rtype = 6;
                $note = '评价讲师获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$rtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            $this->mzSuccess('评价成功');
        } else {
            $this->mzError('评价失败');
        }
    }



    public function buymeetteacher()
    {

        $map['id'] =  t($_GET['tid']);
        $meettype = $_GET['meetingtype'];
        $words = $_GET['words'];
        $meetmoney = $_GET['meetmoney'];
        $map['uid'] = $_GET['teauid'];


        $data = M('zy_teacher') ->where($map) ->  field('id,uid,offline_price,name,online_price') ->select();

        if($meettype == 'online')
        {
            $money = $data[0]['online_price'];
            $meettypetitle = '线上课程';
            $tdata['type'] = 1;
        }
        if($meettype == 'offline')
        {
            $money = $data[0]['offline_price'];
            $meettypetitle = '线下课程';
            $tdata['type'] = 2;
        }
        if($meetmoney != $money)
        {
            echo "请勿篡改价格";
            exit;
        }


        if($meetmoney == 0) {

            $tdata['price'] = $money;
            $tdata['ctime'] = time();
            $tdata['uid'] = $this->mid;
            $tdata['pay_status'] = 3;
            $tdata['tid'] =  t($_GET['tid']);
            $tdata['words'] = $words;
            if ($money == 0) {
                $tdata['pay_status'] = 3;
                $res = M('zy_order_teacher')->add($tdata);
                if ($res) {
                    $credit = M('credit_setting')->where(array('id'=>30,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $ytype = 6;
                        $note = '预约讲师获得的积分';
                    }
                    model('Credit')->addUserCreditRule($this->mid,$ytype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

                    $this->success("预约成功");
                } else {
                    $this->success("预约失败");
                }
            }
        }

        $this ->assign('money',$money);
        $this ->assign('words',$words);
        $this ->assign('meettype',$meettype);
        $this ->assign('meettypetitle',$meettypetitle);
        $this ->assign('tid', $map['id']);
        $this ->assign('data',$data[0]);
        $this ->assign('tuid', $map['uid']);

        $this->display();
    }
}


?>