<?php

class studentidcard extends Admin_Controller {

    function __construct() {
        parent::__construct();

        $this->load->library('Customlib');
    }

    public function index() {

        if (!$this->rbac->hasPrivilege('student_id_card', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Certificate');
        $this->session->set_userdata('sub_menu', 'admin/studentidcard');
        $this->data['idcardlist'] = $this->Student_id_card_model->idcardlist();
        $this->load->view('layout/header');
        $this->load->view('admin/certificate/createidcard', $this->data);
        $this->load->view('layout/footer');
    }

    public function create() {

        if (!$this->rbac->hasPrivilege('student_id_card', 'can_add')) {
            access_denied();
        }

        $data['title'] = 'Student ID Card';
        $this->form_validation->set_rules('school_name', $this->lang->line('school_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('address', $this->lang->line('address_phone_email'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('title', $this->lang->line('id_card_title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('background_image',$this->lang->line('background_image'), 'callback_handle_upload[background_image]');
        $this->form_validation->set_rules('logo_img', $this->lang->line('logo'), 'callback_handle_upload[logo_img]');
        $this->form_validation->set_rules('sign_image', $this->lang->line('signature'), 'callback_handle_upload[sign_image]');

        if ($this->form_validation->run() == FALSE) {
            $this->data['idcardlist'] = $this->Student_id_card_model->idcardlist();
            $this->load->view('layout/header');
            $this->load->view('admin/certificate/createidcard', $this->data);
            $this->load->view('layout/footer');
        } else {
            $admission_no = 0;
            $studentname = 0;
            $class = 0;
            $fathername = 0;
            $mothername = 0;
            $address = 0;
            $phone = 0;
            $dob = 0;
            $bloodgroup = 0;
            $vertical_card =0;
            if ($this->input->post('is_active_admission_no') == 1) {
                $admission_no = $this->input->post('is_active_admission_no');
            }
            if ($this->input->post('is_active_student_name') == 1) {
                $studentname = $this->input->post('is_active_student_name');
            }
            if ($this->input->post('is_active_class') == 1) {
                $class = $this->input->post('is_active_class');
            }
            if ($this->input->post('is_active_father_name') == 1) {
                $fathername = $this->input->post('is_active_father_name');
            }
            if ($this->input->post('is_active_mother_name') == 1) {
                $mothername = $this->input->post('is_active_mother_name');
            }
            if ($this->input->post('is_active_address') == 1) {
                $address = $this->input->post('is_active_address');
            }
            if ($this->input->post('is_active_phone') == 1) {
                $phone = $this->input->post('is_active_phone');
            }
            if ($this->input->post('is_active_dob') == 1) {
                $dob = $this->input->post('is_active_dob');
            }
            if ($this->input->post('is_active_blood_group') == 1) {
                $bloodgroup = $this->input->post('is_active_blood_group');
            }
            $enable_vertical_card=$this->input->post('enable_vertical_card');
             if (isset($enable_vertical_card)) {
                $vertical_card = 1;
            }
            $data = array(
                'title' => $this->input->post('title'),
                'school_name' => $this->input->post('school_name'),
                'school_address' => $this->input->post('address'),
                'header_color' => $this->input->post('header_color'),
                'enable_admission_no' => $admission_no,
                'enable_student_name' => $studentname,
                'enable_class' => $class,
                'enable_fathers_name' => $fathername,
                'enable_mothers_name' => $mothername,
                'enable_address' => $address,
                'enable_phone' => $phone,
                'enable_dob' => $dob,
                'enable_blood_group' => $bloodgroup,
                'enable_vertical_card' => $vertical_card,
                'status' => 1,
            );
            $insert_id = $this->Student_id_card_model->addidcard($data);

            if (!empty($_FILES['background_image']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/background/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['background_image']['name'];

                $config['file_name'] = "background" . $insert_id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('background_image')) {
                    $uploadData = $this->upload->data();
                    $background = $uploadData['file_name'];
                } else {
                    $background = '';
                }
            } else {
                $background = '';
            }

            if (!empty($_FILES['logo_img']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/logo/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['logo_img']['name'];

                $config['file_name'] = "logo" . $insert_id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('logo_img')) {
                    $uploadData = $this->upload->data();
                    $logo_img = $uploadData['file_name'];
                } else {
                    $logo_img = '';
                }
            } else {
                $logo_img = '';
            }

            if (!empty($_FILES['sign_image']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/signature/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['sign_image']['name'];

                $config['file_name'] = "sign" . $insert_id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('sign_image')) {
                    $uploadData = $this->upload->data();
                    $sign_image = $uploadData['file_name'];
                } else {
                    $sign_image = '';
                }
            } else {
                $sign_image = '';
            }

            $upload_data = array('id' => $insert_id, 'logo' => $logo_img, 'background' => $background, 'sign_image' => $sign_image);
            $this->Student_id_card_model->addidcard($upload_data);

            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/studentidcard/index');
        }
    }

    public function handle_upload($str, $var)
    {

        $image_validate = $this->config->item('image_validate');
        $result         = $this->filetype_model->get();
        if (isset($_FILES[$var]) && !empty($_FILES[$var]['name'])) {

            $file_type = $_FILES[$var]['type'];
            $file_size = $_FILES[$var]["size"];
            $file_name = $_FILES[$var]["name"];

            $allowed_extension = array_map('trim', array_map('strtolower', explode(',', $result->image_extension)));
            $allowed_mime_type = array_map('trim', array_map('strtolower', explode(',', $result->image_mime)));
            $ext               = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($files = @getimagesize($_FILES[$var]['tmp_name'])) {

                if (!in_array($files['mime'], $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }

                if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('extension_not_allowed'));
                    return false;
                }

                if ($file_size > $result->image_size) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($image_validate['upload_size'] / 1048576, 2) . " MB");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed') . " " . $this->lang->line('or') . " " . $this->lang->line('extension_not_allowed'));
                return false;
            }

            return true;
        }
        return true;
    }

    function edit($id) {
        if (!$this->rbac->hasPrivilege('student_id_card', 'can_edit')) {
            access_denied();
        }

        $data['title'] = 'Edit ID Card';
        $data['id'] = $id;
        $editidcard = $this->Student_id_card_model->get($id);
        $this->data['editidcard'] = $editidcard;
        $this->form_validation->set_rules('school_name', $this->lang->line('school_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('address', $this->lang->line('address_phone_email'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('title', $this->lang->line('id_card_title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('background_image',$this->lang->line('background_image'), 'callback_handle_upload[background_image]');
        $this->form_validation->set_rules('logo_img', $this->lang->line('logo'), 'callback_handle_upload[logo_img]');
        $this->form_validation->set_rules('sign_image', $this->lang->line('signature'), 'callback_handle_upload[sign_image]');
        if ($this->form_validation->run() == FALSE) {
            $this->data['idcardlist'] = $this->Student_id_card_model->idcardlist();
            $this->load->view('layout/header');
            $this->load->view('admin/certificate/studentidcardedit', $this->data);
            $this->load->view('layout/footer');
        } else {
            $admission_no = 0;
            $studentname = 0;
            $class = 0;
            $fathername = 0;
            $mothername = 0;
            $address = 0;
            $phone = 0;
            $dob = 0;
            $bloodgroup = 0;
            $vertical_card=0;

            if ($this->input->post('is_active_admission_no') == 1) {
                $admission_no = $this->input->post('is_active_admission_no');
            }
            if ($this->input->post('is_active_student_name') == 1) {
                $studentname = $this->input->post('is_active_student_name');
            }
            if ($this->input->post('is_active_class') == 1) {
                $class = $this->input->post('is_active_class');
            }
            if ($this->input->post('is_active_father_name') == 1) {
                $fathername = $this->input->post('is_active_father_name');
            }
            if ($this->input->post('is_active_mother_name') == 1) {
                $mothername = $this->input->post('is_active_mother_name');
            }
            if ($this->input->post('is_active_address') == 1) {
                $address = $this->input->post('is_active_address');
            }
            if ($this->input->post('is_active_phone') == 1) {
                $phone = $this->input->post('is_active_phone');
            }
            if ($this->input->post('is_active_dob') == 1) {
                $dob = $this->input->post('is_active_dob');
            }
            if ($this->input->post('is_active_blood_group') == 1) {
                $bloodgroup = $this->input->post('is_active_blood_group');
            }
            $enable_vertical_card=$this->input->post('enable_vertical_card');
             if (isset($enable_vertical_card)) {
                $vertical_card = 1;
            }

            if (!empty($_FILES['background_image']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/background/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['background_image']['name'];

                $config['file_name'] = "background" . $id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('background_image')) {
                    $uploadData = $this->upload->data();
                    $background = $uploadData['file_name'];
                } else {
                    $background = $this->input->post('old_background');
                }
            } else {
                $background = $this->input->post('old_background');
            }

            if (!empty($_FILES['logo_img']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/logo/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['logo_img']['name'];

                $config['file_name'] = "logo" . $id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('logo_img')) {
                    $uploadData = $this->upload->data();
                    $logo_img = $uploadData['file_name'];
                } else {
                    $logo_img = $this->input->post('old_logo_img');
                }
            } else {
                $logo_img = $this->input->post('old_logo_img');
            }

            if (!empty($_FILES['sign_image']['name'])) {
                $config['upload_path'] = 'uploads/student_id_card/signature/';
                $config['allowed_types'] = 'jpg|jpeg|png|gif';

                $file_name = $_FILES['sign_img']['name'];

                $config['file_name'] = "sign" . $id;
                //Load upload library and initialize configuration
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('sign_image')) {
                    $uploadData = $this->upload->data();
                    $sign_image = $uploadData['file_name'];
                } else {
                    $sign_image = $this->input->post('old_sign_image');
                }
            } else {
                $sign_image = $this->input->post('old_sign_image');
            }

            $data = array(
                'id' => $this->input->post('id'),
                'title' => $this->input->post('title'),
                'school_name' => $this->input->post('school_name'),
                'school_address' => $this->input->post('address'),
                'background' => $background,
                'logo' => $logo_img,
                'sign_image' => $sign_image,
                'header_color' => $this->input->post('header_color'),
                'enable_admission_no' => $admission_no,
                'enable_student_name' => $studentname,
                'enable_class' => $class,
                'enable_fathers_name' => $fathername,
                'enable_mothers_name' => $mothername,
                'enable_address' => $address,
                'enable_phone' => $phone,
                'enable_dob' => $dob,
                'enable_blood_group' => $bloodgroup,
                'enable_vertical_card'=>$vertical_card,
                'status' => 1,
            );

            $this->Student_id_card_model->addidcard($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/studentidcard');
        }
    }

    function delete($id) {
        $data['title'] = 'Certificate List';
        $this->Student_id_card_model->remove($id);
        $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/studentidcard/index');
    }

    public function view() {
        $id = $this->input->post('certificateid');
        $output = '';
        $data['idcard'] = $this->Student_id_card_model->idcardbyid($id);
        $this->load->view('admin/certificate/studentidcardpreview', $data);
          }
}
?>