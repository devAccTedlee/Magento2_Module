<?php
namespace Nouvolution\AddCartSuccessPopup\Controller\Ajax;
/*
error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
*/

class Changeqty extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var \Magento\Checkout\Model\Session
	 */
    protected $session;
	
	/**
	 * @var \Magento\Checkout\Model\Cart
	 */
    protected $cart;

	/**
	 * Constructor
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Checkout\Model\Cart $cart
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Checkout\Model\Cart $cart
	) {
		parent::__construct($context);
		$this->cart = $cart;
	}

	public function execute()
	{
        $result = new N41ApiResult();
        $result->Response = false;

		$itemId= $this->_request->getParam('item');
		$qty= $this->_request->getParam('qty');

        if ($itemId and $qty > 0)
        {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('JIJ ::: message11');
            $params[$itemId]['qty'] = $qty;
            try{
                $this->cart->updateItems($params);
                #$this->cart->saveQuote();
                $this->cart->save();

                $result->Response = true;

            }catch (\Magento\Framework\Exception\LocalizedException $e) {
                $result->Message = $e->getMessage();
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('ERROR :: ChangeQty : '.$e->getMessage());
            }catch(Exception $e){
                $result->Message = $e->getMessage();
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('ERROR :: ChangeQty : '.$e->getMessage());
            }

            $this->getResponse()->representJson(
                json_encode($result)
            );
        }
	}
}

class N41ApiResult{
    public $Response;
    public $Error;
    public $Message;
}

?>