<?php
/**
 * 公告管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class NoticeAction extends AdministratorAction {

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize(){
        $this->pageTitle['index']       = '列表';
        $this->pageTitle['addNotice']   = '添加';

        $this->pageTab[] = array('title'=>$this->pageTitle['index'],'tabHash'=>'index','url'=>U('admin/Notice/index'));
        $this->pageTab[] = array('title'=>$this->pageTitle['addNotice'],'tabHash'=>'addNotice','url'=>U('admin/Notice/addNotice'));

        parent::_initialize();
    }

    /**
     * 公告列表
     * @return void
     */
    public function index(){
        $this->pageTitle['index']       = '列表';
        //页面配置
        $this->pageKeyList = array('id','uid','content','ctime','DOACTION');

        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delSuggest()");

        $this->searchKey = array('uid','content');

        $map = array();
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid']   = array('in', $_POST['uid']);
        }
        if(!empty($_POST['content'])){
            $_POST['content'] = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
            $map['content'] = array('like', "%{$_POST['content']}%");
        }

        //数据列表
        $map['type'] = 1;
        $listData = M('suggest')->where($map)->order('ctime DESC,id DESC')->findPage(20);
        foreach($listData['data'] as $key=>$val){
            $val['ctime']    = friendlyDate($val['ctime']);
            $val['uid']      = getUserSpace($val['uid'], null, '_blank');
            $val['DOACTION'] = '<a href="javascript:;" onClick="admin.editNotice(' . $val ['id'] .')">编辑</a> | <a href="javascript:;" onclick="admin.delNotice('.$val['id'].');">彻底删除</a>';
            $val['content']  = '<div style="width:500px">'.$val['content'].'</div>';
            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }

    /**
     * 添加公告
     */
    public function addNotice(){
        $this->pageTitle['addNotice']       = '添加';
        if(isset($_POST)){
            if(empty($_POST['content'])) {
                $this->error("发布内容不能为空");
            }

            $data['uid']  = $this->mid;
            $data['content']  = t($_POST['content']);
            $data['ctime']   = time();
            $data['type'] = 1;

            $res = M('suggest')->add($data);
            if($res){
                $this->assign ( 'jumpUrl', U ( 'admin/Notice/index' ) );
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'addNotice';

            $this->onsubmit = 'admin.checkNotice(this)';
            $this->pageKeyList   = array('content');
            $this->notEmpty      = array('content');

            $this->savePostUrl = U('admin/Notice/addNotice');
            $this->displayConfig();
        }
    }

    /**
     * 修改公告内容
     */
    public function editNotice(){
        $_REQUEST ['tabHash'] = 'editNotice';

        $this->pageTitle ['editNotice'] = '编辑';
        $id = intval($_REQUEST ['id']);
        $data = M('suggest')->find($id);
        if (! $id || !$data) {
            $this->assign ( 'jumpUrl', U ( 'admin/Notice/index' ) );
            $this->error ( '参数错误' );
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if(empty($_POST['content'])) {
                $this->error("修改后内容不能为空");
            }
            $data['content'] = $_POST['content'];
            if(M('suggest')->save($data)){
                $this->assign ( 'jumpUrl', U ( 'admin/Notice/index' ) );
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }

        $this->pageTab [] = array ('title' => '编辑', 'tabHash' => 'editNotice','url' => U ( 'admin/Notice/editNotice' ));

        $this->pageKeyList = array ('content');

        $this->savePostUrl = U ( 'admin/Notice/editNotice', array ('id' => $id) );
        $this->displayConfig ( $data );
    }
    /**
     * 删除公告
     * @return void
     */
    public function delNotice(){
        if(is_array($_POST['id'])){
            $_POST['id'] = implode(',', $_POST['id']);
        }
        $id = "'".str_replace(array("'", ','), array('', "','"), $_POST['id'])."'";
        $res = M('suggest')->where("id IN($id)")->delete();
        if($res){
            $this->ajaxReturn(null,"删除成功",1);
        }else{
            $this->ajaxReturn(null,"删除失败",0);
        }
    }
}