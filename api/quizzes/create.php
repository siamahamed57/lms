<?php
// --- Includes & Session ---
require_once __DIR__ . '/../../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../account");
    exit;
}
$userRole = $_SESSION['user_role'] ?? 'student';
if ($userRole !== 'admin' && $userRole !== 'instructor') {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>❌ Access Denied!<br>Only Admins or Instructors can create quizzes.</h2>";
    exit;
}

// --- Message Handling ---
$errors = $_SESSION['quiz_creation_errors'] ?? [];
$success = $_SESSION['quiz_creation_success'] ?? '';
unset($_SESSION['quiz_creation_errors'], $_SESSION['quiz_creation_success']);

// --- Check if we are managing a quiz (adding/editing questions) ---
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$quiz = null;
$questions = [];

// --- Check for question to edit ---
$edit_question_id = isset($_GET['edit_question_id']) ? intval($_GET['edit_question_id']) : 0;
$question_to_edit = null;
$question_options_to_edit = [];

if ($quiz_id > 0) {
    // We are in "Manage Questions" mode
    $quiz_data = db_select("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ?", "i", [$quiz_id]);
    if ($quiz_data) {
        $quiz = $quiz_data[0];
        $questions = db_select("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC", "i", [$quiz_id]);

        // Fetch question to edit if an ID is provided in the URL
        if ($edit_question_id > 0) {
            $question_data = db_select("SELECT * FROM questions WHERE id = ? AND quiz_id = ?", "ii", [$edit_question_id, $quiz_id]);
            if ($question_data) {
                $question_to_edit = $question_data[0];
                if (in_array($question_to_edit['type'], ['mcq', 'true_false'])) {
                    $question_options_to_edit = db_select("SELECT * FROM quiz_options WHERE question_id = ? ORDER BY id ASC", "i", [$edit_question_id]);
                }
            }
        }
    } else {
        $quiz_id = 0; // Reset to show create form if quiz not found
        $errors[] = "The requested quiz was not found.";
    }
}
$question_success = $_SESSION['quiz_question_success'] ?? '';
$question_error = $_SESSION['quiz_question_error'] ?? '';
unset($_SESSION['quiz_question_success'], $_SESSION['quiz_question_error']);

// --- Fetch Data for Form Dropdowns ---
$coursesQuery = "SELECT id, title FROM courses";
$queryParams = [];
$queryTypes = '';
if ($userRole === 'instructor') {
    $coursesQuery .= " WHERE instructor_id = ?";
    $queryParams[] = $_SESSION['user_id'];
    $queryTypes .= 'i';
}
$coursesQuery .= " ORDER BY title ASC";
$courses = db_select($coursesQuery, $queryTypes, $queryParams);

