<?php
$baseDir = __DIR__ . '/cikkek';

$allArticles = [];

// Kategória mappák bejárása
$categories = glob($baseDir . '/*', GLOB_ONLYDIR);

foreach ($categories as $categoryDir) {

    $category = basename($categoryDir);

    // ---- displayName betöltése ----
    $displayName = $category;
    $nameFile = $categoryDir . '/name.txt';

    if (file_exists($nameFile)) {
        $nameContent = trim(file_get_contents($nameFile));
        if (!empty($nameContent)) {
            $displayName = $nameContent;
        }
    }

    // ---- JSON cikkek ----
    $articles = glob($categoryDir . '/*.json');

    foreach ($articles as $file) {

        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;

        $slug = pathinfo($file, PATHINFO_FILENAME);

        $coverImage = null;
        if (!empty($data['cover_image'])) {
            $imagePath = $categoryDir . '/' . $data['cover_image'];
            if (file_exists($imagePath)) {
                $coverImage = 'cikkek/' . urlencode($category) . '/' . $data['cover_image'];
            }
        }

        $allArticles[] = [
            'title' => $data['title'],
            'created' => $data['created_at'],
            'author' => $data['author'] ?? null,
            'category' => $category,
            'categoryName' => $displayName,
            'slug' => $slug,
            'cover' => $coverImage
        ];
    }
}

// ---- DÁTUM SZERINT RENDEZÉS (legújabb elöl) ----
usort($allArticles, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
});
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <title>Összes cikk</title>
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
        
        .article-card .meta {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    
        
    <h1 style="text-align:center; margin-top:30px;">Összes cikk</h1>

<div class="articles-container">
<?php foreach($allArticles as $article): ?>
    
    <a href="article.php?category=<?= urlencode($article['category']) ?>&title=<?= urlencode($article['slug']) ?>" class="article-card">
        
        <?php if ($article['cover']): ?>
            <img src="<?= htmlspecialchars($article['cover']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
        <?php endif; ?>

        <div class="info">
            <h3><?= htmlspecialchars($article['title']) ?></h3>

            <div class="meta">
                <span class="category"><?= htmlspecialchars($article['categoryName']) ?></span>
                <?php if ($article['author']): ?>
                    | <span class="author"><?= htmlspecialchars($article['author']) ?></span>
                <?php endif; ?>
            </div>

            <div class="date"><?= htmlspecialchars($article['created']) ?></div>
        </div>

    </a>

<?php endforeach; ?>
</div>
    
</body>
</html>
