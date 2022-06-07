<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Paypal extends Front_Controller
{

    public $pay_method = "";
    public $amount = 0;

    function __construct() {
        parent::__construct();
        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->getSetting();
        $this->amount = $this->setting->online_admission_amount;
        $this->load->library('paypal_payment');
        $this->load->library('mailsmsconf');
        $this->load->model('onlinestudent_model');
    }

    public function index() {
        $this->session->set_userdata('payment_amount',$this->amount);
        $data['setting'] = $this->setting;
        $total = $this->amount;
        $data['amount'] = $total;
        $this->load->view('onlineadmission/paypal/index', $data);
    } 


    public function checkout()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            if ($this->session->has_userdata('payment_amount')) {
                $setting                = $this->setting;
                $reference = $this->session->userdata('reference');
                $total = $this->amount;
                $online_data = $this->onlinestudent_model->getAdmissionData($reference);
                $data["id"]             = $reference;
                $data                   = array();
                $data['total']          = $total;
                $data['productinfo']    = "Online Admission Fees";
                $data['symbol']         = $setting->currency_symbol;
                $data['currency_name']  = $setting->currency;
                $data['guardian_phone'] = "";
                $data['student_fees_master_id'] ="";
                $data['fee_groups_feetype_id'] ="";
                $data['name']           = $online_data->firstname." ".$online_data->lastname;
                $response               = $this->paypal_payment->payment($data);
                if ($response->isSuccessful()) {

                } elseif ($response->isRedirect()) {
                    $response->redirect();
                } else {

                    echo $response->getMessage();
                }
            }
        }
    }

    //paypal successpayment
    public function getsuccesspayment() {
        $params = $this->session->userdata('params');
        $data = array();
        $student_fees_master_id = $params['student_fees_master_id'];
        $fee_groups_feetype_id = $params['fee_groups_feetype_id'];
        $student_id = $params['student_id'];
        $total = $params['total'];

        $data['student_fees_master_id'] = $student_fees_master_id;
        $data['fee_groups_feetype_id'] = $fee_groups_feetype_id;
        $data['student_id'] = $student_id;
        $data['total'] = $total;
        $data['symbol'] = $params['invoice']->symbol;
        $data['currency_name'] = $params['invoice']->currency_name;
        $data['name'] = $params['name'];
        $data['guardian_phone'] = $params['guardian_phone'];
        $response = $this->paypal_payment->success($data, "student");

        $paypalResponse = $response->getData();
        if ($response->isSuccessful()) {
            $purchaseId = $_GET['PayerID'];

            if (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
                if ($purchaseId) {
                    $params = $this->session->userdata('params');
                    $ref_id = $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'];
                    $json_array = array(
                        'amount' => $params['total'],
                        'date' => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine' => $params['fine_amount_balance'],
                        'description' => "Online fees deposit through Paypal Ref ID: " . $ref_id,
                        'received_by' => '',
                        'payment_mode' => 'Paypal',
                    );

                    $data = array(
                        'student_fees_master_id' => $params['student_fees_master_id'],
                        'fee_groups_feetype_id' => $params['fee_groups_feetype_id'],
                        'amount_detail' => $json_array
                    );
                    $send_to = $params['guardian_phone'];
                    $inserted_id = $this->studentfeemaster_model->fee_deposit($data, $send_to);
                    $invoice_detail = json_decode($inserted_id);
                    $sender_details = array('firstname' => $online_data->firstname, 'lastname' => $online_data->lastname, 'email' => $online_data->email,'date'=>$date,'reference_no'=>$online_data->reference_no,'mobileno'=>$online_data->mobileno,'paid_amount'=>$amount);
                    $this->mailsmsconf->mailsms('online_admission_fees_submission', $sender_details);
                    redirect(base_url("students/payment/successinvoice/".$online_data->reference_no));
                }
            }
        } elseif ($response->isRedirect()) {
            $response->redirect();
        } else {
            redirect(base_url("students/payment/paymentfailed/".$online_data->reference_no));
        }
    }

}

?>