document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('add-revenu-btn');
    const addFirstBtn = document.getElementById('add-first-revenu');
    const formSection = document.getElementById('revenu-form-section');
    const form = document.getElementById('revenu-form');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const revenuIdInput = document.getElementById('revenu-id');
    const cancelBtn = document.getElementById('cancel-btn');
    const closeFormBtn = document.getElementById('close-form');

    const deleteModal = document.getElementById('delete-modal');
    const closeDeleteModal = document.getElementById('close-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const deleteForm = document.getElementById('delete-form');
    const revenuToDelete = document.getElementById('revenu-to-delete');
    const deleteRevenuIdInput = document.getElementById('delete-revenu-id');

    const showForm = (isEdit = false, data = null) => {
        form.reset();
        if (isEdit && data) {
            formTitle.textContent = 'Modifier un revenu';
            formAction.value = 'update';
            revenuIdInput.value = data.id;
            form.montant.value = data.montant;
            form.type_revenu.value = data.type_revenu;
            form.date_revenu.value = data.date_revenu;
            form.description.value = data.description || '';
        } else {
            formTitle.textContent = 'Ajouter un revenu';
            formAction.value = 'add';
            revenuIdInput.value = '';
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
            const data = JSON.parse(button.dataset.revenu);
            showForm(true, data);
        });
    });

    // Delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => {
            const revenuId = button.dataset.revenuId;
            const card = button.closest('.revenu-card');
            const type = card.querySelector('h4').textContent;
            const amount = card.querySelector('.revenu-amount').textContent;
            
            revenuToDelete.textContent = `${type.trim()} - ${amount.trim()}`;
            deleteRevenuIdInput.value = revenuId;
            deleteModal.style.display = 'block';
        });
    });

    const hideDeleteModal = () => {
        deleteModal.style.display = 'none';
    };

    if (closeDeleteModal) closeDeleteModal.addEventListener('click', hideDeleteModal);
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', hideDeleteModal);

    // Filtering
    const typeFilter = document.getElementById('type-filter');
    const dateFilter = document.getElementById('date-filter');
    const revenusList = document.getElementById('revenus-list');

    const filterRevenus = () => {
        if (!revenusList) return;
        const selectedType = typeFilter.value;
        const selectedDate = dateFilter.value;

        revenusList.querySelectorAll('.revenu-card').forEach(card => {
            const typeMatch = !selectedType || card.dataset.type === selectedType;
            const dateMatch = !selectedDate || card.dataset.date === selectedDate;
            card.style.display = typeMatch && dateMatch ? 'flex' : 'none';
        });
    };

    if (typeFilter) typeFilter.addEventListener('change', filterRevenus);
    if (dateFilter) dateFilter.addEventListener('change', filterRevenus);
});
