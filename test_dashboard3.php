<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of San Carlos - Dashboard</title>
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
            background-color: #6d7f3f;
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
            color: #e0e0e0;
        }

        .search-container {
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .search-input {
            width: 100%;
            max-width: 600px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .card-header {
            background-color: #6d7f3f;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 15px;
        }

        .card-footer {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: green;
        }

        .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .card-details-item {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }

        .view-contents-btn {
            background-color: #6d7f3f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .view-contents-btn:hover {
            background-color: #5a6b35;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-logo">USC Admin</div>
        <ul class="topbar-menu">
            <li><a href="#">Home</a></li>
            <li><a href="#">Create Post</a></li>
            <li><a href="#">Manage Contents</a></li>
            <li><a href="#">Super Admin Options</a></li>
        </ul>
    </div>

    <div class="search-container">
        <input type="text" class="search-input" placeholder="Search TVs">
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <span>Computer Engineering</span>
                <div class="status-indicator"></div>
            </div>
            <div class="card-body">
                <div class="card-details">
                    <div class="card-details-item">
                        <strong>ID</strong>
                        <p>252</p>
                    </div>
                    <div class="card-details-item">
                        <strong>Department</strong>
                        <p>Computer Engineering</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="view-contents-btn">View Contents</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Computer Engineering</span>
                <div class="status-indicator"></div>
            </div>
            <div class="card-body">
                <div class="card-details">
                    <div class="card-details-item">
                        <strong>ID</strong>
                        <p>255</p>
                    </div>
                    <div class="card-details-item">
                        <strong>Department</strong>
                        <p>Computer Engineering</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="view-contents-btn">View Contents</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Computer Engineering</span>
                <div class="status-indicator"></div>
            </div>
            <div class="card-body">
                <div class="card-details">
                    <div class="card-details-item">
                        <strong>ID</strong>
                        <p>255</p>
                    </div>
                    <div class="card-details-item">
                        <strong>Department</strong>
                        <p>Computer Engineering</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="view-contents-btn">View Contents</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Computer Engineering</span>
                <div class="status-indicator"></div>
            </div>
            <div class="card-body">
                <div class="card-details">
                    <div class="card-details-item">
                        <strong>ID</strong>
                        <p>255</p>
                    </div>
                    <div class="card-details-item">
                        <strong>Department</strong>
                        <p>Computer Engineering</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="view-contents-btn">View Contents</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Computer Engineering</span>
                <div class="status-indicator"></div>
            </div>
            <div class="card-body">
                <div class="card-details">
                    <div class="card-details-item">
                        <strong>ID</strong>
                        <p>255</p>
                    </div>
                    <div class="card-details-item">
                        <strong>Department</strong>
                        <p>Computer Engineering</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="view-contents-btn">View Contents</button>
            </div>
        </div>

    </div>

    <script>
        // Optional: Add search functionality
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                card.style.display = cardText.includes(searchTerm) ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>