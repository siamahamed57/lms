<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization & Data Fetching ---
if (!isset($_SESSION['user_id'])) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Please log in to take this quiz.</h2></div>";
    exit;
}
$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($quiz_id <= 0) {
    die("Invalid quiz specified.");
}

// --- Fetch quiz, course, and questions ---
$quiz_data = db_select("SELECT q.*, c.id as course_id, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ?", "i", [$quiz_id]);

if (empty($quiz_data)) {
    die("Quiz not found.");
}
$quiz = $quiz_data[0];
$course_id = $quiz['course_id'];

// --- Security Check: Ensure student is enrolled ---
$enrollment_check_raw = db_select("SELECT id, expires_at, status FROM enrollments WHERE student_id = ? AND course_id = ?", "ii", [$student_id, $course_id]);
if (empty($enrollment_check_raw)) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied.</h2><p>You are not enrolled in this course.</p></div>";
    exit;
}
$enrollment_check = $enrollment_check_raw[0];
if ($enrollment_check['status'] === 'blocked') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Blocked.</h2><p>Your access to this course has been blocked by an administrator.</p></div>";
    exit;
}
if ($enrollment_check['expires_at'] !== null && strtotime($enrollment_check['expires_at']) < time()) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Expired.</h2><p>Your access to this course has expired. Please re-enroll to continue.</p></div>";
    exit;
}

// --- Fetch full curriculum for the sidebar ---
$curriculum_sql = "
    (SELECT id, title, 'lesson' as type, order_no, quiz_id FROM lessons WHERE course_id = ?)
    UNION
    (SELECT id, title, 'quiz' as type, 999 as order_no, NULL as quiz_id FROM quizzes WHERE course_id = ?)
    ORDER BY order_no, title
";
$curriculum_items = db_select($curriculum_sql, "ii", [$course_id, $course_id]);

// --- Fetch student's completion status for all lessons in this course ---
$completed_lessons_raw = db_select("SELECT lesson_id FROM student_lesson_completion WHERE student_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)", "ii", [$student_id, $course_id]);
$completed_lesson_ids = array_column($completed_lessons_raw, 'lesson_id');


