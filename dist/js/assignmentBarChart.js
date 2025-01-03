function createAssignmentBarChart(studentNames, assignmentScores, assignmentTitles, subjects) {
    var ctx = document.getElementById('assignmentBarChart').getContext('2d');

    var chartLabels = studentNames.map(function (name, index) {
        return name + ' (' + assignmentTitles[index] + ', ' + subjects[index] + ')';
    });

    var assignmentBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Assignment Scores',
                data: assignmentScores,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
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
                        label: function (tooltipItem) {
                            return chartLabels[tooltipItem.dataIndex] + ": " + assignmentScores[tooltipItem.dataIndex] + "%";
                        }
                    }
                }
            }
        }
    });
}
