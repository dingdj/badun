<?php
/**
 * ��̨�̳ǹ���
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminConPriceAction extends AdministratorAction
{


    /**
     * 并发量折扣管理
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']       = '折扣列表';
        $this->pageTab[] = array('title'=>'折扣列表','tabHash'=>'index','url'=>U('live/AdminConPrice/index'));
        $this->pageTitle['addPrice']    = '添加折扣';
        $this->pageTab[] = array('title'=>'添加折扣','tabHash'=>'addPrice','url'=>U('live/AdminConPrice/addPrice'));
        parent::_initialize();

    }
    /**
     * 折扣管理
     */
    public function index(){
        // 管理分页项目
        $this->pageKeyList = array( 'discount_price','starttime','endtime','cuid','ctime','DOACTION');
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delPrice('','delPrice','删除','折扣')");

        //搜索字段
        $this->searchKey = array('discount_price','cuid',array('ctime','ctime1'),array('stime','stime1'));
        //数据的格式化
        $order = 'id desc';
        $list = $this-> _getList('index',null,$order,20);
        $this->assign('pageTitle', '折扣管理');
        $this->_listpk = 'starttime';
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
            $_POST['discount_price'] && $map['discount_price'] = intval(t($_POST['discount_price']));
            $_POST['id'] && $map['id'] = intval(t($_POST['id']));
            $_POST['concurrent_price'] && $map['concurrent_price'] = t($_POST['concurrent_price']);
            $_POST ['cuid'] && $map ['cuid'] = array('in', (string)$_POST ['cuid']);
            if (!empty ($_POST ['ctime'] [0]) && !empty ($_POST ['ctime'] [1])) {   //时间区间条件
                $map ['ctime'] = array('BETWEEN', array(strtotime($_POST ['ctime'] [0]),
                    strtotime($_POST ['ctime'] [1])));
            } else if (!empty ($_POST ['ctime'] [0])) {// 时间大于条件
                $map ['ctime'] = array('GT', strtotime($_POST ['ctime'] [0]));
            } elseif (!empty ($_POST ['ctime'] [1])) {// 时间小于条件
                $map ['ctime'] = array('LT', strtotime($_POST ['ctime'] [1]));
            }

            if (! empty ( $_POST ['etime'] [0] ) && ! empty ( $_POST ['etime'] [1] )) { // 时间区间条件
                $map ['etime'] = array ('BETWEEN',array (strtotime ( $_POST ['etime'] [0] ),
                    strtotime ( $_POST ['etime'] [1] )));
            } else if (! empty ( $_POST ['etime'] [0] )) {// 时间大于条件
                $map ['etime'] = array ('GT',strtotime ( $_POST ['etime'] [0] ));
            } elseif (! empty ( $_POST ['etime'] [1] )) {// 时间小于条件
                $map ['etime'] = array ('LT',strtotime ( $_POST ['etime'] [1] ));
            }

            if (! empty ( $_POST ['stime'] [0] ) && ! empty ( $_POST ['stime'] [1] )) { // 时间区间条件
                $map ['stime'] = array ('BETWEEN',array (strtotime ( $_POST ['stime'] [0] ),
                    strtotime ( $_POST ['etime'] [1] )));
            } else if (! empty ( $_POST ['stime'] [0] )) {// 时间大于条件
                $map ['stime'] = array ('GT',strtotime ( $_POST ['stime'] [0] ));
            } elseif (! empty ( $_POST ['stime'] [1] )) {// 时间小于条件
                $map ['stime'] = array ('LT',strtotime ( $_POST ['stime'] [1] ));
            }

        }
        $res = M('con_discount')->where($map)->distinct(true)->field('discount_price,starttime,endtime,cuid,ctime,is_del')-> order($order)->findPage($limit);
        foreach($res['data'] as $key => $val) {
            $res['data'][$key]['cuid'] = getUserSpace($val['cuid'], null, '_blank');
            $res['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $res['data'][$key]['starttime'] = date('Y-m-d H:i:s',$val["starttime"]);
            $res['data'][$key]['endtime'] = date('Y-m-d H:i:s',$val["endtime"]);

            if ($val['is_del'] == 0) {
                $res['data'][$key]['DOACTION'] .=  '  <a href="javascript:admin.mzPrice(' . $val['starttime'] . ',\'closePrice\',\'禁用\',\'折扣\');">禁用</a>';
            }else
            {
                $res['data'][$key]['DOACTION'] .= '  <a href="javascript:admin.mzPrice(' . $val['starttime'] . ',\'closePrice\',\'启用\',\'折扣\');"> 启用</a>';
            }

                $res['data'][$key]['DOACTION'] .=  ' | <a href="javascript:admin.delPrice(' . $val['starttime'] . ',\'delPrice\',\'彻底删除\',\'折扣\');"> 彻底删除</a>';
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
            'starttime' => array('in', $id)
        );
        $is_del = M('con_discount')->where($where)->getField('is_del');

        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('con_discount')->where($where)->save($data);

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
	if($_POST['ids']) {
		$tdata = array();
		foreach ($_POST['ids'] as $key => $val) {
			$tdata[$key] = strtotime($val);
		}
		$id = implode(",", $tdata);

		$id = trim(t($id), ",");
	}
	else {
		$id = intval($_POST['id']);
	}
        $msg = array();
        $where = array('starttime' => array('in', $id));
        $ret = M('con_discount')->where($where)->delete();

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
            if(empty($_POST['discount_price'])){$this->error("并发量折扣后价格不能为空");}
            if(empty($_POST['starttime'])){$this->error("开始时间不能为空");}
            if(empty($_POST['endtime'])){$this->error("结束时间不能为空");}
            if(strtotime($_POST['starttime']) > strtotime($_POST['endtime'])){$this->error("开始时间不能大于结束时间");}
            $now =time();
            if(strtotime($_POST['starttime']) < $now){$this->error("开始时间不能小于当前时间");}
            if(!is_numeric($_POST['discount_price'])){$this->error("折扣后价格必须为数字");}
            $data['discount_price']  = t($_POST['discount_price']);
            $data['ctime']  = $now;
            $data['is_del']  = 0;
            $data['stime']  = strtotime($_POST['starttime']);
            $data['etime']  = strtotime($_POST['endtime']);
            $data['starttime']  = strtotime($_POST['starttime']);
            $data['endtime']  = strtotime($_POST['endtime']);
            $data['stime'] = date('Y-m-d H:0', $data['stime']);
            $data['stime'] = strtotime($data['stime']);
            $data['cuid']   =  $this->mid;
            $data['etime'] = date('Y-m-d H:0',strtotime(t($_POST['endtime'])));
            $data['etime'] = strtotime($data['etime']);
            if($data['etime'] == strtotime(t($_POST['endtime'])))
            {

            }
            else {
                $data['etime'] =   $data['etime'] + 3600;
            }

            $discprice = M('con_discount');
            $startmap['stime'] =  $data['stime'] ;

            if($discprice -> where($startmap)->getField('id'))
            {
                $this ->error("选择时间已经存在折扣价格");
            }
            $startmap['stime'] =  $data['etime'] - 3600;

            if($discprice -> where($startmap)->getField('id'))
            {
                $this ->error("选择时间已经存在折扣价格");
            }
            $times = intval(($data['etime'] -$data['stime'])/3600);
            $insert_live_arrange_value = "";
            for($i=0;$i<$times ;$i++)
            {
                $insert_live_arrange_value .= "('" .  $data['discount_price'] . "','" . $data['stime'] . "','" . $data['starttime'] . "','" . $data['endtime'] . "','" .  $data['cuid'] . "','" .  $data['ctime'] . "','" .  $data['is_del'] . "'),";
                $data['stime'] = $data['stime'] + 3600;
            }

            $live_arrange_sql = "INSERT INTO " . C("DB_PREFIX") . "con_discount (`discount_price`,`stime`,`starttime`,`endtime`,`cuid`,`ctime`,`is_del`) VALUE " . trim($insert_live_arrange_value, ',');
            $res = $discprice ->execute($live_arrange_sql)? true : false;

            if($res){
                $this->assign('jumpUrl',U('live/AdminConPrice/index'));
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'addPrice';
            $this->onsubmit = 'admin.checkPrice(this)';
            $this->pageKeyList   = array('discount_price','starttime','endtime');
            $this->notEmpty   = array('discount_price','stime','etime');

            $this->savePostUrl = U('live/AdminConPrice/addPrice');
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
            if(empty($_POST['discount_price'])){$this->error("折扣后价格不能为空");}
            if(empty($_POST['stime'])){$this->error("开始时间不能为空");}
            if(empty($_POST['etime'])){$this->error("结束时间不能为空");}
            $data['discount_price']  = t($_POST['discount_price']);
            $data['stime']  = strtotime(t($_POST['stime']));
            $data['etime']  = strtotime(t($_POST['etime']));
            $data['stime'] = date('Y-m-d H:0', $data['stime']);
            $data['stime'] = strtotime($data['stime']);
            $data['etime'] = date('Y-m-d H:0', $data['etime']);
            $data['etime'] = strtotime($data['etime']);
            $data['ctime'] = time();
            $res = M('con_discount')->where(array('id'=>$id))->save($data);
            if($res){
                $this->assign('jumpUrl',U('live/AdminConPrice/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        }else{
            $_REQUEST['tabHash'] = 'editSchool';

            $this->onsubmit = 'admin.checkSchool(this)';
            $this->pageKeyList   = array('id','discount_price','stime','etime');
            $this->notEmpty   = array('discount_price','stime','etime');
            $id = intval($_GET['id']);
            if(!$id){
                $this->error("参数错误");
            }
            $price = M('con_discount')->where('id ='.$id)-> find() ;
            $this->pageTitle['editSchool'] = '编辑折扣';
             $price['stime'] = date('Y-m-d H:i:s',$price["stime"]);
             $price['etime'] = date('Y-m-d H:i:s',$price["etime"]);
            $this->savePostUrl = U('live/AdminConPrice/editPrice');
            $this->displayConfig($price);
        }
    }




















}