<?php
/**
 * 分成列表信息管理控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminSplitAction extends AdministratorAction {
    /**
     * 初始化，访问控制及配置
     * @return void
     */
    public function _initialize() {
        parent::_initialize();

//        $this->pageTab[] = array('title'=>'云课堂用户分成余额列表','tabHash'=>'index','url'=>U('classroom/AdminSplit/index'));
//        $this->pageTab[] = array('title'=>'所有分成余额流水记录','tabHash'=>'flow','url'=>U('classroom/AdminSplit/flow'));
        $this->pageTab[] = array('title'=>'课程分成明细列表','tabHash'=>'splitVideo','url'=>U('classroom/AdminSplit/splitVideo'));
        $this->pageTab[] = array('title'=>'套餐分成明细列表','tabHash'=>'splitAlbum','url'=>U('classroom/AdminSplit/splitAlbum'));
        $this->pageTab[] = array('title'=>'直播课程分成明细列表','tabHash'=>'splitLive','url'=>U('classroom/AdminSplit/splitLive'));
        $this->pageTab[] = array('title'=>'线下课程分成明细列表','tabHash'=>'splitLineClass','url'=>U('classroom/AdminSplit/splitLineClass'));
    }
    
    /**
     * 分成列表信息管理
     * @return void
     */
    public function index(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('uname','realname','idcard','catagroy','school_title','balance','frozen','DOACTION');
        $this->pageTitle['index'] = '云课堂用户列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        //搜索项
        $this->searchKey = array('uid','school_title');
        $school = model('School')->field('id,title')->findALL();
        $this->opt['school_title'] = array('0'=>'不限')+array_column($school,'title','id');

        $this->searchPostUrl = U('classroom/AdminSplit/index', array('tabHash'=>'index'));
        //根据用户查找
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        if(!empty($_POST['school_title'])){
            $mhm_id = intval($_POST['school_title']);
            $uid = model('User')->where('mhm_id='.$mhm_id)->field('uid')->findALL();
            $new_uid = getSubByKey($uid,'uid');
            $map['uid'] = array('in', $new_uid);
        }

        $list = M('zy_split_balance')->where($map)->order('id DESC')->findPage();
        foreach($list['data'] as &$value){
            $user = M('user_verified')->where("uid=".$value["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $value['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$value['uid']] );
            $user_groups = '';
            foreach($user_group as &$val) {
                $user_groups .= $val['user_group_name'].'<br/>';
            }
            $value['realname']    = $user["realname"];
            $value['idcard']      = $user["idcard"];
            $value['catagroy']    = $user_groups;
            $value['uname']       = getUserSpace($value['uid'], null, '_blank');
            $value['balance']     = '<span style="color:green;">￥'.$value['balance'].'</span>';
            $value['frozen']      = '<span style="color:red;">￥'.$value['frozen'].'</span>';
            //处理机构信息
            $mhm_id = model('User')->where('uid='.$value['uid'])->getField('mhm_id');
            $s_map = array('id'=>$mhm_id);
            $school = model('School')->getSchoolFindStrByMap($s_map,'title,doadmin');
            if($school){
                if(!$school['doadmin']){
                    $url = U('school/School/index', array('id' => $val['mhm_id']));
                }else{
                    $url = getDomain($school['doadmin']);
                }
                $value['school_title'] = getQuickLink($url,$school['title'],"平台所有");
            }else{
                $value['school_title'] = "<span style='color: red;'>平台所有</span>";
            }
            $value['DOACTION'] =  '<a href="'.U(APP_NAME.'/'.MODULE_NAME.'/edit', array('id'=>$value['id'], 'tabHash'=>'edit')).'">编辑</a>';
//            $value['DOACTION'] .= ' | <a href="'.U('classroom/AdminSplit/learn',array('uid'=>$value['uid'],'tabHash'=>'learn')).'">TA的学习记录</a>';
            $value['DOACTION'] .= ' | <a href="'.U('classroom/AdminSplit/uflow',array('uid'=>$value['uid'],'tabHash'=>'uflow')).'">TA的分成账户流水</a>';
        }

        $this->displayList($list);
    }

    public function splitVideo(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','vid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','mount_school_id','mount_school_sum','mount_school_id','mount_school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitVideo'] = '课程分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");

        //搜索项 搜索条件：课程ID、课程名称、课程价格、平台、平台机构、课程机构、购买机构、分享者、日期筛选
        $this->searchKey = array('uid','vid','video_title','sum','pid','oschool_uid','sid','mount_school_id','share_id',['ltime1','ltime2']);
        $this->searchPostUrl = U('classroom/AdminSplit/splitVideo', array('tabHash'=>'splitVideo'));
        $school = M('school')->getField('uid,title');
        $school ? : $school = [];
        $this->opt['pid'] = [1 => '只看平台'];
        $this->opt['sid'] = [0 => '请选择'] + $school;
        $this->opt['oschool_uid'] = [0 => '请选择'] + $school;
        $this->opt['mount_school_id'] = [0 => '请选择'] + $school;

        $order = 'id desc';
        $list = $this->_list('zy_split_course',$map = [],$order,20);

        $this->pageButton[] = array('title'=>'导出当前结果','onclick'=>"admin.exportResult('{$list['list_ids']}','zy_split_course')");

        $this->displayList($list);
    }

    public function splitAlbum(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','aid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','mount_school_id','mount_school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitAlbum'] = '套餐分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");

        //搜索项 搜索条件：课程ID、课程名称、课程价格、平台、平台机构、课程机构、购买机构、分享者、日期筛选
        $this->searchKey = array('uid','aid','video_title','sum','pid','oschool_uid','sid','mount_school_id','share_id',['ltime1','ltime2']);

        $this->searchPostUrl = U('classroom/AdminSplit/splitAlbum', array('tabHash'=>'splitAlbum'));

        $school = M('school')->getField('uid,title');
        $school ? : $school = [];
        $this->opt['pid'] = [1 => '只看平台'];
        $this->opt['sid'] = [0 => '请选择'] + $school;
        $this->opt['oschool_uid'] = [0 => '请选择'] + $school;
        $this->opt['mount_school_id'] = [0 => '请选择'] + $school;


        $order = 'id desc';
        $list = $this->_list('zy_split_album',$map = [],$order,20);
        $this->pageButton[] = array('title'=>'导出当前结果','onclick'=>"admin.exportResult('{$list['list_ids']}','zy_split_album')");

        $this->displayList($list);
    }

    public function splitLive(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','lid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','mount_school_id','mount_school_sum','share_id','share_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitLive'] = '直播课程分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");

        //搜索项 搜索条件：课程ID、课程名称、课程价格、平台、平台机构、课程机构、购买机构、分享者、日期筛选
        $this->searchKey = array('uid','vid','video_title','sum','pid','oschool_uid','sid','mount_school_id','share_id',['ltime1','ltime2']);

        $this->searchPostUrl = U('classroom/AdminSplit/splitLive', array('tabHash'=>'splitLive'));

        $school = M('school')->getField('uid,title');
        $school ? : $school = [];
        $this->opt['pid'] = [1 => '只看平台'];
        $this->opt['sid'] = [0 => '请选择'] + $school;
        $this->opt['oschool_uid'] = [0 => '请选择'] + $school;
        $this->opt['mount_school_id'] = [0 => '请选择'] + $school;

        $order = 'id desc';
        $list = $this->_list('zy_split_live',$map = [],$order,20);
        $this->pageButton[] = array('title'=>'导出当前结果','onclick'=>"admin.exportResult('{$list['list_ids']}','zy_split_live')");

        $this->displayList($list);
    }

    //线下课分成明细
    public function splitLineClass(){
        // 页面具有的字段，可以移动到配置文件中！
        $this->pageKeyList = array('id','uid','vid','video_title','sum','pid','platform_sum','oschool_uid','ouats_ouschool_sum','sid','school_sum','ctime','ltime','note','mhm_id');
        $this->pageTitle['splitLineClass'] = '线下课程分成明细列表';
        //按钮
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");

        //搜索项 搜索条件：课程ID、课程名称、课程价格、平台、平台机构、课程机构、购买机构、分享者、日期筛选
        $this->searchKey = array('uid','vid','video_title','sum','pid','oschool_uid','sid','mount_school_id','share_id',['ltime1','ltime2']);
        $this->searchPostUrl = U('classroom/AdminSplit/splitLineClass', array('tabHash'=>'splitLineClass'));
        $school = M('school')->getField('uid,title');
        $school ? : $school = [];
        $this->opt['pid'] = [1 => '只看平台'];
        $this->opt['sid'] = [0 => '请选择'] + $school;
        $this->opt['oschool_uid'] = [0 => '请选择'] + $school;
        $this->opt['mount_school_id'] = [0 => '请选择'] + $school;

        $order = 'id desc';
        $list = $this->_list('zy_split_teacher',$map = [],$order,20);

        $this->pageButton[] = array('title'=>'导出当前结果','onclick'=>"admin.exportResult('{$list['list_ids']}','zy_split_course')");

        $this->displayList($list);
    }

    public function splitExport(){
        $ids = $_GET['id'];
        $type = $_GET['type'];
        if($type == 'zy_split_course'){
            $listData = M('zy_split_course')->where(['id'=>['in',$ids]])->order('id desc')->select();
        }elseif($type == 'zy_split_live'){
            $listData = M('zy_split_live')->where(['id'=>['in',$ids]])->order('id desc')->select();
        }elseif($type == 'zy_split_album'){
            $listData = M('zy_split_album')->where(['id'=>['in',$ids]])->order('id desc')->select();
        }

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
        if($type == 'zy_split_course'){
            $sheet_title = "课程";
        }elseif($type == 'zy_split_live'){
            $sheet_title = "直播";
        }elseif($type == 'zy_split_album'){
            $sheet_title = "套餐";
        }
        $time = time();

        $aime = date('Y-m-d H:i:s',$time);

        $objActSheet->setTitle("{$sheet_title}分成明细导出");

        //合并单元格
        $objActSheet->mergeCells('A1:R1');
        $objActSheet->setCellValue('A1', "{$sheet_title}分成明细{$aime}导出");
        $objStyleA1 = $objPHPExcel->getActiveSheet()->getStyle('A1');
        $objActSheet->setCellValue("A2", "id");
        $objActSheet->setCellValue("B2", "购买用户");
        $objActSheet->setCellValue("C2", "{$sheet_title}id");
        $objActSheet->setCellValue("D2", "{$sheet_title}名称");
        $objActSheet->setCellValue("E2", "购买金额");
        $objActSheet->setCellValue("F2", "平台分成");
        $objActSheet->setCellValue("G2", "购买机构");
        $objActSheet->setCellValue("H2", "购买机构金额");
        $objActSheet->setCellValue("I2", "{$sheet_title}所属机构");
        $objActSheet->setCellValue("J2", "{$sheet_title}所属机构分成");
        $objActSheet->setCellValue("K2", "挂载机构");
        $objActSheet->setCellValue("L2", "挂载机构金额");
        $objActSheet->setCellValue("M2", "分享者");
        $objActSheet->setCellValue("N2", "分享者分成");
        $objActSheet->setCellValue("O2", "生成时间");
        $objActSheet->setCellValue("P2", "最后操作时间");
        $objActSheet->setCellValue("Q2", "备注");
        $objActSheet->setCellValue("R2", "订单所属机构ID");
        $objStyleA1->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//设置垂直居中
        $objStyleA1->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置横向居中
        //颜色填充
        $objStyleA1->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objStyleA1->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_YELLOW);
        //字体设置
        $objStyleA1->getFont()->setName('Candara');
        $objStyleA1->getFont()->setSize(16);
        $objStyleA1->getFont()->setBold(true);
        $objStyleA1->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objStyleA1->getFont()->setBold(true);

        $objActSheet->getColumnDimension('A')->setWidth(15);
        $objActSheet->getColumnDimension('B')->setWidth(20);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(25);
        $objActSheet->getColumnDimension('F')->setWidth(15);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(25);
        $objActSheet->getColumnDimension('I')->setWidth(15);
        $objActSheet->getColumnDimension('J')->setWidth(25);
        $objActSheet->getColumnDimension('K')->setWidth(15);
        $objActSheet->getColumnDimension('L')->setWidth(25);
        $objActSheet->getColumnDimension('M')->setWidth(15);
        $objActSheet->getColumnDimension('N')->setWidth(25);
        $objActSheet->getColumnDimension('O')->setWidth(15);
        $objActSheet->getColumnDimension('P')->setWidth(15);
        $objActSheet->getColumnDimension('Q')->setWidth(15);
        $objActSheet->getColumnDimension('R')->setWidth(50);

        $sum                = floatval(0.00);
        $platform_sum       = floatval(0.00);
        $ouats_ouschool_sum = floatval(0.00);
        $school_sum         = floatval(0.00);
        $mount_school_sum   = floatval(0.00);
        $share_sum          = floatval(0.00);
        foreach ($listData as $key => $val) {
            $sum                += floatval($val['sum']);
            $platform_sum       += floatval($val['platform_sum']);
            $ouats_ouschool_sum += floatval($val['ouats_ouschool_sum']);
            $school_sum         += floatval($val['school_sum']);
            $mount_school_sum   += floatval($val['mount_school_sum']);
            $share_sum          += floatval($val['share_sum']);
        }

        $listData[count($listData)+1] = [
            "id" => "总计",
            "uid" => NULL,
            "vid" => NULL,
            "sum" => floatval($sum),
            "pid" => NULL,
            "platform_sum" => floatval($platform_sum),
            "oschool_uid" => NULL,
            "ouats_ouschool_sum" => floatval($ouats_ouschool_sum),
            "sid" => NULL,
            "school_sum" => floatval($school_sum),
            "mount_school_id" => NULL,
            "mount_school_sum" => floatval($mount_school_sum),
            "share_id" => NULL,
            "share_sum" => floatval($share_sum),
            "st_id" => NULL,
            "school_teacher_sum" => NULL,
            "ctime" => $time,
            "status" => NULL,
            "ltime" => $time,
            "note" => NULL,
            "mhm_id" => NULL,
            "order_id" => NULL,
            "rel_id" => NULL,
            "order_mhm_id" => NULL,
            "is_exchange" => NULL,
        ];

        foreach ($listData as $key => $val){
            $k = $key + 3;

            if($type == 'zy_split_course'){
                $info_title = M('zy_video')->where('id = '.$val['vid'])->getField('video_title');
                $osid = $val['vid'];
            } elseif($type == 'zy_split_live'){
                $info_title = M('zy_video')->where('id = '.$val['vid'])->getField('video_title');
                $osid = $val['vid'];
            }elseif($type == 'zy_split_album'){
                $info_title = M('album')->where('id = '.$val['aid'])->getField('album_title');
                $osid = $val['aid'];
            }

            $uname = getUserName($val['uid']);
            $oschool_uname = getUserName($val['oschool_uid']);
            $sid_uname = getUserName($val['sid']);
            $mount_school_uname = getUserName($val['mount_school_id']);
            $share_uname = getUserName($val['share_id']);
            $ctime = date('Y-m-d H:i:s',$val["ctime"]);
            $ltime = date('Y-m-d H:i:s',$val["ltime"]);

            //设置值
            $objActSheet->setCellValue("A$k", "{$val['id']}");
            $objActSheet->setCellValue("B$k", "{$uname}");
            $objActSheet->setCellValue("C$k", "{$osid}");
            $objActSheet->setCellValue("D$k", "{$info_title}");
            $objActSheet->setCellValue("E$k", "￥{$val['sum']}");
            $objActSheet->setCellValue("F$k", "￥{$val['platform_sum']}");
            $objActSheet->setCellValue("G$k", "{$oschool_uname}");
            $objActSheet->setCellValue("H$k", "￥{$val['ouats_ouschool_sum']}");
            $objActSheet->setCellValue("I$k", "{$sid_uname}");
            $objActSheet->setCellValue("J$k", "￥{$val['school_sum']}");
            $objActSheet->setCellValue("K$k", "{$mount_school_uname}");
            $objActSheet->setCellValue("L$k", "￥{$val['mount_school_sum']}");
            $objActSheet->setCellValue("M$k", "{$share_uname}");
            $objActSheet->setCellValue("N$k", "￥{$val['share_sum']}");
            $objActSheet->setCellValue("O$k", "{$ctime}");
            $objActSheet->setCellValue("P$k", "{$ltime}");
            $objActSheet->setCellValue("Q$k", "{$val['note']}");
            $objActSheet->setCellValue("R$k", "{$val['mhm_id']}");
        }
        $time = date('YmdHis',$time);
        /* 生成到浏览器，提供下载 */
        ob_end_clean();  //清空缓存
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename={$sheet_title}分成明细{$time}.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public function _list($type,$map,$order,$limit){
        $map['status'] = 1;
        //根据用户查找
        if(!empty($_POST['uid'])) {
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
        if(!empty($_POST['share_id'])) {
            $_POST['share_id'] = t($_POST['share_id']);
            $map['share_id'] = array('in', $_POST['share_id']);
        }
        if(isset($_POST)) {
            $_POST['sum'] && $map['sum'] = intval($_POST['sum']);
            $_POST['pid'] && $map['pid'] = intval($_POST['pid']);
            $_POST['oschool_uid'] && $map['oschool_uid'] = intval($_POST['oschool_uid']);
            $_POST['sid'] && $map['sid'] = intval($_POST['sid']);
            $_POST['mount_school_id'] && $map['mount_school_id'] = intval($_POST['mount_school_id']);
            if (! empty ( $_POST ['ltime1'] [0] ) && ! empty ( $_POST ['ltime1'] [1] )) { // 时间区间条件
                $map ['ltime'] = array ('BETWEEN',array (strtotime ( $_POST ['ltime1'] [0] ),
                    strtotime ( $_POST ['ltime1'] [1] )));
            } else if (! empty ( $_POST ['ltime1'] [0] )) {// 时间大于条件
                $map ['ltime'] = array ('GT',strtotime ( $_POST ['ltime1'] [0] ));
            } elseif (! empty ( $_POST ['ltime1'] [1] )) {// 时间小于条件
                $map ['ltime'] = array ('LT',strtotime ( $_POST ['ltime1'] [1] ));
            }
            if(t($_POST['video_title'])) {
                $map['note'] = ['like', "%{$_POST['video_title']}%"];
            }
        }

        if($type == 'zy_split_course'){
            $_POST['vid'] && $map['vid'] = intval($_POST['vid']);
            //方案二
//            if(t($_POST['video_title'])){
//                $vid = M('zy_video')->where(['video_title'=>['like',"%{$_POST['video_title']}%"]])->getField('video_title,id');
//                $map['vid']     = array('in', $vid);
//            }else{
//                $_POST['vid'] && $map['vid'] = intval($_POST['vid']);
//            }
            $list = M('zy_split_course')->where($map)->order($order)->findPage($limit);
            $list_ids = M('zy_split_course')->where($map)->order($order)->field('id')->select();;
            $list_ids = implode(',',getSubByKey(array_filter($list_ids),'id'));
        }elseif($type == 'zy_split_live'){
            if($_POST['vid']){
                $map['lid'] = intval($_POST['vid']);
            }
            //方案二
//            if(t($_POST['video_title'])){
//                $vid = M('zy_video')->where(['video_title'=>['like',"%{$_POST['video_title']}%"]])->getField('video_title,id');
//                $map['vid']     = array('in', $vid);
//            }else{
//                $_POST['vid'] && $map['vid'] = intval($_POST['vid']);
//            }
            $list = M('zy_split_live')->where($map)->order($order)->findPage($limit);
            $list_ids = M('zy_split_live')->where($map)->order($order)->field('id')->select();;
            $list_ids = implode(',',getSubByKey(array_filter($list_ids),'id'));
        }elseif($type == 'zy_split_album'){
            $_POST['aid'] && $map['aid'] = intval($_POST['aid']);
            $list = M('zy_split_album')->where($map)->order($order)->findPage($limit);
            $list_ids = M('zy_split_album')->where($map)->order($order)->field('id')->select();;
            $list_ids = implode(',',getSubByKey(array_filter($list_ids),'id'));
        }elseif($type == 'zy_split_teacher') {
            $_POST['vid'] && $map['vid'] = intval($_POST['vid']);
            $list = M('zy_split_teacher')->where($map)->order($order)->findPage($limit);
            $list_ids = M('zy_split_teacher')->where($map)->order($order)->field('id')->select();;
            $list_ids = implode(',', getSubByKey(array_filter($list_ids), 'id'));
        }

        foreach ($list['data'] as $key => $val){
            if($type == 'zy_split_course'){
                $url = U('classroom/Video/view', array('id' => $val['vid']));
                $title_info = "未知课程";
                $info_title = M('zy_video')->where('id = '.$val['vid'])->getField('video_title');
            } elseif($type == 'zy_split_live'){
                $url = U('live/Index/view', array('id' => $val['lid']));
                $title_info = "未知直播";
                $info_title = M('zy_video')->where('id = '.$val['lid'])->getField('video_title');
            }elseif($type == 'zy_split_album'){
                $url = U('classroom/Album/view', array('id' => $val['aid']));
                $title_info = "未知套餐";
                $info_title = M('album')->where('id = '.$val['aid'])->getField('album_title');
            }elseif($type == 'zy_split_teacher'){
                $url = U('classroom/LineClass/view', array('id' => $val['vid']));
                $title_info = "未知线下课程";
                $info_title = M('zy_teacher_course')->where('course_id = '.$val['vid'])->getField('course_name');
            }
//            $list['data'][$key]['video_title']   = $info_title;
            $list['data'][$key]['video_title'] = getQuickLink($url,$info_title,$title_info);

            $list['data'][$key]['uid']   = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $list['data'][$key]['ltime'] = date('Y-m-d H:i:s',$val["ltime"]);
            $list['data'][$key]['pid']   = getUserSpace($val['pid'], null, '_blank');
            $list['data'][$key]['sid']   = getUserSpace($val['sid'], null, '_blank');
            $list['data'][$key]['mount_school_id']   = getUserSpace($val['mount_school_id'], null, '_blank');
            $list['data'][$key]['oschool_uid']   = getUserSpace($val['oschool_uid'], null, '_blank');
            $list['data'][$key]['share_id'] = getUserSpace($val['share_id'], null, '_blank');
            $list['data'][$key]['st_id'] = getUserSpace($val['st_id'], null, '_blank');
            $list['data'][$key]['platform_sum']       = "<span style='color: red;'>￥{$val['platform_sum']}</span>";
            $list['data'][$key]['ouats_ouschool_sum']       = "<span style='color: red;'>￥{$val['ouats_ouschool_sum']}</span>";
            $list['data'][$key]['school_sum']         = "<span style='color: red;'>￥{$val['school_sum']}</span>";
            $list['data'][$key]['mount_school_sum']         = "<span style='color: red;'>￥{$val['mount_school_sum']}</span>";
            $list['data'][$key]['share_sum'] = "<span style='color: red;'>￥{$val['share_sum']}</span>";
            $list['data'][$key]['school_teacher_sum'] = "<span style='color: red;'>￥{$val['school_teacher_sum']}</span>";
        }
        $list['list_ids'] = $list_ids;
        return $list;
    }
    /**
     * 编辑操作
     */
    public function edit(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST['balance'] = floatval($_POST['balance']);
            $_POST['frozen'] = floatval($_POST['frozen']);
            $set = array(
                'id' => intval($_POST['id']),
                'balance'    => $_POST['balance'],
                'frozen'     => $_POST['frozen'],
            );
            $name = getUserName( M('zy_split_balance')->where('id=' . intval($_POST['id']))->getField('uid') );
            if(false !== M('zy_split_balance')->save($set)){
                LogRecord('admin_classroom','editBalance',array('uname'=>$name,'balance'=>$_POST['balance']),true);
                $this->success('保存成功！');
            }else{
                $this->error('保存失败！');
            }
            exit;
        }
        $_GET['id'] = intval($_GET['id']);
        $this->pageTab[] = array('title'=>'查看/修改','tabHash'=>'edit','url'=>U(APP_NAME.'/'.MODULE_NAME.'/edit', array('id'=>$_GET['id'],'tabHash'=>'edit')));
        $this->pageTitle['edit'] = '用户信息查看/修改';
        $this->savePostUrl = U(APP_NAME.'/'.MODULE_NAME.'/edit');
        $this->submitAlias = '确 定';
        $this->pageKeyList = array('id','uid','balance','frozen');
        $data = M('zy_split_balance')->find($_GET['id']);
        $data['uid'] = getUserSpace($data['uid'], null, '_blank');
        $this->displayConfig($data);
    }

    /**
     * 流水列表
     */
    public function flow(){
        $this->_flow(false);
    }
    
    //学习记录
    public function learn($limit=20){
        $_REQUEST['tabHash'] = 'learn'; 
        $this->pageButton[] = array('title'=>'删除记录','onclick'=>"admin.delLearnAll('delArticle')");
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
        $this->pageKeyList  = array('id','uname','video_title','sid','time','ctime','DOACTION');
        $this->searchKey    = array('id','uid','video_title','sid');
        $uid = intval( $_GET['uid'] );
        $learn = M('learn_record')->where('uid='.$uid)->order("ctime DESC")->findPage($limit);
        foreach($learn['data'] as &$val){
            $val['ctime'] = date('Y-m-d',$val['ctime']) ;
            $val['uname']  = getUserSpace($val['uid']);
            $val['video_title'] = M('zy_video')->where(array('id'=>$id))->getField('video_title'); 
            if($val['is_del'] == 1) {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'显示\',\'学习记录\');">显示</a>';
            }else {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'隐藏\',\'学习记录\');">隐藏</a>';
            }
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle','学习记录--'.$val['name']);
        $this->displayList($learn);
    }

    //显示/隐藏 学习记录
    public function closelearn(){
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('learn_record')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('learn_record')->where($where)->save($data);

        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    /**
     * 用户流水列表
     */
    public function uflow(){
        $this->_flow(intval($_GET['uid']));
    }


    public function _flow($uid){
        
        $this->pageKeyList = array('id','uname','realname','idcard','catagroy','type','num','balance','rel_id','note','ctime');
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
        $this->pageTitle[ACTION_NAME] = $uid?'账户流水-'.getUserName($uid):'所有流水记录';
        if($uid){
            $this->pageTab[]    = array('title'=>'账户流水-'.getUserName($_GET['uid']),'tabHash'=>ACTION_NAME,'url'=>U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME,array('uid'=>$uid)));
            $this->pageButton[] = array('title'=>'&lt;&lt;&nbsp;返回来源页','onclick'=>"admin.zyPageBack()");
            $this->searchKey    = array('type','note','startTime','endTime');
        }else{
            $this->searchKey    = array('uid','type','note','startTime','endTime');
        }

        $this->opt['type']  = array('全部','消费','充值','冻结','解冻','冻结扣除','分成收入');
        $this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));

        $map = array();
        if($uid){
            $map['uid'] = $uid;
        }elseif(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }

        if(!empty($_POST['type']) && $_POST['type']>0){
            $map['type'] = $_POST['type']-1;
        }
        if(!empty($_POST['note'])){
            $map['note'] = array('like', '%'.t($_POST['note']).'%');
        }
        //时间范围内进行查找
        if(!empty($_POST['startTime'])){
            $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
        }
        if(!empty($_POST['endTime'])){
            $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
        }

        $list = D('ZySplit')->flowModel()->where($map)->order('ctime DESC,id DESC')->findPage();
        $relTypes = D('ZySplit')->getRelTypes();
        foreach($list['data'] as $key=>$value){
            $user=D('user_verified')->where("uid=".$value["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $value['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$value['uid']] );
            $user_groups = '';
            foreach($user_group as &$val) {
                $user_groups .= $val['user_group_name'].'<br/>';
            }
            $list['data'][$key]['realname']  = $user["realname"];
            $list['data'][$key]['idcard']    = $user["idcard"];
            $list['data'][$key]['catagroy']  = $user_groups;
            $list['data'][$key]['uname']       = getUserSpace($value['uid'], null, '_blank');
            switch ($value['type']){
                case 0:$list['data'][$key]['type'] = "扣除";break;
                case 1:$list['data'][$key]['type'] = "增加";break;
                case 2:$list['data'][$key]['type'] = "冻结";break;
                case 3:$list['data'][$key]['type'] = "解冻";break;
                case 4:$list['data'][$key]['type'] = "冻结扣除";break;
                case 5:$list['data'][$key]['type'] = "分成收入";break;
            }
            if($value['ctime'] == 0){
                $list['data'][$key]['ctime']    =  '-';
            }else{
                $list['data'][$key]['ctime']    = date('Y-m-d H:i:s', $value['ctime']);
            }
            
            $list['data'][$key]['num']        = '<span style=color:red>￥'.$value['num'].'</span>';        
            $list['data'][$key]['balance']    = '<span style=color:green>￥'.$value['balance'].'</span>';
            $list['data'][$key]['rel_id']     = $value['rel_id']>0?$value['rel_id']:'-';
            if(isset($relTypes[$value['rel_type']])&&$value['rel_id']>0){
                $list['data'][$key]['rel_id'] = $relTypes[$value['rel_type']].'-ID:'.$value['rel_id'];
            }
        }
        $this->displayList($list);
    }

	public function recharge(){
		$this->pageTitle['recharge'] = '用户充值记录';
		$this->pageKeyList = array('id','uname','realname','idcard','catagroy','money','type','vip_length','note','ctime','status','stime','pay_order','pay_type');
        $this->pageButton[] = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
		$this->searchKey    = array('uid','startTime','endTime');
		$this->searchPostUrl= U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, array('uid'=>$uid, 'tabHash'=>ACTION_NAME));
		$recharge = D('ZyRecharge');
		$map['status'] = array('gt', 0);
		if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid'] = array('in', $_POST['uid']);
        }
		//时间范围内进行查找
        if(!empty($_POST['startTime'])){
            $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
        }
        if(!empty($_POST['endTime'])){
            $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
        }
		$data = $recharge->where($map)->order('stime DESC,id DESC')->findPage();
		$types = array('分成充值', '会员充值');
		$status= array('未支付', '已成功', '失败');
		$payType = array('alipay'=>'支付宝', 'unionpay'=>'银联');
		foreach($data['data'] as &$val){
            $user=D('user_verified')->where("uid=".$val["uid"])->find();
            $user_group = model ( 'UserGroupLink' )->getUserGroup ( $val['uid'] );
            $user_group = model ( 'UserGroup' )->getUserGroup ( $user_group[$val['uid']] );
            $user_groups = '';
            foreach($user_group as &$value) {
                $user_groups .= $value['user_group_name'].'<br/>';
            }
            $val['realname'] = $user["realname"];
            $val['idcard']   = $user["idcard"];
            $val['catagroy'] = $user_groups;
			$val['uname']   = getUserSpace($val['uid'], null, '_blank');
			$val['ctime'] = friendlyDate($val['ctime']);
			$val['type']  = isset($types[$val['type']])?$types[$val['type']]:'-';
			$val['money'] = '￥'.$val['money'];
			$val['status']= $status[$val['status']];
			$val['stime'] = friendlyDate($val['stime']);
			$val['stime'] = $val['stime']?$val['stime']:'-';
			$val['pay_type']  = isset($payType[$val['pay_type']])?$payType[$val['pay_type']]:'-';
		}
		$this->displayList($data);
	}
}