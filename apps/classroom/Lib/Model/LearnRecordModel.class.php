<?php
/**
 * 学习记录管理模型
 * @author Ashang <Ashang@phpzsm.com>
 * @version CY1.0
 */
class LearnRecordModel extends Model {

    var $tableName = 'learn_record';
    protected $error = '';
    public $mid = 0;

    /**
     * 获取学习记录列表
     * @param array  $map
     * @param $type 类型
     * @param string $order
     * @param $limit 记录数量
     *
     * @return array
     */
    public function getList($map = array(),$type = 1,$order = 'ctime desc',$limit = 20){

        if ($type == 1) {
            $field = 'id,video_title,video_question_count';
            $list = D('ZyVideo')->where($map)->field($field)->order($order)->findPage($limit);
        }else{
            $list = model('User')->where($map)->field('uid')->order($order)->findPage($limit);
        }
        foreach ($list['data'] as $key=>$val) {
            if ($type == 1) {
                if($val['is_activity'] != 1){
                    unset($list['data'][$key]);
                }else{
                    $list['data'][$key]['video_order_count'] = M('zy_order_course')->where('video_id='.$val['id'])->count();
                    $list['data'][$key]['video_section'] = M('zy_video_section')->where(array('vid' => $val['id'], 'pid' => ['gt', 0], 'cid' => ['gt', 0]))->count();
                    $list['data'][$key]['learn_nums'] = $this->where(array('vid' => $val['id']))->field('uid')->count();
                    $list['data'][$key]['learn_time'] = $this->where(array('vid' => $val['id']))->sum('totaltime') ? : 0;
                }
            } else {
                $list['data'][$key]['uname'] = getUserSpace($val['uid']);
                $list['data'][$key]['course_order'] = M('zy_order_course')->where(array('is_del' => 0, 'pay_status' => 3, 'uid' => $val['uid']))->count();
                $list['data'][$key]['learn_time'] = $this->where(array('uid' => $val['uid']))->sum('totaltime') ? : 0;
                $list['data'][$key]['question'] = M('zy_question')->where(array('uid' => $val['uid']))->count();
                $list['data'][$key]['answers'] = M('zy_wenda_comment')->where(array('is_del' => 0, 'uid' => $val['uid']))->count();
                $list['data'][$key]['topics'] = M('group_topic')->where(array('is_del' => 0, 'uid' => $val['uid']))->count();
                $list['data'][$key]['exams'] = M('ex_user_exam')->where(array('user_exam_is_del' => 0, 'user_id' => $val['uid']))->count();
            }
        }
        return $list;
    }


}
