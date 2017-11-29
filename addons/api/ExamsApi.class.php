<?php
/**
 * @name 考试系统Api
 * @author martinsun<syh@sunyonghong.com>
 * @version v1.0
 */
header("content-type:text/html;charset=utf-8;");
class ExamsApi extends Api
{

    /**
     * 获取考试模块列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-01
     * @return [type] [description]
     */
    public function getExamsMoudles()
    {
        $module = M('exams_module')->order("sort DESC")->select();
        foreach ($module as $key => $value) {
            $module[$key]['icon']            = $value['icon'] ? getImageUrlByAttachId($value['icon']) : '';
            $module[$key]['exams_module_id'] = intval($value['exams_module_id']);
            $module[$key]['is_practice']     = intval($value['is_practice']);
            $module[$key]['btn_text']        = $value['btn_text'] ?: '开始考试';
            unset($module[$key]['sort'], $module[$key]['pid']);
        }
        $this->exitJson($module ?: [], 1);
    }

    /**
     * 获取专业分类
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-01
     * @return [type] [description]
     */
    public function getSubjectCategory()
    {
        $cate = model('CategoryTree')->setTable('exams_subject')->getNetworkList(0);
        $data = $this->parseSubjectCategory($cate);
        $this->exitJson($data ?: [], 1);
    }
    /**
     * 解析专业分类数据
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-01
     * @param  [type] $data [description]
     * @return [type] [description]
     */
    protected function parseSubjectCategory($data)
    {
        $list = [];
        if (is_array($data) && $data) {

            foreach ($data as $value) {
                $item = [
                    'subject_id' => intval($value['id']),
                    'title'      => $value['title'],
                ];
                if ($value['child']) {
                    $childs                   = $this->parseSubjectCategory($value['child']);
                    $childs && $item['child'] = $childs;
                }
                array_push($list, $item);
            }
        }
        return $list;
    }

    /**
     * 获取试卷列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-01
     * @return [type] [description]
     */
    public function getPaperList()
    {

        $map['_string'] = '(`start_time` <= ' . time() . ' OR `start_time` = 0) AND (`end_time` >= ' . time() . ' OR `end_time` = 0)';
        // 试题模块
        $module_id                            = $this->module_id;
        $module_id && $map['exams_module_id'] = $module_id;
        // 试题专业分类
        $cateId = intval($this->subject_id);
        if ($cateId) {
            $ids = model('CategoryTree')->setTable('exams_subject')->getSubCateIdByPid($cateId);
            array_unshift($ids, $cateId);
        }
        $ids && $map['exams_subject_id'] = ['in', $ids];
        // 试题难度
        $level                    = $this->level ? ['in', $this->level] : '';
        $level && $where['level'] = $level;
        // 排序方式
        $order = in_array($this->order, ['default', 'new', 'hot']) ? $this->order : 'default';
        // 查询数据
        $list = D('ExamsPaper', 'exams')->getPaperPageList($map, $this->count, $order);
        //if ($list['data']) {
        //foreach ($list['data'] as &$v) {

        //$v['paper_options'] = D('ExamsPaperOptions', 'exams')->getPaperOptionsById($v['exams_paper_id'],false);
        //}
        //}
        if ($list['gtLastPage'] === true) {
            $this->exitJson((object) [], 0, '暂时没有试卷');
        }
        $list['data'] ? $this->exitJson($list['data'], 1) : $this->exitJson((object) [], 0, '暂时没有试卷');
    }

    /**
     * 获取试卷内容
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-03
     * @return [type] [description]
     */
    public function getPaperInfo()
    {
        // 获取试卷ID
        $paper_id = intval($this->paper_id);
        // 获取试卷信息
        $paper = D("ExamsPaper", 'exams')->getPaperById($paper_id);
        if (!$paper) {
            $this->exitJson((object) [], 0, '暂无该试卷信息');
        }
        // 检测该用试卷是否有考试次数限制
        if ($this->exams_type == 2 && $paper['exams_limit'] > 0) {
            // 查询用户考试次数
            if (D("ExamsUser", 'exams')->setLoginMid($this->mid)->isLimit($paper_id, $paper['exams_limit'])) {
                $this->exitJson((object) [], 0, '你已超过该考试允许参考的最大次数');
            }
        }
        // 获取试卷试题等信息
        $paper['paper_options'] = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById($paper_id);
        // 是否继续作答
        $tempData = [];
        $temp_id  = isset($this->data['exams_users_id']) ? intval($this->exams_users_id) : 0;
        if ($temp_id) {
            // 查询记录
            $map['exams_users_id'] = $temp_id;
            $map['uid']            = $this->mid;
            $map['exams_mode']     = ($this->exams_type == 1) ? 1 : 2;
            $tempData              = D('ExamsUser', 'exams')->getExamsInfoByMap($map) ?: [];
            if ($tempData) {
                $user_answer_temp = [];
                foreach ($tempData['content'] as $question_id => $answer) {
                    $item = [
                        'exmas_question_id' => $question_id,
                        'user_answer'       => $answer,
                    ];
                    array_push($user_answer_temp, $item);
                }
                $paper['user_answer_temp'] = $user_answer_temp;
            }

        }
        $this->exitJson($paper, 1);
    }

