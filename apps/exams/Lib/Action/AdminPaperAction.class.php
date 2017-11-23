<?php
/**
 * 考试系统后台控制器
 * 试卷管理
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
class AdminPaperAction extends AdministratorAction
{
    protected $mod;
    protected $optionsMod;
    /**
     * 初始化
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->_listpk                = 'exams_paper_id';
        $this->mod                    = D('ExamsPaper', 'exams');
        $this->optionsMod             = D("ExamsPaperOptions", 'exams');
        $this->pageTitle['index']     = '试卷列表';
        $this->pageTitle['add']       = '添加试卷';
        $this->pageTitle['edit']      = '编辑试卷';
        $this->pageTitle['assembly']  = '试题组卷';
        $this->pageTitle['examslogs'] = '考试记录';

    }

    /**
     * 首页
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function index()
    {
        $this->pageTab[] = array('title' => '试卷列表', 'tabHash' => 'index', 'url' => U('exams/AdminPaper/index'));
        $this->pageTab[] = array('title' => '添加试卷', 'tabHash' => 'add', 'url' => U('exams/AdminPaper/add'));
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '搜索试卷', 'onclick' => "admin.fold('search_form')");
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '批量删除', 'onclick' => "exams.batchDelete('deletePaper')");
        // 搜索选项的key值
        $this->searchKey                                         = array('exams_paper_id', 'exams_paper_title', 'exams_module_id');
        $map                                                     = [];
        $_POST['exams_paper_title'] && $map['exams_paper_title'] = ['like', '%' . t($_POST['exams_paper_title']) . '%'];
        $_POST['exams_paper_id'] && $map['exams_paper_id']       = intval($_POST['exams_paper_id']);
        $_POST['exams_module_id'] && $map['exams_module_id']     = intval($_POST['exams_module_id']);
        $list                                                    = $this->mod->getPaperPageList($map);
        $this->pageKeyList                                       = ['exams_paper_id', 'exams_paper_title', 'paper_subject', 'exams_module_title', 'level_title', 'questions_count', 'score', 'exams_count', 'start_time', 'end_time', 'update_time', 'DOACTION'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['update_time'] = date("Y-m-d H:i", $v['update_time']);
                $v['start_time']  = date("Y-m-d H:i", $v['start_time']);
                $v['end_time']    = date("Y-m-d H:i", $v['end_time']);
                $v['DOACTION']    = '<a href="' . U('exams/AdminPaper/examslogs', ['tabHash' => 'examslogs', 'paper_id' => $v['exams_paper_id']]) . '">考试记录</a>';
                $v['DOACTION'] .= ' - <a href="' . U('exams/AdminPaper/edit', ['tabHash' => 'edit', 'paper_id' => $v['exams_paper_id']]) . '">编辑</a>';
                $v['DOACTION'] .= ' - <a href="' . U('exams/AdminPaper/assembly', ['tabHash' => 'assembly', 'paper_id' => $v['exams_paper_id']]) . '">组卷</a>';
                $v['DOACTION'] .= ' - <a href="javascript:exams.deletePaper(' . $v['exams_paper_id'] . ')">删除</a>';
            }
            unset($v);
        }
        $exams_module                 = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $exams_module                 = array_column($exams_module, 'title', 'exams_module_id');
        $this->opt['exams_module_id'] = array_merge([0 => '不限'], $exams_module);
        $this->displayList($list);
    }

    /**
     * 添加试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     */
    public function add()
    {
        $this->pageTab[] = array('title' => '试卷列表', 'tabHash' => 'index', 'url' => U('exams/AdminPaper/index'));
        $this->pageTab[] = array('title' => '添加试卷', 'tabHash' => 'add', 'url' => U('exams/AdminPaper/add'));
        if ($_POST) {
            // 解析专业分类
            $subjectArr = explode(',', $_POST['exams_subject_idhidden']);
            // 过滤为0的无效分类
            if (end($subjectArr) == 0) {
                array_pop($subjectArr);
            }
            $data['exams_subject_id']  = end($subjectArr);
            $data['description']       = t($_POST['description']);
            $data['level']             = intval($_POST['level']);
            $data['reply_time']        = intval($_POST['reply_time']);
            $data['exams_module_id']   = intval($_POST['exams_module_id']);
            $data['exams_paper_title'] = t($_POST['exams_paper_title']);
            $data['start_time']        = $_POST['start_time'] ? strtotime($_POST['start_time']) : 0;
            $data['end_time']          = $_POST['end_time'] ? strtotime($_POST['end_time']) : 0;
            $data['sort']              = intval($_POST['sort']);
            $data['exams_limit']       = intval($_POST['exams_limit']);
            $this->checkPaper($data, 'add');
            $data['create_time'] = $data['update_time'] = time();
            if ($this->mod->add($data)) {
                $this->jumpUrl = U('exams/AdminPaper/index');
                $this->success('添加成功');
            } else {
                $this->error('添加失败,请重新尝试');
            }
            exit;
        }
        $this->pageKeyList = ['exams_subject_id', 'exams_module_id', 'level', 'exams_paper_title', 'reply_time', 'exams_limit', 'sort', 'start_time', 'end_time', 'description'];
        $this->notEmpty    = ['exams_paper_id', 'exams_subject_id', 'exams_module_id', 'level', 'exams_paper_title', 'reply_time'];
        ob_start();
        echo W('CategoryLevel', array('table' => 'exams_subject', 'id' => 'exams_subject_id'));
        $subject = ob_get_contents();
        ob_end_clean();
        $this->savePostUrl = U('exams/AdminPaper/add');
        // 获取版块
        $exams_module = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $default      = [
            'exams_subject_id' => $subject,
        ];
        $this->opt['exams_module_id'] = array_column($exams_module, 'title', 'exams_module_id');
        $this->opt['level']           = [1 => '简单', 2 => '普通', 3 => '困难'];
        $this->displayConfig($default);
    }

    /**
     * 试卷数据检验
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @param  [type] $data [description]
     * @param  string $action [description]
     * @return [type] [description]
     */
    protected function checkPaper($data, $action = 'add')
    {
        if ($action != 'add' && !$data['exams_paper_id']) {
            $this->error('编辑试卷出错,请刷新页面重试');
        }

        // 检测数据
        if (!$data['exams_subject_id']) {
            $this->error('请选择专业');
        } elseif (!$data['exams_module_id']) {
            $this->error('请选择版块');
        } elseif (!$data['level']) {
            $this->error('请选择试卷难度');
        } elseif (!$data['exams_paper_title']) {
            $this->error('请填写试卷标题');
        } elseif (!is_numeric($data['reply_time'])) {
            $this->error('请填写有效的考试时长');
        } elseif (!$data['description']) {
            $this->error('请填写试卷简述');
        } elseif (!$data['start_time']) {
            $this->error('请填写考试开始时间');
        } elseif (!$data['end_time']) {
            $this->error('请填写考试开始时间');
        } elseif ($data['end_time'] <= $data['start_time']) {
            $this->error('考试结束时间不能小于开始时间');
        }
    }

    /**
     * 编辑试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function edit()
    {
        $this->pageTab[] = array('title' => '试卷列表', 'tabHash' => 'index', 'url' => U('exams/AdminPaper/index'));
        $this->pageTab[] = array('title' => '添加试卷', 'tabHash' => 'add', 'url' => U('exams/AdminPaper/add'));
        if ($_POST) {
            $paper_id = intval($_POST['exams_paper_id']);
            if (!$paper_id) {
                $this->jumpUrl = U('exams/AdminPaper/index');
                $this->error("未找到编辑的试卷");
            }
            // 解析专业分类
            $subjectArr = explode(',', $_POST['exams_subject_idhidden']);
            // 过滤为0的无效分类
            if (end($subjectArr) == 0) {
                array_pop($subjectArr);
            }
            $data['exams_subject_id']  = end($subjectArr);
            $data['description']       = t($_POST['description']);
            $data['level']             = intval($_POST['level']);
            $data['reply_time']        = intval($_POST['reply_time']);
            $data['exams_module_id']   = intval($_POST['exams_module_id']);
            $data['exams_paper_title'] = t($_POST['exams_paper_title']);
            $data['start_time']        = $_POST['start_time'] ? strtotime($_POST['start_time']) : 0;
            $data['end_time']          = $_POST['end_time'] ? strtotime($_POST['end_time']) : 0;
            $data['exams_paper_id']    = $paper_id;
            $data['sort']              = intval($_POST['sort']);
            $data['exams_limit']       = intval($_POST['exams_limit']);
            $this->checkPaper($data, 'edit');
            $data['update_time'] = time();
            if ($this->mod->save($data)) {
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败,请重新尝试');
            }
            exit;
        }
        $paper_id = intval($_GET['paper_id']);
        $paper    = $this->mod->getPaperById($paper_id);
        if (!$paper) {
            $this->jumpUrl = U('exams/AdminPaper/index');
            $this->error("未找到编辑的试卷");
        }
        $this->pageTab[]   = array('title' => '编辑试卷', 'tabHash' => 'edit', 'url' => U('exams/AdminPaper/edit', ['paper_id' => $paper_id]));
        $this->pageKeyList = ['exams_paper_id', 'exams_subject_id', 'exams_module_id', 'level', 'exams_paper_title', 'reply_time','exams_limit' ,'sort', 'start_time', 'end_time', 'description'];
        $this->notEmpty    = ['exams_paper_id', 'exams_subject_id', 'exams_module_id', 'level', 'exams_paper_title', 'reply_time'];
        ob_start();
        echo W('CategoryLevel', array('table' => 'exams_subject', 'id' => 'exams_subject_id', 'default' => $paper['paper_subject_fullpath']));
        $subject = ob_get_contents();
        ob_end_clean();
        $this->savePostUrl = U('exams/AdminPaper/edit');
        // 获取版块
        $exams_module                 = model('CategoryTree')->setTable('exams_module')->getCategoryList(0);
        $paper['exams_subject_id']    = $subject;
        $this->opt['exams_module_id'] = array_column($exams_module, 'title', 'exams_module_id');
        $this->opt['level']           = [1 => '简单', 2 => '普通', 3 => '困难'];
        $paper['start_time']          = date('Y-m-d H:i', $paper['start_time']);
        $paper['end_time']            = date('Y-m-d H:i', $paper['end_time']);
        $this->displayConfig($paper);
    }

    /**
     * 组卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function assembly()
    {
        $this->pageTab[] = array('title' => '试卷列表', 'tabHash' => 'index', 'url' => U('exams/AdminPaper/index'));
        $this->pageTab[] = array('title' => '添加试卷', 'tabHash' => 'add', 'url' => U('exams/AdminPaper/add'));
        $paper_id        = intval($_GET['paper_id']);
        $paper           = $this->mod->getPaperById($paper_id);
        if (!$paper) {
            $this->jumpUrl = U('exams/AdminPaper/index');
            $this->error("未找到试卷");
        }
        $this->pageTab[] = array('title' => '试题组卷', 'tabHash' => 'assembly', 'url' => U('exams/AdminPaper/assembly', ['paper_id' => $paper_id]));
        // 检测试卷数据
        $paper_options = $this->optionsMod->getPaperOptionsById($paper_id);

        // 获取当前试卷已经选择的类型
        $selected = getSubByKey($paper_options['options_type'], 'question_type');
        if ($selected) {
            // 获取试题类型
            $question_type = M('exams_question_type')->where(['exams_question_type_id' => ['not in', $selected]])->select();
        } else {
            $question_type = M('exams_question_type')->select();
        }
        $this->assign('question_type', $question_type);
        $this->assign("paper", $paper);
        $this->assign("paper_options", $paper_options);
        $this->display();
    }

    /**
     * 添加试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function doAddPaperOptionType()
    {
        if ($_POST) {
            // 获取试题类型
            $question_type = intval($_POST['question_type']);
            $score         = intval($_POST['score']);
            if ($score <= 0) {
                exit(json_encode(['status' => 0, 'message' => "请填写每题正确的分数值"]));
            }

            $data = [
                'question_type'     => $question_type,
                'score'             => $score,
                'question_type_key' => t($_POST['question_type_key']),
                'desc'              => t($_POST['desc']),
            ];

            if ($this->optionsMod->addOptionsType($data, intval($_POST['paper_id']))) {
                exit(json_encode(['status' => 1, 'data' => ['info' => '添加成功']]));
            } else {
                exit(json_encode(['status' => 0, 'message' => '添加失败,请重新尝试']));
            }
        }
    }
    /**
     * 删除试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function doDelPaperOptionType()
    {
        if ($_POST) {
            // 获取试题类型
            $type_id = intval($_POST['type_id']) - 1;

            if ($type_id < 0) {
                exit(json_encode(['status' => 0, 'message' => '删除失败,请重新尝试']));
            }

            if ($this->optionsMod->delOptionsType($type_id, intval($_POST['paper_id']))) {
                exit(json_encode(['status' => 1, 'data' => ['info' => '删除成功']]));
            } else {
                exit(json_encode(['status' => 0, 'message' => '删除失败,请重新尝试']));
            }
        }
    }
    /**
     * 自动组卷模板
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @return [type] [description]
     */
    public function autoCreateQuestion()
    {
        // 获取当前的试卷
        $paper_id = intval($_GET['paper_id']);
        $paper    = $this->mod->getPaperById($paper_id);
        // 统计试题数量
        $map['is_del']                 = 0;
        $map['exams_question_type_id'] = t($_GET['question_type']);
        $map['exams_subject_id']       = $paper['exams_subject_id'];
        $map['exams_module_id']        = $paper['exams_module_id'];
        //$map['level']                  = $paper['level'];
        $ids                              = $this->optionsMod->getQuestionIdByType($paper_id, $map['exams_question_type_id']);
        $ids && $map['exams_question_id'] = ['not in', $ids];
        $count                            = D("ExamsQuestion", 'exams')->where($map)->count();
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 获取试题的列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @return [type] [description]
     */
    public function getQuestionLists()
    {
        $map                                                              = [];
        $limit                                                            = intval($_POST['limit']) ?: 15;
        !empty($_POST['question_type']) && $map['exams_question_type_id'] = intval($_POST['question_type']);
        // 获取当前的试卷
        $paper_id                = intval($_POST['paper_id']);
        $paper                   = $this->mod->getPaperById($paper_id);
        $map['content']          = ['like', '%' . t($_POST['search_title']) . '%'];
        $map['exams_subject_id'] = $paper['exams_subject_id'];
        $map['exams_module_id']  = $paper['exams_module_id'];
        //$map['level']                  = $paper['level'];
        $ids                              = $this->optionsMod->getQuestionIdByType($paper_id, $map['exams_question_type_id']);
        $ids && $map['exams_question_id'] = ['not in', $ids];
        $list                             = D('ExamsQuestion', 'exams')->getQuestionPageList($map, $limit);
        $list['limit']                    = $limit;
        echo json_encode(['status' => 1, 'data' => $list]);exit;
    }
    /**
     * 处理提交组卷信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @return [type] [description]
     */
    public function doAssembly()
    {
        $type          = intval($_POST['type']);
        $paper_id      = intval($_POST['paper_id']);
        $question_type = intval($_POST['question_type']);
        switch ($type) {
            case 1:
                // 手动
                $paper_question = explode(',', $_POST['paper_question']);
                break;

            case 2:
                // 自动组题
                // 获取试卷已有试题个数
                $paper                         = $this->mod->getPaperById($paper_id);
                $map['exams_question_type_id'] = $question_type;
                $map['exams_subject_id']       = $paper['exams_subject_id'];
                $map['exams_module_id']        = $paper['exams_module_id'];
                //$map['level']                  = $paper['level'];
                $ids                              = $this->optionsMod->getQuestionIdByType($paper_id, $map['exams_question_type_id']);
                $ids && $map['exams_question_id'] = ['not in', $ids];
                $ids                              = D('ExamsQuestion', 'exams')->where($map)->field("exams_question_id")->order("RAND()")->limit(intval($_POST['questions_count']))->select();
                $paper_question                   = getSubByKey($ids, 'exams_question_id');
                break;
        }

        if ($this->optionsMod->addQuestions($paper_id, $question_type, $paper_question)) {
            echo json_encode(['status' => 1, 'data' => ['info' => '添加成功']]);exit;
        } else {
            echo json_encode(['status' => 0, 'message' => '添加失败,请重新尝试']);exit;
        }
    }

    /**
     * 删除试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @return [type] [description]
     */
    public function delQuestion()
    {
        if ($_POST) {
            $paper_id      = intval($_POST['paper_id']);
            $question_id   = intval($_POST['question_id']);
            $question_type = intval($_POST['question_type']);
            if ($this->optionsMod->delQuestion($paper_id, $question_type, $question_id)) {
                echo json_encode(['status' => 1, 'data' => ['info' => '删除试题成功']]);exit;
            } else {
                echo json_encode(['status' => 0, 'message' => '删除试题失败,请重新尝试']);exit;
            }
        }
    }

    /**
     * 更新试题类型的分数
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @return [type] [description]
     */
    public function updateQuestionTypeScore()
    {
        if ($_POST) {
            $paper_id      = intval($_POST['paper_id']);
            $score         = (float) ($_POST['score']);
            $question_type = intval($_POST['question_type']);
            if ($this->optionsMod->updateQuestionTypeScore($paper_id, $question_type, $score)) {
                echo json_encode(['status' => 1, 'data' => ['info' => '更新成功']]);exit;
            } else {
                echo json_encode(['status' => 0, 'message' => '更新失败']);exit;
            }
        }
    }

    /**
     * 删除试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function deletePaper()
    {
        $paper_id = is_array($_POST['paper_id']) ? $_POST['paper_id'] : intval($_POST['paper_id']);
        if (M('exams_paper')->where(['exams_paper_id' => ['in', $paper_id]])->setField('is_del', 1)) {
            // 删除
            $res = ['status' => 1, 'data' => ['info' => '删除成功']];
        } else {
            $res = ['status' => 0, 'message' => '删除失败,请稍后重试'];
        }
        echo json_encode($res);exit;
    }

    /**
     * 考试记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function examslogs()
    {
        $paper_id        = intval($_GET['paper_id']);
        $this->pageTab[] = array('title' => '考试记录', 'tabHash' => 'examslogs', 'url' => U('exams/AdminPaper/examslogs', ['paper_id' => $paper_id]));
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        // 搜索选项的key值
        $this->searchKey = array('uid');

        $map                         = [];
        $map['exams_paper_id']       = $paper_id;
        $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
        $list                        = D("ExamsUser", 'exams')->getExamsUserPageList($map);
        $this->pageKeyList           = ['exams_users_id', 'exams_paper_title', 'uname', 'score', 'right_count', 'wrong_count', 'create_time', 'anser_time', 'update_time', 'DOACTION'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['uname']             = getUsername($v['uid']);
                $v['create_time']       = date("Y-m-d H:i", $v['create_time']);
                $v['update_time']       = date("Y-m-d H:i", $v['update_time']);
                $v['status']            = $v['status'] == 1 ? '已审阅' : "未审阅";
                $v['anser_time']        = $v['anser_time'] . ' 分钟';
                $v['DOACTION']          = '待开发';
                $v['exams_paper_title'] = $v['paper_info']['exams_paper_title'];
                //$v['DOACTION']    = '<a href="' . U('exams/AdminPaper/edit', ['tabHash' => 'edit', 'paper_id' => $v['exams_paper_id']]) . '">编辑</a>';
                //$v['DOACTION'] .= ' - <a href="' . U('exams/AdminPaper/assembly', ['tabHash' => 'assembly', 'paper_id' => $v['exams_paper_id']]) . '">组卷</a>';
                //$v['DOACTION'] .= ' - <a href="javascript:exams.deletePaper(' . $v['exams_paper_id'] . ')">删除</a>';
            }
            unset($v);
        }
        $this->searchPostUrl = U("exams/AdminPaper/examslogs", ['paper_id' => $paper_id]);
        $this->displayList($list);
    }
}
