<?php 
session_start();
include("./db/connection.php");

// Get gallery images for preview (limit to 6 images) - without approval check
$gallery_stmt = $pdo->prepare("
    SELECT g.*, 
           CASE 
               WHEN g.user_type = 'volunteer' THEN v.name 
               ELSE g.uploaded_by 
           END as display_name
    FROM gallery g
    LEFT JOIN volunteers v ON g.uploaded_by = v.volunteer_id AND g.user_type = 'volunteer'
    ORDER BY g.uploaded_at DESC 
    LIMIT 6
");
$gallery_stmt->execute();
$gallery_images = $gallery_stmt->fetchAll();

// Get statistics for the homepage
$volunteers_count = $pdo->query("SELECT COUNT(*) FROM volunteers")->fetchColumn();
$events_count = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$certificates_count = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navneet College of Arts ,Science & Commerce.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 0;
            text-align: center;
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        .carousel-item img {
            height: 500px;
            object-fit: auto;
        }
        .nav-menu {
            background: #343a40;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-menu a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav-menu a:hover {
            background: #495057;
            color: #ffc107;
        }
        /* Fixed Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        body {
            padding-top: 80px; /* Adjust based on navbar height */
        }
        /* Gallery Styles */
        .gallery-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 0;
        }
        .gallery-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            height: 100%;
            position: relative;
        }
        .gallery-card:hover {
            transform: scale(1.05);
        }
        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        .gallery-card:hover .gallery-overlay {
            transform: translateY(0);
        }
        .brand-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .college-logo {
            height: 60px;
            width: auto;
            border-radius: 5px;
        }
        /* Smooth transitions */
        * {
            transition: all 0.3s ease;
        }
        /* Leadership Section Styles */
.leadership-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.leadership-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: white;
}

.leadership-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.leader-img-container {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    border: 4px solid #007bff;
    border-radius: 50%;
    padding: 4px;
    background: white;
}

.leader-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.leadership-card .card-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.leadership-card .card-subtitle {
    font-size: 0.9rem;
    font-weight: 500;
}

.leadership-card .card-text {
    color: #555;
    line-height: 1.6;
    font-style: italic;
}
/* Gallery Section Styles */
.gallery-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
}

.gallery-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
    z-index: 1;
}

.gallery-section .container {
    position: relative;
    z-index: 2;
}

.stats-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: 20px;
    color: #333;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.category-badge {
    transition: transform 0.3s ease;
}

.category-badge:hover {
    transform: translateY(-3px);
}

.gallery-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.gallery-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

.gallery-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-card:hover .gallery-image {
    transform: scale(1.1);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.8) 100%);
    display: flex;
    align-items: flex-end;
    padding: 20px;
    opacity: 0;
    transition: all 0.3s ease;
}

.gallery-card:hover .gallery-overlay {
    opacity: 1;
}

.overlay-content {
    transform: translateY(20px);
    transition: transform 0.3s ease;
}

.gallery-card:hover .overlay-content {
    transform: translateY(0);
}

.overlay-content h6 {
    color: white;
    font-weight: 600;
    margin-bottom: 5px;
    line-height: 1.3;
}

.overlay-content small {
    color: rgba(255,255,255,0.9);
    font-size: 0.8rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-number {
        font-size: 2rem;
    }
    
    .gallery-image {
        height: 150px;
    }
    
    .category-badge .badge {
        font-size: 0.8rem;
        padding: 8px 12px;
    }
}
    </style>
