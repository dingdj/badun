<?php
/**
 * excel模型 - 数据对象模型
 * @author jason <syh@sunyonghong.com>
 */
use PHPExcel_IOFactory;
use PHPExcel;
class ExcelModel extends Model {

	/**
     * 导出excel
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-06-08
     * @param  string $xlsName 文件名
     * @param  array $xlsCell 列表数据配置
     * @param  array $xlsData 列表数据
     */
    public static function exportExcel($expTitle, $expCellName, $expTableData,$outinput = true,$dirConfig = [])
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
        $fileName = date('YmdHis'); //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum  = count($expCellName);
        $dataNum  = count($expTableData);

        //加载phpexcel
        //import('PHPExcel', VENDOR_PATH . 'PHPExcel' . DS);

        $objPHPExcel = new PHPExcel();
        $cellName    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getProperties(0)->setCreator(getUserName($_SESSION['mid'])); //设置创建者
        $objPHPExcel->getProperties(0)->setTitle($expTitle); //设置标题
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
        $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //居中
        $objPHPExcel->getActiveSheet(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //居中
        $objPHPExcel->getActiveSheet(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT); //左对齐
        $objPHPExcel->getActiveSheet(0)->getStyle('2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //居中
        $objPHPExcel->getActiveSheet(0)->getStyle('2')->getFont()->setBold(true); //标题栏加粗
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '【' . $expTitle . '】导出时间:' . date('Y-m-d H:i:s'));
        $path = isset($dirConfig['path']) ? $dirConfig['path'] : '';
        $filepath = self::mkExceldir('excel'.'/'.$path);
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        for ($i = 0; $i < $dataNum; $i++) {
            $data = $expTableData[$i];
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $data[$expCellName[$j][0]]);

            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if($outinput === true){
            header('pragma:public');
            header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
            header("Content-Disposition:attachment;filename=".$fileName.'.xlsx'); //attachment新窗口打印inline本窗口打印
            header('Cache-Control: max-age=0');//禁止缓存
            $objWriter->save("php://output");
            exit;
        }
        $filepath = $filepath.'/'.iconv("UTF-8", "GB2312//IGNORE", $fileName) .'.xlsx';
        $objWriter->save($filepath);
        if (!file_exists($filepath)) {
            return false;
        } 
        return iconv('UTF-8','GB2312//IGNORE','excel'.'/'.$path.'/'.$fileName).'.xlsx';
        
    }
    /**
     * 导出excel
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-06-08
     * @param  string $xlsName 文件名
     * @param  array $xlsCell 列表数据配置
     * @param  array $xlsData 列表数据
     */
    public static function export($xlsName, $xlsCell, $xlsData,$outinput = true,$dirConfig = [])
    {
        return self::exportExcel($xlsName, $xlsCell, $xlsData,$outinput,$dirConfig);
    }

    /**
     * 创建excel导出保存目录
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  string $path [description]
     * @return [type] [description]
     */
    public static function mkExceldir($path = ''){
        if($path && !is_dir($path)){
            @mkdir(DATA_PATH.$path,0777,true);
            @chmod(DATA_PATH.$path,0777);
        }
        return DATA_PATH.$path;
    }
    /**
     * 导入excel
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  string $value [description]
     * @return [type] [description]
     */
    public static function import($file_path = '')
    {
        $excel = PHPExcel_IOFactory::load($file_path);
        $sheet = $excel->getActiveSheet(0);
        return $sheet->toArray();
    }
}
