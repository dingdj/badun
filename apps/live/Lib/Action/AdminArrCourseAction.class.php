
<?php
/**
 * Created by Ashang.
 * 云课堂机构展示控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class AdminArrCourseAction extends CommonAction
{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 排课首页显示方法
     */
    public function index()
    {

        $this->display();

    }


    public function data()
    {
        $start = t($_GET['start']);
        $end = t($_GET['end']);
        $nums = M('concurrent')->where('id = 1')->getField('Concurrent_nums');
        if (!empty ($start && $end)) {
            $map ['start'] = array('BETWEEN', array($start, $end));
        }
        $map['is_activity'] = 1;
        $map['is_del'] = 0;
        $serth = array('start','maxmannums');
        $res = M('arrange_course')->where($map)->field($serth)->select();
        if(!$res)
        {
            $res =array();
        }
        $times = intval(($end-$start)/3600);
        for ($i = 0; $i < $times; $i++)
        {
            $timedada[$i]['start'] =$start+$i*3600;
            $timedada[$i]['maxmannums'] = 0;
        }
        $res = array_merge($timedada,$res);
        $j = 0;
        foreach ($res as $k => $val) {
            $tdata[$j]['title'] = $res[$j]['maxmannums'];
            $tdata[$j]['start'] = $res[$j]['start'];
            for($i =$j+1 ;$i<count($res);$i++)
            {
                if ($val['start'] == $res[$i]['start']) {
                    $tdata[$j]['title'] = $tdata[$j]['title'] + $res[$i]['maxmannums'];
                    $res[$i]['maxmannums'] = -1000;
                }
            }
            $j++;
        }
        $data = array();
        $i=0;
        foreach($tdata as $k=>$val)
        {
            if($val['title'] < 0)
            {
                unset($tdata[$k]);
            }
            if($val['title'] >=0 && $i<168)
            {
                $data[$i]['title'] = strval($nums - $val['title'])."/".$nums;
                $data[$i]['start'] = strval($val['start']);
                $data[$i]['end'] = strval($val['start']+3600);
                $data[$i]['allDay'] = false;
                $i++;
            }
        }

        echo json_encode($data);

    }
}
?>
















