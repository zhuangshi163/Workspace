<?php

/**
 * ECSHOP 供货商发货管理
 * ============================================================================
 * 版权所有 2005-2013 广东健康盾防伪有限公司，并保留所有权利。
 * 网站地址: http://www.jkdun.com；
 * ----------------------------------------------------------------------------
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
/* 创建SuppliersDelivery数据类*/
include_once './includes/module/SuppliersDelivery.php';
$suppliers_delivery = new SuppliersDelivery();

$GLOBALS['smarty']->assign('suppliers_id', $_SESSION['suppliers_id']);
/*------------------------------------------------------ */
//-- 发货单
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('delivery_list');

    /* 模板赋值 */
    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['delivery_record']);
    //$smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));
    /* 店家列表*/
	$GLOBALS['smarty']->assign('store_list', $suppliers_delivery->get_store_list());
	/* 时间参数 */
	if (!isset($_REQUEST['start_date']))
	{
		$start_date = local_strtotime('-1 days');
	}
	if (!isset($_REQUEST['end_date']))
	{
		$end_date = local_strtotime('now');
	}
	//$GLOBALS['smarty']->assign ( 'start_time', local_date ( 'Y-m-d H:i:s', $start_date ) );
	$GLOBALS['smarty']->assign ( 'end_time', local_date ( 'Y-m-d H:i:s', $end_date ) );
	/* 供货商*/
	$suppliers_list = suppliers_list_name();
	$GLOBALS['smarty']->assign('suppliers_list', $suppliers_list);
	/* 发货单状态*/
	$GLOBALS['smarty']->assign('delivery_status', $GLOBALS['_LANG']['sds']);
	/* 载入发货单 */
	$delivery_list = $suppliers_delivery->suppliers_delivery_list();
	$GLOBALS['smarty']->assign('delivery_list', $delivery_list['delivery_list']);
    /* 分页信息*/
    $GLOBALS['smarty']->assign('record_count', $delivery_list['record_count']);
    $GLOBALS['smarty']->assign('page_count',   $delivery_list['page_count']);
    
    $GLOBALS['smarty']->assign('cfg_lang',     $GLOBALS['_CFG']['lang']);
    $GLOBALS['smarty']->assign('full_page', 1);
    $GLOBALS['smarty']->assign('filter',       $delivery_list['filter']);
    /* 排序*/
    $sort_flag = sort_flag($delivery_list['filter']);
    $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);
    /* 显示模板 */
    assign_query_info();
    $GLOBALS['smarty']->display('suppliers_delivery_list.htm');
}

/*------------------------------------------------------ */
//-- 搜索、排序、分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_query')
{
	/* 检查权限 */
	admin_priv('delivery_list');

	$result = $suppliers_delivery->suppliers_delivery_list();

	$GLOBALS['smarty']->assign('delivery_list',   $result['delivery_list']);
	$GLOBALS['smarty']->assign('filter',       $result['filter']);
	$GLOBALS['smarty']->assign('record_count', $result['record_count']);
	$GLOBALS['smarty']->assign('page_count',   $result['page_count']);

	$sort_flag = sort_flag($result['filter']);
	$GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);
	make_json_result($GLOBALS['smarty']->fetch('suppliers_delivery_list.htm'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 查看发货单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
	/* 检查权限 */
	admin_priv('delivery_list');
	$delivery_id = isset($_REQUEST['delivery_id']) ? intval($_REQUEST['delivery_id']) : 0;
	$sql = "select * from ".$GLOBALS['ecs']->table('suppliers_delivery')." as sdg where sdg.delivery_id=".$delivery_id;
	$delivery = $GLOBALS['db']->getRow($sql);
	/* 如果发货单不存在，跳转到错误页面*/
	if(empty($delivery)){
		sys_msg($GLOBALS['_LANG']['delivery_not_exist']);
	}
	$GLOBALS['smarty']->assign("delivery", $delivery);
	/* 查找发货表关联的商品*/
	$sql = "SELECT sdg.rec_id, sdg.goods_name, sdg.goods_specification, sdg.need_goods_num, sto.store_name,".
			" sup.suppliers_name, sto.store_id, sup.suppliers_id, sdg.real_goods_num, sdg.box_name".
			" FROM ".$GLOBALS['ecs']->table('suppliers_delivery_goods')."AS sdg ". 
			" INNER JOIN ".$GLOBALS['ecs']->table('suppliers_delivery')." AS sd ON sdg.delivery_id=sd.delivery_id ".
			" LEFT JOIN ".$GLOBALS['ecs']->table('store')." AS sto ON sto.store_id=sd.store_id ".
			" LEFT JOIN ".$GLOBALS['ecs']->table('suppliers')." AS sup ON sup.suppliers_id=sd.suppliers_id".
			" WHERE sdg.delivery_id=$delivery_id";
	$result = $GLOBALS['db']->getAll($sql);
	$GLOBALS['smarty']->assign("goods_list", $result);
	$GLOBALS['smarty']->assign("goods_list_length", count($result));
	/* 当前可执行的操作*/
	if($delivery['status'] == SDS_UNCONFIRMED){
		$operable_list['confirm'] = true;
	}elseif($delivery['status'] == SDS_CONFIRMED){
		$operable_list['ship'] = true;
	}
	$GLOBALS['smarty']->assign('operable_list', $operable_list);
	/* 页面按钮*/
	$GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['view_delivery']);
	$type = isset($_REQUEST['type']) ? isset($_REQUEST['type']):'default';
	if($type == 'ship'){
		$GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['store_ship'], 'href' => 'suppliers_delivery.php?act=store_ship'));
	}elseif ($type == 'default'){
		$GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['delivery_record'], 'href' => 'suppliers_delivery.php?act=list'));
	}
	/* 显示模板 */
	assign_query_info();
	$GLOBALS['smarty']->display("suppliers_delivery_view.htm");
}

