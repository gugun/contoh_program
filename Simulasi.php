<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Simulasi
 *
 * @author gun
 */
class Web_Model_DbTable_Simulasi extends Msu_Model_BaseDao {
    
    protected $_name = 't_simulasi_result';
    protected $_primary = 'id';
    
    protected $vehcDao;
    
    public function getVehcDao() {
        if (!$this->vehcDao) {
            $this->vehcDao = new Admin_Model_DbTable_Vehicle();
        }
        
        return $this->vehcDao;
    }
    
    public function getAllVehicleBrand() {
        $select = $this->_db->select()
                ->from('m_vehicle_brand');
        
        return $this->_db->fetchAll($select);
    }
    
    public function getBrandModel($brand = '') {
        $select = $this->_db->select()
                ->from(array('a'=>'t_vehicle_model'))
                ->joinLeft(array('b'=>'m_vehicle_brand'), 'a.vehicle_model_brand = b.vehicle_brand', 'a.vehicle_model_name')
                ->where('a.vehicle_model_brand = ?', $brand);
        
        return $this->_db->fetchAll($select);
    }
    
    public function getTypeModel($model = '') {
        $select = $this->_db->select()
                ->from(array('a'=>'t_vehicle_type'))
                ->joinLeft(array('b'=>'t_vehicle_model'), 'a.vehicle_type_model = b.vehicle_model_name', 'a.vehicle_type_name')
                ->where('a.vehicle_type_model = ?', $model);
        
        return $this->_db->fetchAll($select);
    }
	
	public function getUserSim($email) {
		$sql = $this->_db->select()
				->from($this->_name)
				->where('create_by = ?', $email);
		
		return $this->_db->fetchRow($sql);
	}
    
}
