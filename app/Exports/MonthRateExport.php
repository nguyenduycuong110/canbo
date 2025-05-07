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
        // Debug để kiểm tra cấu trúc dữ liệu
        foreach ($this->evaluations as $key => $evaluation) {
            Log::info("Debug user team info", [
                'user_name' => $evaluation['user']->name,
                'teams_exists' => isset($evaluation['user']->teams),
                'teams_type' => isset($evaluation['user']->teams) ? gettype($evaluation['user']->teams) : null,
                'user_catalogues' => isset($evaluation['user']->user_catalogues) ? $evaluation['user']->user_catalogues->name : null
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Đặt font mặc định cho toàn bộ spreadsheet
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);

        // Tiêu đề
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'TỔNG HỢP Kết quả đánh giá, xếp loại chất lượng công chức');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:L2');
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
            'Số lần vi phạm quy chế, quy định',
            'Hình thức kỷ luật',
            'Tự xếp loại',
            'Điểm của lãnh đạo đánh giá',
            'Điểm của lãnh đạo phê duyệt',
            '% mức độ hoàn thành nhiệm vụ',
            'Mức xếp loại của Lãnh đạo',
            'Tổng Nhiệm Vụ',
        ];

        $sheet->fromArray($headings, null, 'A4');
        $sheet->getStyle('A4:N4')->getFont()->setBold(true);
        $sheet->getStyle('A4:N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:N4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:N4')->getAlignment()->setWrapText(true);

        // Tạo hàm lấy team an toàn
        $getTeamName = function ($user) {
            // Kiểm tra nếu teams là một collection
            if (isset($user->teams) && method_exists($user->teams, 'first')) {
                $firstTeam = $user->teams->first();
                if ($firstTeam && isset($firstTeam->name)) {
                    return $firstTeam->name;
                }
            }
            // Kiểm tra nếu teams là một đối tượng đơn
            elseif (isset($user->teams) && isset($user->teams->name)) {
                return $user->teams->name;
            }
            // Kiểm tra nếu có đội trực tiếp
            elseif (isset($user->team) && isset($user->team->name)) {
                return $user->team->name;
            }
            
            return 'Không xác định';
        };

        // Sắp xếp evaluations theo team để merge các team giống nhau
        $evaluationsArray = collect($this->evaluations)->sortBy(function ($item) use ($getTeamName) {
            return $getTeamName($item['user']);
        })
        ->unique(function ($item) {
            return $item['user']->id;
        })
        // ->sortBy(function ($item) {
        //     return $item['user']->user_catalogues->level; 
        // })
        ->values()->all();

        // Dữ liệu bảng
        $currentRow = 5;
        $index = 1;
        $mergeStartRow = 5; // Dòng bắt đầu merge
        $previousTeam = null;

        foreach ($evaluationsArray as $key => $evaluation) {
            $user = $evaluation['user'];
            $leaderPoint = ($user->parent_id != 0) ? $this->calculateLeaderPoint($user, $this->month) : 0;
            $approverPoint = ($user->parent_id != 0) ? $this->calculateApproverPoint($user, $this->month) : 0;
            // Lấy team từ quan hệ teams của user
            $team = $getTeamName($evaluation['user']);
            
            // Kiểm tra merge - CHỈ MERGE KHI TEAM GIỐNG NHAU
            if ($previousTeam !== null && $previousTeam !== $team) {
                // Nếu team thay đổi, merge các ô của team trước đó
                if ($currentRow - 1 > $mergeStartRow) {
                    $sheet->mergeCells("D{$mergeStartRow}:D" . ($currentRow - 1));
                }
                $mergeStartRow = $currentRow;
            }
            
            // Chức vụ từ user_catalogues
            $position = '';
            if (isset($evaluation['user']->user_catalogues)) {
                $position = $evaluation['user']->user_catalogues->name ?? '';
            }

            $dataRow = [
                $index++,
                $evaluation['user']->name ?? 'Không xác định',
                $position,
                $team,
                $evaluation['working_days'] ?? 0,
                $evaluation['leave_days'] ?? 0,
                $evaluation['violation_count'] ?? 0,
                $evaluation['disciplinary_action'] ?? '',
                $evaluation['rateInfo']['selfRating'] ?? '',
                $evaluation['rateInfo']['selfRating'] == 'Không đánh giá' ? '' :  $leaderPoint, 
                $evaluation['rateInfo']['selfRating'] == 'Không đánh giá' ? '' :  $approverPoint, 
                $evaluation['completion_percentage'] ?? 0,
                $evaluation['rateInfo']['final_rating'] ?? '',
                $evaluation['totalTasks'] ?? 0,
            ];
            
            $sheet->fromArray($dataRow, null, 'A' . $currentRow);
            
            // Log thông tin debug
            Log::info("Adding row to Excel", [
                'user' => $evaluation['user']->name,
                'team' => $team,
                'previous_team' => $previousTeam,
                'row' => $currentRow,
                'mergeStartRow' => $mergeStartRow
            ]);
            
            $previousTeam = $team;
            $currentRow++;
        }

        // Merge team cuối cùng nếu cần
        if ($currentRow - 1 > $mergeStartRow) {
            $sheet->mergeCells("D{$mergeStartRow}:D" . ($currentRow - 1));
            
            // Log thông tin debug merge cuối cùng
            Log::info("Final merge", [
                'team' => $previousTeam,
                'merge_range' => "D{$mergeStartRow}:D" . ($currentRow - 1)
            ]);
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
        $sheet->getStyle('A4:N' . $lastRow)->applyFromArray($styleArray);

        // Điều chỉnh độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(5);  // STT
        $sheet->getColumnDimension('B')->setWidth(20); // Họ và tên
        $sheet->getColumnDimension('C')->setWidth(15); // Chức vụ
        $sheet->getColumnDimension('D')->setWidth(25); // Đơn vị/Vị trí công tác
        $sheet->getColumnDimension('E')->setWidth(15); // Số ngày làm việc thực tế
        $sheet->getColumnDimension('F')->setWidth(15); // Số ngày nghỉ có phép
        $sheet->getColumnDimension('G')->setWidth(15); // Số lần vi phạm
        $sheet->getColumnDimension('H')->setWidth(15); // Hình thức kỷ luật
        $sheet->getColumnDimension('I')->setWidth(15); // Tự xếp loại
        $sheet->getColumnDimension('J')->setWidth(20); // % mức độ hoàn thành
        $sheet->getColumnDimension('K')->setWidth(15); // Mức xếp loại
        $sheet->getColumnDimension('L')->setWidth(15); // Tổng Nhiệm Vụ
        $sheet->getColumnDimension('M')->setWidth(15); // Tổng Nhiệm Vụ
        $sheet->getColumnDimension('N')->setWidth(15); // Tổng Nhiệm Vụ

        // Căn giữa các cột trong bảng
        $sheet->getStyle('A4:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:N' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:N' . $lastRow)->getAlignment()->setWrapText(true);

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

    private function calculateLeaderPoint($user, $month) {

        list($monthNumber, $year) = explode('/', $month);

        $totalPoint = 0;

        $count = 0;
        
        $evaluations = Evaluation::where('user_id', $user->id)
            ->whereMonth('start_date', $monthNumber)
            ->whereYear('start_date', $year)
            ->get()->toArray();
        
        if (empty($evaluations)) {
            return 0;
        }
        
        foreach ($evaluations as $evaluation) {

            foreach ($evaluation['statuses'] as $status) {

                $userEvaluation = $status['pivot']['user_id'];

                $infoUser = User::where('id', $userEvaluation)->first();
                
                if ($userEvaluation == $user->id || $infoUser->parent_id == 0 ||  $user->user_catalogues->level - $infoUser->user_catalogues->level > 1) {
                    continue;
                }
                
                $pointValue = $status['pivot']['point'];

                $totalPoint += $pointValue;
                
                if ($pointValue != 0) {

                    $count++;

                }
            }
        }
        
        return ($count > 0) ? round($totalPoint / $count, 2) : 0;
    }

    private function calculateApproverPoint($user, $month){
        list($monthNumber, $year) = explode('/', $month);
        $average = 0;
        $totalPoint = 0;
        $count = 0;
        $evaluations = Evaluation::where('user_id', $user->id)
            ->whereMonth('start_date', $monthNumber) 
            ->whereYear('start_date', $year) 
            ->get()->toArray();
        if(!$evaluations){
            return $average;
        }
        foreach ($evaluations as $evaluation) {
            foreach ($evaluation['statuses'] as $status) {
                $userEvaluation = $status['pivot']['user_id'];
                $infoUser = User::where('id', $userEvaluation)->first();
                if ($userEvaluation == $user->id || $user->user_catalogues->level - $infoUser->user_catalogues->level < 2) {
                    continue;
                }
                if ($status['pivot']['point'] > 0) {
                    $totalPoint += $status['pivot']['point'];
                    $count++;
                }
            }
        }
        return ($count > 0) ? round($totalPoint / $count, 2) : 0;
    }
}