/*------------------------------------------------------ */
//-- 提交审核
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'operate')
{
	/* 检查权限 */
	admin_priv('delivery_list');
	$delivery_id = isset($_REQUEST['delivery_id']) ? intval($_REQUEST['delivery_id']) : 0;
	$sql = "select * from ".$GLOBALS['ecs']->table('suppliers_delivery')." as sdg where sdg.delivery_id=".$delivery_id;
	$delivery = $GLOBALS['db']->getRow($sql);
	/* 如果发货单不存在，跳转到错误页面*/
	if(empty($delivery)){
		sys_msg($GLOBALS['_LANG']['delivery_not_exist']);
	}
	/* 确认 */
	if (isset($_POST['confirm']) || $_REQUEST['operation']=='confirm')
	{
		$confirmed = SDS_CONFIRMED;
		$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_delivery')." SET `status`=$confirmed, admin_id='".$_SESSION['admin_id']."' WHERE delivery_id=$delivery_id";
		$GLOBALS['db']->query($sql);
		$label_result = $GLOBALS['_LANG']['confirm_success'];
	}
	/* 发货*/
	elseif (isset($_POST['ship']) || $_REQUEST['operation']=='ship'){
		/* 将供货商拆分订单设为发货状态*/
		$shipped_status = SOSS_SHIPPED;
		$sql = "UPDATE ".$GLOBALS['ecs']->table('split_order_info')." SET shipping_status=$shipped_status WHERE stock_id=".$delivery['stock_id'];
		$GLOBALS['db']->query($sql);
		
		/* 将订货单设为发货状态*/
		$shipped_status = SDS_SHIPPED;
		$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_delivery')." SET `status`=$shipped_status, admin_id='".$_SESSION['admin_id']."' WHERE delivery_id=$delivery_id";
		$GLOBALS['db']->query($sql);
		$label_result = $GLOBALS['_LANG']['ship_success'];
	}
	$links[0]['text'] = $GLOBALS['_LANG']['delivery_record'];
	$links[0]['href'] = 'suppliers_delivery.php?act=list';
	sys_msg($label_result, 0, $links);
}

/*------------------------------------------------------ */
//-- 提交修改
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'submit_update')
{
	/* 检查权限 */
	admin_priv('delivery_list');
	$delivery_id = isset($_REQUEST['delivery_id']) ? intval($_REQUEST['delivery_id']) : 0;
	$real_goods_num_arr = $_REQUEST['real_goods_num'];
	$box_arr = $_REQUEST['box_name'];
	$driver_name = $_REQUEST['driver_name'];
	$driver_phone = $_REQUEST['driver_phone'];
	
	$sql = "select * from ".$GLOBALS['ecs']->table('suppliers_delivery')." where delivery_id=".$delivery_id;
	$delivery = $GLOBALS['db']->getRow($sql);
	/* 如果发货单不存在，跳转到错误页面*/
	if(empty($delivery)){
		sys_msg($GLOBALS['_LANG']['delivery_not_exist']);
	}
	/* 更新发货单商品的审核信息：实际发货商品数量、箱号*/
	$real_goods_num_sql = "";
	$box_num_sql = "";
	$ids = implode(",", array_keys($real_goods_num_arr));
	foreach($real_goods_num_arr as $key=>$real_goods_num){
		$real_goods_num_sql .= sprintf(" WHEN %d THEN '%s' ", $key, $real_goods_num);
		$box_num_sql .= sprintf(" WHEN %d THEN '%s' ", $key, $box_arr[$key]);
	}
	$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_delivery_goods')." SET real_goods_num = CASE rec_id ".
			" $real_goods_num_sql END, box_name = CASE rec_id $box_num_sql END WHERE rec_id in ($ids)";
	$GLOBALS['db']->query($sql);
	/* 修改发货单的司机姓名和电话*/
	$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_delivery')." SET driver_name =".
			" '$driver_name', driver_phone = '$driver_phone', admin_id='".$_SESSION['admin_id']."' ".
			" WHERE delivery_id=$delivery_id";
	$GLOBALS['db']->query($sql);
	$links[0]['text'] = $GLOBALS['_LANG']['delivery_record'];
	$links[0]['href'] = 'suppliers_delivery.php?act=list';
	sys_msg($GLOBALS['_LANG']['update_success'], 0, $links);
}

