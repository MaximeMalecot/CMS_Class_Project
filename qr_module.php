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
			$pageName   = strval(Tools::getValue('QR_MODULE_NAME'));
            $color      = strval(Tools::getValue('QR_MODULE_COLOR'));
            $size       = intval(Tools::getValue('size'));

			//Vérifie qu'il n'est pas vide
			if (!$pageName||empty($pageName))
			{
				//Si oui, affiche une erreur
				$output = $this->displayError($this->l('Valeur invalide'));
			} else {
				Configuration::updateValue('QR_MODULE_NAME', $pageName);
				Configuration::updateValue('QR_MODULE_COLOR', $color);
				Configuration::updateValue('QR_MODULE_DIMENSIONS', $size);
				//notif succes
				$output = $this->displayConfirmation($this->l('Valeurs mise à jour'));
			}
		}

		return $output.$this->displayForm();
	}

    public function displayForm() 
	{
		//Affichage du formulaire
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image_url = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=test&color=$color";

        $image = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail" width="200"></div>';
        $numberInput = '<div class="col-lg-6"><input type="number" name="NumberInput"></div>';

		$form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
				),
				'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Status'),
                        'name' => 'state',
                        'class' => 't',
                        'required'  => true,
                        'is_bool' => true, 
                        'values' => array(
                            array(
                                'id' => 'enabled',
                                'value' => 0,
                                'label' => $this->l('activé')
                            ),
                            array(
                                'id' => 'disabled',
                                'value' => 1,
                                'label' => $this->l('désactivé')
                            )
                        )
                    ),
					array(
						'type' => 'text',
						'label' => $this->l('Configuration value'),
						'name' => 'QR_MODULE_NAME',
						'size' => 20,
						'required' => true,
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color'),
                        'name' => 'QR_MODULE_COLOR'
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->l('Taille'),
                        'name' => 'Taille',
                        'required' => true,
                        'html_content' => 
                                        '<div>
                                            <input placeholder="Taille" type="number" value="'.$size.'" name="size">
                                        </div>'
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->l('Previsualisation'),
                        'name' => 'Previsualization',
                        'required' => true,
                        'html_content' => $image
                    ),
                    array(
                        'type' => 'hidden',
                        'label' => $this->l('Previsualization'),
                        'name' => 'image_url_maker',
                        'image' => $image ,
                        'readonly' => true,
                        'disabled' => true,
                    ),
                    
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'name' => 'btnSubmit'
				)
			),
		);

		$helper = new HelperForm();

        $helper->fields_value['QR_MODULE_NAME'] = strval(Configuration::get('QR_MODULE_NAME'));
        $helper->fields_value['QR_MODULE_COLOR'] = strval(Configuration::get('QR_MODULE_COLOR'));
        $helper->fields_value['height'] = intval(explode('x', Configuration::get('QR_MODULE_DIMENSIONS'))[0]);
        $helper->fields_value['width'] = intval(explode('x', Configuration::get('QR_MODULE_DIMENSIONS'))[1]);
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

    private function hexToRgb($hex, $alpha = false) {
        $hex      = str_replace('#', '', $hex);
        $length   = strlen($hex);
        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        if ( $alpha ) {
           $rgb['a'] = $alpha;
        }
        return implode('-', $rgb);
     }


}