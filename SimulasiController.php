<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SimulasiController
 *
 * @author msu
 */
require_once 'BaseController.php';
class SimulasiController extends Web_BaseController {
    const SUBSIDI_PREMI_PCT = 0.1;
    const UANG_MUKA_PCT = 0.20;
    const FIDUCIA = 250000;
    
    private $fPrice, $fTenor, $fBunga;
    private $fPremigross, $fSukubungaPerTahun, $fAdm;
    private $fPokokhutang, $fSubsidipremi, $fPokokutangsubsidi;
    private $fNilaikontrak, $fPremibayar;
    private $fUangmuka, $fBungatahun, $fAngsuranbulanan;
    private $fPremiasuransi, $fFiducia;
    private $fPremiGrossPct, $fPremiPct, $fTahun;
    private $fTotalBayarAwal;        
    
    public function Process($Price, $UangMuka, $Bulan, $Bunga, $TipeAngsuran) {
                    $fPrice = $Price;
                    $fTenor = $Bulan;
                    $fTahun = $Bulan / 12;

                    $fBunga = $Bunga / 100;

                    $fUangMuka = $fPrice * self::UANG_MUKA_PCT;
                    if ($UangMuka == '') $UangMuka = $fUangMuka;
                    if ((double) $fUangMuka < (double) $UangMuka) $fUangMuka = $UangMuka;

                    $fPokokhutang = $fPrice - $fUangMuka;                    
                    $fAngsuranbulanan = ( $fPokokhutang + ( $fPokokhutang * $fBunga * $fTahun) ) / ( $fTenor );

                    if (trim(strtoupper($TipeAngsuran)) == 'ADV') $fTotalBayarAwal = $fUangMuka + $fAngsuranbulanan;

                    $UangMuka = $fUangMuka;
            
            $data = array($fPokokhutang, $fAngsuranbulanan, $fTotalBayarAwal, $UangMuka);
            
            return $data;
    }
    
