<?php
/**
 * @todo常用函数
 * @author gaoruihua
 */
//获取action
function _get($name) {
    return @$_GET[$name] ? $_GET[$name] : @$_POST[$name];
}

function _getActionName() {
    $action = _get(ACTION);
    if (!$action){
   		$action = _get(OTHERACTION);
    }
    $action or $action = 'index';
    return $action;
}
//验证action是否在obj类中存在.
function _checkAction(&$obj, $action) {
    if (!method_exists($obj, $action)) {
        die ("action not find");
    }
}