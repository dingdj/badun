<?php

/**
 * Eduline考试系统首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
class UserExamAction extends Action {
    /**
     * Eduline考试系统首页方法
     * @return void
     */ 
    public function index() {
        $this->display();
    }
    /**
     * 取得考试分类
     * @param boolean $return 是否返回数据，如果不是返回，则会直接输出Ajax JSON数据
     * @return void|array
     */
    public function getList($return = false) {
        $user_id=$this->uid;
        //排序
        $order = 'user_exam_id DESC';
        $time = time();
        $where .= "user_exam_is_del=0 and user_id=$user_id";
        $data = M("ex_user_exam")->join(C('DB_PREFIX')."ex_exam ON exam_id=user_exam")->where($where)->order($order)->findPage(10);
        foreach ($data['data'] as $key=> $v) {
           $where=array(
                'user_exam'=>$v["user_exam"],
                'user_paper'=>$v["user_paper"],
                'user_exam_number'=>$v["user_exam_number"]
            );
            $paper=M("ex_paper")->where("paper_id=".$v["user_paper"])->find();
            $category=M("ex_exam_category")->where("exam_category_id=".$v["exam_categoryid"])->find();
            $exam_sum=M("ex_user_exam")->where($where)->order("user_exam_score desc")->select();
            foreach ($exam_sum as $k=>$exam) {
                if($exam["user_id"]==$user_id){
                    $data['data'][$key]["user_rank"]=$k+1;
                }
            }
            $data['data'][$key]["user_sum"]=count($exam_sum);
            $data['data'][$key]["paper_point"]=$paper["paper_point"];
            $data['data'][$key]["category_name"]=$category["exam_category_name"];
        }
        if ($data['data']) {
            $this->assign('listData', $data['data']);
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
    public function exam_info(){
        $user_id=$this->uid;
        $exam_id=intval($_GET["exam_id"]);
        $paper_id=intval($_GET["paper_id"]);
        $user_exam_number=intval($_GET["user_exam_number"]);
        if( !M("ExUserExam")->getUserExam($exam_id,$this->mid) ){
            $this->error('你还没有参加此次考试');
        }
        $user_exam=M("ExUserExam")->getUserExamCount($exam_id,$paper_id,$user_id,$user_exam_number);
        $where=array(
            'user_id'=>$user_id,
            'user_exam_id'=>$exam_id,
            'user_paper_id'=>$paper_id,
            'user_exam_time'=>$user_exam["user_exam_number"]
            );
        $user_answer=M("ex_user_answer")->where($where)->field('user_question_answer,user_question_id')->select();
        $exam_info=D('ExExam')->getExam($exam_id,$paper_id);
        $question_type=M('')->query('SELECT question_type_id,question_type_title,COUNT(paper_content_paperid) AS sum, Sum(paper_content_point) as score FROM '.C('DB_PREFIX').'ex_paper_content pc,'.C('DB_PREFIX').'ex_question q,'.C('DB_PREFIX').'ex_question_type qt WHERE pc.paper_content_questionid=q.question_id AND q.question_type=qt.question_type_id AND pc.paper_content_paperid='.$paper_id.' GROUP  BY question_type_id');
        $data=M('ExPaper')->getPaper($paper_id);
        
        
        //考试排名
        $my['uname'] = getUserName($this->mid);
        $user_rank = M('ex_user_exam')->where('user_exam='.$exam_id)->field('user_id , user_exam_score')->order('user_exam_score desc')->limit(10000)->findAll();
        $iscore = 0;
        foreach ( $user_rank as &$val ) {
            $iscore ++;
            $val['user_id'] == $this->mid && $rank = $iscore;
            $val['rank'] = ( string ) $iscore;
            $val['uname'] = getUserName($val['user_id']);
        }
        empty ( $rank ) && $rank = 10000; // 一万名后不再作排名，以提高性能
        
        $my['rank']  = $rank;
        $my['lists'] = $user_rank;

        $this->assign('user_exam',$user_exam);
        $this->assign('my',$my);
        $this->assign('exam_info',$exam_info);
        $this->assign('user_answer',$user_answer);
        $this->assign('data',$data);
        $this->assign('subscript',array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"));
        $this->assign('question_type',$question_type);
        $this->display();
    }
    
    /**
     * 证书查询.
     */
    public function cert(){
        $this->display('cert_search');
        
        
    }
    
    /**
     * 证书查询--ajax
     */
    public function getCert(){
        if($_POST){
            //检查验证码
            if (md5(strtoupper($_POST['yzcode'])) != $_SESSION['verify']) {
                $data = ['status'=>0,'message'=>'验证码错误','alert'=>1];
                echo json_encode($data);exit;
            }
            $map = [
                'cert_code' => $_POST['cert_code'],
                'user_id_card' => $_POST['user_id_card'],
                'user_name' => $_POST['user_name']
            ];
            $map = array_filter($map);
            if(count($map) >= 2){
                $mod = D('ExamCert','exam');
                $list =  $mod->where($map)->order('cert_create_time DESC')->select();
                if($list){
                    // 查询首次在系统中的数据编号
                    $cid = $mod->where($map)->field('cert_id,create_time')->order('create_time ASC')->find();
                    $this->assign('cid',$cid);
                    $this->assign('data',$list);
                    $html = $this->fetch('cert');
                    $data = ['status'=>1,'data'=>$html];
                }else{
                    $data = ['status'=>0,'message'=>'你暂时没有获得相关证书或查询条件不符合'];
                }
            }else{
                $data = ['status'=>0,'message'=>'至少需要填写两条信息'];
            }
            
            
        }else{
            $data = ['status'=>0,'message'=>'错误的请求'];
        }
        echo json_encode($data);exit;
    }
}

