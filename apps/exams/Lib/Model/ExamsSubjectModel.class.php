<?php
/**
 * 考试管理--专业模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsSubjectModel extends Model
{

    protected $tableName = 'exams_subject';

    /**
     * 获取多级的分类名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $subject_id [description]
     * @return [type] [description]
     */
    public function getSubjectNetWorkName($subject_id = 0)
    {
        $title = '';
        if ($subject_id) {
            $subject = $this->where(['exams_subject_id' => $subject_id])->find();
            $title   = $subject['title'];
            if ($subject['pid'] > 0) {
                return ltrim($this->getSubjectNetWorkName($subject['pid']) . '/' . $title, '/');
            }
        }
        return $title;
    }

    /**
     * 获取ID分类全路径
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $subject_id [description]
     * @return [type] [description]
     */
    public function getFullPathById($subject_id = 0)
    {
        if ($subject_id) {
            $subject = $this->where(['exams_subject_id' => $subject_id])->find();
            if ($subject['pid'] > 0) {
                return ltrim($this->getFullPathById($subject['pid']) . ',' . $subject_id, ',');
            }
        }
        return $subject_id;
    }
}
