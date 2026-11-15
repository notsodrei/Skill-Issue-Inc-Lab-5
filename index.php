<?php
/**
 * Lab Activity 4: To-do List Web App (COMP016 - Web Development)
 * Single-page task manager: Create, Read, Update, Delete using PDO.
 */

// 1. Database connection (Replaces session_start)
require_once 'db.php';
date_default_timezone_set('Asia/Manila');

const NAME_MIN = 3;
const NAME_MAX = 100;
const DESC_MAX = 255;

/* ---------- Helpers ---------- */

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function post_string(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function validate_task(string $name, string $description): array {
    $errors = [];
    if ($name === '') {
        $errors['name'] = 'Task Name cannot be empty.';
    } elseif (mb_strlen($name) < NAME_MIN) {
        $errors['name'] = 'Task Name must be at least ' . NAME_MIN . ' characters long.';
    } elseif (mb_strlen($name) > NAME_MAX) {
        $errors['name'] = 'Task Name cannot exceed ' . NAME_MAX . ' characters.';
    }

    if ($description === '') {
        $errors['description'] = 'Task Description is required.';
    } elseif (mb_strlen($description) > DESC_MAX) {
        $errors['description'] = 'Task Description cannot exceed ' . DESC_MAX . ' characters.';
    }
    return $errors;
}

function redirect_to(string $view): void {
    header('Location: index.php?view=' . urlencode($view));
    exit();
}

function icon(string $name): string {
    $paths = [
        'logo'  => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'todo'  => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>',
        'all'   => '<circle cx="7" cy="7" r="2.6"/><circle cx="17" cy="7" r="2.6"/><circle cx="7" cy="17" r="2.6"/><circle cx="17" cy="17" r="2.6"/>',
        'menu'  => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'tick'  => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}

/* ---------- View + edit target (GET) ---------- */

$views = [
    'all' => ['title' => 'All Tasks', 'nav' => 'All Tasks', 'heading' => 'All tasks', 'empty' => 'No tasks yet.', 'icon' => 'all'],
    'todo' => ['title' => 'To-do', 'nav' => 'To-do', 'heading' => 'Tasks to do', 'empty' => "No tasks to do. You're all caught up!", 'icon' => 'todo'],
    'completed' => ['title' => 'Completed', 'nav' => 'Completed', 'heading' => 'Completed tasks', 'empty' => 'No completed tasks yet.', 'icon' => 'check'],
];

$view = $_GET['view'] ?? 'todo';
if (!is_string($view) || !isset($views[$view])) {
    $view = 'todo';
}

$editId = null;
if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
}

/* ---------- Form handling (POST) ---------- */

$errors    = [];
$old       = ['name' => '', 'description' => ''];
$postedOld = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_string('action');
    $id     = (int) post_string('id');

    switch ($action) {
        case 'add':
            $name        = post_string('task_name');
            $description = post_string('task_description');
            $errors      = validate_task($name, $description);

            if (!$errors) {
                // ERIN'S PART: CREATE (INSERT)
                $stmt = $pdo->prepare("INSERT INTO tasks (task_name, task_description, status) VALUES (:name, :desc, 'pending')");
                $stmt->execute([
                    'name' => $name,
                    'desc' => $description
                ]);
                redirect_to($view);
            }

            $old       = ['name' => $name, 'description' => $description];
            $postedOld = true;
            break;

        case 'update':
            // TODO: Teammates will add UPDATE PDO query here
            redirect_to($view);
            break;

        case 'toggle':
            // TODO: Teammates will add toggle (UPDATE status) PDO query here
            redirect_to($view);
            break;

        case 'delete':
            // TODO: Teammates will add DELETE PDO query here
            redirect_to($view);
            break;

        case 'clear_completed':
            // TODO: Teammates will add DELETE where status='completed' PDO query here
            redirect_to($view);
            break;

        default:
            redirect_to($view);
    }
}

// Pre-fill the form when editing 
if ($editId !== null && !$postedOld) {
    $stmt = $pdo->prepare("SELECT task_name, task_description FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $taskToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($taskToEdit) {
        $old = [
            'name'        => $taskToEdit['task_name'],
            'description' => $taskToEdit['task_description'],
        ];
    }
}

