<?php
/**
 * ��̨�̳ǹ���
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class ConPriceAction extends AdministratorAction
{


    /**
     * 并发量折扣管理
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '折扣列表';
        $this->pageTab[] = array('title'=>'折扣列表','tabHash'=>'index','url'=>U('admin/ConPrice/index'));
        $this->pageTitle['addPrice']    = '添加折扣';
        $this->pageTab[] = array('title'=>'添加折扣','tabHash'=>'addPrice','url'=>U('admin/ConPrice/addPrice'));
        parent::_initialize();

    }
    /**
     * 折扣管理
     */
    public function index(){
        // 管理分页项目
        $this->pageKeyList = array( 'price','sTime','eTime','cuid','ctime','DOACTION');
        $this->pageButton[] = array('title'=>'删除折扣','onclick'=>"admin.delPrice('','delPrice','删除','折扣')");
        $this->pageButton[] = array('title' => '搜索折扣', 'onclick' => "admin.fold('search_form')");

        //搜索字段
        $this->searchKey = array('price','cuid',array('ctime','ctime1'),array('sTime','sTime1'),array('eTime','eTime1'));
        //数据的格式化
        $order = 'id desc';
        $list = $this-> _getList('index',null,$order,20);
        $this->assign('pageTitle', '折扣管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
    }

    /***
     * @param $type
     * @param $limit
     * @param $order
     * @return mixed
     * 机构管理列表
     */
    private function _getList($type,$limit,$order){
        if(isset($_POST)) {
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['price'] && $map['price'] = t($_POST['price']);
            $_POST ['cuid'] && $map ['cuid'] = array('in', (string)$_POST ['cuid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) {   //时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }

            if (! empty ( $_POST ['eTime'] [0] ) && ! empty ( $_POST ['eTime'] [1] )) { // 时间区间条件
                $map ['eTime'] = array ('BETWEEN',array (strtotime ( $_POST ['eTime'] [0] ),
                    strtotime ( $_POST ['eTime'] [1] )));
            } else if (! empty ( $_POST ['eTime'] [0] )) {// 时间大于条件
                $map ['eTime'] = array ('GT',strtotime ( $_POST ['eTime'] [0] ));
            } elseif (! empty ( $_POST ['eTime'] [1] )) {// 时间小于条件
                $map ['eTime'] = array ('LT',strtotime ( $_POST ['eTime'] [1] ));
            }

            if (! empty ( $_POST ['sTime'] [0] ) && ! empty ( $_POST ['sTime'] [1] )) { // 时间区间条件
                $map ['sTime'] = array ('BETWEEN',array (strtotime ( $_POST ['sTime'] [0] ),
                    strtotime ( $_POST ['eTime'] [1] )));
            } else if (! empty ( $_POST ['sTime'] [0] )) {// 时间大于条件
                $map ['sTime'] = array ('GT',strtotime ( $_POST ['sTime'] [0] ));
            } elseif (! empty ( $_POST ['sTime'] [1] )) {// 时间小于条件
                $map ['sTime'] = array ('LT',strtotime ( $_POST ['sTime'] [1] ));
            }

        }
        $res = M('concurrent_price')->where($map)-> order($order)->findPage($limit);
        foreach($res['data'] as $key => $val) {
            $res['data'][$key]['cuid'] = getUserSpace($val['cuid'], null, '_blank');
            $res['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $res['data'][$key]['eTime'] = date('Y-m-d H:i:s',$val["eTime"]);
            $res['data'][$key]['sTime'] = date('Y-m-d H:i:s',$val["sTime"]);
            $res['data'][$key]['DOACTION'] =  '<a href="'.U('admin/ConPrice/editPrice',array('id'=>$val['id'],'tabHash'=>'editPrice')).'">编辑</a>';
            if ($val['is_del'] == 0) {
                $res['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.mzPrice(' . $val['id'] . ',\'closePrice\',\'禁用\',\'折扣\');">禁用</a>';
            }else
            {
                $res['data'][$key]['DOACTION'] .= ' | <a href="javascript:admin.mzPrice(' . $val['id'] . ',\'closePrice\',\'启用\',\'折扣\');"> 启用</a>';
            }

                $res['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.delPrice(' . $val['id'] . ',\'delPrice\',\'彻底删除\',\'折扣\');"> 彻底删除</a>';
            }
        $this->assign('pageTitle','折扣管理列表');
        $this->_listpk = 'id';
        $this->allSelected = true;
        return $res;
    }

    /**
     * 启用/禁用
     */
    public function closePrice()
    {
        $msg =array();
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('concurrent_price')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('concurrent_price')->where($where)->save($data);

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
     * 彻底删除操作
     * @return void
     */
    public function  delPrice()
    {
        $id = implode(",", $_POST['ids']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['ids']);
        }
        $msg = array();
        $where = array('id' => array('in', $id));
        $ret = M('concurrent_price')->where($where)->delete();
        if ($ret == true) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
        } else {
            $msg['data'] = '操作失败';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();
    }

    /**
     * 添加折扣
     */
    public function addPrice(){
        if(isset($_POST)){
            if(empty($_POST['price'])){$this->error("并发量折扣后价格不能为空");}
            if(empty($_POST['sTime'])){$this->error("开始时间不能为空");}
            if(empty($_POST['eTime'])){$this->error("结束时间不能为空");}
            if(strtotime($_POST['sTime']) > strtotime($_POST['eTime'])){$this->error("开始时间不能大于结束时间");}
            $now =time();
            if(strtotime($_POST['sTime']) < $now){$this->error("开始时间不能小于当前时间");}
            if(!is_numeric($_POST['price'])){$this->error("折扣后价格必须为数字");}
            $data['price']  = t($_POST['price']);
            $data['cuid']   =  $this->mid;
            $data['ctime']  = $now;
            $data['sTime']  = strtotime($_POST['sTime']);
            $data['eTime']  = strtotime($_POST['eTime']);
            $data['sTime'] = date('Y-m-d H:0', $data['sTime']);
            $data['sTime'] = strtotime($data['sTime']);
            $data['eTime'] = date('Y-m-d H:0', $data['eTime']);
            $data['eTime'] = strtotime($data['eTime']);
            $res = M('concurrent_price')->add($data);
            if($res){
                $this->assign('jumpUrl',U('admin/ConPrice/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'addPrice';
            $this->onsubmit = 'admin.checkPrice(this)';
            $this->pageKeyList   = array('price','sTime','eTime');
            $this->notEmpty   = array('price','sTime','eTime');

            $this->savePostUrl = U('admin/ConPrice/addPrice');
            $this->displayConfig();
        }
    }

    /**
     * 修改折扣信息
     */
    public function editPrice(){
        if(isset($_POST)){
            $id = intval($_POST['id']);
            if(!$id){
                $this->error("参数错误");
            }
            if(empty($_POST['price'])){$this->error("折扣后价格不能为空");}
            if(empty($_POST['sTime'])){$this->error("开始时间不能为空");}
            if(empty($_POST['eTime'])){$this->error("结束时间不能为空");}
            $data['price']  = t($_POST['price']);
            $data['sTime']  = strtotime(t($_POST['sTime']));
            $data['eTime']  = strtotime(t($_POST['eTime']));
            $data['sTime'] = date('Y-m-d H:0', $data['sTime']);
            $data['sTime'] = strtotime($data['sTime']);
            $data['eTime'] = date('Y-m-d H:0', $data['eTime']);
            $data['eTime'] = strtotime($data['eTime']);
            $res = M('concurrent_price')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('admin/ConPrice/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editSchool';

            $this->onsubmit = 'admin.checkSchool(this)';
            $this->pageKeyList   = array('id','price','sTime','eTime');
            $this->notEmpty   = array('price','sTime','eTime');
            $id = intval($_GET['id']);
            if(!$id){
                $this->error("参数错误");
            }
            $price = M('concurrent_price')->where('id ='.$id)-> find() ;
            $this->pageTitle['editSchool'] = '编辑折扣';
             $price['sTime'] = date('Y-m-d H:i:s',$price["sTime"]);
             $price['eTime'] = date('Y-m-d H:i:s',$price["eTime"]);
            $this->savePostUrl = U('admin/ConPrice/editPrice');
            $this->displayConfig($price);
        }
    }




















}