    /**
     * 收藏与取消收藏试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-03
     * @return [type] [description]
     */
    public function collect()
    {
        $action                    = ($this->action == '1') ? 1 : 0;
        $data['uid']               = intval($this->mid);
        $data['source_id']         = intval($this->source_id);
        $data['source_table_name'] = 'exams_question';
        // 收藏
        $mod = D('ZyCollection', 'classroom');
        if ($action === 1) {
            $data['ctime'] = time();
            if ($mod->addcollection($data)) {
                $this->exitJson(['source_id' => $data['source_id']], 1, '收藏成功');
            }
        } else {
            if ($mod->delcollection($data['source_id'], $data['source_table_name'], $data['uid'])) {
                $this->exitJson(['source_id' => $data['source_id']], 1, '取消收藏成功');
            }
        }
        $this->exitJson((object) [], 0, $mod->getError());
    }

    /**
     * 处理提交的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-03
     * @return [type] [description]
     */
    public function doExams()
    {
        $data = [
            'paper_id'       => intval($this->paper_id),
            'user_answer'    => $this->user_answer,
            'anser_time'     => intval($this->anser_time),
            'exams_mode'     => intval($this->exams_type),
            'exams_users_id' => isset($this->data['exams_users_id']) ? intval($this->exams_users_id) : 0,
        ];
        // 检测是否错题再练
        if ($data['exams_users_id'] == 0 && isset($this->data['wrong_exams_user_id'])) {
            $data['is_wrongexams']   = 1;
            $data['wrongexams_temp'] = intval($this->wrong_exams_user_id);
            $data['exams_mode']      = 3;// 强制更改考试类型为 错题再练标识
        }
        if (D("ExamsUser", 'exams')->setLoginMid($this->mid)->doExamsPaper($data)) {
            $this->exitJson(['paper_id' => $data['paper_id']], 1, '提交成功,请等待结果');
        } else {
            $this->exitJson((object) [], 0, '提交处理失败,请重新尝试');
        }
    }

    /**
     * 处理下次再做的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-03
     * @return [type] [description]
     */
    public function doProgressExams()
    {
        $data = [
            'paper_id'       => intval($this->paper_id),
            'user_answer'    => $this->user_answer,
            'anser_time'     => intval($this->anser_time),
            'exams_mode'     => intval($this->exams_type),
            'exams_users_id' => isset($this->data['exams_paper_temp']) ? intval($this->exams_paper_temp) : 0,
        ];
        // 检测是否错题再练
        if ($data['exams_users_id'] == 0 && isset($this->data['wrong_exams_user_id'])) {
            $data['is_wrongexams']   = 1;
            $data['wrongexams_temp'] = intval($this->wrong_exams_user_id);
            $data['exams_mode']      = 3;// 强制更改考试类型为 错题再练标识
        }
        if (D("ExamsUser", 'exams')->setLoginMid($this->mid)->addProgressExams($data)) {
            $this->exitJson(['paper_id' => $data['paper_id']], 1, '保存成功,下次可继续做题');
        } else {
            $this->exitJson((object) [], 0, '保存失败,请重新尝试');
        }
    }

    /**
     * 考试记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @return [type] [description]
     */
    public function examsLog()
    {
        // 类型 1:练习记录  2:考试记录  3: 错题记录
        $exams_mode = intval($this->log_type);
        $map['uid'] = $this->mid;
        switch ($exams_mode) {
            case '2':
                // 考试记录
                $map['progress']   = -1;
                $map['exams_mode'] = 2;
                $list              = D("ExamsUser", 'exams')->getExamsUserPageList($map, $this->count);
                break;
            case '3':
                // 错题记录
                // 进度为100%的试卷
                $map['progress'] = 100;
                // 错题数大于0
                $map['wrong_count'] = ['gt', 0];
                $map['exams_mode']  = ['in', '1,2,3'];
                $list               = D("ExamsUser", 'exams')->getExamsUserPageList($map, $this->count);
                break;
            default:
                // 练习记录
                $map['progress']   = -1;
                $map['exams_mode'] = ['in', '1,3'];
                $list              = D("ExamsUser", 'exams')->getExamsUserPageList($map, $this->count);
                break;
        }
        if ($list['gtLastPage'] === true || !$list['data']) {
            $this->exitJson((object) [], 0, '暂时没有相关记录');
        }
        foreach ($list['data'] as &$v) {
            $v['exams_users_id'] = intval($v['exams_users_id']);
            $v['exams_paper_id'] = intval($v['exams_paper_id']);
            $v['status']         = intval($v['status']);
        }
        $this->exitJson($list['data'], 1);
    }

