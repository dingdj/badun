<?php
/**
 * 考试管理--试卷试题数据模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsPaperOptionsModel extends Model
{

    protected $tableName = 'exams_paper_options';

    /**
     * 获取单个试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getPaperOptionsById($paper_id = 0)
    {
        $paper = $this->where('exams_paper_id=' . $paper_id)->find();
        if ($paper) {
            $paper = $this->haddleData($paper);
        }
        return $paper;
    }
    /**
     * 数据处理
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  array $data [description]
     * @return [type] [description]
     */
    protected function haddleData($data = array())
    {
        if ($data) {
            // 解析试题类型
            $data['options_type'] = unserialize($data['options_type']);
            if ($data['options_type']) {
                foreach ($data['options_type'] as $key => $val) {
                    $data['options_type'][$key]['type_info'] = M("exams_question_type")->where("exams_question_type_id=" . $val['question_type'])->find();
                }
            }

            // 解析试题ID
            $data['options_questions'] = unserialize($data['options_questions']);
            foreach ($data['options_questions'] as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $question_id) {
                        $data['options_questions_data'][$k][] = D('ExamsQuestion', 'exams')->getQuestionById($question_id);
                    }
                }
            }

        }
        return $data;
    }

    /**
     * 添加试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     */
    public function addOptionsType($type_data = array(), $paper_id = 0)
    {
        if ($paper_id) {
            // 检测当前试卷是否有记录
            $hasOne = $this->where("exams_paper_id=" . $paper_id)->find();
            if ($hasOne) {
                $options_type = unserialize($hasOne['options_type']);
                array_push($options_type, $type_data);
                return $this->where("exams_paper_id=" . $paper_id)->save(['options_type' => serialize($options_type)]);
            } else {
                $data = [
                    'options_type'      => serialize([$type_data]),
                    'exams_paper_id'    => $paper_id,
                    'questions_count'   => 0,
                    'score'             => 0,
                    'options_questions' => serialize([]),
                ];
                return $this->add($data);
            }
        }
    }

    /**
     * 删除试题类型
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $type_id [description]
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function delOptionsType($type_id = -1, $paper_id = 0)
    {
        if ($type_id >= 0 && $paper_id) {
            // 获取数据
            $paper = $this->getPaperOptionsById($paper_id);
            if (is_array($paper['options_type'])) {
                $options_type = $paper['options_type'];
                if (isset($options_type[$type_id])) {
                    // 获取该条数据ID
                    $question_type_id = $options_type[$type_id]['question_type'];
                    // 删除对应试题类型的题目
                    unset($paper['options_questions'][$question_type_id]);
                    unset($options_type[$type_id]);
                    // 重组数据
                    $options_type = array_values($options_type);
                    $data         = [
                        'options_questions' => serialize($paper['options_questions']),
                        'options_type'      => serialize($options_type),
                    ];
                    if ($res = $this->where("exams_paper_id=" . $paper_id)->save($data)) {
                        $this->updateQuestionCount($paper_id);
                        return $res;
                    }
                }

            }
        }
        return false;
    }

    /**
     * 添加试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  integer $paper_id [description]
     * @param  integer $question_type [description]
     * @param  array $options [description]
     */
    public function addQuestions($paper_id = 0, $question_type = 0, $options = [])
    {
        if (!$paper_id || !$question_type | !$options) {
            return false;
        }
        // 获取数据
        $paper = $this->getPaperOptionsById($paper_id);
        // 如果已经存在
        if (isset($paper['options_questions'][$question_type])) {
            $questions = $paper['options_questions'][$question_type];
            $options   = array_unique(array_merge($questions, $options));
        }
        $paper['options_questions'][$question_type] = $options;
        if ($res = $this->where("exams_paper_id=" . $paper_id)->save(['options_questions' => serialize($paper['options_questions'])])) {
            $this->updateQuestionCount($paper_id);
            return $res;
        }
    }
    /**
     * 根据试题类型获取已经设置的试题ID
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  integer $paper_id [description]
     * @param  integer $question_type [description]
     * @return [type] [description]
     */
    public function getQuestionIdByType($paper_id = 0, $question_type = 0)
    {
        // 获取数据
        $paper = $this->getPaperOptionsById($paper_id);
        return isset($paper['options_questions'][$question_type]) ? $paper['options_questions'][$question_type] : [];
    }

    /**
     * 删除试卷中的试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  [type] $paper_id [description]
     * @param  [type] $question_type [description]
     * @param  [type] $question_id [description]
     * @return [type] [description]
     */
    public function delQuestion($paper_id, $question_type, $question_id)
    {
        // 获取数据
        $paper = $this->getPaperOptionsById($paper_id);
        // 如果已经存在
        if (isset($paper['options_questions'][$question_type])) {
            $questions = $paper['options_questions'][$question_type];
            array_splice($questions, array_search($question_id, $questions), 1);
            $paper['options_questions'][$question_type] = $questions;
            if ($res = $this->where("exams_paper_id=" . $paper_id)->save(['options_questions' => serialize($paper['options_questions'])])) {
                $this->updateQuestionCount($paper_id);
                return $res;
            }
        }
        return false;
    }

    /**
     * 更新试题类型分数
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  [type] $paper [description]
     * @param  [type] $question_type [description]
     * @param  [type] $score [description]
     * @return [type] [description]
     */
    public function updateQuestionTypeScore($paper_id, $question_type, $score)
    {
        // 获取数据
        $paper = $this->getPaperOptionsById($paper_id);
        if ($paper) {
            foreach ($paper['options_type'] as &$item) {
                if ($item['question_type'] == $question_type) {
                    $item['score'] = $score;
                    break;
                }
            }
            if ($res = $this->where("exams_paper_id=" . $paper_id)->save(['options_type' => serialize($paper['options_type'])])) {
                $this->updateQuestionCount($paper_id);
                return $res;
            }
        }
        return false;
    }

    /**
     * 更新试题总数和试题分数
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  [type] $paper_id [description]
     * @return [type] [description]
     */
    public function updateQuestionCount($paper_id)
    {
        // 获取数据
        $paper           = $this->getPaperOptionsById($paper_id);
        $score           = 0;
        $questions_count = 0;
        foreach ($paper['options_type'] as $type) {
            if (isset($paper['options_questions'][$type['question_type']])) {
                $count = count($paper['options_questions'][$type['question_type']]);
                $score += $type['score'] * $count;
                $questions_count += $count;
            }
        }
        return $this->where("exams_paper_id=" . $paper_id)->save(['score' => $score, 'questions_count' => $questions_count]);
    }

}
