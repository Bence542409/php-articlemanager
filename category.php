<?php
$baseDir = __DIR__ . '/cikkek';
$category = $_GET['category'] ?? null;

if (!$category) {
    die("Kategória nem található.");
}

$categoryDir = $baseDir . '/' . basename($category);

if (!is_dir($categoryDir)) {
    die("Kategória nem létezik.");
}

// ---- Kategória megjelenítendő neve (displayName) ----
$displayName = basename($category); // alapértelmezett: mappanév

$nameFile = $categoryDir . '/name.txt';

if (file_exists($nameFile)) {
    $nameContent = trim(file_get_contents($nameFile));
    if (!empty($nameContent)) {
        $displayName = $nameContent;
    }
}

// Cikkek lekérése a kategóriából (JSON fájlok)
$articles = glob($categoryDir . '/*.json');
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <title><?= htmlspecialchars($displayName) ?></title>
        <meta http-equiv = "Content-Type" content = "text / html; charset = UTF-8" />
        <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Roboto', sans-serif; padding: 0; background: #f9f9f9; }
        .articles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            padding: 50px 20px;
            margin-top: 80px;
            margin-bottom: 150px;
        }
        .article-card {
            width: 300px;
            overflow: hidden;
            text-decoration: none;
            color: black;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .article-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .article-card .info {
            padding: 10px;
        }
        .article-card .info h3 {
            margin: 0 0 5px;
            font-size: 1.2rem;
        }
        .article-card .info .date {
            font-size: 0.9rem;
            color: gray;
        }

        @media(max-width: 700px) {
            .article-card { width: 80%; }
            .article-card img { height: 230px; }
        }
    </style>
</head>
<body>
    
    <h1 style="text-align:center; margin-top:30px;"><?= htmlspecialchars($displayName) ?></h1>

    <div class="articles-container">
        <?php foreach($articles as $file):
            $data = json_decode(file_get_contents($file), true);
            $slug = pathinfo($file, PATHINFO_FILENAME); // ← ez a fájlnév, slug formátumban
            if (!$data) continue;

            $title = $data['title'];
            $created = $data['created_at'];
            $coverImage = null;

            if (!empty($data['cover_image'])) {
                $imagePath = $categoryDir . '/' . $data['cover_image'];

                if (file_exists($imagePath)) {
                    $coverImage = 'cikkek/' . urlencode($category) . '/' . $data['cover_image'];
                }
            }
        ?>
        <a href="article.php?category=<?= urlencode($category) ?>&title=<?= urlencode($slug) ?>" class="article-card">
        <?php if ($coverImage): ?>
            <img src="<?= htmlspecialchars($coverImage) ?>" alt="<?= htmlspecialchars($title) ?>">
        <?php endif; ?>

            <div class="info">
                <h3><?= htmlspecialchars($title) ?></h3>
                <div class="date"><?= htmlspecialchars($created) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

</body>
</html>
