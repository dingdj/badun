<?php

tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class IndexAction extends AdministratorAction
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $nav = array();
        $this->setTitle(L('PUBLIC_SYSTEM_MANAGEMENT'));

        $channel = C('admin_channel');
        $menu = C('admin_menu');

        // 顶级菜单
        foreach ($channel as $k => $v) {
            if (!CheckPermission('top_' . $k)) {
                unset($channel[$k]);
                unset($menu[$k]);
            }
        }
        $menu = $this->checkMenuAuth($menu);
        if(!is_admin($this->mid)){
            unset($channel['apps']);
            unset($channel['extends']);
            unset($menu['task']);
            unset($menu['extends']);
            unset($menu['apps']);
        }
        $this->assign('nav', $nav);
        $this->assign('channel', $channel);
        $this->assign('menu', $menu);
        $this->display();
    }

    protected function checkMenuAuth($menu = []){
        foreach($menu as $k=>$v){
            if(is_array($v)){
                $menu[$k] = $this->checkMenuAuth($v);
                if(empty($menu[$k])){
                    unset($menu[$k]);
                }
            }else {
                $params = '';
                if(strpos($v,'?') !== false){
                    list($v,$params) = explode('?',$v);
                }
                if (!CheckPermission($v)) {
                    unset($menu[$k]);
                    continue;
                }
                $menu[$k] = U($v).'&'.$params;
            }
        }
        return $menu;
    }

}

?>