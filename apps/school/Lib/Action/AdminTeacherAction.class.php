<?php
/**
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
require_once './api/qiniu/rs.php';
class AdminTeacherAction extends AdministratorAction{
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
        $this->pageTitle['index']           = '讲师管理';
        $this->pageTitle['article']         = '讲师文章';
        $this->pageTitle['details']         = '讲师经历';
        $this->pageTitle['style']           = '讲师风采';
        $this->pageTitle['photoDeatils']    = '讲师相册详情';
        $this->pageTitle['disable']         = '讲师文章禁用列表';


        // Tab选项
        $this->pageTab [] = array ('title' => '讲师列表','tabHash' => 'index','url' => U ( 'school/AdminTeacher/index' ));
        $this->pageTab [] = array ('title' => '讲师文章','tabHash' => 'article','url' => U ( 'school/AdminTeacher/article'));
        $this->pageTab [] = array ('title' => '讲师经历','tabHash' => 'details','url' => U ('school/AdminTeacher/details'));
        if(!is_admin($this->mid)){
            $this->pageTab [] = array ('title' => '添加讲师','tabHash' => 'addTeacher','url' => U ( 'school/AdminTeacher/addTeacher' ));
        }
        $this->pageTab [] = array ('title' => '讲师风采','tabHash' => 'style','url' => U ('school/AdminTeacher/style'));
        $this->pageTab [] = array ('title' => '讲师文章禁用列表','tabHash' => 'disable','url' => U ( 'school/AdminTeacher/disable' ));
    }

    public function index(){
        $_REQUEST['tabHash'] = 'index';

        $id     =  intval($_POST['id']);//获取讲师id
        $uid    =  intval($_POST['uid']);//获取用户id
        $uname  =  t($_POST['uname']);//获取用户姓名
        $name   =  t($_POST['name']);//获取讲师姓名
        $mhm_id =  intval($_POST['school_title']);//获取机构id
        $title  =  t($_POST['title']);//获取讲师职称
        $inro   =  t($_POST['inro']);//获取讲师简介
        // 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array('id','uname','school_title','name','face','title','inro','is_del','DOACTION');
        $this->_initTabSpecial();

        if(is_admin($this->mid)){
            $this->searchKey = array('id','uid','uname','name','school_title','title','inro');
        }else{
            $this->searchKey = array('id','uid','uname','name','title','inro');
        }
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delTeacherAll('disableTeacher','teacher')");
        $teacher_title = M('zy_teacher_title_category')->field('zy_teacher_title_category_id,title')->findAll();
        $this->opt['title'] = array_column($teacher_title,'title','zy_teacher_title_category_id');

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['school_title'] = $school;
        $map['mhm_id']= array('gt','0');
        $map['is_del'] = ['neq',2];
        if(!empty($id))$map['id']=$id;
        if(!empty($uid))$map['uid']=$uid;
        if(!empty($uname))$map['uid']=$uname;
        if(!empty($name))$map['name']=array("like","%$name%");
        if(!empty($mhm_id))$map['mhm_id']=$mhm_id;
        if(!empty($title))$map['title']=array("like","%$title%");
        if(!empty($inro))$map['inro']=array("like","%$inro%");
        if(is_admin($this->mid)){
            $trlist=M("zy_teacher")->where($map)->order("id DESC")->findPage(20);
        }else{
            $map['mhm_id'] = is_school($this->mid);
            $trlist=M("zy_teacher")->where($map)->order("id DESC")->findPage(20);
        }
        foreach($trlist['data'] as &$val){
            $arr = explode(',', $val['style_id']);
            foreach ($arr as $k => $v) {
                if($k < 4){
                    $val['style']  .= "<img src=".getAttachUrlByAttachId($v)." width='60px' height='60px'>&nbsp;";
                }
            }
            $url = U('classroom/Teacher/view', array('id' => $val['id']));
            $val['name'] = '<div style="width:192px;height:30px;overflow:hidden;"><a href="' . $url . '" target="_bank">' . $val['name'] . '</a></div>';
            $val['inro']  = mb_substr($val['inro'], 0,40,'utf-8')."...";
            $val['uname'] = getUserSpace($val['uid'], null, '_blank');
            //处理机构信息
            $s_map = array('id'=>$val['mhm_id']);
            $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
            if($school){
                if(!$school['doadmin']){
                    $url = U('school/School/index', array('id' => $val['mhm_id']));
                }else{
                    $url = getDomain($school['doadmin']);
                }
                $val['school_title'] = getQuickLink($url,$school['title'],"平台所有");
            }else{
                $val['school_title'] = "<span style='color: red;'>平台所有</span>";
            }
            $val['title'] = M('zy_teacher_title_category')->where(array('zy_teacher_title_category_id'=>$val['title']))->getField('title');
            $val['face']  = "<img src=".getAttachUrlByAttachId($val['head_id'])." width='60px' height='60px'>";
            $val['inro']  = msubstr($val['inro'], 0,100);
            if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'启用\',\'讲师\',\'teacher\');">启用</a>';
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            else {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'禁用\',\'讲师\',\'teacher\');">禁用</a>';
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/editTeacher',array('id'=>$val['id'],'tabHash'=>'revise')).";>编辑</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/addStyle',array('id'=>$val['id'],'tabHash'=>'addStyle','doadd'=>1)).";>风采上传</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/addArticle',array('id'=>$val['id'],'tabHash'=>'revise','doadd'=>1)).";>添加文章</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/addDetails',array('id'=>$val['id'],'tabHash'=>'revise','doadd'=>1)).";>添加经历</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/courseShow',array('id'=>$val['id'],'tabHash'=>'courseShow')).";>课程展示</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/article',array('id'=>$val['id'])).";>文章展示</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/details',array('id'=>$val['id'])).";>经历展示</a>";
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/style',array('id'=>$val['id'])).";>风采展示</a>";
        }
        $this->_listpk = 'id';
        $this->displayList($trlist);
    }
    /**
     *讲师文章
     */
    public function article(){
        $this->_initTabSpecial();
        $_REQUEST['tabHash'] = 'article';
        if($_GET['id']){
            $tid=intval($_GET['id']);
            $teacher_name = M('zy_teacher')->where('id='.$tid)->getField('name');
            $this->pageTitle['article'] = '讲师文章-' . $teacher_name;
        }else{
            $tid=intval($_POST['tid']);
        }
        $art_title  =  t($_POST['art_title']);
        $id         =  intval($_POST['id']);

        $this->pageKeyList   = array('id','tid','name','cover','art_title','article','ctime','is_del','DOACTION');
        $this->searchKey     = array('id','tid','art_title');
        $this->pageButton[]  = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delTeacherAll('disableTeacher','article')");
        if(!empty($id))$map['id']=$id;
        if(!empty($tid))$map['tid']=$tid;
        if(!empty($art_title))$map['article.art_title']=array("like","%$art_title%");
        if(is_admin($this->mid)){
            $trlist=M("zy_teacher_article")->where($map)->order("id DESC")->findPage(20);
        }else{
            if($tid){
                $map['tid'] = $tid;
                $trlist=M("zy_teacher_article")->where($map)->order("ctime DESC")->findPage(20);
            }else{
                $trlist=M("zy_teacher_article article")->join(' INNER JOIN (SELECT id as t_id FROM '.C('DB_PREFIX').'zy_teacher  WHERE mhm_id IN ('. (string) $this->school_id .')) AS t ON t.t_id = article.tid')->where($map)->order("ctime DESC")->findPage(20);
            }
        }
        foreach($trlist['data'] as &$val){
            $val['name']    = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            $val['cover']   = "<img src=".getAttachUrlByAttachId($val['cover'])." width='60px' height='60px'>";
            $val['article']  = msubstr(t($val['article']), 0,40);
            $val['ctime']    = friendlyDate($val['ctime']);

            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $val['name'] = getQuickLink($url,$val['name'],"未知讲师");
            $url = U('classroom/Teacher/checkDeatil', array('id' => $val['tid'],'aid' => $val['id']));
            $val['art_title'] = getQuickLink($url,$val['art_title'],"未知文章");

            if($val['is_del'] == 0) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'禁用\',\'讲师文章\',\'article\');">禁用</a>';
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'启用\',\'讲师文章\',\'article\');">启用</a>';
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/addArticle',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";

        }
        $this->_listpk = 'id';
        $this->displayList($trlist);
    }
    /**
     *讲师经历
     */
    public function details(){
        $this->_initTabSpecial();
        $_REQUEST['tabHash'] = 'details';
        if($_GET['id']){
            $tid=intval($_GET['id']);
            $teacher_name = M('zy_teacher')->where('id='.$tid)->getField('name');
            $this->pageTitle['details'] = '讲师经历-' . $teacher_name;
        }else{
            $tid=intval($_POST['tid']);
        }
        $title  	=  t($_POST['title']);
        $id         =  intval($_POST['id']);
        $type       =  t($_POST['type']);

        $this->pageKeyList   = array('id','tid','name','Time','title','content','type','ctime','is_del','DOACTION');
        $this->searchKey     = array('id','tid','title','type');
        $this->pageButton[]  = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delTeacherAll('disableTeacher','details')");
        $this->opt['type'] = array('1'=>"过往经历",'2'=>"相关案例");
        if(!empty($id))$map['id']=$id;
        if(!empty($tid))$map['tid']=$tid;
        if(!empty($type))$map['type']=$type;
        if(!empty($title))$map['title']=array("like","%$title%");
        if(is_admin($this->mid)){
            $trlist=M("zy_teacher_details")->where($map)->order("id DESC")->findPage(20);

        }else{
            if($tid){

                $maps['tid'] = $tid;
                $trlist=M("zy_teacher_details")->where($maps)->order("ctime DESC")->findPage(20);
            }else {
                /*$old_mhm_tid = M('zy_teacher')->where(['mhm_id'=>is_school($this->mid)])->field('id')->select();
                $new_mhm_tid = getSubByKey($old_mhm_tid,'id');
                $trlist = M("zy_teacher_details d")->where(['tid'=>['in',trim(implode(',',$new_mhm_tid),',')]])->findPage();*/
               $trlist = M("zy_teacher_details d")->join(' INNER JOIN (SELECT id as t_id  FROM ' . C('DB_PREFIX') . 'zy_teacher  WHERE mhm_id IN (' . (string)$this->school_id . ')) AS t ON t.t_id = d.tid')->where($map)->order("ctime DESC")->findPage(20);
            }
        }

        foreach($trlist['data'] as &$val){
            $val['name']    = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            $val['content']  = msubstr($val['content'], 0,40);
            $val['ctime']    = friendlyDate($val['ctime']);

            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $val['name'] = getQuickLink($url,$val['name'],"未知讲师");
            $url = U('classroom/Teacher/details', array('id' => $val['tid']));
            $val['title'] = getQuickLink($url,$val['title'],"未知经历");

            if($val['type'] == 1){
                $val['type'] = '过往经历';
            }else if($val['type'] == 2){
                $val['type'] = '相关案例';
            }
            if($val['is_del'] == 0) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'禁用\',\'讲师经历\',\'details\');">禁用</a>';
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'启用\',\'讲师经历\',\'details\');">启用</a>';
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/addDetails',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $val['DOACTION'].=' | <a href="javascript:admin.delTeacher('.$val['id'].',\'delTeacher\',\'讲师经历\',\'details\')">彻底删除</a>  ';

        }

        $this->_listpk = 'id';
        $this->displayList($trlist);
    }
    /**
     *讲师相册
     */
    public function style(){
        $this->_initTabSpecial();
        $_REQUEST['tabHash'] = 'style';
        if($_GET['id']){
            $tid=intval($_GET['id']);
            $teacher_name = M('zy_teacher')->where('id='.$tid)->getField('name');
            $this->pageTitle['style'] = '讲师风采-' . $teacher_name;
        }else{
            $tid=intval($_POST['tid']);
        }
        $title  =  t($_POST['title']);
        $id         =  intval($_POST['id']);

        $this->pageKeyList   = array('id','tid','name','title','ctime','is_del','DOACTION');
        $this->searchKey     = array('id','tid','title');
        $this->pageButton[]  = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delTeacherAll('disableTeacher','style')");
        if(!empty($id))$map['id']=$id;
        if(!empty($tid))$map['tid']=$tid;
        if(!empty($title))$map['title']=array("like","%$title%");

        if(is_admin($this->mid)){
            $trlist=M("zy_teacher_photos")->where($map)->order("id DESC")->findPage(20);
        }else{
            if($tid){
                $map['tid'] = $tid;
                $trlist=M("zy_teacher_photos")->where($map)->order("ctime DESC")->findPage(20);
            }else {
                $trlist = M("zy_teacher_photos p")->join(' INNER JOIN (SELECT id as t_id FROM ' . C('DB_PREFIX') . 'zy_teacher  WHERE mhm_id IN (' . (string)$this->school_id . ')) AS t ON t.t_id = p.tid')->where($map)->order("ctime DESC")->findPage(20);
            }
        }
        foreach($trlist['data'] as &$val){
            $val['name']    = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            $val['ctime']    = friendlyDate($val['ctime']);

            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $val['name'] = getQuickLink($url,$val['name'],"未知讲师");
            $url = U('classroom/Teacher/style', array('id' => $val['tid']));
            $val['title'] = getQuickLink($url,$val['title'],"未知风采");

            if($val['is_del'] == 0) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'禁用\',\'讲师相册\',\'style\');">禁用</a>';
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'启用\',\'讲师相册\',\'style\');">启用</a>';
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('school/AdminTeacher/photoDeatils',array('id'=>$val['id'],'tabHash'=>'photoDeatils')).";>相册详情展示</a>";

        }
        $this->_listpk = 'id';
        $this->displayList($trlist);
    }
    /**
     *讲师相册详情
     */
    public function photoDeatils(){
        $_REQUEST['tabHash'] = 'photoDeatils';
        $photo_id=intval($_GET['id']);

        $tid    = intval($_POST['tid']);
        $title  = t($_POST['title']);
        $id     = intval($_POST['id']);
        $this->_initTabSpecial();
        $this->pageKeyList   = array('id','tid','name','title','type','photo_id','cover','ctime','is_del','DOACTION');
        $this->searchKey     = array('id','tid','title');
        $this->pageButton[]  = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delTeacherAll('disableTeacher','photoDeatils')");
        if(!empty($photo_id))$map['photo_id']=$photo_id;
        if(!empty($id))$map['id']=$id;
        if(!empty($tid))$map['tid']=$tid;
        if(!empty($title))$map['title']=array("like","%$title%");
        $trlist=M('zy_teacher_photos_data')->where($map)->order("pic_id DESC")->findPage(20);
        foreach($trlist['data'] as &$val){
            $val['id'] = $val['pic_id'];
            $val['name']    = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            if($val['type'] == 1){
                $val['type'] = '图片';
                $val['cover'] = "<img src=".getCover($val['resource'] , 60 ,60)." width='60px' height='60px'>";
            }else{
                $val['type'] = '视频';
                $val['cover'] = "<img src=".getCover($val['cover'] , 60 ,60)." width='60px' height='60px'>";
            }
            $val['ctime']    = friendlyDate($val['ctime']);

            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $val['name'] = getQuickLink($url,$val['name'],"未知讲师");
            $url = U('classroom/Teacher/getPhotoList', array('id' => $val['tid'],'photo_id'=>$val['photo_id']));
            $val['title'] = getQuickLink($url,$val['title'],"未知相册");
            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);

            if($val['is_del'] == 0) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'禁用\',\'讲师相册详情\',\'photoDeatils\');">禁用</a>';
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1) {
                $val['DOACTION'] ='<a href="javascript:admin.mzTeacherEdit('.$val['id'].',\'closeTeacher\',\'启用\',\'讲师相册详情\',\'photoDeatils\');">启用</a>';
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }

        }
        $this->_listpk = 'id';
        $this->displayList($trlist);
    }

    /**
     *讲师文章禁用列表
     */
    public function disable(){
        $_REQUEST['tabHash'] = 'disable';

        $id     =  intval($_POST['id']);
        $tid    =  intval($_POST['tid']);
        $art_title  =  t($_POST['art_title']);
        $this->_initTabSpecial();
        $this->pageKeyList   = array('id','tid','name','cover','art_title','article','is_del','DOACTION');
        $this->searchKey     = array('id','tid','art_title');
        $this->pageButton[]  = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'删除','onclick'=>"admin.delTeacherAll('delTeacher')");
        $map=array('is_del'=>'1');
        if(!empty($id))$map['id']=$id;
        if(!empty($tid))$map['tid']=$tid;
        if(!empty($art_title))$map['art_title']=array("like","%$art_title%");
        if(is_admin($this->mid)){
            $trlist=M("zy_teacher_article")->where($map)->order("id DESC")->findPage(20);
        }else{
            $trlist=M("zy_teacher_article a")->join(' INNER JOIN (SELECT id as t_id FROM '.C('DB_PREFIX').'zy_teacher WHERE mhm_id IN ('. (string) $this->school_id .')) AS t ON t.t_id = a.tid')->where($map)->order("ctime DESC")->findPage(20);
        }

        foreach($trlist['data'] as &$val){
            $val['name']    = M('zy_teacher')->where('id='.$val['tid'])->getField('name');
            $val['cover']   = "<img src=".getAttachUrlByAttachId($val['cover'])." width='60px' height='60px'>";
            $val['article']  = msubstr($val['article'], 0,40);

            $url = U('classroom/Teacher/view', array('id' => $val['tid']));
            $val['name'] = getQuickLink($url,$val['name'],"未知讲师");

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].="<a href=".U('school/AdminTeacher/addArticle',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            $val['DOACTION'].=' | <a href="javascript:admin.delTeacher('.$val['id'].',\'delTeacher\',\'讲师文章\',\'article\')">彻底删除</a>  ';
        }
        $this->_listpk = 'id';
        $this->displayList($trlist);
    }

    /**
     * 讲师头衔
     */
    /*public function teacherTitle(){
        $_REQUEST['tabHash'] = 'teacherTitle';
        $this->_initTabSpecial();
        $treeData = model ( 'CategoryTree' )->setTable ( 'zy_teacher_title_category' )->getNetworkList ();
        $this->displayTree ( $treeData, 'zy_teacher_title_category',1);
    }*/

    /**
     * 添加讲师
     * Enter description here ...
     */
    public function addTeacher(){
        $this->_initTabSpecial();
        $this->onsubmit = 'admin.checkTeacher(this)';

        $this->pageKeyList = array (
            'email','uname','password','sex', 'uid','realname','idcard','phone','address','reason','attach_id','identity_id'
        );
        $this->notEmpty = array ('uid','realname','phone','idcard','reason','attach_id','identity_id',);

        //注册配置(添加用户页隐藏审核按钮)
        $regInfo = model('Xdata')->get('admin_Config:register');
        if($regInfo['register_audit'] == 1){
            $this->pageKeyList = array_merge($this->pageKeyList,array('is_audit'));
            $this->opt['is_audit'] = array('1'=>'是','2'=>'否');
        }
        if($regInfo['need_active'] == 1){
            $this->pageKeyList = array_merge($this->pageKeyList,array('is_active'));
            $this->opt['is_active'] = array('1'=>'是','2'=>'否');
        }
        // 列表key值 DOACTION表示操作
        $this->opt['type'] = array('2'=>L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1'=>L('PUBLIC_MALE'),'2'=>L('PUBLIC_FEMALE'));
        $schoolData['prompt'] = '请选择一种方式添加讲师（重新添加用户或者选择用户）';

        $_REQUEST['tabHash'] = 'addTeacher';
        $this->pageTitle['addTeacher']      = '添加讲师';

        if(!is_admin($this->mid)){
            $schoolData = model('User')->where('mhm_id='.$this->school_id)->field('uid,uname')->findALL();
            $this->opt['uid'] = array_column($schoolData,'uname','uid');
        }
        $this->savePostUrl = U ('school/AdminTeacher/doAuthenticate');
         //说明是添加
        $this->displayConfig($schoolData);
    }
    /**
     * 提交添加讲师
     * @return void
     */
    public function doAuthenticate(){
         if($_POST['email'] || $_POST['uname']){
            $user = model('User');
            $map = $user->create();
            // 审核与激活修改
            $map['is_active'] = ($map['is_active'] == 2) ? 0 : 1;
            $map['is_audit']  = ($map['is_audit'] == 2) ? 0 : 1;
            $map['is_init']  = ($map['is_init'] == 2) ? 0 : 1;
            $map['mhm_id']  = $this->school_id;
            //检查map返回值，有表单验证
             $user->addUser($map);
             $uid  = $user->where(array('email'=>$_POST['email'],'uname'=>$_POST['uname']))->getField('uid');
             if(!$uid){$this->error('用户添加失败!');}
             $maps['uid'] = $uid;
             $maps['user_group_id'] = '2';
             $exist = D('user_group_link')->where($maps)->find();
             if(!$exist){
                 D('user_group_link')->add($maps);
                 model('User')->cleanCache($uid);
                 // 清除用户组缓存
                 model('Cache')->rm('user_group_' . $uid);
                 // 清除权限缓存
                 model('Cache')->rm('perm_user_' . $uid);
             }
             $data['uid'] = $uid;
        }else{
             $data['uid']  = intval($_POST['uid']);
        }
        $data['user_verified_category_id'] = intval(6);
        $data['usergroup_id'] = intval(3);

        $data['realname'] = t($_POST['realname']);
        $data['idcard'] = t($_POST['idcard']);
        $data['phone'] = t($_POST['phone']);
        $data['reason'] = t($_POST['reason']);
        $data['address'] = t($_POST['address']);
        $data['identity_id'] = t($_POST['identity_id_ids']);
        $data['attach_id']   = t($_POST['attach_id_ids']);
        $data["mhm_id"] = $this->school_id;
        $data['is_school'] = 1;

        $Regx1 = '/^[0-9]*$/';
        $Regx2 = '/^[A-Za-z0-9]*$/';
        $Regx3 = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';

        $teacher = M('zy_teacher')->where('uid='.$data['uid'])->find();
        if(!$data['uid']){$this->error('请选择或添加用户');}
        if(!is_admin($this->mid)){
            $mhm_id = model('User')->where('uid='.$data['uid'])->getField('mhm_id');
            if($this->school_id != $mhm_id){$this->error('请选择本机构下的用户');}
        }
        if($teacher){$this->error('该用户已经是讲师，请重新选择用户');}
        if(strlen($data['realname'])==0){$this->error('真实姓名不能为空');}
        if(strlen($data['idcard'])==0){$this->error('身份证号码不能为空');}
        if(strlen($data['phone'])==0){$this->error('联系电话不能为空');}
        if(strlen($data['reason'])==0){$this->error('认证理由不能为空');}
        if(preg_match($Regx3, $data['realname'])==0 || strlen($data['realname'])>30){$this->error('请输入正确的姓名格式');}
        if(preg_match($Regx2, $data['idcard'])==0 || preg_match($Regx1, substr($data['idcard'],0,17))==0 || strlen($data['idcard'])!==18){$this->error('请输入正确的身份证号码');}
        if(strlen($data['phone']) !== 11 || preg_match($Regx1, $data['phone'])==0){$this->error('请输入正确的联系电话格式');}
        preg_match_all('/./us', $data['reason'], $matchs);   //一个汉字也为一个字符
        if(count($matchs[0])>140){$this->error('认证理由不能超过140个字符');}
        if(!$data['identity_id']){$this->error('请上传身份认证附件');}
        if(!$data['attach_id']){$this->error('请上传资格认证附件');}

        $teacher_verified = M('user_verified')->where('uid='.$data['uid'])->count();
        if($teacher_verified){
            $data['verified'] = '0';
            $res = M('user_verified')->where('uid='.$data['uid'])->save($data);
        }else{
            $res = M('user_verified')->add($data);
        }
        //$id  = M('user_verified')->getLastInsID();
        //$uid = M('user_verified')->where('id='.$id)->getField('uid');

        if($res){
            $this->success("添加讲师成功,请等待平台审核");
            /*$id = M('user_verified')->where('uid='.$uid)->getField('id');
            $result = $this->doVerify($id);
            if($result == "true"){
                $this->success("添加讲师成功,请等待平台审核");
            }*/
        }else{
            $this->error("对不起，添加讲师失败！");
        }
    }
    /*
     * 讲师认证
     * @return true
     * */
    /*public function doVerify($id){
        $user_group = D('user_verified')->where('id='.$id)->find();
        
        if(!is_admin($this->mid)){
            $data['mhm_id'] = $this->school_id;
            M('user') ->where('uid ='.$user_group['uid']) -> save($data);
        }
        //添加成为老师
        if($user_group["user_verified_category_id"]==6){
            $data["uid"]   = $user_group['uid'];
            $data["name"]  = $user_group['realname'];
            $data["mhm_id"]= $this->school_id;
            $data["ctime"] = time();
            $data['identification'] = date(md).mt_rand(1000,9999).$data['uid'];
            M("zy_teacher")->add($data);
        }
        $data['verified'] = "1";
        D('user_verified')->where('id='.$id)->save($data);
        //结束
        $maps['uid'] = $user_group['uid'];
        $maps['user_group_id'] = $user_group['usergroup_id'];
        $exist = D('user_group_link')->where($maps)->find();
        if ( !$exist ){
            D('user_group_link')->add($maps);
            // 清除用户组缓存
            model ( 'Cache' )->rm ('user_group_'.$user_group['uid']);
            // 清除权限缓存
            model('Cache')->rm('perm_user_'.$user_group['uid']);
            // 删除微博信息
            $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
            model('Feed')->cleanCache($feed_ids);

            model('Notify')->sendNotify($user_group['uid'],'admin_user_doverify_ok');
        }
        return true;
    }*/
    
    /**
     * 修改讲师
     * Enter description here ...
     */
    public function editTeacher(){
        $id = intval($_GET['id']);

        $this->_initTabSpecial();
        $this->onsubmit = 'admin.checkTeacher(this)';
        $this->opt['teach_way'] = array('1' => "线上授课", '2' => "线下授课", '3' => "线上/线下均可");
        $this->pageKeyList = array(
            'uid', 'name', 'category', 'school_title', 'title', 'head_id', 'teacher_age', 'high_school', 'graduate_school', 'label',
            'teach_way', 'online_price', 'offline_price', 'inro', 'details', 'teach_evaluation', 'is_best', 'best_sort'
        );
        $this->notEmpty = array('uid', 'name', 'category', 'mhm_id', 'teacher_age', 'high_school', 'graduate_school', 'teach_evaluation', 'label', 'teach_way', 'inro', 'details', 'title', 'head_id',);
        $treeData = M('zy_teacher_title_category')->findALL();
        $this->opt['title'] = array('0' => '请选择')+array_column($treeData, 'title', 'zy_teacher_title_category_id');
        $this->opt['is_best'] = array('0' => '否', '1' => '是');

        $this->savePostUrl = U('school/AdminTeacher/doAddTeacher', 'type=save&id=' . $id);
        $zyTeacher = D('ZyTeacher','classroom')->where('id=' . $id)->find();
        $zyTeacher['school_title'] = $val['school_title'] = model('School')->where('id='.$zyTeacher['mhm_id'])->getField('title');
        $this->pageTitle['addTeacher'] = '编辑讲师-' . $zyTeacher['name'];

        ob_start();
        echo W('CategoryLevel', array('table' => 'zy_currency_category', 'id' => 'teacher_level', 'default' => trim($zyTeacher['fullcategorypath'], ',')));
        $output = ob_get_contents();
        ob_end_clean();
        $zyTeacher['category'] = $output;
        //说明是编辑
        $this->displayConfig($zyTeacher);
    }

    /**
     * 处理修改讲师
     * Enter description here ...
     */
    public function doAddTeacher(){
        $id=intval($_GET['id']);
        $type= t($_GET['type']);

        $myAdminLevelhidden 		= getCsvInt(t($_POST['teacher_levelhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath 			= explode(',',$_POST['teacher_levelhidden']);
        $category 					= array_pop($fullcategorypath);
        $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况

        //要添加的数据
        $data = array(
            'name'=>t($_POST['name']),
            'teacher_category'=> '0' ? array_pop($fullcategorypath) : $category,
            'fullcategorypath'=> $myAdminLevelhidden,//分类全路径
            'inro'=>t($_POST['inro']),
            'details'=>t($_POST['details']),
            'head_id'=>intval($_POST['head_id']),
            'title'=>t($_POST['title']),
            'mhm_id'=>intval($this->school_id),
            'ctime'=>time(),
            'teacher_age'=>t($_POST['teacher_age']),
            'label'=>t($_POST['label']),
            'high_school'=>t($_POST['high_school']),
            'graduate_school'=>t($_POST['graduate_school']),
            'teach_evaluation'=>t($_POST['teach_evaluation']),
            'teach_way'=>t($_POST['teach_way']),
            'uid'=>intval($_POST['uid']),
            'offline_price'=>floatval($_POST['offline_price']),
            'online_price'=>floatval($_POST['online_price'])
        );
        //数据验证
        if(!$data ['name']){$this->error('讲师姓名不能为空!');}
        if(empty($_POST['teacher_levelhidden'])){$this->error("请选择分类");}
        if(!$data ['inro']){$this->error('讲师简介不能为空');}
        if(!$data ['title']){$this->error('请选择讲师头衔');}
        if(!$data ['head_id']){$this->error('请上传讲师的照片!');}
        if(!is_numeric($_POST['offline_price'])){$this->error('线下试听价格必须为数字');}
        if(!is_numeric($_POST['online_price'])){$this->error ('在线试听价格必须为数字');}
        if($type == 'add'){
            if(M("zy_teacher")->where("uid=".intval($_POST['uid']))->find()){
                $this->error('该讲师已被认证过！');
            }
            $data['identification'] = date(md).mt_rand(1000,9999).$data['uid'];
            $res=M('zy_teacher')->add($data);
            if(!$res)$this->error("对不起，添加失败！");
            $this->success("添加讲师成功！");
        }else if($type=='save' && $id){
            $res=M('zy_teacher')->where("id=$id")->save($data);
            if(!$res)$this->error("对不起，修改讲师失败！");
            $this->success("修改讲师成功!");
        }
    }

    /**
     * 上传讲师风采
     * Enter description here ...
     */
    public function addStyle(){
        $id=intval($_GET['id']);
        //如果上传到七牛服务器
        if(getAppConfig('upload_room','basic') == 1 ) {
            //生成上传凭证
            $bucket = getAppConfig('qiniu_Bucket','qiniuyun');
            Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
            $putPolicy = new Qiniu_RS_PutPolicy($bucket);
            $filename="eduline".rand(5,8).time();
            $str = "{$bucket}:{$filename}";
            $entryCode = Qiniu_Encode($str);
            $putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
            $upToken = $putPolicy->Token(null);

            //获取配置上传空间   0本地 1七牛
            $upload_room = getAppConfig('upload_room','basic');
            $this->assign('upload_room' , $upload_room);
            $this->assign("filename" , $filename);
            $this->assign("uptoken" , $upToken);
        }
        $data = array(
            'tid'  => $id,
            'name' => M('zy_teacher')->where( 'id='.$id  )->getField('name')
        );
        $photos = D('ZyTeacherPhotos','classroom')->getPhotosAlbumByTid($id);
        $this->assign($data);
        $this->assign('photos',$photos['data']);
        $this->display();
    }
    /**
     * 处理上传讲师风采
     * Enter description here ...
     */
    public function doAddStyle(){
        $tid=intval($_GET['tid']);
        $photo_id = intval(t($_POST['photo_id']));

        //格式化七牛数据
        $videokey = t($_POST['videokey']);
        //获取上传空间 0本地 1七牛 2阿里云 3又拍云
        if(getAppConfig('upload_room','basic') == 0 ) {
            if( $_POST['attach'][0]) {
                $video_address = getAttachUrlByAttachId( $_POST['attach'][0] );
            } else {
                $video_address = $_POST['video_address'];
            }
        } else {
            $video_address="http://".getAppConfig('qiniu_Domain','qiniuyun')."/".$videokey;
        }

        if($photo_id == 0){
            $photo_id = M('zy_teacher_photos')->where(['tid'=>$tid,'title'=>'默认相册' ])->getField('id');
            if(!$photo_id){

                $data['tid'] = intval($tid);
                $data['title'] = '默认相册';
                $data['ctime'] = time();
                D('ZyTeacherPhotos','classroom')->savePhotoAlbum($data);
            }
        }
        //要添加的数据
        $data['videokey']       = $videokey;
        $data['tid']            = $tid;
        if($_POST['photo_id']){
            $data['photo_id']   = $_POST['photo_id'];
        }else{
            $data['photo_id']       = M('zy_teacher_photos')->where(['tid'=>$tid,'title'=>'默认相册' ])->getField('id');
        }
        $data['title']          = t($_POST['title']);
        $data['type']           = t($_POST['type']);
        $data['cover']          = trim(t($_POST['cover_ids']),"|");

        if($_POST['type'] == 1){$data['resource'] = implode(",", $_POST['attach']);}
        else if($_POST['type'] == 2) {$data['resource'] = $video_address;}
        $res = D('ZyTeacherPhotos','classroom')->addAllSource($data);
        if(!$res)exit(json_encode(array('status'=>'0','info'=>'对不起,上传失败！')));
        exit(json_encode(array('status'=>'1','info'=>'上传成功')));
    }
    /**
     * 添加文章
     * Enter description here ...
     */
    public function addArticle(){
        $id=intval($_GET['id']);
        if(isset($_GET['doadd'])){
            $type = 'add';
        }else{
            $type = 'save';
        }
        $this->_initTabSpecial();
        $this->pageKeyList = array ('tid','name','cover','art_title','article');
        $this->notEmpty = array ('tid','name','cover','art_title', 'article');
        if($type === 'save'){
            //编辑
            $this->savePostUrl = U('school/AdminTeacher/doAddArticle',array('type'=>$type,'id'=>$id));
            $zyArticle = M('zy_teacher_article')->where( 'id='.$id  )->find ();
            $zyArticle['name'] = M('zy_teacher')->where( 'id='.$zyArticle['tid'])->getField('name');
            $this->assign('pageTitle','修改文章--'.$zyArticle['name']);
            $this->displayConfig($zyArticle);
        }else if($type === 'add'){
            //添加
            $data = array(
                'tid'  => $id,
                'name' => M('zy_teacher')->where( 'id='.$id  )->getField('name')
            );
            $this->savePostUrl = U ('school/AdminTeacher/doAddArticle',array('type'=>$type,'id'=>$id));
            $this->assign('pageTitle','添加文章-'.$data['name']);
            $this->displayConfig($data);
        }

    }
    /**
     * 处理添加/修改文章
     * Enter description here ...
     */
    public function doAddArticle(){
        $type= t($_GET['type']);
        if($type == 'save'){
            $id = intval($_GET['id']);
            $tid = M('zy_teacher_article')->where( 'id='.$id  )->getField('tid');
        }else if($type == 'add'){
            $tid = intval($_GET['id']);
        }
        //要添加的数据
        $data=array(
            'tid' => intval($tid),
            'cover'=>intval($_POST['cover']),
            'art_title'=>t($_POST['art_title']),
            'article'=>$_POST['article'],
            'ctime' => time(),
        );
        //数据验证
        if(!$data ['art_title']){
            $this->error('请上传文章的标题!');
        }
        if(!$data ['article']){
            $this->error('文章内容不能为空');
        }
        if($type == 'add'){
            $res=M('zy_teacher_article')->add($data);
            if(!$res){
                $this->error("对不起，添加失败！");
            }else{
                $this->assign('jumpUrl', U('school/AdminTeacher/Article'));
                $this->success("添加文章成功！");
            }

        }else if($type=='save' && $id){
            $res=M('zy_teacher_article')->where("id=$id")->save($data);
            if(!$res){
                $this->error("对不起，修改文章失败！");
            }else{
                $this->assign('jumpUrl', U('school/AdminTeacher/Article'));
                $this->success("修改文章成功！");
            }
        }

    }

    /**
     * 添加经历
     * Enter description here ...
     */
    public function addDetails(){
        $id=intval($_GET['id']);
        if(isset($_GET['doadd'])){
            $type = 'add';
        }else{
            $type = 'save';
        }
        $this->_initTabSpecial();
        $this->pageKeyList = array ('tid','name','Time','type','title','content');
        $this->notEmpty = array ('tid','name','Time','type','title', 'content');
        if($type === 'save'){
            //编辑
            $this->savePostUrl = U('school/AdminTeacher/doAddDetails',array('type'=>$type,'id'=>$id));
            $zyDetails = M('zy_teacher_details')->where( 'id='.$id  )->find ();
            $zyDetails['name'] = M('zy_teacher')->where( 'id='.$zyDetails['tid'])->getField('name');
            $this->opt['type'] = array('1'=>'过往经历','2'=>'相关案例');
            $this->assign('pageTitle','修改经历--'.$zyDetails['name']);
            $this->displayConfig($zyDetails);
        }else if($type === 'add'){
            //添加
            $data = array(
                'tid'  => $id,
                'name' => M('zy_teacher')->where( 'id='.$id  )->getField('name'),
                'type' => $this->opt['type'] = array('1'=>'过往经历','2'=>'相关案例'),
            );
            $this->savePostUrl = U ('school/AdminTeacher/doAddDetails',array('type'=>$type,'id'=>$id));
            $this->assign('pageTitle','添加经历-'.$data['name']);
            $this->displayConfig($data);
        }
    }
    /**
     * 处理添加/修改经历
     * Enter description here ...
     */
    public function doAddDetails(){
        $type= t($_GET['type']);
        if($type == 'save'){
            $id = intval($_GET['id']);
            $tid = M('zy_teacher_details')->where( 'id='.$id  )->getField('tid');
        }else{
            $tid = intval($_GET['id']);
        }

        //要添加的数据
        $data=array(
            'tid' => intval($tid),
            'Time'=>t($_POST['Time']),
            'type'=>t($_POST['type']),
            'title'=>t($_POST['title']),
            'content'=>t($_POST['content']),
            'ctime' => time(),
        );
        //数据验证
        if(!$data ['Time']){
            $this->error('请上传经历开始时间!');
        }
        if(!$data ['type']){
            $this->error('请选择经历类型!');
        }
        if(!$data ['title']){
            $this->error('请上传经历的标题!');
        }
        if(!$data ['content']){
            $this->error('内容不能为空');
        }
        if($type == 'add'){
            $res=M('zy_teacher_details')->add($data);
            if(!$res)$this->error("对不起，添加失败！");
            $this->success("添加经历成功！");
        }else if($type=='save' && $id){
            $res=M('zy_teacher_details')->where("id=$id")->save($data);
            if(!$res)$this->error("对不起，修改经历失败！");
            $this->success("修改经历成功!");
        }

    }

    /**
     * 讲师课程展示
     * Enter description here ....
     */
    public function courseShow(){
        $id=intval($_GET['id']);
        $_REQUEST['tabHash'] = 'courseShow';
        $this->pageTitle['courseShow'] = '课程展示';
        $this->pageKeyList = array ('video_title','cover','t_price','video_collect_count','video_comment_count','video_question_count','video_note_count','video_score','video_order_count');
        $this->searchKey = array('video_title');
        $map['type'] = 1;
        $map['teacher_id'] = $id;
        $res = M('zy_video')->where($map)->findPage(20);
        foreach($res['data'] as &$value) {
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 ,60)." width='60px' height='60px'>";
        }
        $this->assign('pageTitle','课程展示');
        $this->_listpk = 'id';
        $this->allSelected = true;
        $this->displayList($res);
    }




    /**
     * 禁用/启用 讲师/文章/经历/相册/相册详情
     */
    public function closeTeacher()
    {
        $id = implode(",", $_POST['id']);
        $category = t($_POST['category']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $where = array(
            'id' => array('in', $id)
        );
        $map = array(
            'pic_id' => array('in', $id)
        );
        if($category == 'teacher'){
            $is_del = M('zy_teacher')->where($where)->getField('is_del');
        }else if($category == 'article'){
            $is_del = M('zy_teacher_article')->where($where)->getField('is_del');
        }else if($category == 'details'){
            $is_del = M('zy_teacher_details')->where($where)->getField('is_del');
        }else if($category == 'style'){
            $is_del = M('zy_teacher_photos')->where($where)->getField('is_del');
        }else if($category == 'photoDeatils'){
            $is_del = M('zy_teacher_photos_data')->where($map)->getField('is_del');
        }

        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }

        if($category == 'teacher'){
            $res = M('zy_teacher')->where($where)->save($data);
        }else if($category == 'article'){
            $res = M('zy_teacher_article')->where($where)->save($data);
        }else if($category == 'details'){
            $res = M('zy_teacher_details')->where($where)->save($data);
        }else if($category == 'style'){
            $res = M('zy_teacher_photos')->where($where)->save($data);
            $res = M('zy_teacher_photos_data')->where('photo_id='.$id)->save($data);
        }else if($category == 'photoDeatils'){
            $res = M('zy_teacher_photos_data')->where($map)->save($data);
        }

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

    /*
    *批量禁用 讲师文章/经历/风采
    */
    public function disableTeacher(){
        $ids = implode(",",$_POST['ids']);
        $category = t($_POST['category']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );
        $map = array(
            'pic_id'=>array('in',$ids)
        );
        if($category == 'article'){
            $is_del = M('zy_teacher_article')->where($where)->getField('is_del');
        }else if($category == 'details'){
            $is_del = M('zy_teacher_details')->where($where)->getField('is_del');
        }else if($category == 'style'){
            $is_del = M('zy_teacher_photos')->where($where)->getField('is_del');
        }else if($category == 'photoDeatils'){
            $is_del = M('zy_teacher_photos_data')->where($map)->getField('is_del');
        }

        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        if($category == 'teacher'){
            $data['is_del'] = 2;
            $res = M('zy_teacher')->where($where)->save($data);
        }else if($category == 'article'){
            $res = M('zy_teacher_article')->where($where)->save($data);
        }else if($category == 'details'){
            $res = M('zy_teacher_details')->where($where)->save($data);
        }else if($category == 'style'){
            $res = M('zy_teacher_photos')->where($where)->save($data);
            $photo_id = explode(",", $ids);
            foreach ($photo_id as $key => $val) {
                $res = M('zy_teacher_photos_data')->where('photo_id='.$val)->save($data);
            }
        }else if($category == 'photoDeatils'){
            $res = M('zy_teacher_photos_data')->where($map)->save($data);
        }

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

    /*
    *彻底删除 讲师文章
    */
    public function delTeacher(){
        $ids = implode(",",$_POST['ids']);
		$category = t($_POST['category']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
		if($category == 'article'){
			$res = M('zy_teacher_article')->where($where)->delete();
		}else if($category == 'details'){
			$res = M('zy_teacher_details')->where($where)->delete();
		}else if($category == 'style'){
			$res = M('zy_teacher_photos')->where($where)->delete();
		}
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

}
?>