<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Midtrans extends Student_Controller {

    public $api_config = "";

    public function __construct() {
        parent::__construct();

        $api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('Midtrans_lib');
    }

    public function index() {

        $data = array();
        $data['params'] = $this->session->userdata('params');
        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $amount =number_format((float)($data['params']['fine_amount_balance']+$data['params']['total']), 2, '.', '');
        
        $enable_payments = array('credit_card');
        $transaction = array(
            'enabled_payments' => $enable_payments,
            'transaction_details' => array(
                'order_id' => time(),
                'gross_amount' => round($amount), // no decimal allowed
            ),
        );
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $snapToken = $this->midtrans_lib->getSnapToken($transaction, $data['params']['key']);
        $data['snap_Token'] = $snapToken;
        $this->load->view('student/midtrans', $data);
    }

    public function success() {

        $response = json_decode($_POST['result_data']);

        $payment_id = $response->transaction_id;
        $params = $this->session->userdata('params');
        
                $bulk_fees=array();
                    $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Midtrans TXN ID: " . $payment_id,
                        'received_by'     => '',
                        'payment_mode'    => 'Midtrans',
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
                           echo $inserted_id;                   
                    } else {
                      
                    }

    }

}
