<?php
/**
 * 文库管理配置
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminLibraryAction extends AdministratorAction
{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index'] = '列表';
        //$this->pageTitle['disable'] = '禁用文库列表';
        //$this->pageTitle['close'] = '文库回收列表';
        $this->pageTitle['addLibrary'] = '添加';

        $this->pageTab[] = array('title' => '列表', 'tabHash' => 'index', 'url' => U('classroom/AdminLibrary/index'));
        //$this->pageTab[] = array('title' => '禁用文库列表', 'tabHash' => 'disable', 'url' => U('classroom/AdminLibrary/disable'));
//        $this->pageTab[] = array('title' => '文库回收列表', 'tabHash' => 'close', 'url' => U('classroom/AdminLibrary/close'));
        $this->pageTab[] = array('title' => '添加', 'tabHash' => 'addLibrary', 'url' => U('classroom/AdminLibrary/addLibrary'));

        parent::_initialize();
    }

    /**
     * 文库列表管理
     * @return void
     */
    public function index()
    {
        $_REQUEST['tabHash'] = 'index';

        // 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array('id', 'uid',  'title', 'price', 'aid', 'info', 'status', 'is_reviewed', 'is_re','down_nums','ctime', 'DOACTION');
//		$this->pageButton[] = array('title'=>'删除点评','onclick'=>"admin.mzReviewEdit('','delreview','删除','点评')");
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]  =  array('title'=>'禁用','onclick'=>"admin.delLibraryAll('delLibrarys',2)");
        $this->searchKey = array('id', 'uid', 'title', 'price', 'status', 'down_nums',array('ctime', 'ctime1'));

        $this->opt['status'] = array('0'=>'不限','1' => "正常", '2' => "禁用");

        $order = 'id desc';
        $list = $this->_getLibraryList('index', null, $order, 20);

        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }

    /**
     * 禁用文库列表管理
     * @return void
     */
    public function disable()
    {
        $_REQUEST['tabHash'] = 'disable';

        $this->pageKeyList = array('id', 'uid', 'uname', 'title', 'price', 'aid', 'info', 'status', 'is_reviewed', 'ctime', 'DOACTION');
//		$this->pageButton[] = array('title'=>'删除点评','onclick'=>"admin.mzReviewEdit('','delreview','删除','点评')");
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]  =  array('title'=>'删除','onclick'=>"admin.delLibraryAll('delLibrarys',3)");
        $this->searchKey = array('id', 'uid', 'title', 'price', array('ctime', 'ctime1'));

        $order = 'id desc';
        $map['status'] = 2;
        $list = $this->_getLibraryList('disable', $map, $order, 20);

        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }

    /**
     * 禁用文库列表管理
     * @return void
     */
