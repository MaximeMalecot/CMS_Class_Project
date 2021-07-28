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
            !$this->registerHook('displayProductAdditionalInfo') || 
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
            $size       = intval(Tools::getValue('QR_MODULE_SIZE'));
            $size       = $size > 500 ? 500 : $size;
            $size       = $size < 10 ? 10 : $size;

            $color      = strval(Tools::getValue('QR_MODULE_COLOR'));
            $state      = intval(Tools::getValue('QR_MODULE_STATE'));

            $all_categories = $this->getCategories();
            $selected_categories = [];

            foreach ($all_categories as $chbx_options){
                if (Tools::getValue('QR_MODULE_DISPLAY_IN_'.(int)$chbx_options['id'])){
                    $selected_categories[] = $chbx_options['id'];
                }
            }

            if ( empty($color) || empty($size) )
			{
				//Si oui, affiche une erreur
				$output = $this->displayError($this->l('Valeur invalide'));
			} else {
                Configuration::updateValue('QR_MODULE_STATE', ($state == 1));
				Configuration::updateValue('QR_MODULE_COLOR', $color);
				Configuration::updateValue('QR_MODULE_DIMENSIONS', $size);

				//notif success
				$output = $this->displayConfirmation($this->l('Valeurs mise à jour'));
			}

            if( count($selected_categories) < 1){
                Configuration::updateValue('QR_MODULE_DISPLAY_IN', null);
            }else{
                Configuration::updateValue('QR_MODULE_DISPLAY_IN', serialize($selected_categories));
            }

		}

		return $output.$this->displayForm();
	}

    private function getCategories($checked = false){
        $id_lang=(int)Context::getContext()->language->id;
        $id_category = false; 
        $context = null;

        if($checked){
            $selected_categories = Configuration::get('QR_MODULE_DISPLAY_IN');
            if($selected_categories ) {
                $selected_categories = unserialize($selected_categories);
            }
        }
        $categories = [];
        $all_categories=Category::getCategories($id_lang, true, false);
        foreach($all_categories as $category){
            $cat = array(
                'id' => $category['id_category'], 
                'name' => $category['name'], 
                'val' => $category['id_category_default'],
                'checked' => false
            );

            if($checked && $selected_categories && in_array($category['id_category'], $selected_categories) ){
                $cat['checked'] = true;
            }

            $categories[] = $cat;
        }
        return $categories;
    }

    public function displayForm() 
	{
		//Affichage du formulaire
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image_url = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=test&color=$color";

        $image       = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail" width="200"></div>';
        $numberInput = '<div class="col-lg-6"><input type="number" name="NumberInput"></div>';

        $checkbox = '<div class="col-lg-6"><ul>';
        $categories = $this->getCategories(true);
        foreach($categories as $category){
            $checkbox .= '<li><input type="checkbox" name="QR_MODULE_DISPLAY_IN_'. $category['id'] .'" value="'.$category['id'].'"';
            $checkbox .= ( isset($category['checked']) && $category['checked']) ? 'checked' : '' ;
            $checkbox .= '/><label>'.$category['name'].'</label></li>';
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
                        'name' => 'QR_MODULE_STATE',
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
                        'name' => 'QR_MODULE_SIZE',
                        'required' => true,
                        'html_content' => 
                                        '<div>
                                            <input placeholder="Taille" type="number" value="'.$size.'" name="QR_MODULE_SIZE">
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
                        'label' => $this->l('Categories'),
                        'name' => 'QR_MODULE_DISPLAY_IN',
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

        $helper->fields_value['QR_MODULE_STATE']        = Configuration::get('QR_MODULE_STATE');
        $helper->fields_value['QR_MODULE_COLOR']        = strval(Configuration::get('QR_MODULE_COLOR'));

        $helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

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

    private function getQRLink($data = "empty"){
        $color = $this->hexToRgb(strval(Configuration::get('QR_MODULE_COLOR')));
        $size = intval(Configuration::get('QR_MODULE_DIMENSIONS'));
        $image = "https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=$data&color=$color";
        return $image;
    }


    public function hookDisplayProductAdditionalInfo($params) //Quick resume of product
	{
        if(! Configuration::get('QR_MODULE_STATE') ) return;
        $page_name = Dispatcher::getInstance()->getController(); // page_name var doesn't work without without this
        if ($page_name != 'product') return;
        
        $product = new Product((int)Tools::getValue('id_product'));
        $link    = new Link();
        $url     = $link->getProductLink($product);

        $category = $product->id_category_default;
        $selected_categories = Configuration::get('QR_MODULE_DISPLAY_IN');
        if(!$selected_categories) return;
        $selected_categories = unserialize($selected_categories);
        if( !in_array($category, $selected_categories) ) return;
        $image = $this->getQRLink($url);

		$this->context->smarty->assign([
			'image' => $image
		]);

		return $this->display(__FILE__, 'qr_module.tpl');
	}

}