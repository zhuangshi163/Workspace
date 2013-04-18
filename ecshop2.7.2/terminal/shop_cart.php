<?php
// Adam制作 今日特价
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
if ((DEBUG_MODE & 2) != 2) $smarty->caching = true;

$act = !empty($_GET['act']) ? trim($_GET['act']) : '';
$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
$page_size = 5;

if($act=='add'){
	$goods_id = !empty($_GET['goods_id']) && intval($_GET['goods_id'])>0 ? intval($_GET['goods_id']) : 0;
	$count = !empty($_GET['count']) && intval($_GET['count'])>0 ? intval($_GET['count']) : 1;
	$goods = $db->getRow('SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price FROM '.$ecs->table('goods').' as g '.
			'LEFT JOIN ' . $ecs->table('store_price') . " AS sp ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'].
			' where g.goods_id='.$goods_id.' and g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	
	$now = gmtime();
	if(empty($goods)){
		ecs_header("Location: shop_convenient.php\n");
		exit;
	}elseif ($goods["is_promote"]==1&&$goods["promote_start_date"]<=$now&&$goods["promote_end_date"]>$now){
		$goods["sale_price"]=$goods["promote_price"];
		$goods["goods_is_promote"]=1;
		$goods["goods_name"] .=" (特价)";
	}else{
		$goods["goods_is_promote"]=0;
	}
	$old = $db->getRow('SELECT rec_id,goods_number FROM '.$ecs->table('cart').' where goods_id='.$goods_id." and store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if(!empty($old)){
		$rec_id_old = $old['rec_id'];
		$count += $old['goods_number'];
	}else $rec_id_old = 0;
	//检查：库存
    if ($_CFG['use_storage'] == 1 && $count > $goods['goods_number']) show_msg('对不起，该商品当前库存量为 '.$goods['goods_number'].'， 不足您的购买数量。','返回购物');
	
	
	if($rec_id_old>0) $db->query("UPDATE " . $ecs->table('cart') . " SET goods_number = $count WHERE rec_id = ".$rec_id_old." and session_id='".SESS_ID."'");
	else{
		$parent = array(
			'store_id'    => $_SESSION['store']['store_id'],
			'goods_id'      => $goods_id,
			'session_id'      => SESS_ID,
			'goods_sn'      => addslashes($goods['goods_sn']),
			'goods_name'    => addslashes($goods['goods_name']),
			'market_price'  => $goods['market_price'],
			'wholesale_price'=>$goods['wholesale_price'],
			'goods_price'  => $goods['sale_price'],
			'goods_number'  => $count,
			'is_real'       => $goods['is_real'],
			'extension_code'=> $goods['extension_code'],
			'is_shipping'   => $goods['is_shipping'],
			'rec_type'      => CART_GENERAL_GOODS,
			'goods_is_promote' => $goods["goods_is_promote"]
		);
		$db->autoExecute($ecs->table('cart'), $parent, 'INSERT');
	}
	ecs_header("Location: shop_cart.php?page=$page\n");
	exit;

}elseif($act=='convenient_cart'){
	$goods_id = !empty($_GET['goods_id']) && intval($_GET['goods_id'])>0 ? intval($_GET['goods_id']) : 0;
	$addcart = !empty($_GET['addcart']) && intval($_GET['addcart'])==1 ? 1 : 0;
	
	include_once('includes/cls_json.php');
	//$result = array('error' => 0, 'message' => '', 'goods_id' => $goods_id, 'cname' => $addcart==1 ? 'cur' : '');
	$result = array('success' => 1);
    $json  = new JSON;
    
    $good_in_cart = $db->getOne("select c.rec_id from ".$ecs->table('cart')." as c where c.store_id = ".$_SESSION['store']['store_id']." and c.goods_id='".$goods_id."' and c.session_id='".SESS_ID."'");
    if(!empty($good_in_cart)){
    	$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE rec_id='".$good_in_cart."'");
    	die($json->encode($result));
    }
	/*
	if($addcart==0){
		$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE goods_id = '$goods_id' and store_id = '$_SESSION['store']['store_id']' and session_id='".SESS_ID."'");
		die($json->encode($result));
	}
	*/
	$goods = $db->getRow('SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price FROM '.$ecs->table('goods').' as g '.
			'LEFT JOIN ' . $ecs->table('store_price') . " AS sp ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'].
			' where g.goods_id='.$goods_id.' and g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	$now = gmtime();
	if(empty($goods)){
		//$result['error'] = 1;
		$result['success'] = 0;
		$result['message'] = '无此商品';
		die($json->encode($result));
	}elseif ($goods["is_promote"]==1&&$goods["promote_start_date"]<=$now&&$goods["promote_end_date"]>$now){
		$goods["sale_price"]=$goods["promote_price"];
		$goods["goods_is_promote"]=1;
		$goods["goods_name"] .=" (特价)";
	}else{
		$goods["goods_is_promote"]=0;
	}
	/*
	$rec_id_old = $db->getOne('SELECT rec_id FROM '.$ecs->table('cart').' where goods_id='.$goods_id." and store_id = '$_SESSION['store']['store_id']' and session_id='".SESS_ID."'");
	if($rec_id_old>0) $db->query("UPDATE " . $ecs->table('cart') . " SET goods_number=1 WHERE rec_id = ".$rec_id_old." and session_id='".SESS_ID."'");
	else{
	*/
		//检查：库存
		if ($_CFG['use_storage'] == 1 && $goods['goods_number']<1){
			$result['success'] = 0;
			$result['message'] = '库存为零';
			die($json->encode($result));
		}
		$parent = array(
			'store_id'    => $_SESSION['store']['store_id'],
			'goods_id'      => $goods_id,
			'session_id'      => SESS_ID,
			'goods_sn'      => addslashes($goods['goods_sn']),
			'goods_name'    => addslashes($goods['goods_name']),
			'market_price'  => $goods['market_price'],
			'wholesale_price'=> $goods['wholesale_price'],
			'goods_price'  => $goods['sale_price'],
			'goods_number'  => 1,
			'is_real'       => $goods['is_real'],
			'extension_code'=> $goods['extension_code'],
			'is_shipping'   => $goods['is_shipping'],
			'rec_type'      => CART_GENERAL_GOODS,
			'goods_is_promote' => $goods["goods_is_promote"]
		);
		$db->autoExecute($ecs->table('cart'), $parent, 'INSERT');
	//}
	die($json->encode($result));

}elseif($act=='edit_num'){
	$rec_id = !empty($_GET['rec_id']) && intval($_GET['rec_id'])>0 ? intval($_GET['rec_id']) : 0;
	$num = !empty($_GET['num']) && intval($_GET['num'])>0 ? intval($_GET['num']) : 1;
	$goods_id = $db->getOne('SELECT goods_id FROM '.$ecs->table('cart').' where rec_id='.$rec_id." and store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if($goods_id<1){
		ecs_header("Location: shop_convenient.php\n");
		exit;
	}
	$goods_number = $db->getOne('SELECT goods_number FROM '.$ecs->table('goods').' where goods_id='.$goods_id);
	//检查：库存
    if ($_CFG['use_storage'] == 1 && $num > $goods_number){
		$db->query("UPDATE " . $ecs->table('cart') . " SET goods_number = $goods_number WHERE rec_id = ".$rec_id);
		show_msg('对不起，该商品当前库存量为 '.$goods_number.'， 不足您的购买数量。','返回购物');
	}
	
	$db->query("UPDATE " . $ecs->table('cart') . " SET goods_number = $num WHERE rec_id = ".$rec_id);
	ecs_header("Location: shop_cart.php?page=$page\n");
	exit;

}elseif($act=='del'){
	$rec_id = !empty($_GET['rec_id']) && intval($_GET['rec_id'])>0 ? intval($_GET['rec_id']) : 0;
	$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE rec_id = '$rec_id' and store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	$cart_count = $db->getOne('SELECT count(*) FROM '.$ecs->table('cart')." where store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if($cart_count>0) show_msg('商品删除成功。','返回购物车','shop_cart.php');
	else show_msg('商品删除成功。','继续购物','shop_convenient.php');

}elseif($act=='empty_goods'){
	$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	 show_msg('购物车成功清空，现在返回商品导购。','商品导购','shop_convenient.php');

}elseif($act=='order_confirm'){
	$cart_count = $db->getOne('SELECT count(*) FROM '.$ecs->table('cart')." where store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if($cart_count==0) show_msg('购物车中没有商品，请先添加再提交订单。','继续购物','shop_category.php');
	
	$phone = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
	$consignee = !empty($_POST['consignee']) ? trim($_POST['consignee']) : '';
	$is_inv_payee = !empty($_POST['is_inv_payee']) && intval($_POST['consignee'])==1 ? 1 : 0;
	$inv_payee = !empty($_POST['inv_payee']) ? trim($_POST['inv_payee']) : '';
	$clerk_password = !empty($_POST['clerk_password']) ? trim($_POST['clerk_password']) : '';
	
	if ($_CFG['use_storage'] == 1){
		$txt = '';
		$goods_list = $db->getAll('SELECT c.goods_name, c.goods_number as c_num, g.goods_number as g_num FROM ' . $ecs->table('cart') . ' AS c ' .
				'LEFT JOIN ' . $ecs->table('goods') .' AS g ON g.goods_id = c.goods_id '.
				'WHERE c.store_id = '.$_SESSION['store']['store_id'].' and c.session_id=\''.SESS_ID.'\' order by rec_id desc');
		foreach ($goods_list as $k => $v){
			if($v['c_num'] > $v['g_num']) $txt .= '<br />'.htmlspecialchars($v['goods_name']).' 库存数量：<b class="f_red">'.$v['g_num'].'</b>';
		}
		if($txt!=''){
			show_msg('出错：以下商品库存不足。'.$txt,'返回购物车','shop_cart.php');
		}
	}
	
	if($phone=='' || strlen($phone)!=11 || !is_numeric($phone)) show_msg('请正确输入您的联系手机号。');
	if($consignee=='') show_msg('请输入联系人，如姓名：张三。');
	if($clerk_password=='') show_msg('请联系本店店员，输入他的确认密码。');
	if($is_inv_payee==1 && $inv_payee =='') show_msg('请输入发票的抬头，或选择不需要开启发票。');
	
	$clerk_id = intval($db->getOne('SELECT clerk_id FROM '.$ecs->table('store_clerk')." where store_id = ".$_SESSION['store']['store_id']." and password='".md5($clerk_password)."' and restraint=0"));
	if($clerk_id==0) show_msg('店员确认密码不正确，请店员重新输入。');
	
	$ip = real_ip();
	$time_now = time();
	
	$user_id = intval($db->getOne('SELECT user_id FROM '.$ecs->table('users')." where user_name = '$phone'"));
	if($user_id==0){
		$parent = array(
			'store_id'      => $_SESSION['store']['store_id'],
			'user_name'     => $phone,
			'office_phone'  => $phone,
			'reg_time'   	=> $time_now,
			'last_login'    => $time_now,
			'last_ip'       => $ip,
			'visit_count'   => 1
		);
		$db->autoExecute($ecs->table('users'), $parent, 'INSERT');
		$user_id = $db->insert_id();
	}else $db->query("UPDATE " . $ecs->table('users') . " SET visit_count = visit_count+1,last_login='$time_now',last_ip ='$ip' WHERE user_id = ".$user_id);
	
	
	$total = $db->getRow('SELECT count(*) as goods_count,SUM(goods_number) as goods_number,SUM(goods_number*goods_price) as total FROM ' . $ecs->table('cart') . ' WHERE store_id='.$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	$total['total'] = number_format($total['total'], 2, '.', '');
	
    $order_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	$order = array(
		'order_sn'	=> $order_sn,
		'user_id'	=> $user_id,
		'pay_id'	=> 1,
		'store_id'	=> $_SESSION['store']['store_id'],
		'clerk_id'	=> $clerk_id,
		'consignee'	=> $consignee,
		'mobile'	=> $phone,
		'goods_amount'	=> $total['total'],
		'order_amount'	=> $total['total'],
		'inv_payee'	=> $inv_payee,
		'add_time'	=> $time_now
	);
	$db->autoExecute($ecs->table('order_info'), $order, 'INSERT');
	$order_id = $db->insert_id();
	
    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) ".
            " SELECT '$order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id".
            " FROM " .$ecs->table('cart') .
            " WHERE store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'";
    $db->query($sql);
	
    /* 如果使用库存，且下订单时减库存，则减少库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE){
		$res = $db->getAll('SELECT goods_id,goods_number FROM ' . $ecs->table('cart') . ' WHERE store_id = '.$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
		foreach ($res as $row){
			$db->query("UPDATE " . $ecs->table('goods') . " SET goods_number = goods_number-".$row['goods_number']." WHERE goods_id= ".$row['goods_id']);
		}
	}
	$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	$db->query("UPDATE " . $ecs->table('store_clerk') . " SET time_last='$time_now',visit_count=visit_count+1 WHERE clerk_id= ".$clerk_id);
	
    clear_cache_files();
	
	$store_remark = $db->getOne('SELECT store_remark FROM ' . $ecs->table('store') . ' WHERE store_id = '.$_SESSION['store']['store_id']);
	
	$goods_list = $db->getAll('SELECT * FROM ' . $ecs->table('order_goods') . ' WHERE order_id = '.$order_id);
	$goods_array = array();
	foreach ($goods_list as $k => $v){
		$goods_array[] = '{"name":"'.htmlspecialchars($v['goods_name']).'", "price":'.$v['goods_price'].', "count":'.$v['goods_number'].', "total":'.number_format($v['goods_price'] * $v['goods_number'], 2, '.', '').'}';
	}
	$print_html = '<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0>
		<embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0 pluginspage="install_lodop.exe"></embed>
	</object>
	<script language="javascript" type="text/javascript"> 
		var json = {"title":"健康盾网购机订单信息", "name":"'.$consignee.'", "phone": "'.$phone.'", "orderTime": "'.local_date('Y-m-d H:i:s').'", 
		"orderNo": "'.$order_sn.'", "shop":"'.$store_remark.'", "total":'.$total['total'].',"totalCount":'.$total['goods_number'].',
		"goodsList":['.implode(',',$goods_array).']
		}		
	</script>
	<script language="javascript" src="js/LodopFuncs.js"></script>';
	
	show_msg($print_html.'<h1>下单成功</h1>订单号：<span class="f_red">'.$order_sn.'</span><br /><br /><span class="min">我们会尽快配货，并及时通知您到本店取货并付款，谢谢。<span>','继续购物','shop_convenient.php');
	
}elseif ($act=='order_confirm_card'){
	
	$card_number = empty($_POST['card_no'])? '':trim($_POST['card_no']);
	if(empty($card_number)){
		show_msg ( "请刷会员卡!" , "返回购物车", "shop_cart.php");
	}else {
		$card_number = substr($card_number, 0, 12);
		$sql = "SELECT c.card_id, c.card_number, c.store_id, c.card_state,c.user_id, u.user_name, u.real_name, u.mobile_phone" . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " . 
			" inner join " . $GLOBALS ['ecs']->table ( 'users' ) . " as u on c.user_id=u.user_id and c.card_number='$card_number' and c.card_state=1 and c.user_id is not null".
		" where c.store_id=".$_SESSION['store']['store_id'];
		$card = $db->getRow ( $sql );
		if ($card==FALSE) {
			show_msg ( "请使用本店已激活的会员卡!" , "返回购物车", "shop_cart.php");
		}
	}
	$cart_count = $db->getOne('SELECT count(*) FROM '.$ecs->table('cart')." where store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if($cart_count==0) show_msg('购物车中没有商品，请先添加再提交订单。','继续购物','shop_category.php');
	
	if ($_CFG['use_storage'] == 1){
		$txt = '';
		$goods_list = $db->getAll('SELECT c.goods_name, c.goods_number as c_num, g.goods_number as g_num FROM ' . $ecs->table('cart') . ' AS c ' .
				'LEFT JOIN ' . $ecs->table('goods') .' AS g ON g.goods_id = c.goods_id '.
				'WHERE c.store_id = '.$_SESSION['store']['store_id'].' and c.session_id=\''.SESS_ID.'\' order by rec_id desc');
		foreach ($goods_list as $k => $v){
			if($v['c_num'] > $v['g_num']) $txt .= '<br />'.htmlspecialchars($v['goods_name']).' 库存数量：<b class="f_red">'.$v['g_num'].'</b>';
		}
		if($txt!=''){
			show_msg('出错：以下商品库存不足。'.$txt,'返回购物车','shop_cart.php');
		}
	}
	
	//判断会员卡中的余额是否大于订单总款，并在会员卡中做相应的操作
	$user_id = $db->getOne('SELECT user_id FROM'.$ecs->table('user_card')." where card_number='$card_number'");
	$cart_goods = $db->getAll('SELECT * FROM '.$ecs->table('cart')." where store_id = ".$_SESSION['store']['store_id']." and session_id ='".SESS_ID."'");
	$users_user_money = $db->getOne('SELECT user_money FROM '.$ecs->table('users')." where user_id = '$user_id'");
	$total_price = 0;
	foreach ($cart_goods as $column ){
		$total_price = $total_price +doubleval($column['goods_price'])* doubleval($column['goods_number']);
	}
	if($users_user_money < $total_price){
		show_msg('您的会员卡余额不足。请及时充值。','返回购物车','shop_cart.php');
	}
	//$users_user_money = $users_user_money - $total_price;            //修改用户中的用户金额
	//$db->query("UPDATE " . $ecs->table('users') . " SET user_money='$users_user_money' where user_id = ".$user_id);
	log_account_change($user_id, $total_price * (-1), 0, 0, 0);	
	
	$ip = real_ip();
	$time_now = time();
	
	$db->query("UPDATE " . $ecs->table('users') . " SET visit_count = visit_count+1,last_login='$time_now',last_ip ='$ip' WHERE user_id = ".$card['user_id']);
	
	
	$total = $db->getRow('SELECT count(*) as goods_count,SUM(goods_number) as goods_number,SUM(goods_number*goods_price) as total FROM ' . $ecs->table('cart') . ' WHERE store_id='.$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	$total['total'] = number_format($total['total'], 2, '.', '');

	$order_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	$order = array(
		'order_sn'	=> $order_sn,
		'user_id'	=> $card['user_id'],
		'pay_id'	=> 1,
		'store_id'	=> $_SESSION['store']['store_id'],
		'consignee'	=> $card['real_name'],
		'mobile'	=> $card['mobile_phone'],
		'goods_amount'	=> $total['total'],
		'order_amount'	=> $total['total'],
		'add_time'	=> $time_now,
		'user_money'=>$users_user_money-$total_price,
		'referer' => '终端机'	
	);
	$db->autoExecute($ecs->table('order_info'), $order, 'INSERT');
	$order_id = $db->insert_id();
	
    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, wholesale_price, goods_is_promote, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) ".
            " SELECT '$order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, wholesale_price, goods_is_promote, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id".
            " FROM " .$ecs->table('cart') .
            " WHERE store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'";
    $db->query($sql);
	
    /* 如果使用库存，且下订单时减库存，则减少库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE){
		$res = $db->getAll('SELECT goods_id,goods_number FROM ' . $ecs->table('cart') . ' WHERE store_id = '.$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
		foreach ($res as $row){
			$db->query("UPDATE " . $ecs->table('goods') . " SET goods_number = goods_number-".$row['goods_number']." WHERE goods_id= ".$row['goods_id']);
		}
	}
	$db->query("DELETE FROM " . $ecs->table('cart') . " WHERE store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	
    clear_cache_files();
	
	$store_remark = $db->getOne('SELECT store_remark FROM ' . $ecs->table('store') . ' WHERE store_id = '.$_SESSION['store']['store_id']);
	
	$goods_list = $db->getAll('SELECT * FROM ' . $ecs->table('order_goods') . ' WHERE order_id = '.$order_id);
	$goods_array = array();
	foreach ($goods_list as $k => $v){
		$goods_array[] = '{"name":"'.htmlspecialchars($v['goods_name']).'","goods_is_promote": '.$v['goods_is_promote'].', "price":'.$v['goods_price'].', "count":'.$v['goods_number'].', "total":'.number_format($v['goods_price'] * $v['goods_number'], 2, '.', '').'}';
	}
	$print_html = '<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0>
		<embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0 pluginspage="install_lodop.exe"></embed>
	</object>
	<script language="javascript" type="text/javascript"> 
		var json = {"print_times":2,"source":"订购机" ,"title":"健康盾网购机订单信息", "name":"'.$card['real_name'].'", "phone": "'.$card['mobile_phone'].'", "orderTime": "'.local_date('Y-m-d H:i:s').'", 
		"orderNo": "'.$order_sn.'", "shop":"'.$store_remark.'", "total":'.$total['total'].',"totalCount":'.$total['goods_number'].',
		"goodsList":['.implode(',',$goods_array).'],"users_money":'.$order['user_money'].'
		}		
	</script>
	<script language="javascript" src="js/LodopFuncs.js"></script>';
	
	show_msg('<h1 style="padding: 20px 0 40px;">下单成功</h1>您的会员卡余额为：'.$order['user_money'].'元<br /><br />请及时到您的会员卡绑定的社区取货点取货，谢谢。','继续购物','shop_convenient.php','info',true,$print_html,'您已下单成功，您的会员卡余额为'.$order['user_money'].'元，请及时到您的会员卡绑定的社区取货点取货，谢谢');
	
}elseif ($act=='order_find'){
	//var_dump($_POST['mobile_no']).'###';
	$mobile = empty($_POST['mobile_no'])? '':trim($_POST['mobile_no']);
	$card_number = empty($_POST['card_no'])? '':trim($_POST['card_no']);
	if(empty($card_number)){
		$card_number = empty($_GET['card_no'])?"":trim($_GET['card_no']);
	}
	if(empty($card_number)&&empty($mobile)){
		if(empty($mobile)){
			show_msg ( "请刷卡或输入你的手机号码" , "返回会员空间", "shop_user.php ");
		}
	}else{
		$card_number = substr($card_number, 0, 12);
		$sql="select card.store_id from ". $GLOBALS ['ecs']->table ( 'user_card' ) . "as card where card.card_number='$card_number'";
		$store_id=$db->getOne($sql);
		if($store_id!=$_SESSION['store']['store_id']){
			show_msg ( "请使用本店已激活的会员卡!" , "返回会员空间", "shop_user.php ");
		}
	}
	if($card_number =='' && $mobile ==''){
		show_msg ( "查询条件不能为空" , "返回会员空间", "shop_user.php ");
	}
	/*
	 * pay_status`  '支付状态；0，未付款；1，付款中；2，已付款',
	* shipping_status`  '商品配送情况，0，未发货；1，已发货；2，已收货；3，备货中',
	* referer`  '订单的来源页面
	* */
	//$where=' WHERE shipping_status = 0 ';	//订单查询条件
	$where = ' WHERE 1 ';
	//card 
	if($card_number != ''){
		$sql = "SELECT  c.user_id " . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " .
				" left outer join " . $GLOBALS ['ecs']->table ( 'users' ) . " as u on c.user_id=u.user_id ".
				" where c.card_number='$card_number' and c.card_state=1 and c.user_id is not null ";
		$card = $db->getRow ( $sql );
		if ($card==FALSE) {
			show_msg ( "请使用已激活的会员卡!" , "返回会员空间", "shop_user.php");
		}
		
		$where .='and user_id = '.$card['user_id'];		
	}elseif ($mobile != '') {
		$where .='and mobile = '.$mobile;
	}
	$where.=' order by add_time desc';
	
	$total = $db->getRow("SELECT COUNT(*) as order_count,SUM(order_amount) as total  FROM " .$ecs->table('order_info'). $where);
	
	$total_page=ceil($total['order_count']/$page_size);
	$page_big = $total_page==0?1:$total_page;
	
	if($page_big<$page){
		ecs_header("Location: shop_cart.php?act=order_find&page=$page_big\n");
		exit;
	}
	/*
	 * pay_status`  '支付状态；0，未付款；1，付款中；2，已付款',
	* shipping_status`  '商品配送情况，0，未发货；1，已发货；2，已收货；3，备货中',
	* referer`  '订单的来源页面
	* */
	$where .=" LIMIT " .($page-1)*$page_size. ", " . $page_size;
	$order_list = $db->getAll('SELECT order_id,order_sn, from_unixtime(add_time) as add_time,order_amount,consignee,referer FROM ' . $ecs->table('order_info') .$where);
	
	$page_number = ($page-1) * $page_size;
	foreach ($order_list as $k => $v){
		//$order_list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
		$page_number++;
		$order_list[$k]['page_number'] = $page_number;
	}
	
	$total['total'] =  number_format($total['total'], 2, '.', '');
	$total['page_big'] = $page_big;
	$total['page'] = $page;
	$total['page_before'] = $page-1;
	$total['page_after'] = $page_big>$page?$page+1:0;
	$smarty->assign('order_list',      $order_list);
	$smarty->assign('total',      $total);
	$smarty->assign("card_number",$card_number);
	$smarty->display('my_order.html');

	
}elseif ($act=='print_order'){	//
	
	$order_id = empty($_GET['order_id'])? '':trim($_GET['order_id']);
	$card_no = empty($_GET[card_no])?'':trim($_GET['card_no']);
	if(empty($order_id)|| empty($card_no)){
		show_msg ( "参数错误" , "返回取货查询", "shop_user.php?act=get_goods");
	}

	//order info
	$sql = "SELECT  c.card_number,  c.card_state, s.store_remark, o.consignee, o.mobile, o.order_sn" . " FROM " . $GLOBALS ['ecs']->table ( 'user_card' ) . " as c " .
			" left outer join " . $GLOBALS ['ecs']->table ( 'store' ) . " as s on c.store_id=s.store_id " .
			" left outer join " . $GLOBALS ['ecs']->table ( 'order_info' ) . " as o on c.user_id=o.user_id ".
			" where o.order_id = $order_id limit 1";
	$order = $db->getRow ( $sql );
	if ($order==FALSE) {
		show_msg ( "订单不存在" , "返回取货查询", "shop_user.php?act=get_goods");
	}
	
	//total info

	$total = $db->getRow('SELECT count(*) as goods_count,SUM(goods_number) as goods_number,SUM(goods_number*goods_price) as total FROM ' . $ecs->table('order_goods') . ' WHERE order_id='.$order_id);
	$total['total'] = number_format($total['total'], 2, '.', '');
	
	//get user's account balance
	$user_id = $db->getOne('SELECT user_id FROM '.$ecs->table('user_card').' where card_number = "'.$card_no.'"');
    $user_money = $db->getOne('SELECT user_money FROM '.$ecs->table('users').' where user_id ='.$user_id);	
	
	//goods_list info 
	$goods_list = $db->getAll('SELECT * FROM ' . $ecs->table('order_goods') . ' WHERE order_id = '.$order_id);
	$goods_array = array();
	foreach ($goods_list as $k => $v){
		$goods_array[] = '{"name":"'.htmlspecialchars($v['goods_name']).'","goods_is_promote": '.$v['goods_is_promote'].', "price":'.$v['goods_price'].', "count":'.$v['goods_number'].', "total":'.number_format($v['goods_price'] * $v['goods_number'], 2, '.', '').'}';
	}
	
	//update order shipping_status`  '商品配送情况，0，未发货；1，已发货；2，已收货；3，备货中',
	//$db->query("UPDATE " . $ecs->table('order_info') . " SET  shipping_status = 2 WHERE order_id= ".$order_id);
	
	$print_html = '<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0>
		<embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0 pluginspage="install_lodop.exe"></embed>
	</object>
	<script language="javascript" type="text/javascript">
		var json = {"print_times":2,"source":"网页版" , "title":"健康盾网购机订单信息", "name":"'.$order['consignee'].'", "phone": "'.$order['mobile'].'", "orderTime": "'.local_date('Y-m-d H:i:s').'",
		"orderNo": "'.$order['order_sn'].'", "shop":"'.$order['store_remark'].'", "total":'.$total['total'].',"totalCount":'.$total['goods_number'].',
		"goodsList":['.implode(',',$goods_array).'],"users_money":'.$user_money.'
	};
	</script>
	<script language="javascript" src="js/LodopFuncs.js"></script>';
	show_msg('<h1 style="padding: 20px 0 10px;">订单打印成功</h1>订单号：<span class="f_red">'.$order['order_sn'].'</span><br/><br/>您的会员卡余额为：'.$user_money.'元<br /><br />请及时到您的会员卡绑定的社区取货点取货，谢谢。','继续购物','shop_convenient.php','info',true,$print_html,'您的订单已打印成功，您的订单号为'.$order['order_sn'].'，您的会员卡余额为'.$user_money.'元，请及时到您的会员卡绑定的社区取货点取货，谢谢');
	//show_msg($print_html.'<h1>订单打印成功</h1>订单号：<span class="f_red">'.$order['order_sn'].'</span><br /><br /><span class="min">您的订单已打印成功，请及时到您的会员卡号绑定的社区取货点取货，谢谢。<span>','继续购物','shop_convenient.php');
	
	
	
}elseif ($act=='order_detail'){//订单详情
	$order_id=empty($_GET['order_id'])?"":$_GET['order_id'];
	$card_number = empty($_GET['card_no'])? "":trim($_GET['card_no']);
	$from_page = empty($_GET['from_page'])? "":trim($_GET['from_page']);
	if(empty($order_id)){
		show_msg ( "网络错误");
	}
	
	$sql = "SELECT count(distinct og.goods_id*og.goods_price)
			FROM " . $GLOBALS ['ecs']->table ( 'order_goods' ) . " AS og " . 
			"inner join " . $GLOBALS ['ecs']->table ( 'order_info' ) . 
				" AS oi on og.order_id = oi.order_id " . 
			" WHERE oi.order_id='$order_id'";
	$total_records = $GLOBALS ['db']->getOne ( $sql );
	$smarty->assign('total_records',$total_records);
	
	
	
	
	$sql = "SELECT og.*,g.goods_thumb,g.goods_specification,sum(og.goods_number*og.goods_price) as goods_sum FROM " . $GLOBALS ['ecs']->table ( 'order_goods' ) . " AS og " . 
			"inner join " . $GLOBALS ['ecs']->table ( 'order_info' ) . 
				" AS oi on og.order_id = oi.order_id " . 
			"left outer join ". $GLOBALS ['ecs']->table ( 'goods' ) . 
				" AS g on og.goods_id=g.goods_id ".
			" WHERE oi.order_id='$order_id' group by og.goods_price,og.goods_id ";
	$sql .=" LIMIT " .($page-1)*$page_size. ", " . $page_size;
	$goods_list= $GLOBALS ['db']->getAll( $sql );
	
	$page_number = ($page-1) * $page_size;
	foreach ($goods_list as $k => $v){
		$page_number++;
		$goods_list[$k]['page_number'] = $page_number;
		$goods_list[$k]['goods_style_name'] =add_style($v['goods_name'].($v['goods_specification']==''?'':'（'.$v['goods_specification'].'）'),$v['goods_name_style']);
	}
	
	$total_page=ceil($total_records/$page_size);
	$total_big = $total_page==0?1:$total_page;
	
	if($total_big<$page){
		ecs_header("Location: shop_cart.php?act=order_detail&page=$total_big\n");
		exit;
	}
	
	$total['total'] =  number_format($total_records, 2, '.', '');
	$total['page_big'] = $total_big;
	$total['page'] = $page;
	$total['page_before'] = $page-1;
	$total['page_after'] = $total_big>$page?$page+1:0;
	$smarty->assign('total',      $total);
	
	$smarty->assign("order_id",$order_id);
	$smarty->assign("card_number",$card_number);
	$smarty->assign("from_page",$from_page);
	$smarty->assign('goods_list',$goods_list);
	$smarty->display('order_detail.html');
}else{
	$total = $db->getRow('SELECT count(*) as goods_count,SUM(goods_number) as goods_number,SUM(goods_number*goods_price) as total FROM ' . $ecs->table('cart') . ' WHERE store_id='.$_SESSION['store']['store_id']." and session_id='".SESS_ID."'");
	if($total['goods_count']<1)show_msg('购物车暂无商品，请先购买。','返回购物');
	
	$page_big = ceil($total['goods_count']/$page_size);
	if($page_big<$page){
		ecs_header("Location: shop_cart.php?page=$page_big\n");
		exit;
	}
	
	$goods_list = $db->getAll('SELECT c.*,g.goods_specification,g.goods_number as g_number, IFNULL(g.goods_thumb, \'images/no_picture.gif\') AS goods_thumb,g.brand_id,IFNULL(b.brand_name, \'\') AS brand_name FROM ' . $ecs->table('cart') . ' AS c ' .
            'LEFT JOIN ' . $ecs->table('goods') .' AS g ON g.goods_id = c.goods_id '.
            'LEFT JOIN ' . $ecs->table('brand') .' AS b ON b.brand_id = g.brand_id '.
            'WHERE c.store_id = '.$_SESSION['store']['store_id'].' and c.session_id=\''.SESS_ID.'\' order by rec_id desc limit '.($page-1) * $page_size.','.$page_size);
	$page_number = ($page-1) * $page_size;
	foreach ($goods_list as $k => $v){
		$goods_list[$k]['sub'] = number_format($v['goods_price'] * $v['goods_number'], 2, '.', '');
        $goods_list[$k]['goods_style_name'] =add_style($v['goods_name'].($v['goods_specification']==''?'':'（'.$v['goods_specification'].'）'),$v['goods_name_style']);
        $goods_list[$k]['goods_numbera'] = $v['goods_number']-1;
        $goods_list[$k]['goods_numberb'] = $v['goods_number']+1;
        $page_number++;
		$goods_list[$k]['page_number'] = $page_number;
	}
	$total['total'] =  number_format($total['total'], 2, '.', '');
	$total['page_big'] = $page_big;
	$total['page'] = $page;
	$total['page_before'] = $page-1;
	$total['page_after'] = $page_big>$page?$page+1:0;
	$smarty->assign('goods_list',      $goods_list);
	$smarty->assign('total',      $total);
	
	$back_url = !empty($_COOKIE['cart_back_url']) ? trim($_COOKIE['cart_back_url']) : 'shop_convenient.php';
	$back = !empty($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : '';
	if(strpos($back,'shop_brand.php') || strpos($back,'shop_brandlist.php') || strpos($back,'shop_category.php') || strpos($back,'shop_category2.php') || strpos($back,'shop_convenient.php') || strpos($back,'shop_goods.php') || strpos($back,'shop_specials.php') || strpos($back,'shop_store.php')){
		$back_url = $back;
		setcookie('cart_back_url', $back,time()+3600);
	}
	$smarty->assign('back_url',      $back_url);
	
	
	$smarty->display('shop_cart.html');
}


?>