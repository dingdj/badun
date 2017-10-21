<?php
/**
 * 视频空间管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminStatisticsAction extends AdministratorAction
{
	/**
     * 视频空间管理
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();

    }

    /**
     * 系统信息 - 数据看板
     */
    public function showDataCount() {

        !$_GET['type'] && $_GET['type'] = 'today';
        if($_GET['type'] == 'today'){
            $time = strtotime('today');
            $where = " ctime >= '".$time."'";
        }
        $this->assign('type', t($_GET['type']));

        if(is_school($this->mid)){
            $mhm_id = is_school($this->mid);
        }
        $list = model('Online')->getDataCount($where,$mhm_id,$time);
        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 系统信息 - 学员活跃统计
     */
    public function studentActive() {

        !$_GET['type'] && $_GET['type'] = 'today';
        //获取今天日期
        switch($_GET['type']) {
            case 'today':
                $where = " ctime >= '".strtotime('today')."'";
                break;
            case 'week':
                //最新七天
                $where = 'ctime >= "'.strtotime('-1 week today').'"';
                break;
            case '30d':
                //最近30天
                $where = 'ctime >= "'.strtotime('-30 day today').'"';
                break;
            case 'month':
                //查询本月初到现在
                $where = 'ctime >= "'.mktime(0, 0 , 0,date("m"),1,date("Y")).'"';
                break;
            default:
                //默认查询当前时间之前的数据
                $where = " ctime <= ".time();
                break;
        }

        $this->assign('type', t($_GET['type']));

        if(!empty($_GET['start_ctime']) || !empty($_GET['end_ctime'])) {
            $where = '1';
            if(!empty($_GET['start_ctime'])) {
                $where .= ' AND ctime > "'.strtotime($_GET['start_ctime']).'"';
            }
            if(!empty($_GET['end_ctime'])){
                $where .= ' AND ctime < "'.strtotime($_GET['end_ctime']).'"';
            }
            $this->assign('type','');
        }
        if(is_school($this->mid)){
            $mhm_id = is_school($this->mid);
        }
        $dataCount = model('Online')->getStudentCount($where,$mhm_id);
        $this->assign('dataCount',$dataCount);
        $this->display();
    }

    /**
     * 系统信息 - 订单收益统计
     */
    public function vipOrder() {

        !$_GET['type'] && $_GET['type'] = 'today';
        $where = '';
        //获取今天日期
        switch($_GET['type']) {
            case 'today':
                $where = " ctime >= '".strtotime('today')."'";
                break;
            case 'week':
                //最新七天
                $where = 'ctime >= "'.strtotime('-1 week today').'"';
                break;
            case '30d':
                //最近30天
                $where = 'ctime >= "'.strtotime('-30 day today').'"';
                break;
            case 'month':
                //查询本月初到现在
                $where = 'ctime >= "'.mktime(0, 0 , 0,date("m"),1,date("Y")).'"';
                break;
            default:
                //默认查询当前时间之前的数据
                $where = " ctime <= ".time();
                break;
        }

        $this->assign('type', t($_GET['type']));

        if(!empty($_GET['start_ctime']) || !empty($_GET['end_ctime'])) {
            $where = '1';
            if(!empty($_GET['start_ctime'])) {
                $where .= ' AND ctime > "'.strtotime($_GET['start_ctime']).'"';
            }
            if(!empty($_GET['end_ctime'])){
                $where .= ' AND ctime < "'.strtotime($_GET['end_ctime']).'"';
            }
            $this->assign('type','');
        }
        if(is_school($this->mid)){
            $mhm_id = is_school($this->mid);
        }
        //获取的统计数据
        $dataCount = model('Online')->getVipCount($where,$mhm_id);
        $this->assign('dataCount',$dataCount['data']);
        $this->display();
    }

    /**
     * 系统信息 - 所有订单统计
     */
    public function allOrder() {

        !$_GET['type'] && $_GET['type'] = 'today';
        $where = '';
        //获取今天日期
        switch($_GET['type']) {
            case 'today':
                $where = " ctime >= '".strtotime('today')."'";
                break;
            case 'week':
                //最新七天
                $where = 'ctime >= "'.strtotime('-1 week today').'"';
                break;
            case '30d':
                //最近30天
                $where = 'ctime >= "'.strtotime('-30 day today').'"';
                break;
            case 'month':
                //查询本月初到现在
                $where = 'ctime >= "'.mktime(0, 0 , 0,date("m"),1,date("Y")).'"';
                break;
            default:
                //默认查询当前时间之前的数据
                $where = " ctime <= ".time();
                break;
        }

        $this->assign('type', t($_GET['type']));

        if(!empty($_GET['start_ctime']) || !empty($_GET['end_ctime'])) {
            $where = '1';
            if(!empty($_GET['start_ctime'])) {
                $where .= ' AND ctime > "'.strtotime($_GET['start_ctime']).'"';
            }
            if(!empty($_GET['end_ctime'])){
                $where .= ' AND ctime < "'.strtotime($_GET['end_ctime']).'"';
            }
            $this->assign('type','');
        }
        if(is_school($this->mid)){
            $mhm_id = is_school($this->mid);
        }
        //获取的统计数据
        $dataCount = model('Online')->getOrderCount($where,$mhm_id);
        $this->assign('dataCount',$dataCount['data']);
        $this->display();
    }
}