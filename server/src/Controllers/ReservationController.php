<?php

namespace App\Controllers;

use App\Http\HttpStatus;
use App\Http\Response\ResponseBuilder;
use App\Services\ReservationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservationController
{
    public function __construct(private ReservationService $service) {}

    public function list(Request $request, Response $response): Response
    {
        $data = ['reservations' => $this->service->list()];

        return ResponseBuilder::respondWithData($response, data: $data);
    }

    public function find(Request $request, Response $response, array $args): Response
    {
        $data = ['reservation' => $this->service->find((int) $args['id'])];

        return ResponseBuilder::respondWithData($response, data: $data);
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $data = ['reservation' => $this->service->create($body)];

        return ResponseBuilder::respondWithData($response, HttpStatus::Created, $data);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        $data = ['reservation' => $this->service->update((int) $args['id'], $body)];

        return ResponseBuilder::respondWithData($response, data: $data);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $deleted = $this->service->delete((int) $args['id']);

        $status = $deleted ? HttpStatus::OK : HttpStatus::ServerError;

        return ResponseBuilder::respondWithData($response, $status);
    }
}