?>
<!-- Using styles from lesson create page for consistency -->
<style>
    /* Re-using styles from lesson creation for consistency */
    :root {
        --primary-color: #8b5cf6; --primary-light: #a78bfa; --secondary-color: #f3f4f6;
        --text-color: #374151; --placeholder-color: #9ca3af; --bg-color: #e5e7eb;
        --card-bg: #ffffff; --border-color: #d1d5db; --shadow-light: rgba(0, 0, 0, 0.08);
        --shadow-dark: rgba(0, 0, 0, 0.12);
    }
    .app-container { width: 1280px; height: 820px; background: var(--card-bg); border-radius: 12px; box-shadow: 0 10px 20px var(--shadow-light), 0 6px 6px var(--shadow-dark); overflow: hidden; display: flex; flex-direction: column; }
    .form-header { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: white; padding: 1.5rem 2rem; }
    .form-header h1 { font-size: 1.75rem; font-weight: 700; }
    .form-header p { font-size: 0.9rem; opacity: 0.9; }
    .form-container { flex: 1; padding: 1.5rem 2rem; overflow-y: auto; }
    .form-section-title { font-size: 1.2rem; font-weight: 600; color: var(--primary-color); margin: 1.5rem 0 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; }
    .form-section-title:first-of-type { margin-top: 0; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
    .form-group { margin-bottom: 1.2rem; }
    label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.85rem; color: #4b5563; }
    input:not([type="checkbox"]), textarea, select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; color: var(--text-color); background-color: var(--secondary-color); transition: all 0.3s ease-in-out; }
    input:focus, textarea:focus, select:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); background-color: #fff; }
    .help-text { font-size: 0.75rem; color: var(--placeholder-color); margin-top: 0.5rem; }
    .required::after { content: '*'; color: #ef4444; margin-left: 4px; }
    .btn-group { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
    .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease-in-out; }
    .btn-primary { background: var(--primary-color); color: white; }
    .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
    .btn-secondary { background: var(--secondary-color); color: var(--text-color); border: 1px solid var(--border-color); }
    .alert { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .checkbox-group { display: flex; align-items: center; gap: 0.75rem; }
    .checkbox-group input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary-color); }
    /* Styles for question management */
    .question-list { list-style: none; padding: 0; margin-top: 1rem; }
    .question-list li { background: #f9fafb; padding: 10px 15px; border-radius: 6px; margin-bottom: 8px; border-left: 4px solid var(--primary-light); display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease; }
    .question-list li:hover { background: #f3f4f6; }
    .question-actions { display: flex; align-items: center; gap: 1rem; }
    .action-btn-edit, .action-btn-delete { font-size: 0.8rem; font-weight: 600; cursor: pointer; text-decoration: none; padding: 4px 8px; border-radius: 4px; border: none; }
    .action-btn-edit { color: #3b82f6; background-color: rgba(59, 130, 246, 0.1); }
    .action-btn-delete { color: #ef4444; background-color: rgba(239, 68, 68, 0.1); }
    .question-list .q-type { font-size: 0.8rem; color: #6b7280; background: #e5e7eb; padding: 2px 8px; border-radius: 10px; }
    .mcq-option-item { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .mcq-option-item input[type="radio"] { flex-shrink: 0; }
    .mcq-option-item input[type="text"] { flex-grow: 1; }
    .remove-option-btn { background: #fee2e2; color: #b91c1c; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold; line-height: 24px; text-align: center; }
    #add-mcq-option-btn { margin-top: 10px; padding: 5px 10px; font-size: 0.8rem; background: #e5e7eb; border: 1px solid #d1d5db; }
</style>

<div class="app-container">
    <?php if ($quiz_id > 0 && $quiz): ?>
        <!-- UI for Adding Questions to an Existing Quiz -->
        <div class="form-header">
            <h1>Manage Questions: <?= htmlspecialchars($quiz['title']) ?></h1>
            <p>Course: <?= htmlspecialchars($quiz['course_title']) ?></p>
        </div>
        <div class="form-container">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($question_success): ?><div class="alert alert-success"><?= htmlspecialchars($question_success); ?></div><?php endif; ?>
            <?php if ($question_error): ?><div class="alert alert-error"><?= htmlspecialchars($question_error); ?></div><?php endif; ?>

            <h3 class="form-section-title">Existing Questions (<?= count($questions) ?>)</h3>
            <div id="existing-questions-list">
                <?php if (empty($questions)): ?>
                    <p>No questions have been added to this quiz yet.</p>
                <?php else: ?>
                    <ul class="question-list">
                        <?php foreach ($questions as $q): ?>
                            <li id="question-<?= $q['id'] ?>">
                                <span><?= htmlspecialchars($q['question_text']) ?></span>
                                <div class="question-actions">
                                    <span class="q-type"><?= htmlspecialchars($q['type']) ?> - <?= $q['marks'] ?> marks</span>
                                    <a href="?page=create-quiz&quiz_id=<?= $quiz_id ?>&edit_question_id=<?= $q['id'] ?>#add-question-form" class="action-btn-edit">Edit</a>
                                    <button type="button" class="action-btn-delete" onclick="deleteQuestion(<?= $q['id'] ?>, '<?= htmlspecialchars(addslashes($q['question_text']), ENT_QUOTES) ?>')">Delete</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <h3 class="form-section-title" id="add-question-form" style="margin-top: 2rem;">
                <?= $question_to_edit ? 'Edit Question' : 'Add New Question' ?>
            </h3>
            <form method="POST" action="api/quizzes/manage-logic.php">
                <input type="hidden" name="action" value="<?= $question_to_edit ? 'update_question' : 'add_question' ?>">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                <?php if ($question_to_edit): ?>
                    <input type="hidden" name="question_id" value="<?= $question_to_edit['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="question_text" class="required">Question Text</label>
                    <textarea name="question_text" id="question_text" rows="3" required placeholder="e.g., What is the capital of France?"><?= htmlspecialchars($question_to_edit['question_text'] ?? '') ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="question_type" class="required">Question Type</label>
                        <select name="question_type" id="question_type" required>
                            <option value="mcq" <?= (($question_to_edit['type'] ?? 'mcq') === 'mcq') ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                            <option value="true_false" <?= (($question_to_edit['type'] ?? '') === 'true_false') ? 'selected' : '' ?>>True / False</option>
                            <option value="short_answer" <?= (($question_to_edit['type'] ?? '') === 'short_answer') ? 'selected' : '' ?>>Short Answer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marks" class="required">Marks</label>
                        <input type="number" name="marks" id="marks" value="<?= htmlspecialchars($question_to_edit['marks'] ?? '1') ?>" min="1" required>
                    </div>
                </div>

                <div id="question-type-options" class="form-group">
                    <div id="mcq-options-container" class="question-type-specific">
                        <label class="required">Options & Correct Answer</label>
                        <div id="mcq-options-wrapper">
                            <?php if ($question_to_edit && $question_to_edit['type'] === 'mcq'): ?>
                                <?php foreach ($question_options_to_edit as $index => $option): ?>
                                    <div class="mcq-option-item">
                                        <input type="radio" name="correct_option" value="<?= $index ?>" <?= $option['is_correct'] ? 'checked' : '' ?> required>
                                        <input type="text" name="options[]" placeholder="Option <?= $index + 1 ?>" value="<?= htmlspecialchars($option['option_text']) ?>" required>
                                        <?php if ($index > 1): ?><button type="button" class="remove-option-btn">&times;</button><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="mcq-option-item"><input type="radio" name="correct_option" value="0" required><input type="text" name="options[]" placeholder="Option 1" required></div>
                                <div class="mcq-option-item"><input type="radio" name="correct_option" value="1" required><input type="text" name="options[]" placeholder="Option 2" required></div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-mcq-option-btn" class="btn btn-secondary">Add Option</button>
                    </div>

                    <?php
                    $correct_tf_answer = '';
                    if ($question_to_edit && $question_to_edit['type'] === 'true_false') {
                        foreach ($question_options_to_edit as $opt) {
                            if ($opt['is_correct']) {
                                $correct_tf_answer = strtolower($opt['option_text']);
                                break;
                            }
                        }
                    }
                    ?>
                    <div id="true_false-options-container" class="question-type-specific" style="display: none;">
                        <label class="required">Correct Answer</label>
                        <div class="checkbox-group">
                            <label><input type="radio" name="true_false_correct" value="true" required <?= $correct_tf_answer === 'true' ? 'checked' : '' ?>> True</label>
                            <label><input type="radio" name="true_false_correct" value="false" required <?= $correct_tf_answer === 'false' ? 'checked' : '' ?>> False</label>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <?php if ($question_to_edit): ?>
                        <a href="?page=create-quiz&quiz_id=<?= $quiz_id ?>" class="btn btn-secondary">Cancel Edit</a>
                    <?php else: ?>
                        <a href="?page=manage-quizzes" class="btn btn-secondary">Finish & View All Quizzes</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $question_to_edit ? 'Update Question' : 'Add This Question' ?></button>
                </div>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questionTypeSelect = document.getElementById('question_type');
            const allTypeContainers = document.querySelectorAll('.question-type-specific');
            
            function toggleQuestionTypeFields() {
                const selectedType = questionTypeSelect.value;
                allTypeContainers.forEach(container => {
                    const isVisible = container.id.startsWith(selectedType);
                    container.style.display = isVisible ? 'block' : 'none';
                    
                    // Disable inputs in hidden containers to prevent validation errors
                    // and to ensure they are not submitted with the form.
                    container.querySelectorAll('input, button').forEach(input => {
                        input.disabled = !isVisible;
                    });
                });
            }

            questionTypeSelect.addEventListener('change', toggleQuestionTypeFields);

            const addMcqOptionBtn = document.getElementById('add-mcq-option-btn');
            const mcqOptionsWrapper = document.getElementById('mcq-options-wrapper');
            let optionCounter = 2;

            addMcqOptionBtn.addEventListener('click', function() {
                const newItem = document.createElement('div');
                newItem.className = 'mcq-option-item';
                newItem.innerHTML = `<input type="radio" name="correct_option" value="${optionCounter}" required><input type="text" name="options[]" placeholder="Option ${optionCounter + 1}" required><button type="button" class="remove-option-btn">&times;</button>`;
                mcqOptionsWrapper.appendChild(newItem);
                optionCounter++;
            });

            mcqOptionsWrapper.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-option-btn')) {
                    e.target.parentElement.remove();
                }
            });

            // Initial call to set the correct state on page load
            toggleQuestionTypeFields();
        });

        function deleteQuestion(questionId, questionText) {
            if (confirm(`Are you sure you want to delete the question:\n\n"${questionText}"`)) {
                document.getElementById('question_id_to_delete').value = questionId;
                document.getElementById('delete-question-form').submit();
            }
        }
        </script>

        <form id="delete-question-form" method="POST" action="api/quizzes/manage-logic.php" style="display: none;">
            <input type="hidden" name="action" value="delete_question">
            <input type="hidden" name="question_id_to_delete" id="question_id_to_delete">
            <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
        </form>

    <?php else: ?>
        <!-- Original UI for Creating a New Quiz -->
        <div class="form-header">
            <h1>Create a New Quiz</h1>
            <p>Design and configure quizzes for your courses with various settings and rules.</p>
        </div>
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?><li>• <?= htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="api/quizzes/manage-logic.php">
                <input type="hidden" name="action" value="create_quiz">
                <h3 class="form-section-title">Basic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title" class="required">Quiz Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Chapter 1 Review Quiz">
                    </div>
                    <div class="form-group">
                        <label for="course_id" class="required">Course</label>
                        <select name="course_id" id="course_id" required>
                            <option value="">-- Select a Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id']; ?>"><?= htmlspecialchars($course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description / Instructions</label>
                    <textarea id="description" name="description" rows="4" placeholder="Provide instructions or a brief overview of the quiz."></textarea>
                </div>
                <h3 class="form-section-title">Quiz Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="duration">Duration (in minutes)</label>
                        <input type="number" id="duration" name="duration" min="0" value="0">
                        <p class="help-text">Set to 0 for no time limit.</p>
                    </div>
                    <div class="form-group">
                        <label for="attempts_allowed">Attempts Allowed</label>
                        <input type="number" id="attempts_allowed" name="attempts_allowed" min="0" value="1">
                        <p class="help-text">Set to 0 for unlimited attempts.</p>
                    </div>
                    <div class="form-group">
                        <label for="pass_mark">Passing Percentage (%)</label>
                        <input type="number" id="pass_mark" name="pass_mark" min="0" max="100" value="50">
                        <p class="help-text">The minimum score required to pass.</p>
                    </div>
                    <div class="form-group">
                        <label for="status" class="required">Status</label>
                        <select name="status" id="status" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                <h3 class="form-section-title">Behavior & Feedback</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Question Order</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="randomize_questions" name="randomize_questions" value="1">
                            <label for="randomize_questions">Randomize question order for each attempt</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Feedback</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="show_feedback_immediately" name="show_feedback_immediately" value="1" checked>
                            <label for="show_feedback_immediately">Show feedback immediately after attempt</label>
                        </div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary">Create Quiz & Add Questions</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>