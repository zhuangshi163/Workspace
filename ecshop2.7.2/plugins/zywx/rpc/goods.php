<?php
define('IN_ECS', true);
include_once ('./includes/init.php');
include_once './includes/common.php';
include_once './module/goods.action.php';
$action = _getActionName();
$indexAction = new GoodsAction();
_checkAction($indexAction, $action);//验证action
$indexAction->$action();