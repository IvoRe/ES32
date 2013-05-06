##Overview
The ES3 Class is an extension for the Yii Framework.

It is a wrapper for the S3, Amazon's Simple Storage Solution. It is specific for version II of the AWS SDK for PHP.

ES3 also emulates a directory structure of S3, through a prefix.

###Requirements
 - [AWS SDK for PHP II](http://aws.amazon.com/sdkforphp2/)

###How to use
Put the ES3 Class in Yii's extensions subdirectory.
In config.main.php, include:

    'components'=>array(
      's3'=>array(
        'class' => 'ext.es3.ES3',
        'aKey' => '', // your account key, obtain from Amazon
        'sKey' => '', // your secret key, obtain from Amazon
      ),
    )

####Examples
**create a bucket**  

    Yii::app()->s3->createBucket('my.unique.bucket');
    
    
**upload a file**  

    $file = '/path/to/your/file';
    $bucket = 'my.unique.bucket';
    Yii::app()->s3->uploadFile($file, $bucket);	    

**upload a large file **  

    $file = '/path/to/your/file';
    $bucket = 'my.unique.bucket';
    Yii::app()->s3->uploadMultiPart($file, $bucket);

**download a file **  

    $newfile = '/path/to/file/to/be/created';
    $bucket = 'my.unique.bucket';
    $key = 'name.of.object';
    Yii::app()->s3->download($key, $bucket, $newfile);
    
**and many, many more...**
====
