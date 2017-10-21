<?php
/**
 * 云课堂播(套餐)后台配置
 * 1.套餐管理 - 目前支持1级分类
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

    //套餐列表
    public function index(){
        $this->_initSchoolListAdminMenu();
        $this->_initSchoolListAdminTitle();
        $this->pageKeyList      = array('id','album_title','price','cover','user_title','ctime','status','DOACTION');
        $this->pageButton[]  =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  =  array('title'=>'删除','onclick'=>"admin.delAlbumAll('delAlbums','0')");
        $this->searchKey      = array('id','album_title');
        $this->searchPostUrl = U('school/AdminAlbum/index');
        $listData = $this->_getData(20 , 0);
        $this->displayList($listData);
    }



    //编辑、添加套餐
    public function addAlbum(){
        $this->_initSchoolListAdminMenu();
        $this->_initSchoolListAdminTitle();
        $this->pageKeyList = array('album_category','album_title','album_intro','vip_level','school_title','is_mount','price','album_html','cover','is_best');

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $vip_levels = $vip_levels ? array('0'=>'无')+$vip_levels : array('0'=>'无');
        $this->opt['vip_level'] = $vip_levels;

        $this->opt['is_best'] = array( '1'=>'设置为精选' );
        $this->opt['is_mount'] = array( '0'=>'否','1'=>'是');
        $this->notEmpty = array('album_title','album_intro','album_category','cover');
        //获取机构
        $school_title = model('School')->getSchooldTitleByUid($this->mid);
        $this->savePostUrl = U('school/AdminAlbum/doAddAlbum');
        echo W('CategoryLevel',array('table'=>'zy_package_category','id'=>'package_level','default'=>$fullcategorypaths ));
        $output = ob_get_contents();
        $html=$this->fetch("album");
        ob_end_clean();
        $this->displayConfig(array('album_category'=>$output,'album_html'=>$html,'school_title'=>$school_title));
    }

    //添加套餐操作
    public function doAddAlbum(){
        $post = $_POST;
        $data['album_title']         = t($post['album_title']); //套餐名称
        $data['album_intro']         = t($post['album_intro']); //套餐简介
        $data['price']                 = floatval($post['price']); //套餐价格
        $myAdminLevelhidden         = getCsvInt(t($post['package_levelhidden']),0,true,true,',');  //处理分类全路径
        $data['album_category']     =  $myAdminLevelhidden; //分类全路径
        $data['cover']                 = intval($post['cover']); //封面id
        $data['mhm_id']                = intval($this->school_id); //机构id
        $data['is_best']             = isset($post['is_best']) ? intval($post['is_best']) : 0; //编辑精选
        $album_tag                     = explode(',' , $post['album_tag']);
        $video_ids                     = explode(',' , trim($post['video_ids'] ,',' ) );

        if(!$data['album_title']) $this->error('套餐标题不能为空');
        if(!$data['album_intro']) $this->error('套餐简介不能为空');
        if(!$data['album_category']) $this->error('请选择套餐分类');
        if(!$data['cover']) $this->error('还没有上传封面');

        $is_mount = M('album')->where('id = '.$post['id'])->getField('is_mount');
        if($post['is_mount'] != 0 && !$is_mount){
            $data['is_mount'] = 2;
        }else{
            $data['is_mount']        = intval($post['is_mount']);
        }
        //判断课程ID属于该机构
        $time = time();
        $where = "is_del=0 AND is_activity=1 AND mhm_id=$this->school_id AND uctime>$time AND listingtime<$time";
        $video   = D('ZyVideo')->where($where)->select();
        foreach ($video_ids as $key => $val) {
            if(in_array($val,getSubByKey($video,'id'),true)){
                $data['ctime'] = time();
                if($post['id']){
                    $result = M('album')->where('id = '.$post['id'])->data($data)->save();
                } else {
                    $data['ctime']              = time();
                    $data['uid']                = $this->mid;
                    $result = M('album')->data($data)->add();
                }
                echo M()->getLastSql();
                if($result){
                    unset($data);
                    //处理标签和课程
                    if($post['id']){
                        //删除旧课程
                        M('album_video_link')->where('album_id='.$post['id'])->delete();
                        //添加套餐课程关联
                        $sql = 'insert into '.C('DB_PREFIX').'album_video_link (`album_id`,`video_id`) values';
                        foreach($video_ids as $val){
                            $sql .= '('.$post['id'] .','.$val .'),';
                        }
                        M()->query( trim($sql , ','));
                    } else {
                        //添加套餐课程关联
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
            }else{
                $this->error("系统错误，请稍后再试!");
            }
        }
    }

    /**
     * 编辑套餐
     */
    public function editAlbum(){
        $this->_initSchoolListAdminMenu();
        $this->pageTitle['editAlbum'] = '编辑套餐';
        $this->pageTab[]   = array('title'=>'编辑套餐','tabHash'=>'editAlbum','url'=>U('school/AdminAlbum/editAlbum'));
        $this->pageKeyList = array('id','myAdminLevelhidden','album_title','album_intro','school_title','vip_level','is_mount','price','album_html','cover','is_best');

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $vip_levels = $vip_levels ? array('0'=>'无')+$vip_levels : array('0'=>'无');
        $this->opt['vip_level'] = $vip_levels;

        $this->opt['is_best'] = array( '1'=>'设置为精选' );
        $this->opt['is_mount']= array( '0'=>'否','1'=>'是');
        $this->notEmpty       = array('album_title','album_intro','album_category','cover');
        $this->savePostUrl    = U('school/AdminAlbum/doAddAlbum');

        $data = D("Album","classroom")->getAlbumById($_GET['id']);
        $data['school_title'] = M('school')->where('id='.$data['mhm_id'])->getField('title');
        $data['fullcategorypath'] = trim($data['album_category'] , ',');
        ob_start();
//         echo W('VideoLevel',array('type'=>2,'default'=>$data['fullcategorypath']));
        echo W('CategoryLevel',array('table'=>'zy_package_category','id'=>'package_level','default'=>$data['fullcategorypath'] ));
        $output = ob_get_contents();
        ob_end_clean();
        $data['myAdminLevelhidden'] = $output;

        //查询套餐包含的课程
        $video_data = M('album_video_link')->where('album_id='.$_GET['id'])->field('video_id')->findAll();
        $video_data = getSubByKey($video_data , 'video_id');
        $video_ids  = implode(',', $video_data);

        $this->assign("data" , $video_data);
        $this->assign("album_video",$video_ids.',');
        $html = $this->fetch("album");
        $data['album_html'] = $html;
        $this->displayConfig($data);
    }

    //批量删除套餐(隐藏)
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
    //删除套餐(隐藏)
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

    //套餐回收站(被隐藏的套餐)
    public function recycle(){
        $this->_initSchoolListAdminMenu();
        $this->_initSchoolListAdminTitle();
        $this->pageKeyList = array('id','album_title','user_title','ctime','DOACTION');
        $this->pageButton[]  =  array('title'=>'彻底删除','onclick'=>"admin.delAlbumAll('delAlbums',3)");
        $listData = $this->_getData(20,1);
        $this->displayList($listData);
    }


    //获取套餐数据
    private function _getData($limit = 20, $is_del){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['album_title'] && $map['album_title'] = array('like', '%'.t($_POST['album_title']).'%');
        }
        $map['status'] = $is_del ? array('eq',0) : array('neq',0); //搜索非隐藏内容
        $map['is_del'] = 0;
        if(is_admin($this->mid)){
            $list = M('album')->where($map)->order('ctime desc,id desc')->findPage($limit);
        }else{
            $map['mhm_id'] = $this->school_id;
            $list = M('album')->where($map)->order('ctime desc,id desc')->findPage($limit);
        }
        foreach ($list['data'] as &$value){
            $value['album_title'] = msubstr($value['album_title'],0,20);
            $url = U('classroom/Album/view', array('id' => $value['id']));
            $value['album_title'] = getQuickLink($url,$value['album_title'],"未知套餐");
            $value['user_title'] = getUserSpace($value['uid'], null, '_blank');
            $value['price'] = $value['price'].'元';
            $value['ctime'] = friendlyDate($value['ctime']);
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 , 60)." width='60px' height='60px'>";
            $value['DOACTION'] = '<a href="'.U('school/AdminAlbum/reviewAlbum',array('tabHash'=>'reviewAlbum','id'=>$value['id'])).'">评价</a> | ';
            $value['DOACTION'] .= ($value['status'] == 0) ? '<a href="'.U('school/AdminAlbum/editAlbum',array('id'=>$value['id'],'tabHash'=>'editAlbum')).'">编辑</a> |
                    <a onclick="admin.delObject('.$value['id'].',\'Album\','.$value['status'].');" href="javascript:void(0)">恢复</a>' : '<a href="'.U('school/AdminAlbum/editAlbum',array('id'=>$value['id'],'tabHash'=>'editAlbum')).'">编辑</a> |
                            <a onclick="admin.delObject('.$value['id'].',\'Album\','.$value['status'].');" href="javascript:void(0)">删除</a>';;
        }
        return $list;
    }




    /**
     * 套餐对应的提问
     */
    public function askAlbum(){
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'套餐提问列表','tabHash'=>'askAlbum','url'=>U('school/AdmimAlbum/askAlbum'));
        $this->pageTitle['askAlbum'] = '套餐问题列表';
        if(!$_GET['id']) $this->error('请选择要查看的套餐');
        $field = 'id,uid,oid,qst_title,qst_comment_count';
        $this->pageKeyList = array('id','qst_title','uid','oid','qst_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 2;
        $data = D('ZyQuestion','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('school/AdminAlbum/answerAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'answerAlbum')).'">查看回答</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'ask\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }

    /**
     * 提问对应的回答
     */
    public function answerAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的问题');
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'回答列表','tabHash'=>'answerAlbum','url'=>U('school/AdminAlbum/answerAlbum'));
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
    //检索套餐列表
    public function seachVideo(){
        $key   = t($_POST['key']);
        $time  = time();
        $mhm_id = model('School')->where('uid='.$this->mid)->getField('id');
        $where = "is_del=0 AND is_activity=1 AND mhm_id=$mhm_id AND uctime>$time AND listingtime<$time AND video_title like  '%$key%'";
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
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $field = 'id,uid,oid,qst_title';
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'commentAlbum','url'=>U('school/AdminAlbum/commentAlbum'));
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
     * 套餐对应的笔记
     */
    public function noteAlbum(){
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'套餐笔记列表','tabHash'=>'noteAlbum','url'=>U('school/AdminAlbum/noteAlbum'));
        $this->pageTitle['noteAlbum'] = '套餐笔记列表';
        if(!$_GET['id']) $this->error('请选择要查看的套餐');
        $field = 'id,uid,oid,note_title,note_comment_count';
        $this->pageKeyList = array('id','note_title','uid','oid','note_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 2;
        $data = D('ZyNote','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('school/AdminAlbum/noteCommentAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'noteCommentAlbum')).'">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Alubm\',\'note\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }

    /**
     * 笔记对应的评论
     */
    public function noteCommentAlbum(){

        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'noteCommentAlbum','url'=>U('school/AdminAlbum/noteCommentAlbum'));
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
            $data['data'][$key]['type'] = ($vo['type'] == 1) ? '课程' : '套餐';
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
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $field = 'id,uid,oid,note_title';
        $this->pageTab[] = array('title'=>'回复列表','tabHash'=>'noteReplayAlbum','url'=>U('school/AdminAlbum/noteReplayAlbum'));
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
     * 套餐对应的评价
     */
    public function reviewAlbum(){
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'套餐评价列表','tabHash'=>'reviewAlbum','url'=>U('school/AdminAlbum/reviewAlbum'));
        $this->pageTitle['reviewAlbum'] = '套餐评价列表';
        if(!$_GET['id']) $this->error('请选择要查看的评价');
        $field = 'id,uid,oid,review_description,star,review_comment_count';
        $this->pageKeyList = array('id','review_description','uid','oid','star','review_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 2;
        $data = D('ZyReview','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
            $url = U('classroom/Album/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid'] = getQuickLink($url,$data['data'][$key]['oid'],"未知套餐");
            $data['data'][$key]['uid'] = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="'.U('school/AdminAlbum/reviewCommentAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'reviewCommentAlbum')).'">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Album\',\'review\')">删除(连带删除回复)</a>';
            $data['data'][$key]['start'] = $vo['start']/ 20;
        }
        $this->displayList($data);
    }

    /**
     * 评价对应的回复
     */
    public function reviewCommentAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initSchoolListAdminTitle();
        $this->_initSchoolListAdminMenu();
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'reviewCommentAlbum','url'=>U('school/AdminAlbum/reviewCommentAlbum'));
        $this->pageTitle['reviewCommentAlbum'] = '评论列表';
        $field = 'id,uid,oid,review_description';
        $this->pageKeyList = array('id','uid','review_description','oid','DOACTION');
        $this->pageButton[] = array('title' => '删除回复', 'onclick' => "admin.delReviewAll('delReview')");
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
        $map['type'] = 2;
        $data = D('ZyReview','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('Album','classroom')->getAlbumTitleById($vo['oid']);
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
     * 套餐后台管理菜单
     * @return void
     */
    private function _initSchoolListAdminMenu(){
        $this->pageTab[] = array('title'=>'套餐列表','tabHash'=>'index','url'=>U('school/AdminAlbum/index'));
        $this->pageTab[] = array('title'=>'添加套餐','tabHash'=>'addAlbum','url'=>U('school/AdminAlbum/addAlbum'));
        $this->pageTab[] = array('title'=>'套餐回收站','tabHash'=>'recycle','url'=>U('school/AdminAlbum/recycle'));
    }

    /**
     * 套餐后台的标题
     */
    private function _initSchoolListAdminTitle(){
        $this->pageTitle['index'] = '套餐列表';
        $this->pageTitle['addAlbum'] = '添加套餐';
        $this->pageTitle['recycle'] = '套餐回收站';
    }

    /***
     * 批量删除笔记评论
     */
    private function  delnoteContent()
    {

    }


}