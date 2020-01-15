<?php

// Reading command arguments
$OPTIONS = getopt("f:i:o:s:", ["filename:","input:","output:","status:"]);

$FILENAMEARG = (array_key_exists("f", $OPTIONS) ? $OPTIONS['f'] : $OPTIONS['filename']) ;
$INPUTARG = (array_key_exists("i", $OPTIONS) ? $OPTIONS['i'] : $OPTIONS['input']);
$OUTPUTARG = (array_key_exists("o", $OPTIONS) ? $OPTIONS['o'] : $OPTIONS['output']);
$STATUSARG = (array_key_exists("s", $OPTIONS) ? $OPTIONS['s'] : $OPTIONS['status']);

echo($FILENAMEARG . PHP_EOL);
echo($INPUTARG . PHP_EOL);
echo($OUTPUTARG . PHP_EOL);
echo($STATUSARG . PHP_EOL);

// Creating folders
if(!is_dir($INPUTARG)) {
  mkdir($INPUTARG);
}
if(!is_dir($OUTPUTARG)) {
  mkdir($OUTPUTARG);
}


$filesInInput = array_slice(scandir($INPUTARG), 2);

$filesToProcess = [
    [
        "objectName" => $FILENAMEARG, 
        "objectPath" => $INPUTARG . '/' . $filesInInput[0]
    ]
];

$statusJson = [];
$fp = fopen($STATUSARG.'/status.json', 'w');
fwrite($fp, json_encode($statusJson));
fclose($fp);
$metadataJson = [];

