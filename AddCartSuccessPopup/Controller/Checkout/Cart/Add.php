<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Nouvolution\AddCartSuccessPopup\Controller\Checkout\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    protected $_messege = '';   // jin
    protected $_result = [];    // jin

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    if ($this->shouldRedirectToCart()) {
                        $message = __(
                            'You added %1 to your shopping cart.',
                            $product->getName()
                        );
                        $this->_messege = $message; // jin
                        $this->messageManager->addSuccessMessage($message);
                    } else {
                        $this->messageManager->addComplexSuccessMessage(
                            'addCartSuccessMessage',
                            [
                                'product_name' => $product->getName(),
                                'cart_url' => $this->getCartUrl(),
                            ]
                        );
                    }
                }

                /*
                $n41 = \Magento\Framework\App\ObjectManager::getInstance()->create('Nouvolution\N41\Helper\Data');
                $n41->setLog(json_encode($related), "n41-add-cart");
                $n41->setLog($product->getData(), "n41-add-cart");
                $n41->setLog($this->getRequest(), "n41-add-cart");
                $n41->setLog($this->getResponse(), "n41-add-cart");
                */

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                //$resultPage = $objectManager->create('Magento\Framework\View\Result\PageFactory');
                $resultPage = $objectManager->create('Magento\Framework\View\Result\PageFactory')->create();

                //$resultPage = $this->_resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->prepend(__(' heading '));

                $message = __('<h5><a href="'. $product->getProductUrl() .'">%1</a></h5> <h4>WAS ADDED TO YOUR SHOPPING BAG</h4>',
                    $product->getName()
                );

                $_n41 = \Magento\Framework\App\ObjectManager::getInstance()->create('Nouvolution\N41\Helper\Data');

                $mobileAddCartSuccessFooterBlock = <<<HTML
    <div class="addcart-success-footer-wrapper">
        <a id="minicart-cart-btn" class="button" target="_top" href="{$_n41->_common->GetBaseUrl()}checkout/cart">View Shopping Bag</a>
        <a id="minicart-checkout-btn" class="button" target="_top" href="{$_n41->_common->GetBaseUrl()}checkout">Proceed to Checkout</a>
    </div>
HTML;

                // $minicartHtml = $_n41->_common->IsMobile() ? $mobileAddCartSuccessFooterBlock : $resultPage->getLayout()
                //     ->createBlock('Nouvolution\AddCartSuccessPopup\Block\Cart\Add\Popup')
                //     ->setTemplate('Nouvolution_AddCartSuccessPopup::cart/minicart.phtml')
                //     ->toHtml();
                
                $minicartHtml = $mobileAddCartSuccessFooterBlock; // jin - show only cart, checkout btn.
                $this->_result['html'] = $message . $minicartHtml; // jin
                //$this->_result['html'] = $this->_getHtmlResponeAjaxCart($product); // jin

                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {

            $refererUrl = $this->getRequest()->getServer('HTTP_REFERER');
            if (!strpos($refererUrl, 'weltpixel_quickview/catalog_product/view')) {
                /** Fix for product redirects, ex. when quantity is out of stock */
                /* If use this return type, warning works on product detail page. but no warning on QuickView. */
                $this->_result['html'] = "<h5>{$e->getMessage()}</h5>";
                return $this->goBack(null);
            }

            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
            
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        //$result = []; // org
        $result = $this->_result; // jin

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
    }

    /**
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    protected function _getHtmlResponeAjaxCart($product) // JIN
    {
        $message = __('<h5><a href="'. $product->getProductUrl() .'">%1</a></h5> <h4>WAS ADDED TO YOUR SHOPPING BAG</h4>',
            $product->getName()
        );
        $html = '<div class="added-item-wrapper">'.$message.'<br>
					<div class="action_button">
						<ul>
							<li>
								<button title="'. __('Continue Shopping') . '" class="button btn-continue" onclick="jQuery.fancybox.close();">'. __('Continue Shopping') . '</button>
							</li>
							<li>
								<a title="Checkout" class="button btn-viewcart" href="'. $this->_url->getUrl('checkout/cart') .'"><span>'. __('View cart &amp; checkout'). '</span></a>
							</li>
						</ul>
					</div>
				</div>';
        return $html;
    }
}