/* ---------- Data for rendering ---------- */

// ERIN'S PART: READ (SELECT)
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at ASC");
$dbTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allTasks = [];
foreach ($dbTasks as $row) {
    $allTasks[] = [
        'id'          => (int) $row['id'],
        'name'        => $row['task_name'],
        'description' => $row['task_description'],
        'completed'   => ($row['status'] === 'completed')
    ];
}

$counts   = [
    'all'       => count($allTasks),
    'todo'      => count(array_filter($allTasks, fn(array $t): bool => !$t['completed'])),
    'completed' => count(array_filter($allTasks, fn(array $t): bool => $t['completed'])),
];

$visibleTasks = array_values(array_filter(
    $allTasks,
    fn(array $t): bool => match ($view) {
        'todo'      => !$t['completed'],
        'completed' => $t['completed'],
        default     => true,
    }
));

if ($view === 'all') {
    usort($visibleTasks, fn(array $a, array $b): int => $a['completed'] <=> $b['completed']);
}

$isEditing = $editId !== null;
$panelOpen = $isEditing || $errors || isset($_GET['new']);
$today     = new DateTimeImmutable('now');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($views[$view]['title']) ?> - To-do List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="<?= $panelOpen ? 'sheet-open' : '' ?>" data-focus-form="<?= $panelOpen ? 'true' : 'false' ?>">

