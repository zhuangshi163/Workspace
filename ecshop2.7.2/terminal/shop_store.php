<?php
// Adam制作 今日特价
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');
if ((DEBUG_MODE & 2) != 2) $smarty->caching = true;

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

$act = !empty($_GET['act']) ? trim($_GET['act']) : '';
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
if($act=='login'){
	$smarty->display('shop_store_login.html');
	die();
	
}elseif($act=='login_submit'){	
	$store_name = !empty($_REQUEST['store_name']) ? trim($_REQUEST['store_name']) : '';
	$password = !empty($_REQUEST['password']) ? trim($_REQUEST['password']) : '';
	if($store_name=='' || !preg_match("/^[a-zA-Z0-9]+$/",$store_name)) show_msg('请输入正确的用户名。','返回登录');
	
	$store = $db->getRow('SELECT * FROM ' . $ecs->table('store') . " WHERE store_name='$store_name'");
	if(empty($store)) show_msg('用户名输入有误，请重新输入。','返回登录');
	if(md5($password)!=$store['password']) show_msg('密码输入有误，请重新输入。','返回登录');
	if($store['restraint']==1) show_msg('此店家已被限制使用，如有问题请联系网站管理员。','返回登录');
	
	$ip = real_ip();
	$time_now = time();
	$db->query("UPDATE " . $ecs->table('store') . " SET visit_count = visit_count+1,time_last='$time_now',ip_last ='$ip' WHERE store_id = ".$store['store_id']);
	$_SESSION['store']['store_id'] = $store['store_id'];
	$_SESSION['store']['store_name'] = $store['store_name'];
	$_SESSION['store']['site_store_cat'] = $store['store_cat'];
	//$_SESSION['store']['store_id'] = $store['store_id'];
	//setcookie('site_store_id', $store['store_id'],$time_now+31536000);
	//show_msg('店家登录成功。',array('店家后台','商品导购','购物车'),array('shop_store.php','shop_category.php','shop_cart.php'));
	//ecs_header("Location: shop_convenient.php\n");
	header("Location: shop_convenient.php");
	die;
	exit();
}
checkStoreLogin();	//判断店家是否已经登录
if($act=='order_info'){
	$order_id = !empty($_GET['order_id']) && intval($_GET['order_id'])>0 ? intval($_GET['order_id']) : 0;
    $order = $db->getRow("SELECT *,(" . order_amount_field() . ") AS total_fee,(" . order_due_field() . ") AS aaa  FROM " . $ecs->table('order_info') ." WHERE order_id= '$order_id' and store_id=".$_SESSION['store']['store_id']);
	if(empty($order)){
		ecs_header("Location: shop_store.php?act=order\n");
		exit;
	}
	$order['total_fee'] = number_format($order['total_fee'], 2, '.', '');
	$order['handler'] = $order['shipping_status']==SS_SHIPPED && $order['pay_status']==PS_UNPAYED ? true : false;
	
	if($order['handler']==true && !empty($_GET['handler_check'])){
		if(!empty($_GET['handler']) && intval($_GET['handler'])==1){
			$db->query("UPDATE " . $ecs->table('order_info') . " SET shipping_status=".SS_RECEIVED.",pay_status=".PS_PAYED.",pay_time=".time().",order_amount=0,money_paid=".order_due_field()." WHERE order_id= '$order_id'");
			$txt = '操作成功，此订单（'.$order['order_sn'].'）已设置为已收款，已收货。';
		}else $txt = '操作失败，请先在确认提交按钮前打勾。';
		show_msg($txt,'返回订单详情页','shop_store.php?act=order_info&order_id='.$order_id);
	}
	
	if($order['clerk_id']>0){
		$clerk = $db->getRow('SELECT * FROM '.$ecs->table('store_clerk').' WHERE clerk_id='.$order['clerk_id']);
		$order['clerk'] = $clerk['clerk_remark'].'('.$order['clerk_id'].')';
	}else $order['clerk'] = '- -';
	
	$order['short_order_time'] = local_date('Y-m-d H:i', $order['add_time']);
	$order['short_pay_time'] = $order['pay_time']>0 ? local_date('Y-m-d H:i', $order['pay_time']) : '未付款';
	$order['short_shipping_time'] = $order['shipping_time']>0 ? local_date('Y-m-d H:i', $order['shipping_time']) : '未发货';
	
	$smarty->assign('order',      $order);

    $goods_list = array();
	$wholesale_total = 0;
    $sql = "SELECT o.*,g.goods_specification,g.wholesale_price, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn
            FROM " . $ecs->table('order_goods') . " AS o
                LEFT JOIN " . $ecs->table('products') . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $ecs->table('goods') . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $ecs->table('brand') . " AS b
                    ON g.brand_id = b.brand_id
            WHERE o.order_id = '$order[order_id]'";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $row['goods_name'] .= $row['goods_specification']==''?'':'（'.$row['goods_specification'].'）';

        $row['formated_subtotal']       = number_format($row['goods_price'] * $row['goods_number'], 2, '.', '');
		$wholesale_total += $row['wholesale_price'] * $row['goods_number'];

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy')
        {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }

        $goods_list[] = $row;
    }

    $smarty->assign('goods_list', $goods_list);
	
	$wholesale = array('total'=>number_format($wholesale_total, 2, '.', ''),'profit'=>number_format($order['goods_amount']-$wholesale_total, 2, '.', ''),'rate'=>$order['goods_amount']<=0?'0%':number_format(($order['goods_amount']-$wholesale_total)/$order['goods_amount']*100, 1, '.', '').'%');
    $smarty->assign('wholesale', $wholesale);

}elseif($act=='goods'){
	
	$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
	$cat_id = !empty($_GET['cat_id']) && intval($_GET['cat_id'])>0 ? intval($_GET['cat_id']) : 0;
	$page_size = 10;
	
	if($cat_id==0 && $_SESSION['store']['site_store_cat']==0) $where_cat_id = '';
	else{
		$children = get_children($cat_id==0?$_SESSION['store']['site_store_cat']:$cat_id);
		$where_cat_id = "($children OR " . get_extension_goods($children) . ') AND';
	}
	
	$count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('goods') . ' AS g ' .
			' WHERE '.$where_cat_id.' g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	
	$goods_list = $db->getAll('SELECT g.goods_id, g.goods_name, g.goods_specification,g.goods_sn,g.shop_price,g.wholesale_price, IFNULL(sp.price, 0) AS sale_price, IFNULL(g.goods_thumb, \'images/no_picture.gif\') AS goods_thumb FROM ' . $ecs->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $ecs->table('store_price') . ' AS sp ' .
                "ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'] .
            ' WHERE '.$where_cat_id.' g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 
			ORDER BY g.sort_order,g.last_update desc limit '.($page-1) * $page_size.','.$page_size);
	foreach ($goods_list AS $k => $v){
        $goods_list[$k]['goods_name'] .= $v['goods_specification']==''?'':'（'.$v['goods_specification'].'）';
    }
	$smarty->assign('goods_list',      $goods_list);
	
	$smarty->assign('order_list',      $res);
	$page_big = $count>0?ceil($count/$page_size):1;
	$smarty->assign('total',      array('count'=>$count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0),'cat_id'=>$cat_id));

}elseif($act=='edit_sale_price'){
	$goods_id = !empty($_GET['goods_id']) && intval($_GET['goods_id'])>0 ? intval($_GET['goods_id']) : 0;
	$page = !empty($_GET['page']) && intval($_GET['page'])>0 ? intval($_GET['page']) : 1;
	$price = !empty($_GET['price']) && floatval($_GET['price'])>0 ? floatval($_GET['price']) : 0;
	if(intval($db->getOne('SELECT count(*) FROM ' . $ecs->table('goods') . " WHERE goods_id=$goods_id"))==0) show_msg('操作有误，无此商品。','返回列表','shop_store.php?act=goods&page='.$page);
	
	$price_id = intval($db->getOne('SELECT price_id FROM ' . $ecs->table('store_price') . " WHERE goods_id=$goods_id AND store_id=".$_SESSION['store']['store_id']));
	
	if($price==0){
		if($price_id>0) $db->query("DELETE FROM " . $ecs->table('store_price') . " WHERE price_id=$price_id");
	}else{
		$price = number_format($price, 2, '.', '');
		if($price_id>0) $db->query("UPDATE " . $ecs->table('store_price') . " SET price='$price' WHERE price_id=$price_id");
		else{
			$price_arr = array(
				'store_id'	=> $_SESSION['store']['store_id'],
				'goods_id'	=> $goods_id,
				'price'	=> $price
			);
			$db->autoExecute($ecs->table('store_price'), $price_arr, 'INSERT');
		}
	}
	/* 清空缓存 */
    clear_cache_files();
	//ecs_header("Location: ./shop_store.php?act=goods&page=$page\n");
	//exit;
	show_msg('商品价格修改成功。','返回列表','shop_store.php?act=goods&page='.$page);

}elseif($act=='user'){
	$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
	$page_size = 10;
	$count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('users') . ' WHERE store_id='.$_SESSION['store']['store_id']);
	$res = $db->getAll('SELECT u.user_id,u.user_name,u.pay_points,u.reg_time,u.last_login,COUNT(o.order_id) AS order_count FROM ' . $ecs->table('users') . ' AS u ' .
            ' LEFT JOIN ' . $ecs->table('order_info') .' AS o ON o.user_id=u.user_id '.
            " WHERE u.store_id=".$_SESSION['store']['store_id']." GROUP BY u.user_id ORDER BY u.user_id desc limit ".($page-1) * $page_size.','.$page_size);
	
	foreach ($res AS $key => $value){
        $res[$key]['reg_time'] = local_date('Y-m-d H:i', $value['reg_time']);
        $res[$key]['last_login'] = local_date('Y-m-d H:i', $value['last_login']);
    }
	
	$smarty->assign('user_list',      $res);
	$page_big = $count>0?ceil($count/$page_size):1;
	$smarty->assign('total',      array('count'=>$count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0)));


}elseif($act=='clerk'){
	$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
	$page_size = 10;
	$count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('store_clerk') . ' WHERE store_id='.$_SESSION['store']['store_id']);
	$res = $db->getAll('SELECT * FROM ' . $ecs->table('store_clerk') . " WHERE store_id=".$_SESSION['store']['store_id']." GROUP BY clerk_id desc limit ".($page-1) * $page_size.','.$page_size);
	
	foreach ($res AS $key => $value){
		$res[$key]['time_reg'] = local_date('Y-m-d H:m:s',$value['time_reg']);
		$res[$key]['time_last'] = local_date('Y-m-d H:m:s',$value['time_last']);
    }
	
	$smarty->assign('clerk_list',      $res);
	$page_big = $count>0?ceil($count/$page_size):1;
	$smarty->assign('total',      array('count'=>$count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0)));
	
}elseif($act=='points'){


}elseif($act=='points_submit'){
	$phone = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
	if($phone=='' || strlen($phone)!=11 || !is_numeric($phone)) show_msg('请正确输入会员的手机号。');
	$user = $db->getRow('SELECT u.user_id,u.user_name,u.pay_points,u.reg_time,u.last_login,COUNT(o.order_id) AS order_count FROM ' . $ecs->table('users') . ' AS u ' .
            ' LEFT JOIN ' . $ecs->table('order_info') .' AS o ON o.user_id=u.user_id '.
            " WHERE u.user_name = '$phone' and u.store_id=".$_SESSION['store']['store_id']." GROUP BY u.user_id");
	if(empty($user)) show_msg('出错：无此会员（手机号：'.$phone.'）。');
	$user['reg_time'] = local_date('Y-m-d H:i', $user['reg_time']);
   $user['last_login'] = local_date('Y-m-d H:i', $user['last_login']);
	$smarty->assign('user',      $user);


}elseif($act=='add_clerk'){


}elseif($act=='add_clerk_submit'){
	$password = !empty($_POST['password']) ? trim($_POST['password']) : '';
	$password_confirm = !empty($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
	$clerk = array(
		'store_id'     => $_SESSION['store']['store_id'],
		'clerk_remark'   => !empty($_POST['clerk_remark']) ? trim($_POST['clerk_remark']) : '',
		'password'   => md5($password),
		'time_reg'  => time()
	);
	if($password=='') show_msg('请输入店员的交易密码。','重新添加','shop_store.php?act=add_clerk');
	if($password!=$password_confirm) show_msg('交易密码两次输入不一致！','重新添加','shop_store.php?act=add_clerk');
	if( $db->getOne("SELECT count(*) FROM " . $ecs->table('store_clerk').' where store_id='.$store_id.' and  password=\''.$clerk['password'].'\'')>0) show_msg('此交易密码已被本店的其他店员使用，请重新输入。','重新添加','shop_store.php?act=add_clerk');
	$db->autoExecute($ecs->table('store_clerk'), $clerk, 'INSERT');
	show_msg('添加成功，已成功添加一个店员。','返回列表','shop_store.php?act=clerk');
	
}elseif($act=='edit_clerk'){
	$clerk_id = empty($_GET['clerk_id']) ? 0 : intval($_GET['clerk_id']);
	$clerk = $db->getRow("SELECT * FROM " . $ecs->table('store_clerk') . " WHERE clerk_id = '$clerk_id' and store_id=".$_SESSION['store']['store_id']);
	if (empty($clerk)) show_msg('操作有误，无此店员。','返回列表','shop_store.php?act=clerk');
	$smarty->assign('clerk', $clerk);

}elseif($act=='edit_clerk_submit'){
	$clerk_id = empty($_GET['clerk_id']) ? 0 : intval($_GET['clerk_id']);
	$password = !empty($_POST['password']) ? trim($_POST['password']) : '';
	$password_confirm = !empty($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
	$store_id = $db->getOne("SELECT store_id FROM " . $ecs->table('store_clerk').' where clerk_id='.$clerk_id);
	if($store_id!=$_SESSION['store']['store_id']) show_msg('操作有误，无此店员。','返回列表','shop_store.php?act=clerk');
	$clerk = array(
		'clerk_remark'   => !empty($_POST['clerk_remark']) ? trim($_POST['clerk_remark']) : '',
		'restraint'   => !empty($_POST['restraint']) && intval($_POST['restraint'])==1 ? 1 :0
	);
	if($password!=''){
		if($password!=$password_confirm) show_msg('交易密码两次输入不一致！','返回编辑','shop_store.php?act=edit_clerk&clerk_id='.$clerk_id);
		$clerk['password'] = md5($password);
		if( $db->getOne("SELECT count(*) FROM " . $ecs->table('store_clerk').' where store_id='.$_SESSION['store']['store_id'].' and clerk_id<>'.$clerk_id.' and  password=\''.$clerk['password'].'\'')>0) show_msg('此交易密码已被本店的其他店员使用，请重新输入。','返回编辑','shop_store.php?act=edit_clerk&clerk_id='.$clerk_id);
	}
	$db->autoExecute($ecs->table('store_clerk'), $clerk, 'UPDATE', "clerk_id = '$clerk_id'");
	show_msg('店员编辑成功。','返回列表','shop_store.php?act=clerk');
	
}elseif($act=='del_clerk'){
	$clerk_id = empty($_GET['clerk_id']) ? 0 : intval($_GET['clerk_id']);
	$clerk = $db->getRow("SELECT * FROM " . $ecs->table('store_clerk') . " WHERE clerk_id = '$clerk_id' and store_id=".$_SESSION['store']['store_id']);
	if (empty($clerk)) show_msg('操作有误，无此店员。','返回列表','shop_store.php?act=clerk');
	if( $db->getOne("SELECT count(*) FROM " . $ecs->table('order_info').' where clerk_id='.$clerk_id)>0) show_msg('此店员已有销售记录，请先联系网站管理员清除相应订单。','返回列表','shop_store.php?act=clerk');
	
	$db->query("DELETE FROM " . $ecs->table('store_clerk') . ' WHERE clerk_id='.$clerk_id);
	show_msg('店员删除成功。','返回列表','shop_store.php?act=clerk');
	
}elseif($act=='getout'){
	session_destroy();
	show_msg('店家成功退出登录。',array('重新登录','商品导购','购物车'),array('shop_store.php?act=login','shop_category.php','shop_cart.php'));
	show_msg('店员删除成功。','返回列表','shop_store.php?act=clerk');

}
else{
	$act = 'order';
	
	$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
	$page_size = 10;
	$count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('order_info') . ' AS o WHERE o.store_id='.$_SESSION['store']['store_id']);
	$res = $db->getAll('SELECT c.clerk_remark,o.clerk_id,o.order_id,o.order_sn,o.add_time,o.order_status,o.shipping_status,o.pay_status,o.order_amount, o.money_paid,o.consignee,o.mobile,('. order_amount_field('o.') .') AS total_fee FROM ' . $ecs->table('order_info') . ' AS o ' .
            ' LEFT JOIN ' . $ecs->table('store_clerk') .' AS c ON c.clerk_id=o.clerk_id '.
            " WHERE o.store_id=".$_SESSION['store']['store_id']." ORDER BY o.order_id desc limit ".($page-1) * $page_size.','.$page_size);
	
	foreach ($res AS $key => $value){
        $res[$key]['clerk'] = $value['clerk_id']>0 ? $value['clerk_remark'].'('.$value['clerk_id'].')' : '- -';
        $res[$key]['short_order_time'] = local_date('Y-m-d H:i', $value['add_time']);
		$res[$key]['total_fee'] = number_format($value['total_fee'], 2, '.', '');
    }
	
	$smarty->assign('order_list',      $res);
	$page_big = $count>0?ceil($count/$page_size):1;
	$smarty->assign('total',      array('count'=>$count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0)));
}
$smarty->assign('act',      $act);
$smarty->display('shop_store.html');


?>