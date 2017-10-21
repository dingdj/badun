<?php

/**
 * Eduline考试系统首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
class IndexAction extends Action {
    /**
     * Eduline考试系统首页方法
     * @return void
     */ 
    public function index() {
        
        //$tp = C('DB_PREFIX');
        //$str='<img class="tkimg" alt="2000" /><img class="tkimg" alt="20" /><img class="tkimg" alt="2" />';
        //if(preg_match_all("/<[a-z]+ [a-z]+=\"[a-z]+\" [a-z]+=\"[0-9]+\" \/>/",$str,$match)) { 
        //}
        //$result = M('')->query('SELECT `exam_category_id`,`exam_category_name` FROM '.$tp.'ex_exam_category ORDER BY exam_category_insert_date');
        $data=M("ExUserExam")->getUserExamList($this->mid , 10);
        
        // 分析参数
        $map = parse_params_map();
		$cateId = intval($map['c']);
        //$selCate = M('ex_exam_category')->field(['exam_category_id as id','exam_category_name as title'])->order('exam_category_insert_date')->select();
        $selCate = model('CategoryTree')->setTable('ex_exam_category')->getSelectData($cateId);
        $config = [
            'type' => 'exam',
            'params' => ['c']
        ];
        $selCate = showCatetreeForHtml($selCate,$config,'id',$cateId);
        $this->assign('selcate',$selCate);
        $this->assign('data',$data);

        if(!$cateId){
            $cateId = intval($_GET['cateId']);
        }
        $title = M('ex_exam_category')->where('exam_category_id='.$cateId)->getField('exam_category_name');
        $list = $this->getList(true,array('cateId'=>$cateId));
        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('cateId',$cateId);
        
        $this->display();
    }
    /**
     * 取得考试分类
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false,$map = array()) {
        (is_array($map) && $map) && $_GET = array_merge($_GET,$map); 
        $tp = C('DB_PREFIX');
        //排序
        $order = 'e.sort ASC';
        $time = time();
        $where="";
        $cateId=$_GET["cateId"];
        if ($cateId> 0) {
            $where= " exam_categoryid=$cateId and";
        }
        $where .= " exam_is_del=0 AND exam_status=1 ";
        $data = M("ex_exam_category ec")->join("`{$tp}ex_exam` e ON ec.ex_exam_category_id=e.exam_categoryid")->where($where)->order($order)->findPage(10);
        foreach ($data['data'] as $key=> $vo) {
            $data['data'][$key]["exam_begin_time"]=date("Y-m-d H:i:s",$vo["exam_begin_time"]);
            $data['data'][$key]["exam_end_time"]=date("Y-m-d H:i:s",$vo["exam_end_time"]);
            if($vo["exam_total_time"]==0){
                $data['data'][$key]["exam_total_time"]="不限制时长";
            }else{
                $data['data'][$key]["exam_total_time"]=$vo["exam_total_time"]."分钟";
            }
        }
        if ($data['data']) {
            $this->assign('listData', $data['data']);
            $this->assign('where', $where);
            $this->assign('cateId',$_GET['cateId']);//定义分类
        }
        if($this->is_pc){
            $html = $this->fetch('index_list');
        }else{
            $html = $this->fetch('ajax_list');
        }

        $data['data'] = $html;
        if ($return) {
            return $data;
        } else {
            echo json_encode($data);
            exit();
        }
    }
    /**
     * 取得逐题考试数据
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getOneExam($return = false) {
        $tp = C('DB_PREFIX');
        $paper_id=$_GET["paper_id"];
        $subscript=array("A","B","C","D","E","F","G","H","I","J","K");
        $data = D("ExPaper")->join("{$tp}ex_question q ON pc.paper_content_questionid=q.question_id")->where("pc.paper_content_paperid=".$paper_id)->order("paper_content_item")->findPage(1);
        foreach ($data['data'] as $key=> $vo) {
            $$data['data'][$key]["question_content"]=preg_replace("/<[a-z]+ [a-z]+=\"[a-z]+\" [a-z]+=\"[0-9]+\" \/>/", '______', $vo["question_content"]);
            $question_answer="";
            $option_list = M('ex_option' )->where('option_question='.$vo["question_id"])->order('option_item_id')->findAll();
            foreach ($option_list as $k =>$answer) {
                if($vo["question_type"]==3){
                    $question_answer.=$answer["option_content"].","; 
                }else{
                     if($answer["is_right"]==1){
                       $question_answer.=$subscript[$answer["option_item_id"]-1].",";
                    }
                }
            }
            $data['data'][$key]["question_answer"] = $question_answer{strlen($question_answer)-1} == ',' ? substr($question_answer, 0, -1) : $question_answer;  
            $data['data'][$key]["option_list"]=$option_list;
        }
        if($data['data']) {
            $this->assign('exam_info', $exam_info);
            $html = $this->fetch('one_exam');
        }
        if($return) {
            return $data;
        }else{
            echo json_encode($data);
            exit();
        }
    }
    public function exam(){
        $tp = C('DB_PREFIX');
        $exam_id=intval($_GET["id"]);
        if($exam_id==0){
            $this->error('参数错误');
        }
        $paper_id  = M("ex_exam")->where("exam_id=".$exam_id)->getField('paper_id');
        $exam_info = D('ExExam')->getExam($exam_id,$paper_id);
        if($exam_info['exam_begin_time'] > time() || $exam_info['exam_end_time'] < time() ){
            $this->error('考试未开始或已经结束');
        }
        $user_exam_time= M("ExUserExam")->getUserExam($exam_id,$this->uid);
        if($user_exam_time>=$exam_info["exam_times_mode"] &&  $exam_info["exam_times_mode"] != 0){
            if( $exam_info['exam_publish_result_tm_mode'] == 0 || ($exam_info['exam_publish_result_tm_mode'] == 1 && $exam_info['exam_publish_result_tm'] <= time() ) ) {
                $this->assign('jumpUrl' , U('exam/UserExam/exam_info',array('exam_id'=>$exam_id,'paper_id'=>$paper_id)));
                $this->error('你已经参加过此次考试了');
            }
            $this->error('你已经参加过此次考试了<br/>答案将在'.date('Y-m-d H:i' , $exam_info['exam_publish_result_tm']).'公布');
        }
        $data=M('ExPaper')->getPaper($paper_id);
        if(!$data){
            $this->error('该试卷暂未抽选出题!');
        }
        $question_type=M('')->query('SELECT question_type_id,question_type_title,COUNT(paper_content_paperid) AS sum, Sum(paper_content_point) as score FROM '.$tp.'ex_paper_content pc,'.$tp.'ex_question q,'.$tp.'ex_question_type qt WHERE pc.paper_content_questionid=q.question_id AND q.question_type=qt.question_type_id AND pc.paper_content_paperid='.$paper_id.' GROUP  BY question_type_id');
        $this->assign('exam_info',$exam_info);
        $this->assign('data',$data);
        $this->assign('exam_id',$exam_id);
        $this->assign('subscript',array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"));
        $this->assign('question_type',$question_type);
        $this->assign('begin_time',time());
        $this->assign('sum',count($data["question_list"]));
         $this->display();
    }
    public function doExam(){
        $user_id=$this->uid;
        $data["user_id"]=$user_id;
        $data["user_exam"]=$_POST["exam_id"];
        $data["user_paper"]=$_POST["paper_id"];
        $data["user_exam_time"]=time();
        $data["user_exam_score"]=$_POST["user_score"];
        $data["user_total_date"]=D("ExExam")->getTime($_POST["begin_time"],time());
        $data["user_right_count"]=$_POST["rightcount"];
        $data["user_error_count"]=$_POST["errorcount"];
        $user_exam_number=1;
        $count=M("ExUserExam")->getUserExamCount($_POST["exam_id"],$_POST["paper_id"],$user_id);
        if($count){
            $user_exam_number=$count["user_exam_number"]+1;
        }
        $data["user_exam_number"]=$user_exam_number;
        $exam = M('ex_user_exam')->data($data)->add();
        if($exam){
            $question_list=$_POST["question_list"];
            $question_list=explode("+",$question_list);
            foreach ($question_list as $vo) {
                $vo=explode("-",$vo);
                $data["user_id"]=$user_id;
                $data['user_exam_id'] = $_POST["exam_id"];
                $data['user_paper_id'] = $_POST["paper_id"];
                $data['user_question_id'] = $vo[0];
                $data['user_exam_time'] =$user_exam_number;
                $data['user_question_answer'] = $vo[1];
                if($vo[1]=="null" || empty($vo[1])){
                    $data['user_question_answer'] ="未填";
                }
                M('ex_user_answer')->data($data)->add();
            }
            $credit = M('credit_setting')->where(array('id'=>21,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $etype = 6;
                $note = '参加考试获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$etype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
            exit(json_encode(array('status'=>'1','user_exam_number'=>$user_exam_number)));
        }else {
            exit(json_encode(array('status'=>'0')));
        }
    }   
}

