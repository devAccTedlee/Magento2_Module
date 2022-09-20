<?php
namespace Nouvolution\AddCartSuccessPopup\Controller\Ajax;

class Minicart extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */

    protected $jsonData;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonData
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonData = $jsonData;
        parent::__construct($context);
    }

    public function execute()
    { // http://192.168.0.80/currentair/addcartpopup/ajax/minicart

        $resultRedirect = $this->resultPageFactory->create();
        /*
        $blockInstance = $resultRedirect->getLayout()->getBlock('minicart');
        $message['html'] = $blockInstance->toHtml();
        */

        /* -- display minicart block for quickview.
        $minicartHtml = $resultRedirect->getLayout()
            ->createBlock('Nouvolution\AddCartSuccessPopup\Block\Cart\Add\Popup')
            ->setTemplate('Nouvolution_AddCartSuccessPopup::cart/minicart.phtml')
            ->toHtml();

        $message['html'] = $minicartHtml;
        */

        ////// display only success message and checkout btn for quickview.
        $_n41 = \Magento\Framework\App\ObjectManager::getInstance()->create('Nouvolution\N41\Helper\Data');
        $mobileAddCartSuccessFooterBlock = <<<HTML
    <div class="addcart-success-footer-wrapper">
        <a id="minicart-cart-btn" class="button" target="_top" href="{$_n41->_common->GetBaseUrl()}checkout/cart">View Shopping Bag</a>
        <a id="minicart-checkout-btn" class="button" target="_top" href="{$_n41->_common->GetBaseUrl()}checkout">Proceed to Checkout</a>    
    </div>
HTML;
        $message['html'] = $mobileAddCartSuccessFooterBlock;
        ////// display only success message and checkout btn for quickview.


        /** Json Responce */
        $this->getResponse()->representJson(
            $this->jsonData->jsonEncode($message)
        );
    }
}