<?php
/**
 * 考试证书管理.
 *
 * @author martinsun
 *
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');

class AdminExamCertAction extends AdministratorAction
{
    protected $mod = '';//当前操作模型
    /**
     * 初始化，配置内容标题.
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->mod = D('ExamCert', 'exam');
    }

    //证书列表
    public function index()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList = array('cert_code', 'user_name', 'user_id_card', 'cert_unit', 'cert_create_time', 'create_time', 'update_time', 'DOACTION');
        $this->pageButton[] = array('title' => '搜索证书', 'onclick' => "admin.fold('search_form')");
        $this->searchKey = array('cert_code', 'user_id_card');
        $this->searchPostUrl = U('exam/AdminExamCert/index');
        $listData = $this->_getData(20, 0);
        $this->displayList($listData);
    }
    //证书列表
    public function locked()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList = array('cert_code', 'user_name', 'user_id_card', 'cert_unit', 'cert_create_time', 'create_time', 'update_time', 'DOACTION');
        $this->pageButton[] = array('title' => '搜索证书', 'onclick' => "admin.fold('search_form')");
        $this->searchKey = array('cert_code', 'user_id_card');
        $this->searchPostUrl = U('exam/AdminExamCert/index');
        $listData = $this->_getData(20, 1);
        $this->displayList($listData);
    }
    /**
     * 证书列表后台管理菜单.
     */
    private function _initExamListAdminMenu()
    {
        $this->pageTab[] = array('title' => '证书列表', 'tabHash' => 'index', 'url' => U('exam/AdminExamCert/index'));
        $this->pageTab[] = array('title' => '锁定证书列表', 'tabHash' => 'locked', 'url' => U('exam/AdminExamCert/locked'));
        $this->pageTab[] = array('title' => '添加证书', 'tabHash' => 'addCert', 'url' => U('exam/AdminExamCert/addCert'));
        $this->pageTab[] = array('title' => '导入证书', 'tabHash' => 'importCert', 'url' => U('exam/AdminExamCert/importCert'));
    }
    /**
     * 考试后台的标题.
     */
    private function _initExamListAdminTitle()
    {
        $this->pageTitle['index'] = '证书列表';
        $this->pageTitle['locked'] = '锁定证书列表';
        $this->pageTitle['addCert'] = '添加证书';
        $this->pageTitle['importCert'] = '导入证书';
        
    }
    /**
     * 添加证书.
     */
    public function addCert()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList = $this->mod->getImportFields();
        $this->opt['user_sex'] = [1=>'男',2=>'女'];
        $this->opt['cert_is_revocation'] = [0=>'否',1=>'是'];
        $this->savePostUrl = U('exam/AdminExamCert/doaddCert');
        $this->displayConfig();
    }

    /**
     * 导入证书.
     */
    public function importCert()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageKeyList = array(
                'excel',           //试卷ID
        );
        $this->savePostUrl = U('exam/AdminExamCert/doImportCert');
        $this->displayConfig();
    }

    /**
     * 处理导入.
     */
    public function doImportCert()
    {
        $attach_id = trim($_POST['excel_ids'],'|') ?: 0;
        if ($attach_id) {
            $attach = model('Attach')->getAttachById($attach_id);
            if (!in_array($attach['extension'], ['xls', 'xlsx'])) {
                echo json_encode(['status' => 0, 'info' => '请重新上传导入附件']);
                exit;
            } else {
                //检测文件是否存在
                $file_path = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'data', 'upload', $attach['save_path'].$attach['save_name']));
                //导入PHPExcel
                tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'PHPExcel', 'PHPExcel.php')));
                $excel = PHPExcel_IOFactory::load($file_path);
                $sheet = $excel->getActiveSheet(0);
                $data = $sheet->toArray();
                $list = array();
                $field = $this->mod->getImportFields();
                //循环获取excel中的值
                $add_count = 0;
                foreach ($data as $key => $value) {
                    if ($key > 0  && $value) {
                        $add = array();
                        foreach($field as $k=>$v){
                            if($v == 'cert_is_revocation'){
                                $add[$v] = $value[$k] == '是' ? 1 :0;
                            }else{
                                $add[$v] = $value[$k];
                            }
                            
                        }
                        if($this->mod->where(array('cert_code'=>$add['cert_code']))->count() > 0){
                            continue;
                        }
                        $add['create_time'] = $add['update_time'] = time();
                        $add['user_sex'] = ($add['user_sex']=='男') ? 1 :2;
						$index = $key+1;
                        if ($result = $this->mod->add($add)) {
                            $add_count++;
                            $list['I'.$index] = $result;
                        }
                    }
                }
                if ($list) {
                    $ymd = date('Y').'/'.date('md').'/'.date('H').'/';
                    $imageFilePath = UPLOAD_PATH.'/'.$ymd;
                    // 创建目录
                    if (!file_exists($imageFilePath)) {
                        mkdir($imageFilePath, 0777, true);
                    }
                    foreach ($sheet->getDrawingCollection() as $img) {
                        /*表格解析后图片会以资源形式保存在对象中，可以通过getImageResource函数直接获取图片资源然后写入本地文件中*/
                        // 表格行列
						$index = $img->getCoordinates();
						// 取得在list数组中保存的数据id
						$cert_id = isset($list[$index]) ? $list[$index] : 0;

						if(!$cert_id) continue;
						$path = $imageFilePath.$img->getIndexedFilename();
                        if ($img instanceof PHPExcel_Worksheet_Drawing) {  
                        
                            $filename = $img->getPath();
                            copy($filename, $path);
                            $type = 'image/*';
                            // for xls  
                        } else if ($img instanceof PHPExcel_Worksheet_MemoryDrawing) {
                            $image = $img->getImageResource();
                            $renderingFunction = $img->getRenderingFunction();
                            switch ($renderingFunction) {
                                case PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG:  
                                    imagejpeg($image, $path);
                                    $type = 'image/jpg';
                                    break;
                                case PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF:
                                    imagegif($image, $path); 
                                    $type = 'image/gif';
                                    break;
                                case PHPExcel_Worksheet_MemoryDrawing::RENDERING_PNG:
                                    imagepng($image, $path);
                                    $type = 'image/png';
                                    break;
                                case PHPExcel_Worksheet_MemoryDrawing::RENDERING_DEFAULT:
                                    imagegif($image, $path);
                                    $type = 'image/gif';
                                    break;  
                            }
                        } 
                        $arr = getimagesize($path);
                        if($arr[0]){
                            $map = array(
                                'attach_type' => 'user_cert_head',
                                'uid' => $this->mid,
                                'type' => $type,
                                'name' => $img->getIndexedFilename(),
                                'save_path' => $ymd,
                                'save_name' => $img->getIndexedFilename(),
                                'from' => 0,
                                'width' => $arr[0],
                                'height' => $arr[1],
                            );
                            $map['ctime'] = time();
                            $attach_id = model('Attach')->add($map);
                            $this->mod->where('cert_id='.$cert_id)->save(array('user_photo' => $attach_id));
                        }
                    }
                }
                if($add_count > 0){
                    $this->success("导入成功,本次成功导入".$add_count.'条记录');
                }else{
                    $this->error('导入失败,请检查是否重复导入');
                }
            }
        }
        $this->error("请上传附件");
        exit;
    }
    /**
     * 编辑证书.
     */
    public function editCert()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageTab[] = array('title' => '编辑证书', 'tabHash' => 'editCert', 'url' => U('exam/AdminExamCert/editCert', ['cert_id' => $_GET['cert_id']]));
        $this->pageTitle['editCert'] = '编辑证书';
        $this->pageKeyList = $this->mod->getImportFields();
        array_unshift($this->pageKeyList,'cert_id');
        $this->savePostUrl = U('exam/AdminExamCert/doeditCert');
        $data = $this->mod->getCertById($_GET['cert_id']);
        $this->opt['user_sex'] = [1=>'男',2=>'女'];
        $this->opt['cert_is_revocation'] = [0=>'否',1=>'是'];
        $this->displayConfig($data);
    }

    /**
     * @name 处理添加证书
     */
    public function doaddCert()
    {
        if (!empty($_POST)) {
            $data = $this->mod->create($_POST);
            // 验证必填项
            if(!$data['user_name']){
                $this->error('请填写用户姓名');
            }else if(!$data['user_sex']){
                $this->error('请选择用户性别');
            }else if(!$data['user_native_place']){
                $this->error('请填写籍贯');
            }else if(!preg_match('/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/',$data['user_id_card'])){
                $this->error('请填写合法的身份证号');
            }else if(!(int)$data['user_photo']){
                $this->error('请上传用户照片');
            }else if(!$data['cert_code']){
                $this->error('请填写证书编号');
            }
            if ($this->mod->addCert($data)) {
                $this->jumpurl = U('admin/AdminExamCert/index');
                $this->success('添加成功');
            } else {
                $this->error('添加失败,请重试');
            }
        }
    }
    /**
     * @name 处理编辑证书
     */
    public function doeditCert()
    {
        if (!isset($_POST['cert_id'])) {
            $this->error('未查询到证书');
        }
        $data = $this->mod->create($_POST);
        // 验证必填项
        if(!$data['user_name']){
            $this->error('请填写用户姓名');
        }else if(!$data['user_sex']){
            $this->error('请选择用户性别');
        }else if(!$data['user_native_place']){
            $this->error('请填写籍贯');
        }else if(!preg_match('/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/',$data['user_id_card'])){
            $this->error('请填写合法的身份证号');
        }else if(!(int)$data['user_photo']){
            $this->error('请上传用户照片');
        }else if(!$data['cert_code']){
            $this->error('请填写证书编号');
        }
        $data['update_time'] = time();
        if ($this->mod->save($data)) {
            $this->success('编辑证书成功');
        } else {
            $this->error('编辑证书失败,请稍后重试');
        }
    }
    //获取考试相关数据
    private function _getData($limit = 20, $is_del = 0)
    {
        if (isset($_POST)) {
            if ($_POST['cert_code']) {
                $_POST['cert_code'] && $map['cert_code'] = intval($_POST['cert_code']);
            }
            if ($_POST['user_id_card']) {
                $_POST['user_id_card'] && $map['user_id_card'] = $_POST['user_id_card'];
            }
        }
        $map['is_del'] = $is_del;
        $list = M('ex_cert')->where($map)->order('update_time desc')->findPage($limit);
        foreach ($list['data'] as $key => $value) {
            $list['data'][$key]['create_time'] = $value['create_time'] ? date('Y-m-d H:i:s', $value['create_time']) : '';
            $list['data'][$key]['update_time'] = $value['update_time'] ? date('Y-m-d H:i:s', $value['update_time']) : '';
            $list['data'][$key]['DOACTION'] .= '<a href="'.U('exam/AdminExamCert/editCert', array('cert_id' => $value['cert_id'], 'tabHash' => 'editCert')).'">编辑</a> ';
            if($is_del == 0){
                $list['data'][$key]['DOACTION'] .='| <a href="javascript:admin.rmExamCert('.$value['cert_id'].');">锁定</a>';
            }else{
                $list['data'][$key]['DOACTION'] .='| <a href="javascript:admin.reExamCert('.$value['cert_id'].');">解锁</a>';
            }
        }

        return $list;
    }

    /**
     * @name 锁定证书
     */
    public function rmCert()
    {
        if (isset($_POST['cert_id'])) {
            $res = $this->mod->rmCert($_POST['cert_id']);
            if ($res) {
                echo json_encode(['status' => 1, 'info' => '锁定成功']);
                exit;
            } else {
                echo json_encode(['status' => 0, 'info' => '锁定失败,请稍后重试']);
                exit;
            }
        }
    }
    /**
     * @name 解锁证书
     */
    public function reCert()
    {
        if (isset($_POST['cert_id'])) {
            $res = $this->mod->reCert($_POST['cert_id']);
            if ($res) {
                echo json_encode(['status' => 1, 'info' => '解锁成功']);
                exit;
            } else {
                echo json_encode(['status' => 0, 'info' => '解锁失败,请稍后重试']);
                exit;
            }
        }
    }
}
