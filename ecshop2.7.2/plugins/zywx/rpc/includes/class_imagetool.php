<?php
class imagetool {

    var $type; //图片类型
	var $mime;
    var $width; //实际宽度                1200
    var $height; //实际高度               900
    var $resize_width;   //改变后的宽度   190
    var $resize_height; //改变后的高度    280
    var $srcimg; //源图象
    var $dstimg;   //目标图象地址
    var $im;  //临时创建的图象

    public function imagetool() {}
	
	//生成缩略图
    public function thumb($source, $target, $width, $height, $cut=0, $quality=75) {
		$this->srcimg = $source;
		$this ->dst_img($target); //目标图象地址
        $this->resize_width = $width;
        $this->resize_height = $height;
        $this->init(); //初始化图象
        //如果图片尺寸小于缩略图尺寸则返回
		//if( $this->resize_width > $this->width || $this->resize_height > $this->height) return;
        $resize_ratio = ($this->resize_width)/($this->resize_height); //改变后的图象的比例     0.67857142857143
        $ratio = ($this->width)/($this->height); //实际图象的比例     						  1.3333333333333
        if($cut) { //裁图
            if($ratio>=$resize_ratio) { //高度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width,$this->resize_height, (($this->height)*$resize_ratio), $this->height);
                imagejpeg($newimg, $this->dstimg, $quality);
            }
            if($ratio<$resize_ratio) { //宽度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, (($this->width)/$resize_ratio));
                imagejpeg($newimg, $this->dstimg, $quality);
            }
        }  else { //不裁图
//            if($ratio>=$resize_ratio) {
//                $newimg = imagecreatetruecolor($this->resize_width,($this->resize_width)/$ratio);
//                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width)/$ratio, $this->width, $this->height);
//                imagejpeg($newimg, $this->dstimg, $quality);
//            }
//            if($ratio<$resize_ratio) {
//                $newimg = imagecreatetruecolor(($this->resize_height)*$ratio,$this->resize_height);
//                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, ($this->resize_height)*$ratio, $this->resize_height, $this->width, $this->height);
//                imagejpeg($newimg, $this->dstimg, $quality);
//            }
			
			/*根据应用需求   强制返回指定尺寸图片*/
			$newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
			$background = imagecolorallocatealpha($newimg, 255, 255, 255, 127);
     		imagefill($newimg,0,0,$background);
			imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, $this->height);
			imagejpeg($newimg, $this->dstimg, $quality);
        }

		if(file_exists($this->srcimg)) {
			@unlink($this->srcimg);
		}
		
		ImageDestroy($this->im);
		return true;
    }
	
    //初始化图象
    public function init() {
		
		$this->type = strtolower(substr(strrchr($this->srcimg,"."),1)); //图片的类型
		error_reporting(E_ERROR);
		$imginfo = getimagesize($this->srcimg);
		if(empty($imginfo)) {
			$dest = tempnam(ROOT_PATH.'/upload/cache', 'tmpimg_');
			$data = $this->_dfsockopen($this->srcimg);
			file_put_contents($dest, $data);
			$this->srcimg = $dest;
			
			$imginfo = getimagesize($this->srcimg);
		}

		if(empty($imginfo)) return false;
		
		$this->mime = $imginfo['mime'];
		$this->width = $imginfo[0];
        $this->height = $imginfo[1];
		
		switch($this->mime) {
			case 'image/jpeg':
				$this->im = imagecreatefromjpeg($this->srcimg);
				break;
			case 'image/gif':
				 $this->im = imagecreatefromgif($this->srcimg);
				break;
			case 'image/png':
				$this->im = imagecreatefrompng($this->srcimg);
				break;
		}
    }
	
    //图象目标地址
    public function dst_img($dstpath) {
        $full_length  = strlen($this->srcimg);
        $type_length  = strlen($this->type);
        $name_length  = $full_length-$type_length;
        $name         = substr($this->srcimg,0,$name_length-1);
        $this->dstimg = $dstpath;
    }
    
	public function _dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
		$return = '';
		$matches = parse_url($url);
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
	
		if($post) {
			$out = "POST $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= 'Content-Length: '.strlen($post)."\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cache-Control: no-cache\r\n";
			$out .= "Cookie: $cookie\r\n\r\n";
			$out .= $post;
		} else {
			$out = "GET $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cookie: $cookie\r\n\r\n";
		}
	
		if(function_exists('fsockopen')) {
			$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
		} elseif(function_exists('pfsockopen')) {
			$fp = @pfsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
		} else {
			$fp = '';
		}
	
		if(!$fp) {
			return '';
		} else {
			stream_set_blocking($fp, $block);
			stream_set_timeout($fp, $timeout);
			@fwrite($fp, $out);
			$status = stream_get_meta_data($fp);
			if(!$status['timed_out']) {
				while (!feof($fp)) {
					if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
						break;
					}
				}
	
				$stop = false;
				while(!feof($fp) && !$stop) {
					$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
					$return .= $data;
					if($limit) {
						$limit -= strlen($data);
						$stop = $limit <= 0;
					}
				}
			}
			@fclose($fp);
			return $return;
		}
	}
}
?>