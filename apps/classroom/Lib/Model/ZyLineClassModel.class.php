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
     * @return 课程数据列表
     */
    public function getLineclassById($id,$type = 1)
    {
        $map['course_id'] = $id;
        if($type == 1){
            $map ['is_activity'] = 1;
            $map ['is_del'] = 0;
            $map ['uctime'] = array('gt', time());
        }

        $data = $this->where($map)->find();
        if($data){
            $data['uid'] = !$data['uid'] ? 0 : $data['uid'];
            $data['imageurl'] = getAttachUrlByAttachId($data['cover']);
            //计算实际价格
            $data['t_price'] = $data['course_price'];
            $data['price'] = getPrice($data, $this->mid, true, false,4);
            //获取讲师数据
            $teaMap['id'] = $data['teacher_id'];
            $teaField     = 'uid,name,Teach_areas';
            $teacher= D('ZyTeacher','classroom')->getTeacherInfoByMap($teaMap,$teaField);
            $data['teacher_name']= $teacher['name'];
            $data['teacher_uid'] = $teacher['uid'];
            $data['teach_areas'] = $teacher['Teach_areas'];
            //获取机构数据
            $schoolMap['id'] = $data['mhm_id'];
            $data['school_info']= model('School')->getSchoolFindStrByMap($schoolMap);
            $data['school_info']['logo'] = getAttachUrlByAttachId($data['school_info']['logo']);
            //是否已经收藏线下课
            $data['is_collect']  = D('ZyCollection')->where(array('source_id'=>$data['course_id'],'uid'=>$this->mid,'source_table_name'=>'zy_teacher_course'))->count();
            if($data['uctime'] < time()){
                $this->where ( 'course_id='.$data['course_id'] )->save(array('is_del'=>1));
            }
        }
        return $data;
    }

    /**根据课程id获取线下课程标题
     * @param $id课程id
     * @return null
     */
    public function getLineclassTitleById($id)
    {
        $map['course_id'] = $id;
        $data = $this->where($map)->getField('course_name');
        return $data;
    }

    /**获取用户购买的线下课程信息
     * @param $uid 用户id
     * @return $vids 课程数据列表
     */
    public function getUserBuyLineclass($uid,$size = 10)
    {
        $map['uid']        = $uid;
        $map['is_del']     = 0;
        $map['pay_status'] = 3;

        $data = M('zy_order_teacher')->where($map)->field('video_id')->order('ctime desc')->findPage($size);
        foreach($data['data'] as $key=>$val){
            $val = $this->getLineclassById($val['video_id']);
            unset($val['is_collect']);
            $data['data'][$key] = $val;
        }
        return $data;
    }
}

?>