// export_pdf.php
require_once 'vendor/autoload.php'; // If using Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// ... (your PHP logic to fetch results, similar to view_results.php) ...

// Instantiate Dompdf with options
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
$options->set('isRemoteEnabled', true); // Enable remote URLs (for images, fonts)
$dompdf = new Dompdf($options);

// Generate the HTML content for the PDF
$html = '<!DOCTYPE html><html><head><style>
            body { font-family: sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style></head><body>';
$html .= '<h1>Exam Results Report</h1>';
$html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
$html .= '<table><thead><tr><th>SL</th><th>Student Name</th><th>Exam Name</th><th>Score</th><th>Status</th></tr></thead><tbody>';

// Example data loop (replace with your actual results loop)
// $results_run = mysqli_query($connection_db, $your_results_query);
// while ($row = mysqli_fetch_assoc($results_run)) {
//     $html .= '<tr><td>' . $row['SL'] . '</td><td>' . $row['student_name'] . '</td><td>' . $row['exam_name'] . '</td><td>' . $row['score'] . '</td><td>' . $row['final_status'] . '</td></tr>';
// }
// For this example, let's just put dummy data:
$html .= '<tr><td>1</td><td>John Doe</td><td>PHP Quiz</td><td>85/100</td><td>Pass</td></tr>';
$html .= '<tr><td>2</td><td>Jane Smith</td><td>PHP Quiz</td><td>55/100</td><td>Fail</td></tr>';

$html .= '</tbody></table></body></html>';

$dompdf->loadHtml($html);

// (Optional) Set paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF (1 = download, 0 = preview)
$dompdf->stream("exam_results_report.pdf", array("Attachment" => 1));
exit();
