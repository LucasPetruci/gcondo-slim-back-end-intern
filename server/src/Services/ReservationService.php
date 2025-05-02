<?php

namespace App\Services;

use App\Models\Reservation;
use App\Http\Error\HttpNotFoundException;
use App\Http\Error\HttpUnprocessableEntityException;
use App\Services\UnitService;

class ReservationService
{
    public function __construct(protected UnitService $unitService, protected LocationService $locationService) {}

    public function list()
    {
        $reservations = Reservation::with(['unit', 'location'])->get();

        return $reservations;
    }

    public function find(int $id): Reservation
    {
        $reservation = Reservation::with(['unit', 'location'])->find($id);

        if (!$reservation) {
            throw new HttpNotFoundException('Reservation not found');
        }

        return $reservation;
    }

    public function create(array $data): Reservation
    {
        $this->validateReservationData($data);

        $this->validateUnit($data['unit_id']);

        $this->checkDateConflict($data['unit_id'], $data['date']);

        if (isset($data['location_id'])) {
            $this->validateLocation($data['location_id']);
            $this->validateLocationConsistency($data['unit_id'], $data['location_id']);
            $this->validateLocationCapacity($data['location_id'], $data['people_quantity']);
        }

        $reservation = Reservation::create([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date'],
            'location_id' => $data['location_id'] ?? null
        ]);

        return $reservation;
    }

    public function update(int $id, array $data): Reservation
    {
        $reservation = $this->find($id);

        $this->validateReservationData($data);
        $this->validateUnit($data['unit_id']);

        $this->checkDateConflict($data['unit_id'], $data['date']);

        if (isset($data['location_id'])) {
            $this->validateLocation($data['location_id']);
            $this->validateLocationConsistency($data['unit_id'], $data['location_id']);
            $this->validateLocationCapacity($data['location_id'], $data['people_quantity']);
        }


        $reservation-> fill([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date'],
            'location_id' => $data['location_id'] ?? null
        ]);

        $reservation->save();
        
        return $reservation;
    }

    public function delete(int $id): bool
    {
        $reservation = $this->find($id);
        return $reservation->delete();
    }

    /** @throws HttpNotFoundException */
    private function validateUnit(int $unitId): void
    {
        $this->unitService->find($unitId);
    }

    /** @throws HttpNotFoundException */
    private function validateLocation(int $locationId): void
    {
        $this->locationService->find($locationId);
    }

    /** @throws HttpUnprocessableEntityException */
    private function validateLocationConsistency(int $unitId, int $locationId): void
    {
        $unit = $this->unitService->find($unitId);
        $location = $this->locationService->find($locationId);

        if ($location->condominium_id !== $unit->condominium_id) {
            throw new HttpUnprocessableEntityException(
                'Location and unit must be in the same condominium'
            );
        }
    }

    private function validateLocationCapacity(int $locationId, int $peopleQuantity): void
    {
        $location = $this->locationService->find($locationId);
            
            if ($peopleQuantity > $location->max_people) {
                throw new HttpUnprocessableEntityException(
                    'People quantity exceeds location capacity'
                );
            }
    }

    private function checkDateConflict(int $unitId, string $date): void
    {
        $conflictingReservation = Reservation::where('unit_id', $unitId)
            ->where('date', $date)
            ->first();

        if ($conflictingReservation) {
            throw new HttpUnprocessableEntityException(
                'A reservation already exists for this unit on the specified date'
            );
        }
    }

    private function validateReservationData(array $data): void
    {
        if (empty($data['name'])) {
            throw new HttpUnprocessableEntityException('Name is required');
        }

        if (empty($data['unit_id'])) {
            throw new HttpUnprocessableEntityException('Unit ID is required');
        }

        if (isset($data['location_id']) && !is_numeric($data['location_id'])) {
            throw new HttpUnprocessableEntityException('Location ID must be a number when provided');
        }

        if (!isset($data['people_quantity']) || is_null($data['people_quantity']) ||
        !is_numeric($data['people_quantity']) || $data['people_quantity'] <= 0 ||
        $data['people_quantity'] != (int)$data['people_quantity']) {
            throw new HttpUnprocessableEntityException('People quantity must be a non-negative integer');
        }


        if (empty($data['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            throw new HttpUnprocessableEntityException('Date must be in YYYY-MM-DD format');
        }
        
    }
}
