# php-articlemanager
<p>PHP article management system for blog use.</p>
<br />
<p>Built by me and ChatGPT.</p>
<p>The files are in Hungarian, but you can change it as you like.</p>
<p>If PHP is not yet installed on your web server: <a href="https://www.php.net/downloads.php" target="_blank">https://www.php.net/downloads.php</a></p>
<br />
<p>index.php --></p>
<ul>
  <li>lists the subfolders available in the directory "cikkek"</li>
  <li>shows cover.jpg in the category's folder (if cover.jpg is available)</li>
  <li>outputs the contents of name.txt in the category's folder (if name.txt is available)</li>
  <li>outputs the name of the subfolder (if name.txt is not available)</li>
  <li>select the category to go to category.php</li>
</ul>
<p>category.php --></p>
<ul>
  <li>lists the articles available in the category</li>
  <li>shows the banner image of the article (if available)</li>
  <li>select the article to go to article.php</li>
</ul>
<p>article.php --></p>
<ul>
  <li>reads the json file to the article</li>
  <li>shows the banner image of the article (if available)</li>
  <li>select the article to go to article.php</li>
</ul>
<p>admin.php --></p>
<ul>
  <li></li>
</ul>
<p>.htaccess --></p>
<ul>
  <li>redirects example.com/article/[category]/[article] to example.com/article.php?title=[slug]&category=[category]
</ul>
