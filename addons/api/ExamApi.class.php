<?php
/**
 * 在线考试api
 * utime : 2016-04-08
 */

class ExamApi extends Api{
    protected $exam = null; //课程模型对象
    protected $userExam = null; //分类数据模型
    protected $paper = null; //分类数据模型

    /**
     * 初始化模型
     */
    public function _initialize() {
        $this->exam      = D('ExExam','exam');
        $this->userExam  = D('ExUserExam','exam');
        $this->paper     = D('ExPaper','exam');
    }

    //获取考试分类
    public function getCategory(){
        $all  = array(0=>array( 'ex_exam_category_id'=>'0','title'=>'全部') );
        $list = M('ex_exam_category')->where('pid=0')->findAll();

        $list = array_merge($all , $list);
        $list ? $this->exitJson($list , 1) : $this->exitJson($all , 0);
    }
    
    //获取考试列表
    public function getExamList(){
        $cate_id   = $this->data['category_id'];
        $title     = $this->data['title'];
        $user_exam = $this->data['user_exam'];
        if($cate_id>0) {
            $map['exam_categoryid'] = $cate_id;
        }

        if( $title ) {
            $map['exam_name'] = array('like' , '%'.$title.'%');
        }
        if( $user_exam ) {
            $exam_id = M('ex_user_exam')->where('user_id='.$this->mid)->field('user_exam')->select();
            $exam_id = array_unique( getSubByKey($exam_id ,'user_exam') );
            $map['exam_id'] = array('in' , $exam_id);
        }


        $map['exam_is_del'] = 0;
        $list = M('ex_exam')->where($map)->order('sort asc')->limit($this->_limit())->findAll();
        foreach($list as &$val){
            if($val["exam_publish_result_tm_mode"]==1){
                $val['exam_publish_result_tm']   = date('Y/m/d H:i' , $val['exam_publish_result_tm']);
            }
            if($val["exam_user_signup_flg"]==1){
                $val['exam_user_signup_time']   = date('Y/m/d H:i' , $val['exam_user_signup_time']);
                $val['exam_user_signup_end']   = date('Y/m/d H:i' , $val['exam_user_signup_end']);
            }
            $val['is_test']=0;
            
            $user_exam_time = $this->userExam->getUserExam($val['exam_id'] , $this->mid);
            if($user_exam_time >= $val["exam_times_mode"] &&  $val["exam_times_mode"] != 0){
                $val['is_test'] = 1;
            }
            
            
            if($val["paper_id"]){
                $exam_paper=D("ExPaper")->where("paper_content_paperid=".$val["paper_id"])->field("paper_content_point")->findAll();
                $paper_point=getSubByKey($exam_paper,"paper_content_point");
                $val["score_sum"]= array_sum($paper_point);
                $val["content_sum"]=count($exam_paper);
            }
            $val['exam_begin_time'] = date('Y/m/d H:i' , $val['exam_begin_time']);
            $val['exam_end_time']   = date('Y/m/d H:i' , $val['exam_end_time']);
            $val['test_number']     = $user_exam_time;
            $val['count']           = $this->userExam->getUserExam($val['exam_id']);

        }
        $list ? $this->exitJson($list , 1) : $this->exitJson(array() , 0);
    }
    
    //获取考试详情
    public function getExamInfo(){
        $map['exam_id'] = intval($this->data['id']);
        $paper_id       = intval($this->data['paper_id']);
        $map['exam_is_del'] = 0;

        $data = M('ex_exam')->where($map)->field("exam_id,exam_name,exam_describe,exam_total_time,exam_passing_grade,exam_begin_time,exam_end_time,exam_times_mode")->find();
        $data['count']           = $this->userExam->getUserExam( $map['exam_id'] );
        $data['exam_begin_time'] = date('Y/m/d H:i' , $data['exam_begin_time']);
        $data['exam_end_time']   = date('Y/m/d H:i' , $data['exam_end_time']);
        //查询该试卷的总分数，总题数
        $exam_paper = m("ex_paper")->where("paper_id=".$paper_id)->find();
        $data["score_sum"]   = $exam_paper['paper_point'];
        $data["content_sum"] = $exam_paper['paper_question_count'];
        $data ? $this->exitJson($data , 1) : $this->exitJson(array() , 0);
    } 
    
    // //获取题型列表
    // public function getQuestionTypeList(){
    //  $list = M('ex_question_type')->field("question_type_title,question_type_id")->findAll();
    //     $list ? $this->exitJson($list , 1) : $this->exitJson(array() , 0);
    // }
     
