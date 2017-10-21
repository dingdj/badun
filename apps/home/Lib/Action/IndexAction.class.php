<?php
/**
 * 公共控制器
 * @version CY1.0
 */
class IndexAction extends Action {
	
	//app下载方法
	public function appDownload(){
		$download_url = model('Xdata')->get('admin_Config:appConfig');
		$file         = $download_url['download_url'];
        redirect($file);
		// $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        // if (strripos($agent, 'iphone') || strripos($agent, 'ipad')) {
        //     if (strpos($agent, 'micromessenger')) {
        //         redirect($file_ios);
        //     } else {
        //         $type = 'ios';
        //         redirect($file_ios);
        //     }
        // } else {
        //     redirect($file_android);
        // }
	}
}