// --- Fetch Questions and Options ---
$questions_raw = db_select("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", "i", [$quiz_id]);
$questions = [];
if (!empty($questions_raw)) {
    $question_ids = array_column($questions_raw, 'id');
    $options_sql = "SELECT * FROM quiz_options WHERE question_id IN (" . implode(',', array_fill(0, count($question_ids), '?')) . ") ORDER BY id ASC";
    $options_raw = db_select($options_sql, str_repeat('i', count($question_ids)), $question_ids);
    
    $options_by_question = [];
    foreach ($options_raw as $option) {
        $options_by_question[$option['question_id']][] = $option;
    }

    foreach ($questions_raw as $q) {
        $q['options'] = $options_by_question[$q['id']] ?? [];
        $questions[] = $q;
    }
}
?>
<style>
    .learning-wrapper { display: flex; height: calc(100vh - 80px); background: var(--bg-secondary); }
    .learning-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
    .learning-sidebar { flex: 0 0 350px; background: var(--surface); border-left: 1px solid var(--border); overflow-y: auto; padding: 1.5rem; }
    .curriculum-list { list-style: none; padding: 0; margin-top: 1rem; }
    .curriculum-header { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-light); padding: 1rem 0.8rem 0.5rem; border-bottom: 1px solid var(--border); margin-bottom: 0.5rem; }
    .curriculum-item { display: flex; align-items: center; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; transition: background-color 0.2s; }
    .curriculum-item a { text-decoration: none; color: var(--text-light); font-weight: 500; flex-grow: 1; }
    .curriculum-item:hover { background-color: var(--surface-light); }
    .curriculum-item.active { background-color: rgba(185, 21, 255, 0.1); border-left: 4px solid var(--primary); }
    .curriculum-item.active a { color: var(--primary); font-weight: 700; }
    .curriculum-item .icon { width: 20px; text-align: center; margin-right: 1rem; }
    .completed .icon { color: var(--success); }
    .quiz-container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: var(--surface); border-radius: 16px; }
    .quiz-header h1 { font-size: 2rem; font-weight: 700; color: var(--text); }
    .quiz-header p { color: var(--text-light); margin-bottom: 2rem; }
    .question-block { margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
    .question-text { font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem; }
    .options-list { list-style: none; padding: 0; }
    .option-item { margin-bottom: 0.75rem; }
    .option-item label { display: flex; align-items: center; padding: 0.8rem; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; transition: all 0.2s; }
    .option-item label:hover { background: var(--surface-light); border-color: var(--primary); }
    .option-item input[type="radio"] { margin-right: 1rem; accent-color: var(--primary); }
    .btn-submit-quiz { display: block; width: 100%; padding: 1rem; font-size: 1.1rem; font-weight: 700; color: white; background: var(--gradient); border: none; border-radius: 8px; cursor: pointer; margin-top: 2rem; }

    @media (max-width: 768px) {
        .learning-wrapper { flex-direction: column; height: auto; }
        .learning-sidebar { flex: 1 1 auto; border-left: none; border-top: 1px solid var(--border); max-height: 50vh; }
        .learning-content { padding: 1rem; }
        .quiz-container { margin: 0; padding: 1rem; }
        .quiz-header h1 { font-size: 1.5rem; }
    }
</style>

<div class="learning-wrapper">
    <div class="learning-content">
        <div class="quiz-container">
            <div class="quiz-header">
                <h1><?= htmlspecialchars($quiz['title']) ?></h1>
                <p><?= htmlspecialchars($quiz['description']) ?></p>
            </div>

            <?php if (empty($questions)): ?>
                <p>This quiz has no questions yet. Please check back later.</p>
            <?php else: ?>
                <form action="?page=submit_quiz" method="POST">
                    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-block">
                            <p class="question-text"><?= ($index + 1) . '. ' . htmlspecialchars($question['question_text']) ?></p>
                            
                            <?php if ($question['type'] === 'mcq' || $question['type'] === 'true_false'): ?>
                                <ul class="options-list">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <li class="option-item">
                                            <label>
                                                <input type="radio" name="answers[<?= $question['id'] ?>]" value="<?= $option['id'] ?>" required>
                                                <span><?= htmlspecialchars($option['option_text']) ?></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif ($question['type'] === 'short_answer'): ?>
                                <textarea name="answers[<?= $question['id'] ?>]" rows="3" class="w-full p-2 border rounded" placeholder="Your answer..."></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn-submit-quiz">Submit Quiz</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <aside class="learning-sidebar">
        <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($quiz['course_title']) ?></h3>
        <ul class="curriculum-list">
            <?php 
            $current_type = '';
            foreach ($curriculum_items as $item): 
                if ($item['type'] !== $current_type) {
                    $current_type = $item['type'];
                    echo '<li class="curriculum-header">' . htmlspecialchars(ucfirst($current_type)) . 's</li>';
                }
                $is_current = ($item['type'] === 'quiz' && $item['id'] == $quiz_id);
                $is_item_completed = ($item['type'] === 'lesson' && in_array($item['id'], $completed_lesson_ids));
                $link = ($item['type'] === 'lesson') ? '?page=lesson&id=' . $item['id'] : '?page=quiz&id=' . $item['id'];
                $icon_class = $is_item_completed ? 'fa-check-circle' : ($item['type'] === 'quiz' ? 'fa-question-circle' : 'fa-play-circle');
            ?>
                <li class="curriculum-item <?= $is_current ? 'active' : '' ?> <?= $is_item_completed ? 'completed' : '' ?>">
                    <i class="fas <?= $icon_class ?> icon"></i>
                    <a href="<?= $link ?>"><?= htmlspecialchars($item['title']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>
```

### 3. New File: Quiz Submission Logic

This file handles the grading and saving of a student's quiz attempt.

```diff
--- /dev/null
+++ b/c/xampp/htdocs/lms/api/quizzes/submit_quiz.php
@@ -0,0 +1,78 @@
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';

// --- Authorization & Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    die("Invalid request.");
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$submitted_answers = $_POST['answers'] ?? [];

if ($quiz_id <= 0 || empty($submitted_answers)) {
    die("Missing quiz data.");
}

// --- Fetch Correct Answers ---
$questions_sql = "
    SELECT q.id as question_id, q.marks, qo.id as correct_option_id
    FROM questions q
    LEFT JOIN quiz_options qo ON q.id = qo.question_id AND qo.is_correct = 1
    WHERE q.quiz_id = ?
";
$correct_answers_raw = db_select($questions_sql, "i", [$quiz_id]);

$correct_answers = [];
foreach ($correct_answers_raw as $row) {
    $correct_answers[$row['question_id']] = [
        'marks' => $row['marks'],
        'correct_option_id' => $row['correct_option_id']
    ];
}

// --- Grade the Quiz ---
$total_score = 0;
$total_possible_marks = 0;

foreach ($correct_answers as $question_id => $answer_data) {
    $total_possible_marks += $answer_data['marks'];
    if (isset($submitted_answers[$question_id])) {
        $student_answer = $submitted_answers[$question_id];
        // For now, we only grade MCQ/True-False
        if ($student_answer == $answer_data['correct_option_id']) {
            $total_score += $answer_data['marks'];
        }
    }
}

$percentage_score = ($total_possible_marks > 0) ? round(($total_score / $total_possible_marks) * 100) : 0;

// --- Save the Attempt ---
global $conn;
$conn->begin_transaction();
try {
    // Create the attempt record
    $attempt_sql = "INSERT INTO student_quiz_attempts (student_id, quiz_id, score, completed_at) VALUES (?, ?, ?, NOW())";
    $attempt_id = db_execute($attempt_sql, "iid", [$student_id, $quiz_id, $percentage_score]);

    // Save individual answers
    $answer_sql = "INSERT INTO student_quiz_answers (attempt_id, question_id, selected_option_id) VALUES (?, ?, ?)";
    foreach ($submitted_answers as $question_id => $option_id) {
        db_execute($answer_sql, "iii", [$attempt_id, $question_id, $option_id]);
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error saving quiz results: " . $e->getMessage());
}

// --- Redirect to a results page (for now, back to My Courses) ---
// In a future step, you could create a dedicated results page.
$_SESSION['quiz_result_message'] = "Quiz submitted! You scored {$total_score}/{$total_possible_marks} ({$percentage_score}%).";
header("Location: ?page=my-courses");
exit;
?>