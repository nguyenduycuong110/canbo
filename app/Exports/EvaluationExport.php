<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class EvaluationExport
{
    protected $evaluationList;
    protected $month;
    protected $formData;

    public function __construct($evaluationList, $month, $formData)
    {
        $this->evaluationList = $evaluationList;
        $this->month = $month;
        $this->formData = $formData;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Đặt font mặc định cho toàn bộ spreadsheet
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        // Tiêu đề
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'CỤC HẢI QUAN TỈNH HÀ TĨNH');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'CHI CỤC HẢI QUAN CỬA KHẨU QUỐC TẾ CẦU TREO');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Phụ lục I');
        $sheet->getStyle('A3')->getFont()->setItalic(true);

        $sheet->mergeCells('A4:M4');
        $sheet->setCellValue('A4', 'PHIẾU TỰ ĐÁNH GIÁ CÔNG VIỆC HÀNG THÁNG');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A5:M5');
        $sheet->setCellValue('A5', 'Tháng ' . $this->month);
        $sheet->getStyle('A5')->getFont()->setItalic(true);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A6:M6');
        $sheet->setCellValue('A6', '(Dùng cho công chức không giữ chức vụ lãnh đạo)');
        $sheet->getStyle('A6')->getFont()->setItalic(true);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Nội dung thông tin
        $user = $this->evaluationList;
        $userName = $user->name ?? 'Không xác định';
        $department = ($user->teams && $user->units) ? $user->teams->name . ' - ' . $user->units->name : 'Không xác định';

        $sheet->setCellValue('A7', '1. Họ và tên: ' . $userName);
        $sheet->setCellValue('A8', '2. Vị trí, đơn vị công tác: ' . $department);
        $sheet->setCellValue('A9', '3. Số ngày làm việc theo quy định của pháp luật lao động trong tháng: ' . $this->formData['working_days_in_month']);
        $sheet->setCellValue('A10', '4. Số ngày nghỉ trong tháng (có phép): ' . $this->formData['leave_days_with_permission']);
        $sheet->setCellValue('A11', '5. Số ngày nghỉ trong tháng (không phép): ' . $this->formData['leave_days_without_permission']);
        $sheet->setCellValue('A12', '6. Số lần vi phạm quy chế, quy định: ' . $this->formData['violation_count']);
        $sheet->setCellValue('F12', '7. Hành vi vi phạm: ' . $this->formData['violation_behavior']);
        $sheet->setCellValue('I12', '8. Hình thức kỷ luật: ' . $this->formData['disciplinary_action']);
        $sheet->setCellValue('A13', '9. Bảng kê chi tiết công việc:');

        // Tiêu đề bảng
        $headings = [
            'STT',
            'Nội dung công việc',
            'Ngày',
            'Ngày giao việc',
            'Thời gian hoàn thành',
            'Thời gian thực tế thực hiện công việc',
            'Sản phẩm đầu ra',
            'Cá nhân tự đánh giá',
            'Lãnh đạo trực tiếp đánh giá',
            'Tên lãnh đạo trực tiếp đánh giá',
            'Lãnh đạo phê duyệt',
            'Tên lãnh đạo phê duyệt',
            'Ghi chú',
        ];

        $sheet->fromArray($headings, null, 'A15');
        $sheet->getStyle('A15:M15')->getFont()->setBold(true);
        $sheet->getStyle('A15:M15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:M15')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:M15')->getAlignment()->setWrapText(true);

        // Dữ liệu bảng
        $evaluations = $this->evaluationList->evaluations;
        $currentRow = 16;
        $index = 1;

        foreach ($evaluations as $evaluation) {
            $taskName = $evaluation->tasks->name ?? 'Không xác định';

            // Tự đánh giá
            $selfRating = 'Không xác định';
            if ($evaluation->statuses) {
                $selfStatus = collect($evaluation->statuses)->firstWhere('pivot.user_id', $evaluation->user_id);
                $selfRating = $selfStatus ? $selfStatus->name : 'Không xác định';
            }

            // Lãnh đạo trực tiếp đánh giá và tên lãnh đạo trực tiếp
            $leaderRating = '';
            $leaderName = '';
            if (!empty($evaluation->assessmentLeader) && is_array($evaluation->assessmentLeader)) {
                $leaderRating = $evaluation->assessmentLeader['infoStatus']['name'] ?? '';
                $leaderName = $evaluation->assessmentLeader['infoUser']['name'] ?? '';
            }

            // Lãnh đạo phê duyệt và tên lãnh đạo phê duyệt
            $approvalRating = '';
            $approverName = '';
            if (!empty($evaluation->leadershipApproval) && is_array($evaluation->leadershipApproval)) {
                $approvalRating = $evaluation->leadershipApproval['infoStatus']['name'] ?? '';
                $approverName = $evaluation->leadershipApproval['infoUser']['name'] ?? '';
            }

            // Định dạng ngày tháng
            $startDateFormatted = $evaluation->start_date ? \Carbon\Carbon::parse($evaluation->start_date)->format('d/m/Y') : '';
            $dueDateFormatted = $evaluation->due_date ? \Carbon\Carbon::parse($evaluation->due_date)->format('d/m/Y') : '';
           
            // Thời gian thực tế thực hiện công việc (lấy trực tiếp từ database, không format)
            $actualDays = $evaluation->completion_date ?? ''; // Giả sử cột trong database là completion_date

            $dataRow = [
                $index++,
                $taskName,
                $startDateFormatted,
                $startDateFormatted, // Ngày giao việc
                $dueDateFormatted,
                $actualDays, // Hiển thị trực tiếp giá trị từ database
                $evaluation->output ?? '',
                $selfRating,
                $leaderRating,
                $leaderName,
                $approvalRating,
                $approverName,
                '', // Cột "Ghi chú" để trống nếu không có dữ liệu
            ];

            $sheet->fromArray($dataRow, null, 'A' . $currentRow);
            $currentRow++;
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
        $sheet->getStyle('A15:M' . $lastRow)->applyFromArray($styleArray);

        // Điều chỉnh độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(5); // STT
        $sheet->getColumnDimension('B')->setWidth(30); // Nội dung công việc
        $sheet->getColumnDimension('C')->setWidth(15); // Ngày
        $sheet->getColumnDimension('D')->setWidth(15); // Ngày giao việc
        $sheet->getColumnDimension('E')->setWidth(15); // Thời gian hoàn thành
        $sheet->getColumnDimension('F')->setWidth(15); // Thời gian thực tế
        $sheet->getColumnDimension('G')->setWidth(30); // Sản phẩm đầu ra
        $sheet->getColumnDimension('H')->setWidth(30); // Cá nhân tự đánh giá
        $sheet->getColumnDimension('I')->setWidth(30); // Lãnh đạo trực tiếp đánh giá
        $sheet->getColumnDimension('J')->setWidth(20); // Tên lãnh đạo trực tiếp
        $sheet->getColumnDimension('K')->setWidth(30); // Lãnh đạo phê duyệt
        $sheet->getColumnDimension('L')->setWidth(20); // Tên lãnh đạo phê duyệt
        $sheet->getColumnDimension('M')->setWidth(15); // Ghi chú

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A15:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:M' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:M' . $lastRow)->getAlignment()->setWrapText(true);

        // Thêm dòng "10. Kết quả xếp loại chất lượng tháng" và "Cán bộ lập phiếu"
        $sheet->setCellValue('A' . ($lastRow + 2), '10. Kết quả xếp loại chất lượng tháng:');
        $sheet->setCellValue('A' . ($lastRow + 3), 'Cán bộ lập phiếu');

        // Tạo thư mục tạm trong public nếu chưa tồn tại
        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Đặt tên file
        $date = str_replace('/', '_', $this->month);
        $filename = "evaluation_report_{$date}_" . ($user->account ?? 'unknown') . ".xlsx";
        $temp_file = $tempDir . '/' . $filename;

        // Lưu file
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp_file);

        return $temp_file;
    }
}