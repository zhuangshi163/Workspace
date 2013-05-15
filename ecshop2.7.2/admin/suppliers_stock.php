<?php

/**
 * ECSHOP 订单管理
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: order.php 17157 2010-05-13 06:02:31Z yehuaixiao $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_goods.php');

/*------------------------------------------------------ */
//-- 备货单列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
	/* 检查权限 */
	admin_priv('stock_list');

	/* 模板赋值 */
	$smarty->assign('ur_here', $_LANG['02_order_list']);

	$smarty->assign('status_list', $_LANG['cs']);   // 订单状态

	$smarty->assign('os_unconfirmed',   OS_UNCONFIRMED);
	$smarty->assign('cs_await_pay',     CS_AWAIT_PAY);
	$smarty->assign('cs_await_ship',    CS_AWAIT_SHIP);
	$smarty->assign('full_page',        1);

	$stock_list = stock_list();
	$smarty->assign('stock_list',   $stock_list['stocks']);
	$smarty->assign('filter',       $stock_list['filter']);
	$smarty->assign('record_count', $stock_list['record_count']);
	$smarty->assign('page_count',   $stock_list['page_count']);
	$smarty->assign('sort_order_time', '<img src="images/sort_desc.gif">');

	/* 显示模板 */
	assign_query_info();
	$smarty->display('stock_list.htm');
}
else if ($_REQUEST['act'] == 'print')
{
	/* 检查权限 */
	admin_priv('stock_list');

	$stock_id = isset($_REQUEST['stock_id'])?intval($_REQUEST['stock_id']):0;
	if (empty($stock_id))
	{
		make_json_response('', 1, $_LANG['error_get_goods_info']);
	}
	$sql = "SELECT o.*, g.goods_thumb, g.goods_specification,g.goods_sn,g.goods_number AS storage, o.goods_name, IFNULL(b.brand_name, '') AS brand_name " .
			"FROM " . $ecs->table('suppliers_stock_goods') . " AS o ".
			"LEFT JOIN " . $ecs->table('goods') . " AS g ON o.goods_id = g.goods_id " .
			"LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
			"WHERE o.stock_id = '{$stock_id}' ";
	$row = $GLOBALS['db']->getAll($sql);
//	$goods_list = $db->query($sql);
	
	$smarty->assign('goods_list',   $row);
	/* 显示模板 */
	assign_query_info();
	$smarty->display('stock_goods_print.htm');
}


/*------------------------------------------------------ */
//-- 备货单排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
	/* 检查权限 */
	admin_priv('stock_list');
	$stock_list = stock_list();
	$smarty->assign('stock_list',   $stock_list['stocks']);
	$smarty->assign('filter',       $stock_list['filter']);
	$smarty->assign('record_count', $stock_list['record_count']);
	$smarty->assign('page_count',   $stock_list['page_count']);
	$sort_flag  = sort_flag($stock_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	make_json_result($smarty->fetch('stock_list.htm'), '', array('filter' => $stock_list['filter'], 'page_count' => $stock_list['page_count']));
}


/*------------------------------------------------------ */
//-- 获取订单商品信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_stock_goods_info')
{
    /* 取得订单商品 */
    $stock_id = isset($_REQUEST['stock_id'])?intval($_REQUEST['stock_id']):0;
    if (empty($stock_id))
    {
        make_json_response('', 1, $_LANG['error_get_goods_info']);
    }
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, g.goods_thumb, g.goods_sn,g.goods_number AS storage, o.goods_name, IFNULL(b.brand_name, '') AS brand_name " .
            "FROM " . $ecs->table('suppliers_stock_goods') . " AS o ".
            "LEFT JOIN " . $ecs->table('goods') . " AS g ON o.goods_id = g.goods_id " .
            "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE o.stock_id = '{$stock_id}' ";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        /* 虚拟商品支持 */
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($_LANG[$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);
        $_goods_thumb = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $_goods_thumb = (strpos($_goods_thumb, 'http://') === 0) ? $_goods_thumb : $ecs->url() . $_goods_thumb;
        $row['goods_thumb'] = $_goods_thumb;
        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
        $goods_list[] = $row;
    }
    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    $smarty->assign('goods_attr', $attr);
    $smarty->assign('goods_list', $goods_list);
    $str = $smarty->fetch('suppliers_stock_goods_info.htm');
    $goods[] = array('stock_id' => $stock_id, 'str' => $str);
    make_json_result($goods);
}




