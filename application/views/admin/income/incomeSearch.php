<style type="text/css">
    @media print
    {
        .no-print, .no-print *
        {
            display: none !important;
        }
    }
</style>
<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <section class="content-header">
        <h1>
            <i class="fa fa-usd"></i> <?php echo $this->lang->line('income'); ?></h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('select_criteria'); ?></h3>
                    </div>
                <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <form role="form" id="form1" action="<?php echo site_url('admin/income/incomeSearch') ?>" method="post" class="">
                                        <?php echo $this->customlib->getCSRF(); ?>
                                        <div class="col-sm-6 col-md-6">
                                            <div class="form-group">
                                                <label><?php echo $this->lang->line('search') . " " . $this->lang->line('type'); ?></label><small class="req"> *</small>
                                                <select class="form-control" name="search_type" id="search_type" onchange="showdate(this.value)">

                                                    <?php foreach ($searchlist as $key => $search) {
                                                        ?>
                                                        <option value="<?php echo $key ?>" <?php
                                                        if ((isset($search_type)) && ($search_type == $key)) {
                                                            echo "selected";
                                                        }
                                                        ?>><?php echo $search ?></option>
                                                            <?php } ?>
                                                </select>
                                                <span class="text-danger"><?php echo form_error('search_type'); ?></span>
                                            </div>
                                        </div>

                                        <div id='date_result'>

                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm checkbox-toggle pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <form role="form" id="form2" action="<?php echo site_url('admin/income/incomeSearch') ?>" method="post" class="">
                                        <?php echo $this->customlib->getCSRF(); ?>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label><?php echo $this->lang->line('search'); ?></label><small class="req"> *</small>
                                                <input autofocus="" type="text" value="<?php echo set_value('search_text', ""); ?>" name="search_text" id="search_text" class="form-control" placeholder="Search by Income">
                                                <span class="text-danger"><?php echo form_error('search_text'); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <button type="submit" name="search" value="search_full" class="btn btn-primary btn-sm checkbox-toggle pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>

                    </div>
                <div class="" id="exp">
                            <div class="box-header ptbnull"></div>
                            <div class="box-header ptbnull">
                                <h3 class="box-title titlefix"><i class="fa fa-money"></i> <?php echo $this->lang->line('income_result'); ?></h3>
                            </div>
                            <div class="box-body table-responsive">
                                <div class="download_label"><?php echo $this->lang->line('income_result'); ?></div>
                                 <table class="table table-striped table-bordered table-hover income-list" data-export-title="<?php echo $this->lang->line('income_list'); ?>">
                                    <thead>
                                        <tr>
                                            <th><?php echo $this->lang->line('name'); ?></th>
                                            <th><?php echo $this->lang->line('invoice_no'); ?></th>
                                            <th><?php echo $this->lang->line('income_head'); ?></th>
                                            <th><?php echo $this->lang->line('date'); ?></th>
                                            <th class="text-right"><?php echo $this->lang->line('amount'); ?> <span><?php echo "(" . $currency_symbol . ")"; ?></span></th>
                                        </tr>
                                    </thead>
                                    <tbody> </tbody>
                                </table>

                            </div>

                        </div>
                  </div>      
            </div>
        </div>    

    </section><!-- /.content -->
</div><!-- /.content-wrapper -->
<script type="text/javascript">

    var base_url = '<?php echo base_url() ?>';

    function printDiv(elem) {
        Popup(jQuery(elem).html());
    }

    function Popup(data)
    {

        var frame1 = $('<iframe />');
        frame1[0].name = "frame1";
        frame1.css({"position": "absolute", "top": "-1000000px"});
        $("body").append(frame1);
        var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
        frameDoc.document.open();
        //Create a new HTML document.
        frameDoc.document.write('<html>');
        frameDoc.document.write('<head>');
        frameDoc.document.write('<title></title>');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/bootstrap/css/bootstrap.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/font-awesome.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/ionicons.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/AdminLTE.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/skins/_all-skins.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/iCheck/flat/blue.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/morris/morris.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/jvectormap/jquery-jvectormap-1.2.2.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/datepicker/datepicker3.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/daterangepicker/daterangepicker-bs3.css">');
        frameDoc.document.write('</head>');
        frameDoc.document.write('<body>');
        frameDoc.document.write(data);
        frameDoc.document.write('</body>');
        frameDoc.document.write('</html>');
        frameDoc.document.close();
        setTimeout(function () {
            window.frames["frame1"].focus();
            window.frames["frame1"].print();
            frame1.remove();
        }, 500);


        return true;
    }
</script>

<script>
$(document).ready(function() {
     emptyDatatable('income-list','data');

});
</script>
  
<script>
   ( function ( $ ) {
   'use strict';
    $(document).ready(function () {
       $('#form1').on('submit', (function (e) {
        e.preventDefault();
         var search= 'search_filter';
         var formData = new FormData(this);
         formData.append('search', 'search_filter');
         var date_from=""; var date_to=""; var str="";
         $("#search_text").val("");
        $.ajax({
            url: '<?php echo base_url(); ?>admin/income/checkvalidation',
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
               
                if (data.status == "fail") {
                        var message = "";
                        $.each(data.error, function (index, value) {
                            message += value;
                        });
                        toastr.error(message);
                    } else {
                        var search_type = data.search_type ;
                        if(search_type=='period'){
                            date_from=data.date_from; 
                            date_to=data.date_to;

                             if(date_from!="" && date_to!="" ){
                                 str=search_type+"-"+search+"-"+date_from+"-"+date_to ;
                             }
                            
                        }else{
                            str=search_type+"-"+search ;
                        }
                       
                         initDatatable('income-list','admin/income/getincomesearchlist/'+str,[],[],100);
                       
                    }
            }
        });
      
        }

       ));
   });
} ( jQuery ) );
</script>
<script>
   ( function ( $ ) {
   'use strict';
    $(document).ready(function () {
       $('#form2').on('submit', (function (e) {
        e.preventDefault();
         var search= 'search_full';var search_type="";
         var formData = new FormData(this);
         formData.append('search', 'search_full');
         $("#search_type").val("");
         var str="";
        $.ajax({
            url: '<?php echo base_url(); ?>admin/income/checkvalidation',
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
               
                if (data.status == "fail") {
                        var message = "";
                        $.each(data.error, function (index, value) {
                            message += value;
                        });
                        toastr.error(message);
                    } else {
                        search_type=data.search_type;
                        str=search_type+"-"+search ;
                         initDatatable('income-list','admin/income/getincomesearchlist/'+str,[],[],100);
                       
                    }
            }
        });
      
        }

       ));
   });
} ( jQuery ) );
</script>