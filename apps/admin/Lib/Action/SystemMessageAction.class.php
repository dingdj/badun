<?php
/**
 * 验证码管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class SystemMessageAction extends AdministratorAction {

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
		$this->pageTitle ['index'] = '系统消息列表';

		parent::_initialize();
	}
	
	/**
	 * 系统消息列表
	 */
	public function index(){
		$this->pageKeyList = array ('id', 'uid', 'title','appname', 'body','ctime','is_read','DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.SystemMessage('','delmessage','删除','系统消息')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'title', 'appname','body','is_read',array('ctime','ctime1'));
        $this->opt['is_read'] = array( '0'=>'全部','1' => '未读', '2' => '已读');
        $order = 'stime desc';
      //
        $list = $this-> _getmessageList(null,$order,20);
        $this->assign('pageTitle', '商品评价管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
	}

    /***
     * @param $type
     * @param $limit
     * @param $order
     * @return mixed
     * 评论列表
     */
    private function _getmessageList($limit,$order){
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
           $type = intval($_POST['is_read']);
           if ($type == 1)
           {
                $map['is_read'] = 0;
            }
            if ($type == 2)
            {
                $map['is_read'] = 1;
            }

            $_POST['body'] && $map['body'] = array('like', '%' . t($_POST['body']) . '%');
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST['appname'] && $map['appname'] = array('like', '%' . t($_POST['appname']) . '%');
        }
         $list = M('notify_message')->where($map) ->findPage($limit);
        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            if ($val['is_read'] == 0) {
                $list['data'][$key]['is_read'] = "未读";
            }
            if ($val['is_read'] == 1) {
                $list['data'][$key]['is_read'] = "已读";
            }
            $list['data'][$key]['DOACTION'] = ' <a href="javascript:admin.SystemMessage(' . $val['id'] . ',\'delmessage\',\'删除\',\'系统消息\');">删除</a>';
                }
        $this->assign('SystemMessage','系统消息');
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $list;
    }

    /**
     * 删除评论
     * @return void
     */
    public function  delmessage()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $ret = M('notify_message')->where($where)->delete();
        if ($ret == true) {
            $msg['data'] = '删除成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作错误';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }



    }















