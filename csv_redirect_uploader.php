<?php 

	/**
	 * Tested on Magento 1.9
	 *
	 */

	set_time_limit(0);

	ini_set('memory_limit', '4G');

	$mage = dirname(dirname(__DIR__)) . '/trunk/app/Mage.php'; //Mage location

	if ( file_exists($mage) ){

		require_once $mage;
	    umask(0);
	    Mage::init();

		/**
		 * RUN BELOW COMMAND ON TERMINAL
		 * php {path_to_script}csv_redirect_uploader.php
		 *
		 */

		$filename = 'import/redirect.csv'; //csv location

		if (!file_exists($filename)) {
   			echo PHP_EOL.'"'.$filename.'" does not exist! Aborting...'.PHP_EOL.PHP_EOL;
   			exit;
        }

        $total = 0;
        $totalSuccess = 0;
        $totalDelete = 0;
        $length =  0;
        $delimiter = ',';
        $enclosure = '"';
        $escape = '\\';
        $skipline = 1; //Skip line 1

        $rewrite = Mage::getModel('core/url_rewrite');

         if (($fp = fopen($filename, 'r'))) {
            while (($line = fgetcsv($fp, $length, $delimiter, $enclosure, $escape))) {

                $total++;
                if ($skipline && ($total == 1)) {
                    continue;
                }

                $requestPath = $line[0];
                $targetPath = $line[1];

               

                /**
                 * Dev note : This script will update duplicated request_path;
                 *
                 */

                $existing = $rewrite->loadByRequestPath($requestPath);
                if(!empty($existing)){
                  if($existing['request_path'] == $requestPath){
                    $existing->delete();
                    $totalDelete++;
                  }
                }

           		 $rewrite->setIdPath(uniqid())
                    ->setTargetPath($targetPath)
                    ->setOptions('RP')
                    ->setDescription('Uploaded via script.')
                    ->setRequestPath($requestPath)
                    ->setIsSystem(0)
                    ->setStoreId(0);

                try {
                   $rewrite->save();
                   $totalSuccess++;
                } catch (Exception $e) {
                    $logException = $e->getMessage();
                    Mage::logException($e);
                }
            }
            fclose($fp);       
             //Uncomment below if you want to delete file after upload.
             //unlink($filename);

           
           	if($totalDelete > 0){
           		echo $totalDelete.' URL rewrites have been deleted due to duplicates from your csv, please run the script again to update.'.PHP_EOL;
           	}else{
           		echo PHP_EOL.''.$totalSuccess.' URL rewrites have been imported.'.PHP_EOL;
           	}

            if (!empty($logException)) {
                echo 'Error : '.$logException.''.PHP_EOL.PHP_EOL;
            }else{
            	echo "Finished......100%".PHP_EOL.PHP_EOL;
            }    	

       }


	}

?>