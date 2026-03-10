<?php

namespace Config;

/**
 * AppConstants
 * 
 * Quy hoạch toàn bộ các hằng số dùng chung cho hệ thống (Roles, Departments, Statuses).
 */
class AppConstants
{
    // === CHỨC DANH (ROLES) ===
    public const ROLE_ADMIN             = 'Admin';
    public const ROLE_MOD               = 'Mod';
    public const ROLE_TRUONG_PHONG      = 'Trưởng phòng';
    public const ROLE_NHAN_VIEN_CHINH   = 'Nhân viên chính thức';
    public const ROLE_THUC_TAP_SINH     = 'Thực tập sinh';

    // === PHÒNG BAN (DEPARTMENTS) - INT ID ===
    public const DEPT_MARKETING        = 1;
    public const DEPT_SALE             = 2;
    public const DEPT_PHAP_LY          = 3;
    public const DEPT_HANH_CHINH       = 4;
    public const DEPT_CONG_TAC_VIEN    = 5;
    public const DEPT_DOI_TAC          = 6;

    // === VAI TRÒ MẶC ĐỊNH (DEFAULT ROLE) ===
    public const ROLE_DEFAULT           = self::ROLE_THUC_TAP_SINH;

    // === TRẠNG THÁI VỤ VIỆC (CASE STATUS) ===
    public const CASE_STATUS_OPEN       = 'open';
    public const CASE_STATUS_IN_PROGRESS = 'in_progress';
    public const CASE_STATUS_PENDING    = 'pending';
    public const CASE_STATUS_CLOSED     = 'closed';
    public const CASE_STATUS_CANCELLED  = 'cancelled';

    /**
     * Danh sách các vai trò có quyền xem toàn bộ dữ liệu (Privileged Roles)
     */
    public const PRIVILEGED_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MOD,
        self::ROLE_TRUONG_PHONG
    ];
}
