<?php
/**
 * appcan手机应用升级
 */
class UpgradeAction
{
	public function index ()
	{
		$this->widget_upgrade();
	}
	//widget 升级
	public function widget_upgrade ()
	{
		$version   = $_GET['ver'];
		$platform = $_GET['platform'];
		//echo $version;die;
		if(empty($version) || !isset($platform)) 
		{
			exit;
		}
		
		$widget_upgrade_file = dirname(RPC_ROOT).DIRECTORY_SEPARATOR.'widget.xml';
		if(file_exists($widget_upgrade_file))
		{

			$c = file_get_contents($widget_upgrade_file);
			preg_match('/<version>(.*?)<\/version>/', $c, $matches);
			$new_ver = $matches[1];
			preg_match('/<iphone_filename>(.*?)<\/iphone_filename>/', $c, $matches);
			$iphone_name = $matches[1];
			preg_match('/<android_filename>(.*?)<\/android_filename>/', $c, $matches);
			$android_name = $matches[1];

			/*$data = unserialize(file_get_contents($widget_upgrade_file));　　
			$new_ver = $data['app_version'];
			$iphone_name  = $data['iphone_name'];
			$android_name = $data['android_name'];*/
			if($new_ver > $version)
			{
				if($platform == '0')//iphone
				{ 
					empty($iphone_name) && exit;
					$fileurl = $iphone_name;
				}
				elseif($platform == '1')//android
				{ 
					empty($android_name) && exit;
					$fileurl = $android_name;
				}

				if(preg_match('/^http:\/\//', $fileurl)) 
				{
					$dest =tempnam(ROOT_PATH.'plugins/zywx/temp/', 'tmp_');
					$ch = curl_init($fileurl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
					$output = curl_exec($ch);

					$fh = fopen($dest, 'w');
					fwrite($fh, $output);
					fclose($fh);

					$filesize = filesize($dest);
					if(file_exists($dest)) 
					{
						@unlink($dest);
					}
				} 
				else
				{
					$filesize = filesize(ROOT_PATH.'plugins/zywx/upload/'.$fileurl);
					if(file_exists('isTest'))
					{
						$site_url = 'http://192.168.1.124/work/ec_gbk26';
					}
					else
					{
						global $ecs;
						$site_url = $ecs->get_domain();
					}
					$fileurl = $site_url.'/plugins/zywx/upload/'.$fileurl;
				}

				if(empty($filesize)) exit;
				
				$xml  ='<?xml version="1.0" encoding="utf-8" ?>';
				$xml .='<results>';
				$xml .='<updateFileName>ecshop</updateFileName>';
				$xml .='<updateFileUrl>'.$fileurl.'</updateFileUrl>';
				$xml .='<fileSize>'.$filesize.'</fileSize>';
				$xml .='<version>'.$new_ver.'</version>';
				$xml .='</results>';

				echo $xml;
			}
			exit;
		}
		exit;
	}
}