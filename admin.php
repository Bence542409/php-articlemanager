<?php
session_start();

/* ===============================
   ADMIN LOGIN
=============================== */

// Egyszerű felhasználó/jelszó párosok tömb
$users = [
    'admin' => 'admin',
];

// Kilépés kezelése
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Ha még nincs belépve, kezeljük a login POST-ot
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (isset($users[$username]) && $users[$username] === $password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Hibás felhasználónév vagy jelszó!";
        }
    }

    // Ha nincs belépve, mutatjuk a login formot és kilépünk
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Admin belépés</title>
        <style>
            body { font-family: Arial; background: #f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; }
            .login-box { background:#fff; padding:30px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); width:300px; }
            input { width:279px; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:4px; }
            button { width:100%; padding:10px; margin:10px 0; background:#007BFF; color:#fff; border:none; border-radius:4px; cursor:pointer; }
            button:hover { background:#0056b3; }
            .error { color:red; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Belépés</h2>
            <?php if (!empty($login_error)) echo "<p class='error'>$login_error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Felhasználónév" required>
                <input type="password" name="password" placeholder="Jelszó" required>
                <button type="submit">Belépés</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; // kilépünk, amíg nincs login
}



/* ==============================
   CONFIG
============================== */

$baseDir = __DIR__ . "/cikkek";

if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

/* =============================
// ARTICLE IMAGE BLOCK UPLOAD (AJAX)
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['insert_image'])) {

    // Output buffering és hibák elrejtése
    ob_start();
    error_reporting(0);

    $category = basename($_POST['category'] ?? '');
    $title = $_POST['title'] ?? 'article';

    if (!$category) {
        echo json_encode(['success'=>false,'error'=>'Nincs kategória kiválasztva']);
        exit;
    }

    $imageDir = $baseDir . "/" . $category . "/images";
    if (!is_dir($imageDir)) mkdir($imageDir, 0755, true);

    $file = $_FILES['insert_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success'=>false,'error'=>'Nem engedélyezett fájlformátum']);
        exit;
    }

    $filename = uniqid('img_') . "." . $ext;
    $destination = $imageDir . "/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {

        // Ha Linux, Hostinger, nincs PowerShell, ezért itt csak chmod
        @chmod($destination, 0644);

        // relatív útvonal, amit a cikkbe használunk
        $relativePath = "cikkek/" . $category . "/images/" . $filename;

        // Töröljük minden outputot, csak a JSON maradjon
        ob_clean();
        echo json_encode([
            'success' => true,
            'path' => $relativePath
        ]);
        exit;

    } else {
        ob_clean();
        echo json_encode(['success'=>false,'error'=>'Feltöltés sikertelen']);
        exit;
    }
}

/* ==============================
   SEGÉDFÜGGVÉNYEK
============================== */

function generatetitle($text) {

    // Kisbetű UTF-8 módon
    $text = mb_strtolower($text, 'UTF-8');

    // Magyar ékezetek kézi cseréje
    $replace = [
        'á' => 'a', 'é' => 'e', 'í' => 'i',
        'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i',
        'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o',
        'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u'
    ];

    $text = strtr($text, $replace);

    // Minden ami nem a-z vagy szám → kötőjel
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Többszörös kötőjelek törlése
    $text = preg_replace('/-+/', '-', $text);

    return trim($text, '-');
}

function getcategorys($baseDir) {
    return array_filter(glob($baseDir . "/*"), 'is_dir');
}

function getAllArticles($baseDir) {
    $articles = [];
    $categorys = getcategorys($baseDir);

    foreach ($categorys as $category) {
        $files = glob($category . "/*.json");

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                if (empty($data['slug'])) {
                    $data['slug'] = basename($file, '.json');
                }

                $data['category'] = basename($category);
                $articles[] = $data;
            }
        }
    }

    usort($articles, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    return $articles;
}

/* ==============================
   MŰVELETEK
============================== */

$message = "";
$editData = null;
$categorys = getcategorys($baseDir);

/* ---- TÖRLÉS ---- */
if (isset($_GET['delete'], $_GET['category'])) {

    $slug = basename($_GET['delete']);
    $category = basename($_GET['category']);

    $file = $baseDir . "/" . $category . "/" . $slug . ".json";

    if (file_exists($file)) {
        unlink($file);
        $message = "CIKK TÖRLÉSE SIKERES";
    }
}

/* ---- SZERKESZTÉS BETÖLTÉSE ---- */
if (isset($_GET['edit'], $_GET['category'])) {

    $slug = basename($_GET['edit']);
    $category = basename($_GET['category']);

    $file = $baseDir . "/" . $category . "/" . $slug . ".json";

    if (file_exists($file)) {
        $editData = json_decode(file_get_contents($file), true);

        $editData['category'] = $category;
    }
}

/* ---- MENTÉS ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $category = basename($_POST['category']);
    $originaltitle = $_POST['original_title'] ?? null;
    $originalcategory = $_POST['original_category'] ?? null;

    if ($title && $content && $category) {

        $slug = generatetitle($title);
        $targetDir = $baseDir . "/" . $category;
        $imageDir = $targetDir . "/images";

        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        if (!is_dir($imageDir)) mkdir($imageDir, 0755, true);

        /* ===== BORÍTÓKÉP KEZELÉS ===== */
        $coverImagePath = $editData['cover_image'] ?? null;

        if (!empty($_FILES['cover_image']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $newFileName = $slug . "." . $ext;
                $destination = $imageDir . "/" . $newFileName;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {

                    $coverImagePath = "images/" . $newFileName;
                }
            }
        }

        /* ===== Régi fájl törlés ha title vagy category változott ===== */
        if ($originaltitle) {
            $oldFile = $baseDir . "/" . basename($originalcategory) . "/" . basename($originaltitle) . ".json";
            if (file_exists($oldFile)) unlink($oldFile);
        }

        $data = [
            "title" => $title,
            "slug" => $slug,
            "content" => $content,
            "cover_image" => $coverImagePath,
            "author" => trim($_POST['author']), // ← ide került az író
            "created_at" => $originaltitle ? $_POST['created_at'] : date("Y-m-d H:i"),
            "updated_at" => date("Y-m-d H:i")
        ];

        file_put_contents(
            $targetDir . "/" . $slug . ".json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $message = "CIKK MENTÉSE SIKERES";
        $editData = null;
    }
}

$articles = getAllArticles($baseDir);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>admin.php</title>
    <meta http-equiv = "Content-Type" content = "text / html; charset = UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ---------- ALAP ---------- */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
    color: #222;
}

.container {
    max-width: 900px;
    margin: 20px auto;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
}

/* ---------- Headings ---------- */
h1, h2 {
    font-weight: 600;
    margin-bottom: 15px;
    color: #111;
}

/* ---------- Form elemek ---------- */
input[type=text], select, input[type=file], .editor-container {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
    background: #fafafa;
}

/* ---------- Content editor ---------- */
.editor-container {
    min-height: 250px;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    background: #fff;
    overflow-y: auto;
}

/* ---------- Gombok ---------- */
.editor-buttons button {
    padding: 5px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 15px;
    transition: 0.2s;
    min-width: 50px;
}

.editor-buttons button:hover {
    background-color: lightgray;
}
    
.save-btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    background-color: #5CD65C;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 10px;
    transition: 0.2s;
    min-width: 85px;
    margin-top: 30px;
}
.logout-btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    background-color: red;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 10px;
    transition: 0.2s;
    min-width: 85px;
    margin-top: 30px;
}

