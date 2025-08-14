// Global variables
let currentSection = 'people';
let people = [];
let trainings = [];
let currentTrainingId = null;
let currentPersonId = null;
let isLoggedIn = false;

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
        document.getElementById('login-form').addEventListener('submit', handleLogin);
        document.getElementById('day-off-form').addEventListener('submit', saveDayOffSettings);

        // Initialize edit mode (hide buttons initially)
        initializeEditMode();

        // Check login status
        checkLoginStatus();
        
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

// Make navigation function global
window.navigateToSection = navigateToSection;

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

    // Apply authentication state to the newly shown section
    setTimeout(() => applyAuthenticationState(), 100);
}

// API calls for PHP backend
async function apiCall(endpoint, method = 'GET', data = null, queryParams = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    // Build URL with query parameters
    let url = `api.php?endpoint=${encodeURIComponent(endpoint)}&method=${method}`;

    // Add additional query parameters if provided
    if (queryParams) {
        Object.keys(queryParams).forEach(key => {
            url += `&${encodeURIComponent(key)}=${encodeURIComponent(queryParams[key])}`;
        });
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            if (response.status === 401) {
                const errorData = await response.json();
                if (errorData.login_required) {
                    alert('🔐 Login required to perform this action.');
                    showLoginModal();
                    throw new Error('Authentication required');
                }
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        if (error.message !== 'Authentication required') {
            alert('An error occurred. Please try again.');
        }
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
        // Apply current authentication state to newly rendered content
        applyAuthenticationState();
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
                        class="edit-only bg-black text-white px-3 py-1 rounded text-sm hover:bg-gray-800 transition">
                    Update Weight
                </button>
                <button onclick="deletePerson(${person.id}, '${person.name}')"
                        class="edit-only bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
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
        // Apply current authentication state to newly rendered content
        applyAuthenticationState();
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
                            class="edit-only bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                        Edit
                    </button>
                    <button onclick="deleteTraining(${training.id}, '${training.name}')"
                            class="edit-only bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
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
        // Apply current authentication state to newly rendered content
        applyAuthenticationState();
    } catch (error) {
        console.error('Failed to load daily trainings:', error);
    }
}

function renderDailyTrainings(dailyTrainings) {
    const container = document.getElementById('daily-trainings');
    container.innerHTML = '';

    // Check if today is a rest day
    if (dailyTrainings.is_rest_day) {
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
        container.innerHTML = `
            <div class="bg-white border-2 border-black rounded-lg p-8 shadow-lg text-center">
                <div class="text-6xl mb-4">🛌</div>
                <h3 class="text-2xl font-bold mb-2">Rest Day</h3>
                <p class="text-gray-600 text-lg mb-4">Today is ${today} - a scheduled rest day!</p>
                <p class="text-gray-500">No training required today. Enjoy your rest! 😊</p>
                <div class="mt-6 text-sm text-gray-400">
                    Training will resume on the next scheduled training day.
                </div>
            </div>
        `;
        return;
    }

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

            // Determine status and remaining text
            let statusText = '';
            let statusColor = 'text-gray-500';

            if (completed >= totalTarget) {
                if (completed > totalTarget) {
                    statusText = `Over Achieved! +${completed - totalTarget} extra`;
                    statusColor = 'text-purple-600';
                } else {
                    statusText = 'Target Completed! 🎉';
                    statusColor = 'text-green-600';
                }
            } else {
                statusText = `Remaining: ${remaining}`;
                statusColor = 'text-gray-500';
            }

            return `
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <div class="flex-1">
                        <span class="font-medium">${name}</span>
                        ${carriedForward > 0 ? `<span class="text-orange-600 text-xs block">+${carriedForward} carried from yesterday</span>` : ''}
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span class="text-green-600">${completed}/${totalTarget}</span>
                            <span class="${statusColor} text-sm block">${statusText}</span>
                        </div>
                        <button onclick="showAddProgressModal(${training.id}, '${training.name}', ${totalTarget}, ${personId}, '${name}')"
                                class="edit-only bg-black text-white px-3 py-1 rounded text-sm hover:bg-gray-800 transition">
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

// History - New comprehensive view
function updateHistoryPeople() {
    // Load all history data when history section is accessed
    loadAllHistory();
}

async function loadAllHistory() {
    try {
        // Load all activity data without any date filter
        const allActivity = await apiCall(`/history/all`, 'GET', null, { days: 'all' });
        renderRecentActivity(allActivity);
    } catch (error) {
        console.error('Failed to load history:', error);
    }
}



function renderRecentActivity(activityData) {
    const container = document.getElementById('recent-activity-table');

    // Sort by date descending to show latest on top (show all records)
    const allData = activityData
        .filter(item => item.date) // Only items with actual progress
        .sort((a, b) => new Date(b.date) - new Date(a.date));

    let html = '';
    allData.forEach(item => {
        const date = new Date(item.date).toLocaleDateString();
        const completionRate = item.target_reps > 0 ? (item.completed_reps / item.target_reps) * 100 : 0;

        // Simplified status logic without external functions
        let statusText = '';
        let statusColor = '';

        if (completionRate >= 110) {
            statusText = 'Over Achieved';
            statusColor = 'text-purple-600';
        } else if (completionRate >= 100) {
            statusText = 'Completed';
            statusColor = 'text-green-600';
        } else if (completionRate >= 50) {
            statusText = 'Partial';
            statusColor = 'text-yellow-600';
        } else if (completionRate > 0) {
            statusText = 'Partial';
            statusColor = 'text-orange-600';
        } else {
            statusText = 'Missed';
            statusColor = 'text-red-600';
        }

        html += `
            <tr class="hover:bg-gray-50">
                <td class="p-3 border-b text-sm">${date}</td>
                <td class="p-3 border-b font-medium">${item.person_name}</td>
                <td class="p-3 border-b">${item.training_name}</td>
                <td class="p-3 border-b">
                    <span class="text-sm">${item.completed_reps}/${item.target_reps}</span>
                    ${item.carried_forward > 0 ? `<span class="text-xs text-orange-600 block">+${item.carried_forward} carried</span>` : ''}
                </td>
                <td class="p-3 border-b">
                    <span class="${statusColor} font-medium">${statusText}</span>
                </td>
            </tr>
        `;
    });

    if (html === '') {
        html = '<tr><td colspan="5" class="p-6 text-center text-gray-500">No activity found</td></tr>';
    }

    container.innerHTML = html;
}

// Authentication functions
async function checkLoginStatus() {
    try {
        const response = await fetch('auth.php?check=1');
        const data = await response.json();
        updateLoginUI(data.logged_in, data.username);
    } catch (error) {
        console.error('Failed to check login status:', error);
        updateLoginUI(false);
    }
}

function updateLoginUI(loggedIn, username = null) {
    isLoggedIn = loggedIn;
    const loginBtn = document.getElementById('login-btn');
    const logoutBtn = document.getElementById('logout-btn');
    const loginStatus = document.getElementById('login-status');

    if (loggedIn) {
        loginBtn.classList.add('hidden');
        logoutBtn.classList.remove('hidden');
        loginStatus.textContent = `👤 ${username || 'Admin'}`;
        enableEditMode();
    } else {
        loginBtn.classList.remove('hidden');
        logoutBtn.classList.add('hidden');
        loginStatus.textContent = '👀 View Only';
        disableEditMode();
    }
}

function enableEditMode() {
    // Show all add/edit/delete buttons by removing the edit-only class and adding a visible class
    document.querySelectorAll('.edit-only').forEach(el => {
        el.classList.remove('edit-only');
        el.classList.add('edit-visible');
        el.style.display = 'inline-block';
    });
}

function disableEditMode() {
    // Hide all add/edit/delete buttons
    document.querySelectorAll('.edit-only, .edit-visible').forEach(el => {
        el.classList.remove('edit-visible');
        el.classList.add('edit-only');
        el.style.display = 'none';
    });
}

// Initially hide all edit buttons on page load
function initializeEditMode() {
    disableEditMode();
}

// Apply current authentication state to all edit buttons
function applyAuthenticationState() {
    if (isLoggedIn) {
        enableEditMode();
    } else {
        disableEditMode();
    }
}

function showLoginModal() {
    document.getElementById('login-modal').classList.remove('hidden');
    document.getElementById('login-username').focus();
}

function hideLoginModal() {
    document.getElementById('login-modal').classList.add('hidden');
    document.getElementById('login-form').reset();
}

async function handleLogin(e) {
    e.preventDefault();

    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;

    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', username, password })
        });

        const data = await response.json();

        if (data.success) {
            hideLoginModal();
            updateLoginUI(true, username);
            // Force refresh of current section to show edit buttons
            if (currentSection === 'people') {
                renderPeopleTable();
            } else if (currentSection === 'trainings') {
                renderTrainingsGrid();
            } else if (currentSection === 'daily') {
                loadDailyTrainings();
            }
            alert('✅ Login successful! You can now edit data.');
        } else {
            alert('❌ Invalid credentials. Please try again.');
        }
    } catch (error) {
        console.error('Login failed:', error);
        alert('❌ Login failed. Please try again.');
    }
}

async function logout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            await fetch('auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });

            updateLoginUI(false);
            // Apply authentication state to current section
            applyAuthenticationState();
            alert('👋 Logged out successfully.');
        } catch (error) {
            console.error('Logout failed:', error);
        }
    }
}

// Day Off Settings Functions
function showDayOffModal() {
    loadDayOffSettings();
    document.getElementById('day-off-modal').classList.remove('hidden');
}

function hideDayOffModal() {
    document.getElementById('day-off-modal').classList.add('hidden');
}

async function loadDayOffSettings() {
    try {
        const settings = await apiCall('/day-off-settings');

        // Update checkboxes based on settings
        for (let day = 0; day <= 6; day++) {
            const checkbox = document.getElementById(`day-off-${day}`);
            if (checkbox) {
                checkbox.checked = settings[day] || false;
            }
        }
    } catch (error) {
        console.error('Failed to load day off settings:', error);
    }
}

async function saveDayOffSettings(event) {
    event.preventDefault();

    try {
        const settings = {};

        // Collect checkbox states
        for (let day = 0; day <= 6; day++) {
            const checkbox = document.getElementById(`day-off-${day}`);
            if (checkbox) {
                settings[day] = checkbox.checked;
            }
        }

        await apiCall('/day-off-settings', 'POST', settings);
        hideDayOffModal();
        alert('✅ Day off settings saved successfully!');

        // Reload daily trainings to reflect changes
        loadDailyTrainings();
    } catch (error) {
        console.error('Failed to save day off settings:', error);
        alert('❌ Failed to save day off settings. Please try again.');
    }
}

// Make authentication functions globally available
window.showLoginModal = showLoginModal;
window.hideLoginModal = hideLoginModal;
window.logout = logout;

// Debug function to clear carry forward data
async function clearCarryForwardData() {
    if (confirm('This will clear all carry-forward data from today and rest days, then recalculate. Are you sure?')) {
        try {
            await apiCall('/debug/clear-carry-forward', 'POST');
            alert('✅ Carry-forward data cleared and recalculated successfully!');
            loadDailyTrainings();
        } catch (error) {
            console.error('Failed to clear carry-forward data:', error);
            alert('❌ Failed to clear carry-forward data.');
        }
    }
}

// Make day off functions globally available
window.showDayOffModal = showDayOffModal;
window.hideDayOffModal = hideDayOffModal;
window.saveDayOffSettings = saveDayOffSettings;
window.clearCarryForwardData = clearCarryForwardData;
