<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGoodsCommentAction extends AdministratorAction
{


    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 积分列表管理
     */
    public function index(){
        $this->pageTitle['index'] = '评论列表';
        // 管理分页项目
        $this->pageTab[] = array('title' => $this->pageTitle['index'], 'tabHash' => 'index', 'url' => U('mall/AdminGoodsComment/index'));
        $this->pageKeyList = array( 'comment_id','uid','row_id','title','content', 'client_type','ctime','DOACTION');
        $this->pageButton[] = array('title' => '删除评论', 'onclick' => "admin.GoodsComment('','delcomment','删除','评论')");
        $this->pageButton[] = array('title' => '搜索评论', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('comment_id','uid','title', 'content', 'client_type',array('ctime','ctime1'));
        $this->opt['client_type'] = array( '0' => '网站', '1' => '手机网页版','2' =>'android','3' =>'iphone','4' => '不限');
        // 数据的格式化
        $order = 'id desc';
        $list = $this-> _getcommnetList('index',null,$order,20);
        $this->assign('pageTitle', '商品评价管理');
        $this->_listpk = 'comment_id';
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
    private function _getcommnetList($type,$limit,$order){
        if(isset($_POST)) {
            $_POST['comment_id'] && $map['comment_id'] = intval(t($_POST['comment_id']));
            $_POST['content'] && $map['content'] = array('like', '%' . t($_POST['content']) . '%');
            $_POST ['uid'] && $map ['uid'] = array('in', t((string)$_POST ['uid']));
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
                if ($type != '4') {
                    $map['client_type'] = $type;
                }
            }
        }
        $map['table'] = 'event';
        $goods_info = M('Comment')->where($map)-> order("ctime DESC")->findPage($limit);
        foreach($goods_info['data'] as $key => $val) {
            $goods_info['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $goods_info['data'][$key]['content'] = $val['content'];
            $goods_info['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $goods_info['data'][$key]['title'] = M('event')->where(array('id' => $val['row_id']))->getField('title');
            if ($val['client_type'] == 0) {
                $goods_info['data'][$key]['client_type'] = "网站";
            }
            if ($val['client_type'] == 1) {
                $goods_info['data'][$key]['client_type'] = "手机网页版";
            }
            if ($val['client_type'] == 2) {
                $goods_info['data'][$key]['client_type'] = "android";
            }
            if ($val['client_type'] == 3) {
                $goods_info['data'][$key]['client_type'] = "iphone";
            }
            if ($val['is_del'] == 1) {
                $goods_info['data'][$key]['DOACTION'] = '<a href="javascript:admin.GoodsComment(' . $val['comment_id'] . ',\'closecomment\',\'显示\',\'笔记\');">显示</a>';
            } else {
                $goods_info['data'][$key]['DOACTION'] = '<a href="javascript:admin.GoodsComment(' . $val['comment_id'] . ',\'closecomment\',\'隐藏\',\'笔记\');">隐藏</a>';
            }
            $goods_info['data'][$key]['DOACTION'] .= ' | <a href="javascript:admin.GoodsComment(' . $val['comment_id'] . ',\'delcomment\',\'删除\',\'评论\');">删除</a>';
            $goods_info['data'][$key]['DOACTION'] .= ' | <a href="' . U('mall/AdminGoodsComment/GoodsComment', array('uid' => $val['uid'], 'row_id' => $val['row_id'], 'tabHash' => 'GoodsComment')) . '">查看评论</a>';
            if (!empty($_POST['title'])) {
                if (!strstr($goods_info['data'][$key]['title'], $_POST['title'])) {
                    unset($goods_info['data'][$key]);
                }
            }
        }
        $this->assign('pageTitle','商品评价管理');
        $this->_listpk = 'comment_id';
        $this->allSelected = true;
        return $goods_info;
    }

    /**
     * 讨论对应的回复
     */
    public function GoodsComment()
    {
        if (!$_GET['uid']) $this->error('请选择要查看的评论');
        $this->pageTitle['GoodsComment'] = '回复列表';
        $this->pageTab[] = array('title' => '回复列表', 'tabHash' => 'GoodsComment', 'url' => U('mall/AdminGoodsComment/GoodsComment'));
        $this->pageKeyList = array('comment_id', 'uid', 'row_id', 'title', 'content', 'client_type', 'ctime', 'DOACTION');
        $this->pageButton[] = array('title' => '删除回复', 'onclick' => "admin.GoodsComment('','delcomment','删除','评论')");
        $map['to_uid'] = intval($_GET['uid']); //父类id为用户id
        $map['row_id'] = intval($_GET['row_id']);
        $goods_info = M('Comment')->where($map)->findPage(20);
        foreach ($goods_info['data'] as $key => $vo) {
            $goods_info['data'][$key]['ctime'] = date('Y-m-d H:i:s', $vo["ctime"]);
            $goods_info['data'][$key]['content'] = $vo['content'];
            $goods_info['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $goods_info['data'][$key]['title'] = M('event')->where(array('id' => $vo['row_id']))->getField('title');
            if ($vo['client_type'] == 0) {
                $goods_info['data'][$key]['client_type'] = "网站";
            }
            if ($vo['client_type'] == 1) {
                $goods_info['data'][$key]['client_type'] = "手机网页版";
            }
            if ($vo['client_type'] == 2) {
                $goods_info['data'][$key]['client_type'] = "android";
            }
            if ($vo['client_type'] == 3) {
                $goods_info['data'][$key]['client_type'] = "iphone";
            }
            if ($vo['is_del'] == 1) {
                $goods_info['data'][$key]['DOACTION'] = '<a href="javascript:admin.GoodsComment(' . $vo['comment_id'] . ',\'closecomment\',\'显示\',\'笔记\');">显示</a>';
            } else {
                $goods_info['data'][$key]['DOACTION'] = '<a href="javascript:admin.GoodsComment(' . $vo['comment_id'] . ',\'closecomment\',\'隐藏\',\'笔记\');">隐藏</a>';
            }
            $goods_info['data'][$key]['DOACTION'] .= ' | <a href="javascript:admin.GoodsComment(' . $vo['comment_id'] . ',\'delcomment\',\'删除\',\'笔记\');">删除</a>';
        }
        $this->assign('pageTitle','商品评价管理');
            $this->_listpk = 'comment_id';
            $this->allSelected = true;
            $this->displayList($goods_info);
        }






    /**
     * 删除评论
     * @return void
     */
    public function  delcomment()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array('comment_id' => array('in', $id));
        $ret = M('comment')->where($where)->delete();
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


    /**
     * 显示/隐藏评论
     */
    public function closecomment()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $where = array(
            'comment_id' => array('in', $id)
        );
        $is_del = M('comment')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('comment')->where($where)->save($data);
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