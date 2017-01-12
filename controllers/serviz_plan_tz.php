<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!class_exists('Extends_controller'))
    require_once('extends_controller.php');

class Serviz_plan_tz extends Extends_controller {
    
    public $languages;
    /**
     * Конструктор
     * 
     * @property   Serviz_plan_tz_model     $sz
     */
    function __construct()
    {
        parent::__construct();
        if(!class_exists('Serviz_plan_tz_model')) require_once(FCPATH.APPPATH.'models/serviz_plan_tz_model.php');
        $this->spt = new Serviz_plan_tz_model();
        
        // дефинирам стойност от логнатия потребител които да ползвам в заявките
        $this->spt->lang_id     = $this->lang_id;
        $this->spt->user_id     = $this->user_id;
        $this->spt->group_id    = $this->group_id;
        $this->spt->hidden      = $this->hidden;
        $this->spt->type_car    = 
                ($this->uri->segment(3) ? $this->uri->segment(3) : 
                (isset($_SESSION['type_car']) ? $_SESSION['type_car'] : 'car'));
        $_SESSION['type_car']   = $this->spt->type_car;
        
        // дефинирам езиците за групата
        $this->languages        = $this->spt->getLangId();
    }
      
    /**
     * Index контролер на страницата
     */
    public function index()
    {
        //delete
        if($id = (int)$this->input->get('delete')) { $this->spt->delete($id); exit; }
        
        $this->spt->page        = (int)$this->input->get_post('page') ? $this->input->get_post('page') : 1;
        $this->spt->offset      = $this->getLPPforUser();
        
        // добавям е като за пример иначе 0 за активни 1 за изтрити
        $this->spt->setHidden(((int)$this->input->get_post('hidden') ? $this->input->get_post('hidden') : 0));
        if($this->input->post()) $this->spt->filter($this->input->post());
        $data['planning']                   = $this->spt->getAll();
        $data['cars_brands_select']         = $this->spt->getFilterCarsBrands();
        $data['cars_model_select']          = $this->spt->getFilterListModel();
        $data['reasons_for_repair_select']  = $this->spt->getFilterReasonsForRepair();

        // за листинга
        $this->spt->Pagination();
        $data['count']          = $this->spt->count;
        $data['pages']          = $this->spt->pages;
        $data['page']           = $this->spt->page;
        $data['lines_per_page'] = $this->spt->offset;

        $data['editable']       = 1;
        $data['headen']         = ((int)$this->input->get_post('hidden') ? $this->input->get_post('hidden') : 0);
        // url пътища
        $data['path']           = '/serviz_plan_tz/index/'.$this->spt->type_car;
        $data['sort_url']       = site_url().$data['path'].'/';
        
        // za premahwane
        $data['planning_id'] = 1;
        $content = $this->load->view('serviz_plan_tz/list', $data, TRUE);      
        $this->_render($content, transl(10046));
    }
      
    /**
     * Add контролер на страницата
     */
    public function edit()
    {
        // реда на плана за едит
        $planning_id            = $this->input->post('planning_id') ? (int)$this->input->post('planning_id') : (int)$this->input->get('id');
        $data['edit']           = $this->spt->getEdit($planning_id);
        
        // това е за промяна и мисля че трябва да изва от модела за езиците
        $data['languages'] = $this->languages;
        
        // типа данни които търсим
        $type_car                           = $this->spt->getType($data['edit'][$this->spt->lang_id]['type_car_id']);
        $data['brand_id']                   = $this->input->post('brand_id') ? array('id' => $this->input->post('brand_id')) : $this->spt->getBrands($type_car['code'], array('model_id' => $data['edit'][$this->spt->lang_id]['model_id']));
        // филтрите
        $data['cars_brands_select']         = $this->spt->getBrands($type_car['code']);
        $data['cars_model_select']          = $this->spt->getModels($type_car['code'], $data['brand_id']['id']);        
        $data['reasons_for_repair_select']  = $this->spt->getLists('activities_for_repairs', 3);
        
        //основните данни
        if($data['edit'][$this->spt->lang_id]['type_planning'] == 'plan_reg_number')
            $data['car']            = $this->spt->getFromCar($data['edit'][$this->spt->lang_id]['car_id']);
        $data['lang_id']            = $this->spt->lang_id;
        $data['planning_id']        = $data['edit'][$this->spt->lang_id]['planning_id'];
        $data['tabs']               = 'tabs'+date('YmdHis');
        $data['editable']           = 1;
        $data['copy']               = (int)$this->input->get('copy') ? (int)$this->input->get('id') : 0;
        $data['action']             = $this->action;
        
        $view_data['editable']      = 1;
        $view_data['tab_content']   = $this->load->view('serviz_plan_tz/main_tab', $data, TRUE);
        $view_data['planning_id']   = $data['planning_id'];
        $content = $this->load->view('serviz_plan_tz/edit', $view_data, TRUE);
      
        $this->_render($content, transl(10050).' '.$data['edit'][$this->spt->lang_id]['string'].( $data['copy'] ? transl(10042) : ''));
    }
      
