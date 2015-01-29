<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\UrlRewrite\Controller;

use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * UrlRewrite Controller Router
 */
class Router implements \Magento\Framework\App\RouterInterface
{
    /** var \Magento\Framework\App\ActionFactory */
    protected $actionFactory;

    /** @var \Magento\Framework\UrlInterface */
    protected $url;

    /** @var \Magento\Framework\Store\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\App\ResponseInterface */
    protected $response;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\Store\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param UrlFinderInterface $urlFinder
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Store\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResponseInterface $response,
        UrlFinderInterface $urlFinder
    ) {
        $this->actionFactory = $actionFactory;
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->response = $response;
        $this->urlFinder = $urlFinder;
    }

    /**
     * Match corresponding URL Rewrite and modify request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        if ($fromStore = $request->getParam('___from_store')) {
            $oldStoreId = $this->storeManager->getStore($fromStore)->getId();
            $oldRewrite = $this->getRewrite($request->getPathInfo(), $oldStoreId);
            if ($oldRewrite) {
                $rewrite = $this->urlFinder->findOneByData(
                    [
                        UrlRewrite::ENTITY_TYPE => $oldRewrite->getEntityType(),
                        UrlRewrite::ENTITY_ID => $oldRewrite->getEntityId(),
                        UrlRewrite::STORE_ID => $this->storeManager->getStore()->getId(),
                        UrlRewrite::IS_AUTOGENERATED => 1,
                    ]
                );
                if ($rewrite && $rewrite->getRequestPath() !== $oldRewrite->getRequestPath()) {
                    return $this->redirect($request, $rewrite->getRequestPath(), OptionProvider::TEMPORARY);
                }
            }
        }
        $rewrite = $this->getRewrite($request->getPathInfo(), $this->storeManager->getStore()->getId());
        if ($rewrite === null) {
            return null;
        }

        if ($rewrite->getRedirectType()) {
            return $this->processRedirect($request, $rewrite);
        }

        $request->setPathInfo('/' . $rewrite->getTargetPath());
        return $this->actionFactory->create('Magento\Framework\App\Action\Forward', ['request' => $request]);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param UrlRewrite $rewrite
     * @return \Magento\Framework\App\ActionInterface|null
     */
    protected function processRedirect($request, $rewrite)
    {
        $target = $rewrite->getTargetPath();
        if ($rewrite->getEntityType() !== Rewrite::ENTITY_TYPE_CUSTOM
            || ($prefix = substr($target, 0, 6)) !== 'http:/' && $prefix !== 'https:'
        ) {
            $target = $this->url->getUrl('', ['_direct' => $target]);
        }
        return $this->redirect($request, $target, $rewrite->getRedirectType());
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param string $url
     * @param int $code
     * @return \Magento\Framework\App\ActionInterface
     */
    protected function redirect($request, $url, $code)
    {
        $this->response->setRedirect($url, $code);
        $request->setDispatched(true);
        return $this->actionFactory->create('Magento\Framework\App\Action\Redirect', ['request' => $request]);
    }

    /**
     * @param string $requestPath
     * @param int $storeId
     * @return UrlRewrite|null
     */
    protected function getRewrite($requestPath, $storeId)
    {
        return $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => trim($requestPath, '/'),
            UrlRewrite::STORE_ID => $storeId,
        ]);
    }
}
