<?php
/**
 * 考试系统(试卷)后台配置
 * 1.试卷管理 - 目前支持1级分类
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
tsload(APPS_PATH . '/exam/Lib/Action/CommonAction.class.php');

class AdminPaperAction extends AdministratorAction
{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    //试卷列表
    public function index()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList              = array('paper_id', 'paper_category', 'paper_name', 'paper_describe', 'paper_type', 'paper_point', 'paper_question_count', 'paper_status', 'uname', 'paper_insert_date', 'DOACTION');
        $this->pageButton[]             = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]             = array('title' => '删除', 'onclick' => "admin.mzPutRecycle('Paper')");
        $this->searchKey                = array('paper_id', 'paper_name', 'paper_category');
        $this->searchPostUrl            = U('exam/AdminPaper/index');
        $category                       = M('ex_paper_category')->where('pid=0')->getField('ex_paper_category_id,title');
        $this->opt['paper_category']    = $category;
        $this->opt['paper_category'][0] = '不限'; //必须放在赋值的下面
        $this->_listpk                  = 'paper_id';
        $listData                       = $this->_getData(20, 0);
        $questions                      = $this->getQuestionList(0);
        $this->assign('questions', $questions);
        $this->displayList($listData);
    }
    //编辑、添加试卷
    public function addPaper()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        if ($_GET['paper_id']) {
            $paper = M('ex_paper')->where('paper_id=' . $_GET['paper_id'])->find();
            $this->assign('paper', $paper);
            $question_type = D("ExQuestion")->getQuestion_type($paper['paper_question_category']);
            $this->assign("question_type", $question_type);
        }
        //$paper_category = M('ex_paper_category')->findAll();

        //$this->assign("paper_category",$paper_category);

        $this->display();
    }

    //添加试卷操作
    public function doAddPaper()
    {
        $post                     = $_POST;
        $data['paper_name']       = $post['paper_name'];
        $data['paper_category']   = $post['paper_category'];
        $post['ex_paper_category_idhidden'] = rtrim($post['ex_paper_category_idhidden'],',0');
        $data['fullcategorypath'] = ',' . $post['ex_paper_category_idhidden'] . ',';
        if (strpos($post['ex_paper_category_idhidden'], ',') !== false) {
            $data['paper_category'] = substr($post['ex_paper_category_idhidden'], strripos($post['ex_paper_category_idhidden'], ',') + 1);
        } else {
            $data['paper_category'] = $post['ex_paper_category_idhidden'];
        }
        $post['ex_question_category_idhidden'] = rtrim($post['ex_question_category_idhidden'],',0');
        $data['paper_question_fullcategorypath'] = ',' . $post['ex_question_category_idhidden'] . ',';
        if (strpos($post['ex_question_category_idhidden'], ',') !== false) {
            $data['paper_question_category'] = substr($post['ex_question_category_idhidden'], strripos($post['ex_question_category_idhidden'], ',') + 1);
        } else {
            $data['paper_question_category'] = $post['ex_question_category_idhidden'];
        }
        $data['paper_describe'] = $post['paper_describe'];
        $data['paper_type']     = $post['paper_type'];
        $data['attach_id']      = isset($post['attach'][0]) ? intval($post['attach'][0]) : 0;
        $question_type          = $post['question_type'];
        if ($post['paper_id']) {
            if ($question_type) {
                $paper_question               = D("ExPaper")->doPaperQuestion($question_type, $post['paper_id'], $this->mid,$data['paper_question_category']);
                $data["paper_question_count"] = $paper_question["count"];
                $data["paper_point"]          = $paper_question["score"];
            }
            $data['paper_update_date'] = time();
            $result                    = M('ex_paper')->where('paper_id = ' . $post['paper_id'])->data($data)->save();
        } else {
            $data['paper_insert_date'] = time();
            $data['paper_admin']       = $this->mid;
            $result                    = M('ex_paper')->data($data)->add();
            $paper_question            = $question_type ? D("ExPaper")->doPaperQuestion($question_type, $result, $this->mid,$data['paper_question_category']) : 0;
            unset($data);
            $data["paper_question_count"] = $paper_question["count"];
            $data["paper_point"]          = $paper_question["score"];
            M('ex_paper')->where("paper_id=" . $result)->save($data);
        }
        if ($result) {
            unset($data);
            if ($post['paper_id']) {
                exit(json_encode(array('status' => '1', 'info' => '编辑成功')));
            } else {
                $paper_question["question_count"] > 0 ? exit(json_encode(array('status' => '1', 'info' => '添加成功,一共插入' . $question_count . "道题！"))) : exit(json_encode(array('status' => '1', 'info' => '添加成功')));
            }
        } else {
            exit(json_encode(array('status' => '0', 'info' => '系统繁忙，请稍后再试')));
        }
    }
    //添加试题
    public function addQuestion()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageTitle['addQuestion'] = '试题管理';
        $paper_id                       = intval($_GET["paper_id"]);
        $paper_info                     = M("ExPaper")->getPaperInfo($paper_id);

        $paper_question = M("ExQuestion")->getPaperQuestion($paper_id);

        //试题分类
        //$category_list = M('ex_question_category')->select();
        $type_list = M('ex_question_type')->select();
        //$this->assign('category_list',$category_list);
        $this->assign('type_list', $type_list);
        $this->assign('paper_info', $paper_info);
        //$this->assign('question_list', $question_list);
        $this->assign('paper_question', $paper_question);
        $this->display();
    }

    //获取试题列表
    public function getQuestionLists()
    {
        $map['question_is_del'] = 0;
        $map['question_status'] = 1;
	$_POST['question_category'] = rtrim($post['question_category'],',0');
        if ($_POST['question_category']) {
            $map['question_category'] = substr($_POST['question_category'], strripos($_POST['question_category'], ',') + 1);
        }
        if ($_POST['question_type']) {
            $map['question_type'] = intval($_POST['question_type']);
        }

        if ($_POST['title']) {
            $map['question_content'] = array('like', '%' . t($_POST['title']) . '%');
        }
        if ($_POST['question_zj']) {
            $question_id        = M('ex_paper_content')->field('paper_content_questionid')->findAll();
            $question_id        = array_unique(getSubByKey($question_id, 'paper_content_questionid'));
            $map['question_id'] = array('not in', $question_id);
        }

        $total     = M('ex_question')->where($map)->count(); //总记录数
        $page      = intval($_POST['pageNum']); //当前页
        $pageSize  = 10; //每页显示数
        $totalPage = ceil($total / $pageSize); //总页数

        $startPage = $page * $pageSize; //开始记录
        //构造数组
        $list['total']     = $total;
        $list['pageSize']  = $pageSize;
        $list['totalPage'] = $totalPage;

        $list['data'] = M('ex_question')->where($map)->field('question_id,question_content,question_category,question_type,question_point')->order('question_update_date desc')->limit("{$startPage} , {$pageSize}")->findAll();
        foreach ($list['data'] as &$val) {
            $val['question_category_name'] = M('ex_question_category')->where('question_category_id=' . $val['question_category'])->getField('question_category_name');
            $val['question_type_title']    = M('ex_question_type')->where('question_type_id=' . $val['question_type'])->getField('question_type_title');
        }
        exit(json_encode($list));
    }

    //添加试题操作
    public function doAddQuestion()
    {
        $id                                = intval($_POST['id']);
        $paper_question                    = explode(",", $_POST['paper_question']);
        $paper                             = M("ex_paper")->where("paper_id=" . $id)->find();
        $item                              = M('ex_paper_content')->where("paper_content_paperid=" . $id)->field("paper_content_item")->order("paper_content_item desc")->find();
        $num                               = $item ? $item["paper_content_item"] : 0;
        $score                             = 0;
        $count                             = 0;
        $data['paper_content_admin']       = $this->mid;
        $data['paper_content_update_date'] = time();
        $data['paper_content_insert_date'] = time();
        foreach ($paper_question as $vo) {
            $num++;
            $question                         = explode("-", $vo);
            $data["paper_content_paperid"]    = $id;
            $data['paper_content_questionid'] = $question[0];
            $data['paper_content_point']      = $question[1];
            $data['paper_content_item']       = $num;
            if (!M('ex_paper_content')->where("paper_content_paperid=" . $id . " and paper_content_questionid=" . $question[0])->find()) {
                $res   = M('ex_paper_content')->data($data)->add();
                $score = $res ? $score + $question[1] : $score;
                $count = $res ? $count + 1 : $count;
            }
        }
        if ($score > 0 && $count > 0) {
            $_score = $score + $paper['paper_point'];
            $_count = $count + $paper['paper_question_count'];
            $data   = array(
                'paper_point'          => $_score,
                'paper_question_count' => $_count,
            );
            M("ex_paper")->data($data)->where("paper_id=" . $id)->save();
        }
        exit(json_encode(array('status' => '1', 'info' => "操作成功")));
    }
    /**
     * 删除试题
     * @return void
     */
    public function delquestion()
    {
        $data = array(
            'paper_point'          => $_POST["paper_point"],
            'paper_question_count' => $_POST["paper_question_count"],
        );
        $status = M("ex_paper_content")->delete($_POST["id"]);
        if ($status) {
            M("ex_paper")->where("paper_id=" . intval($_POST["paper_id"]))->data($data)->save();
            exit(json_encode(array('status' => '1', 'info' => '操作成功')));
        } else {
            exit(json_encode(array('status' => '1', 'info' => '操作失败')));
        }
    }
    /**
     * 编辑试卷
     */
    public function editPaper()
    {
        $this->_initexamListAdminMenu();
        $this->pageTab[]              = array('title' => '编辑试卷', 'tabHash' => 'editPaper', 'url' => U('exam/AdminPaper/editPaper'));
        $this->pageTitle['editPaper'] = '编辑试卷';
    }
    public function update_paper_status()
    {
        $id                   = $_POST["id"];
        $status               = $_POST["status"];
        $data["paper_status"] = $status;
        if ($status == 1) {
            $result = M("ex_paper_content")->where("paper_content_paperid=" . $id)->findALL();
            if ($result) {
                $res = M('ex_paper')->where("paper_id=" . $id)->data($data)->save();
                if ($res) {
                    exit(json_encode(array('status' => 1, 'info' => '操作成功')));
                } else {
                    exit(json_encode(array('status' => 0, 'info' => '操作失败')));
                }
            } else {
                exit(json_encode(array('status' => 0, 'info' => '该试卷下没有试题,不能启用')));
            }
        } else {
            $res = M('ex_paper')->where("paper_id=" . $id)->data($data)->save();
            if ($res) {
                exit(json_encode(array('status' => 1, 'info' => '操作成功')));
            } else {
                exit(json_encode(array('status' => 0, 'info' => '操作失败')));
            }
        }
    }
    //批量删除(隐藏)试卷
    public function putRecycle()
    {
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $where = array(
            'paper_id' => array('in', $ids),
        );
        $data['paper_is_del'] = 1;
        $res                  = M('ex_paper')->where($where)->save($data);

        if ($res !== false) {
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data']   = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    //删除试卷(隐藏)
    public function delPaper()
    {
        if (!$_POST['id']) {
            exit(json_encode(array('status' => 0, 'info' => '请选择要删除的对象!')));
        }
        $map['paper_id']      = intval($_POST['id']);
        $data['paper_is_del'] = $_POST['is_del'] ? 0 : 1; //传入参数并设置相反的状态
        if (M('ex_paper')->where($map)->data($data)->save()) {
            exit(json_encode(array('status' => 1, 'info' => '操作成功')));
        } else {
            exit(json_encode(array('status' => 0, 'info' => '操作失败')));
        }
    }

    //试卷回收站(被隐藏的试卷)
    public function recycle()
    {
        $this->_initexamListAdminMenu();
        $this->_initexamListAdminTitle();
        $this->pageKeyList  = array('paper_id', 'paper_status', 'paper_name', 'paper_describe', 'paper_type', 'paper_point', 'paper_update_date', 'paper_insert_date', 'DOACTION');
        $this->pageButton[] = array('title' => '清空回收站', 'onclick' => "admin.mzPaperclear()");
        $listData           = $this->_getData(20, 1);
        $this->displayList($listData);
    }
    /**
     * 清空试卷回收站
     * @return void
     */
    public function delTable()
    {
        $result = M("ex_paper")->where(array('paper_is_del' => 1))->delete();
        if ($result) {
            exit(json_encode(array('status' => '1', 'info' => '已删除')));
        } else {
            exit(json_encode(array('status' => '0', 'info' => '操作繁忙,请稍后再试')));
        }
    }
    //获取试卷数据
    private function _getData($limit = 20, $is_del)
    {
        if (isset($_POST)) {
            $_POST['paper_id'] && $map['paper_id']                   = intval($_POST['paper_id']);
            $_POST['paper_name'] && $map['paper_name']               = array('like', '%' . t($_POST['paper_name']) . '%');
            $_POST['paper_category'] && $map['ex_paper_category_id'] = intval($_POST['paper_category']);
        }
        $map['paper_is_del'] = $is_del;
        $list                = M('ex_paper')->where($map)->order('paper_insert_date desc')->findPage($limit);
        foreach ($list['data'] as &$value) {
            $value['paper_describe']    = msubstr($value['paper_describe'], 0, 20);
            $value['paper_category']    = M('ex_paper_category')->where('ex_paper_category_id=' . $value['paper_category'])->getField('title');
            $value['paper_type']        = $value['paper_type'] == '0' ? '<span style="color:green">手动出卷</span>' : '<span style="color:#2E4C8C">自动出卷</span>';
            $value['paper_insert_date'] = date('Y-m-d H:i:s', $value['paper_insert_date']);
            $value['uname']             = getUserName($value['paper_admin']);
            $value['DOACTION']          = $value['paper_status'] == 1 ? '<a href="javascript:admin.updatePaperStatus(' . $value['paper_id'] . ',' . $value['paper_status'] . ');">禁用</a>' : '<a href="javascript:admin.updatePaperStatus(' . $value['paper_id'] . ',' . $value['paper_status'] . ');">启用</a>';
            $value['DOACTION'] .= $value['paper_is_del'] ? '<a onclick="admin.delObject(' . $value['paper_id'] . ',\'Paper\',' . $value['paper_is_del'] . ');" href="javascript:void(0)">恢复</a>' : '  | <a href="' . U('exam/AdminPaper/addQuestion', array('paper_id' => $value['paper_id'], 'tabHash' => 'addQuestion')) . '">试题管理</a> | <a href="' . U('exam/AdminPaper/addPaper', array('paper_id' => $value['paper_id'], 'tabHash' => 'editPaper')) . '">编辑</a> | <a onclick="admin.delObject(' . $value['paper_id'] . ',\'Paper\',' . $value['paper_is_del'] . ');" href="javascript:void(0)">删除(隐藏)</a> ';
            $value['paper_status'] = $value['paper_status'] == 1 ? '<span style="color:green">已启用</span>' : '<span style="color:red">禁用</span>';
        }
        return $list;
    }
    //获取试题数据
    public function getQuestionList($is_del)
    {
        $map['question_is_del'] = $is_del;
        $questions              = M('ex_question q')->join(C('DB_PREFIX') . 'ex_question_type t ON q.question_type = t.question_type_id')->where($map)->order('question_status = 1')->select();
        return $questions;
    }

    /**
     * 试卷后台管理菜单
     * @return void
     */
    private function _initExamListAdminMenu()
    {
        $this->pageTab[] = array('title' => '试卷列表', 'tabHash' => 'index', 'url' => U('exam/AdminPaper/index'));
        $this->pageTab[] = array('title' => '添加试卷', 'tabHash' => 'addPaper', 'url' => U('exam/AdminPaper/addPaper'));
        $this->pageTab[] = array('title' => '试卷回收站', 'tabHash' => 'recycle', 'url' => U('exam/AdminPaper/recycle'));
    }

    /**
     * 试卷后台的标题
     */
    private function _initexamListAdminTitle()
    {
        $this->pageTitle['index']    = '试卷列表';
        $this->pageTitle['addPaper'] = '添加试卷';
        $this->pageTitle['recycle']  = '试卷回收站';
    }
    /**
     * 获取试题分类的数量
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-08-07
     * @return [type] [description]
     */
    public function getQuestionCount()
    {
        $question_type = D("ExQuestion")->getQuestion_type($_POST['question_category_id']);
        $html          = '';
        foreach ($question_type as $key => $val) {
            $html .= $val['question_type_title'] . '：<input type="text" name="' . $val['question_type_id'] . '" value="0">  题 此类型下共有 <font color="#088d08;">' . $val['question_count'] . '</font>  道题<br/>';
        }
        echo json_encode(array('status' => 1, 'data' => ['html' => $html]));
    }

}
