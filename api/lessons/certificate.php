<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization & Data Fetching ---
if (!isset($_SESSION['user_id'])) {
    echo "Please log in to view certificates.";
    exit;
}
$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    die("Invalid course specified.");
}

// --- Data Fetching ---
$course_query = "
    SELECT 
        c.title,
        u_student.name as student_name,
        u_instructor.name as instructor_name,
        (SELECT COUNT(id) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(s.id) FROM student_lesson_completion s JOIN lessons l ON s.lesson_id = l.id WHERE s.student_id = ? AND l.course_id = c.id) as completed_lessons
    FROM courses c
    JOIN users u_student ON u_student.id = ?
    JOIN users u_instructor ON u_instructor.id = c.instructor_id
    WHERE c.id = ?
";
$course_data = db_select($course_query, "iii", [$student_id, $student_id, $course_id]);

if (empty($course_data)) {
    die("Certificate data not found.");
}
$course = $course_data[0];

// --- Security Check: Ensure course is completed ---
$is_completed = ($course['total_lessons'] > 0 && $course['completed_lessons'] >= $course['total_lessons']);
if (!$is_completed) {
    die("You have not completed this course yet.");
}

$completion_date = date("F j, Y"); // Use current date for simplicity

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
        body {
            background-color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
        }
        .certificate-container {
            width: 1000px;
            height: 700px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .certificate-border {
            position: absolute;
            top: 20px; left: 20px; right: 20px; bottom: 20px;
            border: 4px double #b915ff;
        }
        .certificate-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #333;
        }
        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #b915ff;
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin: 0;
        }
        .subtitle {
            font-size: 1.2rem;
            margin-top: 10px;
            color: #555;
        }
        .student-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #b915ff;
            margin: 40px 0;
            border-bottom: 2px solid #ddd;
            display: inline-block;
            padding-bottom: 5px;
        }
        .course-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            width: 80%;
            margin-top: 60px;
        }
        .signature-block {
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #555;
            padding-top: 5px;
            font-weight: 600;
        }
        @media print {
            body { background: none; }
            .certificate-container { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="certificate-content">
            <div class="logo"><i class="fas fa-graduation-cap"></i> UNIES</div>
            <h1>Certificate of Completion</h1>
            <p class="subtitle">This certificate is proudly presented to</p>
            <p class="student-name"><?= htmlspecialchars($course['student_name']) ?></p>
            <p>for successfully completing the online course</p>
            <p class="course-title">"<?= htmlspecialchars($course['title']) ?>"</p>
            <div class="footer">
                <div class="signature-block">
                    <p class="signature-line">Date</p>
                    <p><?= $completion_date ?></p>
                </div>
                <div class="signature-block">
                    <p class="signature-line">Instructor</p>
                    <p><?= htmlspecialchars($course['instructor_name']) ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>