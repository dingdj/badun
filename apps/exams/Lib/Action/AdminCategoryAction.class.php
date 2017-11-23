<?php
/**
 * 考试系统后台控制器
 * 分类管理
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
class AdminCategoryAction extends AdministratorAction
{
    /**
     * 初始化
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->allSelected                   = false;
        $this->pageTitle['subject']          = '专业分类管理';
        $this->pageTitle['module']           = '板块分类管理';
        $this->pageTitle['question']         = '试题类型';
        $this->pageTitle['addQuestionType']  = '添加试题类型';
        $this->pageTitle['editQuestionType'] = '编辑试题类型';
        $this->pageTitle['addModule']        = '添加版块';
        $this->pageTitle['editModule']       = '编辑版块';
    }

    /**
     * 专业分类
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function subject()
    {
        $treeData = model('CategoryTree')->setTable('exams_subject')->getNetworkList();
        $this->displayTree($treeData, 'exams_subject');
    }

    /**
     * 板块分类
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function module()
    {
        //$treeData = model('CategoryTree')->setTable('exams_module')->getNetworkList();
        //$this->displayTree($treeData, 'exams_module',1);
        $this->pageTab[]   = array('title' => '版块列表', 'tabHash' => 'module', 'url' => U('exams/AdminCategory/module', array('tabHash' => 'module')));
        $this->pageTab[]   = array('title' => '添加版块', 'tabHash' => 'addModule', 'url' => U('exams/AdminCategory/addModule', array('tabHash' => 'addModule')));
        $this->pageKeyList = ['exams_module_id', 'title', 'icon', 'description', 'btn_text', 'DOACTION'];
        $list              = M('exams_module')->order("sort DESC")->findPage();
        if ($list['data']) {
            foreach ($list['data'] as &$value) {
                $value['icon']        = '<img width="60" height="60" src="' . getCover($value['icon'], 60, 60) . '" />';
                $value['description'] = mStr($value['description'], 50);
                $value['DOACTION']    = '<a href="' . U('exams/AdminCategory/editModule', ['tabHash' => 'editModule', 'module_id' => $value['exams_module_id']]) . '">编辑</a>';
                $value['DOACTION'] .= ' - <a href="javascript:exams.deleteMoudle(' . $value['exams_module_id'] . ')">删除</a>';
            }
        }
        $this->displayList($list);
    }
    /**
     * 添加版块
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     */
    public function addModule()
    {
        if ($_POST) {
            $data = [
                'title'       => t($_POST['title']),
                'icon'        => intval($_POST['icon']),
                'description' => t($_POST['description']),
                'btn_text'    => t($_POST['btn_text']),
                'sort'        => intval($_POST['sort']),
                'is_practice' => (intval($_POST['is_practice']) == 1) ? 1 : 0,
            ];

            if (M("exams_module")->add($data)) {
                $this->jumpUrl = U('exams/AdminCategory/module');
                $this->success("添加成功");
            } else {
                $this->error("添加失败,请重试");
            }
            exit;
        }
        $this->pageTab[]          = array('title' => '版块列表', 'tabHash' => 'module', 'url' => U('exams/AdminCategory/module', array('tabHash' => 'module')));
        $this->pageTab[]          = array('title' => '添加版块', 'tabHash' => 'addModule', 'url' => U('exams/AdminCategory/addModule', array('tabHash' => 'addModule')));
        $this->pageKeyList        = ['title', 'icon', 'description', 'btn_text', 'is_practice', 'sort'];
        $this->savePostUrl        = U('exams/AdminCategory/addModule');
        $this->opt['is_practice'] = [1 => '允许'];
        $this->displayConfig();
    }

    /**
     * 编辑版块
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function editModule()
    {
        if ($_POST) {
            $data = [
                'title'           => t($_POST['title']),
                'icon'            => intval($_POST['icon']),
                'description'     => t($_POST['description']),
                'btn_text'        => t($_POST['btn_text']),
                'sort'            => intval($_POST['sort']),
                'exams_module_id' => intval($_POST['exams_module_id']),
                'is_practice'     => (intval($_POST['is_practice']) == 1) ? 1 : 0,
            ];

            if (M("exams_module")->save($data)) {
                $this->success("编辑成功");
            } else {
                $this->error("编辑失败,请重试");
            }
            exit;
        }
        $module_id = intval($_GET['module_id']);
        $module    = M("exams_module")->getByExamsModuleId($module_id);
        if (!$module) {
            $this->jumpUrl = U('exams/AdminCategory/module');
            $this->error('编辑的版块不存在');
        }
        $this->pageTab[]          = array('title' => '版块列表', 'tabHash' => 'module', 'url' => U('exams/AdminCategory/module', array('tabHash' => 'module')));
        $this->pageTab[]          = array('title' => '添加版块', 'tabHash' => 'addModule', 'url' => U('exams/AdminCategory/addModule', array('tabHash' => 'addModule')));
        $this->pageTab[]          = array('title' => '编辑版块', 'tabHash' => 'editModule', 'url' => U('exams/AdminCategory/editModule', array('tabHash' => 'editModule', 'module_id' => $module_id)));
        $this->pageKeyList        = ['exams_module_id', 'title', 'icon', 'description', 'btn_text', 'is_practice', 'sort'];
        $this->savePostUrl        = U('exams/AdminCategory/editModule');
        $this->opt['is_practice'] = [1 => '允许'];
        $this->displayConfig($module);
    }
    /**
     * 删除版块
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function deleteModule()
    {
        $module_id = intval($_POST['module_id']);
        // 检测是否有绑定
        if (M("exams_paper")->where("exams_module_id=" . $module_id)->count() > 0 || M("exams_question")->where("exams_module_id=" . $module_id)->count() > 0) {
            $res = ['status' => 0, 'message' => '该版块已有试题或试卷绑定,不允许删除'];
        } elseif (M('exams_module')->where("exams_module_id=" . $module_id)->delete()) {
            // 删除
            $res = ['status' => 1, 'data' => ['info' => '删除成功']];
        }
        echo json_encode($res);exit;
    }

    /**
     * 试题类型表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function question($value = '')
    {
        $this->pageTab[] = array('title' => '试题类型', 'tabHash' => 'question', 'url' => U('exams/AdminCategory/question', array('tabHash' => 'question')));
        $this->pageTab[] = array('title' => '添加类型', 'tabHash' => 'addQuestionType', 'url' => U('exams/AdminCategory/addQuestionType', array('tabHash' => 'addQuestionType')));

        $this->pageKeyList = ['exams_question_type_id', 'question_type_title', 'question_type_key', 'DOACTION'];
        $list              = M('exams_question_type')->findPage();
        if ($list['data']) {
            $keyName = [
                'radio'       => '单选',
                'multiselect' => '多选',
                'judge'       => '判断',
                'completion'  => '填空',
                'essays'      => '论述',
            ];
            foreach ($list['data'] as &$v) {
                $v['question_type_key'] = $keyName[$v['question_type_key']];
                $v['DOACTION']          = '<a href="' . U('exams/AdminCategory/editQuestionType', ['tabHash' => 'editQuestionType', 'question_type_id' => $v['exams_question_type_id']]) . '">编辑</a>';
                $v['DOACTION'] .= ' - <a href="javascript:exams.deleteQuestionType(' . $v['exams_question_type_id'] . ')">删除</a>';
            }
        }

        $this->displayList($list);
    }

    /**
     * 添加试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     */
    public function addQuestionType()
    {
        $this->pageTab[] = array('title' => '试题类型', 'tabHash' => 'question', 'url' => U('exams/AdminCategory/question', array('tabHash' => 'question')));
        $this->pageTab[] = array('title' => '添加类型', 'tabHash' => 'addQuestionType', 'url' => U('exams/AdminCategory/addQuestionType', array('tabHash' => 'addQuestionType')));
        if ($_POST) {
            $data = [
                'question_type_key'   => $_POST['question_type_key'],
                'question_type_title' => t($_POST['question_type_title']),
            ];
            $res = M('exams_question_type')->add($data);
            if ($res) {
                $this->jumpUrl = U('exams/AdminCategory/question');
                $this->success('添加成功');
            } else {
                $this->error('操作失败,请重试');
            }
            exit;
        }
        $this->pageKeyList              = ['question_type_title', 'question_type_key'];
        $this->savePostUrl              = U('exams/AdminCategory/addQuestionType');
        $this->opt['question_type_key'] = [
            'radio'       => '单选',
            'multiselect' => '多选',
            'judge'       => '判断',
            'completion'  => '填空',
            'essays'      => '论述',
        ];
        $this->displayConfig();
    }

    /**
     * 编辑试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @return [type] [description]
     */
    public function editQuestionType()
    {
        $this->pageTab[] = array('title' => '试题类型', 'tabHash' => 'question', 'url' => U('exams/AdminCategory/question', array('tabHash' => 'question')));
        $this->pageTab[] = array('title' => '添加类型', 'tabHash' => 'addQuestionType', 'url' => U('exams/AdminCategory/addQuestionType', array('tabHash' => 'addQuestionType')));
        $this->pageTab[] = array('title' => '编辑类型', 'tabHash' => 'editQuestionType', 'url' => U('exams/AdminCategory/editQuestionType', array('exams_question_type_id' => $_GET['question_type_id'], 'tabHash' => 'editQuestionType')));
        if ($_POST) {
            $data = [
                'question_type_key'   => $_POST['question_type_key'],
                'question_type_title' => t($_POST['question_type_title']),
            ];
            $res = M('exams_question_type')->where('exams_question_type_id=' . $_POST['exams_question_type_id'])->save($data);
            if ($res) {
                $this->jumpUrl = U('exams/AdminCategory/question');
                $this->success('编辑成功');
            } else {
                $this->error('操作失败,请重试');
            }
            exit;
        }
        $this->pageKeyList              = ['exams_question_type_id', 'question_type_title', 'question_type_key'];
        $this->savePostUrl              = U('exams/AdminCategory/editQuestionType');
        $this->opt['question_type_key'] = [
            'radio'       => '单选',
            'multiselect' => '多选',
            'judge'       => '判断',
            'completion'  => '填空',
            'essays'      => '论述',
        ];
        $data = M('exams_question_type')->where('exams_question_type_id=' . $_GET['question_type_id'])->find();
        $this->displayConfig($data);

    }

    /**
     * 删除试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function deleteQuestionType()
    {
        $question_type_id = intval($_POST['question_type_id']);
        // 检测是否有绑定
        if (M("exams_question")->where("exams_question_type_id=" . $question_type_id)->count() > 0) {
            $res = ['status' => 0, 'message' => '该试题类型已有试题绑定,不允许删除'];
        } elseif (M('exams_question_type')->where("exams_question_type_id=" . $question_type_id)->delete()) {
            // 删除
            $res = ['status' => 1, 'data' => ['info' => '删除成功']];
        }
        echo json_encode($res);exit;
    }

}
