<?php
	
define('IN_ECS', true);
include_once ('./includes/init.php');
include_once './includes/common.php';
include_once './module/ec.action.php';
$action = _getActionName();
$indexAction = new ec();
_checkAction($indexAction, $action);
$indexAction->$action();