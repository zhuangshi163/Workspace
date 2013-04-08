<?php

	define('IN_ECS',true);
	define('NO_LOGIN',true);
	require(dirname(__FILE__).'/includes/init.php');

	$proxy = json_decode(zywx_load_cache('proxyserver.conf'),true);
	$appkey = $proxy['zywxappkey'];

	if(empty($appkey))
	{
		exit('The Appcan-Ec Plugins has no appkey , Please Check First!');
	}

	$appid = zywx_load_cache();
	if(!$appid)
	{
		$i = file_get_contents(ZYWX_APPCAN."/plugin/getDownload.action?app_key=$appkey&pt=iPhone");
		$i = explode('|', $i);	
		preg_match('/\/(\d+)\//s', $i[1], $match);
		if($match[1]) 
		{
			$appid = $match[1];
			zywx_save_cache('cache.dat', $appid);
		}
	}

	$wap_down_url = "http://app.tx100.com/$appid/index.html";
	$two_code_url = ZYWX_PROXY.'/index.php?m=qrcode&d='.$wap_down_url.'&e=H&s=6';
	$two_dimensional_code_url=grap_Image($two_code_url);
	$iphone_download_url	= download_package('iPhone');
	$android_download_url	= download_package('Android');
	$smarty->assign('wap_down_url',$wap_down_url);
	$smarty->assign('two_dimensional_code_url',$two_dimensional_code_url);
	$smarty->assign('iphone_download_url',$iphone_download_url);
	$smarty->assign('android_download_url',$android_download_url);
	$smarty->display('client.html');


function zywx_load_cache($cache_file = 'cache.dat') 
{
	if(file_exists($cache_file))
	{
		return file_get_contents($cache_file);
	}
	else
	{
		return '';
	}
}

function zywx_save_cache($cache_file = 'cache.dat',$data) 
{
	file_put_contents($cache_file,$data);
}

function download_package($os = 'iPhone')
{
	global $appkey;

	$data = file_get_contents(ZYWX_APPCAN."/plugin/getDownload.action?app_key=$appkey&pt=$os");
	$data = explode('|', $data);
	return empty($data[0]) ? '': $data[0];
}

//将远程图片读取到本地，并保存
function grap_Image($url) 
{ 
	if($url=="")	return false;
	$filename = md5($url) . '.jpg';
	if(file_exists($filename))
	{
		return $filename;
	}

	ob_start(); 
	readfile($url); 
	$img = ob_get_contents(); 
	ob_end_clean(); 
	$size = strlen($img); 
	$fp2=@fopen($filename, "a"); 
	fwrite($fp2,$img); 
	fclose($fp2); 
	return $filename; 
}