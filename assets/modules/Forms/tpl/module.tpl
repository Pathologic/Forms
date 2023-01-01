<!DOCTYPE html>
<html>
<head>
    <title>[%forms_data%]</title>
    <link rel="stylesheet" type="text/css" href="[+manager_url+]media/style/[+theme+]/style.css" />
    <link rel="stylesheet" href="[+manager_url+]media/style/common/font-awesome/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/js/easy-ui/themes/modx/easyui.css"/>
    <script type="text/javascript" src="[+manager_url+]media/script/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/plugins/datagrid-detailview.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-en.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-[+lang+].js"></script>
    <script type="text/javascript">
        var Config = {
            url:'[+connector+]'
        };
    </script>
    [+lexicon+]
    <script type="text/javascript" src="[+site_url+]assets/modules/Forms/js/module.js"></script>
    <style>
       .datagrid {
            margin-top:15px;
        }
        .datagrid-view td {
            vertical-align: middle;
        }
        .pagination td{
            font-size:12px;
        }
        .delete, .delete:hover {
            color:red;
        }
        #searchPanel {
            padding:10px;
        }
        .panel-header, .panel-body {
            width:auto!important;
        }
        #searchPanel .form-group {
            display:inline-block;
            margin-right:15px;
        }
       #searchPanel .form-group label {
           display: block;
       }
       #formType {
            width:250px;
        }
        #formBegin, #formEnd {
            width:150px;
        }
        .viewwnd {
            padding:10px;
        }
        .formrow {
            margin-bottom:15px;
        }
        .combo-arrow, .combo-clear, .datebox .combo-arrow {
            line-height:30px;
        }
        .combo-clear, .combo-clear:hover, .combo-clear:focus {
            color: red;
            text-align: center;
            text-decoration:none;
        }
        .combo-clear:hover {
            background: #e6e6e6;
        }
        .combo .textbox-text, .combo .textbox-text:focus {
            border:0!important;
            outline:none!important;
        }
        .datagrid-wrap {
            width:auto!important;
        }
    </style>
</head>
<body>
<h1 class="pagetitle">
  <span class="pagetitle-icon">
    <i class="fa fa-envelope-open-o"></i>
  </span>
    <span class="pagetitle-text">
    [%forms_data%]
  </span>
</h1>
<div id="actions">
    <div class="btn-group">
        <a class="btn btn-success" href="javascript:;" onclick="window.location.href='index.php?a=106';">
            <i class="fa fa-times-circle"></i><span>[%close%]</span>
        </a>
    </div>
</div>
<div class="sectionBody">
    <div class="dynamic-tab-pane-control tab-pane">
        <div class="tab-page" style="margin-top:0;">
            <div id="searchPanel" class="easyui-panel" data-options="border:true,collapsible:true,collapsed:true,iconCls:'fa fa-search'" title="[%search_and_export%]">
                <form>
                    <div class="form-group">
                        <label for="formType">[%type%]</label>
                        <input type="text" class="form-control" id="formType">
                    </div>
                    <div class="form-group">
                        <label for="dateStart">[%from_date%]</label>
                        <input type="text" class="form-control" id="formBegin">
                    </div>
                    <div class="form-group">
                        <label for="dateFinish">[%to_date%]</label>
                        <input type="text" class="form-control" id="formEnd">
                    </div>
                    <button id="searchBtn" class="btn btn-primary">[%search%]</button>
                    <button id="exportBtn" class="btn btn-success">[%export%]</button>
                </form>
            </div>
            <div>
            <table id="formsgrid" width=""></table>
            </div>
        </div>
    </div>
</div>
<script>
    $('#filter').on('change','input',function(){
        var filter = $(this).val();
        $('#forms').datagrid('load',{filter:filter});
    });
    GridHelper.initGrid();
    var exportProcess = false;
</script>
</body>
</html>
