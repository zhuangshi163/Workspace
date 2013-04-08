<?php
define('IN_ECS', true);
include_once ('./includes/init.php');
include_once './includes/common.php';
include_once './module/user.action.php';
$action = _getActionName();
$indexAction = new UserAction();
_checkAction($indexAction, $action);//验证action
$indexAction->$action();