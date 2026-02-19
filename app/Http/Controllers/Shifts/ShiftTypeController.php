<?php

namespace App\Http\Controllers\Shifts;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    public function getShiftTypes(): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => 'success',
            'data' => [],
        ];
        $responseCode = 200;

        try {
            $response['data'] = [
                [
                    'id' => 1,
                    'name' => 'onsite',
                    'display_name' => 'Onsite',
                ],
                [
                    'id' => 2,
                    'name' => 'offsite',
                    'display_name' => 'Offsite',
                ],
            ];
        } catch (\Throwable $e) {
            $responseCode = 500;
            $response['status'] = false;
            $response['message'] = $e->getMessage();
        }

        return $this->jsonify($response, $responseCode);
    }
}
