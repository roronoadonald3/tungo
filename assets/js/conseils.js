function appliquerConseil(index) {
    // This function would ideally trigger a more complex action,
    // like redirecting to the relevant page or opening a modal.
    // For now, it will just show an alert.
    alert(`Action pour le conseil ${index + 1} appliquée (simulation).`);
}

function ignorerConseil(index) {
    const conseilCard = document.querySelectorAll('.conseil-card')[index];
    if (conseilCard) {
        conseilCard.style.display = 'none';
    }
}
