<?php
/**
 * 考试系统后台控制器
 * 试题管理
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
class AdminQuestionAction extends AdministratorAction
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
        $this->_listpk             = 'exams_question_id';
        $this->mod                 = D('ExamsQuestion', 'exams');
        $this->pageTitle['index']  = '试题列表';
        $this->pageTitle['add']    = '添加试题';
        $this->pageTitle['edit']   = '编辑试题';
        $this->pageTitle['import'] = '试题导入';
        $this->pageTab[]           = array('title' => '试题列表', 'tabHash' => 'index', 'url' => U('exams/AdminQuestion/index'));
        $this->pageTab[]           = array('title' => '添加试题', 'tabHash' => 'add', 'url' => U('exams/AdminQuestion/add'));
        $this->pageTab[]           = array('title' => '试题导入', 'tabHash' => 'import', 'url' => U('exams/AdminQuestion/import'));
    }

    /**
     * 试题列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @return [type] [description]
     */
    public function index()
    {
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '搜索试题', 'onclick' => "admin.fold('search_form')");
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '批量删除', 'onclick' => "exams.batchDelete('deleteQuestion')");
        // 搜索选项的key值
        $this->searchKey                                                   = array('exams_question_id', 'content', 'exams_question_type_id', 'exams_module_title');
        $map                                                               = [];
        $_POST['exams_question_id'] && $map['exams_question_id']           = intval($_POST['exams_question_id']);
        $_POST['content'] && $map['content']                               = ['like', '%' . t($_POST['content']) . '%'];
        $_POST['exams_question_type_id'] && $map['exams_question_type_id'] = intval($_POST['exams_question_type_id']);
        $_POST['exams_module_title'] && $map['exams_module_id']            = intval($_POST['exams_module_title']);
        $list                                                              = $this->mod->getQuestionPageList($map);
        $this->pageKeyList                                                 = ['exams_question_id', 'content', 'question_subject', 'exams_point_title', 'exams_question_type_title', 'level_title', 'exams_module_title', 'DOACTION'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['DOACTION'] = '<a href="' . U('exams/AdminQuestion/edit', ['tabHash' => 'edit', 'question_id' => $v['exams_question_id']]) . '">编辑</a>';
                $v['DOACTION'] .= ' - <a href="javascript:exams.deleteQuestion(' . $v['exams_question_id'] . ')">删除</a>';
                $v['content'] = mStr(t($v['content']), 50);
            }
            unset($v);
        }
        $exams_module                        = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $exams_module                        = array_column($exams_module, 'title', 'exams_module_id');
        $this->opt['exams_module_title']     = array_merge([0 => '不限'], $exams_module);
        $question_type                       = M('exams_question_type')->select();
        $question_type                       = array_column($question_type, 'question_type_title', 'exams_question_type_id');
        $this->opt['exams_question_type_id'] = array_merge([0 => '不限'], $question_type);
        $this->displayList($list);
    }

    /**
     * 添加试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     */
    public function add()
    {
        if ($_POST) {

            //dump($_POST);exit;
            // 解析试题类型ID
            $question_type = explode('|', $_POST['exams_question_type']);
            if (!is_numeric($question_type[1])) {
                $this->error('请选择试题类型');
            }
            $data['exams_question_type_id'] = $question_type[1];

            // 试题内容
            $data['content'] = $_POST['content'];
            if (!$data['content']) {
                $this->error('请输入试题内容');
            }

            // 解析专业分类
            $subjectArr = explode(',', $_POST['exams_subject_idhidden']);
            // 过滤为0的无效分类
            if (end($subjectArr) == 0) {
                array_pop($subjectArr);
            }
            $data['exams_subject_id'] = end($subjectArr);

            // 试题解析
            $data['analyze'] = $_POST['analyze'];

            // 试题模块
            $data['exams_module_id'] = intval($_POST['exams_module_id']);

            // 试题难度
            $data['level'] = intval($_POST['exams_level']);

            // 试题考点
            $data['exams_point_id'] = intval($_POST['exams_point_id']);

            // 试题选项
            $options = [];
            array_walk($_POST, function ($v, $k) use (&$options) {
                if (strpos($k, 'answer_options_') !== false) {
                    $options[str_replace('answer_options_', '', $k)] = $v;
                }
            });
            $data['answer_options'] = serialize($options);
            // 试题正确选项
            $data['answer_true_option'] = (is_string($_POST['answer_true_option'])) ? serialize([$_POST['answer_true_option']]) : serialize($_POST['answer_true_option']);
            if ($_POST['exams_question_id']) {
                $res = $this->mod->editQuestion($data, $_POST['exams_question_id']);
            } else {
                $res = $this->mod->addQuestion($data);
            }

            if ($res) {
                $this->jumpUrl = U('exams/AdminQuestion/index');
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
            exit;
        }
        // 获取版块
        $exams_module = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $this->assign('exams_module', $exams_module);
        // 获取试题类型
        $exams_question_type = M("exams_question_type")->select();
        $this->assign('exams_question_type', $exams_question_type);
        $this->display();
    }

    /**
     * 编辑
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @return [type] [description]
     */
    public function edit()
    {
        // 获取试题
        $data = $this->mod->getQuestionById($_GET['question_id']);
        // 获取版块
        $exams_module = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $this->assign('exams_module', $exams_module);
        // 获取试题类型
        $exams_question_type = M("exams_question_type")->select();
        $this->assign('exams_question_type', $exams_question_type);
        // 查询考点
        $points = D('ExamsPoint', 'exams')->where('exams_subject_id=' . $data['exams_subject_id'])->select();
        $this->assign('exams_point', $points);
        $this->assign('question', $data);
        $this->display();
    }

    /**
     * 删除试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function deleteQuestion()
    {
        $question_id = is_array($_POST['question_id']) ? $_POST['question_id'] : intval($_POST['question_id']);
        if (M('exams_question')->where(['exams_question_id' => ['in', $question_id]])->setField('is_del', 1)) {
            // 删除
            $res = ['status' => 1, 'data' => ['info' => '删除成功']];
        } else {
            $res = ['status' => 0, 'message' => '删除失败,请稍后重试'];
        }
        echo json_encode($res);exit;
    }

    /**
     * 获取选项内容的配置
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-11
     * @return [type] [description]
     */
    public function getAnswerTpl()
    {
        $key = explode('|', $_GET['question_type_key']);
        if (!empty($key) && in_array($key[0], ['radio', 'multiselect', 'judge', 'completion', 'essays'])) {
            $type  = $key[0];
            $count = intval($_GET['totalCount']) ?: 0;
            if ($count >= 8) {
                echo json_encode(['status' => 0, 'message' => '最多只能添加8个选项']);exit;
            }
            $limit = intval($_GET['limit']) ?: 1;
            if (in_array($key[0], ['judge', 'completion'])) {
                $answer_key = ['A'];
            } else {
                $answer_key = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                $answer_key = array_slice($answer_key, $count, $limit);
            }

            // 渲染模板
            $this->assign('answer_key', $answer_key);
            $this->assign('question_type_key', $type);
            $tpl = $this->fetch('ask_question_tpl');
            echo json_encode(['status' => 1, 'html' => $tpl]);exit;
        }
    }

    /**
     * 试题导入
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @return [type] [description]
     */
    public function import()
    {
        $this->pageKeyList = array('file');
        // 表单URL设置
        $this->savePostUrl = U('exams/AdminQuestion/doImport');

        $this->displayConfig();
    }

    /**
     * 处理导入
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @return [type] [description]
     */
    public function doImport()
    {
        $attach_id = trim($_POST['file_ids'], '|') ?: 0;
        if ($attach_id) {
            $attach = model('Attach')->getAttachById($attach_id);
            if (!in_array($attach['extension'], ['xls', 'xlsx'])) {
                $this->error('请重新上传导入附件');
            } else {
                //检测文件是否存在
                $file_path   = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'data', 'upload', $attach['save_path'] . $attach['save_name']));
                $data        = model('Excel')->import($file_path);
                $add_count   = 0;
                $total_count = 0;
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        if ($key > 0 && $value[0]) {
                            $total_count++;
                            // 试题类型
                            $question['exams_question_type_id'] = intval($value[0]);
                            // 专业
                            $question['exams_subject_id'] = intval($value[1]);
                            // 考点
                            $question['exams_point_id'] = intval($value[2]);
                            // 版块
                            $question['exams_module_id'] = intval($value[3]);
                            // 难度
                            $question['level'] = intval($value[4]);
                            // 内容
                            $question['content'] = $value[5];
                            // 选项
                            $options = array_filter([
                                'A' => $value[6],
                                'B' => $value[7],
                                'C' => $value[8],
                                'D' => $value[9],
                                'E' => $value[10],
                                'F' => $value[11],
                                'G' => $value[12],
                                'H' => $value[13],
                            ]);
                            $question['answer_options'] = serialize($options);
                            // 正确选项或答案
                            $answer                         = explode('|', $value[14]);
                            $question['answer_true_option'] = serialize($answer);
                            // 试题解析
                            $question['analyze'] = $value[15];
                            if ($this->mod->addQuestion($question)) {
                                $add_count++;
                            }

                        }
                    }
                }
                if ($add_count > 0) {
                    $this->jumpUrl = U('exams/AdminQuestion/index');
                    $this->success('共计' . $total_count . '题,本次成功导入' . $add_count . '题');
                } else {
                    $this->error('导入失败,请检查数据格式是否正确');
                }
            }

        }
        $this->error('请重新上传导入附件');
    }

}
