<?php

use \Aws\S3\S3Client;
use \Aws\Common\Enum\Region;
use \Aws\Common\Exception\MultipartUploadException;
/**
 * ES3 class file.
 *
 * ES3 is a wrapper for AWS SDK for PHP version II 
 * Based on the ES extension by author Dana Luther (dana.luther@gmail.com) for version I.
 * @author Ivo Renkema (public@essential-strategy.com)
 * @copyright Copyright no copyright claimed by the author
 */

 class ES3 extends CApplicationComponent
{

    private $_s3; // the AWS client
    public $aKey; // AWS Access key
    public $sKey; // AWS Secret key  
    public $region; // AWS region
    public $bucket; // AWS bucket
    

    private function getInstance()
    {
        if ($this->_s3 === NULL) $this->connect();
		return $this->_s3;
    }

	/**
	 * Instantiate the S3 object
	 */
    public function connect()
    {
        if ( $this->aKey === NULL || $this->sKey === NULL )	throw new CException('S3 Keys are not set.');
        
            // set Europe West (Ireland) as default region:
        $this->region = ($this->region ) ? $this->region : 'eu-west-1';
                
            // Note: Amazon suggests several ways to install the AWS SDK for PHP II on your server
            // See which on works for you: http://docs.aws.amazon.com/awssdkdocsphp2/latest/gettingstartedguide/sdk-php2-installing-the-sdk.html
            // You may have to reconfigure the server for this to work.
            // Include Amazon's AWS SDK using the Composer autoloader:
        require Yii::getPathOfAlias('ext.aws.vendor.').DIRECTORY_SEPARATOR.'autoload.php';
            
            // Instantiate the S3 client with your AWS credentials and desired AWS region
        $this->_s3 = S3Client::factory(array(
            'key'    => $this->aKey, 
            'secret' => $this->sKey, 
            'region' => $this->region,
        )); 
    }
    
	/**
         * Uploads a file to Amazon S3.
	 * @param string $file File to upload; name must include full path
	 * @param string $bucket File will become an object in Bucket by this name
	 * @param string $key Name of the object(i.e. file) with the bucket. If no key is specified, the filename will be used.
	 */
    public function uploadFile( $file, $bucket="", $key = "" )
    {
        $s3 = $this->getInstance();
		
        if( $bucket == "" ) {
            $bucket = $this->bucket;
	}
	
	if ($bucket === NULL || trim($bucket) == "") {
            throw new CException('Bucket name cannot be empty.');
	}

	if(!file_exists($file))  {
            throw new CException('File to-be-uploaded was not found.');
        }	
        if( $key == "" )
	{
            $key =  pathinfo($file,PATHINFO_BASENAME);
	}
	// Upload an object by streaming the contents of a file
        // $file should be absolute path to a file on disk
        $result = $s3->putObject(array(
            'Bucket'     => $bucket,
            'Key'        => $key,
            'SourceFile' => $file,
       /*     'Metadata'   => array(
                'Foo' => 'abc',
                'Baz' => '123'
            ) */
        ));

        // We can poll the object until it is accessible:
        $s3->waitUntilObjectExists(array(
            'Bucket' => $bucket,
            'Key'    => $key,
        ));
    } // END function
	
	/**
         * Use this to upload a large file to Amazon S3.
	 * @param string $file File to upload; name must include full path
	 * @param string $bucket File will become an object in Bucket by this name
	 * @param string $key Name of the object(i.e. file) with the bucket. If no key is specified, the filename will be used.
	 */
    public function uploadMultiPart( $file, $bucket="", $key = "" )
    {
        $s3 = $this->getInstance();
		
        if( $bucket == "" )
	{
            $bucket = $this->bucket;
	}
	
	if ($bucket === NULL || trim($bucket) == "")
        {
            throw new CException('Bucket name cannot be empty.');
	}

	if(!file_exists($file))
        {
            throw new CException('File to-be-uploaded was not found.');
        }	
        if( $key == "" )
	{
            $key =  pathinfo($file,PATHINFO_BASENAME);
	}
		
        $uploader = \Aws\S3\Model\MultipartUpload\UploadBuilder::newInstance()
            ->setClient($s3)
            ->setSource($file)
            ->setBucket($bucket)
            ->setKey($key)
   //         ->setOption('Metadata', array('Foo' => 'Bar'))
    //        ->setOption('CacheControl', 'max-age=3600')
            ->build();

            // Perform the upload. Abort the upload if something goes wrong
        try {
            $uploader->upload();
            echo "Upload complete.\n";
        } 
	catch (MultipartUploadException $e) {
            $uploader->abort();
            echo "Upload failed.\n";
        }
    } // END function
    
