<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>

<div class="content-wrapper">

    <section class="content-header">
        <h1>
            <i class="fa fa-bus"></i> <?php echo $this->lang->line('transport'); ?></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('reports/_inventory');?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <form role="form" action="<?php echo site_url('report/inventorystock') ?>" method="post" class="">
                        <div class="box-body row">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm checkbox-toggle pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="">
                        <div class="box-header ptbnull"></div>
                        <div class="box-header ptbnull">
                            <h3 class="box-title titlefix"><i class="fa fa-money"></i> <?php echo $this->lang->line('stock') . " " . $this->lang->line('report'); ?></h3>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover inventory-list" data-export-title="<?php echo $this->lang->line('stock') . " " . $this->lang->line('report'); ?>">
                                    <thead>
                                        <tr>
                                            <th><?php echo $this->lang->line('name'); ?></th>
                                            <th><?php echo $this->lang->line('category'); ?></th>
                                            <th><?php echo $this->lang->line('supplier'); ?></th>
                                            <th><?php echo $this->lang->line('store'); ?></th>
                                            <th><?php echo $this->lang->line('available') . " " . $this->lang->line('quantity'); ?></th>
                                            <th><?php echo $this->lang->line('total') . " " . $this->lang->line('quantity'); ?></th>
                                            <th><?php echo $this->lang->line('total') . " " . $this->lang->line('issued'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>    
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
</section>
</div>
<script>
<?php
if ($search_type == 'period') {
    ?>

        $(document).ready(function () {
            showdate('period');
        });

    <?php
}
?>
</script>
<script>
    ( function ( $ ) {
    'use strict';
    $(document).ready(function () {
        initDatatable('inventory-list','report/getinventorylist',[],[],100);
    });
} ( jQuery ) )
</script>