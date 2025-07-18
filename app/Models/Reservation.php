<?php

namespace App\Models;

use App\Constant\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use SoftDeletes;
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

    public static function getTablesBooked($data)
    {
        try {
            $requestDateTime = Carbon::parse($data['date']);
            $start = $requestDateTime->copy()->subHours(2);
            $end = $requestDateTime->copy()->addHours(2);
            $data = self::with('tables')
                ->where('status', Status::CONFIRMED)
                ->whereBetween('reserved_at', [$start, $end])
                ->get()
                ->toArray();
            $idTables = collect($data)->pluck('tables')->flatten(1)->pluck('id')->toArray();

            return $idTables;
        } catch (\Exception $e) {
            \Log::error("GetTablesBooked error: " . $e->getMessage());
            throw new \Exception("GetTablesBooked error: " . $e->getMessage());
        }
    }
}
