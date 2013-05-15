<?php
/**
 * 供货商备货模块接口
 * @author jkdun
 *
 */
class SuppliersStock {
	/**
	 * @param array $con 数组条件格式: array('字段名1'=>'值','字段名2'=>'值')
	 * @return array 数组
	 */
	public function findStockByCondition($con){
		$where= 'where 1 ';
		foreach ($con as $k=>$v){
			$where .="and $k=$v";
		}
		
		/* 查询 */
		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('suppliers_stock_info') . " AS s " . $where." order by s.stock_id desc";
		$stock_list = $GLOBALS['db']->getAll($sql);
		return $stock_list;
	}
	
	/**
	 * @param int $stockId 备货单id
	 * @param int $status  要修改的备货单的状态
	 * @return array 数组
	 */
	function updateStockStatusByStockId($stockId,$status){
		$sql = "UPDATE ". $GLOBALS['ecs']->table('suppliers_stock_info') . " AS s SET s.status = ".$status." WHERE s.stock_id=".$stockId;
		$row = $GLOBALS['db']->query($sql);
	}

}

?>