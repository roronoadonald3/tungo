document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('add-objectif-btn');
    const addFirstBtn = document.getElementById('add-first-objectif');
    const formSection = document.getElementById('objectif-form-section');
    const form = document.getElementById('objectif-form');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const objectifIdInput = document.getElementById('objectif-id');
    const cancelBtn = document.getElementById('cancel-btn');
    const closeFormBtn = document.getElementById('close-form');

    const deleteModal = document.getElementById('delete-modal');
    const closeDeleteModal = document.getElementById('close-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const objectifToDelete = document.getElementById('objectif-to-delete');
    const deleteObjectifIdInput = document.getElementById('delete-objectif-id');

    const progressModal = document.getElementById('progress-modal');
    const closeProgressModal = document.getElementById('close-progress-modal');
    const cancelProgressBtn = document.getElementById('cancel-progress');
    const progressForm = document.getElementById('progress-form');
    const progressObjectifIdInput = document.getElementById('progress-objectif-id');

    const showForm = (isEdit = false, data = null) => {
        form.reset();
        if (isEdit && data) {
            formTitle.textContent = 'Modifier un objectif';
            formAction.value = 'update';
            objectifIdInput.value = data.id;
            form.nom.value = data.nom;
            form.type_objectif.value = data.type_objectif;
            form.montant_cible.value = data.montant_cible;
            form.pourcentage_cible.value = data.pourcentage_cible;
            form.date_limite.value = data.date_limite;
        } else {
            formTitle.textContent = 'Nouvel objectif';
            formAction.value = 'add';
            objectifIdInput.value = '';
        }
        formSection.style.display = 'block';
    };

    const hideForm = () => {
        formSection.style.display = 'none';
        form.reset();
    };

    if (addBtn) addBtn.addEventListener('click', () => showForm());
    if (addFirstBtn) addFirstBtn.addEventListener('click', () => showForm());
    if (cancelBtn) cancelBtn.addEventListener('click', hideForm);
    if (closeFormBtn) closeFormBtn.addEventListener('click', hideForm);

    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.objectif);
            showForm(true, data);
        });
    });

    // Delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => {
            const objectifId = button.dataset.objectifId;
            const card = button.closest('.objectif-card');
            const name = card.querySelector('h3').textContent;
            
            objectifToDelete.textContent = name.trim();
            deleteObjectifIdInput.value = objectifId;
            deleteModal.style.display = 'block';
        });
    });

    const hideDeleteModal = () => {
        if (deleteModal) deleteModal.style.display = 'none';
    };

    if (closeDeleteModal) closeDeleteModal.addEventListener('click', hideDeleteModal);
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', hideDeleteModal);

    // Progress update buttons
    document.querySelectorAll('.progress-btn').forEach(button => {
        button.addEventListener('click', () => {
            const objectifId = button.dataset.objectifId;
            progressObjectifIdInput.value = objectifId;
            progressModal.style.display = 'block';
        });
    });

    const hideProgressModal = () => {
        if (progressModal) progressModal.style.display = 'none';
    };

    if (closeProgressModal) closeProgressModal.addEventListener('click', hideProgressModal);
    if (cancelProgressBtn) cancelProgressBtn.addEventListener('click', hideProgressModal);

    // Filtering
    const statusFilter = document.getElementById('status-filter');
    const typeFilter = document.getElementById('type-filter');
    const objectifsGrid = document.getElementById('objectifs-grid');

    const filterObjectifs = () => {
        if (!objectifsGrid) return;
        const selectedStatus = statusFilter.value;
        const selectedType = typeFilter.value;

        objectifsGrid.querySelectorAll('.objectif-card').forEach(card => {
            const statusMatch = !selectedStatus || card.dataset.status === selectedStatus;
            const typeMatch = !selectedType || card.dataset.type === selectedType;
            card.style.display = statusMatch && typeMatch ? 'block' : 'none';
        });
    };

    if (statusFilter) statusFilter.addEventListener('change', filterObjectifs);
    if (typeFilter) typeFilter.addEventListener('change', filterObjectifs);
});
