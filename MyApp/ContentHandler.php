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
        if (isset($data['action']) && $data['action'] === 'delete') {
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
                    $response = ['action' => 'delete', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }

            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];
            $id = $data[$idField];
            $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE {$idField} = ?");
            $stmt->execute([$id]);
                
            $deleted = $stmt->rowCount() > 0;
            $response = ['action' => 'delete', 'success' => $deleted, 'type' => $type, $idField => $id];
            echo "{$type} deleted!\n";

            // Notify the client who sent the request and all connected clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'archive') {
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
                    $response = ['action' => 'archive', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }
            
            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];
            $id = $data[$idField];
                
            // Update isCancelled to 1
            $stmt = $this->pdo->prepare("UPDATE {$table} SET isCancelled = 1 WHERE {$idField} = ?");
            $stmt->execute([$id]);

            $archived = $stmt->rowCount() > 0;
            $response = ['action' => 'archive', 'success' => $archived, 'type' => $type, $idField => $id];
            echo "{$type} archived!\n";
            
            // Notify the client who sent the request and all connected clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'unarchive') {
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
                    $response = ['action' => 'unarchive', 'success' => false, 'message' => 'Invalid type specified'];
                    echo "Invalid type specified\n";
                    $from->send(json_encode($response));
                    return;
                }
            }
            
            $table = $validTypes[$type]['table'];
            $idField = $validTypes[$type]['idField'];
            $id = $data[$idField];
                
            // Update isCancelled to 1
            $stmt = $this->pdo->prepare("UPDATE {$table} SET isCancelled = 0 WHERE {$idField} = ?");
            $stmt->execute([$id]);

            $archived = $stmt->rowCount() > 0;
            $response = ['action' => 'archive', 'success' => $archived, 'type' => $type, $idField => $id];
            echo "{$type} archived!\n";
            
            // Notify the client who sent the request and all connected clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }

        else if (isset($data['action']) && $data['action'] === 'unarchive_and_update_expiration') {
            $validTypes = [
                'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id'],
                'event' => ['table' => 'events_tb', 'idField' => 'event_id'],
                'news' => ['table' => 'news_tb', 'idField' => 'news_id'],
                'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id'],
                'peo' => ['table' => 'peo_tb', 'idField' => 'peo_id'],
                'so' => ['table' => 'so_tb', 'idField' => 'so_id']
            ];
            $type = $data['type'] ?? null;
            
            if (isset($validTypes[$type])) {
                $table = $validTypes[$type]['table'];
                $idField = $validTypes[$type]['idField'];
                $id = $data[$idField];

                $isCancelled = 0;
                $expirationDateTime = $data['expiration_datetime'] ?? null;
                
                // Prepare the update query
                $stmt = $this->pdo->prepare("UPDATE {$table} SET isCancelled = ?, expiration_datetime = ? WHERE {$idField} = ?");
                
                // Bind parameters and execute query
                $stmt->execute([$isCancelled, $expirationDateTime, $id]);
                
                // Fetch updated data
                $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE {$idField} = ?");
                $stmt->execute([$id]);
                $postData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($postData) {
                    $response = ['action' => 'unarchive_and_update_expiration', 'success' => true, 'type' => $type, 'data' => $postData] + $postData;
                    echo "{$type} unarchived and expiration updated!\n";
                } else {
                    $response = ['action' => 'unarchive_and_update_expiration', 'success' => false, 'message' => 'Failed to fetch updated data'];
                    echo "Failed to fetch updated data. Unarchive and expiration update failed.\n";
                }
            } else {
                $response = ['action' => 'unarchive_and_update_expiration', 'success' => false, 'message' => 'Invalid type specified'];
                echo "Invalid type specified. Unarchive and expiration update failed.\n";
            }
            
            // Notify the client who sent the request and all connected clients
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
            }
        }     

        else if (isset($data['action']) && $data['action'] === 'update') {
            $validTypes = [
                'announcement' => [
                    'table' => 'announcements_tb',
                    'idField' => 'announcement_id',
                    'fields' => ['announcement_body', 'announcement_author_id', 'expiration_datetime', 'display_time', 'tv_id'],
                    'mediaFolder' => 'announcement_media'
                ],
                'event' => [
                    'table' => 'events_tb',
                    'idField' => 'event_id',
                    'fields' => ['event_body', 'event_author_id', 'expiration_datetime', 'display_time', 'tv_id'],
                    'mediaFolder' => 'event_media'
                ],
                'news' => [
                    'table' => 'news_tb',
                    'idField' => 'news_id',
                    'fields' => ['news_body', 'news_author_id', 'expiration_datetime', 'display_time', 'tv_id'],
                    'mediaFolder' => 'news_media'
                ],
                'promaterial' => [
                    'table' => 'promaterials_tb',
                    'idField' => 'promaterial_id',
                    'fields' => ['promaterial_author_id', 'expiration_datetime', 'display_time', 'tv_id'],
                    'mediaFolder' => 'promaterial_media'
                ],
                'peo' => [
                    'table' => 'peo_tb',
                    'idField' => 'peo_id',
                    'fields' => ['peo_title', 'peo_description', 'peo_subdescription', 'peo_author_id', 'display_time', 'tv_id']
                ],
                'so' => [
                    'table' => 'so_tb',
                    'idField' => 'so_id',
                    'fields' => ['so_title', 'so_description', 'so_subdescription', 'so_author_id', 'display_time', 'tv_id']
                ]
            ];
        
            $type = $data['type'] ?? null;
        
            if (isset($validTypes[$type])) {
                $table = $validTypes[$type]['table'];
                $idField = $validTypes[$type]['idField'];
                $id = $data[$idField];
                $fields = $validTypes[$type]['fields'];
                if ($data['type'] !== 'peo' && $data['type'] !== 'peo') {
                    $mediaFolder = $validTypes[$type]['mediaFolder'];
                }
            
                $mediaFilename = null;
                if (isset($data['media']) && !empty($data['media'])) {
                    $base64Data = $data['media'];
                    $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                    $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
        
                    if (!file_exists($mediaFolder)) {
                        if (!mkdir($mediaFolder, 0777, true)) {
                            error_log("Failed to create directory: $mediaFolder");
                            $from->send(json_encode(['action' => 'update', 'success' => false, 'message' => 'Failed to create media folder']));
                            return;
                        }
                    }
        
                    $mediaFilename = "{$id}.{$fileExtension}";
                    $mediaPath = "{$mediaFolder}/{$mediaFilename}";
        
                    if (!file_put_contents($mediaPath, $mediaData)) {
                        error_log("Failed to save media file: $mediaPath");
                        $from->send(json_encode(['action' => 'update', 'success' => false, 'message' => 'Failed to save media file']));
                        return;
                    }
        
                    $fields[] = 'media_path';
                }
        
                $params = array_map(fn($field) => $data[$field] ?? null, $fields);
                if ($mediaFilename) {
                    $params[array_search('media_path', $fields)] = $mediaFilename;
                }
                $params[] = $id;
        
                $fieldAssignments = implode(', ', array_map(fn($field) => "$field = ?", $fields));
                $stmt = $this->pdo->prepare("UPDATE {$table} SET {$fieldAssignments} WHERE {$idField} = ?");
                if (!$stmt->execute($params)) {
                    error_log("Failed to execute update query: " . implode(", ", $stmt->errorInfo()));
                    $from->send(json_encode(['action' => 'update', 'success' => false, 'message' => 'Failed to execute update query']));
                    return;
                }
        
                $success = $stmt->rowCount() > 0;
                $response = ['action' => 'update', 'success' => $success, 'type' => $type, $idField => $id];
                echo $success ? "{$type} updated!\n" : "{$type} updated!\n";
        
                $notificationMethod = "send" . ucfirst($type) . "UpdateNotification";
                $notificationParams = array_merge([$from], $params);
                if (method_exists($this, $notificationMethod)) {
                    $this->$notificationMethod(...$notificationParams);
                }
            } else {
                $response = ['action' => 'update', 'success' => false, 'message' => 'Invalid type specified'];
                echo "Invalid type specified\n";
            }
        
            $from->send(json_encode($response));
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($response));
                }
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
                       'topbar_deviceid_font_color',
                       'topbar_deviceid_font_style',
                       'topbar_deviceid_font_family',
                       'topbar_time_font_color',
                       'topbar_time_font_style',
                       'topbar_time_font_family',
                       'topbar_date_font_color',
                       'topbar_date_font_style',
                       'topbar_date_font_family',
                       'topbar_position',
                    ];
            
            // Extract the tv_id and topbar_color details from the incoming data
            $tvId = $data['tv_id'] ?? null;
            $topbarColor = $data['topbar_hex_color'] ?? null;

            $topbarTvNameColor = $data['topbar_tvname_font_color'] ?? null;
            $topbarTvNameFontStyle = $data['topbar_tvname_font_style'] ?? null;
            $topbarTvNameFontFamily = $data['topbar_tvname_font_family'] ?? null;

            $topbarDeviceIdColor = $data['topbar_deviceid_font_color'] ?? null;
            $topbarDeviceIdFontStyle = $data['topbar_deviceid_font_style'] ?? null;
            $topbarDeviceIdFontFamily = $data['topbar_deviceid_font_family'] ?? null;

            $topbarTimeColor = $data['topbar_time_font_color'] ?? null;
            $topbarTimeFontStyle = $data['topbar_time_font_style'] ?? null;
            $topbarTimeFontFamily = $data['topbar_time_font_family'] ?? null;

            $topbarDateColor = $data['topbar_date_font_color'] ?? null;
            $topbarDateFontStyle = $data['topbar_date_font_style'] ?? null;
            $topbarDateFontFamily = $data['topbar_date_font_family'] ?? null;

            $topbarPosition = $data['topbar_position'] ?? null;
            
            if ($tvId !== null || $topbarColor !== null || $topbarTvNameColor !== null || $topbarDeviceIdColor !== null || $topbarTimeColor !== null || $topbarDateColor !== null) {
                // Prepare the parameters for the update statement
                $params = [$topbarColor, 
                           $topbarTvNameColor, 
                           $topbarTvNameFontStyle, 
                           $topbarTvNameFontFamily,
                           $topbarDeviceIdColor, 
                           $topbarDeviceIdFontStyle,
                           $topbarDeviceIdFontFamily,
                           $topbarTimeColor, 
                           $topbarTimeFontStyle,
                           $topbarTimeFontFamily,
                           $topbarDateColor, 
                           $topbarDateFontStyle,
                           $topbarDateFontFamily,
                           $topbarPosition,
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
                        'topbar_deviceid_font_color' => $topbarDeviceIdColor,
                        'topbar_deviceid_font_style' => $topbarDeviceIdFontStyle,
                        'topbar_deviceid_font_family' => $topbarDeviceIdFontFamily,
                        'topbar_time_font_color' => $topbarTimeColor,
                        'topbar_time_font_style' => $topbarTimeFontStyle,
                        'topbar_time_font_family' => $topbarTimeFontFamily,
                        'topbar_date_font_color' => $topbarDateColor,
                        'topbar_date_font_style' => $topbarDateFontStyle,
                        'topbar_date_font_family' => $topbarDateFontFamily,
                        'topbar_position' => $topbarPosition,
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
                $stmt = $this->pdo->prepare("UPDATE containers_tb SET xaxis = ?, yaxis = ?, width_px = ?, height_px = ? WHERE tv_id = ? AND container_id = ?");
        
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
                $status = ($user_type == 'Student') ? 'Pending' : 'Approved';

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
                        $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status) VALUES (?, 'user_registration', 'pending')");
                        $stmt->execute([$user_id]);

                        // Broadcast new notification to all clients
                        $this->broadcastNotification('new_notification');

                        // Send success message to client
                        $response = ['success' => true, 'data' => $data];
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

        else if (isset($data['action']) && $data['action'] === 'approve_user') {
            $user_id = $data['user_id'];

            // Retrieve full_name from session
            $evaluator_name = $data['full_name'];
        
            // Perform the deletion from the database
            $stmt = $this->pdo->prepare("UPDATE users_tb SET status = 'Approved', evaluated_by = ? WHERE user_id = ?");
            $stmt->execute([$evaluator_name, $user_id]);
            
            $user = $stmt->rowCount() > 0;

            if ($user) {
                // Update notification status
                $stmt = $this->pdo->prepare("UPDATE notifications_tb SET status = 'approved', evaluator_name = ?, notification_type = 'user_approved' WHERE user_id = ? AND notification_type = 'user_registration'");
                $stmt->execute([$evaluator_name, $user_id]);

                // Insert user_approved notification
                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, notification_type, status, evaluator_name) VALUES (?, 'user_approved_by_admin', 'approved', ?)");
                $stmt->execute([$user_id, $evaluator_name]);

                // Broadcast update notification to all clients
                $this->broadcastNotification('update_notification');

                $response = ['action' => 'approve_user', 'success' => true, 'user_id' => $user_id];
                echo "User approved and evaluated by: {$evaluator_name}\n";
            } else {
                $response = ['action' => 'approve_user', 'success' => false];
            }

            $from->send(json_encode($response));
        }

        else if (isset($data['action']) && $data['action'] === 'reject_user') {
            $user_id = $data['user_id'];
            $evaluated_message = isset($data['evaluated_message']) ? htmlspecialchars($data['evaluated_message']) : '';

            // Retrieve full_name from session
            $evaluator_name = $data['full_name'];
        
            // Perform the deletion from the database
            $stmt = $this->pdo->prepare("UPDATE users_tb SET status = 'Rejected', evaluated_by = ?, evaluated_message = ? WHERE user_id = ?");
            $stmt->execute([$evaluator_name, $evaluated_message, $user_id]);
            
            $user = $stmt->rowCount() > 0;

            if ($user) {
                // Update notification status
                $stmt = $this->pdo->prepare("UPDATE notifications_tb SET status = 'rejected', evaluator_name = ? WHERE user_id = ? AND notification_type = 'user_registration'");
                $stmt->execute([$evaluator_name, $user_id]);

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
            $userType = $data['user_type'];
            $department = $data['department'];

            // Perform the update in the database
            $stmt = $this->pdo->prepare("UPDATE users_tb SET user_type = ?, department = ? WHERE user_id = ?");
            $stmt->execute([$userType, $department, $userId]);

            $user = $stmt->rowCount() > 0;

            if ($user) {
                // Notify the client who sent the request
                $from->send(json_encode(['action' => 'edit_user', 'success' => true, 'user_id' => $userId]));

                // Notify all connected clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode(['action' => 'edit_user', 'success' => true, 'user_id' => $userId]));
                    }
                }
                echo "User details updated for user_id: {$userId}\n";
            } else {
                $from->send(json_encode(['action' => 'edit_user', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'delete_user') {
            $userId = $data['user_id'];

            // Perform the update in the database
            $stmt = $this->pdo->prepare("DELETE FROM users_tb WHERE user_id = ?");
            $stmt->execute([$userId]);

            $user = $stmt->rowCount() > 0;

            if ($user) {
                // Notify the client who sent the request
                $from->send(json_encode(['action' => 'delete_user', 'success' => true, 'user_id' => $userId]));

                // Notify all connected clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode(['action' => 'delete_user', 'success' => true, 'user_id' => $userId]));
                    }
                }
                echo "User deleted for user_id: {$userId}\n";
            } else {
                $from->send(json_encode(['action' => 'delete_user', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'add_user') {
            $full_name = $data['full_name'];
            $email = $data['email'];
            $password = $data['password'];
            $department = $data['department'];
            $user_type = $data['user_type'];
            $status = ($user_type == 'Student') ? 'Pending' : 'Approved';
            $datetime_registered = date('Y-m-d H:i:s');
        
            try {
                // Check if the email or full name already exists
                $stmt = $this->pdo->prepare("SELECT email, full_name FROM users_tb WHERE email = ? OR full_name = ?");
                $stmt->execute([$email, $full_name]);
                $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingUser) {
                    $message = [];
                    if ($existingUser['email'] === $email) {
                        $message[] = "Email already exists";
                    }
                    if ($existingUser['full_name'] === $full_name) {
                        $message[] = "Full name already exists";
                    }
                    $response = ["action" => "add_user", "success" => false, "message" => implode(" and ", $message) . "."];
                    $from->send(json_encode($response));
                    return;
                }

                // If email and full name don't exist, proceed with insertion
                $hashedPassword = md5($password);
        
                $statement = $this->pdo->prepare(
                    "INSERT INTO users_tb 
                    (full_name, email, password, department, user_type, status, datetime_registered) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $success = $statement->execute([
                    $full_name, $email, $hashedPassword, $department, $user_type, $status, $datetime_registered
                ]);
        
                if ($success) {
                    $user_id = $this->pdo->lastInsertId();
        
                    // Prepare the complete data to send to the clients
                    $responseData = [
                        'user_id' => $user_id,
                        'full_name' => $full_name,
                        'email' => $email,
                        'department' => $department,
                        'status' => $status,
                        'user_type' => $user_type,
                        'datetime_registered' => $datetime_registered
                    ];
        
                    // Send success message to client
                    $response = ['action' => 'add_user', 'success' => true, 'data' => $responseData];
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
                    $response = ["action" => "add_user", "success" => false, "message" => "Failed to insert data. Please try again."];
                    $from->send(json_encode($response));
                }
            } catch (\Exception $e) {
                $response = ["action" => "add_user", "success" => false, "message" => "Failed to insert data. Please try again. Error: " . $e->getMessage()];
                $from->send(json_encode($response));
            }
        }

        else if (isset($data['action']) && $data['action'] === 'add_multiple_users') {
            if (isset($data['csv_file']) && is_string($data['csv_file'])) {
                $csvContent = base64_decode($data['csv_file']);
            } else {
                // Handle the case where csv_file is not a string
                $from->send(json_encode(['action' => 'add_multiple_users', 'success' => false, 'message' => 'Invalid CSV file format']));
                return;
            }
            $lines = explode("\n", $csvContent);
            $addedCount = 0;
            $failedCount = 0;
        
            foreach ($lines as $line) {
                $userData = str_getcsv($line);
                if (count($userData) === 4) { // Ensure we have all required fields
                    $full_name = trim($userData[0]);
                    $email = trim($userData[1]);
                    $user_type = trim($userData[2]);
                    $department = trim($userData[3]);
                    $status = ($user_type == 'Student') ? 'Pending' : 'Approved';
                    $datetime_registered = date('Y-m-d H:i:s');
                    $password = bin2hex(random_bytes(4));  // Generates an 8-character random password
                    $hashedPassword = md5($password); // Consider using password_hash() for better security
        
                    try {
                        // Check if the email already exists
                        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users_tb WHERE email = ? OR full_name = ?");
                        $stmt->execute([$email, $full_name]);
                        $count = $stmt->fetchColumn();

                        if ($count > 0) {
                            $failedCount++;
                            continue;
                        }

                        $statement = $this->pdo->prepare(
                            "INSERT INTO users_tb 
                            (full_name, email, password, department, user_type, status, datetime_registered) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        $success = $statement->execute([
                            $full_name, $email, $hashedPassword, $department, $user_type, $status, $datetime_registered
                        ]);
        
                        if ($success) {
                            $addedCount++;
                            // TODO: Send email to the new user with their temporary password
                        } else {
                            $failedCount++;
                        }
                    } catch (\Exception $e) {
                        $failedCount++;
                        error_log("Failed to add user: " . $e->getMessage());
                    }
                } else {
                    $failedCount++;
                    echo "Failed to add user: " . $line . "\n";
                }
            }
        
            $response = [
                "action" => "add_multiple_users",
                "success" => true,
                "addedCount" => $addedCount,
                "failedCount" => $failedCount,
                "message" => "Multiple users processed. Added: $addedCount, Failed: $failedCount"
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

            // Perform the update in the database
            $stmt = $this->pdo->prepare("UPDATE smart_tvs_tb SET tv_name = ?, tv_brand = ? WHERE tv_id = ?");
            $stmt->execute([$tvName, $tvBrand, $tvId]);

            $tv = $stmt->rowCount() > 0;

            if ($tv) {
                // Notify the client who sent the request
                $from->send(json_encode(['action' => 'edit_smart_tv', 'success' => true, 'tv_id' => $tvId]));

                // Notify all connected clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode(['action' => 'edit_smart_tv', 'success' => true, 'tv_id' => $tvId]));
                    }
                }
                echo "TV details updated for tv_id: {$tvId}\n";
            } else {
                $from->send(json_encode(['action' => 'edit_smart_tv', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'delete_smart_tv') {
            $tvId = $data['tv_id'];

            // Perform the delete in the database
            $stmtDeleteSmartTV = $this->pdo->prepare("DELETE FROM smart_tvs_tb WHERE tv_id = ?");
            $stmtDeleteSmartTV->execute([$tvId]);

            $smart_tv = $stmtDeleteSmartTV->rowCount() > 0;

            if ($smart_tv) {
                // Notify the client who sent the request
                $from->send(json_encode(['action' => 'delete_smart_tv', 'success' => true, 'tv_id' => $tvId]));

                // Notify all connected clients about the update
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode(['action' => 'delete_smart_tv', 'success' => true, 'tv_id' => $tvId]));
                    }
                }
                echo "TV deleted for tv_id: {$tvId}\n";
            } else {
                $from->send(json_encode(['action' => 'delete_smart_tv', 'success' => false]));
            }
            return;
        }

        else if (isset($data['action']) && $data['action'] === 'approve_post') {
            $content_id = $data['content_id'];
            $content_type = $data['content_type'];
            $user_id = $data['user_id'];
            $evaluator_name = $data['full_name'];

            // Update the content status in the respective table
            $table = $content_type . 's_tb'; 
            $idField = $content_type . '_id';
            $stmtUpdateContentTable = $this->pdo->prepare("UPDATE $table SET status = 'Approved' WHERE $idField = ?");
            $stmtUpdateContentTable->execute([$content_id]);
        
            $content_updated = $stmtUpdateContentTable->rowCount() > 0;
        
            if ($content_updated) {
                // Update notification status
                $stmtUpdateNotificationTable = $this->pdo->prepare("UPDATE notifications_tb SET status = 'approved', notification_type = 'content_approved', evaluator_name = ? WHERE user_id = ? AND content_id = ? AND content_type = ?");
                $stmtUpdateNotificationTable->execute([$evaluator_name, $user_id, $content_id, $content_type]);

                // Create a new notification for the user
                $stmtInsertNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status, evaluator_name) VALUES (?, ?, ?, 'content_approved_by_admin', 'approved', ?)");
                $stmtInsertNotificationTable->execute([$user_id, $content_id, $content_type, $evaluator_name]);
    
                // Broadcast update notification to all clients
                $this->broadcastNotification('new_notification');

                echo "Notification sent to all clients\n";

                // Prepare the response
                $response = [
                    'action' => 'approve_post', 
                    'success' => true, 
                    'content_updated' => $content_updated
                ];
        
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
            $user_id = $data['user_id'];
            $evaluator_name = $data['full_name'];

            // Update the content status in the respective table
            $table = $content_type . 's_tb'; 
            $idField = $content_type . '_id';
            $stmtUpdateContentTable = $this->pdo->prepare("UPDATE $table SET status = 'Rejected' WHERE $idField = ?");
            $stmtUpdateContentTable->execute([$content_id]);
        
            $content_updated = $stmtUpdateContentTable->rowCount() > 0;
        
            if ($content_updated) {
                // Update notification status
                $stmtUpdateNotificationTable = $this->pdo->prepare("UPDATE notifications_tb SET status = 'rejected', notification_type = 'content_rejected', evaluator_name = ? WHERE user_id = ? AND content_id = ? AND content_type = ?");
                $stmtUpdateNotificationTable->execute([$evaluator_name, $user_id, $content_id, $content_type]);

                // Create a new notification for the user
                $stmtInsertNotificationTable = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status, evaluator_name) VALUES (?, ?, ?, 'content_rejected_by_admin', 'rejected', ?)");
                $stmtInsertNotificationTable->execute([$user_id, $content_id, $content_type, $evaluator_name]);
    
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

                    $isCancelled = 0;

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
                        'category', 'isCancelled', 'status'
                    ];
                    $values = [
                        $department, $user_type, $user_id, $tv_ids, $data['display_time'] ?? null,
                        $category, $isCancelled, $status
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
                            $filename = "{$id}.{$fileExtension}";
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

                $isCancelled = 0;

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
                    'category', 'isCancelled', 'status'
                ];
                $values = [
                    $department, $user_type, $user_id, null, $data['display_time'] ?? null,
                    $category, $isCancelled, $status
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
                        $filename = "{$id}.{$fileExtension}";
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
                    
                        $isCancelled = 0;
                    
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
                            'category', 'isCancelled'
                        ];
                        $values = [
                            $department, $user_type, $user_id, $tv_ids, $data['display_time'],
                            $category, $isCancelled
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
                            $values[] = $data['schedule_datetime'];
                        } elseif ($data['type'] === 'news') {
                            $fields[] = 'news_body';
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            $values[] = $data['news_body'];
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'];
                        } elseif ($data['type'] === 'announcement') {
                            $fields[] = 'announcement_body';
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            $values[] = $data['announcement_body'];
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'];
                        } elseif ($data['type'] === 'promaterial') {
                            $fields[] = 'expiration_datetime';
                            $fields[] = 'schedule_datetime';
                            // No additional fields for promaterial
                            $values[] = $data['expiration_datetime'];
                            $values[] = $data['schedule_datetime'];
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

                            if ($user_type == 'Student' || $user_type == 'Faculty') {
                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status) VALUES (?, ?, ?, 'content_post', 'pending')");
                                $stmt->execute([$user_id, $data[$idField], $data['type']]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            } else if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status) VALUES (?, ?, ?, 'content_post', 'approved')");
                                $stmt->execute([$user_id, $data[$idField], $data['type']]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            }
                    
                            if (!empty($data['media'])) {
                                $base64Data = $data['media'];
                                $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                                $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                                $filename = "{$id}.{$fileExtension}";
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
                } else {
                    foreach ($data['tv_ids'] as $tv_ids) { 
                        $type = $data['type'] ?? null;
                        $expiration_datetime = $data['expiration_datetime'] ?? null;
                        
                        echo "Type: $type\n";
                        $status = ($user_type == 'Admin') ? 'Approved' : 'Pending';
                        $category = ucfirst($type); // Capitalize the first letter of the type
            
                        $isCancelled = 0;
            
                        // Determine media folder
                        $mediaFolder = $type . '_media';
                        if (!file_exists($mediaFolder)) {
                            mkdir($mediaFolder, 0777, true);
                        }
            
                        $table = $type . '_tb';
                        $idField = $type . '_id';
                        $authorField = $type . '_author_id';
            
                        // Common fields for all types
                        $fields = [
                            'department', 'user_type', $authorField, 'tv_id', 'display_time',
                            'category', 'isCancelled', 'status',
                            'expiration_datetime', 'type'
                        ];

                        $values = [
                            $department, $user_type, $user_id, $tv_ids, $data['display_time'],
                            $category, $isCancelled, $status,
                            $expiration_datetime, $type
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
            
                        // Add type-specific fields
                        foreach ($data as $key => $value) {
                            if (!in_array($key, ['action', 'type', 'tv_id[]', 'tv_ids', 'expiration_datetime', 'display_time', 'media'])) {
                                $fields[] = $key;
                                $values[] = is_array($value) ? json_encode($value) : $value;
                            }
                        }
            
                        $fieldList = implode(', ', $fields);
                        $placeholderList = implode(', ', array_fill(0, count($fields), '?'));

                        echo "Field List: " . $fieldList . "\n";
                        echo "Placeholder List: " . $placeholderList . "\n";
                        echo "Values: " . implode(', ', $values) . "\n";

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
                            
                            if ($user_type == 'Student' || $user_type == 'Faculty') {
                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status) VALUES (?, ?, ?, 'content_post', 'pending')");
                                $stmt->execute([$user_id, $data[$idField], $data['type']]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            } else if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                                // Insert notification for new content post
                                $stmt = $this->pdo->prepare("INSERT INTO notifications_tb (user_id, content_id, content_type, notification_type, status) VALUES (?, ?, ?, 'content_post', 'approved')");
                                $stmt->execute([$user_id, $data[$idField], $data['type']]);
                    
                                // Broadcast update notification to all clients
                                $this->broadcastNotification('new_notification');
                                echo "Notification sent to all clients\n";
                            }
            
                            if (!empty($data['media'])) {
                                $base64Data = $data['media'];
                                $mediaData = base64_decode(preg_replace('#^data:video/\w+;base64,|^data:image/\w+;base64,#i', '', $base64Data));
                                $fileExtension = strpos($base64Data, 'data:video') === 0 ? 'mp4' : 'png';
                                $filename = "{$id}.{$fileExtension}";
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

        else if (isset($data['action']) && $data['action'] === 'post_new_feature') {
            try {
                $featureName = $data['name_of_feature'];
                $tableName = strtolower(str_replace(' ', '_', $featureName)) . '_tb';
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
                    isCancelled TINYINT(1) DEFAULT 0,
                    created_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
                )";
                $this->pdo->exec($sql);
            
                // Add columns based on inputs
                foreach ($data['inputs'] as $input) {
                    $columnName = strtolower(str_replace(' ', '_', $input['name']));
                    $columnType = ($input['type'] == 'text') ? 'TEXT' : 'VARCHAR(255)';
                    $nullableString = ($input['required'] == 'yes') ? 'NOT NULL' : 'NULL';
                    $sql = "ALTER TABLE $tableName ADD COLUMN $columnName $columnType $nullableString";
                    $this->pdo->exec($sql);
                }
            
                // Add other necessary columns
                $sql = "ALTER TABLE $tableName 
                        ADD COLUMN status VARCHAR(255) NOT NULL";
                
                if ($data['content_has_expiration_date'] == 'yes') {
                    $sql .= ", ADD COLUMN expiration_datetime DATETIME";
                }
                
                if ($data['require_content_approval'] == 'yes') {
                    $sql .= ", ADD COLUMN evaluated_by INT(11),
                            ADD COLUMN evaluated_message TEXT";
                }
                
                $this->pdo->exec($sql);
            
                // Insert the feature details into a features_tb
                $fileName = $this->createFeaturePhpFile($featureName, $data['inputs'], $data['selectedIcon'], $data['content_has_expiration_date']);
                $stmt = $this->pdo->prepare("INSERT INTO features_tb (feature_name, file_name, type, department, icon, require_approval, has_expiration) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $featureName,
                    $fileName,
                    $contentType,
                    $data['department'],
                    $data['selectedIcon'],
                    $data['require_content_approval'],
                    $data['content_has_expiration_date']
                ]);
            
                // Insert user types that can access this feature
                $featureId = $this->pdo->lastInsertId();
                foreach ($data['user_types'] as $userType) {
                    $stmt = $this->pdo->prepare("INSERT INTO feature_user_types (feature_id, user_type) VALUES (?, ?)");
                    $stmt->execute([$featureId, $userType]);
                }

                // Create fetch file
                $fetchFileName = 'fetch_' . $contentType . '.php';
                $fetchFilePath = dirname(__DIR__, 1) . '/database/' . $fetchFileName;
                $fetchFileContent = $this->generateFetchFileContent($tableName, $contentType);
                file_put_contents($fetchFilePath, $fetchFileContent);

                // Fetch all tv_ids from smart_tvs_tb
                $stmtFetchTVs = $this->pdo->query("SELECT tv_id FROM smart_tvs_tb");
                $tvIds = $stmtFetchTVs->fetchAll(PDO::FETCH_COLUMN);

                // Insert new container into containers_tb for each TV
                $containerName = ucfirst($featureName);
                $visible = 0;
                $stmtInsertContainer = $this->pdo->prepare("INSERT INTO containers_tb (container_name, type, tv_id, visible) VALUES (?, ?, ?, ?)");
                
                foreach ($tvIds as $tvId) {
                    $stmtInsertContainer->execute([$containerName, $contentType, $tvId, $visible]);
                }

                $response = ['success' => true, 'message' => 'New feature added successfully', 'fileName' => $fileName, 'fetchFileName' => $fetchFileName, 'containerName' => $containerName];
                echo "New feature '{$featureName}' added successfully with file name: {$fileName} and fetch file: {$fetchFileName} and container name: {$containerName}!\n";
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

    private function generateFetchFileContent($tableName, $contentType) {
        return "<?php
// fetch_{$contentType}.php
// Database Connection
\$pdo = new PDO(\"mysql:host=localhost;dbname=smart_tv_cms_db\", \"root\", \"\");

// Fetch {$tableName} from the database
\$statement = \$pdo->query(\"SELECT * FROM {$tableName}\");
\${$contentType} = \$statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode(\${$contentType});
";
    }

    private function createFeaturePhpFile($featureName, $inputs, $icon, $hasExpiration) {
        $fileName = 'form_' . strtolower(str_replace(' ', '_', $featureName)) . '.php';
        $filePath = dirname(__DIR__, 1) . '/' . $fileName; // This goes up two levels from MyApp
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
        // Generate the PHP content here. This is a basic template, you'll need to adjust it based on your specific requirements.
        $content = 
"<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';
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
    <title>Create an $featureName</title>
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
                                <li class=\"breadcrumb-item\"><a href=\"create_post.php?pageid=CreatePost?userId=<?php echo \$user_id; ?>''<?php echo \$full_name; ?>\" style=\"color: #264B2B\">Create Post</a></li>
                                <li class=\"breadcrumb-item active\" aria-current=\"page\">$featureName Form</li>
                            </ol>
                        </nav>
                        <form id=\"" . strtolower(str_replace(' ', '_', $featureName)) . "Form\" enctype=\"multipart/form-data\" class=\"main-form\">
                            <?php include('error_message.php'); ?>
                            <input type=\"hidden\" name=\"type\" value=\"" . strtolower(str_replace(' ', '_', $featureName)) . "\">
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
                                    ";
                                    $quillContentAssignment .= "
                                        document.getElementById('{$inputName}HiddenInput').value = {$inputName}Quill.root.innerHTML;
                                    ";
                                } elseif ($input['type'] == 'image') {
                                    $hasImageInput = true;
                                }
                            }
                        
                            if ($hasImageInput) {
                                $content .= "<?php include('misc/php/upload_preview_media.php')?>\n";
                            }
                        
                            $content .= "
                            <div class=\"form-row\">
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
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src=\"misc/js/capitalize_first_letter.js\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js\"></script>
    <script src=\"misc/js/quill_textarea_submission.js\"></script>
    <script src=\"new_features/newfeature_wsform_submission.js\"></script>
    <script>
        const containers = <?php echo json_encode(\$containers); ?>;
        const tvNames = <?php echo json_encode(\$tv_names); ?>; 
        const userType = '<?php echo \$user_type; ?>';

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
                </div>";
        } else {
            return "
                <div class=\"floating-label-container\">
                    <input type=\"{$input['type']}\" name=\"{$name}\" id=\"{$name}\" {$required} placeholder=\" \" style=\"background: #FFFF; width: 100%\" class=\"floating-label-input\">
                    <label for=\"{$name}\" class=\"floating-label\">{$label}</label>
                </div>";
        }
    }

    // Helper function to broadcast notifications to all clients
    private function broadcastNotification($action) {
        foreach ($this->clients as $client) {
            $client->send(json_encode(['action' => $action]));
        }
    }

    private function saveLayoutToDatabase($tv_id, $layout) {
        $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
    
        // Prepare the SQL statement for updating
        $stmt = $pdo->prepare("
            UPDATE containers_tb
            SET position_order = ?, xaxis = ?, yaxis = ?
            WHERE tv_id = ? AND container_id = ?
        ");

        foreach ($layout as $index => $item) {
            // Execute the update for each container
            $stmt->execute([$index + 1, $item['x'], $item['y'], $tv_id, $item['id']]);
        }
    }
    
    private function loadLayoutFromDatabase($tv_id) {
        $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
        
        $stmt = $pdo->prepare("SELECT container_id FROM containers_tb WHERE tv_id = ? ORDER BY position_order ASC");
        $stmt->execute([$tv_id]);
    
        $layout = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $layout[] = [
                'id' => $row['container_id'],
            ];
        }
    
        // Debug: Check if the layout is populated correctly
        // if (empty($layout)) {
        //     error_log("No layout data found for TV ID: " . $tv_id);
        // } else {
        //     error_log("Layout data found for TV ID: " . $tv_id . ": " . json_encode($layout));
        // }
    
        return $layout;
    }    
}