// Processing each file
foreach ($filesToProcess as $file) {
  $time_start = microtime(true); 

    // Conversion to stl if its a obj
    if(file_exists($file["objectPath"])) {
        $file_extension = pathinfo($file["objectPath"], PATHINFO_EXTENSION);

        // Checking validity of file for stl2pov
        if(file_exists($file["objectPath"]) &&  strtolower($file_extension) != "stl"){
          echo(PHP_EOL."Converting file to stl".PHP_EOL);
            $stlPath = str_replace($file_extension, 'stl', $file["objectPath"]);
            exec("ctmconv ".$file["objectPath"]." ".$stlPath, $outputConv);
            if(file_exists($stlPath)){
              $file["objectPath"] = str_replace($file_extension, "stl", $file["objectPath"]);
            } else {
              echo("Error in conversion : ");
              var_dump($outputConv);
              array_push($statusJson, [
                "file conversion" => [
                  "status" => "error",
                  "message" => $outputConv
                ]
              ]);
              $fp = fopen($STATUSARG.'/status.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
              $fp = fopen('/app/files/results.json', 'w');
                    fwrite($fp, json_encode($statusJson));
                    fclose($fp);
              return 0;
            }
        }
    } else {
        echo("Error file".$file["objectName"]." not found");
        $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
              return 0;
    }

    echo($file["objectPath"]);

    // Stl simplification under threshold
    $treshold = 5242880;
    $filesize = filesize($file["objectPath"]);
    echo(PHP_EOL."old size : ".$filesize.PHP_EOL);
    if ($filesize > $treshold) {
      $path = "tmp/".$file["objectName"]."-simplified.stl";
      $percentageDecrease = 1 - round(($filesize - $treshold)/$filesize, 2, PHP_ROUND_HALF_DOWN);
      echo("Percentage to decrease: ".$percentageDecrease.PHP_EOL);
      exec("/app/a.out ".$file["objectPath"]." ".$path." ".$percentageDecrease, $outputSimp);
      
      if (file_exists($path)){

        rename($path, $file["objectPath"]);

        echo("new size : ".filesize($file["objectPath"]).PHP_EOL);
      } else {
        echo("Error while simplifying file : ");
        var_dump($outputSimp);
        array_push($statusJson, [
          "file simplification" => [
            "status" => "error",
            "message" => $outputSimp
          ]
        ]);
        $fp = fopen($STATUSARG.'/status.json', 'w');
        fwrite($fp, json_encode($statusJson));
        fclose($fp);
        $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
        return 0;
      }
    };

    // Conversion to .pov with stl2pov
    exec('/app/stl2pov '.$file["objectPath"].' > tmp/'.$file["objectName"].'_w.pov', $outputSTLPOV);
    if(!file_exists('tmp/'.$file["objectName"].'_w.pov')) {
        echo("Error reading the file data content");

        // Writting the error on the status file
        array_push($statusJson, [
          "stl2pov conversion" => [
            "status" => "error",
            "message" => $outputSTLPOV
          ]
        ]);

        $fp = fopen($STATUSARG.'/status.json', 'w');
        fwrite($fp, json_encode($statusJson));
        fclose($fp);

        $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
        return 0;
    } else {
        // Editing the status file
        array_push($statusJson, [
          "stl2pov conversion" => [
            "status" => "done",
            "progress" => "100%"
          ]
        ]);
        $fp = fopen($STATUSARG.'/status.json', 'w');
        fwrite($fp, json_encode($statusJson));
        fclose($fp);

        // Reading the pov file
        $fileName = 'tmp/'.$file["objectName"].'_w.pov';

        // Preparing the pov file for the render
        // the name of the mesh to correspond to the template
        $reading = fopen($fileName, 'r');
        $writing = fopen($file["objectName"].'tmp', 'w');
        
        $replaced = false;
        
        // Replacing the name of the mesh to correspond to the template
        while (!feof($reading)) {
          $line = fgets($reading);
          if (stristr($line,'mesh {')) {
            $line = "#declare m_body= mesh {";
            $replaced = true;
          }
          fputs($writing, $line);
        }
        
        fclose($reading); fclose($writing);

        if ($replaced) 
        {
          rename($file["objectName"].'tmp', $fileName);
        } else {
          unlink($file["objectName"].'tmp');
        }

        copy($fileName, 'tmp/'.$file["objectName"].'_h.pov');
        // copy($fileName, $INPUTARG.'/'.$file["objectName"].'.pov');

        $fileName2 = 'tmp/'.$file["objectName"].'_h.pov';

        // Reading the templates files
        $templateV = file_get_contents('template-v.pov', true);
        $templateH = file_get_contents('template-h.pov', true);

        // Adding the template to the pov files
        file_put_contents($fileName, $templateH, FILE_APPEND);
        file_put_contents($fileName2, $templateV, FILE_APPEND);

        // Creating the tmp folder for the frames
        if(!is_dir('tmp/'.$file["objectName"])) {
            mkdir('tmp/'.$file["objectName"]);
        }

        // Generating the frames
        $framesTotal = 15;
        $Width = 720;
        $Height = 720;

        $outputTmpFrames = 'tmp/'.$file["objectName"].'/';

        $command1 = 'povray "'.'tmp/'.$file["objectName"].'_w.pov'.'" +FN +W'.$Width.' +H'.$Height.' -O'.$outputTmpFrames.' +Q9 +AM1 +A +UA -D +KFI0 +KFF'.$framesTotal.'';
        $command2 = 'povray "'.'tmp/'.$file["objectName"].'_h.pov'.'" +FN +W'.$Width.' +H'.$Height.' -O'.$outputTmpFrames.' +Q9 +AM1 +A +UA -D +KFI0 +KFF'.$framesTotal.'';
        
        for ($x = 1; $x <= 2; $x++){

                    // Executing Povray
          exec(($x == 1 ? $command1 : $command2));

          // Checking if the frames are generated.
          // We will assume that if frame 0 and 15 exists then the execution was successfull
          if(!file_exists($outputTmpFrames.$file["objectName"].($x == 1 ? '_w' : '_h').'00.png') && !file_exists($outputTmpFrames.$file["objectName"].($x == 1 ? '_w' : '_h').'15.png')){
              echo("Error when generating the 360");

              // Writting the error on the status file
              array_push($statusJson, [
                "Povray rendering" => [
                  "status" => "error"
                ]
              ]);

              $fp = fopen($STATUSARG.'/status.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);

              $fp = fopen('/app/files/results.json', 'w');
              fwrite($fp, json_encode($statusJson));
              fclose($fp);
              return 0;
          } else {
            array_push($statusJson, [
              "Povray rendering" => [
                "status" => "done",
                "progress" => "100%"
              ]
            ]);

            $fp = fopen($STATUSARG.'/status.json', 'w');
            fwrite($fp, json_encode($statusJson));
            fclose($fp);

            $final = imagecreatetruecolor(($framesTotal+1)*$Width, $Height);

            $transparent = imagecolorallocatealpha( $final, 0, 0, 0, 127 );
            imagefill( $final, 0, 0, $transparent ); 

            // Merging the 16 frames into 1 the 360 image
            for ($i = 0; $i < 16; $i++){
                $frameToAdd = imagecreatefrompng('tmp/'.$file["objectName"].'/'.$file["objectName"].($x == 1 ? '_w' : '_h').($i < 10 ? '0' : '').$i.'.png');
                
                // If its the first frame then keep the orinial file or else use the last generated file
                if($i > 0){
                    $final = imagecreatefrompng($OUTPUTARG.'/'.$file["objectName"].($x == 1 ? '_w' : '_h').'_360.png');
                }

                // Copy the new frame inside the previously generated file
                imagecopyresampled($final, $frameToAdd, $i*$Width, 0, 0, 0, $Width, $Height, $Width, $Height);

                // Turn off alpha blending
                imagealphablending($final, false);

                // Do desired operations

                // Set alpha flag
                imagesavealpha($final, true);


                imagepng($final, $OUTPUTARG.'/'.$file["objectName"].($x == 1 ? '_w' : '_h').'_360.png', 9);

                // Clearing file from memory
                imagedestroy($frameToAdd);
                imagedestroy($final);
            }

            // Optipng
            exec('optipng -o1 '.$OUTPUTARG.'/'.$file["objectName"].($x == 1 ? '_w' : '_h').'_360.png');

          }

        }

          array_push($statusJson, [
            "360 making" => [
              "status" => "done",
              "progress" => "100%"
            ]
          ]);

          $fp = fopen($STATUSARG.'/status.json', 'w');
          fwrite($fp, json_encode($statusJson));
          fclose($fp);
      }  
    }

// Clearing the tmp folder recursively
$dir = 'tmp';
$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

foreach($files as $file) {

    if ($file->isDir()){
        rmdir($file->getRealPath());
    } else {
        unlink($file->getRealPath());
    }

}
$time_end = microtime(true);

$execution_time = ($time_end - $time_start)/60;

array_push($statusJson, [
  "processing" => [
    "status" => "done",
    "progress" => "100%",
    "execution_time" => $execution_time
  ]
]);

// Writting the status file
$fp = fopen($STATUSARG.'/status.json', 'w');
fwrite($fp, json_encode($statusJson));
fclose($fp);

return 0;

?>
