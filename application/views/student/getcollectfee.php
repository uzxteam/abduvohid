<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<style type="text/css">
    .collect_grp_fees{
      font-size: 15px;
    font-weight: 600;
    padding-bottom: 15px;

    }

    .fees-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .fees-list>.item {
        border-radius: 3px;
        -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        padding: 10px 0;
        background: #fff;
    }
    .fees-list>.item:before, .fees-list>.item:after {
        content: " ";
        display: table;
    }
    .fees-list>.item:after {
        clear: both;
    }
    .fees-list .product-img {
        float: left;
    }
    .fees-list .product-img img {
        width: 50px;
        height: 50px;
    }
    .fees-list .product-info {
        margin-left: 0px;
    }
    .fees-list .product-title {
        font-weight: 600;
        font-size: 15px;
        display: inline;
    }
    .fees-list .product-title span{

        font-size: 15px;
        display: inline;
        font-weight: 100 !important;
    }
    .fees-list .product-description {
        display: block;
        color: #999;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .fees-list-in-box>.item {
        -webkit-box-shadow: none;
        box-shadow: none;
        border-radius: 0;
        border-bottom: 1px solid #f4f4f4;
    }
    .fees-list-in-box>.item:last-of-type {
        border-bottom-width: 0;
    }

.fees-footer {
    border-top-color: #f4f4f4;
}
.fees-footer {
    padding: 15px 0px 0px 0px;
    text-align: right;
    border-top: 1px solid #e5e5e5;
}

</style>

<ul class="fees-list fees-list-in-box">
    <?php
    $row_counter = 1;
    $total_amount = 0;
    foreach ($feearray as $fee_key => $fee_value) {
        $amount_prev_paid = 0;
        $fees_fine_amount = 0;
        $fine_amount_paid = 0;
        $fine_amount_status = false;
        $amount_to_be_pay = $fee_value->amount;

        if ($fee_value->is_system) {
            $amount_to_be_pay = $fee_value->student_fees_master_amount;
        }

 
        if (is_string(($fee_value->amount_detail)) && is_array(json_decode(($fee_value->amount_detail), true))) {

            $amount_data = json_decode($fee_value->amount_detail);

            foreach ($amount_data as $amount_data_key => $amount_data_value) {
               $fine_amount_paid+=$amount_data_value->amount_fine;
                $amount_prev_paid = $amount_prev_paid + ($amount_data_value->amount + $amount_data_value->amount_discount);
            }

            if ($fee_value->is_system) {
                $amount_to_be_pay = $fee_value->student_fees_master_amount - $amount_prev_paid;
            } else {

                $amount_to_be_pay = $fee_value->amount - $amount_prev_paid;
            }
        }

 

       if (($fee_value->due_date != "0000-00-00" && $fee_value->due_date != NULL) && (strtotime($fee_value->due_date) < strtotime(date('Y-m-d'))) && $amount_to_be_pay > 0) {
         $fees_fine_amount=$fee_value->fine_amount-$fine_amount_paid;
         $total_amount=$total_amount+$fees_fine_amount;
         $fine_amount_status=true;
        }


        $total_amount = $total_amount + $amount_to_be_pay;
        if ($amount_to_be_pay > 0) {
            ?>
 
            <li class="item">
                <input name="row_counter[]" type="hidden" value="<?php echo $row_counter; ?>">
                <input name="student_fees_master_id_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->id; ?>">
                <input name="fee_groups_feetype_id_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->fee_groups_feetype_id; ?>">
                 <input name="fee_groups_feetype_fine_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fees_fine_amount; ?>">
                <input name="fee_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $amount_to_be_pay; ?>">
                <div class="product-info">
                    <a href="#"  onclick="return false;" class="product-title"><?php echo $fee_value->name; ?>
                        <span class="pull-right"><?php echo $currency_symbol.number_format((float) $amount_to_be_pay, 2, '.', ''); ?></span></a>
                         <span class="product-description">
                        <?php echo $fee_value->code; ?>
                        </span>
                        <?php 
if($fine_amount_status){
    ?>
                       <a href="#"  onclick="return false;" class="product-title text text-danger"><?php echo $this->lang->line('fine'); ?>
                        <span class="pull-right">
                            <?php echo $currency_symbol.number_format((float) $fees_fine_amount, 2, '.', '');; ?>                                
                        </span>
                    </a>

    <?php
}
                         ?>
                </div>
            </li>

            <?php
        }

        $row_counter++;
    }
    ?>
</ul>
<?php if ($total_amount > 0) { ?>
<div class="row collect_grp_fees">
    <div class="col-md-8">
        <span class="pull-right">
            <?php echo $this->lang->line('total') . " " . $this->lang->line('pay'); ?>
        </span>
    </div>
    <div class="col-md-4">
        <span class="pull-right">
            <?php  echo $currency_symbol.number_format((float) $total_amount, 2, '.', ''); ?>
        </span>

    </div>
</div>
<div class="row fees-footer">
    <div class="col-md-12">
          <button type="submit" class="btn btn-primary pull-right payment_collect" data-loading-text="<i class='fa fa-spinner fa-spin '></i><?php echo $this->lang->line('processing')?>"><i class="fa fa-money"></i> <?php echo $this->lang->line('pay'); ?></button>
    </div>
</div>
<?php }else{
    ?>
<div class="alert alert-info">
    <?php echo $this->lang->line('no_fees_found'); ?>
</div>
    <?php
}

 ?>



