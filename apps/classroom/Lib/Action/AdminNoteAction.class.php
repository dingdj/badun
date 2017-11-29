<?php
/**
 * 笔记管理配置
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminNoteAction extends AdministratorAction
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
     * 笔记列表管理
     * @return void
     */
    public function index()
    {
        // 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array(
            'id', 'uid', 'note_title', 'note_description', 'type', 'parent_id', 'oid', 'is_open',
            'note_help_count', 'note_comment_count', 'note_collect_count', 'note_source', 'ctime', 'DOACTION'
        );

        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.delNoteAllEdit('delnote')");

        $this->searchKey = array('id', 'uid', 'type', 'is_open', 'note_title',  'note_description',array('ctime', 'ctime1'));
        $this->opt['type'] = array('0' => '不限', '1' => '课程', '2' => '班级');
        $this->opt['is_open'] = array('0' => '不限', '1' => '不公开', '2' => '公开');

        $list = model('ZyNote')->getNoteList('20', array('parent_id' => array('eq', 0)));

        foreach ($list['data'] as $key => $value) {
            $list['data'][$key]['uid'] = getUserSpace($value['uid'], null, '_blank');
            if ($value['type'] == 1) {
                $url = U('classroom/Video/view', array('id' => $value['oid']));
            } else {
                $url = U('classroom/Album/view', array('id' => $value['oid']));
            }
            $list['data'][$key]['note_title'] = '<a href="' . $url . '" target="_bank">' . $value['note_title'] . '</a>';
            $list['data'][$key]['note_description'] = $value['note_description'];

            if ($value['type'] == 1) {
                $list['data'][$key]['oid'] = getVideoNameForID($value['oid']);
            } else if ($value['type'] == 2) {
                $list['data'][$key]['oid'] = getAlbumNameForID($value['oid']);
            } else {
                $list['data'][$key]['oid'] = '不存在';
            }
//            $list['data'][$key]['oid'] = '<div style="width:200px;height:30px;overflow:hidden;">' . $list['data'][$key]['oid'] . '</div>';
            $video_title = D('ZyVideo','classroom')->getVideoTitleById($value['oid']);
            $url = U('classroom/Video/view', array('id' => $value['oid']));
            $list['data'][$key]['oid'] = getQuickLink($url,$video_title,"未知课程");

            $list['data'][$key]['type'] = ($value['type'] == 1) ? '课程' : '班级';
           if( $list['data'][$key]['is_open'] == 0)
           {
               $list['data'][$key]['is_open'] ="不";
           }
           else
           {
               $list['data'][$key]['is_open'] ="是";
           }
            $list['data'][$key]['ctime'] = date('Y-m-d', $value['ctime']);
            if($value['is_del'] == 1) {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.mzNoteEdit(' . $value['id'] . ',\'closenote\',\'显示\',\'笔记\','.$value['uid'].',' ."'{$value['note_title']}'".','.$value['ctime'].');">显示</a>';
            }
            else {
                $list['data'][$key]['DOACTION'] = '<a href="javascript:admin.mzNoteEdit(' . $value['id'] . ',\'closenote\',\'隐藏\',\'笔记\','.$value['uid'].',' ."'{$value['note_title']}'".','.$value['ctime'].');">隐藏</a>';
            }
            $list['data'][$key]['DOACTION'] .= ' | <a href="javascript:admin.delNoteEdit('.$value['id'].',\'delnote\','.$value['uid'].',' ."'{$value['note_title']}'".','.$value['ctime'].');">删除</a>';
            if($value['type'] == 1)
            {
                $list['data'][$key]['DOACTION'] .= ' | <a href="'.U('classroom/AdminVideo/noteCommentVideo',array('oid'=>$value['oid'],'type'=>$value['type'],'id'=>$value['id'],'tabHash'=>'noteCommentVideo')).'">查看评论</a>';
            }
            else
            {
            $list['data'][$key]['DOACTION'] .= ' | <a href="'.U('classroom/AdminAlbum/noteCommentAlbum',array('oid'=>$value['oid'],'type'=>$value['type'],'id'=>$value['id'],'tabHash'=>'noteCommentAlbum')).'">查看评论</a>';
        }}
        $this->assign('pageTitle', '笔记管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        $this->displayList($list);
    }

    /**
     * 删除笔记
     * @return void
     */
    public function delnote()
    {
        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $return = model('ZyNote')->doDeleteNote($_POST['ids']);

        if ($return['status'] == 1) {
            $return['data'] = '操作成功';
        } elseif ($return['status'] === false) {
            $return['data'] = L('PUBLIC_DELETE_FAIL');
        } elseif ($return['status'] == 100003) {
            $return['data'] = '请选择要删除的内容';
        } else {
            $return['data'] = '操作错误';
        }
        echo json_encode($return);
        exit();
    }


    /**
     * 显示/隐藏笔记
     */
    public function closenote()
    {
        $msg =array();
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('zy_note')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('zy_note')->where($where)->save($data);

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


