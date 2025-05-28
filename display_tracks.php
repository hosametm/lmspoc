<?php
// Fetch tracks from the API
$response = file_get_contents('http://localhost/Get_all_tracks.php');

$data = json_decode($response, true);

$tracks = $data['data'] ?? [];

?>
<!DOCTYPE html>
<html>

<head>
    <title>All Tracks</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }
    </style>
</head>

<body>
    <h2 style="text-align:center;">All Tracks</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Course Count</th>
                <!-- Add more columns as needed -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tracks as $track): ?>
                <tr>
                    <td><?= htmlspecialchars($track['id']) ?></td>
                    <td><?= htmlspecialchars($track['title']) ?></td>
                    <td><?= htmlspecialchars($track['course_count']) ?></td>
                    <!-- Add more columns as needed -->
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>