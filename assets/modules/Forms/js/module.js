var sanitize = function(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/>/g, '&gt;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;');
};
var GridHelper = {
    translate: function(key, def = '') {
        return typeof lang[key] !== undefined ? lang[key] : def;
    },
    handleAjaxError: function (xhr) {
        var message = xhr.status === 200 ? this.translate('response_error') : this.translate('server_error') + ' ' + xhr.status + ' ' + xhr.statusText;
        $.messager.alert(this.translate('error'), message, 'error');
    },
    deleteRow: function (index) {
        $('#formsgrid').datagrid('checkRow', index);
        this.deleteAll();
    },
    getSelected: function() {
        var ids = [];
        var rows = $('#formsgrid').datagrid('getChecked');
        if (rows.length) {
            $.each(rows, function(i, row) {
                ids.push(row.id);
            });
        }

        return ids;
    },
    deleteAll: function() {
        var ids = this.getSelected();
        var that = this;
        $.messager.confirm(that.translate('delete'), that.translate('sure_to_delete'),function(r){
            if (r && ids.length > 0){
                $.post(
                    Config.url,
                    {
                        mode: 'remove',
                        ids:ids
                    },
                    function(response) {
                        if(response.success) {
                            $('#formsgrid').datagrid('reload');
                        } else {
                            $.messager.alert(that.translate('error'), that.translate('delete_error'), 'error');
                        }
                    },'json'
                ).fail(GridHelper.handleAjaxError);
            } else {
                $('#formsgrid').datagrid('uncheckAll');
            }
        });
    },
    view: function(id) {
        var that = this;
        if (id) {
            $.post(
                Config.url,
                {
                    mode: 'view',
                    id: id
                },
                function (response) {
                    if (response.success) {
                        var formdata = response.formdata;
                        var out = '<div class="formrow" style="color:green;"><i>' + that.translate('date') + ' ' + formdata.createdon + ' ' + that.translate('from') + ' IP: ' + formdata.ip + '</i></div>';
                        delete(formdata.createdon);
                        delete(formdata.ip);
                        var mainFields = {
                            name: that.translate('sender'),
                            email: 'E-mail',
                            phone: that.translate('phone')
                        };
                        for (key in mainFields) {
                            if (typeof formdata[key] !== 'undefined') {
                                out += '<div class="formrow"><b>' + mainFields[key] + ': </b>' + sanitize(formdata[key]).replace(/([^>])\n/g, '$1<br/>') + '</div>';
                                delete(formdata[key]);
                            }
                        }
                        out += '<hr>';
                        for (key in formdata) {
                            out += '<div class="formrow"><b>' + key + ': </b>' + sanitize(formdata[key]).replace(/([^>])\n/g, '$1<br/>') + '</div>';
                        }
                        $('<div class="viewwnd">' + out + '</div>').window({
                            title: that.translate('view') + ' ' + id,
                            width: 600,
                            height: 400,
                            minimizable: false
                        });
                    } else {
                        $.messager.alert(that.translate('error'), that.translate('view_error'), 'error');
                    }
                }, 'json'
            ).fail(GridHelper.handleAjaxError);
        }
    },
    initGrid: function () {
        $('#formType').combobox({
            valueField:'type',
            textField:'type',
            queryParams: {
                mode: 'getFormTypes'
            },
            icons:[{
                iconCls:'combo-clear fa fa-remove fa-lg',
                handler:function(e){
                    $(e.data.target).combobox('clear');
                }
            }]
        });
        $('#formBegin,#formEnd').datebox({
            icons:[{
                iconCls:'combo-clear fa fa-remove fa-lg',
                handler:function(e){
                    $(e.data.target).datebox('clear');
                }
            }],
            formatter: function(date){
                var y = date.getFullYear();
                var m = date.getMonth()+1;
                var d = date.getDate();
                return (d<10?('0'+d):d)+'-'+(m<10?('0'+m):m)+'-'+y;
            },
            parser: function(s){
                if (!s) return new Date();
                var ss = (s.split('-'));
                var y = parseInt(ss[2],10);
                var m = parseInt(ss[1],10);
                var d = parseInt(ss[0],10);
                if (!isNaN(y) && !isNaN(m) && !isNaN(d)){
                    return new Date(y,m-1,d);
                } else {
                    return new Date();
                }
            }
        });
        $('#formBegin').datebox('textbox').click(function(e){
            $('#formBegin').datebox('showPanel');
        });
        $('#formEnd').datebox('textbox').click(function(e){
            $('#formEnd').datebox('showPanel');
        });
        $('#searchPanel').on('click', '#searchBtn', function(e){
            e.preventDefault();
            $('#formsgrid').datagrid('load', {
                type: $('#formType').combobox('getValue'),
                begin: $('#formBegin').datebox('getValue'),
                end: $('#formEnd').datebox('getValue')
            })
        }).on('click', '#exportBtn', function(e){
            e.preventDefault();
            GridHelper.startExport({
                type: $('#formType').combobox('getValue'),
                begin: $('#formBegin').datebox('getValue'),
                end: $('#formEnd').datebox('getValue')
            })
        });
        $('#formsgrid').datagrid({
            url: Config.url,
            fitColumns: true,
            pagination: true,
            pageSize: 50,
            pageList: [50, 100, 150, 200],
            idField: 'id',
            singleSelect: true,
            sortName: 'id',
            sortOrder: 'DESC',
            striped: true,
            checkOnSelect: false,
            selectOnCheck: false,
            columns: columns,
            onBeforeLoad: function() {
                $(this).datagrid('clearChecked');
                $('.btn-extra',$(this).datagrid('getPanel')).parent().parent().hide();
                $('#formType').combobox('reload', Config.url);
            },
            onDestroy: function () {
                $(this).datagrid('reload');
            },
            onLoadSuccess: function() {
                $(this).datagrid('resize');
            },
            onSelect: function (index) {
                $(this).datagrid('unselectRow', index);
            },
            onCheck: function(index) {
                $(this).datagrid('unselectRow', index);
                $('.btn-extra',$(this).datagrid('getPanel')).parent().parent().show();
            },
            onUncheck: function() {
                var rows = $(this).datagrid('getChecked');
                if (!rows.length) $('.btn-extra',$(this).datagrid('getPanel')).parent().parent().hide();
            },
            onCheckAll: function() {
                $(this).datagrid('unselectAll');
                $('.btn-extra',$(this).datagrid('getPanel')).parent().parent().show();
            },
            onUncheckAll: function() {
                $('.btn-extra',$(this).datagrid('getPanel')).parent().parent().hide();
            },
            onDblClickRow: function(index, row) {
                GridHelper.view(row.id);
            }
        });
        var pager = $('#formsgrid').datagrid('getPager');    // get the pager of datagrid
        pager.pagination({
            buttons:[
                {
                    iconCls:'fa fa-trash fa-lg btn-extra delete',
                    handler:function(){GridHelper.deleteAll();}
                }
            ]
        });
        $('.btn-extra').parent().parent().hide();
    },
    startExport: function(options) {
        var that = this;
        options.mode = 'startExport';
        $.post(
            Config.url,
            options,
            function (response) {
                if (response.success) {
                    exportProcess = true;
                    $('<div id="exportDlg" style="padding:15px;">' + that.translate('exported') + ': <span>0</span></div>').dialog({
                        title: that.translate('processing_export'),
                        width: 400,
                        modal: true,
                        onClose: function() {
                            $("#exportDlg").remove();
                            exportProcess = false;
                        }
                    });
                    GridHelper.processExport();
                } else {
                    $.messager.alert(that.translate('error'), that.translate('export_error'), 'error');
                }
            }, 'json'
        ).fail(GridHelper.handleAjaxError);
    },
    processExport: function() {
        var that = this;
        if (!exportProcess) return;
        $.post(
            Config.url,
            {
                mode: 'processExport'
            },
            function (response) {
                if (response.success) {
                    $('#exportDlg').dialog('close');
                    if (response.finished && response.exported > 0) {
                        $.messager.alert(that.translate('export_completed'), that.translate('exported') + ': ' + response.exported + '<br><br><a class="btn btn-info" target="_blank" href="' + response.filename + '">Скачать файл</a>');
                    } else {
                        exportProcess = true;
                        $('span', '#exportDlg').text(response.exported);
                        GridHelper.processExport();
                    }
                } else {
                    exportProcess = false;
                    $.messager.alert(that.translate('error'), that.translate('export_error'), 'error');
                }    
            }, 'json'
        ).fail(GridHelper.handleAjaxError);
    }
};
var columns = [[
    {
        field: 'select',
        checkbox:true
    },
    {
        field: 'id',
        title: 'ID',
        sortable: true,
        fixed: true,
        width:50
    },
    {
        field: 'type',
        title: GridHelper.translate('type'),
        sortable: true,
        width:130
    },
    {
        field: 'name',
        title: GridHelper.translate('sender'),
        sortable: true,
        width:150,
        formatter: sanitize
    },
    {
        field: 'email',
        title: 'E-mail',
        sortable: true,
        width: 120,
        formatter: sanitize
    },
    {
        field: 'phone',
        title: GridHelper.translate('phone'),
        sortable: true,
        width:100,
        fixed:true
    },
    {
        field: 'createdon',
        width: 100,
        fixed: true,
        align: 'center',
        title: GridHelper.translate('date'),
        sortable: true,
        formatter: function (value) {
            var sql = value.split(/[- :]/);
            var d = new Date(sql[0], sql[1] - 1, sql[2], sql[3], sql[4], sql[5]);
            var year = d.getFullYear();
            var month = d.getMonth() + 1;
            var day = d.getDate();
            var hour = d.getHours();
            var min = d.getMinutes();
            return ('0' + day).slice(-2) + '.' + ('0' + month).slice(-2) + '.' + year + '<br>' + ('0' + hour).slice(-2) + ':' + ('0' + min).slice(-2);
        }
    },
    {
        field: 'ip',
        title: 'IP',
        align: 'center',
        sortable: true,
        fixed: true,
        width: 100
    },
    {
        field: 'action',
        width: 40,
        title: '',
        align: 'center',
        fixed: true,
        formatter: function (value, row, index) {
            return '<a class="action delete" href="javascript:void(0)" onclick="GridHelper.deleteRow(' + index + ')" title="Удалить"><i class="fa fa-trash fa-lg"></i></a>';
        }
    }
]];
