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

    public function upSertReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['nullable', 'integer', 'exists:reservations,id'],
            'user_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'status' => ['nullable', Rule::in([Status::PENDING, Status::CONFIRMED, Status::CANCELED, Status::COMPLETED, Status::REJECTED]),],
            'tables' => ['required', 'array', 'min:1'],
            'tables.*.id' => ['required', 'integer', 'exists:tables,id'],
            'tables.*.table_number' => ['required', 'integer'],
            'tables.*.capacity' => ['required', 'integer'],
            'note' => ['nullable', 'string'],
            'remark' => ['nullable', 'string'],
        ]);
        try {
            $data = $validator->validated();
            $reservedAt = ($data['date'] . ' ' . $data['time']);

            if ($data['status'] === Status::CONFIRMED) {
                $getTableBooked = Reservation::getIdTablesBooked($reservedAt);

                $findTableBooked = collect($request->tables)->filter(function ($table) use ($getTableBooked) {
                    return in_array($table['id'], $getTableBooked);
                })->pluck('table_number');
                if ($findTableBooked->isNotEmpty()) {
                    return ApiResponse::ErrorResponse(
                        '', 'Tables ' . $findTableBooked->implode(', ') . ' have already been booked on the selected date.'
                    );
                }
            }

            $data['reserved_at'] = $reservedAt;
            DB::BeginTransaction();

            $reservation = $this->reservationRepository->upSertReservation($data);

            DB::commit();
            return ApiResponse::BaseResponse($reservation, 'Reservation created successfully.');
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
                'keyword' => 'nullable|string|max:255',
                'date' => 'nullable|string|date',
                'page' => 'required|integer|min:1',
                'pageSize' => 'required|integer|min:1',
            ]);
            $result = $this->reservationRepository->getDataReservation(
                $validated['keyword'] ?? null,
                $validated['date'] ?? null,
                $validated['page'],
                $validated['pageSize']
            );
            $data = $result['data'];
            $total = $result['total'];
            $meta = [
                'data' => $data,
                'page' => $result['page'],
                'pageSize' => $result['pageSize'],
                'total' => $total,
            ];
            return ApiResponse::PaginateResponse($meta);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getReservationCustomer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'keyword' => 'nullable|string|max:255',
                'page' => 'required|integer|min:1',
                'pageSize' => 'required|integer|min:1',
            ]);
            $result = $this->reservationRepository->getDataReservationCustomer(
                $validated['user_id'],
                $validated['keyword'] ?? null,
                $validated['page'],
                $validated['pageSize']
            );
            $data = $result['data'];
            $total = $result['total'];
            $meta = [
                'data' => $data,
                'page' => $result['page'],
                'pageSize' => $result['pageSize'],
                'total' => $total,
            ];
            return ApiResponse::PaginateResponse($meta);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getTotalStatusReservation(Request $request): JsonResponse
    {
        try {
            $data = $this->reservationRepository->getStatusReservation();
            return ApiResponse::BaseResponse($data);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getTablesAvailable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'id_reservation' => ['nullable', 'integer', 'exists:reservations,id'],
                'date' => ['required', 'date'],
            ]);

        if ($validator->fails()) {
            return ApiResponse::ErrorResponse('Validasi gagal', $validator->errors());
        }

        $data = $validator->validated();
        try {
            $allTables = Table::all()->makeHidden(['created_at', 'updated_at']);
            $idTableBooked = Reservation::getIdTablesBooked($data['date']);

            $tables = $allTables->map(function ($table) use ($idTableBooked) {
                $table->status = in_array($table->id, $idTableBooked) ? 'booked' : 'available';
                return $table;
            });
            return ApiResponse::BaseResponse($tables);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
    public function deleteReservation(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:reservations,id'],
        ]);
        try {
            $reservation = Reservation::findOrFail($request->id);
            $reservation->delete();

            return ApiResponse::BaseResponse($reservation, 'Reservasi berhasil dihapus');
        } catch (\Exception $e) {
            \Log::error("DeleteReservation error: " . $e->getMessage());
            return ApiResponse::ErrorResponse(
                'Gagal menghapus reservasi',
                $e->getMessage()
            );
        }
    }
}
