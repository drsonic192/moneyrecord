<?php

use think\Route;

Route::group('admin', function () {
    // 企业管理路由
    Route::get('Company/company/index', 'admin/Company.company/index');
    Route::get('Company/company/add', 'admin/Company.company/add');
    Route::get('Company/company/edit', 'admin/Company.company/edit');
    Route::get('Company/company/del', 'admin/Company.company/del');
    
    // 企业员工管理路由
    Route::rule('company/user/selectpage', 'admin/Company.user/selectpage', 'GET|POST', ['deny_ext' => false]);
    Route::rule('company/user/index', 'admin/Company.user/index', 'GET|POST');
    Route::rule('company/user/add', 'admin/Company.user/add', 'GET|POST');
    Route::rule('company/user/edit', 'admin/Company.user/edit', 'GET|POST');
    Route::rule('company/user/del', 'admin/Company.user/del', 'GET|POST');
});

return [
    '__alias__'   => [],
    '__pattern__' => [],
    '__domain__'  => [],
];
