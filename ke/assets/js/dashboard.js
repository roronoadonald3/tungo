document.addEventListener('DOMContentLoaded', () => {
    // Last simulation chart
    const lastSimChartCanvas = document.getElementById('last-simulation-chart');
    if (lastSimChartCanvas && typeof simulationData !== 'undefined') {
        const ctx = lastSimChartCanvas.getContext('2d');
        const chartData = {
            labels: Object.keys(simulationData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
            datasets: [{
                data: Object.values(simulationData),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            }]
        };
        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                }
            }
        });
    }

    // Mark advice as read
    const markReadButtons = document.querySelectorAll('.conseil-mark-read');
    markReadButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const card = button.closest('.conseil-card');
            const conseilId = card.dataset.conseilId;

            try {
                const response = await fetch('/api/simulation.php', { // This API endpoint doesn't exist yet
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_read', conseil_id: conseilId })
                });
                const result = await response.json();
                if (result.success) {
                    card.style.opacity = '0.5';
                    button.remove();
                } else {
                    alert('Erreur lors de la mise à jour.');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            }
        });
    });
});
