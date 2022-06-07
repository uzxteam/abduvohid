<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Report extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->time               = strtotime(date('d-m-Y H:i:s'));
        $this->payment_mode       = $this->customlib->payment_mode();
        $this->search_type        = $this->customlib->get_searchtype();
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }

    public function pdfStudentFeeRecord()
    {
        $data                    = [];
        $class_id                = $this->uri->segment(3);
        $section_id              = $this->uri->segment(4);
        $student_id              = $this->uri->segment(5);
        $student                 = $this->student_model->get($student_id);
        $setting_result          = $this->setting_model->get();
        $data['settinglist']     = $setting_result;
        $data['student']         = $student;
        $student_due_fee         = $this->studentfee_model->getDueFeeBystudent($class_id, $section_id, $student_id);
        $data['student_due_fee'] = $student_due_fee;
        $html                    = $this->load->view('reports/students_detail', $data, true);
        $pdfFilePath             = $this->time . ".pdf";
        $this->fontdata          = array(
            "opensans" => array(
                'R'  => "OpenSans-Regular.ttf",
                'B'  => "OpenSans-Bold.ttf",
                'I'  => "OpenSans-Italic.ttf",
                'BI' => "OpenSans-BoldItalic.ttf",
            ),
        );
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfByInvoiceNo()
    {
        $data                    = [];
        $invoice_id              = $this->uri->segment(3);
        $setting_result          = $this->setting_model->get();
        $data['settinglist']     = $setting_result;
        $student_due_fee         = $this->studentfee_model->getFeeByInvoice($invoice_id);
        $data['student_due_fee'] = $student_due_fee;
        $html                    = $this->load->view('reports/pdfinvoiceno', $data, true);
        $pdfFilePath             = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfDepositeFeeByStudent($id)
    {
        $data                        = [];
        $data['title']               = 'Student Detail';
        $student                     = $this->student_model->get($id);
        $setting_result              = $this->setting_model->get();
        $data['settinglist']         = $setting_result;
        $student_fee_history         = $this->studentfee_model->getStudentFees($id);
        $data['student_fee_history'] = $student_fee_history;
        $data['student']             = $student;
        $array                       = array();
        $feecategory                 = $this->feecategory_model->get();
        foreach ($feecategory as $key => $value) {
            $dataarray            = array();
            $value_id             = $value['id'];
            $dataarray[$value_id] = $value['category'];
            $category             = $value['category'];
            $datatype             = array();
            $data_fee_type        = array();
            $feetype              = $this->feetype_model->getFeetypeByCategory($value['id']);
            foreach ($feetype as $feekey => $feevalue) {
                $ftype            = $feevalue['id'];
                $datatype[$ftype] = $feevalue['type'];
            }
            $data_fee_type[]      = $datatype;
            $dataarray[$category] = $datatype;
            $array[]              = $dataarray;
        }
        $data['category_array'] = $array;
        $data['feecategory']    = $feecategory;
        $html                   = $this->load->view('reports/pdfStudentDeposite', $data, true);
        $pdfFilePath            = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfStudentListByText()
    {
        $data                = [];
        $search_text         = $this->uri->segment(3);
        $setting_result      = $this->setting_model->get();
        $data['settinglist'] = $setting_result;
        $resultlist          = $this->student_model->searchFullText($search_text);
        $data['resultlist']  = $resultlist;
        $html                = $this->load->view('reports/pdfStudentListByText', $data, true);
        $pdfFilePath         = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function marksreport()
    {
        $setting_result        = $this->setting_model->get();
        $data['settinglist']   = $setting_result;
        $exam_id               = $this->uri->segment(3);
        $class_id              = $this->uri->segment(4);
        $section_id            = $this->uri->segment(5);
        $data['exam_id']       = $exam_id;
        $data['class_id']      = $class_id;
        $data['section_id']    = $section_id;
        $exam_arrylist         = $this->exam_model->get($exam_id);
        $data['exam_arrylist'] = $exam_arrylist;
        $section               = $this->section_model->getClassNameBySection($class_id, $section_id);
        $data['class']         = $section;
        $examSchedule          = $this->examschedule_model->getDetailbyClsandSection($class_id, $section_id, $exam_id);
        $studentList           = $this->student_model->searchByClassSection($class_id, $section_id);
        $data['examSchedule']  = array();
        if (!empty($examSchedule)) {
            $new_array                      = array();
            $data['examSchedule']['status'] = "yes";
            foreach ($studentList as $stu_key => $stu_value) {
                $array                 = array();
                $array['student_id']   = $stu_value['id'];
                $array['roll_no']      = $stu_value['roll_no'];
                $array['firstname']    = $stu_value['firstname'];
                $array['lastname']     = $stu_value['lastname'];
                $array['admission_no'] = $stu_value['admission_no'];
                $array['dob']          = $stu_value['dob'];
                $array['father_name']  = $stu_value['father_name'];
                $x                     = array();
                foreach ($examSchedule as $ex_key => $ex_value) {
                    $exam_array                     = array();
                    $exam_array['exam_schedule_id'] = $ex_value['id'];
                    $exam_array['exam_id']          = $ex_value['exam_id'];
                    $exam_array['full_marks']       = $ex_value['full_marks'];
                    $exam_array['passing_marks']    = $ex_value['passing_marks'];
                    $exam_array['exam_name']        = $ex_value['name'];
                    $exam_array['exam_type']        = $ex_value['type'];
                    $student_exam_result            = $this->examresult_model->get_result($ex_value['id'], $stu_value['id']);
                    if (empty($student_exam_result)) {
                        $data['examSchedule']['status'] = "no";
                    } else {
                        $exam_array['attendence'] = $student_exam_result->attendence;
                        $exam_array['get_marks']  = $student_exam_result->get_marks;
                    }
                    $x[] = $exam_array;
                }
                $array['exam_array'] = $x;
                $new_array[]         = $array;
            }
            $data['examSchedule']['result'] = $new_array;
        } else {
            $s                    = array('status' => 'no');
            $data['examSchedule'] = $s;
        }
        $html        = $this->load->view('reports/marksreport', $data, true);
        $pdfFilePath = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
        $this->load->view('reports/marksreport', $data);
    }

    public function pdfStudentListByClassSection()
    {
        $data                = [];
        $class_id            = $this->uri->segment(3);
        $section_id          = $this->uri->segment(4);
        $setting_result      = $this->setting_model->get();
        $section             = $this->section_model->getClassNameBySection($class_id, $section_id);
        $data['class']       = $section;
        $data['settinglist'] = $setting_result;
        $resultlist          = $this->student_model->searchByClassSection($class_id, $section_id);
        $data['resultlist']  = $resultlist;
        $html                = $this->load->view('reports/pdfStudentListByClassSection', $data, true);
        $pdfFilePath         = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfStudentListDifferentCriteria()
    {
        $data           = [];
        $class_id       = $this->input->get('class_id');
        $section_id     = $this->input->get('section_id');
        $category_id    = $this->input->get('category_id');
        $gender         = $this->input->get('gender');
        $rte            = $this->input->get('rte');
        $setting_result = $this->setting_model->get();
        $class          = $this->class_model->get($class_id);
        $data['class']  = $class;
        if ($section_id != "") {
            $section         = $this->section_model->getClassNameBySection($class_id, $section_id);
            $data['section'] = $section;
        }
        if ($gender != "") {
            $data['gender'] = $gender;
        }
        if ($rte != "") {
            $data['rte'] = $rte;
        }
        if ($category_id != "") {
            $category         = $this->category_model->get($category_id);
            $data['category'] = $category;
        }
        $data['settinglist'] = $setting_result;
        $resultlist          = $this->student_model->searchByClassSectionCategoryGenderRte($class_id, $section_id, $category_id, $gender, $rte);
        $data['resultlist']  = $resultlist;
        $html                = $this->load->view('reports/pdfStudentListDifferentCriteria', $data, true);
        $pdfFilePath         = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfStudentListByClass()
    {
        $data                = [];
        $class_id            = $this->uri->segment(3);
        $section_id          = "";
        $setting_result      = $this->setting_model->get();
        $section             = $this->class_model->get($class_id);
        $data['class']       = $section;
        $data['settinglist'] = $setting_result;
        $resultlist          = $this->student_model->searchByClassSection($class_id, $section_id);
        $data['resultlist']  = $resultlist;
        $html                = $this->load->view('reports/pdfStudentListByClass', $data, true);
        $pdfFilePath         = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function transactionSearch()
    {
        $data                = [];
        $date_from           = $this->input->get('datefrom');
        $date_to             = $this->input->get('dateto');
        $setting_result      = $this->setting_model->get();
        $data['exp_title']   = 'Transaction From ' . $date_from . " To " . $date_to;
        $date_from           = date('Y-m-d', $this->customlib->datetostrtotime($date_from));
        $date_to             = date('Y-m-d', $this->customlib->datetostrtotime($date_to));
        $expenseList         = $this->expense_model->search("", $date_from, $date_to);
        $feeList             = $this->studentfee_model->getFeeBetweenDate($date_from, $date_to);
        $data['expenseList'] = $expenseList;
        $data['feeList']     = $feeList;
        $data['settinglist'] = $setting_result;
        $html                = $this->load->view('reports/transactionSearch', $data, true);
        $pdfFilePath         = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function pdfExamschdule()
    {
        $data                 = [];
        $setting_result       = $this->setting_model->get();
        $data['settinglist']  = $setting_result;
        $exam_id              = $this->uri->segment(3);
        $section_id           = $this->uri->segment(4);
        $class_id             = $this->uri->segment(5);
        $class                = $this->class_model->get($class_id);
        $data['class']        = $class;
        $examSchedule         = $this->examschedule_model->getDetailbyClsandSection($class_id, $section_id, $exam_id);
        $section              = $this->section_model->getClassNameBySection($class_id, $section_id);
        $data['section']      = $section;
        $data['examSchedule'] = $examSchedule;
        $exam                 = $this->exam_model->get($exam_id);
        $data['exam']         = $exam;
        $html                 = $this->load->view('reports/examSchedule', $data, true);
        $pdfFilePath          = $this->time . ".pdf";
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($pdfFilePath, "D");
    }

    public function get_betweendate($type)
    {

        $this->load->view('reports/betweenDate');

    }

    public function class_subject()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/class_subject_report');
        $data['title']       = 'Add Fees Type';
        $data['searchlist']  = $this->search_type;
        $class               = $this->class_model->get('', $classteacher = 'yes');
        $data['classlist']   = $class;
        $data['search_type'] = '';
        $data['class_id']    = $class_id    = $this->input->post('class_id');
        $data['section_id']  = $section_id  = $this->input->post('section_id');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $data['subjects'] = array();
        } else {
            $data['section_list'] = $this->section_model->getClassBySection($this->input->post('class_id'));

            $data['resultlist'] = $this->subjecttimetable_model->getSubjectByClassandSection($class_id, $section_id);

            $subject = array();
            foreach ($data['resultlist'] as $value) {
                $subject[$value->subject_id][] = $value;
            }

            $data['subjects'] = $subject;
        }

        $this->load->view('layout/header', $data);
        $this->load->view('reports/class_subject', $data);
        $this->load->view('layout/footer', $data);

    }

    public function admission_report()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/admission_report');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/admission_report', $data);
        $this->load->view('layout/footer', $data);

    }

    public function sibling_report()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/sibling_report');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $condition               = array();
        $class                   = $this->class_model->get('', $classteacher = 'yes');
        $data['classlist']       = $class;

        $data['class_id']     = $class_id     = $this->input->post('class_id');
        $data['section_id']   = $section_id   = $this->input->post('section_id');
        $data['section_list'] = $this->section_model->getClassBySection($this->input->post('class_id'));

        if (isset($_POST['class_id']) && $_POST['class_id'] != '') {
            $condition['classes.id'] = $_POST['class_id'];
        }

        if (isset($_POST['section_id']) && $_POST['section_id'] != '') {
            $condition['sections.id'] = $_POST['section_id'];
        }

        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data['resultlist'] = array();
        } else {
            $data['sibling_list'] = $this->student_model->sibling_reportsearch($searchterm, $carray = null, $condition);

            $sibling_parent = array();

            foreach ($data['sibling_list'] as $value) {

                $sibling_parent[] = $value['parent_id'];
            }

            $data['resultlist'] = $this->student_model->sibling_report($searchterm, $carray = null);
            $sibling            = array();

            foreach ($data['resultlist'] as $value) {

                if (in_array($value['parent_id'], $sibling_parent)) {

                    $sibling[$value['parent_id']][] = $value;

                }

            }
            $data['resultlist'] = $sibling;
        }

        $this->load->view('layout/header', $data);
        $this->load->view('reports/sibling_report', $data);
        $this->load->view('layout/footer', $data);

    }

    public function onlinefees_report()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/onlinefees_report');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['group_by']   = $this->customlib->get_groupby();

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        }

        $collection = array();
        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));
        $this->form_validation->set_rules('search_type', $this->lang->line('search') . " " . $this->lang->line('type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

            $data['collectlist'] = array();

        } else {

            $data['collectlist'] = $this->studentfeemaster_model->getOnlineFeeCollectionReport($start_date, $end_date);

        }

        $data['sch_setting'] = $this->sch_setting_detail;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineFeesReport', $data);
        $this->load->view('layout/footer', $data);
    }

    public function studentbookissuereport()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/library');
        $this->session->set_userdata('subsub_menu', 'Reports/library/book_issue_report');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['members']    = array('' => $this->lang->line('all'), 'student' => $this->lang->line('student'), 'teacher' => $this->lang->line('teacher'));
        $this->load->view('layout/header', $data);
        $this->load->view('reports/studentBookIssueReport', $data);
        $this->load->view('layout/footer', $data);
    }

    public function bookduereport()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/library');
        $this->session->set_userdata('subsub_menu', 'Reports/library/bookduereport');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['sch_setting'] = $this->sch_setting_detail;
		$data['members']    = array('' => $this->lang->line('all'), 'student' => $this->lang->line('student'), 'teacher' => $this->lang->line('teacher'));
        $this->load->view('layout/header', $data);
        $this->load->view('reports/bookduereport', $data);
        $this->load->view('layout/footer', $data);
    }

    public function bookinventory()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/library');
        $this->session->set_userdata('subsub_menu', 'Reports/library/bookinventory');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $this->load->view('layout/header', $data);
        $this->load->view('reports/bookinventory', $data);
        $this->load->view('layout/footer', $data);
    }

    public function feescollectionreport()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/fees_collection');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/feescollectionreport');
        $this->load->view('layout/footer');
    }

    public function gerenalincomereport()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'reports/bookinventory');
        $data['searchlist'] = $this->customlib->get_searchtype();
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $start_date       = date('Y-m-d', strtotime($dates['from_date']));
        $end_date         = date('Y-m-d', strtotime($dates['to_date']));
        $data['label']    = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $listbook         = $this->book_model->bookinventory($start_date, $end_date);
        $data['listbook'] = $listbook;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/gerenalincomereport', $data);
        $this->load->view('layout/footer', $data);
    }

    public function studentinformation()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/studentinformation');
        $this->load->view('layout/footer');
    }

    public function attendance()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/attendance');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/attendance');
        $this->load->view('layout/footer');
    }

    public function examinations()
    {
        if (!$this->rbac->hasPrivilege('rank_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/examinations');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/examinations');
        $this->load->view('layout/footer');
    }

    public function library()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/library');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/library');
        $this->load->view('layout/footer');

    }

    public function inventory()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/inventory');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/inventory');
        $this->load->view('layout/footer');

    }

    public function onlineexams()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/online_examinations');
        $this->session->set_userdata('subsub_menu', 'Reports/online_examinations/onlineexams');
        $condition          = "";
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['date_type']  = $this->customlib->date_type();

        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineexams', $data);
        $this->load->view('layout/footer', $data);

    }

    public function onlineexamsresult()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/examinations');
        $this->session->set_userdata('subsub_menu', 'Reports/examinations/onlineexamsresult');
        $condition           = "";
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));

        if (isset($_POST['date_type']) && $_POST['date_type'] != '') {

            $data['date_typeid'] = $_POST['date_type'];

            if ($_POST['date_type'] == 'exam_from_date') {

                $condition = " date_format(exam_from,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";

            } elseif ($_POST['date_type'] == 'exam_to_date') {

                $condition = " date_format(exam_to,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";

            }

        } else {

            $condition = " date_format(created_at,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";

        }

        $data['resultlist'] = $this->onlineexam_model->onlineexamReport($condition);
        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineexamsresult', $data);
        $this->load->view('layout/footer', $data);
    }

    public function onlineexamattend()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/online_examinations');
        $this->session->set_userdata('subsub_menu', 'Reports/online_examinations/onlineexamattend');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['date_type']  = $this->customlib->date_type();
        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineexamattend', $data);
        $this->load->view('layout/footer', $data);
    }

    public function onlineexamrank()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/online_examinations');
        $this->session->set_userdata('subsub_menu', 'Reports/online_examinations/onlineexamrank');

        $exam_id             = $class_id             = $section_id             = $condition             = '';
        $studentrecord       = array();
        $getResultByStudent1 = array();

        $examList          = $this->onlineexam_model->get();
        $data['examList']  = $examList;
        $class             = $this->class_model->get();
        $data['classlist'] = $class;
        $this->form_validation->set_rules('exam_id', $this->lang->line('exam'), 'required');

        if ($this->form_validation->run() == false) {

        } else {
            if (isset($_POST['class_id']) && $_POST['class_id'] != '') {
                $class_id = $_POST['class_id'];
            }

            if (isset($_POST['section_id']) && $_POST['section_id'] != '') {
                $section_id = $_POST['section_id'];
            }

            if (isset($_POST['exam_id']) && $_POST['exam_id'] != '') {
                $exam_id = $_POST['exam_id'];
            }
            $exam         = $this->onlineexam_model->get($exam_id);
            $student_data = $this->onlineexam_model->searchAllOnlineExamStudents($exam_id, $class_id, $section_id);

            if (!empty($student_data)) {
                foreach ($student_data as $student_key => $student_value) {
                    $student_data[$student_key]['questions_results'] = $this->onlineexamresult_model->getResultByStudent($student_value['onlineexam_student_id'], $exam_id);
                }
            }

            $data['exam']         = $exam;
            $data['student_data'] = $student_data;
        }
        $data['sch_setting'] = $this->sch_setting_detail;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineexamrank', $data);
        $this->load->view('layout/footer', $data);

    }

    public function inventorystock()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/inventory');
        $this->session->set_userdata('subsub_menu', 'Reports/inventory/inventorystock');
        $this->load->view('layout/header');
        $this->load->view('reports/inventorystock');
        $this->load->view('layout/footer');
    }

    public function additem()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/inventory');
        $this->session->set_userdata('subsub_menu', 'Reports/inventory/additem');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';
        $this->load->view('layout/header', $data);
        $this->load->view('reports/additem', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getadditemlistbydt()
    {

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {
            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        } else {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $itemresult    = $this->itemstock_model->get_ItemByBetweenDate($start_date, $end_date);

        $resultlist      = json_decode($itemresult);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $key => $value) {

                $row       = array();
                $row[]     = $value->name;
                $row[]     = $value->item_category;
                $row[]     = $value->item_supplier;
                $row[]     = $value->item_store;
                $row[]     = $value->quantity;
                $row[]     = $currency_symbol . $value->purchase_price;
                $row[]     = date($this->customlib->getSchoolDateFormat(), strtotime($value->date));
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function issueinventory()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/inventory');
        $this->session->set_userdata('subsub_menu', 'Reports/inventory/issueinventory');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label']         = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $data['itemissueList'] = $this->itemissue_model->get_IssueInventoryReport($start_date, $end_date);

        $this->load->view('layout/header', $data);
        $this->load->view('reports/issueinventory', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getissueinventorylistbydt()
    {

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {
            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        } else {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label']   = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $itemresult      = $this->itemissue_model->get_IssueInventoryReport($start_date, $end_date);
        $resultlist      = json_decode($itemresult);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $key => $value) {

                if ($value->note == "") {
                    $condition = "<p class='text text-danger no-print'>" . $this->lang->line('no_description') . " </p>";
                } else {
                    $condition = "<p class='text text-info no-print' >" . $value->note . "</p>";
                }

                $title = "<a href='#' data-toggle='popover' class='detail_popover'>" . $value->item_name . "</a> <div class='fee_detail_popover' style='display: none'> " . $condition . " </div> ";

                $row   = array();
                $row[] = $title;
                $row[] = $value->item_category;
                if ($value->return_date == "0000-00-00") {
                    $return_date = "";
                } else {
                    $return_date = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->return_date));
                }
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->issue_date)) . " - " . $return_date;

                $row[]     = $value->staff_name . " " . $value->surname . "(" . $value->employee_id . ")";
                $row[]     = $value->issue_by;
                $row[]     = $value->quantity;
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function finance()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('reports/finance');
        $this->load->view('layout/footer');
    }

    public function income()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/income');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $this->load->view('layout/header', $data);
        $this->load->view('reports/income', $data);
        $this->load->view('layout/footer', $data);
    }

    public function expense()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/expense');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';

        $this->form_validation->set_rules('search_type', $this->lang->line('search') . " " . $this->lang->line('type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        } else {
            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $this->load->view('layout/header', $data);
        $this->load->view('reports/expense', $data);
        $this->load->view('layout/footer', $data);
    }

    public function payroll()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/payroll');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label']        = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $data['payment_mode'] = $this->payment_mode;

        $result              = $this->payroll_model->getbetweenpayrollReport($start_date, $end_date);
        $data['payrollList'] = $result;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/payroll', $data);
        $this->load->view('layout/footer', $data);
    }

    public function incomegroup()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/incomegroup');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';
        $data['headlist']    = $this->incomehead_model->get();
        $this->load->view('layout/header', $data);
        $this->load->view('reports/incomegroup', $data);
        $this->load->view('layout/footer', $data);
    }

    public function expensegroup()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/expensegroup');
        $data['searchlist']  = $this->customlib->get_searchtype();
        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';
        $data['headlist']    = $this->expensehead_model->get();
        $this->load->view('layout/header', $data);
        $this->load->view('reports/expensegroup', $data);
        $this->load->view('layout/footer', $data);
    }

    public function student_profile()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/student_profile');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $data['class_id']        = $class_id        = $this->input->post('class_id');
        $data['section_id']      = $section_id      = $this->input->post('section_id');
        $condition               = "";
        $data['section_list']    = $this->section_model->getClassBySection($this->input->post('class_id'));
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $between_date        = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $search_type = $_POST['search_type'];
        } else {

            $between_date        = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = $search_type = '';
        }

        $from_date = date('Y-m-d', strtotime($between_date['from_date']));
        $to_date   = date('Y-m-d', strtotime($between_date['to_date']));
        $condition .= " date_format(admission_date,'%Y-%m-%d') between  '" . $from_date . "' and '" . $to_date . "'";
        $data['filter_label'] = date($this->customlib->getSchoolDateFormat(), strtotime($from_date)) . " To " . date($this->customlib->getSchoolDateFormat(), strtotime($to_date));

        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;

        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $data['resultlist'] = array();
        } else {
            $condition .= " and classes.id='" . $this->input->post('class_id') . "' and sections.id='" . $this->input->post('section_id') . "'";

            $data['resultlist'] = $this->student_model->student_profile($condition);
        }
        $this->load->view('layout/header', $data);
        $this->load->view('reports/student_profile', $data);
        $this->load->view('layout/footer', $data);
    }

    public function staff_report()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/human_resource');
        $this->session->set_userdata('subsub_menu', 'Reports/human_resource/staff_report');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $condition               = "";
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $between_date        = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $search_type = $_POST['search_type'];

        } else {

            $between_date        = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = $search_type = '';

        }

        $from_date = date('Y-m-d', strtotime($between_date['from_date']));

        $to_date = date('Y-m-d', strtotime($between_date['to_date']));

        $condition .= " and date_format(date_of_joining,'%Y-%m-%d') between  '" . $from_date . "' and '" . $to_date . "'";

        $data['filter_label'] = date($this->customlib->getSchoolDateFormat(), strtotime($from_date)) . " To " . date($this->customlib->getSchoolDateFormat(), strtotime($to_date));

        if (isset($_POST['staff_status']) && $_POST['staff_status'] != '') {
            if ($_POST['staff_status'] == 'both') {

                $search_status = "1,2";

            } elseif ($_POST['staff_status'] == '2') {

                $search_status = "0";

            } else {

                $search_status = "1";

            }
            $condition .= " and `staff`.`is_active` in (" . $search_status . ")";
            $data['status_val'] = $_POST['staff_status'];
        } else {
            $data['status_val'] = 1;
        }

        if (isset($_POST['role']) && $_POST['role'] != '') {
            $condition .= " and `staff_roles`.`role_id`=" . $_POST['role'];
            $data['role_val'] = $_POST['role'];
        }

        if (isset($_POST['designation']) && $_POST['designation'] != '') {
            $condition .= " and `staff_designation`.`id`=" . $_POST['designation'];
            $data['designation_val'] = $_POST['designation'];
        }

        $data['resultlist'] = $this->staff_model->staff_report($condition);

        $leave_type = $this->leavetypes_model->getLeaveType();
        foreach ($leave_type as $key => $leave_value) {
            $data['leave_type'][$leave_value['id']] = $leave_value['type'];
        }
        $data['status']      = $this->customlib->staff_status();
        $data['roles']       = $this->role_model->get();
        $data['designation'] = $this->designation_model->get();

        $data['fields']          = $this->customfield_model->get_custom_fields('staff', 1);
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/staff_report', $data);
        $this->load->view('layout/footer', $data);
    }

    public function attendancereport()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/attendance');
        $this->session->set_userdata('subsub_menu', 'Reports/attendence/attendancereport');
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $class                   = $this->input->post('class_id');
        $section                 = $this->input->post('section_id');
        $data['class_id']        = $class;
        $data['section_id']      = $section;
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $searchterm              = '';
        $condition               = "";
        $date_condition          = "";

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $between_date        = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $search_type = $_POST['search_type'];

        } else {

            $between_date        = $this->customlib->get_betweendate('this_week');
            $data['search_type'] = $search_type = 'this_week';

        }

        $from_date = date('Y-m-d', strtotime($between_date['from_date']));
        $to_date   = date('Y-m-d', strtotime($between_date['to_date']));
        $dates     = array();
        $off_date  = array();
        $current   = strtotime($from_date);
        $last      = strtotime($to_date);

        while ($current <= $last) {

            $date    = date('Y-m-d', $current);
            $day     = date("D", strtotime($date));
            $holiday = $this->stuattendence_model->checkholidatbydate($date);

            if ($day == 'Sun' || $holiday > 0) {
                $off_date[] = $date;
            } else {
                $dates[] = $date;
            }

            $current = strtotime('+1 day', $current);

        }

        $data['filter']          = date($this->customlib->getSchoolDateFormat(), strtotime($from_date)) . " To " . date($this->customlib->getSchoolDateFormat(), strtotime($to_date));
        $data['attendance_type'] = $this->attendencetype_model->getstdAttType('2');
        $this->form_validation->set_rules('attendance_type', $this->lang->line('attendence') . " " . $this->lang->line('type'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $this->load->view('layout/header', $data);
            $this->load->view('reports/stuattendance', $data);
            $this->load->view('layout/footer', $data);

        } else {

            $data['attendance_type_id'] = $attendance_type_id = $this->input->post('attendance_type');
            $condition .= " and `student_attendences`.`attendence_type_id`=" . $this->input->post('attendance_type');
            foreach ($dates as $key => $value) {

            }

            if ($data['class_id'] != '') {
                $condition .= ' and class_id=' . $data['class_id'];
            }
            $condition .= " and date_format(student_attendences.date,'%Y-%m-%d') between '" . $from_date . "' and '" . $to_date . "'";
            if ($data['section_id'] != '') {
                $condition .= ' and section_id=' . $data['section_id'];
            }

            $data['student_attendences'] = $this->stuattendence_model->student_attendences($condition, $date_condition);

            $attd = array();

            foreach ($data['student_attendences'] as $value) {
                $std_id          = $value['id'];
                $attd[$std_id][] = $value;
            }

            foreach ($attd as $key => $att_value) {
                $all_week = 1;
                foreach ($att_value as $value) {

                    if (in_array($value['date'], $off_date)) {

                    } else {
                        if (in_array($value['date'], $dates)) {
                            //echo "Match found";
                        } else {
                            $all_week = 0;
                        }
                    }

                }
                if ($all_week == 1) {
                    $fdata[] = $att_value[0];
                }

            }

            $dates = " '" . $from_date . "' and '" . $to_date . "'";

            $this->load->view('layout/header', $data);
            $this->load->view('reports/stuattendance', $data);
            $this->load->view('layout/footer', $data);
        }

    }

    public function biometric_attlog($offset = 0)
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/attendance');
        $this->session->set_userdata('subsub_menu', 'Reports/attendence/biometric_attlog');
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;

        $config['total_rows'] = $this->stuattendence_model->biometric_attlogcount();

        $config['base_url']    = base_url() . "report/biometric_attlog";
        $config['per_page']    = 100;
        $config['uri_segment'] = '3';

        $config['full_tag_open']  = '<div class="pagination"><ul>';
        $config['full_tag_close'] = '</ul></div>';

        $config['first_link']      = '« First';
        $config['first_tag_open']  = '<li class="prev page">';
        $config['first_tag_close'] = '</li>';

        $config['last_link']      = 'Last »';
        $config['last_tag_open']  = '<li class="next page">';
        $config['last_tag_close'] = '</li>';

        $config['next_link']      = 'Next →';
        $config['next_tag_open']  = '<li class="next page">';
        $config['next_tag_close'] = '</li>';

        $config['prev_link']      = '← Previous';
        $config['prev_tag_open']  = '<li class="prev page">';
        $config['prev_tag_close'] = '</li>';

        $config['cur_tag_open']  = '<li ><a href="" class="active">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open']  = '<li class="page">';
        $config['num_tag_close'] = '</li>';
        $this->pagination->initialize($config);
        $query = $this->stuattendence_model->biometric_attlog(100, $this->uri->segment(3));

        $data['resultlist'] = $query;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/biometric_attlog', $data);
        $this->load->view('layout/footer', $data);
    }

    public function lesson_plan()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/lesson_plan');
        $this->session->set_userdata('subsub_menu', 'Reports/lesson_plan/lesson_plan');
        $data                     = array();
        $data['subjects_data']    = array();
        $class                    = $this->class_model->get();
        $data['classlist']        = $class;
        $data['class_id']         = "";
        $data['section_id']       = "";
        $data['subject_group_id'] = "";
        $data['subject_id']       = "";
        $data['lessons']          = array();
        $lebel                    = "";

        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('subject_group_id', $this->lang->line('subject'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

        } else {

            $data['class_id']               = $_POST['class_id'];
            $data['section_id']             = $_POST['section_id'];
            $data['subject_group_id']       = $_POST['subject_group_id'];
            $subjects                       = $this->subjectgroup_model->getGroupsubjects($_POST['subject_group_id']);
            $subject_group_class_sectionsId = $this->lessonplan_model->getsubject_group_class_sectionsId($_POST['class_id'], $_POST['section_id'], $_POST['subject_group_id']);

            foreach ($subjects as $key => $value) {
                $show_status     = 0;
                $teacher_summary = array();
                $lesson_result   = array();
                $complete        = 0;
                $incomplete      = 0;
                $array[]         = $value;
                $lebel           = ($value->code == '') ? $value->name : $value->name . ' (' . $value->code . ')';

                $subject_details = $this->syllabus_model->get_subjectstatus($value->id, $subject_group_class_sectionsId['id']);
                if ($subject_details[0]->total != 0) {

                    $complete   = ($subject_details[0]->complete / $subject_details[0]->total) * 100;
                    $incomplete = ($subject_details[0]->incomplete / $subject_details[0]->total) * 100;

                    $data['subjects_data'][$value->id] = array(
                        'lebel'      => $lebel,
                        'complete'   => round($complete),
                        'incomplete' => round($incomplete),
                        'id'         => $value->id . '_' . $value->code,
                        'total'      => $subject_details[0]->total,
                        'name'       => $value->name,
                    );

                } else {

                    $data['subjects_data'][$value->id] = array(
                        'lebel'      => $lebel,
                        'complete'   => 0,
                        'incomplete' => 0,
                        'id'         => $value->id . '_' . $value->code,
                        'total'      => 0,
                        'name'       => $value->name,

                    );
                }

                $syllabus_report = $this->syllabus_model->get_subjectsyllabussreport($value->id, $subject_group_class_sectionsId['id']);
                $lesson_result   = array();
                foreach ($syllabus_report as $syllabus_reportkey => $syllabus_reportvalue) {

                    $topic_data     = array();
                    $topic_result   = $this->syllabus_model->get_topicbylessonid($syllabus_reportvalue['id']);
                    $topic_complete = 0;
                    foreach ($topic_result as $topic_resultkey => $topic_resultvalue) {
                        if ($topic_resultvalue['status'] == 1) {
                            $topic_complete++;
                        }

                        $topic_data[] = array('name' => $topic_resultvalue['name'], 'status' => $topic_resultvalue['status'], 'complete_date' => $topic_resultvalue['complete_date']);
                    }
                    $total_topic = count($topic_data);
                    if ($total_topic > 0) {
                        $incomplete_percent = round((($total_topic - $topic_complete) / $total_topic) * 100);
                        $complete_percent   = round(($topic_complete / $total_topic) * 100);
                    } else {
                        $incomplete_percent = 0;
                        $complete_percent   = 0;
                    }

                    $show_status     = 1;
                    $lesson_result[] = array('name' => $syllabus_reportvalue['name'], 'topics' => $topic_data, 'incomplete_percent' => $incomplete_percent, 'complete_percent' => $complete_percent);

                }

                $data['subjects_data'][$value->id]['lesson_summary'] = $lesson_result;

            }
        }

        $data['status'] = array('1' => $this->lang->line('complete'), '0' => $this->lang->line('incomplete'));
        $this->load->view('layout/header', $data);
        $this->load->view('reports/syllabus', $data);
        $this->load->view('layout/footer', $data);
    }

    public function teachersyllabusstatus()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/lesson_plan');
        $this->session->set_userdata('subsub_menu', 'Reports/lesson_plan/teachersyllabusstatus');
        $data                     = array();
        $data['subjects_data']    = array();
        $class                    = $this->class_model->get();
        $data['classlist']        = $class;
        $data['class_id']         = "";
        $data['section_id']       = "";
        $data['subject_group_id'] = "";
        $data['subject_id']       = "";
        $data['lessons']          = array();

        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('subject_group_id', $this->lang->line('subject') . " " . $this->lang->line('group'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('subject_id', $this->lang->line('subject'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

        } else {
            $lebel = "";

            $data['class_id']         = $_POST['class_id'];
            $data['section_id']       = $_POST['section_id'];
            $data['subject_group_id'] = $_POST['subject_group_id'];
            $data['subject_id']       = $_POST['subject_id'];
            $subjects                 = $this->subjectgroup_model->getGroupsubjects($_POST['subject_group_id']);

            $subject_group_class_sectionsId = $this->lessonplan_model->getsubject_group_class_sectionsId($_POST['class_id'], $_POST['section_id'], $_POST['subject_group_id']);

            $teacher_summary          = array();
            $complete                 = 0;
            $incomplete               = 0;
            $data['subject_name']     = "";
            $data['subject_complete'] = 0;
            $subjectdata              = $this->subject_model->get($_POST['subject_id']);

            $subject_details = $this->syllabus_model->get_subjectstatus($_POST['subject_id'], $subject_group_class_sectionsId['id']);
            if ($subject_details[0]->total != 0) {

                $complete   = ($subject_details[0]->complete / $subject_details[0]->total) * 100;
                $incomplete = ($subject_details[0]->incomplete / $subject_details[0]->total) * 100;
                if ($subjectdata['code'] == '') {
                    $lebel = $subjectdata['name'];
                } else {
                    $lebel = $subjectdata['name'] . ' (' . $subjectdata['code'] . ')';
                }
                $data['subjects_data'][$subjectdata['id']] = array(
                    'lebel'      => $lebel,
                    'complete'   => round($complete),
                    'incomplete' => round($incomplete),
                    'id'         => $subjectdata['id'] . '_' . $subjectdata['code'],
                );
                $data['subject_complete'] = round($complete);

            } else {

                $data['subjects_data'][$subjectdata['id']] = array(
                    'lebel'      => $lebel,
                    'complete'   => 0,
                    'incomplete' => 0,
                    'id'         => $subjectdata['id'] . '_' . $subjectdata['code'],
                );
                $data['subject_complete'] = 0;
            }

            $teachers_report = $this->syllabus_model->get_subjectteachersreport($_POST['subject_id'], $subject_group_class_sectionsId['id']);

            foreach ($teachers_report as $teachers_reportkey => $teachers_reportvalue) {
                if ($teachers_reportvalue['code'] == '') {
                    $data['subject_name'] = $teachers_reportvalue['subject_name'];

                } else {
                    $data['subject_name'] = $teachers_reportvalue['subject_name'] . " (" . $teachers_reportvalue['code'] . ")";

                }
                $syllabus_id       = explode(',', $teachers_reportvalue['subject_syllabus_id']);
                $staff_periodsdata = array();
                foreach ($syllabus_id as $syllabus_idkey => $syllabus_idvalue) {

                    $staff_periods       = $this->syllabus_model->get_subjectsyllabusbyid($syllabus_idvalue);
                    $staff_periodsdata[] = $staff_periods;

                }

                $teacher_summary[] = array(
                    'name'           => $teachers_reportvalue['name'],
                    'total_periods'  => $teachers_reportvalue['total_priodes'],
                    'summary_report' => $staff_periodsdata,
                );

            }

            $data['subjects_data'][$subjectdata['id']]['teachers_summary'] = $teacher_summary;

        }

        $this->load->view('layout/header', $data);
        $this->load->view('reports/teacherSyllabusStatus', $data);
        $this->load->view('layout/footer', $data);
    }

    public function alumnireport()
    {
        if (!$this->rbac->hasPrivilege('alumni_report', 'can_view')) {
            access_denied();
        }
        $data                = array();
        $data['sessionlist'] = $this->session_model->get();
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/alumni_report');
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $data['title']           = $this->lang->line('alumini_student_for_passout_session');
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['fields']          = $this->customfield_model->get_custom_fields('students', 1);
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $data['session_id']      = $session_id      = "";
        $userdata                = $this->customlib->getUserData();
        $carray                  = array();
        $alumni_student          = $this->alumni_model->get();
        $alumni_studets          = array();
        foreach ($alumni_student as $key => $value) {
            $alumni_studets[$value['student_id']] = $value;
        }
        $data['alumni_studets'] = $alumni_studets;
        if (!empty($data["classlist"])) {
            foreach ($data["classlist"] as $ckey => $cvalue) {

                $carray[] = $cvalue["id"];
            }
        }

        $button = $this->input->post('search');
        if ($this->input->server('REQUEST_METHOD') == "GET") {
            $this->load->view('layout/header', $data);
            $this->load->view('reports/alumnireport', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $class              = $this->input->post('class_id');
            $section            = $this->input->post('section_id');
            $search             = $this->input->post('search');
            $search_text        = $this->input->post('search_text');
            $data['session_id'] = $session_id = $this->input->post('session_id');
            if (isset($search)) {
                if ($search == 'search_filter') {
                    $this->form_validation->set_rules('session_id', $this->lang->line('session'), 'trim|required|xss_clean');
                    $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
                    if ($this->form_validation->run() == false) {

                    } else {
                        $data['searchby']    = "filter";
                        $data['class_id']    = $this->input->post('class_id');
                        $data['section_id']  = $this->input->post('section_id');
                        $data['search_text'] = $this->input->post('search_text');
                        $resultlist          = $this->student_model->search_alumniStudentReport($class, $section, $session_id);
                        $data['resultlist']  = $resultlist;

                    }
                } else if ($search == 'search_full') {
                    $data['searchby'] = "text";

                    $data['search_text'] = trim($this->input->post('search_text'));
                    $resultlist          = $this->student_model->search_alumniStudentbyAdmissionNoReport($search_text, $carray);
                    $data['resultlist']  = $resultlist;

                }
            }

            $this->load->view('layout/header');
            $this->load->view('reports/alumnireport', $data);
            $this->load->view('layout/footer');
        }

    }

    public function boys_girls_ratio()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/boys_girls_ratio');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        foreach ($data['classlist'] as $key => $value) {
            $carray[] = $value['id'];
        }

        $data['resultlist'] = $this->student_model->student_ratio();
        $total_boys         = $total_girls         = 0;
        foreach ($data['resultlist'] as $key => $value) {

            $total_boys += $value['male'];
            $total_girls += $value['female'];

            $data['result'][] = array('total_student' => $value['total_student'], 'male' => $value['male'], 'female' => $value['female'], 'class' => $value['class'], 'section' => $value['section'], 'class_id' => $value['class_id'], 'section_id' => $value['section_id'], 'boys_girls_ratio' => $this->getRatio($value['male'], $value['female']));
        }

        $data['all_boys_girls_ratio']      = $this->getRatio($total_boys, $total_girls);
        $data['all_student_teacher_ratio'] = $this->getRatio($total_boys, $total_girls);

        $this->load->view('layout/header', $data);
        $this->load->view('reports/student_ratio_report', $data);
        $this->load->view('layout/footer', $data);
    }

    public function student_teacher_ratio()
    {
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/student_information');
        $this->session->set_userdata('subsub_menu', 'Reports/student_information/student_teacher_ratio');
        $data['title']           = 'Add Fees Type';
        $data['searchlist']      = $this->search_type;
        $data['sch_setting']     = $this->sch_setting_detail;
        $data['adm_auto_insert'] = $this->sch_setting_detail->adm_auto_insert;
        $searchterm              = '';
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        foreach ($data['classlist'] as $key => $value) {
            $carray[] = $value['id'];
        }

        $data['resultlist'] = $this->student_model->student_ratio();
        $total_boys         = $total_girls         = $all_teacher         = $all_student         = 0;
        foreach ($data['resultlist'] as $key => $value) {

            $all_student += $value['total_student'];
            $count_classteachers = array();
            $count_classteachers = $this->student_model->count_classteachers($value['class_id'], $value['section_id']);

            if (!empty($count_classteachers)) {
                $total_teacher = $count_classteachers;
            } else {
                $total_teacher = 0;
            }

            $data['result'][] = array('total_student' => $value['total_student'], 'male' => $value['male'], 'female' => $value['female'], 'class' => $value['class'], 'section' => $value['section'], 'class_id' => $value['class_id'], 'section_id' => $value['section_id'], 'total_teacher' => $total_teacher, 'boys_girls_ratio' => $this->getRatio($value['male'], $value['female']), 'teacher_ratio' => $this->getRatio($value['total_student'], $total_teacher));

            $all_teacher += $total_teacher;
        }

        $data['all_student_teacher_ratio'] = $this->getRatio($all_student, $all_teacher);
        $this->load->view('layout/header', $data);
        $this->load->view('reports/teacher_ratio_report', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getRatio($num1, $num2)
    {
        if ($num2 > 0 && $num1 > 0) {
            $num = round($num2 / $num1, 2);

        } else {
            $num = 0;
        }

        if ($num1 == '0') {
            $by = 0;
            return "$by:$num2";
        } else {
            $by = 1;
            return "$by:$num";
        }

    }

    public function daily_attendance_report()
    {
        $data = array();
        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/attendance');
        $this->session->set_userdata('subsub_menu', 'Reports/attendance/daily_attendance_report');
        $date         = "";
        $data['date'] = "";
        $this->form_validation->set_rules('date', $this->lang->line('date'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $date         = " and student_attendences.date='" . date('Y-m-d') . "'";
            $data['date'] = date($this->customlib->getSchoolDateFormat());
        } else {
            $date         = " and student_attendences.date='" . date('Y-m-d', $this->customlib->datetostrtotime($_POST['date'])) . "'";
            $data['date'] = date($this->customlib->getSchoolDateFormat(), $this->customlib->datetostrtotime($_POST['date']));
        }

        $resultlist     = array();
        $data['result'] = $this->stuattendence_model->get_attendancebydate($date);
        if (!empty($data['result'])) {
            $all_student = $all_present = $all_absent = 0;
            foreach ($data['result'] as $key => $value) {
                $total_present = $value->present + $value->excuse + $value->late + $value->half_day;
                $total_student = $total_present + $value->absent;
                if ($total_present > 0) {
                    $presnt_percent = round(($total_present / $total_student) * 100);

                } else {
                    $presnt_percent = 0;

                }
                if ($value->absent > 0) {

                    $presnt_absent = round(($value->absent / $total_student) * 100);
                } else {

                    $presnt_absent = 0;
                }
                $all_student += $total_student;
                $all_present += $total_present;
                $all_absent += $value->absent;

                $data['resultlist'][] = array('class_section' => $value->class_name . " (" . $value->section_name . ")", 'total_present' => $total_present, 'total_absent' => $value->absent, 'present_percent' => $presnt_percent . "%", 'absent_persent' => $presnt_absent . "%");
                # code...
            }
            $data['all_student'] = $all_student;
            $data['all_present'] = $all_present;
            $data['all_absent']  = $all_absent;
            if ($all_student > 0) {
                $data['all_present_percent'] = round(($data['all_present'] / $data['all_student']) * 100) . "%";
                $data['all_absent_percent']  = round(($data['all_absent'] / $data['all_student']) * 100) . "%";
            } else {
                $data['all_present_percent'] = "0%";
                $data['all_absent_percent']  = "0%";
            }

        }

        $this->load->view('layout/header', $data);
        $this->load->view('reports/daily_attendance_report', $data);
        $this->load->view('layout/footer', $data);

    }

    public function getAvailQuantity($item_id)
    {

        $data      = $this->item_model->getItemAvailable($item_id);
        $available = ($data['added_stock'] - $data['issued']);
        if ($available >= 0) {
            return $available;
        } else {
            return 0;
        }

    }

    public function getinventorylist()
    {

        $dstockresult1 = $this->itemstock_model->get_currentstock();
        $m             = json_decode($dstockresult1);
        $dt_data       = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $available_stock = $this->getAvailQuantity($value->id);
                $row             = array();
                $row[]           = $value->name;
                $row[]           = $value->item_category;
                $row[]           = $value->item_supplier;
                $row[]           = $value->item_store;
                $row[]           = $available_stock;
                $row[]           = $value->available_stock;
                $row[]           = $value->total_not_returned;
                $dt_data[]       = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*-----------------function to check search validation for admission report ---*/

    public function searchreportvalidation()
    {

        $this->form_validation->set_rules('search_type', $this->lang->line('search') . " " . $this->lang->line('type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $error = array();

            $error['search_type'] = form_error('search_type');

            $array = array('status' => 0, 'error' => $error);
            echo json_encode($array);
        } else {
            $search_type = $this->input->post('search_type');
            $date_from   = "";
            $date_to     = "";
            if ($search_type == 'period') {

                $date_from = $this->input->post('date_from');
                $date_to   = $this->input->post('date_to');
            }

            $params = array('search_type' => $search_type, 'date_from' => $date_from, 'date_to' => $date_to);
            $array  = array('status' => 1, 'error' => '', 'params' => $params);
            echo json_encode($array);
        }
    }

    public function dtadmissionreport()
    {
        $sch_setting = $this->sch_setting_detail;
        $searchterm  = '';
        $class       = $this->class_model->get();
        $classlist   = $class;
        $count       = 0;

        foreach ($classlist as $key => $value) {
            $carray[] = $value['id'];
        }
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $between_date        = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $search_type = $_POST['search_type'];
        } else {

            $between_date        = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = $search_type = '';
        }

        $from_date    = date('Y-m-d', strtotime($between_date['from_date']));
        $to_date      = date('Y-m-d', strtotime($between_date['to_date']));
        $condition    = " date_format(admission_date,'%Y-%m-%d') between  '" . $from_date . "' and '" . $to_date . "'";
        $filter_label = date($this->customlib->getSchoolDateFormat(), strtotime($from_date)) . " To " . date($this->customlib->getSchoolDateFormat(), strtotime($to_date));

        $result     = $this->student_model->admission_report($searchterm, $carray, $condition);
        $resultlist = json_decode($result);
        $dt_data    = array();
        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $student) {

                $count++;
                $viewbtn = "<a  href='" . base_url() . "student/view/" . $student->id . "'>" . $this->customlib->getFullName($student->firstname, $student->middlename, $student->lastname, $sch_setting->middlename, $sch_setting->lastname) . "</a>";

                $row = array();

                $row[] = $student->admission_no;
                $row[] = $viewbtn;
                $row[] = $student->class . " (" . $student->section . ")";
                if ($sch_setting->father_name) {
                    $row[] = $student->father_name;
                }
                if ($student->dob != null && $student->dob != '0000-00-00') {
                    $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($student->dob));
                } else {
                    $row[] = "";
                }

                if ($sch_setting->admission_date) {
                    if ($student->admission_date != null && $student->admission_date != '0000-00-00') {

                        $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($student->admission_date));
                    } else {
                        $row[] = "";
                    }
                }

                $row[] = $student->gender;

                if ($sch_setting->category) {
                    $row[] = $student->category;
                }
                if ($sch_setting->mobile_no) {
                    $row[] = $student->mobileno;
                }

                $dt_data[] = $row;
            }

            $footer_row   = array();
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = $this->lang->line('total_admission_in_this_duration');
            $footer_row[] = $filter_label;
            $footer_row[] = $count;
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $dt_data[]    = $footer_row;

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    function to get formparateter */
    public function getformparameter()
    {

        $search_type = $this->input->post('search_type');
        $date_type   = $this->input->post("date_type");
        $date_from   = "";
        $date_to     = "";
        if ($search_type == 'period') {

            $date_from = $this->input->post('date_from');
            $date_to   = $this->input->post('date_to');
        }

        $params = array('search_type' => $search_type, 'date_type' => $date_type, 'date_from' => $date_from, 'date_to' => $date_to);
        $array  = array('status' => 1, 'error' => '', 'params' => $params);
        echo json_encode($array);

    }

    public function dtexamreportlist()
    {

        $search_type = $this->input->post('search_type');
        $date_type   = $this->input->post('date_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');

        $data['date_typeid'] = '';
        if (isset($search_type) && $search_type != '') {

            $dates               = $this->customlib->get_betweendate($search_type);
            $data['search_type'] = $search_type;

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));

        if (isset($date_type) && $date_type != '') {

            $data['date_typeid'] = $date_type;

            if ($date_type == 'exam_from_date') {
                $condition = " date_format(exam_from,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";
            } elseif ($date_type == 'exam_to_date') {
                $condition = " date_format(exam_to,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";
            }

        } else {
            $condition = " date_format(created_at,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";
        }

        $sch_setting = $this->sch_setting_detail;
        $results     = $this->onlineexam_model->onlineexamReport($condition);

        $resultlist = json_decode($results);
        $dt_data    = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $subject_value) {

                if ($subject_value->is_active == 1) {
                    $publish_btn = " <i class='fa fa-check-square-o'></i><span style='display:none'>Yes</span>";
                } else {
                    $publish_btn = " <i class='fa fa-exclamation-circle'></i><span style='display:none'>No</span>";
                }

                if ($subject_value->is_active == 1) {
                    $result_publish = " <i class='fa fa-check-square-o'></i><span style='display:none'>Yes</span>";
                } else {
                    $result_publish = "<i class='fa fa-exclamation-circle'></i><span style='display:none'>No</span>";
                }

                $row   = array();
                $row[] = $subject_value->exam;
                $row[] = $subject_value->attempt;
                $row[] = $this->customlib->dateyyyymmddToDateTimeformat($subject_value->exam_from, false);
                $row[] = $this->customlib->dateyyyymmddToDateTimeformat($subject_value->exam_to, false);
                $row[] = $subject_value->duration;
                $row[] = $subject_value->assign;
                $row[] = $subject_value->questions;
                $row[] = $publish_btn;
                $row[] = $result_publish;

                $dt_data[] = $row;
            }

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* function to get exam attempt report using datatable*/

    public function dtexamattemptreport()
    {
        $condition          = "";
        $search_type        = $this->input->post('search_type');
        $date_type          = $this->input->post('date_type');
        $date_from          = $this->input->post('date_from');
        $date_to            = $this->input->post('date_to');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['date_type']  = $this->customlib->date_type();

        $data['date_typeid'] = '';
        if (isset($search_type) && $search_type != '') {
            $dates               = $this->customlib->get_betweendate($search_type);
            $data['search_type'] = $search_type;
        } else {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));

        if (isset($_POST['date_type']) && $_POST['date_type'] != '') {

            $data['date_typeid'] = $_POST['date_type'];

            if ($search_type == 'exam_from_date') {

                $condition .= " and date_format(onlineexam.exam_from,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";

            } elseif ($date_type == 'exam_to_date') {
                $condition .= " and date_format(onlineexam.exam_to,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";
            }

        } else {

            $condition .= " and  date_format(onlineexam.created_at,'%Y-%m-%d') between '" . $start_date . "' and '" . $end_date . "'";

        }

        $result      = $this->onlineexam_model->onlineexamatteptreport($condition);
        $sch_setting = $this->sch_setting_detail;
        $resultlist  = json_decode($result);
        $dt_data     = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $student_value) {

                $exams = explode(',', $student_value->exams);

                $exam_name               = '';
                $exam_from               = '';
                $exam_to                 = '';
                $exam_duration           = '';
                $exam_publish            = "";
                $exam_resultpublish      = "";
                $exam_publishprint       = "";
                $exam_resultpublishprint = "";
                foreach ($exams as $exams_key => $exams_value) {
                    $exam_details = explode('@', $exams_value);

                    if (count($exam_details) == 9) {

                        $exam_name .= $exam_details[1];
                        $exam_from .= date($this->customlib->getSchoolDateFormat(), $this->customlib->dateYYYYMMDDtoStrtotime($exam_details[3]));
                        $exam_to .= date($this->customlib->getSchoolDateFormat(), $this->customlib->dateYYYYMMDDtoStrtotime($exam_details[4]));
                        $exam_duration .= $exam_details[5];
                        $exam_publish .= ($exam_details[7] == 1) ? "<i class='fa fa-check-square-o'></i>" : "<i class='fa fa-exclamation-circle'></i>";
                        $exam_resultpublish .= ($exam_details[8] == 1) ? "<i class='fa fa-check-square-o'></i>" : "<i class='fa fa-exclamation-circle'></i>";

                        $exam_publishprint .= ($exam_details[7] == 1) ? "<span style='display:none'>Yes</span>" : "<span style='display:none'>No</span>";
                        $exam_resultpublishprint .= ($exam_details[8] == 1) ? "<span style='display:none'>Yes</span>" : "<span style='display:none'>No</span>";

                        $exam_name .= '<br>';
                        $exam_from .= "<br>";
                        $exam_to .= "<br>";
                        $exam_duration .= "<br>";
                        $exam_publish .= "<br>";
                        $exam_resultpublish .= "<br>";
                        $exam_publishprint .= "<br>";
                        $exam_resultpublishprint .= "<br>";
                    }
                }

                $row   = array();
                $row[] = $this->customlib->getFullName($student_value->firstname, $student_value->middlename, $student_value->lastname, $sch_setting->middlename, $sch_setting->lastname);
                $row[] = $student_value->admission_no;

                $row[] = $student_value->class;
                $row[] = $student_value->section;
                $row[] = $exam_name;
                $row[] = $exam_from;
                $row[] = $exam_to;
                $row[] = $exam_duration;
                $row[] = $exam_publish . $exam_publishprint;
                $row[] = $exam_resultpublish . $exam_resultpublishprint;

                $dt_data[] = $row;
            }

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    function to get formparateter */
    public function getbookissueparameter()
    {

        $search_type  = $this->input->post('search_type');
        $members_type = $this->input->post("members_type");
        $date_from    = "";
        $date_to      = "";
        if ($search_type == 'period') {

            $date_from = $this->input->post('date_from');
            $date_to   = $this->input->post('date_to');
        }

        $params = array('search_type' => $search_type, 'members_type' => $members_type, 'date_from' => $date_from, 'date_to' => $date_to);
        $array  = array('status' => 1, 'error' => '', 'params' => $params);
        echo json_encode($array);

    }

    /* function to get book issue report by using datatable */
    public function dtbookissuereportlist()
    {

        $search_type = $this->input->post('search_type');
        $member_type = $this->input->post('date_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');

        $data['searchlist'] = $this->customlib->get_searchtype();
        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $data['members'] = array('' => $this->lang->line('all'), 'student' => $this->lang->line('student'), 'teacher' => $this->lang->line('teacher'));
        $start_date      = date('Y-m-d', strtotime($dates['from_date']));
        $end_date        = date('Y-m-d', strtotime($dates['to_date']));
        $data['label']   = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));

        $result = $this->bookissue_model->studentBookIssue_report($start_date, $end_date);

        $sch_setting = $this->sch_setting_detail;

        $resultlist = json_decode($result);
        $dt_data    = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $value) {

                $row   = array();
                $row[] = $value->book_title;
                $row[] = $value->book_no;
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->issue_date));
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->duereturn_date));
                $row[] = $value->members_id;
                $row[] = $value->library_card_no;
                if ($value->admission != 0) {
                    $row[] = $value->admission;
                } else {
                    $row[] = "";
                }
                if ($value->member_type == 'student') {
                    $row[] = $this->customlib->getFullName($value->firstname, $value->middlename, $value->lastname, $sch_setting->middlename, $sch_setting->lastname);
                } else {
                    $row[] = ucwords($value->staff_name);
                }
                $row[] = ucwords($value->member_type);

                $dt_data[] = $row;
            }

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* function to get book due report by using datatable */
    public function dtbookduereportlist()
    {
        $search_type = $this->input->post('search_type');
        $member_type = $this->input->post('date_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        if (isset($_POST['members_type']) && $_POST['members_type'] != '') {

            $data['member_id'] = $_POST['members_type'];

        } else {

            $data['member_id'] = '';

        }

        $data['members'] = array('' => $this->lang->line('all'), 'student' => $this->lang->line('student'), 'teacher' => $this->lang->line('teacher'));

        $start_date    = date('Y-m-d', strtotime($dates['from_date']));
        $end_date      = date('Y-m-d', strtotime($dates['to_date']));
        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $issued_books  = $this->bookissue_model->bookduereport($start_date, $end_date);
        $sch_setting   = $this->sch_setting_detail;

        $resultlist = json_decode($issued_books);
        $dt_data    = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $value) {

                $row   = array();
                $row[] = $value->book_title;
                $row[] = $value->book_no;
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->issue_date));
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->duereturn_date));
                $row[] = $value->members_id;
                $row[] = $value->library_card_no;
                if ($value->admission != 0) {
                    $row[] = $value->admission;
                } else {
                    $row[] = "";
                }
                if ($value->member_type == 'student') {
                    $row[] = $this->customlib->getFullName($value->firstname, $value->middlename, $value->lastname, $sch_setting->middlename, $sch_setting->lastname);
                } else {
                    $row[] = ucwords($value->fname) . " " . ucwords($value->lname);
                }
                $row[] = ucwords($value->member_type);

                $dt_data[] = $row;
            }

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* function to get book issue return report by using datatable */
    public function dtbookinventoryreportlist()
    {
        $search_type = $this->input->post('search_type');

        $date_from = $this->input->post('date_from');
        $date_to   = $this->input->post('date_to');

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();

        $start_date    = date('Y-m-d', strtotime($dates['from_date']));
        $end_date      = date('Y-m-d', strtotime($dates['to_date']));
        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $listbook      = $this->book_model->bookinventory($start_date, $end_date);

        $resultlist = json_decode($listbook);
        $dt_data    = array();

        if (!empty($resultlist->data)) {

            $editbtn   = "";
            $deletebtn = "";
            foreach ($resultlist->data as $resultlist_key => $value) {

                if ($value->description == "") {
                    $condition = "<p class='text text-danger no-print'>" . $this->lang->line('no_description') . " </p>";
                } else {
                    $condition = "<p class='text text-info no-print' >" . $value->description . "</p>";
                }

                $title = "<a href='#' data-toggle='popover' class='detail_popover'>" . $value->description . "</a> <div class='fee_detail_popover' style='display: none'> " . $condition . " </div> ";

                if ($this->rbac->hasPrivilege('books', 'can_edit')) {
                    $editbtn = "<a href='" . base_url() . "admin/book/edit/" . $value->id . "'   class='btn btn-default btn-xs'  data-toggle='tooltip' data-placement='left' title='" . $this->lang->line('edit') . "'><i class='fa fa-pencil'></i></a>";
                }
                if ($this->rbac->hasPrivilege('books', 'delete')) {

                    $deletebtn = "<a href='" . base_url() . "admin/book/delete/" . $value->id . "'   class='btn btn-default btn-xs'  data-toggle='tooltip' data-placement='left' title='" . $this->lang->line('edit') . "'><i class='fa fa-remove'></i></a>";
                }

                $row   = array();
                $row[] = $title;
                $row[] = $value->book_no;
                $row[] = $value->isbn_no;
                $row[] = $value->publish;
                $row[] = $value->author;
                $row[] = $value->subject;
                $row[] = $value->rack_no;
                $row[] = $value->qty;
                $row[] = $value->qty - $value->total_issue;

                $row[]     = $value->qty - ($value->qty - $value->total_issue);
                $row[]     = ($currency_symbol . $value->perunitcost);
                $row[]     = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->postdate));
                $row[]     = $editbtn . " " . $deletebtn;
                $dt_data[] = $row;
            }

        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    this function is used to get and return  form parameter without applying any validation  */
    public function getsearchtypeparam()
    {

        $search_type = $this->input->post('search_type');
        $date_from   = "";
        $date_to     = "";
        if ($search_type == 'period') {

            $date_from = $this->input->post('date_from');
            $date_to   = $this->input->post('date_to');
        }

        $params = array('search_type' => $search_type, 'date_from' => $date_from, 'date_to' => $date_to);
        $array  = array('status' => 1, 'error' => '', 'params' => $params);
        echo json_encode($array);
    }

    public function getincomelistbydt()
    {
        $search_type = $this->input->post('search_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');

        if ($search_type == "") {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        } else {
            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));

        $incomeList = $this->income_model->search("", $start_date, $end_date);

        $incomeList      = json_decode($incomeList);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        $grand_total     = 0;
        if (!empty($incomeList->data)) {
            foreach ($incomeList->data as $key => $value) {
                $grand_total += $value->amount;

                $row   = array();
                $row[] = $value->name;
                $row[] = $value->invoice_no;
                $row[] = $value->income_category;
                $row[] = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->date));
                $row[] = $currency_symbol . $value->amount;

                $dt_data[] = $row;
            }
            $footer_row   = array();
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "<b>" . $this->lang->line('grand_total') . "</b>";
            $footer_row[] = ($currency_symbol . number_format($grand_total, 2, '.', ''));
            $dt_data[]    = $footer_row;
        }

        $json_data = array(
            "draw"            => intval($incomeList->draw),
            "recordsTotal"    => intval($incomeList->recordsTotal),
            "recordsFiltered" => intval($incomeList->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);

    }

    public function getexpenselistbydt()
    {
        $search_type = $this->input->post('search_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');

        if ($search_type == "") {
            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        } else {
            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $expenseList   = $this->expense_model->search($start_date, $end_date,"");

        $m               = json_decode($expenseList);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        $grand_total     = 0;
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $grand_total += $value->amount;

                $row       = array();
                $row[]     = $value->name;
                $row[]     = $value->invoice_no;
                $row[]     = $value->exp_category;
                $row[]     = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($value->date));
                $row[]     = $currency_symbol . $value->amount;
                $dt_data[] = $row;
            }
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = $this->lang->line('grand_total');
            $footer_row[] = ($currency_symbol . number_format($grand_total, 2, '.', ''));
            $dt_data[]    = $footer_row;
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* this function is used to get and return income group report parameter without applying any validation */
    public function getgroupreportparam()
    {

        $search_type = $this->input->post('search_type');
        $head        = $this->input->post('head');

        $date_from = "";
        $date_to   = "";
        if ($search_type == 'period') {

            $date_from = $this->input->post('date_from');
            $date_to   = $this->input->post('date_to');
        }

        $params = array('search_type' => $search_type, 'head' => $head, 'date_from' => $date_from, 'date_to' => $date_to);
        $array  = array('status' => 1, 'error' => '', 'params' => $params);
        echo json_encode($array);
    }

    /* this function is used to get income group report by using datatable */

    public function dtincomegroupreport()
    {
        $search_type = $this->input->post('search_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');
        $head        = $this->input->post('head');

        if (isset($search_type) && $search_type != '') {

            $dates               = $this->customlib->get_betweendate($search_type);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }
        $data['head_id'] = $head_id = "";
        if (isset($_POST['head']) && $_POST['head'] != '') {
            $data['head_id'] = $head_id = $_POST['head'];
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label']   = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $incomeList      = $this->income_model->searchincomegroup($start_date, $end_date, $head_id);
        $m               = json_decode($incomeList);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        $grand_total     = 0;
        if (!empty($m->data)) {
            $grd_total = 0;
            foreach ($m->data as $key => $value) {

                $incomedata    = explode(',', $value->income);
                $income_id     = "";
                $income_name   = "";
                $income_date   = "";
                $invoice_no    = "";
                $income_amount = "";
                $total_amount  = 0;
                foreach ($incomedata as $incomevalue) {
                    $incomeexpload = explode('@', $incomevalue);
                    $income_id .= $incomeexpload[0];
                    $income_id .= "<br>";
                    $income_name .= $incomeexpload[1];
                    $income_name .= "<br>";
                    $income_date .= date($this->customlib->getSchoolDateFormat(), strtotime($incomeexpload[3]));
                    $income_date .= "<br>";
                    $invoice_no .= $incomeexpload[2];
                    $invoice_no .= "<br>";
                    $income_amount .= $incomeexpload[4];
                    $income_amount .= "<br>";
                }
                $total_amount = "<b>" . $value->total_amount . "</b>";
                $grd_total += $value->total_amount;
                $row       = array();
                $row[]     = $value->income_category;
                $row[]     = $income_id;
                $row[]     = $income_name;
                $row[]     = $income_date;
                $row[]     = $invoice_no;
                $row[]     = $income_amount;
                $dt_data[] = $row;

                $amount_row   = array();
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = $total_amount;
                $dt_data[]    = $amount_row;
            }

            $grand_total  = "<b>" . $currency_symbol . $grd_total . "</b>";
            $footer_row   = array();
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "<b>" . $this->lang->line('total') . "</b>";
            $footer_row[] = $grand_total;
            $dt_data[]    = $footer_row;
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* this function is used to get expense group report by using datatable */

    public function dtexpensegroupreport()
    {
        $search_type = $this->input->post('search_type');
        $date_from   = $this->input->post('date_from');
        $date_to     = $this->input->post('date_to');
        $head        = $this->input->post('head');

        $data['date_type']   = $this->customlib->date_type();
        $data['date_typeid'] = '';

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];

        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';

        }

        $data['head_id'] = $head_id = "";
        if (isset($_POST['head']) && $_POST['head'] != '') {
            $data['head_id'] = $head_id = $_POST['head'];
        }

        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));

        $data['label'] = date($this->customlib->getSchoolDateFormat(), strtotime($start_date)) . " " . $this->lang->line('to') . " " . date($this->customlib->getSchoolDateFormat(), strtotime($end_date));
        $result        = $this->expensehead_model->searchexpensegroup($start_date, $end_date, $head_id);

        $m               = json_decode($result);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $dt_data         = array();
        $grand_total     = 0;
        if (!empty($m->data)) {
            $grd_total = 0;
            foreach ($m->data as $key => $value) {

                $expensedata    = explode(',', $value->expense);
                $expense_id     = "";
                $expense_name   = "";
                $expense_date   = "";
                $invoice_no     = "";
                $expense_amount = "";
                foreach ($expensedata as $expensevalue) {
                    $expenseexpload = explode('@', $expensevalue);

                    $expense_id .= $expenseexpload[0];
                    $expense_id .= "<br>";
                    $expense_date .= date($this->customlib->getSchoolDateFormat(), strtotime($expenseexpload[1]));
                    $expense_date .= "<br>";
                    $expense_name .= $expenseexpload[2];
                    $expense_name .= "<br>";
                    $invoice_no .= $expenseexpload[3];
                    $invoice_no .= "<br>";
                    $expense_amount .= $expenseexpload[4];
                    $expense_amount .= "<br>";
                }

                $total_amount = "<b>" . $value->total_amount . "</b>";
                $grd_total += $value->total_amount;
                $row       = array();
                $row[]     = $value->exp_category;
                $row[]     = $expense_id;
                $row[]     = $expense_name;
                $row[]     = $expense_date;
                $row[]     = $invoice_no;
                $row[]     = $expense_amount;
                $dt_data[] = $row;

                $amount_row   = array();
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = "";
                $amount_row[] = $total_amount;
                $dt_data[]    = $amount_row;
            }

            $grand_total  = "<b>" . $currency_symbol . $grd_total . "</b>";
            $footer_row   = array();
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "<b>" . $this->lang->line('total') . "</b>";
            $footer_row[] = $grand_total;
            $dt_data[]    = $footer_row;
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function onlineadmission()
    {

        $this->session->set_userdata('top_menu', 'Reports');
        $this->session->set_userdata('sub_menu', 'Reports/finance');
        $this->session->set_userdata('subsub_menu', 'Reports/finance/onlineadmission');
        $data['searchlist'] = $this->customlib->get_searchtype();
        $data['group_by']   = $this->customlib->get_groupby();

        if (isset($_POST['search_type']) && $_POST['search_type'] != '') {

            $dates               = $this->customlib->get_betweendate($_POST['search_type']);
            $data['search_type'] = $_POST['search_type'];
        } else {

            $dates               = $this->customlib->get_betweendate('this_year');
            $data['search_type'] = '';
        }

        $collection = array();
        $start_date = date('Y-m-d', strtotime($dates['from_date']));
        $end_date   = date('Y-m-d', strtotime($dates['to_date']));
        $this->form_validation->set_rules('search_type', $this->lang->line('search') . " " . $this->lang->line('type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

            $data['collectlist'] = array();

        } else {

            $data['collectlist'] = $this->onlinestudent_model->getOnlineAdmissionFeeCollectionReport($start_date, $end_date);

        } 
        $data['sch_setting'] = $this->sch_setting_detail;
        $this->load->view('layout/header', $data);
        $this->load->view('reports/onlineadmission', $data);
        $this->load->view('layout/footer', $data);
    }
}
