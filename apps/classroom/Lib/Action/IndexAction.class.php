<?php

/**
 * Eduline课堂首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class IndexAction extends CommonAction
{
    /**
     * Eduline课堂首页方法
     * @return void
     */
    public function index() {
        $city = $this->getVisitCity() ?:'110100';
        // 取得首页配置
        $config = model('Xdata')->get('admin_Config:index_item');
        if(!$config || !isset($config['item'])){
            $config['item'] = array('live','bestCourse','newCourse','counts','teacher','school','hotTopic');
        }
        if($config['tpl'] == index_new2 && !isset($config['item'])){
            $config['item'] = array('six','open','hotVideo','live_new','teacher_new','starUser');
        }

        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 1);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();

        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);
        //通用课程基础赛选条件
        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['is_mount'] = 1;
        $map['uctime']      = array('GT',time());
        $map['listingtime'] = array('LT',time());

        if($config['tpl'] == 'index' && $this->is_pc) {
            if ($this->is_pc) {
                //公告 活动 新闻
                $tsuggest = M('suggest')->where(array('type' => 1))->order('ctime desc')->field('id,content')->limit(1)->select();
                $topic = M('zy_topic')->where(array('is_del' => 0))->order('dateline desc,id desc')->field('id,`desc`')->limit(1)->select();

                $tdatacate['suggest'] = $tsuggest[0];
                $tdatacate['topic'] = $topic[0];
            }
            //轮播下五个精选课程
            $map['is_best'] = 1;
            $limit_list = M('zy_video')->where($map)->order('best_sort asc,ctime desc')->field('id,video_title,video_intro,cover,type')->limit(6)->select();
            foreach ($limit_list as &$val){
                $val['video_intro'] = t($val['video_intro']);
            }

            /*if($this->is_wap){
                $cate_map['pid'] = ['neq',0];
                $cate_map['is_h5_and_app'] = 1;
                $h5_cate = M('zy_currency_category')->where($cate_map)->order('sort ASC')->field('zy_currency_category_id,pid,title,middle_ids')->limit(10)->findAll();
            }*/

            //加载分类数据
            $cate_and_video = D('ZyVideo')->cateVideo(1,array('is_choice_pc'=>1),7,$city);

            //根据销售最佳读取最佳讲师等信息
            $this->is_pc ? $be_teacher_size = 6 : $be_teacher_size = 6;
            $teacher_user = M('user')->where(['city'=>$city])->field('uid')->select();
            $be_teacher = M('zy_teacher')->where(['is_del'=>0,'is_best'=>1,'uid'=>['in',implode(',',getSubByKey($teacher_user,'uid'))]])->order('best_sort asc,id')->field('id,head_id,name,inro')->limit($be_teacher_size)->select();

            //入住机构
            $this->is_pc ? $check_in_mechanism_size = 30 : $check_in_mechanism_size = 6;
            $check_in_mechanism = model('School')->where(['city'=>$city])->limit($check_in_mechanism_size)->order('ctime desc,id')->field('id,title,doadmin,logo')->select();
            foreach($check_in_mechanism as $key=>$val){
                if ($val['doadmin']) {
                    $check_in_mechanism[$key]['domain'] = getDomain($val['doadmin']);
                } else {
                    $check_in_mechanism[$key]['domain'] = U('school/School/index', array('id' => $val['id']));
                }
            }
        }

        if($config['tpl'] == 'index_new' || $this->is_wap){
            //资讯
            //$topic = M('zy_topic')->where(array('is_del' => 0))->order('readcount desc,id desc')->field('id,title')->limit(9)->select();

            //直播预告
            if(in_array('live',$config['item'])){
                $map ['type']  = 2;
                $time = time();

                unset($map['is_best']);
                $live = model('Live')->getLiveListByTime($time,$map);
                if($live){
                    $live_ctime = [];
                    foreach($live as $key=>$val){
                        foreach($val as $v){
                            $live_ctime[$key] = $v;
                        }
                    }
                    $live_list = [];
                    foreach($live as $key=>$value){
                        foreach($value as $k=>$val){
                            $map['id'] = $k;
                            $live_list[$key][$k] = D('ZyVideo')->where($map)->field('id,uid,video_title,cover,teacher_id,t_price,video_order_count,mhm_id,is_charge,live_type')->find();
                            $live_list[$key][$k]['video_order_count'] = M('zy_order_live') -> where(array('live_id'=> $map['id'], 'is_del' => 0 ,'pay_status'=>3)) -> count();

                            $live_list[$key][$k]['school'] = model('School')->where('id = '.$live_list[$key][$k]['mhm_id'])->field('title,doadmin')->find();
                            //机构域名
                            if ($live_list[$key][$k]['school']['doadmin']) {
                                $live_list[$key][$k]['school']['domain'] = getDomain($live_list[$key][$k]['school']['doadmin']);
                            } else {
                                $live_list[$key][$k]['school']['domain'] = U('school/School/index', array('id' => $live_list[$key][$k]['mhm_id']));
                            }

                            //购买直播实际价格
                            $live_list[$key][$k]['t_price'] = getPrice($live_list[$key][$k],$this -> mid);
                        }
                    }
                }else{
                    $live_list = [];
                }
            }

            //查询课程条件重置
            unset($map['id']);unset($map['type']);unset($map['is_best']);
            //最新课程
            if(in_array('newCourse',$config['item'])){
                $video  = D('ZyVideo')->where($map)->order('ctime desc','id desc')->findALL();
                foreach ($video as $key=>$val) {
                    $video[$key]['school'] = model('School')->where('id = '.$val['mhm_id'])->field('title,doadmin')->find();
                    //机构域名
                    if ($video[$key]['school']['doadmin']) {
                        $video[$key]['school']['domain'] = getDomain($video[$key]['school']['doadmin']);
                    } else {
                        $video[$key]['school']['domain'] = U('school/School/index', array('id' => $val['mhm_id']));
                    }
                    //购买课程实际价格
                    $video[$key]['t_price'] = getPrice($video[$key],$this -> mid);
                }
            }

            //精选课程
            if(in_array('bestCourse',$config['item'])){
                $map ['is_best']     = 1;
                $bestVideo  = D('ZyVideo')->where($map)->order('best_sort asc')->findALL();
                foreach ($bestVideo as $key=>$val) {
                    $bestVideo[$key]['school'] = model('School')->where('id = '.$val['mhm_id'])->field('title,doadmin')->find();
                    //机构域名
                    if ($bestVideo[$key]['school']['doadmin']) {
                        $bestVideo[$key]['school']['domain'] = getDomain($bestVideo[$key]['school']['doadmin']);
                    } else {
                        $bestVideo[$key]['school']['domain'] = U('school/School/index', array('id' => $val['mhm_id']));
                    }
                    //购买课程实际价格
                    $bestVideo[$key]['t_price'] = getPrice($bestVideo[$key],$this -> mid);
                }
            }

            //数据统计展示
            if(in_array('counts',$config['item'])) {
                $count = [];
                unset($map['is_best']);
                $count['course_count'] = D('ZyVideo')->where($map)->count();
                $count['school_count'] = model('School')->where(array('status' => 1, 'is_del' => 0))->count();
                $count['teacher_count'] = D('ZyTeacher')->where(array('is_reject' => 0, 'is_del' => 0))->count();
                $count['user_count'] = model('User')->where(array('is_audit' => 1, 'is_active' => 1, 'is_del' => 0))->count();
                if ($count['user_count'] >= 99999) {
                    $count['user_count'] = 99999;
                }
            }

            //讲师团队
            if(in_array('teacher',$config['item'])) {
                $teacher_info = D('ZyTeacher')->getBestTeacherInfo('id,name,inro,head_id,graduate_school');
            }

            //入驻机构
            if(in_array('school',$config['item'])) {
                $school_info = model('School')->getBestSchoolInfo('id,title,logo,doadmin');
                foreach ($school_info as $key => $val) {
                    $school_info[$key]['domain'] = getDomain($val['doadmin'], $val['id']);
                }
            }

            //推荐小组
            if(in_array('group',$config['item'])) {
                $group_list = M('group')->where(array('status' => 1, 'is_del' => 0))->order('membercount desc,threadcount desc')->limit(6)->select();
                foreach ($group_list as $key => $val) {
                    $member = M('group_member')->where(['gid' => $val['id'], 'uid' => $this->mid])->field('id,level')->find();
                    if ($member['id']) {
                        $group_list[$key]['groupstatus'] = $member['level'] + 1;
                    }
                    $group_list[$key]['logo']        =  SITE_URL.'/data/upload/'.$val['logo'];
                    $group_list[$key]['threadcount'] = M('group_topic')->where('gid=' . $val['id'] . ' and is_del=0')->count();
                    $group_list[$key]['membercount'] = M('group_member')->where('gid=' . $val['id'] . ' and level != 0')->count();
                }
            }

            //热门资讯
            if(in_array('hotTopic',$config['item'])) {
                $hot_topic = M('zy_topic')->where(array('is_del' => 0))->order('readcount desc,dateline desc')->limit(7)->select();
            }
        }
        $this->assign("config_index",$config);
        $this->assign("ad_list", $ad_list);
        $this->assign('mid', $this->mid);
        $this->assign('is_school', is_school($this->mid) ? is_school($this->mid) : 0);

        if($config['tpl'] == 'index'){
            $this->assign('limit_list',$limit_list);
            $this->assign('tdatacate',$tdatacate);
            $this->assign('h5_cate',$h5_cate);
            $this->assign('cate_and_video',$cate_and_video);
            $this->assign("beTeacher", $be_teacher);
            $this->assign("check_in_mechanism", $check_in_mechanism);
        }
        if($config['tpl'] == 'index_new' || $this->is_wap) {
            $this->assign("topic",$topic);
            $this->assign("live_ctime",$live_ctime);
            $this->assign("live",$live_list);
            $this->assign("video",$video);
            $this->assign("bestVideo",$bestVideo);
            $this->assign("count",$count);
            $this->assign("teacher",$teacher_info);
            $this->assign("school",$school_info);
            $this->assign("group",$group_list);
            $this->assign("topic",$hot_topic);
        }
        if($this->is_wap){
            $this->assign("config_tpl",'index_w3g');
        }
        if($config['tpl'] == 'index_new2' || $this->is_wap) {
            //公开课程
            if(in_array('open',$config['item'])){
                $map['is_charge'] = 1;
                //$map['type'] = 1;
                $openVideo  = D('ZyVideo')->where($map)->order('ctime desc')->field('id,video_title,type,cover')->findpage(4);
                unset($map['is_charge'],$map['type']);
            }
            //热门课程
            if(in_array('hotVideo',$config['item'])){
                $hotVideo  = D('ZyVideo')->where($map)->order('video_collect_count desc,video_comment_count desc,
            video_question_count desc,video_note_count desc,video_score desc,video_order_count desc')->field('id,video_title,type,cover')->findpage(4);
            }
            //直播早知道
            if(in_array('live_new',$config['item'])){
                $map ['type']  = 2;
                $time = time()-172800;

                unset($map['is_best']);
                $live = model('Live')->getLiveListByTime($time,$map,5);
                if($live){
                    $live_ctime = [];
                    foreach($live as $key=>$val){
                        foreach($val as $v){
                            $live_ctime[$key] = $v;
                        }
                    }
                    $nowTime = strtotime(date("Y-m-d"),time());

                    $live_list = [];
                    foreach($live as $key=>$value){
                        foreach($value as $k=>$val){
                            $map['id'] = $k;
                            $live_list[$key][$k] = D('ZyVideo')->where($map)->field('id,uid,video_title,cover,teacher_id,t_price,video_order_count,mhm_id,is_charge,live_type')->find();
                            $live_list[$key][$k]['video_order_count'] = M('zy_order_live') -> where(array('live_id'=> $map['id'], 'is_del' => 0 ,'pay_status'=>3)) -> count();

                            $live_list[$key][$k]['school'] = model('School')->where('id = '.$live_list[$key][$k]['mhm_id'])->field('title,doadmin')->find();
                            //机构域名
                            if ($live_list[$key][$k]['school']['doadmin']) {
                                $live_list[$key][$k]['school']['domain'] = getDomain($live_list[$key][$k]['school']['doadmin']);
                            } else {
                                $live_list[$key][$k]['school']['domain'] = U('school/School/index', array('id' => $live_list[$key][$k]['mhm_id']));
                            }
                        }
                    }
                }else{
                    $live_list = [];
                }
            }

            //名师阵容
            if(in_array('teacher_new',$config['item'])) {
                $teacher_info = D('ZyTeacher')->getBestTeacherInfo('id,name,inro,head_id,title');
                foreach($teacher_info as $k=>$v){
                    //讲师等级
                    $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$v['title'])->find();
                    $teacher_info[$k]['teacher_title_category'] = $teacher_title['title'] ?: '普通讲师';
                }
            }
            //明星学员
            if(in_array('starUser',$config['item'])) {
                $star_user = model('User')->getUserFollowers();
            }

            $this->assign("live_ctime",$live_ctime);
            $this->assign("nowTime",$nowTime);
            $this->assign("live",$live_list);
            $this->assign("openVideo",$openVideo);
            $this->assign("hotVideo",$hotVideo);
            $this->assign("teacher",$teacher_info);
            $this->assign("star_user",$star_user);
        }
		
        $tpl = $config['tpl'] ?: 'index';
        $tpl = $_GET['tpl'] ?:$tpl;
        if($this->is_wap){
            $charset     ='utf-8';
            $contentType ='text/html';
            if($config['tpl'] == 'index_new2'){
                $tpl = "index2_w3g";
                echo $this->fetch($tpl,$charset,$contentType,true);
            }else{
                $tpl = "index_w3g";
                echo $this->fetch($tpl,$charset,$contentType,true);
            }
        }else{
            $this->display($tpl);
        }
    }

    //专家智库
    public function tech(){
        $subject_category= M("zy_teacher_category")->where('pid=0')->order('sort asc')->field("zy_teacher_category_id,title")->select();
        //获取分类下的讲师
        $map = array(
            'is_del'=>0,
            'is_reject'=>0,
            'verified_status'=>1,
            'is_best'=>1,
        );
        $order="best_sort asc";
        foreach($subject_category as $key=>$val){
            $teacher_category = $val['zy_teacher_category_id'];
            $map['fullcategorypath'] = ['like',"%,$teacher_category,%"];
            $subject_category[$key]['child'] = D('ZyTeacher','classroom')->where($map)->order($order)->limit(6)->select();
            foreach($subject_category[$key]['child'] as $k=>$val){
                $teacher_title = M('zy_teacher_title_category')->where('zy_teacher_title_category_id='.$val['title'])->find();
                $val['teacher_title'] = $teacher_title['title'] ?: '普通讲师';
                $subject_category[$key]['child'][$k] = $val;
            }
        }
        $this->assign ( 'selCate', $subject_category );
        $this->display();
    }

    //加载头部广告位
    public function adList(){
        //加载首页头部轮播广告位
        $ad_map = array('is_active' => 1,'display_type' => 3,'place' => 23);
        $ad_list = M('ad')->where($ad_map)->order('display_order DESC')->find();
        //序列化广告内容
        $ad_list = unserialize($ad_list['content']);
        return $ad_list;
    }

    //关于我们
    public function about(){
        //广告位
        $ad_list = $this->adList();
        $this->assign('ad_list' , $ad_list);

        //底部导航单页id
        $field = 'id,title';
        $cate_id = D('Single','admin')->getNavById(6,$field);
        $this->assign('cate_id' , $cate_id);

        $id = intval($_GET['id']);
        $res = M('single')->where('id = '.$id.' and is_del=0')->find();
        //获取单页分类
        $cate = D('Single','admin')->getCate();
        //获取当前单页所在分类的所有相关单页
        $single_list = M('single')->where('cate_id = '.$res['cate_id'].' and is_del=0')->findAll();
        $this->assign('data' , $res);
        $this->assign('cate' , $cate[$res['cate_id']]);
        $this->assign('single_list' , $single_list);
        $this->display();
    }
    //获取单页信息
    public function getSingleInfo(){
        $id = intval($_POST['id']);
        $res = M('single')->where('id = '.$id.' and is_del=0')->find();
        echo json_encode($res);
        exit;
    }
    //联系我们
    public function contact(){
        //广告位
        $ad_list = $this->adList();
        $this->assign('ad_list' , $ad_list);

        $id = intval($_GET['id']);
        $res = M('single')->where('id = '.$id.' and is_del=0')->find();
        //获取单页分类
        $cate = D('Single','admin')->getCate();
        //获取当前单页所在分类的所有相关单页
        $single_list = M('single')->where('cate_id = '.$res['cate_id'].' and is_del=0')->findAll();
        $this->assign('data' , $res);
        $this->assign('cate' , $cate[$res['cate_id']]);
        $this->assign('single_list' , $single_list);
        $this->display();
    }

    public function guess_you_like(){
        if(!$this->mid){
            $guess_cate_id = session('gyl_cate_id');
        }else{
            $guess_cate_id = M('zy_guess_you_like')->where(array('tmp_id'=>session_id(),'type'=>0,'uid'=>$this->mid))->getField('cate_id');
        }

        $map['is_del']      = 0;
        $map['is_activity'] = 1;
        $map['is_mount'] = 1;
        $map['fullcategorypath'] = array('like',"%,{$guess_cate_id},%");
        $map['uctime']      = array('GT',time());
        $map['listingtime'] = array('LT',time());

        $size = intval ( getAppConfig ( 'video_gyl_list_num', 'page', 6 ) );
        $guess_you_like = M('zy_video')->where($map)->order('video_collect_count desc,video_comment_count desc,video_question_count desc,
                video_note_count desc,video_score desc,video_order_count desc,listingtime desc')->field("id,video_title,mhm_id,video_intro,cover,v_price,
                t_price,vip_level,is_best,endtime,starttime,limit_discount,uid,teacher_id,type")->findPage($size);
        if(!$guess_you_like['data']){
            unset($map['fullcategorypath']);
            $guess_you_like = M('zy_video')->where($map)->order('video_collect_count desc,video_comment_count desc,video_question_count desc,
                video_note_count desc,video_score desc,video_order_count desc,listingtime desc')->field("id,video_title,mhm_id,video_intro,cover,v_price,
                t_price,vip_level,is_charge,endtime,starttime,limit_discount,uid,teacher_id,type")->findPage($size);
        }
        foreach ($guess_you_like['data'] as $key => $val){
            $guess_you_like['data'][$key]['video_intro']  = mb_substr(t($val['video_intro']),0,50,'utf-8' );
            $guess_you_like['data'][$key]['mhmName']      = model('School')->getSchooldStrByMap(array('id'=>$val['mhm_id']),'title');
            if($val['type'] == 1){
                $guess_you_like['data'][$key]['money_data']   = getPrice ( $val, $this->mid, true, true );
            }else{
                $guess_you_like['data'][$key]['money_data']['oriPrice'] = $val['t_price'];
                $guess_you_like['data'][$key]['money_data']['price'] = $val['t_price'];
            }
        }

        if ($guess_you_like['data']) {
            $this->assign("guess_you_like", $guess_you_like);
            $html = $this->fetch('guess_you_like_list');
        } else {
            $html = '暂无此类数据~~';
        }

        $guess_you_like ['data'] = $html;

        echo json_encode($guess_you_like);
        exit ();
    }

    public function get_sell_well_list(){

        $map['is_best'] = 1;
        $map['is_del']  = 0;
        $map['status']  = 1;

        $size = intval ( getAppConfig ( 'sell_well_list_num', 'page', 17 ) );
        $get_sell_well_list = model('School')->where(array('is_best'=>1,'is_del'=>0,'status'=>1))->order('best_sort asc,id')
            ->field('id,title,doadmin,cover')->findPage($size);

        if ($get_sell_well_list['data']) {
            $this->assign("get_sell_well_list", $get_sell_well_list);
            $html = $this->fetch('get_sell_well_list');
        } else {
            $html = '暂无此类数据~~';
        }

        $get_sell_well_list ['data'] = $html;

        echo json_encode($get_sell_well_list);
        exit ();
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
            $data['qst_description'] = filter_keyword($content);
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
            $data['review_description'] = filter_keyword($content);
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
            $data['note_description'] = filter_keyword($content);
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
            $data['description'] = $reply_id ? '回复<span class="user-reply">@' . getUserName($reply_id) . '</span>:' . filter_keyword($content) : filter_keyword($content);
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
            2 =>'zy_review',
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
        }
        else if ($type == 3) {
            $data['note_help_count'] = array(
                'exp',
                'note_help_count+1'
            );
        }
        $i = M($stable)->where(array(
            'id' => array(
                'eq',
                $rid
            )))->save($data);

        if($type == 2){
            $data['yong'] = array('exp', 'yong+1');
            $i = M($stable)->where('id ='.$rid)->save($data);
        }
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
            if($type == 2){
                $credit = M('credit_setting')->where(array('id'=>8,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $wt_type = 1;
                    $note = '课程点评点赞获得的积分';
                }
                model('Credit')->addUserCreditRule($fid,$wt_type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
            }
            $this->mzSuccess('添加成功');
        } else {
            $this->mzError('添加失败');
        }
    }



/**
     * 添加笔记、提问的评论
     *
     * @return void
     */
    public function tonghelp() {
        $type = 1;
        $rid = intval($_POST['id']);

        if (session('mzaddTopictonwen' . $rid . $type) >= time()) {
            //请不要重复刷新
            $this->mzError('请不要重复点击哦');
        }
        $data['helpnums'] = array('exp', 'helpnums+1');
        $i = M('comment')->where('comment_id ='.$rid)-> setInc('help_num');
        if ($i) {
            session('mzaddTopictonwen' . $rid . $type, time() + 180);
            //查出被评论人的uid和内容
            $finfo = M('comment')->where(array(
                'comment_id' => array(
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
            //积分操作
            $credit = M('credit_setting')->where(array('id'=>33,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ztype = 6;
                $note = '资讯回复被点赞获得的积分';
            }
            model('Credit')->addUserCreditRule($fid,$ztype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            $this->mzSuccess('添加成功');
        } else {
            $this->mzError('添加失败');
        }
    }

    public function search(){
        $time = time();
        $where = "is_del=0 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $video = D('ZyVideo')->where($where)->order('view_nums desc,video_order_count desc,video_comment_count desc')->findPage(5);
        $teacher = D('ZyTeacher')->where(array('is_reject'=>0,'is_del'=>0))->order('views desc,review_count desc,reservation_count desc')->findPage(5);
        $school = model('School')->where(array('status'=>1,'is_del'=>0))->order('view_count desc,visit_num desc,review_count desc')->findPage(5);

        foreach($school['data'] as $key=>$val){
            if ($val['doadmin']) {
                $school['data'][$key]['domain'] = getDomain($val['doadmin']);
            } else {
                $school['data'][$key]['domain'] = U('school/School/index', array('id' => $val['id']));
            }
        }
        $this->assign('video',$video['data']);
        $this->assign('teacher',$teacher['data']);
        $this->assign('school',$school['data']);
        $this->display();
    }
}
