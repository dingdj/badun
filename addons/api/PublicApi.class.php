<?php
/**
 * 公开api接口
 **/
class PublicApi extends Api
{

    /**
     * 按照层级获取地区列表
     *
     * @request int     $pid     地区ID
     * @param  bool  $notsort 是否不排序，默认排序
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getArea()
    {
        $pid = intval($this->data['pid']);
        $pid or
        $pid = 0;

        isset($this->data['notsort']) or
        $notsort = false;
        $notsort = (boolean) $this->data['notsort'];

        $list = model('Area')->getAreaList($pid);

        if ($notsort) {
            return $list;
        }

        $areas = array();
        foreach ($list as $area) {
            $pre = getFirstLetter($area['title'], 'utf-8', '#');

            /* 多音字处理 */
            if ($area['title'] == '重庆') {
                $pre = 'C';
            }

            if (!isset($areas[$pre]) or !is_array($areas[$pre])) {
                $areas[$pre] = array();
            }
            array_push($areas[$pre], $area);
        }
        ksort($areas);

        $areas ? $this->exitJson($areas , 1) : $this->exitJson( array() , 0);
    }

    /**
     * 获取application幻灯数据
     *
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getSlideShow()
    {
        $list = D('application_slide')->field('`title`, `image`, `type`, `data`')->select();

        foreach ($list as $key => $value) {
            $value['image'] = getImageUrlByAttachId($value['image']);
            $list[$key] = $value;
        }

        $list ? $this->exitJson($list , 1) : $this->exitJson( array() , 0);
    }

    /**
     * 获取关于我们HTML信息
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function showAbout()
    {
        ob_end_clean();
        ob_start();
        header('Content-Type:text/html;charset=utf-8');
        echo '<!DOCTYPE html>',
             '<html lang="zh">',
                '<head><title>关于我们</title></head>',
                '<body>',
                json_decode(json_encode(model('Xdata')->get('admin_Application:about')), false)->about,
                '</body>',
             '</html>';
        ob_end_flush();
        exit;
    }
} // END class PublicApi extends Api
