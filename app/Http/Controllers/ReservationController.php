<?php

namespace App\Http\Controllers;

use App\Constant\Status;
use App\Helper\ApiResponse;
use App\Models\Reservation;
use App\Models\Table;
use App\Repositories\ReservationRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    protected $reservationRepository;
    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }
    public function addReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
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
        $data['reserved_at'] = date('Y-m-d H:i:s', strtotime($data['date'] . ' ' . $data['time']));
        try {
            DB::BeginTransaction();
            $reservation = $this->reservationRepository->addDataRepository($data);

            DB::commit();
            return ApiResponse::BaseResponse($reservation, 'Reservasi berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getReservation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page' => 'required|integer|min:1',
                'pageSize' => 'required|integer|min:1',
            ]);
            $result = $this->reservationRepository->getDataReservation(
                $validated['page'],
                $validated['pageSize']
            );
            $data = $result['data'];
            $total = $result['total'];
            return ApiResponse::PaginateResponse($data, $total);
        }
        catch (\Exception $e) {
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
            $tables = Table::all()->makeHidden(['created_at', 'updated_at']);
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
