<?php
$key="edu^line"; 
$iv="edu^line";
if($myurl<>"") {
	$encrypt=$myurl;
	}else {
	$encrypt="http://demo.cuplayer.com/file/test.mp4";
}
$tb=mcrypt_module_open(MCRYPT_3DES,'','cbc',''); 
mcrypt_generic_init($tb,$key,$iv); 
$encrypt=PaddingPKCS7($encrypt);
$cipher=mcrypt_generic($tb,$encrypt); 
$cipher=base64_encode($cipher);
mcrypt_generic_deinit($tb); 
mcrypt_module_close($tb); 

function PaddingPKCS7 ($data)
{
    $block_size = mcrypt_get_block_size(MCRYPT_3DES, 'cbc');
    $padding_char = $block_size - (strlen($data) % $block_size);  
    $data .= str_repeat(chr($padding_char), $padding_char); 
    return $data;
}
$mycipher = $cipher;
?>