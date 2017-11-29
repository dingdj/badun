<?php
/**
 * 考试管理--试题模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsQuestionModel extends Model
{

    protected $tableName = 'exams_question';

    /**
     * 获取试题的分页列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  array $map [description]
     * @param  integer $limit [description]
     * @return [type] [description]
     */
    public function getQuestionPageList($map = [], $limit = 20)
    {
        $map['is_del'] = 0;
        $list          = $this->where($map)->findPage($limit);
        if ($list['data']) {

            $list['data'] = $this->haddleData($list['data']);
        }
        return $list;
    }

    /**
     * 处理数据
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  [type] $list [description]
     * @return [type] [description]
     */
    protected function haddleData($list)
    {
        if (is_array($list)) {
            $subjectModel = D('ExamsSubject', 'exams');
            foreach ($list as $key => $v) {
                $list[$key]['exams_question_id']      = intval($v['exams_question_id']);
                $list[$key]['exams_question_type_id'] = intval($v['exams_question_type_id']);
                $list[$key]['exams_subject_id']       = intval($v['exams_subject_id']);
                $list[$key]['exams_module_id']        = intval($v['exams_module_id']);
                $list[$key]['exams_point_id']         = intval($v['exams_point_id']);

                $list[$key]['question_subject']          = $subjectModel->getSubjectNetWorkName($v['exams_subject_id']);
                $list[$key]['question_subject_fullpath'] = $subjectModel->getFullPathById($v['exams_subject_id']);
                $list[$key]['exams_module_title']        = $this->getModuleTitleAttr($v['exams_module_id']);
                $list[$key]['exams_point_title']         = $this->getPointTitle($v['exams_point_id']);

                $list[$key]['level_title']               = $this->getLevelTitle($v['level']);
                $list[$key]['answer_options']            = unserialize($v['answer_options']) ?: [];
                $list[$key]['answer_true_option']        = unserialize($v['answer_true_option']) ?: [];
                $list[$key]['type_info']                 = M("exams_question_type")->where("exams_question_type_id=" . $v['exams_question_type_id'])->find();
                $list[$key]['exams_question_type_title'] = $list[$key]['type_info']['question_type_title'];
                // 是否已经收藏
                $list[$key]['is_collect'] = (int) D('ZyCollection', 'classroom')->isCollect($v['exams_question_id'], 'exams_question', $this->mid);
                // 是否已经做过此题
                $list[$key]['is_do'] = D('ExamsLogs', 'exams')->where(['uid' => $this->mid, 'exams_question_id' => $v['exams_question_id']])->count() > 0 ? 1 : 0;
            }
        }
        return $list;
    }
    /**
     * 获取试题的所属模块名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  [type] $module_id [description]
     * @return [type] [description]
     */
    protected function getModuleTitleAttr($module_id)
    {
        if ($module_id == 0) {
            return '知识点练习';
        }
        return M('exams_module')->where('exams_module_id=' . $module_id)->getField('title');
    }

    /**
     * 获取考点名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  [type] $exams_point_id [description]
     * @return [type] [description]
     */
    public function getPointTitle($exams_point_id)
    {
        return M('exams_point')->where('exams_point_id=' . $exams_point_id)->getField('title');
    }

    /**
     * 获取试题分类名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  [type] $type_id [description]
     * @return [type] [description]
     */
    public function getQuestionTypeTitle($type_id)
    {
        return M('exams_question_type')->where('exams_question_type_id=' . $type_id)->getField('question_type_title');
    }

    /**
     * 获取试题难度名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  [type] $level [description]
     * @return [type] [description]
     */
    public function getLevelTitle($level)
    {
        $title = [1 => '简单', 2 => '普通', 3 => '困难'];
        return $title[$level];
    }
    /**
     * 添加试题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  array $data [description]
     */
    public function addQuestion($data = [])
    {
        // 相关时间记录
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $this->add($data);
    }

    public function editQuestion($data = [], $question_id)
    {
        $data['update_time'] = time();
        return $this->where('exams_question_id=' . $question_id)->save($data);
    }

    /**
     * 根据试题ID获取试题信息
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-16
     * @param  integer $question_id [description]
     * @return [type] [description]
     */
    public function getQuestionById($question_id = 0)
    {
        $data = $this->where('exams_question_id=' . $question_id)->find();
        if ($data) {
            $data = $this->haddleData([$data])[0];
        }
        return $data;
    }
}
