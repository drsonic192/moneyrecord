define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/user/index',
                    add_url: 'company/user/add',
                    edit_url: 'company/user/edit',
                    del_url: 'company/user/del',
                    multi_url: 'company/user/multi',
                    table: 'company_user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'company.company_name', title: __('Company_id')},
                        {field: 'user.nickname', title: __('User_id')},
                        {field: 'realname', title: __('Realname')},
                        {field: 'position', title: __('Position')},
                        {field: 'role', title: __('Role'), searchList: {"admin":__('Role admin'),"staff":__('Role staff')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Normal'),"hidden":__('Hidden')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        updateBoundInfo: function(responseData) {
            if (!responseData || !responseData.list) {
                return;
            }
            
            var $results = $('.sp_results li');
            $results.each(function() {
                var $li = $(this);
                var pkey = $li.attr('pkey');
                
                var item = responseData.list.find(function(x) {
                    return x.id == pkey;
                });
                
                // 移除已有的提示
                $li.find('.bound-info').remove();
                
                // 如果有绑定信息，添加红色提示
                if (item && item.bound_info) {
                    $li.append('<span class="bound-info" style="color: red; font-size: 12px !important; ' + 
                        'margin-left: 5px; display: inline-block; vertical-align: middle; line-height: 1;">' + 
                        ' 已绑定' + item.bound_info + '</span>');
                }
            });
        },
        initSelectpage: function() {
            // 存储最新的响应数据
            var latestResponse = null;
            var self = this;  // 保存 Controller 的引用
            
            // 监听 Ajax 请求完成事件
            $(document).off('ajaxComplete');
            $(document).on('ajaxComplete', function(event, xhr, settings) {
                if (settings.url.indexOf('selectpage') > -1) {
                    latestResponse = xhr.responseJSON;
                    
                    // 在 Ajax 完成后立即处理红色提示
                    setTimeout(function() {
                        self.updateBoundInfo(latestResponse);
                    }, 100);
                }
            });
            
            // 先解绑之前的事件处理器
            $(document).off('mousedown', '.sp_container');
            
            // 监听下拉框点击事件（用于初始加载）
            $(document).on('mousedown', '.sp_container', function() {
                // 获取当前记录ID（添加防护检查）
                var currentId = '';
                try {
                    currentId = (Config && Config.row && Config.row.id) ? Config.row.id : '';
                } catch (e) {
                    console.log('No current record ID available');
                }
                
                // 修改 selectpage 的 data-url
                var $input = $('#c-user_id');
                var sourceUrl = $input.data('source');
                if (currentId) {  // 只在有 currentId 时添加参数
                    if (sourceUrl.indexOf('?') > -1) {
                        sourceUrl += '&current_id=' + currentId;
                    } else {
                        sourceUrl += '?current_id=' + currentId;
                    }
                    $input.data('source', sourceUrl);
                }
            });
            
            // 监听选择事件
            $(document).off('sp.change', '#c-user_id');
            $(document).on('sp.change', '#c-user_id', function(e, data) {
                if (data) {
                    // 移除输入框中的红色提示
                    var $input = $(this).siblings('input[type="text"]');
                    $input.val(data.nickname);
                }
            });
        },
        add: function () {
            Controller.api.bindevent();
            Controller.initSelectpage();
            
            // 标记是否正在提交
            var isSubmitting = false;
            
            // 监听表单提交
            $('form[role=form]').on('valid.form', function(e) {
                // 如果正在提交，则返回
                if (isSubmitting) {
                    return false;
                }
                
                var currentUserId = $('#c-user_id').val();
                
                // 阻止表单默认提交
                e.preventDefault();
                
                // 检查用户绑定状态
                $.ajax({
                    url: 'company/user/checkUserBinding',
                    type: 'POST',
                    data: {
                        user_id: currentUserId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.code === 1) {
                            // 用户已绑定其他企业员工
                            Layer.confirm(
                                '该用户已绑定其他企业员工，是否替换用户的绑定？',
                                {icon: 3, title: __('Warning')},
                                function(index) {
                                    Layer.close(index);
                                    // 设置正在提交标记
                                    isSubmitting = true;
                                    // 提交表单，带上替换标记
                                    $('<input>', {
                                        type: 'hidden',
                                        name: 'replace_binding',
                                        value: '1'
                                    }).appendTo($('form[role=form]'));
                                    $('form[role=form]').trigger('submit');
                                },
                                function(index) {
                                    Layer.close(index);
                                    return false;
                                }
                            );
                        } else {
                            // 设置正在提交标记
                            isSubmitting = true;
                            // 用户未绑定，直接提交
                            $('form[role=form]').trigger('submit');
                        }
                    }
                });
                
                return false;
            });
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.initSelectpage();
            
            // 保存初始用户ID（添加防护检查）
            var originalUserId = $('#c-user_id').val();
            var currentId = '';
            try {
                currentId = (Config && Config.row && Config.row.id) ? Config.row.id : '';
            } catch (e) {
                console.log('No current record ID available');
            }
            
            // 标记是否正在提交
            var isSubmitting = false;
            
            // 监听表单提交
            $('form[role=form]').on('valid.form', function(e) {
                // 如果正在提交，则返回
                if (isSubmitting) {
                    return false;
                }
                
                var currentUserId = $('#c-user_id').val();
                
                // 如果用户ID没有改变，直接提交
                if (currentUserId == originalUserId) {
                    return true;
                }
                
                // 阻止表单默认提交
                e.preventDefault();
                
                // 检查用户绑定状态
                $.ajax({
                    url: 'company/user/checkUserBinding',
                    type: 'POST',
                    data: {
                        user_id: currentUserId,
                        current_id: currentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.code === 1) {
                            // 用户已绑定其他企业员工
                            Layer.confirm(
                                '该用户已绑定其他企业员工，是否替换用户的绑定？',
                                {icon: 3, title: __('Warning')},
                                function(index) {
                                    Layer.close(index);
                                    // 设置正在提交标记
                                    isSubmitting = true;
                                    // 提交表单，带上替换标记
                                    $('<input>', {
                                        type: 'hidden',
                                        name: 'replace_binding',
                                        value: '1'
                                    }).appendTo($('form[role=form]'));
                                    $('form[role=form]').trigger('submit');
                                },
                                function(index) {
                                    Layer.close(index);
                                    return false;
                                }
                            );
                        } else {
                            // 设置正在提交标记
                            isSubmitting = true;
                            // 用户未绑定，直接提交
                            $('form[role=form]').trigger('submit');
                        }
                    }
                });
                
                return false;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
}); 