<?php

namespace App\Repositories;

use App\Constant\Status;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationRepository
{
    public function addDataRepository(array $data)
    {
        try {
            $reservation = Reservation::create([
                'user_id' => $data['user_id'],
                'reserved_at' => $data['reserved_at'],
                'note' => $data['note'] ?? '',
                'status' => $data['status'] ?? Status::PENDING,
            ]);
            $reservation->tables()->attach(
                collect($data['tables'])->pluck('id')->toArray()
            );
        } catch (\Exception $e) {
            \Log::error("AddDataRepository error: " . $e->getMessage());
            throw new \Exception("AddDataRepository error: " . $e->getMessage());
        }
    }
    public function getDataReservation($page, $pageSize)
    {
        try {
            $offset = ($page - 1) * $pageSize;
            $reservations = Reservation::with(['user', 'tables'])
                ->orderByDesc('created_at')
                ->offset($offset)
                ->limit($pageSize)
                ->get();

            $total = Reservation::count();
            return [
                'data' => $reservations,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        catch (\Exception $e) {
            \Log::error("GetDataReservation error: " . $e->getMessage());
            throw new \Exception("GetDataReservation error: " . $e->getMessage());
        }
    }
}
