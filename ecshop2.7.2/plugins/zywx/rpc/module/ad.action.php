<?php
/**
 * 首页接口
 */
include_once ('includes/thumbAction.php');
class AdAction {
	public function index () {
		$this->adList();
	}
	//广告列表 广告类型,0图片
	public function adList () {
		$flashdb = array();
	    if (file_exists(ROOT_PATH .'data/flash_data.xml'))
	    {
	
	        // 兼容v2.7.0及以前版本
	        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . 'data/flash_data.xml'), $t, PREG_SET_ORDER))
	        {
	            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . 'data/flash_data.xml'), $t, PREG_SET_ORDER);
	        }
	       
	        if (!empty($t))
	        {
	            foreach ($t as $key => $val)
	            {
	                $val[4] = isset($val[4]) ? $val[4] : 0;
	                $flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
	            }
	        }
	
	    } else {
	    	
	        // 兼容v2.7.0及以前版本
	        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"/', file_get_contents(ROOT_PATH . 'data/cycle_image.xml'), $t, PREG_SET_ORDER))
	        {
	            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"/', file_get_contents(ROOT_PATH . 'data/cycle_image.xml'), $t, PREG_SET_ORDER);
	        }
	       
	        if (!empty($t))
	        {
	            foreach ($t as $key => $val)
	            {
	                $val[4] = isset($val[4]) ? $val[4] : 0;
	                $flashdb[] = array('src'=>$val[1],'url'=>$val[2]);
	            }
	        }
	    	
	    }
	    jsonExit($flashdb); 
	}
	
	//生成缩略图
	public function imagetool () {
	    $width = isset($_GET['width']) ? intval($_GET['width']) : '100';
	    $height = isset($_GET['height']) ? intval($_GET['height']) : '100';	    
		$src = isset($_GET['url']) ? trim($_GET['url']) : "" ;
	    $imgtool = new thumbAction ();
	    $img = $imgtool->thumb($width,$height,$src);
	    var_dump($img);
	   
	}
	

}