	/**
	 * @param mixed $input May be a string, an array or an object. The latter two will be json_encoded
	 * @param string $bucket Name of the bucket; optional.
	 */
    public function uploadObject( $input, $bucket="", $key ="" )
    {
        $s3 = $this->getInstance();
		
        if( $bucket == "" )
		{
            $bucket = $this->bucket;
		}
	
		if ($bucket === NULL || trim($bucket) == "")
        {
            throw new CException('Bucket name cannot be empty');
		}
    
	if($key == "")
        {
            throw new CException('A new key (name) for the object has not been specified');
        }
        if (is_array($input)) 
        {
            $object = json_encode($input);
        }
        else if (is_object($input))
        {
            $object = json_encode($input);
        }
        else if (is_string($input)) 
        {
            $object = $input;
        }
        else
        {
            throw new CException('Input is not in acceptable format');
        }
        
        // Upload an object to Amazon S3
        $result = $s3->putObject(array(
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $object,
        ));

        // Access parts of the result object
        echo $result['Expiration'] . "\n";
        echo $result['ServerSideEncryption'] . "\n";
        echo $result['ETag'] . "\n";
        echo $result['VersionId'] . "\n";
        echo $result['RequestId'] . "\n";

        // Get the URL the object can be downloaded from
        echo $result['ObjectURL'] . "\n";
		
    } // END function
    
        /**
	 * @param string $key The key/name of the object in the bucket
	 * @param string $bucket Name of the bucket on S3
	 */
    public function download($key, $bucket="", $full_path = null )
    {	
        $s3 = $this->getInstance();
        $options = array(
            'Bucket' => $bucket,
            'Key' => $key,
        );
        if( ($full_path != null) && (!is_string($full_path)) ) {
            throw new CException('File location is not a string');
        }
        if ($full_path != null) {
            $options = array_merge((array)$options, array(
                'SaveAs' => $full_path
            ));
        }
			//Check and see if our file exists:
        if($s3->doesObjectExist($bucket,$key))
        {
                    //Now grab our file.
            $obj= $s3->getObject($options);
                    //get our file Content type (if set when uploaded to S3)
            $ctype=$obj->get('ContentType');
                    //get our file data itself
            $data=$obj->get('Body');
            echo 'data '.$data;
        }
        else
        {
            throw new CException('Object does not exist in Bucket');
        } // end if
    }
         
        /**
	 * @return object An Guzzle\Service\Resource\Model object. This object contains all of the data
         *  returned from the service in a normalized array like object. The object also contains a get()
         *  method used to retrieve values from the model by name, and a getPath() method that can be 
         * used to retrieve nested values.
         * @param string $bucket Name of the bucket
	 */
    public function createBucket($bucket = "")
    {
        $s3 = $this->getInstance();
        if( $bucket == "" )
	{
            $bucket = $this->bucket;
	}
	
	if ($bucket === NULL || trim($bucket) == "")
        {
            throw new CException('Bucket param cannot be empty');
	}
        
        try {
                //If you don't specify a region endpoint, createBucket() defaults to US standard
                //If you want the bucket elsewehere, set the LocationContraint parameter to a specific Region
            $s3->createBucket(array(
                'Bucket' => $bucket,
                'LocationConstraint' => \Aws\Common\Enum\Region::EU_WEST_1));
        } catch (BucketAlreadyExistsException $e) {
            echo 'That bucket already exists! ' . $e->getMessage() . "\n";
        }
            // explanation of waitUntilBucketExists: 
            // http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/feature-waiters.html
        $s3->waitUntilBucketExists(array(
            'Bucket'              => $bucket,
            'waiter.interval'     => 10,
            'waiter.max_attempts' => 3
        ));
    }
    
        /**
	      * @return object An Guzzle\Service\Resource\Model object. This object contains all of the data
         *  returned from the service in a normalized array like object. The object also contains a get()
         *  method used to retrieve values from the model by name, and a getPath() method that can be 
         * used to retrieve nested values.
	        */
    public function buckets()
    {
            $s3 = $this->getInstance();
            return $s3->listBuckets();
            
            $result = $s3->listBuckets();
                
            foreach ($result['Buckets'] as $bucket) {
                // Each Bucket value will contain a Name and CreationDate
                echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
            }
    }
	
        /**
	 * @return object An Guzzle\Service\Resource\Model object. This object contains all of the data
         *  returned from the service in a normalized array like object. The object also contains a get()
         *  method used to retrieve values from the model by name, and a getPath() method that can be 
         * used to retrieve nested values. Use a toArray() method to convert to ... an array.
	 * @param string $bucket The name of the bucket
	 * @param string $prefix The prefix (i.e. parent diretories) of the objects to be listed
	 */
    public function listObjects($bucket, $prefix = '')
    {
            $s3 = $this->getInstance();
            return $s3->getIterator('ListObjects', array(
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ));
    }
       /**
	 * @return boolean 
	 * @param string $key The key/name of the object in the bucket
	 * @param string $bucket Name of the bucket on S3
	 */
    public function deleteObject($bucket, $key)
    {
        $s3 = $this->getInstance();
        $s3->registerStreamWrapper();
        return unlink('s3://'.$bucket.DIRECTORY_SEPARATOR.$key);
    }
    
	// Passthru function for basic functions:
    public function call( $func )
    {
		$s3 = $this->getInstance();
		return $s3->$func();
    }
         
    
} // END class
?>