</head>
<body>
    <!-- Fixed Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="brand-container">
                <a class="navbar-brand" href="index.php">
                    <img src="./assets/images/nss_logo.png" alt="NSS Logo" height="50" class="me-2">
                    Navneet College of Arts ,Science & Commerce.
                </a>
                <img src="./assets/images/college_logo.png" alt="College Logo" class="college-logo">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto" style="font-size: 1.1rem;">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Image Carousel -->
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="./assets/images/event1.png" class="d-block w-100" alt="Blood Donation Camp">
                <div class="carousel-caption d-none d-md-block" style="background-color: rgba(0, 0, 0, 0.5); padding: 10px; border-radius: 5px;">
                    <h5>Blood Donation Camp</h5>
                    <p>Join hands to save lives and make a difference in your community.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="./assets/images/event2.png" class="d-block w-100" alt="Cleanliness Drive">
                <div class="carousel-caption d-none d-md-block"  style="background-color: rgba(0, 0, 0, 0.5); padding: 10px; border-radius: 5px;">
                    <h5>Cleanliness Drive</h5>
                    <p>Together for a cleaner and greener tomorrow.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="./assets/images/event3.png" class="d-block w-100" alt="Awareness Rally">
                <div class="carousel-caption d-none d-md-block"  style="background-color: rgba(0, 0, 0, 0.5); padding: 10px; border-radius: 5px;">
                    <h5>Awareness Rally</h5>
                    <p>Spreading knowledge and building a better future.</p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Welcome to Navneet College of Arts ,Science & Commerce.</h1>
            <p class="lead mb-4">National Service Scheme - Empowering students through community service</p>
            <!--  <div class="mt-4">
                <a href="login.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <a href="register.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Register as Volunteer
                </a>
            </div>-->
        </div>
    </section>

    <!-- Leadership Section -->
<section class="leadership-section py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col">
                <h2 class="display-5 fw-bold">Our Leadership</h2>
                <p class="lead">Guiding the NSS mission with vision and dedication</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <!-- Principal -->
            <div class="col-md-4 mb-4">
                <div class="card leadership-card text-center h-100">
                    <div class="card-body">
                        <div class="leader-img-container mb-3">
                            <img src="./assets/images/principal.png" 
                                 alt="Principal - Navneet College of Science & Arts" 
                                 class="leader-img rounded-circle"
                                 onerror="this.src='./assets/images/placeholder_leader.jpg'">
                        </div>
                        <h5 class="card-title text-primary">Dr. Rajendra S. Dhamnaskar</h5>
                        <p class="card-subtitle mb-2 text-muted">Principal</p>
                        <p class="card-text">
                            <small>
                                "The National Service Scheme embodies our commitment to developing 
                                socially responsible citizens. Through community service, our students 
                                learn empathy, leadership, and the true meaning of education."
                            </small>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- NSS Program Officer -->
            <div class="col-md-4 mb-4">
                <div class="card leadership-card text-center h-100">
                    <div class="card-body">
                        <div class="leader-img-container mb-3">
                            <img src="./assets/images/nss_po.png" 
                                 alt="NSS Program Officer - Navneet College" 
                                 class="leader-img rounded-circle"
                                 onerror="this.src='./assets/images/placeholder_leader.jpg'">
                        </div>
                        <h5 class="card-title text-primary">Prof. Krishnakumar Sharma</h5>
                        <p class="card-subtitle mb-2 text-muted">NSS Program Officer</p>
                        <p class="card-text">
                            <small>
                                "NSS is not just a program; it's a movement that transforms young minds. 
                                Our volunteers are the change-makers who bridge the gap between campus 
                                and community, creating lasting social impact."
                            </small>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Unit Leader -->
            <div class="col-md-4 mb-4">
                <div class="card leadership-card text-center h-100">
                    <div class="card-body">
                        <div class="leader-img-container mb-3">
                            <img src="./assets/images/unit_leader.png" 
                                 alt="NSS Unit Leader - Navneet College" 
                                 class="leader-img rounded-circle"
                                 onerror="this.src='./assets/images/placeholder_leader.jpg'">
                        </div>
                        <h5 class="card-title text-primary">Mr. Rahul Rajkapoor Nag</h5>
                        <p class="card-subtitle mb-2 text-muted">NSS Unit Leader</p>
                        <p class="card-text">
                            <small>
                                "Every NSS activity is an opportunity for growth. I'm proud to lead 
                                such dedicated volunteers who consistently go above and beyond to 
                                serve our community with passion and commitment."
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
     
