<?php
/**
 * 考试系统
 * 首页
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class IndexAction extends Action
{

    /**
     * 考试系统首页
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-17
     * @return [type] [description]
     */
    public function index()
    {
        if (isset($_GET['squery'])) {
            $this->paper();
            exit;
        }
        $module = M('exams_module')->order("sort DESC")->select();
        $this->assign('module', $module);
        $this->display();
    }
    /**
     * 试卷列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-18
     * @return [type] [description]
     */
    public function paper()
    {
        // 试题版块;
        $module_id = $_GET['module_id'];
        // 排序方式
        $order = in_array($_GET['order'], ['default', 'new', 'hot']) ? $_GET['order'] : 'default';
        // 分析参数,取得专业ID,以及分类显示
        $map     = parse_params_map();
        $cateId  = intval($map['c']);
        $selCate = model('CategoryTree')->setTable('exams_subject')->getSelectData($cateId);
        $config  = [
            'type'   => 'exams',
            'params' => ['c'],
        ];
        $selCate = showCatetreeForHtml($selCate, $config, 'id', $cateId);
        $this->assign('selcate', $selCate);
        // 试题难度
        $level = $_GET['level'] ? ['in', $_GET['level']] : '';
        // 设置按钮显示文字
        $exams_module = M('exams_module')->where('exams_module_id=' . $module_id)->find();
        $this->assign('exams_module', $exams_module);
        // 获取数据
        $where                                  = [];
        $module_id && $where['exams_module_id'] = $module_id;
        if ($cateId) {
            $ids = model('CategoryTree')->setTable('exams_subject')->getSubCateIdByPid($cateId);
            array_unshift($ids, $cateId);
        }
        $ids && $where['exams_subject_id'] = ['in', $ids];
        $level && $where['level']             = $level;
        $where['_string'] = '(`start_time` <= '.time().' OR `start_time` = 0) AND (`end_time` >= '.time().' OR `end_time` = 0)';
        $list = D('ExamsPaper', 'exams')->getPaperPageList($where, 15, $order);
        foreach ($list['data'] as &$v) {
            $v['paper_options'] = D('ExamsPaperOptions', 'exams')->getPaperOptionsById($v['exams_paper_id']);
        }
        $this->assign('list', $list);
        $this->assign('level', $_GET['level']);
        $this->assign('order', $order);
        $this->assign('module_id',$module_id);
        $this->assign('exams_subject_id', $cateId);
        $this->display('paper_list');
    }

    /**
     * 参与考试
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-18
     * @return [type] [description]
     */
    public function examsroom()
    {
    	// 获取试卷ID
    	$paper_id = intval($_GET['paper_id']);
    	// 获取试卷信息
    	$paper = D("ExamsPaper",'exams')->getPaperById($paper_id);
    	if($paper){
            // 检测该用试卷是否有考试次数限制
            if($_GET['joinType'] == 2 && $paper['exams_limit'] > 0){
                // 查询用户考试次数
                if(D("ExamsUser",'exams')->isLimit($paper_id,$paper['exams_limit'])){
                    $this->error('你已超过该考试允许参考的最大次数');
                }
            }
    		// 获取试卷试题等信息
    		$paper_options = D('ExamsPaperOptions','exmas')->getPaperOptionsById($paper_id);
    		$this->assign('paper_options',$paper_options);
    	}
    	$this->assign('paper',$paper);
    	// 是否练习模式
    	$this->assign('isPractice',($_GET['joinType'] == 1) ? 1 : 2);

        // 是否继续作答
        $tempData = [];
        $temp_id = intval($_GET['temp']);
        if($temp_id){
            // 查询记录
            $map['exams_users_id'] = $temp_id;
            $map['uid'] = $this->mid;
            $map['exams_mode'] = ($_GET['joinType'] == 1) ? 1 : 2;
            $tempData = D('ExamsUser','exams')->getExamsInfoByMap($map);
        }
        $this->assign('tempData',$tempData);
    	$this->display();
    }

    /**
     * 处理提交的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-18
     * @return [type] [description]
     */
    public function doHaddleExams()
    {
    	if(D("ExamsUser",'exams')->doExamsPaper($_POST)){
    		$this->assign('isAdmin',1);
    		$this->jumpUrl = U('exams/Index/index');
    		$this->success('提交成功,请等待结果');
    	}else{
    		$this->error('提交处理失败,请重新尝试');
    	}
    }

    /**
     * 处理下次再做的试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-18
     * @return [type] [description]
     */
    public function doProgressExams()
    {
        if(D("ExamsUser",'exams')->addProgressExams($_POST)){
            echo json_encode(['status'=>1,'data'=>['info'=>'保存成功,下次可继续做题','jumpurl'=>U("classroom/Home/exams",['tab'=>$_POST['exams_mode']])]]);
        }else{
            echo json_encode(['status'=>0,'message'=>'保存失败,请重新尝试']);
        }
    }

    /**
     * 考试结果
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @return [type] [description]
     */
    public function examsresult()
    {
        // 获取试卷ID
        $paper_id = intval($_GET['paper_id']);
        // 获取试卷信息
        $paper = D("ExamsPaper",'exams')->getPaperById($paper_id);
        if($paper){
            
            // 获取试卷试题等信息
            $paper_options = D('ExamsPaperOptions','exmas')->getPaperOptionsById($paper_id);
            $this->assign('paper_options',$paper_options);
            // 查询记录
            $temp_id = intval($_GET['temp']);
            $map['exams_users_id'] = $temp_id;
            $map['uid'] = $this->mid;
            $map['exams_mode'] = $_GET['joinType'] ?: 2;
            $answerData = D('ExamsUser','exams')->getExamsInfoByMap($map);
            // 检测是否已经审阅过
            //if($answerData['status'] != 1){
                //$this->error('请耐心等待试卷审阅结果');
            //}
            $this->assign('answerData',$answerData);
            // 获取错误的答题记录
            $wrongList = D("ExamsLogs",'exams')->getWrongList($paper_id,$temp_id);
            $wrongList && $wrongList = getSubByKey($wrongList,'exams_question_id');
            $this->assign('wrongCount',count($wrongList));
            $this->assign('wrongList',$wrongList);
            // 父级错题
            if($answerData['pid'] > 0){
                $inQuestions = D("ExamsLogs",'exams')->getWrongList($paper_id,$answerData['pid']);
                $inQuestions && $inQuestions = getSubByKey($inQuestions,'exams_question_id');
                $this->assign('inQuestions',$inQuestions);
            }
            // 计算排名
            $rank = D('ExamsUser','exams')->getRankList($temp_id);
            $this->assign('rank',$rank);
        }
        $this->assign('paper',$paper);
        $this->display();
    }

    /**
     * 查看错题
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @param  string $value [description]
     * @return [type] [description]
     */
    public function showWrongPaper()
    {
        // 获取试卷ID
        $paper_id = intval($_GET['paper_id']);
        // 获取试卷信息
        $paper = D("ExamsPaper",'exams')->getPaperById($paper_id);
        if($paper){
            // 获取试卷试题等信息
            $paper_options = D('ExamsPaperOptions','exmas')->getPaperOptionsById($paper_id);
            $this->assign('paper_options',$paper_options);
            // 查询记录
            $temp_id = intval($_GET['temp']);
            $map['exams_users_id'] = $temp_id;
            $map['uid'] = $this->mid;
            $map['exams_mode'] = $_GET['joinType'];
            $answerData = D('ExamsUser','exams')->getExamsInfoByMap($map);
            $this->assign('tempData',$answerData);
            // 获取错误的答题记录
            $wrongList = D("ExamsLogs",'exams')->getWrongList($paper_id,$temp_id);
            $wrongList && $wrongList = getSubByKey($wrongList,'exams_question_id');
            $this->assign('wrongCount',count($wrongList));
            $this->assign('wrongList',$wrongList);

        }
        $this->assign('paper',$paper);
        $this->display('wrong_paper');
    }

    /**
     * 错题再练
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @return [type] [description]
     */
    public function wrongExamsroom()
    {
        // 获取试卷ID
        $paper_id = intval($_GET['paper_id']);
        // 获取试卷信息
        $paper = D("ExamsPaper",'exams')->getPaperById($paper_id);
        if($paper){
            // 获取试卷试题等信息
            $paper_options = D('ExamsPaperOptions','exmas')->getPaperOptionsById($paper_id);
            $this->assign('paper_options',$paper_options);
        }
        $this->assign('paper',$paper);
        // 是否练习模式
        $this->assign('isPractice',($_GET['joinType'] == 1) ? 1 : 2);

        $temp_id = intval($_GET['temp']);
        if($temp_id){
            // 获取错误的答题记录
            $wrongList = D("ExamsLogs",'exams')->getWrongList($paper_id,$temp_id);
            $wrongList && $wrongList = getSubByKey($wrongList,'exams_question_id');
            $this->assign('wrongCount',count($wrongList));
            $this->assign('wrongList',$wrongList);
            $this->assign('exams_users_id',$temp_id);
        }
        $this->display('wrong_exams');
    }

    /**
     * 删除考试记录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-20
     * @return [type] [description]
     */
    public function deleteExeams()
    {
        if($_POST){
            $temp_id = intval($_POST['temp_id']);
            if($temp_id){
                if(D('ExamsUser','exams')->where(['uid'=>$this->mid,'exams_users_id'=>$temp_id])->save(['is_del'=>1])){
                    echo json_encode(['status'=>1,'data'=>['info'=>'删除成功']]);exit;
                }
            }
            echo json_encode(['status'=>0,'message'=>'删除失败,请重新尝试']);exit;
        }
    }
    /**
     * 收藏/取消收藏
     * @return [type] [description]
     */
    public function collect()
    {
        if($_POST){
            $action = ($_POST['action'] == '1') ? 1 : 0;
            $data['uid'] = intval($this->mid);
            $data['source_id'] = intval($_POST['source_id']);
            $data['source_table_name'] = 'exams_question';
            // 收藏
            $mod = D('ZyCollection','classroom');
            if($action === 1){
                $data['ctime'] = time();
                if($mod->addcollection($data)){
                    echo json_encode(['status'=>1,'data'=>['info'=>'收藏成功']]);exit;
                }
            }else{
                if($mod->delcollection($data['source_id'],$data['source_table_name'],$data['uid'])){
                    echo json_encode(['status'=>1,'data'=>['info'=>'取消收藏成功']]);exit;
                }
            }
            echo json_encode(['status'=>0,'message'=>$mod->getError()]);exit;
        }
        
    }
}
