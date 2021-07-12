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

    public function install(){
        if( !parent::install() || !$this->registerHook('leftColumn') || !$this->registerHook('header') || !Configuration::updateValue('QR_MODULE_NAME', 'TEST') ){
            return false;
        }else{
            return true;
        }
    }

    public function uninstall(){
        return parent::uninstall();
    }
}