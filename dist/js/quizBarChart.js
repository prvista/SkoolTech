function createQuizBarChart(studentNames, quizScores, quizTitles, subjects) {
    var ctx = document.getElementById('quizBarChart').getContext('2d');

    var chartLabels = studentNames.map(function(name, index) {
        return name + ' (' + quizTitles[index] + ', ' + subjects[index] + ')';
    });

    var quizBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels, 
            datasets: [{
                label: 'Quiz Scores',
                data: quizScores,  
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return chartLabels[tooltipItem.dataIndex] + ": " + quizScores[tooltipItem.dataIndex] + "%";
                        }
                    }
                }
            }
        }
    });
}