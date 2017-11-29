<?php
/**
 * 考试系统后台控制器
 * 考点管理
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
class AdminPointAction extends AdministratorAction
{
    protected $mod;
    /**
     * 初始化
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->allSelected = false;
        $this->mod                = D('ExamsPoint', 'exams');
        $this->pageTitle['index'] = '列表';
        $this->pageTitle['add']   = '添加';
        $this->pageTitle['edit']  = '编辑';
        $this->pageTab[]          = array('title' => '列表', 'tabHash' => 'index', 'url' => U('exams/AdminPoint/index'));
        $this->pageTab[]          = array('title' => '添加', 'tabHash' => 'add', 'url' => U('exams/AdminPoint/add'));
    }
    /**
     * 考点列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @return [type] [description]
     */
    public function index()
    {
        $list              = $this->mod->getPointPageList();
        $this->pageKeyList = ['exams_point_id', 'point_subject', 'title', 'DOACTION'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['DOACTION'] = '<a href="' . U('exams/AdminPoint/edit', ['tabHash' => 'edit', 'point_id' => $v['exams_point_id']]) . '">编辑</a>';
                $v['DOACTION'] .= ' - <a href="javascript:exams.deletePoint(' . $v['exams_point_id'] . ')">删除</a>';
            }
            unset($v);
        }
        $this->displayList($list);
    }
    /**
     * 添加考点
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     */
    public function add()
    {
        if ($_POST) {
            $subjectArr = explode(',', $_POST['exams_subject_idhidden']);
            // 过滤为0的无效分类
            if (end($subjectArr) == 0) {
                array_pop($subjectArr);
            }
            $subject_id = end($subjectArr);
            $data       = [
                'exams_subject_id' => $subject_id,
                'title'            => t($_POST['title']),
            ];
            $res = $this->mod->add($data);
            if ($res) {
                $this->jumpUrl = U('exams/AdminPoint/index');
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
            exit;
        }
        $this->pageKeyList = ['exams_subject_id', 'title'];
        ob_start();
        echo W('CategoryLevel', array('table' => 'exams_subject', 'id' => 'exams_subject_id'));
        $subject = ob_get_contents();
        ob_end_clean();
        $this->savePostUrl = U('exams/AdminPoint/add');
        $this->displayConfig(['exams_subject_id' => $subject]);
    }

    /**
     * 编辑考点
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     */
    public function edit()
    {
        if ($_POST) {
            $subjectArr = explode(',', $_POST['exams_subject_idhidden']);
            // 过滤为0的无效分类
            if (end($subjectArr) == 0) {
                array_pop($subjectArr);
            }
            $subject_id = end($subjectArr);
            $data       = [
                'exams_subject_id' => $subject_id,
                'title'            => t($_POST['title']),
            ];
            $res = $this->mod->where(['exams_point_id' => intval($_POST['exams_point_id'])])->save($data);
            if ($res) {
                $this->jumpUrl = U('exams/AdminPoint/index');
                $this->success('编辑成功');
            } else {
                $this->error('操作失败,请重试');
            }
            exit;
        }
        $this->pageTab[]   = array('title' => '编辑', 'tabHash' => 'edit', 'url' => U('exams/AdminPoint/edit', array('tabHash' => 'edit', 'point_id' => $_GET['point_id'])));
        $this->pageKeyList = ['exams_point_id', 'exams_subject_id', 'title'];

        $this->savePostUrl = U('exams/AdminPoint/edit');
        $data              = $this->mod->getPointById($_GET['point_id']);
        ob_start();
        echo W('CategoryLevel', array('table' => 'exams_subject', 'id' => 'exams_subject_id', 'default' => $data['point_subject_fullpath']));
        $subject = ob_get_contents();
        ob_end_clean();
        $data['exams_subject_id'] = $subject;
        $this->displayConfig($data);
    }

    /**
     * 加载考点
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @return [type] [description]
     */
    public function loadPonit()
    {
        $subject_id = intval($_GET['subject_id']);
        $data       = $this->mod->where('exams_subject_id=' . $subject_id)->select();
        if ($data) {
            $ret = ['status' => 1, 'data' => $data];
        } else {
            $ret = ['status' => 0, 'message' => '暂时没有设置考点,请先设置考点'];
        }
        echo json_encode($ret);exit;
    }

    /**
     * 删除考点
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function deletePoint()
    {
        $point_id = intval($_POST['point_id']);
        // 检测是否有绑定
        if (M("exams_question")->where("exams_point_id=" . $point_id)->count() > 0) {
            $res = ['status' => 0, 'message' => '该考点已有试题绑定,不允许删除'];
        } elseif (M('exams_point')->where("exams_point_id=" . $point_id)->delete()) {
            // 删除
            $res = ['status' => 1, 'data' => ['info' => '删除成功']];
        }
        echo json_encode($res);exit;
    }

}
