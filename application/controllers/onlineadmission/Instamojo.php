<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Instamojo extends Front_Controller
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

  
    public function index() {

        $reference = $this->session->userdata('reference');
        $data['setting'] = $this->setting;
        $total = $this->amount;
        $data['amount'] = $total;
        $data['error']=array();
        $this->load->view('onlineadmission/instamojo/index', $data);
    } 
 
    
    public function pay()
    {
        $this->session->set_userdata('payment_amount',$this->amount);
        $insta_apikey    = $this->pay_method->api_secret_key;
        $insta_authtoken = $this->pay_method->api_publishable_key;
        $reference = $this->session->userdata('reference');
        $buyer_data = $this->onlinestudent_model->getAdmissionData($reference);
       
        $amount        = $this->amount;
        $ch              = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.instamojo.com/api/1.1/payment-requests/'); // for live https://www.instamojo.com/api/1.1/payment-requests/
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:$insta_apikey",
            "X-Auth-Token:$insta_authtoken"));
        $payload = array(
            'purpose'                 => $this->lang->line('online_admission_form_fees'),
            'amount'                  => $amount,
            'phone'                   => '',
            'buyer_name'              => $buyer_data->firstname." ".$buyer_data->middlename." ".$buyer_data->lastname,
            'redirect_url'            => base_url() . 'onlineadmission/instamojo/complete',
            'send_email'              => false,
            'webhook'                 => '',
            'send_sms'                => false,
            'email'                   => $buyer_data->email,
            'allow_repeated_payments' => false,
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
            $json = json_decode($response, true);
           
            $error = array();
            
          

        $reference = $this->session->userdata('reference');
        $data['setting'] = $this->setting;
        $total = $this->amount;
        $data['amount'] = $total;
        $data['error']=$json['message'];
        $this->load->view('onlineadmission/instamojo/index', $data);
        }
    }

    /**
     * This is a callback function for movies payment completion
     */
    public function complete()
    {
        $reference  = $this->session->userdata('reference');
        $online_data = $this->onlinestudent_model->getAdmissionData($reference);
        $apply_date=date("Y-m-d H:i:s");
        $date         = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($apply_date));
        if ($_GET['payment_status'] == 'Credit') {

            $amount = $this->session->userdata('payment_amount');
            $reference  = $this->session->userdata('reference');
            $transactionid                      = $_GET['payment_id'];
            $gateway_response['admission_id']   = $reference;
            $gateway_response['paid_amount']    = $amount;
            $gateway_response['transaction_id'] = $transactionid;
            $gateway_response['payment_mode']   = 'instamojo';
            $gateway_response['payment_type']   = 'online';
            $gateway_response['note']           = "Payment deposit through Instamojo TXN ID: " . $transactionid;
            $gateway_response['date']           = date("Y-m-d H:i:s");
            $return_detail                      = $this->onlinestudent_model->paymentSuccess($gateway_response);
            $sender_details = array('firstname' => $online_data->firstname, 'lastname' => $online_data->lastname, 'email' => $online_data->email,'date'=>$date,'reference_no'=>$online_data->reference_no,'mobileno'=>$online_data->mobileno,'paid_amount'=>$amount);
            $this->mailsmsconf->mailsms('online_admission_fees_submission', $sender_details);
            redirect(base_url("onlineadmission/checkout/successinvoice/".$online_data->reference_no));

        } else {

            redirect(base_url("onlineadmission/checkout/paymentfailed/".$online_data->reference_no));
        }

    }


}
