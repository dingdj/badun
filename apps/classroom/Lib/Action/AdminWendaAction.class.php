<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-10-16
 * Time: 下午7:10
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminWendaAction extends AdministratorAction
{
    /**
     * 初始化，初始化页面表头信息
     */
    public function _initialize()
    {
        // 管理标题项目
        $this->pageTitle['index'] = '列表';
        $this->pageTitle['cate'] = '分类';
        // 管理分页项目
        $this->pageTab[] = array('title' => '列表', 'tabHash' => 'index', 'url' => U('classroom/AdminWenda/index'));
        $this->pageTab[] = array('title' => '分类', 'tabHash' => 'cate', 'url' => U('classroom/AdminWenda/cate'));

        parent::_initialize();
    }

    public function index()
    {
        // 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array('id', 'username', 'type','wd_description', 'wd_comment_count', 'wd_browse_count', 'solution_state', 'DOACTION');
        $this->searchKey = array('id', 'uid', 'type','solution_state' ,'wd_description');

        $this->opt['type'] = M('zy_wenda_category')->getField('zy_wenda_category_id,title');
        $this->opt['type'][0] = '全部';
        $this->opt['solution_state'] = array('0' => '全部','1' => '未解决', '2' => '已解决');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.delWendaAll('delWenda')");
        $map = array(
            'is_del' => 0,
        );
        $type = intval(t($_POST['type']));
        if (!empty($type)) {
            $map['type'] = $type;
        }
        $id = intval(t($_POST['id']));
        if (!empty($id)) {
            $map['id'] = $id;
        }
        $wd_title = t($_POST['wd_description']);
        if (!empty($wd_title)) {
            $map['wd_description'] = array('like', "%{$_POST['wd_description']}%");
        }
        $uid = intval(t($_POST['uid']));
        if (!empty($uid)) {
            $map['uid'] = $uid;
        }
        if(isset($_POST['solution_state']))
        {
            if(t($_POST['solution_state']) == 0)
            {
            }
            if(t($_POST['solution_state']) == 1)
            {
                $map['solution_state'] = 0;
            }
            if(t($_POST['solution_state']) == 2)
            {
                $map['solution_state'] = 1;
            }
        }
        $wdlist = D("ZyWenda")->where($map)->order("recommend DESC , ctime DESC")->findPage(20);
        //格式化数据
        foreach ($wdlist['data'] as &$val) {
            $val['type'] = $this->opt['type'][$val['type']];
            $val['username'] = getUserSpace($val['uid'], null, '_blank');;
            if ($val['solution_state'] == '1') {
                $val['solution_state'] = "已解决";
            } else {
                $val['solution_state'] = "未解决";
            }
            $val['wd_description'] = "<a target='_blank' href='" . U('wenda/Index/detail', array('id' => $val['id'])) . "'>" . getShort(t($val['wd_description']), 50) . "</a>";
            //判断是否是置顶内容
            if ($val['recommend'] != 1) {
                $val['DOACTION'] .= "<a href='" . U('classroom/AdminWenda/hotWenda', array('id' => $val['id'])) . "'>置顶</a>";
            } else {
                $val['DOACTION'] .= "<a href='" . U('classroom/AdminWenda/closeHot', array('id' => $val['id'])) . "'>取消置顶</a>";
            }
            //添加删除按钮
			$wd_description =t($val['wd_description']);
            $val['DOACTION'] .= ' | <a href="javascript:admin.delWenda('.$val['id'].',\'delWenda\','.$val['uid'].',' ."'{$wd_description}'".','.$val['ctime'].');">删除问答</a>';
            if ($val['type'] == 1) {
                $val['DOACTION'] .= ' | <a href="' . U('classroom/AdminWenda/wendaCommentVideo', array('oid' => $val['oid'], 'id' => $val['id'], 'tabHash' => 'wendaCommentVideo')) . '">查看回复</a>';
            } else {
                $val['DOACTION'] .= ' | <a href="' . U('classroom/AdminWenda/wendaCommentVideo', array('oid' => $val['oid'], 'id' => $val['id'], 'tabHash' => 'wendaCommentVideo')) . '">查看回复</a>';
            }
        }
        $this->_listpk = 'id';
        $this->displayList($wdlist);
    }

    /**
     * 评价对应的回复
     */
    public function wendaCommentVideo()
    {
        if (!$_GET['id']) $this->error('请选择要查看的评论');
        $this->pageTab[] = array('title' => '评论列表', 'tabHash' => 'wendaCommentVideo', 'url' => U('classroom/AdminWenda/wendaCommentVideo',array('id'=>$_GET['id'])));
        $this->pageTitle['wendaCommentVideo'] = '评论列表';
        $this->pageKeyList = array('id', 'uid','wid','reply_description', 'type','ctime','is_Adoption','DOACTION');
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.delWendafuihuAll('delWendahuifu')");
        $map['wid'] = intval($_GET['id']); //父类id为问题id
        $list = M('zy_wenda_comment')->where(array('wid' => $map['wid']))->findPage(20);
        foreach ($list['data'] as $key => $vo) {
            $list['data'][$key]['id'] = $vo['id'];
            $list['data'][$key]['wid'] = $vo['wid'];
            $list['data'][$key]['reply_description'] = $vo['description'];
            $type = M('zy_wenda')->where(array('id' => $vo['wid']))->getField('type');
            $list['data'][$key]['type'] = M('zy_wenda_category')->where(array('zy_wenda_category_id' => $type))->getField('title');
            $list['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $list['data'][$key]['ctime'] = date("Y-m-d H:i:s",$vo['ctime']);
            if($vo['is_Adoption'] == 0)
            {
                $list['data'][$key]['is_Adoption'] = "未采纳";
            }
            else
            {
                $list['data'][$key]['is_Adoption'] = "采纳" ;
            }
			$wd_description =t($vo['description']);
            $list['data'][$key]['DOACTION'] = '<a href=javascript:admin.delWendahuifu('.$vo['id'].',\'delWendahuifu\','.$vo['uid'].',' ."'{$wd_description}'".','.$vo['ctime'].');>删除回复</a>';
        }
        $this->_listpk = 'id';
        $this->allSelected = true;
        $this->displayList($list);
    }


    //问答分类列表
    public function cate()
    {
        $list = model('CategoryTree')->setTable('zy_wenda_category')->getNetworkList();
        $this->displayTree($list, 'zy_wenda_category', 1);
    }

    /**
     * 置顶
     */
    public function hotWenda()
    {
        $wid = intval($_GET['id']);
        if (empty($wid)) {
            $this->error("此问答不存在！");
        }
        $wdinfo = D('ZyWenda')->where(array('id' => $wid, 'is_del' => 0))->find();
        if (!$wdinfo) {
            $this->error("此问答不存在或已被删除！");
        }
        $data['recommend'] = 1;
        $res = D('ZyWenda')->where(array('id' => $wid))->save($data);
        if ($res !== false) {
            $this->success("置顶成功!");
        } else {
            $this->error("置顶失败！");
        }


    }

    /**
     * 取消置顶
     */
    public function closeHot()
    {
        $wid = intval($_GET['id']);
        if (empty($wid)) {
            $this->error("此问答不存在！");
        }
        $wdinfo = D('ZyWenda')->where(array('id' => $wid, 'is_del' => 0))->find();
        if (!$wdinfo) {
            $this->error("此问答不存在或已被删除！");
        }
        $data['recommend'] = 0;
        $res = D('ZyWenda')->where(array('id' => $wid))->save($data);
        if ($res !== false) {
            $this->success("取消置顶成功!");
        } else {
            $this->error("取消置顶失败！");
        }

    }

    /**
     * 删除问答
     */
    public function delWenda()
    {
        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $ids)
        );
        $data['is_del'] = 1;
        $res = D('ZyWenda')->where($where)->save($data);
        //echo D('ZyWenda')->getLastSql();
        if ($res !== false) {
            $msg['data'] = "删除成功";
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "删除失败!";
            echo json_encode($msg);
        }
    }

    //删除问答回复
    public function delWendahuifu()
    {
        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $ids)
        );
       // $data['is_del'] = 1;
        $res = D('ZyWendaComment')->where($where)->delete();
        //echo D('ZyWenda')->getLastSql();
        if ($res !== false) {
            $msg['data'] = "刪除成功！";
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "删除失败!";
            echo json_encode($msg);
        }
    }
}





?>