<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends Front_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->config('form-builder');
        $this->load->config('app-config');
        $this->load->library(array('mailer', 'form_builder', 'mailsmsconf'));
        $this->load->model(array('frontcms_setting_model', 'complaint_Model', 'Visitors_model', 'onlinestudent_model', 'filetype_model', 'customfield_model'));
        $this->blood_group = $this->config->item('bloodgroup');
        $this->load->library('Ajax_pagination');
        $this->load->library('module_lib');
        $this->load->library('captchalib');
        $this->load->helper('customfield');
        $this->banner_content         = $this->config->item('ci_front_banner_content');
        $this->perPage                = 12;
        $ban_notice_type              = $this->config->item('ci_front_notice_content');
        $this->sch_setting_detail     = $this->setting_model->getSetting();
        $this->data['banner_notices'] = $this->cms_program_model->getByCategory($ban_notice_type, array('start' => 0, 'limit' => 5));

    }

    public function show_404()
    {
        $this->load->view('errors/error_message');
    }

    public function index()
    {

        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);

        reset($this->data['main_menus']);
        $first_key                    = key($this->data['main_menus']);
        $home_page_slug               = $this->data['main_menus'][$first_key]['page_slug'];
        $setting                      = $this->frontcms_setting_model->get();
        $this->data['active_menu']    = $home_page_slug;
        $this->data['page_side_bar']  = $setting->is_active_sidebar;
        $this->data['cookie_consent'] = $setting->cookie_consent;
        $result                       = $this->cms_program_model->getByCategory($this->banner_content);
        $this->data['page']           = $this->cms_page_model->getBySlug($home_page_slug);
        if (!empty($result)) {
            $this->data['banner_images'] = $this->cms_program_model->front_cms_program_photos($result[0]['id']);
        }

        $this->load_theme('home');
    }

    public function page($slug)
    {
        $page = $this->cms_page_model->getBySlug(urldecode($slug));
        if (!$page) {
            $this->data['page'] = $this->cms_page_model->getBySlug('404-page');
        } else {

            $this->data['page'] = $page;
        }

        if ($page['is_homepage']) {
            redirect('frontend');
        }
        $this->data['active_menu']   = $slug;
        $this->data['page_side_bar'] = $this->data['page']['sidebar'];

        $this->data['page_content_type'] = "";
        if (!empty($this->data['page']['category_content'])) {
            $content_array = $this->data['page']['category_content'];
            reset($content_array);
            $first_key = key($content_array);
            $totalRec  = $this->cms_program_model->getByCategory($content_array[$first_key]);
            if (!empty($totalRec)) {
                $totalRec = count($totalRec);
            } else {
                $totalRec = 0;
            }

            $config['target']     = '#postList';
            $config['base_url']   = base_url() . 'welcome/ajaxPaginationData';
            $config['total_rows'] = $totalRec;
            $config['per_page']   = $this->perPage;
            $config['link_func']  = 'searchFilter';
            $this->ajax_pagination->initialize($config);
            //get the posts data
            $this->data['page']['category_content'][$first_key] = $this->cms_program_model->getByCategory($content_array[$first_key], array('limit' => $this->perPage));

            $this->data['page_content_type'] = $content_array[$first_key];
            //load the view
        }
        $this->data['page_form'] = false;

        if (strpos($page['description'], '[form-builder:') !== false) {
            $this->data['page_form'] = true;
            $start                   = '[form-builder:';
            $end                     = ']';

            $form_name = $this->customlib->getFormString($page['description'], $start, $end);

            $form = $this->config->item($form_name);

            $this->data['form_name'] = $form_name;
            $this->data['form']      = $form;

            if (!empty($form)) {
                foreach ($form as $form_key => $form_value) {

                    if (isset($form_value['validation'])) {
                        $display_string = ucfirst(preg_replace('/[^A-Za-z0-9\-]/', ' ', $form_value['id']));
                        if ($form_value['id'] == "captcha") {
                            $this->form_validation->set_rules($form_value['id'], $display_string, $form_value['validation']);
                        } else {
                            $this->form_validation->set_rules($form_value['id'], $display_string, $form_value['validation']);
                        }
                    }
                }

                if ($this->form_validation->run() == false) {

                } else {
                    $setting = $this->frontcms_setting_model->get();

                    $response_message = $form['email_title']['mail_response'];
                    $record           = $this->input->post();

                    if ($record['form_name'] == 'contact_us') {
                        $email     = $this->input->post('email');
                        $name      = $this->input->post('name');
                        $cont_data = array(
                            'name'    => $name . " <a href='mailto:$email'>(" . $email . ")</a>",
                            'source'  => 'Online',
                            'email'   => $this->input->post('email'),
                            'purpose' => $this->input->post('subject'),
                            'date'    => date('Y-m-d'),
                            'note'    => $this->input->post('description') . " (Sent from online front site)",
                        );
                        $visitor_id = $this->Visitors_model->add($cont_data);
                    }

                    if ($record['form_name'] == 'complain') {
                        $complaint_data = array(
                            'complaint_type' => 'General',
                            'source'         => 'Online',
                            'name'           => $this->input->post('name'),
                            'email'          => $this->input->post('email'),
                            'contact'        => $this->input->post('contact_no'),
                            'date'           => date('Y-m-d'),
                            'description'    => $this->input->post('description'),
                        );
                        $complaint_id = $this->complaint_Model->add($complaint_data);
                    }

                    $email_subject = $record['email_title'];
                    $mail_body     = "";
                    unset($record['email_title']);
                    unset($record['submit']);
                    foreach ($record as $fetch_k_record => $fetch_v_record) {
                        $mail_body .= ucwords($fetch_k_record) . ": " . $fetch_v_record;
                        $mail_body .= "<br/>";
                    }
                    if (!empty($setting) && $setting->contact_us_email != "") {

                        $this->mailer->send_mail($setting->contact_us_email, $email_subject, $mail_body);
                    }

                    $this->session->set_flashdata('msg', $response_message);
                    redirect('page/' . $slug, 'refresh');
                }
            }
        }

        $this->load_theme('pages/page');
    }

    public function ajaxPaginationData()
    {
        $page              = $this->input->post('page');
        $page_content_type = $this->input->post('page_content_type');
        if (!$page) {
            $offset = 0;
        } else {
            $offset = $page;
        }
        $data['page_content_type'] = $page_content_type;
        //total rows count
        $totalRec = count($this->cms_program_model->getByCategory($page_content_type));
        //pagination configuration
        $config['target']     = '#postList';
        $config['base_url']   = base_url() . 'welcome/ajaxPaginationData';
        $config['total_rows'] = $totalRec;
        $config['per_page']   = $this->perPage;
        $config['link_func']  = 'searchFilter';
        $this->ajax_pagination->initialize($config);
        //get the posts data
        $data['category_content'] = $this->cms_program_model->getByCategory($page_content_type, array('start' => $offset, 'limit' => $this->perPage));
        //load the view
        $this->load->view('themes/default/pages/ajax-pagination-data', $data, false);
    }

    public function read($slug)
    {

        $this->data['active_menu'] = 'home';
        $page                      = $this->cms_program_model->getBySlug(urldecode($slug));

        $this->data['page_side_bar']  = $page['sidebar'];
        $this->data['featured_image'] = $page['feature_image'];
        $this->data['page']           = $page;
        $this->load_theme('pages/read');
    }

    public function getSections()
    {

        $class_id = $this->input->post('class_id');
        $data     = $this->section_model->getClassBySectionAll($class_id);
        echo json_encode($data);
    }

    public function admission()
    {

        if ($this->module_lib->hasActive('online_admission')) {
            $this->data['active_menu'] = 'online_admission';
            $page                      = array('title' => 'Online Admission Form', 'meta_title' => 'online admission form', 'meta_keyword' => 'online admission form', 'meta_description' => 'online admission form');

            $this->data['page_side_bar']  = false;
            $this->data['featured_image'] = false;
            $this->data['page']           = $page;
            ///============
            $this->data['form_admission'] = $this->setting_model->getOnlineAdmissionStatus();
            $genderList                   = $this->customlib->getGender();
            $this->data['genderList']     = $genderList;
            $this->data['title']          = 'Add Student';
            $this->data['title_list']     = 'Online Admission Form';
            $data["student_categorize"]   = 'class';
            $session                      = $this->setting_model->getCurrentSession();
            $class                        = $this->class_model->getAll();
            $this->data['classlist']      = $class;
            $this->data['sch_setting']    = $this->sch_setting_detail;
            $category                     = $this->category_model->get();
            $this->data['categorylist']   = $category;
            $this->data["bloodgroup"]     = $this->blood_group;
            $houses                       = $this->student_model->gethouselist();
            $this->data['houses']         = $houses;
            $reference_no                 = "";
            $refence_status               = "";
            $sch_setting                  = $this->sch_setting_detail;

            $this->data['online_admission_instruction'] = $sch_setting->online_admission_instruction;
            if ($this->captchalib->is_captcha('admission')) {
                $this->form_validation->set_rules('captcha', 'Captcha', 'trim|required|callback_check_captcha');
            }

            if ($this->customlib->getfieldstatus('student_email')) {
                $this->form_validation->set_rules(
                    'email', $this->lang->line('email'), array(
                        'valid_email', 'required',
                        array('check_student_email_exists', array($this->onlinestudent_model, 'check_student_email_exists')),
                    )
                );
            }

            $this->form_validation->set_rules('firstname', $this->lang->line('first_name'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('dob', $this->lang->line('date_of_birth'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('gender', $this->lang->line('gender'), 'trim|required|xss_clean');

            if ($this->customlib->getfieldstatus('if_guardian_is')) {
                $this->form_validation->set_rules('guardian_is', $this->lang->line('guardian'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('guardian_name', $this->lang->line('guardian_name'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('guardian_relation', $this->lang->line('guardian_relation'), 'trim|required|xss_clean');
            }

            if (!empty($_FILES['document']['name'])) {
                $this->form_validation->set_rules('document', $this->lang->line('documents'), 'callback_document_handle_upload[document]');
            }

            if (!empty($_FILES['father_pic']['name'])) {

                $this->form_validation->set_rules('father_pic', $this->lang->line('father') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[father_pic]');
            }
            if (!empty($_FILES['mother_pic']['name'])) {
                $this->form_validation->set_rules('mother_pic', $this->lang->line('mother') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[mother_pic]');
            }

            if (!empty($_FILES['file']['name'])) {
                $this->form_validation->set_rules('file', $this->lang->line('student') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[file]');
            }
            if (!empty($_FILES['guardian_pic']['name'])) {

                $this->form_validation->set_rules('guardian_pic', $this->lang->line('guardian') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[guardian_pic]');
            }

            $custom_fields = $this->customfield_model->getByBelong('students');

            foreach ($custom_fields as $custom_fields_key => $custom_fields_value) {
                if ($custom_fields_value['validation'] && $this->customlib->getfieldstatus($custom_fields_value['name'])) {
                    $custom_fields_id   = $custom_fields_value['id'];
                    $custom_fields_name = $custom_fields_value['name'];
                    $this->form_validation->set_rules("custom_fields[students][" . $custom_fields_id . "]", $custom_fields_name, 'trim|required');
                }
            }

            if ($this->form_validation->run() == false) {

                $this->load_theme('pages/admission', $this->config->item('front_layout'));
            } else {
                //==============

                $document_validate = true;

                $custom_field_post  = $this->input->post("custom_fields[students]");
                $custom_value_array = array();
                if (!empty($custom_field_post)) {

                    foreach ($custom_field_post as $key => $value) {
                        $check_field_type = $this->input->post("custom_fields[students][" . $key . "]");
                        $field_value      = is_array($check_field_type) ? implode(",", $check_field_type) : $check_field_type;
                        $array_custom     = array(
                            'belong_table_id' => 0,
                            'custom_field_id' => $key,
                            'field_value'     => $field_value,
                        );
                        $custom_value_array[] = $array_custom;
                    }
                }

                //=====================
                if ($document_validate) {

                    $class_id   = $this->input->post('class_id');
                    $section_id = $this->input->post('section_id');

                    $data = array(
                        'firstname'        => $this->input->post('firstname'),
                        'class_section_id' => $this->input->post('section_id'),
                        'dob'              => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('dob'))),
                        'gender'           => $this->input->post('gender'),
                    );
                    // for inserting system fields

                    if ($this->customlib->getfieldstatus('if_guardian_is')) {
                        $data['guardian_is'] = $this->input->post('guardian_is');

                        $data['guardian_name']     = $this->input->post('guardian_name');
                        $data['guardian_relation'] = $this->input->post('guardian_relation');
                        $data['guardian_phone']    = $this->input->post('guardian_phone');

                        if ($this->customlib->getfieldstatus('guardian_occupation')) {
                            $data['guardian_occupation'] = $this->input->post('guardian_occupation');
                        }
                        if ($this->customlib->getfieldstatus('guardian_email')) {
                            $data['guardian_email'] = $this->input->post('guardian_email');
                        }
                        if ($this->customlib->getfieldstatus('guardian_address')) {
                            $data['guardian_address'] = $this->input->post('guardian_address');
                        }

                    }

                    $middlename       = $this->input->post('middlename');
                    $lastname         = $this->input->post('lastname');
                    $mobileno         = $this->input->post('mobileno');
                    $email            = $this->input->post('email');
                    $category_id      = $this->input->post('category_id');
                    $religion         = $this->input->post('religion');
                    $cast             = $this->input->post('cast');
                    $house            = $this->input->post('house');
                    $blood_group      = $this->input->post('blood_group');
                    $height           = $this->input->post('height');
                    $weight           = $this->input->post('weight');
                    $measurement_date = $this->input->post('measure_date');

                    $father_name       = $this->input->post('father_name');
                    $father_phone      = $this->input->post('father_phone');
                    $father_occupation = $this->input->post('father_occupation');

                    $mother_name       = $this->input->post('mother_name');
                    $mother_phone      = $this->input->post('mother_phone');
                    $mother_occupation = $this->input->post('mother_occupation');
                    $previous_school   = $this->input->post('previous_school');
                    $note              = $this->input->post('note');

                    $current_address   = $this->input->post('current_address');
                    $permanent_address = $this->input->post('permanent_address');

                    $bank_account_no = $this->input->post('bank_account_no');
                    $bank_name       = $this->input->post('bank_name');
                    $ifsc_code       = $this->input->post('ifsc_code');
                    $adhar_no        = $this->input->post('adhar_no');
                    $samagra_id      = $this->input->post('samagra_id');
                    $previous_school = $this->input->post('previous_school');
                    $note            = $this->input->post('note');
                    $rte             = $this->input->post('rte');

                    if (isset($middlename)) {
                        $data['middlename'] = $this->input->post('middlename');
                    }
                    if (isset($lastname)) {
                        $data['lastname'] = $this->input->post('lastname');
                    }
                    if (isset($mobileno)) {
                        $data['mobileno'] = $this->input->post('mobileno');
                    }
                    if (isset($email)) {
                        $data['email'] = $this->input->post('email');
                    }
                    if (isset($category_id)) {
                        $data['category_id'] = $this->input->post('middlename');
                    }

                    if (isset($religion)) {
                        $data['religion'] = $this->input->post('religion');
                    }
                    if (isset($cast)) {
                        $data['cast'] = $this->input->post('cast');
                    }
                    if (isset($house)) {
                        $data['school_house_id'] = $this->input->post('house');
                    }
                    if (isset($blood_group)) {
                        $data['blood_group'] = $this->input->post('blood_group');
                    }
                    if (isset($height)) {
                        $data['height'] = $this->input->post('height');
                    }
                    if (isset($weight)) {
                        $data['weight'] = $this->input->post('weight');
                    }
                    if (isset($weight)) {
                        $data['weight'] = $this->input->post('weight');
                    }
                    if (!empty($measurement_date)) {
                        $data['measurement_date'] = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('measure_date')));
                    }

                    if (isset($father_name)) {
                        $data['father_name'] = $this->input->post('father_name');
                    }
                    if (isset($father_phone)) {
                        $data['father_phone'] = $this->input->post('father_phone');
                    }
                    if (isset($father_occupation)) {
                        $data['father_occupation'] = $this->input->post('father_occupation');
                    }
                    if (isset($mother_name)) {
                        $data['mother_name'] = $this->input->post('mother_name');
                    }
                    if (isset($mother_phone)) {
                        $data['mother_phone'] = $this->input->post('mother_phone');
                    }
                    if (isset($mother_occupation)) {
                        $data['mother_occupation'] = $this->input->post('mother_occupation');
                    }
                    if ($current_address) {
                        $data['current_address'] = $this->input->post('current_address');
                    }
                    if ($permanent_address) {
                        $data['permanent_address'] = $this->input->post('permanent_address');
                    }
                    if (isset($bank_account_no)) {
                        $data['bank_account_no'] = $this->input->post('bank_account_no');
                    }
                    if (isset($bank_name)) {
                        $data['bank_name'] = $this->input->post('bank_name');
                    }
                    if (isset($ifsc_code)) {
                        $data['ifsc_code'] = $this->input->post('ifsc_code');
                    }
                    if (isset($adhar_no)) {
                        $data['adhar_no'] = $this->input->post('adhar_no');
                    }
                    if (isset($samagra_id)) {
                        $data['samagra_id'] = $this->input->post('samagra_id');
                    }
                    if (isset($note)) {
                        $data['note'] = $this->input->post('note');
                    }
                    if (isset($previous_school)) {
                        $data['previous_school'] = $this->input->post('previous_school');
                    }
                    if (isset($rte)) {
                        $data['rte'] = $this->input->post('rte');
                    }

                    do {
                        $reference_no   = mt_rand(100000, 999999);
                        $refence_status = $this->onlinestudent_model->checkreferenceno($reference_no);
                    } while ($refence_status);

                    $data['reference_no'] = $reference_no;
                    $insert_id            = $this->onlinestudent_model->add($data);
                    if (!empty($custom_value_array)) {
                        $this->customfield_model->insertRecord($custom_value_array, $insert_id);
                    }

                    if (isset($_FILES["document"]) && !empty($_FILES['document']['name'])) {

                        $time     = md5($_FILES["document"]['name'] . microtime());
                        $fileInfo = pathinfo($_FILES["document"]["name"]);
                        $doc_name = $time . '.' . $fileInfo['extension'];
                        move_uploaded_file($_FILES["document"]["tmp_name"], "./uploads/student_documents/online_admission_doc/" . $doc_name);
                        $data['document'] = $doc_name;
                        $data_img         = array('id' => $insert_id, 'document' => $doc_name);
                        $this->onlinestudent_model->edit($data_img); 
                       
                    }

                    if (isset($_FILES["file"]) && !empty($_FILES['file']['name'])) {

                        $fileInfo = pathinfo($_FILES["file"]["name"]);
                        $img_name = $insert_id . '.' . $fileInfo['extension'];
                        move_uploaded_file($_FILES["file"]["tmp_name"], "./uploads/student_images/" . $img_name);
                        $data_img = array('id' => $insert_id, 'image' => 'uploads/student_images/' . $img_name);
                        $this->onlinestudent_model->edit($data_img);

                    }

                    if (isset($_FILES["father_pic"]) && !empty($_FILES['father_pic']['name'])) {
                        $fileInfo = pathinfo($_FILES["father_pic"]["name"]);
                        $img_name = $insert_id . "father" . '.' . $fileInfo['extension'];
                        move_uploaded_file($_FILES["father_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                        $data_img = array('id' => $insert_id, 'father_pic' => 'uploads/student_images/' . $img_name);
                        $this->onlinestudent_model->edit($data_img);
                    }
                    if (isset($_FILES["mother_pic"]) && !empty($_FILES['mother_pic']['name'])) {
                        $fileInfo = pathinfo($_FILES["mother_pic"]["name"]);
                        $img_name = $insert_id . "mother" . '.' . $fileInfo['extension'];
                        move_uploaded_file($_FILES["mother_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                        $data_img = array('id' => $insert_id, 'mother_pic' => 'uploads/student_images/' . $img_name);
                        $this->onlinestudent_model->edit($data_img);
                    }
                    if (isset($_FILES["guardian_pic"]) && !empty($_FILES['guardian_pic']['name'])) {
                        $fileInfo = pathinfo($_FILES["guardian_pic"]["name"]);
                        $img_name = $insert_id . "guardian" . '.' . $fileInfo['extension'];
                        move_uploaded_file($_FILES["guardian_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                        $data_img = array('id' => $insert_id, 'guardian_pic' => 'uploads/student_images/' . $img_name);
                        $this->onlinestudent_model->edit($data_img);
                    }

                    $this->data['class_id']            = $class_id;
                    $this->data['section_id']          = $section_id;
                    $this->data['roll_no']             = $this->input->post('roll_no');
                    $this->data['mobileno']            = $this->input->post('mobileno');
                    $this->data['email']               = $this->input->post('email');
                    $this->data['firstname']           = $this->input->post('firstname');
                    $this->data['lastname']            = $this->input->post('lastname');
                    $this->data['mobileno']            = $this->input->post('mobileno');
                    $this->data['class_section_id']    = $this->input->post('section_id');
                    $this->data['guardian_is']         = $this->input->post('guardian_is');
                    $this->data['dob']                 = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('dob')));
                    $this->data['ifsc_code']           = $this->input->post('ifsc_code');
                    $this->data['bank_account_no']     = $this->input->post('bank_account_no');
                    $this->data['bank_name']           = $this->input->post('bank_name');
                    $this->data['current_address']     = $this->input->post('current_address');
                    $this->data['permanent_address']   = $this->input->post('permanent_address');
                    $this->data['father_name']         = $this->input->post('father_name');
                    $this->data['father_phone']        = $this->input->post('father_phone');
                    $this->data['father_occupation']   = $this->input->post('father_occupation');
                    $this->data['mother_name']         = $this->input->post('mother_name');
                    $this->data['mother_phone']        = $this->input->post('mother_phone');
                    $this->data['mother_occupation']   = $this->input->post('mother_occupation');
                    $this->data['guardian_occupation'] = $this->input->post('guardian_occupation');
                    $this->data['guardian_email']      = $this->input->post('guardian_email');
                    $this->data['gender']              = $this->input->post('gender');
                    $this->data['guardian_name']       = $this->input->post('guardian_name');
                    $this->data['guardian_relation']   = $this->input->post('guardian_relation');
                    $this->data['guardian_phone']      = $this->input->post('guardian_phone');
                    $this->data['guardian_address']    = $this->input->post('guardian_address');
                    $this->data['admission_id']        = $insert_id;

                    $this->session->set_userdata('validlogin', $reference_no);
                    $this->session->set_flashdata('msg', '<div class="alert alert-success">'.' '.$this->lang->line('thanks_for_registration_please_note_your_reference_number') .' '. $reference_no .' ' . $this->lang->line('for_further_communication'). '</div>');
                    redirect('welcome/online_admission_review/' . $reference_no);
                }
            }
        }
    }

    public function online_admission_review($reference_no)
    {

        $ref_status    = $this->onlinestudent_model->checkreferenceno($reference_no);
        $admin_session = $this->session->userdata('admin');

        $status = "";
        if (!empty($admin_session)) {
            $status = "admin";
        }
        if ($this->session->userdata('validlogin') != $reference_no && $status != "admin") {
            exit('No direct script access allowed');
        }

        if ($ref_status) {

            $this->data['active_menu'] = 'online-admission';
            $page                      = array('title' => 'online admission review', 'meta_title' => 'online admission review', 'meta_keyword' => 'online admission review', 'meta_description' => 'online admission review');

            $this->data['page_side_bar']  = false;
            $this->data['featured_image'] = false;
            $this->data['page']           = $page;
            $this->data['meta_title']     = 'Oline Admission Review';
            ///============
            $this->data['status']           = $status;
            $this->data['form_admission']   = $this->setting_model->getOnlineAdmissionStatus();
            $genderList                     = $this->customlib->getGender();
            $this->data['genderList']       = $genderList;
            $this->data['title']            = 'Add Student';
            $this->data['title_list']       = 'Recently Added Student';
            $data["student_categorize"]     = 'class';
            $session                        = $this->setting_model->getCurrentSession();
            $id                             = $this->onlinestudent_model->getidbyrefno($reference_no);
            $class                          = $this->class_model->getAll();
            $this->data['classlist']        = $class;
            $this->data['sch_setting']      = $this->sch_setting_detail;
            $category                       = $this->category_model->get();
            $this->data['categorylist']     = $category;
            $result                         = $this->onlinestudent_model->get($id);
            $classresult                    = $this->onlinestudent_model->getclassbyclasssectionid($result['class_section_id']);
            $class_id                       = $classresult['class_id'];
            $class_name                     = $classresult['class'];
            $this->data['class_name']       = $class_name;
            $this->data['class_section_id'] = $result['section_id'];
            $this->data['firstname']        = $result['firstname'];
            $this->data['middlename']       = $result['middlename'];
            $this->data['lastname']         = $result['lastname'];
            $this->data['gender']           = $result['gender'];
            if ($result['dob'] != null && $result['dob'] != '0000-00-00') {
                $this->data['dob'] = $result['dob'];
            } else {
                $this->data['dob'] = "";
            }
            $this->data['mobileno']    = $result['mobileno'];
            $this->data['email']       = $result['email'];
            $this->data['category_id'] = $result['category_id'];
            $this->data['category']    = $result['category'];
            $this->data['religion']    = $result['religion'];
            $this->data['cast']        = $result['cast'];
            if ($result['school_house_id'] != 0) {
                $this->data['house_name'] = $this->customlib->gethousename($result['school_house_id']);
            } else {
                $this->data['house_name'] = "";
            }
            $this->data['house_id']    = $result['school_house_id'];
            $this->data['blood_group'] = $result['blood_group'];
            $this->data['height']      = $result['height'];
            $this->data['weight']      = $result['weight'];

            if ($result['measurement_date'] != null && $result['measurement_date'] != '0000-00-00') {
                $this->data['measurement_date'] = date($this->customlib->dateformat($result['measurement_date']));
            } else {
                $this->data['measurement_date'] = "";
            }
            $this->data['student_pic'] = $result['image'];

            $this->data['father_name']       = $result['father_name'];
            $this->data['father_phone']      = $result['father_phone'];
            $this->data['father_occupation'] = $result['father_occupation'];
            $this->data['father_pic']        = $result['father_pic'];
            $this->data['mother_name']       = $result['mother_name'];
            $this->data['mother_phone']      = $result['mother_phone'];
            $this->data['mother_occupation'] = $result['mother_occupation'];
            $this->data['mother_pic']        = $result['mother_pic'];

            $this->data['guardian_is']         = $result['guardian_is'];
            $this->data['guardian_name']       = $result['guardian_name'];
            $this->data['guardian_relation']   = $result['guardian_relation'];
            $this->data['guardian_email']      = $result['guardian_email'];
            $this->data['guardian_pic']        = $result['guardian_pic'];
            $this->data['guardian_phone']      = $result['guardian_phone'];
            $this->data['guardian_occupation'] = $result['guardian_occupation'];
            $this->data['guardian_address']    = $result['guardian_address'];

            $this->data['current_address']   = $result['current_address'];
            $this->data['permanent_address'] = $result['permanent_address'];

            $this->data['bank_account_no'] = $result['bank_account_no'];
            $this->data['bank_name']       = $result['bank_name'];
            $this->data['ifsc_code']       = $result['ifsc_code'];
            $this->data['adhar_no']        = $result['adhar_no'];
            $this->data['samagra_id']      = $result['samagra_id'];

            $this->data['previous_school'] = $result['previous_school'];
            $this->data['note']            = $result['note'];
            $this->data['rte']             = $result['rte'];
            $this->data['reference_no']    = $result['reference_no'];
            $this->data['transaction_id']  = $this->customlib->gettransactionid($result['id']);

            $this->data['form_status']  = $result['form_status'];
            $this->data['paid_status']  = $result['paid_status'];
            $this->data['admission_id'] = $id;
            $this->data['reference_no'] = $result['reference_no'];
            $this->data['id']           = $id;

            $this->data['online_admission_payment'] = $this->sch_setting_detail->online_admission_payment;
            $this->data['online_admission_amount']  = $this->sch_setting_detail->online_admission_amount;
            $this->data['online_admission_conditions']  = $this->sch_setting_detail->online_admission_conditions;
			
			
            $this->load_theme('pages/online_admission_review', $this->config->item('front_layout'));

        } else {
            $this->show_404();
        }
    }

    public function editonlineadmission($reference_no)
    {
        $ref_status = $this->onlinestudent_model->checkreferenceno($reference_no);
        if ($ref_status) {

            $this->data['active_menu'] = 'online-admission';
            $page                      = array('title' => 'Online Admission Form', 'meta_title' => 'online admission form', 'meta_keyword' => 'online admission form', 'meta_description' => 'online admission form');

            $this->data['page_side_bar']  = false;
            $this->data['featured_image'] = false;
            $this->data['page']           = $page;
            ///============
            $this->data['form_admission'] = $this->setting_model->getOnlineAdmissionStatus();
            $genderList                   = $this->customlib->getGender();
            $this->data['genderList']     = $genderList;
            $this->data['title']          = 'Add Student';
            $this->data['title_list']     = 'Recently Added Student';
            $data["student_categorize"]   = 'class';
            $session                      = $this->setting_model->getCurrentSession();
            $class                        = $this->class_model->getAll();
            $this->data['classlist']      = $class;
            $this->data['sch_setting']    = $this->sch_setting_detail;
            $category                     = $this->category_model->get();
            $this->data['categorylist']   = $category;
            $class_id                     = $this->input->post('class_id');
            $section_id                   = $this->input->post('section_id');
            $this->data['form_admission'] = $this->setting_model->getOnlineAdmissionStatus();
            $genderList                   = $this->customlib->getGender();
            $this->data['genderList']     = $genderList;
            $this->data['title']          = 'Add Student';
            $this->data['title_list']     = 'Recently Added Student';
            $data["student_categorize"]   = 'class';
            $session                      = $this->setting_model->getCurrentSession();
            $class                        = $this->class_model->getAll();
            $this->data['classlist']      = $class;
            $this->data['sch_setting']    = $this->sch_setting_detail;
            $id                           = $this->onlinestudent_model->getidbyrefno($reference_no);
            $category                     = $this->category_model->get();
            $this->data['categorylist']   = $category;
            $this->data["bloodgroup"]     = $this->blood_group;
            $houses                       = $this->student_model->gethouselist();
            $this->data['houses']         = $houses;
            $result                       = $this->onlinestudent_model->get($id);
            $classresult                  = $this->onlinestudent_model->getclassbyclasssectionid($result['class_section_id']);
            $class_section_id             = $classresult['class_id'];
            $class                        = $classresult['class'];
            $custom_fields                = $this->customfield_model->getByBelong('students');
            //-------------------------------------
            $this->data['class_id']         = $class_id;
            $this->data['class_section_id'] = $result['section_id'];
            $this->data['class_name']       = $class;
            $this->data['section_id']       = $section_id;

            $this->data['firstname']  = $result['firstname'];
            $this->data['middlename'] = $result['middlename'];
            $this->data['lastname']   = $result['lastname'];
            $this->data['gender']     = $result['gender'];
            if ($result['dob'] != null && $result['dob'] != '0000-00-00') {
                $this->data['dob'] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($result['dob']));
            } else {
                $this->data['dob'] = "";
            }
            $this->data['mobileno']    = $result['mobileno'];
            $this->data['email']       = $result['email'];
            $this->data['category_id'] = $result['category_id'];
            $this->data['religion']    = $result['religion'];
            $this->data['cast']        = $result['cast'];
            $this->data['house_id']    = $result['school_house_id'];
            $this->data['blood_group'] = $result['blood_group'];
            $this->data['height']      = $result['height'];
            $this->data['weight']      = $result['weight'];
            if ($result['measurement_date'] != null && $result['measurement_date'] != '0000-00-00' && $result['measurement_date'] != '1970-01-01') {
                $this->data['measurement_date'] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($result['measurement_date']));
            } else {
                $this->data['measurement_date'] = "";
            }

            $this->data['father_name']       = $result['father_name'];
            $this->data['father_phone']      = $result['father_phone'];
            $this->data['father_occupation'] = $result['father_occupation'];

            $this->data['mother_name']       = $result['mother_name'];
            $this->data['mother_phone']      = $result['mother_phone'];
            $this->data['mother_occupation'] = $result['mother_occupation'];

            $this->data['guardian_is']         = $result['guardian_is'];
            $this->data['guardian_name']       = $result['guardian_name'];
            $this->data['guardian_relation']   = $result['guardian_relation'];
            $this->data['guardian_email']      = $result['guardian_email'];
            $this->data['guardian_phone']      = $result['guardian_phone'];
            $this->data['guardian_occupation'] = $result['guardian_occupation'];
            $this->data['guardian_address']    = $result['guardian_address'];

            $this->data['current_address']   = $result['current_address'];
            $this->data['permanent_address'] = $result['permanent_address'];

            $this->data['ifsc_code']               = $result['ifsc_code'];
            $this->data['bank_account_no']         = $result['bank_account_no'];
            $this->data['bank_name']               = $result['bank_name'];
            $this->data['adhar_no']                = $result['adhar_no'];
            $this->data['samagra_id']              = $result['samagra_id'];
            $this->data['previous_school']         = $result['previous_school'];
            $this->data['note']                    = $result['note'];
            $this->data['rte']                     = $result['rte'];
            $this->data['reference_no']            = $result['reference_no'];
            $this->data['online_admission_amount'] = $this->sch_setting_detail->online_admission_amount;
            $this->data['id']                      = $id;

            if (!empty($this->input->post('admission_id'))) {
                $this->form_validation->set_rules('firstname', $this->lang->line('first_name'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('gender', $this->lang->line('gender'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('dob', $this->lang->line('date_of_birth'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');

                if ($this->customlib->getfieldstatus('if_guardian_is')) {
                    $this->form_validation->set_rules('guardian_is', $this->lang->line('guardian'), 'trim|required|xss_clean');
                    $this->form_validation->set_rules('guardian_name', $this->lang->line('guardian_name'), 'trim|required|xss_clean');
                    $this->form_validation->set_rules('guardian_relation', $this->lang->line('guardian_relation'), 'trim|required|xss_clean');
                }
                if ($this->customlib->getfieldstatus('student_email')) {
                    $this->form_validation->set_rules(
                        'email', $this->lang->line('email'), array(
                            'valid_email', 'required',
                            array('check_student_email_exists', array($this->onlinestudent_model, 'check_student_email_exists')),
                        )
                    );
                }

                foreach ($custom_fields as $custom_fields_key => $custom_fields_value) {
                    if ($custom_fields_value['validation'] && $this->customlib->getfieldstatus($custom_fields_value['name'])) {
                        $custom_fields_id   = $custom_fields_value['id'];
                        $custom_fields_name = $custom_fields_value['name'];
                        $this->form_validation->set_rules("custom_fields[students][" . $custom_fields_id . "]", $custom_fields_name, 'trim|required');
                    }
                }

                if (!empty($_FILES['document']['name'])) {
                    $this->form_validation->set_rules('document', $this->lang->line('documents'), 'callback_document_handle_upload[document]');
                }

                if (!empty($_FILES['father_pic']['name'])) {

                    $this->form_validation->set_rules('father_pic', $this->lang->line('father') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[father_pic]');
                }
                if (!empty($_FILES['mother_pic']['name'])) {
                    $this->form_validation->set_rules('mother_pic', $this->lang->line('mother') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[mother_pic]');
                }

                if (!empty($_FILES['file']['name'])) {
                    $this->form_validation->set_rules('file', $this->lang->line('student') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[file]');
                }
                if (!empty($_FILES['guardian_pic']['name'])) {
                    $this->form_validation->set_rules('guardian_pic', $this->lang->line('guardian') . " " . $this->lang->line('photo'), 'callback_document_handle_upload[guardian_pic]');
                }

                if ($this->form_validation->run() == false) {

                    $this->load_theme('pages/editadmission', $this->config->item('front_layout'));
                } else {
                    $document_validate = true;

                    $custom_field_post = $this->input->post("custom_fields[students]");
                    if (isset($custom_field_post)) {
                        $custom_value_array = array();
                        foreach ($custom_field_post as $key => $value) {
                            $check_field_type = $this->input->post("custom_fields[students][" . $key . "]");
                            $field_value      = is_array($check_field_type) ? implode(",", $check_field_type) : $check_field_type;
                            $array_custom     = array(
                                'belong_table_id' => $id,
                                'custom_field_id' => $key,
                                'field_value'     => $field_value,
                            );
                            $custom_value_array[] = $array_custom;
                        }
                        $this->customfield_model->updateRecord($custom_value_array, $id, 'students');
                    }
                    if ($document_validate) {

                        $class_id   = $this->input->post('class_id');
                        $section_id = $this->input->post('section_id');

                        $data = array(
                            'id'               => $id,
                            'firstname'        => $this->input->post('firstname'),
                            'class_section_id' => $this->input->post('section_id'),
                            'dob'              => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('dob'))),
                            'gender'           => $this->input->post('gender'),
                        );

                        if ($this->customlib->getfieldstatus('if_guardian_is')) {
                            $data['guardian_is'] = $this->input->post('guardian_is');

                            $data['guardian_name']     = $this->input->post('guardian_name');
                            $data['guardian_relation'] = $this->input->post('guardian_relation');
                            $data['guardian_phone']    = $this->input->post('guardian_phone');

                            if ($this->customlib->getfieldstatus('guardian_occupation')) {
                                $data['guardian_occupation'] = $this->input->post('guardian_occupation');
                            }
                            if ($this->customlib->getfieldstatus('guardian_email')) {
                                $data['guardian_email'] = $this->input->post('guardian_email');
                            }
                            if ($this->customlib->getfieldstatus('guardian_address')) {
                                $data['guardian_address'] = $this->input->post('guardian_address');
                            }

                        }

                        $middlename       = $this->input->post('middlename');
                        $lastname         = $this->input->post('lastname');
                        $mobileno         = $this->input->post('mobileno');
                        $email            = $this->input->post('email');
                        $category_id      = $this->input->post('category_id');
                        $religion         = $this->input->post('religion');
                        $cast             = $this->input->post('cast');
                        $house            = $this->input->post('house');
                        $blood_group      = $this->input->post('blood_group');
                        $height           = $this->input->post('height');
                        $weight           = $this->input->post('weight');
                        $measurement_date = $this->input->post('measure_date');

                        $father_name       = $this->input->post('father_name');
                        $father_phone      = $this->input->post('father_phone');
                        $father_occupation = $this->input->post('father_occupation');
                        $mother_name       = $this->input->post('mother_name');
                        $mother_phone      = $this->input->post('mother_phone');
                        $mother_occupation = $this->input->post('mother_occupation');

                        $bank_account_no   = $this->input->post('bank_account_no');
                        $ifsc_code         = $this->input->post('ifsc_code');
                        $bank_name         = $this->input->post('bank_name');
                        $current_address   = $this->input->post('current_address');
                        $permanent_address = $this->input->post('permanent_address');
                        $previous_school   = $this->input->post('previous_school');
                        $note              = $this->input->post('note');
                        $rte               = $this->input->post('rte');

                        if (isset($middlename)) {
                            $data['middlename'] = $this->input->post('middlename');
                        }
                        if (isset($lastname)) {
                            $data['lastname'] = $this->input->post('lastname');
                        }
                        if (isset($mobile_no)) {
                            $data['mobileno'] = $this->input->post('mobileno');
                        }
                        if (isset($email)) {
                            $data['email'] = $this->input->post('email');
                        }
                        if (isset($category_id)) {
                            $data['category_id'] = $this->input->post('category_id');
                        }
                        if (isset($religion)) {
                            $data['religion'] = $this->input->post('religion');
                        }
                        if (isset($cast)) {
                            $data['cast'] = $this->input->post('cast');
                        }
                        if (isset($house)) {
                            $data['school_house_id'] = $this->input->post('house');
                        }
                        if (isset($blood_group)) {
                            $data['blood_group'] = $this->input->post('blood_group');
                        }
                        if (isset($height)) {
                            $data['height'] = $this->input->post('height');
                        }
                        if (isset($weight)) {
                            $data['weight'] = $this->input->post('weight');
                        }
                        if (isset($measurement_date)) {
                            $data['measurement_date'] = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('measure_date')));
                        }

                        if (isset($father_name)) {
                            $data['father_name'] = $this->input->post('father_name');
                        }
                        if (isset($father_phone)) {
                            $data['father_phone'] = $this->input->post('father_phone');
                        }
                        if (isset($father_occupation)) {
                            $data['father_occupation'] = $this->input->post('father_occupation');
                        }
                        if (isset($mother_name)) {
                            $data['mother_name'] = $this->input->post('mother_name');
                        }
                        if (isset($mother_phone)) {
                            $data['mother_phone'] = $this->input->post('mother_phone');
                        }
                        if (isset($mother_occupation)) {
                            $data['mother_occupation'] = $this->input->post('mother_occupation');
                        }
                        if (isset($current_address)) {
                            $data['current_address'] = $this->input->post('current_address');
                        }
                        if (isset($permanent_address)) {
                            $data['permanent_address'] = $this->input->post('permanent_address');
                        }
                        if (isset($bank_account_no)) {
                            $data['bank_account_no'] = $this->input->post('bank_account_no');
                        }
                        if (isset($ifsc_code)) {
                            $data['ifsc_code'] = $this->input->post('ifsc_code');
                        }
                        if (isset($bank_name)) {
                            $data['bank_name'] = $this->input->post('bank_name');
                        }
                        if (isset($previous_school)) {
                            $data['previous_school'] = $this->input->post('previous_school');
                        }
                        if (isset($note)) {
                            $data['note'] = $this->input->post('note');
                        }
                        if (isset($rte)) {
                            $data['rte'] = $this->input->post('rte');
                        }

                        $this->onlinestudent_model->edit($data);

                        if (isset($_FILES["document"]) && !empty($_FILES['document']['name'])) {

                            $time     = md5($_FILES["document"]['name'] . microtime());
                            $fileInfo = pathinfo($_FILES["document"]["name"]);
                            $doc_name = $time . '.' . $fileInfo['extension'];
                            move_uploaded_file($_FILES["document"]["tmp_name"], "./uploads/student_documents/online_admission_doc/" . $doc_name);

                            $data_img['document'] = $doc_name;
                            $data_img['id']       = $id;
                            $this->onlinestudent_model->edit($data_img);
                        }

                        if (isset($_FILES["file"]) && !empty($_FILES['file']['name'])) {
                            
                            $fileInfo = pathinfo($_FILES["file"]["name"]);
                            $img_name = $id . '.' . $fileInfo['extension'];
                            move_uploaded_file($_FILES["file"]["tmp_name"], "./uploads/student_images/" . $img_name);
                            $data_img = array('id' => $id, 'image' => 'uploads/student_images/' . $img_name);

                            $this->onlinestudent_model->edit($data_img);

                        }

                        if (isset($_FILES["father_pic"]) && !empty($_FILES['father_pic']['name'])) {
                            $fileInfo = pathinfo($_FILES["father_pic"]["name"]);
                            $img_name = $id . "father" . '.' . $fileInfo['extension'];
                            move_uploaded_file($_FILES["father_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                            $data_img = array('id' => $id, 'father_pic' => 'uploads/student_images/' . $img_name);
                            $this->onlinestudent_model->edit($data_img);
                        }
                        if (isset($_FILES["mother_pic"]) && !empty($_FILES['mother_pic']['name'])) {
                            $fileInfo = pathinfo($_FILES["mother_pic"]["name"]);
                            $img_name = $id . "mother" . '.' . $fileInfo['extension'];
                            move_uploaded_file($_FILES["mother_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                            $data_img = array('id' => $id, 'mother_pic' => 'uploads/student_images/' . $img_name);
                            $this->onlinestudent_model->edit($data_img);
                        }

                        if (isset($_FILES["guardian_pic"]) && !empty($_FILES['guardian_pic']['name'])) {
                            $fileInfo = pathinfo($_FILES["guardian_pic"]["name"]);
                            $img_name = $id . "guardian" . '.' . $fileInfo['extension'];
                            move_uploaded_file($_FILES["guardian_pic"]["tmp_name"], "./uploads/student_images/" . $img_name);
                            $data_img = array('id' => $id, 'guardian_pic' => 'uploads/student_images/' . $img_name);

                            $this->onlinestudent_model->edit($data_img);
                        }

                        $sch_setting = $this->sch_setting_detail;

                        $this->session->set_flashdata('msg', '<div class="alert alert-success">Record Updated Successfully.</div>');

                        redirect('welcome/online_admission_review/' . $reference_no);
                    }
                }
            } else {
                $this->load_theme('pages/editadmission', $this->config->item('front_layout'));
            }
        } else {
            $this->show_404();
        }
    }

    public function document_handle_upload($str, $var)
    {

        $image_validate = $this->config->item('image_validate');
        $result         = $this->filetype_model->get();

        if (isset($_FILES[$var]) && !empty($_FILES[$var]['name'])) {

            $file_type = $_FILES[$var]['type'];
            $file_size = $_FILES[$var]["size"];
            $file_name = $_FILES[$var]["name"];

            $allowed_extension = array_map('trim', array_map('strtolower', explode(',', $result->file_extension)));
            $allowed_mime_type = array_map('trim', array_map('strtolower', explode(',', $result->file_mime)));
            $ext               = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mtype = finfo_file($finfo, $_FILES[$var]['tmp_name']);

            if (!in_array($mtype, $allowed_mime_type)) {
                $this->form_validation->set_message('document_handle_upload', 'File Type Not Allowed');
                return false;
            }

            if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                $this->form_validation->set_message('document_handle_upload', 'Extension Not Allowed');
                return false;
            }

            if ($file_size > $result->file_size) {
                $this->form_validation->set_message('document_handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($image_validate['upload_size'] / 1048576, 2) . " MB");
                return false;
            }

            return true;
        }
        return true;
    }

    public function check_captcha($captcha)
    {
        if ($captcha != $this->session->userdata('captchaCode')):
            $this->form_validation->set_message('check_captcha', $this->lang->line('incorrect_captcha'));
            return false;
        else:
            return true;
        endif;
    }

    public function setsitecookies()
    {
        $cookie_name  = "sitecookies";
        $cookie_value = "1";
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");

    }

    public function checkadmissionstatus()
    {
        $this->form_validation->set_rules('refno', 'Reference Number', 'trim|required|xss_clean');
        $this->form_validation->set_rules('student_dob', $this->lang->line('date_of_birth'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $msg = array(
                'refno' => form_error('refno'),
                'dob'   => form_error('student_dob'),
            );
            $array = array('status' => '0', 'error' => $msg, 'msg' => $this->lang->line('something_went_wrong'));
        } else {

            $refno  = $this->input->post('refno');
            $dob    = $this->customlib->dateFormatToYYYYMMDD($this->input->post('student_dob'));
            $status = $this->onlinestudent_model->checkadmissionstatus($refno, $dob);

            if ($status == 0) {
                $array = array('status' => '2', 'error' => $this->lang->line('invalid_refence_number_or_date_of_birth'), 'msg' => '', 'refno' => $refno);
            } else {

                $is_enroll = $this->customlib->checkisenroll($refno);
                if($is_enroll==0){
                    if (!empty($this->session->userdata('validlogin'))) {
                        $this->session->unset_userdata('validlogin');
                    }
                    $this->session->set_userdata('validlogin', $refno
                    );
                    $array = array('status' => '1', 'error' => '', 'msg' => '', 'id' => $status, 'refno' => $refno);
                }else{
                    $array = array('status' => '2', 'error' => $this->lang->line('you_are_already_enrolled_please_contact_to_school_administrator'), 'msg' => '', 'refno' => $refno);
                }
                
            }

        }

        echo json_encode($array);

    }

    public function submitadmission()
    {
        $this->form_validation->set_rules('checkterm', $this->lang->line('terms_conditions'), 'trim|required|xss_clean');
        $admission_id = $this->input->post('admission_id');

        if ($this->form_validation->run() == true) {

            $data = array('id' => $admission_id, 'form_status' => 1);
            $this->onlinestudent_model->edit($data);
            $result = $this->onlinestudent_model->get($admission_id);

            $firstname    = $result['firstname'];
            $lastname     = $result['lastname'];
            $date         = date('Y-m-d');
            $reference_no = $result['reference_no'];
            $mobileno     = $result['mobileno'];
            $email        = $result['email'];
            $date         = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($date));

            $sender_details = array('firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'date' => $date, 'reference_no' => $reference_no, 'mobileno' => $mobileno);

           $this->mailsmsconf->mailsms('online_admission_form_submission', $sender_details);

            $array = array('status' => '1', 'error' => '', 'id' => $admission_id, 'msg' => '', 'reference_no' => $reference_no);

        } else {

            $array = array('status' => '0', 'error' => form_error('checkterm'), 'msg' => '');
            
        }
        echo json_encode($array);
    }

    public function checktermcondition()
    {
        $this->form_validation->set_rules('checkterm', $this->lang->line('terms_conditions'), 'trim|required|xss_clean');
        $admission_id = $this->input->post('admission_id');

        if ($this->form_validation->run() == true) {
            $array = array('status' => '1', 'error' => '');

        } else {

            $array = array('status' => '0', 'error' => form_error('checkterm'));
            echo json_encode($array);
        }

    }

}
