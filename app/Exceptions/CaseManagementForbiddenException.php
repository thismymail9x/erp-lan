<?php

namespace App\Exceptions;

/** Từ chối cập nhật ngoài phạm vi quản lý bằng mã HTTP 403. */
class CaseManagementForbiddenException extends \CodeIgniter\Exceptions\RuntimeException implements \CodeIgniter\Exceptions\HTTPExceptionInterface
{
    protected $code = 403;
}
