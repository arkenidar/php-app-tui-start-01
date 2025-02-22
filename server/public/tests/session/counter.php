<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>session example : counter</title>
</head>

<body>
    <h1>session example : counter</h1>
    <?php
    $counter = 0;
    if (isset($_SESSION['counter'])) {
        $counter = $_SESSION['counter'];
    }
    $counter++;
    $_SESSION['counter'] = $counter;
    //echo $counter;
    ?>
    <p>
        <span> <?= $_SESSION['counter'] ?> visits</span>
    </p>
    <hr>
    <p>reload the page to increment the counter</p>
    <hr>
    <a href="">reload for next counter</a>

</body>

</html>