/*------------------------------------------------------ */
//-- 门店发货
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'store_ship')
{
	/* 检查权限 */
	admin_priv('store_ship');
	/* 获取可以送货的送货单*/
	$sql = "SELECT store.store_remark, sup.suppliers_name, sd.delivery_sn, sd.delivery_id ".
			" FROM ".$GLOBALS['ecs']->table('suppliers_delivery')." AS sd ". 
			" LEFT JOIN ".$GLOBALS['ecs']->table('suppliers')." AS sup ON sup.suppliers_id=sd.suppliers_id ".
			" LEFT JOIN ".$GLOBALS['ecs']->table('store')." AS store ON store.store_id=sd.store_id ".
			" WHERE sd.`status`=".SDS_CONFIRMED;
	$result = $GLOBALS['db']->getAll($sql);
	$GLOBALS['smarty']->assign('delivery_list', $result);
	$delivery_count = sizeof($result);
	$GLOBALS['smarty']->assign('delivery_count', $delivery_count);
	
	/* 显示模板 */
	$GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['store_ship']);
	$GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['delivery_record'], 'href' => 'suppliers_delivery.php?act=list'));
	assign_query_info();
	$GLOBALS['smarty']->display("suppliers_delivery_ship.htm");
}

/*------------------------------------------------------ */
//-- 门店发货
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'submit_store_ship')
{
	/* 检查权限 */
	admin_priv('store_ship');
	$driver_name_arr = $_REQUEST['driver_name'];
	$driver_phone_arr = $_REQUEST['driver_phone'];
	/* 更新供货商拆分订单的配送状态为已发货*/
	$ids = implode(",", array_keys($driver_name_arr));
	$shipped_status = SOSS_SHIPPED;
	$stock_id = $delivery['stock_id'];
	$now = local_strtotime('now');
	if(!empty($driver_name_arr)){
		$sql = "UPDATE ".$GLOBALS['ecs']->table('split_order_info')." SET shipping_status=$shipped_status WHERE order_id in (".
				" SELECT order_id from (".
				" SELECT DISTINCT(s.order_id) FROM ".$GLOBALS['ecs']->table('split_order_info')." AS s ".
				" INNER JOIN ".$GLOBALS['ecs']->table('suppliers_delivery')." AS sd ON s.stock_id=sd.stock_id ".
				" INNER JOIN ".$GLOBALS['ecs']->table('suppliers_delivery_goods')." AS sdg ON sd.delivery_id=sdg.delivery_id ".
				" WHERE sd.delivery_id IN ($ids) ".
				" ) AS templete)";
		$GLOBALS['db']->query($sql);
	}
	/* 更新发货单的司机姓名和电话*/
	foreach($driver_name_arr as $key=>$driver_name){
		$driver_name_sql .= sprintf(" WHEN %d THEN '%s' ", $key, $driver_name);
		$driver_phone_sql .= sprintf(" WHEN %d THEN '%s' ", $key, $driver_phone_arr[$key]);
	}
	$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_delivery')." SET driver_name = CASE delivery_id ".
			" $driver_name_sql END, driver_phone = CASE delivery_id $driver_phone_sql END, ".
			" driver_confirm_time=$now, `status`=".SDS_SHIPPED.", admin_id='".$_SESSION['admin_id']."' WHERE delivery_id in ($ids)";
	$GLOBALS['db']->query($sql);
	$links[0]['text'] = $GLOBALS['_LANG']['delivery_record'];
	$links[0]['href'] = 'suppliers_delivery.php?act=list';
	sys_msg($GLOBALS['_LANG']['update_success'], 0, $links);
}

/*------------------------------------------------------ */
//-- 删除发货单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_order')
{
	/* 检查权限 */
	admin_priv('delivery_list');
	$delivery_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	/* 查找发货单*/
	$delivery = $suppliers_delivery->get_delivery_by_id($delivery_id);
	/* 如果发货单不存在，跳转到错误页面*/
	if(empty($delivery)){
		sys_msg($GLOBALS['_LANG']['delivery_not_exist']);
	}elseif($delivery['status'] == SDS_VERIFY){
		sys_msg($GLOBALS['_LANG']['delivery_already_verify']);
	}
	/* 删除发货单记录*/
	$suppliers_delivery->delete_delivery_by_id($delivery_id);
	
	$url = 'suppliers_delivery.php?act=delivery_query';
	ecs_header("Location: $url\n");
	exit;
}
/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
	/* 检查权限 */
	admin_priv('delivery_list');
	/* 批量删除*/
	if($_REQUEST['remove_invoice']){
		if(isset($_REQUEST['delivery_id']) && !empty($_REQUEST['delivery_id']) && intval($_REQUEST['delivery_id'])!=0){
			$suppliers_delivery->batch_delete_delivery_by_id(array_values($_REQUEST['delivery_id']));
		}
		$url = 'suppliers_delivery.php?act=list';
		ecs_header("Location: $url\n");
		exit;
	}
}
?>