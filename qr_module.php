<?php
if(!defined('_PS_VERSION_')){
    exit;
}

class Qr_Module extends Module{
    public function __construct()
    {
        $this->name = 'qr_module';
        $this->tab = 'front_office_features';
        $this->version = '0.1';
        $this->author = 'GRP_3';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => 1.6,
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('QR code module');
        $this->description = $this->l('Dislpay a QR Code image for your products');

        $this->confirmUninstall = $this->l('Do you really want to uninstall this module?');

        if(!Configuration::get('QR_MODULE_NAME')){
            $this->warning = $this->l('No name given');
        }
    }

    private function setConfigurationValues(){
        if (!Configuration::updateValue('QR_MODULE_NAME', 'TEST') || !Configuration::updateValue('QR_MODULE_COLOR', '0-0-0') ){
            return false;
        }
        return true;
    }

    private function removeConfigurationValues(){
        if (!Configuration::deleteByName('QR_MODULE_NAME') || !Configuration::deleteByName('QR_MODULE_COLOR') ){
            return false;
        }
        return true;
    }

    public function install(){
        if( !parent::install() || !$this->registerHook('leftColumn') || !$this->registerHook('header') || !$this->setConfigurationValues() ){
            return false;
        }else{
            return true;
        }
    }

    public function uninstall(){
        if( !parent::uninstall() || !$this->removeConfigurationValues() ){
            return false;
        }else{
            return true;
        }
    }

    public function getContent()
	{
		//gestion des données du formulaire

		$output = null;

		//Vérifier si le formulaire a été envoyé
		if (Tools::isSubmit('btnSubmit')) {
			//récupere la valeur du champ txt
			$pageName = strval(Tools::getValue('QR_MODULE_NAME'));

			//Vérifie qu'il n'est pas vide
			if (!$pageName||empty($pageName))
			{
				//Si oui, affiche une erreur
				$output .= $this->displayError($this->l('Valeur invalide'));
			} else {
				Configuration::updateValue('QR_MODULE_NAME', $pageName);
				//notif succes
				$output .= $this->displayConfirmation($this->l('Valeur mise à jour'));
			}
		}

		return $output.$this->displayForm();
	}

    public function displayForm() 
	{
		//Affichage du formulaire

		$form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Configuration value'),
						'name' => 'QR_MODULE_NAME',
						'size' => 20,
						'required' => true
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color'),
                        'name' => 'TK_THEME_BG_COLOR'
                        )						
                    
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'name' => 'btnSubmit'
				)
			),
		);

		$helper = new HelperForm();

		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		//Langue
		//$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
		//$helper->default_form_language = $defaultLang;

		//charge la valeur de AG_MODULE_NAME
		$helper->fields_value['AG_MODULE_NAME'] = Configuration::get('AG_MODULE_NAME');

		return $helper->generateForm(array($form));
	}


}