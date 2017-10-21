<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminLiveMountAction  extends AdministratorAction
{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '课程挂载管理';
        $this->pageTitle['action']       = '待审核课程挂载管理';

        $this->pageTab[] = array('title'=>'挂载课程列表','tabHash'=>'index','url'=>U('live/AdminLiveMount/index'));
        $this->pageTab[] = array('title'=>'待审核挂载课程列表','tabHash'=>'action','url'=>U('live/AdminLiveMount/action'));

        parent::_initialize();
    }
    /**
     * 课程挂载列表管理
     */
    public function index(){
        $this->pageKeyList = array('id','video_title','cover','v_price','t_price','user_title','mhm_name',
            'video_collect_count','video_score','video_order_count','mount_status','best','atime','DOACTION');
        $this->pageButton[] =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->searchKey = array('id','video_title','uid','teacher_id','mhm_id');
        $this->searchPostUrl = U('live/AdminLiveMount/index',array('tabHash'=>'index'));
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school;

        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['teacher_id'] && $map['teacher_id'] = intval($_POST['teacher_id']);
            $_POST['video_title'] && $map['video_title'] = array('like', '%'.t($_POST['video_title']).'%');
            $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
            $_POST['mhm_id'] && $map['mhm_id'] = intval($_POST['mhm_id']);
        }

        $map['is_mount']    = ['neq',0];

        $list = $this->_getList($map);

        $this->displayList($list);
    }

    /**
     * 待审核直播课程挂载列表管理
     */
    public function action(){
        $this->pageKeyList = array('id','video_title','cover','v_price','t_price','user_title','mhm_name',
            'video_collect_count','video_score','video_order_count','mount_status','best','atime','DOACTION');
        $this->pageButton[] =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->searchKey = array('id','video_title','uid','teacher_id','mhm_id');
        $this->searchPostUrl = U('live/AdminLiveMount/action',array('tabHash'=>'index'));
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school;

        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['teacher_id'] && $map['teacher_id'] = intval($_POST['teacher_id']);
            $_POST['video_title'] && $map['video_title'] = array('like', '%'.t($_POST['video_title']).'%');
            $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
            $_POST['mhm_id'] && $map['mhm_id'] = intval($_POST['mhm_id']);
        }

        $map['is_mount']    = 2;

        $list = $this->_getList($map);

        $this->displayList($list);
    }

    /**
     * 挂载直播课程
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
        $video =M('zy_video');
        $result = $video ->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'挂载失败',0);
            return;
        }

        $this->ajaxReturn(null,'挂载成功',1);
    }

    /**
     * 取消挂载直播课程
     */
    public function closeMount()
    {
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['id'] = $id;
        $data['is_mount'] = 0;
        $video =M('zy_video');
        $result = $video ->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'取消挂载失败',0);
            return;
        }

        $this->ajaxReturn(null,'取消挂载成功',1);
    }

    /***
     * @param $type
     * @param $limit
     * @param $order
     * @return mixed
     * 评论列表
     */
    private function _getList($map = [],$type,$limit,$order){
        $map['type']        = 2;
        $map['is_del']      = 0; //搜索非隐藏内容
        $map['is_activity'] = 1;
        $map ['_string']    = ' (is_charge != 1)  AND ( t_price != 0) ';
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['vid'] && $map['vid'] = intval(t($_POST['vid']));
            $_POST ['uid'] && $map ['uid'] = array('in', t((string)$_POST ['uid']));
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
        }
        $list = M('zy_video')->where($map)->order('ctime desc,id desc')->findPage(20);
        foreach ($list['data'] as &$value){
            $value['video_title'] = msubstr($value['video_title'],0,20);
            $url = U('live/Index/view', array('id' => $value['id']));
            $value['video_title'] = getQuickLink($url ,$value['video_title'] ,"未知直播");
            $value['user_title']  = getUserSpace($value['uid'], null, '_blank');
            $school_info = M('school')->where('id = '.$value['mhm_id'])->field('title,doadmin')->find();
            $value['mhm_name']    = getQuickLink(getDomain($school_info['doadmin']),$school_info['title'],'未知机构');
//            $value['activity']    = $value['is_activity'] == '1' ? '<span style="color:green">已审核</span>' : '<span style="color:red">未审核</span>';
            $value['best']        = $value['is_best'] == '1' ? '<span style="color:green">是</span>' : '<span style="color:red">否</span>';
            $teacher_name = M('zy_teacher')->where(array('id'=>$value['teacher_id']))->getField('name');
            $value['atime'] = date("Y-m-d H:i:s", $value['atime']);
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 ,60)." width='60px' height='60px'>";

