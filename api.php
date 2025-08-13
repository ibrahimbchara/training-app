<?php
require_once 'auth.php';

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

    // Day off settings table
    $db->exec("CREATE TABLE IF NOT EXISTS day_off_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        day_of_week INTEGER NOT NULL, -- 0=Sunday, 1=Monday, ..., 6=Saturday
        is_day_off BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(day_of_week)
    )");
}

// Function to carry forward incomplete reps from previous day
function carryForwardIncompleteReps($db, $currentDate) {
    // Don't carry forward to rest days
    if (!isTrainingDay($currentDate)) {
        return;
    }

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

        // Only carry forward if there's actually a deficit (not over-achieved)
        if ($remaining > 0) {
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
}

// Initialize database on first run
initializeDatabase();

// Get request path and method from query parameters or URL
$path = $_GET['endpoint'] ?? '';
$method = $_GET['method'] ?? $_SERVER['REQUEST_METHOD'];

// Clean up the path - remove query parameters if they exist
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

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
        requireAuth(); // Require authentication for adding people

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
        requireAuth(); // Require authentication for updating weight

        $personId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $today = date('Y-m-d');

        $stmt = $db->prepare('INSERT OR REPLACE INTO weight_tracking (person_id, weight, recorded_date) VALUES (?, ?, ?)');
        $stmt->execute([$personId, $input['weight'], $today]);

        echo json_encode(['success' => true]);

    } elseif (preg_match('/^\/people\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        requireAuth(); // Require authentication for deleting people

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
        requireAuth(); // Require authentication for creating trainings

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
        requireAuth(); // Require authentication for updating trainings

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
        requireAuth(); // Require authentication for deleting trainings

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

        // Check if today is a rest day
        if (!isTrainingDay($date)) {
            echo json_encode(['is_rest_day' => true, 'date' => $date]);
            return;
        }

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
        requireAuth(); // Require authentication for adding progress

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

    } elseif ($path === '/history/all' && $method === 'GET') {
        $days = $_GET['days'] ?? 30;

        if ($days === 'all') {
            $dateCondition = '';
            $params = [];
        } else {
            $dateCondition = 'AND dp.date >= date("now", "-' . intval($days) . ' days")';
            $params = [];
        }

        // Get comprehensive training data
        $stmt = $db->prepare("
            SELECT
                p.id as person_id,
                p.name as person_name,
                t.id as training_id,
                t.name as training_name,
                t.daily_target,
                dp.date,
                dp.completed_reps,
                dp.target_reps,
                dp.carried_forward,
                CASE
                    WHEN dp.completed_reps >= dp.target_reps THEN 'completed'
                    WHEN dp.completed_reps > 0 THEN 'partial'
                    ELSE 'missed'
                END as status
            FROM people p
            CROSS JOIN training_types t
            LEFT JOIN training_participants tp ON p.id = tp.person_id AND t.id = tp.training_id
            LEFT JOIN daily_progress dp ON p.id = dp.person_id AND t.id = dp.training_id $dateCondition
            WHERE tp.person_id IS NOT NULL
            ORDER BY dp.date DESC, p.name, t.name
        ");
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($path === '/history/summary' && $method === 'GET') {
        $days = $_GET['days'] ?? 30;

        if ($days === 'all') {
            $dateCondition = '';
        } else {
            $dateCondition = 'AND dp.date >= date("now", "-' . intval($days) . ' days")';
        }

        // Get summary statistics
        $stmt = $db->prepare("
            SELECT
                COUNT(DISTINCT p.id) as total_people,
                COUNT(DISTINCT t.id) as total_trainings,
                COUNT(dp.id) as total_sessions,
                SUM(dp.completed_reps) as total_reps,
                AVG(CASE WHEN dp.target_reps > 0 THEN (dp.completed_reps * 100.0 / dp.target_reps) ELSE 0 END) as avg_completion_rate,
                COUNT(CASE WHEN dp.completed_reps >= dp.target_reps THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN dp.completed_reps > 0 AND dp.completed_reps < dp.target_reps THEN 1 END) as partial_sessions,
                COUNT(CASE WHEN dp.completed_reps = 0 THEN 1 END) as missed_sessions
            FROM people p
            CROSS JOIN training_types t
            LEFT JOIN training_participants tp ON p.id = tp.person_id AND t.id = tp.training_id
            LEFT JOIN daily_progress dp ON p.id = dp.person_id AND t.id = dp.training_id $dateCondition
            WHERE tp.person_id IS NOT NULL
        ");
        $stmt->execute();
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));

    } elseif ($path === '/history/progress-matrix' && $method === 'GET') {
        $days = $_GET['days'] ?? 30;

        if ($days === 'all') {
            $dateCondition = '';
        } else {
            $dateCondition = 'AND dp.date >= date("now", "-' . intval($days) . ' days")';
        }

        // Get progress matrix for table view
        $stmt = $db->prepare("
            SELECT
                p.id as person_id,
                p.name as person_name,
                t.id as training_id,
                t.name as training_name,
                COUNT(dp.id) as total_days,
                SUM(dp.completed_reps) as total_completed,
                SUM(dp.target_reps) as total_target,
                AVG(CASE WHEN dp.target_reps > 0 THEN (dp.completed_reps * 100.0 / dp.target_reps) ELSE 0 END) as completion_rate,
                COUNT(CASE WHEN dp.completed_reps >= dp.target_reps THEN 1 END) as days_completed,
                COUNT(CASE WHEN dp.completed_reps > 0 AND dp.completed_reps < dp.target_reps THEN 1 END) as days_partial,
                COUNT(CASE WHEN dp.completed_reps = 0 OR dp.completed_reps IS NULL THEN 1 END) as days_missed
            FROM people p
            CROSS JOIN training_types t
            LEFT JOIN training_participants tp ON p.id = tp.person_id AND t.id = tp.training_id
            LEFT JOIN daily_progress dp ON p.id = dp.person_id AND t.id = dp.training_id $dateCondition
            WHERE tp.person_id IS NOT NULL
            GROUP BY p.id, p.name, t.id, t.name
            ORDER BY p.name, t.name
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } else if ($path === '/day-off-settings') {
        if ($method === 'GET') {
            $data = getDayOffSettings($db);
            echo json_encode($data);
        } else if ($method === 'POST') {
            requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            $result = updateDayOffSettings($db, $input);
            echo json_encode($result);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Day off settings functions
function getDayOffSettings($db) {
    $stmt = $db->query("SELECT day_of_week, is_day_off FROM day_off_settings ORDER BY day_of_week");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert to associative array for easier frontend handling
    $result = [];
    foreach ($settings as $setting) {
        $result[$setting['day_of_week']] = (bool)$setting['is_day_off'];
    }

    return $result;
}

function updateDayOffSettings($db, $settings) {
    try {
        $db->beginTransaction();

        // Clear existing settings
        $db->exec("DELETE FROM day_off_settings");

        // Insert new settings
        $stmt = $db->prepare("INSERT INTO day_off_settings (day_of_week, is_day_off) VALUES (?, ?)");

        foreach ($settings as $dayOfWeek => $isDayOff) {
            if (is_numeric($dayOfWeek) && $dayOfWeek >= 0 && $dayOfWeek <= 6) {
                $stmt->execute([$dayOfWeek, $isDayOff ? 1 : 0]);
            }
        }

        $db->commit();
        return ['success' => true, 'message' => 'Day off settings updated successfully'];
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function isTrainingDay($date) {
    $db = getDatabase();
    $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 1=Monday, ..., 6=Saturday

    $stmt = $db->prepare("SELECT is_day_off FROM day_off_settings WHERE day_of_week = ?");
    $stmt->execute([$dayOfWeek]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no setting found, assume it's a training day
    if (!$result) {
        return true;
    }

    // Return true if it's NOT a day off
    return !$result['is_day_off'];
}

?>
