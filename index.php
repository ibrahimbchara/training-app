<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Tracker App</title>

    <!-- PWA Meta Tags -->
    <meta name="description" content="Track your daily training progress and manage workout routines">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Training Tracker">
    <meta name="msapplication-TileColor" content="#000000">
    <meta name="msapplication-tap-highlight" content="no">

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icon-16x16.png">
    <link rel="apple-touch-icon" href="icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icon-192x192.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#000000',
                        secondary: '#ffffff',
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS for authentication -->
    <style>
        .edit-only {
            display: none;
        }
        .edit-visible {
            display: inline-block;
        }
    </style>
</head>
<body class="bg-white text-black min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Training Tracker</h1>
            <div class="space-x-4">
                <button onclick="navigateToSection('people')" id="nav-people" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium">People</button>
                <button onclick="navigateToSection('trainings')" id="nav-trainings" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium">Trainings</button>
                <button onclick="navigateToSection('daily')" id="nav-daily" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium">Daily Tracker</button>
                <button onclick="navigateToSection('history')" id="nav-history" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium">History</button>
            </div>
            <div class="space-x-2">
                <span id="login-status" class="text-white text-sm"></span>
                <button onclick="showLoginModal()" id="login-btn" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium border-2 border-white">Login</button>
                <button onclick="logout()" id="logout-btn" class="bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition font-medium border-2 border-white hidden">Logout</button>
            </div>
        </div>
    </nav>

    <!-- People Management Section -->
    <section id="people-section" class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">People Management</h2>
            <button onclick="showAddPersonModal()" class="edit-only bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Add Person</button>
        </div>
        
        <div class="bg-white border-2 border-black rounded-lg overflow-hidden shadow-lg">
            <table class="w-full">
                <thead class="bg-black text-white">
                    <tr>
                        <th class="p-4 text-left font-bold">Name</th>
                        <th class="p-4 text-left font-bold">Age</th>
                        <th class="p-4 text-left font-bold">Height (cm)</th>
                        <th class="p-4 text-left font-bold">Current Weight (kg)</th>
                        <th class="p-4 text-left font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody id="people-table" class="divide-y-2 divide-black">
                    <!-- People will be populated here -->
                </tbody>
            </table>
        </div>
    </section>

    <!-- Training Management Section -->
    <section id="trainings-section" class="container mx-auto p-6 hidden">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Training Management</h2>
            <button onclick="showAddTrainingModal()" class="edit-only bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Create Training</button>
        </div>
        
        <div class="grid gap-6" id="trainings-grid">
            <!-- Training cards will be populated here -->
        </div>
    </section>

    <!-- Daily Tracker Section -->
    <section id="daily-section" class="container mx-auto p-6 hidden">
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-3xl font-bold">Daily Tracker</h2>
                <button onclick="showDayOffModal()" class="edit-only bg-black text-white px-4 py-2 rounded hover:bg-gray-800 transition font-medium">
                    🗓️ Day Off Settings
                </button>
            </div>
            <p class="text-gray-600 text-lg" id="current-date"></p>
        </div>
        
        <div class="grid gap-6" id="daily-trainings">
            <!-- Daily training progress will be populated here -->
        </div>
    </section>

    <!-- History Section -->
    <section id="history-section" class="container mx-auto p-6 hidden">
        <div class="mb-6">
            <h2 class="text-3xl font-bold">Training History & Analytics</h2>
        </div>

        <!-- Activity -->
        <div class="bg-white border-2 border-black rounded-lg overflow-hidden shadow-lg">
            <div class="bg-black text-white p-4">
                <h3 class="text-xl font-bold">Activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left font-bold border-b">Date</th>
                            <th class="p-3 text-left font-bold border-b">Person</th>
                            <th class="p-3 text-left font-bold border-b">Training</th>
                            <th class="p-3 text-left font-bold border-b">Progress</th>
                            <th class="p-3 text-left font-bold border-b">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-activity-table">
                        <!-- Recent activity will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Add Person Modal -->
    <div id="add-person-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">Add New Person</h3>
            <form id="add-person-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Name</label>
                    <input type="text" id="person-name" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Age</label>
                    <input type="number" id="person-age" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Height (cm)</label>
                    <input type="number" id="person-height" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2">Weight (kg)</label>
                    <input type="number" step="0.1" id="person-weight" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideAddPersonModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Add Person</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Training Modal -->
    <div id="add-training-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">Create New Training</h3>
            <form id="add-training-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Training Type</label>
                    <input type="text" id="training-type" placeholder="e.g., Push-ups, Pull-ups" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Daily Target</label>
                    <input type="number" id="training-target" placeholder="e.g., 100" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2">Select Participants</label>
                    <div id="participants-list" class="max-h-40 overflow-y-auto border-2 border-black rounded p-3 bg-gray-50">
                        <!-- Participants checkboxes will be populated here -->
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideAddTrainingModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Create Training</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Training Modal -->
    <div id="edit-training-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">Edit Training</h3>
            <form id="edit-training-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Training Type</label>
                    <input type="text" id="edit-training-type" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Daily Target</label>
                    <input type="number" id="edit-training-target" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2">Select Participants</label>
                    <div id="edit-participants-list" class="max-h-40 overflow-y-auto border-2 border-black rounded p-3 bg-gray-50">
                        <!-- Participants checkboxes will be populated here -->
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideEditTrainingModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition font-medium">Update Training</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Progress Modal -->
    <div id="add-progress-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">Add Progress</h3>
            <form id="add-progress-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Person</label>
                    <select id="progress-person" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-600" required>
                        <!-- Options will be populated -->
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Completed Reps</label>
                    <input type="number" id="progress-reps" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-6">
                    <p class="text-sm text-gray-600">Target: <span id="progress-target"></span> reps</p>
                    <p class="text-xs text-gray-500 mt-1">Enter reps completed in this session</p>
                    <p class="text-xs text-orange-600 mt-1">💡 Use negative numbers to correct mistakes (e.g., -20 to remove 20 reps)</p>
                    <p class="text-xs text-purple-600 mt-1">🚀 Over-achieving? Great! Extra reps won't affect tomorrow's target</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideAddProgressModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Add Progress</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Weight Modal -->
    <div id="update-weight-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">Update Weight</h3>
            <form id="update-weight-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Person</label>
                    <input type="text" id="weight-person-name" class="w-full p-3 border-2 border-black rounded bg-gray-100" readonly>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2">New Weight (kg)</label>
                    <input type="number" step="0.1" id="new-weight" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideUpdateWeightModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Update Weight</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">🔐 Admin Login</h3>
            <form id="login-form">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Username</label>
                    <input type="text" id="login-username" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2">Password</label>
                    <input type="password" id="login-password" class="w-full p-3 border-2 border-black rounded focus:outline-none focus:ring-2 focus:ring-gray-400" required>
                </div>
                <div class="mb-4">
                    <p class="text-xs text-gray-600">
                        👀 <strong>View Mode:</strong> Anyone can view data<br>
                        ✏️ <strong>Edit Mode:</strong> Login required to add/edit/delete
                    </p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideLoginModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Day Off Settings Modal -->
    <div id="day-off-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg border-2 border-black w-96 shadow-2xl">
            <h3 class="text-2xl font-bold mb-6">🗓️ Day Off Settings</h3>
            <p class="text-gray-600 mb-4">Select which days of the week should be rest days (no training required):</p>

            <form id="day-off-form">
                <div class="space-y-3 mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-0" class="mr-3 h-4 w-4">
                        <span>Sunday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-1" class="mr-3 h-4 w-4">
                        <span>Monday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-2" class="mr-3 h-4 w-4">
                        <span>Tuesday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-3" class="mr-3 h-4 w-4">
                        <span>Wednesday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-4" class="mr-3 h-4 w-4">
                        <span>Thursday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-5" class="mr-3 h-4 w-4">
                        <span>Friday</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="day-off-6" class="mr-3 h-4 w-4">
                        <span>Saturday</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideDayOffModal()" class="px-6 py-3 border-2 border-black rounded hover:bg-gray-100 transition font-medium">Cancel</button>
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition font-medium">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <script src="app.js"></script>

    <!-- PWA Service Worker Registration -->
    <script>
        // Register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New content available, show update notification
                                    if (confirm('New version available! Reload to update?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(error => {
                        console.log('SW registration failed: ', error);
                    });
            });
        }

        // PWA Install prompt
        let deferredPrompt;
        let installButton = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt available');
            e.preventDefault();
            deferredPrompt = e;

            // Show install button
            showInstallButton();
        });

        function showInstallButton() {
            // Create install button if it doesn't exist
            if (!installButton) {
                installButton = document.createElement('button');
                installButton.innerHTML = '📱 Install App';
                installButton.className = 'fixed bottom-4 right-4 bg-black text-white px-4 py-2 rounded-lg shadow-lg hover:bg-gray-800 transition z-50';
                installButton.onclick = installPWA;
                document.body.appendChild(installButton);
            }
        }

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((result) => {
                    if (result.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        hideInstallButton();
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        }

        function hideInstallButton() {
            if (installButton) {
                installButton.remove();
                installButton = null;
            }
        }

        // Hide install button if already installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            hideInstallButton();
        });

        // Check if running as PWA
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            console.log('Running as PWA');
            // Add PWA-specific styling or behavior
            document.body.classList.add('pwa-mode');
        }
    </script>
</body>
</html>
