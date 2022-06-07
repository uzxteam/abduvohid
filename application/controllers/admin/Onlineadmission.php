<?php
    class Onlineadmission extends Admin_Controller
    {
         public $sch_setting_detail = array();

    public function __construct()
    {
        parent::__construct();
       
        $this->load->model("onlinestudent_model");
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $this->role;
    }

        public function admissionsetting()
    {
        $this->session->set_userdata('top_menu', 'System Settings');
        $this->session->set_userdata('sub_menu', 'System Settings/onlineadmissionsetting');
        $data                    = array();
        $data['result']          = $this->setting_model->getSetting();
        $data['fields']          = get_onlineadmission_editable_fields();
        $data['inserted_fields'] = $this->onlinestudent_model->getformfields();
        $data['sch_setting_detail'] = $this->sch_setting_detail;
        $data['custom_fields']=$this->onlinestudent_model->getcustomfields();

        if(!empty($this->input->post('submitbtn'))){

            if($this->input->post('online_admission_payment')=='yes'){

            $this->form_validation->set_rules(
                'online_admission_amount', $this->lang->line('amount'), array('required', 'xss_clean',
                    array('check_exists', array($this->onlinestudent_model, 'validate_paymentamount')),
                )
            );
           
            if($this->form_validation->run()==true){
                    $data = array(
                    'online_admission'               => $this->input->post('online_admission'),
                    'online_admission_payment'       => $this->input->post('online_admission_payment'),
                    'online_admission_amount'        => $this->input->post('online_admission_amount'),
                    'online_admission_instruction'   => $this->input->post('online_admission_instruction'),
                    'online_admission_conditions'    => $this->input->post('online_admission_conditions'),
                    'id'                             => 1
                );
                  $this->setting_model->add($data);
                  redirect('admin/onlineadmission/admissionsetting');
            }else{

                $this->load->view("layout/header");
                $this->load->view("admin/onlineadmission/onlineadmission_setting", $data);
                $this->load->view("layout/footer");
            }
        
         }   else{
                
              $data = array(
                'online_admission'          => $this->input->post('online_admission'),
                'online_admission_payment'  => 'no',
                'id'=>1);
               $this->setting_model->add($data);
                 redirect('admin/onlineadmission/admissionsetting');
         }
        
        }else{
            $this->load->view("layout/header");
            $this->load->view("admin/onlineadmission/onlineadmission_setting", $data);
            $this->load->view("layout/footer");
        }


        
    }

    public function changeformfieldsetting()
    { 
        
        $this->form_validation->set_rules('name', $this->lang->line('student'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('status', $this->lang->line('status'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) { 

            $msg = array(
                'status' => form_error('status'),
                'name'   => form_error('name'),
            );

            $array = array('status' => '0', 'error' => $msg, 'msg' => $this->lang->line('something_went_wrong'));

        } else {
            $insert = array(
                'name'   => $this->input->post('name'),
                'status' => $this->input->post('status'),
            );


            $this->onlinestudent_model->addformfields($insert);

            if($this->input->post('name')=='if_guardian_is'){

                $status = $this->input->post('status');
               $this->onlinestudent_model->editguardianfield($status);
            }

            $array = array('status' => '1', 'error' => '', 'msg' => $this->lang->line('success_message'));
        }

        echo json_encode($array);
    }

    
            
    }
 ?>