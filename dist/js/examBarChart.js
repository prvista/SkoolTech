function createExamBarChart(studentNames, examScores, examTitles, subjects) {
    var ctxExam = document.getElementById('examBarChart').getContext('2d');

    var examChart = new Chart(ctxExam, {
        type: 'bar',
        data: {
            labels: <?php echo $examTitlesJson; ?>, // Titles of exams
            datasets: [{
                label: 'Scores',
                data: <?php echo $examScoresJson; ?>, // Scores of students
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true // Ensure the y-axis starts at 0
                }
            }
        }
    });
}
