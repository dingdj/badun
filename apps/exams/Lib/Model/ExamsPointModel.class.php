<?php
/**
 * 考试管理--考点模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsPointModel extends Model
{

    protected $tableName = 'exams_point';
    /**
     * 获取考点分页列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  array $map [description]
     * @param  integer $limit [description]
     * @return array [description]
     */
    public function getPointPageList($map = [], $limit = 20)
    {
        $list = $this->where($map)->findPage($limit);
        if ($list['data']) {
            $subjectModel = D('ExamsSubject', 'exams');
            foreach ($list['data'] as $key => $point) {
                $list['data'][$key]['point_subject']          = $subjectModel->getSubjectNetWorkName($point['exams_subject_id']);
                $list['data'][$key]['point_subject_fullpath'] = $subjectModel->getFullPathById($point['exams_subject_id']);
            }
        }
        return $list;
    }
    /**
     * 根据考点ID获取单条数据
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  integer $point_id [description]
     * @return [type] [description]
     */
    public function getPointById($point_id = 0)
    {
        $point = $this->where('exams_point_id=' . $point_id)->find();
        if ($point) {
            $subjectModel                    = D('ExamsSubject', 'exams');
            $point['point_subject']          = $subjectModel->getSubjectNetWorkName($point['exams_subject_id']);
            $point['point_subject_fullpath'] = $subjectModel->getFullPathById($point['exams_subject_id']);
        }
        return $point;
    }
}
