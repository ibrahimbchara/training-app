// Global variables
let currentSection = 'people';
let people = [];
let trainings = [];
let currentTrainingId = null;
let currentPersonId = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing PHP app...');
    
    // Check if essential elements exist
    const essentialElements = [
        'people-section', 'trainings-section', 'daily-section', 'history-section',
        'nav-people', 'nav-trainings', 'nav-daily', 'nav-history',
        'add-person-form', 'add-training-form', 'add-progress-form', 'update-weight-form'
    ];
    
    const missingElements = essentialElements.filter(id => !document.getElementById(id));
    if (missingElements.length > 0) {
        console.error('Missing essential elements:', missingElements);
        alert('Page not fully loaded. Please refresh the page.');
        return;
    }
    
    // Initialize the app
    try {
        // Check URL hash for current section, default to 'people'
        const currentHash = window.location.hash.substring(1) || 'people';
        showSection(currentHash);
        loadPeople();
        loadTrainings();
        updateCurrentDate();
        
        // Set up form event listeners
        document.getElementById('add-person-form').addEventListener('submit', handleAddPerson);
        document.getElementById('add-training-form').addEventListener('submit', handleAddTraining);
        document.getElementById('edit-training-form').addEventListener('submit', handleEditTraining);
        document.getElementById('add-progress-form').addEventListener('submit', handleAddProgress);
        document.getElementById('update-weight-form').addEventListener('submit', handleUpdateWeight);
        
        console.log('PHP app initialized successfully');
    } catch (error) {
        console.error('Error initializing app:', error);
        alert('Error loading the application. Please refresh the page.');
    }
});

// Handle browser back/forward buttons
window.addEventListener('hashchange', function() {
    const section = window.location.hash.substring(1) || 'people';
    if (['people', 'trainings', 'daily', 'history'].includes(section)) {
        showSection(section);
    }
});

// Navigation function for buttons
function navigateToSection(section) {
    showSection(section);
}

// Navigation
function showSection(section) {
    // Validate section
    const validSections = ['people', 'trainings', 'daily', 'history'];
    if (!validSections.includes(section)) {
        console.warn(`Invalid section: ${section}, defaulting to 'people'`);
        section = 'people';
    }

    // Check if DOM elements exist
    const sectionElement = document.getElementById(section + '-section');
    const navButton = document.getElementById('nav-' + section);

    if (!sectionElement || !navButton) {
        console.error(`Section or navigation button not found: ${section}`);
        return;
    }
    
    // Hide all sections
    document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
    
    // Show selected section
    sectionElement.classList.remove('hidden');
    
    // Update navigation buttons
    document.querySelectorAll('nav button').forEach(btn => {
        btn.classList.remove('bg-gray-300');
        btn.classList.add('bg-white');
    });
    navButton.classList.remove('bg-white');
    navButton.classList.add('bg-gray-300');
    
    currentSection = section;

    // Update URL hash without triggering page reload
    window.history.replaceState(null, null, `#${section}`);

    // Load section-specific data
    if (section === 'daily') {
        loadDailyTrainings();
    } else if (section === 'history') {
        updateHistoryPeople();
    }
}

// API calls for PHP backend
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    // Add endpoint as a query parameter for PHP routing
    const url = `api.php?endpoint=${encodeURIComponent(endpoint)}&method=${method}`;

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        alert('An error occurred. Please try again.');
        throw error;
    }
}

// People management
async function loadPeople() {
    try {
        people = await apiCall('/people');
        renderPeopleTable();
        updateParticipantsList();
        updateHistoryPeople();
    } catch (error) {
        console.error('Failed to load people:', error);
    }
}

