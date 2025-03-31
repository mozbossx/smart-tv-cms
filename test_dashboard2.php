University Dashboard with Topbar

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of San Carlos - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f4f4f4;
        }

        .topbar {
            background-color: #2c3e50;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .topbar-logo {
            font-size: 24px;
            font-weight: bold;
        }

        .topbar-menu {
            display: flex;
            list-style: none;
        }

        .topbar-menu li {
            margin-left: 20px;
        }

        .topbar-menu li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .topbar-menu li a:hover {
            color: #3498db;
        }

        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .dashboard-card:hover {
            transform: scale(1.05);
        }

        .card-icon {
            font-size: 40px;
            margin-right: 15px;
        }

        .card-content h3 {
            font-size: 18px;
            color: #333;
        }

        .card-content p {
            color: #666;
        }

        .activity-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .activity-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .activity-list li {
            list-style: none;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .quick-actions button {
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .quick-actions button:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .activity-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-logo">USC Admin</div>
        <ul class="topbar-menu">
            <li><a href="#" onclick="showSection('dashboard')">Dashboard</a></li>
            <li><a href="#" onclick="showSection('content')">Manage Content</a></li>
            <li><a href="#" onclick="showSection('analytics')">Analytics</a></li>
            <li><a href="#" onclick="showSection('settings')">Settings</a></li>
        </ul>
    </div>

    <div class="main-content" id="dashboardSection">
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">📢</div>
                <div class="card-content">
                    <h3>Announcements</h3>
                    <p>12 Total</p>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-icon">📅</div>
                <div class="card-content">
                    <h3>Upcoming Events</h3>
                    <p>5 Total</p>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-icon">📰</div>
                <div class="card-content">
                    <h3>News Articles</h3>
                    <p>23 Total</p>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-icon">🖼️</div>
                <div class="card-content">
                    <h3>Promo Materials</h3>
                    <p>8 Total</p>
                </div>
            </div>
        </div>

        <div class="activity-section">
            <div class="activity-card">
                <h2>Recent Activities</h2>
                <ul class="activity-list">
                    <li>New announcement published</li>
                    <li>Upcoming event added</li>
                    <li>Promotional material created</li>
                    <li>News article uploaded</li>
                </ul>
            </div>

            <div class="activity-card">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <button>Create Announcement</button>
                    <button>Add Event</button>
                    <button>Publish News</button>
                    <button>Create Promo</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionName) {
            // In a real application, you would show/hide different sections
            console.log(`Showing section: ${sectionName}`);
            // Example of how you might change content dynamically
            document.getElementById('dashboardSection').innerHTML = `
                <h1>${sectionName.toUpperCase()} Section Content</h1>
                <p>This is where ${sectionName} specific content would go.</p>
            `;
        }
    </script>
</body>
</html>