    public function simulasiKreditAction() {
        $request = $this->getRequest();
        $ctgr = $request->getParam('ctgr');
        $ctgr = 'simulasi-kredit';
		
		$q = $request->getParam('user');
		$this->view->q = $q;
		
		$bunga_hid = $request->getParam('percentbunga_hid');
        
		$service2 = ApplicationRegistry::getService('Admin_Service_SimulasiService');
                $rate = $service2->getRate();
		$bunga = (empty($bunga_hid)) ? (float) $rate['simulasi_rate_bunga'] : $bunga_hid ;
		$this->view->rate = $bunga;
		
		$contentService = ApplicationRegistry::getService('Admin_Service_ContentService');
		$data = $contentService->getDataPersyaratanSimulasi('prsyrtn-simulasi');
		$this->view->persyaratan = $data;
		
        $category = ApplicationRegistry::getService('MenuService')->getCategory($ctgr);
        
        if ($category['ctgr_parent'] && $category['ctgr_parent'] == 'simulasi') {
            $subcategory = ApplicationRegistry::getService('CategoryService')->getChildCategory($category['ctgr_parent']);            
            $this->view->subcategory = $subcategory;
        }
        
        $this->view->category = $category;
		
		$session = new Zend_Session_Namespace('WEB_AUTH');
		
		unset($session->price);
		unset($session->uangmuka);
		unset($session->bulan);
		unset($session->hutang);
		unset($session->angsuran);
		unset($session->totalbayarAwal);
		
		$service = ApplicationRegistry::getService('SimulasiService');
		
		if (isset($session->user->user_name)) {
			$simUser = $service->getUserSim($session->user->user_name);
			$this->view->userSim = $simUser;

			if ($q == $session->user->user_name) {
				$session->price = ($simUser) ? $simUser['sim_rslt_price'] : '';
				$session->uangmuka = ($simUser) ? $simUser['sim_rslt_dp'] : '';
				$session->bulan = ($simUser) ? $simUser['sim_rslt_tenor'] : '';
				$session->hutang = ($simUser) ? $simUser['sim_rslt_debt'] : '';
				$session->angsuran = ($simUser) ? $simUser['sim_rslt_instlmnt'] : '';
				$session->totalbayarAwal = ($simUser) ? $simUser['sim_rslt_1st_pymnt_est'] : '';	
				$session->typeSim = ($simUser) ? $simUser['sim_rslt_type'] : '';
				
				$this->view->price = ($simUser) ? $simUser['sim_rslt_price'] : '';
				$this->view->percent = ($simUser) ? $simUser['sim_rslt_percent'] : '';
				$this->view->uangmuka = ($simUser) ? $simUser['sim_rslt_dp'] : '';
				$this->view->bulan = ($simUser) ? $simUser['sim_rslt_tenor'] : '';
				$this->view->hutang = ($simUser) ? $simUser['sim_rslt_debt'] : '';
				$this->view->angsuran = ($simUser) ? $simUser['sim_rslt_instlmnt'] : '';
				$this->view->totalbayarAwal = ($simUser) ? $simUser['sim_rslt_1st_pymnt_est'] : '';
			}
		}
		        
        if ($request->isPost() and $request->getPost('kirim')) {
//            var_dump($request->getParams()); die();

            $price = $request->getParam('price_hid');
            $persen = $request->getParam('percent_hid');
            $uangmuka = $request->getParam('nominal_hid');
            $bulan = $request->getParam('jangka-waktu');
            $tipeAngsuran = 'ADV';
            
            $data = $this->Process($price, $uangmuka, $bulan, $bunga, $tipeAngsuran);			
			
				if (isset($session->user->user_name)) {
					$simdata = array(
						'id'=>$simUser['id'],
						'sim_rslt_price'=>$price,
						'sim_rslt_dp'=>$uangmuka,
						'sim_rslt_debt'=>$data[0],
						'sim_rslt_instlmnt'=>$data[1],
						'sim_rslt_tenor'=>$bulan,
						'sim_rslt_1st_instlmnt'=>$data[1],
						'sim_rslt_1st_pymnt_est'=>$data[2],
						'sim_rslt_percent'=>$persen,
						'sim_rslt_type'=>'C'
					);
													
					$savedata = $service->save($simdata);
				}
			
			/*
			 * Set session hasil simulasi;
			*/
			
			$session->price = $price;
			$session->uangmuka = $uangmuka;
			$session->bulan = $bulan;
			$session->hutang = $data[0];
			$session->angsuran = $data[1];
			$session->totalbayarAwal = $data[2];
                        
            $this->view->price = $price;
            $this->view->percent = $persen;
            $this->view->uangmuka = $uangmuka;
            $this->view->bulan = $bulan;
            $this->view->hutang = $data[0];
            $this->view->angsuran = $data[1];
            $this->view->totalbayarAwal = $data[2];
        }
    }
    
