<?php
/**
 * 考试管理--试题记录模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsLogsModel extends Model
{

    protected $tableName = 'exams_logs';

    /**
     * 添加记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @param  array $data [description]
     */
    public function addLog($data = [])
    {
        $count = 0;
        foreach ($data['data'] as $log) {
            $exists = [
                'exams_paper_id'    => $log['exams_paper_id'],
                'exams_question_id' => $log['exams_question_id'],
                'uid'               => $this->mid,
                'is_right'          => $log['is_right'],
                'exams_users_id'    => $data['exams_users_id'],
            ];
            // 如果更新成功表示有记录
            if ($this->where($exists)->save(['update_time' => time()])) {
                $count++;
            } else {
                $log['update_time']    = time();
                $log['uid']            = $this->mid;
                $log['exams_users_id'] = $data['exams_users_id'];
                $this->add($log) && $count++;
            }
        }
        return $count;
    }

    /**
     * 获取单个试卷的试题记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getListByPaperId($paper_id = 0,$exams_users_id = 0)
    {
        return $this->where(['exams_paper_id' => $paper_id, 'uid' => $this->mid,'exams_users_id'=>$exams_users_id])->select();
    }

    /**
     * 获取单个试卷的正确答题记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getRightList($paper_id = 0,$exams_users_id = 0)
    {
        return $this->where(['is_right' => 1, 'exams_paper_id' => $paper_id, 'uid' => $this->mid,'exams_users_id'=>$exams_users_id])->select();
    }

    /**
     * 获取单个试卷的错误答题记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getWrongList($paper_id = 0,$exams_users_id = 0)
    {
        return $this->where(['is_right' => 0, 'exams_paper_id' => $paper_id, 'uid' => $this->mid,'exams_users_id'=>$exams_users_id])->select();
    }

}
