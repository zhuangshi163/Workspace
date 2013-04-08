<?php
/**
 * 图片压缩处理类
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2012-3-27 下午07:19:32
 * @author   mb  mengbing880814@yahoo.com.cn
 * 
 */
class thumbAction 
{
	/**
	 * icon固定尺寸
	 *
	 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
	 * @version    $Id: \$
	 * @since      File available since Release 1.0 -- 2012-4-1 下午12:02:19
	 * @author   mb  mengbing880814@yahoo.com.cn
	 */
	private $iconSize = array("57*57", "72*72", "114*114");
	
	public function thumb($width,$height,$src,$icon=''){
		//global $width,$height,$src,$icon;
		$width = max(intval($width), 57);
		$height = max(intval($height), 57);
		$src = str_replace('\\', '', stripslashes($src));
		$ext = addslashes(strtolower(substr(strrchr($src, '.'), 1, 10)));
		$name = substr(strrchr(substr($src, 0, strrpos($src, '.')), '/'), 1);
		$name = $name.$width.'_'.$height.'.'.$ext;
		
		$imgInfo = getimagesize($src);
		
		if($imgInfo && $icon){
			$imgSize = $imgInfo[0]."*".$imgInfo[1];
			foreach($this->iconSize as $size){
				if($imgSize == $size){
					header('location: '.$src);
					return;
				}
			}
		}
		//创建目录
		if(!is_dir(RPC_ROOT.'/upload')){
			@mkdir(RPC_ROOT.'/upload', 0700);
		}
		
		//随机清空图片缓存目录
		if (mt_rand(0, 1000) == 20) {
			$this -> clean_attach_cachedir(RPC_ROOT.'/upload');
		}
		
		$thumbfile = 'upload/'.$name;
		if(file_exists($thumbfile)) {
			header('location: '.$thumbfile);
		}
		include_once ('includes/class_imagetool.php');
		$img = new imagetool();
		
		$dest_file = RPC_ROOT.'/'.$thumbfile;
		
		if($img->thumb($src, $dest_file, $width, $height)) {
			header('location: '.$thumbfile);
		} else {
			if($img->mime) {
				header('location: '.$src);exit;
			} else {
				header('location: '.ROOT_PATH.'/images/no_picture.gif');exit;
			}
		}
		
	}
	
	public function clean_attach_cachedir($dir) {
		if (is_dir($dir)) {
			$dh=opendir($dir);
			while (false !== ( $file = readdir ($dh))) {
				if($file!="." && $file!="..") { 
					$fullpath=$dir."/".$file;
					if(!is_dir($fullpath)) {
						@unlink($fullpath);
					} else {
						$this -> clean_attach_cachedir($fullpath);
					}
				}  
			}
			closedir($dh);
		}
	}
}
?>