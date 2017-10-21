<?php
/**
 * NetworkAction
 * 地区网络
 * @uses Action
 * @package
 * @version $id$
 * @copyright 2009-2011 SamPeng
 * @author SamPeng <sampeng87@gmail.com>
 * @license PHP Version 5.2 {@link www.sampeng.cn}
 */
class AreaAction extends Action {
	//地区网络
    public function area() {
        //已选地区
        $selectedArea = explode(',',$_GET['selected']);
        if(!empty($selectedArea[0])) {
            $this->assign('selectedarea',t($_GET['selected']));
        }
        $pNetwork = D('Area');
        $list = $pNetwork->getNetworkList(0);
        $this->assign('list',json_encode($list));
        $this->display();
    }

    /**
     * 更改选择的城市，进行筛选数据
     */
    public function saveTemArea(){
        $visit_city = intval($_POST['city_id']);
        cookie('visit_city',$visit_city);
        $cookie =  cookie('visit_city');
        if($cookie){
            exit(json_encode(array('status' => '1', 'info' => '添加成功')));
        }
    }
}