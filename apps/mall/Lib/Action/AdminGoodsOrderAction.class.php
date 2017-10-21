<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGoodsOrderAction extends AdministratorAction
{

    /**
     * 初始化
     */
    public function _initialize(){
        $this->pageTitle['index']      = '订单列表';


        parent::_initialize();
    }

    /**
     * 订单列表
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';

        $this->pageKeyList = array('id', 'uid', 'uname', 'sname', 'goods_id', 'goods_name', 'price', 'count', 'fare', 'location', 'ctime', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'uid', 'goods_id', 'price', 'fare', array('ctime','ctime1'));
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.delGoodsOrder()");
        // 数据的格式化
        $order = 'id desc';
        $list = $this->_getGoodsOrderList('index',20,$order);

        $this->displayList($list);
    }

    /**
     * 订单详情
     */
    public function viewGoodsOrderInfo(){
        $_REQUEST['tabHash'] = 'viewGoodsOrderInfo';

        $id = intval($_GET['id']);
//        if(!$id){
//            $this->error("参数错误");
//        }
        $goods_order = M('goods_order')->where(array('id'=>$id))->find();
      if($goods_order['sid'] == 0)
      {
          $goods_order['sid'] = "";
      }
        $good_name = model('Goods')->findGoodsInfo(array('id' => $goods_order['goods_id']));
        $goods_order['ctime'] = date('Y-m-d H:i:s',$goods_order["ctime"]);
        $goods_order['goods_name'] = $good_name['title'];
        $goods_order['s_name'] = model('School')->where('id='.$goods_order['sid'])->getField('title');
        $goods_order['uname'] = getUserSpace($goods_order['uid'], null, '_blank');

        $address = model('Address')->where('id='.$goods_order['address_id'])->field('name,phone,location,address')->find();
        $goods_order['name'] = $address['name'];
        $goods_order['phone'] = $address['phone'];
        $goods_order['location'] = $address['location'];
        $goods_order['address'] = $address['address'];

        $this->pageKeyList = array('id', 'uid', 'uname', 'sid','s_name', 'goods_id', 'goods_name', 'price', 'count', 'fare', 'name', 'phone', 'location', 'address', 'ctime');
        $this->pageTitle['viewGoodsOrderInfo'] = '商品订单_ID:'.$goods_order['id'];

        $this->pageTab[] = array('title'=>'订单列表','tabHash'=>'index','url'=>U('mall/AdminGoodsOrder/index'));
        $this->pageTab[] = array('title'=>'商品订单 - 查看详细','tabHash'=>'viewGoodsOrderInfo','url'=>U('mall/AdminGoodsOrder/viewGoodsOrderInfo',array('id'=>$id)));

        //点击按钮返回来源页面
        $this->submitAlias = '返 回';
        $this->onsubmit = 'admin.zyPageBack()';
        $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

        $this->displayConfig($goods_order);
    }

    /**
     * 解析订单列表数据
     * @param integer $type 状态
     * @param integer $limit 结果集数目，默认为20
     * @param integer $order 排序方式
     * @param $order 排序
     * @return array 解析后的商品列表数据
     */
    private function _getGoodsOrderList($type,$limit,$order,$map){
        if(isset($_POST)){
            $_POST['id'] && $map['id'] = intval($_POST['id']);
            $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
            $_POST['sid'] && $map['sid'] = intval($_POST['sid']);
            $_POST['goods_id'] && $map['goods_id'] = intval($_POST['goods_id']);
            $_POST['stock'] && $map['stock'] = intval($_POST['stock']);
            $_POST['fare'] && $map['fare']   = floatval($_POST['fare']);
            $_POST['price'] && $map['price']   = floatval($_POST['price']);
            if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) { // 时间区间条件
                $map ['ctime'] = array ('BETWEEN',array (strtotime ( $_POST ['ctime'] [0] ),
                    strtotime ( $_POST ['ctime'] [1] )));
            } else if (! empty ( $_POST ['ctime'] [0] )) {// 时间大于条件
                $map ['ctime'] = array ('GT',strtotime ( $_POST ['ctime'] [0] ));
            } elseif (! empty ( $_POST ['ctime'] [1] )) {// 时间小于条件
                $map ['ctime'] = array ('LT',strtotime ( $_POST ['ctime'] [1] ));
            }
        }
        $map['is_del'] = 0;
        $goods_order_info = model('Goods')->_getGoodsOrderList($limit,$order,$map);

        foreach($goods_order_info['data'] as $key => $val){
            $goods_order_info['data'][$key]['uname']  =  getUserSpace($val['uid'], null, '_blank');
            $goods_order_info['data'][$key]['ctime'] = date('Y-m-d H:i:s',$val["ctime"]);
            $good_name = model('Goods')->findGoodsInfo(array('id' => $val['goods_id']));
//            $goods_order_info['data'][$key]['goods_name'] = $good_name['title'];
            $url = U('mall/Goods/view', array('id' => $val['goods_id']));
            $goods_order_info['data'][$key]['goods_name'] = getQuickLink($url,$good_name['title'],"未知商品");
            /*if($goods_order_info['data'][$key]['sid'] == 0)
            {
                $goods_order_info['data'][$key]['sid']  ="";
            }*/
            $goods_order_info['data'][$key]['sname'] = model('School')->where('id='.$val['sid'])->getField('title');
            $goods_order_info['data'][$key]['location'] = model('Address')->where('id='.$val['address_id'])->getField('location');

            $goods_order_info['data'][$key]['DOACTION'] = '<a href="'.U('mall/AdminGoodsOrder/viewGoodsOrderInfo',array('id'=>$val['id'])).'">查看详细</a> ';

//            if($val['is_del'] == 0){
//                $goods_order_info['data'][$key]['is_del'] = "<span style='color: green;'>正常</span>";
//                $goods_order_info['data'][$key]['DOACTION'] .= "<a href=javascript:admin.closeGoodsOrder(" . $val['id'] . ",'closeVip');>删除（隐藏）</a> | ";
//            }else if($val['status'] == 1){
//                $goods_order_info['data'][$key]['is_del'] = "<span style='color: red;'>回收</span>";
//            }
//            $goods_order_info['data'][$key]['DOACTION'] .= '<a title="此操作会彻底删除数据" href="javascript:void(0)" onclick="admin.delGoodsOrder(\''.$val['id'].'\')">彻底删除</a>  ';
            switch(strtolower($type)){
                case 'index':
                    break;
            }
        }
        return $goods_order_info;
    }

//    public function delGoods(){
//        $id = intval($_POST['goods_id']);
//        $res = M('goods')->where(array('id'=>$id))->delete();
//        if($res){
//            $this->ajaxReturn(null,"删除成功",1);
//        }else{
//            $this->ajaxReturn(null,"删除失败",0);
//        }
//    }

    //批量删除商品订单
    public function delGoodsOrder(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $where = array(
            'id'=>array('in',$ids)
        );

        $data['is_del'] = 1;
        $res = M('goods_order')->where($where)->save($data);
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
}