    /**
     * 考试记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @return [type] [description]
     */
    public function deleteExamsLog()
    {
        $temp_id = intval($this->exams_users_id);
        if ($temp_id) {
            if (D('ExamsUser', 'exams')->where(['uid' => $this->mid, 'exams_users_id' => $temp_id])->save(['is_del' => 1])) {
                $this->exitJson(['exams_users_id' => $temp_id], 1, '删除成功');
            }
        }
        $this->exitJson((object) [], 0, '删除失败,请重新尝试');
    }

    /**
     * 收藏列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @return [type] [description]
     */
    public function getCollectList()
    {
        // 题目收藏
        $list = D("ZyCollection", 'classroom')->where(['source_table_name' => 'exams_question', 'uid' => $this->mid])->findpage($this->count);
        if ($list['gtLastPage'] === true) {
            $this->exitJson((object) [], 0, '暂时没有收藏的试题了');
        }
        if ($list['data']) {
            $mod = D("ExamsQuestion", 'exams');
            foreach ($list['data'] as &$value) {
                $value['question_info'] = $mod->getQuestionById($value['source_id']);
            }
        }
        $list['data'] ? $this->exitJson($list['data'], 1) : $this->exitJson((object) [], 0, '暂时没有收藏的试题了');
    }

    /**
     * 查看试题信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @return [type] [description]
     */
    public function getQuesionInfo()
    {
        $question_id = intval($this->question_id);
        $info        = D("ExamsQuestion", 'exams')->setLoginMid($this->mid)->getQuestionById($question_id);
        if ($info) {
            $this->exitJson($info, 1);
        }
        $this->exitJson((object) [], 0, '没有该试题信息');
    }

    /**
     * 查看错题试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function showWrongList()
    {
        // 获取试卷ID
        $paper_id = intval($this->paper_id);
        // 获取试卷信息
        $paper = D("ExamsPaper", 'exams')->getPaperById($paper_id);
        if ($paper) {
            // 获取试卷试题等信息
            $paper_options = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById($paper_id);
            // 查询记录
            $temp_id               = intval($this->exams_users_id);
            $map['exams_users_id'] = $temp_id;
            $map['uid']            = $this->mid;
            $answerData            = D('ExamsUser', 'exams')->getExamsInfoByMap($map);
            // 获取错误的答题记录
            $wrongList               = D("ExamsLogs", 'exams')->getWrongList($paper_id, $temp_id);
            $wrongList && $wrongList = getSubByKey($wrongList, 'exams_question_id');
            $new_options             = [];
            foreach ($paper_options['options_questions_data'] as $type_id => $list) {
                foreach ($list as $question) {
                    in_array($question['exams_question_id'], $wrongList) && $new_options[$type_id][] = $question;
                }
            }
            // 删除无用的数据
            unset($paper_options['options_questions']);
            $paper_options['options_questions_data'] = $new_options;
            // 重置options_type值
            $options_type = [];
            foreach ($paper_options['options_type'] as $key => $value) {
                if (array_key_exists($value['question_type'], $new_options)) {
                    $options_type[] = $value;
                }
            }
            $paper_options['options_type'] = $options_type;
            //$data['paper_info'] = $paper;
            $data['paper_info']    = $answerData['paper_info'];
            $data['paper_options'] = $paper_options;
            // 组装用户作答数据
            $user_answer_data = [];
            foreach ($answerData['content'] as $question_id => $answer) {
                $item = [
                    'exmas_question_id' => $question_id,
                    'user_answer'       => $answer,
                ];
                array_push($user_answer_data, $item);
            }
            $answerData['content'] = $user_answer_data;
            unset($answerData['paper_info']);
            $data['exams_user_info'] = $answerData;
            $this->exitJson($data, 1);
        }
        $this->exitJson((object) [], 0, '未获取到试卷信息');

    }

    /**
     * 错题再练
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-11-06
     * @return [type] [description]
     */
    public function wrongExams()
    {
        // 获取试卷ID
        $paper_id = intval($this->paper_id);
        // 获取试卷信息
        $paper = D("ExamsPaper", 'exams')->getPaperById($paper_id);
        if (!$paper) {
            $this->exitJson((object) [], 0, '暂无该试卷信息');
        }
        // 获取试卷试题等信息
        $paper_options = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById($paper_id);
        // 获取错误的答题记录
        $temp_id                 = intval($this->exams_users_id);
        $wrongList               = D("ExamsLogs", 'exams')->getWrongList($paper_id, $temp_id);
        $wrongList && $wrongList = getSubByKey($wrongList, 'exams_question_id');
        $new_options             = [];
        foreach ($paper_options['options_questions_data'] as $type_id => $list) {
            foreach ($list as $question) {
                in_array($question['exams_question_id'], $wrongList) && $new_options[$type_id][] = $question;
            }
        }
        // 删除无用的数据
        unset($paper_options['options_questions']);
        $paper_options['options_questions_data'] = $new_options;
        // 重置options_type值
        $options_type = [];
        foreach ($paper_options['options_type'] as $key => $value) {
            if (array_key_exists($value['question_type'], $new_options)) {
                $options_type[] = $value;
            }
        }
        $paper_options['options_type'] = $options_type;
        $paper['paper_options']        = $paper_options;
        $this->exitJson($paper, 1);
    }
}
