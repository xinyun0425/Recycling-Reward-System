<?php
require 'google-api-php-client/vendor/autoload.php'; 

function uploadToGoogleDrive($filePath, $fileName) {
    $client = new Google\Client();
    $client->setHttpClient(new GuzzleHttp\Client(['verify' => false])); 
    $client->setAuthConfig('keen-diode-454703-r9-847455d54fc8.json');
    $client->addScope(Google\Service\Drive::DRIVE_FILE);
    
    $service = new Google\Service\Drive($client);

    $fileMetadata = new Google\Service\Drive\DriveFile([
        'name' => $fileName, 
        'parents' => ['1ifuDZKMObiclp8U2nQNT6cDIOV8Jwnhy'] 
    ]);

    $content = file_get_contents($filePath);
    if (!$content) {
        die("Error: Unable to read file.");
    }
    
    $mimeType = mime_content_type($filePath);

    try {
        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        $fileID = $file->id;

        $permission = new Google\Service\Drive\Permission();
        $permission->setType('anyone');
        $permission->setRole('reader');
        $service->permissions->create($fileID, $permission);

        return $fileID;
    } catch (Exception $e) {
        echo "Error uploading file: " . $e->getMessage();
        return false;
    }
}

$filePath = "User-Logo.png"; // Adjust filename
$fileName = "test_image.jpg";

$fileID = uploadToGoogleDrive($filePath, $fileName);

if ($fileID) {
    echo "File uploaded successfully! Google Drive ID: " . $fileID;
} else {
    echo "Upload failed.";
}
?>
