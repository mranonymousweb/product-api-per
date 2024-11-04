<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class productapi extends Module
{
    public function __construct()
    {
        $this->name = 'myproductapi';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Product API');
        $this->description = $this->l('Provides an API for products in JSON format.');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
}
