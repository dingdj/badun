<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class TeacherAction extends Action
{
    protected $teacher = null;//讲师模型对象
    protected $passport=null;
	//初始化
	public function _initialize() { 
		$this->teacher=D('ZyTeacher',"classroom");
        $this->passport = model('Passport');
	}
	public function index() {
        $teacher_schedule=M("zy_teacher_schedule")->where("pid=0")->findALL();
        $this->assign("teacher_schedule",$teacher_schedule);
		$this->display();
	}
	/**
     * 讲师详情页面
     */
    public function view(){
        $id   = intval($_GET['id']);
        $data = $this->teacher->getTeacherInfo($id);
        $user=model("User")->where("uid=".$data["uid"])->find();
        $data["sex"]=$user["sex"];
        $data["teacher_schedule"] = explode(",",$data["teacher_schedule"]);
        //教师课程
        $teacher_course=M("zy_teacher_course")->where("course_teacher=".$data['uid'])->findALL();
        $data["course_count"] = count($teacher_course);
        foreach ($teacher_course as $key => $value) {
            $teacher_review=M('zy_teacher_review')->where("course_id=".$value["course_id"])->field('star')->findAll();
            if($teacher_review){
                $review= $teacher_review;
            }
        }
        if($review){
            $data["star"]=intval(array_sum(getSubByKey($review,'star'))/count($review));
        }else{
            $data["star"]=0;
        }
        //课程安排表
        $teacher_schedule=M("zy_teacher_schedule")->where("pid=0")->findALL();
        $teacher_level=array();
        for ($i=0; $i <3 ; $i++) { 
            foreach ($teacher_schedule as $key => $value) {
                $level=M("zy_teacher_schedule")->where("pid=".$value["id"])->findALL();
                $teacher_level[$i][]=$level[$i];
            }
        }
        //可支配余额
        $data['balance'] = D("zyLearnc","classroom")->getUser($this->mid);
        
        //教师视频
        $video_count=M("zy_video")->where("teacher_id=".$id)->findALL();
        $data["video_count"]=count($video_count);
        //获取讲师推荐
        $recTeacher = M("zy_teacher")->where(array('id'=>array('neq',$id)))->order('reservation_count DESC')->limit(3)->select();
        //获取推荐的课程
        $recClass=M("zy_video")->field('id,teacher_id,video_title,cover')->order('video_order_count desc')->where('is_del=0')->limit(3)->select();
        //获取讲师名
        $name = M('zy_teacher')->where(array('uid'=>array('in',array_unique(getSubBykey($recClass,'teacher_id')))))->field('head_id','uid,name')->findAll();
        foreach($recClass as $k=>&$value){
            for($i=0;$i<count($name);$i++){
                if($value['teacher_id'] == $name[$i]['uid']){
                    $value['name'] = $name[$i]['name'];
                    $value['head_id'] = $name[$i]['head_id'];
                }
            }
        }
        $this->assign('teacher_level',$teacher_level);
        $this->assign('teacher_course',$teacher_course);
        $this->assign('recTeacher',$recTeacher);
        $this->assign('recClass',$recClass);
        $this->assign("data",$data);
        $this->assign("teacher_schedule",$teacher_schedule);
        $this->display();
    }
    //预约课程
    public function buyCourse(){
        $map=array(
            'uid'=>$this->mid,
            'course_id'=>intval($_POST["course_id"]),
            'course_price'=>$_POST["course_price"],
            'teacher_id'=>intval($_POST["teacher_id"]),
            'teach_way'=>intval($_POST["teach_way"]),
            'ctime'=>time()
            );
        if(!$this->mid){
            exit(json_encode(array('status'=>'0','info'=>'请先登录')));
        }
        if (!$_POST['course_id']) {
            exit(json_encode(array('status'=>'0','info'=>'没有选择班级')));
        }
        if (M("zy_order_course")->where(array("uid"=>$this->mid,"course_id"=>intval($_POST['course_id'])))->find()) {
            exit(json_encode(array('status'=>'0','info'=>'您已预约此课程')));
        }
        if (!D('ZyLearnc',"classroom")->isSufficient($this->mid, $_POST["course_price"], 'balance')) {
            exit(json_encode(array('status'=>'0','info'=>'可支配的学币不足')));
        }
        $res=M("zy_order_course")->add($map);
        if($res){ 
            M()->query("UPDATE `".C('DB_PREFIX')."zy_teacher` SET `reservation_count`=`reservation_count`+1 WHERE `id`=".intval($_POST["teacher_id"]));
            //发送系统消息
            $teacher_name = M('teacher')->where('id='.$_POST['teacher_id'].' and is_del=0')->getField('name');
            $s['uid']   = $this->mid;
            $s['title'] = "恭喜您约课成功";
            $s['body']  = "恭喜您成功约课".$teacher_name."老师的课";
            $s['ctime'] = time();
            model('Notify')->sendMessage($s);
            exit(json_encode(array('status'=>'1','info'=>'约课成功')));
        }else{
            exit(json_encode(array('status'=>'0','info'=>'约课失败')));
        }
    }
	/**
     * 获取讲师列表方法
     */
    public function getList(){
        $where="t.is_del=0 ";
        $order="reservation_count desc";
        $week= $_GET['week'];
        $sex= intval($_GET['sex']);
        if($sex>0){
            $InSql="";
            $user = M("zy_teacher a ")->join(C('DB_PREFIX')."user u on a.uid=u.uid and u.uid")->where("sex=".$sex)->findALL();
            foreach ($user as $key => $value) {
                if(!strstr($InSql,$value["uid"])){
                    $InSql.=$value["uid"].",";
                }
            }
            $InSql=substr_replace($InSql, '', -1);
            $where .= " AND u.uid IN (". (string) $InSql . " )";

        }
        if($reservation>0){
            $InSql="";
            $result=M("zy_teacher_schedule")->where(array('id'=>array('IN',$reservation)))->findALL();
            foreach ($result as $key => $value) {
                $str=" teacher_schedule like '%".$value["id"]."%' and is_del=0 ";
                if($reservation == '1,2,3,4,5,6,7') {
                	$tacher = M('zy_teacher')->where('is_del=0')->select();
                }else{
                	$tacher = M('zy_teacher')->where($str)->select();
                }
                foreach ($tacher as $val) {
                   if(!strstr($InSql,$val["id"])){
                        $InSql.=$val["id"].",";
                    }
                }
            }
            $InSql=substr_replace($InSql, '', -1);
            $where .= " AND id IN (". (string) $InSql . " )";
        }
        $data=M('zy_teacher t')->join(C('DB_PREFIX')."user u ON  u.uid=t.uid ")->where($where)->order($order)->findPage(30);
        if ($data['data']) {
             foreach ($data['data'] as $key => &$value) {
                $max_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price desc")->field("course_price")->find();
                $min_price=M("zy_teacher_course")->where("course_teacher=".$value["id"])->order("course_price")->field("course_price")->find();
                $value["max_price"]=$max_price ? $max_price["course_price"]: 0;
                $value["min_price"]=$min_price ? $min_price["course_price"]: 0;
                $value["video"] = M('zy_video')->where('is_del=0 and teacher_id='.$value['id'])->order('video_order_count desc')->field('id,video_title')->find();
            }
            $this->assign('listData', $data['data']);
            $this->assign('sex', $sex);
            $this->assign('reservation', $reservation);
            $html = $this->fetch('index_list');
        }else{
            $html="<div style=\"margin-top:20px;\">对不起，没有找到符合条件的教师T_T</div>";
        }
        $data['data'] = $html;
        echo json_encode($data);
        exit;
    }

	
		
	
}