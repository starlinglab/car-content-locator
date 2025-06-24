<?php
// Database connection
include "global.php";

// Function to safely get database connection
function getDbConnection() {
    try {
        $conn = getdb();
        return $conn;
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Function to get active deals for a specific attachment ID
function getActiveDeals($attachmentId) {
    $conn = getDbConnection();
    $sql = "SELECT deals.client_id as provider, COUNT(*) as deal_count 
            FROM `files` 
            INNER JOIN file_ranges ON file_ranges.file_id = files.id
            INNER JOIN file_range_car ON file_ranges.file_id = file_range_car.file_range_id
            INNER JOIN cars ON cars.id = file_range_car.car_id
            INNER JOIN deals ON cars.piece_cid = deals.piece_cid
            WHERE `files`.attachment_id = ? AND deals.state = 'active'
            GROUP BY deals.client_id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attachmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $providers = [];
    while ($row = $result->fetch_assoc()) {
        $providers[$row["provider"]] = $row["deal_count"];
    }
    
    $stmt->close();
    return $providers;
}

// Get main USC data count
$conn = getDbConnection();
$sql = "SELECT title_id, COUNT(*) as count 
        FROM usc_data 
        GROUP BY title_id 
        ORDER BY title_id DESC";
        
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Get prepared files count
$conn2 = getDbConnection();
$sql2 = "SELECT title_id, COUNT(*) as count 
         FROM usc_files 
         GROUP BY title_id 
         ORDER BY title_id DESC";
         
$stmt2 = $conn2->prepare($sql2);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Process prepared files data
$preparedFiles = [];
while ($row = $result2->fetch_assoc()) {
    $preparedFiles[$row['title_id']] = $row['count'];
}

// Output HTML header and CSS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USC Data Overview</title>
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #8338ec;
            --dark-text: #212529;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --hover-bg: #e9ecef;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            background-color: var(--light-bg);
            margin: 0;
            padding: 20px;
        }
        
        h1 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: var(--hover-bg);
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .provider-badge {
            display: inline-block;
            background-color: rgba(56, 176, 0, 0.15);
            color: #38b000;
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 0.85rem;
            margin: 2px;
            border: 1px solid rgba(56, 176, 0, 0.2);
        }
        
        .no-data {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>USC Data Overview</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Title ID</th>
                    <th>Total Files</th>
                    <th>Pre-Prepared</th>
                    <th>Providers</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Display results
            while ($row = $result->fetch_assoc()) {
                $titleId = $row['title_id'];
                $totalCount = $row['count'];
                $preparedCount = isset($preparedFiles[$titleId]) ? $preparedFiles[$titleId] : 0;
                
                // Get active deals - commented out for now as it may be too intensive
                // $providers = getActiveDeals($titleId);
                
                echo "<tr>";
                echo "<td><a href='show_collection_files.php?collection={$titleId}'>{$titleId}</a></td>";
                echo "<td>{$totalCount}</td>";
                echo "<td>{$preparedCount}</td>";
                echo "<td><span class='no-data'>View details</span></td>";
                // To enable provider badges, uncomment and replace the above line:
                /*
                echo "<td>";
                if (!empty($providers)) {
                    foreach ($providers as $provider => $count) {
                        echo "<span class='provider-badge'>{$provider} ({$count})</span> ";
                    }
                } else {
                    echo "<span class='no-data'>No active providers</span>";
                }
                echo "</td>";
                */
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
// Close connections
$stmt->close();
$stmt2->close();
$conn->close();
$conn2->close();
?>