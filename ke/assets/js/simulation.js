document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('simulation-form');
    if (!form) return;

    const steps = Array.from(form.querySelectorAll('.form-step'));
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const progressSteps = Array.from(document.querySelectorAll('.progress-step'));
    const saveBtn = document.getElementById('save-simulation');
    const newSimBtn = document.getElementById('new-simulation');

    let currentStep = 0;
    let budgetChart = null;

    const updateFormStep = () => {
        steps.forEach((step, index) => {
            step.classList.toggle('active', index === currentStep);
        });

        progressSteps.forEach((step, index) => {
            if (index < currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index === currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });

        prevBtn.style.display = currentStep > 0 ? 'inline-block' : 'none';
        nextBtn.style.display = currentStep < steps.length - 1 ? 'inline-block' : 'none';
    };

    const validateStep = () => {
        const currentStepElement = steps[currentStep];
        const inputs = currentStepElement.querySelectorAll('input[required]');
        for (const input of inputs) {
            if (!input.value) {
                alert('Veuillez remplir tous les champs requis.');
                return false;
            }
        }
        return true;
    };

    nextBtn.addEventListener('click', () => {
        if (validateStep() && currentStep < steps.length - 1) {
            currentStep++;
            if (currentStep === steps.length - 1) {
                calculateResults();
            }
            updateFormStep();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateFormStep();
        }
    });

    // Step 2: Profile selection
    const profileCards = document.querySelectorAll('.profile-card');
    profileCards.forEach(card => {
        card.addEventListener('click', () => {
            profileCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('profil').value = card.dataset.profile;
        });
    });

    // Step 3: Repartition mode selection
    const repartitionOptions = document.querySelectorAll('.option-card');
    const manualRepartition = document.getElementById('repartition-manuelle');
    repartitionOptions.forEach(card => {
        card.addEventListener('click', () => {
            repartitionOptions.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const mode = card.dataset.mode;
            document.getElementById('mode').value = mode;
            manualRepartition.style.display = mode === 'manuel' ? 'block' : 'none';
        });
    });

    // Manual repartition sliders
    const sliders = document.querySelectorAll('.budget-slider');
    const totalPercentage = document.getElementById('total-percentage');
    sliders.forEach(slider => {
        slider.addEventListener('input', () => {
            const valueSpan = slider.nextElementSibling;
            valueSpan.textContent = `${slider.value}%`;
            updateTotalPercentage();
        });
    });

    const updateTotalPercentage = () => {
        let total = 0;
        sliders.forEach(s => total += parseInt(s.value));
        totalPercentage.textContent = `${total}%`;
        if (total !== 100) {
            totalPercentage.parentElement.classList.add('error');
        } else {
            totalPercentage.parentElement.classList.remove('error');
        }
    };

    // Step 4: Calculate and display results
    const calculateResults = () => {
        const revenu = parseFloat(document.getElementById('revenu').value);
        const mode = document.getElementById('mode').value;
        const profil = document.getElementById('profil').value;
        let repartition = {};

        if (mode === 'manuel') {
            sliders.forEach(slider => {
                repartition[slider.name] = parseInt(slider.value);
            });
        } else { // Automatic
            switch (profil) {
                case 'irregulier':
                    repartition = { logement: 25, alimentation: 20, transport: 10, sante: 10, loisirs: 10, epargne: 25 };
                    break;
                case 'mixte':
                    repartition = { logement: 30, alimentation: 25, transport: 15, sante: 10, loisirs: 10, epargne: 10 };
                    break;
                case 'regulier':
                default:
                    repartition = { logement: 35, alimentation: 20, transport: 15, sante: 10, loisirs: 10, epargne: 10 };
                    break;
            }
        }

        // Update results table
        for (const key in repartition) {
            const amount = (revenu * repartition[key] / 100).toFixed(0);
            document.getElementById(`${key}-amount`).textContent = `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`;
            document.getElementById(`${key}-percent`).textContent = `${repartition[key]}%`;
        }

        // Update chart
        const ctx = document.getElementById('budget-chart').getContext('2d');
        const chartData = {
            labels: Object.keys(repartition).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
            datasets: [{
                data: Object.values(repartition),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            }]
        };
        if (budgetChart) {
            budgetChart.destroy();
        }
        budgetChart = new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                }
            }
        });
    };

    // Save simulation
    saveBtn.addEventListener('click', async () => {
        const revenu = parseFloat(document.getElementById('revenu').value);
        const profil = document.getElementById('profil').value;
        const mode = document.getElementById('mode').value;
        let repartition = {};
        sliders.forEach(slider => {
            repartition[slider.name] = parseInt(slider.value);
        });

        const data = {
            action: 'save_simulation',
            data: { revenu, profil, mode, repartition }
        };

        try {
            const response = await fetch('/api/simulation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                alert('Simulation sauvegardée avec succès !');
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                alert(`Erreur: ${result.message}`);
                if (result.message.includes('non connecté')) {
                    window.location.href = 'login.php?redirect=simulation.php';
                }
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        }
    });

    // New simulation
    newSimBtn.addEventListener('click', () => {
        currentStep = 0;
        form.reset();
        profileCards.forEach(c => c.classList.remove('selected'));
        repartitionOptions.forEach(c => c.classList.remove('selected'));
        manualRepartition.style.display = 'none';
        if (budgetChart) budgetChart.destroy();
        updateFormStep();
    });

    updateFormStep();
});
