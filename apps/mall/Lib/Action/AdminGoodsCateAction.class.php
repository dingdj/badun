<?php
/**
 * 后台商城管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminGoodsCateAction extends AdministratorAction
{

    protected $base_config = array();

    /**
     * 初始化，
     */
    public function _initialize() {
        $this->pageTitle['index']      = '分类';

        parent::_initialize();
    }

    /**
     * 商品分类列表
     */
    public function index(){
        $treeData = model ( 'CategoryTree' )->setTable ( 'goods_category' )->getNetworkList ();
        $this->displayCoverTree ( $treeData, 'goods_category');
    }
}