<!-- Features Section -->
<section class="container my-5">
    <div class="row text-center mb-5">
        <div class="col"> 
            <h2>Why Join NSS?</h2>
            <p class="lead">Make a difference while developing yourself</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">🤝</div>
                    <h5 class="card-title">Community Service</h5>
                    <p class="card-text">Engage in meaningful community development activities and social service programs that create real impact.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">🎓</div>
                    <h5 class="card-title">Certification</h5>
                    <p class="card-text">Earn recognized certificates for your participation and contributions, valuable for your academic and professional journey.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">🌟</div>
                    <h5 class="card-title">Skill Development</h5>
                    <p class="card-text">Develop leadership, teamwork, communication, and organizational skills through hands-on practical experience.</p>
                </div>
            </div>
        </div>
        
        <!-- New Cards -->
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">😊</div>
                    <h5 class="card-title">Entertainment & Joyfulness</h5>
                    <p class="card-text">Experience the joy of serving others while participating in fun activities, cultural events, and memorable celebrations with fellow volunteers.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">👑</div>
                    <h5 class="card-title">Leadership Opportunities</h5>
                    <p class="card-text">Take on leadership roles, organize events, and guide teams to develop essential leadership qualities that last a lifetime.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3" style="font-size: 3rem;">🏕️</div>
                    <h5 class="card-title">NSS 7 Days Camp</h5>
                    <p class="card-text">Participate in our intensive 7-day special camp for immersive community service, team building, and unforgettable experiences in rural development.</p>
                </div>
            </div>
        </div>
    </div>
</section>

 <!-- Gallery Section -->
<section id="gallery" class="gallery-section py-5">
    <div class="container">

        <!-- Section Heading -->
        <div class="row text-center mb-5">
            <div class="col">
                <h2 class="display-5 fw-bold">Our Gallery</h2>
                <p class="lead">Moments that define our community service journey</p>
            </div>
        </div>

        <?php
        include("./db/connection.php");

        // Get event types
        $eventTypesQuery = "
            SELECT DISTINCT e.event_type
            FROM events e
            JOIN gallery g ON e.event_id = g.event_id
            WHERE g.image_path IS NOT NULL
            ORDER BY e.event_type
        ";
        $eventTypes = $pdo->query($eventTypesQuery)->fetchAll();

        // Event type badge configuration
        $types = [
            'blood_camp' => ['icon' => '🩸', 'name' => 'Blood Donation', 'color' => 'danger'],
            'tree_plantation' => ['icon' => '🌳', 'name' => 'Tree Plantation', 'color' => 'success'],
            'cleanliness_drive' => ['icon' => '🧹', 'name' => 'Cleanliness Drive', 'color' => 'info'],
            'awareness' => ['icon' => '📢', 'name' => 'Awareness Program', 'color' => 'warning'],
            'medical_camp' => ['icon' => '🏥', 'name' => 'Medical Camp', 'color' => 'primary'],
            'educational' => ['icon' => '📚', 'name' => 'Educational Activity', 'color' => 'secondary'],
            'cultural' => ['icon' => '🎭', 'name' => 'Cultural Event', 'color' => 'dark'],
            'sports' => ['icon' => '⚽', 'name' => 'Sports Activity', 'color' => 'success'],
            'college_event' => ['icon' => '🏫', 'name' => 'College Event', 'color' => 'secondary'],
            'regular' => ['icon' => '🔄', 'name' => 'Regular Activity', 'color' => 'secondary'],
            'special_camp' => ['icon' => '🏕️', 'name' => 'Special Camp', 'color' => 'warning'],
            'other' => ['icon' => '📋', 'name' => 'Other', 'color' => 'light']
        ];
        ?>

        <div class="row g-4">

        <?php if(count($eventTypes) > 0): ?>
        <?php foreach($eventTypes as $row): 

            $event_type = $row['event_type'];
            $typeInfo = $types[$event_type] ?? $types['other'];

            // Fetch all media for this event type
            $stmt = $pdo->prepare("
                SELECT g.image_path 
                FROM gallery g
                JOIN events e ON g.event_id = e.event_id
                WHERE e.event_type = ?
            ");
            $stmt->execute([$event_type]);
            $mediaList = $stmt->fetchAll(PDO::FETCH_COLUMN);
        ?>

            <!-- Event Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card shadow border-0 h-100 p-3 text-center">

                    <!-- Badge -->
                    <div class="mb-3">
                        <span class="badge bg-<?= $typeInfo['color'] ?> fs-6 px-3 py-2">
                            <?= $typeInfo['icon'] ?> <?= $typeInfo['name'] ?>
                        </span>
                    </div>

                    <!-- Media Preview -->
                    <div class="media-box position-relative">

                        <?php foreach($mediaList as $index => $file): 
                            $mediaPath = "./assets/uploads/".$file;
                            $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, ['mp4','webm','ogg']);
                        ?>

                            <?php if($isVideo): ?>
                                <video
                                    class="media-item img-fluid rounded shadow-sm <?= $index==0?'':'d-none' ?>"
                                    style="width:100%; height:250px; object-fit:cover;"
                                    muted loop>
                                    <source src="<?= $mediaPath ?>">
                                </video>
                            <?php else: ?>
                                <img
                                    src="<?= $mediaPath ?>"
                                    class="media-item img-fluid rounded shadow-sm <?= $index==0?'':'d-none' ?>"
                                    style="width:100%; height:250px; object-fit:cover;"
                                    onerror="this.src='./assets/images/placeholder.jpg'">
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                </div>
            </div>

        <?php endforeach; ?>
        <?php else: ?>

            <div class="col-12 text-center">
                <div class="alert alert-light">
                    <h4>No Gallery Media Found</h4>
                    <p>Images and videos will appear here once uploaded.</p>
                </div>
            </div>

        <?php endif; ?>

        </div>

        <!-- Call to Action -->
        <div class="text-center mt-5">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-images me-2"></i>View Full Gallery
                </a>
            </div>
        </div>

    </div>
