<?php
namespace Nouvolution\AddCartSuccessPopup\Block\Cart\Add;

class Popup extends \Magento\Framework\View\Element\Template
{
    public $helper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Nouvolution\MainPage\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $data);
    }

    public function test(){
        return "TEST BLOCK!!!#!##- JIN";
    }

}
