<?php

namespace App\Models;

use App\Constant\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    protected $table = 'reservations';
    protected $guarded = ['id'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tables()
    {
        return $this->belongsToMany(Table::class, 'reservation_table')->withTimestamps();
    }

    public static function addReservation($data)
    {
        try {
                $date = $data['date'];
                $time = $data['time'];
                $params = [
                    'user_id' => $data['user'],
                    'reserved_at' => Carbon::createFromFormat('Y-m-d H:i', "$date $time"),
                    'time' => $data['time'],
                    'note' => $data['note'] ?? null,
                    'status' => Status::PENDING,
                ];
                self::create($params);

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
            $data=  self::where('status', Status::CONFIRMED)
                ->whereBetween('reserved_at', [$start, $end])
                ->get()
                ->toArray();
            if($data === null) {
                return [];
            }
            return $data;
        } catch (\Exception $e) {
            \Log::error("GetTablesBooked error: " . $e->getMessage());
            throw new \Exception("GetTablesBooked error: " . $e->getMessage());
        }
    }
}
