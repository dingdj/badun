<?php
/**
 * 云课堂播(班级)后台配置
 * 1.班级管理 - 目前支持1级分类
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAlbumAction extends AdministratorAction
{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    //班级列表
    public function index(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageKeyList      = array('id','album_title','price','cover','user_title','ctime','status','DOACTION');
        $this->pageButton[]  =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  =  array('title'=>'删除','onclick'=>"admin.delAlbumAll('delAlbums','0')");
        $this->searchKey      = array('id','album_title');
        $this->searchPostUrl = U('classroom/AdminAlbum/index');
        $listData = $this->_getData(10 , 0);
        $this->displayList($listData);
    }
    
    
    /**
     * 班级-课程列表
     */
    public function video(){
        $this->_initClassroomListAdminMenu();
        $id = t($_GET['id']);
        $limit = 20;
        $this->pageTitle['video'] = '班级课程';
        $this->pageTab[]   = array('title'=>'班级课程','tabHash'=>'video','url'=>U('classroom/AdminAlbum/video',array('id'=>$id,'tabHash'=>'video')));
        $this->pageKeyList = array('id','video_title','cover','v_price','user_title','teacher_id','teacher_name','ctime','DOACTION');
        $this->pageButton[] =  array('title'=>'搜索课程','onclick'=>"admin.fold('search_form')");
        $this->searchKey = array('id','video_title');
        $this->searchPostUrl = U('classroom/AdminAlbum/video',array('id'=>$id));
        
        if(empty($id)){
            $this->error('参数错误');
        }
        $link = M('album_video_link')->where(array('album_id'=>$id))->select();
        $vIds = array_column($link,'video_id');
        if(!empty($vIds)){
            $map['id'] = array('in',$vIds);
            $map['is_activity'] = 1;
            if(isset($_POST)){
                $_POST['id'] && $map['id'] = intval($_POST['id']);
                $_POST['video_title'] && $map['video_title'] = array('like', '%'.t($_POST['video_title']).'%');
            }
            
            $list = M('zy_video')->where($map)->order('ctime desc,id desc')->findPage($limit);
            foreach ($list['data'] as &$value){
                $value['video_title'] = msubstr($value['video_title'],0,20);
                if($value['type'] == 1){
                    $url = U('classroom/Video/view', array('id' => $value['id']));
                    $value['video_title'] = getQuickLink($url,$value['video_title'],"未知课程");
                }else{
                    $url = U('live/Index/view', array('id' => $value['id']));
                    $value['video_title'] = getQuickLink($url,$value['video_title'],"未知直播");
                    //讲师信息
                    $value['teacher_id'] = $this->teacher($value['live_type'],$value['id']);
                }

                $value['user_title']  = getUserSpace($value['uid'], null, '_blank');
                $teacher_name = M('zy_teacher')->where(array('id'=>$value['teacher_id']))->getField('name');
//                $value['teacher_name']  = $teacher_name;
                $url = U('classroom/Teacher/view', array('id' => $value['teacher_id']));
                $value['teacher_name'] = getQuickLink($url,$teacher_name,"未知讲师");

                $value['ctime'] = date("Y-m-d H:i:s", $value['ctime']);
                $value['cover'] = "<img src=".getCover($value['cover'] , 60 ,60)." width='60px' height='60px'>";

                if($value['type'] == 1){
                    $value['DOACTION'] = '<a target="_blank" href=" '.U('classroom/Video/watch',array('id'=>$value['id'],'s_id'=>$s_id)).' ">查看</a> ';
                }else{
                    $value['DOACTION'] = '<a target="_blank" href=" '.U('live/Index/view',array('id'=>$value['id'])).' ">查看</a> ';
                }
            }
        }else{
            $list = array();
        }
        
        
        
        $this->displayList($list);
        
    }

    //直播主讲教师id
    protected function teacher($live_type,$id)
    {
        if($live_type == 1){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_zshd')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_zshd')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }elseif ($live_type == 3){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_gh')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_gh')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }
        elseif ($live_type == 4){
            $map = array();
            $map['live_id']=$id;
            $map['is_del']=0;
            $map['startDate']=array('gt',time());
            $live_data = M('zy_live_cc')->where($map)->order('startDate asc')->find();
            if(!$live_data){
                $maps = array();
                $maps['live_id']=$id;
                $maps['is_del']=0;
                $maps['invalidDate']=array('gt',time());
                $live_data = M('zy_live_cc')->where($maps)->order('invalidDate asc')->find();
            }
            $speaker_id = $live_data['speaker_id'];
        }
        return $speaker_id;
    }
    
    //编辑、添加班级
    public function addAlbum(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageKeyList = array('album_category','album_title','album_intro','mhm_id','vip_level','is_mount','price','album_html','cover');
        
        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $vip_levels = $vip_levels ? array('0'=>'无')+$vip_levels : array('0'=>'无');
        $this->opt['vip_level'] = $vip_levels;
    
        $this->opt['is_best'] = array( '1'=>'设置为精选' );
        $this->opt['is_mount'] = array( '0'=>'否','1'=>'是');
        $this->notEmpty = array('album_title','album_intro','album_category','cover');
        $this->savePostUrl = U('classroom/AdminAlbum/doAddAlbum');
        echo W('CategoryLevel',array('table'=>'zy_package_category','id'=>'package_level','default'=>$fullcategorypaths ));
        $output = ob_get_contents();
        $html=$this->fetch("album");
        ob_end_clean();

        $all_school = model('School')->getAllSchol([],'id,title');
        $all_school = $all_school ? array('0'=>'请选择')+$all_school : array('0'=>'请选择');
        $this->opt['mhm_id'] = $all_school;

        $this->displayConfig(array('album_category'=>$output,'album_html'=>$html));
    }

    //添加班级操作
    public function doAddAlbum(){
        $post = $_POST;
        $data['album_title']         = t($post['album_title']); //班级名称
        $data['album_intro']         = t($post['album_intro']); //班级简介
        $data['price']                 = $post['price']; //班级价格
        $myAdminLevelhidden         = getCsvInt(t($post['package_levelhidden']),0,true,true,',');  //处理分类全路径
        $data['album_category']     =  $myAdminLevelhidden; //分类全路径
        $data['cover']                 = intval($post['cover']); //封面id
        $data['mhm_id']                 = intval($post['mhm_id']); //封面id
        $data['is_mount']                 = intval($post['is_mount']); //是否挂载平台
        $data['vip_level']                 = intval($post['vip_level']); //会员等级
        $data['is_best']             = isset($post['is_best']) ? intval($post['is_best']) : 0; //编辑精选
        $album_tag                     = explode(',' , $post['album_tag']);
        //$video_ids                     = explode(',' , trim($post['video_ids'] ,',' ) );
        $video_ids                     = $post['video'];

        if(!$data['album_title']) $this->error('班级标题不能为空');
        if(!$data['album_intro']) $this->error('班级简介不能为空');
        if(!$data['album_category']) $this->error('请选择班级分类');
        if(!$data['mhm_id']) $this->error('机构不能为空！');
        if(!$data['cover']) $this->error('还没有上传封面');
        
        if($post['id']){
            $result = M('album')->where('id = '.$post['id'])->data($data)->save();
        } else {
            $data['ctime']              = time();
            $data['uid']                = $this->mid;
            $result = M('album')->data($data)->add();
        }
        if($result !== false){
            unset($data);
            //处理标签和课程
            if($post['id']){
                //删除旧课程
                M('album_video_link')->where('album_id='.$post['id'])->delete();
                //添加班级课程关联
                $sql = 'insert into '.C('DB_PREFIX').'album_video_link (`album_id`,`video_id`) values';
                foreach($video_ids as $val){
                    $sql .= '('.$post['id'] .','.$val .'),';
                }
                M()->query( trim($sql , ','));
            } else {
                //添加班级课程关联
                $sql = 'insert into '.C('DB_PREFIX').'album_video_link (`album_id`,`video_id`) values';
                foreach($video_ids as $val){
                    $sql .= '('.$result .','.$val .'),';
                }
                M()->query( trim($sql , ','));
            }
            if($post['id']){
                $this->success('编辑成功');
            } else {
                $this->success('添加成功');
            }
        } else {
            $this->error("系统错误，请稍后再试");
        }
    }

    /**
     * 编辑班级
     */
    public function editAlbum(){
        $this->_initClassroomListAdminMenu();
        $this->pageTitle['editAlbum'] = '编辑';
        $this->pageTab[]   = array('title'=>'编辑','tabHash'=>'editAlbum','url'=>U('classroom/AdminAlbum/editAlbum'));
        $this->pageKeyList = array('id','myAdminLevelhidden','album_title','album_intro','mhm_id','vip_level','is_mount','price','album_html','cover');
        
        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $vip_levels = $vip_levels ? array('0'=>'无')+$vip_levels : array('0'=>'无');
        $this->opt['vip_level'] = $vip_levels;

        $this->opt['is_best'] = array( '1'=>'设置为精选' );
        $this->opt['is_mount'] = array( '0'=>'否','1'=>'是');
        $this->notEmpty       = array('album_title','album_intro','album_category','cover');
        $this->savePostUrl    = U('classroom/AdminAlbum/doAddAlbum');
        
        $data = D("Album","classroom")->getAlbumById($_GET['id']);
        $data['fullcategorypath'] = trim($data['album_category'] , ',');
        ob_start();
//         echo W('VideoLevel',array('type'=>2,'default'=>$data['fullcategorypath']));
        echo W('CategoryLevel',array('table'=>'zy_package_category','id'=>'package_level','default'=>$data['fullcategorypath'] ));
        $output = ob_get_contents();
        ob_end_clean();
        $data['myAdminLevelhidden'] = $output;

        $all_school = model('School')->getAllSchol([],'id,title');
        $all_school = $all_school ? array('0'=>'请选择')+$all_school : array('0'=>'请选择');
        $this->opt['mhm_id'] = $all_school;

        //查询班级包含的课程
        $video_data = M('album_video_link')->where('album_id='.$_GET['id'])->field('video_id')->findAll();
        $video_data = getSubByKey($video_data , 'video_id');
        $video_ids  = implode(',', $video_data);

        $this->assign("data" , $video_data);
        $this->assign("album_video",$video_ids.',');
        $this->assign("videos",$video_ids);
        $html = $this->fetch("album");
        $data['album_html'] = $html;
        $this->displayConfig($data);
    }

    //批量删除班级(隐藏)
    public function delAlbums(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );
        if($_POST['status'] == 3){
            $data['is_del'] = 1;
        }else{
            $data['status'] = $_POST['status'];
        }
        $res = M('album')->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    //删除班级(隐藏)
    public function delAlbum(){
        if(!$_POST['id']){
            exit(json_encode(array('status'=>0,'info'=>'请选择要删除的对象!')));
        } 
        $map['id'] = intval($_POST['id']);
        $data['status'] = $_POST['is_del'] ? 0 : 1; //传入参数并设置相反的状态
        if(M('album')->where($map)->data($data)->save()){
            exit(json_encode(array('status'=>1,'info'=>'操作成功')));
        } else {
            exit(json_encode(array('status'=>1,'info'=>'操作失败')));
        }
    }

    //班级回收站(被隐藏的班级)
    public function recycle(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageKeyList = array('id','album_title','user_title','ctime','DOACTION');
        $this->pageButton[]  =  array('title'=>'彻底删除','onclick'=>"admin.delAlbumAll('delAlbums',3)");
        $listData = $this->_getData(20,1);
        $this->displayList($listData);
    }
    

    //获取班级数据
    private function _getData($limit = 20, $is_del){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['album_title'] && $map['album_title'] = array('like', '%'.t($_POST['album_title']).'%');
        }
        $map['status'] = $is_del ? array('eq',0) : array('neq',0); //搜索非隐藏内容
        $map['is_del'] = 0;
        $list = M('album')->where($map)->order('ctime desc,id desc')->findPage($limit);
        foreach ($list['data'] as &$value){
            $value['album_title'] = msubstr($value['album_title'],0,20);
            $url = U('classroom/Album/view', array('id' => $value['id']));
            $value['album_title'] = getQuickLink($url,$value['album_title'],"未知班级");
            $value['user_title'] = getUserSpace($value['uid'], null, '_blank');
            $value['price'] = $value['price'].'元';
            $value['ctime'] = date("Y-m-d H:i:s", $value['ctime']);
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 , 60)." width='60px' height='60px'>";
            $value['DOACTION'] = '<a href="'.U('classroom/AdminAlbum/reviewAlbum',array('tabHash'=>'reviewAlbum','id'=>$value['id'])).'">评价</a> | ';
            $value['DOACTION'] .= '<a href="'.U('classroom/AdminAlbum/video',array('tabHash'=>'video','id'=>$value['id'])).'">查看课程</a> | ';
            $value['DOACTION'] .= ($value['status'] == 0) ? '<a href="'.U('classroom/AdminAlbum/editAlbum',array('id'=>$value['id'],'tabHash'=>'editAlbum')).'">编辑</a> | 
                    <a onclick="admin.delObject('.$value['id'].',\'Album\','.$value['status'].');" href="javascript:void(0)">恢复</a>  | ' : '<a href="'.U('classroom/AdminAlbum/editAlbum',array('id'=>$value['id'],'tabHash'=>'editAlbum')).'">编辑</a> |
                            <a onclick="admin.delAlbums('.$value['id'].',\'Album\','.$value['status'].');" href="javascript:void(0)">删除</a> | ';
            if($value['is_mount'] == 1) {
                $value['mount_status'] = '<span onclick="admin.closeAlbumMount('.$value['id'].','.$value['is_mount'].');" style="color:green">已挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.closeAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">取消挂载</a>';
            } if($value['is_mount'] == 0) {
                $value['mount_status'] = '<span onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" style="color:color: rgba(169, 169, 169, 0.6);">未挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">允许挂载</a>';
            }else if($value['is_mount'] == 2) {
                $value['mount_status'] = '<span onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" style="red">已提交挂载待审核</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openAlbumMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">挂载审核</a> ';
            }
        }
        return $list;
    }

    /**
     * 班级对应的提问
     */
    public function askAlbum(){
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'班级提问列表','tabHash'=>'askAlbum','url'=>U('classroom/AdmimAlbum/askAlbum'));
        $this->pageTitle['askAlbum'] = '班级问题列表';
        if(!$_GET['id']) $this->error('请选择要查看的班级');
        $field = 'id,uid,oid,qst_title,qst_comment_count';
        $this->pageKeyList = array('id','qst_title','uid','oid','qst_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 2;
        $data = D('ZyQuestion','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('classroom/AdminAlbum/answerAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'answerAlbum')).'">查看回答</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'ask\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }
    
    /**
     * 提问对应的回答
     */
    public function answerAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的问题');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'回答列表','tabHash'=>'answerAlbum','url'=>U('classroom/AdminAlbum/answerAlbum'));
        $this->pageTitle['answerAlbum'] = '回答列表';
        $field = 'id,uid,oid,qst_description';
        $this->pageKeyList = array('id','uid','oid','qst_description','DOACTION');
        $this->pageButton[] = array('title' => '删除提问', 'onclick' => "admin.mzQuestionEdit('delquestion')");
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
        $map['type'] = 2;
        $data = D('ZyQuestion','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'ask\')">删除</a>';
        }
        $this->displayList($data);
    }

    //获取机构课程列表
    public function getVideoList(){
        $mhm_id = intval(t($_POST['mhm_id']));
        $time  = time();
        $where = "is_del=0 AND `mhm_id`=$mhm_id AND is_activity=1 AND uctime>$time AND listingtime<$time ";
        $list = D('ZyVideo')->where($where)->select();
        echo json_encode($list);exit;
    }
    //检索班级列表
    public function seachVideo(){
        $key   = t($_POST['key']);
        $mhm_id = intval(t($_POST['mhm_id']));
        $time  = time();
        $where = "is_del=0 AND `mhm_id`=$mhm_id AND is_activity=1 AND uctime>$time AND listingtime<$time AND video_title like  '%$key%'";
        $videolist = D('ZyVideo')->where($where)->select();
        $this->assign("list",$videolist);

        $html = $this->fetch("seachVideo");
        echo json_encode($html);exit;
    }
    /**
     * 对回答的评论
     */
    public function commentAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的回答');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $field = 'id,uid,oid,qst_title';
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'commentAlbum','url'=>U('classroom/AdminAlbum/commentAlbum'));
        $this->pageTitle['commentAlbum'] = '评论列表';
        $this->pageKeyList = array('id','qst_title','uid','oid','DOACTION');
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
        $map['type'] = 2;
        $data = D('ZyQuestion','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'ask\')">删除</a>';
        }
        $this->displayList($data);
    }
    
    /******************************************提问结束，笔记开始 ************/
    
    /**
     * 班级对应的笔记
     */
    public function noteAlbum(){
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'班级笔记列表','tabHash'=>'noteAlbum','url'=>U('classroom/AdminAlbum/noteAlbum'));
        $this->pageTitle['noteAlbum'] = '班级笔记列表';
        if(!$_GET['id']) $this->error('请选择要查看的班级');
        $field = 'id,uid,oid,note_title,note_comment_count';
        $this->pageKeyList = array('id','note_title','uid','oid','note_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 2;
        $data = D('ZyNote','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('classroom/AdminAlbum/noteCommentAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'noteCommentAlbum')).'">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Alubm\',\'note\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }
    
    /**
     * 笔记对应的评论
     */
    public function noteCommentAlbum(){

        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'noteCommentAlbum','url'=>U('classroom/AdminAlbum/noteCommentAlbum'));
        $this->pageTitle['noteCommentAlbum'] = '评论列表';
        $field = 'id,uid,oid,type,note_description';
        $this->pageKeyList = array('id','uid','note_description','type','oid','DOACTION');
        $this->pageButton[] = array('title' => '删除笔记', 'onclick' => "admin.delNoteAllEdit('delnote')");
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
    //    $map['type'] = 2;
        $data = D('ZyNote','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['type'] = $vo['type'];
            if ($vo['type'] == 1) {
                $data['data'][$key]['oid'] = getVideoNameForID($vo['oid']);
            } else if ($vo['type'] == 2) {
                $data['data'][$key]['oid'] = getAlbumNameForID($vo['oid']);
            } else {
                $data['data'][$key]['oid'] = '不存在';
            }
            $data['data'][$key]['oid'] = '<div style="width:200px;height:30px;overflow:hidden;">' . $data['data'][$key]['oid'] . '</div>';
            $data['data'][$key]['type'] = ($vo['type'] == 1) ? '课程' : '班级';
            $data['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['note_description'] = $vo['note_description'];
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'note\')">删除</a>';
        }
        $this->_listpk = 'id';
        $this->allSelected = true;
        $this->displayList($data);
    }
    
    /**
     * 对笔记评论的回复
     */
    public function noteReplayAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $field = 'id,uid,oid,note_title';
        $this->pageTab[] = array('title'=>'回复列表','tabHash'=>'noteReplayAlbum','url'=>U('classroom/AdminAlbum/noteReplayAlbum'));
        $this->pageTitle['noteReplayAlbum'] = '回复列表';
        $this->pageKeyList = array('id','note_title','uid','oid','DOACTION');
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
        $map['type'] = 2;
        $data = D('ZyNote','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'note\')">删除</a>';
        }
        $this->displayList($data);
    }
    
    /*******************************************笔记操作结束,评论开始******************/
    /**
     * 班级对应的评价
     */
    public function reviewAlbum(){
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        if(!$_GET['id']) $this->error('请选择要查看的评价');
        $this->pageTab[] = array('title'=>'班级评价列表','tabHash'=>'reviewAlbum','url'=>U('classroom/AdminAlbum/reviewAlbum',array('id'=>intval($_GET['id']))));
        $this->pageTitle['reviewAlbum'] = '班级评价列表';
        
        $field = 'id,uid,app_id,to_comment';
        $this->pageKeyList = array('id','to_comment','uid','app_id','DOACTION');
        $map['app_id'] = intval($_GET['id']);
        $map['to_comment_id'] = 0; //父类id为0
        $map['is_del'] = 0;
        $map['app_table'] = 'album';
        $order = "id DESC";
        $limit=20;
        $data = M('ZyComment')->where ( $map )->order ( $order )->field ( $field )->findPage ( $limit );
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['app_id'] = D('Album','classroom')->getAlbumTitleById($vo['app_id']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('classroom/AdminAlbum/reviewCommentAlbum',array('app_id'=>$vo['app_id'],'id'=>$vo['id'],'tabHash'=>'reviewCommentAlbum')).'">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'review\')">删除(连带删除回复)</a>';
        }
        $this->displayList($data);
    }
    
    /**
     * 评价对应的回复
     */
    public function reviewCommentAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'reviewCommentAlbum','url'=>U('classroom/AdminAlbum/reviewCommentAlbum'));
        $this->pageTitle['reviewCommentAlbum'] = '评论列表';
        $field = 'id,uid,app_id,to_comment';
        $this->pageKeyList = array('id','uid','to_comment','app_id','DOACTION');
        $this->pageButton[] = array('title' => '删除回复', 'onclick' => "admin.delReviewAll('delReview')");
        $map['to_comment_id'] = intval($_GET['id']); //父类id为问题id
        $map['app_id'] = intval($_GET['app_id']);
        $map['is_del'] = 0;
        $map['app_table'] = 'album';
        $order = "id DESC";
        $limit = 20;
        $data = M('ZyComment')->where ( $map )->order ( $order )->field ( $field )->findPage ( $limit );
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['app_id'] = D('Album','classroom')->getAlbumTitleById($vo['app_id']);
            $data['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] ="<a href=javascript:admin.reviewhuifu(" . $vo['id'] . ",'delreviewhuifu');>删除回复</a>";
        }
        $this->displayList($data);
    }
    
    //****************************评论结束***********************//
    /**
     * 删除提问、回答、评论
     * 
     */
    public function delProperty(){
        if(!$_POST['id']) exit(json_encode(array('status'=>0,'info'=>'错误的参数')));
        if(!$_POST['property'] || !in_array($_POST['property'], array('ask','note','review'))) exit(json_encode(array('status'=>0,'info'=>'参数错误')));
        if($_POST['property'] == 'ask'){
            $result = D('ZyQuestion','classroom')->doDeleteQuestion(intval($_POST['id']));
        }  else if($_POST['property'] == 'note'){
            $result = D('ZyNote','classroom')->doDeleteNote(intval($_POST['id']));
        } else if($_POST['property']){
            $result = D('ZyReview','classroom')->doDeleteReview(intval($_POST['id']));
        }
        if($result['status'] == 1){
            exit(json_encode(array('status'=>1,'info'=>'删除成功')));
        } else {
            exit(json_encode(array('status'=>0,'info'=>'删除失败，请稍后再试')));
        }
    }

    /**
     * 班级后台管理菜单
     * @return void
     */
    private function _initClassroomListAdminMenu(){
        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('classroom/AdminAlbum/index'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addAlbum','url'=>U('classroom/AdminAlbum/addAlbum'));
        $this->pageTab[] = array('title'=>'回收站','tabHash'=>'recycle','url'=>U('classroom/AdminAlbum/recycle'));
    }

    /**
     * 班级后台的标题
     */
    private function _initClassroomListAdminTitle(){
        $this->pageTitle['index'] = '列表';
        $this->pageTitle['addAlbum'] = '添加';
        $this->pageTitle['recycle'] = '回收站';
    }

    /***
     * 批量删除笔记评论
     */
    private function  delnoteContent()
    {

    }

    /**
     * 挂载班级
     */
    public function openMount()
    {
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['id'] = $id;
        $data['is_mount'] = 1;
        $data['atime'] = time();
        $result = M('album')->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'挂载失败',0);
            return;
        }

        $this->ajaxReturn(null,'挂载成功',1);
    }

    /**
     * 取消挂载班级
     */
    public function closeMount()
    {
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['id'] = $id;
        $data['is_mount'] = 0;
        $result = M('album')->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'取消挂载失败',0);
            return;
        }

        $this->ajaxReturn(null,'取消挂载成功',1);
    }

}