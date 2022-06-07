<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Stripe extends Front_Controller
{

    public $pay_method = "";
    public $amount = 0;

    function __construct() {
        parent::__construct();
        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->getSetting();
        $this->amount = $this->setting->online_admission_amount;
        $this->load->library('stripe_payment');
        $this->load->library('mailsmsconf');
        $this->load->model('onlinestudent_model');
    }

    public function index() {

        $reference = $this->session->userdata('reference');
        $data['setting'] = $this->setting;
        
        $online_data = $this->onlinestudent_model->getAdmissionData($reference);
        $total = $this->amount;
        $data['amount'] = $total;
        $data['name'] = $online_data->firstname." ".$online_data->lastname;
        $data['currency_name'] = $this->setting->currency;
        $data['api_publishable_key'] = $this->pay_method->api_publishable_key;
        $this->load->view('onlineadmission/stripe/index', $data);
    }

    public function complete() {
        
        $stripeToken         = $this->input->post('stripeToken');
        $stripeTokenType     = $this->input->post('stripeTokenType');
        $stripeEmail         = $this->input->post('stripeEmail');
        $data                = $this->input->post();
        $data['stripeToken'] = $stripeToken;
        $data['total']  = $this->amount;
        $data['description'] = $this->lang->line('online_admission_form_fees');
        $data['currency']    = 'USD';
        $response            = $this->stripe_payment->payment($data);

        if ($response->isSuccessful()) {
            $transactionid = $response->getTransactionReference();
            $response      = $response->getData();
            if ($response['status'] == 'succeeded') {
                $amount = $this->session->userdata('payment_amount');
                $reference  = $this->session->userdata('reference');
                $online_data = $this->onlinestudent_model->getAdmissionData($reference);
                $apply_date=date("Y-m-d H:i:s");
                $date         = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($apply_date));
                $gateway_response['admission_id']   = $reference; 
                $gateway_response['paid_amount']    = $this->amount;
                $gateway_response['transaction_id'] = $transactionid;
                $gateway_response['payment_mode']   = 'stripe';
                $gateway_response['payment_type']   = 'online';
                $gateway_response['note']           = "Payment deposit through Stripe TXN ID: " . $transactionid;
                $gateway_response['date']           = date("Y-m-d H:i:s");
                $return_detail                      = $this->onlinestudent_model->paymentSuccess($gateway_response);
                $sender_details = array('firstname' => $online_data->firstname, 'lastname' => $online_data->lastname, 'email' => $online_data->email,'date'=>$date,'reference_no'=>$online_data->reference_no,'mobileno'=>$online_data->mobileno,'paid_amount'=>$this->amount);
                $this->mailsmsconf->mailsms('online_admission_fees_submission', $sender_details);
                redirect(base_url("onlineadmission/checkout/successinvoice//".$online_data->reference_no));
            }
        } elseif ($response->isRedirect()) {
            $response->redirect();
        } else {
            redirect(site_url("onlineadmission/checkout/paymentfailed/".$online_data->reference_no));
        }
    }

}

?>