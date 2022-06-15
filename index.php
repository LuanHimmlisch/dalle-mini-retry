<?php
if (isset($_POST['prompt'])) {
    $seconds = 0;
    $retries = 0;

    do {
        $data = call($_POST['prompt']);
        $seconds += $data['seconds'];

        if ($data['status'] == 200) {
            $images = json_decode($data['data'])->images;

            if (isset($_POST['save'])) {
                array_map(function ($index, $image) {
                    $path = __DIR__ . '/images/' . (string) time() . "($index).png";
                    file_put_contents($path, base64_decode($image));
                }, array_keys($images), $images);
            }
        } else {
            $seconds++;
            $retries++;
            sleep(1);
        }
    } while ($data['status'] !== 200 && $retries <= 100);
}


function call($prompt)
{
    $curl = curl_init('https://bf.dallemini.ai/generate');
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode([
            "prompt" => $prompt
        ]),
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
            "DNT: 1",
            "Host: bf.dallemini.ai",
            "Origin: https://hf.space",
            "Referrer: https://hf.space",
        ]
    ]);

    $data = curl_exec($curl);
    curl_close($curl);


    return [
        "status" => curl_getinfo($curl, CURLINFO_HTTP_CODE),
        "seconds" => curl_getinfo($curl, CURLINFO_TOTAL_TIME),
        "data" => $data
    ];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dall-E Retry<?= isset($_POST['prompt']) ? ' | ' . $_POST['prompt'] : '' ?></title>
    <style>
        body {
            max-width: 768px;
            margin: 0 auto;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #0b0f19;
            color: #fff;
            text-align: center;
            padding: 2rem 0;
        }

        .images {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .seconds {
            font-weight: 300;
            color: #445f7e;
        }

        img {
            width: 100%;
        }

        input {
            padding: .5em 1em;
            font-size: 1.2rem;
            border: 2px #1f2937 solid;
            border-radius: 0.25em;
            color: #fff;
            background-color: #0b0f19;
            outline: none;
        }

        button {
            padding: .5em 1em;
            border-radius: 0.25em;
            background-color: #ce6400;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <form action="" method="post">
        <input type="text" name="prompt" id="prompt" placeholder="Prompt...">
        <input type="checkbox" name="save" id="save" title="Save?">
        <button type="submit" onclick="document.querySelector('h1').innerHTML = 'Searching, please wait...'">Search</button>
    </form>


    <h1><?= isset($_POST['prompt']) ? $_POST['prompt'] : 'Search for something' ?></h1>
    <p class="seconds"><?= isset($seconds) ? "Executed in $seconds seconds with $retries retries" : '' ?></p>

    <div class="images">
        <?php
        if (isset($images)) {
            foreach ($images as $key => $image) {
                $image = trim($image);
                echo "<img src='data:image/png;base64,$image' alt='Generated image $key' title='Generated image index $key'>";
            }
        }
        ?>
    </div>

    <?= $error ?? '' ?>
</body>

</html>