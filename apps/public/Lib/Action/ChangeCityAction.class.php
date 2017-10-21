<?php

/**
 * 首页控制器
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class ChangeCityAction extends Action
{

    /**
     * 切换城市页面
     * @return void
     */
    public function index()
    {
        $province = model('Area')->where('pid=0')->field('area_id,title')->select();
        $this->assign('province',$province);

        $str = '';
        $pNetwork = model('Area')->where(['pid' => ['neq', 0]])->field('area_id,title,pid')->select();
        foreach ($pNetwork as $key => $val) {
            $er_pNetwork = model('Area')->where(['area_id' => $val['pid']])->field('area_id,title,pid')->select();
            foreach ($er_pNetwork as $k => $v) {
                if ($v['pid'] == 0) {
                    $str .= ',' . $val['area_id'];
                }
            }
        }
        $pNetwork = model('Area')->where(['area_id' => ['in', trim($str, ',')]])->field('area_id,title,pid')->select();
        foreach ($pNetwork as $key => $val) {
            //$pNetwork[$key]['id'] = $val['area_id'];
            if ($val['title'] == '市辖区' || $val['title'] == '市' || $val['title'] == '县' || $val['title'] == '区') {
                $pNetwork[$key]['title'] = M('area')->where(array('area_id' => $val['pid']))->getField('title');
            }
            if ($val['title'] == '省直辖行政单位' || $val['title'] == '省直辖县级行政单位') {
                unset($pNetwork[$key]);
            }
        }
        $arr = array_column($pNetwork, 'title', 'area_id');
        $newArray = array_unique($arr);
        $visitRegion = [];
        foreach ($newArray as $k => $v) {
            $visitRegion[$k]['city_id'] = $k;
            $visitRegion[$k]['city_name'] = $v;
            $visitRegion[$k]['letter'] = $this->getFirstChar($v);
        }

		$charArray = $this->get_all_city($visitRegion);
		$this->assign('charArray', $charArray);
		$this->display();
	}

    //获取城市子集
    public function getSubCategory(){
        $pid = $_POST['pid'];
        $list = model('Area')->where('pid='.(int)$pid)->field('area_id,title')->select();
        if($list){
            $res = [
                'status'=>1,
                'data' => $list
            ];
        }else{
            $res = [
                'status'=>0,
                'message' => '暂无子分类'
            ];
        }
        echo json_encode($res);exit;
    }
    //根据首字母排序
    public function getFirstChar($s)
    {
        $s0 = mb_substr($s, 0, 1, 'utf-8');
        $s = iconv('UTF-8', 'gb2312', $s0);       //将UTF-8转换成GB2312编码
        if (ord($s0) > 128) {//汉字开头，汉字没有以U、V开头的
            $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
            if ($asc >= -20319 and $asc <= -20284) return "A";
            if ($asc >= -20283 and $asc <= -19776) return "B";
            if ($asc >= -19775 and $asc <= -19219) return "C";
            if ($asc >= -19218 and $asc <= -18711) return "D";
            if ($asc >= -18710 and $asc <= -18527) return "E";
            if ($asc >= -18526 and $asc <= -18240) return "F";
            if ($asc >= -18239 and $asc <= -17760) return "G";
            if ($asc >= -17759 and $asc <= -17248) return "H";
            if ($asc >= -17247 and $asc <= -17418) return "I";
            if ($asc >= -17417 and $asc <= -16475) return "J";
            if ($asc >= -16474 and $asc <= -16213) return "K";
            if ($asc >= -16212 and $asc <= -15641) return "L";
            if ($asc >= -15640 and $asc <= -15166) return "M";
            if ($asc >= -15165 and $asc <= -14923) return "N";
            if ($asc >= -14922 and $asc <= -14915) return "O";
            if ($asc >= -14914 and $asc <= -14631) return "P";
            if ($asc >= -14630 and $asc <= -14150) return "Q";
            if ($asc >= -14149 and $asc <= -14091) return "R";
            if ($asc >= -14090 and $asc <= -13319) return "S";
            if ($asc >= -13318 and $asc <= -12839) return "T";
            if ($asc >= -12838 and $asc <= -12557) return "W";
            if ($asc >= -12556 and $asc <= -11848) return "X";
            if ($asc >= -11847 and $asc <= -11056) return "Y";
            if ($asc >= -11055 and $asc <= -10247) return "Z";
            if ($s0 == '亳') return "B";
            if ($s0 == '泸' || $s0 == '漯') return "L";
            if ($s0 == '濮') return "P";
            if ($s0 == '衢') return "Q";
        } else if (ord($s) >= 65 and ord($s) <= 90) { //大写英文开头
            return substr($s, 0, 1);
        }
    }

    //获取所有城市,整排
    public function get_all_city($city)
    {
        if (is_array($city)) {
            for ($i = A; $i <= Z; $i++) {
                foreach ($city as $k => $vo) {
                    if ($i == $vo['letter']) {
                        $array[$i][$k] = $vo;
                    }
                }

                if ($i == Z) {
                    break;
                }
            }
        }
        return $array;
    }
}