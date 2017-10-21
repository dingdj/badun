<?php
/**
 * 后台，系统配置控制器
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class AdminlivetimeAction extends AdministratorAction
{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index'] = '排课截止时间管理';
        $this->pageTab[] = array('title' => '排课截止时间管理', 'tabHash' => 'index', 'url' => U('live/AdminLivetime/index'));
        parent::_initialize();
    }

    public function index()
    {
        $this->pageKeyList = array ('afnowhours');
        $this->displayConfig ();
    }
}