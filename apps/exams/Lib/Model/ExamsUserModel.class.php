<?php
/**
 * 考试管理--用户考试记录模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsUserModel extends Model
{

    protected $tableName = 'exams_users';

    /**
     * 获取考试记录分页列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  array $map [description]
     * @param  integer $limit [description]
     * @return array [description]
     */
    public function getExamsUserPageList($map = [], $limit = 15)
    {
        !isset($map['progress']) && $map['progress']     = 100;
        !isset($map['exams_mode']) && $map['exams_mode'] = 2;
        $map['is_del']                                   = 0;
        // 如果设置了不限制进度
        if ($map['progress'] == -1) {
            unset($map['progress']);
        }
        $list = $this->where($map)->order('update_time desc')->findPage($limit);
        if ($list['data']) {
            $list['data'] = $this->haddleData($list['data']);
        }
        return $list;
    }
    /**
     * 处理数据
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @param  [type] $data [description]
     * @return [type] [description]
     */
    protected function haddleData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $e) {
                $data[$key]['paper_info'] = D('ExamsPaper', 'exams')->getPaperById($e['exams_paper_id']);
                // 分析答题内容
                $data[$key]['content'] = unserialize($e['content']);
                // 计算正确率
                $data[$key]['right_rate']    = (round($e['right_count'] / ($e['right_count'] + $e['wrong_count']), 2) * 100) . '%';
                $data[$key]['examiner_data'] = unserialize($e['examiner_data']);
            }
        }
        return $data;
    }

    /**
     * 处理提交的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-18
     * @return [type] [description]
     */
    public function doExamsPaper($data)
    {
        // 获取试卷ID
        $paper_id = intval($data['paper_id']);
        // 分析试题
        $questions      = $this->filter_array($data['user_answer']);
        $exams_users_id = isset($data['exams_users_id']) ? $data['exams_users_id'] : 0;
        $right_count    = 0;
        $wrong_count    = 0;
        $score          = 0;
        // 获取试卷配置
        $options_type    = D('ExamsPaperOptions', 'exmas')->where('exams_paper_id=' . $paper_id)->getField('options_type');
        $options_type    = unserialize($options_type);
        $scoreConfig     = array_column($options_type, 'score', 'question_type');
        $question_answer = [];
        $logs            = []; // 保存记录信息
        $status          = 1;
        // 是否错题练习
        $is_wrongexams = isset($data['is_wrongexams']);
        foreach ($questions as $question_id => &$answer) {
            // 统一转换为数组处理
            if (is_string($answer)) {
                $answer = [$answer];
            }
            array_push($question_answer, $question_id);
            // 获取试题信息
            $question_info = D("ExamsQuestion", 'exams')->getQuestionById($question_id);
            $is_right      = 0;
            switch ($question_info['type_info']['question_type_key']) {
                case 'radio':
                case 'judge':
                case 'multiselect':
                case 'completion':
                    // 检测正误
                    $answer_true_option = $question_info['answer_true_option'];
                    if ($answer && count(array_diff($answer_true_option, $answer)) === 0) {
                        $right_count += 1;
                        // 累计分数
                        $score += $scoreConfig[$question_info['exams_question_type_id']];
                        $is_right = 1;
                    } else {
                        $wrong_count += 1;
                    }
                    break;
                case 'essays':
                    // 存在论述题,需要审阅
                    $status = 0;
                    break;
            }

            array_push($logs, ['exams_paper_id' => $paper_id, 'exams_question_id' => $question_id, 'is_right' => $is_right]);

        }
        $all_question_ids = [];
        $end_questions    = array_keys($questions);
        // 检测未填题
        if (!$is_wrongexams) {
            $options_questions = D('ExamsPaperOptions', 'exmas')->where('exams_paper_id=' . $paper_id)->getField('options_questions');
            $options_questions = unserialize($options_questions);

            foreach ($options_questions as $value) {
                $all_question_ids = array_merge($all_question_ids, $value);
                foreach ($value as $q_id) {
                    // 记录试题
                    !in_array($q_id, $end_questions) && array_push($logs, ['exams_paper_id' => $paper_id, 'exams_question_id' => $q_id, 'is_right' => 0]);
                }
            }
        } else {
            // 获取试题
            $wrongList = D("ExamsLogs", 'exams')->getWrongList($paper_id, $data['wrongexams_temp']);
            foreach ($wrongList as $wrong) {
                $all_question_ids[] = $wrong['exams_question_id'];
                !in_array($wrong['exams_question_id'], $end_questions) && array_push($logs, ['exams_paper_id' => $paper_id, 'exams_question_id' => $wrong['exams_question_id'], 'is_right' => 0]);
            }
        }
        $wrong_count += count(array_diff($all_question_ids, $question_answer));
        // 计算完成率
        // 答题数 / 试题总数 * 100%
        $questions_count = (int) D('ExamsPaperOptions', 'exmas')->where('exams_paper_id=' . $paper_id)->getField('questions_count');
        $completion_rate = round(count($questions) / $questions_count, 2) * 100;
        $data            = [
            'right_count'     => $right_count,
            'wrong_count'     => $wrong_count,
            'score'           => $score,
            'exams_paper_id'  => $paper_id,
            'content'         => serialize($questions),
            'status'          => $status,
            'uid'             => $this->mid,
            'examiner_uid'    => 0,
            'anser_time'      => intval($data['anser_time']),
            'create_time'     => time(),
            'update_time'     => time(),
            'progress'        => 100,
            'exams_mode'      => intval($data['exams_mode']) ?: 2,
            'completion_rate' => $completion_rate . '%',
            'pid'             => isset($data['wrongexams_temp']) ? intval($data['wrongexams_temp']) : 0,
        ];
        // 如果是继续答题,执行修改
        if (!$exams_users_id) {
            $res = $this->add($data);
            // 记录试题
            D('ExamsLogs', 'exams')->addLog(['data' => $logs, 'exams_users_id' => $res]);
            if ($res && $data['exams_mode'] == 2) {
                D("ExamsPaper", 'exams')->where('exams_paper_id=' . $paper_id)->setInc('exams_count');
            }
            return $res;
        } else {
            // 记录试题
            D('ExamsLogs', 'exams')->addLog(['data' => $logs, 'exams_users_id' => $exams_users_id]);
            $data['exams_users_id'] = $exams_users_id;
            return $this->save($data);
        }
    }

    /**
     * 处理下次在做的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @param  [type] $data [description]
     */
    public function addProgressExams($data)
    {
        // 获取试卷ID
        $paper_id = intval($data['paper_id']);
        // 分析试题
        $questions      = $this->filter_array($data['user_answer']);
        $exams_users_id = isset($data['exams_users_id']) ? $data['exams_users_id'] : 0;
        foreach ($questions as $question_id => &$answer) {
            // 统一转换为数组处理
            if (is_string($answer)) {
                $answer = [$answer];
            }
        }
        if ($data['is_timeout'] == 0) {
            // 获取总题数
            $questions_count = (int) D('ExamsPaperOptions', 'exmas')->where('exams_paper_id=' . $paper_id)->getField('questions_count');
            $progress        = (round(count($questions) / $questions_count, 2)) * 100;
        } else {
            $progress = 100;
        }
        if (!$exams_users_id) {
            $data = [
                'right_count'     => 0,
                'wrong_count'     => 0,
                'score'           => 0,
                'exams_paper_id'  => $paper_id,
                'content'         => serialize($questions),
                'status'          => 0,
                'uid'             => $this->mid,
                'examiner_uid'    => 0,
                'anser_time'      => intval($data['anser_time']),
                'create_time'     => time(),
                'update_time'     => time(),
                'progress'        => $progress,
                'exams_mode'      => intval($data['exams_mode']) ?: 2,
                'completion_rate' => $progress . '%',
            ];
            $res = $this->add($data);
            // 记录试题
            D('ExamsLogs', 'exams')->addLog(['data' => $logs, 'exams_users_id' => $res]);
            if ($res && $data['exams_mode'] == 2) {
                D("ExamsPaper", 'exams')->where('exams_paper_id=' . $paper_id)->setInc('exams_count');
            }
            return $res;
        } else {
            $data = [
                'exams_users_id'  => $exams_users_id,
                'content'         => serialize($questions),
                'anser_time'      => intval($data['anser_time']),
                'update_time'     => time(),
                'progress'        => $progress,
                'completion_rate' => $progress . '%',
            ];
            return $this->save($data);
        }
    }

    /**
     * 去除多维数组中的空值
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @return mixed
     * @param $arr 目标数组
     * @param array $values 去除的值  默认 去除  '',null,false,0,'0',[]
     */
    public function filter_array(&$arr, $values = ['', null, []])
    {
        foreach ($arr as $k => $v) {
            if (is_array($v) && count($v) > 0) {
                $arr[$k] = $this->filter_array($v, $values);
            }
            foreach ($values as $value) {
                if ($v === $value) {
                    unset($arr[$k]);
                    break;
                }
            }
        }
        return $arr;
    }

    /**
     * 获取单条考试记录信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function getExamsInfoById($id = 0)
    {
        $info = $this->where('exams_users_id=' . $id)->find();
        if ($info) {
            $info = $this->haddleData([$info])[0];
        }
        return $info;
    }

    /**
     * 根据条件获取单条考试记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-19
     * @param  array $map [description]
     * @return [type] [description]
     */
    public function getExamsInfoByMap($map = [])
    {
        $info = $this->where($map)->find();
        if ($info) {
            $info = $this->haddleData([$info])[0];
        }
        return $info;
    }

    /**
     * 获取排名
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @return [type] [description]
     */
    public function getRankList($exams_users_id = 0, $count = 10)
    {
        // 获取当前排名前$count的名次
        $top    = 'top' . $count;
        $prefix = C('DB_PREFIX');
        $sql    = 'SELECT *, CASE WHEN @prevRank = score THEN @curRank WHEN @prevRank := score THEN @curRank := @curRank + 1 END AS rank from ' . $prefix . 'exams_users,(SELECT @curRank := 0,@prevRank := NULL) r ORDER BY score DESC LIMIT 0,' . $count;
        $list   = $this->query($sql);

        // 获取当前排名
        $sql     = 'select *,(select count(1) from ' . $prefix . 'exams_users where score >= (select score from ' . $prefix . 'exams_users where exams_users_id = ' . $exams_users_id . ' order by score desc limit 1)) as rank from ' . $prefix . 'exams_users where exams_users_id = ' . $exams_users_id . ';';
        $nowRank = $this->query($sql);
        return [
            'list' => $list,
            'now'  => $nowRank ? $nowRank[0] : [],
        ];
    }

    /**
     * 是否超过考试次数限制
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-26
     * @param  integer $paper_id [description]
     * @param  integer $exams_limit [description]
     * @return boolean [description]
     */
    public function isLimit($paper_id = 0, $exams_limit = 0)
    {
        if ($exams_limit == 0) {
            return false;
        }

        return (int) $this->where(['exams_mode'=>2,'exams_paper_id' => $paper_id, 'pid' => 0])->count() >= $exams_limit ? true : false;
    }
}
