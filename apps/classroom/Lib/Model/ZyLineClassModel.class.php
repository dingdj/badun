<?php
/**
 * Created by Ashang.
 * 云课堂教师风采模型
 * Date: 14-10-7
 * Time: 下午3:40
 */

class ZyLineClassModel extends Model {
    var $tableName = 'zy_teacher_course';
    protected $error = '';
    public $mid = 0;

    /**根据课程id获取线下课程信息
     * @param $id课程id
     * @return null
     */
    public function getLineclassById($id)
    {
        $map['course_id'] = $id;
        $map ['is_activity'] = 1;
        $map ['is_del'] = 0;
        $map ['uctime'] = array('gt', time());

        $data = $this->where($map)->find();
        $data['uid'] = !$data['uid'] ? 0 : $data['uid'];
        $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
        //计算实际价格
        $data['t_price'] = $data['course_price'];
        $data['price'] = getPrice($data, $this->mid, true, false,4);
        $data['teacher_name']  = D('ZyTeacher','classroom')->where(array('id'=>$data['teacher_id']))->getField('name');
        $data['teach_areas'] = D('ZyTeacher')->where('id='.$data['teacher_id'])->getField('Teach_areas');
        if($data['uctime'] < time()){
            $this->where ( 'course_id='.$data['course_id'] )->save(array('is_del'=>1));
        }
        return $data;
    }

}

?>