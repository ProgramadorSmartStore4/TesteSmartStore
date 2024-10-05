<?php
require 'vendor/autoload.php'; // Carrega o SDK da AWS usando o Composer

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$bucketName = 'fabriciogcruz';
$region = 'us-east-1'; // Defina a região correta do seu bucket
$bucketUrl = "https://$bucketName.s3.amazonaws.com"; // URL base do bucket

// Configurações do AWS S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $region,
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadCount = count($_FILES['images']['name']);
        $successCount = 0;
        $uploadedImages = []; // Array para armazenar os links das imagens

        for ($i = 0; $i < $uploadCount; $i++) {
            $fileTmpPath = $_FILES['images']['tmp_name'][$i];
            $fileName = $_FILES['images']['name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileType = $_FILES['images']['type'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Verificar se a extensão do arquivo é PNG ou JPEG
            if (in_array($fileExt, ['png', 'jpeg', 'jpg'])) {
                try {
                    // Definir o nome único do arquivo
                    $key = 'uploads/' . uniqid() . '-' . basename($fileName);

                    // Enviar arquivo para o bucket S3
                    $s3->putObject([
                        'Bucket' => $bucketName,
                        'Key'    => $key,
                        'SourceFile' => $fileTmpPath,
                        'ACL'    => 'public-read', // Permitir leitura pública
                    ]);

                    $uploadedImages[] = $bucketUrl . '/' . $key; // Adiciona o link da imagem ao array
                    $successCount++;
                } catch (S3Exception $e) {
                    echo "Erro ao enviar arquivo para S3: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "Tipo de arquivo não suportado: $fileName (somente PNG ou JPEG permitidos)<br>";
            }
        }

        if ($successCount > 0) {
            echo "$successCount imagem(ns) enviada(s) com sucesso!<br>";
            echo "Imagens enviadas:<br>";
            foreach ($uploadedImages as $imageLink) {
                echo "<a href='$imageLink' target='_blank'><img src='$imageLink' width='200' alt='Imagem enviada'></a><br>";
            }
        } else {
            echo "Nenhuma imagem foi enviada.";
        }
    } else {
        echo "Nenhuma imagem foi selecionada.";
    }
}
?>