    public function simulasiBudgetAction() {
        $request = $this->getRequest();
        $ctgr = $request->getParam('ctgr');
        $ctgr = 'simulasi-budget';
		
		$q = $request->getParam('user');
		$this->view->q = $q;		
        
		$bunga_hid = $request->getParam('percentbunga_budget_hid');
		
		$service2 = ApplicationRegistry::getService('Admin_Service_SimulasiService');
                $rate = $service2->getRate();
		$bunga = (empty($bunga_hid)) ? (float) $rate['simulasi_rate_bunga'] : $bunga_hid ;
		$this->view->rate = $bunga;
		
		$contentService = ApplicationRegistry::getService('Admin_Service_ContentService');
		$data = $contentService->getDataPersyaratanSimulasi('prsyrtn-simulasi');
		$this->view->persyaratan = $data;		

        $category = ApplicationRegistry::getService('MenuService')->getCategory($ctgr);
        
        if ($category['ctgr_parent'] && $category['ctgr_parent'] == 'simulasi') {
            $subcategory = ApplicationRegistry::getService('CategoryService')->getChildCategory($category['ctgr_parent']);            
            $this->view->subcategory = $subcategory;
        }
        
        $this->view->category = $category;
		
		$session = new Zend_Session_Namespace('WEB_AUTH');
		
		unset($session->price);
		unset($session->uangmuka);
		unset($session->bulan);
		unset($session->hutang);
		unset($session->angsuran);
		unset($session->totalbayarAwal);
		
		$service = ApplicationRegistry::getService('SimulasiService');
		
		if (isset($session->user->user_name)) {
			$simUser = $service->getUserSim($session->user->user_name);
			$this->view->userSim = $simUser;
			if ($q == $session->user->user_name) {
				$session->price = ($simUser) ? $simUser['sim_rslt_price'] : '';;
				$session->uangmuka = ($simUser) ? $simUser['sim_rslt_dp'] : '';
				$session->bulan = ($simUser) ? $simUser['sim_rslt_tenor'] : '';
				$session->hutang = ($simUser) ? $simUser['sim_rslt_debt'] : '';
				$session->angsuran = ($simUser) ? $simUser['sim_rslt_instlmnt'] : '';
				$session->totalbayarAwal = ($simUser) ? $simUser['sim_rslt_1st_pymnt_est'] : '';				
				
				$this->view->price = ($simUser) ? $simUser['sim_rslt_price'] : '';
				$this->view->percent = ($simUser) ? $simUser['sim_rslt_percent'] : '';
				$this->view->uangmuka = ($simUser) ? $simUser['sim_rslt_dp'] : '';
				$this->view->bulan = ($simUser) ? $simUser['sim_rslt_tenor'] : '';
				$this->view->hutang = ($simUser) ? $simUser['sim_rslt_debt'] : '';
				$this->view->angsuran = ($simUser) ? $simUser['sim_rslt_instlmnt'] : '';
				$this->view->totalbayarAwal = ($simUser) ? $simUser['sim_rslt_1st_pymnt_est'] : '';
			}	
		}	
        
        if ($request->isPost() and $request->getPost('kirim')) {
            
            $prior_budget = $request->getParam('prior-budget');
            $uangmuka = $request->getParam('dpbudget_hid');
            $angsuran = $request->getParam('angs_hid');
            $bulan = $request->getParam('jangka-waktu');
                      
            $tipeAngsuran = 'ADV';
            
            if (empty($uangmuka)) {
                $price = ( (double) $angsuran * (int) $bulan ) + ( ((double) $angsuran * ( $bunga / 100 )) * $bulan );
				$uangmuka = ( 0.20 * $price );
            } else if (!empty($uangmuka)) {
                $price = ( 5 * $uangmuka );
            }                       
            
            $data = $this->Process($price, $uangmuka, $bulan, $bunga, $tipeAngsuran);			
			
				if (isset($session->user->user_name)) {
					$simdata = array(
						'id'=>$simUser['id'],
						'sim_rslt_price'=>$price,
						'sim_rslt_dp'=>$uangmuka,
						'sim_rslt_debt'=>$data[0],
						'sim_rslt_instlmnt'=>$data[1],
						'sim_rslt_tenor'=>$bulan,
						'sim_rslt_1st_instlmnt'=>$data[1],
						'sim_rslt_1st_pymnt_est'=>$data[2],
						'sim_rslt_type'=>'B'
					);
													
					$savedata = $service->save($simdata);
				}	
			
			$session->price = $price;
			$session->uangmuka = (!empty($uangmuka)) ? $uangmuka  : $data[3];
			$session->bulan = $bulan;
			$session->hutang = $data[0];
			$session->angsuran = (!empty($angsuran)) ? $angsuran : $data[1];
			$session->totalbayarAwal = (!empty($angsuran)) ? ( $angsuran + $uangmuka ) : (double) $data[2];			
            
            $this->view->price = $price;
            $this->view->priority_budget = $prior_budget;
            $this->view->uangmuka = (!empty($uangmuka)) ? $uangmuka  : $data[3];
            $this->view->bulan = $bulan;
            $this->view->hutang = $data[0];
            $this->view->angsuran = (!empty($angsuran)) ? $angsuran : $data[1];
            $this->view->totalbayarAwal = (!empty($angsuran)) ? ( $angsuran + $uangmuka ) : (double) $data[2];
        }        
    }
    
    public function indexAction() {}
    
    public function viewAction() {}
    
    public function submenuAction() {}
    
    public function categoryAction() {}
    
}

?>
