<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card for <?php echo htmlspecialchars($reportCard['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 2.5em;
        }
        h2 {
            text-align: center;
            color: #555;
            font-size: 1.5em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        td {
            background-color: #f9f9f9;
        }
        .gwa {
            font-weight: bold;
            font-size: 1.5em;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Report Card</h1>
        <h2><?php echo htmlspecialchars($reportCard['name']); ?></h2>
        
        <table>
            <tr>
                <th>Subject</th>
                <th>Scores (%)</th>
            </tr>
            <?php foreach ($reportCard['subjects'] as $subject => $scores): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subject); ?></td>
                    <td>
                        <?php 
                            // Create a string of scores for display
                            $scoreStrings = [];
                            foreach ($scores as $score) {
                                $scoreStrings[] = "Assignment: " . htmlspecialchars($score['assignment_score']) . "%, Quiz: " . htmlspecialchars($score['quiz_score']) . "%, Exam: " . htmlspecialchars($score['exam_score']) . "%";
                            }
                            echo implode('<br>', $scoreStrings); // Join scores with line breaks for display
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p class="gwa">General Weighted Average (GWA): <?php echo htmlspecialchars($reportCard['gwa']); ?></p>
        
        <div class="footer">
            <p>School Name: Your School Name</p>
            <p>Address: Your School Address</p>
            <p>Date Issued: <?php echo date("Y-m-d"); ?></p>
        </div>
    </div>
</body>
</html>
