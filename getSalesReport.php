<?php
include 'dbConnect.php'; // Ensure the path is correct

header('Content-Type: text/html'); // Ensure the response is HTML

// Get the report type
$reportType = isset($_GET['type']) ? $_GET['type'] : 'daily';

// Determine the date range
switch ($reportType) {
    case 'weekly':
        $startDate = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'monthly':
        $startDate = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'daily':
    default:
        $startDate = date('Y-m-d');
        break;
}

try {
    // Use JOIN to include product names and prices
    $query = $mysqli->prepare("
        SELECT 
            p.name AS product_name, 
            SUM(il.change) AS total_quantity, 
            SUM(il.change * p.price) AS total_revenue, 
            DATE(il.date) AS sale_date
        FROM inventory_log il
        JOIN products p ON il.product_id = p.id
        WHERE il.reason = 'Purchase' AND DATE(il.date) >= ?
        GROUP BY p.name, sale_date
    ");
    $query->bind_param('s', $startDate);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        echo '<table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Total Quantity</th>
                        <th>Total Revenue</th>
                        <th>Sale Date</th>
                    </tr>
                </thead>
                <tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars($row['product_name']) . '</td>
                    <td>' . htmlspecialchars($row['total_quantity']) . '</td>
                    <td>' . number_format($row['total_revenue'], 2) . '</td>
                    <td>' . htmlspecialchars($row['sale_date']) . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No sales data available for this period.</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">An error occurred while fetching sales data: ' . htmlspecialchars($e->getMessage()) . '</p>';
} finally {
    if (isset($query)) {
        $query->close();
    }
    $mysqli->close();
}
?>
