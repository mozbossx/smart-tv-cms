<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of San Carlos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        :root {
            --primary-color: #4a1e78;
            --secondary-color: #6a3fa3;
            --background-color: #f4f4f8;
            --text-color: #333;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .navbar {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px 0;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            align-items: center;
        }

        .navbar-nav li {
            margin-left: 20px;
            display: flex;
            align-items: center;
        }

        .navbar-nav a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .navbar-nav a:hover {
            color: #e0e0e0;
        }

        .navbar-nav a svg {
            width: 20px;
            height: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            overflow-y: auto;
            max-height: 400px;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .post {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .post:last-child {
            border-bottom: none;
        }

        .post-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .post-date {
            color: #777;
            font-size: 0.8rem;
        }

        .post-content {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                flex-direction: column;
                align-items: center;
            }

            .navbar-nav li {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <i class="fa fa-bank" style="color: #2ECC71;"></i>
                University of San Carlos
            </div>
            <ul class="navbar-nav">
                <li>
                    <a href="#home">
                        <i class="fa fa-home" style="color: #2ECC71;"></i>
                        Home
                    </a>
                </li>
                <li>
                    <a href="#announcements">
                        <lucide-icon name="megaphone"></lucide-icon>
                        Announcements
                    </a>
                </li>
                <li>
                    <a href="#events">
                        <lucide-icon name="calendar"></lucide-icon>
                        Events
                    </a>
                </li>
                <li>
                    <a href="#news">
                        <lucide-icon name="newspaper"></lucide-icon>
                        News
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard">
            <div class="card" id="announcements-card">
                <h2 class="card-title">Announcements</h2>
                <div id="announcements-content"></div>
            </div>
            <div class="card" id="events-card">
                <h2 class="card-title">Upcoming Events</h2>
                <div id="events-content"></div>
            </div>
            <div class="card" id="news-card">
                <h2 class="card-title">News</h2>
                <div id="news-content"></div>
            </div>
            <div class="card" id="objectives-card">
                <h2 class="card-title">Program Objectives</h2>
                <div id="objectives-content"></div>
            </div>
        </div>
    </div>

    <script>
        // Sample data for different sections
        const announcementsData = [
            {
                title: "Semester Registration Open",
                date: "May 15, 2025",
                content: "Registration for the Fall 2025 semester is now open. Please complete your registration online."
            },
            {
                title: "Scholarship Applications Due",
                date: "June 1, 2025",
                content: "Annual scholarship applications are due. Submit your applications through the student portal."
            }
        ];

        const eventsData = [
            {
                title: "Annual Research Symposium",
                date: "July 10, 2025",
                content: "Join us for our annual research symposium featuring groundbreaking work from our faculty and students."
            },
            {
                title: "Career Fair",
                date: "August 15, 2025",
                content: "Meet potential employers and explore career opportunities across various industries."
            }
        ];

        const newsData = [
            {
                title: "New Computer Science Lab Launched",
                date: "May 20, 2025",
                content: "The university has opened a state-of-the-art computer science laboratory with cutting-edge technology."
            },
            {
                title: "Research Grant Awarded",
                date: "June 5, 2025",
                content: "Our university received a major research grant for innovative sustainable technology projects."
            }
        ];

        const objectivesData = [
            {
                title: "Academic Excellence",
                content: "Provide high-quality education that prepares students for global challenges."
            },
            {
                title: "Research Innovation",
                content: "Foster a culture of research and innovation across all disciplines."
            }
        ];

        // Function to render posts in a section
        function renderPosts(containerId, data) {
            const container = document.getElementById(containerId);
            container.innerHTML = data.map(post => `
                <div class="post">
                    <h3 class="post-title">${post.title}</h3>
                    ${post.date ? `<div class="post-date">${post.date}</div>` : ''}
                    <div class="post-content">${post.content}</div>
                </div>
            `).join('');
        }

        // Render content for each section
        renderPosts('announcements-content', announcementsData);
        renderPosts('events-content', eventsData);
        renderPosts('news-content', newsData);
        renderPosts('objectives-content', objectivesData);

        // Additional interactivity (optional)
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('click', () => {
                card.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    card.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>