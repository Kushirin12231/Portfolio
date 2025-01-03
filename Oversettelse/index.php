<?php
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Real-Time Translator</title>
  <link rel="stylesheet" href="Style.css">
  <script src="Scripts.js" defer></script> <!-- Make sure script runs after DOM loads -->
</head>
<body>
  <header class="header">
    <div class="container">
      <nav class="nav">
        <a href="#maten-for-dagen">placeholder</a>
        <a href="#reviews">placeholder</a>
        <a href="#priser">placeholder</a>
      </nav>
    </div>
  </header>

  <section class="images">
    <img src="..\Oversettelse\Pictures\Image1.webp" class="logo" alt="Logo">
  </section>

  <section>
    <div class="flex-container">
      <div class="flex-items">
        <select class="dropdown" id="language-from">
          <option value="no">Norwegian</option>
          <option value="en">English</option>
        </select>
        <textarea id="input-text" class="text" placeholder="Type here..."></textarea>
      </div>

      <div class="flex-items">
        <section class="border"></section>
      </div>

      <div class="flex-items">
        <select class="dropdown" id="language-to">
          <option value="en">English</option>
          <option value="no">Norwegian</option>
        </select>
        <textarea id="output-text" class="text" placeholder="Translation will appear here..." readonly></textarea>
      </div>
    </div>
  </section>
</body>
</html>
