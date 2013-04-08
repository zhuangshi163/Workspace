<?php

/**
 * ECSHOP rpc Widget接口公共函数
 * 
 * $Author: lilixing $
 * $Id: lib_main.php 15013 2011-12-26 09:31:42Z lilixing $
 */
//搜索输出
function jsonExitSearch($data)
{
	if(is_array($data))
	{
		if(EC_CHARSET != 'utf-8')
		{
			$data = gbktoutf8($data);
		}
	}
	else
	{
		$data = gbktoutf8($data);
	}
	echo $_GET['jsoncallback'],"(".json_encode($data).")";
	exit;
}

//搜索转码
function search_utf8togbk($data) 
{
	if(is_array($data))
	{
		foreach($data as &$value)
		{
			$value = utf8togbk($value);
		}
	}
	else
	{
		if(EC_CHARSET != 'utf-8')
		{
			$data = iconv("utf-8", 'gbk',$data);
		}
		elseif(isgbk($data))
		{
			$data = iconv('gbk','utf-8',$data);
		}
		return $data;
	}
}
/**
 *@todo 接受参数utf-8  返回也是utf-8 
 *@param string or array $data	
 *@return 返回JSON数据，并推出
 *
 */
function jsonExit($data)
{
	
//	if(is_array($data))	{
//		//if(EC_CHARSET != 'utf-8') $data = gbktoutf8($data);
//		$data = utf8togbk($data);
//		$data = json_encode($data);
//	}else{
//		$data = utf8togbk($data);
//	}
//	echo $_GET['jsoncallback'],"($data)";
//	exit();
	if(is_array($data))
	{
		//if(EC_CHARSET != 'utf-8')
		$data = gbktoutf8($data);
		$data = json_encode($data);
	}
	else
	{
		$data = gbktoutf8($data);
	}
	echo $_GET['jsoncallback'],"($data)";
	exit;
	
}

/*
 * 功能：对字符串或数组进行utf8编码
 * 参数：字符串或数组
 * 返回值：编码后的字符串或数组
 */
function gbktoutf8($string) 
{
	if(is_array($string))
	{
		foreach($string as &$value) 
		{
			$value = gbktoutf8($value);
		}
	}
	else
	{
		if(isgbk($string) && strlen(utf8_decode($string)) > 1 )//&& !in_array(substr($string,0,1),range(0,9)) )
		{
			$string = iconv('gbk', "utf-8//IGNORE",$string);
		}
	}
	return $string;
}

/**
 *
 * @todo  对字符串或数组进行本地化编码
 * @param array or string  
 * @return string 
 *	
 */
function utf8togbk($data)
{
	if(is_array($data))
	{
		foreach($data as &$value)
		{
			$value = utf8togbk($value);
		}
	}
	else
	{
		//if(EC_CHARSET != 'utf-8'){
		if(!isgbk($data))
		{
			$data = iconv("utf-8", 'gbk',$data);
		}
	}
	return $data;
}

/*
 * 功能：判断字符串是否是gbk编码
 * 参数：字符串
 * 返回值：boolean
 */
function isgbk($string) 
{
	for($i=0; $i<strlen($string); $i++) 
	{
    	$v = ord( $string[$i] );
        if( $v > 127) 
		{
        	if( ($v >= 228) && ($v <= 233) ) 
			{
            	if( ($i+2) >= (strlen($string) - 1)) return true;
                $v1 = ord( $string[$i+1] );
                $v2 = ord( $string[$i+2] );
                if( ($v1 >= 128) && ($v1 <=191) && ($v2 >=128) && ($v2 <= 191) )
                	return false;
                else
                	return true;
            }
    	}
	}
	return true;
}

/**
 *
 *  语言包替换函数：将对应的字符串替换成汉语
 *
 */
function rpcLang($file,$key)
{
	if(EC_CHARSET == 'gbk')
	{
		if(file_exists('lang/lang_gbk_'.$file))
		{
			include('lang/lang_gbk_'.$file);
		}	
	}
	else
	{
		if(file_exists('lang/lang_'.$file))
		{
			include('lang/lang_'.$file);
		}	
	}
		
	return empty($lang[$key]) ? '' : $lang[$key];
}

function utf8rpcLang($file,$key)
{
	if(file_exists('lang/lang_gbk_'.$file))
	{
		include('lang/lang_gbk_'.$file);
	}	
	return empty($lang[$key]) ? '' : $lang[$key];
}

/**
 *
 * 检测用户是否等录
 *
 */
function checkLogin()
{
	if(empty($_SESSION['user_id']))
	{
		$msg = rpcLang('user.php', 'nologin');
		jsonExit("{\"status\":\"$msg\"}");
	}
}
