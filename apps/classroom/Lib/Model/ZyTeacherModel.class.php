<?php
/**
 * Created by Ashang.
 * 云课堂教师风采模型
 * Date: 14-10-7
 * Time: 下午3:40
 */

class ZyTeacherModel extends Model {
    public $mid = 0;

    /**根据讲师id获取讲师信息
     * @param $id讲师id
     * @return null
     */
    public function getTeacherInfo($id){
        if(intval($id)==0)return null;
        $teacher_info=$this->where(array('id'=>$id))->find();
        return $teacher_info;
    }

    /**
     * @name 根据条件获得获取讲师的某个字段
     */
    public function getTeacherStrByMap($map,$field){
        $teacher_str = $this->where($map)->getField($field);
        return $teacher_str;
    }
    
    /**
     * @name 搜索
     */
    public function getListBySearch($map,$limit = 6,$order = ''){
        $map['is_del'] = 0;
        $teacher = $this->where($map)->order($order)->findPage($limit);
        if($teacher['data']){
            foreach($teacher['data'] as &$teacherInfo){
                $teacherInfo['headimg'] = getCover($teacherInfo['head_id'] , 150 , 150);
                $teacherInfo['follow_state'] = model('Follow')->getFollowState($this->mid , $teacherInfo['uid']);
                $time = time();
                $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND teacher_id= ".$teacherInfo['id'];
                $teacherInfo['video_count']   = D('ZyVideo','classroom')->where($where)->count() ?:'0';
                $count_info['video_count'] = $teacherInfo['video_count'];
                $teacherInfo['ext_info'] = model('User')->formatForApi($count_info,$teacherInfo['uid'],$this->mid);
                
            }
        }
        return $teacher;
    }

    /**
     * @name 获取某机构下的所有讲师
     * @$school_id  机构ID
     * @return  array  讲师ID集合
     */
    public function getSchoolAllTeacher($school_id){
        $map = array('mhm_id'=>$school_id,'is_del'=>0,'is_reject'=>0);
        $tid = $this->where($map)->field('id')->findALL();
        foreach($tid as $k=>&$v){
            $new_tid[] = implode(',',$v);
        }
        return $new_tid;
    }
}

?>