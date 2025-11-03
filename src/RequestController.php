<?php

namespace HungryBus\ApiConnector;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class RequestController
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

    private array|string $params = [];
    private array $headers = [];
    private string $method;
    private string $url;
    protected string $endpoint;
    private string $body = '';
    private bool $asForm = false;

    public function __construct()
    {
        //
    }

    public function setApiEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    abstract protected function authenticate(): static;

    protected function asForm(): static
    {
        $this->asForm = true;

        return $this;
    }

    protected function execute(): Response|JsonResponse
    {
        $endpoint = $this->url . $this->endpoint;

        if ($this->asForm) {
            $res = Http::asForm()
                ->withHeaders($this->headers)
                ->{$this->method}($endpoint, json_decode($this->body, true));
        } else {
            $res = Http::withHeaders($this->headers)
                ->withBody($this->body)
                ->{$this->method}($endpoint, $this->params);
        }

        if ($res->status() == 500) {
            throw new \Exception('Server Error');
        }

        if ($res->failed() && $res->status() !== 404) {
            throw new \Exception(json_decode($res->body())?->error ?? 'Connection Error');
        }

        if ($res->status() == 404) {
            throw new NotFoundHttpException();
        }

        if ($res->status() == 429) {
            throw new \Exception('Too Many Requests');
        }

        if ($res->status() == 401) {
            $this->authenticate();
        }

        return $res;
    }

    protected function executeDebug(): Response|JsonResponse
    {
        $endpoint = $this->url . $this->endpoint;

        return Http::withHeaders($this->headers)->dd($this->headers)
            ->{$this->method}($endpoint, $this->params);
    }

    protected function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    protected function setParams(array|string $params = []): static
    {
        $this->params = $params;

        return $this;
    }

    protected function setHeaders(array $headers = []): static
    {
        $this->headers = $headers;

        return $this;
    }

    protected function setMethod(string $method = 'GET'): static
    {
        if (!in_array(strtoupper($method), self::ALLOWED_METHODS)) {
            throw new \Exception('Invalid method selected for API call');
        }

        $this->method = strtolower($method);

        return $this;
    }

    protected function setBody(string|array $body): static
    {
        $this->body = is_array($body) ? json_encode($body) : $body;

        return $this;
    }

    protected function post(): static
    {
        $this->method = 'post';

        return $this;
    }

    protected function get(): static
    {
        $this->method = 'get';

        return $this;
    }

    protected function put(): static
    {
        $this->method = 'put';

        return $this;
    }

    protected function patch(): static
    {
        $this->method = 'patch';

        return $this;
    }
}