<?php

namespace App\Services;

use App\Http\Error\HttpNotFoundException;
use App\Http\Error\HttpBadRequestException;
use App\Http\Error\HttpUnprocessableEntityException;
use App\Models\Location;
use App\Services\CondominiumService;

class LocationService
{
    public function __construct(protected CondominiumService $condominiumService) {}

    public function list()
    {
        return Location::with('condominium')->get();
    }

    public function listByCondominium(int $condominiumId)
    {
        $this->validateCondominium($condominiumId);

        return Location::where('condominium_id', $condominiumId)
            ->with('condominium')
            ->get();
    }

    public function find(int $id): Location
    {
        $location = Location::with('condominium')->find($id);

        if (!$location) {
            throw new HttpNotFoundException('Location not found');
        }

        return $location;
    }

    public function create(array $data): Location
    {
        $this->validateLocationData($data);

        $this->validateCondominium($data['condominium_id']);

        $location = Location::create([
            'condominium_id' => $data['condominium_id'],
            'name' => $data['name'],
            'max_people' => $data['max_people'] ?? null,
            'square_meters' => $data['square_meters'] ?? null
        ]);

        return $location;
    }

    public function update(int $id, array $data): Location
    {
        $location = $this->find($id);

        $this->validateLocationData($data);
        $this->validateCondominium($data['condominium_id']);

        $location->fill([
            'name' => $data['name'],
            'max_people' => $data['max_people'] ?? null,
            'square_meters' => $data['square_meters'] ?? null,
            'condominium_id' => $data['condominium_id']
        ]);

        $location->save();

        return $location;
    }

    public function delete(int $id): bool
    {
        $location = $this->find($id);
        
        // check if there are any reservations associated with the location
        if ($location->reservations()->count() > 0) {
            throw new HttpBadRequestException('Cannot delete location with reservations');
        }
    
        return $location->delete();
    }

    /** @throws HttpNotFoundException */
    private function validateCondominium(int $condominiumId): void
    {
        $this->condominiumService->find($condominiumId);
    }

    /** @throws HttpUnprocessableEntityException */
    private function validateLocationData(array $data)
    {
        if (empty($data['condominium_id'])) {
            throw new HttpUnprocessableEntityException('Condominium ID is required');
        }

        if (empty($data['name'])) {
            throw new HttpUnprocessableEntityException('Name is required');
        }

        // Validar square_meters se fornecido
        if (isset($data['square_meters']) && !is_null($data['square_meters'])) {
            if (!is_numeric($data['square_meters']) || $data['square_meters'] <= 0) {
                throw new HttpUnprocessableEntityException('Square meters must be a positive number');
            }
        }

        // Validar max_people se fornecido
        if (isset($data['max_people']) && !is_null($data['max_people'])) {
            if (!is_numeric($data['max_people']) || $data['max_people'] <= 0) {
                throw new HttpUnprocessableEntityException('Max people must be a positive number');
            }
        }
    }
}
