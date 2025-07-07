<?php

namespace App\Models;

use App\Constant\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    protected $table = 'reservations';
    protected $guarded = ['id'];

    public static function addReservation($data)
    {
        try {
            foreach ($data['tables'] as $table) {
                $date = $data['date'];
                $time = $data['time'];
                $params = [
                    'user_id' => $data['user'],
                    'reserved_at' => Carbon::createFromFormat('Y-m-d H:i', "$date $time"),
                    'time' => $data['time'],
                    'table_id' => $table['id'],
                    'note' => $data['note'] ?? null,
                    'status' => Status::PENDING,
                ];
                self::create($params);
            }
        } catch (\Exception $e) {
            \Log::error("AddReservation error: " . $e->getMessage());
            throw new \Exception("AddReservation error: " . $e->getMessage());
        }
    }
    public static function getTablesBooked($data)
    {
        try {
            $requestDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['time']}");
            $start = $requestDateTime->copy()->subHours(2);
            $end   = $requestDateTime->copy()->addHours(2);
            return self::where('status', Status::CONFIRMED)
                ->whereBetween('reserved_at', [$start, $end])
                ->pluck('table_id')
                ->toArray();
        } catch (\Exception $e) {
            \Log::error("GetTablesBooked error: " . $e->getMessage());
            throw new \Exception("GetTablesBooked error: " . $e->getMessage());
        }
    }
}
