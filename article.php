<?php
/* ==============================
   BETÖLTÉS
============================== */

$baseDir = __DIR__ . "/cikkek";

$title = $_GET['title'] ?? null;
$category = $_GET['category'] ?? null;

if (!$title || !$category) {
    http_response_code(404);
    die("Cikk nem található.");
}

$title = basename($title);
$category = basename($category);

$file = $baseDir . "/" . $category . "/" . $title . ".json";

if (!file_exists($file)) {
    http_response_code(404);
    die("Cikk nem található.");
}

$data = json_decode(file_get_contents($file), true);

if (!$data) {
    http_response_code(500);
    die("Hibás cikk formátum.");
}

$title = $data['title'];
$content = $data['content'];
$created = $data['created_at'];
$coverImage = $data['cover_image'] ?? null;
$coverImageUrl = $coverImage ? "cikkek/" . urlencode($category) . "/" . urlencode($coverImage) : null;
$author = $data['author'] ?? null;
$slug = $data['slug'] ?? null;

// ---- Kategória megjelenítendő neve (name.txt alapján) ----
$displayName = $category; // alapértelmezett: mappanév

$nameFile = $baseDir . "/" . $category . "/name.txt";

if (file_exists($nameFile)) {
    $nameContent = trim(file_get_contents($nameFile));
    if (!empty($nameContent)) {
        $displayName = $nameContent;
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= htmlspecialchars($title) ?></title>
        <meta http-equiv = "Content-Type" content = "text / html; charset = UTF-8" />
        <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { margin: 0; font-family: 'Roboto', sans-serif; padding: 0; background: #f9f9f9; }
            .article h1 { margin-bottom: 10px; }
            .article .date { color: gray; margin-bottom: 20px; }
            .article-image img { width: 100%; max-height: 400px; object-fit: cover; margin-bottom: 20px; }
            
            .article {
                amx-width: 100%;
                overflow-wrap: break-word; /* vagy word-wrap: break-word; régebbi böngészők */
                word-break: break-word;    /* extra biztosítás, hogy hosszú szavak törődjenek */
                margin-bottom: 50px;
                padding: 50px;
            }
            
            a {
                color: dodgerblue;
                text-decoration: none;
                transition: all 0.15s ease-out;
            }
            a:hover {
                color: skyblue;
            }
            
            .article h1:first-of-type {
                font-size: 2.4rem;
                line-height: 1.2;
                margin-bottom: 15px;
                padding-bottom: 5px;
                border-bottom: 3px solid #000;
                display: inline-block;
                background-color: lightgray;
                padding: 5px; */
                cursor: pointer;
                transition: opacity 0.2s ease;
            }

            .article h1:hover {
                opacity: 0.7;
            }

            .article .date {
                color: #777;
                font-size: 1rem;
                margin-bottom: 5px;
            }

            .article .category {
                color: #777;
                font-size: 1rem;
                margin-bottom: 5px;
            }
            
            .article .author {
                color: #777;
                font-size: 1rem;
                padding-bottom: 50px;
            }

            @media(max-width: 700px) {
                .article {padding: 35px;}
            }
            
        </style>
</head>
<body>

    <?php if ($coverImageUrl): ?>
        <div class="article-image">
            <img src="<?= htmlspecialchars($coverImageUrl) ?>" alt="<?= htmlspecialchars($title) ?>">
        </div>
    <?php endif; ?>

    <div class="article">
        <h1 id="copyTitle" 
    data-category="<?= htmlspecialchars($category) ?>" 
    data-slug="<?= htmlspecialchars($slug) ?>" 
    title="Kattints a címre a link kimásolásához" 
    style="cursor:pointer; display:inline-block; position:relative;">
    <?= htmlspecialchars($title) ?>
</h1>

        <div class="date">Dátum: <?= htmlspecialchars($created) ?></div>
        <div class="category">Kategória: <?= htmlspecialchars($displayName ?? $category) ?></div>
        <?php if ($author): ?>
        <div class="author" style="margin-bottom: 50px">Író: <?= htmlspecialchars($author) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </div>
    
 
<script>
document.getElementById("copyTitle").addEventListener("click", function(e) {
    const category = this.dataset.category;
    const slug = this.dataset.slug;

    if (!category || !slug) return;

    const link = `https://example.com/article/${category}/${slug}`;

    // Létrehozunk egy ideiglenes textarea-t
    const temp = document.createElement("textarea");
    temp.value = link;
    document.body.appendChild(temp);
    temp.select();

    // Másolás
    document.execCommand("copy");

    // Eltávolítás
    document.body.removeChild(temp);
});
</script>


</body>
</html>
