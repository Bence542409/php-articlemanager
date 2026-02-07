<?php
$baseDir = __DIR__ . '/cikkek';

// Kategóriák lekérése
$categories = array_filter(glob($baseDir . '/*'), 'is_dir');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <title>Kategóriák</title>
    <meta http-equiv = "Content-Type" content = "text / html; charset = UTF-8" />
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Roboto', sans-serif; padding: 0; background: #f9f9f9; }

        .categories-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 50px 20px;
            margin-top: 100px;
            margin-bottom: 150px;
        }

        .category-card {
            position: relative;
            width: 250px;
            height: 200px;
            overflow: hidden;
            text-decoration: none;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: flex-end;
            background-size: cover;
            background-position: center;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .category-card .label {
            background: rgba(0,0,0,0.5);
            width: 100%;
            text-align: center;
            padding: 10px 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        @media(max-width: 700px) {
            .category-card {
                width: 45%;
                height: 150px;
            }
            .category-card .label {
                font-size: 1.2rem;
                padding: 8px 0;
            }
        }
    </style>
</head>
<body>

    <div class="categories-container">
    <?php foreach($categories as $catPath):

        $catFolder = basename($catPath); // mappanév (URL-hez kell)

        // ---- KATEGÓRIA CÍM beolvasása name.txt-ből ----
        $displayName = $catFolder; // fallback, ha nincs name.txt

        $nameFile = $catPath . '/name.txt';
        if (file_exists($nameFile)) {
            $displayName = trim(file_get_contents($nameFile));
        }

        // ---- Borítókép ----
        $coverImage = file_exists($catPath . '/cover.jpg') 
            ? 'cikkek/' . urlencode($catFolder) . '/cover.jpg' 
            : '../icon/default-category.jpg';
    ?>

        <a href="category.php?category=<?= urlencode($catFolder) ?>"
           class="category-card"
           style="background-image: url('<?= htmlspecialchars($coverImage) ?>')">

            <div class="label">
                <?= htmlspecialchars($displayName) ?>
            </div>
        </a>

    <?php endforeach; ?>
    </div>

</body>
</html>
