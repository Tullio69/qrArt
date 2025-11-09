<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Overview</title>
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 8px; }
    </style>
</head>
<body>
<h1>Analytics Overview</h1>
<?php if (isset($error)) : ?>
    <p><?= esc($error) ?></p>
<?php elseif (isset($report)) : ?>
    <table>
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
        <?php foreach ($report->getRows() as $row): ?>
            <tr>
                <td>Active Users</td>
                <td><?= esc($row->getMetricValues()[0]->getValue()) ?></td>
            </tr>
            <tr>
                <td>Page Views</td>
                <td><?= esc($row->getMetricValues()[1]->getValue()) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else : ?>
    <p>No data.</p>
<?php endif; ?>
</body>
</html>
