<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlCache\Controller\GraphQl;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQlCache\Model\CacheInfo;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\ResultInterface;
use Magento\PageCache\Model\Config;
use Magento\GraphQl\Controller\HttpRequestProcessor;

/**
 * Class Plugin
 */
class Plugin
{
    /**
     * @var CacheInfo
     */
    private $cacheInfo;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * @var HttpRequestProcessor
     */
    private $requestProcessor;

    /**
     * @param CacheInfo $cacheInfo
     * @param Config $config
     * @param HttpResponse $response
     * @param HttpRequestProcessor $requestProcessor
     */
    public function __construct(
        CacheInfo $cacheInfo,
        Config $config,
        HttpResponse $response,
        HttpRequestProcessor $requestProcessor
    ) {
        $this->cacheInfo = $cacheInfo;
        $this->config = $config;
        $this->response = $response;
        $this->requestProcessor = $requestProcessor;
    }

    /**
     * Process graphql headers
     *
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        /** @var \Magento\Framework\App\Request\Http $request */
        $this->requestProcessor->processHeaders($request);
    }

    /**
     * Plugin for GraphQL after dispatch to set tag and cache headers
     *
     * The $response doesn't have a set type because it's alternating between ResponseInterface and ResultInterface
     * depending if it comes from builtin cache or the dispatch.
     *
     * @param FrontControllerInterface $subject
     * @param ResponseInterface | ResultInterface $response
     * @param RequestInterface $request
     * @return ResponseInterface | ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDispatch(
        FrontControllerInterface $subject,
        $response,
        RequestInterface $request
    ) {
        $cacheTags = $this->cacheInfo->getCacheTags();
        $isCacheValid = $this->cacheInfo->isCacheable();
        if (!empty($cacheTags)
            && $isCacheValid
            && $this->config->isEnabled()
        ) {
            $this->response->setPublicHeaders($this->config->getTtl());
            $this->response->setHeader('X-Magento-Tags', implode(',', $cacheTags), true);
        }

        return $response;
    }
}
