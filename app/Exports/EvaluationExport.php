<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

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
        $user = $this->evaluationList;

        // Đặt font mặc định cho toàn bộ spreadsheet
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        // Tiêu đề
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'HẢI QUAN KHU VỰC XI');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', mb_strtoupper($user->teams->name, 'UTF-8') );
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
            'Điểm',
            'Tên lãnh đạo trực tiếp đánh giá',
            'Lãnh đạo phê duyệt',
            'Điểm',
            'Tên lãnh đạo phê duyệt',
            'Ghi chú',
        ];

        $sheet->fromArray($headings, null, 'A15');
        $sheet->getStyle('A15:O15')->getFont()->setBold(true);
        $sheet->getStyle('A15:O15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:O15')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:O15')->getAlignment()->setWrapText(true);

        // Dữ liệu bảng
        $evaluations = $this->evaluationList->evaluations;
        $currentRow = 16;
        $index = 1;

        foreach ($evaluations as $evaluation) {
            $taskName = $evaluation->tasks->name ?? 'Không xác định';

            // Tự đánh giá
            $selfRating = 'Chưa tự đánh giá';
            $selfName = '';
            if (!empty($evaluation->selfAssessment)) {
                $selfRating = isset($evaluation->selfAssessment['infoStatus']) && !empty($evaluation->selfAssessment['infoStatus']->name)
                    ? $evaluation->selfAssessment['infoStatus']->name
                    : 'Chưa tự đánh giá';
                $selfName = isset($evaluation->selfAssessment['infoUser']) && !empty($evaluation->selfAssessment['infoUser']->name)
                    ? $evaluation->selfAssessment['infoUser']->name
                    : '';
            }
            $selfAssessmentDisplay = $selfRating;

            // Lãnh đạo trực tiếp đánh giá và tên lãnh đạo trực tiếp
            $leaderRating = 'Chưa đánh giá';
            $leaderName = '';
            if (!empty($evaluation->deputyAssessment)) { // Sử dụng deputyAssessment thay vì assessmentLeader
                $leaderRating = isset($evaluation->deputyAssessment['infoStatus']) && !empty($evaluation->deputyAssessment['infoStatus']->name)
                    ? $evaluation->deputyAssessment['infoStatus']->name
                    : 'Chưa đánh giá';
                $leaderName = isset($evaluation->deputyAssessment['infoUser']) && !empty($evaluation->deputyAssessment['infoUser']->name)
                    ? $evaluation->deputyAssessment['infoUser']->name
                    : '';
                $leaderPoint = isset($evaluation->deputyAssessment['point']) && !empty($evaluation->deputyAssessment['point'])
                ? $evaluation->deputyAssessment['point']
                : '';
            }
            
            $leaderAssessmentDisplay = $leaderRating ;

            // Lãnh đạo phê duyệt và tên lãnh đạo phê duyệt
            $approvalRating = 'Chưa phê duyệt';
            $approverName = '';
            if (!empty($evaluation->leadershipApproval)) {
                $approvalRating = isset($evaluation->leadershipApproval['infoStatus']) && !empty($evaluation->leadershipApproval['infoStatus']->name)
                    ? $evaluation->leadershipApproval['infoStatus']->name
                    : 'Chưa phê duyệt';
                $approverName = isset($evaluation->leadershipApproval['infoUser']) && !empty($evaluation->leadershipApproval['infoUser']->name)
                    ? $evaluation->leadershipApproval['infoUser']->name
                    : '';
                $approverPoint = isset($evaluation->leadershipApproval['point']) && !empty($evaluation->leadershipApproval['point'])
                ? $evaluation->leadershipApproval['point']
                : '';
            }
            $approvalAssessmentDisplay = $approvalRating;

            // Log để kiểm tra
            Log::info('Evaluation Data for Export:', [
                'evaluation_id' => $evaluation->id,
                'self_assessment' => $selfAssessmentDisplay,
                'leader_assessment' => $leaderAssessmentDisplay,
                'approval_assessment' => $approvalAssessmentDisplay,
            ]);

            // Định dạng ngày tháng
            $startDateFormatted = $evaluation->start_date ? \Carbon\Carbon::parse($evaluation->start_date)->format('d/m/Y') : '';
            $dueDateFormatted = $evaluation->due_date ? \Carbon\Carbon::parse($evaluation->due_date)->format('d/m/Y') : '';
            
            // Thời gian thực tế thực hiện công việc
            $actualDays = $evaluation->completion_date ?? '';

            $dataRow = [
                $index++,
                $taskName,
                $startDateFormatted,
                $startDateFormatted, // Ngày giao việc
                $dueDateFormatted,
                $actualDays,
                $evaluation->output ?? '',
                $selfAssessmentDisplay,
                $leaderAssessmentDisplay,
                $leaderPoint ?? '',
                $leaderName, // Tên lãnh đạo trực tiếp (giữ riêng để khớp với cột tiêu đề)
                $approvalAssessmentDisplay,
                $approverPoint ?? '',
                $approverName, // Tên lãnh đạo phê duyệt (giữ riêng để khớp với cột tiêu đề)
                '', // Cột "Ghi chú"
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
        $sheet->getStyle('A15:O' . $lastRow)->applyFromArray($styleArray);

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
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A15:O' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A15:O' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A15:O' . $lastRow)->getAlignment()->setWrapText(true);

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