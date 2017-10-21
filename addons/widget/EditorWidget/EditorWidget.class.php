<?php
/**
 * 编辑器渲染控制器.
 *
 * @example W('Editor',array('width'=>'80%','height'=>'200','contentName'=>'mycontent','value'=>'默认的值'),'type'=>'default','maximumWords'=>'允许输入最大字数')
 *
 * @version 2.0
 */
class EditorWidget extends Widget
{
    /**
     * 富文本编辑器渲染方法.
     *
     * @author martinsun <syh@sunyonghong.com>
     * @datetime 2017-03-29T15:23:36+080
     *
     * @version  2.0
     *
     * @param string $data['contentName']  编辑器的名称,即表单内的name名称
     * @param string $data['width']        编辑器渲染显示的宽度,可以是百分比或像素
     * @param string $data['height']       编辑器渲染显示的高度,可以是百分比或像素
     * @param int    $data['maximumWords'] 编辑器右下角的字数统计 0表示不统计
     * @param string $data['type']         编辑器的类型
     *                                     可以是: default 默认
     *                                     default_and_formula 默认和公式
     *                                     only_formula 只有公式
     *
     * @return string 渲染的编辑器页面
     */
    public function render($data)
    {
        $var = array();
        $var['contentName'] = 'content';
        $var['width'] = '99%';
        $var['height'] = '200px';
        $var['type'] = 'default_and_formula';
        $var['maximumWords'] = 0;

        !empty($data) && $var = array_merge($var, $data);
        $var['barList'] = $this->getBarList($var['type']);
        // 大小格式处理
        $var['width'] = is_numeric($var['width']) ? $var['width'].'px' : $var['width'];
        $var['height'] = is_numeric($var['height']) ? $var['height'].'px' : $var['height'];
        $content = $this->renderFile(dirname(__file__).'/default.html', $var);
        unset($var, $data);
        // 输出数据
        return $content;
    }

    /**
     * 获取编辑器的控制栏.
     *
     * @author martinsun <syh@sunyonghong.com>
     * @datetime 2017-03-29T15:28:26+080
     *
     * @version  2.0
     *
     * @param string $type 编辑器的类型
     *                     可以是: default 默认
     *                     default_and_formula 默认和公式
     *                     only_formula 只有公式
     *
     * @return array 包含的编辑器控制栏的数据
     */
    public function getBarList($type = 'default')
    {
        $bars = [
            'fullscreen', 'source', '|',
            'undo', 'redo', '|',
            'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
            'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
            'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
            'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
            'directionalityltr', 'directionalityrtl', 'indent', '|',
            'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
            'touppercase', 'tolowercase', '|',
            'link', 'unlink', 'anchor', '|', 
            'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
            'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'gmap', 'insertframe', 'insertcode', 'pagebreak', 'template', 'background', '|',
            'horizontal', 'date', 'time', 'spechars', 'snapscreen', 'wordimage', '|',
            'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|',
            'print', 'preview', 'searchreplace', 'drafts', 'help',
        ];
        //webapp 暂时不用
        switch ($type) {
            case 'full':
            case 'default_and_formula':
                $bars[] = 'kityformula';// 公式插件
                break;
            case 'only_formula':
                $bars = [
                     'fullscreen', 'source', '|',
                     'bold', 'italic', 'underline', '|', 'fontsize', '|', 'kityformula', 'preview',
                ];
                break;
            case 'simple':
                $bars = [
                    'undo','redo','|',
                    'bold','italic','underline','fontborder','strikethrough','removeformat','|',
                    'forecolor', 'backcolor','fontfamily', 'fontsize','justifyleft', 'justifycenter', 'justifyright', 'justifyjustify','|',
                    'simpleupload','insertimage','emotion'
                ];
                break;
            case 'more':
                $bars = [
                    'source', '|',
                    'undo', 'redo', '|',
                    'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
                    'forecolor', 'backcolor','fontfamily', 'fontsize','justifyleft', 'indent','justifycenter', 'justifyright', 'justifyjustify','|',
                    'link', 'unlink','|',
                    'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
                    'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map',
                    'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|',
                    'print', 'preview','kityformula'
                ];
                break;
            default:
                break;

        }
        // 是否显示公式插件
        if (in_array('kityformula', $bars)) {
            $showformula = 1;
        } else {
            $showformula = 0;
        }
        $barList = ['bars' => json_encode([$bars]), 'showformula' => $showformula];

        return $barList;
    }
}
