define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xshop/hot/index' + location.search,
                    add_url: 'xshop/hot/add',
                    edit_url: 'xshop/hot/edit',
                    del_url: 'xshop/hot/del',
                    multi_url: 'xshop/hot/multi',
                    import_url: 'xshop/hot/import',
                    table: 'xshop_hot',
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
                        {field: 'id', title: __('Id'), operate: 'LIKE'},
                        {field: 'platform', title: __('Platform')},
                        {field: 'word', title: __('Word'), operate: 'LIKE'},
                        {field: 'hot_value', title: __('Hot_value')},
                        {field: 'first_time', title: __('First_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'duration', title: __('Duration')},
                        {field: 'top_ranking', title: __('Top_ranking')},
                        {field: 'date', title: __('Date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});