<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('has_permission')) {
    /**
     * Kiểm tra nhanh xem người dùng hiện tại có quyền thực thi hành động hay không.
     * Cờ 'sys.admin' luôn trả về true cho mọi quyền.
     *
     * @param string $actionKey Mã quyền (VD: 'case.manage', 'user.view')
     * @return bool
     */
    function has_permission(string $actionKey): bool
    {
        $session = \Config\Services::session();
        $userPerms = $session->get('permissions');

        if (!is_array($userPerms)) {
            return false;
        }

        $cleanKey = trim($actionKey);

        if (in_array('sys.admin', $userPerms)) {
            return true;
        }

        return in_array($cleanKey, $userPerms);
    }
}
