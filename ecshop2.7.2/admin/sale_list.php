<?php

/**
 * ECSHOP 销售明细列表程序
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: sale_list.php 17063 2010-03-25 06:35:46Z liuhui $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/statistic.php');
$smarty->assign('lang', $_LANG);

//判断是否为供货商管理员登录
if(empty($_SESSION['suppliers_id'])||$_SESSION['suppliers_id']==0){
	$is_suppliers_admin=0;
}else{
	$is_suppliers_admin=1;
}
$GLOBALS['smarty']->assign ( 'is_suppliers_admin', $is_suppliers_admin );

if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    /* 检查权限 */
    check_authz_json('sale_order_stats');
    if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
    }
    /*------------------------------------------------------ */
    //--Excel文件下载
    /*------------------------------------------------------ */
    if ($_REQUEST['act'] == 'download')
    {
        $store_id=isset($_REQUEST['store_id'])?$_REQUEST['store_id']:"";
		$store_name="";
		if(empty($store_id)){
			$store_name="全部门店";
		}else{
			$sql="select store_remark from ". $GLOBALS ['ecs']->table ( 'store' ) ." where store_id='$store_id'";
			$store_name=$GLOBALS['db']->getOne($sql);
			if(empty($store_name)){
				show_message("系统繁忙!");
			} 
		}
		$file_name =str_replace(" ","_" ,$_REQUEST ['start_date']) . '至' . str_replace(" ","_" ,$_REQUEST ['end_date'] ). '_' .$store_name.'_'. $_LANG [$_REQUEST ['order_type']] . '_sale';
		$goods_sales_list = get_sale_list ( false ,$is_suppliers_admin);
		header ( "Content-type: application/vnd.ms-excel; charset=utf-8" );
		header ( "Content-Disposition: attachment; filename=".ecs_iconv(EC_CHARSET, "GB2312", $file_name).".xls" );
		
		$GLOBALS['smarty']->assign('start_date', $_REQUEST['start_date']);
		$GLOBALS['smarty']->assign('end_date', $_REQUEST['end_date']);
		$GLOBALS['smarty']->assign('goods_sales_list', $goods_sales_list['sale_list_data']);
		$GLOBALS['smarty']->assign('total_number', $goods_sales_list['total_number']);
		$GLOBALS['smarty']->assign('total_wholesale_price', $goods_sales_list['total_wholesale_price']);
		$GLOBALS['smarty']->assign('total_price', $goods_sales_list['total_price']);
		
		$table = $GLOBALS['smarty']->fetch('excel_sale_list.htm');
		echo ecs_iconv(EC_CHARSET, "GB2312", $table);
		exit ();
    }
    $sale_list_data = get_sale_list(true, $is_suppliers_admin);
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);
    $smarty->assign ( 'total_wholesale_price', $sale_list_data ['total_wholesale_price'] );
    $smarty->assign ( 'total_price', $sale_list_data ['total_price'] );
    $smarty->assign ('total_number', $sale_list_data ['total_number']);

    make_json_result($smarty->fetch('sale_list.htm'), '', array('filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']));
}
/*------------------------------------------------------ */
//--商品明细列表
/*------------------------------------------------------ */
else
{
    /* 权限判断 */
    admin_priv('sale_order_stats');
    /* 时间参数 */
    if (!isset($_REQUEST['start_date']))
    {
        $start_date = local_strtotime('-1 days');
    }
    if (!isset($_REQUEST['end_date']))
    {
        $end_date = local_strtotime('now');
    }
    
    $sale_list_data = get_sale_list(true, $is_suppliers_admin);
    $order_type = array ('finished' => $_LANG ['finished'], 
    		'await_ship' => $_LANG ['await_ship'], 
    		'await_pay' => $_LANG ['await_pay'], 
    		'unconfirmed' => $_LANG ['unconfirmed'], 
    		'unprocessed' => $_LANG ['unprocessed'], 
    		'unpay_unship' => $_LANG ['unpay_unship'], 
    		'shipped' => $_LANG ['shipped'] );
    $cat_id = empty ( $_REQUEST ['cat_id'] ) ? 0 : intval ( $_REQUEST ['cat_id'] );
    
    /* 供货商 */
    $suppliers_id = isset ( $_REQUEST ['suppliers_id'] ) ? (empty ( $_REQUEST ['suppliers_id'] ) ? '' : trim ( $_REQUEST ['suppliers_id'] )) : '';
    $suppliers_list_name = suppliers_list_name ();
    
    /* 赋值到模板 */
    $smarty->assign('filter',       $sale_list_data['filter']);
    $smarty->assign('record_count', $sale_list_data['record_count']);
    $smarty->assign('page_count',   $sale_list_data['page_count']);
    $smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
    $smarty->assign('ur_here',          $_LANG['sell_stats']);
    $smarty->assign('full_page',        1);
    $smarty->assign ( 'start_date', local_date ( 'Y-m-d H:i:s', $start_date ) );
	$smarty->assign ( 'end_date', local_date ( 'Y-m-d H:i:s', $end_date ) );
    $smarty->assign('ur_here',      $_LANG['sale_list']);
    $smarty->assign('cfg_lang',     $_CFG['lang']);
    $smarty->assign('action_link',  array('text' => $_LANG['down_sales'],'href'=>'#download'));
    
    $smarty->assign ( 'store_list', get_store_list () );
    $smarty->assign ( 'order_type',  $order_type);
    $smarty->assign ( 'cat_list', cat_list ( 0, $cat_id ) );
    $smarty->assign ( 'suppliers_list_name', $suppliers_list_name );
    $smarty->assign ( 'total_wholesale_price', $sale_list_data ['total_wholesale_price'] );
    $smarty->assign ( 'total_price', $sale_list_data ['total_price'] );
    $smarty->assign ('total_number', $sale_list_data ['total_number']);

    /* 显示页面 */
	assign_query_info();
    $smarty->display('sale_list.htm');
}
/*------------------------------------------------------ */
//--获取销售明细需要的函数
/*------------------------------------------------------ */
function get_sale_list($is_pagination = true,$is_suppliers_admin=0) {
	
	$day = getdate ();
	$today = local_mktime ( 23, 59, 59, $day ['mon'], $day ['mday'], $day ['year'] );
	/* 时间参数 */
	$filter ['start_date'] = empty ( $_REQUEST ['start_date'] ) ? local_strtotime ( 'today midnight' ) : local_strtotime ( $_REQUEST ['start_date'] );
	$filter ['end_date'] = empty ( $_REQUEST ['end_date'] ) ? local_strtotime ( 'now' ) : local_strtotime ( $_REQUEST ['end_date'] );
	/* 查询订单状态条件 */
	$filter ['order_type'] = empty ( $_REQUEST ['order_type'] ) ? 'unprocessed' : $_REQUEST ['order_type'];
	/* 查询门店条件 */
	$filter ['store_id'] = empty ( $_REQUEST ['store_id'] ) ? '' : $_REQUEST ['store_id'];
	$good_where = " WHERE 1 " . order_query_sql ( $filter ['order_type'], 'oi.' )
					. " AND oi.add_time >= '" . $filter ['start_date'] . "' AND oi.add_time < '"
					. ($filter ['end_date'] + 86400) . "'";
	if (! empty ( $filter ['store_id'] )) {
		$good_where .= " AND oi.store_id = '" . $filter ['store_id'] . "'";
	}
	
	/* 商品分类条件 */
	$filter ['cat_id'] = empty ( $_REQUEST ['cat_id'] ) ? 0 : intval ( $_REQUEST ['cat_id'] );
	/* 推荐类型条件 */
	$filter ['intro_type'] = empty ( $_REQUEST ['intro_type'] ) ? '' : trim ( $_REQUEST ['intro_type'] );
	$filter ['is_promote'] = empty ( $_REQUEST ['is_promote'] ) ? 0 : intval ( $_REQUEST ['is_promote'] );
	/* 商品品牌条件 */
	$filter ['brand_id'] = empty ( $_REQUEST ['brand_id'] ) ? 0 : intval ( $_REQUEST ['brand_id'] );
	/* 关键字查询 */
	$filter ['keyword'] = empty ( $_REQUEST ['keyword'] ) ? '' : trim ( $_REQUEST ['keyword'] );
	/* 供货商权限处理 */
	if ($is_suppliers_admin==0) {
		$filter ['suppliers_id'] = isset ( $_REQUEST ['suppliers_id'] ) ? (empty ( $_REQUEST ['suppliers_id'] ) ? '' : trim ( $_REQUEST ['suppliers_id'] )) : '';
	} else {
		$filter ['suppliers_id'] = $_SESSION ['suppliers_id'];
	}
	/* 上下架条件 */
	$filter ['is_on_sale'] = isset ( $_REQUEST ['is_on_sale'] ) ? ((empty ( $_REQUEST ['is_on_sale'] ) && $_REQUEST ['is_on_sale'] === 0) ? '' : trim ( $_REQUEST ['is_on_sale'] )) : '';
	/* 关键字条件 */
	if (isset ( $_REQUEST ['is_ajax'] ) && $_REQUEST ['is_ajax'] == 1) {
		$filter ['keyword'] = json_str_iconv ( $filter ['keyword'] );
	}
	/* 排序条件 */
	$filter ['sort_by'] = empty ( $_REQUEST ['sort_by'] ) ? 'cat.cat_name DESC,goods_num DESC,og.goods_name' : trim ( $_REQUEST ['sort_by'] );
	/* 排序方式 */
	$filter ['sort_order'] = empty ( $_REQUEST ['sort_order'] ) ? 'ASC' : trim ( $_REQUEST ['sort_order'] );
	/* 扩展条件 */
	$filter ['extension_code'] = empty ( $_REQUEST ['extension_code'] ) ? '' : trim ( $_REQUEST ['extension_code'] );
	
	/**
	 * 查询语句
	 * @var string
	 */
	$good_where .= $filter ['cat_id'] > 0 ? " AND " . get_children ( $filter ['cat_id'] ) : '';
	
	/* 推荐类型 */
	switch ($filter ['intro_type']) {
		case 'is_best' :
			$good_where .= " AND g.is_best=1";
			break;
		case 'is_hot' :
			$good_where .= ' AND g.is_hot=1';
			break;
		case 'is_new' :
			$good_where .= ' AND g.is_new=1';
			break;
		case 'is_promote' :
			$good_where .= " AND g.is_promote = 1 AND g.promote_price > 0 AND g.promote_start_date <= '$today' AND g.promote_end_date >= '$today'";
			break;
		case 'all_type' :
			$good_where .= " AND (g.is_best=1 OR g.is_hot=1 OR g.is_new=1 OR (g.is_promote = 1 AND g.promote_price > 0 AND g.promote_start_date <= '" . $today . "' AND g.promote_end_date >= '" . $today . "'))";
	}
	
	/* 品牌 */
	if ($filter ['brand_id']) {
		$good_where .= " AND brand.brand_id='$filter[brand_id]'";
	}
	
	/* 扩展 */
	if ($filter ['extension_code']) {
		$good_where .= " AND g.extension_code='$filter[extension_code]'";
	}
	
	/* 关键字 */
	if (! empty ( $filter ['keyword'] )) {
		$good_where .= " AND (g.goods_sn LIKE '%" . mysql_like_quote ( $filter ['keyword'] ) . "%' OR g.goods_name LIKE '%" . mysql_like_quote ( $filter ['keyword'] ) . "%')";
	}
	
	/* 上架 */
	if ($filter ['is_on_sale'] !== '') {
		$good_where .= " AND (g.is_on_sale = '" . $filter ['is_on_sale'] . "')";
	}
	
	/* 供货商 */
	if (! empty ( $filter ['suppliers_id'] )) {
		$good_where .= " AND (g.suppliers_id = '" . $filter ['suppliers_id'] . "')";
	}
	
	/* 记录总数 */
	$sql="SELECT COUNT(*) FROM (SELECT og.goods_id FROM " . $GLOBALS['ecs']->table('order_goods') ." AS og ". 
  		" inner join " . $GLOBALS['ecs']->table('order_info') ." AS oi on og.order_id = oi.order_id"
  		." inner join " . $GLOBALS['ecs']->table('goods') ." AS g on g.goods_id = og.goods_id "
  		." left join " . $GLOBALS['ecs']->table('category') ." AS cat on g.cat_id = cat.cat_id" 
  		." left join " . $GLOBALS['ecs']->table('brand') ." AS brand on g.brand_id = brand.brand_id" 
  		." left join " . $GLOBALS['ecs']->table('store') ." AS store on oi.store_id = store.store_id"  
  		." left join " . $GLOBALS['ecs']->table('suppliers') ." AS su on g.suppliers_id = su.suppliers_id ".$good_where 
		." GROUP BY og.goods_id,og.goods_price, og.wholesale_price, store.store_id ,og.goods_name ) AS t";
	$filter ['record_count'] = $GLOBALS ['db']->getOne ( $sql );
	
	//分页大小
	$filter = page_and_size ( $filter );
	
	$sql="SELECT og.goods_id, g.cat_id, cat.cat_name, g.brand_id,brand.brand_name, su.suppliers_id, su.suppliers_name, "
  		." og.goods_sn, og.goods_name, oi.store_id, store.store_remark, g.goods_specification , g.goods_number ,"
  		." og.wholesale_price ,og.goods_price as sale_price, oi.order_status, SUM(og.goods_number) AS goods_num," 
  		." SUM(og.goods_number * og.goods_price) AS turnover ,SUM(og.goods_number * og.wholesale_price) AS wholesale_turnover"  
		." FROM " . $GLOBALS['ecs']->table('order_goods') ." AS og "
  		." inner join " . $GLOBALS['ecs']->table('order_info') ." AS oi on og.order_id = oi.order_id"
  		." inner join " . $GLOBALS['ecs']->table('goods') ." AS g on g.goods_id = og.goods_id" 
  		." left join " . $GLOBALS['ecs']->table('category') ." AS cat on g.cat_id = cat.cat_id" 
  		." left join " . $GLOBALS['ecs']->table('brand') ." AS brand on g.brand_id = brand.brand_id" 
  		." left join " . $GLOBALS['ecs']->table('store') ." AS store on oi.store_id = store.store_id"  
  		." left join " . $GLOBALS['ecs']->table('suppliers') ." AS su on g.suppliers_id = su.suppliers_id "
  		.$good_where . " GROUP BY og.goods_id,og.goods_price, og.wholesale_price, store.store_id ,og.goods_name "
  		." ORDER BY $filter[sort_by] $filter[sort_order]";

	if ($is_pagination) {
		$sql .= " LIMIT " . $filter ['start'] . ', ' . $filter ['page_size'];
	}
	
	$filter ['keyword'] = stripslashes ( $filter ['keyword'] );
	
	$sale_list_data= $GLOBALS ['db']->getAll( $sql );
	
	$total_price=0;
	$total_wholesale_price=0;
	$total_number=0;
	foreach ( $sale_list_data as $key => $item ) {
		/* 计算各个商品总销售数量 */
		$total_number += $sale_list_data [$key] ['goods_num'];
		$total_price +=$sale_list_data [$key]['turnover'];
		$total_wholesale_price +=$sale_list_data [$key]['wholesale_turnover'];
		$sale_list_data [$key]['sale_price']=price_format($sale_list_data [$key]['sale_price']);
		$sale_list_data [$key]['wholesale_price']=price_format($sale_list_data [$key]['wholesale_price']);
	}
	
	$arr = array ('sale_list_data' => $sale_list_data, 
		'filter' => $filter, 
		'page_count' => $filter ['page_count'], 
		'record_count' => $filter ['record_count'], 
		'total_price' => price_format($total_price),
		'total_wholesale_price' => price_format($total_wholesale_price),
		'total_number' => $total_number
	);
	return $arr;	
}

/**
 * 取得门店列表
 * @return array 门店列表 id => name
 */
function get_store_list()
{
	$sql = 'SELECT store_id, store_remark FROM ' . $GLOBALS['ecs']->table('store') . " WHERE restraint=0 ORDER BY store_id DESC ";
	$res = $GLOBALS['db']->getAll($sql);

	$store_list = array();
	foreach ($res AS $row)
	{
		$store_list[$row['store_id']] = addslashes($row['store_remark']);
	}

	return $store_list;
}
?>