<?php

namespace App\Services;

use DateTime;

/**
 * CaseTimelineService
 * 
 * Lớp dịch vụ quản lý quy trình nghiệp vụ (Workflow) và thời hạn (Timeline) mặc định.
 */
class CaseTimelineService
{
    /**
     * THUẬT TOÁN TÍNH TOÁN THỜI HẠN (Deadline Engine).
     * 
     * @param DateTime $startDate Ngày bắt đầu tính toán.
     * @param int|float $days Số ngày cần cộng thêm.
     * @return DateTime
     */
    public function calculateDeadline(DateTime $startDate, $days)
    {
        $date = clone $startDate;
        
        if ($days < 1) {
            $hours = $days * 24;
            $date->modify("+".round($hours)." hours");
            return $date;
        }

        while ($days > 0) {
            $date->modify('+1 day');
            if ($date->format('N') < 6) { 
                $days--;
            }
        }
        
        return $date;
    }
}