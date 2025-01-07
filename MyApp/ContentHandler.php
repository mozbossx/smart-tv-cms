<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class ContentHandler implements MessageComponentInterface 
{
    protected $clients;
    protected $pdo;

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        echo "Content Server Started!\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // $this->clients->attach($conn);
        // echo "New connection! ({$conn->resourceId})\n";

        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParameters);

        $user_id = isset($queryParameters['user_id']) ? urldecode($queryParameters['user_id']) : null;
        $full_name = isset($queryParameters['full_name']) ? urldecode($queryParameters['full_name']) : 'Unknown';
        $user_type = isset($queryParameters['user_type']) ? urldecode($queryParameters['user_type']) : 'Unknown';
        $department = isset($queryParameters['department']) ? urldecode($queryParameters['department']) : 'Unknown';
        $email = isset($queryParameters['email']) ? urldecode($queryParameters['email']) : 'Unknown';
       
        // Store the values in the connection object for later use
        $conn->user_id = $user_id;
        $conn->full_name = $full_name;
        $conn->user_type = $user_type;
        $conn->department = $department;
        $conn->email = $email;

        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Handle delete actions
        if (isset($data['action']) && $data['action'] === 'delete_content') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id'],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id'],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id'],
                'orgchart' => ['table' => 'org_chart_members', 'idField' => 'orgchart_id']
            ];
            $type = $data['type'] ?? null;

            if (!isset($validTypes[$type])) {
                // If it's not a default type, check if it's a new feature
                $stmt = $this->pdo->prepare("SELECT * FROM features_tb WHERE type = ?");
                $stmt->execute([strtolower($type)]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($feature) {
                    // It's for 'new feature', so we need to determine its table and id field
                    $validTypes[$type] = [
                        'table' => strtolower($feature['type']) . '_tb',
                        'idField' => strtolower($feature['type']) . '_id'
                    ];
                } else {
                    // If it's neither a default type nor a new feature, it's invalid
                    $response = ['action' => 'delete_content', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }

            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];
            
            $content_id = $data[$idField];
            $user_id = $data['user_id']; // logged-in user's ID
            $author_id = $data['author_id']; // author's ID
            $author_name = $data['author_name'];
            $author_user_type = $data['author_user_type'];
            $author_department = $data['author_department'];
            $evaluator_message = $data['evaluator_message'] ?? null;
            $evaluator_name = $data['evaluator_name'];
            $content_tv_id = $data['tv_id'] ?? null;

            /*
                $user_id = $data['user_id'];
                $email = $data['email'];
                $full_name = $data['full_name'];
                $user_type = $data['user_type'];
                $department = $data['department'];
                $evaluator_name = $data['evaluator_name'];
                $evaluator_id = $data['evaluator_id'];
            */

            // First, check if media_path column exists in the table
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as column_exists 
                FROM information_schema.COLUMNS 
                WHERE TABLE_NAME = ? 
                AND COLUMN_NAME = 'media_path'
                AND TABLE_SCHEMA = DATABASE()
            ");
            $stmt->execute([$table]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Only fetch and handle media_path if the column exists
            if ($result['column_exists']) {
                $stmt = $this->pdo->prepare("SELECT media_path FROM {$table} WHERE {$idField} = ?");
                $stmt->execute([$content_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If there's a media file, delete it
                if ($result && !empty($result['media_path'])) {
                    $mediaFolder = "{$type}_media";
                    $mediaPath = "{$mediaFolder}/{$result['media_path']}";
                    if (file_exists($mediaPath)) {
                        unlink($mediaPath); // Delete the file
                    }
                    error_log("Deleted media file: {$mediaPath}");
                }
            }

            $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE {$idField} = ?");
            $stmt->execute([$content_id]);
                
            $deleted = $stmt->rowCount() > 0;
            if ($deleted) {
                $stmtSearchForRejectedByAdminNotification = $this->pdo->prepare("SELECT * FROM notifications_tb WHERE user_id = ? AND content_id = ? AND notification_type = 'content_rejected_by_admin'");
                $stmtSearchForRejectedByAdminNotification->execute([$user_id, $content_id]);
                $rejectedByAdminNotification = $stmtSearchForRejectedByAdminNotification->fetchAll(PDO::FETCH_ASSOC);

                if ($rejectedByAdminNotification) {
                    $stmtDeleteRejectedByAdminNotification = $this->pdo->prepare("DELETE FROM notifications_tb WHERE user_id = ? AND content_id = ? AND notification_type = 'content_rejected_by_admin'");
                    $stmtDeleteRejectedByAdminNotification->execute([$user_id, $content_id]);
                }

                // Insert notification for the user who deleted his own content
                if ($author_id == $user_id && $evaluator_name != "System") {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_deleted', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                } else if ($author_id == $user_id && $evaluator_name == "System") {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_deleted_by_system', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                }

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$author_department, $author_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], $type, 'content_deleted', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateDepartmentAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateDepartmentAdmin->execute([$admin['user_id']]);
                    }
                }

                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$author_id, $user_id]);
                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($superAdmins) {
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($superAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], $type, 'content_deleted', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);
                        
                        $stmtUpdateSuperAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateSuperAdmin->execute([$superAdmin['user_id']]);
                    }
                }
                
                // Insert notification for the user own the content but got deleted by admin
                if ($author_id != $user_id) {
                    $stmtNotifyUser = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser->execute([$content_id, $user_id, $type, 'content_deleted', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtNotifyUser2 = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser2->execute([$content_id, $author_id, $type, 'content_deleted_by_admin', 'deleted', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$author_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$user_id]);
                }
        
                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');

                $response = [
                    'action' => 'delete_content', 
                    'success' => $deleted, 
                    'type' => $type, 
                    $idField => $content_id
                ];
                
                echo "{$type} deleted!\n";

                // Notify the client who sent the request and all connected clients
                $from->send(json_encode($response));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'trash_content') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id'],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id'],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id']
            ];
            $type = $data['type'] ?? null;

            if (!isset($validTypes[$type])) {
                // If it's not a default type, check if it's a new feature
                $stmt = $this->pdo->prepare("SELECT * FROM features_tb WHERE type = ?");
                $stmt->execute([strtolower($type)]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($feature) {
                    // It's a new feature, so we need to determine its table and id field
                    $validTypes[$type] = [
                        'table' => strtolower($feature['type']) . '_tb',
                        'idField' => strtolower($feature['type']) . '_id'
                    ];
                } else {
                    // If it's neither a default type nor a new feature, it's invalid
                    $response = ['action' => 'trash_content', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }

            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];

            $content_id = $data[$idField];
            $user_id = $data['user_id']; // logged-in user's ID
            $author_id = $data['author_id']; // author's ID
            $author_name = $data['author_name'];
            $author_user_type = $data['author_user_type'];
            $author_department = $data['author_department'];
            $evaluator_message = $data['evaluator_message'] ?? null;
            $evaluator_name = $data['evaluator_name'];
            $content_tv_id = $data['tv_id'];
                
            // Update isTrashed to 1
            $stmt = $this->pdo->prepare("UPDATE {$table} SET isTrashed = 1, isArchived = 0, datetime_trashed = NOW(), datetime_archived = NULL WHERE {$idField} = ?");
            $stmt->execute([$content_id]);

            $trashed = $stmt->rowCount() > 0;

            if ($trashed) {
                // Insert notification for the user who trashed his own content
                if ($author_id == $user_id && $evaluator_name != "System") {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_trashed', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                } else if ($author_id == $user_id && $evaluator_name == "System") {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_trashed_by_system', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                }

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$author_department, $author_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], $type, 'content_trashed', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateDepartmentAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateDepartmentAdmin->execute([$admin['user_id']]);
                    }
                }

                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$author_id, $user_id]);
                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($superAdmins) {
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($superAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], $type, 'content_trashed', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);
                        
                        $stmtUpdateSuperAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateSuperAdmin->execute([$superAdmin['user_id']]);
                    }
                }
                
                // Insert notification for the user own the content but got trashed by admin
                if ($author_id != $user_id) {
                    $stmtNotifyUser = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser->execute([$content_id, $user_id, $type, 'content_trashed', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtNotifyUser2 = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser2->execute([$content_id, $author_id, $type, 'content_trashed_by_admin', 'trashed', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$author_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$user_id]);
                }
                // // Insert notification for the user who owns the content
                // if ($user_id == $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name) VALUES (?, ?, ?, 'content_trashed', 'trashed', ?, ?)");
                //     $stmt->execute([$id, $author_id, $type, $evaluator_message, $evaluator_name]);

                //     $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser1->execute([$author_id]);
                // }

                // // Insert notification for the user who doesn't own the content
                // if ($author_id != $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name) VALUES (?, ?, ?, 'content_trashed_by_admin', 'trashed', ?, ?)");
                //     $stmt->execute([$id, $author_id, $type, $evaluator_message, $evaluator_name]);

                //     $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser2->execute([$user_id]);
                // }
        
                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');

                $response = ['action' => 'trash_content', 'success' => $trashed, 'type' => $type, $idField => $content_id];
                echo "{$type} trashed!\n";
                
                // Notify the client who sent the request and all connected clients
                $from->send(json_encode($response));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'archive_content') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id'],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id'],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id']
            ];
            $type = $data['type'] ?? null;

            if (!isset($validTypes[$type])) {
                // If it's not a default type, check if it's a new feature
                $stmt = $this->pdo->prepare("SELECT * FROM features_tb WHERE type = ?");
                $stmt->execute([strtolower($type)]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($feature) {
                    // It's a new feature, so we need to determine its table and id field
                    $validTypes[$type] = [
                        'table' => strtolower($feature['type']) . '_tb',
                        'idField' => strtolower($feature['type']) . '_id'
                    ];
                } else {
                    // If it's neither a default type nor a new feature, it's invalid
                    $response = ['action' => 'archive_content', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }

            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];

            $content_id = $data[$idField];
            $user_id = $data['user_id']; // logged-in user's ID
            $author_id = $data['author_id']; // author's ID
            $author_name = $data['author_name'];
            $author_user_type = $data['author_user_type'];
            $author_department = $data['author_department'];
            $evaluator_message = $data['evaluator_message'] ?? null;
            $evaluator_name = $data['evaluator_name'];
            $content_tv_id = $data['tv_id'];
                
            // Update isArchived to 1
            $stmt = $this->pdo->prepare("UPDATE {$table} SET isArchived = 1, isTrashed = 0, datetime_archived = NOW(), datetime_trashed = NULL WHERE {$idField} = ?");
            $stmt->execute([$content_id]);

            $archived = $stmt->rowCount() > 0;

            if ($archived) {
                // Insert notification for the user who archived his own content
                if ($author_id == $user_id) {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_archived', 'archived', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                } 

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$author_department, $author_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], $type, 'content_archived', 'archived', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateDepartmentAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateDepartmentAdmin->execute([$admin['user_id']]);
                    }
                }

                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$author_id, $user_id]);
                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($superAdmins) {
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($superAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], $type, 'content_archived', 'archived', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);
                        
                        $stmtUpdateSuperAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateSuperAdmin->execute([$superAdmin['user_id']]);
                    }
                }
                
                // Insert notification for the user own the content but got archived by admin
                if ($author_id != $user_id) {
                    $stmtNotifyUser = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser->execute([$content_id, $user_id, $type, 'content_archived', 'archived', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtNotifyUser2 = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser2->execute([$content_id, $author_id, $type, 'content_archived_by_admin', 'archived', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$author_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$user_id]);
                }
                // // Insert notification for the user who owns the content
                // if ($user_id == $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name) VALUES (?, ?, ?, 'content_archived', 'archived', ?, ?)");
                //     $stmt->execute([$id, $author_id, $type, $evaluator_message, $evaluator_name]);

                //     $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser1->execute([$author_id]);
                // }

                // // Insert notification for the user who doesn't own the content
                // if ($author_id != $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name) VALUES (?, ?, ?, 'content_archived_by_admin', 'archived', ?, ?)");
                //     $stmt->execute([$id, $author_id, $type, $evaluator_message, $evaluator_name]);

                //     $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser2->execute([$user_id]);
                // }
        
                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');

                $response = ['action' => 'archive_content', 'success' => $archived, 'type' => $type, $idField => $content_id];
                echo "{$type} archived!\n";
                
                // Notify the client who sent the request and all connected clients
                $from->send(json_encode($response));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'restore_content') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id'],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id'],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id']
            ];
            $type = $data['type'] ?? null;

            if (!isset($validTypes[$type])) {
                // If it's not a default type, check if it's a new feature
                $stmt = $this->pdo->prepare("SELECT * FROM features_tb WHERE type = ?");
                $stmt->execute([strtolower($type)]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($feature) {
                    // It's a new feature, so we need to determine its table and id field
                    $validTypes[$type] = [
                        'table' => strtolower($feature['type']) . '_tb',
                        'idField' => strtolower($feature['type']) . '_id'
                    ];
                } else {
                    // If it's neither a default type nor a new feature, it's invalid
                    $response = ['action' => 'restore_content', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }
            
            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];

            $content_id = $data[$idField];
            $user_id = $data['user_id']; // logged-in user's ID
            $author_id = $data['author_id']; // author's ID
            $author_name = $data['author_name'];
            $author_user_type = $data['author_user_type'];
            $author_department = $data['author_department'];
            $evaluator_message = $data['evaluator_message'] ?? null;
            $evaluator_name = $data['evaluator_name'];
            $content_tv_id = $data['tv_id'];

            // Update isArchived to 1 and isTrashed to 0
            $stmt = $this->pdo->prepare("UPDATE {$table} SET isArchived = 0, isTrashed = 0, datetime_archived = NULL, datetime_trashed = NULL WHERE {$idField} = ?");
            $stmt->execute([$content_id]);

            $restored = $stmt->rowCount() > 0;

            if ($restored) {
                // Insert notification for the user who restored his own content
                if ($author_id == $user_id) {
                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$content_id, $user_id, $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser1->execute([$user_id]);
                } 

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$author_department, $author_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateDepartmentAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateDepartmentAdmin->execute([$admin['user_id']]);
                    }
                }

                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$author_id, $user_id]);
                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($superAdmins) {
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($superAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);
                        
                        $stmtUpdateSuperAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateSuperAdmin->execute([$superAdmin['user_id']]);
                    }
                }
                
                // Insert notification for the user own the content but got restored by admin
                if ($author_id != $user_id) {
                    $stmtNotifyUser = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser->execute([$content_id, $user_id, $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtNotifyUser2 = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtNotifyUser2->execute([$content_id, $author_id, $type, 'content_restored_by_admin', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$author_id]);

                    $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    $stmtUpdateUser2->execute([$user_id]);
                }

                // Insert notification for the user who owns the content
                // if ($user_id == $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name, content_tv_id) VALUES (?, ?, ?, 'content_restored', 'restored', ?, ?, ?)");
                //     $stmt->execute([$content_id, $author_id, $type, $evaluator_message, $evaluator_name, $content_tv_id]);

                //     $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser1->execute([$author_id]);
                // }

                // // Insert notification for the user who doesn't own the content
                // if ($author_id != $user_id) {
                //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name, content_tv_id) VALUES (?, ?, ?, 'content_restored_by_admin', 'restored', ?, ?, ?)");
                //     $stmt->execute([$content_id, $author_id, $type, $evaluator_message, $evaluator_name, $content_tv_id]);

                //     $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                //     $stmtUpdateUser2->execute([$user_id]);
                // }
        
                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');
                
                $response = ['action' => 'restore_content', 'success' => $restored, 'type' => $type, $idField => $content_id];
                echo "{$type} restored!\n";
                
                // Notify the client who sent the request and all connected clients
                $from->send(json_encode($response));
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'restore_content_and_update_expiration') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id']
            ];
            $type = $data['type'] ?? null;
            
            if (isset($validTypes[$type])) {
                $table = $validTypes[$type]['table'];
                $idField = $validTypes[$type]['idField'];
                
                $content_id = $data[$idField];
                $user_id = $data['user_id']; // logged-in user's ID
                $author_id = $data['author_id']; // author's ID
                $author_name = $data['author_name'];
                $author_user_type = $data['author_user_type'];
                $author_department = $data['author_department'];
                $evaluator_message = $data['evaluator_message'] ?? null;
                $evaluator_name = $data['evaluator_name'];
                $content_tv_id = $data['tv_id'];
        
                $expirationDateTime = $data['expiration_datetime'] ?? null;
                
                // Prepare the update query
                $stmt = $this->pdo->prepare("UPDATE {$table} SET isArchived = 0, isTrashed = 0, datetime_archived = NULL, datetime_trashed = NULL, expiration_datetime = ? WHERE {$idField} = ?");
                $stmt->execute([$expirationDateTime, $content_id]);
                
                $restored = $stmt->rowCount() > 0;
        
                if ($restored) {
                    // Insert notification for the user who restored his own content
                    if ($author_id == $user_id) {
                        $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$content_id, $user_id, $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateUser1->execute([$user_id]);
                    } 

                    $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                        SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                    ");
                    $stmtSelectDepartmentAdmins->execute([$author_department, $author_id, $user_id]);
                    $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Notify each Admin of the approved user's department
                    if ($departmentAdmins) {
                        $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                            INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($departmentAdmins as $admin) {
                            $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                            $stmtUpdateDepartmentAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                            $stmtUpdateDepartmentAdmin->execute([$admin['user_id']]);
                        }
                    }

                    $stmtSelectSuperAdmins = $this->pdo->prepare("
                        SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                    ");
                    $stmtSelectSuperAdmins->execute([$author_id, $user_id]);
                    $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Notify each Admin of the approved user's department
                    if ($superAdmins) {
                        $stmtNotifySuperAdmins = $this->pdo->prepare("
                            INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($superAdmins as $superAdmin) {
                            $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);
                            
                            $stmtUpdateSuperAdmin = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                            $stmtUpdateSuperAdmin->execute([$superAdmin['user_id']]);
                        }
                    }
                    
                    // Insert notification for the user own the content but got restored by admin
                    if ($author_id != $user_id) {
                        $stmtNotifyUser = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtNotifyUser->execute([$content_id, $user_id, $type, 'content_restored', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtNotifyUser2 = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_message, evaluator_name, content_tv_id) 
                                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtNotifyUser2->execute([$content_id, $author_id, $type, 'content_restored_by_admin', 'restored', $author_name, $author_user_type, $author_department, $evaluator_message, $evaluator_name, $content_tv_id]);

                        $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateUser2->execute([$author_id]);

                        $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        $stmtUpdateUser2->execute([$user_id]);
                    }
                    // Insert notification for the user who owns the content
                    // if ($user_id == $user_id) {
                    //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name, content_tv_id) VALUES (?, ?, ?, 'content_restored', 'restored', ?, ?, ?)");
                    //     $stmt->execute([$content_id, $author_id, $type, $evaluator_message, $evaluator_name, $content_tv_id]);
        
                    //     $stmtUpdateUser1 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    //     $stmtUpdateUser1->execute([$author_id]);
                    // }
        
                    // // Insert notification for the user who doesn't own the content
                    // if ($author_id != $user_id) {
                    //     $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, content_type, notification_type, status, evaluator_message, evaluator_name, content_tv_id) VALUES (?, ?, ?, 'content_restored_by_admin', 'restored', ?, ?, ?)");
                    //     $stmt->execute([$content_id, $author_id, $type, $evaluator_message, $evaluator_name, $content_tv_id]);
        
                    //     $stmtUpdateUser2 = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                    //     $stmtUpdateUser2->execute([$user_id]);
                    // }
            
                    // Broadcast new notification to all clients
                    $this->broadcastNotification('new_notification');
                    
                    // Fetch updated data
                    $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE {$idField} = ?");
                    $stmt->execute([$content_id]);
                    $postData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($postData) {
                        $response = [
                            'action' => 'restore_content_and_update_expiration', 
                            'success' => true, 
                            'type' => $type, 
                            'data' => $postData
                        ] + $postData;
                        echo "{$type} content restored and expiration updated!\n";
                    } else {
                        $response = ['action' => 'restore_content_and_update_expiration', 'success' => false, 'message' => 'Failed to fetch updated data'];
                        echo "Failed to fetch updated data. Restore and expiration update failed.\n";
                    }
                } else {
                    $response = ['action' => 'restore_content_and_update_expiration', 'success' => false, 'message' => 'Failed to restore and update content'];
                    echo "Failed to restore and update {$type} content.\n";
                }
            } else {
                $response = ['action' => 'restore_content_and_update_expiration', 'success' => false, 'message' => 'Invalid type specified'];
                echo "Invalid type specified. Restore and expiration update failed.\n";
            }
            
            // Notify the client who sent the request and all connected clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_content') {
            $type = $data['type'];
            $user_id = $from->user_id;
            $full_name = $from->full_name;
            $user_type = $from->user_type;
            $department = $from->department;
            // $user_id = $data['user_id'];
            // $full_name = $data['full_name'];
            // $department = $data['department'];
            // $user_type = $data['user_type'];
            
            // Define valid content types and their table structures
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id', 'bodyField' => 'announcement_body'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id', 'bodyField' => 'event_body'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id', 'bodyField' => 'news_body'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id', 'bodyField' => null],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id', 'bodyField' => null],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id', 'bodyField' => null]
            ];

            // Fetch new features from the database
            $stmtFeatures = $this->pdo->prepare("SELECT feature_name, feature_id FROM features_tb");
            $stmtFeatures->execute();
            $newFeatures = $stmtFeatures->fetchAll(PDO::FETCH_ASSOC);

            // Add new features to validTypes
            foreach ($newFeatures as $feature) {
                $featureType = strtolower(str_replace(' ', '_', $feature['feature_name']));
                $validTypes[$featureType] = [
                    'table' => $featureType . '_tb',
                    'idField' => $featureType . '_id',
                    'bodyField' => null // New features use dynamic fields
                ];
            }

            $type = $data['type'] ?? null;
        
            if (isset($validTypes[$type])) {
                $table = $validTypes[$type]['table'];
                $idField = $validTypes[$type]['idField'];
                $bodyField = $validTypes[$type]['bodyField'];
                $content_id = $data[$idField];
        
                // Start building the update query
                $updateFields = [];
                $params = [];
        
                // Set status based on user type only for non-PEO/SO content
                if ($type !== 'peo' && $type !== 'so') {
                    $status = ($user_type == 'Student' || $user_type == 'Faculty' || $user_type == 'Staff') ? 'Pending' : 'Approved';
                    $updateFields[] = "status = ?";
                    $params[] = $status;
                }
        
                // Handle different content types
                if ($bodyField) {
                    // For announcement, event, news
                    $updateFields[] = "$bodyField = ?";
                    $params[] = $data["{$type}_body"];
                } else if ($type === 'peo' || $type === 'so') {
                    // For PEO and SO
                    $updateFields[] = "{$type}_title = ?, {$type}_description = ?, {$type}_subdescription = ?";
                    $params[] = $data["{$type}_title"];
                    $params[] = $data["{$type}_description"];
                    $params[] = $data["{$type}_subdescription"];
                } else {
                     // For new features - dynamically add all fields from the form
                    foreach ($data as $key => $value) {
                        // Skip arrays (like tv_ids) and special fields
                        if (!is_array($value) && !in_array($key, ['action', 'type', $idField, 'tv_id[]', 'expiration_datetime', 'tv_ids', 'action_value', 'media'])) {
                            $updateFields[] = "$key = ?";
                            $params[] = $value;
                        }
                    }
                }
        
                // Add common fields only for non-PEO/SO content
                if ($type !== 'peo' && $type !== 'so') {
                    if (isset($data['expiration_datetime'])) {
                        $updateFields[] = "expiration_datetime = ?";
                        $params[] = $data['expiration_datetime'];
                    }
                }

                if (isset($data['display_time'])) {
                    $updateFields[] = "display_time = ?";
                    $params[] = $data['display_time'];
                }
        
                // Add content_id to params
                $params[] = $content_id;
        
                // Build and execute update query
                $updateQuery = "UPDATE $table SET " . implode(", ", $updateFields) . " WHERE $idField = ?";
                $stmt = $this->pdo->prepare($updateQuery);
                $success = $stmt->execute($params);
        
                if ($success) {
                    // Handle media update if present
                    if (!empty($data['media'])) {
                        // Determine media folder
                        $mediaFolder = $type . '_media';
                        if (!file_exists($mediaFolder)) {
                            mkdir($mediaFolder, 0777, true);
                        }

                        $base64Data = $data['media'];
                        $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                        $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                        $timeNow = date('YmdHis');
                        $filename = "{$content_id}_{$timeNow}.{$fileExtension}";
                        $media_save = "{$mediaFolder}/{$filename}";

                        // Save the new media file
                        file_put_contents($media_save, $mediaData);

                        // First, check for and delete existing image
                        $stmtMediaPath = $this->pdo->prepare("SELECT media_path FROM $table WHERE $idField = ?");
                        $stmtMediaPath->execute([$content_id]);
                        $existingImage = $stmtMediaPath->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existingImage && $existingImage['media_path']) {
                            $existingFilePath = "{$mediaFolder}/" . $existingImage['media_path'];
                            if (file_exists($existingFilePath)) {
                                unlink($existingFilePath);
                                error_log("Deleted existing image: $existingFilePath");
                            }
                        }

                        // Update the record with new media_path
                        $updateStatement = $this->pdo->prepare("UPDATE $table SET media_path = ? WHERE $idField = ?");
                        $updateStatement->execute([$filename, $content_id]);

                        $data['media_path'] = $filename;
                    }
                    // Create notifications based on user type
                    if ($user_type == 'Student' || $user_type == 'Faculty' || $user_type == 'Staff') {
                        // Notify content owner
                        $stmt = $this->pdo->prepare("
                            INSERT INTO notifications_tb (
                                content_id, user_id, content_type, notification_type, 
                                status, action_user_name, action_user_department, 
                                action_user_type
                            ) VALUES (?, ?, ?, 'content_updated', 'pending', ?, ?, ?)
                        ");
                        $stmt->execute([
                            $content_id, $user_id, $type, $full_name, 
                            $department, $user_type
                        ]);
        
                        // Notify department admins
                        $stmtAdmins = $this->pdo->prepare("
                            SELECT user_id FROM users_tb 
                            WHERE user_type = 'Admin' 
                            AND department = ? 
                            AND user_id != ?
                        ");
                        $stmtAdmins->execute([$department, $user_id]);
                        $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);
        
                        foreach ($admins as $admin) {
                            $stmt->execute([
                                $content_id, $admin['user_id'], $type, 'content_updated',
                                'pending', $full_name, $department, $user_type
                            ]);
                        }
        
                        // Notify super admins
                        $stmtSuperAdmins = $this->pdo->prepare("
                            SELECT user_id FROM users_tb 
                            WHERE user_type = 'Super Admin' 
                            AND user_id != ?
                        ");
                        $stmtSuperAdmins->execute([$user_id]);
                        $superAdmins = $stmtSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
        
                        foreach ($superAdmins as $superAdmin) {
                            $stmt->execute([
                                $content_id, $superAdmin['user_id'], $type, 'content_updated',
                                'pending', $full_name, $department, $user_type
                            ]);
                        }
        
                        // Update notification counts
                        $this->pdo->prepare("
                            UPDATE users_tb 
                            SET notification_count = notification_count + 1 
                            WHERE user_id IN (
                                SELECT user_id FROM users_tb 
                                WHERE (user_type = 'Admin' AND department = ?) 
                                OR user_type = 'Super Admin'
                            )
                        ")->execute([$department]);
                    }
        
                    // Broadcast notification update
                    $this->broadcastNotification('new_notification');
        
                    // Different response messages for PEO/SO vs other content types
                    if ($type === 'peo' || $type === 'so') {
                        $response = [
                            'action' => 'update_content',
                            'success' => true,
                            'message' => ucfirst($type) . ' has been updated',
                            'type' => $type,
                            $idField => $content_id,
                            'data' => $data
                        ];
                    } else {
                        $response = [
                            'action' => 'update_content',
                            'success' => true,
                            'message' => ucfirst(str_replace('_',' ', $type)) . ' has been updated' . 
                                ($status === 'Pending' ? ' and is pending approval' : ''),
                            'type' => $type,
                            $idField => $content_id,
                            'data' => $data
                        ];
                    }

                    error_log("Content updated successfully!");
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Failed to update ' . $type
                    ];
                    error_log("Failed to update content!");
                }
        
                $from->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_background_type') {
            // Handle background type update
            $tvId = $data['tv_id'];
            $isStaticBackground = $data['isStaticBackground'];
            $isImageBackground = $data['isImageBackground'];

            // Update the background type settings
            $stmt = $this->pdo->prepare("UPDATE background_tv_tb SET isStaticBackground = ?, isImageBackground = ? WHERE tv_id = ?");
            $success = $stmt->execute([$isStaticBackground, $isImageBackground, $tvId]);

            if ($success) {
                $response = [
                    'action' => 'update_background_type',
                    'success' => true,
                    'tv_id' => $tvId,
                    'isStaticBackground' => $isStaticBackground,
                    'isImageBackground' => $isImageBackground
                ];
                error_log("Background type updated successfully!");
            } else {
                $response = [
                    'action' => 'update_background_type',
                    'success' => false,
                    'message' => 'Failed to update background type'
                ];
                error_log("Failed to update background type!");
            }

            // Notify all clients about the update
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_topbar_position') {
            $tvId = $data['tv_id'];
            $topbarPosition = $data['topbar_position'];

            $stmt = $this->pdo->prepare("UPDATE topbar_tv_tb SET topbar_position = ? WHERE tv_id = ?");
            $success = $stmt->execute([$topbarPosition, $tvId]);

            if ($success) {
                $response = [
                    'action' => 'update_topbar_position',
                    'success' => true,
                    'tv_id' => $tvId,
                    'topbar_position' => $topbarPosition
                ];
            } else {
                $response = [
                    'action' => 'update_topbar_position',
                    'success' => false,
                    'message' => 'Failed to update topbar position'
                ];
            }

            // Notify all clients about the update
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_background_image') {
            $tvId = $data['tv_id'];
            $imageData = $data['image']; // Base64 encoded image data
            
            // Create directory if it doesn't exist
            $uploadDir = "tv_backgrounds/$tvId";
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create directory: $uploadDir");
                    $response = [
                        'action' => 'update_background_image',
                        'success' => false,
                        'message' => 'Failed to create directory'
                    ];
                    $from->send(json_encode($response));
                    return;
                }
            }
        
            // First, check for and delete existing image
            $stmt = $this->pdo->prepare("SELECT background_image_path FROM background_tv_tb WHERE tv_id = ?");
            $stmt->execute([$tvId]);
            $existingImage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingImage && $existingImage['background_image_path']) {
                $existingFilePath = "tv_backgrounds/$tvId/" . $existingImage['background_image_path'];
                if (file_exists($existingFilePath)) {
                    unlink($existingFilePath);
                    error_log("Deleted existing image: $existingFilePath");
                }
            }
        
            // Generate filename with current timestamp
            $timestamp = date('Y-m-d_H-i-s');
            
            // Extract image data and type from base64 string
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageType = $matches[1];
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
                
                // Generate filename and paths
                $filename = "{$tvId}_{$timestamp}.{$imageType}";
                $fullFilePath = "$uploadDir/$filename";
                $relativeFilePath = "tv_backgrounds/$tvId/$filename"; // For database storage
                
                error_log("Attempting to save file to: $fullFilePath");
                
                if (file_put_contents($fullFilePath, $imageData)) {
                    // Update database with new image path (using relative path)
                    $stmt = $this->pdo->prepare("UPDATE background_tv_tb SET background_image_path = ? WHERE tv_id = ?");
                    $success = $stmt->execute([$filename, $tvId]);
                    
                    if ($success) {
                        $response = [
                            'action' => 'update_background_image',
                            'success' => true,
                            'tv_id' => $tvId,
                            'background_image_path' => $relativeFilePath
                        ];
                        error_log("Background image updated successfully! Path: $relativeFilePath");
                    } else {
                        error_log("Database update failed: " . print_r($stmt->errorInfo(), true));
                        $response = [
                            'action' => 'update_background_image',
                            'success' => false,
                            'message' => 'Failed to update database'
                        ];
                    }
                } else {
                    error_log("Failed to write file to: $fullFilePath");
                    $response = [
                        'action' => 'update_background_image',
                        'success' => false,
                        'message' => 'Failed to save image file'
                    ];
                }
            } else {
                error_log("Invalid image data format received");
                $response = [
                    'action' => 'update_background_image',
                    'success' => false,
                    'message' => 'Invalid image data'
                ];
            }
        
            // Notify all clients about the update
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_department_logo') {
            $tvId = $data['tv_id'];
            $imageData = $data['logo']; // Base64 encoded image data
            
            // Create directory if it doesn't exist
            $uploadDir = "tv_department_logos/$tvId";
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create directory: $uploadDir");
                    $response = [
                        'action' => 'update_department_logo',
                        'success' => false,
                        'message' => 'Failed to create directory'
                    ];
                    $from->send(json_encode($response));
                    return;
                }
            }
        
            // First, check for and delete existing image
            $stmt = $this->pdo->prepare("SELECT department_logo_path FROM topbar_tv_tb WHERE tv_id = ?");
            $stmt->execute([$tvId]);
            $existingImage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingImage && $existingImage['department_logo_path']) {
                $existingFilePath = "tv_department_logos/$tvId/" . $existingImage['department_logo_path'];
                if (file_exists($existingFilePath)) {
                    unlink($existingFilePath);
                    error_log("Deleted existing image: $existingFilePath");
                }
            }
        
            // Generate filename with current timestamp
            $timestamp = date('Y-m-d_H-i-s');
            
            // Extract image data and type from base64 string
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageType = $matches[1];
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
                
                // Generate filename and paths
                $filename = "{$tvId}_{$timestamp}.{$imageType}";
                $fullFilePath = "$uploadDir/$filename";
                $relativeFilePath = "tv_department_logos/$tvId/$filename"; // For database storage
                
                error_log("Attempting to save file to: $fullFilePath");
                
                if (file_put_contents($fullFilePath, $imageData)) {
                    // Update database with new image path (using relative path)
                    $stmt = $this->pdo->prepare("UPDATE topbar_tv_tb SET department_logo_path = ? WHERE tv_id = ?");
                    $success = $stmt->execute([$filename, $tvId]);
                    
                    if ($success) {
                        $response = [
                            'action' => 'update_department_logo',
                            'success' => true,
                            'tv_id' => $tvId,
                            'department_logo_path' => $relativeFilePath
                        ];
                        error_log("Department logo updated successfully! Path: $relativeFilePath");
                    } else {
                        error_log("Database update failed: " . print_r($stmt->errorInfo(), true));
                        $response = [
                            'action' => 'update_department_logo',
                            'success' => false,
                            'message' => 'Failed to update database'
                        ];
                    }
                } else {
                    error_log("Failed to write file to: $fullFilePath");
                    $response = [
                        'action' => 'update_department_logo',
                        'success' => false,
                        'message' => 'Failed to save image file'
                    ];
                }
            } else {
                error_log("Invalid image data format received");
                $response = [
                    'action' => 'update_department_logo',
                    'success' => false,
                    'message' => 'Invalid image data'
                ];
            }
        
            // Notify all clients about the update
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_background_color') {        
            // Define the table and fields to update
            $table = 'background_tv_tb';
            $idField = 'tv_id';
            $fields = ['background_hex_color'];
            
            // Extract the tv_id and background_color details from the incoming data
            $tvId = $data['tv_id'] ?? null;
            $backgroundColor = $data['background_hex_color'] ?? null; // Ensure it defaults to null if not set
            
            if ($tvId !== null && $backgroundColor !== null) {
                // Prepare the parameters for the update statement
                $params = [$backgroundColor, $tvId];
                $fieldAssignments = implode(', ', array_map(fn($field) => "$field = ?", $fields));
                $stmt = $this->pdo->prepare("UPDATE {$table} SET {$fieldAssignments} WHERE {$idField} = ?");
                
                if (!$stmt->execute($params)) {
                    error_log("Failed to execute update query: " . implode(", ", $stmt->errorInfo()));
                    $from->send(json_encode(['action' => 'update_background_color', 'success' => false, 'message' => 'Failed to execute update query']));
                    return;
                }
        
                $success = $stmt->rowCount() > 0;
                $response = [
                    'action' => 'update_background_color',
                    'success' => $success,
                    $idField => $tvId,
                    'background_hex_color' => $backgroundColor 
                ];
                echo $success ? "Background color updated {$backgroundColor}!\n" : "Background color not updated!\n";
                
                // Notify other clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            } else {
                $response = ['action' => 'update_background_color', 'success' => false, 'message' => 'Invalid input'];
                echo "Invalid input for background color\n";
            }
            
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'update_topbar_color') {       
            // Define the table and fields to update
            $table = 'topbar_tv_tb';
            $idField = 'tv_id';
            $fields = ['topbar_hex_color',
                       'topbar_tvname_font_color',
                       'topbar_tvname_font_style',
                       'topbar_tvname_font_family',
                       'topbar_tvid_font_color',
                       'topbar_tvid_font_style',
                       'topbar_tvid_font_family',
                       'topbar_time_font_color',
                       'topbar_time_font_style',
                       'topbar_time_font_family',
                       'topbar_date_font_color',
                       'topbar_date_font_style',
                       'topbar_date_font_family'
                    ];
            
            // Extract the tv_id and topbar_color details from the incoming data
            $tvId = $data['tv_id'] ?? null;
            $topbarColor = $data['topbar_hex_color'] ?? null;

            $topbarTvNameColor = $data['topbar_tvname_font_color'] ?? null;
            $topbarTvNameFontStyle = $data['topbar_tvname_font_style'] ?? null;
            $topbarTvNameFontFamily = $data['topbar_tvname_font_family'] ?? null;

            $topbarTvIdColor = $data['topbar_tvid_font_color'] ?? null;
            $topbarTvIdFontStyle = $data['topbar_tvid_font_style'] ?? null;
            $topbarTvIdFontFamily = $data['topbar_tvid_font_family'] ?? null;

            $topbarTimeColor = $data['topbar_time_font_color'] ?? null;
            $topbarTimeFontStyle = $data['topbar_time_font_style'] ?? null;
            $topbarTimeFontFamily = $data['topbar_time_font_family'] ?? null;

            $topbarDateColor = $data['topbar_date_font_color'] ?? null;
            $topbarDateFontStyle = $data['topbar_date_font_style'] ?? null;
            $topbarDateFontFamily = $data['topbar_date_font_family'] ?? null;
            
            if ($tvId !== null || $topbarColor !== null || $topbarTvNameColor !== null || $topbarTvIdColor !== null || $topbarTimeColor !== null || $topbarDateColor !== null) {
                // Prepare the parameters for the update statement
                $params = [$topbarColor, 
                           $topbarTvNameColor, 
                           $topbarTvNameFontStyle, 
                           $topbarTvNameFontFamily,
                           $topbarTvIdColor, 
                           $topbarTvIdFontStyle,
                           $topbarTvIdFontFamily,
                           $topbarTimeColor, 
                           $topbarTimeFontStyle,
                           $topbarTimeFontFamily,
                           $topbarDateColor, 
                           $topbarDateFontStyle,
                           $topbarDateFontFamily,
                           $tvId];
                $fieldAssignments = implode(', ', array_map(fn($field) => "$field = ?", $fields));
                $stmt = $this->pdo->prepare("UPDATE {$table} SET {$fieldAssignments} WHERE {$idField} = ?");
                
                if (!$stmt->execute($params)) {
                    error_log("Failed to execute update query: " . implode(", ", $stmt->errorInfo()));
                    $from->send(json_encode(['action' => 'update_topbar_color', 'success' => false, 'message' => 'Failed to execute update query']));
                    return;
                }
        
                $success = $stmt->rowCount() > 0;
                if ($success) {
                    $response = [
                        'action' => 'update_topbar_color',
                        'success' => true,
                        'tv_id' => $tvId,
                        'topbar_hex_color' => $topbarColor,
                        'topbar_tvname_font_color' => $topbarTvNameColor,
                        'topbar_tvname_font_style' => $topbarTvNameFontStyle,
                        'topbar_tvname_font_family' => $topbarTvNameFontFamily,
                        'topbar_tvid_font_color' => $topbarTvIdColor,
                        'topbar_tvid_font_style' => $topbarTvIdFontStyle,
                        'topbar_tvid_font_family' => $topbarTvIdFontFamily,
                        'topbar_time_font_color' => $topbarTimeColor,
                        'topbar_time_font_style' => $topbarTimeFontStyle,
                        'topbar_time_font_family' => $topbarTimeFontFamily,
                        'topbar_date_font_color' => $topbarDateColor,
                        'topbar_date_font_style' => $topbarDateFontStyle,
                        'topbar_date_font_family' => $topbarDateFontFamily
                    ];
                } else {
                    $response = [
                        'action' => 'update_topbar_color',
                        'success' => false,
                        'message' => 'Failed to update topbar color',
                    ];
                }
                
                // Notify other clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            } else {
                $response = ['action' => 'update_topbar_color', 'success' => false, 'message' => 'Invalid input'];
                echo "Invalid input for topbar color\n";
            }
            
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'update_container_colors') {
            $table = 'containers_tb';
            $idField = 'container_id';
            
            $tvId = $data['tv_id'] ?? null;
            $containers = $data['containers'] ?? [];
            
            if ($tvId !== null && !empty($containers)) {
                $stmtUpdateColors = $this->pdo->prepare("UPDATE {$table} SET parent_background_color = ?, parent_font_color = ?, parent_font_style = ?, parent_font_family = ?, child_background_color = ?, child_font_color = ?, child_font_style = ?, child_font_family = ? WHERE {$idField} = ? AND tv_id = ?");
                
                foreach ($containers as $containerId => $colors) {
                    $bgColor = $colors['bg_color'] ?? null;
                    $fontColor = $colors['font_color'] ?? null;
                    $fontStyle = $colors['fontstyle'] ?? null;
                    $fontFamily = $colors['fontfamily'] ?? null;
                    $cardBgColor = $colors['card_bg_color'] ?? null;
                    $cardFontColor = $colors['fcard_color'] ?? null;
                    $cardFontStyle = $colors['fcardstyle'] ?? null;
                    $cardFontFamily = $colors['fcardfamily'] ?? null;
            
                    if ($bgColor !== null && $fontColor !== null && $cardBgColor !== null) {
                        $params = [$bgColor, $fontColor, $fontStyle, $fontFamily, $cardBgColor, $cardFontColor, $cardFontStyle, $cardFontFamily,$containerId, $tvId];
                        if (!$stmtUpdateColors->execute($params)) {
                            error_log("Failed to execute update query for container_id {$containerId}: " . implode(", ", $stmtUpdateColors->errorInfo()));
                            $from->send(json_encode(['action' => 'update_container_colors', 'success' => false, 'message' => "Failed to update container_id {$containerId}"]));
                            return;
                        }
                    } else {
                        error_log("Missing colors for container_id {$containerId}");  // Log missing colors
                    }
                }
                
                // Fetch the updated container colors to send back to the client
                $stmtFetchColors = $this->pdo->prepare("SELECT container_id, type, parent_background_color, parent_font_color, parent_font_style, parent_font_family, child_background_color, child_font_color, child_font_style, child_font_family FROM {$table} WHERE tv_id = ?");
                $stmtFetchColors->execute([$tvId]);
                $updatedContainers = $stmtFetchColors->fetchAll(PDO::FETCH_ASSOC);
        
                $response = [
                    'action' => 'update_container_colors',
                    'success' => true,
                    'tv_id' => $tvId,
                    'containers' => $updatedContainers
                ];
        
                foreach ($this->clients as $client) {
                    $client->send(json_encode($response));
                }
            } else {
                $response = ['action' => 'update_container_colors', 'success' => false, 'message' => 'Invalid input'];
            }
            
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'update_container_positions') {
            $tvId = $data['tv_id'] ?? null;
            $positions = $data['positions'] ?? [];
        
            if ($tvId !== null && !empty($positions)) {
                $stmt = $this->pdo->prepare("UPDATE containers_tb SET xaxis = ?, yaxis = ?, width_px = ?, height_px = ? WHERE tv_id = ? AND container_id = ? AND visible = 1");
        
                foreach ($positions as $position) {
                    $params = [
                        $position['x'],
                        $position['y'],
                        $position['width'],
                        $position['height'],
                        $tvId,
                        $position['id']
                    ];
        
                    if (!$stmt->execute($params)) {
                        error_log("Failed to update position for container_id {$position['id']}: " . implode(", ", $stmt->errorInfo()));
                    }
                }
        
                $response = [
                    'action' => 'update_container_positions',
                    'success' => true,
                    'tv_id' => $tvId
                ];
        
                // Notify all clients about the update
                foreach ($this->clients as $client) {
                    $client->send(json_encode($response));
                }
        
                echo "Container positions updated for TV ID: $tvId\n";
            } else {
                $response = [
                    'action' => 'update_container_positions',
                    'success' => false,
                    'message' => 'Invalid input'
                ];
                $from->send(json_encode($response));
                echo "Invalid input for updating container positions\n";
            }
        }

        else if (isset($data['action']) && $data['action'] === 'show_hide_content') {
            // Define the table and fields to update
            $table = 'containers_tb';
            $idField = 'container_id';
        
            // Extract the tv_id and containers data from the incoming data
            $tvId = $data['tv_id'] ?? null;
            $containers = $data['containers'] ?? [];
        
            if ($tvId !== null && !empty($containers)) {
                // Prepare the update statement
                $stmt = $this->pdo->prepare("UPDATE {$table} SET visible = ? WHERE {$idField} = ? AND tv_id = ?");
        
                // Iterate over each container and update its visibility
                foreach ($containers as $containerId => $visible) {
                    $params = [$visible, $containerId, $tvId];
        
                    if (!$stmt->execute($params)) {
                        error_log("Failed to execute update query for container_id {$containerId}: " . implode(", ", $stmt->errorInfo()));
                        $from->send(json_encode(['action' => 'show_hide_content', 'success' => false, 'message' => "Failed to update container_id {$containerId}"]));
                        return;
                    }
                }
        
                $response = ['action' => 'show_hide_content', 'success' => true, 'tv_id' => $tvId];
                echo "Visibility for containers updated!\n";
        
                // Notify other clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($response));
                    }
                }
            } else {
                $response = ['action' => 'show_hide_content', 'success' => false, 'message' => 'Invalid input'];
                echo "Invalid input for Hiding/Showing Content container\n";
            }
        
            $from->send(json_encode($response));
        }         

        // User Registration
        else if (isset($data['otp_code']) && isset($data['session_data'])) {
            $otp = $data['session_data']['otp'];
            $otp_code = $data['otp_code'];

            if ($otp != $otp_code) {
                $response = ["success" => false, "message" => "Invalid OTP Code. Try Again."];
                $from->send(json_encode($response));
                return;
            } else {
                // Retrieve user details from the message data
                $userDetails = $data['session_data']['registration_details'];
                $full_name = $userDetails['full_name'];
                $email = $userDetails['email'];
                $password = $userDetails['password'];
                $department = $userDetails['department'];
                $user_type = $userDetails['user_type'];
                $datetime_registered = $userDetails['datetime_registered'];

                // Set status based on user_type
                $status = ($user_type == 'Student' || $user_type == 'TBD') ? 'Pending' : 'Approved';

                try {
                    // Hash the password using md5
                    $hashedPassword = md5($password);

                    // If no file uploaded, store input to announcements_tb without media_path
                    $statement = $this->pdo->prepare(
                        "INSERT INTO users_tb 
                        (full_name, email, password, department, user_type, status, datetime_registered) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $success = $statement->execute([
                        $full_name, $email, $hashedPassword, $department, $user_type, $status, $datetime_registered
                    ]);

                    if ($success) {
                        // Create a folder for the user
                        $userFolder = "uploads/" . $full_name;
                        if (!file_exists($userFolder)) {
                            mkdir($userFolder, 0777, true);
                        }

                        $user_id = $this->pdo->lastInsertId();

                        // Prepare the complete data to send to the clients
                        $data['user_id'] = $user_id;
                        $data['full_name'] = $full_name;
                        $data['email'] = $email;
                        $data['department'] = $department;
                        $data['status'] = $status;
                        $data['user_type'] = $user_type;
                        $data['datetime_registered'] = $datetime_registered;

                        // Insert notification for new user registration
                        $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, action_user_email, action_user_name, action_user_type, action_user_department) VALUES (?, 'user_registration', 'pending', ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $email, $full_name, $user_type, $department]);

                        // Increase the notification count of the Super Admin and notify them that a student or faculty posted new content
                        $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin'");
                        $stmtUpdateSuperAdminNotificationCount->execute();

                        // Increase the notification count of the Admin and notify them that a student or faculty posted new content
                        $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ?");
                        $stmtUpdateAdminNotificationCount->execute([$department]);

                        // Increase the notification count of the student or faculty
                        // $stmtUpdateUserWhoRegisteredNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                        // $stmtUpdateUserWhoRegisteredNotificationCount->execute([$user_id]);
                
                        // Broadcast new notification to all clients
                        $this->broadcastNotification('new_notification');

                        // Send success message to client
                        $response = ['success' => true, 
                                     'data' => $data, 
                                     'user_type' => $user_type];
                        echo "A user account has been created! \n";
                    } else {
                        $response = ["success" => false, "message" => "Failed to insert data. Please try again."];
                    }
                } catch (\Exception $e) {
                    $response = ["success" => false, "message" => "Failed to insert data. Please try again. Error: " . $e->getMessage()];
                }

                $from->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'update_user_type') {
            $user_type = $data['user_type'];
            $email = $data['email'];
    
            try {
                $stmt = $this->pdo->prepare("UPDATE users_tb SET user_type = ? WHERE email = ?");
                $success = $stmt->execute([$user_type, $email]);

                $this->broadcastNotification('new_notification');
    
                if ($success) {
                    $response = ['success' => true, 'message' => 'User type updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update user type'];
                }
            } catch (\Exception $e) {
                $response = ['success' => false, 'message' => 'Error updating user type: ' . $e->getMessage()];
            }
    
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'approve_user') {
            $user_id = $data['user_id'];
            $email = $data['email'];
            $full_name = $data['full_name'];
            $user_type = $data['user_type'];
            $department = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];
        
            // Perform the deletion from the database
            $stmtUpdateUserStatus = $this->pdo->prepare("UPDATE users_tb SET status = 'Approved', evaluated_by = ? WHERE user_id = ?");
            $stmtUpdateUserStatus->execute([$evaluator_name, $user_id]);
            
            $approve_user = $stmtUpdateUserStatus->rowCount() > 0;

            if ($approve_user) {
                // Update notification status
                $stmtUpdateEvaluatorNotification = $this->pdo->prepare("UPDATE notifications_tb SET user_id = ?, status = 'approved', evaluator_name = ?, action_user_email = ?, action_user_name = ?, action_user_type = ?, action_user_department = ?, notification_type = 'user_approved', created_at = NOW() WHERE user_id = ? AND notification_type = 'user_registration'");
                $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $email, $full_name, $user_type, $department, $user_id]);

                // Notify the Admin or Super Admin who approved the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'user_approved', 'approved', $evaluator_name, $email, $full_name, $user_type, $department]);
                    }
                }
                
                // Notify all Super Admins except the one who approved the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'user_approved', 'approved', $evaluator_name, $email, $full_name, $user_type, $department]);
                    }
                }

                // Notify the new user that an admin approved them
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department) 
                                             VALUES (?, 'user_approved_by_admin', 'approved', ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $evaluator_name, $email, $full_name, $user_type, $department]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                // Broadcast update notification to all clients
                $this->broadcastNotification('update_notification');

                $response = [
                    'action' => 'approve_user', 
                    'success' => true, 
                    'user_id' => $user_id,
                    'email' => $email
                ];
                echo "User approved and evaluated by: {$evaluator_name}\n";
            } else {
                $response = ['action' => 'approve_user', 'success' => false];
            }

            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'reject_user') {
            $user_id = $data['user_id'];
            $email = $data['email'];
            $full_name = $data['full_name'];
            $user_type = $data['user_type'];
            $department = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];
            $evaluator_message = isset($data['evaluator_message']) ? htmlspecialchars($data['evaluator_message']) : '';
        
            // Perform the deletion from the database
            $stmt = $this->pdo->prepare("UPDATE users_tb SET status = 'Rejected', evaluated_by = ?, evaluator_message = ? WHERE user_id = ?");
            $stmt->execute([$evaluator_name, $evaluator_message, $user_id]);
            
            $reject_user = $stmt->rowCount() > 0;

            if ($reject_user) {
                // Update notification status
                $stmtUpdateEvaluatorNotification = $this->pdo->prepare("UPDATE notifications_tb SET user_id = ?, status = 'rejected', evaluator_name = ?, action_user_email = ?, action_user_name = ?, action_user_type = ?, action_user_department = ?, evaluator_message = ?, notification_type = 'user_rejected', created_at = NOW() WHERE user_id = ? AND notification_type = 'user_registration'");
                $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $email, $full_name, $user_type, $department, $evaluator_message, $user_id]);

                // Notify the Admin or Super Admin who rejected the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the rejected user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department, evaluator_message)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'user_rejected', 'rejected', $evaluator_name, $email, $full_name, $user_type, $department, $evaluator_message]);
                    }
                }
                
                // Notify all Super Admins except the one who rejected the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department, evaluator_message)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'user_rejected', 'rejected', $evaluator_name, $email, $full_name, $user_type, $department, $evaluator_message]);
                    }
                }

                // Notify the new user that an admin rejected them
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_email, action_user_name, action_user_type, action_user_department, evaluator_message) 
                                             VALUES (?, 'user_rejected_by_admin', 'rejected', ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $evaluator_name, $email, $full_name, $user_type, $department, $evaluator_message]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                // Broadcast update notification to all clients
                $this->broadcastNotification('update_notification');

                $response = ['action' => 'reject_user', 'success' => true, 'user_id' => $user_id];
                echo "User rejected and evaluated by: {$evaluator_name}\n";
            } else {
                $response = ['action' => 'reject_user', 'success' => false];
            }

            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'edit_user') {
            $userId = $data['user_id'];
            $email = $data['email'];
            $full_name = $data['full_name'];
            $newUserType = $data['user_type'];
            $newDepartment = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];

            // Fetch the current user data
            $stmtFetchUser = $this->pdo->prepare("SELECT user_type, department FROM users_tb WHERE user_id = ?");
            $stmtFetchUser->execute([$userId]);
            $currentUser = $stmtFetchUser->fetch(PDO::FETCH_ASSOC);

            $changedFields = [];
            if ($currentUser['user_type'] !== $newUserType) {
                $changedFields[] = 'user_type';
            }
            if ($currentUser['department'] !== $newDepartment) {
                $changedFields[] = 'department';
            }

            // Perform the update in the database
            $stmt = $this->pdo->prepare("UPDATE users_tb SET user_type = ?, department = ? WHERE user_id = ?");
            $stmt->execute([$newUserType, $newDepartment, $userId]);

            $user = $stmt->rowCount() > 0;
        
            if ($user && !empty($changedFields)) {
                // Prepare the notification message and data
                $notificationFields = [];
                $notificationValues = [];
                if (in_array('user_type', $changedFields)) {
                    $notificationFields[] = 'action_user_type';
                    $notificationValues[] = $newUserType;
                }
                if (in_array('department', $changedFields)) {
                    $notificationFields[] = 'action_user_department';
                    $notificationValues[] = $newDepartment;
                }

                $fieldString = implode(', ', $notificationFields);
                $placeholders = implode(', ', array_fill(0, count($notificationFields), '?'));

                // Notify the Admin or Super Admin who edited the user
                $stmtInsertAdminOrSuperAdminNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, {$fieldString}, action_user_name) VALUES (?, 'user_edited', 'edited', ?, {$placeholders}, ?)");
                $stmtInsertAdminOrSuperAdminNotificationTable->execute(array_merge([$evaluator_id, $evaluator_name], $notificationValues, [$full_name]));

                // Notify the student or faculty that their account has been edited
                $stmtInsertStudentOrFacultyNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, {$fieldString}, action_user_name) VALUES (?, 'user_edited_by_admin', 'edited', ?, {$placeholders}, ?)");
                $stmtInsertStudentOrFacultyNotificationTable->execute(array_merge([$userId, $evaluator_name], $notificationValues, [$full_name]));

                // Increase the notification count of the Admin or Super Admin
                $stmtUpdateAdminOrSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE full_name = ?");
                $stmtUpdateAdminOrSuperAdminNotificationCount->execute([$evaluator_name]);

                // Increase the notification count of the edited user (only once)
                $stmtUpdateStudentOrFacultyNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_id = ?");
                $stmtUpdateStudentOrFacultyNotificationCount->execute([$userId]);

                // Prepare the response for the edited user
                $editedUserResponse = [
                    'action' => 'user_edited',
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'Your account has been updated. You will be logged out now.'
                ];

                // Find the connection for the edited user
                $editedUserConnection = null;
                foreach ($this->clients as $client) {
                    if ($client->user_id == $userId) {
                        $editedUserConnection = $client;
                        break;
                    }
                }

                // Send the response to the edited user if they are connected
                if ($editedUserConnection) {
                    $editedUserConnection->send(json_encode($editedUserResponse));
                }

                // Prepare the general response for other clients
                $generalResponse = [
                    'action' => 'edit_user',
                    'success' => true,
                    'user_id' => $userId,
                    'user_type' => $newUserType,
                    'department' => $newDepartment,
                    'data' => $data
                ];

                // Send the general response to all other clients
                foreach ($this->clients as $client) {
                    if ($client !== $editedUserConnection) {
                        $client->send(json_encode($generalResponse));
                    }
                }

                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');
                echo "User details updated for user_id: {$userId}\n";
            } else {
                $from->send(json_encode(['action' => 'edit_user', 'success' => false, 'message' => 'Failed to update user details. Please try again.']));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'delete_user') {
            $user_id = $data['user_id'];
            $full_name = $data['full_name'];
            $user_type = $data['user_type'];
            $department = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];
            $evaluator_message = isset($data['evaluator_message']) ? htmlspecialchars($data['evaluator_message']) : '';

            // Fetch user details before deletion
            $stmtFetchUser = $this->pdo->prepare("SELECT full_name, email, department FROM users_tb WHERE user_id = ?");
            $stmtFetchUser->execute([$user_id]);
            $userDetails = $stmtFetchUser->fetch(PDO::FETCH_ASSOC);
        
            // Perform the deletion in the database
            $stmt = $this->pdo->prepare("DELETE FROM users_tb WHERE user_id = ?");
            $stmt->execute([$user_id]);
        
            $user = $stmt->rowCount() > 0;
        
            if ($user) {
                // Notify the Admin or Super Admin who deleted the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                if ($evaluatorId) {
                    $stmtUpdateEvaluatorNotification = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_name, action_user_type, action_user_department, evaluator_message) VALUES (?, 'user_deleted', 'deleted', ?, ?, ?, ?, ?)");
                    $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $full_name, $user_type, $department, $evaluator_message]);
                }

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the deleted user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_name, action_user_type, action_user_department, evaluator_message)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'user_deleted', 'deleted', $evaluator_name, $full_name, $user_type, $department, $evaluator_message]);
                    }
                }
                
                // Notify all Super Admins except the one who deleted the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_name, action_user_type, action_user_department, evaluator_message)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'user_deleted', 'deleted', $evaluator_name, $full_name, $user_type, $department, $evaluator_message]);
                    }
                }

                // Notify the new user that an admin deleted them
                // $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_name, action_user_type, action_user_department, evaluator_message) VALUES (?, 'user_deleted_by_admin', 'deleted', ?, ?, ?, ?, ?)");
                // $stmt->execute([$user_id, $evaluator_name, $full_name, $user_type, $department, $evaluator_message]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                // Broadcast new notification to all clients
                $this->broadcastNotification('new_notification');
        
                // Prepare the response
                $response = [
                    'action' => 'user_deleted',
                    'success' => true,
                    'user_id' => $user_id,
                    'message' => 'Your account has been deleted. You will be logged out now.',
                    'email' => $userDetails['email'],
                    'full_name' => $userDetails['full_name'],
                    'department' => $userDetails['department'],
                    'evaluator_name' => $evaluator_name,
                    'evaluator_message' => $evaluator_message
                ];
        
                // Find the connection for the deleted user
                $deletedUserConnection = null;
                foreach ($this->clients as $client) {
                    if ($client->user_id == $user_id) {
                        $deletedUserConnection = $client;
                        break;
                    }
                }
        
                // Send the response to the deleted user if they are connected
                if ($deletedUserConnection) {
                    $deletedUserConnection->send(json_encode($response));
                }
        
                // Notify all other clients about the deletion, without the logout message
                $generalResponse = [
                    'action' => 'user_deleted',
                    'success' => true,
                    'user_id' => $user_id,
                    'email' => $userDetails['email'],
                    'full_name' => $userDetails['full_name'],
                    'department' => $userDetails['department'],
                    'evaluator_name' => $evaluator_name,
                    'evaluator_message' => $evaluator_message
                ];
                foreach ($this->clients as $client) {
                    if ($client !== $deletedUserConnection) {
                        $client->send(json_encode($generalResponse));
                    }
                }
        
                echo "User deleted for user_id: {$user_id}\n";
            } else {
                $from->send(json_encode(['action' => 'delete_user', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'add_user') {
            $full_name = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $department = trim($data['department'] ?? '');
            $user_type = trim($data['user_type'] ?? '');
            $status = 'Approved';
            $status_notification = 'approved';
            $evaluator_name = trim($data['evaluator_name'] ?? '');
            $evaluator_id = $data['evaluator_id'];
            $evaluator_type = $data['evaluator_type'];
            $evaluator_department = $data['evaluator_department'];

            // Check for empty fields
            if (empty($full_name) || empty($email) || empty($password) || empty($department) || empty($user_type)) {
                $response = [
                    "action" => "add_user", 
                    "success" => false, 
                    "message" => "Please fill up all fields.",
                    "messageType" => "error"
                ];
                $from->send(json_encode($response));
                return;
            }

            // Validate email
            if (!preg_match('/@usc\.edu\.ph$/', $email)) {
                $response = [
                    "action" => "add_user", 
                    "success" => false, 
                    "message" => "Invalid email. Please use a @usc.edu.ph email address.",
                    "messageType" => "error"
                ];
                $from->send(json_encode($response));
                return;
            }

            // Validate full name
            if (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
                $response = [
                    "action" => "add_user", 
                    "success" => false, 
                    "message" => "Invalid full name. Please use only letters and spaces.",
                    "messageType" => "error"
                ];
                $from->send(json_encode($response));
                return;
            }
        
            try {
                // Check if the email or full name already exists
                $stmt = $this->pdo->prepare("SELECT email, full_name FROM users_tb WHERE email = ? OR full_name = ?");
                $stmt->execute([$email, $full_name]);
                $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingUser) {
                    if ($existingUser['email'] === $email || $existingUser['full_name'] === $full_name) {
                        $message = "User already exists";
                    }

                    $response = [
                        "action" => "add_user", 
                        "success" => false, 
                        "message" => $message,
                        "messageType" => "error"
                    ];

                    error_log($message);
                    $from->send(json_encode($response));
                    return;
                }

                // If email and full name don't exist, proceed with insertion
                $hashedPassword = md5($password);
        
                $statement = $this->pdo->prepare(
                    "INSERT INTO users_tb 
                    (full_name, email, password, department, user_type, status) 
                    VALUES (?, ?, ?, ?, ?, ?)"
                );
                $success = $statement->execute([
                    $full_name, $email, $hashedPassword, $department, $user_type, $status
                ]);
        
                if ($success) {
                    $user_id = $this->pdo->lastInsertId();

                    // Notify the Admin or Super Admin who added the user
                    $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                    $stmtUserIdEvaluator->execute([$evaluator_id]);
                    $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                    if ($evaluatorId) {
                        // Notify the user who added the new user
                        $stmtNotifyEvaluator = $this->pdo->prepare("
                            INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtNotifyEvaluator->execute([$evaluator_id, 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                    }

                    $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                        SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                    ");
                    $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                    $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Notify each Admin of the added user's department
                    if ($departmentAdmins) {
                        $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                            INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($departmentAdmins as $admin) {
                            $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                        }
                    }
                    
                    // Notify all Super Admins except the one who added the user
                    $stmtSelectSuperAdmins = $this->pdo->prepare("
                        SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                    ");
                    $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                    $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                    // Notify all Super Admins
                    if ($allSuperAdmins) {
                        error_log("allSuperAdmins called");
                        $stmtNotifySuperAdmins = $this->pdo->prepare("
                            INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        // $stmtNotifySuperAdmins->execute([$user_id, 'user_added', 'approved', $evaluator_name, $user_type, $department]);
                        foreach ($allSuperAdmins as $superAdmin) {
                            $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                        }
                    }

                    // Notify the new user that an admin added them
                    $stmtNotifyUser = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtNotifyUser->execute([$user_id, 'user_added_by_admin', 'approved', $evaluator_name, $user_type, $department, $full_name]);

                    // Update notification counts for Admins, Super Admins, and the evaluator
                    $stmtUpdateNotificationCounts = $this->pdo->prepare("
                        UPDATE users_tb 
                        SET notification_count = notification_count + 1 
                        WHERE (user_type = 'Admin' AND department = ?) 
                        OR user_type = 'Super Admin'
                        OR user_id = ? OR user_id = ?
                    ");
                    $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                    // Broadcast new notification to all clients    
                    $this->broadcastNotification('new_notification');
        
                    // Prepare the complete data to send to the clients
                    $responseData = [
                        'user_id' => $user_id,
                        'full_name' => $full_name,
                        'email' => $email,
                        'department' => $department,
                        'status' => $status,
                        'user_type' => $user_type,
                        'evaluator_name' => $evaluator_name
                    ];

                    // Send success message to client
                    $response = [
                        'action' => 'add_user', 
                        'success' => true, 
                        'data' => $responseData, 
                        'email' => $email,
                        'message' => 'User added successfully',
                        'messageType' => 'success',
                        'evaluator_name' => $evaluator_name,
                        'evaluator_id' => $evaluator_id
                    ];
                    $from->send(json_encode($response));
        
                    // Notify all other clients about the new user
                    foreach ($this->clients as $client) {
                        if ($client !== $from) {
                            $client->send(json_encode($response));
                        }
                    }
        
                    echo "A new user account has been created!\n";
        
                    // TODO: Send email to the new user with their temporary password
                    // You should implement an email sending function here
        
                } else {
                    $response = [
                        "action" => "add_user", 
                        "success" => false, 
                        "message" => "Failed to insert data. Please try again.",
                        "messageType" => "error"
                    ];
                    $from->send(json_encode($response));
                }
            } catch (\Exception $e) {
                $response = [
                    "action" => "add_user", 
                    "success" => false, 
                    "message" => "Failed to insert data. Please try again. Error: " . $e->getMessage(),
                    "messageType" => "error"
                ];
                $from->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'add_multiple_users') {
            $evaluator_name = trim($data['evaluator_name'] ?? '');
            $evaluator_id = $data['evaluator_id'] ?? '';
            $evaluator_type = $data['evaluator_type'] ?? '';
            $evaluator_department = $data['evaluator_department'] ?? '';

            if (isset($data['csv_file']) && is_string($data['csv_file'])) {
                $csvContent = base64_decode($data['csv_file']);
            } else {
                // Handle the case where csv_file is not a string
                $from->send(json_encode(['action' => 'add_multiple_users', 'success' => false, 'message' => 'Invalid CSV file format. Try Again']));
                return;
            }
            $lines = explode("\n", $csvContent);
            $addedCount = 0;
            $failedCount = 0;
            $errorMessages = [];
            $addedUsers = [];

            // Check if the CSV file is empty
            if (count($lines) <= 1) { // Assuming the first line might be headers
                $from->send(json_encode(['action' => 'add_multiple_users', 'success' => false, 'message' => 'The CSV file is empty or contains only headers.']));
                return;
            }
        
            foreach ($lines as $lineNumber => $line) {
                $userData = str_getcsv($line);
                
                // Skip empty lines
                if (empty(array_filter($userData))) {
                    continue;
                }
        
                // Check for the correct number of fields
                if (count($userData) !== 4) {
                    $errorMessages[] = "Line $lineNumber: Incorrect number of fields. Expected 4, got " . count($userData);
                    $failedCount++;
                    continue;
                }
        
                $full_name = trim($userData[0]);
                $email = trim($userData[1]);
                $user_type = trim($userData[2]);
                $department = trim($userData[3]);
        
                // Check for empty fields
                if (empty($full_name) || empty($email) || empty($user_type) || empty($department)) {
                    $errorMessages[] = "Line $lineNumber: One or more required fields are empty.";
                    $failedCount++;
                    continue;
                }
        
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@usc\.edu\.ph$/', $email)) {
                    $errorMessages[] = "Line $lineNumber: Invalid email format for $email. Must be a valid @usc.edu.ph address.";
                    $failedCount++;
                    continue;
                }
        
                // Validate user type
                $validUserTypes = ['Super Admin', 'Admin', 'Faculty', 'Student', 'Staff'];
                if (!in_array($user_type, $validUserTypes)) {
                    $errorMessages[] = "Line $lineNumber: Invalid user type '$user_type'. Must be one of: " . implode(', ', $validUserTypes);
                    $failedCount++;
                    continue;
                }
        
                // Validate department
                $validDepartments = ['COMPUTER ENGINEERING', 'CHEMICAL ENGINEERING', 'CIVIL ENGINEERING', 'INDUSTRIAL ENGINEERING', 'ELECTRICAL ENGINEERING', 'MECHANICAL ENGINEERING', 'ELECTRONICS ENGINEERING'];
                if (!in_array(strtoupper($department), $validDepartments)) {
                    $errorMessages[] = "Line $lineNumber: Invalid department '$department'. Must be one of: " . implode(', ', $validDepartments);
                    $failedCount++;
                    continue;
                }
        
                $status = 'Approved';
                $password = bin2hex(random_bytes(4));  // Generates an 8-character random password
                $hashedPassword = md5($password); 
        
                try {
                    // Check if the email or full name already exists
                    $stmt = $this->pdo->prepare("SELECT email, full_name FROM users_tb WHERE email = ? OR full_name = ?");
                    $stmt->execute([$email, $full_name]);
                    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($existingUser) {
                        $failedCount++;
                        if ($existingUser['email'] === $email) {
                            $errorMessages[] = "Line $lineNumber: Email already exists: $email";
                        }
                        if ($existingUser['full_name'] === $full_name) {
                            $errorMessages[] = "Line $lineNumber: Full name already exists: $full_name";
                        }
                        continue;
                    }
        
                    $statement = $this->pdo->prepare(
                        "INSERT INTO users_tb 
                        (full_name, email, password, department, user_type, status) 
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $success = $statement->execute([
                        $full_name, $email, $hashedPassword, $department, $user_type, $status
                    ]);
        
                    if ($success) {
                        $user_id = $this->pdo->lastInsertId();
                        $addedCount++;

                        // Notify the Admin or Super Admin who added the user
                        $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                        $stmtUserIdEvaluator->execute([$evaluator_id]);
                        $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                        if ($evaluatorId) {
                            // Notify the user who added the new user
                            $stmtNotifyEvaluator = $this->pdo->prepare("
                                INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmtNotifyEvaluator->execute([$evaluator_id, 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                        }

                        // Notify each Admin of the department
                        $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                            SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                        ");
                        $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                        $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($departmentAdmins) {
                            $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                                INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            foreach ($departmentAdmins as $admin) {
                                $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                            }
                        }
                        
                        // Notify all Super Admins except the one who added the user
                        $stmtSelectSuperAdmins = $this->pdo->prepare("
                            SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                        ");
                        $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                        $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                        if ($allSuperAdmins) {
                            $stmtNotifySuperAdmins = $this->pdo->prepare("
                                INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            foreach ($allSuperAdmins as $superAdmin) {
                                $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'user_added', 'approved', $evaluator_name, $user_type, $department, $full_name]);
                            }
                        }

                        // Notify the new user that they were added
                        $stmtNotifyUser = $this->pdo->prepare("
                            INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name, action_user_type, action_user_department, action_user_name)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtNotifyUser->execute([$user_id, 'user_added_by_admin', 'approved', $evaluator_name, $user_type, $department, $full_name]);

                        // Update notification counts
                        $stmtUpdateNotificationCounts = $this->pdo->prepare("
                            UPDATE users_tb 
                            SET notification_count = notification_count + 1 
                            WHERE (user_type = 'Admin' AND department = ?) 
                            OR user_type = 'Super Admin'
                            OR user_id = ? OR user_id = ?
                        ");
                        $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                        // Broadcast new notification to all clients
                        $this->broadcastNotification('new_notification');

                        $addedUsers[] = [
                            'email' => $email,
                            'password' => $password,
                            'full_name' => $full_name,
                            'department' => $department,
                            'user_type' => $user_type
                        ];
                    } else {
                        $failedCount++;
                        $errorMessages[] = "Line $lineNumber: Failed to add user: $full_name";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMessages[] = "Line $lineNumber: Error adding user $full_name: " . $e->getMessage();
                    error_log("Failed to add user: " . $e->getMessage());
                }
            }
        
            $response = [
                "action" => "add_multiple_users",
                "success" => $addedCount > 0,
                "addedCount" => $addedCount,
                "failedCount" => $failedCount,
                "message" => "Multiple users processed. Added: $addedCount, Failed: $failedCount",
                "errorMessages" => $errorMessages,
                "addedUsers" => $addedUsers
            ];
            $from->send(json_encode($response));
        
            // Notify all clients to refresh their user tables
            foreach ($this->clients as $client) {
                $client->send(json_encode(["action" => "refresh_users"]));
            }
        
            echo "Multiple users processed. Added: $addedCount, Failed: $failedCount\n";
        }

        else if (isset($data['action']) && $data['action'] === 'edit_smart_tv') {
            $tvId = $data['tv_id'];
            $tvName = $data['tv_name'];
            $tvBrand = $data['tv_brand'];
            $tvDepartment = $data['tv_department'];

            // Perform the update in the database
            $stmt = $this->pdo->prepare("UPDATE smart_tvs_tb SET tv_name = ?, tv_brand = ?, tv_department = ? WHERE tv_id = ?");
            $stmt->execute([$tvName, $tvBrand, $tvDepartment, $tvId]);

            $tv = $stmt->rowCount() > 0;

            if ($tv) {
                // Update notification status
                $stmtUpdateEvaluatorNotification = $this->pdo->prepare("UPDATE notifications_tb SET user_id = ?, status = 'approved', notification_type = 'smart_tv_edited', evaluator_name = ?, created_at = NOW() WHERE user_id = ? AND content_tv_id = ?");
                $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $user_id, $content_id]);

                // Notify the Admin or Super Admin who approved the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'content_approved', $content_type, 'approved', $evaluator_name, $full_name, $department, $content_tv_id]);
                    }
                }
                
                // Notify all Super Admins except the one who approved the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'content_approved', $content_type, 'approved', $evaluator_name, $full_name, $department, $content_tv_id]);
                    }
                }

                // Notify the new user that an admin approved them
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id) 
                                             VALUES (?, 'content_approved_by_admin', ?, 'approved', ?, ?, ?, ?)");
                $stmt->execute([$user_id, $content_type, $evaluator_name, $full_name, $department, $content_tv_id]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);
                // Prepare the response with the new TV name, brand, and department
                $response = [
                    'action' => 'edit_smart_tv',
                    'success' => true,
                    'tv_id' => $tvId,
                    'tv_name' => $tvName,
                    'tv_brand' => $tvBrand,
                    'tv_department' => $tvDepartment
                ];
                // Notify the client who sent the request
                foreach ($this->clients as $client) {
                    $client->send(json_encode($response));
                }

                echo "TV details updated for tv_id: {$tvId}\n";
            } else {
                $response = ['action' => 'edit_smart_tv', 'success' => false];
                $from->send(json_encode($response));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'delete_smart_tv') {
            $tvId = $data['tv_id'];
        
            // Start transaction
            $this->pdo->beginTransaction();
            
            try {
                // Add to deleted TVs table first
                $stmtAddToDeleted = $this->pdo->prepare("INSERT INTO deleted_tvs_tb (tv_id, deleted_at) VALUES (?, NOW())");
                $stmtAddToDeleted->execute([$tvId]);
        
                // Then delete from smart_tvs_tb
                $stmtDeleteSmartTV = $this->pdo->prepare("DELETE FROM smart_tvs_tb WHERE tv_id = ?");
                $stmtDeleteSmartTV->execute([$tvId]);
        
                $this->pdo->commit();
        
                $response = [
                    'action' => 'delete_smart_tv',
                    'data' => $data,
                    'success' => true,
                    'tv_id' => $tvId,
                    'unset_cookies' => true
                ];
        
                // Notify all connected clients about the update
                foreach ($this->clients as $client) {
                    $client->send(json_encode($response));
                }
                
                echo "TV deleted for tv_id: {$tvId}\n";
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $from->send(json_encode(['action' => 'delete_smart_tv', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'approve_post') {
            $content_id = $data['content_id'];
            $content_type = $data['content_type'];
            $content_tv_id = $data['content_tv_id'];
            $user_id = $data['user_id'];
            $full_name = $data['full_name'];
            $department = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];

            // Update the content status in the respective table
            if ($content_type === 'announcement' || $content_type === 'event' || $content_type === 'promaterial') {
                $table = $content_type . 's_tb'; 
            } else {
                $table = $content_type . '_tb'; 
            }
                
            $idField = $content_type . '_id';
            $stmtUpdateContentTable = $this->pdo->prepare("UPDATE $table SET status = 'Approved' WHERE $idField = ?");
            $stmtUpdateContentTable->execute([$content_id]);
        
            $content_updated = $stmtUpdateContentTable->rowCount() > 0;
        
            if ($content_updated) {
                // Update notification status
                $stmtUpdateEvaluatorNotification = $this->pdo->prepare("UPDATE notifications_tb SET user_id = ?, status = 'approved', notification_type = 'content_approved', evaluator_name = ?, created_at = NOW() WHERE user_id = ? AND content_id = ?");
                $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $user_id, $content_id]);

                // Notify the Admin or Super Admin who approved the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the approved user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$admin['user_id'], 'content_approved', $content_type, 'approved', $evaluator_name, $full_name, $department, $content_tv_id]);
                    }
                }
                
                // Notify all Super Admins except the one who approved the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$superAdmin['user_id'], 'content_approved', $content_type, 'approved', $evaluator_name, $full_name, $department, $content_tv_id]);
                    }
                }

                // Notify the new user that an admin approved them
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, content_type, status, evaluator_name, action_user_name, action_user_department, content_tv_id) 
                                             VALUES (?, 'content_approved_by_admin', ?, 'approved', ?, ?, ?, ?)");
                $stmt->execute([$user_id, $content_type, $evaluator_name, $full_name, $department, $content_tv_id]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                // Create a new notification for the user
                // $stmtInsertNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status, evaluator_name) VALUES (?, ?, ?, 'content_approved_by_admin', 'approved', ?)");
                // $stmtInsertNotificationTable->execute([$user_id, $content_id, $content_type, $evaluator_name]);

                // // Increase the notification count of the Evaluator and notify them that a student or faculty posted new content
                // $stmtUpdateAdminOrSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE full_name = ?");
                // $stmtUpdateAdminOrSuperAdminNotificationCount->execute([$evaluator_name]);




                // Check the current notification count of the student or faculty
                // $stmtCheckNotificationCount = $this->pdo->prepare("SELECT notification_count FROM users_tb WHERE user_id = ?");
                // $stmtCheckNotificationCount->execute([$user_id]);
                // $currentNotificationCount = $stmtCheckNotificationCount->fetchColumn();

                // Only update the notification count if it's currently 0
                // if ($currentNotificationCount == 0) {
                //     $stmtUpdateStudentOrFacultyNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = 1 WHERE user_id = ?");
                //     $stmtUpdateStudentOrFacultyNotificationCount->execute([$user_id]);
                // }
        
                // Broadcast update notification to all clients
                $this->broadcastNotification('new_notification');

                echo "Notification sent to all clients\n";

                // Prepare the response
                $response = [
                    'action' => 'approve_post', 
                    'success' => true, 
                    'content_updated' => $content_updated,
                    'type' => $content_type, 
                    'user_id' => $user_id,
                    'data' => $data
                ];

                // $response = [
                //     'action' => 'approve_post', 
                //     'success' => true, 
                //     'user_id' => $user_id, 
                //     'data' => $data
                // ];

        
                echo "Content approved and notification updated!\n";
            } else {
                $response = ['action' => 'approve_post', 'success' => false, 'message' => 'Failed to approve content'];
            }
        
            // Notify the client who sent the request
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'reject_post') {
            $content_id = $data['content_id'];
            $content_type = $data['content_type'];
            $content_tv_id = $data['content_tv_id'];
            $user_id = $data['user_id'];
            $full_name = $data['full_name'];
            $department = $data['department'];
            $evaluator_name = $data['evaluator_name'];
            $evaluator_id = $data['evaluator_id'];
            $evaluator_message = $data['evaluator_message'];

            // Update the content status in the respective table
            if ($content_type === 'announcement' || $content_type === 'event' || $content_type === 'promaterial') {
                $table = $content_type . 's_tb'; 
            } else {
                $table = $content_type . '_tb'; 
            }
                
            $idField = $content_type . '_id';
            $stmtUpdateContentTable = $this->pdo->prepare("UPDATE $table SET status = 'Rejected' WHERE $idField = ?");
            $stmtUpdateContentTable->execute([$content_id]);
        
            $content_updated = $stmtUpdateContentTable->rowCount() > 0;
        
            if ($content_updated) {
                // Update notification status
                $stmtUpdateEvaluatorNotification = $this->pdo->prepare("UPDATE notifications_tb SET user_id = ?, status = 'rejected', notification_type = 'content_rejected', evaluator_name = ?, evaluator_message = ?, created_at = NOW() WHERE user_id = ? AND content_id = ?");
                $stmtUpdateEvaluatorNotification->execute([$evaluator_id, $evaluator_name, $evaluator_message, $user_id, $content_id]);

                // Notify the Admin or Super Admin who rejected the user
                $stmtUserIdEvaluator = $this->pdo->prepare("SELECT user_id FROM users_tb WHERE user_id = ?");
                $stmtUserIdEvaluator->execute([$evaluator_id]);
                $evaluatorId = $stmtUserIdEvaluator->fetchAll(PDO::FETCH_ASSOC);

                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ? AND user_id != ?
                ");
                $stmtSelectDepartmentAdmins->execute([$department, $evaluator_id, $user_id]);
                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                
                // Notify each Admin of the rejected user's department
                if ($departmentAdmins) {
                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, notification_type, content_type, status, evaluator_name, evaluator_message, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($departmentAdmins as $admin) {
                        $stmtNotifyDepartmentAdmins->execute([$content_id, $admin['user_id'], 'content_rejected', $content_type, 'rejected', $evaluator_name, $evaluator_message, $full_name, $department, $content_tv_id]);
                    }
                }
                
                // Notify all Super Admins except the one who rejected the user
                $stmtSelectSuperAdmins = $this->pdo->prepare("
                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ? AND user_id != ?
                ");
                $stmtSelectSuperAdmins->execute([$evaluator_id, $user_id]);
                $allSuperAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);

                // Notify all Super Admins
                if ($allSuperAdmins) {
                    error_log("allSuperAdmins called");
                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                        INSERT INTO notifications_tb (content_id, user_id, notification_type, content_type, status, evaluator_name, evaluator_message, action_user_name, action_user_department, content_tv_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($allSuperAdmins as $superAdmin) {
                        $stmtNotifySuperAdmins->execute([$content_id, $superAdmin['user_id'], 'content_rejected', $content_type, 'rejected', $evaluator_name, $evaluator_message, $full_name, $department, $content_tv_id]);
                    }
                }

                // Notify the new user that an admin rejected them
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (content_id, user_id, notification_type, content_type, status, evaluator_name, evaluator_message, action_user_name, action_user_department, content_tv_id) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$content_id, $user_id, 'content_rejected_by_admin', $content_type, 'rejected', $evaluator_name, $evaluator_message, $full_name, $department, $content_tv_id]);

                // Update notification counts for Admins, Super Admins, and the evaluator
                $stmtUpdateNotificationCounts = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE (user_type = 'Admin' AND department = ?) 
                    OR user_type = 'Super Admin'
                    OR user_id = ? OR user_id = ?
                ");
                $stmtUpdateNotificationCounts->execute([$department, $evaluator_id, $user_id]);

                // Update notification status
                // $stmtUpdateNotificationTable = $this->pdo->prepare("UPDATE notifications_tb SET status = 'rejected', notification_type = 'content_rejected', evaluator_name = ?, evaluator_message = ? WHERE user_id = ? AND content_id = ? AND content_type = ?");
                // $stmtUpdateNotificationTable->execute([$evaluator_name, $evaluator_message, $user_id, $content_id, $content_type]);

                // // Create a new notification for the user
                // $stmtInsertNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status, evaluator_name, evaluator_message) VALUES (?, ?, ?, 'content_rejected_by_admin', 'rejected', ?, ?)");
                // $stmtInsertNotificationTable->execute([$user_id, $content_id, $content_type, $evaluator_name, $evaluator_message]);

                // // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                // $stmtUpdateAdminOrSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE full_name = ?");
                // $stmtUpdateAdminOrSuperAdminNotificationCount->execute([$evaluator_name]);

                // // Check the current notification count of the student or faculty
                // $stmtCheckNotificationCount = $this->pdo->prepare("SELECT notification_count FROM users_tb WHERE user_id = ?");
                // $stmtCheckNotificationCount->execute([$user_id]);
                // $currentNotificationCount = $stmtCheckNotificationCount->fetchColumn();

                // // Only update the notification count if it's currently 0
                // if ($currentNotificationCount == 0) {
                //     $stmtUpdateStudentOrFacultyNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = 1 WHERE user_id = ?");
                //     $stmtUpdateStudentOrFacultyNotificationCount->execute([$user_id]);
                // }
    
                // Broadcast update notification to all clients
                $this->broadcastNotification('new_notification');

                echo "Notification sent to all clients\n";

                // Prepare the response
                $response = [
                    'action' => 'reject_post', 
                    'success' => true, 
                    'content_updated' => $content_updated
                ];
        
                echo "Content rejected and notification updated!\n";
            } else {
                $response = ['action' => 'reject_post', 'success' => false, 'message' => 'Failed to reject content'];
            }
        
            // Notify the client who sent the request
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'delete_notification') {
            $notificationId = $data['notification_id'];
    
            $stmt = $this->pdo->prepare("DELETE FROM notifications_tb WHERE notification_id = ?");
            $stmt->execute([$notificationId]);
    
            $deleted = $stmt->rowCount() > 0;
    
            $response = [
                'action' => 'delete_notification',
                'success' => $deleted,
                'notification_id' => $notificationId
            ];
    
            if ($deleted) {
                echo "Notification deleted: {$notificationId}\n";
            } else {
                echo "Failed to delete notification: {$notificationId}\n";
            }
    
            // Notify the client who sent the request
            $from->send(json_encode($response));
    
            // Broadcast the update to all connected clients
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'fetch_dimensions') {
            $stmt = $this->pdo->prepare("SELECT width, height FROM content_template_tb LIMIT 1");
            $stmt->execute();
            $dimensions = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($dimensions) {
                $response = [
                    'action' => 'update_dimensions',
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height']
                ];
                echo "Dimensions fetched and sent to client!\n";
            } else {
                $response = [
                    'action' => 'error',
                    'message' => 'Failed to fetch dimensions'
                ];
                echo "Failed to fetch dimensions\n";
            }
    
            // Send the response to the client who sent the request
            $from->send(json_encode($response));
    
            // Broadcast the dimensions to all connected clients
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'save_draft') {
            // Access values from the connection object
            $user_id = $from->user_id;
            $full_name = $from->full_name;
            $user_type = $from->user_type;
            $department = $from->department;
            if (isset($data['tv_ids']) && is_array($data['tv_ids']) && !empty($data['tv_ids'])) { // Check for tv_id as an array
                error_log("TV IDs are selected."); // Log for debugging
                // $tv_ids = $data['tv_ids'];
                foreach ($data['tv_ids'] as $tv_ids) { 
                    $status = 'Draft';
                    $category = match ($data['type']) {
                        'event' => 'Event',
                        'announcement' => 'Announcement',
                        'news' => 'News',
                        'promaterial' => 'Promotional Materials',
                        'peo' => 'PEO',
                        'so' => 'SO',
                        default => 'Unknown'
                    };

                    $isArchived = 0;

                    // Determine media folder
                    $mediaFolder = match ($data['type']) {
                        'event' => 'event_media',
                        'announcement' => 'announcement_media',
                        'news' => 'news_media',
                        'promaterial' => 'promaterial_media',
                        default => null
                    };

                    if ($mediaFolder && !file_exists($mediaFolder)) {
                        mkdir($mediaFolder, 0777, true);
                    }

                    $table = match ($data['type']) {
                        'announcement' => 'announcements_tb',
                        'event' => 'events_tb',
                        'news' => 'news_tb',
                        'promaterial' => 'promaterials_tb',
                        'peo' => 'peo_tb',
                        'so' => 'so_tb',
                        default => 'No Table Found'
                    };

                    $idField = match ($data['type']) {
                        'announcement' => 'announcement_id',
                        'event' => 'event_id',
                        'news' => 'news_id',
                        'promaterial' => 'promaterial_id',
                        'peo' => 'peo_id',
                        'so' => 'so_id',
                        default => 'No Content ID Found'
                    };

                    $authorField = match ($data['type']) {
                        'announcement' => 'announcement_author_id',
                        'event' => 'event_author_id',
                        'news' => 'news_author_id',
                        'promaterial' => 'promaterial_author_id',
                        'peo' => 'peo_author_id',
                        'so' => 'so_author_id',
                        default => 'No Author Found'
                    };

                    // Common fields for all types
                    $fields = [
                        'department', 'user_type', $authorField, 'tv_id', 'display_time',
                        'category', 'isArchived', 'status'
                    ];
                    $values = [
                        $department, $user_type, $user_id, $tv_ids, $data['display_time'] ?? null,
                        $category, $isArchived, $status
                    ];

                    // Add type-specific fields
                    if ($data['type'] === 'event') {
                        $fields[] = 'event_body';
                        $fields[] = 'expiration_datetime';
                        $fields[] = 'schedule_datetime';
                        $values[] = $data['event_body'] ?? null;
                        $values[] = $data['expiration_datetime'] ?? null;
                        $values[] = $data['schedule_datetime'] ?? null;
                    } elseif ($data['type'] === 'news') {
                        $fields[] = 'news_body';
                        $fields[] = 'expiration_datetime';
                        $fields[] = 'schedule_datetime';
                        $values[] = $data['news_body'] ?? null;
                        $values[] = $data['expiration_datetime'] ?? null;
                        $values[] = $data['schedule_datetime'] ?? null;
                    } elseif ($data['type'] === 'announcement') {
                        $fields[] = 'announcement_body';
                        $fields[] = 'expiration_datetime';
                        $fields[] = 'schedule_datetime';
                        $values[] = $data['announcement_body'] ?? null;
                        $values[] = $data['expiration_datetime'] ?? null;
                        $values[] = $data['schedule_datetime'] ?? null;
                    } elseif ($data['type'] === 'promaterial') {
                        $fields[] = 'expiration_datetime';
                        $fields[] = 'schedule_datetime';
                        $values[] = $data['expiration_datetime'] ?? null;
                        $values[] = $data['schedule_datetime'] ?? null;
                    } elseif ($data['type'] === 'peo') {
                        $fields[] = 'peo_title';
                        $fields[] = 'peo_description';
                        $fields[] = 'peo_subdescription';                        
                        $values[] = $data['peo_title'] ?? null;
                        $values[] = $data['peo_description'] ?? null;
                        $values[] = $data['peo_subdescription'] ?? null;                        
                    } elseif ($data['type'] === 'so') {
                        $fields[] = 'so_title';
                        $fields[] = 'so_description';
                        $fields[] = 'so_subdescription';
                        $values[] = $data['so_title'] ?? null;
                        $values[] = $data['so_description'] ?? null;
                        $values[] = $data['so_subdescription'] ?? null;
                    }

                    $fieldList = implode(', ', $fields);
                    $placeholderList = implode(', ', array_fill(0, count($fields), '?'));

                    $statement = $this->pdo->prepare(
                        "INSERT INTO $table ($fieldList) VALUES ($placeholderList)"
                    );

                    $success = $statement->execute($values);

                    if ($success) {
                        $id = $this->pdo->lastInsertId();
                        $data[$idField] = $id;
                        $data[$authorField] = $user_id;
                        $data['category'] = $category;
                        $data['user_type'] = $user_type;
                        $data['status'] = $status;

                        if (!empty($data['media'])) {
                            $base64Data = $data['media'];
                            $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                            $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                            $timeNow = date('YmdHis');
                            $filename = "{$id}_{$timeNow}.{$fileExtension}";
                            $media_save = "{$mediaFolder}/{$filename}";

                            file_put_contents($media_save, $mediaData);

                            // Update the record with media_path
                            $updateStatement = $this->pdo->prepare("UPDATE $table SET media_path = ? WHERE $idField = ?");
                            $updateStatement->execute([$filename, $id]);

                            $data['media_path'] = $filename;
                        }

                        $from->send(json_encode(['success' => true, 'data' => $data]));
                        echo ucfirst($data['type']) . " draft saved " . (!empty($data['media']) ? 'with' : 'without') . " media!\n";
                    } else {
                        $from->send(json_encode(['error' => 'Error saving draft for ' . $data['type'] . '. Try again later']));
                    }
                }
            } else if (empty($data['tv_ids'])) {
                error_log("No TV IDs provided."); // Log for debugging
                $status = 'Draft';
                $category = match ($data['type']) {
                    'event' => 'Event',
                    'announcement' => 'Announcement',
                    'news' => 'News',
                    'promaterial' => 'Promotional Materials',
                    'peo' => 'PEO',
                    'so' => 'SO',
                    default => 'Unknown'
                };

                $isArchived = 0;

                // Determine media folder
                $mediaFolder = match ($data['type']) {
                    'event' => 'event_media',
                    'announcement' => 'announcement_media',
                    'news' => 'news_media',
                    'promaterial' => 'promaterial_media',
                    default => null
                };

                if ($mediaFolder && !file_exists($mediaFolder)) {
                    mkdir($mediaFolder, 0777, true);
                }

                $table = match ($data['type']) {
                    'announcement' => 'announcements_tb',
                    'event' => 'events_tb',
                    'news' => 'news_tb',
                    'promaterial' => 'promaterials_tb',
                    'peo' => 'peo_tb',
                    'so' => 'so_tb',
                    default => 'No Table Found'
                };

                $idField = match ($data['type']) {
                    'announcement' => 'announcement_id',
                    'event' => 'event_id',
                    'news' => 'news_id',
                    'promaterial' => 'promaterial_id',
                    'peo' => 'peo_id',
                    'so' => 'so_id',
                    default => 'No Content ID Found'
                };

                $authorField = match ($data['type']) {
                    'announcement' => 'announcement_author_id',
                    'event' => 'event_author_id',
                    'news' => 'news_author_id',
                    'promaterial' => 'promaterial_author_id',
                    'peo' => 'peo_author_id',
                    'so' => 'so_author_id',
                    default => 'No Author Found'
                };
                
                // Common fields for all types
                $fields = [
                    'department', 'user_type', $authorField, 'tv_id', 'display_time',
                    'category', 'isArchived', 'status'
                ];
                $values = [
                    $department, $user_type, $user_id, null, $data['display_time'] ?? null,
                    $category, $isArchived, $status
                ];

                // Add type-specific fields
                if ($data['type'] === 'event') {
                    $fields[] = 'event_body';
                    $fields[] = 'expiration_datetime';
                    $fields[] = 'schedule_datetime';
                    $values[] = $data['event_body'] ?? null;
                    $values[] = $data['expiration_datetime'] ?? null;
                    $values[] = $data['schedule_datetime'] ?? null;
                } elseif ($data['type'] === 'news') {
                    $fields[] = 'news_body';
                    $fields[] = 'expiration_datetime';
                    $fields[] = 'schedule_datetime';
                    $values[] = $data['news_body'] ?? null;
                    $values[] = $data['expiration_datetime'] ?? null;
                    $values[] = $data['schedule_datetime'] ?? null;
                } elseif ($data['type'] === 'announcement') {
                    $fields[] = 'announcement_body';
                    $fields[] = 'expiration_datetime';
                    $fields[] = 'schedule_datetime';
                    $values[] = $data['announcement_body'] ?? null;
                    $values[] = $data['expiration_datetime'] ?? null;
                    $values[] = $data['schedule_datetime'] ?? null;
                } elseif ($data['type'] === 'promaterial') {
                    $fields[] = 'expiration_datetime';
                    $fields[] = 'schedule_datetime';
                    $values[] = $data['expiration_datetime'] ?? null;
                    $values[] = $data['schedule_datetime'] ?? null;
                } elseif ($data['type'] === 'peo') {
                    $fields[] = 'peo_title';
                    $fields[] = 'peo_description';
                    $fields[] = 'peo_subdescription';
                    $values[] = $data['peo_title'] ?? null;
                    $values[] = $data['peo_description'] ?? null;
                    $values[] = $data['peo_subdescription'] ?? null;                    
                } elseif ($data['type'] === 'so') {
                    $fields[] = 'so_title';
                    $fields[] = 'so_description';
                    $fields[] = 'so_subdescription';
                    $values[] = $data['so_title'] ?? null;
                    $values[] = $data['so_description'] ?? null;
                    $values[] = $data['so_subdescription'] ?? null;
                }

                $fieldList = implode(', ', $fields);
                $placeholderList = implode(', ', array_fill(0, count($fields), '?'));

                $statement = $this->pdo->prepare(
                    "INSERT INTO $table ($fieldList) VALUES ($placeholderList)"
                );

                $success = $statement->execute($values);

                if ($success) {
                    $id = $this->pdo->lastInsertId();
                    $data[$idField] = $id;
                    $data[$authorField] = $user_id;
                    $data['category'] = $category;
                    $data['user_type'] = $user_type;
                    $data['status'] = $status;

                    if (!empty($data['media'])) {
                        $base64Data = $data['media'];
                        $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                        $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                        $timeNow = date('YmdHis');
                        $filename = "{$id}_{$timeNow}.{$fileExtension}";
                        $media_save = "{$mediaFolder}/{$filename}";

                        file_put_contents($media_save, $mediaData);

                        // Update the record with media_path
                        $updateStatement = $this->pdo->prepare("UPDATE $table SET media_path = ? WHERE $idField = ?");
                        $updateStatement->execute([$filename, $id]);

                        $data['media_path'] = $filename;
                    }

                    $from->send(json_encode(['success' => true, 'data' => $data]));
                    echo ucfirst($data['type']) . " draft saved " . (!empty($data['media']) ? 'with' : 'without') . " media!\n";
                } else {
                    $from->send(json_encode(['error' => 'Error saving draft for ' . $data['type'] . '. Try again later']));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'post_content') {
            // Access values from the connection object
            $user_id = $from->user_id;
            $full_name = $from->full_name;
            $user_type = $from->user_type;
            $department = $from->department;
            
            if (isset($data['tv_ids']) && is_array($data['tv_ids'])) { // Check for tv_id as an array
                error_log("TV IDs are selected."); // Log for debugging
                // $tv_ids = $data['tv_ids'];
                if ($data['type'] === 'orgchart' && isset($data['orgChartData']) && is_array($data['orgChartData'])) {
                    // For Orgchart only
                    foreach ($data['tv_ids'] as $tv_ids) {
                        $orgChartData = $data['orgChartData'];
                        $display_time = $data['display_time'];
                        $orgchart_id = rand(100, 999); // Generate a unique ID for this orgchart
                        foreach ($orgChartData as $member) {
                            $stmt = $this->pdo->prepare("
                                INSERT INTO org_chart_members (parent_node_id, parent_id, orgchart_id, name, title, type, display_time, tv_id, picture)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $member['id'],
                                $member['parent_id'],
                                $orgchart_id,
                                $member['name'],
                                $member['title'],
                                'orgchart',
                                $display_time,
                                $tv_ids,
                                $member['picture']
                            ]);
                            
                        }
                        $from->send(json_encode(['success' => true, 'data' => $data, 'orgchart_id' => $orgchart_id]));
                        // $from->send(json_encode(['action' => 'post_content', 'success' => true, 'type' => 'orgchart', 'orgchart_id' => $orgchart_id, 'orgChartData' => $orgChartData]));
                        echo "A member is being added! \n";
                    }
                } else if ($data['type'] === 'event' || $data['type'] === 'announcement' || $data['type'] === 'news' || $data['type'] === 'promaterial' || $data['type'] === 'peo' || $data['type'] === 'so') {
                    // For Announcement, Event, News, Promotional Materials, PEO, and SO only
                    foreach ($data['tv_ids'] as $tv_ids) {
                        $status = ($user_type == 'Admin' || $user_type == 'Super Admin') ? 'Approved' : 'Pending';
                        $category = match ($data['type']) {
                            'event' => 'Event',
                            'announcement' => 'Announcement',
                            'news' => 'News',
                            'promaterial' => 'Promotional Materials',
                            'peo' => 'PEO',
                            'so' => 'SO',
                            default => 'Unknown'
                        };
                    
                        $isArchived = 0;
                    
                        // Determine media folder
                        $mediaFolder = match ($data['type']) {
                            'event' => 'event_media',
                            'announcement' => 'announcement_media',
                            'news' => 'news_media',
                            'promaterial' => 'promaterial_media',
                            default => null
                        };
                    
                        if ($mediaFolder && !file_exists($mediaFolder)) {
                            mkdir($mediaFolder, 0777, true);
                        }
                    
                        $table = match ($data['type']) {
                            'announcement' => 'announcements_tb',
                            'event' => 'events_tb',
                            'news' => 'news_tb',
                            'promaterial' => 'promaterials_tb',
                            'peo' => 'peo_tb',
                            'so' => 'so_tb',
                            default => 'No Table Found'
                        };
    
                        $idField = match ($data['type']) {
                            'announcement' => 'announcement_id',
                            'event' => 'event_id',
                            'news' => 'news_id',
                            'promaterial' => 'promaterial_id',
                            'peo' => 'peo_id',
                            'so' => 'so_id',
                            default => 'No Content ID Found'
                        };
    
                        $authorField = match ($data['type']) {
                            'announcement' => 'announcement_author_id',
                            'event' => 'event_author_id',
                            'news' => 'news_author_id',
                            'promaterial' => 'promaterial_author_id',
                            'peo' => 'peo_author_id',
                            'so' => 'so_author_id',
                            default => 'No Author Found'
                        };
                    
                        // Common fields for all types
                        $fields = [
                            'department', 'user_type', $authorField, 'tv_id', 'display_time',
                            'category', 'isArchived'
                        ];
                        $values = [
                            $department, $user_type, $user_id, $tv_ids, $data['display_time'],
                            $category, $isArchived
                        ];
    
                        if ($data['type'] !== 'peo' && $data['type'] !== 'so') {
                            $fields[] = 'status';
                            $values[] = $status;
                        }
                    
                        // Add type-specific fields
                        if ($data['type'] === 'event') {
                            $fields[] = 'event_body';
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            $values[] = $data['event_body'];
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'] ?? null;
                        } elseif ($data['type'] === 'news') {
                            $fields[] = 'news_body';
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            $values[] = $data['news_body'];
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'] ?? null;
                        } elseif ($data['type'] === 'announcement') {
                            $fields[] = 'announcement_body';
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            $values[] = $data['announcement_body'];
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'] ?? null;
                        } elseif ($data['type'] === 'promaterial') {
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            // No additional fields for promaterial
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'] ?? null;
                        } elseif ($data['type'] === 'peo') {
                            $fields[] = 'peo_title';
                            $fields[] = 'peo_description';
                            $fields[] = 'peo_subdescription';
                            $values[] = $data['peo_title'];
                            $values[] = $data['peo_description'];
                            $values[] = $data['peo_subdescription'];
                        } elseif ($data['type'] === 'so') {
                            $fields[] = 'so_title';
                            $fields[] = 'so_description';
                            $fields[] = 'so_subdescription';
                            $values[] = $data['so_title'];
                            $values[] = $data['so_description'];
                            $values[] = $data['so_subdescription'];
                        }
                    
                        $fieldList = implode(', ', $fields);
                        $placeholderList = implode(', ', array_fill(0, count($fields), '?'));
                    
                        $statement = $this->pdo->prepare(
                            "INSERT INTO $table ($fieldList) VALUES ($placeholderList)"
                        );
                    
                        $success = $statement->execute($values);
                    
                        if ($success) {
                            $id = $this->pdo->lastInsertId();
                            $data[$idField] = $id;
                            $data[$authorField] = $user_id;
                            $data['category'] = $category;
                            $data['user_type'] = $user_type;
                            $data['status'] = $status;

                            if ($user_type == 'Student' || $user_type == 'Faculty' || $user_type == 'Staff') {
                                // Check if post is scheduled
                                $notification_type = isset($data['schedule_datetime']) && !empty($data['schedule_datetime']) 
                                    ? 'content_scheduled' 
                                    : 'content_post';

                                // Prepare base notification fields and values
                                $notificationFields = "user_id, content_id, content_type, notification_type, status, action_user_name, action_user_department, action_user_type, content_tv_id";
                                $notificationValues = "?, ?, ?, ?, 'pending', ?, ?, ?, ?";
                                $params = [$user_id, $data[$idField], $data['type'], $notification_type, $full_name, $department, $user_type, $tv_ids];

                                // Add schedule_datetime if it exists
                                if (!empty($data['schedule_datetime'])) {
                                    $notificationFields .= ", content_schedule_datetime";
                                    $notificationValues .= ", ?";
                                    $params[] = $data['schedule_datetime'];
                                }

                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb ($notificationFields) VALUES ($notificationValues)");
                                $stmt->execute($params);

                                // Increase the notification count of the Admin of the department
                                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ? AND user_id != ?");
                                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                                // Increase the notification count of the Super Admin 
                                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin' AND user_id != ?");
                                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);

                                // Increase the notification count of the user who posted the content
                                $stmtUpdateUserWhoPosted = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1, last_post_date = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $stmtUpdateUserWhoPosted->execute([$user_id]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            } else if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                                // Check if post is scheduled
                                $notification_type = isset($data['schedule_datetime']) && !empty($data['schedule_datetime']) 
                                    ? 'content_scheduled' 
                                    : 'content_post';

                                // Insert notification for the user who posted his own content
                                if ($user_id) {
                                    // Prepare base query without schedule_datetime
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $params = [$data[$idField], $user_id, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    // Add schedule_datetime if it exists
                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $params[] = $data['schedule_datetime'];
                                    }

                                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb ($notificationFields) VALUES ($notificationValues)");
                                    $stmt->execute($params);
                                }

                                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ?
                                ");
                                $stmtSelectDepartmentAdmins->execute([$department, $user_id]);
                                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($departmentAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $baseParams[] = $data['schedule_datetime'];
                                    }

                                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($departmentAdmins as $admin) {
                                        $params = $baseParams;
                                        $params[1] = $admin['user_id']; // Set the user_id
                                        $stmtNotifyDepartmentAdmins->execute($params);
                                    }
                                }

                                $stmtSelectSuperAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ?
                                ");
                                $stmtSelectSuperAdmins->execute([$user_id]);
                                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($superAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $baseParams[] = $data['schedule_datetime'];
                                    }

                                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($superAdmins as $superAdmin) {
                                        $params = $baseParams;
                                        $params[1] = $superAdmin['user_id']; // Set the user_id
                                        $stmtNotifySuperAdmins->execute($params);
                                    }
                                }

                                // Increase the notification count of the user who posted the content
                                $stmtUpdateUserWhoPosted = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1, last_post_date = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $stmtUpdateUserWhoPosted->execute([$user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ? AND user_id != ?");
                                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin' AND user_id != ?");
                                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            }
                    
                            if (!empty($data['media'])) {
                                $base64Data = $data['media'];
                                $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                                $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                                $timeNow = date('YmdHis');
                                $filename = "{$id}_{$timeNow}.{$fileExtension}";
                                $media_save = "{$mediaFolder}/{$filename}";
                    
                                file_put_contents($media_save, $mediaData);
                    
                                // Update the record with media_path
                                $updateStatement = $this->pdo->prepare("UPDATE $table SET media_path = ? WHERE $idField = ?");
                                $updateStatement->execute([$filename, $id]);
                    
                                $data['media_path'] = $filename;
                            }

                            $response = ['action' => 'post_content', 'success' => true, 'user_id' => $user_id, 'data' => $data];
                            echo ucfirst($data['type']) . " " . (!empty($data['media']) ? 'with' : 'without') . " media uploaded!\n";
                        } else {
                            $response = ['action' => 'post_content', 'success' => false, 'error' => 'Error processing ' . $data['type'] . '. Try again later'];
                        }
                        $from->send(json_encode($response));
                    }
                } else if ($data['type'] === 'facility') {
                    // For Announcement, Event, News, Promotional Materials, PEO, and SO only
                    foreach ($data['tv_ids'] as $tv_ids) {
                        $category = match ($data['type']) {
                            'facility' => 'Facility',
                            default => 'Unknown'
                        };

                        $isArchived = 0;
                                        
                        // Determine media folder
                        $mediaFolder = match ($data['type']) {
                            'facility' => 'facility_media',
                            default => null
                        };
                    
                        if ($mediaFolder && !file_exists($mediaFolder)) {
                            mkdir($mediaFolder, 0777, true);
                        }
                    
                        $table = match ($data['type']) {
                            'facility' => 'facility_tb',
                            default => 'No Table Found'
                        };
    
                        $idField = match ($data['type']) {
                            'facility' => 'facility_id',
                            default => 'No Content ID Found'
                        };
    
                        $authorField = match ($data['type']) {
                            'facility' => 'facility_author_id',
                            default => 'No Author Found'
                        };
                    
                        // Common fields for all types
                        $fields = [
                            'department', $authorField, 'tv_id', 'display_time',
                            'category', 'isArchived'
                        ];
                        $values = [
                            $department, $user_id, $tv_ids, $data['display_time'],
                            $category, $isArchived
                        ];

                        $fields[] = 'facility_body';
                        $values[] = $data['facility_body'];
                            
                        $fieldList = implode(', ', $fields);
                        $placeholderList = implode(', ', array_fill(0, count($fields), '?'));
                    
                        $statement = $this->pdo->prepare(
                            "INSERT INTO $table ($fieldList) VALUES ($placeholderList)"
                        );
                    
                        $success = $statement->execute($values);
                    
                        if ($success) {
                            $id = $this->pdo->lastInsertId();
                            $data[$idField] = $id;
                            $data[$authorField] = $user_id;
                            $data['category'] = $category;

                            if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                                // Check if post is scheduled
                                $notification_type = 'content_post';

                                // Insert notification for the user who posted his own content
                                if ($user_id) {
                                    // Prepare base query without schedule_datetime
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $params = [$data[$idField], $user_id, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb ($notificationFields) VALUES ($notificationValues)");
                                    $stmt->execute($params);
                                }

                                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ?
                                ");
                                $stmtSelectDepartmentAdmins->execute([$department, $user_id]);
                                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($departmentAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($departmentAdmins as $admin) {
                                        $params = $baseParams;
                                        $params[1] = $admin['user_id']; // Set the user_id
                                        $stmtNotifyDepartmentAdmins->execute($params);
                                    }
                                }

                                $stmtSelectSuperAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ?
                                ");
                                $stmtSelectSuperAdmins->execute([$user_id]);
                                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($superAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($superAdmins as $superAdmin) {
                                        $params = $baseParams;
                                        $params[1] = $superAdmin['user_id']; // Set the user_id
                                        $stmtNotifySuperAdmins->execute($params);
                                    }
                                }

                                // Increase the notification count of the user who posted the content
                                $stmtUpdateUserWhoPosted = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1, last_post_date = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $stmtUpdateUserWhoPosted->execute([$user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ? AND user_id != ?");
                                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin' AND user_id != ?");
                                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            }
                    
                            // Handle multiple images
                            if (isset($data['media']) && is_array($data['media'])) {
                                $imageNames = []; // Array to store image filenames
                                
                                // Create the media folder if it doesn't exist
                                if (!file_exists($mediaFolder)) {
                                    mkdir($mediaFolder, 0777, true);
                                }

                                // Process each image in the array
                                foreach ($data['media'] as $index => $base64Data) {
                                    // Skip empty entries
                                    if (empty($base64Data)) continue;

                                    // Clean the base64 string
                                    $cleanedBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
                                    
                                    // Decode the base64 data
                                    $mediaData = base64_decode($cleanedBase64);
                                    
                                    // Generate unique filename using facility_id and index
                                    $filename = "{$id}_{$index}.png";
                                    $media_save = "{$mediaFolder}/{$filename}";
                                    
                                    // Save the file
                                    if (file_put_contents($media_save, $mediaData)) {
                                        $imageNames[] = $filename;
                                        error_log("Saved image: " . $filename); // Debug log
                                    } else {
                                        error_log("Failed to save image: " . $filename); // Debug log
                                    }
                                }
                                
                                // Only update if we have images
                                if (!empty($imageNames)) {
                                    // Join image names with commas and update the record
                                    $imageNamesString = implode(',', $imageNames);
                                    $updateStatement = $this->pdo->prepare("UPDATE $table SET facility_images_array = ? WHERE $idField = ?");
                                    $updateStatement->execute([$imageNamesString, $id]);
                                    
                                    $data['facility_images_array'] = $imageNamesString;
                                    error_log("Stored image array: " . $imageNamesString); // Debug log
                                }
                            }

                            $response = ['action' => 'post_content', 'success' => true, 'user_id' => $user_id, 'data' => $data];
                            echo ucfirst($data['type']) . " " . (!empty($imageNames) ? 'with ' . count($imageNames) . ' images' : 'without media') . " uploaded!\n";
                        } else {
                            $response = ['action' => 'post_content', 'success' => false, 'error' => 'Error processing ' . $data['type'] . '. Try again later'];
                        }
                        $from->send(json_encode($response));
                    }
                } else {
                    // For New Features only
                    foreach ($data['tv_ids'] as $tv_ids) { 
                        $type = $data['type'] ?? null;
                        $expiration_datetime = $data['expiration_datetime'] ?? null;
                        
                        $status = ($user_type == 'Admin' || $user_type == 'Super Admin') ? 'Approved' : 'Pending';
                        $category = ucfirst($type); // Capitalize the first letter of the type
            
                        $isArchived = 0;
            
                        // Determine media folder
                        $mediaFolder = $type . '_media';
                        if (!file_exists($mediaFolder)) {
                            mkdir($mediaFolder, 0777, true);
                        }
            
                        $table = $type . '_tb';
                        $idField = $type . '_id';
                        $authorField = $type . '_author_id';
            
                        // Common fields for all types - Remove duplicates
                        $fields = [
                            'department', 
                            'user_type', 
                            $authorField, 
                            'tv_id', 
                            'display_time',
                            'category', 
                            'isArchived', 
                            'status',
                            'expiration_datetime', 
                            'type'
                        ];

                        $values = [
                            $department, 
                            $user_type, 
                            $user_id, 
                            $tv_ids, 
                            $data['display_time'],
                            $category, 
                            $isArchived, 
                            $status,
                            $expiration_datetime, 
                            $type
                        ];

                        // Add other fields specific to the content type
                        // if ($contentType === 'bulletin_board') {
                        //     $fields[] = 'president_name';
                        //     $values[] = $data['president_name'];
                        // }

                        // Add all other fields from the form data
                        // foreach ($data as $key => $value) {
                        //     if (!in_array($key, ['action', 'type', 'tv_ids', 'display_time', 'media']) && $key !== $authorField) {
                        //         $fields[] = $key;
                        //         $values[] = $value;
                        //     }
                        // }
            
                        // Add type-specific fields, excluding those already added
                        foreach ($data as $key => $value) {
                            if (!in_array($key, [
                                'action', 'type', 'tv_id[]', 'tv_ids', 
                                'expiration_datetime', 'display_time', 'media',
                                'department', 'user_type', $authorField, 'category',
                                'status', $idField
                            ])) {
                                $fields[] = $key;
                                $values[] = is_array($value) ? json_encode($value) : $value;
                            }
                        }
            
                        $fieldList = implode(', ', $fields);
                        $placeholderList = implode(', ', array_fill(0, count($fields), '?'));

                        $statement = $this->pdo->prepare(
                            "INSERT INTO $table ($fieldList) VALUES ($placeholderList)"
                        );
            
                        $success = $statement->execute($values);
            
                        if ($success) {
                            echo "Success: " . $success . "\n";
                            $id = $this->pdo->lastInsertId();
                            $data[$idField] = $id;
                            $data[$authorField] = $user_id;
                            $data['category'] = $category;
                            $data['user_type'] = $user_type;
                            $data['status'] = $status;
                            
                            if ($user_type == 'Student' || $user_type == 'Faculty' || $user_type == 'Staff') {
                                // Check if post is scheduled
                                $notification_type = 'content_post';

                                // Prepare base notification fields and values
                                $notificationFields = "user_id, content_id, content_type, notification_type, status, action_user_name, action_user_department, action_user_type, content_tv_id";
                                $notificationValues = "?, ?, ?, ?, 'pending', ?, ?, ?, ?";
                                $params = [$user_id, $data[$idField], $data['type'], $notification_type, $full_name, $department, $user_type, $tv_ids];

                                // Add schedule_datetime if it exists
                                if (!empty($data['schedule_datetime'])) {
                                    $notificationFields .= ", content_schedule_datetime";
                                    $notificationValues .= ", ?";
                                    $params[] = $data['schedule_datetime'];
                                }

                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb ($notificationFields) VALUES ($notificationValues)");
                                $stmt->execute($params);

                                // Increase the notification count of the Admin of the department
                                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ? AND user_id != ?");
                                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                                // Increase the notification count of the Super Admin 
                                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin' AND user_id != ?");
                                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);

                                // Increase the notification count of the user who posted the content
                                $stmtUpdateUserWhoPosted = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1, last_post_date = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $stmtUpdateUserWhoPosted->execute([$user_id]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            } else if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                                $notification_type = 'content_post';
                                // Insert notification for the user who posted his own content
                                if ($user_id) {
                                    // Prepare base query without schedule_datetime
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $params = [$data[$idField], $user_id, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    // Add schedule_datetime if it exists
                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $params[] = $data['schedule_datetime'];
                                    }

                                    $stmt = $this->pdo->prepare("INSERT INTO notifications_tb ($notificationFields) VALUES ($notificationValues)");
                                    $stmt->execute($params);
                                }

                                $stmtSelectDepartmentAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Admin' AND department = ? AND user_id != ?
                                ");
                                $stmtSelectDepartmentAdmins->execute([$department, $user_id]);
                                $departmentAdmins = $stmtSelectDepartmentAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($departmentAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $baseParams[] = $data['schedule_datetime'];
                                    }

                                    $stmtNotifyDepartmentAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($departmentAdmins as $admin) {
                                        $params = $baseParams;
                                        $params[1] = $admin['user_id']; // Set the user_id
                                        $stmtNotifyDepartmentAdmins->execute($params);
                                    }
                                }

                                $stmtSelectSuperAdmins = $this->pdo->prepare("
                                    SELECT * FROM users_tb WHERE user_type = 'Super Admin' AND user_id != ?
                                ");
                                $stmtSelectSuperAdmins->execute([$user_id]);
                                $superAdmins = $stmtSelectSuperAdmins->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Notify each Admin of the approved user's department
                                if ($superAdmins) {
                                    $notificationFields = "content_id, user_id, content_type, notification_type, status, action_user_name, action_user_type, action_user_department, evaluator_name, content_tv_id";
                                    $notificationValues = "?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?";
                                    $baseParams = [$data[$idField], null, $data['type'], $notification_type, $full_name, $user_type, $department, $full_name, $tv_ids];

                                    if (!empty($data['schedule_datetime'])) {
                                        $notificationFields .= ", content_schedule_datetime";
                                        $notificationValues .= ", ?";
                                        $baseParams[] = $data['schedule_datetime'];
                                    }

                                    $stmtNotifySuperAdmins = $this->pdo->prepare("
                                        INSERT INTO notifications_tb ($notificationFields)
                                        VALUES ($notificationValues)
                                    ");

                                    foreach ($superAdmins as $superAdmin) {
                                        $params = $baseParams;
                                        $params[1] = $superAdmin['user_id']; // Set the user_id
                                        $stmtNotifySuperAdmins->execute($params);
                                    }
                                }

                                // Increase the notification count of the user who posted the content
                                $stmtUpdateUserWhoPosted = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1, last_post_date = CURRENT_TIMESTAMP WHERE user_id = ?");
                                $stmtUpdateUserWhoPosted->execute([$user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Admin' AND department = ? AND user_id != ?");
                                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                                // Increase the notification count of the Admin or Super Admin and notify them that a student or faculty posted new content
                                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("UPDATE users_tb SET notification_count = notification_count + 1 WHERE user_type = 'Super Admin' AND user_id != ?");
                                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            }
            
                            if (!empty($data['media'])) {
                                $base64Data = $data['media'];
                                $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                                $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                                $timeNow = date('YmdHis');
                                $filename = "{$id}_{$timeNow}.{$fileExtension}";
                                $media_save = "{$mediaFolder}/{$filename}";
            
                                file_put_contents($media_save, $mediaData);
            
                                // Update the record with media_path
                                $updateStatement = $this->pdo->prepare("UPDATE $table SET media_path = ? WHERE $idField = ?");
                                $updateStatement->execute([$filename, $id]);
            
                                $data['media_path'] = $filename;
                            }
            
                            $from->send(json_encode(['success' => true, 'data' => $data]));
                            echo ucfirst($type) . " " . (!empty($data['media']) ? 'with' : 'without') . " media uploaded!\n";
                        } else {
                            echo "Error: " . $success . "\n";
                            $from->send(json_encode(['error' => 'Error processing ' . $type . '. Try again later']));
                        }
                    }
                }
            } else {
                error_log("No TV IDs selected."); // Log for debugging
                $tv_ids = $data['tv_ids'];
            }
        }

        else if (isset($data['action']) && $data['action'] === 'post_now_content') {
            // Access values from the connection object
            $user_id = $from->user_id;
            $full_name = $from->full_name;
            $user_type = $from->user_type;
            $department = $from->department;

            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id']
            ];
        
            $type = $data['type'] ?? null;
            
            if (!isset($validTypes[$type])) {
                // Check if it's a new feature
                $stmt = $this->pdo->prepare("SELECT * FROM features_tb WHERE type = ?");
                $stmt->execute([strtolower($type)]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
        
                if ($feature) {
                    $validTypes[$type] = [
                        'table' => strtolower($feature['type']) . '_tb',
                        'idField' => strtolower($feature['type']) . '_id'
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Invalid content type'];
                    $from->send(json_encode($response));
                    return;
                }
            }
        
            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];
            $content_id = $data[$idField];
        
            // Update the schedule_datetime to NULL
            $stmt = $this->pdo->prepare("UPDATE $table SET schedule_datetime = NULL WHERE $idField = ?");
            $success = $stmt->execute([$content_id]);
        
            if ($success) {
                // Update the notification type from scheduled to post
                $updateNotification = $this->pdo->prepare("
                    UPDATE notifications_tb 
                    SET notification_type = 'content_scheduled_posted',
                        content_schedule_datetime = NULL,
                        created_at = CURRENT_TIMESTAMP
                    WHERE content_id = ? 
                    AND content_type = ?
                    AND notification_type = 'content_scheduled'
                ");
                $updateNotification->execute([$content_id, $type]);
                
                // Update notification counts for all relevant parties
                // For content owner
                $stmtUpdateUserWhoPosted = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1, 
                        last_post_date = CURRENT_TIMESTAMP 
                    WHERE user_id = ?
                ");
                $stmtUpdateUserWhoPosted->execute([$user_id]);

                // For department admins
                $stmtUpdateAdminNotificationCount = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE user_type = 'Admin' 
                    AND department = ? 
                    AND user_id != ?
                ");
                $stmtUpdateAdminNotificationCount->execute([$department, $user_id]);

                // For super admins
                $stmtUpdateSuperAdminNotificationCount = $this->pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE user_type = 'Super Admin' 
                    AND user_id != ?
                ");
                $stmtUpdateSuperAdminNotificationCount->execute([$user_id]);
        
                // Broadcast notification update
                $this->broadcastNotification('new_notification');
        
                $response = [
                    'action' => 'post_now_content', 
                    'success' => true,
                    'message' => ucfirst($type) . ' has been posted immediately',
                    'type' => $type,
                    $idField => $content_id
                ];
                
                echo ucfirst($type) . " posted immediately!\n";
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to post ' . $type . ' immediately'
                ];
            }
        
            // Send response to all clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }
        
        // For Viewing Content
        else if (isset($data['action']) && $data['action'] === 'view_content') {
            $content_id = $data['content_id'];
            $content_type = $data['content_type'];
            $user_id = $data['user_id'];
        
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id', 'fields' => ['announcement_id', 'announcement_author_id', 'announcement_body', 'created_datetime', 'expiration_datetime', 'schedule_datetime', 'media_path', 'display_time', 'tv_id']],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id', 'fields' => ['event_id', 'event_author_id', 'event_body', 'created_datetime', 'expiration_datetime', 'schedule_datetime', 'media_path', 'display_time', 'tv_id']],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id', 'fields' => ['news_id', 'news_author_id', 'news_body', 'created_datetime', 'expiration_datetime', 'schedule_datetime', 'media_path', 'display_time', 'tv_id']],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id', 'fields' => ['promaterial_id', 'promaterial_author_id', 'created_datetime', 'expiration_datetime', 'schedule_datetime', 'media_path', 'display_time', 'tv_id']],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id', 'fields' => ['peo_id', 'peo_author_id', 'peo_title', 'peo_description', 'peo_subdescription', 'created_datetime', 'display_time', 'tv_id']],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id', 'fields' => ['so_id', 'so_author_id', 'so_title', 'so_description', 'so_subdescription', 'created_datetime', 'display_time', 'tv_id']]
            ];
        
            if (isset($validTypes[$content_type])) {
                $table = $validTypes[$content_type]['table'];
                $idField = $validTypes[$content_type]['idField'];
                $fields = implode(', ', $validTypes[$content_type]['fields']);

                $queryTvName = "SELECT ST.tv_name as tv_display
                        FROM {$table} CT
                        LEFT JOIN smart_tvs_tb ST ON CT.tv_id = ST.tv_id
                        WHERE CT.tv_id = ST.tv_id";

                $statement = $this->pdo->prepare($queryTvName);
                $statement->execute();
                $result = $statement->fetch(PDO::FETCH_ASSOC);
        
                $stmt = $this->pdo->prepare("SELECT $fields FROM $table WHERE $idField = ? AND {$content_type}_author_id = ?");
                $stmt->execute([$content_id, $user_id]);
                $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
                if ($content) {
                    $response = [
                        'action' => 'view_content',
                        'success' => true,
                        'content_type' => $content_type,
                        'content' => $content,
                        'idField' => $idField,
                        'tv_display' => $result['tv_display']
                    ];
                } else {
                    $response = [
                        'action' => 'view_content',
                        'success' => false,
                        'message' => 'Content not found'
                    ];
                }
            } else {
                $response = [
                    'action' => 'view_content',
                    'success' => false,
                    'message' => 'Invalid content type'
                ];
            }
        
            $from->send(json_encode($response));
        }

        // For Creating New Feature only
        else if (isset($data['action']) && $data['action'] === 'post_new_feature') {
            try {
                $featureName = $data['name_of_feature'];
                $tableName = strtolower(str_replace(' ', '_', $featureName)) . '_tb';

                // Check for reserved feature names
                $reservedNames = ['announcement', 'event', 'news', 'promaterial', 'promotional material', 'promotional materials', 'peo', 'program educational objectives', 'so', 'student outcomes', 'orgchart', 'organizational chart'];
                if (in_array(strtolower($featureName), $reservedNames)) {
                    $response = [
                        'success' => false,
                        'message' => 'This feature name is reserved. Please choose a different name.',
                        'messageType' => 'error'
                    ];
                    $from->send(json_encode($response));
                    return;
                }
                
                // Check for invalid characters in feature name
                if (!preg_match('/^[a-zA-Z\s]+$/', $featureName)) {
                    $response = [
                        'success' => false,
                        'message' => 'Feature name can only contain letters and spaces.',
                        'messageType' => 'error'
                    ];
                    $from->send(json_encode($response));
                    return;
                }

                // Check if feature name already exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM features_tb WHERE LOWER(feature_name) = LOWER(?)");
                $stmt->execute([strtolower($featureName)]);
                if ($stmt->fetchColumn() > 0) {
                    $response = [
                        'success' => false,
                        'message' => 'A feature with this name already exists. Please choose a different name.',
                        'messageType' => 'error'
                    ];
                    $from->send(json_encode($response));
                    return;
                }

                // Check for duplicate input names
                $inputNames = [];
                foreach ($data['inputs'] as $input) {
                    $inputName = strtolower($input['name']);
                    if (in_array($inputName, $inputNames)) {
                        $response = [
                            'success' => false,
                            'message' => 'Duplicate input names found. Each input must have a unique name.',
                            'messageType' => 'error'
                        ];
                        $from->send(json_encode($response));
                        return;
                    }
                    $inputNames[] = $inputName;
                }
                
                // If validation passes, get featureId and contentType
                $featureId = strtolower(str_replace(' ', '_', $featureName));
                $contentType = strtolower(str_replace(' ', '_', $featureName));
                
                // Create the new table
                $sql = "CREATE TABLE IF NOT EXISTS $tableName (
                    {$featureId}_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    {$featureId}_author_id INT(11) NOT NULL,
                    department VARCHAR(255) NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    user_type VARCHAR(255) NOT NULL,
                    display_time INT(100) NOT NULL,
                    tv_id INT(100) NOT NULL,
                    category VARCHAR(255) NOT NULL,
                    isArchived TINYINT(1) DEFAULT 0,
                    isTrashed TINYINT(1) DEFAULT 0,
                    schedule_datetime DATETIME DEFAULT NULL,
                    created_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (tv_id) REFERENCES smart_tvs_tb(tv_id),
                    FOREIGN KEY ({$featureId}_author_id) REFERENCES users_tb(user_id)
                )";
                $this->pdo->exec($sql);
            
                // Add columns based on inputs
                foreach ($data['inputs'] as $input) {
                    $columnName = strtolower(str_replace(' ', '_', $input['name']));
                    $columnType = ($input['type'] == 'text') ? 'TEXT' : 'VARCHAR(255)';
                    $nullableString = ($input['required'] == 'yes') ? 'NOT NULL' : 'NULL';
                    $sql = "ALTER TABLE $tableName ADD COLUMN $columnName $columnType $nullableString";
                    $this->pdo->exec($sql);
        
                    // If this input is of type 'media', add a media_path column
                    if ($input['type'] == 'media') {
                        $sql = "ALTER TABLE $tableName ADD COLUMN media_path VARCHAR(255) NULL";
                        $this->pdo->exec($sql);
                    }
                }
            
                // Add other necessary columns
                $sql = "ALTER TABLE $tableName 
                        ADD COLUMN status VARCHAR(255) NOT NULL";
                
                if ($data['content_has_expiration_date'] == 'yes') {
                    $sql .= ", ADD COLUMN expiration_datetime DATETIME";
                }

                $this->pdo->exec($sql);
            
                // Insert the feature details into a features_tb
                $fileName = $this->createFeaturePhpFile($featureName, $data['inputs'], $data['selected_icon'], $data['content_has_expiration_date']);
                $stmt = $this->pdo->prepare("
                    INSERT INTO features_tb 
                    (feature_name, file_name, type, department, icon, has_expiration) 
                    VALUES 
                    (:feature_name, :file_name, :type, :department, :icon, :has_expiration)
                ");
                $stmt->execute([
                    ':feature_name' => $featureName,
                    ':file_name' => $fileName,
                    ':type' => $contentType,
                    ':department' => $data['department'],
                    ':icon' => $data['selected_icon'],
                    ':has_expiration' => $data['content_has_expiration_date']
                ]);
            
                // Insert user types that can access this feature
                $featureId = $this->pdo->lastInsertId();
                foreach ($data['user_types'] as $userType) {
                    $stmt = $this->pdo->prepare("INSERT INTO feature_user_types (feature_id, user_type) VALUES (?, ?)");
                    $stmt->execute([$featureId, $userType]);
                }

                // Create fetch file
                // $fetchFileName = 'fetch_' . $contentType . '.php';
                // $fetchFilePath = dirname(__DIR__, 1) . '/database/' . $fetchFileName;
                // $fetchFileContent = $this->generateFetchFileContent($tableName, $contentType);
                // file_put_contents($fetchFilePath, $fetchFileContent);

                // Fetch all tv_ids from smart_tvs_tb
                $stmtFetchTVs = $this->pdo->prepare("SELECT tv_id FROM smart_tvs_tb WHERE tv_department = ?");
                $stmtFetchTVs->execute([$data['department']]);
                $tvIds = $stmtFetchTVs->fetchAll(PDO::FETCH_COLUMN);

                // Insert new container into containers_tb for each TV
                $containerName = ucfirst($featureName);
                $visible = 0;
                $stmtInsertContainer = $this->pdo->prepare("
                    INSERT INTO containers_tb 
                    (container_name, type, tv_id, visible) 
                    VALUES 
                    (?, ?, ?, ?)
                ");
                
                foreach ($tvIds as $tvId) {
                    try {
                        $stmtInsertContainer->execute([$containerName, $contentType, $tvId, $visible]);
                        error_log("Container inserted for TV ID {$tvId}");
                    } catch (PDOException $e) {
                        error_log("Error inserting container for TV ID {$tvId}: " . $e->getMessage());
                    }
                }

                $response = ['success' => true, 'message' => 'New feature added successfully', 'fileName' => $fileName, 'containerName' => $containerName];
                echo "New feature '{$featureName}' added successfully with file name: {$fileName} and container name: {$containerName}!\n";
            } catch (PDOException $e) {
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();

                if ($errorCode == '42S01') {
                    $response = ['success' => false, 'message' => 'A feature with this name already exists. Please choose a different name.'];
                } elseif ($errorCode == '42000') {
                    $response = ['success' => false, 'message' => 'Invalid feature name. Please avoid using special characters.'];
                } else {
                    $response = ['success' => false, 'message' => 'An error occurred while creating the feature. Please try again.'];
                }
                
                error_log("SQL Error: " . $errorMessage);
                echo "Error creating new feature: {$errorMessage}\n";
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
                error_log("Unexpected Error: " . $e->getMessage());
                echo "Unexpected error creating new feature: {$e->getMessage()}\n";
            }
            
            $from->send(json_encode($response));
        } 

        else if (isset($data['action']) && $data['action'] === 'delete_feature') {
            try {
                $featureId = $data['feature_id'];
                
                // 1. Get feature information before deletion
                $stmt = $this->pdo->prepare("
                    SELECT feature_name, file_name, type, department 
                    FROM features_tb 
                    WHERE feature_id = ?
                ");
                $stmt->execute([$featureId]);
                $feature = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$feature) {
                    throw new Exception("Feature not found");
                }

                // Get TV IDs associated with this feature's department
                $stmt = $this->pdo->prepare("
                    SELECT tv_id 
                    FROM smart_tvs_tb 
                    WHERE tv_department = ?
                ");
                $stmt->execute([$feature['department']]);
                $tvIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
                // 2. Delete the PHP file
                $filePath = dirname(__DIR__, 1) . '/' . $feature['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
        
                // 3. Drop the feature's database table
                $tableName = strtolower(str_replace(' ', '_', $feature['type'])) . '_tb';
                $this->pdo->exec("DROP TABLE IF EXISTS `$tableName`");
        
                // 4. Delete containers associated with this feature
                $stmt = $this->pdo->prepare("
                    DELETE FROM containers_tb 
                    WHERE type = ?
                ");
                $stmt->execute([$feature['type']]);
        
                // 5. Delete feature user types
                $stmt = $this->pdo->prepare("
                    DELETE FROM feature_user_types 
                    WHERE feature_id = ?
                ");
                $stmt->execute([$featureId]);
        
                // 6. Delete the feature from features_tb
                $stmt = $this->pdo->prepare("
                    DELETE FROM features_tb 
                    WHERE feature_id = ?
                ");
                $stmt->execute([$featureId]);
        
                $response = [
                    'action' => 'delete_feature',
                    'success' => true,
                    'feature_id' => $featureId,
                    'tv_ids' => $tvIds,
                    'message' => 'Feature deleted successfully'
                ];
        
            } catch (Exception $e) {
                $response = [
                    'action' => 'delete_feature',
                    'success' => false,
                    'feature_id' => $featureId,
                    'message' => 'Error deleting feature: ' . $e->getMessage()
                ];
        
                error_log("Error deleting feature: " . $e->getMessage());
            }
        
            // Send response to the client
            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'reset_notification_count') {
            $user_id = $data['user_id'];
            
            // Reset the notification count for the user
            $stmt = $this->pdo->prepare("UPDATE users_tb SET notification_count = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
    
            $success = $stmt->rowCount() > 0;
            $response = ['action' => 'reset_notification_count', 'success' => $success];
            
            if ($success) {
                echo "Notification count reset for user {$user_id}\n";
            } else {
                echo "Failed to reset notification count for user {$user_id}\n";
            }
    
            $from->send(json_encode($response));
        }

        // Broadcast the message to all connected clients
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send(json_encode($data));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

//     private function generateFetchFileContent($tableName, $contentType) {
//         return "<?php
// // fetch_{$contentType}.php
// // Database Connection
// \$pdo = new PDO(\"mysql:host=localhost;dbname=smart_tv_cms_db\", \"root\", \"\");

// // Fetch {$tableName} from the database
// \$statement = \$pdo->query(\"SELECT * FROM {$tableName}\");
// \${$contentType} = \$statement->fetchAll(PDO::FETCH_ASSOC);

// // Return JSON response
// header('Content-Type: application/json');
// echo json_encode(\${$contentType});
// ";
//     }

    private function createFeaturePhpFile($featureName, $inputs, $icon, $hasExpiration) {
        $fileName = 'form_' . strtolower(str_replace(' ', '_', $featureName)) . '.php';
        $filePath = dirname(__DIR__, 1) . '/' . $fileName; // This goes up two levels from MyApp folder
        $content = $this->generateFeaturePhpContent($featureName, $inputs, $icon, $hasExpiration);
        $result = file_put_contents($filePath, $content);
        
        if ($result === false) {
            error_log("Failed to create file: " . $filePath);
        } else {
            error_log("Successfully created file: " . $filePath);
        }
        
        return $fileName;
    }

    private function generateFeaturePhpContent($featureName, $inputs, $icon, $hasExpiration) {
        $typeVar = strtolower(str_replace(' ', '_', $featureName));
        // Generate the PHP content here. 
        $content = 
"<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Check if user has access to this feature
include 'new_features/newfeature_check_feature_access.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

// Check if we're in edit mode and have media data
\$isEditMode = isset(\$_GET['action']) && \$_GET['action'] === 'edit';
\$contentData = null;

if (\$isEditMode && isset(\$_GET['{$typeVar}_id'])) {
    // Fetch the content data
    \$stmt = \$conn->prepare(\"SELECT * FROM {$typeVar}_tb WHERE {$typeVar}_id = ? AND tv_id = ?\");
    \$stmt->bind_param(\"ii\", \$_GET['{$typeVar}_id'], \$_GET['tv_id']);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$contentData = \$result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css\" />
    <link rel=\"icon\" type=\"image/png\" href=\"images/usc_icon.png\">
    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
    <link href=\"https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css\">
    <link href=\"https://fonts.googleapis.com/css2?family=Questrial&display=swap\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"style.css\">
    <link href=\"https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css\" rel=\"stylesheet\" />
    <script src=\"https://code.jquery.com/jquery-3.2.1.min.js\"></script>
    <title><?php echo \$isEditMode ? 'Edit {$featureName}' : 'Create a {$featureName}'; ?></title>
</head>
<body>
    <div class=\"main-section\" id=\"all-content\">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class=\"main-container\">
            <div class=\"column1\">
                <div class=\"content-inside-form\">
                    <div class=\"content-form\">
                        <nav aria-label=\"breadcrumb\">
                            <ol class=\"breadcrumb\" style=\"background: none\">
                            <?php if (\$isEditMode): ?>
                                <li class=\"breadcrumb-item\"><a href=\"user_home.php?pageid=UserHome?userId=<?php echo \$user_id; ?>''<?php echo \$full_name; ?>\" style=\"color: #264B2B\">Home</a></li>
                                <li class=\"breadcrumb-item\">
                                    <a href=\"tv_contents.php?tvId=<?php echo \$_GET['tv_id']; ?>&isIframe=true\" style=\"color: #264B2B\">
                                        <?php 
                                        // Fetch TV name based on tv_id
                                        \$stmt = \$conn->prepare(\"SELECT tv_name FROM smart_tvs_tb WHERE tv_id = ?\");
                                        \$stmt->bind_param(\"i\", \$_GET['tv_id']);
                                        \$stmt->execute();
                                        \$result = \$stmt->get_result();
                                        \$tv = \$result->fetch_assoc();
                                        echo htmlspecialchars(\$tv['tv_name']); 
                                        ?>
                                    </a>
                                </li>
                                <li class=\"breadcrumb-item active\" aria-current=\"page\">Edit</li>
                            <?php else: ?>
                                <li class=\"breadcrumb-item\">
                                    <a href=\"create_post.php?pageid=CreatePost?userId=<?php echo \$user_id; ?>''<?php echo \$full_name; ?>\" style=\"color: #264B2B\">Create Post</a>
                                </li>
                                <li class=\"breadcrumb-item active\" aria-current=\"page\">{$featureName} Form</li>
                            <?php endif; ?>
                            </ol>
                        </nav>
                        <form id=\"" . strtolower(str_replace(' ', '_', $featureName)) . "Form\" enctype=\"multipart/form-data\" class=\"main-form\">
                            <?php include('error_message.php'); ?>
                            <input type=\"hidden\" name=\"type\" value=\"" . strtolower(str_replace(' ', '_', $featureName)) . "\">
                            <?php if (\$isEditMode && isset(\$_GET['{$typeVar}_id'])): ?>
                                <input type=\"hidden\" name=\"{$typeVar}_id\" value=\"<?php echo htmlspecialchars(\$_GET['{$typeVar}_id']); ?>\">
                                <input type=\"hidden\" name=\"action_value\" value=\"edit\">
                            <?php endif; ?>
                            <h1 style=\"text-align: center\">$featureName Form</h1>
                            ";

                            $quillInitialization = "";
                            $quillContentAssignment = "";
                            $hasImageInput = false;

                            // Add input fields based on the feature's inputs
                            foreach ($inputs as $input) {
                                $content .= $this->generateInputField($input);
                                if ($input['type'] == 'text') {
                                    $inputName = strtolower(str_replace(' ', '_', $input['name']));
                                    $quillInitialization .= "
                                        var {$inputName}Quill = new Quill('#{$inputName}', {
                                            theme: 'snow',
                                            placeholder: 'Enter {$input['name']}',
                                            modules: {
                                                toolbar: [
                                                    ['bold', 'italic', 'underline'],
                                                    ['link'],
                                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                                                ]
                                            }
                                        });
                                        // If edit mode, populate the editor
                                        const existing{$inputName}Content = document.querySelector('#existing_{$inputName}_content');
                                        if (existing{$inputName}Content) {
                                            {$inputName}Quill.root.innerHTML = existing{$inputName}Content.value;
                                        }
                                    ";
                                    $quillContentAssignment .= "
                                        document.getElementById('{$inputName}HiddenInput').value = {$inputName}Quill.root.innerHTML;
                                    ";
                                } elseif ($input['type'] == 'media') {
                                    $hasImageInput = true;
                                }
                            }
                        
                            if ($hasImageInput) {
                                $content .= "<?php include('misc/php/upload_preview_media.php')?>\n";
                            }
                        
                            $content .= "
                            <div class=\"form-row\">
                            <?php 
                                \$type = '{$typeVar}';
                            ?>\n
                            ";

                            if ($hasExpiration === 'yes') {
                                $content .= "<?php include('misc/php/expiration_date.php')?>\n";
                            }

                            $content .= "
                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                            </div>
                            <div style=\"display: flex; flex-direction: row; margin-left: auto; margin-top: 10px\">
                                <div>
                                    <button type=\"button\" name=\"preview\" id=\"previewButton\" class=\"preview-button\" style=\"margin-right: 0\" onclick=\"validateAndOpenNewFeaturePreviewModal()\">
                                        <i class=\"fa fa-eye\" style=\"padding-right: 5px\"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php include('new_features/newfeature_preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('new_features/newfeature_error_modal.php') ?>
    <?php include('new_features/newfeature_success_modal.php') ?>
    <script src=\"https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js\"></script>
    <script src=\"new_features/newfeature_wsform_submission.js\"></script>
    <script>
        const containers = <?php echo json_encode(\$containers); ?>;
        const tvNames = <?php echo json_encode(\$tv_names); ?>; 

        document.addEventListener('DOMContentLoaded', function() {
            $quillInitialization

            const form = document.getElementById('" . strtolower(str_replace(' ', '_', $featureName)) . "Form');
            form.onsubmit = function(e) {
                e.preventDefault();
                submitFormViaWebSocket();
            };
        });
    </script>
</body>
</html>";
    
        return $content;
    }

    private function generateInputField($input) {
        $name = strtolower(str_replace(' ', '_', $input['name']));
        $label = ucfirst($input['name']);
        $required = $input['required'] == 'yes' ? 'required' : '';
    
        if ($input['type'] == 'text') {
            return "
                <div class=\"floating-label-container\">
                    <div id=\"quillEditorContainer_{$name}\" class=\"quill-editor-container-newfeature\">
                        <label for=\"quillEditorContainer_{$name}\" style=\"position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold\">{$label}</label>
                        <div id=\"{$name}\" style=\"height: 150px;\"></div>
                    </div>
                    <input type=\"hidden\" name=\"{$name}\" id=\"{$name}HiddenInput\">
                    <?php if (\$isEditMode && \$contentData): ?>
                        <input type=\"hidden\" id=\"existing_{$name}_content\" value=\"<?php echo htmlspecialchars(\$contentData['{$name}']); ?>\">
                    <?php endif; ?>
                </div>";
        } else {
            
        }
    }

    // Helper function to broadcast notifications to all clients
    private function broadcastNotification($action) {
        foreach ($this->clients as $client) {
            $client->send(json_encode(['action' => $action]));
        }
    }

    // private function saveLayoutToDatabase($tv_id, $layout) {
    //     $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
    
    //     // Prepare the SQL statement for updating
    //     $stmt = $pdo->prepare("
    //         UPDATE containers_tb
    //         SET position_order = ?, xaxis = ?, yaxis = ?
    //         WHERE tv_id = ? AND container_id = ?
    //     ");

    //     foreach ($layout as $index => $item) {
    //         // Execute the update for each container
    //         $stmt->execute([$index + 1, $item['x'], $item['y'], $tv_id, $item['id']]);
    //     }
    // }
    
    // private function loadLayoutFromDatabase($tv_id) {
    //     $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
        
    //     $stmt = $pdo->prepare("SELECT container_id FROM containers_tb WHERE tv_id = ? ORDER BY position_order ASC");
    //     $stmt->execute([$tv_id]);
    
    //     $layout = [];
    //     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //         $layout[] = [
    //             'id' => $row['container_id'],
    //         ];
    //     }
    
    //     // Debug: Check if the layout is populated correctly
    //     // if (empty($layout)) {
    //     //     error_log("No layout data found for TV ID: " . $tv_id);
    //     // } else {
    //     //     error_log("Layout data found for TV ID: " . $tv_id . ": " . json_encode($layout));
    //     // }
    
    //     return $layout;
    // }    
}