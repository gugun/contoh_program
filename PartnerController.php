<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PartnerController
 *
 * @author msu
 */
require_once 'BaseController.php';
class PartnerController extends Web_BaseController {
    
    public function indexAction() {
        $request = $this->getRequest();
			
            $data = $request->getParams();			
            $top = $request->getParam('top');
            if (!$top) {
                $top = 5;
            }
            
            $page = $request->getParam('page');
            if (!$page or !is_numeric($page)) {
                $page = 1;
            }            
			
			$category = ApplicationRegistry::getService('MenuService')->getCategory($data['ctgr']);
            
            $service = ApplicationRegistry::getService('PartnerService');
            $showAllRegion = $service->showAllRegion();
            
            $data['region_select'] = ($request->isPost() and $request->getPost('kirim')) ? $request->getPost('region_select') : 'jabotabek';
            
            $partner = $service->getPartnerData($data['region_select'], 'id', $page, $top);
            
            $this->view->allRegion = $showAllRegion;
            $this->view->region = $data['region_select'];
            $this->view->partner = $partner->list;
            $this->view->totalRow = $partner->totalRow;
            $this->view->perPage = $partner->perPage;
            $this->view->totalPage = $partner->totalPage;
            $this->view->currentPage = $page;     
			$this->view->category = $category;
    }
    
    public function viewAction() {
        
    }
    
    public function submenuAction() {
        
    }
    
    public function categoryAction() {
        
    }        
}

?>
