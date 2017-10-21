<?php
/**
 * 首页模块控制器
 * @author dengjb <dengjiebin@higher-edu.cn>
 * @version GJW2.0
 */
class ExamAction extends Action
{
	//初始化
	public function _initialize() { 

	}
	public function index() {
		$tp = C('DB_PREFIX');
        $str='<img class="tkimg" alt="2000" /><img class="tkimg" alt="20" /><img class="tkimg" alt="2" />';
        if(preg_match_all("/<[a-z]+ [a-z]+=\"[a-z]+\" [a-z]+=\"[0-9]+\" \/>/",$str,$match)) { 
        }
        $result = M('')->query('SELECT `exam_category_id`,`exam_category_name` FROM '.$tp.'ex_exam_category ORDER BY exam_category_insert_date');
        $data=D("ExUserExam","exam")->getUserExamList($this->mid);
        $this->assign('selCate',$result);
        $this->assign('data',$data);
        $this->display();
	}	
	public function exam(){
        $tp = C('DB_PREFIX');
        $exam_id=intval($_GET["id"]);
        if($exam_id==0){
            echo "<script>alert('参数错误');history.go(-1);</script>";
        }
        $paper_list=M("ex_exam_paper")->where("exam_paper_exam=".$exam_id)->findALl();
        $paper_id=0;
        if(count($paper_list)==1){
            $paper_id=$paper_list[0]["exam_paper_paper"];
        }else{
            $list="";
            foreach ($paper_list as $v) {
                $list[]=$v["exam_paper_paper"];
            }
            $paper_id=$list[array_rand($list)];
        }
        $exam_info=D('ExExam',"exam")->getExam($exam_id,$paper_id);
        $user_exam_time= D("ExUserExam","exam")->getUserExam($exam_id,$this->uid);
        if($user_exam_time>=$exam_info["exam_times_mode"] &&  $exam_info["exam_times_mode"]!=0){
            echo "<script>alert('考试次数已达上限');history.go(-1);</script>";
        }
        $data=D('ExPaper',"exam")->getPaper($paper_id);
        if(!$data){
            echo "<script>alert('该试卷暂未抽选出题');history.go(-1);</script>";
        }
        $question_type=M('')->query('SELECT question_type_id,question_type_title,COUNT(paper_content_paperid) AS sum, Sum(paper_content_point) as score FROM '.$tp.'ex_paper_content pc,'.$tp.'ex_question q,'.$tp.'ex_question_type qt WHERE pc.paper_content_questionid=q.question_id AND q.question_type=qt.question_type_id AND pc.paper_content_paperid='.$paper_id.' GROUP  BY question_type_id');
        $this->assign('exam_info',$exam_info);
        $this->assign('data',$data);
        $this->assign('exam_id',$exam_id);
        $this->assign('subscript',array("A","B","C","D","E","F","G","H","I","J","K"));
        $this->assign('question_type',$question_type);
        $this->assign('begin_time',time());
        $this->assign('sum',count($data["question_list"]));
        $this->display();
    }
	/**
     * 取得考试分类
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
        $tp = C('DB_PREFIX');
        //排序
        $order = 'exam_begin_time DESC';
        $time = time();
        $where="";
        $cateId=$_GET["cateId"];
        if ($cateId> 0) {
            $where= " exam_categoryid=$cateId and";
        }
        $where .= " exam_is_del=0 AND exam_begin_time<$time and exam_end_time>$time";
        $data = M("ex_exam_category ec")->join("`{$tp}ex_exam` e ON ec.exam_category_id=e.exam_categoryid")->where($where)->order($order)->findPage(30);
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
            $html = $this->fetch('index_list');
        } else {
            $html = $this->fetch('index_list');
        }
        $data['data'] = $html;
        if ($return) {
            return $data;
        } else {
            echo json_encode($data);
            exit();
        }
    }
}