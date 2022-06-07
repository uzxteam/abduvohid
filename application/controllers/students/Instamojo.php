<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Instamojo extends Student_Controller {

     public $api_config = "";

    public function __construct() {
        parent::__construct();

        $api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
    }
  
    public function index() {
 
        $data = array();
        $data['params'] = $this->session->userdata('params');

        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $data['student_data'] = $this->student_model->get($data['params']['student_id']);
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $this->load->view('student/instamojo', $data);
    }
 
    public function insta_pay() {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
        $params = $this->session->userdata('params');
        if ($this->form_validation->run() == false) {
            $data = array();
            
            $data['setting'] = $this->setting;
            $data['api_error'] = $data['api_error'] = array();
              $data['student_data'] = $this->student_model->get($params['student_id']);
            $this->load->view('student/instamojo', $data);
        } else {

            
            $instadetails = $this->paymentsetting_model->getActiveMethod();
            $insta_apikey = $instadetails->api_secret_key;
            $insta_authtoken = $instadetails->api_publishable_key;
            $data = array();
            $data['name'] = $params['name'];
            
            $amount =number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.instamojo.com/api/1.1/payment-requests/'); // for live https://www.instamojo.com/api/1.1/payment-requests/
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:$insta_apikey",
                "X-Auth-Token:$insta_authtoken"));
            $payload = Array(
                'purpose' => 'Student Fess',
                'amount' => $amount,
                'phone' => $_POST['phone'],
                'buyer_name' => $data['name'],
                'redirect_url' => base_url() . 'students/instamojo/success',
                'send_email' => false,
                'webhook' => base_url() . 'webhooks/insta_webhook',
                'send_sms' => false,
                'email' => $_POST['email'],
                'allow_repeated_payments' => false
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response, true);

            if ($json['success']) {
                $url = $json['payment_request']['longurl'];
                header("Location: $url");
            } else {
                 
                $data = array();

                $data['params'] = $this->session->userdata('params');
                $data['student_data'] = $this->student_model->get($data['params']['student_id']);
                $data['setting'] = $this->setting;
                $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
                $json = json_decode($response, true);
                $data['api_error'] = $json['message'];
                $this->load->view('student/instamojo', $data);
            }
        }
    }

    public function success() {
        

        if ($_GET['payment_status'] == 'Credit') {
                    $payment_id = $_GET['payment_id']; 
                    $bulk_fees=array();
                    $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Instamojo TXN ID: " . $payment_id,
                        'received_by'     => '',
                        'payment_mode'    => 'Instamojo',
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

                } else {
                    redirect(base_url('students/payment/paymentfailed'));
                }
    }

}
