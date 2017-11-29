<?php
tsload(APPS_PATH.'/school/Lib/Action/CommonAction.class.php');
require_once './api/qiniu/rs.php';
class UserAction extends CommonAction{
    protected $base_config = array();
    protected $gh_config = array();
    protected $zshd_config = array();
    protected $cc_config = array();
    /**
     * 初始化
     * @return void
     */
    public function _initialize() {
        $this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
        $this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
        $this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
        $this->cc_config   =  model('Xdata')->get('live_AdminConfig:ccConfig');

        parent::_initialize();
    }

    //机构信息
    public function index(){
        $id = intval($_GET['id']);
        $uid   = intval($this->mid);

        $data = model('School')->where( 'uid=' .$uid )->find ();
        $verifyInfo = M('school_edit_verified')->where(array('mhm_id'=>$data['id'],'verified'=>0))->find();
        $this->assign($data);
        $this->assign('verifyInfo',$verifyInfo);
        $this->display();
    }

    //机构信息
    public function mount(){
        $mount_data = M('zy_video_mount')->where(['is_activity'=>1,'is_del'=>0,'uid'=>$this->mid])->order('atime desc')->field('vid')->select();
        $mount_id = implode(',',getSubByKey($mount_data , 'vid'));
        $map ['is_activity'] = 1;
        $map ['is_del']      = 0;
        $map ['uctime']      = ['gt',time()];
        $map ['listingtime'] = ['lt',time()];
        $map ['id'] = ['in',$mount_id];
        $mount_data = M('zy_video')->where($map)->field('id,video_title,cover,video_binfo,teacher_id,
        v_price,t_price,mhm_id')->findPage(10);

        foreach ($mount_data['data'] as $key => $val){
            $smap['vid'] = $val['id'];
            $smap['pid'] = array('neq',0);
            $count = M('zy_video_section')->where($smap)->count();
            $mount_data['data'][$key]['sectionNum'] = $count;
            $school_info = model('School')->where('id='.$val['mhm_id'])->field('title,school_and_oschool')->find();
            $mount_data['data'][$key]['school_title'] = $school_info['title'];
            $mount_data['data'][$key]['teacher_name'] = M('zy_teacher')->where('id='.$val['teacher_id'])->getField('name');
//            $data ['mzprice'] = getPrice ( $val, $this->mid, true, true );
        }
       
        $this->assign('mount_data',$mount_data);
        $this->display();
    }

    //机构独立域名
    public function domainName(){
        $setDomain = preg_replace('/^[^\.]*/is', '', $_SERVER['HTTP_HOST']);
        $uid   = intval($this->mid);
        $data = model('School')->where( 'uid=' .$uid )->find ();
        $where = array('uid'=>$uid,'type'=>2);
        $verified = M('school_verified')->where($where)->find();
        $this->assign('setDomain',$setDomain);
        if($_POST){
            $map['doadmin'] = array('like', '%' . t($_POST['doadmin']) . '%');
            $domain = model('School')->where($map)->find();
            if($domain){
                echo json_encode(array('info'=>'该域名已经被使用，请更换域名','status'=>'0'));exit;
            }
            $school['uid']  = $uid;
            $school['title']= $data['title'];
            $school['logo'] = $data['logo'];
            $school['doadmin'] = t($_POST['doadmin']);
            $school['type']  = 2;
            $school['ctime'] = time();
            if(!$verified){
                $res = M('school_verified')->add($school);
            }else{
                $school['status'] = 0 ;
                $res = M('school_verified')->where($where)->save($school);
            }
            if($res){
                echo json_encode(array('info'=>'申请成功,请等待审核','status'=>'1'));exit;
            }else{
                echo json_encode(array('info'=>'申请失败','status'=>'0'));exit;
            }
        }else{
            if($verified['status'] == 0){
                $this->assign("verified",$verified);
            }
            $this->assign($data);
            $this->display();
        }
    }
    //机构独立财务账号
    public function finance(){
        $data = D('ZyBcard','classroom')->getUserOnly($this->mid,1);
        $verified = M('finance_verified')->where('uid='.$this->mid)->find();
        if($verified['attach_id']){
            $a = explode('|', $verified['attach_id']);
            foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach','classroom')->where("attach_id=$a[$key]")->find();
                    $verified['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }
        if($verified['status'] == 0 || $verified['status'] == -1){
            $this->assign("verified",$verified);
        }
        if($data['is_school'] == 1){
            $this->assign('data', $data);
        }else if($verified['status'] == -1){
            $this->assign('data', $verified);
        }
        $this->assign('banks', D('ZyBcard','classroom')->getBanks());
        $this->display();
    }

    //视频大小转换方法
    public function formatBytes($size) {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
        return round($size, 2).$units[$i];
    }

    //机构视频空间
    public function videoSpace(){
        $uid   = intval($this->mid);
        $data = model('School')->where('uid='.$uid)->find();
        $where = array('uid'=>$uid,'type'=>1);
        $verified = M('school_verified')->where($where)->find();
        //$data['usedSpace'] = M('zy_video_space')->where('mhm_id='.$data['id'])->getField('used_video_space') ? : 0;
        //$count = $verified['videoSpace'] - $data['usedSpace'];
        $data['usedSpace'] = $data['videoSpace'];
        $count = $data['videoSpace'];
        $data['style'] = round($count / $data['videoSpace'] * 100).'%';
        $oneprice = M('videospaceprice') -> where('id = 1')-> getField('oneprice');
        $this -> assign('oneprice',$oneprice);
        if($_POST){

            $this->assign('jumpUrl', U('school/User/buyvideoSpace'));

//            $school['uid'] = $uid;
//            $school['title'] = $data['title'];
//            $school['logo'] = $data['logo'];
//            $school['videoSpace'] = intval($_POST['videoSpace']);
//            $school['type']  = 1;
//            $school['ctime'] = time();
//            if(!$verified){
//                $res = M('school_verified')->add($school);
//            }else{
//                $school['status'] = 0 ;
//                $res = M('school_verified')->where($where)->save($school);
//            }
//            if($res)$this->error("申请成功,请等待审核");
//            $this->success("申请失败!");
        }
        else{
            if($verified['status'] == 0){
                $this->assign("verified",$verified);
            }

            $this->assign($data);
            $this->display();
        }
    }






    public function buyvideoSpace(){

        $spacecount = $_GET['videoSpace'];
        $oneprice = M('videospaceprice') -> where('id = 1')-> getField('oneprice');
        $totalprice = $oneprice * $spacecount;

        $this->assign('spacecount',$spacecount);
        $this->assign('totalprice',$totalprice);
        $this->display();
    }





    //机构自定义首页
    public function template(){
        $id   = intval($_POST['id']);
        if($_POST){
            $uid = intval($this->mid);
            $data['template'] = implode(',',$_POST['data']);
            $res = model('School')->where(array('uid'=>$uid))->save($data);
            if($res)echo json_encode(array('info'=>'保存成功','status'=>'1'));exit;
            echo json_encode(array('info'=>'保存失败','status'=>'1'));exit;
        }
        $data = model('Attach')->where( 'attach_id=' .$id )->find ();
        $template = model('School')->where('uid='.$this->mid)->getField('template');
        $template = explode(',',$template);
        $this->assign($data);
        $this->assign('template',$template);
        $this->display();
    }
    //处理修改机构信息
    public function doIndex(){
        $uid= intval($this->mid);

        $myAdminLevelhidden         = getCsvInt(t($_POST['school_categoryhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath           = explode(',',$_POST['school_categoryhidden']);
        $category                   = array_pop($fullcategorypath);
        $category                   = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $school_category    = '0' ? array_pop($fullcategorypath) : $category;
        $fullcategorypath   = $myAdminLevelhidden;//分类全路径

        $data = array(
            'title'     => t($_POST['title']),
            'info'      => $_POST['info'],
            'school_category'  => $school_category,
            'fullcategorypath' => $fullcategorypath,
        );
        $data_add = array(
            'logo'      => trim(t($_POST['attach_ids']),'|'),
            'province'  => intval($_POST['province']),
            'city'      => intval($_POST['city']),
            'area'      => intval($_POST['area']),
            'location'  => t($_POST['city_names']),
        );
        $map = array('uid'=>$uid);
        if(!$data_add['logo']){$data_add['logo'] = model('School')->getSchooldStrByMap($map,'logo');}
        if(empty($data_add['logo'])){$this->error("请上传机构logo");}
        if(empty($data['info'])){$this->error("机构简介不能为空");}
        $school = model('School')->where('uid='.$uid)->field('title,info,school_category,fullcategorypath')->find();
        model('School')->where('uid='.$uid)->save($data_add);

        if($data != $school ){
            $data['ctime'] = time();
            $data['mhm_id'] = model('School')->where('uid='.$uid)->getField('id');
            $edit_school = M('school_edit_verified')->where('mhm_id='.$data['mhm_id'])->find();
            if($edit_school){
                $data['verified'] = 0;
                $res = M('school_edit_verified')->where('mhm_id='.$data['mhm_id'])->save($data);
            }else{
                $res = M('school_edit_verified')->add($data);
            }
            if(!$res)$this->error("对不起，提交信息失败！");
        }
        $this->success("提交信息成功!");
    }
    //处理独立财务账户申请
    public function doFinance(){
        $id= intval($_GET['id']);
        $uid = intval($this->mid);
        $title = model('School')->getSchooldTitleByUid($uid);
        $mhm_id = model('School')->where('uid='.$uid)->getField('id');
        $data = array(
            'uid'              => intval($uid),
            'mhm_id'           => intval($mhm_id) ? : 0,
            'title'            => t($title),
            'accounttype'      => t($_POST['accounttype']),
            'account'          => t($_POST['account']),
            'accountmaster'    => t($_POST['accountmaster']),
            'tel_num'          => t($_POST['tel_num']),
            'reason'           => t($_POST['reason']),
            'attach_id'        => trim(t($_POST['attach_ids']),'|'),
            'ctime'            => time(),

        );

        preg_match_all('/./us', $data['reason'], $matchs);   //一个汉字为一个字符
        if(count($matchs[0])>140){echo '认证理由不能超过140个字符';exit;}

        $verified = M('finance_verified')->where('uid=' .$uid)->find();
        if(!$verified){
            $res = M('finance_verified')->add($data);
        }else{
            $data['status'] = 0 ;
            $res = M('finance_verified')->where('uid=' .$uid)->save($data);
        }
        if($res)$this->success("申请成功,请等待审核");
        $this->error("申请失败!");
    }
    //申请成为机构
    public function setInfo(){
        //认证

        $this->rz();
        $school_category=model('CategoryTree')->setTable('school_category')->getNetworkList();
        $this->assign("school_category",$school_category);
        //$this->assign("status",$status);
        $this->display();
    }

    //机构认证
    protected function rz(){
        $verifyInfo = model('School')->where('uid='.$this->mid)->find();
        if($verifyInfo && $verifyInfo['status'] == 2){
            $this->assign('isAdmin',1);
            $this->error('你申请的机构已被禁用,请联系管理员');
        }
        if($verifyInfo['attach_id']){
            $a = explode(',', $verifyInfo['attach_id']);
            foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach','classroom')->where("attach_id=$a[$key]")->find();

                    $verifyInfo['attachment'] .= $attachInfo['save_name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }
        if($verifyInfo['identity_id']){
            $a = explode(',', $verifyInfo['identity_id']);
            foreach($a as $key=>$val){
                if($val !== "") {
                    $attachInfo = D('attach','classroom')->where("attach_id=$a[$key]")->find();

                    $verifyInfo['certification'] .= $attachInfo['save_name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }
        $ceta_name_data = array_filter(explode(',', $verifyInfo['fullcategorypath']));
        $ceta_name = "";
        foreach ($ceta_name_data as $key => &$val){
            $ceta_name .= M('school_category')->where(array('school_category_id'=>$val))->getField('title')." ";
        }
        if($verifyInfo){
            $verifyInfo['ceta_name'] = trim($ceta_name,' ');
            /*if($verifyInfo['other_data']){
                $a = explode('|', $verifyInfo['other_data']);
                foreach($a as $key=>$val){
                    if($val !== "") {
                        $attachInfo = D('attach','classroom')->where("attach_id=$a[$key]")->find();
                        $verifyInfo['other_data_list'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                    }
                }
            }*/
        }
        //附件限制
        $attach = model('Xdata')->get("admin_Config:attachimage");
        $imageArr = array('gif','jpg','jpeg','png','bmp');
        foreach($imageArr as $v){
            if(strstr($attach['attach_allow_extension'],$v)){
                $imageAllow[] = $v;
            }
        }
        $attachOption['attach_allow_extension'] = implode(', ', $imageAllow);
        $attachOption['attach_max_size'] = $attach['attach_max_size'];
        $this->assign('attachOption',$attachOption);

        $user = model('User')->getUserInfo($this->mid);
        $this->assign('verifyInfo',$verifyInfo);
    }

    /**
     * 提交申请认证
     * @return void
     */
    public function doAuthenticate(){
        $verifyInfo = model('School')->where('uid='.$this->mid)->find();

        $myAdminLevelhidden         = getCsvInt(t($_POST['school_categoryhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath           = explode(',',$_POST['school_categoryhidden']);
        $category                   = array_pop($fullcategorypath);
        $category                   = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $data['school_category']    = '0' ? array_pop($fullcategorypath) : $category;
        $data['fullcategorypath']   = $myAdminLevelhidden;//分类全路径

        $data['title']  = filter_keyword(t($_POST['title']));
        $data['idcard'] = t($_POST['idcard']);
        $data['phone']  = t($_POST['phone']);
        $data['reason'] = filter_keyword(t($_POST['reason']));
        $data['address']    = filter_keyword(t($_POST['address']));
        $data['other_data'] = t($_POST['other_data_ids']);
        $identity = t($_POST['identity_ids']);
        $attach = t($_POST['attach_ids']);
        $data['identity_id'] = str_replace('|',',',$identity);
        $data['attach_id']   = str_replace('|',',',$attach);
        //位置信息
        $data['location'] = t($_POST['city_names']);
        $province = intval($_POST['province']);
        $city = intval($_POST['city']);
        $area = intval($_POST['area']);

        isset($province) && $data['province'] = $province;
        isset($city) && $data['city'] = $city;
        isset($area) && $data['area'] = $area;
        $data['ctime'] = time();
        if($verifyInfo){
            $data['status'] = 0;
            $res = model('School')->where('uid='.$verifyInfo['uid'])->save($data);
        }else{
            $data['uid'] = $this->mid;
            $res = model('School')->add($data);
        }
        if($res){
            echo json_encode(array('status'=>'1'));exit;
        }else{
            echo json_encode(array('status'=>'0'));exit;
        }
    }

    /**
     * 注销认证
     * @return bool 操作是否成功  1:成功   0:失败
     */
    public function delverify(){
        $verified_group_id = model('School')->where('uid='.$this->mid)->getField('usergroup_id');
        $data['status'] = 3;
        $res = model('School')->where('uid='.$this->mid)->save($data);
        if($res){
            echo 1;
        }else{
            echo 0;
        }
    }



    /**
     * 会员中心课程--排课处理
     * @return void
     */
    public function ArrCourse(){

        $this->display();
    }

    /**
     * 会员中心课程--排课数据
     * @return void
     */
    public function ArrCoursedata()
    {

        $mid = $this->mid;
        $school = M('school')-> where('uid= '.$mid)->getField('id');
        $data = array('video_title','start','maxmannums');
        $start = t($_GET['start']);
        $end = t($_GET['end']);
        if ($start && $end) {
            $map ['start'] = array('BETWEEN', array($start, $end));
        }
        $map['mhm_id'] = $school;
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
        $map['course_del'] = 0;
        $res = M('arrange_course')->where($map)->field($data)->select();

        $j = 0;
        foreach ($res as $k => $val) {
            $tdata[$j]['title'] = '有直播';
            $tdata[$j]['start'] = $res[$j]['start'];
            $tdata[$j]['maxmannums'] = $res[$j]['maxmannums'];
            for ($i = $j + 1; $i < count($res); $i++) {
                if ($val['start'] == $res[$i]['start']) {
                    $tdata[$j]['title'] =  "有直播";
                    $res[$i]['maxmannums'] = -1000;
                }
            }
            $j++;
        }

        $data = array();
        $i = 0;
        foreach ($tdata as $k => $val) {
            if ($val['maxmannums'] < 0) {
                unset($tdata[$k]);
            }
            if ($val['maxmannums'] >= 0  && $i<168) {
                $data[$i]['title'] = "　　有直播";
                $data[$i]['start'] = strval($val['start']);
                $data[$i]['end'] = strval($val['start'] + 3600);
                $data[$i]['allDay'] = false;
                $i++;
            }
        }
        echo json_encode($data);

    }


    /**
     * 会员中心课程--添加排课处理
     * @return void
     */
    public function addArrCourse(){

        $mid = $this->mid;
        $shoolId = M('school')->where('uid =' . $mid)->getField('id');
        $videomap['mhm_id'] =$shoolId;
        $now = time();
        $now = date('Y-m-d H:0', $now);
        $now = strtotime($now);
        $videomap['is_del'] =0;
        $videomap['uctime'] = array('GT', $now+3600);
        $videomap['listingtime']= array('LT',$now );
        $videomap['type']= 2;
        $videomap['is_activity']= 1;
        $video_title = M('zy_video') ->where($videomap)-> field('id,video_title,live_type') ->select() ;

        $teacher = M('zy_teacher')->where(array('is_del'=>'0','mhm_id'=>$shoolId))->getField('id,name');
        $supportMobile = array('0'=>'不支持','1'=>'支持');
        $ghliveMode = array('1'=>'通用','2'=>'大视频','3'=>'1对1');

         $clientJoin =  array('1'=>'支持手机端','0'=>'不支持手机端');
         $webJoin =  array('1'=>'支持WEB端','0'=>'不支持WEB端');
         $zshduiMode = array('1'=>'模板一 视频直播+聊天互动+直播问答 三分屏','2'=>'模板二 视频为主 文档为小窗','3'=>'模板三 视频直播+直播文档 两分屏','4'=>'模板四 视频直播+聊天互动+直播文档+直播问答');
         $ccuiMode = array('1'=>'模板一 视频直播','2'=>'模板二 视频直播+聊天互动+直播问答','3'=>'模板三 视频直播+聊天互动', '4'=>'模板四 视频直播+聊天互动+直播文档','5'=>'模板五 视频直播+聊天互动+直播文档+直播问答','6'=>'模板六 视频直播+直播问答');


        $this ->assign('video_title',$video_title);
        $this ->assign('teacher',$teacher);
        $this ->assign('supportMobile',$supportMobile);
        $this ->assign('ghliveMode',$ghliveMode);
        $this ->assign('ccclientJoin',$clientJoin);
        $this ->assign('zshdclientJoin',$clientJoin);
        $this ->assign('ccwebJoin',$webJoin);
        $this ->assign('zshdwebJoin',$webJoin);
        $this ->assign('webJoin',$webJoin);
        $this ->assign('zshduiMode',$zshduiMode);
        $this ->assign('ccuiMode',$ccuiMode);


        $this->display('addArrCourse');
    }



    public function doaddgh()
    {

        if(!$_POST['live_id'])
        {
        $this -> error('请选择课程');
        }

        if(!$_POST['speaker_id'])
        {
            $this -> error('请选择讲师');
        }

        if(!$_POST['ghliveMode'])
        {
            $this ->error('请选择直播模式');
        }
        if(!$_POST['ghtaccount'])
        {
            $this ->error('请输入讲师账户');
        }
        if(!$_POST['ghtpasswd'])
        {
           $this ->error('请输入讲师密码');
        }

        if(!$_POST['gheventtime'])
        {
           $this ->error('请选择排课时间');
        }
        if(!$_POST['ghmaxmannums'])
        {
            $this ->error('请输入申请并发量');
        }
        if($_POST['ghmaxmannums'] <= 0)
        {
            $this ->error('申请并发量不能小于等于0');
        }
        if(!$_POST['ghintroduce'])
        {
            $this ->error('课时简介不能为空');
          }

        if(!$_POST['gheventtime'])
        {
            $this ->error('请选择排课时间');

        }
        
        $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
        if($_POST['ghmaxmannums'] > $video_maxmannums)
        {
            $this->error('不能大于该课堂规定并发量');
        }

        $map['uid'] = $this->mid;
        $map['is_del'] = 0;
        $map['pay_status'] = 3;
        $map['stime'] = array('LT', strtotime(t($_POST['gheventtime'])));
        $map['etime'] = array('GT', strtotime(t($_POST['gheventtime'])) + 3600);
        $res = M('zy_order_concurrent')->where($map)->Field('connums')->select();
        $tdata['maxmannums'] = 0;

        $result['start'] = strtotime(t($_POST['gheventtime']));
        $result['startDate'] = strtotime(t($_POST['gheventtime']));
        $result['invalidDate'] = $result['startDate']+3600;
        $result['beginTime'] = strtotime(t($_POST['gheventtime']));
        $result['endTime'] =  $result['beginTime'] + 3600;
        $result['video_title'] = t($_POST['ghtitle']);
        $result['course_id'] = t($_POST['live_id']);
        $result["maxmannums"] = intval(t($_POST['ghmaxmannums']));
        $result["speaker_id"] = t($_POST['speaker_id']);
        $result['is_activity'] = 0;
        $result['is_del'] = 0;
        $mid = $this->mid;
        $shoolId = M('school')->where('uid =' . $mid)->getField('id');
        $result['mhm_id'] = $shoolId;

        $data['live_id'] = t($_POST['live_id']);
        $data["title"] = t($_POST['ghtitle']);
        $data["speaker_id"] = t($_POST['speaker_id']);
        $data['maxAttendees'] = t($_POST['ghmaxmannums']);
        $data["supportMobile"] = t($_POST['supportMobile']);
        $data["liveMode"] = t($_POST['ghliveMode']);
        $data["account"] = t($_POST['ghtaccount']);
        $data["passwd"] = t($_POST['ghtpasswd']);
        $data["introduce"] = t($_POST['ghintroduce']);
        $data["is_active"] = 0;
        $data["is_del"] = 0;
        $data["startDate"] = strtotime(t($_POST['gheventtime']));
        $data["startDate"] = $data["startDate"] * 1000;
        $data["invalidDate"] = strtotime(t($_POST['gheventtime'])) + 3600;
        $data["invalidDate"] = $data["invalidDate"] * 1000;
        $data['uid'] = $this->mid;


        $livegh = M('zy_live_gh');
        $course = M('arrange_course');

        $url  = $this->gh_config['api_url'].'/openApi/createLiveRoom';
        unset($data['systemdata_list']);
        unset($data['systemdata_key']);
        unset($data['pageTitle']);



        if (!$shoolId) {
            $this->error('你不是机构管理员');
        }
        if (!is_numeric($_POST['ghmaxmannums'])) {
            $this->error('并发量必须为数字');
        }
        $detime =model('Xdata')->get('live_AdminLivetime:index')['afnowhours'];
        $tdata['blueconnums'] = 0;
        if(strtotime(t($_POST['gheventtime'])) <(time()+ $detime*3600))
        {
            $blueconmap['starttime'] = $result['startDate'] -1 ;
            $blueconmap['pay_status'] = 3;
            $blueconmap['uid'] = $this->uid;
            $blueconmap['is_del'] = 0;
            $blueres = M('zy_order_bluecon')->where($blueconmap)->field('blueconnums')->select();

            foreach ($blueres as $val) {
                $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
            }

            if(!$blueres) {
                $this->error("当前时间为排课缓冲时间请选择下一个时间段，或者购买绿色通道！");
            }

            if (t($_POST["ghmaxmannums"]) > $tdata['blueconnums']) {
                $this->error('购买的绿色通道不足！');
            }

            $data['maxAttendees'] = 0;
            $result["maxmannums"] = 0;
            $data["blueconnums"] = t($_POST['ghmaxmannums']);
            $result['blueconnums'] = t($_POST['ghmaxmannums']);

            $livegh->startTrans();

            $id = $livegh->add($data);


            if ($id) {
                $data['id'] = $id;
                $gh_data = $livegh ->where('id=' . intval($data['id']))->find();
                $teacher_info = t(M('zy_teacher')->where('id=' . intval($gh_data['speaker_id']))->getField('inro'));
                $data['teachers'] = json_encode(array(array('account' => $data['account'], 'passwd' => base64_encode(md5($data['passwd'], true)), 'info' => $teacher_info)));
                $data = array_merge($data, _ghdata());
                $rest = json_decode(request_post($url, $data), true);


                $result['room_id'] =  $rest['liveRoomId'];
                $result['live_id'] =  $id;
                $result['start'] =  $result['startDate'] ;
                $result['beginTime'] =  $result['startDate'] ;
                $result['endTime'] =  $result['startDate'] + 3600;
                $tres = $course->add($result);

                if ($rest['code'] == 0) {
                    $data['room_id'] = $rest['liveRoomId'];
                    $troom = $livegh ->where('id=' . $id)->save($data);

                    if(!$troom)
                    {
                        $livegh ->rollback();
                        $this->error('排课失败!!');
                        return;
                    }
                    if(!$tres)
                    {
                        $livegh ->rollback();
                        $this->error('排课失败!');
                        return;
                    }
                    $livegh ->commit();
                    $this->success('排课成功，请等待审核！');
                    return;
                } else {
                    //删除本地数据
                    M('zy_live_gh')->where('id=' . $id)->delete();
                    $this->error('创建失败!');
                }
            } else {
                $livegh ->rollback();
                $this->error('创建失败!');
            }
        }

        foreach ($res as $val) {
            $tdata['maxmannums'] = $tdata['maxmannums'] + $val['connums'];
        }


        $bluemap['starttime'] = $result['startDate'] -1 ;
        $bluemap['pay_status'] = 3;
        $bluemap['uid'] = $this->uid;
        $bluemap['is_del'] = 0;
        $blueres = M('zy_order_bluecon')->where($bluemap)->field('blueconnums')->select();
        $tdata['blueconnums'] = 0;
        $curemannums = t($_POST["ghmaxmannums"]);

        if ($blueres) {
            foreach ($blueres as $val) {
                $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
            }
            $curemannums = $_POST["ghmaxmannums"] - $tdata['blueconnums'];
        }

        if (!$res && !$blueres) {
            $this->error("对不起，请先购买该时段并发数目或者绿色通道！");
        }


        $nums['maxmannums'] = 0;

        if ($res) {
            $ever['start'] = strtotime(t($_POST['gheventtime']));
            $ever['mhm_id'] = $shoolId;
            $ever['is_del'] = 0;
            $ever['is_activity'] = 1;

            $total = M('arrange_course')->where($ever)->Field('maxmannums')->select();
            if ($total) {
                foreach ($total as $val) {
                    $nums['maxmannums'] = $nums['maxmannums'] + $val['maxmannums'];
                }
            }

        }

        $tdata['maxmannums'] = $tdata['maxmannums'] + $tdata['blueconnums'];


        if ($tdata['maxmannums'] < intval(t($_POST['ghmaxmannums'] + $nums['maxmannums']))) {
            $this->error("对不起，你购买的并发数量或者绿色通道不足");
        }

        $livegh = M('zy_live_gh');
        $course = M('arrange_course');
        $dafengchetb['start'] = strtotime(t($_POST['gheventtime']));
        $dafengchetb['is_del'] = 0;
        $dafengchetb['is_activity'] = 1;
        $donemanmus['maxmannums'] = 0;
        $resmannums = M('arrange_course')->where($dafengchetb)->field('maxmannums')->select();
        if ($resmannums) {
            foreach ($total as $val) {
                $donemanmus['maxmannums'] = $donemanmus['maxmannums'] + $val['maxmannums'];
            }
        }
        $allconnums =  M('concurrent')->where('id = 1')->getField('Concurrent_nums');
        $resconnums = $allconnums - $donemanmus['maxmannums'];

        if ($_POST["ghmaxmannums"] > $resconnums) {

            if($curemannums > $resconnums) {

                $this->error('没有足够的并发');
            }
        }

        $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
        if((t($_POST['ghmaxmannums']) - $tdata['blueconnums']) > $video_maxmannums)
        {
            $this->error('不能大于该课堂规定并发量');
        }
        $result['blueconnums'] = $tdata['blueconnums'];

        if(t($_POST['ghmaxmannums']) >=  ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
        {

            $result['maxmannums'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
            $result['blueconnums'] =  t($_POST['ghmaxmannums']) -  $result['maxmannums'] ;
            $data['maxAttendees'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
            $data["blueconnums"] = t($_POST['ghmaxmannums']) -  $result['maxmannums'] ;

            if( $resconnums >= $video_maxmannums) {
                if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $video_maxmannums) {
                    $result['maxmannums'] = $video_maxmannums;
                    $result['blueconnums'] = t($_POST['ghmaxmannums']) - $result['maxmannums'];
                    $data['maxAttendees'] = $video_maxmannums;
                    $data["blueconnums"] = t($_POST['ghmaxmannums']) - $data['maxAttendees'];
                }
            }
            else {
                if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $resconnums) {
                    $result['maxmannums'] = $resconnums;
                    $result['blueconnums'] = t($_POST['ghmaxmannums']) - $result['maxmannums'];
                    $data['maxAttendees'] = $resconnums;
                    $data["blueconnums"] = t($_POST['ghmaxmannums']) - $data['maxAttendees'];
                }
            }
        }

        if(t($_POST['ghmaxmannums']) < ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
        {
            $result['maxmannums'] = t($_POST['ghmaxmannums']);
            $result['blueconnums'] = 0;
            $data['maxAttendees'] = t($_POST['ghmaxmannums']);
            $data["blueconnums"] = 0;

            if( $resconnums >= $video_maxmannums) {
                if (t($_POST['ghmaxmannums']) > $video_maxmannums) {

                    $result['maxmannums'] = $video_maxmannums;
                    $result['blueconnums'] = t($_POST['maxmannums']) - $result['maxmannums'];
                    $data['maxAttendees'] = $video_maxmannums;
                    $data["blueconnums"] = t($_POST['ghmaxmannums']) - $data['maxAttendees'];
                }
            }
            else {
                if (t($_POST['ghmaxmannums']) > $resconnums) {
                    $result['maxmannums'] = $resconnums;
                    $result['blueconnums'] = t($_POST['ghmaxmannums']) - $result['maxmannums'];
                    $data['maxAttendees'] = $resconnums;
                    $data["blueconnums"] = t($_POST['ghmaxmannums']) - $data['maxAttendees'];
                }
            }
        }

        $opp['is_del'] = 0;
        $opp['course_id'] = t($_POST['live_id']);
        $opp['start'] = strtotime(t($_POST['gheventtime']));
        $videotime = M('arrange_course') -> where($opp) ->select();
        if($videotime)
        {
            $this->error('该课程在此时间段已存在直播课时');
        }
        $url = $this->gh_config['api_url'] . '/openApi/createLiveRoom';


        $livegh->startTrans();
        $id = $livegh->add($data);

        if ($id) {
            $data['id'] = $id;
            $gh_data = $livegh ->where('id=' . intval($data['id']))->find();
            $teacher_info = t(M('zy_teacher')->where('id=' . intval($gh_data['speaker_id']))->getField('inro'));
            $data['teachers'] = json_encode(array(array('account' => $data['account'], 'passwd' => base64_encode(md5($data['passwd'], true)), 'info' => $teacher_info)));
            $data = array_merge($data, _ghdata());
            $rest = json_decode(request_post($url, $data), true);
            
            $result['room_id'] =  $rest['liveRoomId'];
            $result['start'] =  $result['startDate'] ;
            $result['beginTime'] =  $result['startDate'] ;
            $result['endTime'] =  $result['startDate'] + 3600;
            $result['live_id'] =  $id;
            $tres = $course->add($result);

            if ($rest['code'] == 0) {
                $data['room_id'] = $rest['liveRoomId'];
                $troom = $livegh ->where('id=' . $id)->save($data);

                if(!$troom)
                {
                    $livegh ->rollback();
                    $this->error('排课失败!!');
                    return;
                }
                if(!$tres)
                {
                    $livegh ->rollback();
                    $this->error('排课失败!');
                    return;
                }
                $livegh ->commit();
                $this->success('排课成功，请等待审核！');
                return;
            } else {
                //删除本地数据
                M('zy_live_gh')->where('id=' . $id)->delete();
                $this->error('创建失败!');
            }
        } else {
            $livegh ->rollback();
            $this->error('创建失败!');
        }
    }




    public function doaddzshd()
    {


        if (isset($_POST)) {

            $startDate = strtotime($_POST['zshdeventtime']);
            $invalidDate = strtotime($_POST['zshdeventtime']) + 3600;

            $newTime = time();
            $livemap['zshdtitle'] = trim(t($_POST['zshdtitle']));
            $field = 'zshdtitle';
            $liveSubject = model('Live')->getZshdLiveRoomInfo($livemap, $field);

            if (empty($_POST['zshdtitle'])) {
                $this->error('直播课时名称不能为空');
            }
            if ($_POST['zshdtitle'] == $liveSubject['subject']) {
                $this->error('已有此直播课时名称,请勿重复添加');
            }
            if (empty($_POST['speaker_id'])) {
                $this->error('演讲人不能为空');
            }
            if (empty($startDate)) {
                $this->error('开始时间不能为空');
            }
            if ($startDate < $newTime) {
                $this->error('开始时间必须大于当前时间');
            }
            if (empty($invalidDate)) {
                $this->error('结束时间不能为空');
            }
            if ($_POST['zshdclientJoin'] == 0) {
                $clientJoin = 'false';
            } else {
                $clientJoin = 'true';
            }
            if ($_POST['zshdwebJoin'] == 0) {
                $webJoin = 'false';
            } else {
                $webJoin = 'true';
            }
            if (!$clientJoin && !$webJoin) {
                $this->error('Web端学生加入或客户端开启学生加入必须开启其一');
            }
            if (empty($_POST['zshdteacherToken'])) {
                $this->error('老师口令不能为空');
            }
            if (!is_numeric($_POST['zshdteacherToken'])) {
                $this->error('老师口令必须为数字');
            }
            if (strlen($_POST['zshdteacherToken']) < 6 || strlen($_POST['zshdteacherToken']) > 15) {
                $this->error('老师口令只能为6-15位数字');
            }
            if (empty($_POST['zshdassistantToken'])) {
                $this->error('助教口令不能为空');
            }
            if (!is_numeric($_POST['zshdassistantToken'])) {
                $this->error('助教口令必须为数字');
            }
            if (strlen($_POST['zshdassistantToken']) < 6 || strlen($_POST['zshdassistantToken']) > 15) {
                $this->error('助教口令只能为6-15位数字');
            }
            if (empty($_POST['zshdstudentToken'])) {
                $this->error('学生WEB端口令不能为空');
            }
            if (!is_numeric($_POST['zshdstudentToken'])) {
                $this->error('学生WEB端口令必须为数字');
            }
            if (strlen($_POST['zshdstudentToken']) < 6 || strlen($_POST['zshdstudentToken']) > 15) {
                $this->error('学生WEB端口令只能为6-15位数字');
            }
            if ($_POST['zshdteacherToken'] == $_POST['zshdassistantToken'] || $_POST['zshdteacherToken'] == $_POST['zshdstudentClientToken'] || $_POST['zshdteacherToken'] == $_POST['zshdstudentToken'] || $_POST['zshdassistantToken'] == $_POST['zshdstudentClientToken']
                || $_POST['zshdassistantToken'] == $_POST['zshdstudentToken'] || $_POST['zshdstudentClientToken'] == $_POST['zshdstudentToken']
            ) {
                $this->error('四个口令的值不能相同');
            }
            if (empty($_POST['zshdscheduleInfo'])) {
                $this->error('直播课时安排信息不能为空');
            }
            if (empty($_POST['zshddescription'])) {
                $this->error('直播课时信息不能为空');
            }

            $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
            if($_POST['zshdmaxmannums'] > $video_maxmannums)
            {
                $this->error('不能大于该课堂规定并发量');
            }

            $map['uid'] = $this->mid;
            $map['is_del'] = 0;
            $map['pay_status'] = 3;
            $map['stime'] = array('LT', strtotime(t($_POST['zshdeventtime'])));
            $map['etime'] = array('GT', strtotime(t($_POST['zshdeventtime'])) + 3600);

            $res = M('zy_order_concurrent')->where($map)->Field('connums')->select();

            $tdata['maxmannums'] = 0;


            $result['start'] = strtotime(t($_POST['zshdeventtime']));
            $result['startDate'] = strtotime(t($_POST['zshdeventtime']));
            $result['beginTime'] = strtotime(t($_POST['zshdeventtime']));
            $result['endTime'] =  $result['beginTime'] + 3600;
            $result['invalidDate'] = $result['startDate']+3600;
            $result['video_title'] = t($_POST['zshdtitle']);
            $result['course_id'] = t($_POST['live_id']);
            $result["maxmannums"] = intval(t($_POST['zshdmaxmannums']));
            $result["speaker_id"] = t($_POST['speaker_id']);
            $result['is_activity'] = 0;
            $result['is_del'] = 0;
            $mid = $this->mid;
            $shoolId = M('school')->where('uid =' . $mid)->getField('id');
            $result['mhm_id'] = $shoolId;



            $live_zshd  =  M('zy_live_zshd');
            $course = M('arrange_course');

            if (!$shoolId) {
                $this->error('你不是机构管理员');
            }


            $detime =model('Xdata')->get('live_AdminLivetime:index')['afnowhours'];
            $tdata['blueconnums'] = 0;
            if(strtotime(t($_POST['zshdeventtime'])) <(time()+ $detime*3600))
            {

                $blueconmap['starttime'] = $result['startDate'] -1 ;
                $blueconmap['pay_status'] = 3;
                $blueconmap['uid'] = $this->uid;
                $blueconmap['is_del'] = 0;
                $blueres = M('zy_order_bluecon')->where($blueconmap)->field('blueconnums')->select();

                foreach ($blueres as $val) {
                    $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
                }

                if(!$blueres) {
                    $this->error("当前时间为排课缓冲时间请选择下一个时间段，或者购买绿色通道！");
                }

                if (t($_POST["zshdmaxmannums"]) > $tdata['blueconnums']) {
                    $this->error('购买的绿色通道不足！');
                }

                $data['maxAttendees'] = 0;
                $result["maxmannums"] = 0;
                $data["blueconnums"] = t($_POST['zshdmaxmannums']);
                $result['blueconnums'] = t($_POST['zshdmaxmannums']);




                $result['start'] =  $result['startDate'] ;
                $result['beginTime'] =  $result['startDate'] ;
                $result['endTime'] =  $result['startDate'] + 3600;




                $speaker = M('zy_teacher')->where("id={$_POST['speaker']}")->field('id,name,inro')->find();
                $url = $this->zshd_config['api_url'] . '/room/created?';
                $param = 'subject=' . urlencode(t($_POST['zshdtitle'])) . '&startDate=' . t($startDate * 1000) .
                    '&invalidDate=' . t($invalidDate * 1000) . '&teacherToken=' . t($_POST['zshdteacherToken']) .
                    '&assistantToken=' . t($_POST['zshdassistantToken']) . '&studentClientToken=' . t($_POST['zshdstudentClientToken']) .
                    '&studentToken=' . t($_POST['zshdstudentToken']) . '&scheduleInfo=' . urlencode(t($_POST['zshdscheduleInfo'])) .
                    '&description=' . urlencode(t($_POST['zshddescription'])) . '&clientJoin=' . $clientJoin . '&webJoin=' . $webJoin .
                    '&uiMode=' . intval($_POST['zshduiMode']) . '&speakerInfo=' . urlencode(t($speaker['inro']));
                $hash = $param . '&loginName=' . $this->zshd_config['api_key'] . '&password=' . md5($this->zshd_config['api_pwd']) . '&sec=true';
                $url = $url . $hash;
                $addLive = getDataByUrl($url);

                if ($addLive['code'] == 0) {
                    if (empty($addLive["number"])) {
                        $this->error('服务器创建失败');
                    }
                    //查此次插入数据库的课堂名称
                    $url = $this->zshd_config['api_url'] . '/room/info?';
                    $param = 'roomId=' . $addLive["id"];
                    $hash = $param . '&loginName=' . $this->zshd_config['api_key'] . '&password=' . md5($this->zshd_config['api_pwd']) . '&sec=true';
                    $url = $url . $hash;
                    $live = getDataByUrl($url);
                    if (empty($live["number"])) {
                        $this->error('服务器查询失败');
                    }

                    if ($addLive["clientJoin"]) {
                        $liveClientJoin = 1;
                    } else {
                        $liveClientJoin = 0;
                    }
                    if ($addLive["webJoin"]) {
                        $liveWebJoin = 1;
                    } else {
                        $liveWebJoin = 0;
                    }

                    $data["uid"] = $this->mid;
                    $data["number"] = $addLive["number"];
                    $data["subject"] = $live['subject'];
                    $data["speaker_id"] = intval($_POST['speaker_id']);
                    $data["startDate"] = $addLive["startDate"] / 1000;
                    $data["invalidDate"] = $addLive["invalidDate"] / 1000;
                    $data["teacherJoinUrl"] = $addLive["teacherJoinUrl"];
                    $data["studentJoinUrl"] = $addLive["studentJoinUrl"];
                    $data["teacherToken"] = $addLive["teacherToken"];
                    $data["assistantToken"] = $addLive["assistantToken"];
                    $data["studentClientToken"] = $addLive["studentClientToken"];
                    $data["studentToken"] = $addLive["studentToken"];
                    $data["scheduleInfo"] = t($_POST['zshdscheduleInfo']);
                    $data["description"] = t($_POST['zshddescription']);
                    $data['uiMode'] = intval($_POST['zshduiMode']);
                    $data["clientJoin"] = $liveClientJoin;
                    $data["webJoin"] = $liveWebJoin;
                    $data["roomid"] = $addLive["id"];
                    $data["is_active"] = 0;
                    $data["live_id"] = $_POST['live_id'];

                    $live_zshd ->startTrans();

                    $result['start'] =  $result['startDate'] ;
                    $result['beginTime'] =  $result['startDate'] ;
                    $result['endTime'] =  $result['startDate'] + 3600;
                    $result['room_id'] = $data["roomid"];
                    $ret = $live_zshd ->add($data);
                    $result['live_id'] = $ret;
                    $tres = $course->add($result);
                    if (!$ret) {
                        $live_zshd  ->rollback();
                        $this->error('创建失败!');
                    }
                    if (!$tres) {
                        $live_zshd  ->rollback();
                        $this->error('创建失败!');
                    }

                    $live_zshd ->commit();
                    $this->success('创建成功，请等待审核');
                }

                else {
                    $this->error('服务器出错啦');
                }

            }




            foreach ($res as $val) {
                $tdata['maxmannums'] = $tdata['maxmannums'] + $val['connums'];
            }


            $bluemap['starttime'] = $result['startDate'] -1 ;
            $bluemap['pay_status'] = 3;
            $bluemap['uid'] = $this->uid;
            $bluemap['is_del'] = 0;
            $blueres = M('zy_order_bluecon')->where($bluemap)->field('blueconnums')->select();
            $tdata['blueconnums'] = 0;
            $curemannums = t($_POST["zshdmaxmannums"]);

            if ($blueres) {
                foreach ($blueres as $val) {
                    $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
                }
                $curemannums = $_POST["zshdmaxmannums"] - $tdata['blueconnums'];
            }



            if (!$res && !$blueres) {
                $this->error("对不起，请先购买该时段并发数目或者绿色通道！");
            }


            $nums['maxmannums'] = 0;

            if ($res) {
                $ever['start'] = strtotime(t($_POST['zshdeventtime']));
                $ever['mhm_id'] = $shoolId;
                $ever['is_del'] = 0;
                $ever['is_activity'] = 1;

                $total = M('arrange_course')->where($ever)->Field('maxmannums')->select();
                if ($total) {
                    foreach ($total as $val) {
                        $nums['maxmannums'] = $nums['maxmannums'] + $val['maxmannums'];
                    }
                }

            }

            $tdata['maxmannums'] = $tdata['maxmannums'] + $tdata['blueconnums'];


            if ($tdata['maxmannums'] < intval(t($_POST['zshdmaxmannums'] + $nums['maxmannums']))) {
                $this->error("对不起，你购买的并发数量或者绿色通道不足");
            }


            $course = M('arrange_course');
            $dafengchetb['start'] = strtotime(t($_POST['zshdeventtime']));
            $dafengchetb['is_del'] = 0;
            $dafengchetb['is_activity'] = 1;
            $donemanmus['maxmannums'] = 0;
            $resmannums = M('arrange_course')->where($dafengchetb)->field('maxmannums')->select();
            if ($resmannums) {
                foreach ($total as $val) {
                    $donemanmus['maxmannums'] = $donemanmus['maxmannums'] + $val['maxmannums'];
                }
            }
            $allconnums =  M('concurrent')->where('id = 1')->getField('Concurrent_nums');
            $resconnums = $allconnums - $donemanmus['maxmannums'];

            if ($_POST["zshdmaxmannums"] > $resconnums) {

                if($curemannums > $resconnums) {

                    $this->error('没有足够的并发');
                }
            }

            $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
            if((t($_POST['zshdmaxmannums']) - $tdata['blueconnums']) > $video_maxmannums)
            {
                $this->error('不能大于该课堂规定并发量');
            }
            $result['blueconnums'] = $tdata['blueconnums'];

            if(t($_POST['zshdmaxmannums']) >=  ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
            {

                $result['maxmannums'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
                $result['blueconnums'] =  t($_POST['zshdmaxmannums']) -  $result['maxmannums'] ;
                $data['maxAttendees'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
                $data["blueconnums"] = t($_POST['zshdmaxmannums']) -  $result['maxmannums'] ;

                if( $resconnums >= $video_maxmannums) {
                    if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $video_maxmannums) {
                        $result['maxmannums'] = $video_maxmannums;
                        $result['blueconnums'] = t($_POST['zshdmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $video_maxmannums;
                        $data["blueconnums"] = t($_POST['zshdmaxmannums']) - $data['maxAttendees'];
                    }
                }
                else {
                    if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $resconnums) {
                        $result['maxmannums'] = $resconnums;
                        $result['blueconnums'] = t($_POST['zshdmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $resconnums;
                        $data["blueconnums"] = t($_POST['zshdmaxmannums']) - $data['maxAttendees'];
                    }
                }
            }

            if(t($_POST['zshdmaxmannums']) < ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
            {
                $result['maxmannums'] = t($_POST['zshdmaxmannums']);
                $result['blueconnums'] = 0;
                $data['maxAttendees'] = t($_POST['zshdmaxmannums']);
                $data["blueconnums"] = 0;

                if( $resconnums >= $video_maxmannums) {
                    if (t($_POST['zshdmaxmannums']) > $video_maxmannums) {

                        $result['maxmannums'] = $video_maxmannums;
                        $result['blueconnums'] = t($_POST['maxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $video_maxmannums;
                        $data["blueconnums"] = t($_POST['zshdmaxmannums']) - $data['maxAttendees'];
                    }
                }
                else {
                    if (t($_POST['zshdmaxmannums']) > $resconnums) {
                        $result['maxmannums'] = $resconnums;
                        $result['blueconnums'] = t($_POST['zshdmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $resconnums;
                        $data["blueconnums"] = t($_POST['zshdmaxmannums']) - $data['maxAttendees'];
                    }
                }
            }


            $opp['is_del'] = 0;
            $opp['course_id'] = t($_POST['live_id']);
            $opp['start'] = strtotime(t($_POST['zshdeventtime']));
            $videotime = M('arrange_course') -> where($opp) ->select();

            if($videotime)
            {
                $this->error('该课程在此时间段已存在直播课时');
            }


            $speaker = M('zy_teacher')->where("id={$_POST['speaker']}")->field('id,name,inro')->find();
            $url = $this->zshd_config['api_url'] . '/room/created?';
            $param = 'subject=' . urlencode(t($_POST['zshdtitle'])) . '&startDate=' . t($startDate * 1000) .
                '&invalidDate=' . t($invalidDate * 1000) . '&teacherToken=' . t($_POST['zshdteacherToken']) .
                '&assistantToken=' . t($_POST['zshdassistantToken']) . '&studentClientToken=' . t($_POST['zshdstudentClientToken']) .
                '&studentToken=' . t($_POST['zshdstudentToken']) . '&scheduleInfo=' . urlencode(t($_POST['zshdscheduleInfo'])) .
                '&description=' . urlencode(t($_POST['zshddescription'])) . '&clientJoin=' . $clientJoin . '&webJoin=' . $webJoin .
                '&uiMode=' . intval($_POST['zshduiMode']) . '&speakerInfo=' . urlencode(t($speaker['inro']));
            $hash = $param . '&loginName=' . $this->zshd_config['api_key'] . '&password=' . md5($this->zshd_config['api_pwd']) . '&sec=true';
            $url = $url . $hash;
            $addLive = getDataByUrl($url);

            if ($addLive['code'] == 0) {
                if (empty($addLive["number"])) {
                    $this->error('服务器创建失败');
                }
                //查此次插入数据库的课堂名称
                $url = $this->zshd_config['api_url'] . '/room/info?';
                $param = 'roomId=' . $addLive["id"];
                $hash = $param . '&loginName=' . $this->zshd_config['api_key'] . '&password=' . md5($this->zshd_config['api_pwd']) . '&sec=true';
                $url = $url . $hash;
                $live = getDataByUrl($url);
                if (empty($live["number"])) {
                    $this->error('服务器查询失败');
                }

                if ($addLive["clientJoin"]) {
                    $liveClientJoin = 1;
                } else {
                    $liveClientJoin = 0;
                }
                if ($addLive["webJoin"]) {
                    $liveWebJoin = 1;
                } else {
                    $liveWebJoin = 0;
                }

                $data["uid"] = $this->mid;
                $data["number"] = $addLive["number"];
                $data["subject"] = $live['subject'];
                $data["speaker_id"] = intval($_POST['speaker_id']);
                $data["startDate"] = $addLive["startDate"] / 1000;
                $data["invalidDate"] = $addLive["invalidDate"] / 1000;
                $data["teacherJoinUrl"] = $addLive["teacherJoinUrl"];
                $data["studentJoinUrl"] = $addLive["studentJoinUrl"];
                $data["teacherToken"] = $addLive["teacherToken"];
                $data["assistantToken"] = $addLive["assistantToken"];
                $data["studentClientToken"] = $addLive["studentClientToken"];
                $data["studentToken"] = $addLive["studentToken"];
                $data["scheduleInfo"] = t($_POST['zshdscheduleInfo']);
                $data["description"] = t($_POST['zshddescription']);
                $data['uiMode'] = intval($_POST['zshduiMode']);
                $data["clientJoin"] = $liveClientJoin;
                $data["webJoin"] = $liveWebJoin;
                $data["roomid"] = $addLive["id"];
                $data["is_active"] = 0;
                $data["live_id"] = $_POST['live_id'];


                $live_zshd  =  M('zy_live_zshd');
                $live_zshd ->startTrans();
                $course = M('arrange_course');
                $ret = $live_zshd->add($data);

                $result['start'] =  $result['startDate'] ;
                $result['beginTime'] =  $result['startDate'] ;
                $result['endTime'] =  $result['startDate'] + 3600;
                $result['room_id'] = $data["roomid"];
                $result['live_id'] = $ret;
                $tres = $course->add($result);

                if (!$ret) {
                    $live_zshd  ->rollback();
                    $this->error('创建失败!');
                }
                if (!$tres) {
                    $live_zshd  ->rollback();
                    $this->error('创建失败!');
                }
                $live_zshd ->commit();
                $this->success('创建成功，请等待审核');
            }

            else {
                $this->error('服务器出错啦');
            }

        }
    }



    public function doaddcc()
    {

        if (isset($_POST)) {
            $startDate = strtotime($_POST['cceventtime']);
            $invalidDate = strtotime($_POST['cceventtime'])+3600;

            if (empty($_POST['cctitle'])) {
                $this->error('直播课时名称不能为空');
            }
            if (empty($_POST['speaker_id'])) {
                $this->error('演讲人不能为空');
            }
            if (empty($startDate)) {
                $this->error('开始时间不能为空');
            }
            if (empty($invalidDate)) {
                $this->error('结束时间不能为空');
            }

            if (empty($_POST['ccmaxmannums'])) {
                $this->error('最大并发不能为空');
            }
            if (!is_numeric($_POST['ccmaxmannums'])) {
                $this->error('最大并发必须为数字');
            }
            if (empty($_POST['ccuiMode'])) {
                $this->error('直播模版不能为空');
            }
            if (empty($_POST['ccteacherToken'])) {
                $this->error('老师口令不能为空');
            }
            if (!is_numeric($_POST['ccteacherToken'])) {
                $this->error('老师口令必须为数字');
            }
            if (strlen($_POST['ccteacherToken']) < 6 || strlen($_POST['ccteacherToken']) > 15) {
                $this->error('老师口令只能为6-15位数字');
            }
            if (empty($_POST['ccassistantToken'])) {
                $this->error('助教口令不能为空');
            }
            if (!is_numeric($_POST['ccassistantToken'])) {
                $this->error('助教口令必须为数字');
            }
            if (strlen($_POST['ccassistantToken']) < 6 || strlen($_POST['ccassistantToken']) > 15) {
                $this->error('助教口令只能为6-15位数字');
            }
            if (empty($_POST['ccstudentClientToken'])) {
                $this->error('学生口令不能为空');
            }
            if (!is_numeric($_POST['ccstudentClientToken'])) {
                $this->error('学生口令必须为数字');
            }
            if (strlen($_POST['ccstudentClientToken']) < 6 || strlen($_POST['ccstudentClientToken']) > 15) {
                $this->error('学生口令只能为6-15位数字');
            }
            if (empty($_POST['ccdescription'])) {
                $this->error('直播课时信息不能为空');
            }

            $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
            if($_POST['ccmaxmannums'] > $video_maxmannums)
            {
                $this->error('不能大于该课堂规定并发量');
            }


            $map['uid'] = $this->mid;
            $map['is_del'] = 0;
            $map['pay_status'] = 3;
            $map['stime'] = array('LT', strtotime(t($_POST['cceventtime'])));
            $map['etime'] = array('GT', strtotime(t($_POST['cceventtime'])) + 3600);

            $res = M('zy_order_concurrent')->where($map)->Field('connums')->select();

            $tdata['maxmannums'] = 0;


            $result['start'] = strtotime(t($_POST['cceventtime']));
            $result['startDate'] = strtotime(t($_POST['cceventtime']));
            $result['invalidDate'] = $result['startDate']+3600;

            $result['beginTime'] = strtotime(t($_POST['cceventtime']));
            $result['endTime'] = $result['beginTime']+3600;

            $result['video_title'] = t($_POST['cctitle']);
            $result['course_id'] = t($_POST['live_id']);
            $result["maxmannums"] = intval(t($_POST['ccmaxmannums']));
            $result["speaker_id"] = t($_POST['speaker_id']);
            $result['is_activity'] = 0;
            $result['is_del'] = 0;
            $mid = $this->mid;
            $shoolId = M('school')->where('uid =' . $mid)->getField('id');
            $result['mhm_id'] = $shoolId;


            $live_cc  =  M('zy_live_cc');
            $course = M('arrange_course');

            if (!$shoolId) {
                $this->error('你不是机构管理员');
            }



            $detime =model('Xdata')->get('live_AdminLivetime:index')['afnowhours'];
            $tdata['blueconnums'] = 0;




            if(strtotime(t($_POST['cceventtime'])) <(time()+ $detime*3600))
            {

                $blueconmap['starttime'] = $result['startDate'] -1 ;
                $blueconmap['pay_status'] = 3;
                $blueconmap['uid'] = $this->uid;
                $blueconmap['is_del'] = 0;
                $blueres = M('zy_order_bluecon')->where($blueconmap)->field('blueconnums')->select();

                foreach ($blueres as $val) {
                    $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
                }

                if(!$blueres) {
                    $this->error("当前时间为排课缓冲时间请选择下一个时间段，或者购买绿色通道！");
                }

                if (t($_POST["ccmaxmannums"]) > $tdata['blueconnums']) {
                    $this->error('购买的绿色通道不足！');
                }

                $data['maxAttendees'] = 0;
                $result["maxmannums"] = 0;
                $data["blueconnums"] = t($_POST['ccmaxmannums']);
                $result['blueconnums'] = t($_POST['ccmaxmannums']);



                $result['start'] =  $result['startDate'] ;
                $result['beginTime'] =  $result['startDate'] ;
                $result['endTime'] =  $result['startDate'] + 3600;


                $url = $this->cc_config['api_url'] . 'room/create?';

                $query_map['name'] = urlencode(t($_POST['cctitle']));
                $query_map['desc'] = urlencode(t($_POST['ccdescription']));
                $query_map['templatetype'] = urlencode(t($_POST['ccuiMode']));
                $query_map['authtype'] = urlencode(1);
                $query_map['publisherpass'] = urlencode(t($_POST['ccteacherToken']));
                $query_map['assistantpass'] = urlencode(t($_POST['ccassistantToken']));
                $query_map['playpass'] = urlencode(t($_POST['ccstudentClientToken']));
                $query_map['barrage'] = urlencode(t($_POST['ccclientJoin']));
                $query_map['foreignpublish'] = urlencode(t($_POST['ccwebJoin']));
                $query_map['userid'] = urlencode($this->cc_config['user_id']);
                $query_map['openlowdelaymode'] = urlencode(1);

                $url = $url . createHashedQueryString($query_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($query_map)[0];

                $res = getDataByUrl($url);

                if ($res['result'] == 'OK') {
                    if (empty($res['room']['id'])) {
                        $this->error('服务器创建失败');
                    }

                    $get_live_info_url = $this->cc_config['api_url'] . 'room/search?';
                    $get_live_uri_info_url = $this->cc_config['api_url'] . 'room/code?';

                    $info_map['userid'] = urlencode($this->cc_config['user_id']);
                    $info_map['roomid'] = $res['room']['id'];

                    //查询服务器
                    $live_info_url = $get_live_info_url . createHashedQueryString($info_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($info_map)[0];
                    $live_url_info_url = $get_live_uri_info_url . createHashedQueryString($info_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($info_map)[0];
                    $live_info_res = getDataByUrl($live_info_url);
                    $live_url_info_res = getDataByUrl($live_url_info_url);

                    if ($live_info_res['result'] != 'OK' || $live_url_info_res['result'] != 'OK') {
                        $this->error('服务器查询失败');
                    }

                    $live_info_res = $live_info_res['room'];
                    $data['uid'] = $this->mid;
                    $data['roomid'] = $live_info_res['id'];
                    $data['subject'] = $live_info_res['name'];
                    $data['speaker_id'] = intval($_POST['speaker_id']);
                    $data['startDate'] = $startDate;
                    $data['invalidDate'] = $invalidDate;
                    $data['uiMode'] = $live_info_res['templateType'];
                    $data['clientJoin'] = $live_info_res['barrage'];
                    $data['webJoin'] = $live_info_res['foreignPublish'];
                    $data['teacherToken'] = $live_info_res['publisherPass'];
                    $data['assistantToken'] = $live_info_res['assistantPass'];
                    $data['studentClientToken'] = $live_info_res['playPass'];
                    $data['description'] = $live_info_res['desc'];
                    $data['teacherJoinUrl'] = $live_url_info_res['clientLoginUrl'];
                    $data['assistantJoinUrl'] = explode('?', $live_url_info_res['assistantLoginUrl'])[0] . '/login?' . explode('?', $live_url_info_res['assistantLoginUrl'])[1];
                    $data['studentJoinUrl'] = $live_url_info_res['viewUrl'];
                    $data['is_del'] = 0;
                    $data['is_active'] = 0;
                    $data['live_id'] = $_POST['live_id'];

                    $live_cc =  M('zy_live_cc');
                    $course = M('arrange_course');

                    $live_cc ->startTrans();
                    $ret = $live_cc->add($data);

                    $result['room_id'] = $data['roomid'] ;
                    $result['live_id'] = $ret ;
                    $result['beginTime'] =  $result['startDate'] ;
                    $result['endTime'] =  $result['startDate'] + 3600;
                    $tres = $course->add($result);

                    if (!$ret) {
                        $live_cc ->rollback();
                        $this->error('创建失败!');
                    }
                    if (!$tres) {
                        $live_cc ->rollback();
                        $this->error('创建失败!');
                    }
                    $live_cc ->commit();
                    $this->assign('jumpUrl', U('school/AdminLive/ccLiveRoom', array('id' => $data['live_id'])));
                    $this->success('创建成功，请等待审核');
                } else {
                    $this->error('服务器出错啦');
                }


            }


            foreach ($res as $val) {
                $tdata['maxmannums'] = $tdata['maxmannums'] + $val['connums'];
            }

            $bluemap['starttime'] = $result['startDate'] -1 ;
            $bluemap['pay_status'] = 3;
            $bluemap['uid'] = $this->uid;
            $bluemap['is_del'] = 0;
            $blueres = M('zy_order_bluecon')->where($bluemap)->field('blueconnums')->select();
            $tdata['blueconnums'] = 0;
            $curemannums = t($_POST["ccmaxmannums"]);

            if ($blueres) {
                foreach ($blueres as $val) {
                    $tdata['blueconnums'] = $tdata['blueconnums'] + $val['blueconnums'];
                }
                $curemannums = $_POST["ccmaxmannums"] - $tdata['blueconnums'];
            }



            if (!$res && !$blueres) {
                $this->error("对不起，请先购买该时段并发数目或者绿色通道！");
            }


            $nums['maxmannums'] = 0;

            if ($res) {
                $ever['start'] = strtotime(t($_POST['cceventtime']));
                $ever['mhm_id'] = $shoolId;
                $ever['is_del'] = 0;
                $ever['is_activity'] = 1;

                $total = M('arrange_course')->where($ever)->Field('maxmannums')->select();
                if ($total) {
                    foreach ($total as $val) {
                        $nums['maxmannums'] = $nums['maxmannums'] + $val['maxmannums'];
                    }
                }

            }

            $tdata['maxmannums'] = $tdata['maxmannums'] + $tdata['blueconnums'];


            if ($tdata['maxmannums'] < intval(t($_POST['ccmaxmannums'] + $nums['maxmannums']))) {
                $this->error("对不起，你购买的并发数量或者绿色通道不足");
            }


            $course = M('arrange_course');
            $dafengchetb['start'] = strtotime(t($_POST['cceventtime']));
            $dafengchetb['is_del'] = 0;
            $dafengchetb['is_activity'] = 1;
            $donemanmus['maxmannums'] = 0;
            $resmannums = M('arrange_course')->where($dafengchetb)->field('maxmannums')->select();
            if ($resmannums) {
                foreach ($total as $val) {
                    $donemanmus['maxmannums'] = $donemanmus['maxmannums'] + $val['maxmannums'];
                }
            }
            $allconnums =  M('concurrent')->where('id = 1')->getField('Concurrent_nums');
            $resconnums = $allconnums - $donemanmus['maxmannums'];

            if ($_POST["ccmaxmannums"] > $resconnums) {

                if($curemannums > $resconnums) {

                    $this->error('没有足够的并发');
                }
            }

            $video_maxmannums = M('zy_video') -> where('id ='.t($_POST['live_id'])) ->getField('maxmannums');
            if((t($_POST['ccmaxmannums']) - $tdata['blueconnums']) > $video_maxmannums)
            {
                $this->error('不能大于该课堂规定并发量');
            }
            $result['blueconnums'] = $tdata['blueconnums'];

            if(t($_POST['ccmaxmannums']) >=  ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
            {


                $result['maxmannums'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
                $result['blueconnums'] =  t($_POST['ccmaxmannums']) -  $result['maxmannums'] ;
                $data['maxAttendees'] = $tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums'];
                $data["blueconnums"] = t($_POST['ccmaxmannums']) -  $result['maxmannums'] ;

                if( $resconnums >= $video_maxmannums) {
                    if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $video_maxmannums) {
                        $result['maxmannums'] = $video_maxmannums;
                        $result['blueconnums'] = t($_POST['ccmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $video_maxmannums;
                        $data["blueconnums"] = t($_POST['ccmaxmannums']) - $data['maxAttendees'];
                    }
                }
                else {
                    if (($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']) > $resconnums) {
                        $result['maxmannums'] = $resconnums;
                        $result['blueconnums'] = t($_POST['ccmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $resconnums;
                        $data["blueconnums"] = t($_POST['ccmaxmannums']) - $data['maxAttendees'];
                    }
                }

            }

            if(t($_POST['ccmaxmannums']) < ($tdata['maxmannums'] - $nums['maxmannums'] - $tdata['blueconnums']))
            {

                $result['maxmannums'] = t($_POST['ccmaxmannums']);
                $result['blueconnums'] = 0;
                $data['maxAttendees'] = t($_POST['ccmaxmannums']);
                $data["blueconnums"] = 0;

                if( $resconnums >= $video_maxmannums) {
                    if (t($_POST['ccmaxmannums']) > $video_maxmannums) {

                        $result['maxmannums'] = $video_maxmannums;
                        $result['blueconnums'] = t($_POST['maxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $video_maxmannums;
                        $data["blueconnums"] = t($_POST['ccmaxmannums']) - $data['maxAttendees'];
                    }
                }
                else {
                    if (t($_POST['ccmaxmannums']) > $resconnums) {
                        $result['maxmannums'] = $resconnums;
                        $result['blueconnums'] = t($_POST['ccmaxmannums']) - $result['maxmannums'];
                        $data['maxAttendees'] = $resconnums;
                        $data["blueconnums"] = t($_POST['ccmaxmannums']) - $data['maxAttendees'];
                    }
                }
            }


            $opp['is_del'] = 0;
            $opp['course_id'] = t($_POST['live_id']);
            $opp['start'] = strtotime(t($_POST['cceventtime']));
            $videotime = M('arrange_course') -> where($opp) ->select();

            if($videotime)
            {
                $this->error('该课程在此时间段已存在直播课时');
            }



            $url = $this->cc_config['api_url'] . 'room/create?';

            $query_map['name'] = urlencode(t($_POST['cctitle']));
            $query_map['desc'] = urlencode(t($_POST['ccdescription']));
            $query_map['templatetype'] = urlencode(t($_POST['ccuiMode']));
            $query_map['authtype'] = urlencode(1);
            $query_map['publisherpass'] = urlencode(t($_POST['ccteacherToken']));
            $query_map['assistantpass'] = urlencode(t($_POST['ccassistantToken']));
            $query_map['playpass'] = urlencode(t($_POST['ccstudentClientToken']));
            $query_map['barrage'] = urlencode(t($_POST['ccclientJoin']));
            $query_map['foreignpublish'] = urlencode(t($_POST['ccwebJoin']));
            $query_map['userid'] = urlencode($this->cc_config['user_id']);
            $query_map['openlowdelaymode'] = urlencode(1);

            $url = $url . createHashedQueryString($query_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($query_map)[0];

            $res = getDataByUrl($url);

            if ($res['result'] == 'OK') {
                if (empty($res['room']['id'])) {
                    $this->error('服务器创建失败');
                }

                $get_live_info_url = $this->cc_config['api_url'] . 'room/search?';
                $get_live_uri_info_url = $this->cc_config['api_url'] . 'room/code?';

                $info_map['userid'] = urlencode($this->cc_config['user_id']);
                $info_map['roomid'] = $res['room']['id'];

                //查询服务器
                $live_info_url = $get_live_info_url . createHashedQueryString($info_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($info_map)[0];
                $live_url_info_url = $get_live_uri_info_url . createHashedQueryString($info_map)[1] . '&time=' . time() . '&hash=' . createHashedQueryString($info_map)[0];
                $live_info_res = getDataByUrl($live_info_url);
                $live_url_info_res = getDataByUrl($live_url_info_url);

                if ($live_info_res['result'] != 'OK' || $live_url_info_res['result'] != 'OK') {
                    $this->error('服务器查询失败');
                }

                $live_info_res = $live_info_res['room'];
                $data['uid'] = $this->mid;
                $data['roomid'] = $live_info_res['id'];
                $data['subject'] = $live_info_res['name'];
                $data['speaker_id'] = intval($_POST['speaker_id']);
                $data['startDate'] = $startDate;
                $data['invalidDate'] = $invalidDate;
                $data['uiMode'] = $live_info_res['templateType'];
                $data['clientJoin'] = $live_info_res['barrage'];
                $data['webJoin'] = $live_info_res['foreignPublish'];
                $data['teacherToken'] = $live_info_res['publisherPass'];
                $data['assistantToken'] = $live_info_res['assistantPass'];
                $data['studentClientToken'] = $live_info_res['playPass'];
                $data['description'] = $live_info_res['desc'];
                $data['teacherJoinUrl'] = $live_url_info_res['clientLoginUrl'];
                $data['assistantJoinUrl'] = explode('?', $live_url_info_res['assistantLoginUrl'])[0] . '/login?' . explode('?', $live_url_info_res['assistantLoginUrl'])[1];
                $data['studentJoinUrl'] = $live_url_info_res['viewUrl'];
                $data['is_del'] = 0;
                $data['is_active'] = 0;
                $data['live_id'] = $_POST['live_id'];

                $live_cc =M('zy_live_cc');
                $course = M('arrange_course');
                $live_cc->startTrans();
                $ret = $live_cc ->add($data);
                $result['room_id'] = $data['roomid'] ;
                $result['live_id'] = $ret ;
                $result['beginTime'] =  $result['startDate'] ;
                $result['endTime'] =  $result['startDate'] + 3600;
                $tres = $course ->add($result);
                if (!$tres) {
                    $live_cc ->rollback();
                    $this->error('创建失败!');
                } if (!$ret) {
                    $live_cc  ->rollback();
                    $this->error('创建失败!');
                }
                $live_cc ->commit();
                $this->assign('jumpUrl', U('school/AdminLive/ccLiveRoom', array('id' => $data['live_id'])));
                $this->success('创建成功，请等待审核');
            } else {
                $this->error('服务器出错啦');
            }
        }
    }




    public function buydisccon()
    {

        $data['start']  = strtotime($_GET['eventtime']);
        $time = date('Y-m-d H:i',$data['start']);
        $nexttime = $data['start']  +3600;
        $nexttime = date('Y-m-d H:i',$nexttime);
        $mannums = $_GET['maxmannums'];

        $discmap['is_del'] = 0;
        $discmap['stime'] = $_GET['start'];

        $timemoney = M('con_discount') -> where($discmap) -> getField('discount_price');
        if(!$timemoney)
        {
            $map['id'] = 1;
            $timemoney = M('concurrent') -> where($map) -> getField('onehprice');
        }

        $this->assign('time',$time);
        $this->assign('buytime', $data['start']);
        $this->assign('nexttime',$nexttime);
        $this->assign('mannums',$mannums);
        $this->assign('mannums',$mannums);
        $this->assign('timemoney',$timemoney);
        $this ->display();
    }





    /***
     * 获取排课直播列表
     *
     */
    public function getlivelist()
    {

      $eventtime= strtotime($_GET['eventtime']);
        if($_POST['starttime']) {
        $map['start'] = t($_POST['starttime']);
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
            $mid = $this->mid;
            $shoolId = M('school')->where('uid =' . $mid)->getField('id');
            $map['mhm_id'] = $shoolId;
        $total =M('arrange_course') ->where($map) ->count();//总记录数
        $page      = intval($_POST['pageNum']); //当前页
        $pageSize  = 10; //每页显示数
        $totalPage = ceil($total/$pageSize); //总页数

        $startPage = $page*$pageSize; //开始记录
        //构造数组
        $list['total']     = $total;
        $list['pageSize']  = $pageSize;
        $list['totalPage'] = $totalPage;
        $list['data'] = M('arrange_course')->where($map)->limit("{$startPage} , {$pageSize}")->findAll();
        foreach($list['data'] as &$val) {
            $val['course_name']  = M('zy_video') ->where('id ='.$val['course_id']) ->getField('video_title');
            $val['mhm_name'] =M('school') ->where('id ='.$val['mhm_id']) ->getField('title');
            $val['teacher'] =M('zy_teacher') ->where('id ='.$val['speaker_id']) ->getField('name');
            $val['beginTime'] = date('m-d H:i',$val["beginTime"]);;
            $val['endTime'] = date('m-d H:i',$val["endTime"]);;
        }
            exit(json_encode($list));
        }

        $this ->assign('starttime',$eventtime);
        $this->display();


    }



    /***
     * 获取排课直播列表
     *
     */
    public function getdiscprice()
    {

        $eventtime= strtotime($_GET['eventtime']);
        if($_POST['starttime']) {
            $map['stime'] = t($_POST['starttime']);
            $map['is_del'] = 0;
            $onehprice = M('concurrent')->where('id = 1')->getField('onehprice');

            $list['data'] = M('con_discount')->where($map)->findAll();
            if(!$list['data'][0]['discount_price']) {
                $list['data'][0]['discount_price'] = "无";
                }
                else{
                    $list['data'][0]['discount_price'] =  $list['data'][0]['discount_price']."元/1个";
                }
            $list['data'][0]['onehprice'] = $onehprice."元/1个";
            exit(json_encode($list));
        }

        $this ->assign('starttime',$eventtime);
        $this->display();


    }

    /***
     * 购买绿色通道
     *
     */

   public function  buybluecon()
   {
       if(!t($_GET['eventtime']))
       {
           $this ->error("请选择所需绿色通道时间");
       }
       $nowtime = strtotime(t($_GET['eventtime']));
       $starttime = date('Y-m-d H:00', $nowtime);
       $endtime = $nowtime +3600;
       $endtime = date('Y-m-d H:00', $endtime);

       $discmap['is_del'] = 0;
       $discmap['stime'] = $_GET['start'];

       $blueconprice = M('concurrent') -> where('id = 1') -> getField('blueconprice');

       $this->assign('starttime',$starttime);
       $this->assign('nowtime',$nowtime);
       $this->assign('endtime',$endtime);
       $this->assign('blueconprice',$blueconprice);
       $this ->display();


   }






    /*
     * 剩余并发
     * @return void
     */
    public function leftConcurrent()
    {

        $map['uid'] = $this->mid;
        $now = time();
        $now = date('Y-m-d H:0', $now);
        $now = strtotime($now);
        $map['is_del'] = 0;
        $map['pay_status'] = 3;
        $map['stime'] = array('LT', $now);
        $map['etime'] = array('GT', $now);
        $res = M('zy_order_concurrent')->where($map)->Field('connums')->select();
        $tdata['maxmannums'] = 0;
        foreach ($res as $val) {
            $tdata['maxmannums'] = $tdata['maxmannums'] + $val['connums'];
        }
        $ever['start'] = $now;
        $shoolId = M('school')->where('uid =' . $map['uid'])->getField('id');
        $ever['mhm_id'] = $shoolId;
        $ever['is_del'] = 0;
        $total = M('arrange_course') -> where($ever)->Field('maxmannums')->select();
        $nums['maxmannums'] = 0;
        if($total) {
            foreach ($total as $val) {
                $nums['maxmannums'] = $nums['maxmannums'] + $val['maxmannums'];
            }
        }
        $nowleft =  $tdata['maxmannums']  - $nums['maxmannums'];


        $nexttime = $now+3600;
        $maps['stime'] = array('LT', $nexttime);
        $maps['etime'] = array('GT', $nexttime);
        $maps['is_del'] = 0;
        $maps['pay_status'] = 3;
        $maps['uid'] = $this->mid;
        $res = M('zy_order_concurrent')->where($maps)->Field('connums')->select();
        $nextdata['maxmannums'] = 0;
        foreach ($res as $val) {
            $nextdata['maxmannums'] = $nextdata['maxmannums'] + $val['connums'];
        }

        $nextever['start'] = $nexttime;
        $nextever['mhm_id'] = $shoolId;
        $nextever['is_del'] = 0;
        $total = M('arrange_course') -> where($nextever)->Field('maxmannums')->select();
        $nextnums['maxmannums'] = 0;
        if($total) {
            foreach ($total as $val) {
                $nextnums['maxmannums'] = $nextnums['maxmannums'] + $val['maxmannums'];
            }
        }
        $nextleft =  $nextdata['maxmannums']  - $nextnums['maxmannums'];


        $this ->assign('nowleft',$nowleft);
        $this ->assign('nextleft',$nextleft);
        $this ->assign('tdata',  $tdata['maxmannums']);
        $this ->assign('nextdata',$nextdata['maxmannums']);
        $this->display();

    }


    /**
     * 会员中心课程--添加排课数据的展示
     * @return void
     */
    public function addArrCoursedata()
    {

        $timehour = date("Y-m-d H:0:0", time());
        $start = strtotime($timehour) + 3600;
        $start = (t($_GET['start']) < time()) ? $start : t($_GET['start']);


        $end = t($_GET['end']);
        $nums = M('concurrent')->where('id = 1')->getField('Concurrent_nums');

        if (!empty ($start && $end)) {
            $map ['start'] = array('BETWEEN', array($start, $end));
        }
        $serth = array('start', 'maxmannums');
        $map['is_del'] = 0;
        $map['is_activity'] = 1;
        $res = M('arrange_course')->where($map)->field($serth)->select();
        if (!$res) {
            $res = array();
        }
        $times = intval(($end - $start) / 3600);

        if ($times > 0) {
            for ($i = 0; $i < $times; $i++) {
                $timedada[$i]['start'] = $start + $i * 3600;
                $timedada[$i]['maxmannums'] = 0;
            }
            $res = array_merge($timedada, $res);
            $j = 0;
            foreach ($res as $k => $val) {
                $tdata[$j]['title'] = $res[$j]['maxmannums'];
                $tdata[$j]['start'] = $res[$j]['start'];
                for ($i = $j + 1; $i < count($res); $i++) {
                    if ($val['start'] == $res[$i]['start']) {
                        $tdata[$j]['title'] = $tdata[$j]['title'] + $res[$i]['maxmannums'];
                        $res[$i]['maxmannums'] = -1000;
                    }
                }
                $j++;
            }
            $data = array();
            $i = 0;
            foreach ($tdata as $k => $val) {
                if ($val['title'] < 0) {
                    unset($tdata[$k]);
                }
                if ($val['title'] >= 0  && $i<168) {
                    $data[$i]['title'] = strval($nums - $val['title']) . "/" . $nums;
                    $data[$i]['start'] = strval($val['start']);
                    $data[$i]['end'] = strval($val['start'] + 3600);
                    $data[$i]['allDay'] = false;
                    $i++;
                }
            }
            echo json_encode($data);
        }
    }



    public function buyConcurrent(){

        $res =M('Concurrent')->where("id = 1")->select();
        foreach ($res as $val) {
            $onemprice = $val['onemprice'];
            $threemprcie = $val['threemprcie'];
            $sixmprice = $val['sixmprice'];
            $oneyprice = $val['oneyprice'];
        }
        $this->assign('onemprice',$onemprice);
        $this->assign('threeprcie',$threemprcie);
        $this->assign('sixmprice',$sixmprice);
        $this->assign('oneyprice',$oneyprice);

        $this->display();
    }

    /**
     * 删除机构挂载课程
     * @return void
     */
    public function delMountVideo(){
        $id   = $_POST["id"];
        $res  = M('zy_video_mount')->where('vid='.$id )->delete();
        if($res){
            exit(json_encode(array('status'=>'1','info'=>'已删除')));
        }else{
            exit(json_encode(array('status'=>'0','info'=> '操作繁忙,请稍后再试')));
        }
    }

    /**
     * CC加密hash
     * 功能：将一个Map按照Key字母升序构成一个QueryString. 并且加入时间混淆的hash串
     * @param queryMap  query内容
     * @param time  加密时候，为当前时间；解密时，为从querystring得到的时间；
     * @param salt   加密salt
     * @return
     */
    public function  createHashedQueryString($queryMap) {

        ksort($queryMap);

        $param = '';
        foreach($queryMap as $key => $value){
            $param .= $key.'='.$value.'&';
        }
        $param_code = trim($param,'&');

        $param = $param_code.'&time='.time().'&salt='.$this->cc_config['api_key'];

        $param_arr[0] = md5($param);
        $param_arr[1] = $param_code;

        return $param_arr;
    }

    /***
     * 获取广告位列表
     */
    public function advertising (){
        $uid = $this->mid;
        $banner = model('School')->where('uid='.$uid)->getField('banner');
        $data['content'] = unserialize($banner);
        foreach($data['content'] as &$value) {
            $attachInfo = model('Attach')->getAttachById($value['banner']);
            $value['bannerpic'] = getImageUrl($attachInfo['save_path'].$attachInfo['save_name']);
        }

        $this->assign('data', $data);
        $this->assign('editPage', true);
        $this->display();
    }

    public function doEditAdSpace()
    {
        // 数据组装
        $picData = array();
        for($i = 0; $i < count($_POST['banner']); $i++) {
            $picData[] = array('banner'=>$_POST['banner'][$i], 'bannerurl'=>$_POST['bannerurl'][$i] );
        }
        $data['banner'] = serialize($picData);

        $map['uid'] = intval($this->mid);
        $res = model('School')->where($map)->save($data);

        if($res)$this->success("修改成功");
        $this->error("修改失败!");
    }

    //并发量订单
    public function concurrentOrder()
    {
        $this->display();
    }
    //异步获取数据
    public function getOrderlist()
    {
        $orderby = t($_GET['orderby']);
        if ($orderby) {
            if ($orderby != 0) {
                $map['pay_status'] = $orderby;
            }
        }
        $map['uid'] = intval($this->mid);
        $map['is_del'] = intval(0);

        $table = "zy_order_concurrent";
        $order = 'ctime DESC';
//        $size = intval ( getAppConfig ( 'video_list_num', 'page', 6 ) );
        $playtype = "";
        $data = M($table)->where($map)->order($order)->findPage(10);

        $this->assign('pagecount', $data['totalPages']);
        if ($data['data']) {
            foreach ($data['data'] as $key => &$val) {
                $val['cover'] = M('album')->where('album_id =' . $val['album_id'])->getField('cover');
                $playtype = '5';
                $val['video_name'] = "并发量购买";

                //价格和折扣
                $val['uname'] = getUserSpace($val['uid'], null, '_blank');
                $val['price'] = $val['price'];
                $val['connums'] = $val['connums'];
                $val['ctime'] = $val['ctime'];

                $val['strtime'] = friendlyDate($val['ctime']);
            }
            if ($data['html'] == null) {
                $data['html'] = ' ';
            }
            $this->assign("data", $data['data']);
            $this->assign("playtype", $playtype);
            $html = $this->fetch('order_list');
        } else {
            $html = '暂无此类订单';
            if ($data['html'] == null) {
                $data['html'] = '';
            }
        }

        $data ['data'] = $html;
        echo json_encode($data);exit();
    }
}