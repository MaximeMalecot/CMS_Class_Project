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
        if (!Configuration::updateValue('QR_MODULE_COLOR', '0-0-0') || 
            !Configuration::updateValue('QR_MODULE_DIMENSIONS', '100') ){
            return false;
        }
        return true;
    }

    private function removeConfigurationValues(){
        if (!Configuration::deleteByName('QR_MODULE_COLOR') || 
            !Configuration::deleteByName('QR_MODULE_DIMENSIONS') ){
            return false;
        }
        return true;
    }

    public function install(){
        if( !parent::install() || 
            !$this->registerHook('displayLeftColumnProduct') || 
            !$this->registerHook('displayProductAdditionalInfo') || 
            !$this->registerHook('leftColumn') || 
            !$this->registerHook('header') || 
            !$this->setConfigurationValues() ){
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
            $color      = strval(Tools::getValue('QR_MODULE_COLOR'));
            $size       = intval(Tools::getValue('size'));
            $state      = intval(Tools::getValue('state'));

            //Vérifie qu'il n'est pas vide
			if ( empty($color) || empty($size) )
			{
				//Si oui, affiche une erreur
				$output = $this->displayError($this->l('Valeur invalide'));
			} else {
                Configuration::updateValue('QR_MODULE_STATE', ($state == 1));
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

        $image       = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail" width="200"></div>';
        $numberInput = '<div class="col-lg-6"><input type="number" name="NumberInput"></div>';

        $id_lang=(int)Context::getContext()->language->id;
        $start=0;
        $limit=100;
        $order_by='id_product';
        $order_way='DESC';
        $id_category = false; 
        $only_active =true;
        $context = null;

        $all_products=Product::getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category,
                $only_active ,  $context);

        foreach($all_products as $product){
            $categories_id[] = $product['id_category_default'];
        }

        $categories_id = array_unique($categories_id);

        $checkbox = '<div class="col-lg-6"><ul>';

        foreach($categories_id as $category){
            $checkbox .= '<li><input type="checkbox" value="'.$category.'" /><label>'.$this->getCategoryName($category).'</label></li>';
        }

        $checkbox .= '</ul></div>';

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
                                'id' => 'disabled',
                                'value' => 0,
                                'label' => $this->l('désactivé')
                            ),
                            array(
                                'id' => 'enabled',
                                'value' => 1,
                                'label' => $this->l('activé'),
                                'checked' => true,
                            ),
                        )
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
                        'type' => 'html',
                        'lang' => true,
                        'label' => $this->l('Category'),
                        'name' => 'Categories',
                        'html_content' => $checkbox
                    )
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'name' => 'btnSubmit'
				)
			),
		);

		$helper = new HelperForm();

        $helper->fields_value['state']           = Configuration::get('QR_MODULE_STATE');
        $helper->fields_value['QR_MODULE_COLOR'] = strval(Configuration::get('QR_MODULE_COLOR'));

        $helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        //Langue
		//$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
		//$helper->default_form_language = $defaultLang;

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

     public static function getCategoryName($id){

        $category = new Category($id,Context::getContext()->language->id);
        
        return $category->name;
        
    }

    public function hookDisplayLeftColumn($params) 
	{
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=test&color=$color";

		$this->context->smarty->assign([
			'image' => $image
		]);

		return $this->display(__FILE__, 'qr_module.tpl');
	}

    public function hookDisplayLeftColumnProduct($params) 
	{
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=test&color=$color";

		$this->context->smarty->assign([
			'image' => $image
		]);

		return $this->display(__FILE__, 'qr_module.tpl');
	}

    public function hookDisplayProductAdditionalInfo($params) 
	{
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=test&color=$color";

		$this->context->smarty->assign([
			'image' => $image
		]);

		return $this->display(__FILE__, 'qr_module.tpl');
	}

	public function hookDisplayHeader() 
	{
		$this->context->controller->registerStylesheet(
			'qr_module',
			$this->_path.'views/css/qr_module.css',
			['server' => 'remote', 'position' => 'head', 'priority' => 150]
		);
	}

}