<?php
/**
 * 考试管理--试卷模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsPaperModel extends Model
{

    protected $tableName   = 'exams_paper';
    protected $order_field = [
        'default' => 'sort DESC,exams_count DESC,create_time DESC',
        'new'     => 'create_time desc',
        'hot'     => 'exams_count desc',
    ];
    /**
     * 获取试卷分页列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  array $map [description]
     * @param  integer $limit [description]
     * @return array [description]
     */
    public function getPaperPageList($map = [], $limit = 20, $order = 'default')
    {
        $map['is_del'] = 0;
        $order         = isset($this->order_field[$order]) ? $this->order_field[$order] : $order;
        $list          = $this->where($map)->order($order)->findPage($limit);
        if ($list['data']) {
            $list['data'] = $this->haddleData($list['data']);

        }
        return $list;
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
            $subjectModel = D('ExamsSubject', 'exams');
            foreach ($data as $key => $v) {
                $data[$key]['paper_subject']          = $subjectModel->getSubjectNetWorkName($v['exams_subject_id']);
                $data[$key]['paper_subject_fullpath'] = $subjectModel->getFullPathById($v['exams_subject_id']);
                $data[$key]['exams_module_title']     = $this->getModuleTitleAttr($v['exams_module_id']);
                $data[$key]['level_title']            = $this->getLevelTitle($v['level']);
                $paper_options = D("ExamsPaperOptions",'exams')->getPaperOptionsById($v['exams_paper_id']);
                $data[$key]['questions_count'] = $paper_options['questions_count'];
                $data[$key]['score'] = $paper_options['score'];
            }
        }
        return $data;
    }
    /**
     * 获取试卷的所属模块名称
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
     * 获取试卷难度名称
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
     * 获取单个试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getPaperById($paper_id = 0)
    {
        $paper = $this->where('exams_paper_id=' . $paper_id)->find();
        if ($paper) {
            $paper = $this->haddleData([$paper])[0];
        }
        return $paper;
    }
}