    /**
     * Add контролер на страницата
     */
    public function add()
    {
        // това е за промяна и мисля че трябва да изва от модела за езиците
        $data['languages'] = $this->languages;
       
        $type_car                   = $this->spt->getTypeIdFromCode($this->spt->type_car);
        $data['type_car_id']        = $type_car['id'];
        $data['cars_brands_select'] = $this->spt->getBrands($this->spt->type_car);        
        if($this->input->post('brand_id'))
            $data['cars_model_select']  = $this->spt->getModels($this->spt->type_car, $this->input->post('brand_id'));

        $data['reasons_for_repair_select']  = $this->spt->getReasons();
        
        // имаме си id
        $data['planning_id']        = 0;
        $data['tabs']               = 'tabs'+date('YmdHis');
        $data['editable']           = 1;
        $data['action']             = $this->action;
        
        $view_data['editable']      = 1;
        $view_data['tab_content']   = $this->load->view('serviz_plan_tz/main_tab', $data, TRUE);
        $view_data['planning_id']   = 0;
        $content = $this->load->view('serviz_plan_tz/edit', $view_data, TRUE);
      
        $this->_render($content, transl(10049, 'Създаване на Типово Задание'));
    }
      
    /**
     * job_tab контролер на страницата
     */
    public function job_tab()
    {
        $planning_id = (int)$this->input->get('id');
        
        //delete
        if($id = (int)$this->input->get('delete')) { $this->spt->deleteJob($id); exit; }
        
        // функция за валидация
        if($this->input->post()) { 
            $this->form_validate('job');
            
            if ($this->form_validation->run()){
                $this->spt->saveJob($this->input->post());
                // връщаме OK
                $ret['_RESULT']         = 'OK';
                $ret['_MESSAGE']        = '<p class="success">Form is OK</p>';
                $ret['_URL_TO_RELOAD']  = 'serviz_plan_tz/job_tab?id='.$this->input->post('planning_id');
                $ret['_DIV_TO_RELOAD']  = '#page_job';
            } else {
                // неуспешна валидация, 
                $ret=$this->form_validation->error_array();
                // генерираме масив с грешки
                foreach ($ret as $k => $v){
                    $ret[$k] = '<div class="error_message error fontnormal">'.$v.'</div>';
                }
                
                // генерираме съобщение за грешка
                $ret['_RESULT'] = FALSE;
                $ret['_MESSAGE']= '<p class="error">Form is not OK</p>';
            }
            echo json_encode($ret);
            exit;
        }
        
        // добавям е като за пример иначе 0 за активни 1 за изтрити
        $hidden = ((int)$this->input->get_post('hidden') ? $this->input->get_post('hidden') : 0);
        $data['headen']             = $hidden;
        $data['tabs_id']            = 'tabs'+date('YmdHis');
        $data['planning_id']        = (int)$this->input->get('id');
        $data['edit']               = $this->spt->getJob($planning_id, $hidden);
        $data['types_of_repairing'] = $this->spt->getLists('types_of_repairing', 1);
        $data['status_operation']   = $this->spt->getLists('status_operation', 1);
        
        $this->load->view('serviz_plan_tz/job_tab', $data);
    }
      
