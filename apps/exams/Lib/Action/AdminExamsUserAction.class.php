<?php
/**
 * 考试系统后台控制器
 * 用户成绩管理
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
class AdminExamsUserAction extends AdministratorAction
{
    protected $mod;
    /**
     * 初始化
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-09-30
     * @return [type] [description]
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->allSelected                 = false;
        $this->mod                         = D('ExamsUser', 'exams');
        $this->pageTitle['index']          = '成绩列表';
        $this->pageTitle['waitHalleExams'] = '待批阅考试';
        $this->pageTitle['doHaddleExams']  = '阅卷';
        $this->pageTab[]                   = array('title' => '成绩列表', 'tabHash' => 'index', 'url' => U('exams/AdminExamsUser/index'));
        $this->pageTab[]                   = array('title' => '待批阅试卷', 'tabHash' => 'waitHalleExams', 'url' => U('exams/AdminExamsUser/waitHalleExams'));

    }
    /**
     * 成绩首页
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-22
     * @return [type] [description]
     */
    public function index()
    {
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        // 搜索选项的key值
        $this->searchKey                                   = array('exams_paper_id', 'uid');
        $map                                               = [];
        $_POST['exams_paper_id'] && $map['exams_paper_id'] = intval($_POST['exams_paper_id']);
        $_POST['uid'] && $map['uid']                       = intval($_POST['uid']);
        $map['status']                                     = 1;
        $map['exams_mode']                                 = ['in', '1,2,3'];
        $this->pageButton[]                                = array('title' => '导出', 'onclick' => "exams.exportExams('" . urlencode(sunjiami(json_encode($map), "hll")) . "')");
        $list                                              = $this->mod->getExamsUserPageList($map);
        $this->pageKeyList                                 = ['exams_users_id', 'exams_paper_title', 'uname', 'score', 'right_count', 'wrong_count', 'examiner_uname', 'create_time', 'anser_time', 'update_time'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['uname']             = getUsername($v['uid']);
                $v['create_time']       = date("Y-m-d H:i", $v['create_time']);
                $v['update_time']       = date("Y-m-d H:i", $v['update_time']);
                $v['anser_time']        = ceil($v['anser_time'] / 60) . ' 分钟' . ($v['anser_time'] % 60) . '秒';
                $v['exams_paper_title'] = $v['paper_info']['exams_paper_title'];
                $v['examiner_uname']    = $v['examiner_uid'] == 0 ? '系统' : getUsername($v['examiner_uid']);
                //$v['DOACTION']    = '<a target="_blank" href="' . U('exams/Index/examsresult', ['joinType' => '2', 'paper_id' => $v['exams_paper_id'],'temp'=>$v['exams_users_id']]) . '">查看答题</a>';
                //$v['DOACTION'] .= ' - <a href="' . U('exams/AdminPaper/assembly', ['tabHash' => 'assembly', 'paper_id' => $v['exams_paper_id']]) . '">组卷</a>';
                //$v['DOACTION'] .= ' - <a href="javascript:exams.deletePaper(' . $v['exams_paper_id'] . ')">删除</a>';
            }
            unset($v);
        }
        $this->displayList($list);
    }

    /**
     * 待审阅
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-22
     * @return [type] [description]
     */
    public function waitHalleExams()
    {
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        // 搜索选项的key值
        $this->searchKey                                   = array('exams_paper_id', 'uid');
        $map                                               = [];
        $_POST['exams_paper_id'] && $map['exams_paper_id'] = intval($_POST['exams_paper_id']);
        $_POST['uid'] && $map['uid']                       = intval($_POST['uid']);
        $map['status']                                     = 0;
        $map['exams_mode']                                 = ['in', '1,2,3'];
        $list                                              = $this->mod->getExamsUserPageList($map);
        $this->pageKeyList                                 = ['exams_users_id', 'exams_paper_title', 'uname', 'score', 'right_count', 'wrong_count', 'create_time', 'anser_time', 'update_time', 'DOACTION'];
        if ($list['data']) {
            foreach ($list['data'] as &$v) {
                $v['uname']             = getUsername($v['uid']);
                $v['create_time']       = date("Y-m-d H:i", $v['create_time']);
                $v['update_time']       = date("Y-m-d H:i", $v['update_time']);
                $v['anser_time']        = ceil($v['anser_time'] / 60) . ' 分钟' . ($v['anser_time'] % 60) . '秒';
                $v['exams_paper_title'] = $v['paper_info']['exams_paper_title'];
                //$v['DOACTION']    = '<a target="_blank" href="' . U('exams/Index/examsresult', ['joinType' => '2', 'paper_id' => $v['exams_paper_id'],'temp'=>$v['exams_users_id']]) . '">查看答题</a>';
                $v['DOACTION'] = '<a href="' . U('exams/AdminExamsUser/doHaddleExams', ['tabHash' => 'doHaddleExams', 'paper_id' => $v['exams_paper_id'], 'exams_users_id' => $v['exams_users_id']]) . '">阅卷</a>';
                //$v['DOACTION'] .= ' - <a href="javascript:exams.deletePaper(' . $v['exams_paper_id'] . ')">删除</a>';
            }
            unset($v);
        }
        $this->displayList($list);
    }

    /**
     * 处理审阅试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-22
     * @return [type] [description]
     */
    public function doHaddleExams()
    {
        if ($_POST) {
            $examiner_data = $_POST['examiner_data'];
            // 获取试卷信息
            $paper_options = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById(intval($_POST['paper_id']));
            $score_options = array_column($paper_options['options_type'], 'score', 'question_type');
            $score         = 0; // 论述题总得分
            $examiner_add  = [];
            // 检测分数是否正确
            foreach ($examiner_data as $option_type => $val) {
                // 取得配置的得分
                $config_score = $score_options[$option_type];
                // 检测得分是否大于配置分数
                foreach ($val as $question_id => $v_score) {
                    if ($v_score > $config_score) {
                        $res = ['status' => 0, 'message' => '第' . $_POST['question_num'][$question_id] . '题所得分数不能大于' . $config_score];
                        echo json_encode($res);exit;
                    }
                    $score += $v_score;
                    $examiner_add[$question_id] = $v_score;
                }
            }
            $save = [
                'examiner_data' => serialize($examiner_add),
                'examiner_uid'  => $this->mid,
                'status'        => 1,
                'score'         => ['exp', 'score+' . $score],
            ];
            $where = [
                'exams_users_id' => intval($_POST['exams_users_id']),
                'status'         => 0,
            ];
            if ($this->mod->where($where)->save($save)) {
                $res = ['status' => 1, 'data' => ['info' => '提交成功']];
            } else {
                $res = ['status' => 0, 'message' => '处理失败,请稍后重试'];
            }
            echo json_encode($res);exit;
        }
        $paper_id        = $_GET['paper_id'];
        $exams_users_id  = $_GET['exams_users_id'];
        $this->pageTab[] = array('title' => '阅卷', 'tabHash' => 'doHaddleExams', 'url' => U('exams/AdminExamsUser/doHaddleExams', ['paper_id' => $paper_id, 'exams_users_id' => $exams_users_id]));
        $answerData      = $this->mod->getExamsInfoByMap(['exams_paper_id' => $paper_id, 'exams_users_id' => $exams_users_id]);
        $paper_options   = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById($paper_id);
        $this->assign('paper_options', $paper_options);
        $this->assign('answerData', $answerData);
        $this->display('haddle_exams');
    }
    /**
     * 导出
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-24
     * @return [type] [description]
     */
    public function export()
    {
        $map = json_decode(sunjiemi(urldecode($_GET['explod']), 'hll'), true);
        if ($map) {
            $xlsCell = [
                ['exams_users_id', 'ID'],
                ['exams_paper_title', '试卷名'],
                ['uname', '用户名'],
                ['score', '成绩'],
                ['right_count', '正确数'],
                ['wrong_count', '错误数'],
                ['examiner_uname', '阅卷人'],
                ['create_time', '考试时间'],
                ['anser_time', '考试用时'],
            ];
            $list = $this->mod->where($map)->select();
            if ($list) {
                foreach ($list as &$v) {
                    $v['uname']             = getUsername($v['uid']);
                    $v['create_time']       = date("Y-m-d H:i", $v['create_time']);
                    $v['anser_time']        = ceil($v['anser_time'] / 60) . ' 分钟' . ($v['anser_time'] % 60) . '秒';
                    $v['exams_paper_title'] = D('ExamsPaper', 'exams')->where('exams_paper_id=' . $v['exams_paper_id'])->getField('exams_paper_title');
                    $v['examiner_uname']    = $v['examiner_uid'] == 0 ? '系统' : getUsername($v['examiner_uid']);
                }
                unset($v);
            }
            model('Excel')->export('试卷列表', $xlsCell, $list);
        }
        header('HTTP/1.1 401 Unauthorized');
        header('status: 401 Unauthorized');
        exit;
    }
}
