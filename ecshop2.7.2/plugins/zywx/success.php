<?php
	define('IN_ECS',true);
	require(dirname(__FILE__).'/includes/init.php');
	$domain = str_replace('/plugins/zywx/','',$ecs->url());
	$zywxdata  =  json_decode(file_get_contents('proxyserver.conf'),true);
	 if(!isset($zywxdata['zywxid']))
	 {
		exit('no authcode');
	 }
	$authcode = $zywxdata['zywxid'];
	$url =ZYWX_PROXY."/index.php?m=curl&a=loginReport&domain=".$domain."&authcode=".$authcode;
	//echo "<script>parent.loginStatusCallback('success');</script>";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="robots" content="noindex, nofollow">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript">
			var url = "<?php echo $url?>";
			$.getJSON(url+'&callback=?',function(data){
				if(data.status == 1)
				{
					parent.show_made_app(data.zywxemail);
				}else
				{
					alert(data.msg);
				}
			});
		</script>
	</head>
	<body>
	</body>
</html>