<div class="app">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar" aria-label="Sidebar">
        <div class="sidebar-head">
            <a class="brand" href="index.php?view=todo">
                <?= icon('logo') ?>
                <span class="brand-name">To-do List</span>
            </a>
            <button type="button" class="icon-btn sidebar-close" data-close-drawer aria-label="Close menu">
                <?= icon('close') ?>
            </button>
        </div>

        <button type="button" class="btn btn-primary btn-new" data-new-task
                data-new-url="index.php?view=<?= e($view) ?>&amp;new=1" aria-label="New Task">
            <span aria-hidden="true">+</span><span class="btn-label"> New Task</span>
        </button>

        <nav class="nav" aria-label="Task views">
            <ul>
                <?php foreach ($views as $key => $info): ?>
                    <li>
                        <a class="nav-link<?= $view === $key ? ' is-active' : '' ?>"
                           href="index.php?view=<?= e($key) ?>"
                           <?= $view === $key ? 'aria-current="page"' : '' ?>>
                            <?= icon($info['icon']) ?>
                            <span class="nav-label"><?= e($info['nav']) ?></span>
                            <span class="badge"><?= (int) $counts[$key] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="content">

        <!-- Top bar -->
        <header class="topbar">
            <button type="button" class="icon-btn menu-btn" data-open-drawer
                    aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
                <?= icon('menu') ?>
            </button>
            <h1 class="topbar-title"><?= e($views[$view]['title']) ?></h1>
            <p class="topbar-date">
                <time datetime="<?= e($today->format('Y-m-d')) ?>">
                    <span class="date-long"><?= e($today->format('l, F j, Y')) ?></span>
                    <span class="date-short"><?= e($today->format('D, M j, Y')) ?></span>
                </time>
            </p>
        </header>

        <main class="main">

            <!-- Summary -->
            <section class="stats" aria-label="Task summary">
                <dl class="stat">
                    <dt>Total Tasks</dt>
                    <dd><?= (int) $counts['all'] ?></dd>
                </dl>
                <dl class="stat stat-todo">
                    <dt>To-do</dt>
                    <dd><?= (int) $counts['todo'] ?></dd>
                </dl>
                <dl class="stat stat-done">
                    <dt>Completed</dt>
                    <dd><?= (int) $counts['completed'] ?></dd>
                </dl>
            </section>

            <button type="button" class="btn btn-primary btn-new-mobile" data-new-task
                    data-new-url="index.php?view=<?= e($view) ?>&amp;new=1">+ New Task</button>

            <div class="workspace">

                <!-- Task list (Read) -->
                <section class="list-section" aria-labelledby="list-title">
                    <div class="list-head">
                        <h2 id="list-title"><?= e($views[$view]['heading']) ?></h2>

                        <?php if ($view === 'completed' && $counts['completed'] > 0): ?>
                            <form method="POST" data-confirm
                                  data-confirm-title="Clear completed tasks?"
                                  data-confirm-text="This will permanently delete all completed tasks.">
                                <input type="hidden" name="action" value="clear_completed">
                                <button type="submit" class="btn btn-secondary">Clear Completed</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($view === 'completed' && $counts['completed'] > 0): ?>
                        <p class="hint">Uncheck a task to move it back to To-do.</p>
                    <?php endif; ?>

                    <?php if (!$visibleTasks): ?>
                        <div class="empty">
                            <?= icon($views[$view]['icon']) ?>
                            <p><?= e($views[$view]['empty']) ?></p>
                        </div>
                    <?php else: ?>
                        <ul class="task-list">
                            <?php foreach ($visibleTasks as $task): ?>
                                <li class="task<?= $task['completed'] ? ' is-complete' : '' ?><?= $task['id'] === $editId ? ' is-editing' : '' ?>">

                                    <form method="POST" class="task-check">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                        <button type="submit" class="check"
                                                aria-label="<?= e(($task['completed'] ? 'Move back to To-do: ' : 'Mark as completed: ') . $task['name']) ?>">
                                            <?= icon('tick') ?>
                                        </button>
                                    </form>

                                    <div class="task-body">
                                        <h3 class="task-title"><?= e($task['name']) ?></h3>
                                        <p class="task-desc"><?= e($task['description']) ?></p>
                                    </div>

                                    <div class="task-actions">
                                        <a class="btn btn-secondary"
                                           href="index.php?view=<?= e($view) ?>&amp;edit=<?= (int) $task['id'] ?>">Edit</a>

                                        <form method="POST" data-confirm
                                              data-confirm-title="Delete task?"
                                              data-confirm-text="Are you sure you want to delete this task?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <!-- Task form (Create / Update) -->
                <section class="panel" id="task-panel" aria-labelledby="panel-title"
                         data-mode="<?= $isEditing ? 'edit' : 'add' ?>"
                         data-cancel-url="index.php?view=<?= e($view) ?>">
                    <div class="panel-head">
                        <h2 id="panel-title"><?= $isEditing ? 'Edit task' : 'Add a task' ?></h2>
                        <button type="button" class="icon-btn panel-close" data-close-sheet aria-label="Close">
                            <?= icon('close') ?>
                        </button>
                    </div>

                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'add' ?>">
                        <?php if ($isEditing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editId ?>">
                        <?php endif; ?>

                        <div class="field<?= isset($errors['name']) ? ' field--error' : '' ?>">
                            <label for="task_name">Task Name</label>
                            <input type="text" id="task_name" name="task_name"
                                   placeholder="Enter task name" autocomplete="off"
                                   value="<?= e($old['name']) ?>"
                                   <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="task_name_error"' : '' ?>>
                            <?php if (isset($errors['name'])): ?>
                                <p class="field-error" id="task_name_error" role="alert"><?= e($errors['name']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field<?= isset($errors['description']) ? ' field--error' : '' ?>">
                            <label for="task_description">Task Description</label>
                            <textarea id="task_description" name="task_description" rows="4"
                                      placeholder="Enter task description"
                                      <?= isset($errors['description']) ? 'aria-invalid="true" aria-describedby="task_description_error"' : '' ?>><?= e($old['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <p class="field-error" id="task_description_error" role="alert"><?= e($errors['description']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?= $isEditing ? 'Save Changes' : 'Add Task' ?></button>
                            <?php if ($isEditing): ?>
                                <a class="btn btn-secondary" href="index.php?view=<?= e($view) ?>">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>

            </div>
        </main>
    </div>
</div>

<div class="backdrop" data-backdrop></div>

<!-- Confirmation dialog -->
<dialog class="dialog" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-text">
    <h2 id="confirm-title">Delete task?</h2>
    <p id="confirm-text">Are you sure you want to delete this task?</p>
    <div class="dialog-actions">
        <button type="button" class="btn btn-secondary" data-dialog-cancel>Cancel</button>
        <button type="button" class="btn btn-confirm" data-dialog-confirm>Confirm</button>
    </div>
</dialog>

</body>
</html>