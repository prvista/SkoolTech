<?php
require_once('tcpdf/tcpdf.php');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'professor') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['download_pdf'])) {
    $student_id = $_POST['student_id'];

    // Fetch student report card data
    $sql_student_report = "SELECT s.name, s.student_number, ss.subject, ss.assignment_score, ss.quiz_score, ss.exam_score 
    FROM students s 
    JOIN subject_scores ss ON s.id = ss.student_id 
    WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql_student_report);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result_student_report = $stmt->get_result();

    // Initialize report card data
    $reportCard = [
        'name' => '',
        'student_number' => '',
        'subjects' => [],
        'gwa' => 0
    ];

    if ($result_student_report->num_rows > 0) {
        while ($row = $result_student_report->fetch_assoc()) {
            if (empty($reportCard['name'])) {
                $reportCard['name'] = $row['name'];
                $reportCard['student_number'] = $row['student_number'];
            }

            $reportCard['subjects'][$row['subject']][] = [
                'assignment_score' => $row['assignment_score'],
                'quiz_score' => $row['quiz_score'],
                'exam_score' => $row['exam_score']
            ];
        }
    }

    // Calculate GWA
    if (!empty($reportCard['subjects'])) {
        $totalScore = 0;
        $totalSubjects = 0;

        foreach ($reportCard['subjects'] as $subject => $scores) {
            $assignmentAvg = !empty($scores) ? array_sum(array_column($scores, 'assignment_score')) / count($scores) : 0;
            $quizAvg = !empty($scores) ? array_sum(array_column($scores, 'quiz_score')) / count($scores) : 0;
            $examAvg = !empty($scores) ? array_sum(array_column($scores, 'exam_score')) / count($scores) : 0;

            $averageScore = ($assignmentAvg + $quizAvg + $examAvg) / 3;
            $totalScore += $averageScore;
            $totalSubjects++;
        }

        $reportCard['gwa'] = $totalSubjects > 0 ? round($totalScore / $totalSubjects, 2) : 0;
    }

    // Create PDF
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->AddPage();

    // Image path
    $imageFile = 'C:\Users\vista\Downloads\img'; // Ensure this path is correct
    $pdf->Image($imageFile, 10, 10, 30, 30); // Image at (10, 10) position with 30x30 size

    // Set title and student information
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Laguna College', 0, 1, 'C'); // Title centered
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, $reportCard['name'] . "'s Report Card", 0, 1, 'C');
    $pdf->Cell(0, 10, 'Student Number: ' . $reportCard['student_number'], 0, 1, 'C');

    // Table header
    $pdf->SetFont('helvetica', 'B', 12);
    $colWidths = [60, 60, 60, 60]; // Column widths
    $totalWidth = array_sum($colWidths);
    $x = ($pdf->getPageWidth() - $totalWidth) / 2; // Center position
    $pdf->SetX($x); // Set x position for the table
    $pdf->Cell($colWidths[0], 10, 'Subject', 1, 0, 'C');
    $pdf->Cell($colWidths[1], 10, 'Assignment Score', 1, 0, 'C');
    $pdf->Cell($colWidths[2], 10, 'Quiz Score', 1, 0, 'C');
    $pdf->Cell($colWidths[3], 10, 'Exam Score', 1, 1, 'C'); // New line after the header

    // Populate the table with scores
    $pdf->SetFont('helvetica', '', 12);
    foreach ($reportCard['subjects'] as $subject => $scores) {
        $assignmentAvg = !empty($scores) ? round(array_sum(array_column($scores, 'assignment_score')) / count($scores), 2) : 'N/A';
        $quizAvg = !empty($scores) ? round(array_sum(array_column($scores, 'quiz_score')) / count($scores), 2) : 'N/A';
        $examAvg = !empty($scores) ? round(array_sum(array_column($scores, 'exam_score')) / count($scores), 2) : 'N/A';

        $pdf->SetX($x); // Reset x position for each row
        $pdf->Cell($colWidths[0], 10, $subject, 1);
        $pdf->Cell($colWidths[1], 10, $assignmentAvg, 1);
        $pdf->Cell($colWidths[2], 10, $quizAvg, 1);
        $pdf->Cell($colWidths[3], 10, $examAvg, 1);
        $pdf->Ln();
    }

    // Add GWA and date issued below the table
    $pdf->Ln(5); // Space before GWA
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetX($x); // Center GWA
    $pdf->Cell(0, 10, 'GWA: ' . $reportCard['gwa'], 0, 1, 'C'); // Centered GWA
    $pdf->Ln(0); // Space before the issued date
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->SetX($x); // Center issued date
    $pdf->Cell(0, 10, 'Date Issued: ' . date('Y-m-d'), 0, 0, 'C'); // Centered issued date

    $pdf->Output('report_card_' . $student_id . '.pdf', 'D');

}
?>
