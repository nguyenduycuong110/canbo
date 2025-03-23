<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class MonthRateExport
{
    protected $evaluations;
    protected $month;

    public function __construct($evaluations, $month)
    {
        $this->evaluations = $evaluations;
        $this->month = $month;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Đặt font mặc định cho toàn bộ spreadsheet
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        // Tiêu đề
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'Kết quả đánh giá, xếp loại lưu trữ công chức chế độ');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'Tháng ' . $this->month);
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề bảng
        $headings = [
            'STT',
            'Họ và tên',
            'Chức vụ',
            'Đơn vị/Vị trí công tác',
            'Số ngày làm việc thực tế',
            'Số ngày nghỉ có phép',
            'Số ngày nghỉ không phép',
            'Số lần vi phạm quy chế, quy định',
            'Hình thức kỷ luật',
            'Tự xếp loại',
            '% mức độ hoàn thành vượt nhiệm vụ (Lãnh đạo đánh giá)',
            'Mức xếp loại của Lãnh đạo',
            'Chú thích',
        ];

        $sheet->fromArray($headings, null, 'A4');
        $sheet->getStyle('A4:M4')->getFont()->setBold(true);
        $sheet->getStyle('A4:M4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:M4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:M4')->getAlignment()->setWrapText(true);

        // Sắp xếp evaluations theo team để merge các team giống nhau
        $evaluationsArray = collect($this->evaluations)->sortBy('team')->values()->all();

        // Dữ liệu bảng
        $currentRow = 5;
        $index = 1;
        $mergeStartRow = 5; // Dòng bắt đầu merge
        $previousTeam = null;

        foreach ($evaluationsArray as $key => $evaluation) {
            $team = $evaluation->team ?? 'Không xác định';

            // Kiểm tra merge
            if ($previousTeam !== null && $previousTeam !== $team) {
                // Nếu team thay đổi, merge các ô của team trước đó
                if ($currentRow - 1 > $mergeStartRow) {
                    $sheet->mergeCells("D{$mergeStartRow}:D" . ($currentRow - 1));
                }
                $mergeStartRow = $currentRow;
            }

            $dataRow = [
                $index++,
                $evaluation->user->name ?? 'Không xác định',
                $evaluation->position ?? '',
                $team,
                $evaluation->working_days ?? '',
                $evaluation->leave_days_with_permission ?? '',
                $evaluation->leave_days_without_permission ?? '',
                $evaluation->violation_count ?? '',
                $evaluation->disciplinary_action ?? '',
                $evaluation->self_rating ?? '',
                $evaluation->overachieved_percentage ?? '',
                $evaluation->rating ?? '',
                $evaluation->note ?? '',
            ];

            $sheet->fromArray($dataRow, null, 'A' . $currentRow);
            $previousTeam = $team;
            $currentRow++;
        }

        // Merge team cuối cùng nếu cần
        if ($currentRow - 1 >= $mergeStartRow) {
            $sheet->mergeCells("D{$mergeStartRow}:D" . ($currentRow - 1));
        }

        // Thêm border cho bảng
        $lastRow = $currentRow - 1;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A4:M' . $lastRow)->applyFromArray($styleArray);

        // Điều chỉnh độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(5); // STT
        $sheet->getColumnDimension('B')->setWidth(20); // Họ và tên
        $sheet->getColumnDimension('C')->setWidth(15); // Chức vụ
        $sheet->getColumnDimension('D')->setWidth(25); // Đơn vị/Vị trí công tác
        $sheet->getColumnDimension('E')->setWidth(15); // Số ngày làm việc thực tế
        $sheet->getColumnDimension('F')->setWidth(15); // Số ngày nghỉ có phép
        $sheet->getColumnDimension('G')->setWidth(15); // Số ngày nghỉ không phép
        $sheet->getColumnDimension('H')->setWidth(15); // Số lần vi phạm
        $sheet->getColumnDimension('I')->setWidth(15); // Hình thức kỷ luật
        $sheet->getColumnDimension('J')->setWidth(15); // Tự xếp loại
        $sheet->getColumnDimension('K')->setWidth(20); // % mức độ hoàn thành
        $sheet->getColumnDimension('L')->setWidth(15); // Mức xếp loại
        $sheet->getColumnDimension('M')->setWidth(15); // Chú thích

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A4:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:M' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:M' . $lastRow)->getAlignment()->setWrapText(true);

        // Tạo thư mục tạm trong public nếu chưa tồn tại
        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Đặt tên file
        $date = str_replace('/', '_', $this->month);
        $filename = "evaluation_summary_{$date}.xlsx";
        $temp_file = $tempDir . '/' . $filename;

        // Lưu file
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp_file);

        return $temp_file;
    }
}