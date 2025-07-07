<?php

namespace App\Http\Controllers;

use App\Constant\Status;
use App\Helper\ApiResponse;
use App\Models\Reservation;
use App\Models\Tables;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function Reservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],

            'tables' => ['required', 'array', 'min:1'],
            'tables.*.id' => ['required', 'integer', 'exists:tables,id'],
            'tables.*.table_number' => ['required', 'integer'],
            'tables.*.capacity' => ['required', 'integer'],
            'tables.*.status' => ['required', Rule::in([Status::AVAILABLE])],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::ErrorResponse('Validasi gagal', $validator->errors());
        }

        $data = $validator->validated();
        try {
            DB::BeginTransaction();
            $reservation = Reservation::addReservation($data);

            DB::commit();
            return ApiResponse::BaseResponse($reservation, 'Reservasi berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getTablesAvailable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::ErrorResponse('Validasi gagal', $validator->errors());
        }

        $data = $validator->validated();
        try {
            $tables = Tables::all()->makeHidden(['created_at', 'updated_at']);
            $tableBooked = Reservation::getTablesBooked($data);

            $tables = $tables->map(function ($table) use ($tableBooked) {
                $table->status = in_array($table->id, $tableBooked) ? 'booked' : 'available';
                return $table;
            });
            return ApiResponse::BaseResponse($tables);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
}