//            $value['DOACTION'] = '<a href=" '.U('live/AdminLiveMount/lesson',array('vid'=>$value['id'])).' ">课时管理</a> | ';
//            $value['DOACTION'] .= '<a href=" '.U('live/AdminLiveMount/learn',array('uid'=>$value['uid'],'id'=>$value['id'])).' ">学习记录</a> | ';
//            //获取章节
//            $video_section_data = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0 , $value['id']);
//            $s_id = reset(reset($video_section_data)['child'])['id'];
//            $value['DOACTION'] .= '<a target="_blank" href=" '.U('live/Video/watch',array('id'=>$value['id'],'s_id'=>$s_id)).' ">查看</a> | ';
//            $value['DOACTION'] .= '<a href="'.U('live/AdminLiveMount/askVideo',array('tabHash'=>'askVideo','id'=>$value['id'])).'">提问</a> | ';
//            $value['DOACTION'] .= '<a href="'.U('live/AdminLiveMount/noteVideo',array('tabHash'=>'noteVideo','id'=>$value['id'])).'">笔记</a> | ';
//            $value['DOACTION'] .= '<a href="'.U('live/AdminLiveMount/reviewVideo',array('tabHash'=>'reviewVideo','id'=>$value['id'])).'">评价</a> | ';
//            if( $value['is_del'] == 0 && $value['is_activity'] == '0'){
//                $value['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideo('.$value['id'].',true)">通过审核</a> | ';
//            }
//            $value['DOACTION'] .='<a href="'.U('live/AdminLiveMount/addVideo',array('id'=>$value['id'],'tabHash'=>'editVideo')).'">编辑</a> |';
//
//            if($value['is_del'] == 1) {
//                $value['DOACTION'] .=   '<a onclick="admin.openObject('.$value['id'].',\'Video\','.$value['is_del'].');" href="javascript:void(0)">启用</a> ';
//            } if($value['is_del'] == 0) {
//                $value['DOACTION'] .=   '<a onclick="admin.closeObject('.$value['id'].',\'Video\','.$value['is_del'].');" href="javascript:void(0)">禁用</a> ';
//            }
            if($value['is_mount'] == 1) {
                $value['mount_status'] = '<span onclick="admin.closeMount('.$value['id'].','.$value['is_mount'].');" style="color:green">已挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.closeMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">取消挂载</a>';
            } if($value['is_mount'] == 0) {
                $value['mount_status'] = '<span onclick="admin.openMount('.$value['id'].','.$value['is_mount'].');" style="color:color: rgba(169, 169, 169, 0.6);">未挂载</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">允许挂载</a>';
            }else if($value['is_mount'] == 2) {
                $value['mount_status'] = '<span onclick="admin.openMount('.$value['id'].','.$value['is_mount'].');" style="red">已提交挂载待审核</span>';
                $value['DOACTION'] .=   '<a onclick="admin.openMount('.$value['id'].','.$value['is_mount'].');" href="javascript:void(0)">挂载审核</a> ';
            }
//            $value['DOACTION'] .=    '<a onclick="admin.closeObject('.$value['id'].',\'Video\','.$value['is_del'].');" href="javascript:void(0)"> 删除</a> ';
        }
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $list;
    }

    /**
     *  审核
     */
    public function Mountacivity()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $where = array(
            'id' => array('in', $id)
        );
        $data['is_activity'] = 1;
        $data['atime'] = time();
        $res = M('zy_video_mount')->where($where)->save($data);
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

}