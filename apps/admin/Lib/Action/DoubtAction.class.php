<?php
/**
 * 咨询管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class DoubtAction extends AdministratorAction {

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
        $this->pageTitle['index'] = '咨询列表';

        parent::_initialize();
    }

	/**
     *
	 * 咨询管理列表
	 */
	public function index(){
		$this->pageKeyList = array ('id','uid','content','email','phone','ctime','client_type','is_rep','DOACTION');
        // 管理分页项目
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.AskComment('','delcomment','删除','评论')");
        //搜索字段
        $this->searchKey = array('id','uid', 'content', 'client_type','email','phone','is_rep',array('ctime','ctime1'));
        $this->opt['client_type'] = array('0' =>'全部', '1' => '网站', '2' => '手机网页版','3' =>'android','4' =>'iphone');
        $this->opt['is_rep'] = array( '0' => '未回复', '1' => '已回复','2'=>'全部');
        // 数据的格式化
        $order = 'id desc';
        $list = $this-> _getasktList(null,$order,20);
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
    private function _getasktList($limit,$order){
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['phone'] && $map['phone'] = intval($_POST['phone']);
            $_POST['email'] && $map['email'] = $_POST['email'];
            $_POST['content'] && $map['content'] = array('like', '%' . t($_POST['content']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
            $type = intval($_POST['client_type']);
            if (!empty($type)) {
                if ($type != '0') {
                    $map['client_type'] = $type;
                }
            }
            $rep = intval($_POST['is_rep']);
            if ($rep != '2') {
                $map['is_rep'] = $rep;
            }
        }
        $map['to_uid'] = '0';
        $map['to_comment_id'] = '0';
        $list = M('doubt')->where($map) ->findPage($limit);
        foreach($list['data'] as $key => $val) {
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['content'] = $val['content'];
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            if ($val['client_type'] == 0) {
                $list['data'][$key]['client_type'] = "未知";
            }
            if ($val['client_type'] == 1) {
                $list['data'][$key]['client_type'] = "网站";
            }
            if ($val['client_type'] == 2) {
                $list['data'][$key]['client_type'] = "手机网页版";
            }
            if ($val['client_type'] == 3) {
                $list['data'][$key]['client_type'] = "android";
            }
            if ($val['client_type'] == 4) {
                $list['data'][$key]['client_type'] = "iphone";
            }
            if($val['is_rep'] == 1)
            {
                $list['data'][$key]['is_rep'] = "已回复";
            }
            else
            {
                $list['data'][$key]['is_rep'] = "未回复";
            }
            $list['data'][$key]['DOACTION'] .= '  <a href="javascript:admin.AskComment(' . $val['id'] . ',\'delask\',\'删除\',\'咨询\');">删除</a>';
            $list['data'][$key]['DOACTION'] .= ' | <a href="' . U('admin/Doubt/AskComment', array('uid' => $val['uid'], 'comment_id' => $val['id'], 'tabHash' => 'AskComment')) . '">查看回复</a>';
        }
        $this->assign('pageTitle','咨询管理');
        $this->_listpk = 'comment_id';
        $this->allSelected = true;
        return $list;
    }

    /**
     * 讨论对应的回复
     */
    public function AskComment()
    {
        if (!$_GET['uid']) $this->error('请选择要查看的评论');
        $this->pageTitle['GoodsComment'] = '回复列表';
        $this->pageTab[] = array('title' => '回复列表', 'tabHash' => 'GoodsComment', 'url' => U('admin/Doubt/ AskComment'));
        $this->pageKeyList = array ('id','uid','content','email','phone','ctime','DOACTION');
        $this->pageButton[] = array('title' => '删除回复', 'onclick' => "admin.AskComment('','delask','删除','回复')");
        $map['to_uid'] = intval($_GET['uid']); //父类id为用户
        $map['to_comment_id'] = intval($_GET['comment_id']);
        $list = M('doubt')->where($map)->findPage(20);
        foreach ($list ['data'] as $key => $vo) {
            $list ['data'][$key]['ctime'] = date('Y-m-d H:i:s', $vo["ctime"]);
            $list ['data'][$key]['content'] = $vo['content'];
            $list ['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $list ['data'][$key]['DOACTION'] .= ' <a href="javascript:admin.AskComment(' . $vo['id'] . ',\'delask\',\'删除\',\'回复\');">删除</a>';
        }
        $this->assign('pageTitle','咨询管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        $this->displayList($list);
    }



    /**
     * 删除评论
     * @return void
     */
    public function  delask()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $ret = M('doubt')->where($where)->delete();
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