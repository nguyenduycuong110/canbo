<?php 
namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Log;
use App\Models\Evaluation;
use App\Models\User;

class MonthRankExport
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
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'CỤC HẢI QUAN');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'CHI CỤC HẢI QUAN KHU VỰC XI');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:J3');
        $sheet->setCellValue('A3', 'BẢNG TỔNG HỢP KẾT QUẢ XẾP LOẠI CHẤT LƯỢNG HÀNG THÁNG');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A4:J4');
        $sheet->setCellValue('A4', 'Tháng ' . $this->month);
        $sheet->getStyle('A4')->getFont()->setItalic(true);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Thêm dòng trống sau tháng năm
        $sheet->getRowDimension(5)->setRowHeight(10);

        // Tiêu đề bảng
        $headings = [
            'TT',
            '',  // Cột phụ
            'Họ và tên',
            'Chức vụ',
            'Số ngày làm việc theo pháp luật lao động',
            'Số ngày làm việc thực tế',
            'Số ngày nghỉ có lý do',
            'Số lần vi phạm quy chế, quy định',
            'Hình thức kỷ luật',
            'Kết quả xếp loại',
        ];

        $sheet->fromArray($headings, null, 'A6');
        $sheet->getStyle('A6:J6')->getFont()->setBold(true);
        $sheet->getStyle('A6:J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:J6')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A6:J6')->getAlignment()->setWrapText(true);

        // Thêm số thứ tự văn bản
        $sheet->setCellValue('A7', '1');
        $sheet->setCellValue('B7', '2');
        $sheet->setCellValue('C7', '3');
        $sheet->setCellValue('D7', '4');
        $sheet->setCellValue('E7', '5');
        $sheet->setCellValue('F7', '6');
        $sheet->setCellValue('G7', '7');
        $sheet->setCellValue('H7', '8');
        $sheet->setCellValue('I7', '9');
        $sheet->setCellValue('J7', '10');
        $sheet->getStyle('A7:J7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Định nghĩa vị trí lãnh đạo Chi cục (cố định)
        $leadershipPositions = ['Chi cục trưởng', 'Phó Chi cục trưởng', 'Chi cục phó'];

        // Hàm xác định phòng ban cho user
        $getDepartment = function ($user) use ($leadershipPositions) {
            // Lấy chức vụ từ user_catalogues
            $position = '';
            if (isset($user->user_catalogues)) {
                $position = $user->user_catalogues->name ?? '';
            }
            
            // Kiểm tra nếu là lãnh đạo Chi cục (cố định)
            foreach ($leadershipPositions as $leaderPos) {
                if (stripos($position, $leaderPos) !== false) {
                    return 'Lãnh đạo Chi cục';
                }
            }
            
            // Nếu không phải lãnh đạo, lấy team từ user
            if (isset($user->teams) && method_exists($user->teams, 'first')) {
                $firstTeam = $user->teams->first();
                if ($firstTeam && isset($firstTeam->name)) {
                    return $firstTeam->name;
                }
            } 
            elseif (isset($user->teams) && isset($user->teams->name)) {
                return $user->teams->name;
            } 
            elseif (isset($user->team) && isset($user->team->name)) {
                return $user->team->name;
            }
            
            // Nếu không xác định được, mặc định là "Không xác định"
            return 'Không xác định';
        };

        // Sắp xếp evaluations và gom nhóm theo phòng ban
        $departmentUsers = [];
        
        foreach ($this->evaluations as $evaluation) {
            $user = $evaluation['user'];
            $department = $getDepartment($user);
            $position = '';
            
            if (isset($user->user_catalogues)) {
                $position = $user->user_catalogues->name ?? '';
            }
            
            if (!isset($departmentUsers[$department])) {
                $departmentUsers[$department] = [];
            }
            
            $departmentUsers[$department][] = [
                'user' => $user,
                'position' => $position,
                'evaluation' => $evaluation
            ];
        }

        // Đảm bảo Lãnh đạo Chi cục luôn ở đầu tiên
        $sortedDepartments = [];
        if (isset($departmentUsers['Lãnh đạo Chi cục'])) {
            $sortedDepartments['Lãnh đạo Chi cục'] = $departmentUsers['Lãnh đạo Chi cục'];
            unset($departmentUsers['Lãnh đạo Chi cục']);
        }
        
        // Sắp xếp các phòng ban còn lại theo thứ tự bảng chữ cái
        ksort($departmentUsers);
        $sortedDepartments = array_merge($sortedDepartments, $departmentUsers);

        // Tạo bảng với cấu trúc phòng ban
        $currentRow = 8;
        $globalIndex = 1;
        $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        $deptIndex = 0;
        
        foreach ($sortedDepartments as $deptName => $users) {
            if (empty($users)) continue;
            
            // Viết tên phòng ban
            $sheet->setCellValue('A' . $currentRow, $globalIndex);
            $sheet->setCellValue('B' . $currentRow, $romanNumerals[$deptIndex]);
            $sheet->mergeCells('C' . $currentRow . ':J' . $currentRow);
            $sheet->setCellValue('C' . $currentRow, $deptName);
            $sheet->getStyle('C' . $currentRow)->getFont()->setBold(true);
            
            $currentRow++;
            
            // Thêm dữ liệu nhân viên
            $localIndex = 1;
            foreach ($users as $userData) {
                $user = $userData['user'];
                $position = $userData['position'];
                $evaluation = $userData['evaluation'];
                
                $sheet->setCellValue('A' . $currentRow, $globalIndex . '.' . $localIndex);
                $sheet->setCellValue('B' . $currentRow, $localIndex);
                $sheet->setCellValue('C' . $currentRow, $user->name ?? 'Không xác định');
                $sheet->setCellValue('D' . $currentRow, $position);
                $sheet->setCellValue('E' . $currentRow, $evaluation['working_days_in_month'] ?? '0');
                $sheet->setCellValue('F' . $currentRow, $evaluation['working_actual_days_in_month'] ?? '0');
                $sheet->setCellValue('G' . $currentRow, $evaluation['leave_days_with_permission'] ?? '0');
                $sheet->setCellValue('H' . $currentRow, $evaluation['violation_count'] ?? '0');
                $sheet->setCellValue('I' . $currentRow, $evaluation['disciplinary_action'] ?? '');
                $sheet->setCellValue('J' . $currentRow, $evaluation['final_rating'] ?? '');
                
                // Log thông tin debug
                Log::info("Adding user to Excel", [
                    'user' => $user->name,
                    'department' => $deptName,
                    'position' => $position,
                    'row' => $currentRow
                ]);
                
                $localIndex++;
                $currentRow++;
            }
            
            $globalIndex++;
            $deptIndex++;
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
        $sheet->getStyle('A6:J' . $lastRow)->applyFromArray($styleArray);

        // Điều chỉnh độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(5);   // TT
        $sheet->getColumnDimension('B')->setWidth(5);   // TT phụ
        $sheet->getColumnDimension('C')->setWidth(25);  // Họ và tên
        $sheet->getColumnDimension('D')->setWidth(20);  // Chức vụ
        $sheet->getColumnDimension('E')->setWidth(18);  // Số ngày làm việc theo pháp luật
        $sheet->getColumnDimension('F')->setWidth(15);  // Số ngày làm việc thực tế
        $sheet->getColumnDimension('G')->setWidth(15);  // Số ngày nghỉ có lý do
        $sheet->getColumnDimension('H')->setWidth(15);  // Số lần vi phạm quy định
        $sheet->getColumnDimension('I')->setWidth(15);  // Hình thức kỷ luật
        $sheet->getColumnDimension('J')->setWidth(15);  // Kết quả xếp loại

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A6:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:J' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A6:J' . $lastRow)->getAlignment()->setWrapText(true);
        
        // Căn trái cho cột tên và tên phòng ban
        $sheet->getStyle('C8:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        // Căn trái cho các phòng ban
        foreach ($romanNumerals as $index => $numeral) {
            foreach (range(8, $lastRow) as $row) {
                if ($sheet->getCell('B' . $row)->getValue() === $numeral) {
                    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('C' . $row)->getFont()->setBold(true);
                }
            }
        }

        // Tạo thư mục tạm trong public nếu chưa tồn tại
        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Đặt tên file
        $date = str_replace('/', '_', $this->month);
        $filename = "Bảng tổng hợp xếp loại tháng {$date}.xlsx";
        $temp_file = $tempDir . '/' . $filename;

        // Lưu file
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp_file);

        return $temp_file;
    }
    
}