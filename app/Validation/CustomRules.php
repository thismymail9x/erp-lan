<?php

namespace App\Validation;

class CustomRules
{
    /**
     * Checks if the value is unique in the database, excluding soft-deleted records.
     * 
     * @param string $str
     * @param string $field e.g. "customers.phone,id,{id}"
     * @param array  $data
     * @return bool
     */
    public function is_unique_not_deleted(?string $str = null, string $field = '', array $data = []): bool
    {
        // Parse the rule parameters
        [$fieldParam, $ignoreField, $ignoreValue] = array_pad(explode(',', $field), 3, null);
        sscanf($fieldParam, '%[^.].%[^.]', $table, $column);

        $db = \Config\Database::connect();
        $builder = $db->table($table)
                      ->where($column, $str)
                      ->where('deleted_at', null);

        if ($ignoreField && $ignoreValue && $ignoreValue !== '{' . $ignoreField . '}') {
            $builder->where("{$ignoreField} !=", $ignoreValue);
        }

        return $builder->countAllResults() === 0;
    }
}
