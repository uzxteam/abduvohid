<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Billplz extends Student_Controller {

    public $api_config = "";

    function __construct() {
        parent::__construct();
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('billplz_lib');
    }

    public function index() {

        $params = $this->session->userdata('params');
        $data = array();
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $student_id = $params['student_id'];
        $total = number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');;
        $data['name'] = $params['name'];
        $data['title'] = 'Student Fee';
        $data['total'] = $total * 100;
        $data['amount'] = $total;
        $data['guardian_phone'] = $params['guardian_phone'];
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $this->load->view('student/billplz/index', $data);
    } 

    public function pay(){

    	$params = $this->session->userdata('params');
        $amount =number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');
        $data = array();
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $student_id = $params['student_id'];
        $data['name'] = $params['name'];
        $data['title'] = 'Student Fee';
        $data['return_url'] = base_url() . 'students/billplz/callback';
        $parameter = array(
            'title' => $data['name'],
            'description' => $data['title'],
            'amount' => $amount*100,
        ); 

        $optional = array(
            'fixed_amount' => 'true',
            'fixed_quantity' => 'true',
            'payment_button' => 'pay',
            'redirect_uri'=>$data['return_url'],
            'photo' => '',
            'split_header' => false,
            'split_payments' => array(
            ['split_payments[][email]' => $this->api_config->api_email],
            ['split_payments[][fixed_cut]' => '0'],
            ['split_payments[][variable_cut]' => ''],
            ['split_payments[][stack_order]' => '0'],
        )
        );
        $api_key=$this->api_config->api_secret_key;
        $this->billplz_lib->payment($parameter,$optional,$api_key);
    }

    public function callback() {
    	

        $params = $this->session->userdata('params');
        $amount =number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');
        $data = array();
        if($_GET['billplz']['paid']=='true'){
        	$payment_id =$_GET['billplz']['id'];
            $bulk_fees=array();
            $params     = $this->session->userdata('params');
         
            foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
           
             $json_array = array(
                'amount'          =>  $fee_value['amount_balance'],
                'date'            => date('Y-m-d'),
                'amount_discount' => 0,
                'amount_fine'     => $fee_value['fine_balance'],
                'description'     => "Online fees deposit through Billplz TXN ID: " . $payment_id,
                'received_by'     => '',
                'payment_mode'    => 'Billplz',
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
                  redirect(base_url("students/payment/successinvoice"));                     
            } else {
              redirect(base_url('students/payment/paymentfailed'));
            }
        }else{
            redirect(base_url("students/payment/paymentfailed"));
        }
        
      
    }

}
