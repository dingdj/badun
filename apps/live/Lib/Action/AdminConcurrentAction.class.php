<?php
/**
 * 后台，系统配置控制器
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class AdminConcurrentAction extends AdministratorAction {

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize(){
        $this->pageTitle['index']   = '并发量管理';
        $this->pageTab[] = array( 'title' =>'并发量管理', 'tabHash' => 'index', 'url' => U('live/AdminConcurrent/index') );
        parent::_initialize();
    }


    public function index()
    {
        if (isset($_POST)) {
            $result['Concurrent_nums'] = intval(t($_POST['Concurrent_nums']));
            $result['Con_oprice'] = t($_POST['Con_oprice']);
            $result['onehprice'] = t($_POST['onehprice']);
            $result['onemprice'] = t($_POST['onemprice']);
            $result['threemprcie'] = t($_POST['threemprcie']);
            $result['sixmprice'] = t($_POST['sixmprice']);
            $result['oneyprice'] = t($_POST['oneyprice']);
            $result['blueconprice'] = t($_POST['blueconprice']);
            $result['ctime'] = time();
            if (empty($_POST['Concurrent_nums'])) {
                $this->error("并发量数目不能为空！");
            }
            if (empty($_POST['onehprice'])) {
                $this->error("单并发一个小时原价不能为空！");
            }
            if (empty($_POST['onemprice'])) {
                $this->error("单并发一个月价格不能为空！");
            }
            if (empty($_POST['threemprcie'])) {
                $this->error("单并发三个月价格不能为空！");
            }
            if (empty($_POST['sixmprice'])) {
                $this->error("单并发六个月价格不能为空！");
            }
            if (empty($_POST['oneyprice'])) {
                $this->error("单并发一年价格不能为空！");
            }
            if (empty($_POST['blueconprice'])) {
                $this->error("绿色通道价格不能为空！");
            }

            if (!is_numeric($_POST['threemprcie'])) {
                $this->error("单并发三个月原价必须为数字");
            }
            if (!is_numeric($_POST['sixmprice'])) {
                $this->error("单并发六个月原价必须为数字");
            }
            if (!is_numeric($_POST['oneyprice'])) {
                $this->error("单并发一年原价必须为数字!");
            }
            if (!is_numeric($_POST['oneyprice'])) {
                $this->error("绿色通道价格必须为数字!");
            }
            $res = M('Concurrent')->where('id =1')->save($result);

            if ($res) {
             $this->success('操作成功');
            } else {
               $this->error('操作失败');
            }
        }
        else {
            $_REQUEST['tabHash'] = 'index';
            $this->pageKeyList = array('Concurrent_nums','onehprice','onemprice','threemprcie','sixmprice','oneyprice','blueconprice');
            $this->notEmpty   = array('Concurrent_nums','onehprice','onemprice','threemprcie','sixmprice','oneyprice','blueconprice');
            $num = M('Concurrent')->where('id =1')-> find() ;
            $this->savePostUrl = U('live/AdminConcurrent/index');
            $this->displayConfig($num);
        }
    }

}
