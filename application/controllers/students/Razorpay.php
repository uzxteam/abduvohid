<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Razorpay extends Student_Controller {

    public $api_config = "";

    function __construct() {
        parent::__construct();
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
    }

    public function index() {

        $params = $this->session->userdata('params');

        $data = array();
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $student_id = $params['student_id'];
        $data['name'] = $params['name'];
        $data['merchant_order_id'] = time() . "01";
        $data['txnid'] = time() . "02";
        $data['title'] = 'Student Fee';
        $data['return_url'] = site_url() . 'students/razorpay/callback';
        $amount=number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');
        $data['total'] = $amount * 100;
        $data['key_id'] = $this->api_config->api_publishable_key;
        $data['currency_code'] = $params['invoice']->currency_name;
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];

        $this->load->view('student/razorpay', $data);
    }

    public function callback() {

        $params = $this->session->userdata('params');
        $payment_id = $_POST['razorpay_payment_id'];
      
       $bulk_fees=array();
                    $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Razorpay TXN ID: " . $payment_id,
                        'received_by'     => '',
                        'payment_mode'    => 'Razorpay',
                    );

                    $insert_fee_data = array(
                        'student_fees_master_id' => $fee_value['student_fees_master_id'],
                        'fee_groups_feetype_id'  => $fee_value['fee_groups_feetype_id'],
                        'amount_detail'          => $json_array,
                    );                 
                   $bulk_fees[]=$insert_fee_data;
                    //========
                    }
                    $send_to     = $params['guardian_phone'];
                    $inserted_id = $this->studentfeemaster_model->fee_deposit_bulk($bulk_fees, $send_to);
                    if ($inserted_id) {
                               echo 1;             
                    } else {
                                echo 0;     
                    } 
    }

}
