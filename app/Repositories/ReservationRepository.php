<?php

namespace App\Repositories;

use App\Constant\Status;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationRepository
{
    public function upSertReservation(array $data)
    {
        try {
            if (isset($data['id'])) {
                $reservation = Reservation::findOrFail($data['id']);

                $reservation->update([
                    'user_id'     => $data['user_id'] ?? null,
                    'customer_name' => $data['customer_name'],
                    'reserved_at' => $data['reserved_at'],
                    'status'      => $data['status'],
                    'note'        => $data['note'] ?? '',
                    'remark'        => $data['remark'] ?? '',
                ]);

                if (isset($data['tables'])) {
                    $reservation->tables()->sync(
                        collect($data['tables'])->pluck('id')->toArray()
                    );
                }
            } else {
                $reservation = Reservation::create([
                    'user_id'     => $data['user_id'] ?? null,
                    'customer_name' => $data['customer_name'],
                    'reserved_at' => $data['reserved_at'],
                    'status'      => $data['status'] ?? Status::PENDING,
                    'note'        => $data['note'] ?? '',
                    'remark'        => $data['remark'] ?? '',
                ]);

                if (isset($data['tables'])) {
                    $reservation->tables()->attach(
                        collect($data['tables'])->pluck('id')->toArray()
                    );
                }
            }

            return $reservation->load('tables');
        } catch (\Exception $e) {
            \Log::error("upSertReservation error: " . $e->getMessage());
            throw new \Exception("upSertReservation error: " . $e->getMessage());
        }
    }

    public function getDataReservation($keyword, $date, $page, $pageSize): array
    {
        try {
            $offset = ($page - 1) * $pageSize;
            $query = Reservation::with([
                'user',
                'tables' => fn($query) => $query->without('pivot')
            ])
                ->when($keyword, function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('customer_name', 'ILIKE', "%$keyword%")
                            ->orWhere('status', 'ILIKE', "%$keyword%")
                            ->orWhere('note', 'ILIKE', "%$keyword%")
                            ->orWhere('remark', 'ILIKE', "%$keyword%");
                    });
                })
                ->when($date, function ($query, $date) {
                    $query->whereDate('reserved_at', $date);
                });

            $total = $query->count();

            $data = $query->orderBy('reserved_at')
                ->offset($offset)
                ->limit($pageSize)
                ->get()
                ->each(function ($reservation) {
                    $reservation->tables->each->makeHidden('pivot');
                });

            return [
                'data' => $data,
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
    public function getDataReservationCustomer( $userId ,$page, $pageSize): array
    {
        try {
            $offset = ($page - 1) * $pageSize;
            $reservations = Reservation::with(['tables' => function ($query) {
                $query->without('pivot');
            }])
                ->where('user_id', $userId)
                ->orderBy('reserved_at')
                ->offset($offset)
                ->limit($pageSize)
                ->get()
                ->each(function ($reservation) {
                    $reservation->tables->each->makeHidden('pivot');
                });

            $total = Reservation::where('user_id', $userId)->count();
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
    public function getStatusReservation(): array
    {
        try {
            $statuses = [
                'pending' => Status::PENDING,
                'confirmed' => Status::CONFIRMED,
                'completed' => Status::COMPLETED,
                'canceled' => Status::CANCELED,
                'rejected' => Status::REJECTED,
            ];

            $data = [];
            foreach ($statuses as $key => $value) {
                $data[] = [
                    'status' => $key,
                    'total' => Reservation::where('status', $value)->count()
                ];
            }
            return $data;
        } catch (\Exception $e) {
            \Log::error("GetStatusReservation error: " . $e->getMessage());
            throw new \Exception("GetStatusReservation error: " . $e->getMessage());
        }
    }
}
