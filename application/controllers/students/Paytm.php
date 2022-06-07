<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Paytm extends Student_Controller {

    public $setting = "";

    public function __construct() {

        parent::__construct();
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('Paytm_lib');
        //===================================================
    }

    public function index() {

        $params = $this->session->userdata('params');
        $data = array();
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $student_id = $params['student_id'];
        $total = $params['total'];
        $data['student_id'] = $student_id;
        $amount=number_format((float)($params['fine_amount_balance']+$params['total']), 2, '.', '');
        $data['total'] = $amount;
        $data['symbol'] = $params['invoice']->symbol;
        $data['currency_name'] = $params['invoice']->currency_name;
        $data['name'] = $params['name'];
        $data['guardian_phone'] = $params['guardian_phone'];
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $posted = $_POST;
        $paytmParams = array();
        $ORDER_ID = time();
        $CUST_ID = time();

        $paytmParams = array(
            "MID" => $this->api_config->api_publishable_key,
            "WEBSITE" => $this->api_config->paytm_website,
            "INDUSTRY_TYPE_ID" => $this->api_config->paytm_industrytype,
            "CHANNEL_ID" => "WEB",
            "ORDER_ID" => $ORDER_ID,
            "CUST_ID" => $data['student_id'],
            "TXN_AMOUNT" => $data['total'],
            "CALLBACK_URL" => base_url() . "students/Paytm/paytm_response",
        );
 
        $paytmChecksum = $this->paytm_lib->getChecksumFromArray($paytmParams, $this->api_config->api_secret_key);
        $paytmParams["CHECKSUMHASH"] = $paytmChecksum;
       // $transactionURL              = 'https://securegw-stage.paytm.in/order/process';//for sand-box
        $transactionURL              = 'https://securegw.paytm.in/order/process';// for live
        $data['paytmParams'] = $paytmParams;
        $data['transactionURL'] = $transactionURL;

        $this->load->view('student/paytm', $data);
    }

    public function paytm_response() {

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";

        $paramList = $_POST;

        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";



        $isValidChecksum = $this->paytm_lib->verifychecksum_e($paramList, $this->api_config->api_secret_key, $paytmChecksum);


        if ($isValidChecksum == "TRUE") {




            if ($_POST["STATUS"] == "TXN_SUCCESS") {

                $params = $this->session->userdata('params');
                $ref_id = $_POST['TXNID'];
                $bulk_fees=array();
                    $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Paytm TXN ID: " . $ref_id,
                        'received_by'     => '',
                        'payment_mode'    => 'Paytm',
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
                redirect(base_url("students/payment/paymentfailed"));
            }
        } else {
            redirect(base_url("students/payment/paymentfailed"));
        }
    }

}

?>
