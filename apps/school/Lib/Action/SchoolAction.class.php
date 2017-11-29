<?php

/**
 * Eduline机构首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/school/Lib/Action/CommonAction.class.php');
class SchoolAction extends CommonAction {


    public function _initialize() {
        parent::_initialize();
    }
    /**
     * Eduline机构首页方法
     * @return void
     */
    public function index() {
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $school = model('school')->getSchoolInfoById($mhm_id);
        }
        //机构星级 及 评论数
        //$school['review_count'] = M('zy_review')->where('mhm_id='.$mhm_id)->count() ?: 0;
//        $star = M('zy_review')->where('mhm_id='.$mhm_id)->avg('star');
//        $school['star'] = round($star);
//        $star= $school['star'] / 5 *100;
//        $school['favorable_rate'] = round($star,2).'%';

        //机构评价（课程）
        $schoolmap['mhm_id'] = $school['school_id'];
        $schoolmap['is_del'] = 0;
        $schoolmap['is_activity'] = 1;
        $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();

        $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
        $vmap['oid'] = ['in',$live_id];
        $vmap['is_del'] = 0;

        //机构评价（讲师）
        $ostar =  M('zy_review')->where($vmap)->avg('star');
        $tidmap['mhm_id'] = $school['school_id'];
        $tidmap['is_del'] = 0;
        $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
        $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');

        $vtmap['tid'] = ['in',$tid];
        $vtmap['is_del'] = 0;

        $tstar =   M('zy_review')->where($vtmap)->avg('star');
        $star = ceil(($tstar + $ostar)/2/20) * 20;
        $school['favorable_rate'] = round($star,2).'%' ? : 0;
        $school['star'] = $star;
        $school['review_count'] = M('zy_review')->where($vtmap)->count();
        //机构域名
        $school['domain'] = getDomain($school['doadmin'],$mhm_id);
        $this->assign('school',$school);

        //广告图
        $adList = unserialize($school['banner']);
        $this->assign('ad_list',$adList);

        //机构优惠券显示
        $limit = 7;
        $order = 'ctime desc';
        $time = time();
        $map['status'] = 1;
        $map['is_del'] = 0;
        $map['sid'] = $mhm_id;
        $map['end_time'] = array('gt',$time);
        $map['coupon_type'] = 0;
        $map['type'] = 1;
        $video_coupon = model('Coupon')->getList($map,$order,$limit);
        $map['type'] = 2;
        $discount = model('Coupon')->getList($map,$order,$limit);
        $map['type'] = 3;
        $vip_card = model('Coupon')->getList($map,$order,$limit);
        foreach($vip_card['data'] as $k=>$v){
            $vip_card['data'][$k]['vip_grade'] = M('user_vip')->where(array('id'=>$v['vip_grade']))->getField('title');
        }
        $map['type'] = 4;
        $recharge = model('Coupon')->getList($map,$order,$limit);

        $this->assign('video_coupon',$video_coupon['data']);
        $this->assign('discount',$discount['data']);
        $this->assign('vip_card',$vip_card['data']);
        $this->assign('recharge',$recharge['data']);
        //机构课程分类
        $pid = model ( 'VideoCategory' )->where(array('pid'=>0))->field('zy_currency_category_id')->select();
        $pids = getSubByKey($pid,'zy_currency_category_id');

        $school_info = model('School')->where('id='.$mhm_id)->field('id,uid')->find();

        $mount_map['uid']    = $school_info['uid'];
        $mount_map['mhm_id'] = $school_info['id'];
        $mount_map['is_activity']    = 1;
        $mount_map['is_del'] = 0;
        $mount_id = M( 'zy_video_mount')->where ( $mount_map )->field('vid')->select();
        $mount_ids = implode(',',getSubByKey($mount_id,'vid'));
        if($mount_ids) {
            $mhmWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$mount_ids})) ";
        }else{
            $mhmWhere['mhm_id'] = $school_info['id'];
        }
        M('school')->where(array('id'=> $mhm_id))->setInc('visit_num');
        $mhmWhere['listingtime'] = array('lt', time());
        $mhmWhere['uctime'] = array('gt', time());
        $mhmWhere['is_activity'] = 1;
        $mhmWhere['is_mount'] = 1;
        $mhmWhere['is_del'] = 0;
        $mhmWhere['type'] = 2;

        //直播课程处理
        $liveData = D ( 'ZyVideo' ,'classroom')->where($mhmWhere)->field('fullcategorypath,id')->order(
            'video_order_count desc,video_score desc,video_collect_count desc')->limit(1000)->select();
        $count['live_cate'] = D ( 'ZyVideo' ,'classroom')->where($mhmWhere)->count();

        //点播课程处理
        $mhmWhere['type'] = 1;
        $data = D ( 'ZyVideo' ,'classroom')->where($mhmWhere)->field('fullcategorypath,id')->order(
            'video_order_count desc,video_score desc,video_collect_count desc')->limit(1000)->select();
        $count['cate'] = D ( 'ZyVideo' ,'classroom')->where($mhmWhere)->count();

        //点播课程列表
        $id = getSubByKey($data,'id');
        $maps['id'] = array('in',$id);
        $cate = D ( 'ZyVideo' ,'classroom')->where($maps)->limit(8)->select();
        foreach ($cate as $items=>$va){
            $mount_1and2 = M( 'zy_video_mount')->where ( ['vid'=>$va['id'],'mhm_id'=>$mhm_id] )->getField('vid');
            if($mount_1and2){
                $cate[$items]['mount_iand'] = 1;
            }
            $cate[$items]['mzprice'] = getPrice ( $va, $this->mid, true, true ,$va['type']);
            $cate[$items]['mzprice']['t_price'] =  $cate[$items]['mzprice']['price'];

            $teacher = M('zy_teacher')->where(array('id' => $va['teacher_id']))->find();
            $cate[$items]['tea_name'] = $teacher['name'];

            $mhmData = model('School')->where('id='.$va['mhm_id'])->field('doadmin,title')->find();
            //机构域名
            $cate[$items]['domain'] = getDomain($mhmData['doadmin'],$va['mhm_id']);
            $cate[$items]['mhm_title'] = $mhmData['title'];
        }

        //直播课程列表
        $id = getSubByKey($liveData,'id');
        $maps['id'] = array('in',$id);
        $live_cate = D ( 'ZyVideo' ,'classroom')->where($maps)->limit(8)->select();
        foreach ($live_cate as $items=>$va){
            $mount_1and2 = M( 'zy_video_mount')->where ( ['vid'=>$va['id'],'mhm_id'=>$mhm_id] )->getField('vid');
            if($mount_1and2){
                $live_cate[$items]['mount_iand'] = 1;
            }
            $live_cate[$items]['mzprice'] = getPrice ( $va, $this->mid, true, true ,$va['type']);
            $live_cate[$items]['mzprice']['t_price'] =  $live_cate[$items]['mzprice']['price'] ;

            $teacher = M('zy_teacher')->where(array('id' => $teacher_id))->getField('name');
            $live_cate[$items]['tea_name'] = $teacher;
        }

        //教师团队
        $teacherWhere = array();
        $teacherWhere['mhm_id'] = $mhm_id;
        $teacherWhere['is_del'] = 0;
        $teacher = M('zy_teacher')->where($teacherWhere)->order('course_count desc,reservation_count desc,review_count desc,views desc')->limit(6)->select();
        $count['teacher'] = M('zy_teacher')->where($teacherWhere)->count();

        //班级列表
        $album_mount_map['uid'] = $school_info['uid'];
        $album_mount_map['mhm_id'] = $school_info['id'];
        $video_mount_map['is_activity'] = 1;
        $video_mount_map['is_del'] = 0;
        $album_mount_id = M( 'zy_video_mount')->where ( $album_mount_map )->field('aid')->select();
        $album_mount_ids = implode(',',array_filter(getSubByKey($album_mount_id,'aid')));
        if($album_mount_ids) {
            $albumWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$album_mount_ids})) ";
        }else{
            $albumWhere['mhm_id'] = $mhm_id;
        }
        $albumWhere['status'] = 1;
        $albumWhere['is_del'] = 0;
        $albumWhere['price']  = ['neq',0];
        $album = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->limit(6)->select();
        $count['album'] = M('album')->where($albumWhere)->count();
        foreach ($album as $item=>$value){
//            $mount_1and2 = M( 'zy_video_mount')->where ( ['aid'=>$value['id'],'mhm_id'=>$mhm_id] )->getField('aid');
//            if($mount_1and2){
//                $album[$item]['mount_iand'] = 1;
//            }
            $album_category = array_filter(explode(',',$value['album_category']));
            $cate_name = M('zy_package_category')->where(array('zy_package_category_id'=>$album_category['1']))->find();
            $album[$item]['cate_name'] = $cate_name['title'];

            $album[$item]['video_count'] = M('album_video_link')->where(array('album_id'=>$value['id']))->count();

            $all_price = getAlbumPrice($value['id'],$this->mid);
            $album[$item]['price'] = $all_price['price'];
            $album[$item]['oPrice'] = $all_price['oriPrice'];
            $album[$item]['disPrice'] = $all_price['disPrice'];
        }
        M('school')->where(array('id'=> $mhm_id))->setInc('visit_num');

        //模版
        $school_template = model('School')->where(array('id'=>$mhm_id))->getField('template');
        if(!$school_template){
            $template = 0 ;
        }else{
            $template = array_flip(explode(",", $school_template));
        }
        $chars = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        $mount_url_str2 = '';
        for ( $i = 0; $i < 4; $i++ ){
            $mount_url_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        for ( $i = 0; $i < 4; $i++ ){
            $mount_url_str2 .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        //机构学员数
        $schoolUid = model('School')->where(array('id'=>$mhm_id))->getField('uid');
        $student = model('Follow')->where(array('fid' => $schoolUid))->count();

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
        $user_count = $user_count+$student;
        //机构课程数
        $video_count = M('zy_video')->where('mhm_id='.$mhm_id)->count();
        //机构讲师数
        $teacher_count = M('zy_teacher')->where('mhm_id='.$mhm_id)->count();

        $this->assign('template', $template);
        $this->assign('mid', $this->mid);
        $this->assign('mhm_id', $mhm_id);
        $this->assign('album_mount_url_str', "H".$mount_url_str);
        $this->assign('this_mhm_id', $mhm_id);
        $this->assign('mount_url_str', "L".$mount_url_str);
        $this->assign('mount_url_str2', "V".$mount_url_str);
        $this->assign('this_mhm_id', $mhm_id);
        $this->assign('cate', $cate);
        $this->assign('live_cate', $live_cate);
        $this->assign('teacher', $teacher);
        $this->assign('album', $album);
        $this->assign('user_count', $user_count);
        $this->assign('video_count', $video_count);
        $this->assign('teacher_count', $teacher_count);
        $this->assign('count', $count);
        $this->display('index');
    }

    /*
     * 关于我们
     * */
    public function about_us(){
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $school = model('school')->getSchoolInfoById($mhm_id);
        }

        //机构域名
        $school['domain'] = getDomain($school['doadmin'],$school['school_id']);
        /*if($school['doadmin'] && $school['doadmin'] != 'www'){
            $school['domain'] = getDomain($school['doadmin']);
        }else{
            $school['domain'] = U('school/School/index',array('id'=>$school['school_id']));
        }*/
        //广告图
        $adList = unserialize($school['banner']);
        $this->assign('ad_list',$adList);
        //模版
        $school_template = model('School')->where(array('id'=>$mhm_id))->getField('template');
        if(!$school_template){
            $template = 0 ;
        }else{
            $template = array_flip(explode(",", $school_template));
        }
        //机构评价（课程）
        $schoolmap['mhm_id'] = $school['school_id'];
        $schoolmap['is_del'] = 0;
        $schoolmap['is_activity'] = 1;
        $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();

        $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
        $vmap['oid'] = ['in',$live_id];
        $vmap['is_del'] = 0;

        //机构评价（讲师）
        $ostar =  M('zy_review')->where($vmap)->avg('star');
        $tidmap['mhm_id'] = $school['school_id'];
        $tidmap['is_del'] = 0;
        $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
        $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');

        $vtmap['tid'] = ['in',$tid];
        $vtmap['is_del'] = 0;

        $tstar =   M('zy_review')->where($vtmap)->avg('star');
        $star = ceil(($tstar + $ostar)/2/20) * 20;
        $school['favorable_rate'] = round($star,2).'%' ? : 0;
        $school['star'] = $star;
        //机构学员数
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

        $this->assign('template', $template);
        $this->assign('school',$school);
        $this->assign('mhm_id',$mhm_id);
        $this->assign('user_count',$user_count);
        $this->display('about_us');
    }

    /*
     * 机构课程分类详情
     */
    public function video_list($mhm_id,$type){
        $limit = 12;
        $order = 'video_order_count desc,video_score desc,video_collect_count desc';

        /*if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $school = model('school')->getSchoolInfoById($mhm_id);
        }*/

        $school = model('school')->getSchoolInfoById($mhm_id);
        //机构域名
        $school['domain'] = getDomain($school['doadmin'],$school['school_id']);

//        $cate_id = intval($_GET['cateId']);

        $video_mount_map['uid'] = $school['uid'];
        $video_mount_map['mhm_id'] = $mhm_id;
        $video_mount_map['is_activity'] = 1;
        $video_mount_map['is_del'] = 0;
        $video_mount_id = M( 'zy_video_mount')->where ( $video_mount_map )->field('vid')->select();
        $video_mount_ids = implode(',',array_filter(getSubByKey($video_mount_id,'vid')));
        if($video_mount_ids) {
            $map['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$video_mount_ids})) ";
        }else{
            $map['mhm_id'] = $mhm_id;
        }

        $map['type'] = $type;
        $map['is_activity'] = 1;
        $map['is_del'] = 0;
        $map['listingtime'] = array('lt', time());
        $map['uctime'] = array('gt', time());
//        $map['fullcategorypath'] =array('like','%' . $cate_id . '%');

        $data = M( 'zy_video' )->where($map)->order($order)->field('id,video_title,uid,is_activity,ctime,type,teacher_id,cover,is_charge,t_price,v_price,mhm_id')->findPage($limit);
        foreach($data['data'] as $key=>$val){
            $mount_1and2 = M( 'zy_video_mount')->where ( ['vid'=>$val['id'],'mhm_id'=>$mhm_id] )->getField('vid');
            if($mount_1and2){
                $data['data'][$key]['mount_iand'] = 1;
            }
            $school_info = model('school')->where('id='.$val['mhm_id'])->field('id,title,doadmin')->find();
            $data['data'][$key]['school_title'] = $school_info['title'];
            //机构域名
            $data['data'][$key]['domain'] = getDomain($school_info['doadmin'],$school_info['id']);

            $teacher = M('zy_teacher')->where(array('id' => $val['teacher_id']))->find();
            $data['data'][$key]['tea_name'] = $teacher['name'];
            if($type == 1){
                $data['data'][$key]['order_count'] =  M( 'zy_order_course' )->where('video_id='.$val['id'])->count();
            }else{
                $data['data'][$key]['order_count'] =  M( 'zy_order_live' )->where('live_id='.$val['id'])->count();
            }
            $data['data'][$key]['mzprice'] = getPrice ( $val, $this->mid, true, true ,$val['type']);
            $data['data'][$key]['mzprice']['t_price'] =  $data['data'][$key]['mzprice']['price'];
        }
        //广告图
        $adList = unserialize($school['banner']);
        $this->assign('ad_list',$adList);
        //模版
        /*$school_template = model('School')->where(array('id'=>$mhm_id))->getField('template');
        if(!$school_template){
            $template = 0 ;
        }else{
            $template = array_flip(explode(",", $school_template));
        }*/
        //机构评价（课程）
        /*$schoolmap['mhm_id'] = $school['school_id'];
        $schoolmap['is_del'] = 0;
        $schoolmap['is_activity'] = 1;
        $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();

        $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
        $vmap['oid'] = ['in',$live_id];
        $vmap['is_del'] = 0;*/

        //机构评价（讲师）
        /*$ostar =  M('zy_review')->where($vmap)->avg('star');
        $tidmap['mhm_id'] = $school['school_id'];
        $tidmap['is_del'] = 0;
        $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
        $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');

        $vtmap['tid'] = ['in',$tid];
        $vtmap['is_del'] = 0;

        $tstar =   M('zy_review')->where($vtmap)->avg('star');
        $star = ceil(($tstar + $ostar)/2/20) * 20;
        $school['favorable_rate'] = round($star,2).'%' ? : 0;
        $school['star'] = $star;*/
        //机构学员数
       /* $user = model('User')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
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
        $user_count = count(array_unique($user_count)) ? : 1;*/

        $chars = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        for ( $i = 0; $i < 4; $i++ ){
            $mount_url_str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }

        $this->assign('mount_url_str', "L".$mount_url_str);
        $this->assign('data', $data);
        $this->assign('listData', $data['data']);
//        $this->assign('template', $template);
        $this->assign('school',$school );
        $this->assign('mhm_id',$mhm_id );
//        $this->assign('user_count',$user_count);

    }
    /*
     *机构课程详情--加载更多
     */
    public function getVideoList(){
        $limit = 6;
        $order = 'video_order_count desc,video_score desc,video_collect_count desc';

        $cate_id = intval($_GET['cateId']);
        $map['mhm_id'] = intval($_GET['id']);
        $map['is_activity'] = 1;
        $map['is_del'] = 0;
        $map['listingtime'] = array('lt', time());
        $map['uctime'] = array('gt', time());
        $map['fullcategorypath'] =array('like','%' . $cate_id . '%');
        $data = M( 'zy_video' )->where($map)->order($order)->field('id,video_title,uid,is_activity,ctime,type,teacher_id,cover,is_charge,t_price,v_price')->findPage($limit);

        foreach($data['data'] as $key=>$val){
            $teacher = M('zy_teacher')->where(array('id' => $val['teacher_id']))->find();
            $data['data'][$key]['tea_name'] = $teacher['name'];
            $data['data'][$key]['mzprice']['price'] = $val['t_price'];
            $data['data'][$key]['mzprice']['oriPrice'] = $val['v_price'];
            if(is_school($this->mid) || is_admin($this->mid)){
                $data['data'][$key]['is_charge'] = 1;
            }
        }

        $this->assign('data', $data);
        $this->assign('listData', $data['data']);

        $html = $this->fetch('ajax_video');
        $data['data']=$html;
        exit( json_encode($data) );
    }

    /*
     * 机构班级详情
     */
    public function album_list($mhm_id){
        $limit = 4;
        $school = model('school')->getSchoolInfoById($mhm_id);

        //班级
        $album_mount_map['uid'] = $school['uid'];
        $album_mount_map['mhm_id'] = $mhm_id;
        $video_mount_map['is_activity'] = 1;
        $video_mount_map['is_del'] = 0;
        $album_mount_id = M( 'zy_video_mount')->where ( $album_mount_map )->field('aid')->select();
        $album_mount_ids = implode(',',array_filter(getSubByKey($album_mount_id,'aid')));
        if($album_mount_ids) {
            $albumWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$album_mount_ids})) ";
        }else{
            $albumWhere['mhm_id'] = $mhm_id;
        }
        $albumWhere['status'] = 1;
        $albumWhere['is_del'] = 0;
        $albumWhere['price']  = ['neq',0];
        $order = 'order_count desc,comment_count desc,collect_count desc';
