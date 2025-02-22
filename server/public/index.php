<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home page</title>
</head>
<body>
    <h1>Home page</h1>
    <p>Welcome to the home page</p>
    <hr>
    <p>shared variables</p>
    <form action="" method="post">
        <input type="text" name="item">
        <input type="submit" value="add to container">
    </form>
    <?php
    if (isset($_POST['item'])) {
        $shared_variables['container'][] = $_POST['item'];
    }
    ?>
    <p>container: <?php print_r($shared_variables['container']); ?></p>
    <hr>
    counter:
    <?php
    echo $shared_variables['counter']++;
    ?>
    <hr>
    <?php
    $name = 'John'; // This is a PHP variable
    if (isset($_GET['name'])) {
        $name = $_GET['name'];
    }
    echo "<p>Hello, $name!</p>";
    ?>
    <p>How are you today?</p>
    <p>Good, I hope!</p>
    <hr>
    <p>Here is a picture of a doggie:</p>
    <p>Isn't he cute?</p>
    <p>Yes, he is!</p>
    <img src="media/images/doggie.webp" alt="doggie">

</body>
</html>