function renderPeopleTable() {
    const tbody = document.getElementById('people-table');
    if (!tbody) {
        console.error('People table element not found');
        return;
    }

    tbody.innerHTML = '';

    people.forEach(person => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="p-4 font-medium">${person.name}</td>
            <td class="p-4">${person.age}</td>
            <td class="p-4">${person.height}</td>
            <td class="p-4">${person.current_weight || 'Not set'}</td>
            <td class="p-4 space-x-2">
                <button onclick="showUpdateWeightModal(${person.id}, '${person.name}')"
                        class="bg-black text-white px-3 py-1 rounded text-sm hover:bg-gray-800 transition">
                    Update Weight
                </button>
                <button onclick="deletePerson(${person.id}, '${person.name}')"
                        class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                    Delete
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showAddPersonModal() {
    const modal = document.getElementById('add-person-modal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        console.error('Add person modal not found');
    }
}

function hideAddPersonModal() {
    const modal = document.getElementById('add-person-modal');
    const form = document.getElementById('add-person-form');
    
    if (modal) modal.classList.add('hidden');
    if (form) form.reset();
}

async function handleAddPerson(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('person-name').value,
        age: parseInt(document.getElementById('person-age').value),
        height: parseFloat(document.getElementById('person-height').value),
        weight: parseFloat(document.getElementById('person-weight').value)
    };
    
    try {
        await apiCall('/people', 'POST', formData);
        hideAddPersonModal();
        loadPeople();
    } catch (error) {
        console.error('Failed to add person:', error);
    }
}

function showUpdateWeightModal(personId, personName) {
    currentPersonId = personId;
    document.getElementById('weight-person-name').value = personName;
    document.getElementById('update-weight-modal').classList.remove('hidden');
}

function hideUpdateWeightModal() {
    document.getElementById('update-weight-modal').classList.add('hidden');
    document.getElementById('update-weight-form').reset();
    currentPersonId = null;
}

async function handleUpdateWeight(e) {
    e.preventDefault();

    const weight = parseFloat(document.getElementById('new-weight').value);

    try {
        await apiCall(`/people/${currentPersonId}/weight`, 'PUT', { weight });
        hideUpdateWeightModal();
        loadPeople();
    } catch (error) {
        console.error('Failed to update weight:', error);
    }
}

async function deletePerson(personId, personName) {
    if (confirm(`Are you sure you want to delete ${personName}? This will remove all their training data and cannot be undone.`)) {
        try {
            await apiCall(`/people/${personId}`, 'DELETE');
            loadPeople();
            loadTrainings(); // Refresh trainings as participant counts may change
        } catch (error) {
            console.error('Failed to delete person:', error);
            alert('Failed to delete person. Please try again.');
        }
    }
}

// Training management
async function loadTrainings() {
    try {
        trainings = await apiCall('/trainings');
        renderTrainingsGrid();
    } catch (error) {
        console.error('Failed to load trainings:', error);
    }
}

function renderTrainingsGrid() {
    const grid = document.getElementById('trainings-grid');
    grid.innerHTML = '';

    trainings.forEach(training => {
        const card = document.createElement('div');
        card.className = 'bg-white border-2 border-black rounded-lg p-6 shadow-lg';
        card.innerHTML = `
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <h3 class="text-xl font-bold mb-2">${training.name}</h3>
                    <p class="text-gray-600 mb-2">Daily Target: ${training.daily_target} reps</p>
                    <p class="text-gray-600 mb-2">Participants: ${training.participant_count || 0}</p>
                    <p class="text-sm text-gray-500">${training.participants || 'No participants'}</p>
                </div>
                <div class="flex space-x-2 ml-4">
                    <button onclick="showEditTrainingModal(${training.id})"
                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                        Edit
                    </button>
                    <button onclick="deleteTraining(${training.id}, '${training.name}')"
                            class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                        Delete
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

function updateParticipantsList() {
    const list = document.getElementById('participants-list');
    list.innerHTML = '';
    
    people.forEach(person => {
        const div = document.createElement('div');
        div.className = 'flex items-center mb-2';
        div.innerHTML = `
            <input type="checkbox" id="participant-${person.id}" value="${person.id}" class="mr-2">
            <label for="participant-${person.id}" class="text-sm">${person.name}</label>
        `;
        list.appendChild(div);
    });
}

function showAddTrainingModal() {
    updateParticipantsList();
    document.getElementById('add-training-modal').classList.remove('hidden');
}

function hideAddTrainingModal() {
    document.getElementById('add-training-modal').classList.add('hidden');
    document.getElementById('add-training-form').reset();
}

async function handleAddTraining(e) {
    e.preventDefault();

    const participants = Array.from(document.querySelectorAll('#participants-list input:checked'))
        .map(cb => parseInt(cb.value));

    const formData = {
        name: document.getElementById('training-type').value,
        daily_target: parseInt(document.getElementById('training-target').value),
        participants: participants
    };

    try {
        await apiCall('/trainings', 'POST', formData);
        hideAddTrainingModal();
        loadTrainings();
    } catch (error) {
        console.error('Failed to add training:', error);
    }
}

// Edit training functions
let currentEditTrainingId = null;

async function showEditTrainingModal(trainingId) {
    currentEditTrainingId = trainingId;

    // Get training details
    const training = trainings.find(t => t.id == trainingId);
    if (!training) {
        console.error('Training not found');
        return;
    }

    // Populate form with current values
    document.getElementById('edit-training-type').value = training.name;
    document.getElementById('edit-training-target').value = training.daily_target;

    // Load participants with current selections
    await loadEditParticipantsList(trainingId);

    document.getElementById('edit-training-modal').classList.remove('hidden');
}

function hideEditTrainingModal() {
    document.getElementById('edit-training-modal').classList.add('hidden');
    document.getElementById('edit-training-form').reset();
    currentEditTrainingId = null;
}

async function loadEditParticipantsList(trainingId) {
    try {
        const participants = await apiCall(`/trainings/${trainingId}/participants`);
        const list = document.getElementById('edit-participants-list');
        list.innerHTML = '';

        participants.forEach(person => {
            const div = document.createElement('div');
            div.className = 'flex items-center mb-2';
            div.innerHTML = `
                <input type="checkbox" id="edit-participant-${person.id}" value="${person.id}"
                       ${person.is_participant ? 'checked' : ''} class="mr-2">
                <label for="edit-participant-${person.id}" class="text-sm">${person.name}</label>
            `;
            list.appendChild(div);
        });
    } catch (error) {
        console.error('Failed to load participants for editing:', error);
    }
}

async function handleEditTraining(e) {
    e.preventDefault();

    const participants = Array.from(document.querySelectorAll('#edit-participants-list input:checked'))
        .map(cb => parseInt(cb.value));

    const formData = {
        name: document.getElementById('edit-training-type').value,
        daily_target: parseInt(document.getElementById('edit-training-target').value),
        participants: participants
    };

    try {
        await apiCall(`/trainings/${currentEditTrainingId}`, 'PUT', formData);
        hideEditTrainingModal();
        loadTrainings();
    } catch (error) {
        console.error('Failed to update training:', error);
    }
}

async function deleteTraining(trainingId, trainingName) {
    if (confirm(`Are you sure you want to delete "${trainingName}"? This will remove all progress data for this training and cannot be undone.`)) {
        try {
            await apiCall(`/trainings/${trainingId}`, 'DELETE');
            loadTrainings();
        } catch (error) {
            console.error('Failed to delete training:', error);
            alert('Failed to delete training. Please try again.');
        }
    }
}

// Daily tracking
function updateCurrentDate() {
    const today = new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('current-date').textContent = today;
}

async function loadDailyTrainings() {
    const today = new Date().toISOString().split('T')[0];

    try {
        const dailyTrainings = await apiCall(`/daily-trainings/${today}`);
        renderDailyTrainings(dailyTrainings);
    } catch (error) {
        console.error('Failed to load daily trainings:', error);
    }
}

function renderDailyTrainings(dailyTrainings) {
    const container = document.getElementById('daily-trainings');
    container.innerHTML = '';

    if (dailyTrainings.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-8">No trainings scheduled for today. Create some trainings first!</p>';
        return;
    }

    dailyTrainings.forEach(training => {
        const card = document.createElement('div');
        card.className = 'bg-white border-2 border-black rounded-lg p-6 shadow-lg';

        const progressData = training.progress ? training.progress.split(',') : [];
        const progressHTML = progressData.map(item => {
            const parts = item.split(':');
            const name = parts[0];
            const completed = parseInt(parts[1]) || 0;
            const carriedForward = parseInt(parts[2]) || 0;
            const totalTarget = training.daily_target + carriedForward;
            const remaining = totalTarget - completed;
            const personId = people.find(p => p.name === name)?.id;

            return `
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <div class="flex-1">
                        <span class="font-medium">${name}</span>
                        ${carriedForward > 0 ? `<span class="text-orange-600 text-xs block">+${carriedForward} carried from yesterday</span>` : ''}
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span class="text-green-600">${completed}/${totalTarget}</span>
                            <span class="text-gray-500 text-sm block">Remaining: ${remaining}</span>
                        </div>
                        <button onclick="showAddProgressModal(${training.id}, '${training.name}', ${totalTarget}, ${personId}, '${name}')"
                                class="bg-black text-white px-3 py-1 rounded text-sm hover:bg-gray-800 transition">
                            Add
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        card.innerHTML = `
            <div class="mb-4">
                <h3 class="text-xl font-bold">${training.name}</h3>
                <p class="text-gray-600">Daily Target: ${training.daily_target} reps</p>
            </div>
            <div class="space-y-2">
                ${progressHTML}
            </div>
        `;
        container.appendChild(card);
    });
}

function showAddProgressModal(trainingId, trainingName, target, personId = null, personName = null) {
    currentTrainingId = trainingId;
    currentPersonId = personId;
    document.getElementById('progress-target').textContent = target;

    const select = document.getElementById('progress-person');

    if (personId && personName) {
        // Person-specific progress: pre-select and disable dropdown
        select.innerHTML = `<option value="${personId}" selected>${personName}</option>`;
        select.disabled = true;
        select.classList.add('bg-gray-100', 'cursor-not-allowed');

        // Update modal title to show it's for specific person
        document.querySelector('#add-progress-modal h3').textContent = `Add Progress for ${personName}`;
    } else {
        // General progress: load all participants
        loadTrainingParticipants(trainingId);
        select.disabled = false;
        select.classList.remove('bg-gray-100', 'cursor-not-allowed');

        // Reset modal title
        document.querySelector('#add-progress-modal h3').textContent = 'Add Progress';
    }

    // Clear the reps input
    document.getElementById('progress-reps').value = '';

    document.getElementById('add-progress-modal').classList.remove('hidden');
}

function hideAddProgressModal() {
    document.getElementById('add-progress-modal').classList.add('hidden');
    document.getElementById('add-progress-form').reset();

    // Reset person dropdown
    const select = document.getElementById('progress-person');
    select.disabled = false;
    select.classList.remove('bg-gray-100', 'cursor-not-allowed');

    // Reset modal title
    document.querySelector('#add-progress-modal h3').textContent = 'Add Progress';

    // Clear variables
    currentTrainingId = null;
    currentPersonId = null;
}

async function loadTrainingParticipants(trainingId) {
    try {
        const participants = await apiCall(`/training-participants/${trainingId}`);
        const select = document.getElementById('progress-person');
        select.innerHTML = '<option value="">Select person</option>';

        participants.forEach(person => {
            const option = document.createElement('option');
            option.value = person.id;
            option.textContent = person.name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Failed to load training participants:', error);
    }
}

async function handleAddProgress(e) {
    e.preventDefault();

    // Use currentPersonId if set (person-specific), otherwise get from dropdown
    const personId = currentPersonId || parseInt(document.getElementById('progress-person').value);
    const completedReps = parseInt(document.getElementById('progress-reps').value);
    const target = parseInt(document.getElementById('progress-target').textContent);
    const today = new Date().toISOString().split('T')[0];

    if (!personId) {
        alert('Please select a person');
        return;
    }

    const formData = {
        person_id: personId,
        training_id: currentTrainingId,
        date: today,
        completed_reps: completedReps,
        target_reps: target
    };

    try {
        await apiCall('/daily-progress', 'POST', formData);
        hideAddProgressModal();
        loadDailyTrainings();
    } catch (error) {
        console.error('Failed to add progress:', error);
    }
}

// History
function updateHistoryPeople() {
    const select = document.getElementById('history-person-select');
    select.innerHTML = '<option value="">Select a person to view history</option>';

    people.forEach(person => {
        const option = document.createElement('option');
        option.value = person.id;
        option.textContent = person.name;
        select.appendChild(option);
    });
}

async function loadPersonHistory() {
    const personId = document.getElementById('history-person-select').value;
    if (!personId) {
        document.getElementById('history-content').innerHTML = '';
        return;
    }

    try {
        const [trainingHistory, weightHistory] = await Promise.all([
            apiCall(`/history/person/${personId}`),
            apiCall(`/weight-history/${personId}`)
        ]);

        renderPersonHistory(trainingHistory, weightHistory);
    } catch (error) {
        console.error('Failed to load person history:', error);
    }
}

function renderPersonHistory(trainingHistory, weightHistory) {
    const container = document.getElementById('history-content');

    let html = '<div class="grid md:grid-cols-2 gap-6">';

    // Training history
    html += `
        <div class="bg-white border-2 border-black rounded-lg p-6">
            <h3 class="text-xl font-bold mb-4">Training History</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
    `;

    if (trainingHistory.length === 0) {
        html += '<p class="text-gray-500">No training history found.</p>';
    } else {
        trainingHistory.forEach(record => {
            html += `
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <div>
                        <span class="font-medium">${record.training_name}</span>
                        <span class="text-gray-500 text-sm block">${new Date(record.date).toLocaleDateString()}</span>
                    </div>
                    <span class="text-green-600">${record.completed_reps}/${record.target_reps}</span>
                </div>
            `;
        });
    }

    html += '</div></div>';

    // Weight history
    html += `
        <div class="bg-white border-2 border-black rounded-lg p-6">
            <h3 class="text-xl font-bold mb-4">Weight History</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
    `;

    if (weightHistory.length === 0) {
        html += '<p class="text-gray-500">No weight history found.</p>';
    } else {
        weightHistory.forEach(record => {
            html += `
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-500">${new Date(record.recorded_date).toLocaleDateString()}</span>
                    <span class="font-medium">${record.weight} kg</span>
                </div>
            `;
        });
    }

    html += '</div></div></div>';

    container.innerHTML = html;
}