.save-btn:hover {
    background-color: #28A745;
}
.logout-btn:hover {
    background-color: darkred;
}


/* ---------- Cikk lista ---------- */
.article-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.article-item strong {
    font-size: 1rem;
}

.article-item a {
    color: #007BFF;
    text-decoration: none;
    font-size: 0.9rem;
    margin-left: 8px;
}

.article-item a:hover {
    text-decoration: underline;
}

/* ---------- Borító preview ---------- */
.cover-preview {
    max-width: 150px;
    border-radius: 6px;
    margin-top: 10px;
}

/* ---------- Reszponzív ---------- */
@media(max-width: 600px) {
    .article-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .button {
        width: 100%;
        margin-right: 0;
    }
}

</style>

<script>
function formatText(tag) {
    const textarea = document.getElementById("content");
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);

    const before = textarea.value.substring(0, start);
    const after = textarea.value.substring(end);

    textarea.value = before + "<" + tag + ">" + selected + "</" + tag + ">" + after;
}

// Mielőtt a form submit-ol, szinkronizáljuk a contenteditable div tartalmát a hidden textarea-val
function syncEditor() {
    const editor = document.getElementById('editor');
    const textarea = document.getElementById('content');
    textarea.value = editor.innerHTML;
}
</script>

</head>
<body>
<div class="container">
    <h1>Cikkek kezelése</h1>

    <!-- Üzenet -->
    <?php if ($message): ?>
    <p id="message" class="success" style="color: green"><?= $message ?></p>
    <script>
    setTimeout(function() {
        const msg = document.getElementById('message');
        if(msg) {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }
    }, 2000); // 5000 ms = 5 másodperc
    </script>
    <?php endif; ?>

    <!-- Új / szerkesztett cikk -->
    <h2><?= $editData ? "Cikk szerkesztése" : "Új cikk" ?></h2>

    <form method="POST" enctype="multipart/form-data" onsubmit="syncEditor()">
        <label>Cím:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required>

        <label>Író:</label>
        <input type="text" name="author" value="<?= htmlspecialchars($editData['author'] ?? '') ?>">

        <label>Kategória:</label>
        <select name="category" required>
            <option value="" disabled selected>--- Válassz kategóriát ---</option>
            <?php foreach ($categorys as $categoryPath): 
                $categoryName = basename($categoryPath);
            ?>
            <option value="<?= $categoryName ?>"
                <?= (isset($editData['category']) && $editData['category'] === $categoryName) ? 'selected' : '' ?>>
                <?= $categoryName ?>
            </option>
            <?php endforeach; ?>
        </select>

        <label>Tartalom:</label>
        <div id="editor" class="editor-container" contenteditable="true">
            <?= $editData['content'] ?? '<p></p>' ?>
        </div>
        <input type="hidden" name="content" id="content">
        
        <div class="editor-buttons">
            <button type="button" onclick="format('h2')" title="Alcím">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M200-280v-400h80v160h160v-160h80v400h-80v-160H280v160h-80Zm480 0v-320h-80v-80h160v400h-80Z"/></svg>
            </button>
            <button type="button" onclick="format('formatBlock','p')" title="Bekezdés">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M360-160v-240q-83 0-141.5-58.5T160-600q0-83 58.5-141.5T360-800h360v80h-80v560h-80v-560H440v560h-80Z"/></svg>
            </button>
            <button type="button" onclick="format('bold')" title="Félkövér">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M272-200v-560h221q65 0 120 40t55 111q0 51-23 78.5T602-491q25 11 55.5 41t30.5 90q0 89-65 124.5T501-200H272Zm121-112h104q48 0 58.5-24.5T566-372q0-11-10.5-35.5T494-432H393v120Zm0-228h93q33 0 48-17t15-38q0-24-17-39t-44-15h-95v109Z"/></svg>
            </button>
            <button type="button" onclick="format('italic')" title="Dölt">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M200-200v-100h160l120-360H320v-100h400v100H580L460-300h140v100H200Z"/></svg>
            </button>
            <button type="button" onclick="format('insertUnorderedList')" title="Rendezetlen lista">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M360-200v-80h480v80H360Zm0-240v-80h480v80H360Zm0-240v-80h480v80H360ZM200-160q-33 0-56.5-23.5T120-240q0-33 23.5-56.5T200-320q33 0 56.5 23.5T280-240q0 33-23.5 56.5T200-160Zm0-240q-33 0-56.5-23.5T120-480q0-33 23.5-56.5T200-560q33 0 56.5 23.5T280-480q0 33-23.5 56.5T200-400Zm-56.5-263.5Q120-687 120-720t23.5-56.5Q167-800 200-800t56.5 23.5Q280-753 280-720t-23.5 56.5Q233-640 200-640t-56.5-23.5Z"/></svg>
            </button>
            <button type="button" onclick="format('insertOrderedList')" title="Rendezett lista">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-80v-60h100v-30h-60v-60h60v-30H120v-60h120q17 0 28.5 11.5T280-280v40q0 17-11.5 28.5T240-200q17 0 28.5 11.5T280-160v40q0 17-11.5 28.5T240-80H120Zm0-280v-110q0-17 11.5-28.5T160-510h60v-30H120v-60h120q17 0 28.5 11.5T280-560v70q0 17-11.5 28.5T240-450h-60v30h100v60H120Zm60-280v-180h-60v-60h120v240h-60Zm180 440v-80h480v80H360Zm0-240v-80h480v80H360Zm0-240v-80h480v80H360Z"/></svg>
            </button>
            <button type="button" onclick="addImageBlock()" title="Kép beszúrása">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M480-480ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h320v80H200v560h560v-280h80v280q0 33-23.5 56.5T760-120H200Zm40-160h480L570-480 450-320l-90-120-120 160Zm480-280v-167l-64 63-56-56 160-160 160 160-56 56-64-63v167h-80Z"/></svg>
            </button>
            <button type="button" onclick="insertLink()" title="Link beszúrása">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M680-160v-120H560v-80h120v-120h80v120h120v80H760v120h-80ZM440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80ZM320-440v-80h320v80H320Zm560-40h-80q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480Z"/></svg>
            </button>
            <button type="button" onclick="format('outdent')" title="Behúzás csökkentése">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-120v-80h720v80H120Zm320-160v-80h400v80H440Zm0-160v-80h400v80H440Zm0-160v-80h400v80H440ZM120-760v-80h720v80H120Zm160 440L120-480l160-160v320Z"/></svg>
            </button>
            <button type="button" onclick="format('indent')" title="Behúzás növelése">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-120v-80h720v80H120Zm320-160v-80h400v80H440Zm0-160v-80h400v80H440Zm0-160v-80h400v80H440ZM120-760v-80h720v80H120Zm0 440v-320l160 160-160 160Z"/></svg>
            </button>
            <button type="button" onclick="format('justifyLeft')" title="Balra zárt">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-120v-80h720v80H120Zm0-160v-80h480v80H120Zm0-160v-80h720v80H120Zm0-160v-80h480v80H120Zm0-160v-80h720v80H120Z"/></svg>
            </button>
            <button type="button" onclick="format('justifyCenter')" title="Középre zárt">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-120v-80h720v80H120Zm160-160v-80h400v80H280ZM120-440v-80h720v80H120Zm160-160v-80h400v80H280ZM120-760v-80h720v80H120Z"/></svg>
            </button>
            <button type="button" onclick="format('justifyRight')" title="Jobbra zárt">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-760v-80h720v80H120Zm240 160v-80h480v80H360ZM120-440v-80h720v80H120Zm240 160v-80h480v80H360ZM120-120v-80h720v80H120Z"/></svg>
            </button>
            <button type="button" onclick="format('justifyFull')" title="Sorkizárt">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M120-120v-80h720v80H120Zm0-160v-80h720v80H120Zm0-160v-80h720v80H120Zm0-160v-80h720v80H120Zm0-160v-80h720v80H120Z"/></svg>
            </button>
            <input type="file" id="imageBlockInput" accept="image/*" style="display:none;">
        </div>

        <label>Borítókép:</label>
        <input type="file" name="cover_image" accept="image/*">
        <?php if(!empty($editData['cover_image'])): ?>
            <img src="cikkek/<?= $editData['category'] ?>/<?= $editData['cover_image'] ?>" class="cover-preview"><br />
        <?php endif; ?>

        <?php if($editData): ?>
            <input type="hidden" name="original_title" value="<?= $editData['title'] ?>">
            <input type="hidden" name="original_category" value="<?= $editData['category'] ?>">
            <input type="hidden" name="created_at" value="<?= $editData['created_at'] ?>">
        <?php endif; ?>

        <button type="submit" class="save-btn">Mentés</button>
        <button type="button" onclick="window.location.href='admin.php?logout=1';" class="logout-btn">Kijelentkezés</button>
    </form>

    <!-- Meglévő cikkek listája -->
    <h2>Publikált cikkek</h2>
    <?php foreach($articles as $article): ?>
        <div class="article-item">
            <div class="article-info">
                <strong><?= htmlspecialchars($article['title']) ?></strong><br>
                <small>Kategória: <?= htmlspecialchars($article['category']) ?> | Dátum: <?= $article['created_at'] ?></small>
            </div>
            <div class="article-actions">
                <a href="?edit=<?= $article['slug'] ?>&category=<?= $article['category'] ?>">Szerkesztés</a>
                <a href="?delete=<?= $article['slug'] ?>&category=<?= $article['category'] ?>" onclick="return confirm('Biztos?')">Törlés</a>
                <a href="article.php?title=<?= $article['slug'] ?>&category=<?= $article['category'] ?>" target="_blank">Megnyitás</a>
            </div>
        </div>
    <?php endforeach; ?>
    
    
