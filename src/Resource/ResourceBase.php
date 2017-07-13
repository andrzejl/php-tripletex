<?php

namespace zaporylie\Tripletex\Resource;

use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\RequestInterface;
use zaporylie\Tripletex\Tripletex;

abstract class ResourceBase implements ResourceInterface
{
    /**
     * @var \zaporylie\Tripletex\Tripletex
     */
    protected $app;

    /**
     * SessionCreate constructor.
     *
     * @param \zaporylie\Tripletex\Tripletex $app
     */
    public function __construct(Tripletex $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        if (!isset($this->method)) {
            throw new \LogicException('Missing http method');
        }

        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if (!isset($this->path)) {
            throw new \LogicException('Missing request path');
        }

        return $this->app->getClient()->getBaseUrl() . $this->path;
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @todo: Handle errors.
     */
    public function doRequest(RequestInterface $request)
    {
        $trusted_request = $request->withAddedHeader(
            'Authorization',
            'Basic '.base64_encode($this->app->getClient()->getCompanyId().':'.$this->app->getClient()->getSessionToken())
        );
        $trusted_request = $trusted_request->withAddedHeader(
            'Accept',
            'application/json'
        );
        $response = $this->app->getClient()->getHttpClient()->sendRequest($trusted_request);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            // Get response.
            $body = $response->getBody()->getContents();

            /** @var \zaporylie\Tripletex\Exception\ApiException $responseObject */
            // Deserialize response.
            $exception = $this->app->getSerializer()->deserialize(
                $body,
                'zaporylie\Tripletex\Exception\ApiException',
                'json'
            );

            throw $exception;
        }

        return $response;
    }
}