//        $album = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->findPage(6);
        $album = D('Album','classroom')->getList($albumWhere,$order,$limit);
        $count['album'] = M('album')->where($albumWhere)->count();
        foreach ($album['data'] as $item=>$value){
//            $mount_1and2 = M( 'zy_video_mount')->where ( ['aid'=>$value['id'],'mhm_id'=>$mhm_id] )->getField('aid');
//            if($mount_1and2){
//                $album[$item]['mount_iand'] = 1;
//            }
            $album_category = array_filter(explode(',',$value['album_category']));
            $cate_name = M('zy_package_category')->where(array('zy_package_category_id'=>$album_category['1']))->find();
            $album['data'][$item]['cate_name'] = $cate_name['title'];
            $album['data'][$item]['video_count'] = M('album_video_link')->where(array('album_id'=>$value['id']))->count();

            $all_price = getAlbumPrice($value['id'],$this->mid);
            $album['data'][$item]['price'] = $all_price['price'];
            $album['data'][$item]['oPrice'] = $all_price['oriPrice'];
            $album['data'][$item]['disPrice'] = $all_price['disPrice'];
        }
        /*$album = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->findPage($limit);
        foreach ($album as $item=>$value){
            $album_category = array_filter(explode(',',$value['album_category']));
            $cate_name = M('zy_package_category')->where(array('zy_package_category_id'=>$album_category['1']))->find();
            $album[$item]['cate_name'] = $cate_name['title'];

            $album[$item]['video_count'] = M('album_video_link')->where(array('album_id'=>$value['id']))->count();;
        }*/
        M('school')->where(array('id'=> $mhm_id))->setInc('visit_num');

        //机构域名
        $school['domain'] = getDomain($school['doadmin'],$school['school_id']);
        //广告图
        $adList = unserialize($school['banner']);
        $this->assign('ad_list',$adList);

        $this->assign('data', $album);
        $this->assign('listData', $album['data']);
        $this->assign('school',$school );
        $this->assign('mhm_id',$mhm_id );
    }

    /*
     * 机构讲师详情
     */
    public function teacher_list($mhm_id){
        $limit = 6;
        $order = 'reservation_count desc';

//        if($_GET['id']){
//            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
//        }else{
//            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
//        }
//        if(!$mhm_id){
//            header('HTTP/1.1 404 Not Found');exit;
//        }else{
//            $school = model('school')->getSchoolInfoById($mhm_id);
//        }
        $school = model('school')->getSchoolInfoById($mhm_id);

        $teachermap['mhm_id'] = $mhm_id;
        $teachermap['is_del'] = 0;
        $teachermap['is_reject'] = 0;
        $data = M('zy_teacher')->where($teachermap)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value["video"] = M('zy_video')->where('is_del=0 and teacher_id='.$value['id'])->order('video_order_count desc')->field('id,video_title,t_price,video_order_count')->find();
            $value['teach_areas']=explode(",",$value['teach_areas']);
            $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$value['title'])->find();
            $value['teacher_title_category'] = $teacher_title['title'] ?: '普通讲师';
            if($teacher_title['cover']){
                $value['teacher_title_cover'] = getCover($teacher_title['cover'],19,19);
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

        //机构域名
        $school['domain'] = getDomain($school['doadmin'],$school['school_id']);
        //广告图
        $adList = unserialize($school['banner']);
        $this->assign('ad_list',$adList);
        //模版
        $school_template = model('School')->where(array('id'=>$mhm_id))->getField('template');
        if(!$school_template){
            $template = 0 ;
        }else{
            $template = array_flip(explode(",", $school_template));
        }
        //机构评价（课程）
        $schoolmap['mhm_id'] = $school['school_id'];
        $schoolmap['is_del'] = 0;
        $schoolmap['is_activity'] = 1;
        $videoid = M('zy_video') -> where($schoolmap) -> field('id')->select();

        $live_id = trim(implode(',',array_unique(getSubByKey($videoid,'id'))),',');
        $vmap['oid'] = ['in',$live_id];
        $vmap['is_del'] = 0;

        //机构评价（讲师）
        $ostar =  M('zy_review')->where($vmap)->avg('star');
        $tidmap['mhm_id'] = $school['school_id'];
        $tidmap['is_del'] = 0;
        $tids = M('zy_teacher') -> where($tidmap) -> field('id')->select();
        $tid = trim(implode(',',array_unique(getSubByKey($tids,'id'))),',');

        $vtmap['tid'] = ['in',$tid];
        $vtmap['is_del'] = 0;

        $tstar =   M('zy_review')->where($vtmap)->avg('star');
        $star = ceil(($tstar + $ostar)/2/20) * 20;
        $school['favorable_rate'] = round($star,2).'%' ? : 0;
        $school['star'] = $star;
        //机构学员数
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

        $this->assign('data', $data);
        $this->assign('listData', $data['data']);
        $this->assign('template', $template);
        $this->assign('school',$school );
        $this->assign('mhm_id',$mhm_id );
        $this->assign('listData', $data['data']);
        $this->assign('user_count',$user_count);
    }

    /*
     *机构讲师详情--加载更多
     */
    public function getTeacherList(){
        $limit = 10;
        $order = 'reservation_count desc';

        $map['mhm_id'] = intval($_GET['id']);
        $map['is_del'] = 0;
        $map['is_reject'] = 0;
        $data = M('zy_teacher')->where($map)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value['teach_areas']=explode(",",$value['teach_areas']);
            $user = model('User')->getUserInfo($value['uid']);
            $value['Teacher_areas'] = $user['location'];

            $star = M('zy_review')->where('tid='.$value['id'])->avg('star');
            $value['review_count']   =  M('zy_review')->where('tid='.$value['id'])->count();
            $value['star']           = round($star/20);

            //讲师标签
            $value['label'] = array_filter(explode(",", $value['label']));
        }

        $this->assign('data', $data);
        $this->assign('listData', $data['data']);

        $html = $this->fetch('ajax_teacher');
        $data['data']=$html;
        exit( json_encode($data) );
    }

    /**
     * 机构分类分页数据
     * @return json
     */
    public function getPage()
    {
        $cate_id = intval($_POST['cate_id']);
        $mhm_id = intval($_POST['mhm_id']);
        $cate_ids =  model ( 'VideoCategory' )->getVideoChildCategory($cate_id);
        $where = array();
        $where['video_category'] = array('in',$cate_ids);
        $where['is_del'] = 0;
        $where['is_activity'] = 1;
        $where['mhm_id'] = $mhm_id;
        $data = D ('ZyVideo','classroom' )->where($where)->findPage(10);
        if($data['data']){
            foreach ($data['data'] as $items=>$va){
                if(ceil($va['v_price']) >0){
                    $data['data'][$items]['is_free'] = 0;
                }else{
                    $data['data'][$items]['is_free'] = 1;
                }
                $teacher = M('zy_teacher')->where(array('id' => $va['teacher_id']))->find();
                $data['data'][$items]['tea_name'] = $teacher['name'];
                $data['data']['state'] = 1;
                $data['data']['msg'] = '获取成功';
            }
        }else{
            $data['data'] = array();
            $data['data']['state'] = 0;
            $data['data']['msg'] = '没有更多数据';
        }
        echo json_encode($data['data']);
        exit;
    }
    /**
     * 最佳原创
     * @return void
     */
    public function get_original_recommend() {
        //数据分类 1:课程;2:班级;
        $limit = 1;
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        //班级
        $album_table = "SELECT `id`,`re_sort`,2 as `type`,`album_category` as `category`,`uid`,`cover`,`album_title` as `title`,`album_score` as `score`,`album_intro` as `intro` FROM `{$albumtable}` WHERE `is_del`=0 AND `original_recommend`=1";
        echo $album_table . "<br/>";
        //课程
        $video_table = "SELECT `id`,`re_sort`,1 as `type`,`video_category` as `category`,`uid`,`cover`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` FROM `{$zy_videotable}` WHERE `is_del`=0 AND `original_recommend`=1 AND `is_activity`=1 ";
        echo $video_table . "<br/>";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell` ORDER BY `re_sort` DESC  LIMIT 0,{$limit}";
        echo $sql;
        die();
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 编辑精选
     * @return void
     */
    public function get_best_recommend() {
        //数据分类 1:课程;2:班级;
        $limit = intval($_POST['limit']);
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        //班级
        $album_table = "SELECT `id`,`be_sort`,2 as `type`,`album_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`album_title` as `title`,`album_score` as `score`,`album_intro` as `intro` FROM `{$albumtable}` WHERE `is_del`=0 AND `best_recommend`=1";
        echo $album_table . "<br/>";
        //课程
        $video_table = "SELECT `id`,`be_sort`,1 as `type`,`video_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` FROM `{$zy_videotable}` WHERE `is_del`=0 AND `best_recommend`=1 AND `is_activity`=1 ";
        echo $video_table . "<br/>";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell` ORDER BY `be_sort` DESC  LIMIT 0,{$limit}";
        echo $sql;
        die();
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 为我推荐
     * @return void
     */
    public function get_recommend() {
        //数据分类 1:课程;2:班级;
        $limit = intval($_POST['limit']);
        $uid = intval($this->mid);
        $zy_ordertable = C('DB_PREFIX') . 'zy_order';
        $zy_order_albumtable = C('DB_PREFIX') . 'zy_order_album';
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        //班级
        $album_table = "SELECT `id`,2 as `type`,`album_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`album_title` as `title`,`album_score` as `score`,`album_intro` as `intro` FROM `{$albumtable}` WHERE `is_del`=0 and `album_category` IN(SELECT `album_category` FROM `{$albumtable}` WHERE `id` IN (SELECT `album_id` AS `rid` FROM `{$zy_order_albumtable}` WHERE `uid`={$uid})) AND `id` NOT IN (SELECT `album_id` AS `rid` FROM `{$zy_order_albumtable}` WHERE `uid`={$uid})";
        //课程
        $video_table = "SELECT `id`,1 as `type`,`video_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` FROM `{$zy_videotable}` WHERE `is_del`=0 and `video_category` IN(SELECT `video_category` FROM `{$zy_videotable}` WHERE `id` IN (SELECT `video_id` AS `rid` FROM `{$zy_ordertable}` WHERE `uid`={$uid})) AND `id` NOT IN (SELECT `video_id` AS `rid` FROM `{$zy_ordertable}` WHERE `uid`={$uid})";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell`  LIMIT 0,{$limit}";
        //计算为我推荐总数
        $sql_count = "SELECT count(*) as `count` FROM ({$album_table} UNION {$video_table}) as `mysellwell` where 1;";
        //1:先找为我推荐的班级或者课程---根据分类来找
        $count = M('')->query($sql_count);
        if (intval($count[0]['count'])) {
            //处理和返回
            $this->dealAndReturn($sql);
            exit;
        }
        //2:通过观看记录找为我推荐
        $session_data = session('watch_history');
        $session_data = $session_data ? implode(',', $session_data) : 0;
        $sql = "SELECT `id`,1 as `type`,`video_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` FROM {$zy_videotable} where `video_category` IN(SELECT `video_category` FROM {$zy_videotable} WHERE `id` IN ({$session_data})) and `is_del`=0 and `id` NOT IN({$session_data}) LIMIT 0,{$limit};";
        $sql_count = "SELECT count(*) as `count` FROM {$zy_videotable} where `video_category` IN(SELECT `video_category` FROM {$zy_videotable} WHERE `id` IN ({$session_data})) and `is_del`=0 and `id` NOT IN({$session_data});";
        $count = M('')->query($sql_count);
        if (intval($count[0]['count'])) {
            //处理和返回
            $this->dealAndReturn($sql);
            exit;
        }
        //3:如果发现没有-则找每日最新
        $this->get_today_new();
    }
    /**
     * 我的收藏
     * @return void
     */
    public function get_my_collect() {
        //数据分类 1:课程;2:班级;
        $limit = intval($_POST['limit']);
        $uid = intval($this->mid);
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        $zy_collectiontable = C('DB_PREFIX') . 'zy_collection';
        //班级
        $album_table = "SELECT `id`,2 as `type`,`album_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`album_title` as `title`,`album_score` as `score`,`album_intro` as `intro` from `{$albumtable}` WHERE `id` in(SELECT `source_id` FROM `{$zy_collectiontable}` WHERE `uid`={$uid} and `source_table_name` = 'album' ORDER BY `ctime` DESC)";
        //课程
        $video_table = "SELECT `id`,1 as `type`,`video_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` from `{$zy_videotable}` WHERE `id` in(SELECT `source_id` FROM `{$zy_collectiontable}` WHERE `uid`={$uid} and `source_table_name` = 'zy_video' ORDER BY `ctime` DESC)";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell` ORDER BY `score` DESC LIMIT 0,{$limit}";
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 我的观看记录
     * @return void
     */
    public function get_watch_record() {
        $session_data = session('watch_history');
        $aids = implode(',', $session_data);
        //数据分类 1:课程;2:班级;
        $limit = intval($_POST['limit']);
        $uid = intval($this->mid);
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        $sql = "SELECT `id`,1 as `type`,`big_ids`,`video_category` as `category`,`uid`,`is_offical`,`big_ids`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_score` as `score`,`video_intro` as `intro` FROM `{$zy_videotable}` WHERE `id` in({$aids}) ORDER BY `ctime` DESC limit 0,{$limit}";
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 畅销排行榜
     * @return void
     */
    public function get_sell_well() {
        $limit = intval($_POST['limit']);
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        //数据分类 1:课程;2:班级;
        //班级
        $album_table = "SELECT `id`,2 as `type`,`big_ids`,`uid`,`is_offical`,`album_category` as `category`,`middle_ids`,`small_ids`,`album_title` as `title`,`album_order_count` as `number`,`album_score` as `score`,`album_intro` as `intro` from `{$albumtable}` WHERE `is_del`=0";
        //课程
        $video_table = "SELECT `id`,1 as `type`,`big_ids`,`uid`,`is_offical`,`video_category` as `category`,`middle_ids`,`small_ids`,`video_title` as `title`,`video_order_count` as `number`,`video_score` as `score`,`video_intro` as `intro` from `{$zy_videotable}` WHERE `is_del`=0 AND `is_activity`=1";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell` ORDER BY `number` DESC LIMIT 0,{$limit}";
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 每日上新
     * @return void
     */
    public function get_today_new() {
        $limit = intval($_POST['limit']);
        $albumtable = C('DB_PREFIX') . 'album';
        $zy_videotable = C('DB_PREFIX') . 'zy_video';
        //C('DB_PREFIX')
        //数据分类 1:课程;2:班级;
        //班级
        $album_table = "SELECT `id`,2 as `type`,`big_ids`,`uid`,`is_offical`,`album_category` as `category`,`middle_ids`,`small_ids`,`album_title` as `title`,`ctime`,`album_score` as `score`,`album_intro` as `intro` from `{$albumtable}` WHERE `is_del`=0";
        //课程
        $video_table = "SELECT `id`,1 as `type`,`big_ids`,`uid`,`is_offical`,`video_category` as `category`,`middle_ids`,`small_ids`,`video_title` as `title`,`ctime`,`video_score` as `score`,`video_intro` as `intro` from `{$zy_videotable}` WHERE `is_del`=0 AND `is_activity`=1";
        //拼接总的数据
        $sql = "SELECT * FROM ({$album_table} UNION {$video_table}) as `mysellwell` ORDER BY `ctime` DESC LIMIT 0,{$limit}";
        //处理和返回
        $this->dealAndReturn($sql);
    }
    /**
     * 处理和返回
     * @return void
     */
    private function dealAndReturn($sql, $isRec = false) {
        //从数据库中取得
        $mylist = M('')->query($sql);
        //处理数据
        foreach ($mylist as & $value) {
            $value['score'] = floor(intval($value['score']) / 20);
            //$value['big_ids']  = getAttachUrlByAttachId($value['big_ids']);
            $value['category'] = getCategoryName($value['category'], true);
            $value['isGetResource'] = isGetResource($value['type'], $value['id'], array(
                'video',
                'upload',
                'note',
                'question'
            ));
            $value['title'] = msubstr($value['title'], 0, 21);
            $value['intro'] = msubstr($value['intro'], 0, 87);
            if ($value['type'] == 2) {
                $value['href'] = U('classroom/Album/view', 'id=' . $value['id']);
            } else {
                $value['href'] = U('classroom/Video/view', 'id=' . $value['id']);
            }
        }
        //关注
        $fids = model('Follow')->field('fid')->where('uid=' . intval($this->mid))->select();
        $this->assign('fids', getSubByKey($fids, 'fid'));
        //print_r($mylist);
        $this->assign('mylist', $mylist);
        //取得数据
        $content = $this->fetch('list');
        echo json_encode(array(
            'data' => $content
        ));
        exit;
    }
    /**
     * 专题列表
     * @return void
     */
    public function special() {
        //取得专题分类
        $scid = intval($_GET['scid']);
        if (!$scid) {
            $this->assign('isAdmin', 1);
            $this->error('您请求的专题列表不存在');
            return false;
        }
        $this->display();
    }
    /**
     * 获取分类数据列表
     */
    public function getCategoryData() {
    }
    /**
     * 投稿发布框
     * @return void
     */
    public function contributeBox() {
        $this->display();
    }
    /**
     * 提问/笔记/点评内容详情页
     * type 1:提问,2:点评,3:笔记
     * @return void
     */
    public function resource() {
        $types = array(
            1 => 'ZyQuestion',
            2 => 'ZyReview',
            3 => 'ZyNote',
        );
        $rid = intval($_GET['rid']);
        $type = intval($_GET['type']);
        $stable = $types[$type];
        if (!$stable) {
            $this->assign('isAdmin', 1);
            $this->error('参数错误');
        }
        $map['id'] = array(
            'eq',
            $rid
        );
        $data = D($stable)->where($map)->find();
        //print_r(D($stable)->getLastSql());
        if (!$data) {
            $this->assign('isAdmin', 1);
            $this->error('资源不存在');
        }
        $data['strtime'] = friendlyDate($data['ctime']);
        $data['username'] = getUserName($data['uid']);
        if ($type == 1) {
            $data['title'] = $data['qst_title'];
            $data['content'] = $data['qst_description'];
            $data['source'] = $data['qst_source'];
            $data['help_count'] = $data['qst_help_count'];
            $data['comment_count'] = $data['qst_comment_count'];
            $data['iscollect'] = D('ZyCollection')->isCollect($data['id'], 'zy_question', intval($this->mid));
        } else if ($type == 3) {
            $data['title'] = $data['note_title'];
            $data['content'] = $data['note_description'];
            $data['source'] = $data['note_source'];
            $data['help_count'] = $data['note_help_count'];
            $data['comment_count'] = $data['note_comment_count'];
            $data['iscollect'] = D('ZyCollection')->isCollect($data['id'], 'zy_note', intval($this->mid));
        }
        //返回班级或者课程信息
        $this->getRInfo($data['oid'], $data['type']);
        $data['title'] = msubstr($data['title'], 0, 40);
        $this->assign('type', $type);
        $this->assign('data', $data);
        $this->display();
    }
    private function getRInfo($id, $type) {
        $map['id'] = array(
            'eq',
            $id
        );
        if ($type == 1) {
            //课程
            if (!$id) {
                $this->assign('isAdmin', 1);
                $this->error('课程不存在!');
            }
            $field = '`video_title` as `title`,`video_category` as `category`,`video_score` as `score`,`uid`,`id`,`ctime`,`video_comment_count` as `comment_count`';
            //取课程信息
            $data = M('ZyVideo')->where($map)->field($field)->find();
        } else if ($type == 2) {
            //班级
            if (!$id) {
                $this->assign('isAdmin', 1);
                $this->error('班级不存在!');
            }
            $field = '`album_title` as `title`,`album_category` as `category`,`album_score` as `score`,`uid`,`id`,`ctime`,`album_comment_count` as `comment_count`';
            //取班级信息
            $data = M('Album')->where($map)->field($field)->find();
        } else {
            $this->assign('isAdmin', 1);
            $this->error('参数错误!');
        }
        $data['score'] = floor($data['score'] / 20);
        //print_r($data);exit;
        $this->assign('datainfo', $data);
        $this->assign('id', $id);
        $this->assign('type', $type);
    }
    /**
     * 提问/笔记/点评内容详情页
     * type 1:提问,2:点评,3:笔记
     * @return void
     */
    public function getTopHot() {
        $zyVoteMod = D('ZyVote');
        $types = array(
            3 => 'ZyQuestion',
            4 => 'ZyNote',
        );
        $limit = intval($_POST['limit']);
        $pid = intval($_POST['pid']);
        $type = intval($_POST['type']);
        $stable = $types[$type];
        $order = $type == 3 ? 'qst_vote_count DESC' : 'note_vote_count DESC';
        $data = M($stable)->where(array(
            'parent_id' => array(
                'eq',
                $pid
            )
        ))->order($order)->findPage($limit);
        //处理数据
        foreach ($data['data'] as & $value) {
            $value['username'] = getUserName($value['uid']);
            $value['strtime'] = friendlyDate($value['ctime']);
            if ($type == 3) {
                $value['content'] = $value['qst_description'];
                $value['comment_count'] = $value['qst_comment_count'];
                $value['vote_count'] = $value['qst_vote_count'];
                $value['source'] = $value['qst_source'];
                $value['isvote'] = $zyVoteMod->isVote($value['id'], 'zy_question', $this->mid) ? 1 : 0;
            } else if ($type == 4) {
                $value['content'] = $value['note_description'];
                $value['comment_count'] = $value['note_comment_count'];
                $value['vote_count'] = $value['note_vote_count'];
                $value['source'] = $value['note_source'];
                $value['isvote'] = $zyVoteMod->isVote($value['id'], 'zy_note', $this->mid) ? 1 : 0;
            }
        }
        //处理数据
        echo json_encode($data);
        exit;
    }
    /**
     * 提问/笔记/点评内容详情页
     * type 1:提问,2:点评,3:笔记
     * @return void
     */
    public function getTopNew() {
        $zyVoteMod = D('ZyVote');
        $types = array(
            3 => 'ZyQuestion',
            4 => 'ZyNote',
        );
        $limit = intval($_POST['limit']);
        $pid = intval($_POST['pid']);
        $type = intval($_POST['type']);
        $stable = $types[$type];
        $order = 'ctime DESC';
        $data = M($stable)->where(array(
            'parent_id' => array(
                'eq',
                $pid
            )
        ))->order($order)->findPage($limit);
        //处理数据
        foreach ($data['data'] as & $value) {
            $value['username'] = getUserName($value['uid']);
            $value['strtime'] = friendlyDate($value['ctime']);
            if ($type == 3) {
                $value['content'] = $value['qst_description'];
                $value['comment_count'] = $value['qst_comment_count'];
                $value['vote_count'] = $value['qst_vote_count'];
                $value['source'] = $value['qst_source'];
                $value['isvote'] = $zyVoteMod->isVote($value['id'], 'zy_question', $this->mid) ? 1 : 0;
            } else if ($type == 4) {
                $value['content'] = $value['note_description'];
                $value['comment_count'] = $value['note_comment_count'];
                $value['vote_count'] = $value['note_vote_count'];
                $value['source'] = $value['note_source'];
                $value['isvote'] = $zyVoteMod->isVote($value['id'], 'zy_note', $this->mid) ? 1 : 0;
            }
        }
        echo json_encode($data);
        exit;
    }
    public function getListForId() {
        $types = array(
            3 => 'ZyQuestion',
            4 => 'ZyNote',
        );
        $limit = intval($_POST['limit']);
        $pid = intval($_POST['pid']);
        $type = intval($_POST['type']);
        $stable = $types[$type];
        $order = 'ctime desc';
        $data = M($stable)->where(array(
            'parent_id' => array(
                'eq',
                $pid
            )
        ))->order($order)->findPage($limit);
        //处理数据
        foreach ($data['data'] as & $value) {
            $value['username'] = getUserName($value['uid']);
            $value['strtime'] = friendlyDate($value['ctime']);
            if ($type == 3) {
                $value['content'] = $value['qst_description'];
            } else if ($type == 4) {
                $value['content'] = $value['note_description'];
            }
            $value['content'] = msubstr($value['content'], 0, 240);
        }
        echo json_encode($data);
        exit;
    }
    /**
     * 添加笔记、提问的评论
     * type 1:提问,2:点评,3:笔记
     * @return void
     */
    public function dowrite() {
        $types = array(
            1 => 'ZyQuestion',
            2 => 'ZyReview',
            3 => 'ZyNote',
        );
        if (!$this->mid) {
            //请不要重复刷新
            $this->mzError('请登录!');
        }
        $rid = intval($_POST['rid']);
        $reply_id = intval($_POST['rep_uid']);
        $type = intval($_POST['type']);
        $content = t($_POST['content']);
        $stable = $types[$type];
        //先找到之前的数据(评论的问题、点评、笔记的)
        $data = M($stable)->where(array(
            'id' => array(
                'eq',
                $rid
            )
        ))->find();
        if ($type == 1) {
            $data['parent_id'] = $rid;
            $data['uid'] = $this->mid;
            $data['qst_description'] = $content;
            $data['qst_help_count'] = 0;
            $data['qst_comment_count'] = 0;
            $data['qst_collect_count'] = 0;
            $data['qst_vote_count'] = 0;
            $data['qst_source'] = 'web网页';
            $data['ctime'] = time();
            unset($data['id'], $data['qst_title']);
        } else if ($type == 2) {
            $data['parent_id'] = $rid;
            $data['uid'] = $this->mid;
            $data['review_description'] = $content;
            $data['review_help_count'] = 0;
            $data['review_comment_count'] = 0;
            $data['review_collect_count'] = 0;
            $data['review_vote_count'] = 0;
            $data['review_source'] = 'web网页';
            $data['ctime'] = time();
            unset($data['id'], $data['review_title']);
        } else if ($type == 3) {
            $data['parent_id'] = $rid;
            $data['uid'] = $this->mid;
            $data['note_description'] = $content;
            $data['note_help_count'] = 0;
            $data['note_comment_count'] = 0;
            $data['note_collect_count'] = 0;
            $data['note_vote_count'] = 0;
            $data['note_source'] = 'web网页';
            $data['ctime'] = time();
            unset($data['id'], $data['note_title']);
        }
        if (session('mzaddQuestionandnote11' . $rid . $type) >= time()) {
            //请不要重复刷新
            $this->mzError('请不要重复添加,3分钟之后再试!');
        }
        $data['reply_uid'] = $reply_id;
        $i = M($stable)->add($data);
        if ($i) {
            $deinfo = "";
            if ($type == 1) {
                $_data['qst_comment_count'] = array(
                    'exp',
                    'qst_comment_count+1'
                );
            } else if ($type == 2) {
                $_data['review_comment_count'] = array(
                    'exp',
                    'review_comment_count+1'
                );
            } else if ($type == 3) {
                $_data['note_comment_count'] = array(
                    'exp',
                    'note_comment_count+1'
                );
            }
            M($stable)->where(array(
                'id' => array(
                    'eq',
                    $rid
                )
            ))->save($_data);
            session('mzaddQuestionandnote' . $rid . $type, time() + 180);
            $data['userface'] = getUserFace($this->mid, 's');
            $data['user_src'] = U('classroom/UserShow/index', 'uid=' . $this->mid);
            $data['username'] = getUserName($this->mid);
            $data['strtime'] = friendlyDate($data['ctime']);
            $data['description'] = $reply_id ? '回复<span class="user-reply">@' . getUserName($reply_id) . '</span>:' . $content : $content;
            $data['uid'] = $this->mid;
            //查出被评论人的uid和内容
            $finfo = M($stable)->where(array(
                'id' => array(
                    'eq',
                    $rid
                )
            ))->find();
            if (empty($reply_id)) {
                $fid = $finfo['uid'];
            } else {
                $fid = $reply_id;
            }
            if ($type == 1) {
                $deinfo = $finfo['qst_description'];
            } else if ($type == 3) {
                $deinfo = $finfo['note_description'];
            }
            model('Message')->doCommentmsg($this->mid, $fid, $finfo['oid'], $finfo['type'], 'zy_question', 0, limitNumber($deinfo, 30) , $content);
            $this->mzSuccess('添加成功', '', $data);
        } else {
            $this->mzError('添加失败');
        }
    }
    /**
     * 添加笔记、提问的评论
     * type 1:提问,2:点评,3:笔记
     * @return void
     */
    public function tongwen() {
        $types = array(
            1 => 'ZyQuestion',
            3 => 'ZyNote',
        );
        $rid = intval($_POST['rid']);
        $type = intval($_POST['type']);
        if (session('mzaddQuestionandnotetonwen' . $rid . $type) >= time()) {
            //请不要重复刷新
            $this->mzError('请不要重复点击哦');
        }
        $stable = $types[$type];
        if ($type == 1) {
            $data['qst_help_count'] = array(
                'exp',
                'qst_help_count+1'
            );
        } else {
            $data['note_help_count'] = array(
                'exp',
                'note_help_count+1'
            );
        }
        $i = M($stable)->where(array(
            'id' => array(
                'eq',
                $rid
            )
        ))->save($data);
        if ($i) {
            session('mzaddQuestionandnotetonwen' . $rid . $type, time() + 180);
            //查出被评论人的uid和内容
            $finfo = M($stable)->where(array(
                'id' => array(
                    'eq',
                    $rid
                )
            ))->find();
            if (empty($reply_id)) {
                $fid = $finfo['uid'];
            } else {
                $fid = $reply_id;
            }
            model('Message')->doCommentmsg($this->mid, $fid, $finfo['id'], 0, 'zy_question', 0, limitNumber($finfo['qst_description'], 30) , $content);
            $this->mzSuccess('添加成功');
        } else {
            $this->mzError('添加失败');
        }
    }
    /**
     *卡券领取
     *@return void
     */
    function saveUSerCoupon() {
        $code = t($_POST['code']);
        $type = intval($_POST['type']);
        $uid = intval($_POST['uid']);
        $sid = intval($_POST['sid']);
        model('Coupon')->mid = $uid;
        $time = time();
        $ext_where = " AND u.etime > $time AND c.sid =$sid ";
        $coupon = model('Coupon')->getCanuseCouponList($uid,$type,$ext_where);
        foreach($coupon as $k=>$v){
            $new_type[] = $v['type'];
        }
        if(in_array($type,$new_type)){
            $data['data']['state'] = 0;
            $data['data']['info'] = '你已经拥有该类型优惠券,请使用后再领取';
        }else{
            $res = model('Coupon')->grantCouponByCode($code,$uid);
            if($res == false){
                $data['data']['status'] = 0;
                $data['data']['info'] = '领取失败';
            }else{
                $data['data']['status'] = 1;
                $data['data']['info'] = '领取成功，请及时使用';
            }
        }
        echo json_encode($data['data']);
        exit;
    }

    //课程列表
    public function course() {
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $this->video_list($mhm_id,1);
        }
        $this->display();
    }
    //班级列表
    public function  album() {
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $this->album_list($mhm_id);
        }
        $this->display();
    }
    //直播列表
    public function live() {
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $this->video_list($mhm_id,2);
        }
        $this->display();
    }
    //讲师列表
    public function teacher_index() {
        if($_GET['id']){
            $mhm_id = model('school')->where(array('id'=>$_GET['id'],'status'=>1,'is_del'=>0))->getField('id');
        }else{
            $mhm_id = model('school')->where(array('doadmin'=>$_GET['doadmin'],'status'=>1,'is_del'=>0))->getField('id');
        }
        if(!$mhm_id){
            header('HTTP/1.1 404 Not Found');exit;
        }else{
            $this->teacher_list($mhm_id);
        }
        $this->display('teacher_index');
    }
}

