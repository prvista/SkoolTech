function createGradeDistributionLineChart(gradeDistribution) {
    const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
    const labels = Object.keys(gradeDistribution);
    const data = Object.values(gradeDistribution);

    const myChart = new Chart(ctx, {
        type: 'line', // or 'bar', 'pie', etc.
        data: {
            labels: labels,
            datasets: [{
                label: 'Grade Distribution',
                data: data,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 3
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