</div>
    
<script>
// Szöveg formázása
function format(cmd, value=null){
    if(cmd==='h2'){
        document.execCommand('formatBlock', false, 'h2');
    } else if(value){
        document.execCommand(cmd, false, value);
    } else {
        document.execCommand(cmd, false, null);
    }
}

// Form submit előtt szinkronizáljuk a div tartalmát
function syncEditor() {
    document.getElementById('content').value = document.getElementById('editor').innerHTML;
}

// Kép blokk hozzáadása (Hostinger-kompatibilis)
function addImageBlock() {
    const fileInput = document.getElementById('imageBlockInput');
    fileInput.click();

    fileInput.onchange = function() {
        const file = fileInput.files[0];
        if(!file) return;

        const category = document.querySelector('select[name="category"]').value;
        if(!category) {
            alert("Válassz kategóriát a kép feltöltéséhez!");
            return;
        }

        const formData = new FormData();
        formData.append('insert_image', file);
        formData.append('category', category);
        formData.append('title', document.querySelector('input[name="title"]').value);

        fetch('admin.php', { method:'POST', body: formData })
        .then(res => res.text()) // szövegként olvassuk
        .then(txt => {
            let data;
            try {
                data = JSON.parse(txt); // próbáljuk JSON-ként parse-olni
            } catch(e) {
                console.error("Nem valid JSON: ", txt);
                alert("Hiba a kép feltöltésekor. Ellenőrizd a PHP hibákat!");
                return;
            }

            if(data.success) {
                const editor = document.getElementById('editor');
                const div = document.createElement('div');
                div.className = 'image-block';
                div.innerHTML = `<img src="${data.path}" style="max-width:100%; margin:10px 0;">`;
                editor.appendChild(div);
                fileInput.value = '';

            } else {
                alert("Hiba a kép feltöltésekor: " + data.error);
            }
        })
        .catch(err => console.error(err));
    }
}
    
    function insertLink() {
    const selection = window.getSelection();
    const selectedText = selection.toString();

    let url = prompt("Add meg a link URL-jét:");
    if (!url) return;

    // Ha nem írt be http-et, automatikusan hozzáadjuk
    if (!url.startsWith("http://") && !url.startsWith("https://")) {
        url = "https://" + url;
    }

    if (selectedText.length > 0) {
        // Ha van kijelölt szöveg
        document.execCommand(
            "insertHTML",
            false,
            `<a href="${url}" target="_blank" rel="noopener noreferrer">${selectedText}</a>`
        );
    } else {
        // Ha nincs kijelölve semmi
        let text = prompt("Mi legyen a link szövege?");
        if (!text) return;

        document.execCommand(
            "insertHTML",
            false,
            `<a href="${url}" target="_blank" rel="noopener noreferrer">${text}</a>`
        );
    }
}



// Form submit listener
document.querySelector('form').addEventListener('submit', syncEditor);
</script>

</body>
</html>
