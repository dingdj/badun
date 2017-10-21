<?php
class OfficeModel extends Model {
	protected $ext = [
		'wp','sword','excel','ppt','png','jpg','gif','bmp','xls','xlsx','doc','docx','pptx','txt'
	];
	//将文档转换为pdf
	public function transfer_office() {
		$map = ['turn_pdf'=>0,'is_del'=>0,'type'=>4];
		$list = M('zy_video_data')->where($map)->order('ctime desc')->findPage();
		if($list['data']){

			set_time_limit(0);
			foreach ($list['data'] as $k => $v) {
				// 转码非pdf
				$extension = substr(strrchr($v['video_address'], '.'), 1);
				if(in_array($extension,$this->ext)){
					$path = parse_url($v['video_address']);
					$file = SITE_PATH.$path['path'];
					$turnFileName = strstr($file,'.',true).'.pdf';
					// 待转码文件存在
					if(file_exists($file)){
						if(!file_exists($turnFileName)){
							$command = 'PATH=$PATH unoconv -l -f pdf '.$file.'> /dev/null &';
							exec($command);
						}
						// 转码后的文件
						//if(file_exists($turnFileName)){
						M('zy_video_data')->where('id='.$v['id'])->save(['turn_pdf'=>1]);
						//}else{
						//D('Resource','classroom')->where('resource_id='.$v['resource_id'])->save(['is_turnpdf'=>2]);
						//}
					}else{
						M('zy_video_data')->where('id='.$v['id'])->save(['turn_pdf'=>2]);
					}

				}else{
					M('zy_video_data')->where('id='.$v['id'])->save(['turn_pdf'=>1]);
				}


			}
		}
	}
}