<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CsvController extends AbstractController
{
    #[Route('/api/upload', name: 'csv_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $handle = fopen($file->getPathname(), 'r');
        fgetcsv($handle); 

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            // Debug output
            dump($data); // Symfony Debug Tool
            error_log(print_r($data, true));
        
            // Check if the row has valid data
            if (count($data) < 5 || empty(trim($data[0]))) {
                continue; // Skip empty or malformed rows
            }
        
            $user = new User();
            $user->setName(trim($data[0]));
            $user->setEmail(trim($data[1]));
            $user->setUsername(trim($data[2]));
            $user->setAddress(trim($data[3]));
            $user->setRole(trim($data[4]));
        
            $em->persist($user);
        
            // Send email notification
            $email = (new Email())
                ->from('noreply.travelplanner2024@gmail.com')
                ->to(trim($data[1]))
                ->subject('Welcome to Our Platform')
                ->text("Hello {$data[0]},\n\nYour account has been created successfully!");
        
            $mailer->send($email);
        }
        

        fclose($handle);
        $em->flush();

        return $this->json(['message' => 'CSV uploaded successfully and emails sent']);
    }

    #[Route('/api/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->json($users);
    }

    #[Route('/api/backup', name: 'backup_database', methods: ['GET'])]
public function backupDatabase(): Response
{
    $backupDir = __DIR__ . '/../../backups'; // Ensure the backups folder exists
    $backupFile = $backupDir . '/backup.sql';

    // Create directory if it doesn't exist
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    $mysqldumpPath = '"C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe"';
    $database = 'user_management';
    $user = 'root';
    $password = 'Ganesha@31';

    // Ensuring data, structure, and additional options are included
    $command = "$mysqldumpPath -u $user --password=\"$password\" --databases $database "
        . "--add-drop-database --add-drop-table --routines --events --triggers --complete-insert --quick > \"$backupFile\" 2>&1";

    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        return $this->json([
            'error' => 'Database backup failed',
            'details' => implode("\n", $output)
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->json([
        'message' => 'Database backup successful',
        'backup_file' => $backupFile
    ]);
}

#[Route('/api/restore', name: 'restore_database', methods: ['POST'])]
public function restoreDatabase(): Response
{
    // Get absolute path
    $backupDir = realpath(__DIR__ . '/../../backups');
    $backupFile = $backupDir . '/backup.sql';

    // Debugging: Check if file exists
    if (!file_exists($backupFile)) {
        return $this->json(['error' => 'Backup file not found at ' . $backupFile], Response::HTTP_BAD_REQUEST);
    }

    $mysqlPath = '"C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe"';
    $database = 'user_management';
    $user = 'root';
    $password = 'Ganesha@31';

    // Ensure path is escaped properly
    $command = "$mysqlPath -u $user --password=\"$password\" $database < \"$backupFile\" 2>&1";

    $output = [];
    $returnVar = null;
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        return $this->json([
            'error' => 'Database restore failed',
            'details' => implode("\n", $output),
            'command' => $command,  // Debugging
            'backup_file' => $backupFile
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->json(['message' => 'Database restored successfully']);
}


}
