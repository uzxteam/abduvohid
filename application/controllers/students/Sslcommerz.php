<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sslcommerz extends Student_Controller
{
    public $api_config = "";
    public function __construct()
    {
        parent::__construct();

        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting    = $this->setting_model->get();
      
    }
 
    public function index()
    {

        $data = array();
        $data['params'] = $this->session->userdata('params');

        $data['setting'] = $this->setting;
        $data['api_error'] ='';
        $data['student_data'] = $this->student_model->get($data['params']['student_id']);
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $this->load->view('student/sslcommerz/index', $data);

    }

    public function pay()
    {
        $params       = $this->session->userdata('params');
        $student_data = $this->student_model->get($params['student_id']);

        $requestData        = array();
        $CURLOPT_POSTFIELDS = array(
            'store_id'         => $this->api_config->api_publishable_key,
            'store_passwd'     => $this->api_config->api_password,
            'total_amount'     => number_format((float) ($params['fine_amount_balance'] + $params['total']), 2, '.', ''),
            'currency'         => $params['invoice']->currency_name,
            'tran_id'          => abs(crc32(uniqid())),
            'success_url'      => base_url() . 'students/sslcommerz/success',
            'fail_url'         => base_url() . 'students/sslcommerz/fail',
            'cancel_url'       => base_url() . 'students/sslcommerz/cancel',
            'cus_name'         => $params['name'],
            'cus_email'        => !empty($_POST['email']) ? $_POST['email'] : "example@email.com",
            'cus_add1'         => !empty($student_data['permanent_address']) ? $student_data['permanent_address'] : "Dhaka",
            'cus_phone'        => !empty($_POST['phone']) ? $_POST['phone'] : "01711111111",
            'cus_city'         => '',
            'cus_country'      => '',
            'multi_card_name'  => 'mastercard,visacard,amexcard,internetbank,mobilebank,othercard ',
            'shipping_method'  => 'NO',
            'product_name'     => 'test',
            'product_category' => 'Electronic',
            'product_profile'  => 'general',
        );
        $string = "";
        foreach ($CURLOPT_POSTFIELDS as $key => $value) {
            $string .= $key . '=' . $value . "&";
            if ($key == 'product_profile') {
                $string .= $key . '=' . $value;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//https://securepay.sslcommerz.com/gwprocess/v4/api.php
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$string");

        $headers   = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response = json_decode($result);
        
        if($response->status=='FAILED'){
          $data = array();
        $data['params'] = $this->session->userdata('params');
        $data['setting'] = $this->setting;
        $data['api_error'] = $response->failedreason;
        $data['student_data'] = $this->student_model->get($data['params']['student_id']);
        $data['student_fees_master_array']=$data['params']['student_fees_master_array'];
        $this->load->view('student/sslcommerz/index', $data);
        }else{
            header("Location: $response->GatewayPageURL");
        }

    }

    public function success()
    {

        if ($_POST['status'] == 'VALID') {
            $params = $this->session->userdata('params');

            $payment_id = $_POST['val_id'];
            $bulk_fees=array();
                    $params     = $this->session->userdata('params');
                 
                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {
                   
                     $json_array = array(
                        'amount'          =>  $fee_value['amount_balance'],
                        'date'            => date('Y-m-d'),
                        'amount_discount' => 0,
                        'amount_fine'     => $fee_value['fine_balance'],
                        'description'     => "Online fees deposit through Sslcommerz TXN ID: " . $payment_id,
                        'received_by'     => '',
                        'payment_mode'    => 'Sslcommerz',
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
            redirect(base_url("students/payment/successinvoice/" . $invoice_detail->invoice_id . "/" . $invoice_detail->sub_invoice_id));
        } else {

            redirect(base_url("students/payment/paymentfailed"));
        }

    }

    public function fail()
    {

        redirect(base_url("students/payment/paymentfailed"));

    }
    public function cancel()
    {

        redirect(base_url("students/payment/paymentfailed"));

    }

}
