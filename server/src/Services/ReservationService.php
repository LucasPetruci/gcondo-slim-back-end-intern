<?php

namespace App\Services;

use App\Models\Reservation;
use App\Http\Error\HttpNotFoundException;
use App\Http\Error\HttpUnprocessableEntityException;
use App\Services\UnitService;

class ReservationService
{
    public function __construct(protected UnitService $unitService) {}

    public function list()
    {
        $reservations = Reservation::with('unit')->get();

        return $reservations;
    }

    public function find(int $id): Reservation
    {
        $reservation = Reservation::with('unit')->find($id);

        if (!$reservation) {
            throw new HttpNotFoundException('Reservation not found');
        }

        return $reservation;
    }

    public function create(array $data): Reservation
    {
        $this->validateReservationData($data);

        $this->validateUnit($data['unit_id']);

        $reservation = Reservation::create([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date']
        ]);

        return $reservation;
    }

    public function update(int $id, array $data): Reservation
    {
        $reservation = $this->find($id);

        $this->validateReservationData($data);
        $this->validateUnit($data['unit_id']);

        $reservation-> fill([
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'people_quantity' => $data['people_quantity'],
            'date' => $data['date']
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


    private function validateReservationData(array $data): void
    {
        if (empty($data['name'])) {
            throw new HttpUnprocessableEntityException('Name is required');
        }

        if (empty($data['unit_id'])) {
            throw new HttpUnprocessableEntityException('Unit ID is required');
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
