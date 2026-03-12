<?php

namespace App\Libraries;

class AttendanceType
{
    const LAT = 21.051701;
    const LNG = 105.780193;
    // --- Các loại hình chấm công ---
    const PRESENT = 'present'; // Tại nơi làm việc
    const REMOTE = 'remote'; // Làm từ xa
    const INVALID_LOCATION = 'invalid_location'; // Không rõ địa điểm

    // --- Các hằng số về thời gian quy định ---
    const STANDARD_CHECK_IN_TIME = '08:00:00';
    const STANDARD_CHECK_OUT_TIME = '17:30:00';

    /**
     * Ngưỡng thời gian cho phép đi muộn hoặc về sớm mà không bị tính là vi phạm.
     * Ở đây ta quy định là 10 phút (600 giây).
     */
    const GRACE_PERIOD_SECONDS = 10 * 60;
    // tổng thời gian làm việc hàng ngày kể cả nghỉ trƯA, tính bằng s
    const TOTAL_WORKED = 34200;

    // --- Trạng thái hợp lệ của thời gian chấm công ---
    const STATUS_PASS = 'Đúng giờ';
    const STATUS_ERROR = 'Vi phạm';


    public static function arrType()
    {
        return array(
            self::PRESENT => 'Tại công ty',
            self::REMOTE => 'Làm việc từ xa',
            self::INVALID_LOCATION => 'Không rõ địa điểm',
        );
    }

    public static function typeList($key = '')
    {
        if ($key == '') {
            return self::$arr;
        }
        if (isset(self::$arr[$key])) {
            return self::$arr[$key];
        }
        return '';
    }

    // Các hàm khác nếu có...
    public static function isLocationValid($userLat, $userLng)
    {
        if (empty( $userLat ) || empty( $userLng)) {
            return false;
        }
        $officeLat = AttendanceType::LAT;
        $officeLng = AttendanceType::LNG;
        // Bán kính cho phép, 100m = 0.1km
        $allowedRadiusKm = 0.1;

        // Bán kính Trái Đất (km)
        $earthRadius = 6371;

        // Chuyển đổi từ độ sang radian
        $dLat = deg2rad($userLat - $officeLat);
        $dLon = deg2rad($userLng - $officeLng);

        // Áp dụng công thức Haversine.
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($officeLat)) * cos(deg2rad($userLat)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Tính khoảng cách
        $distance = $earthRadius * $c; // Khoảng cách tính bằng km

        // Trả về true nếu khoảng cách nhỏ hơn hoặc bằng bán kính cho phép
        return $distance <= $allowedRadiusKm;
    }

}