/**
 *  获取备货信息列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function stock_list()
{
	$result = get_filter();
	if ($result === false)
	{
		/* 过滤信息 */

		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

		$filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
		$filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

		$where = 'WHERE 1 ';

		if ($filter['start_time'])
		{
			$where .= " AND o.add_time >= '$filter[start_time]'";
		}
		if ($filter['end_time'])
		{
			$where .= " AND o.add_time <= '$filter[end_time]'";
		}

		/* 分页大小 */
		$filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

		if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
		{
			$filter['page_size'] = intval($_REQUEST['page_size']);
		}
		elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
		{
			$filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
		}
		else
		{
			$filter['page_size'] = 15;
		}

			$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('suppliers_stock_info') . " AS s ". $where;

		$filter['record_count']   = $GLOBALS['db']->getOne($sql);
		$filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

		/* 查询 */
		$sql = "SELECT s.stock_id, s.stock_sn, u.user_name, s.add_time,s.status" .
				" FROM " . $GLOBALS['ecs']->table('suppliers_stock_info') . " AS s ,". $GLOBALS['ecs']->table('admin_user') . " AS u " . $where .
				" and u.user_id=s.admin_id and s.suppliers_id=".$_SESSION['suppliers_id']." ORDER BY $filter[sort_by] $filter[sort_order] ".
				" LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

		set_filter($filter, $sql);
	}
	else
	{
		$sql    = $result['sql'];
		$filter = $result['filter'];
	}

	$row = $GLOBALS['db']->getAll($sql);

	/* 格式话数据 */
	foreach ($row AS $key => $value)
	{
		$row[$key]['formated_order_amount'] = price_format($value['order_amount']);
		$row[$key]['formated_money_paid'] = price_format($value['money_paid']);
		$row[$key]['formated_total_fee'] = price_format($value['total_fee']);
		$row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
		if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED)
		{
			/* 如果该订单为无效或取消则显示删除链接 */
			$row[$key]['can_remove'] = 1;
		}
		else
		{
			$row[$key]['can_remove'] = 0;
		}
	}
	$arr = array('stocks' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

	return $arr;
}

/**
 * 取得订单商品
 * @param   array     $order  订单数组
 * @return array
 */
function get_order_goods($order)
{
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn " .
            "FROM " . $GLOBALS['ecs']->table('split_order_goods') . " AS o ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON o.product_id = p.product_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON o.goods_id = g.goods_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE o.order_id = '$order[order_id]' ";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy')
        {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods_list($row['goods_id']);
        }

        //处理货品id
        $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

        $goods_list[] = $row;
    }

    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    return array('goods_list' => $goods_list, 'attr' => $attr);
}

function updateStockStatusByStockId($stockId,$status){
	$sql = "UPDATE ". $GLOBALS['ecs']->table('suppliers_stock_info') . " AS s SET s.status = ".$status." WHERE s.stock_id=".$stockId;
	$row = $GLOBALS['db']->query($sql);
}



/**
 * 判断订单是否已发货（含部分发货）
 * @param   int     $order_id  订单 id
 * @return  int     1，已发货；0，未发货
 */
function order_deliveryed($order_id)
{
    $return_res = 0;

    if (empty($order_id))
    {
        return $return_res;
    }

    $sql = 'SELECT COUNT(delivery_id)
            FROM ' . $GLOBALS['ecs']->table('delivery_order') . '
            WHERE order_id = \''. $order_id . '\'
            AND status = 0';
    $sum = $GLOBALS['db']->getOne($sql);

    if ($sum)
    {
        $return_res = 1;
    }

    return $return_res;
}

/**
 * 获取站点根目录网址
 *
 * @access  private
 * @return  Bool
 */
function get_site_root_url()
{
    return 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/' . ADMIN_PATH . '/order.php', '', PHP_SELF);

}
?>