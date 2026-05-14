<?php

namespace App\Controllers;

use App\Models\ContactModel;

class TempImportController extends BaseController
{
    public function import()
    {
        // 1. Xác định thư mục tạm và tệp nguồn
        $tempDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'temp_excel';
        $excelFile = $tempDir . DIRECTORY_SEPARATOR . 'Danh_sach.xlsx';
        
        // Nếu không thấy ở temp_excel, thử tìm ở ROOT
        if (!file_exists($excelFile)) {
            $excelFile = ROOTPATH . 'Danh_sach.xlsx';
        }

        // 2. Tự động giải nén nếu chưa có thư mục xl/
        $xlDir = $tempDir . DIRECTORY_SEPARATOR . 'xl';
        if (!is_dir($xlDir)) {
            if (!file_exists($excelFile)) {
                return "Không tìm thấy tệp Excel tại: $excelFile. Vui lòng đảm bảo tệp Danh_sach.xlsx nằm trong thư mục writable/temp_excel/";
            }

            if (!class_exists('ZipArchive')) {
                return "Lỗi: Server thiếu thư viện ZipArchive để giải nén tệp .xlsx tự động.";
            }

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($excelFile) === TRUE) {
                $zip->extractTo($tempDir);
                $zip->close();
            } else {
                return "Lỗi: Không thể mở tệp $excelFile để giải nén.";
            }
        }

        // 3. Đường dẫn các tệp XML cần thiết
        $sharedStringsFile = $xlDir . DIRECTORY_SEPARATOR . 'sharedStrings.xml';
        $sheet1File = $xlDir . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet1.xml';

        if (!file_exists($sharedStringsFile) || !file_exists($sheet1File)) {
            return "Lỗi: Không tìm thấy các tệp cấu trúc Excel sau khi giải nén.<br>SharedStrings: $sharedStringsFile<br>Sheet1: $sheet1File";
        }

        // 4. Load Shared Strings
        $strings = [];
        $xmlStrings = @simplexml_load_file($sharedStringsFile);
        if (!$xmlStrings) return "Lỗi: Không thể đọc tệp sharedStrings.xml (XML không hợp lệ).";

        foreach ($xmlStrings->si as $si) {
            $strings[] = (string)($si->t ?? $si->r->t ?? '');
        }

        // 5. Load Sheet Data
        $xmlSheet = @simplexml_load_file($sheet1File);
        if (!$xmlSheet) return "Lỗi: Không thể đọc tệp sheet1.xml (XML không hợp lệ).";
        
        $rows = $xmlSheet->sheetData->row;

        $contactModel = new ContactModel();
        $importedCount = 0;

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($rows as $index => $row) {
            // Bỏ qua dòng tiêu đề (Dòng 1)
            if ($index == 0) continue;

            $data = [
                'source'           => null,
                'unit_name'        => '',
                'phone'            => null,
                'address'          => null,
                'position'         => null,
                'area'             => null,
                'reorganized_unit' => null,
                'notes'            => null,
                'province'         => null,
                'created_by'       => 1, // Mặc định Admin
                'is_private'       => 0
            ];

            foreach ($row->c as $cell) {
                $r = (string)$cell['r']; // VD: A2, B2
                $col = preg_replace('/[0-9]+/', '', $r);
                $type = (string)$cell['t'];
                $val = (string)$cell->v;

                $finalVal = ($type == 's') ? ($strings[(int)$val] ?? '') : $val;
                if ($finalVal === '') $finalVal = null;

                switch ($col) {
                    case 'A': $data['source'] = $finalVal; break;
                    case 'B': $data['unit_name'] = $finalVal; break;
                    case 'C': $data['phone'] = $finalVal; break;
                    case 'D': $data['address'] = $finalVal; break;
                    case 'E': $data['position'] = $finalVal; break;
                    case 'F': $data['area'] = $finalVal; break;
                    case 'G': $data['reorganized_unit'] = $finalVal; break;
                    case 'H': $data['notes'] = $finalVal; break;
                    case 'I': $data['province'] = $finalVal; break;
                }
            }

            // Chỉ insert nếu có tên đơn vị
            if (!empty($data['unit_name'])) {
                $contactModel->insert($data);
                $importedCount++;
            }
        }

        $db->transComplete();

        return "Chúc mừng! Đã nạp thành công $importedCount liên hệ vào hệ thống từ tệp $excelFile.";
    }
}
