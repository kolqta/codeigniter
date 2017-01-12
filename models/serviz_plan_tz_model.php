<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!class_exists('Serviz_plan_to_model'))
    require_once(FCPATH.APPPATH.'models/serviz_plan_to_model.php');

class Serviz_plan_tz_model extends Serviz_plan_to_model {

    /**
     * Конструктор
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Изтеглят се данните от декларираната таблица
     *
     * @param  object $query_row - sql заявката за информацията която ни трябва на базата на @type_car
     * @return array
     */
    public function getAll($query_row = false) {
        $query_row = $this->getCar();         
        
        if($query_row) {
            if($this->where)
                $query_row = $query_row->where($this->where);
            // тази част е много важна за листването на страниците
            $this->query_row = clone $query_row;
            $result = $query_row->limit($this->offset, $this->limit())->get(); 
            if(is_object($result) and $result->num_rows() > 0) return $result->result_array();
            else return false;
        }
    }
    
    /**
     * Изтеглям всички коли от плана
     *
     * @return this->db select where
     */
    public function getCar() {    
        $query_row = $this->db->select('pt.planning_id, pt.reasons_for_repair_id, lum.string as model, lumr.string as marka, lupl.string as plan_name,lutc.string, lu1.code, c.identificator, c.car_number,lurp.string as repairs')
                ->from('planning_templates pt')                
                ->join('language_users lupl', 'pt.string_id = lupl.string_id', 'left') 
                ->join('lists_users lu1', 'pt.type_car_id = lu1.list_user_id', 'left') 
                ->join('language_users lutc', 'lu1.string_id = lutc.string_id', 'left') 
                ->join('list_cars_model lcm', 'pt.model_id = lcm.cars_model_id', 'left') 
                ->join('lists_users lu2', 'lcm.cars_brand_id = lu2.list_user_id', 'left') 
                ->join('language_users lumr', 'lu2.string_id = lumr.string_id', 'left') 
                ->join('language_users lum', 'lcm.string_id = lum.string_id', 'left') 
                ->join('cars c', 'pt.car_id = c.car_id', 'left') 
                ->join('lists_users lu', 'pt.reasons_for_repair_id = lu.list_user_id', 'left') 
                ->join('language_users lurp', 'lu.string_id = lurp.string_id', 'left')
                ->where('pt.group_id', $this->group_id)
                ->where('pt.hidden', $this->hidden)
                ->where('pt.type_planning', 'plan_type')
                ->where('lupl.lang_id', (int)$this->lang_id)                
                ->where('lutc.lang_id', (int)$this->lang_id)
                ->where('lumr.lang_id', (int)$this->lang_id)
                ->where('lum.lang_id', (int)$this->lang_id)
                ->where('lurp.lang_id', (int)$this->lang_id) 
                ->where('lu1.code', $this->type_car);
        
        return $query_row;
    }
    
    /**
     * Запазвам формата от job_tab в таблицата 'planning_job'
     *
     */
    public function saveJob($post = array()) {
        foreach($post['plan_time'] as $key => $value){
            $data = array();
            if(trim($value) != '') {
                if($key < 0) {
                    $data['plan_time']                  = $value;
                    $data['operations_status_list_id']  = $post['operations_status_list_id'][$key];
                    $this->db->where('planning_job_id', abs($key))->update('planning_job', $data);
                    $this->saveLog('update', 359, 'planning_job', $data, abs($key)); // Лог
                } else {
                    $data['planning_id']                = $post['planning_id'];
                    $data['plan_time']                  = $value;
                    $data['operations_status_list_id']  = $post['operations_status_list_id'][$key];
                    $data['repair_id']                  = $post['repair_id'][$key];
                    $data['hidden']                     = 0;
                    $this->db->insert('planning_job', $data);
                    $this->saveLog('insert', 359, 'planning_job', $data); // Лог

                }
            }
        }
    }

    /**
     * Копирам зададения план
     *
     * @param  var $planning_id
     * @return var ID
     */
    public function copy($old_planning_id = 0, $new_planning_id = 0) {
        if($old_planning_id) {
            //записвам задачите
            $planning_job = $this->db->select()->from('planning_job')->where('planning_id', $old_planning_id)->get()->result_array();
            foreach($planning_job as $key => $value) {
                //старото ID
                $old_planning_job_id = $value['planning_job_id'];
                
                $data                   = array();
                $data                   = $value;
                $data['planning_id']    = $new_planning_id;
                unset($data['planning_job_id']);
                
                $this->db->insert('planning_job', $data);
                // новото ID
                $new_planning_job_id = $this->db->insert_id();
                
                //записвам ресурсите
                $planning_resources = $this->db->select()->from('planning_resources')->where('planning_job_id', $old_planning_job_id)->get()->result_array();
                foreach($planning_resources as $key => $value) {
                    $data                       = array();
                    $data                       = $value;
                    $data['planning_job_id']    = $new_planning_job_id;
                    unset($data['pk']);
                    $this->db->insert('planning_resources', $data);
                }
            }
        }
    }
}

/* End of file Serviz_plan_tz_model.php */
/* Location: ./application/models/Serviz_plan_tz_model.php */