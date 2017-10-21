<?php
/**
 * Created by Ashang.
 * 机构展示控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/school/Lib/Action/CommonAction.class.php');
class IndexAction extends CommonAction{
    /**
    * 初始化，配置内容标题
    * @return void
    */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 机构首页显示方法
     */
    public function index(){
        $map = " status=1 AND is_del = 0";
        $order = "collect_num desc,visit_num desc,review_count desc,view_count desc";
        $area_id = intval($_GET['area']);
        $sort_type = intval($_GET['sort_type']);

        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
            $title=M("school_category")->where('school_category_id='.end($cateId))->getField("title");
            $this->assign('title',$title);
        }
        $subject_category= M("school_category")->where('pid=0')->order('sort asc')->field("school_category_id,title")->select();
        $this->assign ( 'selCate', $subject_category );
        if($cateId) {
            $selCate = M("school_category")->where(array('pid'=>$cateId[0]))->field("school_category_id,title")->findALL();
            $this->assign('cate',$selCate);
        }
        if($cateId[1]){
            $selChildCate = M("school_category")->where(array('pid'=>$cateId[1]))->field("school_category_id,title")->findALL();
            $this->assign('childCate',$selChildCate);
        }
        $this->assign('cateId', $cateId[0]);
        $this->assign('cate_id', $cateId[1]);
        $this->assign('cate_ids', $cateId[2]);

        if($_GET['search']){
            $search = t($_GET['search']);
            $map .= " AND (title like '%$search%' or info like '%$search%')";
        }else if($this->getVisitCity()){
            //$map.= " AND city = " . $this->getVisitCity();
        }
        if($area_id > 0){
            $map.= " AND area = {$area_id}";
            $title=M("area")->where('area_id='.$area_id)->getField("title");
            $this->assign('area_title',$title);
        }
        if($cateId){
            $school_category = implode(',',$cateId);
            $map .= " AND fullcategorypath like '%,$school_category,%'";
        }
        if($sort_type == 1){
            $order = 'view_count desc,view_count desc,collect_num desc';
        }elseif($sort_type == 2){
            $order = 'ctime desc';
        }
        //机构列表
        $data = model('School')->where($map)->order($order)->findPage(8);
        if ($data['data']) {
            foreach ($data['data'] as $key => &$value) {
                $maps = array('mhm_id'=>$value['id']);
                foreach (array_filter(explode(',', $value['str_tag'])) as $k => $v){
                    $value['new_str_tag'] .= "<span>{$v}</span>";
                }
                $value['counts'] = D('ZyVideo')->where($maps)->count();
                /*$user = model('User')->findUserInfo($value['uid'],'location');
                $value['location'] = $user['location'];*/
                $value['learn_count'] = D('zy_order_course')->where($maps)->count();
                $value['teacher_count'] = D('ZyTeacher')->where($maps)->count();
                //机构等级
                $school_vip = M('school_vip')->where(['id'=>$value['school_vip']])->find();
                $value['school_vip_name'] = $school_vip['title'] ?: '普通机构';
                if($school_vip['cover']){
                    $value['school_vip_cover'] = getCover($school_vip['cover'],19,19);
                }
                //是否已经收藏机构
                $isExist = D('ZyCollection','classroom')->where(array('source_id'=>$value['id'],'uid'=>$this->mid,'source_table_name'=>'school'))->count();
                if($isExist){
                    $value['collection'] = 1;
                }else{
                    $value['collection'] = 0;
                }
                //机构评价（课程）
                $value['review_count'] =  M('zy_review')->where('mhm_id='.$value['id'])->count() ? : 11;

               $schoolmap['mhm_id'] = $value['id'];
               $schoolmap['is_del'] = 0;
               $schoolmap['is_activity'] = 1;
                $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();

                $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
                $vmap['oid'] = ['in',$live_id];
                $vmap['is_del'] = 0;


                //机构评价（讲师）
                $review_ocount =  M('zy_review')->where($vmap)->count() ;
                $value['review_count'] = $review_ocount ? : 11;
                $ostar =  M('zy_review')->where($vmap)->avg('star');
                $tidmap['mhm_id'] = $value['id'];
                $tidmap['is_del'] = 0;
                $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
                $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');

                $vtmap['tid'] = ['in',$tid];
                $vtmap['is_del'] = 0;
                $review_tcount = M('zy_review')->where($vtmap)->count();
                $value['review_count'] =   $value['review_count'] + ($review_tcount ? : 3);

                $tstar =   M('zy_review')->where($vtmap)->avg('star');

                $value['star'] =  ceil(($tstar + $ostar)/2/20);

                $star = $value['star'] * 20;
                $value['favorable_rate'] = round($star,2).'%' ? : 0;
                //机构域名
                if($value['doadmin'] != 'www'){
                    $value['domain'] = getDomain($value['doadmin']);
                }else{
                    $value['domain'] = U('school/School/index',array('id'=>$value['id']));
                }

            }
        }
        $this->assign('data',$data);
        $this->assign('listData',$data['data']);
        $this->assign('area_id',$area_id);
        $this->assign('sort_type',$sort_type);
        
        //猜你喜欢
        $map = array();
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
        $map['uctime'] = array('GT',time());
        $datas = D('ZyGuessYouLike','classroom')->getGYLData(0,$this->mid,9);
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

            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $datas[$key]['live']['count'] = $live_data['count'];
                $datas[$key]['live']['now'] = $live_data['now'];
                $datas[$key]['mzprice']['price'] = $val['t_price'];
                $datas[$key]['mzprice']['oriPrice'] = $val['v_price'];
            }else{
                $datas[$key]['mzprice'] = getPrice ( $val, $this->mid, true, true );
            }
            //如果为管理员/机构管理员自己机构的课程 则免费
            if(is_admin($this->mid) || $val['is_charge'] == 1) {
                $datas[$key]['mzprice']['price'] = 0;
            }
            if(is_school($this->mid) ==  $val['mhm_id']){
                $datas[$key]['mzprice']['price'] = 0;
            }

            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($val['uid']) || $tid == $val['teacher_id'])
            {
                $datas[$key]['mzprice']['price'] = 0;
            }
        }
        //精选课程
        $favouritmap = array();
        $favouritmap['is_del'] = 0;
        $favouritmap['is_activity'] = 1;
        $favouritmap['is_best'] = 1;
        $favouritmap['type']        = 1;
        $favouritmap['uctime'] = array('GT',time());
        $favourites = D ('ZyVideo','classroom' )->where($favouritmap)->order('ctime desc')->limit(3)->select();

        foreach ($favourites as $k=>$v){
            $mhmName = model('School')->getSchoolInfoById($v['mhm_id']);
            $favourites[$k]['mhmName'] = $mhmName['title'];
            if($v['type'] == 2){
                $favourites[$k]['mzprice']['price'] = $v['t_price'];
                $favourites[$k]['mzprice']['oriPrice'] = $v['v_price'];
            }else{
                $favourites[$k]['mzprice'] = getPrice ( $v, $this->mid, true, true );
            }
            if(ceil($v['t_price']) > 0){
                $favourites[$k]['v_prices'] = $v['t_price'];
            }else{
                $favourites[$k]['v_prices'] = 0;
            }

            //如果为管理员/机构管理员自己机构的课程 则免费
            if(is_admin($this->mid) || $v['is_charge'] == 1) {
                $favourites[$k]['mzprice']['price'] = 0;
            }
            if(is_school($this->mid) ==  $v['mhm_id']){
                $favourites[$k]['mzprice']['price'] = 0;
            }

            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($v['uid']) || $tid == $v['teacher_id'])
            {
                $favourites[$k]['mzprice']['price'] = 0;
            }
        }
        //热门推荐  teacher_id
        $hotmap = array();
        $hotmap['is_del'] = 0;
        $hotmap['is_activity'] = 1;
        $hotmap['type']        = 1;
        $hotmap['uctime'] = array('GT',time());
        $hot = D ('ZyVideo','classroom' )->where($hotmap)->
        order('video_collect_count desc,video_comment_count desc,video_question_count desc,video_note_count desc,video_score desc,video_order_count desc')
            ->limit(3)->select();
        foreach ($hot as $ks=>$vs){
            $mhmName = model('School')->getSchoolInfoById($vs['mhm_id']);
            $hot[$ks]['mhmName'] = $mhmName['title'];
            if($v['type'] == 2){
                $hot[$ks]['mzprice']['price'] = $vs['t_price'];
                $hot[$ks]['mzprice']['oriPrice'] = $vs['v_price'];
            }else{
                $hot[$ks]['mzprice'] = getPrice ( $vs, $this->mid, true, true );
            }
            if(ceil($vs['v_price']) > 0){
                $hot[$ks]['v_prices'] = $vs['v_price'];
            }else{
                $hot[$ks]['v_prices'] = 0;
            }

            //如果为管理员/机构管理员自己机构的课程 则免费
            if(is_admin($this->mid) || $vs['is_charge'] == 1) {
                $hot[$ks]['mzprice']['price'] = 0;
            }
            if($vs['mhm_id'] && is_school($this->mid) ==  $vs['mhm_id']){
                $hot[$ks]['mzprice']['price'] = 0;
            }

            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($vs['uid']) || $tid == $vs['teacher_id'])
            {
                $hot[$ks]['mzprice']['price'] = 0;
            }
        }
        /*if($cateId){
            $this->assign('showNowArea',model('CategoryTree')->setTable('zy_currency_category')->getCategoryList($cateId));
        }*/

        $area   = M("area")->where("pid=".$this->getVisitCity() ?:'110100')->findALL();
        $this->assign("area",$area);
        $this->assign("datas",$datas);
        $this->assign("hotData",$hot);
        $this->assign("favourit",$favourites);
        $this->display();
    }
    /**
     * 获取机构列表方法
     */
    public  function getSchoolList(){
        $map="status=1 AND is_del = 0";
        $order="id ASC";
        $area_id = intval($_GET['area']);
        $sort_type = intval($_GET['sort_type']);

        $cate_id = t($_GET ['cateId']);
        if($cate_id > 0){
            $cateId = explode(",", $cate_id );
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

        if($_GET['search']){
            $search = t($_GET['search']);
            $map .= " AND (title like '%$search%' or info like '%$search%')";
        }else if($this->getVisitCity()){
            $map.= " AND city = " . $this->getVisitCity();
        }
        if($area_id > 0){
            $map.= " AND area = {$area_id}";
            $title=M("area")->where('area_id='.$area_id)->getField("title");
            $this->assign('area_title',$title);
        }
        if($cateId){
            $school_category = implode(',',$cateId);
            $map .= " AND fullcategorypath like '%,$school_category,%'";
        }
        if($sort_type == 1){
            $order = 'view_count desc,collect_num desc';
        }elseif($sort_type == 2){
            $order = 'ctime desc';
        }

        //机构列表
        $data = model('School')->where($map)->order($order)->findPage(8);
        if ($data['data']) {
            foreach ($data['data'] as $key => &$value) {
                $maps = array('mhm_id'=>$value['id']);
                foreach (array_filter(explode(',', $value['str_tag'])) as $k => $v){
                    $value['new_str_tag'] .= "<span>{$v}</span>";
                }
                $value['counts'] = D('ZyVideo')->where($maps)->count();
                /*$user = model('User')->findUserInfo($value['uid'],'location');
                $value['location'] = $user['location'];*/
                $value['learn_count'] = D('zy_order_course')->where($maps)->count();
                $value['teacher_count'] = D('ZyTeacher')->where($maps)->count();
                //机构等级
                $school_vip = M('school_vip')->where(['id'=>$value['school_vip']])->find();
                $value['school_vip_name'] = $school_vip['title'] ?: '普通机构';
                if($school_vip['cover']){
                    $value['school_vip_cover'] = getCover($school_vip['cover'],19,19);
                }
                //是否已经收藏机构
                $isExist = D('ZyCollection','classroom')->where(array('source_id'=>$value['id'],'uid'=>$this->mid,'source_table_name'=>'school'))->count();
                if($isExist){
                    $value['collection'] = 1;
                }else{
                    $value['collection'] = 0;
                }
                //机构评价
                $value['review_count'] =  M('zy_review')->where('mhm_id='.$value['id'])->count() ? : 11;
                $star = M('zy_review')->where('mhm_id='.$value['id'])->avg('star');
                $value['star'] = round($star/20) ;
                $value['favorable_rate'] = round($star,2).'%' ? : 0;
                //机构域名
                $value['domain'] = getDomain($value['doadmin']);
            }
        }
        $this->assign('data',$data);
        $this->assign('listData',$data['data']);
        $this->assign('area_id',$area_id);
        $this->assign('sort_type',$sort_type);

        $html = $this->fetch('ajax_school');
        $data['data']=$html;
        exit( json_encode($data) );
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
    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 1){
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
        }elseif ($live_type == 3){
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
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;

        return $live_data;
    }
    //收藏机构
    public function collect(){
        $zyCollectionMod = D('ZyCollection','classroom');
        $type   = intval($_POST['type']);//0:取消收藏;1:收藏;
        $source_id = intval($_POST['source_id']);//机构ID

        $data['uid'] = intval($this->mid);
        $data['source_id'] = intval($source_id);
        $data['source_table_name'] = 'school';
        $data['ctime'] = time();

        if(!$type){
            $i = $zyCollectionMod->delcollection($data['source_id'],$data['source_table_name'],$data['uid']);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                M('school')->where(array('id'=>$source_id))->setDec('collect_num');
                $credit = M('credit_setting')->where(array('id'=>51,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] < 0){
                    $stype = 7;
                    $note = '取消收藏机构扣除的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$stype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

                $this->mzSuccess('取消收藏成功!');
            }
        }else{
            $i = $zyCollectionMod->addcollection($data);
            if($i === false){
                $this->mzError($zyCollectionMod->getError());
            }else{
                $credit = M('credit_setting')->where(array('id'=>22,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $stype = 6;
                    $note = '收藏机构获得的积分';
                }
                model('Credit')->addUserCreditRule($this->mid,$stype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

                M('school')->where(array('id'=>$source_id))->setInc('collect_num');
                $this->mzSuccess('收藏成功!');
            }
        }
    }
    /**
     * 取得课程分类
     */
    public function getCategroy() {
        $id = intval ( $_POST['pid'] );
        if ($id > 0) {
            $data = model('VideoCategory')->getChildCategory($id);
        }
        if (empty ( $data )){
            $data = null;
        }else{
            if($_POST['lv'] == 1){
                foreach ($data as $k=>$v){
                    $data[$k]['selName'] = 'pre';
                    $data[$k]['lv'] = intval($_POST['lv']) + 1;
                }
            }elseif ($_POST['lv'] == 2){
                foreach ($data as $k=>$v){
                    $data[$k]['selName'] = 'city';
                    $data[$k]['lv'] = intval($_POST['lv']) + 1;
                }
            }
        }

        echo json_encode($data);
        exit;
    }
}