    /**
     * resources_tab контролер на страницата
     */
    public function resources_tab()
    {
        //save
        if($this->input->post('planning_job_id')) {
            // функция за валидация
            $this->form_validate('resources');
            if ($this->form_validation->run()){
                $this->spt->saveResources($this->input->post());
                // връщаме OK
                $ret['_RESULT']     = 'OK';
                $ret['_MESSAGE']    = '<p class="success">Form is OK</p>';
                $ret['_URL_TO_RELOAD']  = 'serviz_plan_tz/resources_tab?id='.$this->input->post('planning_job_id').'&name='.$this->input->post('tab_name');
                $ret['_DIV_TO_RELOAD']  = '#resources_job';
            } else {
                // неуспешна валидация, 
                $ret=$this->form_validation->error_array();
                // генерираме масив с грешки
                foreach ($ret as $k => $v){
                    $ret[$k] = '<div class="error_message error fontnormal">'.$v.'</div>';
                }
                
                // генерираме съобщение за грешка
                $ret['_RESULT'] = FALSE;
                $ret['_MESSAGE']= '<p class="error">Form is not OK</p>';
            }
            echo json_encode($ret); exit;
        }
        //delete
        if($id = (int)$this->input->get('delete')) { $this->spt->deleteResources($id); exit; }
        
        $data['planning_job_id']    = (int)$this->input->get('id');
        $data['name']               = $this->input->get('name');
        // добавям е като за пример иначе 0 за активни 1 за изтрити
        $hidden = ((int)$this->input->get_post('hidden') ? $this->input->get_post('hidden') : 0);
        $data['edit']               = $this->spt->getResources($data['planning_job_id'], $hidden);
        $data['headen']             = $hidden;
        
        $data['type_resources']     = $this->spt->getLists('type_resources', 1);
        if($this->input->post('row') == 'type_resources') {
            $data['resources']      = $this->spt->getListsResource((int)$this->input->post('value'));
            echo json_encode($data['resources']); exit;
        }
        if($this->input->post('row') == 'resources_id') {
            $data['units']          = $this->spt->getListsUnits((int)$this->input->post('value'));
            echo $data['units']; exit;
        }
        $group_setting              = $this->spt->getGroupSetting();
        $data['vat']                = $group_setting['vat'];
        
        $content = $this->load->view('serviz_plan_tz/resources_tab', $data, TRUE);
        
        $this->_render($content, transl(10021).' '.$this->input->get('name'));
    }
      
    /**
     * Edit_main_tab контролер на страницата
     */
    public function main_tab()
    {
        // функция за валидация
        $this->form_validate('main');
        if ($this->input->post() or $this->input->is_ajax_request()) {
            // проверяваме дали сме в AJAX call
            if ($this->form_validation->run()){
                // инсъртвам данните ако всичко е наред
                $planning_id    = $this->spt->save($this->input->post());
                if($this->input->post('copy')) $this->spt->copy((int)$this->input->post('copy'), $planning_id);
                $string         = $this->input->post('string');
                
                // връщаме OK
                $ret['_RESULT']     = 'OK';
                $ret['_MESSAGE']    = '<p class="success">Form is OK</p>';
                // и инструкция за опресняване 
                $ret['_ID']         = $planning_id;
                $ret['_TITLE']      = 'План: '.$string[1];
                $ret['_ENABLE_TABS']= array(1);                
            } else {
                // неуспешна валидация, 
                $ret=$this->form_validation->error_array();
                // генерираме масив с грешки
                foreach ($ret as $k => $v){
                    $ret[$k] = '<div class="error_message error fontnormal">'.$v.'</div>';
                }
                
                // генерираме съобщение за грешка
                $ret['_RESULT'] = FALSE;
                $ret['_MESSAGE']= '<p class="error">Form is not OK</p>';
            }
            echo json_encode($ret);
        }
    }
      
    /**
     * Get контролер на страницата
     */
    public function getCarsInfo() {
        $result = $this->spt->getCars($this->input->get('term'), $this->input->get('in'));
        $data = array();
        if ($result) foreach ($result as $key => $value) {
            $data[] = array(
                'label'	=> ($this->input->get('in') == 'car_number' ? $value['car_number'] : $value['identificator']),
                'value'	=> ($this->input->get('in') == 'car_number' ? $value['identificator'] : $value['car_number']).'-'.$value['car_id']
            );
        }
        if($data) echo json_encode($data);
        exit;
    }
      
