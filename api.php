<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
function getDatabase() {
    try {
        $db = new PDO('sqlite:training_tracker.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}

// Initialize database tables
function initializeDatabase() {
    $db = getDatabase();
    
    // People table
    $db->exec("CREATE TABLE IF NOT EXISTS people (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        age INTEGER NOT NULL,
        height REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Weight tracking table
    $db->exec("CREATE TABLE IF NOT EXISTS weight_tracking (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        person_id INTEGER NOT NULL,
        weight REAL NOT NULL,
        recorded_date DATE NOT NULL,
        FOREIGN KEY (person_id) REFERENCES people (id),
        UNIQUE(person_id, recorded_date)
    )");

    // Training types table
    $db->exec("CREATE TABLE IF NOT EXISTS training_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        daily_target INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Training participants table
    $db->exec("CREATE TABLE IF NOT EXISTS training_participants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        training_id INTEGER NOT NULL,
        person_id INTEGER NOT NULL,
        FOREIGN KEY (training_id) REFERENCES training_types (id),
        FOREIGN KEY (person_id) REFERENCES people (id),
        UNIQUE(training_id, person_id)
    )");

    // Daily progress table
    $db->exec("CREATE TABLE IF NOT EXISTS daily_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        person_id INTEGER NOT NULL,
        training_id INTEGER NOT NULL,
        date DATE NOT NULL,
        completed_reps INTEGER DEFAULT 0,
        target_reps INTEGER NOT NULL,
        carried_forward INTEGER DEFAULT 0,
        FOREIGN KEY (person_id) REFERENCES people (id),
        FOREIGN KEY (training_id) REFERENCES training_types (id),
        UNIQUE(person_id, training_id, date)
    )");

    // Add carried_forward column if it doesn't exist (for existing databases)
    try {
        $db->exec("ALTER TABLE daily_progress ADD COLUMN carried_forward INTEGER DEFAULT 0");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }
}

// Function to carry forward incomplete reps from previous day
function carryForwardIncompleteReps($db, $currentDate) {
    $previousDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));

    // Get all incomplete progress from previous day
    $stmt = $db->prepare("
        SELECT person_id, training_id, target_reps, completed_reps
        FROM daily_progress
        WHERE date = ? AND completed_reps < target_reps
    ");
    $stmt->execute([$previousDate]);
    $incompleteProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($incompleteProgress as $progress) {
        $remaining = $progress['target_reps'] - $progress['completed_reps'];

        // Check if today's record already exists
        $checkStmt = $db->prepare("
            SELECT id FROM daily_progress
            WHERE person_id = ? AND training_id = ? AND date = ?
        ");
        $checkStmt->execute([$progress['person_id'], $progress['training_id'], $currentDate]);

        if (!$checkStmt->fetch()) {
            // Create today's record with carried forward reps
            $insertStmt = $db->prepare("
                INSERT INTO daily_progress (person_id, training_id, date, completed_reps, target_reps, carried_forward)
                VALUES (?, ?, ?, 0, ?, ?)
            ");
            $insertStmt->execute([
                $progress['person_id'],
                $progress['training_id'],
                $currentDate,
                $progress['target_reps'],
                $remaining
            ]);
        }
    }
}

// Initialize database on first run
initializeDatabase();

// Get request path and method from query parameters or URL
$path = $_GET['endpoint'] ?? '';
$method = $_GET['method'] ?? $_SERVER['REQUEST_METHOD'];

// Fallback to URL parsing if no query parameters
if (empty($path)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_path = parse_url($request_uri, PHP_URL_PATH);
    $path = str_replace('/api.php', '', $parsed_path);
}

// Debug logging (remove in production)
error_log("API Debug - Path: $path, Method: $method");

// Route handling
try {
    $db = getDatabase();
    
    // People endpoints
    if ($path === '/people' && $method === 'GET') {
        $stmt = $db->query("
            SELECT p.*, wt.weight as current_weight 
            FROM people p 
            LEFT JOIN weight_tracking wt ON p.id = wt.person_id 
            AND wt.recorded_date = (
                SELECT MAX(recorded_date) 
                FROM weight_tracking 
                WHERE person_id = p.id
            )
            ORDER BY p.name
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } elseif ($path === '/people' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare('INSERT INTO people (name, age, height) VALUES (?, ?, ?)');
        $stmt->execute([$input['name'], $input['age'], $input['height']]);
        
        $personId = $db->lastInsertId();
        $today = date('Y-m-d');
        
        // Add initial weight record
        $stmt = $db->prepare('INSERT INTO weight_tracking (person_id, weight, recorded_date) VALUES (?, ?, ?)');
        $stmt->execute([$personId, $input['weight'], $today]);
        
        echo json_encode([
            'id' => $personId,
            'name' => $input['name'],
            'age' => $input['age'],
            'height' => $input['height'],
            'current_weight' => $input['weight']
        ]);
        
    } elseif (preg_match('/^\/people\/(\d+)\/weight$/', $path, $matches) && $method === 'PUT') {
        $personId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $today = date('Y-m-d');

        $stmt = $db->prepare('INSERT OR REPLACE INTO weight_tracking (person_id, weight, recorded_date) VALUES (?, ?, ?)');
        $stmt->execute([$personId, $input['weight'], $today]);

        echo json_encode(['success' => true]);

    } elseif (preg_match('/^\/people\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        $personId = $matches[1];

        // Delete in order due to foreign key constraints
        $db->prepare('DELETE FROM daily_progress WHERE person_id = ?')->execute([$personId]);
        $db->prepare('DELETE FROM training_participants WHERE person_id = ?')->execute([$personId]);
        $db->prepare('DELETE FROM weight_tracking WHERE person_id = ?')->execute([$personId]);
        $db->prepare('DELETE FROM people WHERE id = ?')->execute([$personId]);

        echo json_encode(['success' => true]);
        
    } elseif ($path === '/trainings' && $method === 'GET') {
        $stmt = $db->query("
            SELECT t.*, 
                   GROUP_CONCAT(p.name) as participants,
                   COUNT(tp.person_id) as participant_count
            FROM training_types t
            LEFT JOIN training_participants tp ON t.id = tp.training_id
            LEFT JOIN people p ON tp.person_id = p.id
            GROUP BY t.id
            ORDER BY t.name
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } elseif ($path === '/trainings' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare('INSERT INTO training_types (name, daily_target) VALUES (?, ?)');
        $stmt->execute([$input['name'], $input['daily_target']]);
        
        $trainingId = $db->lastInsertId();
        
        // Add participants
        if (!empty($input['participants'])) {
            $stmt = $db->prepare('INSERT INTO training_participants (training_id, person_id) VALUES (?, ?)');
            foreach ($input['participants'] as $personId) {
                $stmt->execute([$trainingId, $personId]);
            }
        }
        
        echo json_encode(['id' => $trainingId, 'name' => $input['name'], 'daily_target' => $input['daily_target']]);

    } elseif (preg_match('/^\/trainings\/(\d+)$/', $path, $matches) && $method === 'PUT') {
        $trainingId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);

        // Update training details
        $stmt = $db->prepare('UPDATE training_types SET name = ?, daily_target = ? WHERE id = ?');
        $stmt->execute([$input['name'], $input['daily_target'], $trainingId]);

        // Remove all existing participants
        $db->prepare('DELETE FROM training_participants WHERE training_id = ?')->execute([$trainingId]);

        // Add new participants
        if (!empty($input['participants'])) {
            $stmt = $db->prepare('INSERT INTO training_participants (training_id, person_id) VALUES (?, ?)');
            foreach ($input['participants'] as $personId) {
                $stmt->execute([$trainingId, $personId]);
            }
        }

        echo json_encode(['success' => true]);

    } elseif (preg_match('/^\/trainings\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        $trainingId = $matches[1];

        // Delete in order due to foreign key constraints
        $db->prepare('DELETE FROM daily_progress WHERE training_id = ?')->execute([$trainingId]);
        $db->prepare('DELETE FROM training_participants WHERE training_id = ?')->execute([$trainingId]);
        $db->prepare('DELETE FROM training_types WHERE id = ?')->execute([$trainingId]);

        echo json_encode(['success' => true]);

    } elseif (preg_match('/^\/trainings\/(\d+)\/participants$/', $path, $matches) && $method === 'GET') {
        $trainingId = $matches[1];
        $stmt = $db->prepare("
            SELECT p.id, p.name,
                   CASE WHEN tp.person_id IS NOT NULL THEN 1 ELSE 0 END as is_participant
            FROM people p
            LEFT JOIN training_participants tp ON p.id = tp.person_id AND tp.training_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$trainingId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif (preg_match('/^\/daily-trainings\/(.+)$/', $path, $matches) && $method === 'GET') {
        $date = $matches[1];

        // First, carry forward any incomplete reps from previous day
        carryForwardIncompleteReps($db, $date);

        $stmt = $db->prepare("
            SELECT DISTINCT t.id, t.name, t.daily_target,
                   GROUP_CONCAT(p.name || ':' || COALESCE(dp.completed_reps, 0) || ':' || COALESCE(dp.carried_forward, 0)) as progress
            FROM training_types t
            JOIN training_participants tp ON t.id = tp.training_id
            JOIN people p ON tp.person_id = p.id
            LEFT JOIN daily_progress dp ON t.id = dp.training_id AND p.id = dp.person_id AND dp.date = ?
            GROUP BY t.id, t.name, t.daily_target
            ORDER BY t.name
        ");
        $stmt->execute([$date]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } elseif ($path === '/daily-progress' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Check existing progress
        $stmt = $db->prepare('SELECT completed_reps, carried_forward FROM daily_progress WHERE person_id = ? AND training_id = ? AND date = ?');
        $stmt->execute([$input['person_id'], $input['training_id'], $input['date']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $newCompletedReps = $existing ? $existing['completed_reps'] + $input['completed_reps'] : $input['completed_reps'];
        $carriedForward = $existing ? $existing['carried_forward'] : 0;

        // Don't allow negative total reps
        $newCompletedReps = max(0, $newCompletedReps);

        $stmt = $db->prepare('INSERT OR REPLACE INTO daily_progress (person_id, training_id, date, completed_reps, target_reps, carried_forward) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$input['person_id'], $input['training_id'], $input['date'], $newCompletedReps, $input['target_reps'], $carriedForward]);

        echo json_encode(['success' => true, 'total_completed' => $newCompletedReps]);
        
    } elseif (preg_match('/^\/training-participants\/(\d+)$/', $path, $matches) && $method === 'GET') {
        $trainingId = $matches[1];
        $stmt = $db->prepare("
            SELECT p.id, p.name
            FROM people p
            JOIN training_participants tp ON p.id = tp.person_id
            WHERE tp.training_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$trainingId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } elseif (preg_match('/^\/history\/person\/(\d+)$/', $path, $matches) && $method === 'GET') {
        $personId = $matches[1];
        $stmt = $db->prepare("
            SELECT dp.*, t.name as training_name, dp.date
            FROM daily_progress dp
            JOIN training_types t ON dp.training_id = t.id
            WHERE dp.person_id = ?
            ORDER BY dp.date DESC, t.name
            LIMIT 100
        ");
        $stmt->execute([$personId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } elseif (preg_match('/^\/weight-history\/(\d+)$/', $path, $matches) && $method === 'GET') {
        $personId = $matches[1];
        $stmt = $db->prepare("
            SELECT weight, recorded_date
            FROM weight_tracking
            WHERE person_id = ?
            ORDER BY recorded_date DESC
        ");
        $stmt->execute([$personId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
