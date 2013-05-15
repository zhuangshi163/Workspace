<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$act = isset($_GET['act'])?$_GET['act']:'list';
require_once 'includes/module/SuppliersSort.php';
$suppliersSort = new SuppliersSort();
//分拣列表
if ($act == 'list'){
	require_once 'includes/module/SuppliersStock.php';
	$suppliersStock = new SuppliersStock();
	$stockList = $suppliersStock ->findStockByCondition(array('status'=>0));
	//选择了备货单或填写了备货单后查找
	if($_GET['suppliers_stock_id']|is_numeric($_GET['keyword'])){
		/**
		 * 调用判断备货单是否分拣接口
		 */

		$stockId = $_GET['suppliers_stock_id'] ? $_GET['suppliers_stock_id']:$_GET['keyword'];
		$suppliersSort ->insertSort($stockId);
		$sortList = $suppliersSort ->findSortByStockId($stockId);
			
		//print_r($sortList);die;
		$smarty->assign('stock_id',$stockId);
		$smarty->assign('sort_list',$sortList);
	}
	
	$smarty->assign('suppliers_stock',$stockList);
	//print_r($stockList);die;
	/**
	 * 访问位置显示
	 */
	$smarty->display('suppliers_sort_list.htm');
}

//分箱核对
elseif ($act == 'check'&& $sortId=$_GET['sort_id']){
	$sortGoods = $suppliersSort ->getCheckBySortId($sortId);

	$smarty->assign('suppliers_stock_id',$_GET['stock_id']);
	$smarty->assign('sort_id',$sortId);
	$smarty->assign('sortGoods',$sortGoods);
	$smarty->display('suppliers_sort_check.htm');
}

//保存分箱信息
elseif ($act == 'check_submit'){
	//print_r($_GET['sGoods']);die;
	$sortId = $_GET['sort_id'];
	$stockId = $_GET['suppliers_stock_id'];
	$suppliersSort ->saveSortGoods($_GET['sGoods']);
	
	//更新备货单状态（先判断是否是全部门店已经分拣然后再更新）
	//echo $data['sort_id'];die;
	$suppliersSort->updateSortStatusBySortId($sortId, 1);
	
	//如果所有的门店已经分拣完成则更新其对应备货单状态为已经分拣与向发货单表中插入数据
	require_once 'includes/module/SuppliersDelivery.php';
	$suppliersDelivery = new SuppliersDelivery();
	$suppliersDelivery ->innsert_delivery($sortId);//向发货单表中插入数据
	
	$stockList = $suppliersSort ->findSortByStockId($stockId);
	$status = true;
	foreach ($stockList as $v){
		if ($v['status']== 0){
			$status = false;
			break;
		}
	}
	if ($status==true){
		require_once 'includes/module/SuppliersStock.php';
		$suppliersStock = new SuppliersStock();
		$suppliersStock ->updateStockStatusByStockId($stockId, 2);//更新备货单状态为已分拣
		
		header("location: suppliers_delivery.php?act=list");
		exit();
	}
	
	header("location: suppliers_sort.php?suppliers_stock_id={$stockId}");
}

//查看
elseif ($act == 'detail'&& $sortId=$_GET['sort_id']){
	$sortGoods = $suppliersSort ->getCheckBySortId($sortId);
	
	$smarty->assign('suppliers_stock_id',$_GET['stock_id']);
	$smarty->assign('sortGoods',$sortGoods);
	$smarty->display('suppliers_sort_detail.htm');
}
//打印
elseif ($act == 'print'&& $sortId=$_GET['sort_id']){
	$sortGoods = $suppliersSort ->getCheckBySortId($sortId);

	$smarty->assign('suppliers_stock_id',$_GET['stock_id']);
	$smarty->assign('sortGoods',$sortGoods);
	//$smarty->template_dir = '../' . DATA_DIR;
	$smarty->display('suppliers_sort_print.htm');
}
?>