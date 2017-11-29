<?php
/**
 * 用户
 * @author ashangmanage <arsom@qq.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class UserShowAction extends CommonAction{

    protected $userid;

    public function _initialize() {
        $this->userid=intval($_GET['uid']);
        $username=getUserName($this->userid);
        if(empty($username)){//用户不存在自动跳转首页
            $this->redirect(U("classroom/Index/index"));
        }

        //判断当前访问页面是不是自己
        if($this->userid == $this->mid){
            $this->assign("is_me",true);
        }

        //获取用户关注状态fid
        $fstatus = M('user_follow')->where(array('uid'=>$this->mid,'fid'=>$this->userid))->find();
        if($fstatus){
            $this->assign("isfollow",true);
        }

        if($this->mid!=0 && $this->mid!=$this->userid){
            //查询最近访问是否有自己的uid
            $map=array(
                'uid'=>$this->mid,
                'fuid'=>$this->userid,
            );
            $res=M("ZyUserVisitor")->where($map)->find();
            $data['ctime']=time();
            if($res){
                M("ZyUserVisitor")->where($map)->save($data);
            }else{
                $map['ctime']=$data['ctime'];
                M("ZyUserVisitor")->add($map);
            }
        }

        //判断用户是不是讲师
        $res=D('ZyTeacher')->where(array('uid'=>$this->userid))->find();
        if($res){
          $this->assign("isteacher",true);
          $this->assign("teacherid",$res['id']);
        }
        $vrlist=M("ZyUserVisitor")->where(array('fuid'=>$this->userid))->order("ctime DESC")->limit(6)->select();
        foreach ($vrlist as &$vr){
            $vr['tmp'] = model('Follow')->getFollowCount(array($vr['uid']));
        	$vr['userinfo'] = model('User')->getUserInfo($vr['uid']);
        }
        $user = model('User')->where(array('uid'=>$this->userid))->field('uid,uname,phone,sex,location,profession,background_id,intro')->find();
        $this->twcont   = D("ZyQuestion")->where(array('uid'=>$this->userid))->count();//加载提问数量
        $this->videocont= D("zy_order_course")->where(array('uid'=>$this->userid,'is_del'=>0,'pay_status'=>3))->count() ? D("zy_order_course")->where(array('uid'=>$this->userid,'is_del'=>0,'pay_status'=>3))->count() : 0;//加载我的课程总数
        $this->commcont = M("ZyWendaComment")->where(array('uid'=>$this->userid,'is_del'=>0))->count();//加载我的评论
        $this->wdcont   = M('ZyWenda')->where(array('uid'=>$this->userid,'is_del'=>0))->count();//加载我的问答数量
        $this->note     = M('ZyNote')->where(array('uid'=>$this->userid,'is_open'=>1,'is_del'=>0,'parent_id'=>0))->count();//笔记数量
        $this ->gcount  = M('group')-> where(array('uid '=> $this ->userid,'is_del'=>0)) ->count();
        $user['credit_user'] = M('credit_user')->where(array('uid'=>$this->userid))->getField('score');//积分数
        if(!$user['credit_user']){
            $user['credit_user'] = 0;
        }
        
        $this->assign('vrlist',$vrlist);
        $this->assign("userid", $this->userid);
        $this->assign("user", $user);
    }

    /**
     * 用户资料显示Index页面
     */
    public function index(){
        $uid        = intval($this->userid);
        $limit      = 3;
        //拼接两个表名
        $vtablename = C('DB_PREFIX').'zy_video';
        $otablename = C('DB_PREFIX').'zy_order_course';

        //拼接字段
        $fields     = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`teacher_id`,{$vtablename}.`mhm_id`,{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_binfo`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.`video_order_count`,{$vtablename}.`ctime`,{$vtablename}.`t_price`";
        //不是通过班级购买的
        //$where     = "{$otablename}.`is_del`=0 and {$otablename}.`order_album_id`=0 and {$otablename}.`uid`={$uid}";
        $where     = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
        $data = M('zy_order_course')->join("{$vtablename} on {$otablename}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        foreach($data['data'] as &$val){
            $val['teacher_neme'] = M('zy_teacher')->where(['id'=>$val['teacher_id']])->getField('name');
            $val['school_title'] = M('school')->where(['id'=>$val['mhm_id']])->getField('title');
        }

        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $teacher_id = D('ZyTeacher')->getTeacherStrByMap(array('uid'=>$uid),'id');
        $photos = D('ZyTeacherPhotos')->getPhotosAlbumByTid($teacher_id);
        $this->assign('vms', getSubByKey($vms, 'video_id'));
        $this->assign('data',$data);
        $this->assign('user_show_type','show');
        $this->assign('tid',$teacher_id);
        $this->assign('photos',$photos['data']);
        $this->display();
    }


    /**
     * 加载用户问答
     */
    public function wenda(){
     $map=array(
         'uid'=>$this->userid,
         'is_del'=>0
     );
      $wdlist=D("ZyWenda")->where($map)->order("ctime DESC")->findPage(10);
      $this->assign("wdlist",$wdlist);
      $this->display();
    }

    /**
     * 加载评论
     */
    public function wdcomm(){
        $map=array(
            'uid'=>$this->userid,
            'is_del'=>0,
            'parent_id'=>0,
        );
        $idstr="";
        $wendaIds=D("ZyWendaComment")->where($map)->field("wid")->order("ctime DESC")->select();
        $wendaIds= unique_arr($wendaIds,true);//去掉重复数据
        foreach($wendaIds as &$val){//拼装id
            $idstr .=$val['wid'].",";

        }
        $where['id']=array('in',trim($idstr,","));
        $where['is_del']=0;
        $where['uid']= array("neq",$this->userid);
        $wendaList=D("ZyWenda")->where($where)->order("ctime DESC")->findPage(10);
        foreach($wendaList['data'] as &$val){
            $val['commt']=D('ZyWendaComment')->where(array('uid'=>$this->userid,'wid'=>$val['id']))->order("ctime DESC")->find();
        }
       /* dump($wendaList);*/

        $this->assign("wdlist",$wendaList);
        $this->display();


    }

    /**
     * 加载提问
     */
    public function question(){
        $map=array(
            'uid'=>$this->userid,
            'parent_id'=>0,
        );
        $limit = 10;
        $question=D("ZyQuestion")->where ( $map )->order ( "ctime desc" )->findPage ( $limit );

        $this->assign("wtlist",$question);
        $this->display();


    }
    /**
     * 加载笔记
     */
    public function note(){
        $map['uid']       = intval($this->userid);
        $map['parent_id'] = 0;
        $map['is_open'] = 1;
        $map['is_del'] = 0;
        $order = 'ctime DESC';
        $limit = 10;
        $zyNoteMod = D('ZyNote');
        $data = $zyNoteMod->where($map)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value['note_title'] = msubstr($value['note_title'], 0, 15);
            $value['note_description'] = msubstr($value['note_description'], 0, 150);
            $value['strtime'] = friendlyDate($value['ctime']);
            $value['qcount'] = $zyNoteMod->where(array('parent_id' => array('eq', $value['id'])))->count();
        }

        $this->assign("data",$data);
        $this->display();
    }

    //关注
    public function follow(){
        $uid=intval($_GET['uid']);
        $follow = model('Follow');
        $user = model('User');
        $this->mid=$uid;

        $count = $follow->getFollowCount(array($this->mid));
        $count = $count[$this->mid];
        $type  = t($_GET['type']);
//        if($type != 'follower'){
            $data = $follow->getFollowingList($this->mid, null, 9);
//        }else{
//            $data = $follow->getFollowerList($this->mid, 5);
//        }
        foreach($data['data'] as &$rs){
            $rs['user'] = $user->getUserInfo($rs['fid']);
        }
        $fids = getSubByKey($data['data'], 'fid');
        $followState = $follow->getFollowStateByFids($this->mid, $fids);
        //print_r($followState);
        $this->assign('followState', $followState);
        $this->assign('data', $data);
//        $this->assign('type', $type);
        $this->assign('count', $count);
        $this->display();
    }

    //粉丝
    public function fans(){
        $uid=intval($_GET['uid']);
        $follow = model('Follow');
        $user = model('User');
        $this->mid=$uid;

        $count = $follow->getFollowCount(array($this->mid));
        $count = $count[$this->mid];
        $type  = t($_GET['type']);
        $type = 'follower';
        if($type != 'follower'){
            $data = $follow->getFollowingList($this->mid, null, 9);
        }else{
            $data = $follow->getFollowerList($this->mid, 9);
        }
        foreach($data['data'] as &$rs){
            $rs['user'] = $user->getUserInfo($rs['fid']);
        }
        $fids = getSubByKey($data['data'], 'fid');
        $followState = $follow->getFollowStateByFids($this->mid, $fids);
        //print_r($followState);
        $this->assign('followState', $followState);
        $this->assign('data', $data);
        $this->assign('type', $type);
        $this->assign('count', $count);
        $this->display();
    }

    public function course(){
        $uid        = intval($this->userid);
        $limit      = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX').'zy_video';
        $otablename = C('DB_PREFIX').'zy_order_course';

        //拼接字段
        $fields     = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,";
        $fields .= "{$vtablename}.`teacher_id`,{$vtablename}.`mhm_id`,{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_binfo`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.`video_order_count`,{$vtablename}.`ctime`,{$vtablename}.`t_price`";
        //不是通过班级购买的
        //$where     = "{$otablename}.`is_del`=0 and {$otablename}.`order_album_id`=0 and {$otablename}.`uid`={$uid}";
        $where     = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
        $data = M('zy_order_course')->join("{$vtablename} on {$otablename}.`video_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);
        foreach($data['data'] as &$val){
            $val['teacher_neme'] = M('zy_teacher')->where(['id'=>$val['teacher_id']])->getField('name');
            $val['school_title'] = M('school')->where(['id'=>$val['mhm_id']])->getField('title');
        }

        $this->assign('data',$data);
        $this->display();
    }

    public function live(){
        $uid   = intval($this->userid);
        $limit = 9;
        //拼接两个表名
        $vtablename = C('DB_PREFIX') . 'zy_video';
        $otablename = C('DB_PREFIX') . 'zy_order_live';
        //拼接字段
        $fields = '';
        $fields .= "{$otablename}.`learn_status`,{$otablename}.`uid`,{$otablename}.`id` as `oid`,{$otablename}.`live_id`,";
        $fields .= "{$vtablename}.`video_title`,{$vtablename}.`video_category`,{$vtablename}.`id`,{$vtablename}.`video_intro`,";
        $fields .= "{$vtablename}.`cover`,{$vtablename}.video_order_count,{$vtablename}.`t_price`,{$vtablename}.`mhm_id`";
        //不是通过班级购买的
        $where = "{$otablename}.`is_del`=0 and {$otablename}.`pay_status`=3 and {$otablename}.`uid`={$uid}";
        $data = M('zy_order_live')->join("{$vtablename} on {$otablename}.`live_id`={$vtablename}.`id`")->where($where)->field($fields)->findPage($limit);

        //循环计算课程价格
        foreach ($data['data'] as $key => &$val) {
            $data['data'][$key]['money'] = $val['t_price'];
            $data['data'][$key]['order_count'] = M('zy_order_live')->where(['live_id'=>$val['live_id'], 'is_del' => 0, 'pay_status' => 3])->count();

            $school = model('School')->where('id='.$val['mhm_id'])->field('doadmin,title')->find();
            $data['data'][$key]['mhm_title'] = $school['title'];
            if(!$school['doadmin']){
                $data['data'][$key]['domain'] = U('school/School/index', array('id' => $val['mhm_id']));
            }else{
                $data['data'][$key]['domain'] = getDomain($school['doadmin']);
            }
        }
        $vms = D('ZyVideoMerge')->getList(intval($this->mid), session_id());
        $this->assign('vms', getSubByKey($vms, 'live_id'));
        //把数据传入模板
        $this->assign('listData', $data);
        $this->assign('data', $data['data']);
        $this->assign('mid', $this->mid);
        $this->display();
    }

    public function group(){
        $map['uid'] = intval($this->userid);
        $map['is_del'] = 0;

        $data = M('group') ->where($map)->order('ctime desc')-> findpage(9);
        foreach($data['data'] as  $key => $val)
        {
            $data['data'][$key]['logo'] = $this->logo_path_to_url($val['logo']);
            $data['data'][$key]['cid0'] =  M('group_category') -> where('id ='.$val['cid0']) ->getField('title');
        }
        //把数据传入模板
        $this->assign('listData', $data);
        $this->assign('group_data', $data['data']);
        $this->assign('mid', $this->mid);
        $this->display();
    }



    /**
     * 根据群组Logo的保存路径获取Logo的URL
     *
     * @param string $save_path 群组Logo的保存路径
     * @return string 群组Logo的URL. 给定路径不存在时, 返回默认的群组Logo的URL地址.
     */
    function logo_path_to_url($save_path,$width=186,$height=186)
    {
        $path = getImageUrl($save_path,$width,$height, true);
        if ( $save_path != 'default.png' )
            return $path;
        else
            return SITE_URL . '/apps/group/_static/images/default.png';
    }

}


?>