</section>


<!-- JS Auto Slider -->
<script>
document.querySelectorAll('.media-box').forEach(box => {
    let items = box.querySelectorAll('.media-item');
    let index = 0;

    if(items.length > 1){
        setInterval(() => {

            items[index].classList.add('d-none');
            if(items[index].tagName === 'VIDEO') {
                items[index].pause();
            }

            index = (index + 1) % items.length;

            items[index].classList.remove('d-none');
            if(items[index].tagName === 'VIDEO') {
                items[index].play();
            }

        }, 3000);
    }
});
</script>


    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold text-primary"><?= $volunteers_count ?></h3>
                    <p class="lead">Active Volunteers</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold text-success"><?= $events_count ?></h3>
                    <p class="lead">Upcoming Events</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold text-warning">50+</h3>
                    <p class="lead">Communities Served</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4 fw-bold text-info"><?= $certificates_count ?></h3>
                    <p class="lead">Certificates Issued</p>
                </div>
            </div>
        </div>
    </section>
<!--
// CTA Section 
    <section class="container my-5 text-center">
        <h2>Ready to Make a Difference?</h2>
        <p class="lead mb-4">Join our community of volunteers and start your journey of service today.</p>
        <a href="register.php" class="btn btn-primary btn-lg me-3">
            <i class="fas fa-user-plus me-2"></i>Register Now
        </a>
        <a href="login.php" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
        </a>
    </section>
        -->
    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <img src="./assets/images/nss_logo.png" alt="NSS Logo" height="40" class="mb-2">
                    <p class="mb-0 small">National Service Scheme</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Navneet College of Arts ,Science & Commerce.. All Rights Reserved.</p>
                    <p class="mb-0 small">Building responsible citizens through community service</p>
                </div>
                <div class="col-md-4">
                    <img src="./assets/images/college_logo.png" alt="College Logo" height="40" class="mb-2">
                    <p class="mb-0 small">Navneet College of Science & Arts</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(52, 58, 64, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = '#343a40';
                navbar.style.backdropFilter = 'none';
            }
        });

        // Active nav link highlighting
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', function() {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>