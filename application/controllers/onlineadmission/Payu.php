<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payu extends Front_Controller
{

    public $pay_method = "";
    public $amount = 0;

    function __construct() {
        parent::__construct();
        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->getSetting();
        $this->amount = $this->setting->online_admission_amount;
        $this->load->library('mailsmsconf');
        $this->load->model('onlinestudent_model');
    }

    public function index()
    {
        $this->session->set_userdata('payment_amount',$this->amount);
        $reference = $this->session->userdata('reference');
        $txnid                      = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $online_data = $this->onlinestudent_model->getAdmissionData($reference);
        //payumoney details
        $amount           = $this->amount;
        $customer_name    = $online_data->firstname." ".$online_data->lastname;
        $customer_emial   = $online_data->email;
        $customer_mobile  = $online_data->mobileno;
        $customer_address  = $online_data->current_address;
        $product_info = 'Online Fees Payment';
        $MERCHANT_KEY = $this->pay_method->api_secret_key;
        $SALT         = $this->pay_method->salt;

        //optional udf values
        $udf1 = '';
        $udf2 = '';
        $udf3 = '';
        $udf4 = '';
        $udf5 = '';

        $hashstring = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_emial . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $SALT;
        $hash       = strtolower(hash('sha512', $hashstring));

        $success = base_url('onlineadmission/payu/success');
        $fail    = base_url('onlineadmission/payu/success');
        $cancel  = base_url('onlineadmission/payu/success');
        $data    = array(
            'mkey'                      => $MERCHANT_KEY,
            'tid'                       => $txnid,
            'hash'                      => $hash,
            'amount'                    => $amount,
            'name'                      => $customer_name,
            'productinfo'               => $product_info,
            'mailid'                    => $customer_emial,
            'phoneno'                   => $customer_mobile,
            'address'                   => $customer_address,
            'action'                    => "https://secure.payu.in/_payment", //for live change action  https://secure.payu.in
            'sucess'                    => $success,
            'failure'                   => $fail,
            'cancel'                    => $cancel,
        );
        $data['setting']      = $this->setting;
      
        $this->load->view('onlineadmission/payu/index', $data);
    }

    public function checkout()
    {

        $this->form_validation->set_rules('firstname', 'Customer Name', 'required|trim|xss_clean');
        $this->form_validation->set_rules('phone', 'Mobile No', 'required|trim|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|xss_clean');
        $this->form_validation->set_rules('amount', 'Amount', 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'firstname' => form_error('firstname'),
                'phone'     => form_error('phone'),
                'email'     => form_error('email'),
                'amount'    => form_error('amount'),
            );
            $array = array('status' => 'fail', 'error' => $data);
            echo json_encode($array);
        } else {

            $array = array('status' => 'success', 'error' => '');
            echo json_encode($array);
        }
    }

    public function success()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
          
            $amount = $this->amount;
            if ($this->input->post('status') == "success") {
                $mihpayid      = $this->input->post('mihpayid');
                $transactionid = $this->input->post('txnid');
                if (!empty($transactionid)) {
                    $reference  = $this->session->userdata('reference');
                    $online_data = $this->onlinestudent_model->getAdmissionData($reference);
                    $apply_date=date("Y-m-d H:i:s");
                    $date         = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($apply_date));
                    $gateway_response['admission_id']   = $reference; 
                    $gateway_response['paid_amount']    = $amount;
                    $gateway_response['transaction_id'] = $transactionid;
                    $gateway_response['payment_mode']   = 'payu';
                    $gateway_response['payment_type']   = 'online';
                    $gateway_response['note']           = "Payment deposit through Payu TXN ID: " . $transactionid;
                    $gateway_response['date']           = date("Y-m-d H:i:s");
                    $return_detail                      = $this->onlinestudent_model->paymentSuccess($gateway_response);
                    $sender_details = array('firstname' => $online_data->firstname, 'lastname' => $online_data->lastname, 'email' => $online_data->email,'date'=>$date,'reference_no'=>$online_data->reference_no,'mobileno'=>$online_data->mobileno,'paid_amount'=>$amount);
                    $this->mailsmsconf->mailsms('online_admission_fees_submission', $sender_details);
                    redirect(base_url("onlineadmission/checkout/successinvoice/".$online_data->reference_no));
                } else {
                    redirect(base_url("onlineadmission/checkout/paymentfailed/".$online_data->reference_no));
                }
            }else {
                redirect(base_url("onlineadmission/checkout/paymentfailed/".$online_data->reference_no));
            }
        }
    }

}