//    public function close()
//    {
//        $_REQUEST['tabHash'] = 'close';
//
//        $this->pageKeyList = array('id', 'uid', 'uname', 'title', 'price', 'aid', 'info', 'status', 'is_reviewed', 'ctime', 'DOACTION');
////		$this->pageButton[] = array('title'=>'删除点评','onclick'=>"admin.mzReviewEdit('','delreview','删除','点评')");
//        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
//        $this->searchKey = array('id', 'uid', 'title', 'price', 'status', array('ctime', 'ctime1'));
//
//        $order = 'id desc';
//        $map['status'] = 0;
//        $list = $this->_getLibraryList('close', $map, $order, 20);
//
//        $this->_listpk = 'id';
//        $this->allSelected = true;
//        array_values($list);
//        $this->displayList($list);
//    }

    /**
     * 添加新文库
     */
    public function addLibrary()
    {
        if (isset($_POST)) {
            if (empty($_POST['title'])) {
                $this->error("文库名称不能为空");
            }
            if (empty($_POST['library_levelhidden'])) {
                $this->error("请选择分类");
            }
            if (empty($_POST['info'])) {
                $this->error("商品文库不能为空");
            }
            if ($_POST['price'] == '') {
                $this->error("商品文库不能为空");
            }
            if (!is_numeric($_POST['price'])) {
                $this->error('价格必须为数字');
            }

            $myAdminLevelhidden = getCsvInt(t($_POST['library_levelhidden']), 0, true, true, ',');  //处理分类全路径
            $fullcategorypath = explode(',', $_POST['library_levelhidden']);
            $category = array_pop($fullcategorypath);
            $category = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['category']     = $category == '0' ? array_pop($fullcategorypath) : $category;
            $data['fullcategorypath']     = $myAdminLevelhidden; //分类全路径

            $data['title'] = t($_POST['title']);
            $data['info'] = t($_POST['info']);
            $data['price'] = floatval($_POST['price']);
            $data['attach_id'] = trim($_POST['attach_id_ids'], '|');
            $data['is_reviewed'] = 1;
            $data['is_re'] = intval($_POST['is_re']);
            $data['ctime'] = time();
            $data['uid'] = $this->mid;
            $res = model('Doc')->add($data);
            if ($res) {
                $this->assign('jumpUrl', U('classroom/AdminLibrary/index'));
                $this->success("添加成功");
            } else {
                $this->error("添加失败");
            }
        } else {
            $_REQUEST['tabHash'] = 'addLibrary';

            $this->onsubmit = 'admin.checkLibrary(this)';
            $this->pageKeyList = array('title', 'library_cate', 'info', 'is_re', 'price', 'attach_id');
            $this->notEmpty = array('title', 'library_cate', 'info', 'is_re', 'price', 'attach_id');
            $this->opt['is_re'] = array('0' => '不推荐', '1' => '推荐');

            ob_start();
            echo W('CategoryLevel', array('table' => 'doc_category', 'id' => 'library_level'));
            $output = ob_get_contents();
            ob_end_clean();
            
            $this->savePostUrl = U('classroom/AdminLibrary/addLibrary');
            $this->displayConfig(array('library_cate' => $output));
        }
    }

    /**
     * 编辑文库
     */
    public function editLibrary()
    {

        if (isset($_POST)) {
            $id = intval($_POST['id']);

            if (!$id) {
                $this->error("参数错误");
            }
            if (empty($_POST['title'])) {
                $this->error("文库名称不能为空");
            }
            if (empty($_POST['library_levelhidden'])) {
                $this->error("请选择分类");
            }
            if (empty($_POST['info'])) {
                $this->error("商品文库不能为空");
            }
            if ($_POST['price'] == '') {
                $this->error("商品文库不能为空");
            }
            if (!is_numeric($_POST['price'])) {
                $this->error('价格必须为数字');
            }

            $myAdminLevelhidden = getCsvInt(t($_POST['library_levelhidden']), 0, true, true, ',');  //处理分类全路径
            $fullcategorypath = explode(',', $_POST['library_levelhidden']);
            $category = array_pop($fullcategorypath);
            $category = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['category']     = $category == '0' ? array_pop($fullcategorypath) : $category;
            $data['fullcategorypath']     = $myAdminLevelhidden; //分类全路径

            $data['title'] = t($_POST['title']);
            $data['info'] = t($_POST['info']);
            $data['is_re'] = intval($_POST['is_re']);
            $data['price'] = floatval($_POST['price']);
            if ($data['attach_id']) {
                $data['attach_id'] = trim($_POST['attach_id_ids'], '|');
            }
            $data['ctime'] = time();
            $data['uid'] = $this->mid;

            $res = M('doc')->where(array('id' => $id))->save($data);
            if ($res) {
                $this->assign('jumpUrl', U('classroom/AdminLibrary/index'));
                $this->success("编辑成功");
            } else {
                $this->error("编辑失败");
            }
        } else {
            $_REQUEST['tabHash'] = 'editLibrary';

            $this->onsubmit = 'admin.checkLibrary(this)';
            $this->pageKeyList = array('id', 'title', 'library_cate', 'info', 'is_re', 'price', 'attach_id');
            $this->notEmpty = array('title', 'library_cate', 'info', 'is_re', 'price', 'attach_id');

            $this->opt['is_re'] = array('0' => '不推荐', '1' => '推荐');
            $id = intval($_GET['id']);

            $library_info = M('doc')->where(array('id' => $id))->find();


            $this->pageTitle['editLibrary'] = '编辑文库-' . $library_info['title'];

            ob_start();
            echo W('CategoryLevel', array('table' => 'doc_category', 'id' => 'library_level', 'default' => trim($library_info['fullcategorypath'], ',')));
            $library_info['library_cate'] = ob_get_contents();
            ob_end_clean();
            $this->savePostUrl = U('classroom/AdminLibrary/editLibrary');
            $this->displayConfig($library_info);
        }
    }

    private function _getLibraryList($type, $map, $order, $limit)
    {
        if (isset($_POST)) {
            $_POST ['id'] && $map ['id'] = intval($_POST ['id']);
            $_POST ['status'] && $map ['status'] = intval($_POST ['status']);
            $_POST ['down_nums'] && $map ['down_nums'] = intval($_POST ['down_nums']);
            $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST['price'] && $map['price'] = floatval($_POST['price']);

           
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) { // 时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }
        }
        $map['is_del'] = 0;
        $list = M('doc')-> where($map)-> order("ctime desc")->findPage($limit);
        foreach ($list['data'] as $key => $val) {
            $list['data'][$key]['id'] = $val['id'];
            $list['data'][$key]['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $list['data'][$key]['uid'] = getUserSpace($val['uid'], null, '_blank');
            $list['data'][$key]['info'] = mb_substr($val['info'], 0, 20, 'utf-8') . '...';
            $url = U('classroom/Library/index');
            $list['data'][$key]['title'] = getQuickLink($url,$val['title'],"未知文库");
            if ($val['is_reviewed'] == 0) {
                $list['data'][$key]['is_reviewed'] = "<span style='color: red;'>未审核</span>";
            } else if ($val['is_reviewed'] == 1) {
                $list['data'][$key]['is_reviewed'] = "<span style='color: green;'>已审核</span>";
            }
            $list['data'][$key]['is_re'] = $val['is_re'] ? '推荐' : '不推荐';

//            if ($val['status'] == 0) {
//                $list['data'][$key]['status'] = "<span style='color: red;'>删除</span>";
//            }
            if ($val['status'] == 1) {
                $list['data'][$key]['status'] = "<span style='color: green;'>正常</span>";
            } else if ($val['status'] == 2) {
                $list['data'][$key]['status'] = "<span style='color: #9c9c9c;'>禁用</span>";
            }
            switch (strtolower($type)) {
                case 'index':
                    if ($val['status'] != 2 && $val['status'] != 0) {
                        $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.closeLibrary(\'' . $val['id'] . '\')">禁用</a> | ';
                    }
                    if ($val['status'] == 2) {
                        $list['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openLibrary(\'' . $val['id'] . '\')">启用</a> | ';
                    }
                    break;
                case 'disable':
                    $list['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openLibrary(\'' . $val['id'] . '\')">启用</a> | ';

                    break;
            }
            $list['data'][$key]['DOACTION'] .= '<a href="' . U('classroom/AdminLibrary/editLibrary', array('id' => $val['id'])) . '">编辑</a>';
//            if ($val['status'] != 0) {
//                $list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.delLibrary(\'' . $val['id'] . '\')">删除</a>  ';
//            } else {
//                $list['data'][$key]['DOACTION'] .= '<a href="javascript:;" onclick="admin.openLibrary(\'' . $val['id'] . '\')">启用</a>';
//            }
//            switch (strtolower($type)) {
//                case 'close':
//                    $list['data'][$key]['DOACTION'] .= ' | <a href="javascript:void(0)" onclick="admin.deleteLibrary(\'' . $val['id'] . '\')">彻底删除</a>  ';
//                    break;
//            }
        }
        return $list;
    }

    //批量禁用--删除文库
    public function delLibrarys(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id'=>array('in',$ids)
        );
        if($_POST['status'] == 3){
            $data['is_del'] = 1;
        }else{
            $data['status'] = $_POST['status'];
        }
        $res = M('doc')->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    /**
     * 彻底删除商品操作
     */
    public function deleteLibrary()
    {
        $id = intval($_POST['library_id']);
        if (!$id) {
            $this->ajaxReturn(null, "参数错误", 0);
        }
        $res = M('doc')->where(array('id' => $id))->delete();
        if ($res) {
            $this->ajaxReturn(null, "彻底删除成功", 1);
        } else {
            $this->ajaxReturn(null, "彻底删除失败", 0);
        }
    }

    /**
     * 删除商品操作
     */
    public function delLibrary()
    {
        $id = intval($_POST['library_id']);
        if (!$id) {
            $this->ajaxReturn(null, "参数错误", 0);
        }
//        $data['status'] = 0;
//        $res = M('doc')->where(array('id' => $id))->save($data);
        $res = M('doc')->where(array('id' => $id))->delete();
        if ($res) {
            $this->ajaxReturn(null, "删除成功", 1);
        } else {
            $this->ajaxReturn(null, "删除失败", 0);
        }
    }

    /**
     * 启用商品操作
     */
    public function openLibrary()
    {
        $id = intval($_POST['library_id']);
        if (!$id) {
            $this->ajaxReturn(null, "参数错误", 0);
        }
        $data['status'] = 1;
        $res = M('doc')->where(array('id' => $id))->save($data);
        if ($res) {
            $this->ajaxReturn(null, "启用成功", 1);
        } else {
            $this->ajaxReturn(null, "启用失败", 0);
        }
    }

    /**
     * 禁用商品操作
     */
    public function closeLibrary()
    {
        $id = intval($_POST['library_id']);
        if (!$id) {
            $this->ajaxReturn(null, "参数错误", 0);
        }
        $data['status'] = 2;
        $res = M('doc')->where(array('id' => $id))->save($data);
        if ($res) {
            $this->ajaxReturn(null, "禁用成功", 1);
        } else {
            $this->ajaxReturn(null, "禁用失败", 0);
        }
    }
}