    //获取试题列表
    public function getQuestionInfo(){
        $data=array();
        $tp = C('DB_PREFIX');
        $exam_id=intval($this->data["exam_id"]);
        $paper_id=intval($this->data["paper_id"]);
        $exam_info = D('ExExam' , 'exam')->getExam($exam_id,$paper_id);
        $user_exam_time= $this->userExam->getUserExam($exam_id,$this->mid);
        if($user_exam_time>=$exam_info["exam_times_mode"] &&  $exam_info["exam_times_mode"] != 0){
            $this->exitJson("您已经考试过了！" , 2);
        }
        if($paper_id<=0){
            $this->exitJson("没有抽出试卷！" , 3);
        }
        $question_list=$this->paper->getPaper($paper_id);
        if($question_list){
           if(count($question_list["question_list"])==0){
                $this->exitJson(array() , 0);
            }
            foreach ($question_list["question_list"] as $key => $value) {
                $question_list["question_list"][$key]["question_content"]=$value["question_content"];
                $question_list["question_list"][$key]["question_qsn_guide"]=$value["question_qsn_guide"];
            }
            $question_type=M('')->query('SELECT question_type_id,question_type_title,COUNT(paper_content_paperid) AS sum, Sum(paper_content_point) as score FROM '.$tp.'ex_paper_content pc,'.$tp.'ex_question q,'.$tp.'ex_question_type qt WHERE pc.paper_content_questionid=q.question_id AND q.question_type=qt.question_type_id AND pc.paper_content_paperid='.$paper_id.' GROUP  BY question_type_id order by sort asc');
            foreach ($question_type as $key => $value) {
                $arr=array();
                foreach ($question_list["question_list"] as $k => $v) {
                    if($value["question_type_id"]==$v["question_type"]){
                        $arr[]=$question_list["question_list"][$k];
                    }
                }
                $question_type[$key]["question_list"]=$arr;
            }
            $data["subscript"]=array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
            $data["score"]    = $question_list["score"];
            $data["paper_id"] = $question_list["paper_id"];
            $data["count"]    = $question_list["count"];
            $data["question"] = $question_type;
            $data['mp_path']  = $exam_info['mp_path'];
        }
        if($data){
            $_data=array(
                'uid'=>$this->mid,
                'exam_id'=>$exam_id
            );
            if(!M("ex_exam_recode")->where($_data)->find()){
                $_data["ctime"]=time();
                M("ex_exam_recode")->add($_data);
            }
            $this->exitJson($data , 1); 
        }else{
            $this->exitJson(array() , 0); 
        }
    }
    //试卷批阅操作（自动阅卷）
    public function doUserExam(){
        $data["user_id"]=$this->mid;
        $data["user_exam"]=$this->data["exam_id"];
        $data["user_paper"]=$this->data["paper_id"];
        $data["user_exam_time"]=time();
        $data["user_exam_score"]=$this->data["user_score"];
        $data["user_total_date"] = $this->data["total_date"];
        $data["user_right_count"]=$this->data["rightcount"];
        $data["user_error_count"]=$this->data["errorcount"];
        $user_exam_number=1;
        $count=$this->userExam->getUserExamCount($data["user_exam"],$data["user_paper"],$this->mid);
        if($count){
            $user_exam_number=$count["user_exam_number"]+1;
        }
        $data["user_exam_number"]=$user_exam_number;
        $result = M('ex_user_exam')->data($data)->add();
        $where=M('ex_user_exam')->getLastSql();
        if($result){
            $question_list=$this->data["question_list"];
            $question_list=explode("+",$question_list);

            foreach ($question_list as $vo) {
                $vo=explode("-",$vo);
                $data["user_id"]=$this->mid;
                $data['user_exam_id'] = $this->data["exam_id"];
                $data['user_paper_id'] = $this->data["paper_id"];
                $data['user_question_id'] = $vo[0];
                $data['user_exam_time'] =$user_exam_number;
                $data['user_question_answer'] = $vo[1];
                $data['attach_id'] = $vo[2];
                M('ex_user_answer')->data($data)->add();
            }
        }
        if($result){
            $exam_info=$this->exam->where("exam_id=".$this->data["exam_id"])->field("exam_publish_result_tm_mode,exam_publish_result_tm,exam_passing_grade")->find();
            $exam_info["user_score"]=$this->data["user_score"];
            //操作积分
            model('Credit')->getCreditInfo($this->mid,21);
            $this->exitJson($exam_info , 1);
        }else{
            $this->exitJson($where , 0);
        }
    } 
    //上传附件
    public function addAttach(){
        $data['attach_type'] = 'question_image';
        $data['upload_type'] = 'image';
        $info = model('Attach')->upload($data); 
        if ($info ['status']) {
            $attach_id= getSubByKey ( $info ['info'], 'attach_id' );
            $this->exitJson($attach_id , 1);
        } else {
           $this->exitJson("上传失败" , 0);
        }
    }
    public function userExam(){
        $exam_id=$this->data["exam_id"];
        $userExam=$this->userExam->where("user_id=".$this->mid." and  user_exam=".$exam_id)->order("user_exam_id desc")->find();
        return $userExam ? $userExam : array();
    }
    public function UserExamInfo(){
        $data=array();
        $tp = C('DB_PREFIX');
        $exam_id     = $this->data["exam_id"];
        $test_number = $this->data["test_number"];
        $userExam=$this->userExam->where("user_id=".$this->mid." and  user_exam=".$exam_id .' and user_exam_number='.$test_number)->order("user_exam_id desc")->find();
        $question_list=$this->paper->getPaper($userExam["user_paper"]);
        if($question_list["question_list"]){
            foreach ($question_list["question_list"] as $key => $value) {
                $map=array(
                    'user_id'=>$userExam["user_id"],
                    'user_paper_id'=>$userExam["user_paper"],
                    'user_exam_id'=>$userExam["user_exam"],
                    'user_question_id'=>$value["question_id"],
                    'user_exam_time'=>$userExam["user_exam_number"],
                );
                $user_answer=M("ex_user_answer")->where($map)->find();
                $question_list["question_list"][$key]["user_answer"] = $user_answer["user_question_answer"] ? $user_answer["user_question_answer"] : '';
                
                if( $user_answer["attach_id"] ) {
                    $attach_id = explode(",",$user_answer["attach_id"]);
                    foreach ($attach_id as $k => $v) {
                        $question_list["question_list"][$key]["attach_id"][] = getImageUrlByAttachId($v) ?: '';
                    }
                } else {
                    $question_list["question_list"][$key]["attach_id"]=array();
                }
                
                $question_list["question_list"][$key]["question_content"]= $value["question_content"];
                $question_list["question_list"][$key]["question_qsn_guide"]= $value["question_qsn_guide"];
            }
            $question_type=M('')->query('SELECT question_type_id,question_type_title,COUNT(paper_content_paperid) AS sum, Sum(paper_content_point) as score FROM '.$tp.'ex_paper_content pc,'.$tp.'ex_question q,'.$tp.'ex_question_type qt WHERE pc.paper_content_questionid=q.question_id AND q.question_type=qt.question_type_id AND pc.paper_content_paperid='.$userExam["user_paper"].' GROUP  BY question_type_id');
            foreach ($question_type as $key => $value) {
                $arr=array();
                foreach ($question_list["question_list"] as $k => $v) {
                    if($value["question_type_id"]==$v["question_type"]){
                        $arr[]=$question_list["question_list"][$k];
                    }
                }
                $question_type[$key]["question_list"]=$arr;
            }
            $data["subscript"]=array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
            $data["score"]=$question_list["score"];
            $data["paper_id"]=$question_list["paper_id"];
            $data["count"]=$question_list["count"];
            $data["question"]=$question_type;
            $data["user_total_score"]=$userExam["user_total_score"];
            $data["user_exam_score"]=$userExam["user_exam_score"];
            $data["user_total_date"]=$userExam["user_total_date"];
            $data["user_right_count"]=$userExam["user_right_count"];
            $data["user_error_count"]=$userExam["user_error_count"];
            $this->exitJson($data,1);
        }else{
            $this->exitJson("数据不见了！" , 0);
        }
    }
    //获取考试记录时间
    public function getExamRecodeTime(){
        $exam_id=intval($this->data["exam_id"]);
        $where=array(
            'exam_id'=>$exam_id,
            'uid'=>$this->mid
        );
        $data=M("ex_exam_recode")->where($where)->field("ctime")->find();
        if($data){
            $data["time"]=time();
            $this->exitJson($data , 1);
        }else{
            $data["ctime"]=time();
            $data["time"]=time();
            $this->exitJson($data, 0);
        }
    }
}

?> 