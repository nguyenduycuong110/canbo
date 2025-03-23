<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class LeaderEvaluationExport
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
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'CỤC HẢI QUAN TỈNH HÀ TĨNH');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'CHI CỤC HẢI QUAN CỬA KHẨU QUỐC TẾ CẦU TREO');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Phụ lục I');
        $sheet->getStyle('A3')->getFont()->setItalic(true);

        $sheet->mergeCells('A4:L4');
        $sheet->setCellValue('A4', 'PHIẾU TỰ ĐÁNH GIÁ CÔNG VIỆC HÀNG THÁNG');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A5:L5');
        $sheet->setCellValue('A5', 'Tháng ' . $this->month);
        $sheet->getStyle('A5')->getFont()->setItalic(true);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A6:L6');
        $sheet->setCellValue('A6', '(Dùng cho công chức giữ chức vụ lãnh đạo)');
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
            'Tổng số công việc/ nhiệm vụ được giao',
            'Số công việc/ nhiệm vụ hoàn thành vượt mức về thời gian hoặc chất lượng',
            'Số công việc/ nhiệm vụ hoàn thành đúng hạn, đảm bảo chất lượng',
            'Số công việc/ nhiệm vụ không hoàn thành đúng hạn hoặc không đảm bảo yêu cầu',
            'Lãnh đạo trực tiếp đánh giá',
            'Tên lãnh đạo trực tiếp đánh giá',
            'Lãnh đạo phê duyệt',
            'Tên lãnh đạo phê duyệt',
            'Ghi chú',
        ];

        $sheet->fromArray($headings, null, 'A15');
        $sheet->getStyle('A15:L15')->getFont()->setBold(true);
        $sheet->getStyle('A15:L15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:L15')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:L15')->getAlignment()->setWrapText(true);

        // Dữ liệu bảng
        $evaluations = $this->evaluationList->evaluations;
        $currentRow = 16;
        $index = 1;

        foreach ($evaluations as $evaluation) {
            $taskName = $evaluation->tasks->name ?? 'Không xác định';

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
            $startDateFormatted = $evaluation->created_at ? \Carbon\Carbon::parse($evaluation->created_at)->format('d/m/Y') : '';

            $dataRow = [
                $index++,
                $taskName,
                $startDateFormatted,
                $evaluation->total_tasks ?? '',
                $evaluation->overachieved_tasks ?? '',
                $evaluation->completed_tasks_ontime ?? '',
                $evaluation->failed_tasks_count ?? '',
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
        $sheet->getStyle('A15:L' . $lastRow)->applyFromArray($styleArray);

        // Điều chỉnh độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(5); // STT
        $sheet->getColumnDimension('B')->setWidth(30); // Nội dung công việc
        $sheet->getColumnDimension('C')->setWidth(15); // Ngày
        $sheet->getColumnDimension('D')->setWidth(15); // Tổng số công việc/nhiệm vụ được giao
        $sheet->getColumnDimension('E')->setWidth(15); // Số công việc/nhiệm vụ hoàn thành vượt mức
        $sheet->getColumnDimension('F')->setWidth(15); // Số công việc/nhiệm vụ hoàn thành đúng hạn
        $sheet->getColumnDimension('G')->setWidth(15); // Số công việc/nhiệm vụ không hoàn thành
        $sheet->getColumnDimension('H')->setWidth(30); // Lãnh đạo trực tiếp đánh giá
        $sheet->getColumnDimension('I')->setWidth(20); // Tên lãnh đạo trực tiếp
        $sheet->getColumnDimension('J')->setWidth(30); // Lãnh đạo phê duyệt
        $sheet->getColumnDimension('K')->setWidth(20); // Tên lãnh đạo phê duyệt
        $sheet->getColumnDimension('L')->setWidth(15); // Ghi chú

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A15:L' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:L' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:L' . $lastRow)->getAlignment()->setWrapText(true);

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