<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Stripe extends Student_Controller {

    public $setting = "";

    function __construct() {
        parent::__construct();

        $this->load->library('stripe_payment');

        $this->setting = $this->setting_model->get();
    }

    public function index() {
        $this->session->set_userdata('top_menu', 'Library');
        $this->session->set_userdata('sub_menu', 'book/index');
        $data = array();
        $data['params'] = $this->session->userdata('params');
        $data['setting'] = $this->setting;
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $this->load->view('student/stripe', $data);
    }

    public function complete() {
        
        $data = $this->input->post();
        $data['description'] = 'Online fees deposit';
        $data['currency'] = 'USD';
        $response = $this->stripe_payment->payment($data);
       
        if ($response->isSuccessful()) {
            $transactionid = $response->getTransactionReference();
            $response = $response->getData();
            if ($response['status'] == 'succeeded') {
               
                $payment_data['transactionid'] = $transactionid;
                $bulk_fees=array();
                $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Stripe TXN ID: " . $transactionid,
                        'received_by'     => '',
                        'payment_mode'    => 'Stripe',
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

                
            }
        } elseif ($response->isRedirect()) {
            $response->redirect();
        } else {

            redirect(site_url('user/user/dashboard'));
        }
    }

}

?>