    /**
     * функция която подготвя стойностите за валидация на формите main_tab
     */
    public function form_validate($tab = 'main')
    {
        if($tab == 'main') {
            // инициализираме задължителните полета във формата
            // с този цикъл задавам за всики един език по отделна проверка и валидация задължително на БГ
            foreach($this->languages as $key => $lang) {
                if($lang['lang_id'] == 1)
                    $this->form_validation->set_rules('string[1]', 'Българския е задължителен', 'trim|required|xss_clean|callback_check_string');
                else if($this->input->post('string['.$lang['lang_id'].']'))
                    $this->form_validation->set_rules('string['.$lang['lang_id'].']', 'Българския е задължителен', 'trim|required|xss_clean|callback_check_string');
            }
            
            $this->form_validation->set_rules('reasons_for_repair_id', 'Описание', 'trim|required|xss_clean|is_natural_no_zero');
            
            //По марка и модел
            if(!$this->input->post('planning_id') and $this->input->post('type_planning') == 'plan_brand') {
                $this->form_validation->set_rules('brand_id', 'Марkа', 'trim|required|xss_clean|is_natural_no_zero');
                $this->form_validation->set_rules('model_id', 'Модел', 'trim|required|xss_clean|is_natural_no_zero|callback_check_model');
                
            //По регистрационен номер
            } else if($this->input->post('type_planning') == 'plan_reg_number') {
                if(!$this->input->post('planning_id')) {
                    $this->form_validation->set_rules('car_number', 'Регистрационен номер', 'trim|required|xss_clean');
                    $this->form_validation->set_rules('last_service_date', 'Последно обслужване', 'trim|required|xss_clean');
                }
                $this->form_validation->set_rules('last_service_km', 'Последно километраж', 'trim|required|xss_clean');
            }
        
        } else if($tab == 'resources') {
            if($this->input->post('type_resources')) foreach($this->input->post('type_resources') as $key => $value)
                if($key >= 0) $this->form_validation->set_rules('type_resources['.$key.']', 'Тип ресурс', 'trim|required|xss_clean|is_natural_no_zero');            
            if($this->input->post('resources_id')) foreach($this->input->post('resources_id') as $key => $value)
                if($key >= 0) $this->form_validation->set_rules('resources_id['.$key.']', 'Ресурс', 'trim|required|xss_clean|is_natural_no_zero');
            if($this->input->post('quantity')) foreach($this->input->post('quantity') as $key => $value)
                $this->form_validation->set_rules('quantity['.$key.']', 'Количество', 'trim|required|xss_clean');
            if($this->input->post('unit_price')) foreach($this->input->post('unit_price') as $key => $value)
               $this->form_validation->set_rules('unit_price['.$key.']', 'Цена', 'trim|required|xss_clean');
           
        } else if($tab == 'job') {
            if($this->input->post('plan_time')) foreach($this->input->post('plan_time') as $key => $value) {
                if($key >= 0) {
                    $this->form_validation->set_rules('plan_time['.$key.']', 'Планирана продължителност', 'required|regex_match[/^(\d{1,2})$|^(\d{1,2}):?$|^(\d{1,2}):(([0-5])([0-9]?))?$/]');
                    $this->form_validation->set_message('plan_time['.$key.']', transl(556));
                }
            }
            if($this->input->post('repair_id')) foreach($this->input->post('repair_id') as $key => $value)
                if($key >= 0) $this->form_validation->set_rules('repair_id['.$key.']', 'Операция', 'trim|required|xss_clean|is_natural_no_zero');
            if($this->input->post('operations_status_list_id')) foreach($this->input->post('operations_status_list_id') as $key => $value)
                if($key >= 0) $this->form_validation->set_rules('operations_status_list_id['.$key.']', 'Тип на операцията', 'trim|required|xss_clean|is_natural_no_zero');
            
        }
    }
    
    /**
     * Функция за проверка на на стринга дали вече присътства с базата данни "language_users"
     * 
     * @param  val $str - името за проверка
     * @return bool
     */
    public function check_string($str) {
        foreach($this->languages as $key => $lang) {
            if($this->spt->check_name($str, $lang['lang_id'], $this->input->post('planning_id'))) {
                $this->form_validation->set_message(__FUNCTION__, transl(10043));
                return false;
            } else
                return true;
        }
    }
    
    /**
     * Функция за проверка на модел, целта е да не се повтарят
     * 
     * @param  val $model_id - ID на модела
     * @return bool
     */
    public function check_model($model_id) {
        if($this->spt->check_model($model_id, $this->input->post('planning_id'))) {
            $this->form_validation->set_message(__FUNCTION__, transl(10044));
            return false;
        } else
            return true;
    }
    
}

/* End of file Serviz_plan_tz_model.php */
/* Location: ./application/controllers/Serviz_plan_tz_model.php */