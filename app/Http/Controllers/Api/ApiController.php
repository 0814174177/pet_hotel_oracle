<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Controller nền tảng (Base Controller) dành cho toàn bộ các API Controller khác kế thừa.
 * Cung cấp các phương thức tái sử dụng để xác thực bộ lọc thời gian và định dạng JSON response chuẩn.
 */
abstract class ApiController extends Controller
{
    /**
     * Khai báo các quy tắc xác thực (validation rules) dùng chung cho các tham số thời gian.
     *
     * @return array Mảng chứa các quy tắc xác thực chuẩn cho mốc thời gian, chu kỳ, ngày, tháng, năm.
     */
    public static function timeFilterValidationRules(): array
    {
        return [
            'period_type' => ['nullable', 'string', 'in:day,month,year,range'],
            'period'      => ['nullable', 'string', 'in:day,month,year,range'],
            'date'        => ['nullable', 'date'],
            'month'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'        => ['nullable', 'integer', 'min:1'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Xác thực Request đầu vào dựa trên các quy tắc lọc thời gian mặc định.
     *
     * @param Request $request Dữ liệu HTTP Request.
     * @return array Trả về mảng các dữ liệu thời gian đã vượt qua xác thực an toàn.
     */
    protected function timeFilters(Request $request): array
    {
        return $request->validate(self::timeFilterValidationRules());
    }

    /**
     * Xác thực Request đầu vào bằng cách gộp các quy tắc lọc thời gian mặc định
     * cùng với mảng các quy tắc kiểm tra (rules) bổ sung tùy chỉnh.
     *
     * @param Request $request Dữ liệu HTTP Request.
     * @param array $rules Mảng các quy tắc xác thực bổ sung.
     * @return array Trả về mảng các dữ liệu đã được xác thực an toàn.
     */
    protected function validateWithTimeFilters(Request $request, array $rules = []): array
    {
        return $request->validate(array_merge(self::timeFilterValidationRules(), $rules));
    }

    /**
     * Trả về định dạng JSON chuẩn cho mọi response của API.
     * Có hỗ trợ thực thi hàm Closure để trì hoãn xử lý dữ liệu (Lazy evaluation) và bắt ngoại lệ (Try-Catch).
     *
     * @param mixed $data Dữ liệu cần trả về (có thể là mảng, đối tượng, hoặc Closure).
     * @param string $message Thông báo kết quả thực thi (mặc định: 'Success').
     * @param int $status Mã trạng thái HTTP (mặc định: 200).
     * @param array $extra Các thông tin siêu dữ liệu (meta, pagination...) đính kèm thêm.
     * @param string|null $errorMessage Thông báo lỗi tùy chỉnh (nếu muốn bắt ngoại lệ và trả về 500 thay vì throw).
     * @return JsonResponse Cấu trúc JSON trả về cho Client.
     * @throws Throwable Ném ra ngoại lệ nếu xảy ra lỗi và không có $errorMessage truyền vào.
     */
    protected function respondData(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $extra = [],
        ?string $errorMessage = null
    ): JsonResponse {
        try {
            $resolvedData = $data instanceof Closure ? $data() : $data;
        } catch (Throwable $exception) {
            if ($errorMessage === null) {
                throw $exception;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data'    => $resolvedData,
        ], $extra), $status);
    }
}