document.addEventListener('DOMContentLoaded', () => {
    const sliders = document.querySelectorAll('.budget-slider');
    const totalValue = document.getElementById('total-value');
    const totalStatus = document.getElementById('total-status');
    const saveBtn = document.getElementById('save-btn');
    const resetBtn = document.getElementById('reset-btn');
    const chartCanvas = document.getElementById('repartitionChart');
    let repartitionChart = null;

    const initialValues = {};
    sliders.forEach(slider => {
        initialValues[slider.id] = slider.value;
    });

    const updateSliderValues = () => {
        let total = 0;
        sliders.forEach(slider => {
            const valueDisplay = document.getElementById(`${slider.id}-value`);
            valueDisplay.textContent = `${slider.value}%`;
            total += parseInt(slider.value);
        });

        totalValue.textContent = `${total}%`;
        if (total === 100) {
            totalStatus.textContent = '✓ Équilibré';
            totalStatus.style.color = 'var(--success-color)';
            saveBtn.disabled = false;
        } else {
            totalStatus.textContent = `✗ Déséquilibré (${total - 100}%)`;
            totalStatus.style.color = 'var(--danger-color)';
            saveBtn.disabled = true;
        }
        updateChart();
    };

    const updateChart = () => {
        const labels = Array.from(sliders).map(s => s.previousElementSibling.textContent.trim().split('\n')[0]);
        const data = Array.from(sliders).map(s => s.value);

        if (repartitionChart) {
            repartitionChart.data.datasets[0].data = data;
            repartitionChart.update();
        } else {
            const ctx = chartCanvas.getContext('2d');
            repartitionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                    }
                }
            });
        }
    };

    sliders.forEach(slider => {
        slider.addEventListener('input', updateSliderValues);
    });

    resetBtn.addEventListener('click', () => {
        sliders.forEach(slider => {
            slider.value = initialValues[slider.id];
        });
        updateSliderValues();
    });

    saveBtn.addEventListener('click', async () => {
        const repartition = {};
        sliders.forEach(slider => {
            repartition[slider.id] = slider.value;
        });

        try {
            const response = await fetch('/api/simulation.php', { // This API endpoint doesn't exist yet
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_repartition', data: repartition })
            });
            const result = await response.json();
            if (result.success) {
                alert('Répartition sauvegardée avec succès !');
            } else {
                alert(`Erreur: ${result.message}`);
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue.');
        }
    });

    // Apply suggestion buttons
    document.querySelectorAll('.btn-apply').forEach(button => {
        button.addEventListener('click', () => {
            const profile = button.dataset.profile;
            let suggestion = {};
            switch (profile) {
                case 'conservateur':
                    suggestion = { logement: 25, alimentation: 20, transport: 15, sante: 10, education: 5, loisirs: 5, epargne: 20 };
                    break;
                case 'equilibre':
                    suggestion = { logement: 30, alimentation: 25, transport: 15, sante: 10, education: 5, loisirs: 5, epargne: 10 };
                    break;
                case 'dynamique':
                    suggestion = { logement: 35, alimentation: 30, transport: 15, sante: 5, education: 5, loisirs: 10, epargne: 0 };
                    break;
            }
            sliders.forEach(slider => {
                slider.value = suggestion[slider.id] || 0;
            });
            updateSliderValues();
        });
    });

    updateSliderValues();
});
