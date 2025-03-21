<?php

namespace App\Exports\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfEvaluationExport
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
        // Tạo HTML content
        $html = $this->generateHtml();

        // Cấu hình Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // Font hỗ trợ tiếng Việt

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Đặt khổ giấy A4, ngang để bảng rộng hơn
        $dompdf->render();

        // Tạo thư mục tạm trong public nếu chưa tồn tại
        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Đặt tên file
        $user = $this->evaluationList;
        $date = str_replace('/', '_', $this->month);
        $filename = "pdf_evaluation_report_{$date}_" . ($user->account ?? 'unknown') . ".pdf";
        $temp_file = $tempDir . '/' . $filename;

        // Lưu file PDF
        file_put_contents($temp_file, $dompdf->output());

        return $temp_file;
    }

    protected function generateHtml()
    {
        $user = $this->evaluationList;
        $userName = $user->name ?? 'Không xác định';
        $department = ($user->teams && $user->units) ? $user->teams->name . ' - ' . $user->units->name : 'Không xác định';
        $evaluations = $this->evaluationList->evaluations;

        // Tạo HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 13pt;
                    line-height: 1.5;
                }
                h1, h2, h3 {
                    text-align: center;
                    margin: 5px 0;
                }
                p {
                    margin: 5px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    border: 1px solid black;
                    padding: 5px;
                    text-align: center;
                    vertical-align: middle;
                    word-wrap: break-word;
                }
                th {
                    font-weight: bold;
                }
                .italic {
                    font-style: italic;
                }
                .bold {
                    font-weight: bold;
                }
                .row-flex {
                    display: flex;
                    justify-content: space-between;
                }
                .row-flex div {
                    width: 33%;
                }
                .table-header {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                .table-header th {
                    border: 1px solid black;
                    padding: 5px;
                    text-align: center;
                    vertical-align: middle;
                    word-wrap: break-word;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <h1>CỤC HẢI QUAN TỈNH HÀ TĨNH</h1>
            <h2>CHI CỤC HẢI QUAN CỬA KHẨU QUỐC TẾ CẦU TREO</h2>
            <p class="italic">Phụ lục I</p>
            <h1 class="bold">PHIẾU TỰ ĐÁNH GIÁ CÔNG VIỆC HÀNG THÁNG</h1>
            <p class="italic">Tháng ' . htmlspecialchars($this->month) . '</p>
            <p class="italic">(Dùng cho công chức không giữ chức vụ lãnh đạo)</p>

            <p>1. Họ và tên: ' . htmlspecialchars($userName) . '</p>
            <p>2. Vị trí, đơn vị công tác: ' . htmlspecialchars($department) . '</p>
            <p>3. Số ngày làm việc theo quy định của pháp luật lao động trong tháng: ' . htmlspecialchars($this->formData['working_days_in_month']) . '</p>
            <p>4. Số ngày nghỉ trong tháng (có phép): ' . htmlspecialchars($this->formData['leave_days_with_permission']) . '</p>
            <p>5. Số ngày nghỉ trong tháng (không phép): ' . htmlspecialchars($this->formData['leave_days_without_permission']) . '</p>
            <div class="row-flex">
                <div>6. Số lần vi phạm quy chế, quy định: ' . htmlspecialchars($this->formData['violation_count']) . '</div>
                <div>7. Hành vi vi phạm: ' . htmlspecialchars($this->formData['violation_behavior']) . '</div>
                <div>8. Hình thức kỷ luật: ' . htmlspecialchars($this->formData['disciplinary_action']) . '</div>
            </div>
            <p>9. Bảng kê chi tiết công việc:</p>

            <!-- Tiêu đề bảng (chỉ hiển thị một lần) -->
            <table class="table-header">
                <tr>
                    <th style="width: 5%">STT</th>
                    <th style="width: 15%">Nội dung công việc</th>
                    <th style="width: 7%">Ngày</th>
                    <th style="width: 7%">Ngày giao việc</th>
                    <th style="width: 7%">Thời gian hoàn thành</th>
                    <th style="width: 7%">Thời gian thực tế thực hiện công việc</th>
                    <th style="width: 10%">Sản phẩm đầu ra</th>
                    <th style="width: 10%">Cá nhân tự đánh giá</th>
                    <th style="width: 10%">Lãnh đạo trực tiếp đánh giá</th>
                    <th style="width: 7%">Tên lãnh đạo trực tiếp đánh giá</th>
                    <th style="width: 10%">Lãnh đạo phê duyệt</th>
                    <th style="width: 7%">Tên lãnh đạo phê duyệt</th>
                    <th style="width: 5%">Ghi chú</th>
                </tr>
            </table>

            <!-- Bảng dữ liệu (không có tiêu đề, để tránh lặp lại) -->
            <table>
                <tbody>';

        // Dữ liệu bảng
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
            $completionDateFormatted = $evaluation->completion_date ? \Carbon\Carbon::parse($evaluation->completion_date)->format('d/m/Y') : '';

            // Thời gian thực tế thực hiện công việc
            $actualDays = $evaluation->completion_date ?? '';

            $html .= '
                <tr>
                    <td style="width: 5%">' . $index++ . '</td>
                    <td style="width: 15%">' . htmlspecialchars($taskName) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($startDateFormatted) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($startDateFormatted) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($dueDateFormatted) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($actualDays) . '</td>
                    <td style="width: 10%">' . htmlspecialchars($evaluation->output ?? 'Không xác định') . '</td>
                    <td style="width: 10%">' . htmlspecialchars($selfRating) . '</td>
                    <td style="width: 10%">' . htmlspecialchars($leaderRating) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($leaderName) . '</td>
                    <td style="width: 10%">' . htmlspecialchars($approvalRating) . '</td>
                    <td style="width: 7%">' . htmlspecialchars($approverName) . '</td>
                    <td style="width: 5%"></td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <p style="margin-top: 20px;">10. Kết quả xếp loại chất lượng tháng:</p>
            <p>Cán bộ lập phiếu</p>
        </body>
        </html>';

        return $html;
    }
}