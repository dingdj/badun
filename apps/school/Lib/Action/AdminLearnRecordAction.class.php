<?php
/**
 * 学币列表信息管理控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminLearnRecordAction extends AdministratorAction {
    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();
        $this->pageTab[] = array('title'=>'课程学习记录','tabHash'=>'index','url'=>U('school/AdminLearnRecord/index'));
        $this->pageTab[] = array('title'=>'用户学习记录','tabHash'=>'user','url'=>U('school/AdminLearnRecord/user'));
    }
    
    /**
     * 课程学习记录管理
     * @return void
     */
    public function index(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','video_title','video_order','learn_nums','video_section','learn_time','video_question','DOACTION');
        $this->pageTitle['index'] = '课程学习记录';
        //按钮
//        $this->pageButton[] = array('title'=>'删除记录','onclick'=>"admin.delLearns()");
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('id','video_title');
        $this->searchPostUrl = U('school/AdminLearnRecord/index', array('tabHash'=>'index'));
        $this->allSelected = false;

        if(!empty($_POST['id'])){
            $map['id'] = intval($_POST['id']);
        }
        if(!empty($_POST['video_title'])){
            $map['video_title'] = ['like', '%' . t($_POST['video_title']) . '%'];
        }
        $map['is_del'] = 0;
        $map['type'] = 1;
        if(is_school($this->mid)){
            $map['mhm_id'] = is_school($this->mid);
        }
        $list = D('LearnRecord','classroom')->getList($map,1);
        foreach($list['data'] as &$val){
            $val['video_order']    = $val['video_order_count'];
            $val['video_question'] = $val['video_question_count'];
            $url = U('classroom/Video/view', array('id' => $val['id']));
            $val['video_title'] = getQuickLink($url,$val['video_title'],"未知课程");
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/learn',array('vid'=>$val['id'])).' ">查看详情</a>';
        }
        $this->displayList($list);
    }

    //学习记录
    public function user(){
        $_REQUEST['tabHash'] = 'user';
//        $this->pageButton[] = array('title'=>'删除记录','onclick'=>"admin.delLearns()");
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageTitle['user'] = '用户学习记录';
        $this->pageKeyList = array('uid','uname','course_order','learn_time','question','answers','topics','exams','DOACTION');

        $this->searchKey    = array('uid','uname');
        $this->searchPostUrl = U('school/AdminLearnRecord/user', array('tabHash'=>'user'));
        $this->allSelected = false;

        if(!empty($_POST['uid'])){
            $map['uid'] = intval($_POST['uid']);
        }
        if(!empty($_POST['uname'])){
            $map['uname'] = ['like', '%' . t($_POST['uname']) . '%'];
        }
        $map['is_del'] = 0;
        if(is_school($this->mid)){
            $map['mhm_id'] = is_school($this->mid);
        }
        $list = D('LearnRecord','classroom')->getList($map,2);
        foreach($list['data'] as &$val){
            $val['uname'] = getUserSpace($val['uid'], null, '_blank');
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/learn',array('uid'=>$val['uid'])).' ">学习详情</a> | ';
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/question',array('uid'=>$val['uid'])).' ">提问详情</a> | ';
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/answer',array('uid'=>$val['uid'])).' ">回答详情</a> | ';
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/topic',array('uid'=>$val['uid'])).' ">发帖详情</a> | ';
            $val['DOACTION'] .= '<a href=" '.U('school/AdminLearnRecord/userExam',array('uid'=>$val['uid'])).' ">考试详情</a>';
        }
        $this->displayList($list);
    }

    //删除 学习记录
    public function delLearn(){
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $where = array(
            'id' => array('in', $id)
        );

        $type = t($_POST['type']);
        if($type == 'learn'){
            $data['is_del'] = 2;
            $res = M('learn_record')->where($where)->save($data);
        }else if($type == 'question'){
            $res =  model('ZyQuestion')->doDeleteQuestion($id);
        }else if($type == 'answer'){
            $res = M('zy_wenda_comment')->where($where)->delete();
        }else if($type == 'topic'){
            $data['is_del'] = 1;
            $res = D('Topic','group')->where($where)->save($data);
        }else if($type == 'userExam'){
            $data['user_exam_is_del'] = 1;
            unset($where);
            $where = array(
                'user_exam_id' => array('in', $id)
            );
            $res = M('ex_user_exam')->where($where)->save($data);
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

    //学习记录详情
    public function learn($limit=20){
        $_REQUEST['tabHash'] = 'learn';
        $id  = intval( $_GET['vid'] );
        $video_title = M('zy_video')->where('id='.$id)->getField('video_title');
        $uid = intval( $_GET['uid'] );
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLearns('learn')");
        $this->pageKeyList  = array('id','uname','video_title','section_title','time','ctime');
        $map['is_del'] = ['neq',2] ;

        if($id){
            $map['vid'] = $id ;
        }else{
            $map['uid'] = $uid ;
        }
        $learn = M('learn_record')->where($map)->order("ctime DESC")->findPage($limit);
        foreach($learn['data'] as &$val){
            if($val['time'] == 0){
                $val['time'] = '<span style="color:green;">已完成</span>';
            }
            $val['ctime'] = date('Y-m-d H:i:s',$val['ctime']) ;
            $val['uname']  = getUserSpace($val['uid'], null, '_blank');
            $url = U('classroom/Video/view', array('id' => $val['vid']));
            $val['video_title'] = M('zy_video')->where('id='.$val['vid'])->getField("video_title") ?: "<sapn style='color:red;'>课程数据有误</span>";
            $val['video_title'] = getQuickLink($url,$video_title,"未知课程");
            $val['section_title'] = M('zy_video_section')->where(array('zy_video_section_id'=>$val['sid']))->getField('title');
        }
        unset($val);
        $this->_listpk = 'id';
        if($id){
            $this->assign('pageTitle','学习记录--'.$video_title);
        }else{
            $this->assign('pageTitle','学习记录--'.getUserName($uid));
        }
        $this->displayList($learn);
    }

    //提问详情
    public function question($limit=20){
        $_REQUEST['tabHash'] = 'question';
        $uid = intval( $_GET['uid'] );
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLearns('question')");
        $this->pageKeyList = array(
            'id','uid','qst_title','qst_description','type','oid',
            'qst_help_count','qst_comment_count','qst_source','ctime'
        );

        $list = D('ZyQuestion','classroom')->getQuestionList($limit,array('uid'=>$uid,'parent_id'=>['eq',0]));
        foreach($list['data'] as $key=>$value){
            $list['data'][$key]['uid']      = getUserSpace($value['uid'], null, '_blank');
            if($value['type']==1){
                $url = U('school/Video/view', array('id'=>$value['oid']));
            }else{
                $url = U('school/Album/view', array('id'=>$value['oid']));
            }
            $list['data'][$key]['qst_title']  = '<div style="width:200px;height:30px;overflow:hidden;"><a href="'.$url.'" target="_bank">'.$value['qst_title'].'</a></div>';
            $list['data'][$key]['qst_description']  = '<div style="width:200px;height:30px;overflow:hidden;">'.$value['qst_description'].'</div>';

            if($value['type']==1){
                $list['data'][$key]['oid']  = getVideoNameForID($value['oid']);
                $url = U('classroom/Video/view', array('id' => $value['oid']));
                $type = "未知课程";
            }else if($value['type']==2){
                $list['data'][$key]['oid']  = getAlbumNameForID($value['oid']);
                $url = U('classroom/Album/view', array('id' => $value['oid']));
                $type = "未知班级";
            }else{
                $list['data'][$key]['oid']  = '不存在';
            }
            $list['data'][$key]['oid'] = getQuickLink($url, $list['data'][$key]['oid'],$type);

//            $list['data'][$key]['oid'] = '<div style="width:160px;height:30px;overflow:hidden;">'.$list['data'][$key]['oid'].'</div>';
            $list['data'][$key]['type']     = ($value['type']==1)?'课程':'班级';
            $list['data'][$key]['ctime']    = date('Y-m-d',$value['ctime']);
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle','提问详情-'.getUserName($uid));
        $this->displayList($list);
    }

    //回答详情
    public function answer($limit=20){
        $_REQUEST['tabHash'] = 'answer';
        $uid = intval( $_GET['uid'] );
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLearns('answer')");
        $this->pageKeyList = array('id', 'uid','wid','reply_description', 'type', 'is_Adoption');

        $list = M('zy_wenda_comment')->where(array('uid' => $uid))->findPage($limit);
        foreach ($list['data'] as $key => $vo) {
            $list['data'][$key]['id'] = $vo['id'];
            $list['data'][$key]['wid'] = $vo['wid'];
            $list['data'][$key]['reply_description'] = $vo['description'];
            $type = M('zy_wenda')->where(array('id' => $vo['wid']))->getField('type');
            $list['data'][$key]['type'] = M('zy_wenda_category')->where(array('zy_wenda_category_id' => $type))->getField('title');
            $list['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            if($vo['is_Adoption'] == 0)
            {
                $list['data'][$key]['is_Adoption'] = "未采纳";
            }
            else
            {
                $list['data'][$key]['is_Adoption'] = "采纳" ;
            }
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle','学习记录--'.getUserName($uid));
        $this->displayList($list);
    }

    //发帖详情
    public function topic($limit=20){
        $_REQUEST['tabHash'] = 'topic';
        $uid = intval( $_GET['uid'] );
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLearns('topic')");
        $this->pageKeyList  = array('id','title','gid','uname','viewcount','replycount','addtime');
        $map['is_del'] = ['neq',2] ;

        $condition[] = 'uid='.$uid;
        $order = 'addtime DESC';
        $list = D('Topic','group')->getTopicList(1, $condition, null, $order, $limit, 0);
        foreach($list['data'] as &$val){
            $val['addtime'] = date('Y-m-d H:i:s',$val['addtime']) ;
            $val['uname']  = getUserSpace($val['uid'], null, '_blank');
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle','发帖详情--'.getUserName($uid));
        $this->displayList($list);
    }

    //考试详情
    public function userExam($limit=20){
        $_REQUEST['tabHash'] = 'learn';
        $id  = intval( $_GET['vid'] );
        $uid = intval( $_GET['uid'] );
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delLearns('userExam')");
        $this->pageKeyList = array('user_exam_id','exam_name','paper_name','paper_point','exam_passing_grade','user_exam_number','user_total_date','user_exam_score','user_right_count','user_error_count','user_exam_time');

        $map['is_del'] = ['neq',2] ;

        $model=M();
        $list = $model->table(C('DB_PREFIX').'ex_paper p,'.C('DB_PREFIX').'ex_exam e,'.C('DB_PREFIX').'ex_user_exam ue' )->where('p.paper_id = ue.user_paper and e.exam_id=ue.user_exam and user_exam_is_del=0 and user_id='.$uid)->field('paper_name,paper_point,exam_name,exam_passing_grade,user_exam_id,user_id,user_exam,user_paper,user_exam_number,user_exam_time,user_exam_score,user_total_date,user_right_count,user_error_count,user_exam_is_del')->order('ue.user_exam_id')->findPage($limit);
        foreach ($list['data'] as $key => $value){
            $list['data'][$key]['exam_name'] = msubstr($value['exam_name'],0,30);
            $url = U('exam/Index/index');
            $list['data'][$key]['exam_name'] = getQuickLink($url,$list['data'][$key]['exam_name'],"未知考试");
            $list['data'][$key]['paper_name'] = msubstr($value['paper_name'],0,30);
            $list['data'][$key]['user_exam_time'] = date("Y-m-d H:i:s",$value['user_exam_time']);
            $list['data'][$key]['user_exam_time'] = date("Y-m-d H:i:s",$value['user_exam_time']);
            $list['data'][$key]['user_exam_number'] = "第<font style='color:#34876f;'>".$value['user_exam_number']."</font>次";
        }
        unset($val);
        $this->_listpk = 'user_exam_id';
        $this->assign('pageTitle','考试详情--'.getUserName($uid));
        $this->displayList($list);
    }
}