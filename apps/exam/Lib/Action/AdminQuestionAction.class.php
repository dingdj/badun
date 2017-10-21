<?php
/**
 * 考试系统(题库)后台配置
 * 1.题库管理 - 目前支持1级分类
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
tsload(APPS_PATH . '/exam/Lib/Action/CommonAction.class.php');

class AdminQuestionAction extends AdministratorAction
{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    //试题列表
    public function index()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList  = array('question_id', 'title', 'question_type_title', 'question_point', 'question_content', 'sort', 'question_status', 'question_insert_date', 'DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.mzPutRecycle('Question')");
        $this->searchKey    = array('question_id', 'question_content', 'question_category', 'question_type');
        $category           = M('ex_question_category')->where('pid=0')->getField('ex_question_category_id,title');
        $type               = M('ex_question_type')->getField('question_type_id,question_type_title');

        $this->opt['question_category']    = $category;
        $this->opt['question_category'][0] = '不限'; //必须放在赋值的下面
        $this->opt['question_type']        = $type;
        $this->opt['question_type'][0]     = '不限';

        $this->_listpk       = 'question_id';
        $this->searchPostUrl = U('exam/AdminQuestion/index');
        $listData            = $this->_getData(20, 0, array('1'));
        $this->displayList($listData);
    }

    //编辑、添加试题
    public function addQuestion()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $question_id = intval($_GET['question_id']);
        //$map[''] = ;
        //$question_category = M('ex_question_category')->where($map)->order('ex_question_category_id')->select();
        $question_type = M('ex_question_type')->where($map)->order('question_type_id')->select();
        if (isset($_GET['question_id']) && $question_id) {
            //获取问题详情
            $question = M('ex_question')->where('question_id=' . $question_id)->find();
            //问题选项详情
            $option       = M('ex_option')->where('option_question=' . $question_id)->order('option_id asc')->select();
            $option_count = count($option);

            $this->assign('question', $question);
            $this->assign('option', $option);
            $this->assign('option_count', $option_count);
        }
        //$this->assign('question_category',$question_category);
        $this->assign('question_type', $question_type);
        $this->display();
    }

    //添加试题操作
    public function doAddQuestion()
    {
        $post = $_POST;
        $post['ex_question_category_idhidden'] = rtrim($post['ex_question_category_idhidden'],',0');
        $data['fullcategorypath'] = ',' . $post['ex_question_category_idhidden'] . ',';
        if (strpos($post['ex_question_category_idhidden'], ',') !== false) {
            $data['question_category'] = substr($post['ex_question_category_idhidden'], strripos($post['ex_question_category_idhidden'], ',') + 1);
        } else {
            $data['question_category'] = $post['ex_question_category_idhidden'];
        }

        $data['question_type']   = $post['question_type'];
        $data['question_status'] = $post['question_status'];
        $data['question_point']  = $post['question_point'];
        if ($data['question_type'] == 3) {
            $data['question_option_count'] = $post['question_option_count_tk'] ? $post['question_option_count_tk'] : 4;
        } else {
            $data['question_option_count'] = $post['question_option_count'] ? $post['question_option_count'] : 2;
        }
        $data['question_content']   = $post['question_content'];
        $data['question_qsn_guide'] = $post['question_qsn_guide'] ? $post['question_qsn_guide'] : "暂无解析！";
        $data['sort']               = $post['sort'];

        $data['question_admin'] = $this->mid;
        $question_id            = intval($post['question_id']);

        if ($question_id > 0) {
            $data['question_update_date'] = time();
            $result                       = M('ex_question')->where('question_id = ' . $question_id)->save($data);
            if ($result !== false) {
                M('ex_option')->where('option_question=' . $question_id)->delete();
                $question_option_count = intval($data['question_option_count']);
                //添加题干选项
                if ($data['question_type'] == 1 || $data['question_type'] == 4) {
                    //单选  判断
                    for ($i = 1; $i <= $question_option_count; $i++) {
                        $sql_values .= "($question_id,$i,'" . $post['single_question_answer' . $i] . "'," . ($post['opt'] == $i ? 1 : 0) . "),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`,`is_right`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } elseif ($data['question_type'] == 2) {
                    //多选
                    for ($i = 1; $i <= $question_option_count; $i++) {
                        $sql_values .= "($question_id,$i,'" . $post['many_question_answer' . $i] . "'," . (in_array($i, $post['opts']) ? 1 : 0) . "),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`,`is_right`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } elseif ($data['question_type'] == 3) {
                    //填空
                    $count = substr_count($data['question_content'], '<img');
                    for ($i = 1; $i <= $count; $i++) {
                        $sql_values .= "($question_id,$i,'" . $post['tk_question_answer' . $i] . "'),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } else {
                    //其他题型

                }

            }
        } else {
            $data['question_insert_date'] = time();
            $data['question_update_date'] = time();
            //添加试题操作
            $id = M('ex_question')->data($data)->add();
            if ($id > 0) {
                $question_option_count = intval($data['question_option_count']);
                //添加题干选项
                if ($data['question_type'] == 1 || $data['question_type'] == 4) {
                    //单选  判断
                    for ($i = 1; $i <= $question_option_count; $i++) {
                        $sql_values .= "($id,$i,'" . $post['single_question_answer' . $i] . "'," . ($post['opt'] == $i ? 1 : 0) . "),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`,`is_right`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } elseif ($data['question_type'] == 2) {
                    //多选
                    for ($i = 1; $i <= $question_option_count; $i++) {
                        $sql_values .= "($id,$i,'" . $post['many_question_answer' . $i] . "'," . (in_array($i, $post['opts']) ? 1 : 0) . "),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`,`is_right`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } elseif ($data['question_type'] == 3) {
                    //填空
                    $count = substr_count($data['question_content'], '<img');
                    for ($i = 1; $i <= $count; $i++) {
                        $sql_values .= "($id,$i,'" . $post['tk_question_answer' . $i] . "'),";
                    }
                    $sql    = "INSERT INTO " . C("DB_PREFIX") . "ex_option (`option_question`,`option_item_id`,`option_content`) VALUE" . trim($sql_values, ',');
                    $model  = Model();
                    $result = $model->query($sql);
                } else {
                    //其他题型

                }
            } else {
                $this->error('操作失败');
            }
        }
        if ($result !== false) {
            unset($data);
            if ($post['question_id']) {
                $this->assign('jumpUrl', U('exam/AdminQuestion/index'));
                $this->success('编辑成功');
            } else {
                $this->assign('jumpUrl', U('exam/AdminQuestion/index'));
                $this->success('添加成功');
            }
        } else {
            $this->error('操作失败');
        }
    }

    //试题回收站(被隐藏的试题)
    public function postRecycle()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList  = array('question_id', 'question_type_title', 'question_point', 'question_content', 'question_status', 'question_insert_date', 'DOACTION');
        $this->pageButton[] = array('title' => '清空回收站', 'onclick' => 'admin.mzRecycleClear()');
        $listData           = $this->_getData(20, 1);
        $this->displayList($listData);
    }

    //批量删除(隐藏)试题
    public function putRecycle()
    {
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $where = array(
            'question_id' => array('in', $ids),
        );
        $data['question_is_del'] = 1;
        $res                     = M('ex_question')->where($where)->save($data);

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

    //删除(隐藏)试题
    public function delQuestion()
    {
        if (!$_POST['id']) {
            exit(json_encode(array('status' => 0, 'info' => '请选择要删除的对象!')));
        }
        $map['question_id']      = intval($_POST['id']);
        $data['question_is_del'] = $_POST['question_is_del'] ? 0 : 1; //传入参数并设置相反的状态
        if (M('ex_question')->where($map)->data($data)->save()) {
            exit(json_encode(array('status' => 1, 'info' => '操作成功')));
        } else {
            exit(json_encode(array('status' => 1, 'info' => '操作失败')));
        }
    }
    //获取试题数据
    private function _getData($limit = 20, $is_del)
    {
        if (isset($_POST)) {
            $_POST['question_category'] && $map['ex_question_category_id'] = intval($_POST['question_category']);
            $_POST['question_id'] && $map['question_id']                   = intval($_POST['question_id']);
            $_POST['question_type'] && $map['question_type_id']            = intval($_POST['question_type']);
            $_POST['question_content'] && $map['question_content']         = array('like', '%' . t($_POST['question_content']) . '%');
        }
        $map['question_is_del'] = $is_del; //搜索非隐藏内容
        $list                   = M('ex_question q')->where($map)->join('`' . C('DB_PREFIX') . 'ex_question_category` c ON q.question_category = c.ex_question_category_id')->join(C('DB_PREFIX') . 'ex_question_type t ON q.question_type = t.question_type_id')->order('question_id desc')->findPage($limit);
        //->join('`'.C('DB_PREFIX').'user` u ON q.question_admin = u.uid')
        foreach ($list['data'] as &$value) {
            $value['question_content']     = msubstr(t($value['question_content']), 0, 20);
            $value['activity']             = $value['is_activity'] == '1' ? '<span style="color:green">已审核</span>' : '<span style="color:red">未审核</span>';
            $value['question_status']      = $value['question_status'] == '1' ? '<span style="color:green">启用</span>' : '<span style="color:red">禁用</span>';
            $value['question_insert_date'] = date('Y-m-d H:i:s', $value['question_insert_date']);
            if ($value['question_is_del']) {

            }
            $value['id']     = $value['paper_id'];
            $value['is_del'] = $value['question_is_del'];
            $value['DOACTION'] .= $value['question_is_del'] ? '<a onclick="admin.recObject(' . $value['question_id'] . ',\'Question\',' . $value['question_is_del'] . ');" href="javascript:void(0)">恢复</a>' : '<a href="' . U('exam/AdminQuestion/addQuestion', array('question_id' => $value['question_id'], 'tabHash' => 'addQuestion')) . '">编辑</a> | <a onclick="admin.delObject(' . $value['question_id'] . ',\'Question\',' . $value['question_is_del'] . ');" href="javascript:void(0)">删除(隐藏)</a> ';
        }
        return $list;
    }

    /**
     * 清空回收站
     * @return void
     */
    public function delRecycle()
    {
        $result = M("ex_question")->where(array('question_is_del' => 1))->delete();
        if ($result) {
            exit(json_encode(array('status' => '1', 'info' => '已删除')));
        } else {
            exit(json_encode(array('status' => '0', 'info' => '操作繁忙,请稍后再试')));
        }
    }
    public function questionImport()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageTitle['importUser'] = '导入题库';
        $this->pageKeyList             = array('question');
        // 表单URL设置
        $this->savePostUrl = U('exam/AdminQuestion/doQuestionImport');

        $this->displayConfig();
    }
    /**
     * 试题批量导入
     * @return void
     */
    public function doQuestionImport()
    {
        $attach_id = trim($_POST['question_ids'], '|') ?: 0;
        if ($attach_id) {
            $attach = model('Attach')->getAttachById($attach_id);
            if (!in_array($attach['extension'], ['xls', 'xlsx'])) {
                $this->error('请重新上传导入附件');
            } else {
                //检测文件是否存在
                $file_path = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'data', 'upload', $attach['save_path'] . $attach['save_name']));
                //导入PHPExcel
                tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'PHPExcel', 'PHPExcel.php')));
                $excel = PHPExcel_IOFactory::load($file_path);
                $sheet = $excel->getActiveSheet(0);
                $data  = $sheet->toArray();
                unset($data[0]);
                unset($data[1]);
                //定义试题总条数，错误类型条数
                $sum                 = 0;
                $error               = 0;
                $right               = 0;
                $error_question_type = 0;
                foreach ($data as $value) {
                    $sum++;
                    $question_category     = $value[0];
                    $question_type         = $value[1];
                    $question_point        = $value[2];
                    $question_option_count = $value[3];
                    $question_content      = $value[4];
                    $option                = $value[5];
                    $answer                = $value[6];
                    $question_qsn_guide    = $value[7] ?: "暂无解析!";

                    if ($question_type = M("ex_question_type")->where("question_type_id='" . $question_type . "'")->find()) {
                        $question_type = $question_type["question_type_id"];
                        $question      = array(
                            'question_category'     => $question_category,
                            'question_type'         => $question_type,
                            'question_point'        => $question_point,
                            'question_option_count' => $question_option_count,
                            'question_content'      => $question_content,
                            'question_qsn_guide'    => $question_qsn_guide,
                            'question_admin'        => $this->uid,
                            'question_status'       => 1,
                            'question_update_date'  => time(),
                            'question_insert_date'  => time(),
                        );

                        $where['question_category'] = $question_category;
                        $where['question_type']     = $question_type;
                        $where['question_content']  = $question_content;

                        $re = M('ex_question')->where($where)->getField('question_id');
                        if (!$re) {
                            $result = M("ex_question")->data($question)->add();
                        }
                        if ($result) {
                            $right++;
                            //主观题除外
                            if ($question["question_type"] != 5) {
                                $answer_list = explode(",", $answer);
                                $option_list = explode(":", $option);
                                foreach ($option_list as $key => $value) {
                                    $opt["option_question"] = $result;
                                    $opt["option_item_id"]  = $key + 1;
                                    $opt["option_content"]  = $value;
                                    if (in_array($value, $answer_list)) {
                                        $opt["is_right"] = 1;
                                    } else {
                                        $opt["is_right"] = 0;
                                    }
                                    M("ex_option")->data($opt)->add();
                                }
                            }
                        } else {
                            $error++;
                        }
                    } else {
                        $error_question_type++;
                    }
                }
                $this->assign('jumpUrl', U('exam/AdminQuestion/index'));
                $this->success("导入成功,一共有{$sum}条数据，其中类型错误的数据有{$error_question_type}条，导入成功的数据有{$right}条，导入失败的数据有{$error}条!");
            }
            $this->error('请重新上传导入附件');
        }
    }
    /**
     * 试题模板导出
     * @return void
     */
    public function doExport()
    {
        $file = SITE_PATH . '/excel/export.xls';
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . basename($file));
        readfile($file);
        exit;
        $question_type = M("ex_question_type")->field("question_type_id,question_type_title")->select();
        $type          = "";
        foreach ($question_type as $key => $value) {
            $type .= $value["question_type_title"] . ",";
        }
        $type = "'" . '"' . substr($type, 0, strlen($type) - 1) . '"' . "'";
        require_once 'PHPExcel/PHPExcel.php';
        require_once 'PHPExcel/PHPExcel/Writer/Excel5.php';
        require_once 'PHPExcel/PHPExcel/Writer/Excel2007.php';
        $objPHPExcel = new PHPExcel();
        /* 设置输出的excel文件为2007兼容格式 */
        //$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        /* 设置当前的sheet */
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        /* sheet标题 */
        $objActSheet->setTitle("试题模板导出");
        //合并单元格
        $objActSheet->mergeCells('A1:G1');
        $objActSheet->setCellValue('A1', "试题模板导出");
        $objStyleA1 = $objPHPExcel->getActiveSheet()->getStyle('A1');
        $objActSheet->mergeCells('H1:S1');
        $objActSheet->setCellValue("A2", "试题类型");
        $objActSheet->setCellValue('H1', "备注: 1)、包含图片的试题不能上传;2)、答案选项以英文':'隔开;3)、多选题正确答案以英文','隔开");
        $objValidation = $objActSheet->getCell("A2")->getDataValidation(); //这一句为要设置数据有效性的单元格
        $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
            ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setErrorTitle('输入的值有误')
            ->setError('您输入的值不在下拉框列表内.')
            ->setPromptTitle("试题分类")
            ->setFormula1($type);
        $objActSheet->setCellValue("B2", "试题分数");
        $objActSheet->setCellValue("C2", "试题描述");
        $objActSheet->setCellValue("D2", "答案选项");
        $objActSheet->setCellValue("E2", "试题答案个数");
        $objActSheet->setCellValue("F2", "正确答案");
        $objActSheet->setCellValue("G2", "解析");
        //内容居中
        $objStyleA1->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); //设置垂直居中
        $objStyleA1->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //设置横向居中
        //颜色填充
        $objStyleA1->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objStyleA1->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_YELLOW);
        //字体设置
        $objStyleA1->getFont()->setName('Candara');
        $objStyleA1->getFont()->setSize(16);
        $objStyleA1->getFont()->setBold(true);
        $objStyleA1->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objStyleA1->getFont()->setBold(true);
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(10);
        $objActSheet->getColumnDimension('C')->setWidth(30);
        $objActSheet->getColumnDimension('D')->setWidth(25);
        $objActSheet->getColumnDimension('E')->setWidth(20);
        $objActSheet->getColumnDimension('F')->setWidth(50);
        $objActSheet->getColumnDimension('G')->setWidth(50);
        /* 生成到浏览器，提供下载 */
        ob_end_clean(); //清空缓存
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        $excelFileName = "试题模板导出";
        header("Content-Disposition:attachment;filename=export.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }
    /**
     * 试题后台管理菜单
     * @return void
     */
    private function _initExamListAdminMenu()
    {
        $this->pageTab[] = array('title' => '试题列表', 'tabHash' => 'index', 'url' => U('exam/AdminQuestion/index'));
        $this->pageTab[] = array('title' => '添加试题', 'tabHash' => 'addQuestion', 'url' => U('exam/AdminQuestion/addQuestion'));
        $this->pageTab[] = array('title' => '试题回收站', 'tabHash' => 'postRecycle', 'url' => U('exam/AdminQuestion/postRecycle'));
        $this->pageTab[] = array('title' => '试题批量导入', 'tabHash' => 'questionImport', 'url' => U('exam/AdminQuestion/questionImport'));
    }
    /**
     * 试题后台的标题
     */
    private function _initExamListAdminTitle()
    {
        $this->pageTitle['index']          = '试题列表';
        $this->pageTitle['addQuestion']    = '添加试题';
        $this->pageTitle['postRecycle']    = '试题回收站';
        $this->pageTitle['questionImport'] = '试题批量导入';
    }

}
